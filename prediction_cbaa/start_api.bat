@echo off
echo ========================================
echo CBAA Board Exam Prediction API
echo Starting on Port 5002
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
echo Starting Flask API server...
echo API will be available at: http://localhost:5002
echo Press Ctrl+C to stop the server
echo.

python prediction_api_cbaa.py

pause
