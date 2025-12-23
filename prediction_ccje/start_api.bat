@echo off
echo ============================================
echo CCJE Board Exam Prediction API
echo Port: 5001
echo ============================================
echo.

cd /d "%~dp0"

echo Starting CCJE Prediction API server...
echo.
echo API will be available at: http://localhost:5001
echo.
echo Press Ctrl+C to stop the server.
echo.

python prediction_api_ccje.py

if errorlevel 1 (
    echo.
    echo ERROR: Failed to start API server. Please check:
    echo   1. Port 5001 is not in use
    echo   2. All required packages are installed (run setup.bat first)
    echo   3. Models are trained (run train.bat first)
    pause
)
