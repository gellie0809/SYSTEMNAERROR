@echo off
echo ============================================================
echo    CAS Board Exam Prediction System - Setup
echo ============================================================
echo.
echo Creating Python virtual environment...
C:\laragon\bin\python\python-3.10\python.exe -m venv venv

echo.
echo Activating virtual environment...
call venv\Scripts\activate.bat

echo.
echo Installing required packages...
pip install --upgrade pip
pip install -r requirements.txt

echo.
echo ============================================================
echo    Setup Complete!
echo ============================================================
echo.
echo Next steps:
echo   1. Run train.bat to train the models
echo   2. Run start_api.bat to start the API server
echo.
pause
