@echo off
echo ===============================================
echo Starting Prediction API Server
echo ===============================================
echo.
echo API will run on: http://localhost:5000
echo Press Ctrl+C to stop
echo.

REM Set Python path from Laragon
set PYTHON_PATH=C:\laragon\bin\python\python-3.10\python.exe

if exist venv\Scripts\activate.bat (
    call venv\Scripts\activate.bat
    python prediction_api.py
) else (
    echo Virtual environment not found. Running setup...
    echo.
    "%PYTHON_PATH%" -m venv venv
    call venv\Scripts\activate.bat
    pip install --upgrade pip
    pip install -r requirements.txt
    echo.
    echo Setup complete! Now starting API...
    echo.
    python prediction_api.py
)
