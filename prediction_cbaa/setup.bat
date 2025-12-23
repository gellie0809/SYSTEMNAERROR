@echo off
echo ========================================
echo CBAA Board Exam Prediction API - Setup
echo ========================================
echo.

set PYTHON_PATH=C:\laragon\bin\python\python-3.10\python.exe

REM Check if Python exists
if not exist "%PYTHON_PATH%" (
    echo ERROR: Python not found at %PYTHON_PATH%
    echo Please verify Python installation
    pause
    exit /b 1
)

echo Creating virtual environment...
"%PYTHON_PATH%" -m venv venv

echo.
echo Activating virtual environment...
call venv\Scripts\activate.bat

echo.
echo Installing required packages...
python -m pip install --upgrade pip
pip install flask flask-cors mysql-connector-python pandas numpy scikit-learn joblib matplotlib seaborn reportlab

echo.
echo ========================================
echo Setup completed successfully!
echo ========================================
echo.
echo Next steps:
echo 1. Run train.bat to train the models
echo 2. Run start_api.bat to start the API server
echo.
pause
