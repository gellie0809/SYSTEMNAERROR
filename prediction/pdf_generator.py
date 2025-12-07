from fpdf import FPDF
import os
from datetime import datetime

class PredictionPDFGenerator(FPDF):
    def __init__(self):
        super().__init__()
        self.set_auto_page_break(auto=True, margin=15)
        
    def header(self):
        # Logo/Header
        self.set_fill_color(139, 164, 154)  # COE color
        self.rect(0, 0, 210, 40, 'F')
        
        self.set_text_color(255, 255, 255)
        self.set_font('Arial', 'B', 20)
        self.cell(0, 15, '', 0, 1)
        self.cell(0, 10, 'LSPU - San Pablo City Campus', 0, 1, 'C')
        self.set_font('Arial', '', 12)
        self.cell(0, 8, 'College of Engineering', 0, 1, 'C')
        self.ln(10)
        
    def footer(self):
        self.set_y(-15)
        self.set_font('Arial', 'I', 8)
        self.set_text_color(128, 128, 128)
        self.cell(0, 10, f'Page {self.page_no()}', 0, 0, 'C')
        
    def chapter_title(self, title):
        self.set_fill_color(203, 222, 211)
        self.set_text_color(59, 98, 85)
        self.set_font('Arial', 'B', 14)
        self.cell(0, 10, title, 0, 1, 'L', True)
        self.ln(5)
        
    def add_prediction_table(self, predictions):
        # Table header
        self.set_fill_color(139, 164, 154)
        self.set_text_color(255, 255, 255)
        self.set_font('Arial', 'B', 9)
        
        col_widths = [75, 30, 35, 30]
        headers = ['Exam Type', 'Current', 'Predicted', 'Change']
        
        for i, header in enumerate(headers):
            self.cell(col_widths[i], 8, header, 1, 0, 'C', True)
        self.ln()
        
        # Table rows
        self.set_text_color(0, 0, 0)
        self.set_font('Arial', '', 9)
        
        for i, pred in enumerate(predictions):
            # Alternate row colors
            if i % 2 == 0:
                self.set_fill_color(242, 242, 242)
            else:
                self.set_fill_color(255, 255, 255)
            
            change = pred['predicted_passing_rate'] - pred['historical_avg']
            change_str = f"{change:+.2f}%"
            
            # Exam type (with text wrapping)
            exam_name = pred['board_exam_type']
            if len(exam_name) > 40:
                exam_name = exam_name[:37] + '...'
            
            self.cell(col_widths[0], 8, exam_name, 1, 0, 'L', True)
            self.cell(col_widths[1], 8, f"{pred['historical_avg']}%", 1, 0, 'C', True)
            self.cell(col_widths[2], 8, f"{pred['predicted_passing_rate']}%", 1, 0, 'C', True)
            
            # Color-code the change
            if change > 0:
                self.set_text_color(0, 128, 0)
            elif change < 0:
                self.set_text_color(255, 0, 0)
            else:
                self.set_text_color(0, 0, 0)
                
            self.cell(col_widths[3], 8, change_str, 1, 0, 'C', True)
            self.set_text_color(0, 0, 0)
            self.ln()
    
    def add_confidence_intervals(self, predictions):
        self.chapter_title('Confidence Intervals (95%)')
        
        self.set_font('Arial', '', 9)
        self.set_text_color(60, 60, 60)
        self.multi_cell(0, 5, 'The following intervals show the range where the actual passing rate is 95% likely to fall:', 0, 'L')
        self.ln(3)
        
        for pred in predictions:
            ci = pred['confidence_interval_95']
            
            self.set_font('Arial', 'B', 10)
            self.set_text_color(59, 98, 85)
            
            exam_name = pred['board_exam_type']
            if len(exam_name) > 50:
                exam_name = exam_name[:47] + '...'
            
            self.cell(0, 6, exam_name, 0, 1)
            
            self.set_font('Arial', '', 9)
            self.set_text_color(0, 0, 0)
            
            # Create visual bar
            self.set_fill_color(203, 222, 211)
            bar_width = 150
            ci_range = ci['upper'] - ci['lower']
            
            # Predicted value position
            pred_pos = ((pred['predicted_passing_rate'] - ci['lower']) / ci_range) * bar_width if ci_range > 0 else bar_width / 2
            
            # Draw confidence interval bar
            x_start = self.get_x() + 10
            y_pos = self.get_y()
            
            self.rect(x_start, y_pos, bar_width, 6, 'D')
            self.set_fill_color(139, 164, 154)
            self.rect(x_start, y_pos, bar_width, 6, 'F')
            
            # Mark predicted value
            self.set_fill_color(59, 98, 85)
            self.rect(x_start + pred_pos - 1, y_pos - 1, 2, 8, 'F')
            
            self.ln(7)
            
            # Text labels
            self.cell(10, 5, '', 0, 0)
            self.cell(50, 5, f"Lower: {ci['lower']}%", 0, 0)
            self.cell(50, 5, f"Predicted: {pred['predicted_passing_rate']}%", 0, 0)
            self.cell(50, 5, f"Upper: {ci['upper']}%", 0, 1)
            self.ln(3)
    
    def add_model_info(self, model_info):
        self.chapter_title('Model Information')
        
        self.set_font('Arial', '', 10)
        self.set_text_color(0, 0, 0)
        
        info_data = [
            ['Algorithm Used:', model_info['model_name']],
            ['Training Date:', datetime.fromisoformat(model_info['trained_date']).strftime('%B %d, %Y at %I:%M %p')],
            ['Model Accuracy (R² Score):', f"{model_info['r2_score']:.4f}"],
            ['Mean Absolute Error:', f"{model_info['mae']:.2f}%"],
            ['Cross-Validation Score:', f"{model_info['cv_mean']:.4f} (±{model_info['cv_std']:.4f})"],
        ]
        
        for label, value in info_data:
            self.set_font('Arial', 'B', 10)
            self.cell(70, 7, label, 0, 0)
            self.set_font('Arial', '', 10)
            self.cell(0, 7, str(value), 0, 1)
            
    def add_interpretation_guide(self):
        self.add_page()
        self.chapter_title('Understanding the Predictions')
        
        self.set_font('Arial', '', 10)
        self.set_text_color(0, 0, 0)
        
        sections = [
            {
                'title': 'What is a Confidence Interval?',
                'text': 'A 95% confidence interval means we are 95% confident that the actual passing rate will fall within the given range. The narrower the interval, the more precise our prediction.'
            },
            {
                'title': 'R² Score (Coefficient of Determination)',
                'text': 'Measures how well the model fits the data. Values closer to 1.0 indicate better predictions. A score of 0.8 means the model explains 80% of the variation in passing rates.'
            },
            {
                'title': 'Mean Absolute Error (MAE)',
                'text': 'The average difference between predicted and actual values. Lower is better. An MAE of 5% means predictions are typically within 5 percentage points of actual results.'
            },
            {
                'title': 'How to Use These Predictions',
                'text': 'These predictions help in planning and resource allocation. If a particular exam shows a declining trend, additional support and preparation programs may be needed.'
            }
        ]
        
        for section in sections:
            self.set_font('Arial', 'B', 11)
            self.set_text_color(59, 98, 85)
            self.cell(0, 7, section['title'], 0, 1)
            
            self.set_font('Arial', '', 10)
            self.set_text_color(0, 0, 0)
            self.multi_cell(0, 5, section['text'], 0, 'L')
            self.ln(3)
    
    def generate_report(self, predictions, model_info):
        """Generate complete PDF report"""
        try:
            self.add_page()
            
            # Title
            self.set_font('Arial', 'B', 16)
            self.set_text_color(59, 98, 85)
            self.cell(0, 10, 'Board Exam Passing Rate Predictions', 0, 1, 'C')
            
            self.set_font('Arial', '', 11)
            self.set_text_color(100, 100, 100)
            self.cell(0, 6, f'Prediction Year: {predictions[0]["prediction_year"]}', 0, 1, 'C')
            self.cell(0, 6, f'Generated: {datetime.now().strftime("%B %d, %Y")}', 0, 1, 'C')
            self.ln(10)
            
            # Summary
            self.chapter_title('Executive Summary')
            
            total_exams = len(predictions)
            avg_change = sum(p['predicted_passing_rate'] - p['historical_avg'] for p in predictions) / total_exams
            improving = sum(1 for p in predictions if p['predicted_passing_rate'] > p['historical_avg'])
            declining = sum(1 for p in predictions if p['predicted_passing_rate'] < p['historical_avg'])
            
            self.set_font('Arial', '', 10)
            summary_text = f"""
Total Board Exams Analyzed: {total_exams}
Average Predicted Change: {avg_change:+.2f} percentage points
Exams with Improving Trends: {improving}
Exams with Declining Trends: {declining}
Exams with Stable Performance: {total_exams - improving - declining}

Prediction Model: {model_info['model_name']}
Model Accuracy: {model_info['r2_score']:.2%}
            """
            
            self.multi_cell(0, 5, summary_text.strip(), 0, 'L')
            self.ln(5)
            
            # Predictions table
            self.chapter_title('Detailed Predictions')
            self.add_prediction_table(predictions)
            self.ln(8)
            
            # Confidence intervals
            self.add_confidence_intervals(predictions)
            self.ln(5)
            
            # Model info
            self.add_model_info(model_info)
            
            # Interpretation guide
            self.add_interpretation_guide()
            
            # Save PDF
            output_dir = 'output'
            os.makedirs(output_dir, exist_ok=True)
            
            filename = f'LSPU_Predictions_{predictions[0]["prediction_year"]}_{datetime.now().strftime("%Y%m%d_%H%M%S")}.pdf'
            output_path = os.path.join(output_dir, filename)
            
            self.output(output_path)
            
            print(f"✅ PDF report generated: {output_path}")
            return output_path
            
        except Exception as e:
            print(f"Error generating PDF: {e}")
            return None
