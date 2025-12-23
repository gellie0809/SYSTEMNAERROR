# CAS Anonymous Data Prediction System

## Overview
This is a machine learning prediction system specifically designed for the **College of Arts and Sciences (CAS)** anonymous board exam data. It operates completely independently from the Engineering department prediction system.

## Features
- ✅ **Separate ML Models**: Uses dedicated models stored in `models/arts_and_sciences/`
- ✅ **7 ML Algorithms**: Compares Linear Regression, Ridge, Lasso, Random Forest, Gradient Boosting, XGBoost, and SVR
- ✅ **95% Confidence Intervals**: Provides prediction ranges for reliability
- ✅ **Independent Operation**: Does not affect Engineering department predictions
- ✅ **Anonymous Data**: Works with CAS anonymous board exam records
- ✅ **CAS Theme**: Pink/Maroon color scheme matching CAS branding

## File Structure
```
prediction/
├── advanced_predictor_cas.py      # CAS-specific ML training module
├── prediction_api.py               # API with CAS endpoints (/api/cas/*)
├── train_cas.bat                   # Training script for CAS models
├── models/
│   └── arts_and_sciences/         # CAS model storage
│       ├── best_model.pkl         # Trained best model
│       ├── scaler.pkl             # Feature scaler
│       └── model_metadata.json    # Model info
└── output/
    └── arts_and_sciences/         # CAS output directory
        └── graphs/                # Visualization graphs

FINALSYSTEMNAERROR/
└── prediction_cas.php             # CAS prediction interface
```

## Setup Instructions

### 1. Install Dependencies (First Time Only)
```bash
cd C:\laragon\www\SYSTEMNAERROR-3\FINALSYSTEMNAERROR\prediction
setup.bat
```

### 2. Train CAS Models
```bash
cd C:\laragon\www\SYSTEMNAERROR-3\FINALSYSTEMNAERROR\prediction
train_cas.bat
```

This will:
- Fetch CAS anonymous data from database
- Train 7 different ML algorithms
- Compare performance and select best model
- Generate model comparison charts
- Save models to `models/arts_and_sciences/`

### 3. Start API Server
```bash
cd C:\laragon\www\SYSTEMNAERROR-3\FINALSYSTEMNAERROR\prediction
start_api.bat
```

The API server runs on `http://localhost:5000` and serves BOTH:
- Engineering endpoints: `/api/predict`, `/api/train`, etc.
- CAS endpoints: `/api/cas/predict`, `/api/cas/train`, etc.

### 4. Access CAS Predictions
Open in browser:
```
http://localhost/SYSTEMNAERROR-3/FINALSYSTEMNAERROR/prediction_cas.php
```

Or click the **"AI Predictions"** button from the CAS Anonymous Dashboard.

## API Endpoints

### CAS-Specific Endpoints
```
GET  /api/cas/predict          - Get CAS predictions with confidence intervals
POST /api/cas/train            - Train CAS models with latest data
GET  /api/cas/model/info       - Get CAS model information
GET  /api/cas/graphs/<name>    - Get CAS visualization graphs
```

### Engineering Endpoints (Unchanged)
```
GET  /api/predict              - Get Engineering predictions
POST /api/train                - Train Engineering models
GET  /api/model/info           - Get Engineering model info
GET  /api/graphs/<name>        - Get Engineering graphs
```

## Database Requirements

The system fetches data from:
```sql
SELECT * FROM anonymous_board_passers 
WHERE department = 'Arts and Sciences'
AND (is_deleted IS NULL OR is_deleted = 0)
```

Required fields:
- `board_exam_type` - Type of board exam (e.g., LET, Licensure Exam for Librarians)
- `board_exam_date` - Date of the exam
- `exam_type` - "First Timer" or "Repeater"
- `result` - "Passed", "Failed", or "Conditional"
- `department` - Must be "Arts and Sciences"
- `is_deleted` - Soft delete flag (0 or NULL for active records)

## How It Works

### 1. Data Preparation
- Fetches anonymous CAS board exam records
- Groups by exam type, date, and take attempts
- Calculates passing rates, fail rates, conditional rates
- Creates features: year normalized, examinees count, ratios

### 2. Model Training
- Trains 7 different ML algorithms
- Uses cross-validation for reliability
- Compares models using R² score and MAE
- Selects best performing model automatically

### 3. Prediction Generation
- Uses trained model to predict next year's passing rates
- Calculates 95% confidence intervals (±5%)
- Estimates passed/failed/conditional counts
- Generates predictions per exam type and take attempt

## Model Performance Metrics

### R² Score (Accuracy)
- **Range**: 0 to 1
- **Interpretation**: How well the model fits the data
- **Good**: > 0.7
- **Excellent**: > 0.9

### MAE (Mean Absolute Error)
- **Range**: 0 to 100 (percentage points)
- **Interpretation**: Average prediction error
- **Good**: < 5%
- **Excellent**: < 2%

### CV Score (Cross-Validation)
- **Range**: 0 to 1
- **Interpretation**: Model consistency across data splits
- **Good**: > 0.6
- **Excellent**: > 0.8

## Independence from Engineering System

### Separate Storage
- Engineering: `models/engineering/`
- CAS: `models/arts_and_sciences/`

### Separate API Endpoints
- Engineering: `/api/*`
- CAS: `/api/cas/*`

### Separate Data Source
- Engineering: `WHERE department = 'Engineering'`
- CAS: `WHERE department = 'Arts and Sciences'`

### Separate Training
- Engineering: `train.bat` → `advanced_predictor.py`
- CAS: `train_cas.bat` → `advanced_predictor_cas.py`

## Troubleshooting

### "No predictions available" Error
**Solution**: Train the CAS models first
```bash
cd prediction
train_cas.bat
```

### "Prediction service unavailable" Error
**Solution**: Start the API server
```bash
cd prediction
start_api.bat
```

### "No data available for training" Error
**Solution**: Add CAS anonymous data via:
1. Go to CAS Anonymous Dashboard
2. Click "Add Anonymous Data"
3. Enter board exam records
4. Train models again

### Graph not showing
**Solution**: Retrain models to regenerate graphs
- Click "Retrain Models with Latest Data" button
- Graphs will be saved to `output/arts_and_sciences/graphs/`

## Retraining Models

### When to Retrain?
- After adding new CAS anonymous data
- Monthly or quarterly for updated predictions
- When prediction accuracy seems low

### How to Retrain?
**Option 1**: Via Web Interface
1. Go to `prediction_cas.php`
2. Click "Retrain Models with Latest Data"
3. Wait for completion (may take 1-3 minutes)

**Option 2**: Via Command Line
```bash
cd prediction
train_cas.bat
```

## Color Theme
The CAS prediction interface uses the CAS department color scheme:
- Primary: `#ec4899` (Pink)
- Secondary: `#9f1239` (Maroon)
- Accent: `#db2777` (Medium Pink)
- Dark: `#4F0024` (Dark Maroon)
- Light: `#fdf2f8`, `#fce7f3` (Light Pink backgrounds)

## Technical Details

### Python Dependencies
- pandas, numpy - Data processing
- scikit-learn - ML algorithms and metrics
- xgboost - Advanced gradient boosting
- mysql-connector-python - Database connection
- flask, flask-cors - API server
- matplotlib, seaborn - Visualization
- joblib - Model serialization

### ML Algorithms Used
1. **Linear Regression** - Simple baseline
2. **Ridge Regression** - L2 regularization
3. **Lasso Regression** - L1 regularization
4. **Random Forest** - Ensemble decision trees
5. **Gradient Boosting** - Sequential ensemble
6. **XGBoost** - Optimized gradient boosting
7. **Support Vector Regression** - Non-linear patterns

### Feature Engineering
- Year normalization
- Examinees count
- First timer / Repeater ratios
- Historical passing rates
- Moving averages (3-period)
- One-hot encoded exam types

## Support
For issues or questions:
1. Check this README
2. Review `TROUBLESHOOTING.md` in prediction folder
3. Check API server logs
4. Verify database connection

## Version
- Created: December 6, 2025
- System: CAS Anonymous Data ML Prediction v1.0
- Compatible with: Engineering Prediction System (independent operation)
