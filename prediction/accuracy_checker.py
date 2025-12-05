"""
ACCURACY CHECKER - Historical Validation
This script validates prediction accuracy by:
1. Training on older data (e.g., 2021-2022)
2. Predicting future year (e.g., 2023)
3. Comparing predictions with actual 2023 results
4. Showing how accurate the model would have been
"""

import mysql.connector
import pandas as pd
import numpy as np
from sklearn.ensemble import RandomForestRegressor, GradientBoostingRegressor
from sklearn.linear_model import LinearRegression
from sklearn.metrics import mean_squared_error, r2_score, mean_absolute_error
from sklearn.preprocessing import StandardScaler
import matplotlib
matplotlib.use('Agg')
import matplotlib.pyplot as plt
import seaborn as sns
from datetime import datetime
import os
import json

class AccuracyValidator:
    """Validates model by predicting past years and comparing with actual results"""
    
    def __init__(self, db_config):
        self.db_config = db_config
        self.output_dir = 'accuracy_validation'
        os.makedirs(self.output_dir, exist_ok=True)
        os.makedirs(os.path.join(self.output_dir, 'graphs'), exist_ok=True)
    
    def fetch_data(self):
        """Fetch all historical data"""
        conn = mysql.connector.connect(**self.db_config)
        cursor = conn.cursor(dictionary=True)
        
        query = """
            SELECT 
                YEAR(board_exam_date) as year,
                MONTH(board_exam_date) as month,
                board_exam_type,
                exam_type as take_attempts,
                result,
                board_exam_date
            FROM anonymous_board_passers
            WHERE department = 'Engineering'
            AND (is_deleted IS NULL OR is_deleted = 0)
            AND board_exam_date IS NOT NULL
            ORDER BY board_exam_date ASC
        """
        
        cursor.execute(query)
        data = cursor.fetchall()
        cursor.close()
        conn.close()
        
        return pd.DataFrame(data)
    
    def prepare_aggregated_data(self, df):
        """Aggregate data by year and exam type"""
        aggregated = df.groupby(['year', 'board_exam_type', 'take_attempts']).agg({
            'result': lambda x: {
                'total': len(x),
                'passed': (x == 'Passed').sum(),
                'failed': (x == 'Failed').sum(),
                'conditional': (x == 'Conditional').sum()
            }
        }).reset_index()
        
        aggregated['total_examinees'] = aggregated['result'].apply(lambda x: x['total'])
        aggregated['passed'] = aggregated['result'].apply(lambda x: x['passed'])
        aggregated['failed'] = aggregated['result'].apply(lambda x: x['failed'])
        aggregated['conditional'] = aggregated['result'].apply(lambda x: x['conditional'])
        aggregated = aggregated.drop('result', axis=1)
        
        aggregated['passing_rate'] = (aggregated['passed'] / aggregated['total_examinees'] * 100).round(2)
        aggregated['fail_rate'] = (aggregated['failed'] / aggregated['total_examinees'] * 100).round(2)
        aggregated['conditional_rate'] = (aggregated['conditional'] / aggregated['total_examinees'] * 100).round(2)
        
        aggregated['first_timer_ratio'] = (aggregated['take_attempts'] == 'First Timer').astype(int) * 100
        aggregated['repeater_ratio'] = (aggregated['take_attempts'] == 'Repeater').astype(int) * 100
        
        return aggregated
    
    def prepare_features(self, df):
        """Prepare features for modeling"""
        df['year_normalized'] = df['year'] - df['year'].min()
        
        # One-hot encode exam types
        exam_dummies = pd.get_dummies(df['board_exam_type'], prefix='exam')
        df = pd.concat([df, exam_dummies], axis=1)
        
        return df
    
    def validate_year_prediction(self, df, train_years, test_year):
        """
        Train on specific years and predict another year
        Example: Train on [2021, 2022], predict 2023, compare with actual 2023
        """
        print(f"\n{'='*80}")
        print(f"VALIDATING: Training on {train_years} → Predicting {test_year}")
        print(f"{'='*80}")
        
        # Prepare features
        df = self.prepare_features(df)
        
        # Get feature columns
        exam_cols = [col for col in df.columns if col.startswith('exam_')]
        feature_cols = ['year_normalized', 'total_examinees', 'first_timer_ratio', 
                       'repeater_ratio', 'fail_rate', 'conditional_rate'] + exam_cols
        
        # Split data
        train_data = df[df['year'].isin(train_years)]
        test_data = df[df['year'] == test_year]
        
        if len(test_data) == 0:
            print(f"   ⚠️  No data available for {test_year}")
            return None
        
        X_train = train_data[feature_cols]
        y_train = train_data['passing_rate']
        X_test = test_data[feature_cols]
        y_test = test_data['passing_rate']
        
        print(f"   Training samples: {len(X_train)}")
        print(f"   Testing samples: {len(X_test)}")
        
        # Train multiple models
        models = {
            'Linear Regression': LinearRegression(),
            'Random Forest': RandomForestRegressor(n_estimators=100, random_state=42),
            'Gradient Boosting': GradientBoostingRegressor(n_estimators=100, random_state=42)
        }
        
        results = []
        
        for name, model in models.items():
            model.fit(X_train, y_train)
            y_pred = model.predict(X_test)
            
            r2 = r2_score(y_test, y_pred)
            mae = mean_absolute_error(y_test, y_pred)
            rmse = np.sqrt(mean_squared_error(y_test, y_pred))
            
            results.append({
                'model': name,
                'r2': r2,
                'mae': mae,
                'rmse': rmse,
                'predictions': y_pred,
                'actuals': y_test.values
            })
            
            print(f"\n   {name}:")
            print(f"      R² Score: {r2:.4f}")
            print(f"      MAE: {mae:.2f}%")
            print(f"      RMSE: {rmse:.2f}%")
        
        # Best model
        best = max(results, key=lambda x: x['r2'])
        
        print(f"\n   🏆 Best Model: {best['model']}")
        print(f"      Predictions were off by ±{best['mae']:.2f}% on average")
        
        # Show actual vs predicted for best model
        print(f"\n   📊 ACTUAL vs PREDICTED (using {best['model']}):")
        print(f"   {'Exam Type':<50} {'Actual':>10} {'Predicted':>10} {'Difference':>12}")
        print(f"   {'-'*84}")
        
        for idx, (actual, pred) in enumerate(zip(best['actuals'], best['predictions'])):
            exam_type = test_data.iloc[idx]['board_exam_type']
            diff = pred - actual
            if len(exam_type) > 48:
                exam_type = exam_type[:45] + '...'
            print(f"   {exam_type:<50} {actual:>9.2f}% {pred:>9.2f}% {diff:>+11.2f}%")
        
        return {
            'test_year': test_year,
            'train_years': train_years,
            'results': results,
            'best_model': best['model'],
            'test_data': test_data,
            'predictions': best['predictions'],
            'actuals': best['actuals']
        }
    
    def run_complete_validation(self):
        """Run validation across multiple years"""
        print("\n" + "="*80)
        print("HISTORICAL PREDICTION ACCURACY VALIDATION")
        print("="*80)
        print("Testing how accurate predictions would have been for past years")
        print("="*80)
        
        df = self.fetch_data()
        df_agg = self.prepare_aggregated_data(df)
        
        available_years = sorted(df_agg['year'].unique())
        print(f"\nAvailable years: {available_years}")
        
        if len(available_years) < 3:
            print("⚠️  Need at least 3 years of data for validation")
            return
        
        all_validations = []
        
        # Validate each year (except first 2 years which are used for initial training)
        for i in range(2, len(available_years)):
            train_years = available_years[:i]
            test_year = available_years[i]
            
            validation = self.validate_year_prediction(df_agg, train_years, test_year)
            if validation:
                all_validations.append(validation)
        
        # Create summary
        print("\n" + "="*80)
        print("VALIDATION SUMMARY - HOW ACCURATE WERE THE PREDICTIONS?")
        print("="*80)
        
        summary_data = []
        for val in all_validations:
            best_result = [r for r in val['results'] if r['model'] == val['best_model']][0]
            summary_data.append({
                'Predicted Year': val['test_year'],
                'Trained On': f"{val['train_years'][0]}-{val['train_years'][-1]}",
                'Best Model': val['best_model'],
                'R² Score': best_result['r2'],
                'MAE (%)': best_result['mae'],
                'RMSE (%)': best_result['rmse']
            })
        
        summary_df = pd.DataFrame(summary_data)
        print("\n" + summary_df.to_string(index=False))
        
        avg_mae = summary_df['MAE (%)'].mean()
        avg_r2 = summary_df['R² Score'].mean()
        
        print(f"\n{'='*80}")
        print(f"OVERALL ACCURACY ACROSS ALL PREDICTIONS:")
        print(f"{'='*80}")
        print(f"Average R² Score: {avg_r2:.4f}")
        print(f"Average MAE: {avg_mae:.2f}%")
        print(f"\n💡 Interpretation:")
        print(f"   On average, predictions were off by ±{avg_mae:.2f} percentage points")
        print(f"   The model explains {avg_r2*100:.1f}% of variation in passing rates")
        
        # Create visualization
        self._create_accuracy_graphs(all_validations)
        
        # Save summary
        summary_df.to_csv(os.path.join(self.output_dir, 'accuracy_summary.csv'), index=False)
        
        # Save detailed report
        report = {
            'validation_date': datetime.now().isoformat(),
            'available_years': available_years,
            'validations': all_validations,
            'overall_metrics': {
                'average_r2': float(avg_r2),
                'average_mae': float(avg_mae),
                'average_rmse': float(summary_df['RMSE (%)'].mean())
            }
        }
        
        with open(os.path.join(self.output_dir, 'detailed_validation.json'), 'w') as f:
            json.dump(report, f, indent=2, default=str)
        
        print(f"\n✅ Reports saved to: {self.output_dir}/")
        print(f"   - accuracy_summary.csv")
        print(f"   - detailed_validation.json")
        print(f"   - graphs/*.png")
        
        return report
    
    def _create_accuracy_graphs(self, validations):
        """Create visualization graphs"""
        print("\n📊 Generating accuracy visualization graphs...")
        
        # Graph 1: Actual vs Predicted for all years
        fig, axes = plt.subplots(len(validations), 1, figsize=(12, 4*len(validations)))
        
        if len(validations) == 1:
            axes = [axes]
        
        for idx, val in enumerate(validations):
            best_result = [r for r in val['results'] if r['model'] == val['best_model']][0]
            
            x_pos = np.arange(len(best_result['actuals']))
            width = 0.35
            
            axes[idx].bar(x_pos - width/2, best_result['actuals'], width, 
                         label='Actual', color='#3B6255', alpha=0.8)
            axes[idx].bar(x_pos + width/2, best_result['predictions'], width, 
                         label='Predicted', color='#8BA49A', alpha=0.8)
            
            axes[idx].set_xlabel('Test Cases', fontweight='bold')
            axes[idx].set_ylabel('Passing Rate (%)', fontweight='bold')
            axes[idx].set_title(f'{val["test_year"]} Predictions (Trained on {val["train_years"][0]}-{val["train_years"][-1]}) - MAE: {best_result["mae"]:.2f}%', 
                              fontweight='bold')
            axes[idx].legend()
            axes[idx].grid(True, alpha=0.3, axis='y')
        
        plt.tight_layout()
        plt.savefig(os.path.join(self.output_dir, 'graphs', 'actual_vs_predicted.png'), 
                   dpi=300, bbox_inches='tight')
        plt.close()
        
        # Graph 2: Error distribution
        fig, ax = plt.subplots(figsize=(12, 6))
        
        all_errors = []
        year_labels = []
        
        for val in validations:
            best_result = [r for r in val['results'] if r['model'] == val['best_model']][0]
            errors = best_result['predictions'] - best_result['actuals']
            all_errors.extend(errors)
            year_labels.extend([val['test_year']] * len(errors))
        
        data_for_box = [
            [e for e, y in zip(all_errors, year_labels) if y == val['test_year']]
            for val in validations
        ]
        
        bp = ax.boxplot(data_for_box, labels=[val['test_year'] for val in validations],
                       patch_artist=True)
        
        for patch in bp['boxes']:
            patch.set_facecolor('#8BA49A')
            patch.set_alpha(0.7)
        
        ax.axhline(y=0, color='red', linestyle='--', linewidth=2, label='Perfect Prediction')
        ax.set_xlabel('Prediction Year', fontweight='bold')
        ax.set_ylabel('Prediction Error (%)', fontweight='bold')
        ax.set_title('Prediction Error Distribution by Year', fontweight='bold', fontsize=14)
        ax.legend()
        ax.grid(True, alpha=0.3, axis='y')
        
        plt.tight_layout()
        plt.savefig(os.path.join(self.output_dir, 'graphs', 'error_distribution.png'), 
                   dpi=300, bbox_inches='tight')
        plt.close()
        
        # Graph 3: MAE over time
        fig, ax = plt.subplots(figsize=(10, 6))
        
        years = [val['test_year'] for val in validations]
        maes = [
            [r for r in val['results'] if r['model'] == val['best_model']][0]['mae']
            for val in validations
        ]
        
        ax.plot(years, maes, marker='o', linewidth=2, markersize=10, 
               color='#3B6255', label='MAE')
        ax.fill_between(years, 0, maes, alpha=0.3, color='#8BA49A')
        
        ax.set_xlabel('Year', fontweight='bold')
        ax.set_ylabel('Mean Absolute Error (%)', fontweight='bold')
        ax.set_title('Prediction Accuracy Over Time', fontweight='bold', fontsize=14)
        ax.legend()
        ax.grid(True, alpha=0.3)
        
        plt.tight_layout()
        plt.savefig(os.path.join(self.output_dir, 'graphs', 'mae_over_time.png'), 
                   dpi=300, bbox_inches='tight')
        plt.close()
        
        print("   ✓ Graphs saved successfully")

if __name__ == "__main__":
    db_config = {
        'host': 'localhost',
        'user': 'root',
        'password': '',
        'database': 'project_db'
    }
    
    validator = AccuracyValidator(db_config)
    report = validator.run_complete_validation()
