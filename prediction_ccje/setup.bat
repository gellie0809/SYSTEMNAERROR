@echo off
echo ============================================
echo CCJE Board Exam Prediction - Setup
echo ============================================
echo.

REM Check if Python is installed
python --version > nul 2>&1
if errorlevel 1 (
    echo ERROR: Python is not installed or not in PATH
    echo Please install Python 3.8+ from https://www.python.org/downloads/
    pause
    exit /b 1
)

echo Python found. Installing required packages...
echo.

REM Install required packages
pip install flask flask-cors numpy pandas scikit-learn matplotlib seaborn mysql-connector-python joblib reportlab

if errorlevel 1 (
    echo.
    echo WARNING: Some packages may have failed to install.
    echo Please check the error messages above.
) else (
    echo.
    echo ============================================
    echo Setup completed successfully!
    echo ============================================
)

echo.
echo Creating directories...
if not exist "models" mkdir models
if not exist "graphs" mkdir graphs
if not exist "reports" mkdir reports

echo.
echo Next steps:
echo 1. Run train.bat to train the models
echo 2. Run start_api.bat to start the prediction API
echo.
pause
