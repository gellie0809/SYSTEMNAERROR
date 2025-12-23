# CBAA Board Exam Prediction System

AI-powered board exam prediction system for the College of Business Administration and Accountancy (CBAA) using anonymous board passer data.

## Quick Start

### 1. Setup (First Time Only)
```bash
setup.bat
```

This will:
- Create a Python virtual environment
- Install all required packages
- Prepare the system for training

### 2. Train Models
```bash
train.bat
```

This will:
- Fetch CBAA anonymous board passer data
- Train 7 different machine learning models
- Generate performance visualizations
- Save trained models and metadata

### 3. Start API Server
```bash
start_api.bat
```

This will start the Flask API on port 5002

## Features

### 7 Machine Learning Algorithms
1. Linear Regression
2. Ridge Regression
3. Lasso Regression
4. Random Forest
5. Gradient Boosting
6. Support Vector Machine
7. Decision Tree

### API Endpoints (Port 5002)

- `GET /api/predict` - Get next year predictions
- `GET /api/model/info` - Get model information and metrics
- `POST /api/train` - Train all models
- `GET /api/graphs/<type>` - Get visualization graphs
- `GET /api/backtest` - Validate model accuracy
- `GET /api/cbaa/export/training-report` - Download comprehensive PDF report

### Visualizations

- Model Comparison (R² Scores)
- Accuracy Comparison
- Error Comparison (MAE)
- Predictions vs Actual
- Historical Trends

### Training Report

Comprehensive PDF report including:
- Complete training dataset
- Model performance metrics
- Algorithm comparison
- Predictions and analysis
- All visualization graphs

## Requirements

- Python 3.8 or higher
- MySQL database with anonymous_board_passers table
- CBAA department data

## Data Source

Uses anonymous board passer data from the `anonymous_board_passers` table where:
- `department = 'Business Administration and Accountancy'`
- `is_deleted = 0` or `NULL`

## Port Configuration

- Engineering: Port 5000
- CCJE: Port 5001
- **CBAA: Port 5002** ← This system

## Notes

- Ensure the MySQL database is running before training or starting the API
- Train models before making predictions
- Models are automatically saved after training
- API server must be running for web interface to work
