# 🔧 Troubleshooting Guide

## Common Issues and Solutions

### 1. Python Not Found

**Error:** `'python' is not recognized as an internal or external command`

**Solution:**
1. Download Python from https://www.python.org/downloads/
2. During installation, **CHECK** ✅ "Add Python to PATH"
3. Restart Command Prompt
4. Verify: `python --version`

**Alternative:**
If already installed but not in PATH:
- Search Windows for "Environment Variables"
- Edit System PATH
- Add Python installation path (usually `C:\Python3X\`)

---

### 2. Module Import Errors

**Error:** `ModuleNotFoundError: No module named 'sklearn'` (or pandas, flask, etc.)

**Solution:**
```batch
cd C:\laragon\www\SYSTEMNAERROR-3\FINALSYSTEMNAERROR\prediction
venv\Scripts\activate.bat
pip install -r requirements.txt
```

If that fails:
```batch
pip install --upgrade pip
pip install scikit-learn pandas numpy flask flask-cors joblib matplotlib seaborn scipy xgboost fpdf Pillow mysql-connector-python
```

---

### 3. API Connection Failed

**Error:** Prediction page shows "Service Unavailable"

**Checklist:**
- [ ] Is `start_api.bat` running?
- [ ] Check the terminal window - any errors?
- [ ] Try: http://localhost:5000/api/health in browser
- [ ] Port 5000 might be used by another program

**Solution:**
1. Close any other programs using port 5000
2. Run `start_api.bat` again
3. Look for "Running on http://0.0.0.0:5000" message

---

### 4. No Training Data

**Error:** `No data available for training!`

**Cause:** Database has no board exam records for Engineering

**Solution:**
1. Add board exam data through the main system:
   - Go to "Testing Anonymous Data" page
   - Add at least 20-30 records
   - Include different years and exam types
   
2. Requirements for each record:
   - Board exam date (not null)
   - Department = 'Engineering'
   - Result (Passed/Failed/Conditional)
   - Exam type (First Timer/Repeater)

---

### 5. Database Connection Error

**Error:** `mysql.connector.errors.DatabaseError`

**Solution:**
1. Check if MySQL/MariaDB is running (via Laragon)
2. Verify database credentials in `advanced_predictor.py`:
   ```python
   db_config = {
       'host': 'localhost',
       'user': 'root',
       'password': '',  # Your MySQL password
       'database': 'db_lspu_usc'  # Your database name
   }
   ```
3. Test connection in MySQL:
   ```sql
   SELECT COUNT(*) FROM anonymous_board_passers WHERE department='Engineering';
   ```

---

### 6. PDF Export Fails

**Error:** PDF doesn't download or shows error

**Causes & Solutions:**

**Issue:** fpdf module error
```batch
pip install fpdf==1.7.2
```

**Issue:** API not responding
- Ensure `start_api.bat` is running
- Check browser console for errors (F12)

**Issue:** No predictions available
- Train the model first using `train.bat`

---

### 7. Graphs Not Showing

**Error:** Graph images don't load or show error icon

**Solutions:**

1. **Retrain the model** - graphs are generated during training:
   ```batch
   train.bat
   ```

2. **Check output folder:**
   - Navigate to: `prediction/output/graphs/`
   - Look for: `model_comparison.png`, `residuals.png`
   
3. **Matplotlib backend issue:**
   Edit `advanced_predictor.py`, ensure line 13-14:
   ```python
   import matplotlib
   matplotlib.use('Agg')  # Use non-GUI backend
   ```

---

### 8. Training Takes Forever

**Symptom:** `train.bat` runs for more than 10 minutes

**Causes:**
- Very large dataset (1000+ records) - this is normal
- Slow computer - XGBoost and Random Forest are computationally intensive
- Insufficient RAM

**Solutions:**
- Be patient - training is a one-time process
- Close other programs to free up RAM
- Consider reducing data to last 3-5 years if you have decades of data

---

### 9. Predictions Seem Inaccurate

**Symptom:** Predicted values don't match expectations

**Causes & Solutions:**

**Too little data:**
- Need at least 30-50 records per exam type
- Need data spanning multiple years
- Add more historical data

**Data quality issues:**
- Check for duplicate records
- Verify dates are correct
- Ensure exam types are consistent

**Model needs retraining:**
- Retrain after adding new data: `train.bat`
- Check R² score - should be > 0.7 for good predictions

**Natural variation:**
- Exam difficulty varies year to year
- External factors affect passing rates
- Use confidence intervals to understand uncertainty

---

### 10. "Virtual environment not found"

**Error when running train.bat or start_api.bat**

**Solution:**
Run setup again:
```batch
cd C:\laragon\www\SYSTEMNAERROR-3\FINALSYSTEMNAERROR\prediction
setup.bat
```

---

### 11. Permission Denied Errors

**Error:** `PermissionError: [WinError 5] Access is denied`

**Solutions:**
1. Run Command Prompt as Administrator
2. Check if antivirus is blocking Python
3. Ensure you have write permissions to the prediction folder

---

### 12. API Returns 404 or 500 Errors

**Error codes in browser console**

**404 - Not Found:**
- Model not trained yet - run `train.bat`
- Check if `models/` folder exists with `.pkl` files

**500 - Internal Server Error:**
- Check the API terminal window for error details
- Database connection issue
- Invalid data format

**Solution:**
Look at the terminal running `start_api.bat` for detailed error messages

---

### 13. Confidence Intervals Too Wide

**Symptom:** Intervals like [45%, 95%] - very uncertain

**Causes:**
- Limited training data
- High variance in historical data
- Natural unpredictability

**What to do:**
- This is honest - system is showing uncertainty
- Add more data to improve confidence
- Use the midpoint prediction with caution
- Plan for both scenarios

---

### 14. Slow API Responses

**Symptom:** Predictions take long to load

**Solutions:**
1. Close and restart `start_api.bat`
2. Reduce bootstrap iterations in `advanced_predictor.py`:
   ```python
   # Line ~340
   n_iterations = 500  # Change from 1000 to 500
   ```
3. Use a faster computer
4. Check network/localhost connection

---

### 15. Model Files Missing

**Error:** `FileNotFoundError: models/best_model.pkl`

**Solution:**
Simply train the model:
```batch
train.bat
```

This creates all necessary model files in `models/` folder.

---

## 🆘 Getting Help

If none of these solutions work:

1. **Check the Python terminal** running `start_api.bat` for detailed errors
2. **Check browser console** (F12 → Console tab) for frontend errors
3. **Verify your setup:**
   - Python installed? → `python --version`
   - Packages installed? → `pip list`
   - Database running? → Open phpMyAdmin
   - Data exists? → Check database tables

4. **Common checks:**
   ```batch
   # Test Python
   python --version
   
   # Test database connection
   php -r "require 'c:/laragon/www/SYSTEMNAERROR-3/FINALSYSTEMNAERROR/db_config.php'; echo 'Connected!';"
   
   # Test API
   # Open in browser: http://localhost:5000/api/health
   ```

---

## 📝 Debug Checklist

Before asking for help, verify:

- [x] Python 3.8+ installed
- [x] Virtual environment created (`venv` folder exists)
- [x] Requirements installed (`pip list` shows packages)
- [x] Database running (Laragon started)
- [x] Data in database (at least 20 records)
- [x] Model trained (`models/` folder has .pkl files)
- [x] API server running (`start_api.bat` active)
- [x] No firewall blocking port 5000
- [x] Using correct URL (localhost, not 127.0.0.1 sometimes matters)

---

## 💡 Pro Tips

1. **Keep API running:** Minimize the terminal, don't close it
2. **Retrain regularly:** After adding significant data
3. **Check graphs:** They tell you if model is working well
4. **Use confidence intervals:** They show prediction certainty
5. **Start simple:** Get basic predictions working before optimizing
6. **Monitor performance:** R² score should be > 0.7 for trust

---

Need more help? Check:
- README.md for detailed documentation
- QUICK_START.txt for basic usage
- Comments in Python code for technical details
