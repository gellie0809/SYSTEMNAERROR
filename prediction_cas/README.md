# CAS Board Exam Prediction System

AI-powered board exam passing rate prediction for the College of Arts and Sciences (CAS) department.

## Quick Start

1. **First-time Setup:**
   ```
   setup.bat
   ```
   This creates a virtual environment and installs all required packages.

2. **Train the Models:**
   ```
   train.bat
   ```
   This trains 7 different ML algorithms on CAS anonymous board exam data.

3. **Start the API Server:**
   ```
   start_api.bat
   ```
   This starts the prediction API on port 5003.

4. **Access the Prediction Page:**
   Go to the CAS Anonymous Dashboard and click "AI Predictions"

## API Endpoints

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/predict` | GET | Get predictions for next year |
| `/api/model/info` | GET | Get model information and metrics |
| `/api/train` | POST | Train/retrain all models |
| `/api/graphs/<type>` | GET | Get visualization graphs |
| `/api/backtest` | GET | Validate model with backtesting |
| `/api/export/pdf` | POST | Export predictions to PDF |
| `/api/cas/export/training-report` | GET | Download complete training report |
| `/api/health` | GET | Health check |

## Port Configuration

- Engineering: Port 5000
- CCJE: Port 5001
- CBAA: Port 5002
- **CAS: Port 5003**

## Algorithms Used

1. Linear Regression
2. Ridge Regression
3. Lasso Regression
4. Random Forest
5. Gradient Boosting
6. Support Vector Machine
7. Decision Tree

## Training Report Contents

The downloadable training report includes:
1. Data Collection methodology
2. Data Cleaning and Preparation
3. Dataset Splitting (80% train, 20% test)
4. Feature Selection
5. Model Selection
6. Model Training process
7. Model Testing and Evaluation
8. Evaluation Metrics (R², MAE, MSE, RMSE)
9. Prediction Generation
10. Complete Training Dataset
11. Model Performance Comparison
12. Visualizations

## Files

- `advanced_predictor_cas.py` - Main ML predictor class
- `prediction_api_cas.py` - Flask API server
- `training_report_generator_cas.py` - PDF report generator
- `requirements.txt` - Python dependencies
- `setup.bat` - Environment setup script
- `train.bat` - Model training script
- `start_api.bat` - API server starter
