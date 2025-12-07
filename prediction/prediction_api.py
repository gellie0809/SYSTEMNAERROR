from flask import Flask, jsonify, request, send_file
from flask_cors import CORS
import os
from datetime import datetime
from advanced_predictor import AdvancedBoardExamPredictor
from pdf_generator import PredictionPDFGenerator
from training_report_generator import TrainingReportGenerator

app = Flask(__name__)
CORS(app)

DB_CONFIG = {
    'host': 'localhost',
    'user': 'root',
    'password': '',
    'database': 'project_db'
}

@app.route('/api/predict', methods=['GET'])
def predict():
    """Get predictions with confidence intervals"""
    try:
        predictor = AdvancedBoardExamPredictor(DB_CONFIG)
        predictions = predictor.predict_next_year()
        
        if predictions:
            return jsonify({
                'success': True,
                'data': predictions
            })
        else:
            return jsonify({
                'success': False,
                'error': 'No predictions available. Model may not be trained.'
            }), 404
            
    except Exception as e:
        return jsonify({
            'success': False,
            'error': str(e)
        }), 500

@app.route('/api/train', methods=['POST'])
def train():
    """Train and compare all models"""
    try:
        predictor = AdvancedBoardExamPredictor(DB_CONFIG)
        metadata = predictor.train_and_compare_models()
        
        if metadata:
            return jsonify({
                'success': True,
                'message': 'Models trained successfully',
                'metadata': metadata
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

@app.route('/api/model/info', methods=['GET'])
def model_info():
    """Get model information and comparison"""
    try:
        import json
        metadata_path = 'models/model_metadata.json'
        
        if os.path.exists(metadata_path):
            with open(metadata_path, 'r') as f:
                metadata = json.load(f)
            return jsonify({
                'success': True,
                'data': metadata
            })
        else:
            return jsonify({
                'success': False,
                'error': 'Model not trained yet'
            }), 404
            
    except Exception as e:
        return jsonify({
            'success': False,
            'error': str(e)
        }), 500

@app.route('/api/export/pdf', methods=['POST'])
def export_pdf():
    """Generate and download prediction PDF report"""
    try:
        data = request.get_json()
        predictions = data.get('predictions')
        model_info = data.get('model_info')
        
        if not predictions or not model_info:
            return jsonify({
                'success': False,
                'error': 'Missing prediction data'
            }), 400
        
        # Generate PDF
        pdf_gen = PredictionPDFGenerator()
        pdf_path = pdf_gen.generate_report(predictions, model_info)
        
        if pdf_path and os.path.exists(pdf_path):
            return send_file(
                pdf_path,
                mimetype='application/pdf',
                as_attachment=True,
                download_name=f'LSPU_Board_Exam_Predictions_{predictions[0]["prediction_year"]}.pdf'
            )
        else:
            return jsonify({
                'success': False,
                'error': 'Failed to generate PDF'
            }), 500
            
    except Exception as e:
        return jsonify({
            'success': False,
            'error': str(e)
        }), 500

@app.route('/api/graphs/<graph_name>', methods=['GET'])
def get_graph(graph_name):
    """Serve generated graphs"""
    try:
        graph_path = os.path.join('output', 'graphs', f'{graph_name}.png')
        
        if os.path.exists(graph_path):
            return send_file(graph_path, mimetype='image/png')
        else:
            return jsonify({
                'success': False,
                'error': 'Graph not found'
            }), 404
            
    except Exception as e:
        return jsonify({
            'success': False,
            'error': str(e)
        }), 500

@app.route('/api/export/training-report', methods=['GET'])
def export_training_report():
    """Generate and download detailed training report PDF"""
    try:
        # Generate training report
        generator = TrainingReportGenerator()
        timestamp = datetime.now().strftime('%Y%m%d_%H%M%S')
        output_file = f'output/Training_Report_{timestamp}.pdf'
        
        success = generator.generate_pdf_report(output_file)
        
        if success and os.path.exists(output_file):
            return send_file(
                output_file,
                mimetype='application/pdf',
                as_attachment=True,
                download_name=f'LSPU_Training_Report_{timestamp}.pdf'
            )
        else:
            return jsonify({
                'success': False,
                'error': 'Failed to generate training report'
            }), 500
            
    except Exception as e:
        return jsonify({
            'success': False,
            'error': str(e)
        }), 500

@app.route('/api/health', methods=['GET'])
def health():
    """Health check endpoint"""
    return jsonify({
        'status': 'healthy',
        'service': 'Advanced Board Exam Prediction API',
        'features': [
            'Multiple Algorithm Comparison',
            'Confidence Intervals',
            'Visualization Graphs',
            'PDF Export'
        ]
    })

if __name__ == '__main__':
    print("=" * 70)
    print("ADVANCED BOARD EXAM PREDICTION API")
    print("=" * 70)
    print("\nServer starting at: http://localhost:5000")
    print("\nAvailable Endpoints:")
    print("  GET  /api/predict              - Get predictions with confidence intervals")
    print("  POST /api/train                - Train and compare all models")
    print("  GET  /api/model/info           - Get model information")
    print("  POST /api/export/pdf           - Generate prediction PDF report")
    print("  GET  /api/export/training-report - Generate detailed training report")
    print("  GET  /api/graphs/<name>        - Get visualization graphs")
    print("  GET  /api/health               - Health check")
    print("\nFeatures:")
    print("  ✓ 7 Machine Learning Algorithms")
    print("  ✓ 95% Confidence Intervals")
    print("  ✓ Performance Visualization")
    print("  ✓ PDF Export")
    print("=" * 70)
    
    app.run(host='0.0.0.0', port=5000, debug=True)
