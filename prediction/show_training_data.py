import mysql.connector
import pandas as pd
import numpy as np
from sklearn.model_selection import train_test_split
from datetime import datetime
import os

class TrainingDataExtractor:
    """Extract and display the 33 training records used in model training"""
    
    def __init__(self):
        self.db_config = {
            'host': 'localhost',
            'user': 'root',
            'password': '',
            'database': 'project_db'
        }
        self.output_dir = 'output'
        os.makedirs(self.output_dir, exist_ok=True)
    
    def collect_data(self):
        """Collect data from database"""
        print("\n" + "="*100)
        print("EXTRACTING 33 TRAINING RECORDS")
        print("="*100)
        
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
            print(f"\n✓ Collected {len(df)} raw records from database")
            
            return df
            
        except Exception as e:
            print(f"✗ Error: {e}")
            return None
    
    def prepare_and_aggregate(self, df):
        """Clean and aggregate data"""
        print(f"\n✓ Aggregating data by Year, Month, Exam Type, and Attempts...")
        
        # Aggregate
        aggregated = df.groupby(['year', 'month', 'board_exam_type', 'take_attempts']).agg({
            'id': 'count',
            'result': lambda x: {
                'total': len(x),
                'passed': (x == 'Passed').sum(),
                'failed': (x == 'Failed').sum(),
                'conditional': (x == 'Conditional').sum()
            }
        }).reset_index()
        
        # Flatten
        aggregated['total_examinees'] = aggregated['result'].apply(lambda x: x['total'])
        aggregated['passed'] = aggregated['result'].apply(lambda x: x['passed'])
        aggregated['failed'] = aggregated['result'].apply(lambda x: x['failed'])
        aggregated['conditional'] = aggregated['result'].apply(lambda x: x['conditional'])
        aggregated = aggregated.drop('result', axis=1)
        aggregated = aggregated.drop('id', axis=1)
        
        # Calculate passing rate
        aggregated['passing_rate'] = (aggregated['passed'] / aggregated['total_examinees'] * 100).round(2)
        
        # Add features
        aggregated = self._add_features(aggregated)
        
        print(f"✓ Aggregated into {len(aggregated)} records")
        
        return aggregated
    
    def _add_features(self, df):
        """Add features needed for modeling"""
        # Convert to float
        numeric_cols = ['passed', 'failed', 'conditional', 'total_examinees']
        for col in numeric_cols:
            if col in df.columns:
                df[col] = pd.to_numeric(df[col], errors='coerce')
        
        # Calculate rates
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
    
    def split_data(self, df):
        """Split into training and testing sets (80/20)"""
        print(f"\n✓ Splitting data: 80% training, 20% testing...")
        
        # Get feature columns
        exam_cols = [col for col in df.columns if col.startswith('exam_')]
        feature_cols = ['year_normalized', 'total_examinees', 'first_timer_ratio', 
                       'repeater_ratio', 'fail_rate', 'conditional_rate', 
                       'passing_rate_ma3'] + exam_cols
        
        X = df[feature_cols]
        y = df['passing_rate']
        
        # Split with same random state as validation
        X_train, X_test, y_train, y_test, idx_train, idx_test = train_test_split(
            X, y, df.index, test_size=0.2, random_state=42, shuffle=True
        )
        
        # Get full records for training set
        training_records = df.loc[idx_train].copy()
        testing_records = df.loc[idx_test].copy()
        
        print(f"✓ Training set: {len(training_records)} records")
        print(f"✓ Testing set: {len(testing_records)} records")
        
        return training_records, testing_records
    
    def display_training_table(self, training_records):
        """Display training records in detailed table"""
        print("\n" + "="*120)
        print("TABLE: 33 TRAINING RECORDS USED FOR MODEL TRAINING")
        print("="*120)
        
        # Select columns to display
        display_cols = ['year', 'month', 'board_exam_type', 'take_attempts', 
                       'total_examinees', 'passed', 'failed', 'conditional', 
                       'passing_rate', 'fail_rate']
        
        display_df = training_records[display_cols].sort_values(['year', 'month', 'board_exam_type']).reset_index(drop=True)
        display_df.index = display_df.index + 1  # Start from 1
        
        # Print table header
        print(f"\n{'No.':<5} {'Year':<6} {'Month':<7} {'Exam Type':<45} {'Attempts':<13} "
              f"{'Total':<7} {'Passed':<8} {'Failed':<8} {'Cond':<6} {'Pass%':<8} {'Fail%':<8}")
        print("-" * 120)
        
        # Print each row
        for idx, row in display_df.iterrows():
            exam_short = row['board_exam_type'][:42] + '...' if len(row['board_exam_type']) > 45 else row['board_exam_type']
            attempts_short = row['take_attempts'][:10]
            
            print(f"{idx:<5} {int(row['year']):<6} {int(row['month']):<7} {exam_short:<45} {attempts_short:<13} "
                  f"{int(row['total_examinees']):<7} {int(row['passed']):<8} {int(row['failed']):<8} "
                  f"{int(row['conditional']):<6} {row['passing_rate']:<8.2f} {row['fail_rate']:<8.2f}")
        
        print("-" * 120)
        print(f"Total Training Records: {len(display_df)}")
        print("="*120)
        
        return display_df
    
    def save_to_csv(self, training_records, testing_records):
        """Save training and testing records to CSV"""
        
        # Training data CSV
        training_file = os.path.join(self.output_dir, 'training_data_33_records.csv')
        training_records.to_csv(training_file, index=False)
        print(f"\n✓ Training data saved to: {training_file}")
        
        # Testing data CSV
        testing_file = os.path.join(self.output_dir, 'testing_data_9_records.csv')
        testing_records.to_csv(testing_file, index=False)
        print(f"✓ Testing data saved to: {testing_file}")
        
        # Summary statistics
        stats_file = os.path.join(self.output_dir, 'training_data_summary.txt')
        with open(stats_file, 'w') as f:
            f.write("="*100 + "\n")
            f.write("TRAINING DATA (33 RECORDS) - SUMMARY STATISTICS\n")
            f.write("="*100 + "\n\n")
            
            f.write("1. DISTRIBUTION BY YEAR:\n")
            year_dist = training_records['year'].value_counts().sort_index()
            for year, count in year_dist.items():
                f.write(f"   {year}: {count} records\n")
            
            f.write("\n2. DISTRIBUTION BY EXAM TYPE:\n")
            exam_dist = training_records['board_exam_type'].value_counts()
            for exam, count in exam_dist.items():
                f.write(f"   {exam}: {count} records\n")
            
            f.write("\n3. DISTRIBUTION BY ATTEMPTS:\n")
            attempts_dist = training_records['take_attempts'].value_counts()
            for attempt, count in attempts_dist.items():
                f.write(f"   {attempt}: {count} records\n")
            
            f.write("\n4. PASSING RATE STATISTICS:\n")
            f.write(f"   Minimum: {training_records['passing_rate'].min():.2f}%\n")
            f.write(f"   Maximum: {training_records['passing_rate'].max():.2f}%\n")
            f.write(f"   Mean: {training_records['passing_rate'].mean():.2f}%\n")
            f.write(f"   Median: {training_records['passing_rate'].median():.2f}%\n")
            f.write(f"   Std Dev: {training_records['passing_rate'].std():.2f}%\n")
            
            f.write("\n5. TOTAL EXAMINEES STATISTICS:\n")
            f.write(f"   Total examinees across all records: {int(training_records['total_examinees'].sum())}\n")
            f.write(f"   Average per record: {training_records['total_examinees'].mean():.1f}\n")
            f.write(f"   Total passed: {int(training_records['passed'].sum())}\n")
            f.write(f"   Total failed: {int(training_records['failed'].sum())}\n")
            f.write(f"   Total conditional: {int(training_records['conditional'].sum())}\n")
            
            f.write("\n" + "="*100 + "\n")
        
        print(f"✓ Summary statistics saved to: {stats_file}")
    
    def generate_table(self):
        """Main function to generate training data table"""
        print(f"\nGenerated: {datetime.now().strftime('%B %d, %Y at %I:%M %p')}\n")
        
        # Collect data
        df = self.collect_data()
        if df is None:
            return
        
        # Aggregate
        aggregated = self.prepare_and_aggregate(df)
        
        # Split
        training_records, testing_records = self.split_data(aggregated)
        
        # Display
        self.display_training_table(training_records)
        
        # Save
        self.save_to_csv(training_records, testing_records)
        
        print(f"\n{'='*120}")
        print("✅ EXTRACTION COMPLETE!")
        print(f"{'='*120}")
        print(f"\n📊 Files created in '{self.output_dir}/' folder:")
        print("   1. training_data_33_records.csv - Full training dataset")
        print("   2. testing_data_9_records.csv - Testing dataset")
        print("   3. training_data_summary.txt - Statistical summary")
        print(f"\n{'='*120}\n")

if __name__ == "__main__":
    extractor = TrainingDataExtractor()
    extractor.generate_table()
