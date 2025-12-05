import mysql.connector
import pandas as pd
import numpy as np
from sklearn.linear_model import LinearRegression, Ridge, Lasso
from sklearn.ensemble import RandomForestRegressor, GradientBoostingRegressor
from sklearn.svm import SVR
from sklearn.preprocessing import StandardScaler
from sklearn.metrics import mean_absolute_error, mean_absolute_percentage_error, r2_score
import xgboost as xgb
import matplotlib
matplotlib.use('Agg')
import matplotlib.pyplot as plt
import seaborn as sns
from datetime import datetime
import os

class PredictionValidator:
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
        
    def fetch_data(self):
        """Fetch all historical data"""
        try:
            conn = mysql.connector.connect(**self.db_config)
            cursor = conn.cursor(dictionary=True)
            
            query = """
                SELECT 
                    YEAR(board_exam_date) as year,
                    board_exam_type,
                    exam_type as take_attempts,
                    COUNT(*) as total_examinees,
                    SUM(CASE WHEN result = 'Passed' THEN 1 ELSE 0 END) as passed,
                    SUM(CASE WHEN result = 'Failed' THEN 1 ELSE 0 END) as failed,
                    SUM(CASE WHEN result = 'Conditional' THEN 1 ELSE 0 END) as conditional
                FROM anonymous_board_passers
                WHERE department = 'Engineering'
                AND (is_deleted IS NULL OR is_deleted = 0)
                AND board_exam_date IS NOT NULL
                GROUP BY YEAR(board_exam_date), board_exam_type, exam_type
                ORDER BY year ASC
            """
            
            cursor.execute(query)
            data = cursor.fetchall()
            cursor.close()
            conn.close()
            
            df = pd.DataFrame(data)
            
            # Convert to numeric
            numeric_cols = ['passed', 'failed', 'conditional', 'total_examinees']
            for col in numeric_cols:
                df[col] = pd.to_numeric(df[col], errors='coerce')
            
            # Calculate passing rate
            df['passing_rate'] = (df['passed'] / df['total_examinees'] * 100).round(2)
            
            return df
            
        except Exception as e:
            print(f"Error fetching data: {e}")
            return None
    
    def prepare_features(self, df, include_year):
        """Prepare features for a specific year range"""
        # Filter data up to the specified year
        train_df = df[df['year'] <= include_year].copy()
        
        # Create features
        train_df['first_timer_ratio'] = (train_df['take_attempts'] == 'First Timer').astype(int) * 100
        train_df['repeater_ratio'] = (train_df['take_attempts'] == 'Repeater').astype(int) * 100
        train_df['fail_rate'] = (train_df['failed'] / train_df['total_examinees'] * 100).round(2)
        train_df['conditional_rate'] = (train_df['conditional'] / train_df['total_examinees'] * 100).round(2)
        train_df['year_normalized'] = train_df['year'] - train_df['year'].min()
        
        # One-hot encode exam types
        board_exam_dummies = pd.get_dummies(train_df['board_exam_type'], prefix='exam')
        train_df = pd.concat([train_df, board_exam_dummies], axis=1)
        
        return train_df
    
    def train_and_predict(self, train_year, predict_year):
        """Train on data up to train_year and predict for predict_year"""
        print(f"\n{'='*70}")
        print(f"Training on data up to {train_year}, predicting {predict_year}")
        print(f"{'='*70}")
        
        df = self.fetch_data()
        if df is None or len(df) == 0:
            return None
        
        # Check if we have data for both years
        years_available = sorted(df['year'].unique())
        print(f"Available years: {years_available}")
        
        if predict_year not in years_available:
            print(f"No actual data for {predict_year} to compare!")
            return None
        
        # Prepare training data
        train_df = self.prepare_features(df, train_year)
        
        # Get feature columns
        exam_cols = [col for col in train_df.columns if col.startswith('exam_')]
        feature_cols = ['year_normalized', 'total_examinees', 'first_timer_ratio', 
                       'repeater_ratio', 'fail_rate', 'conditional_rate'] + exam_cols
        
        # Training data
        X_train = train_df[feature_cols]
        y_train = train_df['passing_rate']
        
        # Get actual results for predict_year
        actual_data = df[df['year'] == predict_year].groupby('board_exam_type').agg({
            'total_examinees': 'sum',
            'passed': 'sum',
            'failed': 'sum',
            'conditional': 'sum',
            'passing_rate': 'mean'
        }).reset_index()
        
        # Prepare prediction data based on train_year
        last_year_data = df[df['year'] == train_year].groupby('board_exam_type').agg({
            'total_examinees': 'sum',
            'passed': 'sum',
            'failed': 'sum',
            'conditional': 'sum'
        }).reset_index()
        
        # Add ratios
        last_year_detail = df[df['year'] == train_year]
        for _, row in last_year_data.iterrows():
            exam_type = row['board_exam_type']
            exam_detail = last_year_detail[last_year_detail['board_exam_type'] == exam_type]
            first_timer = exam_detail[exam_detail['take_attempts'] == 'First Timer']['total_examinees'].sum()
            repeater = exam_detail[exam_detail['take_attempts'] == 'Repeater']['total_examinees'].sum()
            total = first_timer + repeater
            last_year_data.loc[last_year_data['board_exam_type'] == exam_type, 'first_timer_ratio'] = (first_timer / total * 100) if total > 0 else 50
            last_year_data.loc[last_year_data['board_exam_type'] == exam_type, 'repeater_ratio'] = (repeater / total * 100) if total > 0 else 50
        
        # Train all models and compare
        results = []
        scaler = StandardScaler()
        X_train_scaled = scaler.fit_transform(X_train)
        
        for model_name, model in self.models.items():
            try:
                # Train
                if model_name in ['Support Vector Regression', 'Ridge Regression', 'Lasso Regression']:
                    model.fit(X_train_scaled, y_train)
                else:
                    model.fit(X_train, y_train)
                
                # Predict for each exam type
                predictions = []
                year_norm_base = train_df['year'].min()
                
                for _, row in last_year_data.iterrows():
                    exam_type = row['board_exam_type']
                    
                    # Create feature vector
                    features = {
                        'year_normalized': int(predict_year - year_norm_base),
                        'total_examinees': float(row['total_examinees']),
                        'first_timer_ratio': float(row.get('first_timer_ratio', 50)),
                        'repeater_ratio': float(row.get('repeater_ratio', 50)),
                        'fail_rate': float((row['failed'] / row['total_examinees'] * 100) if row['total_examinees'] > 0 else 0),
                        'conditional_rate': float((row['conditional'] / row['total_examinees'] * 100) if row['total_examinees'] > 0 else 0)
                    }
                    
                    # Add exam type encoding
                    for exam_col in exam_cols:
                        exam_name = exam_col.replace('exam_', '')
                        features[exam_col] = 1 if exam_name in exam_type else 0
                    
                    X_pred = pd.DataFrame([features])[feature_cols]
                    
                    # Predict
                    if model_name in ['Support Vector Regression', 'Ridge Regression', 'Lasso Regression']:
                        X_pred_scaled = scaler.transform(X_pred)
                        pred = model.predict(X_pred_scaled)[0]
                    else:
                        pred = model.predict(X_pred)[0]
                    
                    pred = max(0, min(100, float(pred)))
                    
                    # Get actual value
                    actual_row = actual_data[actual_data['board_exam_type'] == exam_type]
                    if not actual_row.empty:
                        actual_value = float(actual_row['passing_rate'].values[0])
                        predictions.append({
                            'exam_type': exam_type,
                            'predicted': pred,
                            'actual': actual_value,
                            'error': abs(pred - actual_value)
                        })
                
                if predictions:
                    pred_df = pd.DataFrame(predictions)
                    mae = float(pred_df['error'].mean())
                    mape = float((pred_df['error'] / pred_df['actual'] * 100).mean())
                    
                    results.append({
                        'model': model_name,
                        'mae': mae,
                        'mape': mape,
                        'predictions': predictions
                    })
                    
                    print(f"\n{model_name}:")
                    print(f"  Mean Absolute Error: {mae:.2f}%")
                    print(f"  Mean Absolute Percentage Error: {mape:.2f}%")
                    
            except Exception as e:
                print(f"  {model_name} failed: {e}")
                continue
        
        return results
    
    def run_full_validation(self):
        """Run backtesting for all available year pairs"""
        print("\n" + "="*70)
        print("HISTORICAL PREDICTION ACCURACY VALIDATION")
        print("Comparing predictions vs actual results")
        print("="*70)
        
        df = self.fetch_data()
        if df is None:
            return
        
        years = sorted(df['year'].unique())
        print(f"\nAvailable years in database: {years}")
        
        if len(years) < 2:
            print("\nNeed at least 2 years of data for validation!")
            return
        
        all_results = []
        
        # Test each year prediction
        for i in range(len(years) - 1):
            train_year = years[i]
            predict_year = years[i + 1]
            
            results = self.train_and_predict(train_year, predict_year)
            
            if results:
                all_results.append({
                    'train_year': train_year,
                    'predict_year': predict_year,
                    'results': results
                })
                
                # Show detailed results
                print(f"\n{'='*70}")
                print(f"DETAILED RESULTS: {predict_year} Predictions")
                print(f"{'='*70}")
                
                best_model = min(results, key=lambda x: x['mae'])
                print(f"\n🏆 Best Model: {best_model['model']}")
                print(f"   Average Error: {best_model['mae']:.2f}%")
                
                print(f"\n📊 Prediction vs Actual Comparison:")
                for pred in best_model['predictions']:
                    error_pct = (pred['error'] / pred['actual'] * 100) if pred['actual'] > 0 else 0
                    status = "✅" if pred['error'] < 5 else "⚠️" if pred['error'] < 10 else "❌"
                    print(f"\n  {status} {pred['exam_type']}:")
                    print(f"      Predicted: {pred['predicted']:.2f}%")
                    print(f"      Actual:    {pred['actual']:.2f}%")
                    print(f"      Error:     {pred['error']:.2f}% ({error_pct:.1f}% relative error)")
        
        # Summary
        if all_results:
            print(f"\n{'='*70}")
            print("VALIDATION SUMMARY")
            print(f"{'='*70}")
            
            for result_set in all_results:
                print(f"\n{result_set['train_year']} → {result_set['predict_year']}:")
                best = min(result_set['results'], key=lambda x: x['mae'])
                print(f"  Best Model: {best['model']}")
                print(f"  Average Prediction Error: {best['mae']:.2f}%")
                print(f"  Relative Error: {best['mape']:.2f}%")
            
            # Overall accuracy
            all_errors = []
            for result_set in all_results:
                best = min(result_set['results'], key=lambda x: x['mae'])
                all_errors.append(best['mae'])
            
            overall_mae = np.mean(all_errors)
            print(f"\n{'='*70}")
            print(f"📈 OVERALL PREDICTION ACCURACY")
            print(f"{'='*70}")
            print(f"Average Prediction Error Across All Years: {overall_mae:.2f}%")
            
            if overall_mae < 5:
                print("✅ EXCELLENT - Predictions are highly accurate!")
            elif overall_mae < 10:
                print("⚠️ GOOD - Predictions are reasonably accurate")
            else:
                print("❌ FAIR - Predictions need improvement")
            
            self._create_validation_visualizations(all_results)
    
    def _create_validation_visualizations(self, all_results):
        """Create visualization of validation results"""
        print("\n📊 Creating validation visualizations...")
        
        os.makedirs('output/validation', exist_ok=True)
        
        # Prepare data for plotting
        plot_data = []
        for result_set in all_results:
            best_model = min(result_set['results'], key=lambda x: x['mae'])
            for pred in best_model['predictions']:
                plot_data.append({
                    'year': result_set['predict_year'],
                    'exam_type': pred['exam_type'],
                    'predicted': pred['predicted'],
                    'actual': pred['actual'],
                    'error': pred['error']
                })
        
        plot_df = pd.DataFrame(plot_data)
        
        # Create figure with subplots
        fig, axes = plt.subplots(2, 2, figsize=(16, 12))
        
        # 1. Predicted vs Actual scatter
        axes[0, 0].scatter(plot_df['actual'], plot_df['predicted'], 
                          alpha=0.6, s=150, c='#8BA49A', edgecolor='#3B6255', linewidth=2)
        min_val = min(plot_df['actual'].min(), plot_df['predicted'].min())
        max_val = max(plot_df['actual'].max(), plot_df['predicted'].max())
        axes[0, 0].plot([min_val, max_val], [min_val, max_val], 
                       'r--', lw=2, label='Perfect Prediction')
        axes[0, 0].set_xlabel('Actual Passing Rate (%)', fontweight='bold', fontsize=12)
        axes[0, 0].set_ylabel('Predicted Passing Rate (%)', fontweight='bold', fontsize=12)
        axes[0, 0].set_title('Prediction Accuracy: Predicted vs Actual', 
                            fontweight='bold', fontsize=14)
        axes[0, 0].legend(fontsize=10)
        axes[0, 0].grid(True, alpha=0.3)
        
        # 2. Error distribution
        axes[0, 1].hist(plot_df['error'], bins=15, color='#8BA49A', 
                       edgecolor='#3B6255', alpha=0.7)
        axes[0, 1].axvline(x=plot_df['error'].mean(), color='red', 
                          linestyle='--', lw=2, label=f'Mean Error: {plot_df["error"].mean():.2f}%')
        axes[0, 1].set_xlabel('Prediction Error (%)', fontweight='bold', fontsize=12)
        axes[0, 1].set_ylabel('Frequency', fontweight='bold', fontsize=12)
        axes[0, 1].set_title('Error Distribution', fontweight='bold', fontsize=14)
        axes[0, 1].legend(fontsize=10)
        axes[0, 1].grid(True, alpha=0.3)
        
        # 3. Error by exam type
        error_by_exam = plot_df.groupby('exam_type')['error'].mean().sort_values()
        axes[1, 0].barh(range(len(error_by_exam)), error_by_exam.values, color='#8BA49A', edgecolor='#3B6255')
        axes[1, 0].set_yticks(range(len(error_by_exam)))
        axes[1, 0].set_yticklabels([label[:40] + '...' if len(label) > 40 else label for label in error_by_exam.index], fontsize=9)
        axes[1, 0].set_xlabel('Average Prediction Error (%)', fontweight='bold', fontsize=12)
        axes[1, 0].set_title('Average Error by Exam Type', fontweight='bold', fontsize=14)
        axes[1, 0].grid(True, alpha=0.3, axis='x')
        
        # 4. Predictions by year
        exam_types = plot_df['exam_type'].unique()
        colors = plt.cm.Set3(np.linspace(0, 1, len(exam_types)))
        
        for idx, exam_type in enumerate(exam_types):
            exam_data = plot_df[plot_df['exam_type'] == exam_type].sort_values('year')
            label = exam_type[:30] + '...' if len(exam_type) > 30 else exam_type
            axes[1, 1].plot(exam_data['year'], exam_data['actual'], 
                          'o-', label=f'{label} (Actual)', linewidth=2, markersize=8, color=colors[idx])
            axes[1, 1].plot(exam_data['year'], exam_data['predicted'], 
                          's--', linewidth=2, markersize=6, alpha=0.7, color=colors[idx])
        
        axes[1, 1].set_xlabel('Year', fontweight='bold', fontsize=12)
        axes[1, 1].set_ylabel('Passing Rate (%)', fontweight='bold', fontsize=12)
        axes[1, 1].set_title('Historical Predictions vs Actual Results', 
                            fontweight='bold', fontsize=14)
        axes[1, 1].legend(fontsize=7, ncol=2, loc='best')
        axes[1, 1].grid(True, alpha=0.3)
        
        plt.tight_layout()
        plt.savefig('output/validation/accuracy_validation.png', dpi=300, bbox_inches='tight')
        plt.close()
        
        print(f"   ✓ Validation graphs saved to output/validation/accuracy_validation.png")
        
        # Save detailed results to CSV
        plot_df.to_csv('output/validation/validation_results.csv', index=False)
        print(f"   ✓ Detailed results saved to output/validation/validation_results.csv")

if __name__ == "__main__":
    db_config = {
        'host': 'localhost',
        'user': 'root',
        'password': '',
        'database': 'project_db'
    }
    
    validator = PredictionValidator(db_config)
    validator.run_full_validation()
