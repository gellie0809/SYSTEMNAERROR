# CTE Board Exam Prediction System

## Overview
This module provides AI-powered board exam passing rate predictions for the College of Teacher Education (CTE) department using anonymous board passer data.

## Features
- 7 machine learning algorithms comparison
- Model validation through backtesting
- PDF report generation
- Historical trends visualization
- Confidence intervals for predictions

## Quick Start

### 1. Setup (First Time Only)
```batch
setup.bat
```

### 2. Train Models
```batch
train.bat
```

### 3. Start API Server
```batch
start_api.bat
```

The API will be available at http://localhost:5003

## API Endpoints

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/predict` | GET | Get predictions for next year |
| `/api/model/info` | GET | Get model information and metrics |
| `/api/train` | POST | Train all models |
| `/api/graphs/<type>` | GET | Get visualization graphs |
| `/api/backtest` | GET | Validate model with historical data |
| `/api/export/pdf` | POST | Export predictions to PDF |
| `/api/cte/export/training-report` | GET | Download training report PDF |
| `/api/health` | GET | Health check |

## Port Configuration
- Engineering: Port 5000
- CCJE: Port 5001
- CBAA: Port 5002
- **CTE: Port 5003**

## Machine Learning Algorithms
1. Linear Regression
2. Ridge Regression
3. Lasso Regression
4. Random Forest
5. Gradient Boosting
6. Support Vector Machine
7. Decision Tree

## Directory Structure
```
prediction_cte/
├── advanced_predictor_cte.py      # ML training and prediction
├── prediction_api_cte.py           # Flask API server
├── training_report_generator_cte.py # PDF report generator
├── requirements.txt                # Python dependencies
├── setup.bat                       # Environment setup script
├── train.bat                       # Model training script
├── start_api.bat                   # API server startup script
├── models/                         # Trained models (auto-generated)
├── graphs/                         # Visualization graphs (auto-generated)
└── reports/                        # PDF reports (auto-generated)
```

## Training Report Contents
The downloadable training report includes:
1. Data Collection methodology
2. Data Cleaning and Preparation steps
3. Dataset Splitting (80% training, 20% testing)
4. Feature Selection details
5. Model Selection rationale
6. Model Training process
7. Model Testing and Evaluation
8. Evaluation Metrics (R², MAE, MSE, RMSE, Accuracy)
9. Complete Training Dataset
10. Model Performance Comparison
11. Visualizations
