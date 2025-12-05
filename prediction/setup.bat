@echo off
echo ===============================================
echo LSPU Board Exam Prediction System - Advanced
echo ===============================================
echo.

REM Set Python path from Laragon
set PYTHON_PATH=C:\laragon\bin\python\python-3.10\python.exe

REM Check Python
"%PYTHON_PATH%" --version >nul 2>&1
if errorlevel 1 (
    echo ERROR: Python is not installed!
    echo Please install Python 3.8+ from python.org
    pause
    exit /b 1
)

echo [1/3] Creating virtual environment...
"%PYTHON_PATH%" -m venv venv

echo [2/3] Activating environment...
call venv\Scripts\activate.bat

echo [3/3] Installing packages...
pip install --upgrade pip
pip install -r requirements.txt

echo.
echo ===============================================
echo Setup Complete!
echo ===============================================
echo.
echo Next steps:
echo   1. Train models: train.bat
echo   2. Start API: start_api.bat
echo.
pause
