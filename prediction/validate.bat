@echo off
echo ===============================================
echo Historical Prediction Accuracy Validation
echo ===============================================
echo.
echo This will test how accurate predictions would have been
echo for historical years by comparing predicted vs actual results.
echo.
pause

call venv\Scripts\activate.bat
python validate_accuracy.py
pause
