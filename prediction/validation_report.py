import mysql.connector
import pandas as pd
import numpy as np
from sklearn.linear_model import LinearRegression, Ridge, Lasso
from sklearn.ensemble import RandomForestRegressor, GradientBoostingRegressor
from sklearn.svm import SVR
from sklearn.model_selection import train_test_split, cross_val_score
from sklearn.metrics import (mean_squared_error, r2_score, mean_absolute_error, 
                             accuracy_score, precision_score, recall_score, 
                             confusion_matrix, classification_report)
from sklearn.preprocessing import StandardScaler
import xgboost as xgb
import json
from datetime import datetime
import os
import matplotlib
matplotlib.use('Agg')
import matplotlib.pyplot as plt
import seaborn as sns

class BoardExamValidationReport:
    """
    Complete validation report for Board Exam Prediction System
    Covers all 8 required steps:
    1. Data Collection
    2. Data Cleaning and Preparation
    3. Splitting of dataset (80/20)
    4. Feature Selection
    5. Model Selection
    6. Model Training
    7. Model Testing and Evaluation
    8. Evaluation Metrics & Prediction Generation
    """
    
    def __init__(self, db_config):
        self.db_config = db_config
        self.output_dir = 'validation_output'
        os.makedirs(self.output_dir, exist_ok=True)
        os.makedirs(os.path.join(self.output_dir, 'graphs'), exist_ok=True)
        
        self.report = {
            'step1_data_collection': {},
            'step2_data_cleaning': {},
            'step3_data_splitting': {},
            'step4_feature_selection': {},
            'step5_model_selection': {},
            'step6_model_training': {},
            'step7_model_evaluation': {},
            'step8_metrics_and_predictions': {}
        }
    
    def step1_data_collection(self):
        """STEP 1: DATA COLLECTION"""
        print("\n" + "="*80)
        print("STEP 1: DATA COLLECTION FROM DATABASE")
        print("="*80)
        
        try:
            conn = mysql.connector.connect(**self.db_config)
            cursor = conn.cursor(dictionary=True)
            
            query = """
                SELECT 
                    id,
                    YEAR(board_exam_date) as year,
                    MONTH(board_exam_date) as month,
                    board_exam_type,
                    exam_type as take_attempts,
                    result,
                    board_exam_date,
                    department
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
            
            df = pd.DataFrame(data)
            
            print(f"✓ Successfully collected {len(df)} records from database")
            print(f"✓ Date range: {df['board_exam_date'].min()} to {df['board_exam_date'].max()}")
            print(f"✓ Unique exam types: {df['board_exam_type'].nunique()}")
            print(f"✓ Years covered: {sorted(df['year'].unique())}")
            
            self.report['step1_data_collection'] = {
                'total_records': len(df),
                'date_range': {
                    'start': str(df['board_exam_date'].min()),
                    'end': str(df['board_exam_date'].max())
                },
                'exam_types': df['board_exam_type'].unique().tolist(),
                'years': sorted(df['year'].unique().tolist()),
                'columns': df.columns.tolist()
            }
            
            return df
            
        except Exception as e:
            print(f"✗ Error collecting data: {e}")
            return None
    
    def step2_data_cleaning(self, df):
        """STEP 2: DATA CLEANING AND PREPARATION"""
        print("\n" + "="*80)
        print("STEP 2: DATA CLEANING AND PREPARATION")
        print("="*80)
        
        initial_count = len(df)
        
        # Check for missing values
        missing_before = df.isnull().sum()
        print(f"\n📊 Missing values check:")
        print(missing_before[missing_before > 0])
        
        # Remove duplicates
        duplicates = df.duplicated().sum()
        df = df.drop_duplicates()
        print(f"\n✓ Removed {duplicates} duplicate records")
        
        # Remove records with missing critical fields
        df = df.dropna(subset=['year', 'board_exam_type', 'result'])
        print(f"✓ Removed records with missing critical fields")
        
        # Aggregate data by year, exam type, and attempts
        aggregated = df.groupby(['year', 'month', 'board_exam_type', 'take_attempts']).agg({
            'id': 'count',
            'result': lambda x: {
                'total': len(x),
                'passed': (x == 'Passed').sum(),
                'failed': (x == 'Failed').sum(),
                'conditional': (x == 'Conditional').sum()
            }
        }).reset_index()
        
        # Flatten the result column
        aggregated['total_examinees'] = aggregated['result'].apply(lambda x: x['total'])
        aggregated['passed'] = aggregated['result'].apply(lambda x: x['passed'])
        aggregated['failed'] = aggregated['result'].apply(lambda x: x['failed'])
        aggregated['conditional'] = aggregated['result'].apply(lambda x: x['conditional'])
        aggregated = aggregated.drop('result', axis=1)
        aggregated = aggregated.drop('id', axis=1)
        
        # Calculate passing rate
        aggregated['passing_rate'] = (aggregated['passed'] / aggregated['total_examinees'] * 100).round(2)
        
        final_count = len(aggregated)
        
        print(f"\n✓ Data aggregated into {final_count} records")
        print(f"✓ Records cleaned: {initial_count} → {final_count}")
        
        self.report['step2_data_cleaning'] = {
            'initial_records': initial_count,
            'final_records': final_count,
            'duplicates_removed': duplicates,
            'missing_values_before': missing_before.to_dict(),
            'aggregation_groups': ['year', 'month', 'board_exam_type', 'take_attempts']
        }
        
        return aggregated
    
    def step3_data_splitting(self, df):
        """STEP 3: SPLITTING DATASET (80% Training, 20% Testing)"""
        print("\n" + "="*80)
        print("STEP 3: SPLITTING DATASET INTO TRAINING AND TESTING SETS")
        print("="*80)
        
        # Prepare features first
        df = self._prepare_features(df)
        
        # Get feature columns
        exam_cols = [col for col in df.columns if col.startswith('exam_')]
        feature_cols = ['year_normalized', 'total_examinees', 'first_timer_ratio', 
                       'repeater_ratio', 'fail_rate', 'conditional_rate', 
                       'passing_rate_ma3'] + exam_cols
        
        X = df[feature_cols]
        y = df['passing_rate']
        
        # Split 80/20
        X_train, X_test, y_train, y_test = train_test_split(
            X, y, test_size=0.2, random_state=42, shuffle=True
        )
        
        print(f"\n✓ Total samples: {len(X)}")
        print(f"✓ Training set: {len(X_train)} samples ({len(X_train)/len(X)*100:.1f}%)")
        print(f"✓ Testing set: {len(X_test)} samples ({len(X_test)/len(X)*100:.1f}%)")
        print(f"\n✓ Training target range: {y_train.min():.2f}% - {y_train.max():.2f}%")
        print(f"✓ Testing target range: {y_test.min():.2f}% - {y_test.max():.2f}%")
        
        self.report['step3_data_splitting'] = {
            'total_samples': len(X),
            'training_samples': len(X_train),
            'testing_samples': len(X_test),
            'training_percentage': 80.0,
            'testing_percentage': 20.0,
            'random_state': 42,
            'shuffle': True,
            'training_target_range': {
                'min': float(y_train.min()),
                'max': float(y_train.max()),
                'mean': float(y_train.mean())
            },
            'testing_target_range': {
                'min': float(y_test.min()),
                'max': float(y_test.max()),
                'mean': float(y_test.mean())
            }
        }
        
        return X_train, X_test, y_train, y_test, feature_cols
    
    def step4_feature_selection(self, X_train, feature_cols):
        """STEP 4: FEATURE SELECTION - Important Variables"""
        print("\n" + "="*80)
        print("STEP 4: FEATURE SELECTION - IDENTIFYING IMPORTANT VARIABLES")
        print("="*80)
        
        print(f"\n📊 Selected {len(feature_cols)} features:")
        print("\nFeature Categories:")
        print("  1. Temporal Features:")
        print("     - year_normalized: Normalized year (time trend)")
        print("\n  2. Volume Features:")
        print("     - total_examinees: Number of exam takers")
        print("\n  3. Attempt Pattern Features:")
        print("     - first_timer_ratio: Percentage of first-time takers")
        print("     - repeater_ratio: Percentage of repeaters")
        print("\n  4. Performance Features:")
        print("     - fail_rate: Failure percentage")
        print("     - conditional_rate: Conditional pass percentage")
        print("     - passing_rate_ma3: 3-period moving average of passing rate")
        print("\n  5. Exam Type Features (One-Hot Encoded):")
        exam_features = [f for f in feature_cols if f.startswith('exam_')]
        for ef in exam_features:
            print(f"     - {ef}")
        
        # Calculate feature importance using Random Forest
        from sklearn.ensemble import RandomForestRegressor
        rf_temp = RandomForestRegressor(n_estimators=100, random_state=42)
        rf_temp.fit(X_train, self.y_train_temp)
        
        feature_importance = pd.DataFrame({
            'feature': feature_cols,
            'importance': rf_temp.feature_importances_
        }).sort_values('importance', ascending=False)
        
        print("\n🎯 Feature Importance Ranking (using Random Forest):")
        for idx, row in feature_importance.iterrows():
            print(f"   {row['feature']:.<40} {row['importance']:.4f}")
        
        self.report['step4_feature_selection'] = {
            'total_features': len(feature_cols),
            'feature_list': feature_cols,
            'feature_categories': {
                'temporal': ['year_normalized'],
                'volume': ['total_examinees'],
                'attempt_patterns': ['first_timer_ratio', 'repeater_ratio'],
                'performance': ['fail_rate', 'conditional_rate', 'passing_rate_ma3'],
                'exam_types': exam_features
            },
            'feature_importance': feature_importance.to_dict('records')
        }
        
        # Save feature importance graph
        self._plot_feature_importance(feature_importance)
        
        return feature_importance
    
    def step5_model_selection(self):
        """STEP 5: MODEL SELECTION - Regression Algorithms"""
        print("\n" + "="*80)
        print("STEP 5: MODEL SELECTION - REGRESSION ALGORITHMS")
        print("="*80)
        
        models = {
            'Linear Regression': {
                'model': LinearRegression(),
                'description': 'Basic linear relationship model',
                'use_case': 'Simple trends, baseline model'
            },
            'Ridge Regression': {
                'model': Ridge(alpha=1.0),
                'description': 'Linear regression with L2 regularization',
                'use_case': 'Prevents overfitting, handles multicollinearity'
            },
            'Lasso Regression': {
                'model': Lasso(alpha=0.1),
                'description': 'Linear regression with L1 regularization',
                'use_case': 'Feature selection, sparse models'
            },
            'Random Forest': {
                'model': RandomForestRegressor(n_estimators=100, random_state=42),
                'description': 'Ensemble of decision trees',
                'use_case': 'Non-linear patterns, robust to outliers'
            },
            'Gradient Boosting': {
                'model': GradientBoostingRegressor(n_estimators=100, random_state=42),
                'description': 'Sequential boosting algorithm',
                'use_case': 'High accuracy, complex patterns'
            },
            'XGBoost': {
                'model': xgb.XGBRegressor(n_estimators=100, random_state=42),
                'description': 'Optimized gradient boosting',
                'use_case': 'Best performance, handles missing data'
            },
            'Support Vector Regression': {
                'model': SVR(kernel='rbf'),
                'description': 'Support vector machine for regression',
                'use_case': 'Non-linear patterns, robust'
            }
        }
        
        print("\n📚 Selected Models for Comparison:")
        for i, (name, info) in enumerate(models.items(), 1):
            print(f"\n{i}. {name}")
            print(f"   Description: {info['description']}")
            print(f"   Use Case: {info['use_case']}")
        
        self.report['step5_model_selection'] = {
            'total_models': len(models),
            'models': {name: {
                'description': info['description'],
                'use_case': info['use_case']
            } for name, info in models.items()}
        }
        
        return {name: info['model'] for name, info in models.items()}
    
    def step6_model_training(self, models, X_train, y_train):
        """STEP 6: MODEL TRAINING"""
        print("\n" + "="*80)
        print("STEP 6: MODEL TRAINING ON 80% TRAINING DATA")
        print("="*80)
        
        scaler = StandardScaler()
        X_train_scaled = scaler.fit_transform(X_train)
        
        trained_models = {}
        training_results = []
        
        for name, model in models.items():
            print(f"\n🔧 Training {name}...")
            
            try:
                start_time = datetime.now()
                
                # Train model
                if name in ['Support Vector Regression', 'Ridge Regression', 'Lasso Regression']:
                    model.fit(X_train_scaled, y_train)
                    y_pred_train = model.predict(X_train_scaled)
                else:
                    model.fit(X_train, y_train)
                    y_pred_train = model.predict(X_train)
                
                end_time = datetime.now()
                training_time = (end_time - start_time).total_seconds()
                
                # Training metrics
                train_r2 = r2_score(y_train, y_pred_train)
                train_mse = mean_squared_error(y_train, y_pred_train)
                train_rmse = np.sqrt(train_mse)
                train_mae = mean_absolute_error(y_train, y_pred_train)
                
                print(f"   ✓ Training completed in {training_time:.2f} seconds")
                print(f"   ✓ Training R² Score: {train_r2:.4f}")
                print(f"   ✓ Training MAE: {train_mae:.2f}%")
                
                trained_models[name] = model
                training_results.append({
                    'model': name,
                    'training_time': training_time,
                    'train_r2': train_r2,
                    'train_mse': train_mse,
                    'train_rmse': train_rmse,
                    'train_mae': train_mae
                })
                
            except Exception as e:
                print(f"   ✗ Error: {str(e)}")
        
        self.report['step6_model_training'] = {
            'models_trained': len(trained_models),
            'training_results': training_results
        }
        
        return trained_models, scaler
    
    def step7_model_evaluation(self, trained_models, X_train, X_test, y_train, y_test, scaler):
        """STEP 7: MODEL TESTING AND EVALUATION"""
        print("\n" + "="*80)
        print("STEP 7: MODEL TESTING AND EVALUATION ON 20% TEST DATA")
        print("="*80)
        
        X_train_scaled = scaler.transform(X_train)
        X_test_scaled = scaler.transform(X_test)
        
        evaluation_results = []
        
        for name, model in trained_models.items():
            print(f"\n🧪 Testing {name}...")
            
            try:
                # Make predictions on test set
                if name in ['Support Vector Regression', 'Ridge Regression', 'Lasso Regression']:
                    y_pred_test = model.predict(X_test_scaled)
                else:
                    y_pred_test = model.predict(X_test)
                
                # Test metrics
                test_r2 = r2_score(y_test, y_pred_test)
                test_mse = mean_squared_error(y_test, y_pred_test)
                test_rmse = np.sqrt(test_mse)
                test_mae = mean_absolute_error(y_test, y_pred_test)
                
                # Cross-validation
                if name in ['Support Vector Regression', 'Ridge Regression', 'Lasso Regression']:
                    cv_scores = cross_val_score(model, X_train_scaled, y_train, cv=5, scoring='r2')
                else:
                    cv_scores = cross_val_score(model, X_train, y_train, cv=5, scoring='r2')
                
                cv_mean = cv_scores.mean()
                cv_std = cv_scores.std()
                
                print(f"   ✓ Test R² Score: {test_r2:.4f}")
                print(f"   ✓ Test RMSE: {test_rmse:.2f}%")
                print(f"   ✓ Test MAE: {test_mae:.2f}%")
                print(f"   ✓ Cross-Validation: {cv_mean:.4f} (±{cv_std:.4f})")
                
                evaluation_results.append({
                    'model': name,
                    'test_r2': test_r2,
                    'test_mse': test_mse,
                    'test_rmse': test_rmse,
                    'test_mae': test_mae,
                    'cv_mean': cv_mean,
                    'cv_std': cv_std,
                    'predictions': y_pred_test.tolist()
                })
                
            except Exception as e:
                print(f"   ✗ Error: {str(e)}")
        
        # Find best model
        best_idx = max(range(len(evaluation_results)), 
                      key=lambda i: evaluation_results[i]['test_r2'])
        best_model_name = evaluation_results[best_idx]['model']
        
        print(f"\n{'='*80}")
        print(f"🏆 BEST MODEL: {best_model_name}")
        print(f"   R² Score: {evaluation_results[best_idx]['test_r2']:.4f}")
        print(f"   RMSE: {evaluation_results[best_idx]['test_rmse']:.2f}%")
        print(f"   MAE: {evaluation_results[best_idx]['test_mae']:.2f}%")
        print(f"{'='*80}")
        
        self.report['step7_model_evaluation'] = {
            'evaluation_results': evaluation_results,
            'best_model': best_model_name,
            'best_model_metrics': evaluation_results[best_idx]
        }
        
        return evaluation_results, best_model_name, y_test
    
    def step8_metrics_and_predictions(self, evaluation_results, y_test):
        """STEP 8: EVALUATION METRICS AND PREDICTION GENERATION"""
        print("\n" + "="*80)
        print("STEP 8: COMPREHENSIVE EVALUATION METRICS & PREDICTIONS")
        print("="*80)
        
        # Detailed metrics explanation
        print("\n📊 EVALUATION METRICS EXPLAINED:")
        print("\n1. R² Score (Coefficient of Determination):")
        print("   - Measures how well predictions match actual values")
        print("   - Range: 0 to 1 (higher is better)")
        print("   - 1.0 = perfect predictions, 0.0 = no better than average")
        
        print("\n2. Mean Squared Error (MSE):")
        print("   - Average of squared differences between predicted and actual")
        print("   - Lower is better")
        print("   - Penalizes large errors more heavily")
        
        print("\n3. Root Mean Squared Error (RMSE):")
        print("   - Square root of MSE")
        print("   - Same unit as target variable (percentage points)")
        print("   - Easier to interpret than MSE")
        
        print("\n4. Mean Absolute Error (MAE):")
        print("   - Average absolute difference between predicted and actual")
        print("   - Most interpretable metric")
        print("   - Example: MAE of 5.0 means predictions are off by ±5% on average")
        
        print("\n5. Cross-Validation Score:")
        print("   - Tests model on multiple data splits")
        print("   - Ensures model generalizes well")
        print("   - Shows consistency across different data subsets")
        
        # For classification metrics (if we classify as Pass/Fail based on threshold)
        print("\n\n📈 ADDITIONAL METRICS (Classification View):")
        print("   Converting regression to classification using 50% passing threshold:")
        
        best_result = max(evaluation_results, key=lambda x: x['test_r2'])
        y_pred = np.array(best_result['predictions'])
        
        # Convert to binary classification (Pass >= 50%, Fail < 50%)
        y_test_binary = (y_test >= 50).astype(int)
        y_pred_binary = (y_pred >= 50).astype(int)
        
        accuracy = accuracy_score(y_test_binary, y_pred_binary)
        
        print(f"\n   Accuracy: {accuracy:.4f} ({accuracy*100:.2f}%)")
        print("   - Percentage of correct pass/fail classifications")
        
        if len(np.unique(y_test_binary)) > 1 and len(np.unique(y_pred_binary)) > 1:
            precision = precision_score(y_test_binary, y_pred_binary, zero_division=0)
            recall = recall_score(y_test_binary, y_pred_binary, zero_division=0)
            
            print(f"   Precision: {precision:.4f}")
            print("   - Of predicted passes, how many actually passed")
            
            print(f"   Recall: {recall:.4f}")
            print("   - Of actual passes, how many were correctly predicted")
            
            # Confusion matrix
            cm = confusion_matrix(y_test_binary, y_pred_binary)
            print(f"\n   Confusion Matrix:")
            print(f"                 Predicted Fail  Predicted Pass")
            print(f"   Actual Fail:        {cm[0,0]:>4}            {cm[0,1]:>4}")
            print(f"   Actual Pass:        {cm[1,0]:>4}            {cm[1,1]:>4}")
            
            classification_metrics = {
                'accuracy': float(accuracy),
                'precision': float(precision),
                'recall': float(recall),
                'confusion_matrix': cm.tolist()
            }
        else:
            classification_metrics = {
                'accuracy': float(accuracy),
                'note': 'Precision and recall not calculated (single class in predictions)'
            }
        
        # Summary table
        print("\n\n" + "="*80)
        print("📋 MODEL COMPARISON SUMMARY")
        print("="*80)
        print(f"{'Model':<30} {'R² Score':>10} {'RMSE':>10} {'MAE':>10} {'CV Score':>12}")
        print("-"*80)
        for result in sorted(evaluation_results, key=lambda x: x['test_r2'], reverse=True):
            print(f"{result['model']:<30} {result['test_r2']:>10.4f} "
                  f"{result['test_rmse']:>10.2f} {result['test_mae']:>10.2f} "
                  f"{result['cv_mean']:>12.4f}")
        
        self.report['step8_metrics_and_predictions'] = {
            'regression_metrics': {
                'r2_score': 'Coefficient of determination (0-1, higher better)',
                'mse': 'Mean Squared Error (lower better)',
                'rmse': 'Root Mean Squared Error in % (lower better)',
                'mae': 'Mean Absolute Error in % (lower better)',
                'cross_validation': '5-fold CV score for generalization'
            },
            'classification_metrics': classification_metrics,
            'model_rankings': sorted(evaluation_results, 
                                    key=lambda x: x['test_r2'], 
                                    reverse=True)
        }
        
        return classification_metrics
    
    def _prepare_features(self, df):
        """Helper: Prepare features for modeling"""
        # Convert to float
        numeric_cols = ['passed', 'failed', 'conditional', 'total_examinees']
        for col in numeric_cols:
            if col in df.columns:
                df[col] = pd.to_numeric(df[col], errors='coerce')
        
        # Calculate rates
        df['passing_rate'] = (df['passed'] / df['total_examinees'] * 100).round(2)
        df['fail_rate'] = (df['failed'] / df['total_examinees'] * 100).round(2)
        df['conditional_rate'] = (df['conditional'] / df['total_examinees'] * 100).round(2)
        
        # Attempt ratios
        df['first_timer_ratio'] = (df['take_attempts'] == 'First Timer').astype(int) * 100
        df['repeater_ratio'] = (df['take_attempts'] == 'Repeater').astype(int) * 100
        
        # Time features
        df['year_normalized'] = df['year'] - df['year'].min()
        
        # Moving average
        if len(df) > 3:
            df = df.sort_values(['board_exam_type', 'year', 'month'])
            df['passing_rate_ma3'] = df.groupby('board_exam_type')['passing_rate'].transform(
                lambda x: x.rolling(window=3, min_periods=1).mean()
            )
        else:
            df['passing_rate_ma3'] = df['passing_rate']
        
        # One-hot encode exam types
        exam_dummies = pd.get_dummies(df['board_exam_type'], prefix='exam')
        df = pd.concat([df, exam_dummies], axis=1)
        
        return df
    
    def _plot_feature_importance(self, feature_importance):
        """Plot feature importance"""
        plt.figure(figsize=(12, 8))
        
        top_features = feature_importance.head(10)
        colors = plt.cm.BuGn(np.linspace(0.4, 0.8, len(top_features)))
        
        plt.barh(range(len(top_features)), top_features['importance'], color=colors)
        plt.yticks(range(len(top_features)), top_features['feature'])
        plt.xlabel('Importance Score', fontweight='bold')
        plt.title('Top 10 Most Important Features', fontweight='bold', fontsize=14)
        plt.gca().invert_yaxis()
        plt.tight_layout()
        
        plt.savefig(os.path.join(self.output_dir, 'graphs', 'feature_importance.png'), 
                   dpi=300, bbox_inches='tight')
        plt.close()
        
        print(f"\n   ✓ Feature importance graph saved")
    
    def generate_complete_report(self):
        """Run complete validation and generate report"""
        print("\n" + "="*80)
        print("BOARD EXAM PREDICTION - COMPLETE VALIDATION REPORT")
        print("="*80)
        print(f"Generated: {datetime.now().strftime('%B %d, %Y at %I:%M %p')}")
        print("="*80)
        
        # Execute all steps
        df = self.step1_data_collection()
        if df is None:
            print("Failed to collect data. Exiting.")
            return
        
        df_clean = self.step2_data_cleaning(df)
        X_train, X_test, y_train, y_test, feature_cols = self.step3_data_splitting(df_clean)
        
        # Store y_train temporarily for feature selection
        self.y_train_temp = y_train
        
        feature_importance = self.step4_feature_selection(X_train, feature_cols)
        models = self.step5_model_selection()
        trained_models, scaler = self.step6_model_training(models, X_train, y_train)
        evaluation_results, best_model_name, y_test = self.step7_model_evaluation(
            trained_models, X_train, X_test, y_train, y_test, scaler
        )
        classification_metrics = self.step8_metrics_and_predictions(evaluation_results, y_test)
        
        # Save complete report
        report_path = os.path.join(self.output_dir, 'validation_report.json')
        with open(report_path, 'w') as f:
            json.dump(self.report, f, indent=2, default=str)
        
        print(f"\n\n{'='*80}")
        print("✅ VALIDATION COMPLETE!")
        print(f"{'='*80}")
        print(f"📄 Full report saved to: {report_path}")
        print(f"📊 Graphs saved to: {os.path.join(self.output_dir, 'graphs')}")
        print(f"\n🏆 Best Model: {best_model_name}")
        print(f"{'='*80}\n")
        
        return self.report

if __name__ == "__main__":
    db_config = {
        'host': 'localhost',
        'user': 'root',
        'password': '',
        'database': 'project_db'
    }
    
    validator = BoardExamValidationReport(db_config)
    report = validator.generate_complete_report()
