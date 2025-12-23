# CCJE Board Exam Prediction System - Quick Start Guide

## 🎯 Overview
The CCJE prediction system is now fully set up with all the features from the Engineering department, running on a separate port (5001) to avoid conflicts.

## ✅ What's Been Created

### Backend (Python/Flask API)
```
prediction_ccje/
├── advanced_predictor_ccje.py          # 7 ML algorithms trainer
├── prediction_api_ccje.py              # Flask API (port 5001)
├── training_report_generator_ccje.py   # PDF report generator
├── requirements.txt                     # Python dependencies
├── setup.bat                            # First-time setup
├── train.bat                            # Train models
├── start_api.bat                       # Start API server
├── README.md                           # Full documentation
└── models/, graphs/, reports/          # Auto-created folders
```

### Frontend (PHP)
- **prediction_ccje_anonymous.php** - Full-featured web interface

## 🚀 Setup Steps (Run These Once)

### Step 1: Navigate to Prediction Folder
```powershell
cd C:\laragon\www\SYSTEMNAERROR-3\FINALSYSTEMNAERROR\prediction_ccje
```

### Step 2: Run Setup (First Time Only)
```powershell
.\setup.bat
```
This will:
- Create Python virtual environment
- Install all required packages (scikit-learn, Flask, pandas, etc.)

### Step 3: Train the Models
```powershell
.\train.bat
```
This will:
- Fetch CCJE anonymous data from database
- Train 7 different ML algorithms
- Generate comparison graphs
- Save the best model

### Step 4: Start the API Server
```powershell
.\start_api.bat
```
This will:
- Start Flask API on http://localhost:5001
- Keep the window open while in use

## 📊 Features Included

### ✅ AI Model Performance
- R² Score (Accuracy)
- Mean Absolute Error (MAE)
- Cross-Validation Score
- Training date and record count

### ✅ Algorithm Comparison
Compares 7 algorithms:
1. Linear Regression
2. Ridge Regression
3. Lasso Regression
4. Random Forest
5. Gradient Boosting
6. Support Vector Machine (SVM)
7. Decision Tree

### ✅ Performance Visualization
- Model comparison bar charts
- Residual analysis scatter plots
- Real-time graph generation

### ✅ Predictions with Confidence Intervals
- 95% confidence intervals
- Historical vs predicted comparisons
- Standard deviation calculations

### ✅ Download Training Report
- Complete PDF with all training data
- Model performance metrics
- Algorithm comparison tables
- Visualization graphs included

### ✅ Export to PDF
- Professional prediction reports
- LSPU CCJE branding
- Ready for presentations

## 🔗 Access the System

1. **Make sure API is running** (keep start_api.bat window open)
2. **Open browser** to CCJE dashboard
3. **Click** "Prediction for Anonymous Data" in sidebar
4. **View** predictions, graphs, and metrics

## 📡 API Endpoints (Port 5001)

- `GET /api/health` - Health check
- `GET /api/status` - Model status
- `GET /api/predict` - Get predictions
- `GET /api/model/info` - Model metrics
- `POST /api/train` - Retrain models
- `GET /api/graphs/model_comparison` - Comparison chart
- `GET /api/graphs/residuals` - Residual analysis
- `GET /api/export/training-report` - Download PDF report
- `POST /api/export/pdf` - Export predictions

## 🔒 Independence from Engineering

### Port Separation
- **CCJE**: http://localhost:5001
- **Engineering**: http://localhost:5000

### Data Separation
- **CCJE**: Uses `anonymous_board_passers` filtered by "Criminal Justice Education"
- **Engineering**: Uses its own data source

### No Interference
- Separate Python environments
- Separate model files
- Separate APIs

## 🛠️ Troubleshooting

### "Prediction Service Unavailable"
**Solution**: Follow the setup instructions on the error page
1. Run `setup.bat`
2. Run `train.bat`
3. Run `start_api.bat`
4. Refresh page

### "Port 5001 already in use"
**Solution**: 
- Check if another CCJE API is running
- Stop it and restart
- Or change port in `prediction_api_ccje.py`

### "No training data available"
**Solution**:
- Verify CCJE anonymous records exist in database
- Check database connection settings
- Run SQL query to confirm data

### Import/Module Errors
**Solution**:
- Run `setup.bat` again
- Make sure virtual environment is activated
- Check `requirements.txt`

## 📁 File Locations

### Python Backend
```
C:\laragon\www\SYSTEMNAERROR-3\FINALSYSTEMNAERROR\prediction_ccje\
```

### PHP Frontend
```
C:\laragon\www\SYSTEMNAERROR-3\FINALSYSTEMNAERROR\prediction_ccje_anonymous.php
```

### Sidebar Link
```
C:\laragon\www\SYSTEMNAERROR-3\FINALSYSTEMNAERROR\includes\ccje_nav.php
(Lines 52-56)
```

## 🎨 CCJE Red Theme
The entire system uses CCJE branding colors:
- Primary Red: #D32F2F
- Dark Red: #800020
- Gold Accent: #FAD6A5

## 🔄 Updating Models

To retrain with latest data:
1. Click "Retrain Models" button in web interface, OR
2. Run `train.bat` again from command line

## 📊 Expected Results

After training, you should see:
- **Best Model**: Usually Random Forest or Gradient Boosting
- **R² Score**: 0.85-0.95 (higher is better)
- **MAE**: 2-5% (lower is better)
- **Graphs**: Model comparison and residual analysis

## 🎉 You're Ready!

The CCJE prediction system is fully operational with all features matching the Engineering department. The system is completely independent and will not affect any other department's prediction systems.

For support or questions, refer to the README.md in the prediction_ccje folder.
