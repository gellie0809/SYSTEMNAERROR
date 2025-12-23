"""
Advanced Board Exam Passing Rate Predictor for CTE Department
Uses anonymous board passer data to train 7 different ML algorithms
Port: 5003 (separate from Engineering - port 5000, CCJE - port 5001, CBAA - port 5002)
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

class CTEBoardExamPredictor:
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
        self.department = 'Teacher Education'
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
    
    def fetch_cte_anonymous_data(self):
        """Fetch CTE anonymous board passer data"""
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
                AND department = 'Teacher Education'
                GROUP BY board_exam_type, YEAR(board_exam_date), MONTH(board_exam_date), DAY(board_exam_date)
                ORDER BY exam_year, board_exam_type
            """
            
            df = pd.read_sql(query, connection)
            print(f"✅ Fetched {len(df)} CTE anonymous records")
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
            month_mapping = {
                'January': 1, 'February': 2, 'March': 3, 'April': 4,
                'May': 5, 'June': 6, 'July': 7, 'August': 8,
                'September': 9, 'October': 10, 'November': 11, 'December': 12
            }
            df['exam_month_num'] = df['exam_month'].map(month_mapping).fillna(5)
        else:
            df['exam_month_num'] = 5  # Default to May (typical LET month)
        
        # Encode board exam type
        exam_types = df['board_exam_type'].unique()
        for exam_type in exam_types:
            df[f'is_{exam_type.replace(" ", "_").replace("(", "").replace(")", "")}'] = (df['board_exam_type'] == exam_type).astype(int)
        
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
        print("\n🤖 Starting CTE Board Exam Prediction Training...")
        print("=" * 70)
        
        # Fetch data
        df = self.fetch_cte_anonymous_data()
        if df is None or len(df) == 0:
            print("❌ No CTE data available for training")
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
        
        # Split data
        X_train, X_test, y_train, y_test = train_test_split(
            X, y, test_size=0.2, random_state=42
        )
        
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
            train_rmse = np.sqrt(mean_squared_error(y_train, y_pred_train))
            test_rmse = np.sqrt(mean_squared_error(y_test, y_pred_test))
            
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
            
            # Store metrics
            self.metrics[name] = {
                'name': name,
                'train_r2': float(train_r2),
                'test_r2': float(test_r2),
                'train_mae': float(train_mae),
                'test_mae': float(test_mae),
                'train_rmse': float(train_rmse),
                'test_rmse': float(test_rmse),
                'cv_mean': float(cv_mean),
                'cv_std': float(cv_std),
                'accuracy': float(accuracy)
            }
            
            # Save model
            self.models[name] = model
            
            # Track best model
            if test_r2 > best_r2:
                best_r2 = test_r2
                self.best_model_name = name
            
            print(f"   ✓ Test R² Score: {test_r2:.4f}")
            print(f"   ✓ Test MAE: {test_mae:.2f}%")
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
        
        # Save feature columns
        with open(f"{self.model_dir}/features.json", 'w') as f:
            json.dump(self.feature_columns, f)
        
        # Save metadata
        metadata = {
            'department': self.department,
            'training_date': datetime.now().isoformat(),
            'training_records': len(self.training_data) if hasattr(self, 'training_data') else 0,
            'best_model': self.best_model_name,
            'metrics': self.metrics,
            'num_features': len(self.feature_columns),
            'feature_names': self.feature_columns
        }
        
        with open(f"{self.model_dir}/metadata.json", 'w') as f:
            json.dump(metadata, f, indent=2)
        
        print(f"\n💾 All models saved to {self.model_dir}/")
    
    def generate_visualizations(self, X_test, y_test):
        """Generate comprehensive visualization graphs"""
        import os
        os.makedirs(self.graph_dir, exist_ok=True)
        
        print("\n📊 Generating visualization graphs...")
        
        # Set style
        sns.set_style("whitegrid")
        plt.rcParams['figure.figsize'] = (12, 8)
        
        # CTE Blue color scheme
        primary_color = '#4663ac'  # Blue
        secondary_color = '#c1d8f0'  # Light blue
        accent_color = '#c8d9ed'  # Lighter blue
        
        # 1. Model Comparison - R² Scores (only show positive R² models)
        fig, ax = plt.subplots(figsize=(12, 6))
        models = list(self.metrics.keys())
        r2_scores = [max(0, self.metrics[m]['test_r2']) for m in models]  # Clamp negative to 0
        original_r2 = [self.metrics[m]['test_r2'] for m in models]
        colors = [primary_color if m == self.best_model_name else secondary_color for m in models]
        
        bars = ax.barh(models, r2_scores, color=colors, edgecolor='#4663ac', linewidth=2)
        ax.set_xlabel('R² Score', fontsize=12, fontweight='bold')
        ax.set_title('CTE Model Comparison - R² Scores', fontsize=14, fontweight='bold', pad=20)
        ax.set_xlim(0, 1.1)
        
        # Add value labels (show original values)
        for i, (model, score, orig) in enumerate(zip(models, r2_scores, original_r2)):
            label = f'{orig:.4f}' if orig >= 0 else f'{orig:.4f} (poor fit)'
            color = '#4663ac' if orig > 0.8 else '#F9A825' if orig > 0 else '#D32F2F'
            x_pos = max(score, 0.01)
            ax.text(x_pos + 0.02, i, label, va='center', fontweight='bold', fontsize=10, color=color)
        
        plt.tight_layout()
        plt.savefig(f'{self.graph_dir}/model_comparison.png', dpi=300, bbox_inches='tight')
        plt.close()
        
        # 2. Accuracy Comparison
        fig, ax = plt.subplots(figsize=(12, 6))
        accuracies = [self.metrics[m]['accuracy'] for m in models]
        bars = ax.bar(models, accuracies, color=secondary_color, edgecolor='#4663ac', linewidth=2)
        ax.set_ylabel('Accuracy (%)', fontsize=12, fontweight='bold')
        ax.set_title('CTE Model Accuracy Comparison', fontsize=14, fontweight='bold', pad=20)
        ax.set_ylim(0, 100)
        plt.xticks(rotation=45, ha='right')
        
        # Add value labels
        for bar in bars:
            height = bar.get_height()
            ax.text(bar.get_x() + bar.get_width()/2., height,
                   f'{height:.1f}%', ha='center', va='bottom', fontweight='bold')
        
        plt.tight_layout()
        plt.savefig(f'{self.graph_dir}/accuracy_comparison.png', dpi=300, bbox_inches='tight')
        plt.close()
        
        # 3. MAE Comparison
        fig, ax = plt.subplots(figsize=(12, 6))
        mae_scores = [self.metrics[m]['test_mae'] for m in models]
        bars = ax.bar(models, mae_scores, color=primary_color, edgecolor='#4663ac', linewidth=2)
        ax.set_ylabel('Mean Absolute Error (%)', fontsize=12, fontweight='bold')
        ax.set_title('CTE Model Error Comparison (Lower is Better)', fontsize=14, fontweight='bold', pad=20)
        plt.xticks(rotation=45, ha='right')
        
        # Add value labels
        for bar in bars:
            height = bar.get_height()
            ax.text(bar.get_x() + bar.get_width()/2., height,
                   f'{height:.2f}%', ha='center', va='bottom', fontweight='bold')
        
        plt.tight_layout()
        plt.savefig(f'{self.graph_dir}/mae_comparison.png', dpi=300, bbox_inches='tight')
        plt.close()
        
        # 4. Predictions vs Actual (Best Model)
        best_model = self.models[self.best_model_name]
        y_pred = best_model.predict(X_test)
        
        fig, ax = plt.subplots(figsize=(10, 10))
        ax.scatter(y_test, y_pred, alpha=0.6, s=100, color=secondary_color, edgecolors='#4663ac', linewidth=2)
        
        # Perfect prediction line
        min_val = min(y_test.min(), y_pred.min())
        max_val = max(y_test.max(), y_pred.max())
        ax.plot([min_val, max_val], [min_val, max_val], 'r--', lw=2, label='Perfect Prediction')
        
        ax.set_xlabel('Actual Passing Rate (%)', fontsize=12, fontweight='bold')
        ax.set_ylabel('Predicted Passing Rate (%)', fontsize=12, fontweight='bold')
        ax.set_title(f'CTE Predictions vs Actual - {self.best_model_name}', 
                    fontsize=14, fontweight='bold', pad=20)
        ax.legend(fontsize=10)
        ax.grid(True, alpha=0.3)
        
        plt.tight_layout()
        plt.savefig(f'{self.graph_dir}/predictions_vs_actual.png', dpi=300, bbox_inches='tight')
        plt.close()
        
        # 5. Residual Analysis (Best Model)
        fig, axes = plt.subplots(1, 2, figsize=(14, 6))
        
        # Residuals
        residuals = y_test - y_pred
        
        # Residual distribution
        axes[0].hist(residuals, bins=15, color=secondary_color, edgecolor='#4663ac', linewidth=2, alpha=0.7)
        axes[0].axvline(x=0, color='red', linestyle='--', linewidth=2, label='Zero Error')
        axes[0].set_xlabel('Residual (Actual - Predicted)', fontsize=11, fontweight='bold')
        axes[0].set_ylabel('Frequency', fontsize=11, fontweight='bold')
        axes[0].set_title('Residual Distribution', fontsize=12, fontweight='bold')
        axes[0].legend()
        axes[0].grid(True, alpha=0.3)
        
        # Residuals vs Predicted
        axes[1].scatter(y_pred, residuals, alpha=0.6, s=100, color=primary_color, edgecolors='#4663ac', linewidth=2)
        axes[1].axhline(y=0, color='red', linestyle='--', linewidth=2, label='Zero Error Line')
        axes[1].set_xlabel('Predicted Passing Rate (%)', fontsize=11, fontweight='bold')
        axes[1].set_ylabel('Residual (Actual - Predicted)', fontsize=11, fontweight='bold')
        axes[1].set_title('Residuals vs Predicted Values', fontsize=12, fontweight='bold')
        axes[1].legend()
        axes[1].grid(True, alpha=0.3)
        
        plt.suptitle(f'CTE Residual Analysis - {self.best_model_name}', fontsize=14, fontweight='bold', y=1.02)
        plt.tight_layout()
        plt.savefig(f'{self.graph_dir}/residual_analysis.png', dpi=300, bbox_inches='tight')
        plt.close()
        
        # 6. Training History (if available)
        if self.training_data is not None and len(self.training_data) > 0:
            fig, ax = plt.subplots(figsize=(14, 7))
            
            for exam_type in self.training_data['board_exam_type'].unique():
                data = self.training_data[self.training_data['board_exam_type'] == exam_type]
                ax.plot(data['exam_year'], data['passing_rate'], 
                       marker='o', linewidth=2, markersize=8, label=exam_type)
            
            ax.set_xlabel('Year', fontsize=12, fontweight='bold')
            ax.set_ylabel('Passing Rate (%)', fontsize=12, fontweight='bold')
            ax.set_title('CTE Historical Passing Rate Trends', fontsize=14, fontweight='bold', pad=20)
            ax.legend(fontsize=9, loc='best')
            ax.grid(True, alpha=0.3)
            
            plt.tight_layout()
            plt.savefig(f'{self.graph_dir}/historical_trends.png', dpi=300, bbox_inches='tight')
            plt.close()
        
        print(f"   ✓ Saved model_comparison.png")
        print(f"   ✓ Saved accuracy_comparison.png")
        print(f"   ✓ Saved mae_comparison.png")
        print(f"   ✓ Saved predictions_vs_actual.png")
        print(f"   ✓ Saved residual_analysis.png")
        print(f"   ✓ Saved historical_trends.png")
    
    def predict_next_year(self):
        """Predict passing rates for next year"""
        # Load best model
        if not self.best_model_name:
            # Try to load from metadata
            try:
                with open(f"{self.model_dir}/metadata.json", 'r') as f:
                    metadata = json.load(f)
                    self.best_model_name = metadata['best_model']
            except:
                return None
        
        model_file = f"{self.model_dir}/{self.best_model_name.replace(' ', '_').lower()}_model.pkl"
        scaler_file = f"{self.model_dir}/scaler.pkl"
        features_file = f"{self.model_dir}/features.json"
        
        try:
            model = joblib.load(model_file)
            scaler = joblib.load(scaler_file)
            with open(features_file, 'r') as f:
                features = json.load(f)
        except:
            return None
        
        # Get latest data
        df = self.fetch_cte_anonymous_data()
        if df is None or len(df) == 0:
            return None
        
        # Prepare features
        df = self.prepare_features(df)
        
        # Get latest year and predict next year
        latest_year = df['exam_year'].max()
        next_year = latest_year + 1
        
        predictions = []
        
        for exam_type in df['board_exam_type'].unique():
            exam_data = df[df['board_exam_type'] == exam_type].iloc[-1:]
            
            # Create next year features
            next_year_features = exam_data[features].copy()
            next_year_features['year_numeric'] = next_year
            
            # Scale and predict
            X_scaled = scaler.transform(next_year_features)
            predicted_rate = model.predict(X_scaled)[0]
            
            # Ensure prediction is within valid range
            predicted_rate = max(0, min(100, predicted_rate))
            
            predictions.append({
                'exam_type': exam_type,
                'predicted_year': int(next_year),
                'predicted_passing_rate': float(round(predicted_rate, 2)),
                'model_used': self.best_model_name
            })
        
        return predictions
    
    def backtest(self, test_year=2023, train_until_year=2022):
        """Validate model by predicting a known year"""
        df = self.fetch_cte_anonymous_data()
        if df is None or len(df) == 0:
            return None
        
        # Filter training data up to train_until_year
        train_df = df[df['exam_year'] <= train_until_year].copy()
        test_df = df[df['exam_year'] == test_year].copy()
        
        if len(train_df) == 0 or len(test_df) == 0:
            return {'error': 'Insufficient data for backtesting'}
        
        # Prepare features
        train_df = self.prepare_features(train_df)
        test_df = self.prepare_features(test_df)
        
        # Get feature columns
        exclude_cols = ['board_exam_type', 'exam_year', 'passing_rate', 'total_takers', 
                       'total_passers', 'exam_month', 'exam_day']
        feature_cols = [col for col in train_df.columns if col not in exclude_cols]
        
        # Ensure same features
        for col in feature_cols:
            if col not in test_df.columns:
                test_df[col] = 0
        
        X_train = train_df[feature_cols]
        y_train = train_df['passing_rate']
        X_test = test_df[feature_cols]
        y_test = test_df['passing_rate']
        
        # Train and test
        scaler = StandardScaler()
        X_train_scaled = scaler.fit_transform(X_train)
        X_test_scaled = scaler.transform(X_test)
        
        model = RandomForestRegressor(n_estimators=100, random_state=42)
        model.fit(X_train_scaled, y_train)
        
        y_pred = model.predict(X_test_scaled)
        
        # Calculate metrics
        mae = mean_absolute_error(y_test, y_pred)
        rmse = np.sqrt(mean_squared_error(y_test, y_pred))
        r2 = r2_score(y_test, y_pred)
        accuracy = max(0, 100 - mae)
        
        results = {
            'test_year': test_year,
            'trained_until': train_until_year,
            'mae': float(mae),
            'rmse': float(rmse),
            'r2_score': float(r2),
            'accuracy': float(accuracy),
            'predictions': []
        }
        
        for i, (actual, predicted) in enumerate(zip(y_test, y_pred)):
            results['predictions'].append({
                'exam_type': test_df.iloc[i]['board_exam_type'],
                'actual': float(actual),
                'predicted': float(predicted),
                'error': float(abs(actual - predicted))
            })
        
        return results

if __name__ == "__main__":
    predictor = CTEBoardExamPredictor()
    predictor.train_all_models()
