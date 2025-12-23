@echo off
echo ========================================
echo CBAA Board Exam Prediction - Training
echo ========================================
echo.

REM Check if virtual environment exists
if not exist "venv\Scripts\activate.bat" (
    echo ERROR: Virtual environment not found
    echo Please run setup.bat first
    pause
    exit /b 1
)

echo Activating virtual environment...
call venv\Scripts\activate.bat

echo.
echo Training all 7 machine learning models...
echo This may take a few minutes...
echo.

python advanced_predictor_cbaa.py

echo.
echo ========================================
echo Training completed!
echo ========================================
echo.
echo You can now:
echo 1. Run start_api.bat to start the API server
echo 2. Access the prediction system through the web interface
echo.
pause
