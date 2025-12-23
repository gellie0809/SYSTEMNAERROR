# CBAA Board Exam Prediction System - Quick Start Guide

## 🚀 Quick Setup (5 Minutes)

### Step 1: Install Dependencies
```bash
cd C:\laragon\www\SYSTEMNAERROR-3\FINALSYSTEMNAERROR\prediction_cbaa
setup.bat
```

### Step 2: Verify Data
Open in browser:
```
http://localhost/SYSTEMNAERROR-3/FINALSYSTEMNAERROR/prediction_cbaa/check_cbaa_data.php
```

Ensure you have at least 10+ records for training.

### Step 3: Train Models
```bash
train.bat
```

Wait 2-5 minutes for training to complete.

### Step 4: Start API Server
```bash
start_api.bat
```

Keep this terminal window open.

### Step 5: Access Web Interface
Open in browser:
```
http://localhost/SYSTEMNAERROR-3/FINALSYSTEMNAERROR/prediction_cbaa_anonymous.php
```

Login with: `cbaa_admin@lspu.edu.ph`

## ✅ What You Get

- **7 ML Algorithms** trained and compared
- **Real-time Predictions** for next year
- **Performance Visualizations** (graphs and charts)
- **Model Comparison** (accuracy, R², MAE)
- **Backtesting/Validation** (test on historical data)
- **PDF Training Report** (comprehensive documentation)

## 📊 Features

### 1. Predictions
- Next year passing rate predictions
- Predictions by board exam type
- Confidence intervals
- Model used for prediction

### 2. Model Performance
- R² Score (goodness of fit)
- Mean Absolute Error (MAE)
- Cross-validation scores
- Accuracy percentage

### 3. Visualizations
- Model comparison charts
- Accuracy comparison
- Error analysis
- Predictions vs actual
- Historical trends

### 4. Validation
- Backtest on 2023 data
- Train on 2021-2022 data
- Compare predictions vs actual
- Validate model accuracy

### 5. Export
- Download comprehensive PDF report
- Includes all training data
- Model metrics
- All visualizations

## 🎯 Port Information

- **CBAA API**: Port 5002
- Engineering API: Port 5000
- CBAA API: Port 5001

All systems run independently without conflicts.

## 🔧 Troubleshooting

### API Not Running
```bash
cd prediction_cbaa
start_api.bat
```

### Need to Retrain
```bash
cd prediction_cbaa
train.bat
```

### Check Python
```bash
python --version
```
Should be Python 3.8 or higher.

### View API Status
```
http://localhost:5002/api/health
```

## 📝 API Endpoints

- `GET /api/predict` - Get predictions
- `GET /api/model/info` - Model metrics
- `POST /api/train` - Train models
- `GET /api/graphs/<type>` - Get graphs
- `GET /api/backtest` - Validate accuracy
- `GET /api/cbaa/export/training-report` - Download PDF

## 🎓 Department-Specific

This system is specifically designed for:
- **Department**: Business Administration and Accountancy
- **Data Source**: Anonymous board passers
- **Exam Types**: CPALE, REBLE, CMA, CBLE, etc.

## 💡 Tips

1. Train models before using predictions
2. Keep API server running while using web interface
3. Retrain models when new data is added
4. Download training report for documentation
5. Use backtesting to validate accuracy

## ❓ Support

For issues or questions:
1. Check `check_cbaa_data.php` for data availability
2. Verify API is running on port 5002
3. Check terminal for error messages
4. Ensure MySQL database is running
