@echo off
echo ===================================================
echo CTE Board Exam Prediction - Model Training
echo ===================================================
echo.
echo Training all 7 machine learning algorithms...
echo This will use data from anonymous_board_passers table
echo for the Teacher Education department.
echo.

call venv\Scripts\activate
python -c "from advanced_predictor_cte import CTEBoardExamPredictor; p = CTEBoardExamPredictor(); p.train_all_models()"

echo.
echo ===================================================
echo Training complete!
echo.
echo Models saved in: models/
echo Graphs saved in: graphs/
echo.
echo Run start_api.bat to start the prediction API.
echo ===================================================
pause
