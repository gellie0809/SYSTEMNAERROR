import mysql.connector
import pandas as pd
import numpy as np
from sklearn.linear_model import LinearRegression, Ridge, Lasso
from sklearn.ensemble import RandomForestRegressor, GradientBoostingRegressor
from sklearn.svm import SVR
from sklearn.model_selection import train_test_split, cross_val_score
from sklearn.metrics import mean_squared_error, r2_score, mean_absolute_error
from sklearn.preprocessing import StandardScaler
import xgboost as xgb
import joblib
import json
from datetime import datetime
import os
import matplotlib
matplotlib.use('Agg')  # Use non-GUI backend
import matplotlib.pyplot as plt
import seaborn as sns
from scipy import stats

def convert_to_python_types(obj):
    """Convert numpy/pandas types to native Python types for JSON serialization"""
    if isinstance(obj, (np.integer, np.int64, np.int32)):
        return int(obj)
    elif isinstance(obj, (np.floating, np.float64, np.float32)):
        return float(obj)
    elif isinstance(obj, np.ndarray):
        return obj.tolist()
    elif isinstance(obj, dict):
        return {key: convert_to_python_types(value) for key, value in obj.items()}
    elif isinstance(obj, list):
        return [convert_to_python_types(item) for item in obj]
    return obj

class AdvancedBoardExamPredictorCAS:
    def __init__(self, db_config):
        self.db_config = db_config
        self.models = {
            'Linear Regression': LinearRegression(),
            'Ridge Regression': Ridge(alpha=1.0),
            'Lasso Regression': Lasso(alpha=0.1),
            'Random Forest': RandomForestRegressor(n_estimators=100, random_state=42),
            'Gradient Boosting': GradientBoostingRegressor(n_estimators=100, random_state=42),
            'XGBoost': xgb.XGBRegressor(n_estimators=100, random_state=42),
            'Support Vector Regression': SVR(kernel='rbf')
        }
        self.best_model = None
        self.best_model_name = None
        self.scaler = StandardScaler()
        self.model_dir = 'models/arts_and_sciences'
        self.output_dir = 'output/arts_and_sciences'
        
        # Create directories
        os.makedirs(self.model_dir, exist_ok=True)
        os.makedirs(self.output_dir, exist_ok=True)
        os.makedirs(os.path.join(self.output_dir, 'graphs'), exist_ok=True)
    
    def fetch_data_from_db(self):
        """Fetch historical data from database for CAS department"""
        try:
            conn = mysql.connector.connect(**self.db_config)
            cursor = conn.cursor(dictionary=True)
            
            query = """
                SELECT 
                    YEAR(board_exam_date) as year,
                    MONTH(board_exam_date) as month,
                    board_exam_type,
                    exam_type as take_attempts,
                    COUNT(*) as total_examinees,
                    SUM(CASE WHEN result = 'Passed' THEN 1 ELSE 0 END) as passed,
                    SUM(CASE WHEN result = 'Failed' THEN 1 ELSE 0 END) as failed,
                    SUM(CASE WHEN result = 'Conditional' THEN 1 ELSE 0 END) as conditional,
                    AVG(CASE WHEN result = 'Passed' THEN 1 ELSE 0 END) as avg_pass_rate
                FROM anonymous_board_passers
                WHERE department = 'Arts and Sciences'
                AND (is_deleted IS NULL OR is_deleted = 0)
                AND board_exam_date IS NOT NULL
                GROUP BY YEAR(board_exam_date), MONTH(board_exam_date), board_exam_type, exam_type
                ORDER BY year ASC, month ASC
            """
            
            cursor.execute(query)
            data = cursor.fetchall()
            cursor.close()
            conn.close()
            
            return pd.DataFrame(data)
            
        except Exception as e:
            print(f"Error fetching CAS data: {e}")
            return None
    
    def prepare_features(self, df):
        """Prepare enhanced features for training"""
        # Convert numeric columns to float to avoid Decimal issues
        numeric_cols = ['passed', 'failed', 'conditional', 'total_examinees', 'avg_pass_rate']
        for col in numeric_cols:
            if col in df.columns:
                df[col] = pd.to_numeric(df[col], errors='coerce')
        
        # Calculate passing rate
        df['passing_rate'] = (df['passed'] / df['total_examinees'] * 100).round(2)
        
        # Create numerical features
        df['first_timer_ratio'] = (df['take_attempts'] == 'First Timer').astype(int) * 100
        df['repeater_ratio'] = (df['take_attempts'] == 'Repeater').astype(int) * 100
        
        # Calculate rates
        df['fail_rate'] = (df['failed'] / df['total_examinees'] * 100).round(2)
        df['conditional_rate'] = (df['conditional'] / df['total_examinees'] * 100).round(2)
        
        # Add time-based features
        df['year_normalized'] = df['year'] - df['year'].min()
        
        # Rolling averages (if enough data)
        if len(df) > 3:
            df = df.sort_values(['board_exam_type', 'year', 'month'])
            df['passing_rate_ma3'] = df.groupby('board_exam_type')['passing_rate'].transform(
                lambda x: x.rolling(window=3, min_periods=1).mean()
            )
        else:
            df['passing_rate_ma3'] = df['passing_rate']
        
        # One-hot encode board exam types
        board_exam_dummies = pd.get_dummies(df['board_exam_type'], prefix='exam')
        df = pd.concat([df, board_exam_dummies], axis=1)
        
        return df
    
    def train_and_compare_models(self):
        """Train all models and compare performance"""
        print("=" * 70)
        print("CAS DEPARTMENT - ADVANCED BOARD EXAM PREDICTION SYSTEM")
        print("Multiple Algorithm Comparison with Confidence Intervals")
        print("=" * 70)
        
        print("\nFetching data from database...")
        df = self.fetch_data_from_db()
        
        if df is None or len(df) == 0:
            print("No CAS data available for training!")
            return None
        
        print(f"Found {len(df)} CAS records")
        
        # Prepare features
        df = self.prepare_features(df)
        
        # Get feature columns
        exam_cols = [col for col in df.columns if col.startswith('exam_')]
        feature_cols = ['year_normalized', 'total_examinees', 'first_timer_ratio', 
                       'repeater_ratio', 'fail_rate', 'conditional_rate', 
                       'passing_rate_ma3'] + exam_cols
        
        X = df[feature_cols]
        y = df['passing_rate']
        
        # Split data
        test_size = 0.2 if len(df) > 10 else 0.1
        X_train, X_test, y_train, y_test = train_test_split(
            X, y, test_size=test_size, random_state=42
        )
        
        # Scale features for some models
        X_train_scaled = self.scaler.fit_transform(X_train)
        X_test_scaled = self.scaler.transform(X_test)
        
        # Store results
        results = []
        
        print("\n" + "=" * 70)
        print("TRAINING AND EVALUATING MODELS")
        print("=" * 70)
        
        for name, model in self.models.items():
            print(f"\n> Training {name}...")
            
            try:
                # Use scaled data for certain models
                if name in ['Support Vector Regression', 'Ridge Regression', 'Lasso Regression']:
                    model.fit(X_train_scaled, y_train)
                    y_pred_train = model.predict(X_train_scaled)
                    y_pred_test = model.predict(X_test_scaled)
                else:
                    model.fit(X_train, y_train)
                    y_pred_train = model.predict(X_train)
                    y_pred_test = model.predict(X_test)
                
                # Calculate metrics
                train_r2 = r2_score(y_train, y_pred_train)
                test_r2 = r2_score(y_test, y_pred_test)
                train_mse = mean_squared_error(y_train, y_pred_train)
                test_mse = mean_squared_error(y_test, y_pred_test)
                train_mae = mean_absolute_error(y_train, y_pred_train)
                test_mae = mean_absolute_error(y_test, y_pred_test)
                
                # Cross-validation score
                if name in ['Support Vector Regression', 'Ridge Regression', 'Lasso Regression']:
                    cv_scores = cross_val_score(model, X_train_scaled, y_train, cv=min(5, len(X_train)), 
                                               scoring='r2')
                else:
                    cv_scores = cross_val_score(model, X_train, y_train, cv=min(5, len(X_train)), 
                                               scoring='r2')
                
                cv_mean = cv_scores.mean()
                cv_std = cv_scores.std()
                
                results.append({
                    'model': name,
                    'train_r2': train_r2,
                    'test_r2': test_r2,
                    'train_mse': train_mse,
                    'test_mse': test_mse,
                    'train_mae': train_mae,
                    'test_mae': test_mae,
                    'cv_mean': cv_mean,
                    'cv_std': cv_std,
                    'model_object': model
                })
                
                print(f"   Test R² Score: {test_r2:.4f}")
                print(f"   Test MAE: {test_mae:.2f}%")
                print(f"   CV Score: {cv_mean:.4f} (+/- {cv_std:.4f})")
                
            except Exception as e:
                print(f"   ❌ Error: {str(e)}")
                continue
        
        # Find best model based on test MAE (lower is better) since R2 may be NaN with small datasets
        results_df = pd.DataFrame(results)
        
        # Try to use R2 score first, fall back to MAE if all R2 are NaN
        if results_df['test_r2'].notna().any():
            best_idx = results_df['test_r2'].idxmax()
        else:
            # Use MAE (lower is better)
            best_idx = results_df['test_mae'].idxmin()
        
        best_result = results[best_idx]
        
        self.best_model = best_result['model_object']
        self.best_model_name = best_result['model']
        
        print("\n" + "=" * 70)
        print("CAS MODEL COMPARISON RESULTS")
        print("=" * 70)
        print(f"\n>> Best Model: {self.best_model_name}")
        print(f"   R² Score: {best_result['test_r2']:.4f}")
        print(f"   MAE: {best_result['test_mae']:.2f}%")
        print(f"   Cross-Validation: {best_result['cv_mean']:.4f} (+/- {best_result['cv_std']:.4f})")
        
        # Save results
        self._save_comparison_results(results_df)
        self._create_comparison_visualizations(results_df, y_test, 
            self.best_model.predict(X_test_scaled if self.best_model_name in 
            ['Support Vector Regression', 'Ridge Regression', 'Lasso Regression'] 
            else X_test))
        
        # Save best model
        model_path = os.path.join(self.model_dir, 'best_model.pkl')
        joblib.dump(self.best_model, model_path)
        joblib.dump(self.scaler, os.path.join(self.model_dir, 'scaler.pkl'))
        
        # Save metadata
        metadata = {
            'department': 'Arts and Sciences',
            'trained_date': datetime.now().isoformat(),
            'best_model': self.best_model_name,
            'features': feature_cols,
            'exam_types': exam_cols,
            'training_records': len(X_train),
            'testing_records': len(X_test),
            'all_models': results_df.drop('model_object', axis=1).to_dict('records'),
            'best_model_metrics': {
                'r2_score': float(best_result['test_r2']) if pd.notna(best_result['test_r2']) else 0.0,
                'mae': float(best_result['test_mae']),
                'mse': float(best_result['test_mse']),
                'cv_mean': float(best_result['cv_mean']) if pd.notna(best_result['cv_mean']) else 0.0,
                'cv_std': float(best_result['cv_std']) if pd.notna(best_result['cv_std']) else 0.0
            }
        }
        
        with open(os.path.join(self.model_dir, 'model_metadata.json'), 'w') as f:
            json.dump(metadata, f, indent=2)
        
        print(f"\n[SUCCESS] CAS Models saved to {self.model_dir}/")
        return metadata
    
    def _save_comparison_results(self, results_df):
        """Save model comparison to CSV"""
        output_path = os.path.join(self.output_dir, 'model_comparison.csv')
        results_df.drop('model_object', axis=1).to_csv(output_path, index=False)
        print(f"\n[INFO] Comparison results saved to {output_path}")
    
    def _create_comparison_visualizations(self, results_df, y_test, y_pred):
        """Create visualization graphs with CAS colors"""
        print("\n[INFO] Generating CAS visualization graphs...")
        
        # Set style
        sns.set_style("whitegrid")
        plt.rcParams['figure.figsize'] = (12, 8)
        
        # CAS color scheme
        cas_primary = '#9f1239'
        cas_secondary = '#4F0024'
        cas_light = '#fecdd3'
        
        # 1. Model Comparison Bar Chart
        fig, axes = plt.subplots(2, 2, figsize=(15, 12))
        
        # R2 Score comparison
        axes[0, 0].barh(results_df['model'], results_df['test_r2'], color=cas_primary)
        axes[0, 0].set_xlabel('R² Score', fontweight='bold')
        axes[0, 0].set_title('CAS Model Comparison - R² Score', fontweight='bold', fontsize=14)
        axes[0, 0].axvline(x=0.7, color='red', linestyle='--', alpha=0.5, label='Good threshold')
        axes[0, 0].legend()
        
        # MAE comparison
        axes[0, 1].barh(results_df['model'], results_df['test_mae'], color=cas_secondary)
        axes[0, 1].set_xlabel('Mean Absolute Error (%)', fontweight='bold')
        axes[0, 1].set_title('CAS Model Comparison - MAE', fontweight='bold', fontsize=14)
        axes[0, 1].invert_xaxis()
        
        # Cross-validation scores with error bars
        axes[1, 0].barh(results_df['model'], results_df['cv_mean'], 
                       xerr=results_df['cv_std'], color=cas_light, edgecolor=cas_secondary)
        axes[1, 0].set_xlabel('Cross-Validation Score', fontweight='bold')
        axes[1, 0].set_title('CAS Cross-Validation Performance', fontweight='bold', fontsize=14)
        
        # Actual vs Predicted
        axes[1, 1].scatter(y_test, y_pred, alpha=0.6, color=cas_primary, s=100, edgecolor=cas_secondary)
        axes[1, 1].plot([y_test.min(), y_test.max()], [y_test.min(), y_test.max()], 
                       'r--', lw=2, label='Perfect Prediction')
        axes[1, 1].set_xlabel('Actual Passing Rate (%)', fontweight='bold')
        axes[1, 1].set_ylabel('Predicted Passing Rate (%)', fontweight='bold')
        axes[1, 1].set_title(f'CAS {self.best_model_name} - Actual vs Predicted', 
                            fontweight='bold', fontsize=14)
        axes[1, 1].legend()
        axes[1, 1].grid(True, alpha=0.3)
        
        plt.tight_layout()
        plt.savefig(os.path.join(self.output_dir, 'graphs', 'model_comparison.png'), 
                   dpi=300, bbox_inches='tight')
        plt.close()
        
        # 2. Residuals Plot
        residuals = y_test - y_pred
        fig, axes = plt.subplots(1, 2, figsize=(15, 5))
        
        # Residual scatter
        axes[0].scatter(y_pred, residuals, alpha=0.6, color=cas_primary, s=100, edgecolor=cas_secondary)
        axes[0].axhline(y=0, color='red', linestyle='--', lw=2)
        axes[0].set_xlabel('Predicted Values (%)', fontweight='bold')
        axes[0].set_ylabel('Residuals (%)', fontweight='bold')
        axes[0].set_title('CAS Residual Plot', fontweight='bold', fontsize=14)
        axes[0].grid(True, alpha=0.3)
        
        # Residual distribution
        axes[1].hist(residuals, bins=15, color=cas_primary, edgecolor=cas_secondary, alpha=0.7)
        axes[1].set_xlabel('Residuals (%)', fontweight='bold')
        axes[1].set_ylabel('Frequency', fontweight='bold')
        axes[1].set_title('CAS Residual Distribution', fontweight='bold', fontsize=14)
        axes[1].axvline(x=0, color='red', linestyle='--', lw=2)
        axes[1].grid(True, alpha=0.3)
        
        plt.tight_layout()
        plt.savefig(os.path.join(self.output_dir, 'graphs', 'residuals.png'), 
                   dpi=300, bbox_inches='tight')
        plt.close()
        
        print(f"   [OK] CAS Graphs saved to {self.output_dir}/graphs/")
    
    def predict_with_confidence(self, X):
        """Make predictions with confidence intervals"""
        # For simplicity, using bootstrap method for confidence intervals
        n_iterations = 1000
        predictions = []
        
        for _ in range(n_iterations):
            # Bootstrap sample
            indices = np.random.choice(len(X), len(X), replace=True)
            X_boot = X.iloc[indices] if isinstance(X, pd.DataFrame) else X[indices]
            
            # Predict
            if self.best_model_name in ['Support Vector Regression', 'Ridge Regression', 'Lasso Regression']:
                X_boot_scaled = self.scaler.transform(X_boot)
                pred = self.best_model.predict(X_boot_scaled)
            else:
                pred = self.best_model.predict(X_boot)
            
            predictions.append(pred)
        
        predictions = np.array(predictions)
        
        # Calculate confidence intervals
        mean_pred = np.mean(predictions, axis=0)
        lower_bound = np.percentile(predictions, 2.5, axis=0)
        upper_bound = np.percentile(predictions, 97.5, axis=0)
        std_pred = np.std(predictions, axis=0)
        
        return {
            'prediction': mean_pred,
            'lower_95': lower_bound,
            'upper_95': upper_bound,
            'std': std_pred
        }
    
    def predict_next_year(self):
        """Predict passing rates for next year with confidence intervals"""
        # Load model and metadata
        model_path = os.path.join(self.model_dir, 'best_model.pkl')
        if not os.path.exists(model_path):
            print("CAS Model not found! Please train the model first.")
            return None
        
        self.best_model = joblib.load(model_path)
        self.scaler = joblib.load(os.path.join(self.model_dir, 'scaler.pkl'))
        
        with open(os.path.join(self.model_dir, 'model_metadata.json'), 'r') as f:
            metadata = json.load(f)
        
        self.best_model_name = metadata['best_model']
        
        # Get historical data
        df = self.fetch_data_from_db()
        if df is None or len(df) == 0:
            return None
        
        df = self.prepare_features(df)
        
        latest_year = df['year'].max()
        next_year = int(latest_year) + 1
        
        # Aggregate latest year data
        latest_data = df[df['year'] == latest_year].groupby('board_exam_type').agg({
            'total_examinees': 'sum',
            'passed': 'sum',
            'failed': 'sum',
            'conditional': 'sum',
            'first_timer_ratio': 'mean',
            'repeater_ratio': 'mean',
            'passing_rate': 'mean',
            'passing_rate_ma3': 'mean'
        }).reset_index()
        
        predictions = []
        year_normalized_base = df['year'].min()
        
        for _, row in latest_data.iterrows():
            exam_type = row['board_exam_type']
            
            # Create feature vector
            features = {
                'year_normalized': next_year - year_normalized_base,
                'total_examinees': row['total_examinees'],
                'first_timer_ratio': row['first_timer_ratio'],
                'repeater_ratio': row['repeater_ratio'],
                'fail_rate': (row['failed'] / row['total_examinees'] * 100) if row['total_examinees'] > 0 else 0,
                'conditional_rate': (row['conditional'] / row['total_examinees'] * 100) if row['total_examinees'] > 0 else 0,
                'passing_rate_ma3': row['passing_rate_ma3']
            }
            
            # Add exam type encoding
            for exam_col in metadata['exam_types']:
                exam_name = exam_col.replace('exam_', '')
                features[exam_col] = 1 if exam_name in exam_type else 0
            
            X_pred = pd.DataFrame([features])[metadata['features']]
            
            # Get prediction with confidence interval
            pred_result = self.predict_with_confidence(X_pred)
            
            predicted_rate = float(pred_result['prediction'][0])
            lower_95 = float(pred_result['lower_95'][0])
            upper_95 = float(pred_result['upper_95'][0])
            std_dev = float(pred_result['std'][0])
            
            # Ensure bounds
            predicted_rate = max(0, min(100, predicted_rate))
            lower_95 = max(0, min(100, lower_95))
            upper_95 = max(0, min(100, upper_95))
            
            predictions.append({
                'board_exam_type': str(exam_type),
                'predicted_passing_rate': round(float(predicted_rate), 2),
                'confidence_interval_95': {
                    'lower': round(float(lower_95), 2),
                    'upper': round(float(upper_95), 2)
                },
                'std_deviation': round(float(std_dev), 2),
                'current_year': int(latest_year),
                'prediction_year': int(next_year),
                'historical_avg': round(float(row['passing_rate']), 2)
            })
        
        result = {
            'predictions': predictions,
            'model_info': {
                'model_name': str(metadata['best_model']),
                'trained_date': str(metadata['trained_date']),
                'r2_score': round(float(metadata['best_model_metrics']['r2_score']), 4),
                'mae': round(float(metadata['best_model_metrics']['mae']), 2),
                'cv_mean': round(float(metadata['best_model_metrics']['cv_mean']), 4),
                'cv_std': round(float(metadata['best_model_metrics']['cv_std']), 4)
            }
        }
        
        return convert_to_python_types(result)

if __name__ == "__main__":
    db_config = {
        'host': 'localhost',
        'user': 'root',
        'password': '',
        'database': 'project_db'
    }
    
    predictor = AdvancedBoardExamPredictorCAS(db_config)
    
    # Train and compare models
    metadata = predictor.train_and_compare_models()
    
    if metadata:
        print("\n" + "=" * 70)
        print("GENERATING CAS PREDICTIONS WITH CONFIDENCE INTERVALS")
        print("=" * 70)
        
        predictions = predictor.predict_next_year()
        
        if predictions:
            print("\n[CAS PREDICTIONS FOR NEXT YEAR]\n")
            for pred in predictions['predictions']:
                print(f"{'=' * 70}")
                print(f"[{pred['board_exam_type']}]")
                print(f"{'=' * 70}")
                print(f"  Current Year ({pred['current_year']}): {pred['historical_avg']}%")
                print(f"  Predicted ({pred['prediction_year']}): {pred['predicted_passing_rate']}%")
                print(f"  95% Confidence Interval: [{pred['confidence_interval_95']['lower']}%, {pred['confidence_interval_95']['upper']}%]")
                print(f"  Standard Deviation: +/-{pred['std_deviation']}%")
                change = pred['predicted_passing_rate'] - pred['historical_avg']
                arrow = "[UP]" if change > 0 else "[DOWN]" if change < 0 else "[STABLE]"
                print(f"  Expected Change: {arrow} {change:+.2f}%\n")
