@echo off
echo ============================================
echo CCJE Board Exam Prediction - Model Training
echo ============================================
echo.

cd /d "%~dp0"

echo Training CCJE prediction models...
echo This may take a few minutes depending on the amount of data.
echo.

python -c "from advanced_predictor_ccje import CCJEBoardExamPredictor; p = CCJEBoardExamPredictor(); data = p.fetch_ccje_anonymous_data(); X, y, features = p.prepare_features(data); results = p.train_all_models(X, y, features); p.save_models(); p.generate_visualizations(results, data); print('Training completed successfully!')"

if errorlevel 1 (
    echo.
    echo ERROR: Training failed. Please check the error messages above.
    echo Make sure:
    echo   1. Database connection is configured correctly in db_config
    echo   2. There is sufficient data in the anonymous_board_passers table
    echo   3. All required Python packages are installed (run setup.bat first)
) else (
    echo.
    echo ============================================
    echo Training completed successfully!
    echo ============================================
    echo.
    echo Models saved to: models/
    echo Graphs saved to: graphs/
)

echo.
pause
