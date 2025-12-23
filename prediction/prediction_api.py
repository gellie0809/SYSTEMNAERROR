from flask import Flask, jsonify, request, send_file
from flask_cors import CORS
import os
from datetime import datetime
from advanced_predictor import AdvancedBoardExamPredictor
from advanced_predictor_cas import AdvancedBoardExamPredictorCAS
from pdf_generator import PredictionPDFGenerator
from training_report_generator import TrainingReportGenerator
from training_report_generator_v2 import EngineeringTrainingReportGenerator

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
    """Generate and download detailed training report PDF (comprehensive version)"""
    try:
        # Use the new comprehensive report generator
        generator = EngineeringTrainingReportGenerator()
        report_path = generator.generate_report()
        
        if report_path and os.path.exists(report_path):
            return send_file(
                report_path,
                mimetype='application/pdf',
                as_attachment=True,
                download_name=f'Engineering_Training_Report_{datetime.now().strftime("%Y%m%d_%H%M%S")}.pdf'
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

@app.route('/api/export/training-report-legacy', methods=['GET'])
def export_training_report_legacy():
    """Generate and download legacy training report PDF"""
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

# ============================================================================
# CAS DEPARTMENT ENDPOINTS
# ============================================================================

@app.route('/api/cas/predict', methods=['GET'])
def predict_cas():
    """Get predictions for CAS Anonymous Data with confidence intervals"""
    try:
        predictor = AdvancedBoardExamPredictorCAS(DB_CONFIG)
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

@app.route('/api/cas/train', methods=['POST'])
def train_cas():
    """Train and compare all models for CAS"""
    try:
        predictor = AdvancedBoardExamPredictorCAS(DB_CONFIG)
        metadata = predictor.train_and_compare_models()
        
        if metadata:
            return jsonify({
                'success': True,
                'message': 'CAS models trained successfully',
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

@app.route('/api/cas/model/info', methods=['GET'])
def model_info_cas():
    """Get CAS model information and comparison"""
    try:
        import json
        metadata_path = 'models/arts_and_sciences/model_metadata.json'
        
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
                'error': 'CAS model not trained yet'
            }), 404
            
    except Exception as e:
        return jsonify({
            'success': False,
            'error': str(e)
        }), 500

@app.route('/api/cas/graphs/<graph_name>', methods=['GET'])
def get_graph_cas(graph_name):
    """Serve CAS generated graphs"""
    try:
        graph_path = os.path.join('output', 'arts_and_sciences', 'graphs', f'{graph_name}.png')
        
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

@app.route('/api/cas/export/training-report', methods=['GET'])
def export_training_report_cas():
    """Generate and download detailed CAS training report PDF"""
    try:
        # Generate CAS training report
        generator = TrainingReportGenerator()
        timestamp = datetime.now().strftime('%Y%m%d_%H%M%S')
        output_file = f'output/arts_and_sciences/CAS_Training_Report_{timestamp}.pdf'
        
        # Create output directory if it doesn't exist
        os.makedirs('output/arts_and_sciences', exist_ok=True)
        
        success = generator.generate_pdf_report(output_file, department='Arts and Sciences')
        
        if success and os.path.exists(output_file):
            return send_file(
                output_file,
                mimetype='application/pdf',
                as_attachment=True,
                download_name=f'LSPU_CAS_Training_Report_{timestamp}.pdf'
            )
        else:
            return jsonify({
                'success': False,
                'error': 'Failed to generate CAS training report'
            }), 500
            
    except Exception as e:
        return jsonify({
            'success': False,
            'error': str(e)
        }), 500

if __name__ == '__main__':
    print("=" * 70)
    print("ADVANCED BOARD EXAM PREDICTION API")
    print("=" * 70)
    print("\nServer starting at: http://localhost:5000")
    print("\nAvailable Endpoints:")
    print("\n  ENGINEERING DEPARTMENT:")
    print("  GET  /api/predict              - Get predictions with confidence intervals")
    print("  POST /api/train                - Train and compare all models")
    print("  GET  /api/model/info           - Get model information")
    print("  POST /api/export/pdf           - Generate prediction PDF report")
    print("  GET  /api/export/training-report - Generate detailed training report")
    print("  GET  /api/graphs/<name>        - Get visualization graphs")
    print("\n  COLLEGE OF ARTS AND SCIENCES:")
    print("  GET  /api/cas/predict          - Get CAS predictions with confidence intervals")
    print("  POST /api/cas/train            - Train and compare CAS models")
    print("  GET  /api/cas/model/info       - Get CAS model information")
    print("  GET  /api/cas/export/training-report - Generate CAS detailed training report")
    print("  GET  /api/cas/graphs/<name>    - Get CAS visualization graphs")
    print("\n  SYSTEM:")
    print("  GET  /api/health               - Health check")
    print("\nFeatures:")
    print("  ✓ 7 Machine Learning Algorithms")
    print("  ✓ 95% Confidence Intervals")
    print("  ✓ Performance Visualization")
    print("  ✓ PDF Export")
    print("  ✓ Separate Models per Department")
    print("=" * 70)
    
    app.run(host='0.0.0.0', port=5000, debug=False, use_reloader=False, threaded=True)
