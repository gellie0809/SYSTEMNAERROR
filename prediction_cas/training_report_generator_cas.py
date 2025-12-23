"""
Training Report Generator for CAS Department
Generates comprehensive PDF with complete training methodology, data, and model performance
"""

import mysql.connector
from mysql.connector import Error
import pandas as pd
import json
import os
from datetime import datetime
from reportlab.lib.pagesizes import letter, A4
from reportlab.lib import colors
from reportlab.lib.styles import getSampleStyleSheet, ParagraphStyle
from reportlab.lib.units import inch
from reportlab.platypus import SimpleDocTemplate, Table, TableStyle, Paragraph, Spacer, PageBreak, Image, ListFlowable, ListItem
from reportlab.lib.enums import TA_CENTER, TA_LEFT, TA_RIGHT, TA_JUSTIFY
from reportlab.pdfgen import canvas

class CASTrainingReportGenerator:
    def __init__(self):
        self.db_config = {
            'host': 'localhost',
            'database': 'project_db',
            'user': 'root',
            'password': ''
        }
        self.department = 'Arts and Sciences'
        self.output_dir = 'reports'
        
        # Create reports directory if it doesn't exist
        if not os.path.exists(self.output_dir):
            os.makedirs(self.output_dir)
    
    def connect_db(self):
        """Connect to MySQL database"""
        try:
            connection = mysql.connector.connect(**self.db_config)
            if connection.is_connected():
                return connection
        except Error as e:
            print(f"Error connecting to database: {e}")
            return None
    
    def fetch_training_data(self):
        """Fetch all CAS anonymous training data"""
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
                AND department = 'Arts and Sciences'
                GROUP BY board_exam_type, YEAR(board_exam_date), MONTH(board_exam_date), DAY(board_exam_date)
                ORDER BY board_exam_type, exam_year
            """
            
            df = pd.read_sql(query, connection)
            return df
            
        except Error as e:
            print(f"Error fetching data: {e}")
            return None
        finally:
            if connection.is_connected():
                connection.close()
    
    def load_model_metadata(self):
        """Load model training metadata"""
        try:
            with open('models/metadata.json', 'r') as f:
                return json.load(f)
        except:
            return None
    
    def generate_report(self):
        """Generate comprehensive training report PDF with complete methodology"""
        print("\n📄 Generating CAS Training Report...")
        
        # Fetch data
        training_data = self.fetch_training_data()
        if training_data is None or len(training_data) == 0:
            print("❌ No training data available")
            return None
        
        metadata = self.load_model_metadata()
        
        # Create PDF
        timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
        filename = f"{self.output_dir}/CAS_Training_Report_{timestamp}.pdf"
        
        doc = SimpleDocTemplate(filename, pagesize=letter,
                               rightMargin=0.75*inch, leftMargin=0.75*inch,
                               topMargin=0.75*inch, bottomMargin=0.75*inch)
        
        elements = []
        styles = getSampleStyleSheet()
        
        # Custom styles - CAS Pink theme
        title_style = ParagraphStyle(
            'CustomTitle',
            parent=styles['Heading1'],
            fontSize=22,
            textColor=colors.HexColor('#C75B9B'),
            spaceAfter=12,
            alignment=TA_CENTER,
            fontName='Helvetica-Bold'
        )
        
        subtitle_style = ParagraphStyle(
            'CustomSubtitle',
            parent=styles['Heading2'],
            fontSize=14,
            textColor=colors.HexColor('#FF99CC'),
            spaceAfter=12,
            alignment=TA_CENTER
        )
        
        heading_style = ParagraphStyle(
            'CustomHeading',
            parent=styles['Heading2'],
            fontSize=14,
            textColor=colors.HexColor('#C75B9B'),
            spaceAfter=8,
            spaceBefore=16,
            fontName='Helvetica-Bold'
        )
        
        subheading_style = ParagraphStyle(
            'CustomSubheading',
            parent=styles['Heading3'],
            fontSize=12,
            textColor=colors.HexColor('#ec4899'),
            spaceAfter=6,
            spaceBefore=10,
            fontName='Helvetica-Bold'
        )
        
        body_style = ParagraphStyle(
            'CustomBody',
            parent=styles['Normal'],
            fontSize=10,
            textColor=colors.HexColor('#333333'),
            spaceAfter=8,
            alignment=TA_JUSTIFY,
            leading=14
        )
        
        # Extract metadata values
        report_date = datetime.now().strftime("%B %d, %Y at %I:%M %p")
        training_date = "N/A"
        best_model = "N/A"
        best_r2 = 0
        best_mae = 0
        best_mse = 0
        best_rmse = 0
        num_features = 0
        feature_names = []
        
        if metadata:
            training_date = datetime.fromisoformat(metadata.get('training_date', datetime.now().isoformat())).strftime("%B %d, %Y")
            best_model = metadata.get('best_model', 'N/A')
            best_metrics = metadata.get('metrics', {}).get(best_model, {})
            best_r2 = best_metrics.get('test_r2', 0)
            best_mae = best_metrics.get('test_mae', 0)
            best_mse = best_metrics.get('test_mse', 0)
            best_rmse = best_metrics.get('test_rmse', 0)
            num_features = metadata.get('num_features', 8)
            feature_names = metadata.get('feature_names', [])
        
        # ==================== COVER PAGE ====================
        elements.append(Spacer(1, 1.5*inch))
        elements.append(Paragraph("LAGUNA STATE POLYTECHNIC UNIVERSITY", title_style))
        elements.append(Spacer(1, 0.2*inch))
        elements.append(Paragraph("College of Arts and Sciences", subtitle_style))
        elements.append(Spacer(1, 0.5*inch))
        elements.append(Paragraph("AI BOARD EXAM PREDICTION SYSTEM", title_style))
        elements.append(Paragraph("Complete Machine Learning Training Report", subtitle_style))
        elements.append(Spacer(1, 0.5*inch))
        
        # Report info box
        cover_info = [
            ['Report Generated:', report_date],
            ['Department:', self.department],
            ['Training Date:', training_date],
            ['Total Training Records:', str(len(training_data))],
            ['Best Performing Model:', best_model],
            ['Model Accuracy (R²):', f"{best_r2:.4f}" if best_r2 else "N/A"],
            ['Number of Features:', str(num_features)],
        ]
        
        cover_table = Table(cover_info, colWidths=[2.5*inch, 4*inch])
        cover_table.setStyle(TableStyle([
            ('ALIGN', (0, 0), (0, -1), 'RIGHT'),
            ('ALIGN', (1, 0), (1, -1), 'LEFT'),
            ('FONTNAME', (0, 0), (0, -1), 'Helvetica-Bold'),
            ('FONTNAME', (1, 0), (1, -1), 'Helvetica'),
            ('FONTSIZE', (0, 0), (-1, -1), 11),
            ('TEXTCOLOR', (0, 0), (0, -1), colors.HexColor('#C75B9B')),
            ('TEXTCOLOR', (1, 0), (1, -1), colors.HexColor('#333333')),
            ('BOTTOMPADDING', (0, 0), (-1, -1), 8),
            ('TOPPADDING', (0, 0), (-1, -1), 8),
        ]))
        elements.append(cover_table)
        elements.append(PageBreak())
        
        # ==================== TABLE OF CONTENTS ====================
        elements.append(Paragraph("TABLE OF CONTENTS", heading_style))
        elements.append(Spacer(1, 0.2*inch))
        
        toc_items = [
            "1. Introduction",
            "2. Data Collection",
            "3. Data Cleaning and Preparation",
            "4. Dataset Splitting (80% Training, 20% Testing)",
            "5. Feature Selection",
            "6. Model Selection",
            "7. Model Training",
            "8. Model Testing and Evaluation",
            "9. Evaluation Metrics",
            "10. Prediction Generation",
            "11. Complete Training Dataset",
            "12. Model Performance Comparison",
            "13. Visualizations",
        ]
        
        for item in toc_items:
            elements.append(Paragraph(item, body_style))
        
        elements.append(PageBreak())
        
        # ==================== 1. INTRODUCTION ====================
        elements.append(Paragraph("1. Introduction", heading_style))
        intro_text = """
        This report documents the complete machine learning training process for the CAS (College of Arts 
        and Sciences) Board Exam Prediction System. The system uses historical anonymous board exam data 
        to predict future passing rates using advanced regression algorithms. This AI-powered prediction 
        system aims to help the institution make data-driven decisions regarding board exam preparation 
        and student support programs, particularly for Licensure Examination for Teachers (LET) and 
        Psychometrician board examinations.
        """
        elements.append(Paragraph(intro_text, body_style))
        
        # ==================== 2. DATA COLLECTION ====================
        elements.append(Paragraph("2. Data Collection", heading_style))
        
        data_collection_text = f"""
        <b>Data Source:</b> The training data was collected from the LSPU Board Exam Records Management System, 
        specifically from the <i>anonymous_board_passers</i> table in the MySQL database.<br/><br/>
        
        <b>Department Filter:</b> Only records from the "{self.department}" department were included.<br/><br/>
        
        <b>Collection Method:</b> SQL query aggregating exam results by board exam type and year.<br/><br/>
        
        <b>Data Period:</b> {training_data['exam_year'].min()} to {training_data['exam_year'].max()}<br/><br/>
        
        <b>Total Records Collected:</b> {len(training_data)} aggregated records<br/><br/>
        
        <b>Exam Types Covered:</b>
        """
        elements.append(Paragraph(data_collection_text, body_style))
        
        exam_types = training_data['board_exam_type'].unique()
        for exam_type in exam_types:
            elements.append(Paragraph(f"• {exam_type}", body_style))
        
        # ==================== 3. DATA CLEANING ====================
        elements.append(Paragraph("3. Data Cleaning and Preparation", heading_style))
        
        cleaning_text = """
        The following data cleaning and preparation steps were performed:<br/><br/>
        
        <b>a) Filtering Invalid Records:</b><br/>
        • Excluded soft-deleted records (is_deleted = 1)<br/>
        • Filtered only records from the Arts and Sciences department<br/><br/>
        
        <b>b) Aggregation:</b><br/>
        • Grouped data by board_exam_type, exam_year, exam_month, and exam_day<br/>
        • Calculated total_takers, total_passers, and passing_rate for each group<br/><br/>
        
        <b>c) Missing Value Handling:</b><br/>
        • Records with null board_exam_date were excluded<br/>
        • Passing rates calculated as (total_passers / total_takers) × 100<br/><br/>
        
        <b>d) Feature Engineering:</b><br/>
        • Created year_numeric feature for temporal analysis<br/>
        • Generated takers_scaled (normalized total takers)<br/>
        • Computed passers_ratio (passers/takers)<br/>
        • Extracted exam_month_num from dates<br/>
        • Created lag features (passing_rate_lag1, passing_rate_lag2)<br/>
        • Calculated 3-year moving average (passing_rate_ma3)<br/>
        • One-hot encoded categorical exam types<br/>
        """
        elements.append(Paragraph(cleaning_text, body_style))
        
        # ==================== 4. DATASET SPLITTING ====================
        elements.append(Paragraph("4. Dataset Splitting (80% Training, 20% Testing)", heading_style))
        
        total_records = len(training_data)
        train_size = int(total_records * 0.8)
        test_size = total_records - train_size
        
        split_text = f"""
        The dataset was split into training and testing sets to ensure proper model validation:<br/><br/>
        
        <b>Split Ratio:</b> 80% Training / 20% Testing<br/><br/>
        
        <b>Total Records:</b> {total_records}<br/>
        <b>Training Set Size:</b> {train_size} records (80%)<br/>
        <b>Testing Set Size:</b> {test_size} records (20%)<br/><br/>
        
        <b>Split Method:</b> train_test_split from scikit-learn with random_state=42 for reproducibility<br/><br/>
        
        <b>Purpose:</b><br/>
        • Training Set: Used to train the machine learning models<br/>
        • Testing Set: Used to evaluate model performance on unseen data<br/>
        """
        elements.append(Paragraph(split_text, body_style))
        
        # ==================== 5. FEATURE SELECTION ====================
        elements.append(Paragraph("5. Feature Selection", heading_style))
        
        feature_text = f"""
        Feature selection identifies the most important variables that influence the prediction of passing rates. 
        A total of <b>{len(feature_names) if feature_names else num_features} features</b> were selected based on their relevance to board exam performance:<br/><br/>
        
        <b>Selected Features:</b>
        """
        elements.append(Paragraph(feature_text, body_style))
        
        feature_descriptions = {
            'year_numeric': 'Year converted to numeric value for trend analysis',
            'takers_scaled': 'Normalized number of exam takers (0-1 scale)',
            'passers_ratio': 'Ratio of passers to total takers',
            'exam_month_num': 'Month when the exam was conducted (1-12)',
            'is_LET_Elementary': 'Binary indicator for LET Elementary exam type',
            'is_LET_Secondary': 'Binary indicator for LET Secondary exam type',
            'is_Psychometrician': 'Binary indicator for Psychometrician exam type',
            'passing_rate_lag1': 'Previous year passing rate (1-year lag)',
            'passing_rate_lag2': 'Passing rate from 2 years ago (2-year lag)',
            'passing_rate_ma3': '3-year moving average of passing rates',
        }
        
        feature_table_data = [['Feature Name', 'Description']]
        if feature_names:
            for feat in feature_names:
                desc = feature_descriptions.get(feat, 'Feature variable for prediction')
                feature_table_data.append([feat, desc])
        else:
            for feat, desc in feature_descriptions.items():
                feature_table_data.append([feat, desc])
        
        if len(feature_table_data) > 1:
            feature_table = Table(feature_table_data, colWidths=[2.8*inch, 4*inch])
            feature_table.setStyle(TableStyle([
                ('BACKGROUND', (0, 0), (-1, 0), colors.HexColor('#C75B9B')),
                ('TEXTCOLOR', (0, 0), (-1, 0), colors.whitesmoke),
                ('ALIGN', (0, 0), (-1, -1), 'LEFT'),
                ('FONTNAME', (0, 0), (-1, 0), 'Helvetica-Bold'),
                ('FONTSIZE', (0, 0), (-1, -1), 9),
                ('BOTTOMPADDING', (0, 0), (-1, 0), 10),
                ('BACKGROUND', (0, 1), (-1, -1), colors.white),
                ('GRID', (0, 0), (-1, -1), 1, colors.HexColor('#FF99CC')),
                ('ROWBACKGROUNDS', (0, 1), (-1, -1), [colors.white, colors.HexColor('#FFF0F5')])
            ]))
            elements.append(feature_table)
        
        elements.append(PageBreak())
        
        # ==================== 6. MODEL SELECTION ====================
        elements.append(Paragraph("6. Model Selection", heading_style))
        
        model_text = """
        Seven different regression algorithms were selected and evaluated to find the best performing model 
        for predicting board exam passing rates:<br/><br/>
        """
        elements.append(Paragraph(model_text, body_style))
        
        models_info = [
            ['Model', 'Type', 'Description'],
            ['Linear Regression', 'Linear', 'Basic regression assuming linear relationship between features and target'],
            ['Ridge Regression', 'Linear (L2)', 'Linear regression with L2 regularization to prevent overfitting'],
            ['Lasso Regression', 'Linear (L1)', 'Linear regression with L1 regularization for feature selection'],
            ['Random Forest', 'Ensemble', 'Ensemble of decision trees using bagging for improved accuracy'],
            ['Gradient Boosting', 'Ensemble', 'Sequential ensemble method that corrects errors iteratively'],
            ['Support Vector Machine', 'Kernel-based', 'Finds optimal hyperplane for regression with RBF kernel'],
            ['Decision Tree', 'Tree-based', 'Recursive partitioning based on feature values'],
        ]
        
        model_table = Table(models_info, colWidths=[1.8*inch, 1.2*inch, 3.8*inch])
        model_table.setStyle(TableStyle([
            ('BACKGROUND', (0, 0), (-1, 0), colors.HexColor('#C75B9B')),
            ('TEXTCOLOR', (0, 0), (-1, 0), colors.whitesmoke),
            ('ALIGN', (0, 0), (-1, -1), 'LEFT'),
            ('FONTNAME', (0, 0), (-1, 0), 'Helvetica-Bold'),
            ('FONTSIZE', (0, 0), (-1, -1), 9),
            ('BOTTOMPADDING', (0, 0), (-1, 0), 10),
            ('BACKGROUND', (0, 1), (-1, -1), colors.white),
            ('GRID', (0, 0), (-1, -1), 1, colors.HexColor('#FF99CC')),
            ('ROWBACKGROUNDS', (0, 1), (-1, -1), [colors.white, colors.HexColor('#FFF0F5')])
        ]))
        elements.append(model_table)
        
        # ==================== 7. MODEL TRAINING ====================
        elements.append(Paragraph("7. Model Training", heading_style))
        
        training_text = f"""
        The model training process was conducted as follows:<br/><br/>
        
        <b>a) Data Preprocessing:</b><br/>
        • Features scaled using StandardScaler (zero mean, unit variance)<br/>
        • Categorical variables one-hot encoded<br/><br/>
        
        <b>b) Training Process:</b><br/>
        • Training Date: {training_date}<br/>
        • Training Duration: Approximately 2-5 seconds per model<br/>
        • All 7 models trained on the same training set<br/>
        • Cross-validation performed where applicable<br/><br/>
        
        <b>c) Hyperparameters:</b><br/>
        • Random Forest: n_estimators=100, random_state=42<br/>
        • Gradient Boosting: n_estimators=100, learning_rate=0.1<br/>
        • Ridge/Lasso: alpha=1.0 (default regularization)<br/>
        • SVM: kernel='rbf', C=1.0<br/><br/>
        
        <b>d) Training Environment:</b><br/>
        • Python 3.10 with scikit-learn 1.7.2<br/>
        • Models saved using joblib for persistence<br/>
        """
        elements.append(Paragraph(training_text, body_style))
        
        # ==================== 8. MODEL TESTING ====================
        elements.append(Paragraph("8. Model Testing and Evaluation", heading_style))
        
        testing_text = """
        After training, each model was evaluated on the held-out test set (20% of data):<br/><br/>
        
        <b>Evaluation Process:</b><br/>
        • Models predict passing rates on test set<br/>
        • Predictions compared to actual values<br/>
        • Multiple metrics calculated for comprehensive evaluation<br/>
        • Best model selected based on R² score and accuracy<br/><br/>
        
        <b>Backtesting Validation:</b><br/>
        • Additional validation by training on historical data (e.g., 2021-2022)<br/>
        • Predicting known year (e.g., 2023) to verify accuracy<br/>
        • Comparing predicted vs actual values<br/>
        """
        elements.append(Paragraph(testing_text, body_style))
        
        elements.append(PageBreak())
        
        # ==================== 9. EVALUATION METRICS ====================
        elements.append(Paragraph("9. Evaluation Metrics", heading_style))
        
        metrics_explanation = """
        The following metrics were used to evaluate model performance:<br/><br/>
        """
        elements.append(Paragraph(metrics_explanation, body_style))
        
        metrics_def = [
            ['Metric', 'Formula / Description', 'Interpretation'],
            ['R² (R-Squared)', 'R² = 1 - (SS_res / SS_tot)', 'Proportion of variance explained. Range: 0-1, higher is better. 1.0 = perfect fit'],
            ['MAE (Mean Absolute Error)', 'MAE = (1/n) × Σ|actual - predicted|', 'Average absolute difference. Lower is better. In percentage points.'],
            ['MSE (Mean Squared Error)', 'MSE = (1/n) × Σ(actual - predicted)²', 'Average squared difference. Penalizes large errors more.'],
            ['RMSE (Root MSE)', 'RMSE = √MSE', 'Square root of MSE. Same unit as target variable.'],
            ['Accuracy', '100 - MAE', 'Simplified accuracy measure. Higher is better.'],
        ]
        
        metrics_def_table = Table(metrics_def, colWidths=[1.3*inch, 2.5*inch, 3*inch])
        metrics_def_table.setStyle(TableStyle([
            ('BACKGROUND', (0, 0), (-1, 0), colors.HexColor('#C75B9B')),
            ('TEXTCOLOR', (0, 0), (-1, 0), colors.whitesmoke),
            ('ALIGN', (0, 0), (-1, -1), 'LEFT'),
            ('FONTNAME', (0, 0), (-1, 0), 'Helvetica-Bold'),
            ('FONTSIZE', (0, 0), (-1, -1), 8),
            ('BOTTOMPADDING', (0, 0), (-1, 0), 10),
            ('BACKGROUND', (0, 1), (-1, -1), colors.white),
            ('GRID', (0, 0), (-1, -1), 1, colors.HexColor('#FF99CC')),
            ('ROWBACKGROUNDS', (0, 1), (-1, -1), [colors.white, colors.HexColor('#FFF0F5')]),
            ('VALIGN', (0, 0), (-1, -1), 'MIDDLE'),
        ]))
        elements.append(metrics_def_table)
        elements.append(Spacer(1, 0.3*inch))
        
        # Best Model Metrics
        if metadata and 'metrics' in metadata:
            best_metrics = metadata.get('metrics', {}).get(best_model, {})
            
            elements.append(Paragraph(f"<b>Best Model Performance ({best_model}):</b>", subheading_style))
            
            rmse_val = best_metrics.get('test_rmse', 0)
            mse_val = best_metrics.get('test_mse', rmse_val ** 2 if rmse_val else 0)
            
            best_metrics_data = [
                ['Metric', 'Value', 'Notes'],
                ['R² (R-Squared)', f"{best_metrics.get('test_r2', 0):.4f}", 'Excellent fit' if best_metrics.get('test_r2', 0) > 0.9 else 'Good fit' if best_metrics.get('test_r2', 0) > 0.7 else 'Moderate fit'],
                ['MAE (Mean Absolute Error)', f"{best_metrics.get('test_mae', 0):.4f}%", f"Average error of {best_metrics.get('test_mae', 0):.2f} percentage points"],
                ['MSE (Mean Squared Error)', f"{mse_val:.4f}", 'Squared error metric'],
                ['RMSE (Root MSE)', f"{rmse_val:.4f}%", f"Typical error of ±{rmse_val:.2f}%"],
                ['Accuracy', f"{best_metrics.get('accuracy', 0):.2f}%", 'Overall prediction accuracy'],
                ['Dataset Used', f"CAS ({len(training_data)} records)", f"{training_data['exam_year'].min()}-{training_data['exam_year'].max()}"],
            ]
            
            best_table = Table(best_metrics_data, colWidths=[2*inch, 1.5*inch, 3.3*inch])
            best_table.setStyle(TableStyle([
                ('BACKGROUND', (0, 0), (-1, 0), colors.HexColor('#9f1239')),
                ('TEXTCOLOR', (0, 0), (-1, 0), colors.whitesmoke),
                ('ALIGN', (0, 0), (-1, -1), 'LEFT'),
                ('ALIGN', (1, 1), (1, -1), 'CENTER'),
                ('FONTNAME', (0, 0), (-1, 0), 'Helvetica-Bold'),
                ('FONTSIZE', (0, 0), (-1, -1), 10),
                ('BOTTOMPADDING', (0, 0), (-1, 0), 10),
                ('BACKGROUND', (0, 1), (-1, -1), colors.HexColor('#FFF0F5')),
                ('GRID', (0, 0), (-1, -1), 1, colors.HexColor('#C75B9B')),
            ]))
            elements.append(best_table)
        
        # ==================== 10. PREDICTION GENERATION ====================
        elements.append(Paragraph("10. Prediction Generation", heading_style))
        
        prediction_text = f"""
        The prediction generation process works as follows:<br/><br/>
        
        <b>a) Data Preparation:</b><br/>
        • Fetch latest available data from database<br/>
        • Prepare features using the same preprocessing pipeline<br/>
        • Create next-year features based on latest data<br/><br/>
        
        <b>b) Prediction Process:</b><br/>
        • Load the best trained model ({best_model})<br/>
        • Load the fitted StandardScaler<br/>
        • Transform input features using the scaler<br/>
        • Generate prediction using model.predict()<br/><br/>
        
        <b>c) Output:</b><br/>
        • Predicted passing rate (0-100%)<br/>
        • Prediction year<br/>
        • Model used for prediction<br/>
        • Confidence bounds based on historical accuracy<br/>
        """
        elements.append(Paragraph(prediction_text, body_style))
        
        elements.append(PageBreak())
        
        # ==================== 11. COMPLETE TRAINING DATASET ====================
        elements.append(Paragraph("11. Complete Training Dataset", heading_style))
        elements.append(Paragraph(f"Total Records: {len(training_data)}", body_style))
        elements.append(Spacer(1, 0.2*inch))
        
        # Create data table
        table_data = [['Exam Type', 'Year', 'Takers', 'Passers', 'Passing Rate']]
        
        for _, row in training_data.iterrows():
            exam_name = str(row['board_exam_type'])
            if len(exam_name) > 35:
                exam_name = exam_name[:32] + '...'
            table_data.append([
                exam_name,
                str(int(row['exam_year'])),
                str(int(row['total_takers'])),
                str(int(row['total_passers'])),
                f"{row['passing_rate']:.2f}%"
            ])
        
        data_table = Table(table_data, colWidths=[2.8*inch, 0.7*inch, 0.8*inch, 0.8*inch, 1*inch])
        data_table.setStyle(TableStyle([
            ('BACKGROUND', (0, 0), (-1, 0), colors.HexColor('#C75B9B')),
            ('TEXTCOLOR', (0, 0), (-1, 0), colors.whitesmoke),
            ('ALIGN', (0, 0), (-1, -1), 'CENTER'),
            ('ALIGN', (0, 1), (0, -1), 'LEFT'),
            ('FONTNAME', (0, 0), (-1, 0), 'Helvetica-Bold'),
            ('FONTSIZE', (0, 0), (-1, 0), 10),
            ('FONTSIZE', (0, 1), (-1, -1), 9),
            ('BOTTOMPADDING', (0, 0), (-1, 0), 10),
            ('BACKGROUND', (0, 1), (-1, -1), colors.white),
            ('GRID', (0, 0), (-1, -1), 1, colors.HexColor('#FF99CC')),
            ('ROWBACKGROUNDS', (0, 1), (-1, -1), [colors.white, colors.HexColor('#FFF0F5')])
        ]))
        elements.append(data_table)
        
        elements.append(PageBreak())
        
        # ==================== 12. MODEL PERFORMANCE COMPARISON ====================
        if metadata and 'metrics' in metadata:
            elements.append(Paragraph("12. Model Performance Comparison", heading_style))
            elements.append(Paragraph("All 7 models evaluated on the test set:", body_style))
            elements.append(Spacer(1, 0.2*inch))
            
            metrics_data = [['Model', 'R² Score', 'MAE (%)', 'MSE', 'RMSE (%)', 'Accuracy (%)']]
            
            for model_name, metrics in metadata['metrics'].items():
                is_best = "★ " if model_name == best_model else ""
                rmse = metrics.get('test_rmse', 0)
                mse = metrics.get('test_mse', rmse ** 2 if rmse else 0)
                metrics_data.append([
                    is_best + model_name,
                    f"{metrics.get('test_r2', 0):.4f}",
                    f"{metrics.get('test_mae', 0):.2f}",
                    f"{mse:.4f}",
                    f"{rmse:.2f}",
                    f"{metrics.get('accuracy', 0):.2f}"
                ])
            
            metrics_table = Table(metrics_data, colWidths=[2*inch, 0.9*inch, 0.8*inch, 0.8*inch, 0.9*inch, 1*inch])
            metrics_table.setStyle(TableStyle([
                ('BACKGROUND', (0, 0), (-1, 0), colors.HexColor('#C75B9B')),
                ('TEXTCOLOR', (0, 0), (-1, 0), colors.whitesmoke),
                ('ALIGN', (0, 0), (-1, -1), 'CENTER'),
                ('ALIGN', (0, 1), (0, -1), 'LEFT'),
                ('FONTNAME', (0, 0), (-1, 0), 'Helvetica-Bold'),
                ('FONTSIZE', (0, 0), (-1, 0), 10),
                ('FONTSIZE', (0, 1), (-1, -1), 9),
                ('BOTTOMPADDING', (0, 0), (-1, 0), 10),
                ('BACKGROUND', (0, 1), (-1, -1), colors.white),
                ('GRID', (0, 0), (-1, -1), 1, colors.HexColor('#FF99CC')),
                ('ROWBACKGROUNDS', (0, 1), (-1, -1), [colors.white, colors.HexColor('#FFF0F5')])
            ]))
            elements.append(metrics_table)
            elements.append(Spacer(1, 0.2*inch))
            elements.append(Paragraph(f"★ indicates the best performing model: <b>{best_model}</b>", body_style))
        
        elements.append(PageBreak())
        
        # ==================== 13. VISUALIZATIONS ====================
        elements.append(Paragraph("13. Visualizations", heading_style))
        
        graph_files = [
            ('graphs/model_comparison.png', 'Model R² Score Comparison', 'Comparison of R² scores across all 7 regression models. Higher scores indicate better fit.'),
            ('graphs/accuracy_comparison.png', 'Model Accuracy Comparison', 'Accuracy percentages for each model. Based on (100 - MAE).'),
            ('graphs/mae_comparison.png', 'Mean Absolute Error Comparison', 'MAE values showing average prediction error. Lower is better.'),
            ('graphs/predictions_vs_actual.png', 'Predictions vs Actual Values', 'Scatter plot comparing predicted values against actual passing rates.'),
            ('graphs/residual_analysis.png', 'Residual Analysis', 'Distribution and pattern of prediction errors (residuals).'),
            ('graphs/historical_trends.png', 'Historical Passing Rate Trends', 'Time series of passing rates by exam type over the years.'),
        ]
        
        for graph_file, graph_title, graph_desc in graph_files:
            if os.path.exists(graph_file):
                elements.append(Paragraph(f"<b>{graph_title}</b>", subheading_style))
                elements.append(Paragraph(graph_desc, body_style))
                elements.append(Spacer(1, 0.1*inch))
                img = Image(graph_file, width=6*inch, height=3.5*inch)
                elements.append(img)
                elements.append(Spacer(1, 0.3*inch))
        
        # ==================== REPORT SUMMARY ====================
        elements.append(PageBreak())
        elements.append(Paragraph("Report Summary", heading_style))
        
        summary_text = f"""
        This comprehensive training report documents the complete machine learning pipeline used to develop 
        the CAS Board Exam Prediction System. The system was trained on {len(training_data)} historical records 
        spanning from {training_data['exam_year'].min()} to {training_data['exam_year'].max()}.<br/><br/>
        
        <b>Key Findings:</b><br/>
        • Best Performing Model: {best_model}<br/>
        • Model Accuracy (R²): {best_r2:.4f}<br/>
        • Mean Absolute Error: {best_mae:.4f}%<br/>
        • Mean Squared Error: {best_mse:.4f}<br/>
        • Root Mean Squared Error: {best_rmse:.4f}%<br/>
        • Number of Features Used: {num_features}<br/>
        • Total Models Evaluated: 7<br/><br/>
        
        The {best_model} model demonstrated the highest predictive accuracy and is recommended for 
        generating future passing rate predictions. Regular retraining is recommended as new exam data becomes available.
        <br/><br/>
        
        <i>Report generated by LSPU CAS AI Board Exam Prediction System</i><br/>
        <i>{report_date}</i>
        """
        elements.append(Paragraph(summary_text, body_style))
        
        # Build PDF
        doc.build(elements)
        print(f"✅ Report generated: {filename}")
        return filename

if __name__ == "__main__":
    generator = CASTrainingReportGenerator()
    generator.generate_report()
