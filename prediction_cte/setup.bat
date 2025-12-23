@echo off
echo ===================================================
echo CTE Board Exam Prediction - Environment Setup
echo ===================================================
echo.

echo Creating Python virtual environment...
python -m venv venv
if errorlevel 1 (
    echo Failed to create virtual environment. Make sure Python is installed.
    pause
    exit /b 1
)

echo Activating virtual environment...
call venv\Scripts\activate

echo Installing required packages...
pip install --upgrade pip
pip install -r requirements.txt

echo.
echo ===================================================
echo Setup complete!
echo.
echo Next steps:
echo 1. Run train.bat to train the prediction models
echo 2. Run start_api.bat to start the prediction API
echo ===================================================
pause
