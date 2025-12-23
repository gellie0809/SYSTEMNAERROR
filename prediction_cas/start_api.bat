@echo off
echo ============================================================
echo    CAS Board Exam Prediction API Server
echo ============================================================
echo.
echo Activating virtual environment...
call venv\Scripts\activate.bat

echo.
echo Starting API server on http://localhost:5003
echo Department: Arts and Sciences
echo.
echo Press Ctrl+C to stop the server.
echo.
venv\Scripts\python.exe prediction_api_cas.py
