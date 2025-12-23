"""
Advanced Board Exam Passing Rate Predictor for CCJE Department
Uses anonymous board passer data to train 7 different ML algorithms
Port: 5001 (separate from Engineering - port 5000, CBAA - port 5002)
"""

import mysql.connector
from mysql.connector import Error
import pandas as pd
import numpy as np
from sklearn.model_selection import train_test_split, cross_val_score
from sklearn.preprocessing import StandardScaler
from sklearn.linear_model import LinearRegression, Ridge, Lasso
from sklearn.ensemble import RandomForestRegressor, GradientBoostingRegressor
from sklearn.svm import SVR
from sklearn.tree import DecisionTreeRegressor
from sklearn.metrics import mean_absolute_error, mean_squared_error, r2_score
import joblib
import json
from datetime import datetime
import matplotlib
matplotlib.use('Agg')
import matplotlib.pyplot as plt
import seaborn as sns
import warnings
warnings.filterwarnings('ignore')

class CCJEBoardExamPredictor:
    def __init__(self):
        self.db_config = {
            'host': 'localhost',
            'database': 'project_db',
            'user': 'root',
            'password': ''
        }
        self.models = {}
        self.scaler = StandardScaler()
        self.feature_columns = []
        self.department = 'Criminal Justice Education'
        self.model_dir = 'models'
        self.graph_dir = 'graphs'
        
        # 7 Algorithms for comparison
        self.algorithms = {
            'Linear Regression': LinearRegression(),
            'Ridge Regression': Ridge(alpha=1.0),
            'Lasso Regression': Lasso(alpha=0.1),
            'Random Forest': RandomForestRegressor(n_estimators=100, random_state=42),
            'Gradient Boosting': GradientBoostingRegressor(n_estimators=100, random_state=42),
            'Support Vector Machine': SVR(kernel='rbf'),
            'Decision Tree': DecisionTreeRegressor(random_state=42)
        }
        
        self.metrics = {}
        self.best_model_name = None
        self.training_data = None
        
    def connect_db(self):
        """Connect to MySQL database"""
        try:
            connection = mysql.connector.connect(**self.db_config)
            if connection.is_connected():
                return connection
        except Error as e:
            print(f"Error connecting to database: {e}")
            return None
    
    def fetch_ccje_anonymous_data(self):
        """Fetch CCJE anonymous board passer data"""
        connection = self.connect_db()
        if not connection:
            return None
        
        try:
            query = """
                SELECT 
                    board_exam_type,
                    YEAR(board_exam_date) as exam_year,
                    COUNT(*) as total_takers,
                    SUM(CASE WHEN result = 'Passed' THEN 1 ELSE 0 END) as total_passers,
                    (SUM(CASE WHEN result = 'Passed' THEN 1 ELSE 0 END) / COUNT(*)) * 100 as passing_rate,
                    MONTH(board_exam_date) as exam_month,
                    DAY(board_exam_date) as exam_day
                FROM anonymous_board_passers
                WHERE (is_deleted IS NULL OR is_deleted = 0)
                AND department = 'Criminal Justice Education'
                GROUP BY board_exam_type, YEAR(board_exam_date), MONTH(board_exam_date), DAY(board_exam_date)
                ORDER BY exam_year, board_exam_type
            """
            
            df = pd.read_sql(query, connection)
            print(f"✅ Fetched {len(df)} CCJE anonymous records")
            return df
            
        except Error as e:
            print(f"Error fetching data: {e}")
            return None
        finally:
            if connection.is_connected():
                connection.close()
    
    def prepare_features(self, df):
        """Prepare features for machine learning"""
        # Create features
        df['year_numeric'] = df['exam_year']
        df['takers_scaled'] = df['total_takers'] / 100
        df['passers_ratio'] = df['total_passers'] / df['total_takers']
        
        # Encode exam month if available
        if 'exam_month' in df.columns and df['exam_month'].notna().any():
            df['exam_month_num'] = df['exam_month'].fillna(6)  # Default to June
        else:
            df['exam_month_num'] = 6  # Default to June (typical Criminology exam month)
        
        # Encode board exam type
        exam_types = df['board_exam_type'].unique()
        for exam_type in exam_types:
            safe_name = exam_type.replace(" ", "_").replace("(", "").replace(")", "").replace("-", "_")
            df[f'is_{safe_name}'] = (df['board_exam_type'] == exam_type).astype(int)
        
        # Historical rolling averages
        df = df.sort_values(['board_exam_type', 'exam_year'])
        df['passing_rate_lag1'] = df.groupby('board_exam_type')['passing_rate'].shift(1)
        df['passing_rate_lag2'] = df.groupby('board_exam_type')['passing_rate'].shift(2)
        df['passing_rate_ma3'] = df.groupby('board_exam_type')['passing_rate'].rolling(3).mean().reset_index(0, drop=True)
        
        # Fill NaN values
        df = df.fillna(df.mean(numeric_only=True))
        
        return df
    
    def train_all_models(self):
        """Train all 7 algorithms and compare performance"""
        print("\n🤖 Starting CCJE Board Exam Prediction Training...")
        print("=" * 70)
        
        # Fetch data
        df = self.fetch_ccje_anonymous_data()
        if df is None or len(df) == 0:
            print("❌ No CCJE data available for training")
            return False
        
        self.training_data = df.copy()
        
        # Prepare features
        df = self.prepare_features(df)
        
        # Define features
        exclude_cols = ['board_exam_type', 'exam_year', 'passing_rate', 'total_takers', 
                       'total_passers', 'exam_month', 'exam_day']
        self.feature_columns = [col for col in df.columns if col not in exclude_cols]
        
        X = df[self.feature_columns]
        y = df['passing_rate']
        
        print(f"📊 Training on {len(df)} records with {len(self.feature_columns)} features")
        print(f"📋 Features: {', '.join(self.feature_columns[:5])}...")
        
        # Split data (80% training, 20% testing)
        X_train, X_test, y_train, y_test = train_test_split(
            X, y, test_size=0.2, random_state=42
        )
        
        print(f"📈 Training set: {len(X_train)} records")
        print(f"📉 Testing set: {len(X_test)} records")
        
        # Scale features
        X_train_scaled = self.scaler.fit_transform(X_train)
        X_test_scaled = self.scaler.transform(X_test)
        
        # Train all algorithms
        best_r2 = -np.inf
        
        for name, model in self.algorithms.items():
            print(f"\n🔧 Training {name}...")
            
            # Train model
            model.fit(X_train_scaled, y_train)
            
            # Predictions
            y_pred_train = model.predict(X_train_scaled)
            y_pred_test = model.predict(X_test_scaled)
            
            # Metrics
            train_r2 = r2_score(y_train, y_pred_train)
            test_r2 = r2_score(y_test, y_pred_test)
            train_mae = mean_absolute_error(y_train, y_pred_train)
            test_mae = mean_absolute_error(y_test, y_pred_test)
            train_mse = mean_squared_error(y_train, y_pred_train)
            test_mse = mean_squared_error(y_test, y_pred_test)
            train_rmse = np.sqrt(train_mse)
            test_rmse = np.sqrt(test_mse)
            
            # Cross-validation (adjust cv based on sample size)
            n_samples = len(X_train_scaled)
            cv_folds = min(3, n_samples) if n_samples < 5 else 5
            
            if cv_folds >= 2:
                cv_scores = cross_val_score(model, X_train_scaled, y_train, cv=cv_folds, 
                                           scoring='r2')
                cv_mean = cv_scores.mean()
                cv_std = cv_scores.std()
            else:
                cv_mean = test_r2
                cv_std = 0.0
            
            # Calculate accuracy percentage (based on how close predictions are to actual)
            accuracy = max(0, 100 - test_mae)  # Simplified accuracy metric
            
            # Calculate precision and recall approximation for regression
            # Using a threshold-based approach
            threshold = 5.0  # Within 5 percentage points
            within_threshold = np.abs(y_pred_test - y_test) <= threshold
            precision = np.sum(within_threshold) / len(y_test) * 100 if len(y_test) > 0 else 0
            
            # Store metrics
            self.metrics[name] = {
                'name': name,
                'train_r2': float(train_r2),
                'test_r2': float(test_r2),
                'train_mae': float(train_mae),
                'test_mae': float(test_mae),
                'train_mse': float(train_mse),
                'test_mse': float(test_mse),
                'train_rmse': float(train_rmse),
                'test_rmse': float(test_rmse),
                'cv_mean': float(cv_mean),
                'cv_std': float(cv_std),
                'accuracy': float(accuracy),
                'precision': float(precision)
            }
            
            # Save model
            self.models[name] = model
            
            # Track best model
            if test_r2 > best_r2:
                best_r2 = test_r2
                self.best_model_name = name
            
            print(f"   ✓ Test R² Score: {test_r2:.4f}")
            print(f"   ✓ Test MAE: {test_mae:.2f}%")
            print(f"   ✓ Test RMSE: {test_rmse:.2f}%")
            print(f"   ✓ Accuracy: {accuracy:.2f}%")
        
        print(f"\n🏆 Best Model: {self.best_model_name} (R² = {best_r2:.4f})")
        
        # Save models and metadata
        self.save_models()
        self.generate_visualizations(X_test_scaled, y_test)
        
        return True
    
    def save_models(self):
        """Save all trained models and metadata"""
        import os
        os.makedirs(self.model_dir, exist_ok=True)
        
        # Save each model
        for name, model in self.models.items():
            model_filename = f"{self.model_dir}/{name.replace(' ', '_').lower()}_model.pkl"
            joblib.dump(model, model_filename)
        
        # Save scaler
        joblib.dump(self.scaler, f"{self.model_dir}/scaler.pkl")
        
        # Save metadata
        metadata = {
            'department': self.department,
            'training_date': datetime.now().isoformat(),
            'best_model': self.best_model_name,
            'feature_names': self.feature_columns,
            'num_features': len(self.feature_columns),
            'training_records': len(self.training_data) if self.training_data is not None else 0,
            'metrics': self.metrics
        }
        
        with open(f"{self.model_dir}/metadata.json", 'w') as f:
            json.dump(metadata, f, indent=2)
        
        print(f"✅ Models saved to {self.model_dir}/")
    
    def load_models(self):
        """Load trained models"""
        import os
        
        if not os.path.exists(f"{self.model_dir}/metadata.json"):
            return False
        
        try:
            # Load metadata
            with open(f"{self.model_dir}/metadata.json", 'r') as f:
                metadata = json.load(f)
            
            self.best_model_name = metadata.get('best_model', 'Lasso Regression')
            self.feature_columns = metadata.get('feature_names', [])
            self.metrics = metadata.get('metrics', {})
            
            # Load scaler
            self.scaler = joblib.load(f"{self.model_dir}/scaler.pkl")
            
            # Load best model
            model_filename = f"{self.model_dir}/{self.best_model_name.replace(' ', '_').lower()}_model.pkl"
            if os.path.exists(model_filename):
                self.models[self.best_model_name] = joblib.load(model_filename)
                return True
            
            return False
        except Exception as e:
            print(f"Error loading models: {e}")
            return False
    
    def predict_next_year(self):
        """Generate predictions for next year"""
        # Load models if not loaded
        if not self.models:
            if not self.load_models():
                return None
        
        # Fetch current data
        df = self.fetch_ccje_anonymous_data()
        if df is None or len(df) == 0:
            return None
        
        # Prepare features
        df = self.prepare_features(df)
        
        # Get unique exam types
        exam_types = df['board_exam_type'].unique()
        
        predictions = []
        current_year = datetime.now().year
        prediction_year = current_year + 1
        
        best_model = self.models.get(self.best_model_name)
        if not best_model:
            return None
        
        for exam_type in exam_types:
            exam_data = df[df['board_exam_type'] == exam_type].copy()
            
            if len(exam_data) == 0:
                continue
            
            # Get latest record as base for prediction
            latest = exam_data.loc[exam_data['exam_year'].idxmax()].copy()
            
            # Create prediction input
            pred_input = {}
            for col in self.feature_columns:
                if col in exam_data.columns:
                    pred_input[col] = [latest[col]]
                else:
                    pred_input[col] = [0]
            
            # Update year for prediction
            if 'year_numeric' in pred_input:
                pred_input['year_numeric'] = [prediction_year]
            
            # Create DataFrame and scale
            X_pred = pd.DataFrame(pred_input)
            X_pred_scaled = self.scaler.transform(X_pred)
            
            # Predict
            predicted_rate = best_model.predict(X_pred_scaled)[0]
            predicted_rate = max(0, min(100, predicted_rate))  # Bound to 0-100
            
            predictions.append({
                'board_exam_type': exam_type,
                'exam_type': exam_type,
                'predicted_passing_rate': round(predicted_rate, 2),
                'predicted_year': prediction_year,
                'model_used': self.best_model_name
            })
        
        return predictions
    
    def backtest(self, test_year=2023, train_until_year=2022):
        """Validate model by backtesting on historical data
        
        This trains on data from years <= train_until_year and predicts test_year,
        then compares with actual results to validate model accuracy.
        """
        print(f"\n🔬 Backtesting: Training on data until {train_until_year}, predicting {test_year}...")
        
        # Fetch all data
        df = self.fetch_ccje_anonymous_data()
        if df is None or len(df) == 0:
            print("❌ No data available")
            return None
        
        print(f"📊 Available years: {sorted(df['exam_year'].unique())}")
        
        # Split by year
        train_df = df[df['exam_year'] <= train_until_year].copy()
        test_df = df[df['exam_year'] == test_year].copy()
        
        print(f"📈 Training data: {len(train_df)} records, Test data: {len(test_df)} records")
        
        if len(train_df) == 0:
            print(f"❌ No training data for years <= {train_until_year}")
            return None
            
        if len(test_df) == 0:
            print(f"❌ No test data for year {test_year}")
            return None
        
        # Use simple features that don't require lag values
        # Create basic features from training data
        train_features = []
        train_targets = []
        
        for _, row in train_df.iterrows():
            features = {
                'year_numeric': row['exam_year'],
                'total_takers': row.get('total_takers', 0) or 0,
                'total_passers': row.get('total_passers', 0) or 0,
            }
            train_features.append(features)
            
            # Calculate passing rate
            if row.get('total_takers', 0) and row['total_takers'] > 0:
                passing_rate = (row.get('total_passers', 0) / row['total_takers']) * 100
            else:
                passing_rate = row.get('passing_rate', 50)
            train_targets.append(passing_rate)
        
        if len(train_features) < 1:
            print("❌ Insufficient training data")
            return None
        
        # Create DataFrame
        X_train = pd.DataFrame(train_features)
        y_train = np.array(train_targets)
        
        # Handle any NaN values
        X_train = X_train.fillna(0)
        
        # Scale features
        scaler = StandardScaler()
        X_train_scaled = scaler.fit_transform(X_train)
        
        # Train model (use Ridge for stability with small datasets)
        from sklearn.linear_model import Ridge
        model = Ridge(alpha=1.0)
        model.fit(X_train_scaled, y_train)
        
        # Now predict for test year
        results = []
        total_error = 0
        count = 0
        
        for _, row in test_df.iterrows():
            # Get actual passing rate
            if row.get('total_takers', 0) and row['total_takers'] > 0:
                actual_rate = (row.get('total_passers', 0) / row['total_takers']) * 100
            else:
                actual_rate = row.get('passing_rate', 50)
            
            # Create test features
            test_features = {
                'year_numeric': row['exam_year'],
                'total_takers': row.get('total_takers', 0) or 0,
                'total_passers': row.get('total_passers', 0) or 0,
            }
            
            X_test = pd.DataFrame([test_features])
            X_test = X_test.fillna(0)
            X_test_scaled = scaler.transform(X_test)
            
            # Predict
            predicted_rate = model.predict(X_test_scaled)[0]
            predicted_rate = max(0, min(100, predicted_rate))
            
            error = abs(predicted_rate - actual_rate)
            total_error += error
            count += 1
            
            exam_type = row.get('board_exam_type', row.get('exam_type', 'Criminology Licensure Exam'))
            
            results.append({
                'exam_type': exam_type,
                'actual': round(actual_rate, 2),
                'predicted': round(predicted_rate, 2),
                'error': round(error, 2)
            })
            
            print(f"   {exam_type}: Actual={actual_rate:.2f}%, Predicted={predicted_rate:.2f}%, Error={error:.2f}%")
        
        if count == 0:
            print("❌ No predictions could be made")
            return None
        
        mae = total_error / count
        accuracy = max(0, 100 - mae)
        
        print(f"\n✅ Backtesting complete: MAE={mae:.2f}%, Accuracy={accuracy:.1f}%")
        
        return {
            'test_year': test_year,
            'trained_until': train_until_year,
            'predictions': results,
            'mae': round(mae, 2),
            'accuracy': round(accuracy, 1)
        }
    
    def generate_visualizations(self, X_test_scaled, y_test):
        """Generate visualization graphs"""
        import os
        os.makedirs(self.graph_dir, exist_ok=True)
        
        # Set style
        plt.style.use('seaborn-v0_8-whitegrid')
        colors_red = ['#D32F2F', '#C62828', '#B71C1C', '#E53935', '#EF5350', '#F44336', '#E57373']
        
        # 1. Model R² Score Comparison
        fig, ax = plt.subplots(figsize=(12, 6))
        models = list(self.metrics.keys())
        r2_scores = [self.metrics[m]['test_r2'] for m in models]
        bars = ax.barh(models, r2_scores, color=colors_red)
        ax.set_xlabel('R² Score', fontsize=12)
        ax.set_title('Model R² Score Comparison - CCJE Department', fontsize=14, fontweight='bold', color='#800020')
        ax.axvline(x=0, color='gray', linestyle='--', alpha=0.5)
        
        # Highlight best model
        best_idx = r2_scores.index(max(r2_scores))
        bars[best_idx].set_color('#800020')
        bars[best_idx].set_edgecolor('#FAD6A5')
        bars[best_idx].set_linewidth(2)
        
        for i, (bar, score) in enumerate(zip(bars, r2_scores)):
            ax.text(score + 0.01, bar.get_y() + bar.get_height()/2, 
                   f'{score:.4f}', va='center', fontsize=10)
        
        plt.tight_layout()
        plt.savefig(f'{self.graph_dir}/model_comparison.png', dpi=150, bbox_inches='tight')
        plt.close()
        
        # 2. Accuracy Comparison
        fig, ax = plt.subplots(figsize=(12, 6))
        accuracies = [self.metrics[m]['accuracy'] for m in models]
        bars = ax.barh(models, accuracies, color=colors_red)
        ax.set_xlabel('Accuracy (%)', fontsize=12)
        ax.set_title('Model Accuracy Comparison - CCJE Department', fontsize=14, fontweight='bold', color='#800020')
        ax.set_xlim(0, 100)
        
        best_idx = accuracies.index(max(accuracies))
        bars[best_idx].set_color('#800020')
        
        for bar, acc in zip(bars, accuracies):
            ax.text(acc + 1, bar.get_y() + bar.get_height()/2, 
                   f'{acc:.1f}%', va='center', fontsize=10)
        
        plt.tight_layout()
        plt.savefig(f'{self.graph_dir}/accuracy_comparison.png', dpi=150, bbox_inches='tight')
        plt.close()
        
        # 3. MAE Comparison
        fig, ax = plt.subplots(figsize=(12, 6))
        maes = [self.metrics[m]['test_mae'] for m in models]
        bars = ax.barh(models, maes, color=colors_red)
        ax.set_xlabel('Mean Absolute Error (%)', fontsize=12)
        ax.set_title('Model MAE Comparison - CCJE Department', fontsize=14, fontweight='bold', color='#800020')
        
        best_idx = maes.index(min(maes))
        bars[best_idx].set_color('#800020')
        
        for bar, mae in zip(bars, maes):
            ax.text(mae + 0.1, bar.get_y() + bar.get_height()/2, 
                   f'{mae:.2f}%', va='center', fontsize=10)
        
        plt.tight_layout()
        plt.savefig(f'{self.graph_dir}/mae_comparison.png', dpi=150, bbox_inches='tight')
        plt.close()
        
        # 4. Predictions vs Actual
        best_model = self.models.get(self.best_model_name)
        if best_model is not None:
            fig, ax = plt.subplots(figsize=(10, 10))
            y_pred = best_model.predict(X_test_scaled)
            
            ax.scatter(y_test, y_pred, alpha=0.7, c='#D32F2F', s=100, edgecolors='#800020')
            
            # Perfect prediction line
            min_val = min(min(y_test), min(y_pred))
            max_val = max(max(y_test), max(y_pred))
            ax.plot([min_val, max_val], [min_val, max_val], 'k--', lw=2, label='Perfect Prediction')
            
            ax.set_xlabel('Actual Passing Rate (%)', fontsize=12)
            ax.set_ylabel('Predicted Passing Rate (%)', fontsize=12)
            ax.set_title(f'Predictions vs Actual ({self.best_model_name})\nCCJE Department', 
                        fontsize=14, fontweight='bold', color='#800020')
            ax.legend()
            
            plt.tight_layout()
            plt.savefig(f'{self.graph_dir}/predictions_vs_actual.png', dpi=150, bbox_inches='tight')
            plt.close()
        
        # 5. Residual Analysis
        if best_model is not None:
            fig, axes = plt.subplots(1, 2, figsize=(14, 5))
            
            y_pred = best_model.predict(X_test_scaled)
            residuals = y_test.values - y_pred
            
            # Residual distribution
            axes[0].hist(residuals, bins=15, color='#D32F2F', edgecolor='#800020', alpha=0.7)
            axes[0].axvline(x=0, color='black', linestyle='--', lw=2)
            axes[0].set_xlabel('Residual (Actual - Predicted)', fontsize=11)
            axes[0].set_ylabel('Frequency', fontsize=11)
            axes[0].set_title('Residual Distribution', fontsize=12, fontweight='bold', color='#800020')
            
            # Residual vs Predicted
            axes[1].scatter(y_pred, residuals, alpha=0.7, c='#D32F2F', s=80, edgecolors='#800020')
            axes[1].axhline(y=0, color='black', linestyle='--', lw=2)
            axes[1].set_xlabel('Predicted Passing Rate (%)', fontsize=11)
            axes[1].set_ylabel('Residual', fontsize=11)
            axes[1].set_title('Residuals vs Predicted Values', fontsize=12, fontweight='bold', color='#800020')
            
            plt.tight_layout()
            plt.savefig(f'{self.graph_dir}/residual_analysis.png', dpi=150, bbox_inches='tight')
            plt.close()
        
        # 6. Historical Trends
        df = self.fetch_ccje_anonymous_data()
        if df is not None and len(df) > 0:
            fig, ax = plt.subplots(figsize=(12, 6))
            
            for exam_type in df['board_exam_type'].unique():
                exam_data = df[df['board_exam_type'] == exam_type].sort_values('exam_year')
                ax.plot(exam_data['exam_year'], exam_data['passing_rate'], 
                       marker='o', linewidth=2, markersize=8, label=exam_type[:30])
            
            ax.set_xlabel('Year', fontsize=12)
            ax.set_ylabel('Passing Rate (%)', fontsize=12)
            ax.set_title('Historical Passing Rate Trends - CCJE Department', 
                        fontsize=14, fontweight='bold', color='#800020')
            ax.legend(loc='best', fontsize=9)
            ax.grid(True, alpha=0.3)
            
            plt.tight_layout()
            plt.savefig(f'{self.graph_dir}/historical_trends.png', dpi=150, bbox_inches='tight')
            plt.close()
        
        print(f"✅ Visualizations saved to {self.graph_dir}/")


if __name__ == "__main__":
    print("=" * 70)
    print("🎓 CCJE Board Exam Prediction System - Training Module")
    print("=" * 70)
    
    predictor = CCJEBoardExamPredictor()
    success = predictor.train_all_models()
    
    if success:
        print("\n" + "=" * 70)
        print("✅ Training completed successfully!")
        print("=" * 70)
        
        # Run backtest
        backtest_results = predictor.backtest(2023, 2022)
        if backtest_results:
            print(f"\n📊 Backtest Results:")
            print(f"   Test Year: {backtest_results['test_year']}")
            print(f"   Accuracy: {backtest_results['accuracy']}%")
            print(f"   MAE: {backtest_results['mae']}%")
        
        # Generate predictions
        predictions = predictor.predict_next_year()
        if predictions:
            print(f"\n🔮 Predictions for {datetime.now().year + 1}:")
            for pred in predictions:
                print(f"   • {pred['board_exam_type']}: {pred['predicted_passing_rate']}%")
    else:
        print("\n❌ Training failed. Please check the data.")
