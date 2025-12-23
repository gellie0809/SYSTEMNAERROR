# CCJE Board Exam Prediction System

This system uses AI/ML to predict board exam passing rates for the College of Criminal Justice Education (CCJE) based on anonymous historical data.

## Features

- **7 Machine Learning Algorithms**: Linear Regression, Ridge, Lasso, Random Forest, Gradient Boosting, SVM, Decision Tree
- **Performance Comparison**: Automatic selection of best performing model
- **Confidence Intervals**: 95% confidence intervals for predictions
- **Visualization**: Model comparison charts and residual analysis
- **Training Reports**: Comprehensive PDF reports with all training data
- **REST API**: Flask-based API on port 5001

## Setup Instructions

### First Time Setup

1. Open Command Prompt in this directory
2. Run: `setup.bat`
3. Wait for installation to complete

### Training the Models

1. Run: `train.bat`
2. This will train all 7 algorithms on CCJE anonymous data
3. Training typically takes 2-5 minutes

### Starting the API Server

1. Run: `start_api.bat`
2. The API will be available at `http://localhost:5001`
3. Keep this window open while using the prediction system

### Accessing the Web Interface

1. Make sure the API server is running
2. Open your browser to the CCJE dashboard
3. Click "Prediction for Anonymous Data" in the sidebar

## API Endpoints

- `GET /api/health` - Health check
- `GET /api/status` - Check if models are trained
- `GET /api/predict` - Get predictions for next year
- `GET /api/model/info` - Get model information and metrics
- `POST /api/train` - Retrain all models
- `GET /api/graphs/<type>` - Get visualization graphs
- `GET /api/export/training-report` - Download training report PDF
- `POST /api/export/pdf` - Export predictions to PDF

## Port Information

- **CCJE API**: Port 5001
- **Engineering API**: Port 5000 (separate system)

These systems run independently and do not interfere with each other.

## Data Source

The system uses anonymous board passer data from the `anonymous_board_passers` table, filtered for "Criminal Justice Education" exam types. This ensures student privacy while maintaining prediction accuracy.

## Files Structure

```
prediction_ccje/
├── advanced_predictor_ccje.py      # ML training module
├── prediction_api_ccje.py          # Flask API server
├── training_report_generator_ccje.py # PDF report generator
├── requirements.txt                 # Python dependencies
├── setup.bat                        # Setup script
├── train.bat                        # Training script
├── start_api.bat                   # API server launcher
├── models/                         # Trained models (created after training)
├── graphs/                         # Generated graphs (created after training)
└── reports/                        # Training reports (created on demand)
```

## Troubleshooting

### "Model not trained" error
- Run `train.bat` first to train the models

### "Port already in use" error
- Another service is using port 5001
- Stop the other service or change the port in `prediction_api_ccje.py`

### Import errors
- Run `setup.bat` again to reinstall dependencies
- Make sure you're using the virtual environment

### No data available
- Check that CCJE anonymous records exist in the database
- Verify the database connection in `advanced_predictor_ccje.py`

## Support

For issues or questions, contact the system administrator.
