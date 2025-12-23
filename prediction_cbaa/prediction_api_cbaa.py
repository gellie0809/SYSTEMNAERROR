"""
Flask API for CBAA Board Exam Predictions
Port: 5002 (separate from Engineering - port 5000, CCJE - port 5001)
"""

from flask import Flask, jsonify, request, send_file
from flask.json.provider import DefaultJSONProvider
from flask_cors import CORS
from advanced_predictor_cbaa import CBAABoardExamPredictor
from training_report_generator_cbaa import CBAATrainingReportGenerator
import os
import json
import math
from datetime import datetime

app = Flask(__name__)
CORS(app)

# Custom JSON provider to handle NaN values
class NanSafeJSONProvider(DefaultJSONProvider):
    def default(self, obj):
        if isinstance(obj, float):
            if math.isnan(obj) or math.isinf(obj):
                return None
        return super().default(obj)

app.json = NanSafeJSONProvider(app)

# Recursive function for nested structures
def nan_to_null(obj):
    """Convert NaN values to None for JSON serialization"""
    if isinstance(obj, float):
        if math.isnan(obj) or math.isinf(obj):
            return None
        return obj
    elif isinstance(obj, dict):
        return {k: nan_to_null(v) for k, v in obj.items()}
    elif isinstance(obj, list):
        return [nan_to_null(item) for item in obj]
    return obj

predictor = CBAABoardExamPredictor()

@app.route('/api/predict', methods=['GET'])
def predict():
    """Get predictions for next year with full data for UI display"""
    try:
        predictions = predictor.predict_next_year()
        
        if predictions is None:
            return jsonify({
                'success': False,
                'error': 'Model not trained or no data available'
            }), 500
        
        # Fetch historical data to calculate averages and comparisons
        df = predictor.fetch_cbaa_anonymous_data()
        current_year = datetime.now().year
        
        # Enhance predictions with historical data and confidence intervals
        enhanced_predictions = []
        for pred in predictions:
            exam_type = pred.get('exam_type', pred.get('board_exam_type', ''))
            
            # Get historical data for this exam type
            historical = df[df['board_exam_type'] == exam_type] if df is not None else None
            
            if historical is not None and len(historical) > 0:
                # Calculate historical average
                historical_avg = round(float(historical['passing_rate'].mean()), 2)
                
                # Calculate standard deviation for confidence interval
                std_dev = float(historical['passing_rate'].std()) if len(historical) > 1 else 5.0
                std_dev = round(std_dev, 2) if not (std_dev != std_dev) else 5.0  # Handle NaN
                
                # Get latest year passing rate
                latest_year_data = historical.loc[historical['exam_year'].idxmax()]
                latest_rate = round(float(latest_year_data['passing_rate']), 2)
                latest_year = int(latest_year_data['exam_year'])
            else:
                historical_avg = 0
                std_dev = 5.0
                latest_rate = 0
                latest_year = current_year - 1
            
            predicted_rate = pred.get('predicted_passing_rate', 0)
            prediction_year = pred.get('predicted_year', current_year + 1)
            
            # Calculate 95% confidence interval (±1.96 standard deviations)
            ci_lower = max(0, round(predicted_rate - 1.96 * std_dev, 2))
            ci_upper = min(100, round(predicted_rate + 1.96 * std_dev, 2))
            
            enhanced_predictions.append({
                'board_exam_type': exam_type,
                'prediction_year': int(prediction_year),
                'predicted_year': int(prediction_year),  # Keep both for compatibility
                'predicted_passing_rate': round(float(predicted_rate), 2),
                'historical_avg': historical_avg,
                'current_year': latest_year,
                'current_rate': latest_rate,
                'confidence_interval_95': {
                    'lower': ci_lower,
                    'upper': ci_upper
                },
                'std_deviation': std_dev,
                'model_used': pred.get('model_used', predictor.best_model_name)
            })
        
        # Clean NaN values before returning
        clean_predictions = nan_to_null({
            'predictions': enhanced_predictions
        })
        
        return jsonify({
            'success': True,
            'data': clean_predictions,
            'timestamp': datetime.now().isoformat()
        })
    
    except Exception as e:
        return jsonify({
            'success': False,
            'error': str(e)
        }), 500

@app.route('/api/model/info', methods=['GET'])
def model_info():
    """Get model information and metrics"""
    try:
        metadata_path = 'models/metadata.json'
        
        if not os.path.exists(metadata_path):
            return jsonify({
                'success': False,
                'error': 'Model not trained yet'
            }), 404
        
        with open(metadata_path, 'r') as f:
            metadata = json.load(f)
        
        # Convert metrics dict to list for frontend
        all_models = []
        for model_name, metrics in metadata['metrics'].items():
            model_data = metrics.copy()
            model_data['model'] = model_name  # Add model name for display
            all_models.append(model_data)
        
        metadata['all_models'] = all_models
        
        # Add best_model_metrics for the PHP frontend
        best_model_name = metadata.get('best_model', 'Lasso Regression')
        if best_model_name in metadata['metrics']:
            metadata['best_model_metrics'] = metadata['metrics'][best_model_name]
        
        # Clean NaN values
        clean_metadata = nan_to_null(metadata)
        
        return jsonify({
            'success': True,
            'data': clean_metadata
        })
    
    except Exception as e:
        return jsonify({
            'success': False,
            'error': str(e)
        }), 500

@app.route('/api/train', methods=['POST'])
def train():
    """Train all models"""
    try:
        success = predictor.train_all_models()
        
        if success:
            # Get updated metadata
            with open('models/metadata.json', 'r') as f:
                metadata = json.load(f)
            
            # Clean NaN values before returning
            clean_metadata = nan_to_null(metadata)
            
            return jsonify({
                'success': True,
                'message': 'All 7 models trained successfully',
                'metadata': clean_metadata
            })
        else:
            return jsonify({
                'success': False,
                'error': 'Training failed'
            }), 500
    
    except Exception as e:
        return jsonify({
            'success': False,
            'error': str(e)
        }), 500

@app.route('/api/graphs/<graph_type>', methods=['GET'])
def get_graph(graph_type):
    """Get visualization graphs"""
    try:
        graph_path = f'graphs/{graph_type}.png'
        
        if not os.path.exists(graph_path):
            return jsonify({
                'success': False,
                'error': 'Graph not found. Please train the models first.'
            }), 404
        
        return send_file(graph_path, mimetype='image/png')
    
    except Exception as e:
        return jsonify({
            'success': False,
            'error': str(e)
        }), 500

@app.route('/api/backtest', methods=['GET'])
def backtest():
    """Validate model accuracy by backtesting on historical data"""
    try:
        # Get optional parameters
        test_year = request.args.get('test_year', 2023, type=int)
        train_until_year = request.args.get('train_until', 2022, type=int)
        
        results = predictor.backtest(test_year, train_until_year)
        
        if results is None:
            return jsonify({
                'success': False,
                'error': 'Insufficient data for backtesting'
            }), 500
        
        # Clean NaN values
        clean_results = nan_to_null(results)
        
        return jsonify({
            'success': True,
            'data': clean_results
        })
    
    except Exception as e:
        return jsonify({
            'success': False,
            'error': str(e)
        }), 500

@app.route('/api/export/pdf', methods=['POST'])
def export_predictions_pdf():
    """Generate and download predictions PDF"""
    try:
        from reportlab.lib.pagesizes import letter
        from reportlab.lib import colors
        from reportlab.platypus import SimpleDocTemplate, Table, TableStyle, Paragraph, Spacer, Image
        from reportlab.lib.styles import getSampleStyleSheet, ParagraphStyle
        from reportlab.lib.units import inch
        import io
        
        data = request.get_json()
        predictions = data.get('predictions', [])
        model_info = data.get('model_info', {})
        
        # Handle if model_info is a list (convert to dict)
        if isinstance(model_info, list) and len(model_info) > 0:
            model_info = model_info[0] if isinstance(model_info[0], dict) else {}
        elif not isinstance(model_info, dict):
            model_info = {}
        
        # Create PDF in memory
        buffer = io.BytesIO()
        doc = SimpleDocTemplate(buffer, pagesize=letter, topMargin=0.5*inch, bottomMargin=0.5*inch)
        elements = []
        styles = getSampleStyleSheet()
        
        # Custom styles with CBAA brown/orange colors
        title_style = ParagraphStyle(
            'CustomTitle',
            parent=styles['Heading1'],
            fontSize=20,
            textColor=colors.HexColor('#763A12'),
            spaceAfter=20,
            alignment=1
        )
        
        subtitle_style = ParagraphStyle(
            'CustomSubtitle',
            parent=styles['Heading2'],
            fontSize=14,
            textColor=colors.HexColor('#AA4C0A'),
            spaceAfter=12
        )
        
        # Title
        elements.append(Paragraph("LSPU Board Exam Predictions Report", title_style))
        elements.append(Paragraph("College of Business Administration and Accountancy", subtitle_style))
        elements.append(Spacer(1, 20))
        
        # Model Info - handle both dict access patterns
        best_model = 'Lasso Regression'
        if isinstance(model_info, dict):
            best_model = model_info.get('best_model', model_info.get('model_used', 'Lasso Regression'))
        
        elements.append(Paragraph(f"<b>Algorithm:</b> {best_model}", styles['Normal']))
        elements.append(Paragraph(f"<b>Generated:</b> {datetime.now().strftime('%B %d, %Y at %I:%M %p')}", styles['Normal']))
        elements.append(Spacer(1, 20))
        
        # Predictions Table
        if predictions and len(predictions) > 0:
            elements.append(Paragraph("Passing Rate Predictions", subtitle_style))
            
            # Get prediction year from first item
            first_pred = predictions[0] if isinstance(predictions[0], dict) else {}
            pred_year = first_pred.get('predicted_year', first_pred.get('prediction_year', 2026))
            
            elements.append(Paragraph(f"<b>Prediction Year:</b> {pred_year}", styles['Normal']))
            elements.append(Spacer(1, 10))
            
            table_data = [['Exam Type', 'Predicted Passing Rate']]
            for pred in predictions:
                if isinstance(pred, dict):
                    # Handle both field naming patterns
                    exam_type = pred.get('exam_type', pred.get('board_exam_type', 'N/A'))
                    passing_rate = pred.get('predicted_passing_rate', 0)
                    
                    # Truncate long exam names for table
                    if len(exam_type) > 50:
                        exam_type = exam_type[:47] + '...'
                    
                    table_data.append([
                        exam_type,
                        f"{passing_rate:.2f}%"
                    ])
            
            table = Table(table_data, colWidths=[4.5*inch, 2*inch])
            table.setStyle(TableStyle([
                ('BACKGROUND', (0, 0), (-1, 0), colors.HexColor('#AA4C0A')),
                ('TEXTCOLOR', (0, 0), (-1, 0), colors.white),
                ('ALIGN', (0, 0), (-1, -1), 'CENTER'),
                ('ALIGN', (0, 1), (0, -1), 'LEFT'),
                ('FONTNAME', (0, 0), (-1, 0), 'Helvetica-Bold'),
                ('FONTSIZE', (0, 0), (-1, 0), 11),
                ('BOTTOMPADDING', (0, 0), (-1, 0), 12),
                ('BACKGROUND', (0, 1), (-1, -1), colors.HexColor('#FFF5F0')),
                ('TEXTCOLOR', (0, 1), (-1, -1), colors.HexColor('#333333')),
                ('FONTNAME', (0, 1), (-1, -1), 'Helvetica'),
                ('FONTSIZE', (0, 1), (-1, -1), 10),
                ('GRID', (0, 0), (-1, -1), 1, colors.HexColor('#E08600')),
                ('VALIGN', (0, 0), (-1, -1), 'MIDDLE'),
                ('TOPPADDING', (0, 1), (-1, -1), 8),
                ('BOTTOMPADDING', (0, 1), (-1, -1), 8),
            ]))
            elements.append(table)
        
        elements.append(Spacer(1, 30))
        
        # Footer
        footer_style = ParagraphStyle(
            'Footer',
            parent=styles['Normal'],
            fontSize=9,
            textColor=colors.gray,
            alignment=1
        )
        elements.append(Paragraph("This report was generated by the LSPU CBAA AI Board Exam Prediction System", footer_style))
        elements.append(Paragraph("Predictions are based on historical anonymous data and machine learning analysis", footer_style))
        
        doc.build(elements)
        buffer.seek(0)
        
        return send_file(
            buffer,
            mimetype='application/pdf',
            as_attachment=True,
            download_name=f'CBAA_Predictions_{datetime.now().strftime("%Y%m%d_%H%M%S")}.pdf'
        )
    
    except Exception as e:
        import traceback
        traceback.print_exc()
        return jsonify({
            'success': False,
            'error': str(e)
        }), 500

@app.route('/api/cbaa/export/training-report', methods=['GET'])
def export_training_report():
    """Generate and download training report PDF"""
    try:
        generator = CBAATrainingReportGenerator()
        report_file = generator.generate_report()
        
        if report_file and os.path.exists(report_file):
            return send_file(
                report_file,
                mimetype='application/pdf',
                as_attachment=True,
                download_name=f'CBAA_Training_Report_{datetime.now().strftime("%Y%m%d")}.pdf'
            )
        else:
            return jsonify({
                'success': False,
                'error': 'Failed to generate report'
            }), 500
    
    except Exception as e:
        return jsonify({
            'success': False,
            'error': str(e)
        }), 500

@app.route('/api/health', methods=['GET'])
def health_check():
    """Health check endpoint"""
    return jsonify({
        'status': 'healthy',
        'service': 'CBAA Board Exam Prediction API',
        'port': 5002,
        'timestamp': datetime.now().isoformat()
    })

if __name__ == '__main__':
    print("=" * 70)
    print("🎓 CBAA Board Exam Prediction API")
    print("=" * 70)
    print("🌐 Starting server on http://localhost:5002")
    print("📊 Department: Business Administration and Accountancy")
    print("🔗 Available endpoints:")
    print("   - GET  /api/predict              - Get predictions")
    print("   - GET  /api/model/info           - Get model information")
    print("   - POST /api/train                - Train models")
    print("   - GET  /api/graphs/<type>        - Get visualization graphs")
    print("   - GET  /api/backtest             - Validate model accuracy")
    print("   - POST /api/export/pdf           - Export predictions to PDF")
    print("   - GET  /api/cbaa/export/training-report - Download training report PDF")
    print("   - GET  /api/health               - Health check")
    print("=" * 70)
    
    app.run(host='0.0.0.0', port=5002, debug=True)
