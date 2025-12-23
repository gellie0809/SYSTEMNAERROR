@echo off
echo ========================================
echo   Starting All Prediction API Servers
echo ========================================
echo.

echo [1/5] Starting Engineering API (Port 5000)...
start "Engineering API - Port 5000" cmd /k "cd /d C:\laragon\www\SYSTEMNAERROR-3\FINALSYSTEMNAERROR\prediction && venv\Scripts\python.exe prediction_api.py"
timeout /t 2 /nobreak >nul

echo [2/5] Starting CCJE API (Port 5001)...
start "CCJE API - Port 5001" cmd /k "cd /d C:\laragon\www\SYSTEMNAERROR-3\FINALSYSTEMNAERROR\prediction_ccje && venv\Scripts\python.exe prediction_api_ccje.py"
timeout /t 2 /nobreak >nul

echo [3/5] Starting CBAA API (Port 5002)...
start "CBAA API - Port 5002" cmd /k "cd /d C:\laragon\www\SYSTEMNAERROR-3\FINALSYSTEMNAERROR\prediction_cbaa && venv\Scripts\python.exe prediction_api_cbaa.py"
timeout /t 2 /nobreak >nul

echo [4/5] Starting CAS API (Port 5003)...
start "CAS API - Port 5003" cmd /k "cd /d C:\laragon\www\SYSTEMNAERROR-3\FINALSYSTEMNAERROR\prediction_cas && venv\Scripts\python.exe prediction_api_cas.py"
timeout /t 2 /nobreak >nul

echo [5/5] Starting CTE API (Port 5004)...
start "CTE API - Port 5004" cmd /k "cd /d C:\laragon\www\SYSTEMNAERROR-3\FINALSYSTEMNAERROR\prediction_cte && venv\Scripts\python.exe prediction_api_cte.py"
timeout /t 2 /nobreak >nul

echo.
echo ========================================
echo   All Prediction APIs Started!
echo ========================================
echo.
echo   Engineering: http://localhost:5000
echo   CCJE:        http://localhost:5001
echo   CBAA:        http://localhost:5002
echo   CAS:         http://localhost:5003
echo   CTE:         http://localhost:5004
echo.
echo You should see 5 terminal windows open.
echo Keep them running to use AI predictions.
echo.
echo To stop all APIs: Close all terminal windows
echo.
pause
