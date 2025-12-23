@echo off
echo ===================================================
echo CTE Board Exam Prediction - API Server
echo ===================================================
echo.
echo Starting API server on http://localhost:5003
echo (Port 5003 to avoid conflicts with other departments)
echo.
echo Press Ctrl+C to stop the server.
echo.

call venv\Scripts\activate
python prediction_api_cte.py
