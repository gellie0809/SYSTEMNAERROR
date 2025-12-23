# CAS Department AI Prediction Feature - Implementation Guide

## Overview
This document describes the AI prediction feature for the **College of Arts and Sciences (CAS)** department, which operates independently from the Engineering department's prediction system.

## Features Implemented

### 1. **Separate Prediction Models**
- **Location**: `prediction/advanced_predictor_cas.py`
- **Department**: College of Arts and Sciences
- **Data Source**: Anonymous board exam data for CAS (`department = 'Arts and Sciences'`)
- **Model Storage**: `models/arts_and_sciences/`
- **Output Storage**: `output/arts_and_sciences/`

### 2. **Independent API Endpoints**
All CAS prediction endpoints are under `/api/cas/`:
- `GET /api/cas/predict` - Get CAS predictions with confidence intervals
- `POST /api/cas/train` - Train CAS models
- `GET /api/cas/model/info` - Get CAS model information
- `GET /api/cas/graphs/<name>` - Get CAS visualization graphs

### 3. **Dedicated Web Interface**
- **File**: `prediction_cas.php`
- **Access**: Only for `cas_admin@lspu.edu.ph`
- **Button Location**: Anonymous Dashboard CAS (`anonymous_dashboard_cas.php`)
- **Color Scheme**: Rose/Pink theme (#9f1239, #4F0024, #fecdd3)

## Setup Instructions

### First-Time Setup

1. **Navigate to prediction directory**:
   ```powershell
   cd c:\laragon\www\SYSTEMNAERROR-3\FINALSYSTEMNAERROR\prediction
   ```

2. **Ensure virtual environment is set up** (if not already):
   ```powershell
   .\setup.bat
   ```

3. **Train CAS models**:
   ```powershell
   .\train_cas.bat
   ```
   This will:
   - Fetch CAS anonymous board exam data
   - Train 7 different ML algorithms
   - Compare performance and select best model
   - Generate visualizations
   - Save models to `models/arts_and_sciences/`

4. **Start the API server** (if not already running):
   ```powershell
   .\start_api.bat
   ```

### Accessing CAS Predictions

1. **Login** as CAS admin: `cas_admin@lspu.edu.ph`
2. **Navigate** to Anonymous Dashboard CAS
3. **Click** the "AI Predictions" button
4. **View** predictions for the next year

## Architecture

### Data Flow
```
anonymous_board_passers (department='Arts and Sciences')
    ↓
advanced_predictor_cas.py
    ↓
models/arts_and_sciences/best_model.pkl
    ↓
prediction_api.py (/api/cas/*)
    ↓
prediction_cas.php
```

### Separation from Engineering Department

| Aspect | Engineering | CAS |
|--------|-------------|-----|
| **Python Module** | `advanced_predictor.py` | `advanced_predictor_cas.py` |
| **Model Directory** | `models/` | `models/arts_and_sciences/` |
| **Output Directory** | `output/` | `output/arts_and_sciences/` |
| **API Endpoints** | `/api/*` | `/api/cas/*` |
| **Web Interface** | `prediction_engineering.php` | `prediction_cas.php` |
| **Data Filter** | `department='Engineering'` | `department='Arts and Sciences'` |
| **Access** | `eng_admin@lspu.edu.ph` | `cas_admin@lspu.edu.ph` |

## Features

### AI Model Capabilities
✅ **7 Machine Learning Algorithms**:
1. Linear Regression
2. Ridge Regression
3. Lasso Regression
4. Random Forest
5. Gradient Boosting
6. XGBoost
7. Support Vector Regression

✅ **95% Confidence Intervals** for all predictions

✅ **Performance Metrics**:
- R² Score (Accuracy)
- Mean Absolute Error (MAE)
- Cross-Validation Scores

✅ **Visualization Graphs**:
- Model Comparison Charts
- Residual Analysis
- Actual vs Predicted Plots

### Prediction Display

For each board exam type, the system shows:
- Current year passing rate
- Predicted next year passing rate
- Expected change (increase/decrease)
- 95% Confidence Interval
- Standard deviation

### Algorithm Comparison

The system compares all 7 algorithms and:
- Automatically selects the best performing model
- Shows performance metrics for all models
- Displays a trophy icon for the winning algorithm

## Color Scheme (CAS Theme)

The CAS prediction interface uses a rose/pink color scheme:
- **Primary**: #9f1239 (Rose Red)
- **Secondary**: #4F0024 (Dark Rose)
- **Light**: #fecdd3 (Light Pink)
- **Background**: #fef2f2 to #fecdd3 gradient

## Graph Locations

After training, graphs are saved to:
- `output/arts_and_sciences/graphs/model_comparison.png`
- `output/arts_and_sciences/graphs/residuals.png`

## Database Query

The CAS predictor queries:
```sql
SELECT 
    YEAR(board_exam_date) as year,
    MONTH(board_exam_date) as month,
    board_exam_type,
    exam_type as take_attempts,
    COUNT(*) as total_examinees,
    SUM(CASE WHEN result = 'Passed' THEN 1 ELSE 0 END) as passed,
    SUM(CASE WHEN result = 'Failed' THEN 1 ELSE 0 END) as failed,
    SUM(CASE WHEN result = 'Conditional' THEN 1 ELSE 0 END) as conditional,
    AVG(CASE WHEN result = 'Passed' THEN 1 ELSE 0 END) as avg_pass_rate
FROM anonymous_board_passers
WHERE department = 'Arts and Sciences'
AND (is_deleted IS NULL OR is_deleted = 0)
AND board_exam_date IS NOT NULL
GROUP BY YEAR(board_exam_date), MONTH(board_exam_date), board_exam_type, exam_type
ORDER BY year ASC, month ASC
```

## Troubleshooting

### "Prediction Service Unavailable"
**Solution**: 
1. Train the CAS models: `.\train_cas.bat`
2. Start the API server: `.\start_api.bat`
3. Refresh the page

### "Model not trained yet"
**Solution**: Run `.\train_cas.bat` to train CAS models

### "Graph not available"
**Solution**: Retrain the models using the "Retrain Models" button on the prediction page

### API not responding
**Solution**: 
1. Check if the API server is running on port 5000
2. Run `.\start_api.bat`
3. Check for port conflicts

## Retraining Models

Models should be retrained when:
- New CAS data is added to the database
- Significant changes in passing rates are observed
- At the start of each academic year

**To retrain**:
1. Via web interface: Click "Retrain Models" button on `prediction_cas.php`
2. Via command line: Run `.\train_cas.bat`

## Independence from Engineering

✅ **No conflicts**: CAS and Engineering predictions run independently
✅ **Separate models**: Each department has its own trained models
✅ **Separate storage**: Models and outputs are stored in different directories
✅ **Separate endpoints**: Different API routes for each department
✅ **Separate authentication**: Different admin accounts

## File Summary

### New Files Created
1. `prediction/advanced_predictor_cas.py` - CAS ML predictor
2. `prediction_cas.php` - CAS prediction web interface

### Modified Files
None - All existing files remain unchanged

### Existing Files Used
1. `prediction/prediction_api.py` - Already had CAS endpoints
2. `prediction/train_cas.bat` - Training script for CAS
3. `anonymous_dashboard_cas.php` - Already had prediction button

## Testing Checklist

- [ ] CAS models train successfully
- [ ] API returns CAS predictions
- [ ] Graphs are generated with CAS colors
- [ ] Prediction page displays correctly
- [ ] Confidence intervals are shown
- [ ] All 7 algorithms are compared
- [ ] Retrain button works
- [ ] Engineering predictions still work independently
- [ ] No cross-department data contamination

## Future Enhancements

Potential improvements:
- PDF export for CAS predictions
- Historical accuracy tracking
- Multi-year predictions
- Comparison with Engineering department trends
- Email alerts for significant prediction changes

---

**Last Updated**: December 6, 2025
**Status**: ✅ Fully Implemented and Ready for Use
