# 🎓 LSPU Board Exam Prediction System
## Advanced AI-Powered Predictions with Multiple Algorithms

### 🌟 Features

✅ **Multiple Algorithm Comparison** - 7 different ML algorithms
✅ **95% Confidence Intervals** - Statistical confidence ranges
✅ **Performance Visualization** - Beautiful charts and graphs
✅ **PDF Export** - Professional prediction reports
✅ **Real-time API** - RESTful API for predictions
✅ **COE Color Theme** - Matches Engineering department branding

---

## 📋 Installation Guide

### Step 1: Install Python

1. Download Python 3.8 or higher from [python.org](https://www.python.org/downloads/)
2. **IMPORTANT**: Check "Add Python to PATH" during installation
3. Verify installation:
   ```
   python --version
   ```

### Step 2: Setup the Prediction System

1. Open Command Prompt or PowerShell
2. Navigate to prediction folder:
   ```
   cd C:\laragon\www\SYSTEMNAERROR-3\FINALSYSTEMNAERROR\prediction
   ```
3. Run setup:
   ```
   setup.bat
   ```

This will:
- Create a virtual environment
- Install all required packages (scikit-learn, XGBoost, pandas, etc.)
- Set up the project structure

### Step 3: Train the Models

Run the training script to analyze your data and train all 7 algorithms:
```
train.bat
```

You'll see:
- Data extraction from your database
- Training progress for each algorithm
- Model comparison results
- Best model selection
- Visualization graph generation

### Step 4: Start the API Server

```
start_api.bat
```

The API will run at `http://localhost:5000`

### Step 5: Access Predictions

Open in your browser:
```
http://localhost/SYSTEMNAERROR-3/FINALSYSTEMNAERROR/prediction_engineering.php
```

---

## 🤖 Machine Learning Algorithms Used

The system trains and compares these 7 algorithms:

1. **Linear Regression** - Simple, interpretable baseline
2. **Ridge Regression** - L2 regularization for stability
3. **Lasso Regression** - L1 regularization for feature selection
4. **Random Forest** - Ensemble of decision trees
5. **Gradient Boosting** - Sequential ensemble learning
6. **XGBoost** - Optimized gradient boosting
7. **Support Vector Regression** - Non-linear pattern recognition

The **best performing model** is automatically selected based on:
- R² Score (how well it predicts)
- Cross-Validation Score (consistency)
- Mean Absolute Error (accuracy)

---

## 📊 Understanding the Predictions

### Prediction Values
- **Historical Average**: Last year's actual passing rate
- **Predicted Rate**: AI-predicted rate for next year
- **Change**: Expected increase/decrease

### Confidence Intervals (95%)
- Shows the range where the actual result is 95% likely to fall
- **Narrower intervals** = more confident predictions
- **Example**: Predicted 75% with interval [72%, 78%]
  - We're 95% confident the actual rate will be between 72-78%

### Model Metrics

**R² Score (Coefficient of Determination)**
- Range: 0.0 to 1.0 (higher is better)
- 0.8 = Model explains 80% of variation
- 0.95 = Excellent predictions

**Mean Absolute Error (MAE)**
- Average error in percentage points
- 3.5% MAE = predictions typically within 3.5% of actual
- Lower is better

**Cross-Validation Score**
- Tests model on different data subsets
- Ensures the model generalizes well
- Higher is better

---

## 📁 File Structure

```
prediction/
├── advanced_predictor.py     # Main ML training script
├── prediction_api.py          # Flask API server
├── pdf_generator.py           # PDF report generator
├── requirements.txt           # Python dependencies
├── setup.bat                  # Installation script
├── train.bat                  # Training script
├── start_api.bat              # API launcher
├── models/                    # Saved ML models
│   ├── best_model.pkl
│   ├── scaler.pkl
│   └── model_metadata.json
└── output/                    # Generated files
    ├── graphs/                # Visualization charts
    │   ├── model_comparison.png
    │   └── residuals.png
    └── *.pdf                  # Exported reports
```

---

## 🔧 Troubleshooting

### "Python is not recognized"
- Reinstall Python and check "Add to PATH"
- Or manually add Python to your system PATH

### "Module not found" error
1. Activate virtual environment:
   ```
   venv\Scripts\activate.bat
   ```
2. Reinstall packages:
   ```
   pip install -r requirements.txt
   ```

### "Connection refused" when accessing predictions
- Make sure `start_api.bat` is running
- Check if port 5000 is available
- Firewall might be blocking the connection

### "No data available for training"
- Add board exam data through the main system first
- Data must include:
  - Board exam dates
  - Exam results (Passed/Failed/Conditional)
  - Department = 'Engineering'

### Predictions seem inaccurate
- Need more historical data (at least 2-3 years)
- Add more exam records
- Retrain the model after adding data

---

## 🚀 API Endpoints

### GET /api/predict
Returns predictions with confidence intervals

**Response:**
```json
{
  "success": true,
  "data": {
    "predictions": [
      {
        "board_exam_type": "Registered Electrical Engineer",
        "predicted_passing_rate": 75.23,
        "confidence_interval_95": {
          "lower": 72.15,
          "upper": 78.31
        },
        "std_deviation": 3.12,
        "current_year": 2024,
        "prediction_year": 2025,
        "historical_avg": 73.50
      }
    ],
    "model_info": {
      "model_name": "XGBoost",
      "r2_score": 0.9234,
      "mae": 2.87
    }
  }
}
```

### POST /api/train
Retrain all models with latest data

### GET /api/model/info
Get model performance and comparison

### POST /api/export/pdf
Generate PDF report

### GET /api/graphs/<name>
Get visualization graphs
- `/api/graphs/model_comparison`
- `/api/graphs/residuals`

---

## 📈 How It Works

1. **Data Collection**
   - Extracts historical board exam data from your database
   - Groups by year, exam type, and attempt (first-timer/repeater)

2. **Feature Engineering**
   - Calculates passing rates, fail rates, conditional rates
   - Creates time-based features
   - Encodes categorical variables (exam types)
   - Computes rolling averages

3. **Model Training**
   - Splits data into training (80%) and testing (20%)
   - Trains all 7 algorithms
   - Performs cross-validation
   - Selects best model based on performance

4. **Prediction**
   - Uses best model for next year's predictions
   - Bootstrap method for confidence intervals
   - Ensures predictions stay within 0-100% range

5. **Visualization**
   - Generates comparison charts
   - Creates residual plots
   - Shows actual vs predicted scatter plots

6. **Export**
   - Professional PDF reports
   - Includes interpretation guide
   - Executive summary with trends

---

## 🎯 Best Practices

### For Accurate Predictions:

1. **Quality Data**
   - Ensure dates are accurate
   - Verify exam results are correct
   - Avoid duplicate records

2. **Regular Updates**
   - Add new exam data promptly
   - Retrain model quarterly or after major exams
   - Keep data current

3. **Interpretation**
   - Don't rely solely on point predictions
   - Consider confidence intervals
   - Look for trends over time
   - Use for planning, not guarantees

4. **Data Volume**
   - Minimum: 20-30 records per exam type
   - Recommended: 50+ records spanning 2-3 years
   - More data = better predictions

---

## 🔄 Maintenance

### When to Retrain:
- After adding significant new data (quarterly)
- When prediction accuracy drops
- After curriculum changes
- Annually at minimum

### Model Updates:
The system automatically:
- Saves model metadata
- Tracks performance metrics
- Generates comparison reports
- Updates visualizations

---

## 💡 Use Cases

1. **Resource Planning** - Allocate support resources based on predictions
2. **Curriculum Review** - Identify programs needing improvement
3. **Student Support** - Plan review sessions for predicted low-performing exams
4. **Budget Allocation** - Justify funding for preparatory programs
5. **Trend Analysis** - Track departmental performance over time
6. **Reporting** - Professional reports for administration

---

## 📞 Support

For issues or questions:
1. Check this README
2. Review error messages in terminal
3. Verify Python and packages are installed
4. Check if API is running
5. Ensure database connection is working

---

## 🏆 Credits

Developed for:
**Laguna State Polytechnic University**
**College of Engineering**
**San Pablo City Campus**

Technology Stack:
- Python 3.8+
- scikit-learn, XGBoost
- Flask, pandas, numpy
- matplotlib, seaborn
- PHP 7.4+, MySQL

---

## 📜 License

For internal use by LSPU College of Engineering only.

---

## ✨ Version History

**v2.0** - Advanced Features
- Multiple algorithm comparison
- Confidence intervals
- Visualization graphs
- PDF export
- Enhanced UI

**v1.0** - Initial Release
- Basic linear regression
- Simple predictions
