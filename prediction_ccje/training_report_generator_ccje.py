"""
Training Report Generator for CCJE Department
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

class CCJETrainingReportGenerator:
    def __init__(self):
        self.db_config = {
            'host': 'localhost',
            'database': 'project_db',
            'user': 'root',
            'password': ''
        }
        self.department = 'Criminal Justice Education'
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
        """Fetch all CCJE anonymous training data"""
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
        print("\n📄 Generating CCJE Training Report...")
        
        # Fetch data
        training_data = self.fetch_training_data()
        if training_data is None or len(training_data) == 0:
            print("❌ No training data available")
            return None
        
        metadata = self.load_model_metadata()
        
        # Create PDF
        timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
        filename = f"{self.output_dir}/CCJE_Training_Report_{timestamp}.pdf"
        
        doc = SimpleDocTemplate(filename, pagesize=letter,
                               rightMargin=0.75*inch, leftMargin=0.75*inch,
                               topMargin=0.75*inch, bottomMargin=0.75*inch)
        
        elements = []
        styles = getSampleStyleSheet()
        
        # Custom styles - CCJE Red theme
        title_style = ParagraphStyle(
            'CustomTitle',
            parent=styles['Heading1'],
            fontSize=22,
            textColor=colors.HexColor('#800020'),
            spaceAfter=12,
            alignment=TA_CENTER,
            fontName='Helvetica-Bold'
        )
        
        subtitle_style = ParagraphStyle(
            'CustomSubtitle',
            parent=styles['Heading2'],
            fontSize=14,
            textColor=colors.HexColor('#D32F2F'),
            spaceAfter=12,
            alignment=TA_CENTER
        )
        
        heading_style = ParagraphStyle(
            'CustomHeading',
            parent=styles['Heading2'],
            fontSize=14,
            textColor=colors.HexColor('#800020'),
            spaceAfter=8,
            spaceBefore=16,
            fontName='Helvetica-Bold'
        )
        
        subheading_style = ParagraphStyle(
            'CustomSubheading',
            parent=styles['Heading3'],
            fontSize=12,
            textColor=colors.HexColor('#D32F2F'),
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
        num_features = 0
        feature_names = []
        
        if metadata:
            training_date = datetime.fromisoformat(metadata.get('training_date', datetime.now().isoformat())).strftime("%B %d, %Y")
            best_model = metadata.get('best_model', 'N/A')
            best_metrics = metadata.get('metrics', {}).get(best_model, {})
            best_r2 = best_metrics.get('test_r2', 0)
            num_features = metadata.get('num_features', 8)
            feature_names = metadata.get('feature_names', [])
        
        # ==================== COVER PAGE ====================
        elements.append(Spacer(1, 1.5*inch))
        elements.append(Paragraph("LAGUNA STATE POLYTECHNIC UNIVERSITY", title_style))
        elements.append(Spacer(1, 0.2*inch))
        elements.append(Paragraph("College of Criminal Justice Education", subtitle_style))
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
            ('TEXTCOLOR', (0, 0), (0, -1), colors.HexColor('#800020')),
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
        This report documents the complete machine learning training process for the CCJE (College of Criminal 
        Justice Education) Board Exam Prediction System. The system uses historical anonymous board exam data 
        to predict future passing rates using advanced regression algorithms. This AI-powered prediction system 
        aims to help the institution make data-driven decisions regarding board exam preparation and student 
        support programs for the Criminology Licensure Examination (CLE).
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
        • Filtered only records from the CCJE department<br/><br/>
        
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
        train_count = int(total_records * 0.8)
        test_count = total_records - train_count
        
        splitting_text = f"""
        The dataset was split into training and testing sets using scikit-learn's train_test_split function 
        with a random state of 42 for reproducibility.<br/><br/>
        
        <b>Split Configuration:</b><br/>
        • Total Records: {total_records}<br/>
        • Training Set: {train_count} records (80%)<br/>
        • Testing Set: {test_count} records (20%)<br/>
        • Random State: 42 (for reproducibility)<br/><br/>
        
        <b>Purpose of Splitting:</b><br/>
        • Training Set: Used to train the machine learning models<br/>
        • Testing Set: Used to evaluate model performance on unseen data<br/>
        • This prevents overfitting and provides realistic accuracy estimates<br/>
        """
        elements.append(Paragraph(splitting_text, body_style))
        
        # ==================== 5. FEATURE SELECTION ====================
        elements.append(Paragraph("5. Feature Selection", heading_style))
        
        feature_text = """
        Feature selection identifies the most important variables that influence the prediction. 
        The following features were selected for the model:<br/><br/>
        
        <b>Numerical Features:</b><br/>
        • year_numeric - The exam year (temporal feature)<br/>
        • takers_scaled - Normalized number of exam takers<br/>
        • passers_ratio - Ratio of passers to takers<br/>
        • exam_month_num - Month when exam was taken<br/><br/>
        
        <b>Temporal Features (Lag Variables):</b><br/>
        • passing_rate_lag1 - Previous year's passing rate<br/>
        • passing_rate_lag2 - Two years ago passing rate<br/>
        • passing_rate_ma3 - 3-year moving average<br/><br/>
        
        <b>Categorical Features (One-Hot Encoded):</b><br/>
        • is_[exam_type] - Binary indicator for each board exam type<br/><br/>
        
        <b>Feature Importance:</b><br/>
        The lag features (passing_rate_lag1, passing_rate_lag2) and moving averages are typically 
        the most important predictors, as historical performance is a strong indicator of future results.
        """
        elements.append(Paragraph(feature_text, body_style))
        
        if feature_names:
            elements.append(Paragraph("<b>Complete Feature List:</b>", body_style))
            for i, feature in enumerate(feature_names, 1):
                elements.append(Paragraph(f"{i}. {feature}", body_style))
        
        elements.append(PageBreak())
        
        # ==================== 6. MODEL SELECTION ====================
        elements.append(Paragraph("6. Model Selection", heading_style))
        
        model_text = """
        Seven different regression algorithms were selected for comparison to find the best performing model 
        for CCJE board exam prediction:<br/><br/>
        
        <b>1. Linear Regression</b><br/>
        • Basic regression model assuming linear relationship between features and target<br/>
        • Pros: Simple, interpretable, fast training<br/>
        • Cons: May not capture non-linear patterns<br/><br/>
        
        <b>2. Ridge Regression (α=1.0)</b><br/>
        • Linear regression with L2 regularization<br/>
        • Pros: Handles multicollinearity, prevents overfitting<br/>
        • Cons: Includes all features (no feature selection)<br/><br/>
        
        <b>3. Lasso Regression (α=0.1)</b><br/>
        • Linear regression with L1 regularization<br/>
        • Pros: Performs feature selection, handles multicollinearity<br/>
        • Cons: May exclude important features<br/><br/>
        
        <b>4. Random Forest (n_estimators=100)</b><br/>
        • Ensemble of decision trees with bagging<br/>
        • Pros: Handles non-linearity, robust to outliers<br/>
        • Cons: Less interpretable, can overfit<br/><br/>
        
        <b>5. Gradient Boosting (n_estimators=100)</b><br/>
        • Sequential ensemble that corrects errors<br/>
        • Pros: Often achieves best accuracy, handles complex patterns<br/>
        • Cons: Slower training, can overfit<br/><br/>
        
        <b>6. Support Vector Machine (kernel='rbf')</b><br/>
        • Uses kernel trick for non-linear regression<br/>
        • Pros: Effective in high dimensions, robust to outliers<br/>
        • Cons: Requires feature scaling, slower on large datasets<br/><br/>
        
        <b>7. Decision Tree</b><br/>
        • Tree-based model with recursive partitioning<br/>
        • Pros: Highly interpretable, handles non-linearity<br/>
        • Cons: Prone to overfitting, unstable<br/>
        """
        elements.append(Paragraph(model_text, body_style))
        
        # ==================== 7. MODEL TRAINING ====================
        elements.append(Paragraph("7. Model Training", heading_style))
        
        training_text = """
        <b>Training Process:</b><br/><br/>
        
        <b>Step 1: Feature Scaling</b><br/>
        All features were standardized using StandardScaler to have zero mean and unit variance. 
        This is crucial for algorithms like SVM and regularized regression.<br/><br/>
        
        <b>Step 2: Model Fitting</b><br/>
        Each of the 7 algorithms was trained on the scaled training data (80% of records). 
        The training process involved:<br/>
        • Fitting the model to training features (X_train) and target (y_train)<br/>
        • Storing trained model parameters<br/><br/>
        
        <b>Step 3: Cross-Validation</b><br/>
        5-fold cross-validation was performed on the training set to estimate model stability:<br/>
        • Data divided into 5 equal parts<br/>
        • Each fold used as validation while others used for training<br/>
        • Average performance calculated across all folds<br/><br/>
        
        <b>Step 4: Model Persistence</b><br/>
        All trained models were saved using joblib for later use in predictions.
        """
        elements.append(Paragraph(training_text, body_style))
        
        elements.append(PageBreak())
        
        # ==================== 8. MODEL TESTING AND EVALUATION ====================
        elements.append(Paragraph("8. Model Testing and Evaluation", heading_style))
        
        testing_text = """
        <b>Testing Process:</b><br/><br/>
        
        <b>Step 1: Prediction Generation</b><br/>
        Each trained model was used to predict passing rates on the testing set (20% of records) 
        that was not used during training.<br/><br/>
        
        <b>Step 2: Metric Calculation</b><br/>
        Multiple evaluation metrics were calculated comparing predictions to actual values:<br/>
        • R² Score (coefficient of determination)<br/>
        • Mean Absolute Error (MAE)<br/>
        • Mean Squared Error (MSE)<br/>
        • Root Mean Squared Error (RMSE)<br/><br/>
        
        <b>Step 3: Model Comparison</b><br/>
        All models were ranked based on test R² score to identify the best performer.<br/><br/>
        
        <b>Step 4: Backtesting Validation</b><br/>
        To verify prediction accuracy, we performed backtesting:<br/>
        • Trained model using only 2019-2022 data<br/>
        • Predicted 2023 passing rates<br/>
        • Compared predictions to actual 2023 results<br/>
        • This validates that predictions are reliable for future years<br/>
        """
        elements.append(Paragraph(testing_text, body_style))
        
        # ==================== 9. EVALUATION METRICS ====================
        elements.append(Paragraph("9. Evaluation Metrics", heading_style))
        
        metrics_text = """
        <b>Metrics Used for Model Evaluation:</b><br/><br/>
        
        <b>R² (R-Squared / Coefficient of Determination)</b><br/>
        • Measures proportion of variance explained by the model<br/>
        • Range: -∞ to 1 (1 = perfect fit, 0 = baseline model)<br/>
        • Interpretation: Higher is better<br/><br/>
        
        <b>MAE (Mean Absolute Error)</b><br/>
        • Average absolute difference between predicted and actual values<br/>
        • Unit: Same as target variable (percentage points)<br/>
        • Interpretation: Lower is better (closer predictions)<br/><br/>
        
        <b>MSE (Mean Squared Error)</b><br/>
        • Average squared difference between predicted and actual values<br/>
        • Penalizes larger errors more heavily<br/>
        • Interpretation: Lower is better<br/><br/>
        
        <b>RMSE (Root Mean Squared Error)</b><br/>
        • Square root of MSE<br/>
        • Unit: Same as target variable (percentage points)<br/>
        • Interpretation: Lower is better, represents typical error magnitude<br/><br/>
        
        <b>Accuracy</b><br/>
        • Calculated as: 100 - MAE<br/>
        • Represents how close predictions are on average<br/>
        • Interpretation: Higher is better<br/><br/>
        
        <b>Precision (Threshold-based)</b><br/>
        • Percentage of predictions within 5 percentage points of actual<br/>
        • Interpretation: Higher means more reliable predictions<br/>
        """
        elements.append(Paragraph(metrics_text, body_style))
        
        # Model metrics table
        if metadata and 'metrics' in metadata:
            elements.append(Paragraph("<b>Model Performance Summary:</b>", subheading_style))
            
            metrics_data = [['Model', 'R²', 'MAE', 'MSE', 'RMSE', 'Accuracy']]
            
            for model_name, m in metadata['metrics'].items():
                metrics_data.append([
                    model_name[:25],
                    f"{m.get('test_r2', 0):.4f}",
                    f"{m.get('test_mae', 0):.2f}%",
                    f"{m.get('test_mse', 0):.2f}",
                    f"{m.get('test_rmse', 0):.2f}%",
                    f"{m.get('accuracy', 0):.1f}%"
                ])
            
            metrics_table = Table(metrics_data, colWidths=[1.8*inch, 0.7*inch, 0.8*inch, 0.8*inch, 0.8*inch, 0.9*inch])
            metrics_table.setStyle(TableStyle([
                ('BACKGROUND', (0, 0), (-1, 0), colors.HexColor('#D32F2F')),
                ('TEXTCOLOR', (0, 0), (-1, 0), colors.white),
                ('ALIGN', (0, 0), (-1, -1), 'CENTER'),
                ('FONTNAME', (0, 0), (-1, 0), 'Helvetica-Bold'),
                ('FONTSIZE', (0, 0), (-1, 0), 9),
                ('BOTTOMPADDING', (0, 0), (-1, 0), 10),
                ('BACKGROUND', (0, 1), (-1, -1), colors.HexColor('#FFF5F5')),
                ('TEXTCOLOR', (0, 1), (-1, -1), colors.HexColor('#333333')),
                ('FONTNAME', (0, 1), (-1, -1), 'Helvetica'),
                ('FONTSIZE', (0, 1), (-1, -1), 8),
                ('GRID', (0, 0), (-1, -1), 1, colors.HexColor('#D32F2F')),
                ('VALIGN', (0, 0), (-1, -1), 'MIDDLE'),
                ('TOPPADDING', (0, 1), (-1, -1), 6),
                ('BOTTOMPADDING', (0, 1), (-1, -1), 6),
            ]))
            elements.append(metrics_table)
        
        elements.append(PageBreak())
        
        # ==================== 10. PREDICTION GENERATION ====================
        elements.append(Paragraph("10. Prediction Generation", heading_style))
        
        prediction_text = f"""
        <b>How Predictions Are Generated:</b><br/><br/>
        
        <b>Step 1: Load Best Model</b><br/>
        The best performing model ({best_model}) is loaded from the saved model files.<br/><br/>
        
        <b>Step 2: Prepare Input Features</b><br/>
        For each exam type, the following features are prepared:<br/>
        • Latest historical data as base values<br/>
        • Updated year_numeric to prediction year ({datetime.now().year + 1})<br/>
        • Lag features from recent years<br/><br/>
        
        <b>Step 3: Feature Scaling</b><br/>
        Input features are scaled using the same StandardScaler used during training.<br/><br/>
        
        <b>Step 4: Generate Prediction</b><br/>
        The model predicts the passing rate for each exam type. Predictions are bounded 
        between 0% and 100%.<br/><br/>
        
        <b>Step 5: Calculate Confidence Intervals</b><br/>
        95% confidence intervals are calculated based on historical standard deviation:<br/>
        • Lower bound = Prediction - (1.96 × Std Dev)<br/>
        • Upper bound = Prediction + (1.96 × Std Dev)<br/>
        """
        elements.append(Paragraph(prediction_text, body_style))
        
        # ==================== 11. COMPLETE TRAINING DATASET ====================
        elements.append(PageBreak())
        elements.append(Paragraph("11. Complete Training Dataset", heading_style))
        
        elements.append(Paragraph(f"Total Records: {len(training_data)}", body_style))
        elements.append(Spacer(1, 0.2*inch))
        
        # Training data table
        data_table_header = ['Exam Type', 'Year', 'Takers', 'Passers', 'Passing Rate']
        data_table_rows = [data_table_header]
        
        for _, row in training_data.iterrows():
            exam_type_short = str(row['board_exam_type'])[:30] + '...' if len(str(row['board_exam_type'])) > 30 else str(row['board_exam_type'])
            data_table_rows.append([
                exam_type_short,
                str(int(row['exam_year'])),
                str(int(row['total_takers'])),
                str(int(row['total_passers'])),
                f"{row['passing_rate']:.2f}%"
            ])
        
        # Split into multiple pages if needed
        max_rows_per_page = 25
        for i in range(0, len(data_table_rows), max_rows_per_page):
            chunk = data_table_rows[i:i+max_rows_per_page]
            if i > 0:
                chunk = [data_table_header] + chunk[1:] if chunk[0] != data_table_header else chunk
            
            data_table = Table(chunk, colWidths=[2.2*inch, 0.7*inch, 0.8*inch, 0.8*inch, 1*inch])
            data_table.setStyle(TableStyle([
                ('BACKGROUND', (0, 0), (-1, 0), colors.HexColor('#D32F2F')),
                ('TEXTCOLOR', (0, 0), (-1, 0), colors.white),
                ('ALIGN', (0, 0), (-1, -1), 'CENTER'),
                ('ALIGN', (0, 1), (0, -1), 'LEFT'),
                ('FONTNAME', (0, 0), (-1, 0), 'Helvetica-Bold'),
                ('FONTSIZE', (0, 0), (-1, 0), 9),
                ('BOTTOMPADDING', (0, 0), (-1, 0), 8),
                ('BACKGROUND', (0, 1), (-1, -1), colors.HexColor('#FFF5F5')),
                ('TEXTCOLOR', (0, 1), (-1, -1), colors.HexColor('#333333')),
                ('FONTNAME', (0, 1), (-1, -1), 'Helvetica'),
                ('FONTSIZE', (0, 1), (-1, -1), 8),
                ('GRID', (0, 0), (-1, -1), 0.5, colors.HexColor('#D32F2F')),
                ('VALIGN', (0, 0), (-1, -1), 'MIDDLE'),
                ('TOPPADDING', (0, 1), (-1, -1), 4),
                ('BOTTOMPADDING', (0, 1), (-1, -1), 4),
            ]))
            elements.append(data_table)
            
            if i + max_rows_per_page < len(data_table_rows):
                elements.append(PageBreak())
        
        # ==================== 12. MODEL PERFORMANCE COMPARISON ====================
        elements.append(PageBreak())
        elements.append(Paragraph("12. Model Performance Comparison", heading_style))
        
        if metadata and 'metrics' in metadata:
            comparison_text = f"""
            <b>Best Performing Model: {best_model}</b><br/><br/>
            
            The model was selected based on the highest R² score on the testing set. 
            A higher R² indicates better prediction accuracy.<br/><br/>
            
            <b>Model Rankings by R² Score:</b>
            """
            elements.append(Paragraph(comparison_text, body_style))
            
            # Sort models by R² score
            sorted_models = sorted(metadata['metrics'].items(), 
                                  key=lambda x: x[1].get('test_r2', 0), 
                                  reverse=True)
            
            for rank, (model_name, m) in enumerate(sorted_models, 1):
                r2 = m.get('test_r2', 0)
                mae = m.get('test_mae', 0)
                acc = m.get('accuracy', 0)
                
                rank_text = f"""
                <b>{rank}. {model_name}</b><br/>
                   R² Score: {r2:.4f} | MAE: {mae:.2f}% | Accuracy: {acc:.1f}%
                """
                elements.append(Paragraph(rank_text, body_style))
        
        # ==================== 13. VISUALIZATIONS ====================
        elements.append(PageBreak())
        elements.append(Paragraph("13. Visualizations", heading_style))
        
        viz_text = """
        The following visualization graphs are generated during model training and are available 
        in the graphs/ directory:<br/><br/>
        
        <b>1. Model R² Score Comparison (model_comparison.png)</b><br/>
        Horizontal bar chart comparing R² scores across all 7 algorithms.<br/><br/>
        
        <b>2. Model Accuracy Comparison (accuracy_comparison.png)</b><br/>
        Horizontal bar chart showing accuracy percentages for each model.<br/><br/>
        
        <b>3. MAE Comparison (mae_comparison.png)</b><br/>
        Comparison of Mean Absolute Error across models (lower is better).<br/><br/>
        
        <b>4. Predictions vs Actual (predictions_vs_actual.png)</b><br/>
        Scatter plot showing how well predictions match actual values.<br/><br/>
        
        <b>5. Residual Analysis (residual_analysis.png)</b><br/>
        Distribution of prediction errors and residuals vs predicted values.<br/><br/>
        
        <b>6. Historical Trends (historical_trends.png)</b><br/>
        Line chart showing passing rate trends over the years for each exam type.<br/>
        """
        elements.append(Paragraph(viz_text, body_style))
        
        # Add graphs if they exist
        graph_files = [
            ('model_comparison.png', 'Model R² Score Comparison'),
            ('accuracy_comparison.png', 'Model Accuracy Comparison'),
            ('predictions_vs_actual.png', 'Predictions vs Actual Values'),
            ('residual_analysis.png', 'Residual Analysis'),
            ('historical_trends.png', 'Historical Passing Rate Trends'),
        ]
        
        for graph_file, graph_title in graph_files:
            graph_path = f'graphs/{graph_file}'
            if os.path.exists(graph_path):
                elements.append(PageBreak())
                elements.append(Paragraph(graph_title, subheading_style))
                try:
                    img = Image(graph_path, width=6*inch, height=3*inch)
                    elements.append(img)
                except:
                    elements.append(Paragraph(f"[Graph: {graph_file}]", body_style))
        
        # ==================== FOOTER ====================
        elements.append(PageBreak())
        elements.append(Paragraph("End of Report", heading_style))
        
        footer_text = f"""
        This report was automatically generated by the LSPU CCJE AI Board Exam Prediction System.<br/><br/>
        
        <b>Report Details:</b><br/>
        • Generated: {report_date}<br/>
        • Department: {self.department}<br/>
        • System Version: 1.0<br/><br/>
        
        For questions or support, please contact the LSPU IT Department.
        """
        elements.append(Paragraph(footer_text, body_style))
        
        # Build PDF
        doc.build(elements)
        
        print(f"✅ Report saved to: {filename}")
        return filename


if __name__ == "__main__":
    generator = CCJETrainingReportGenerator()
    report_file = generator.generate_report()
    if report_file:
        print(f"\n📄 Training report generated: {report_file}")
    else:
        print("\n❌ Failed to generate report")
