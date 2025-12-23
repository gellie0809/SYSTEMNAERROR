@echo off
echo ================================================================
echo COLLEGE OF ARTS AND SCIENCES - ML TRAINING SYSTEM
echo ================================================================
echo.
echo This script will train AI models using CAS anonymous board exam data
echo.

REM Check if virtual environment exists
if not exist "venv" (
    echo ERROR: Virtual environment not found!
    echo Please run setup.bat first to install dependencies
    pause
    exit /b 1
)

echo Activating virtual environment...
call venv\Scripts\activate.bat

echo.
echo ================================================================
echo TRAINING CAS PREDICTION MODELS
echo ================================================================
echo.
echo This will:
echo  - Fetch CAS anonymous board exam data
echo  - Train 7 different ML algorithms
echo  - Compare performance and select best model
echo  - Generate visualizations
echo  - Save models to models/arts_and_sciences/
echo.

python advanced_predictor_cas.py

if %ERRORLEVEL% EQU 0 (
    echo.
    echo ================================================================
    echo SUCCESS! CAS Models Trained Successfully
    echo ================================================================
    echo.
    echo Models saved to: models/arts_and_sciences/
    echo.
    echo Next steps:
    echo  1. Make sure API server is running: start_api.bat
    echo  2. Access predictions at: prediction_cas.php
    echo.
) else (
    echo.
    echo ================================================================
    echo ERROR: Training Failed
    echo ================================================================
    echo.
    echo Possible issues:
    echo  - No CAS anonymous data in database
    echo  - Database connection failed
    echo  - Missing dependencies
    echo.
)

pause
