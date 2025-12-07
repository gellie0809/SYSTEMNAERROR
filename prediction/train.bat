@echo off
echo Training Advanced Prediction Models...
echo This will compare 7 different algorithms!
echo.

REM Set Python path from Laragon
set PYTHON_PATH=C:\laragon\bin\python\python-3.10\python.exe

if exist venv\Scripts\activate.bat (
    call venv\Scripts\activate.bat
    python advanced_predictor.py
) else (
    echo Virtual environment not found. Running setup...
    echo.
    "%PYTHON_PATH%" -m venv venv
    call venv\Scripts\activate.bat
    pip install --upgrade pip
    pip install -r requirements.txt
    echo.
    echo Setup complete! Now training...
    echo.
    python advanced_predictor.py
)
pause
