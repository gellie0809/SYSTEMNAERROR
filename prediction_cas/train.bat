@echo off
echo ============================================================
echo    CAS Board Exam Prediction - Model Training
echo ============================================================
echo.
echo Activating virtual environment...
call venv\Scripts\activate.bat

echo.
echo Starting model training on CAS anonymous data...
echo This will train 7 different ML algorithms.
echo.
venv\Scripts\python.exe advanced_predictor_cas.py

echo.
echo ============================================================
echo    Training Complete!
echo ============================================================
echo.
echo Models saved to: models/
echo Graphs saved to: graphs/
echo.
pause
