<?php
session_start();

// Only allow College of Business Administration and Accountancy admin
if (!isset($_SESSION["users"]) || $_SESSION["users"] !== 'cbaa_admin@lspu.edu.ph') {
    header("Location: index.php");
    exit();
}

function callPredictionAPI($endpoint, $method = 'GET', $data = null) {
    // Use port 5002 for CBAA to avoid conflicts with Engineering (port 5000) and CCJE (port 5001)
    $url = "http://localhost:5002/api/" . $endpoint;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        }
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode == 200) {
        return json_decode($response, true);
    }
    return null;
}

$predictionsData = callPredictionAPI('predict');
$modelInfo = callPredictionAPI('model/info');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Board Exam Predictions - CBAA Anonymous Data</title>
    <link rel="stylesheet" href="css/sidebar.css"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
        background: linear-gradient(135deg, #FFFBEA 0%, #FEF3C7 50%, #FDE68A 100%);
        margin: 0;
        font-family: 'Inter', sans-serif;
        min-height: 100vh;
        position: relative;
    }

    body::before {
        content: '';
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background:
            radial-gradient(circle at 20% 20%, rgba(245, 158, 11, 0.08) 0%, transparent 50%),
            radial-gradient(circle at 80% 60%, rgba(217, 119, 6, 0.1) 0%, transparent 50%),
            radial-gradient(circle at 40% 80%, rgba(251, 191, 36, 0.06) 0%, transparent 50%);
        pointer-events: none;
        z-index: 0;
    }

    /* Sidebar styling moved to css/sidebar.css (shared) */

    .topbar {
        position: fixed;
        top: 0;
        left: 260px;
        right: 0;
        background: linear-gradient(135deg, #D97706 0%, #F59E0B 100%);
        height: 70px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 40px;
        box-shadow: 0 4px 20px rgba(217, 119, 6, 0.3);
        z-index: 50;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .dashboard-title {
        font-size: 1.4rem;
        color: #FFFFFF;
        font-weight: 700;
        letter-spacing: 1px;
        margin: 0;
    }

    .logout-btn {
        background: rgba(255, 255, 255, 0.2);
        color: #FFFFFF;
        border: 2px solid rgba(255, 255, 255, 0.4);
        border-radius: 12px;
        padding: 12px 24px;
        font-size: 0.95rem;
        font-weight: 600;
        font-family: 'Inter', sans-serif;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 8px;
        backdrop-filter: blur(10px);
    }

    .logout-btn:hover {
        background: rgba(255, 255, 255, 0.3);
        border-color: rgba(255, 255, 255, 0.6);
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
    }
        .main {
            margin-left: 260px;
            margin-top: 70px;
            padding: 32px;
            min-height: calc(100vh - 70px);
        }
        
        .prediction-card {
            background: white;
            border-radius: 20px;
            padding: 32px;
            margin-bottom: 24px;
            box-shadow: 0 10px 40px rgba(211, 47, 47, 0.12);
            border: 2px solid rgba(211, 47, 47, 0.15);
        }
        
        .prediction-card h2 {
            color: #763A12;
            font-size: 1.8rem;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .prediction-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 20px;
            margin-top: 24px;
        }
        
        .prediction-item {
            background: linear-gradient(135deg, #FDF3E7 0%, #EFBF38 100%);
            padding: 24px;
            border-radius: 16px;
            border-left: 4px solid #AA4C0A;
            transition: all 0.3s;
        }
        
        .prediction-item:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 30px rgba(211, 47, 47, 0.3);
        }
        
        .prediction-item h3 {
            color: #763A12;
            font-size: 1.1rem;
            margin-bottom: 16px;
            font-weight: 700;
        }
        
        .rate-comparison {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 12px 0;
        }
        
        .rate-box {
            text-align: center;
            flex: 1;
        }
        
        .rate-label {
            font-size: 0.85rem;
            color: #64748b;
            margin-bottom: 4px;
        }
        
        .rate-value {
            font-size: 2rem;
            font-weight: 700;
            color: #763A12;
        }
        
        .rate-change {
            font-size: 1.1rem;
            font-weight: 600;
            padding: 8px 16px;
            border-radius: 8px;
            margin-top: 12px;
            text-align: center;
        }
        
        .rate-change.positive {
            background: rgba(211, 47, 47, 0.3);
            color: #763A12;
        }
        
        .rate-change.negative {
            background: rgba(239, 68, 68, 0.2);
            color: #dc2626;
        }
        
        .confidence-interval {
            margin-top: 16px;
            padding: 12px;
            background: rgba(255, 255, 255, 0.7);
            border-radius: 10px;
        }
        
        .confidence-interval h4 {
            font-size: 0.9rem;
            color: #64748b;
            margin-bottom: 8px;
        }
        
        .ci-bar {
            height: 30px;
            background: linear-gradient(90deg, #EFBF38, #AA4C0A);
            border-radius: 8px;
            position: relative;
            margin: 10px 0;
        }
        
        .ci-marker {
            position: absolute;
            top: -5px;
            width: 3px;
            height: 40px;
            background: #763A12;
            box-shadow: 0 0 10px rgba(128, 0, 32, 0.5);
        }
        
        .ci-labels {
            display: flex;
            justify-content: space-between;
            font-size: 0.85rem;
            color: #64748b;
            margin-top: 4px;
        }
        
        .model-info-card {
            background: linear-gradient(135deg, #AA4C0A 0%, #763A12 100%);
            color: white;
            padding: 24px;
            border-radius: 16px;
            margin-top: 24px;
        }
        
        .model-info-card h3 {
            margin: 0 0 16px 0;
            font-size: 1.3rem;
        }
        
        .model-metrics {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 16px;
            margin-top: 16px;
        }
        
        .metric {
            text-align: center;
            background: rgba(255, 255, 255, 0.15);
            padding: 16px;
            border-radius: 10px;
        }
        
        .metric-label {
            font-size: 0.85rem;
            opacity: 0.9;
            margin-bottom: 8px;
        }
        
        .metric-value {
            font-size: 1.8rem;
            font-weight: 700;
        }
        
        .algorithm-comparison {
            background: white;
            padding: 24px;
            border-radius: 16px;
            margin-top: 16px;
        }
        
        .algorithm-list {
            display: grid;
            gap: 12px;
            margin-top: 16px;
        }
        
        .algorithm-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px;
            background: #f8fafc;
            border-radius: 8px;
            border-left: 3px solid #cbd5e0;
        }
        
        .algorithm-item.best {
            background: linear-gradient(90deg, rgba(211, 47, 47, 0.1), rgba(250, 214, 165, 0.1));
            border-left-color: #AA4C0A;
        }
        
        .algorithm-name {
            font-weight: 600;
            color: #763A12;
        }
        
        .algorithm-score {
            font-size: 0.9rem;
            color: #64748b;
        }
        
        .btn {
            background: linear-gradient(135deg, #AA4C0A 0%, #763A12 100%);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.95rem;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(211, 47, 47, 0.4);
        }
        
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #64748b 0%, #475569 100%);
        }
        
        .btn-group {
            display: flex;
            gap: 12px;
            margin-top: 16px;
            flex-wrap: wrap;
        }
        
        .error-message {
            background: #fee2e2;
            color: #991b1b;
            padding: 20px;
            border-radius: 12px;
            border-left: 4px solid #dc2626;
            margin-bottom: 20px;
        }
        
        .error-message h3 {
            margin-bottom: 8px;
        }
        
        .graph-container {
            background: white;
            padding: 24px;
            border-radius: 16px;
            margin-top: 24px;
        }
        
        .graph-container img {
            width: 100%;
            height: auto;
            border-radius: 12px;
        }
        
        .loading {
            text-align: center;
            padding: 40px;
            color: #64748b;
        }
        
        .loading i {
            font-size: 3rem;
            color: #AA4C0A;
            margin-bottom: 16px;
        }
        
        @media (max-width: 768px) {
            .topbar {
                left: 0;
                padding: 0 20px;
            }
            
            .main {
                margin-left: 0;
                padding: 20px;
            }
            
            .prediction-grid {
                grid-template-columns: 1fr;
            }
            
            .model-metrics {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/includes/cbaa_nav.php'; ?>
    
    <div class="topbar">
        <div class="dashboard-title">
            <i class="fas fa-brain"></i>
            AI Board Exam Predictions - CBAA Anonymous Data
        </div>
        <a href="anonymous_dashboard_cbaa.php" class="btn" style="background: rgba(255,255,255,0.2);">
            <i class="fas fa-arrow-left"></i> Back to Anonymous Dashboard
        </a>
    </div>
    
    <div class="main">
        <?php if ($predictionsData && $predictionsData['success']): ?>
            <!-- Main Predictions -->
            <div class="prediction-card">
                <h2>
                    <i class="fas fa-chart-line"></i>
                    Passing Rate Predictions for <?php 
                        $year = date('Y') + 1;
                        if (isset($predictionsData['data'][0]['predicted_year'])) {
                            $year = $predictionsData['data'][0]['predicted_year'];
                        } elseif (isset($predictionsData['data']['predictions'][0]['prediction_year'])) {
                            $year = $predictionsData['data']['predictions'][0]['prediction_year'];
                        }
                        echo $year;
                    ?>
                </h2>
                
                <div class="btn-group">
                    <button onclick="exportToPDF()" class="btn">
                        <i class="fas fa-file-pdf"></i> Export to PDF
                    </button>
                    <button onclick="downloadTrainingReport()" class="btn" style="background: linear-gradient(135deg, #763A12 0%, #AA4C0A 100%);">
                        <i class="fas fa-book"></i> Download Training Report
                    </button>
                    <button onclick="retrainModel()" class="btn btn-secondary">
                        <i class="fas fa-sync-alt"></i> Retrain Models
                    </button>
                </div>
                
                <div class="prediction-grid">
                    <?php if (isset($predictionsData['data']['predictions']) && is_array($predictionsData['data']['predictions'])): ?>
                    <?php foreach ($predictionsData['data']['predictions'] as $pred): ?>
                        <?php 
                            $change = isset($pred['predicted_passing_rate'], $pred['historical_avg']) ? $pred['predicted_passing_rate'] - $pred['historical_avg'] : 0;
                            $changeClass = $change >= 0 ? 'positive' : 'negative';
                            $ci = isset($pred['confidence_interval_95']) ? $pred['confidence_interval_95'] : ['lower' => 0, 'upper' => 100];
                            $ci_range = isset($ci['upper'], $ci['lower']) ? $ci['upper'] - $ci['lower'] : 0;
                            $marker_pos = $ci_range > 0 ? (($pred['predicted_passing_rate'] - $ci['lower']) / $ci_range * 100) : 50;
                        ?>
                        <div class="prediction-item">
                            <h3><?php echo isset($pred['board_exam_type']) ? htmlspecialchars($pred['board_exam_type']) : 'Unknown'; ?></h3>
                            
                            <div class="rate-comparison">
                                <div class="rate-box">
                                    <div class="rate-label"><?php echo isset($pred['current_year']) ? $pred['current_year'] : date('Y'); ?></div>
                                    <div class="rate-value"><?php echo isset($pred['historical_avg']) ? $pred['historical_avg'] : 0; ?>%</div>
                                </div>
                                
                                <div>
                                    <i class="fas fa-arrow-right" style="color: #AA4C0A; font-size: 1.5rem;"></i>
                                </div>
                                
                                <div class="rate-box">
                                    <div class="rate-label"><?php echo isset($pred['prediction_year']) ? $pred['prediction_year'] : date('Y') + 1; ?></div>
                                    <div class="rate-value"><?php echo isset($pred['predicted_passing_rate']) ? $pred['predicted_passing_rate'] : 0; ?>%</div>
                                </div>
                            </div>
                            
                            <div class="rate-change <?php echo $changeClass; ?>">
                                <?php echo $change >= 0 ? '+' : ''; ?><?php echo number_format($change, 2); ?>%
                                <?php if ($change >= 0): ?>
                                    <i class="fas fa-arrow-up"></i>
                                <?php else: ?>
                                    <i class="fas fa-arrow-down"></i>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Confidence Interval -->
                            <div class="confidence-interval">
                                <h4>95% Confidence Interval</h4>
                                <div class="ci-bar">
                                    <div class="ci-marker" style="left: <?php echo $marker_pos; ?>%;"></div>
                                </div>
                                <div class="ci-labels">
                                    <span><?php echo isset($ci['lower']) ? $ci['lower'] : 0; ?>%</span>
                                    <span><strong><?php echo isset($pred['predicted_passing_rate']) ? $pred['predicted_passing_rate'] : 0; ?>%</strong></span>
                                    <span><?php echo isset($ci['upper']) ? $ci['upper'] : 100; ?>%</span>
                                </div>
                                <p style="font-size: 0.8rem; color: #64748b; margin-top: 8px; text-align: center;">
                                    ±<?php echo isset($pred['std_deviation']) ? $pred['std_deviation'] : 0; ?>% standard deviation
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Model Information -->
            <?php if ($modelInfo && $modelInfo['success']): ?>
                <div class="model-info-card">
                    <h3><i class="fas fa-robot"></i> AI Model Performance</h3>
                    <p style="opacity: 0.95;">
                        Algorithm: <strong><?php echo isset($modelInfo['data']['best_model']) ? $modelInfo['data']['best_model'] : 'N/A'; ?></strong><br>
                        Trained: <?php echo isset($modelInfo['data']['training_date']) ? date('F d, Y', strtotime($modelInfo['data']['training_date'])) : 'N/A'; ?><br>
                        Training Data: <?php echo isset($modelInfo['data']['training_records']) ? $modelInfo['data']['training_records'] : 0; ?> anonymous records
                    </p>
                    
                    <div class="model-metrics">
                        <div class="metric">
                            <div class="metric-label">Accuracy (R²)</div>
                            <div class="metric-value"><?php echo isset($modelInfo['data']['best_model_metrics']['test_r2']) ? number_format($modelInfo['data']['best_model_metrics']['test_r2'], 4) : 'N/A'; ?></div>
                        </div>
                        <div class="metric">
                            <div class="metric-label">Avg Error</div>
                            <div class="metric-value"><?php echo isset($modelInfo['data']['best_model_metrics']['test_mae']) ? number_format($modelInfo['data']['best_model_metrics']['test_mae'], 2) . '%' : 'N/A'; ?></div>
                        </div>
                        <div class="metric">
                            <div class="metric-label">CV Score</div>
                            <div class="metric-value"><?php echo isset($modelInfo['data']['best_model_metrics']['cv_mean']) && $modelInfo['data']['best_model_metrics']['cv_mean'] !== null ? number_format($modelInfo['data']['best_model_metrics']['cv_mean'], 3) : 'N/A'; ?></div>
                        </div>
                    </div>
                    
                    <!-- Algorithm Comparison -->
                    <div class="algorithm-comparison">
                        <h4 style="color: #763A12; margin-bottom: 12px;">
                            <i class="fas fa-layer-group"></i> Algorithm Comparison
                        </h4>
                        <div class="algorithm-list">
                            <?php 
                            $allModels = $modelInfo['data']['all_models'];
                            usort($allModels, function($a, $b) {
                                return $b['test_r2'] <=> $a['test_r2'];
                            });
                            
                            foreach ($allModels as $idx => $model): 
                                $isBest = $idx === 0;
                            ?>
                                <div class="algorithm-item <?php echo $isBest ? 'best' : ''; ?>">
                                    <span class="algorithm-name">
                                        <?php if ($isBest): ?>
                                            <i class="fas fa-trophy" style="color: #f59e0b; margin-right: 8px;"></i>
                                        <?php endif; ?>
                                        <?php echo $model['model']; ?>
                                    </span>
                                    <span class="algorithm-score">
                                        R²: <?php echo number_format($model['test_r2'], 4); ?> | 
                                        MAE: <?php echo number_format($model['test_mae'], 2); ?>%
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Backtesting Validation -->
                <div class="model-performance">
                    <h3 style="color: #763A12; margin-bottom: 15px;">
                        <i class="fas fa-vial"></i> Model Validation (Backtesting)
                    </h3>
                    <p style="margin-bottom: 15px; color: #666; line-height: 1.6;">
                        To verify prediction accuracy, we trained the model on 2021-2022 data and predicted 2023. 
                        Here's how accurate our prediction was compared to the actual 2023 result:
                    </p>
                    
                    <div id="backtestResults" style="margin-top: 20px;">
                        <div style="text-align: center; padding: 20px;">
                            <i class="fas fa-spinner fa-spin" style="color: #763A12; font-size: 24px;"></i>
                            <p style="margin-top: 10px; color: #666;">Loading validation results...</p>
                        </div>
                    </div>
                </div>
                
                <!-- Visualization Graphs -->
                <div class="prediction-card">
                    <h2>
                        <i class="fas fa-chart-bar"></i>
                        Performance Visualization
                    </h2>
                    
                    <div class="graph-container">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
                            <h3 style="color: #763A12; margin: 0;">Model R² Score Comparison</h3>
                            <button onclick="showChartInfo('r2comparison')" style="background: linear-gradient(135deg, #D97706 0%, #B45309 100%); color: white; border: none; padding: 8px 16px; border-radius: 8px; cursor: pointer; font-size: 0.85rem; font-weight: 600; display: flex; align-items: center; gap: 6px; transition: all 0.3s;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(217,119,6,0.3)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                                <i class="fas fa-info-circle"></i> What is this?
                            </button>
                        </div>
                        <img src="http://localhost:5002/api/graphs/model_comparison" alt="Model Comparison" style="max-width: 100%; height: auto;"
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                        <div class="loading" style="display: none;">
                            <i class="fas fa-chart-line"></i>
                            <p>Graph not available. Please retrain the models.</p>
                        </div>
                    </div>
                    
                    <div class="graph-container">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
                            <h3 style="color: #763A12; margin: 0;">Performance Metrics</h3>
                            <button onclick="showChartInfo('performance')" style="background: linear-gradient(135deg, #D97706 0%, #B45309 100%); color: white; border: none; padding: 8px 16px; border-radius: 8px; cursor: pointer; font-size: 0.85rem; font-weight: 600; display: flex; align-items: center; gap: 6px; transition: all 0.3s;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(217,119,6,0.3)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                                <i class="fas fa-info-circle"></i> What is this?
                            </button>
                        </div>
                        <img src="http://localhost:5002/api/graphs/accuracy_comparison" alt="Accuracy Comparison" style="max-width: 100%; height: auto;"
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                        <div class="loading" style="display: none;">
                            <i class="fas fa-chart-bar"></i>
                            <p>Graph not available. Please retrain the models.</p>
                        </div>
                    </div>
                    
                    <div class="graph-container">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
                            <h3 style="color: #763A12; margin: 0;">Actual vs Predicted</h3>
                            <button onclick="showChartInfo('actualvspredicted')" style="background: linear-gradient(135deg, #D97706 0%, #B45309 100%); color: white; border: none; padding: 8px 16px; border-radius: 8px; cursor: pointer; font-size: 0.85rem; font-weight: 600; display: flex; align-items: center; gap: 6px; transition: all 0.3s;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(217,119,6,0.3)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                                <i class="fas fa-info-circle"></i> What is this?
                            </button>
                        </div>
                        <img src="http://localhost:5002/api/graphs/predictions_vs_actual" alt="Predictions vs Actual" style="max-width: 100%; height: auto;"
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                        <div class="loading" style="display: none;">
                            <i class="fas fa-chart-scatter"></i>
                            <p>Graph not available. Please retrain the models.</p>
                        </div>
                    </div>
                    
                    <div class="graph-container">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
                            <h3 style="color: #763A12; margin: 0;">Residual Analysis</h3>
                            <button onclick="showChartInfo('residuals')" style="background: linear-gradient(135deg, #D97706 0%, #B45309 100%); color: white; border: none; padding: 8px 16px; border-radius: 8px; cursor: pointer; font-size: 0.85rem; font-weight: 600; display: flex; align-items: center; gap: 6px; transition: all 0.3s;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(217,119,6,0.3)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                                <i class="fas fa-info-circle"></i> What is this?
                            </button>
                        </div>
                        <img src="http://localhost:5002/api/graphs/residual_analysis" alt="Residual Analysis" style="max-width: 100%; height: auto;"
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                        <div class="loading" style="display: none;">
                            <i class="fas fa-chart-area"></i>
                            <p>Graph not available. Please retrain the models.</p>
                        </div>
                    </div>
                    
                    <div class="graph-container">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
                            <h3 style="color: #763A12; margin: 0;">Historical Trend</h3>
                            <button onclick="showChartInfo('historical')" style="background: linear-gradient(135deg, #D97706 0%, #B45309 100%); color: white; border: none; padding: 8px 16px; border-radius: 8px; cursor: pointer; font-size: 0.85rem; font-weight: 600; display: flex; align-items: center; gap: 6px; transition: all 0.3s;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(217,119,6,0.3)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                                <i class="fas fa-info-circle"></i> What is this?
                            </button>
                        </div>
                        <img src="http://localhost:5002/api/graphs/historical_trends" alt="Historical Trends" style="max-width: 100%; height: auto;"
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                        <div class="loading" style="display: none;">
                            <i class="fas fa-chart-line"></i>
                            <p>Graph not available. Please retrain the models.</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
        <?php else: ?>
            <div class="error-message">
                <h3>⚠️ Prediction Service Unavailable</h3>
                <p style="margin: 8px 0;">The CBAA prediction service is not running or the model needs to be trained.</p>
                <p style="margin: 8px 0;"><strong>Setup Instructions:</strong></p>
                <ol style="margin-left: 20px; margin-top: 8px;">
                    <li>Open a terminal/command prompt</li>
                    <li>Navigate to: <code>C:\laragon\www\SYSTEMNAERROR-3\FINALSYSTEMNAERROR\prediction_cbaa\</code></li>
                    <li>Run: <code>setup.bat</code> (first time only)</li>
                    <li>Run: <code>train.bat</code> to train the models on CBAA anonymous data</li>
                    <li>Run: <code>start_api.bat</code> to start the API server on port 5002</li>
                    <li>Refresh this page</li>
                </ol>
                <p style="margin: 8px 0; padding: 12px; background: #fef3c7; border-left: 4px solid #f59e0b; border-radius: 8px;">
                    <strong>Note:</strong> This prediction system uses anonymous board passer data from CBAA and runs on a separate port (5002) to avoid conflicts with the Engineering (port 5000) and CCJE (port 5001) prediction systems.
                </p>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        // Load backtest results on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadBacktestResults();
        });
        
        async function loadBacktestResults() {
            try {
                const response = await fetch('http://localhost:5002/api/backtest?test_year=2023&train_until=2022');
                const data = await response.json();
                
                if (data.success && data.data && data.data.predictions) {
                    const results = data.data;
                    
                    let html = '<div style="background: linear-gradient(135deg, #FFF5F5 0%, #FFE8E8 100%); border: 3px solid #AA4C0A; border-radius: 16px; padding: 25px; margin-bottom: 20px; box-shadow: 0 4px 15px rgba(211, 47, 47, 0.15);">';
                    html += `<div style="text-align: center; margin-bottom: 20px;">`;
                    html += `<div style="display: inline-block; background: linear-gradient(135deg, #763A12 0%, #AA4C0A 100%); color: white; padding: 15px 30px; border-radius: 50px; margin-bottom: 15px; box-shadow: 0 4px 12px rgba(128, 0, 32, 0.3);">`;
                    html += `<i class="fas fa-check-circle" style="margin-right: 10px; font-size: 24px; vertical-align: middle;"></i>`;
                    html += `<span style="font-size: 52px; font-weight: bold; vertical-align: middle;">${results.accuracy.toFixed(1)}%</span>`;
                    html += `</div>`;
                    html += `<div style="color: #763A12; font-size: 16px; font-weight: 600; letter-spacing: 0.5px;">Average Prediction Accuracy</div>`;
                    html += `</div>`;
                    
                    html += `<div style="background: white; border-radius: 12px; padding: 20px; margin-top: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); border: 2px solid #FFE8E8;">`;
                    html += `<table style="width: 100%; border-collapse: collapse;">`;
                    html += `<tr style="border-bottom: 3px solid #AA4C0A; background: linear-gradient(to right, #763A12, #AA4C0A);">`;
                    html += `<th style="padding: 15px; text-align: left; font-weight: 700; color: white; font-size: 15px; letter-spacing: 0.5px;">Exam Type</th>`;
                    html += `<th style="padding: 15px; text-align: center; font-weight: 700; color: white; font-size: 15px; letter-spacing: 0.5px;">Actual 2023</th>`;
                    html += `<th style="padding: 15px; text-align: center; font-weight: 700; color: white; font-size: 15px; letter-spacing: 0.5px;">Predicted</th>`;
                    html += `<th style="padding: 15px; text-align: center; font-weight: 700; color: white; font-size: 15px; letter-spacing: 0.5px;">Error</th>`;
                    html += `</tr>`;
                    
                    results.predictions.forEach(pred => {
                        const accuracy = 100 - pred.error;
                        html += `<tr style="border-bottom: 1px solid #FFE8E8; transition: background 0.2s;" onmouseover="this.style.background='#FFF5F5'" onmouseout="this.style.background='white'">`;
                        html += `<td style="padding: 14px; color: #555; font-size: 13px;">`;
                        html += `<i class="fas fa-chart-line" style="color: #AA4C0A; margin-right: 8px;"></i>`;
                        html += `${pred.exam_type}</td>`;
                        html += `<td style="padding: 14px; text-align: center; font-weight: 700; font-size: 16px; color: #763A12;">${pred.actual.toFixed(2)}%</td>`;
                        html += `<td style="padding: 14px; text-align: center; font-weight: 700; font-size: 16px; color: #1565C0;">${pred.predicted.toFixed(2)}%</td>`;
                        html += `<td style="padding: 14px; text-align: center; font-weight: 600; font-size: 14px; color: #E08600;">${pred.error.toFixed(2)}%</td>`;
                        html += `</tr>`;
                    });
                    
                    // Summary row
                    html += `<tr style="background: linear-gradient(135deg, #FFF9E6 0%, #FFE8CC 100%); border-top: 2px solid #AA4C0A;">`;
                    html += `<td style="padding: 14px; color: #763A12; font-weight: 700; font-size: 14px;">`;
                    html += `<i class="fas fa-calculator" style="margin-right: 8px;"></i>Average Results</td>`;
                    html += `<td style="padding: 14px; text-align: center; font-weight: 700; font-size: 14px; color: #763A12;">-</td>`;
                    html += `<td style="padding: 14px; text-align: center; font-weight: 700; font-size: 14px; color: #1565C0;">-</td>`;
                    html += `<td style="padding: 14px; text-align: center; font-weight: 800; font-size: 16px; color: #2E7D32;">${results.mae.toFixed(2)}% MAE</td>`;
                    html += `</tr>`;
                    
                    html += `</table>`;
                    html += `</div>`;
                    
                    html += `<div style="margin-top: 20px; padding: 18px; background: linear-gradient(135deg, #FFF9E6 0%, #FFE8CC 100%); border-radius: 12px; border-left: 5px solid #EFBF38; box-shadow: 0 2px 8px rgba(250, 214, 165, 0.3);">`;
                    html += `<div style="display: flex; align-items: start; gap: 12px;">`;
                    html += `<div style="flex-shrink: 0; width: 40px; height: 40px; background: linear-gradient(135deg, #763A12, #AA4C0A); border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 6px rgba(128, 0, 32, 0.3);">`;
                    html += `<i class="fas fa-lightbulb" style="color: #EFBF38; font-size: 18px;"></i>`;
                    html += `</div>`;
                    html += `<div style="flex: 1;">`;
                    html += `<p style="margin: 0; color: #5D4037; font-size: 14px; line-height: 1.7; font-weight: 500;">`;
                    html += `<strong style="color: #763A12; font-size: 15px;">What this means:</strong><br>`;
                    html += `Our AI model achieved <strong style="color: #763A12;">${results.accuracy.toFixed(1)}%</strong> accuracy when predicting ${results.test_year} results using only ${results.trained_until} and earlier training data. `;
                    html += `The average prediction error was only <strong style="color: #E08600;">${results.mae.toFixed(2)}%</strong>. `;
                    html += `This validates that our machine learning model can reliably predict future CBAA board exam passing rates.`;
                    html += `</p>`;
                    html += `</div>`;
                    html += `</div>`;
                    html += `</div>`;
                    
                    html += `</div>`;
                    
                    document.getElementById('backtestResults').innerHTML = html;
                } else {
                    document.getElementById('backtestResults').innerHTML = 
                        `<div style="padding: 25px; text-align: center; color: #999; background: #FFF5F5; border-radius: 12px; border: 2px dashed #AA4C0A;">
                            <i class="fas fa-database" style="font-size: 40px; color: #AA4C0A; margin-bottom: 10px;"></i>
                            <p style="margin: 0; font-size: 14px;">Validation data not available</p>
                        </div>`;
                }
            } catch (error) {
                document.getElementById('backtestResults').innerHTML = 
                    `<div style="padding: 25px; text-align: center; color: #999; background: #FFF5F5; border-radius: 12px; border: 2px dashed #AA4C0A;">
                        <i class="fas fa-exclamation-circle" style="font-size: 40px; color: #AA4C0A; margin-bottom: 10px;"></i>
                        <p style="margin: 0; font-size: 14px;">Unable to load validation results</p>
                    </div>`;
            }
        }
        
        async function exportToPDF() {
            const btn = event.target;
            const originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating PDF...';
            
            try {
                const predictions = <?php echo json_encode($predictionsData['data'] ?? []); ?>;
                const modelInfo = <?php echo json_encode($modelInfo['data'] ?? []); ?>;
                
                const response = await fetch('http://localhost:5002/api/export/pdf', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        predictions: predictions,
                        model_info: modelInfo
                    })
                });
                
                if (response.ok) {
                    const blob = await response.blob();
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    const year = predictions[0]?.predicted_year || predictions[0]?.prediction_year || 2026;
                    a.download = `LSPU_CBAA_Predictions_${year}_${new Date().getTime()}.pdf`;
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                    document.body.removeChild(a);
                    
                    alert('✅ PDF report downloaded successfully!');
                } else {
                    const error = await response.json();
                    alert('Error: ' + (error.error || 'Failed to generate PDF'));
                }
            } catch (error) {
                alert('Error: ' + error.message);
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        }
        
        async function downloadTrainingReport() {
            const btn = event.target;
            const originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating Report...';
            
            try {
                const response = await fetch('http://localhost:5002/api/cbaa/export/training-report', {
                    method: 'GET'
                });
                
                if (response.ok) {
                    const blob = await response.blob();
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = `LSPU_CBAA_Training_Report_${new Date().getTime()}.pdf`;
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                    document.body.removeChild(a);
                    
                    alert('✅ Training Report downloaded successfully!\n\nThis report includes:\n• Complete CBAA anonymous training records\n• Model training process\n• Algorithm comparison\n• Validation results\n• Accuracy metrics\n• Historical validation');
                } else {
                    const error = await response.json();
                    alert('Error: ' + (error.error || 'Failed to generate training report'));
                }
            } catch (error) {
                alert('Error: ' + error.message + '\n\nMake sure the CBAA Python API is running on port 5002.');
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        }
        
        async function retrainModel() {
            if (!confirm('This will retrain all 7 prediction algorithms with the latest CBAA anonymous data. This may take a few minutes. Continue?')) {
                return;
            }
            
            const btn = event.target;
            const originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Training Models...';
            
            try {
                const response = await fetch('http://localhost:5002/api/train', {
                    method: 'POST'
                });
                
                if (!response.ok) {
                    throw new Error('Training request failed with status: ' + response.status);
                }
                
                const data = await response.json();
                
                if (data.success) {
                    const r2Score = data.metadata?.best_model_metrics?.test_r2;
                    const scoreText = r2Score != null ? r2Score.toFixed(4) : 'N/A';
                    alert('✅ All models retrained successfully!\n\nBest Model: ' + data.metadata.best_model + '\nR² Score: ' + scoreText);
                    location.reload();
                } else {
                    alert('Training failed: ' + (data.error || 'Unknown error'));
                }
            } catch (error) {
                alert('Error: ' + error.message + '\n\nMake sure the CBAA Python API is running on port 5002.');
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        }
        
        function showChartInfo(chartType) {
            const chartInfoData = {
                'r2comparison': {
                    title: 'Model R² Score Comparison',
                    type: 'Bar Chart',
                    purpose: 'Compare the accuracy of different AI prediction models',
                    explanation: 'This chart shows how well each of our 7 prediction algorithms performs. The R² score (R-squared) measures how accurately the model predicts passing rates, where 1.0 is perfect prediction and 0.0 means the model is no better than random guessing.',
                    howToRead: [
                        'Higher bars = Better prediction accuracy',
                        'R² above 0.8 = Excellent model performance',
                        'R² between 0.6-0.8 = Good performance',
                        'R² below 0.6 = Model needs improvement'
                    ],
                    useCase: 'Use this to identify which AI algorithm is most reliable for CBAA board exam predictions.',
                    icon: 'fa-chart-bar'
                },
                'performance': {
                    title: 'Performance Metrics',
                    type: 'Multi-Metric Comparison',
                    purpose: 'Evaluate multiple quality measures of AI models',
                    explanation: 'This visualization shows three key performance indicators: Mean Absolute Error (MAE) - average prediction error in percentage points, Root Mean Square Error (RMSE) - emphasizes larger errors, and R² Score - overall accuracy. Lower MAE and RMSE are better, while higher R² is better.',
                    howToRead: [
                        'MAE: Average error (lower is better)',
                        'RMSE: Penalizes large errors (lower is better)',
                        'R²: Overall accuracy (higher is better)',
                        'Compare across all 7 algorithms'
                    ],
                    useCase: 'Use this for a comprehensive quality assessment when selecting the best prediction model.',
                    icon: 'fa-tachometer-alt'
                },
                'actualvspredicted': {
                    title: 'Actual vs Predicted',
                    type: 'Scatter Plot',
                    purpose: 'Verify how close predictions match real results',
                    explanation: 'This scatter plot compares what the AI predicted versus what actually happened in past board exams. Each point represents one exam. Points close to the diagonal line mean accurate predictions. Points far from the line indicate the model was off.',
                    howToRead: [
                        'Diagonal line = Perfect prediction',
                        'Points near line = Accurate predictions',
                        'Points above line = Model over-predicted',
                        'Points below line = Model under-predicted'
                    ],
                    useCase: 'Use this to see if the model is consistently accurate or tends to over/under-estimate passing rates.',
                    icon: 'fa-bullseye'
                },
                'residuals': {
                    title: 'Residual Analysis',
                    type: 'Residual Plot',
                    purpose: 'Detect systematic errors or biases in predictions',
                    explanation: 'Residuals are the differences between actual and predicted values. This plot shows if the model makes consistent mistakes. Ideally, residuals should be randomly scattered around zero. Patterns in residuals indicate the model has bias.',
                    howToRead: [
                        'Zero line = No error',
                        'Random scatter = Good model',
                        'Visible patterns = Model has bias',
                        'Large residuals = Big prediction errors'
                    ],
                    useCase: 'Use this to validate model reliability and identify if predictions are trustworthy across different passing rate ranges.',
                    icon: 'fa-wave-square'
                },
                'historical': {
                    title: 'Historical Trend',
                    type: 'Line Chart with Trend',
                    purpose: 'Visualize passing rate changes over time',
                    explanation: 'This line chart displays CBAA board exam passing rates over multiple years, showing actual historical data and the trend line. This helps identify if performance is improving, declining, or staying stable over time.',
                    howToRead: [
                        'Blue line = Actual passing rates',
                        'Trend line = Overall direction',
                        'Upward trend = Improving performance',
                        'Downward trend = Declining performance'
                    ],
                    useCase: 'Use this to understand long-term performance patterns and make strategic decisions for improvement programs.',
                    icon: 'fa-chart-line'
                }
            };
            
            const info = chartInfoData[chartType];
            if (!info) return;
            
            // Create modal with CBAA golden theme
            const modal = document.createElement('div');
            modal.style.cssText = `
                position: fixed; top: 0; left: 0; right: 0; bottom: 0;
                background: rgba(0, 0, 0, 0.8); z-index: 10000;
                display: flex; align-items: center; justify-content: center;
                padding: 20px; animation: fadeIn 0.3s ease;
            `;
            
            modal.innerHTML = `
                <div style="background: white; border-radius: 20px; padding: 40px; max-width: 650px; max-height: 90vh; overflow-y: auto; box-shadow: 0 25px 80px rgba(0,0,0,0.5); animation: slideUp 0.3s ease; position: relative;">
                    <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 24px; padding-bottom: 20px; border-bottom: 3px solid #D97706;">
                        <div style="width: 60px; height: 60px; background: linear-gradient(135deg, #D97706 0%, #B45309 100%); border-radius: 15px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(217, 119, 6, 0.4);">
                            <i class="fas ${info.icon}" style="font-size: 28px; color: white;"></i>
                        </div>
                        <div style="flex: 1;">
                            <h2 style="margin: 0; font-size: 1.8rem; color: #0f1724; font-weight: 800;">${info.title}</h2>
                            <p style="margin: 4px 0 0 0; color: #64748b; font-weight: 600; font-size: 0.95rem;">${info.type}</p>
                        </div>
                        <button onclick="this.closest('div[style*=fixed]').remove()" 
                            style="width: 40px; height: 40px; border: none; background: #f1f5f9; border-radius: 10px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.3s;"
                            onmouseover="this.style.background='#e2e8f0'; this.style.transform='rotate(90deg)'"
                            onmouseout="this.style.background='#f1f5f9'; this.style.transform='rotate(0deg)'">
                            <i class="fas fa-times" style="font-size: 18px; color: #64748b;"></i>
                        </button>
                    </div>
                    
                    <div style="margin-bottom: 24px; padding: 20px; background: linear-gradient(135deg, #FEF3E7 0%, #FDE8CC 100%); border-radius: 12px; border-left: 4px solid #D97706;">
                        <h3 style="margin: 0 0 12px 0; color: #0f1724; font-size: 1.2rem; font-weight: 700; display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-bullseye" style="color: #D97706;"></i> Purpose
                        </h3>
                        <p style="margin: 0; color: #334155; font-size: 1.05rem; font-weight: 600; line-height: 1.6;">${info.purpose}</p>
                    </div>

                    <div style="margin-bottom: 24px;">
                        <h3 style="margin: 0 0 12px 0; color: #0f1724; font-size: 1.1rem; font-weight: 700; display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-align-left" style="color: #D97706;"></i> What This Chart Shows
                        </h3>
                        <p style="margin: 0; color: #475569; line-height: 1.8; font-size: 0.95rem;">${info.explanation}</p>
                    </div>

                    <div style="margin-bottom: 24px;">
                        <h3 style="margin: 0 0 16px 0; color: #0f1724; font-size: 1.1rem; font-weight: 700; display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-book-reader" style="color: #D97706;"></i> How to Read This Chart
                        </h3>
                        <ul style="margin: 0; padding-left: 0; list-style: none;">
                            ${info.howToRead.map(tip => `
                                <li style="margin-bottom: 10px; padding: 12px 16px; background: #f8fafc; border-radius: 8px; border-left: 3px solid #D97706; color: #334155; font-size: 0.95rem;">
                                    <i class="fas fa-check-circle" style="color: #D97706; margin-right: 8px;"></i>${tip}
                                </li>
                            `).join('')}
                        </ul>
                    </div>

                    <div style="padding: 20px; background: linear-gradient(135deg, #D97706 0%, #B45309 100%); border-radius: 12px; color: white;">
                        <h3 style="margin: 0 0 12px 0; font-size: 1.1rem; font-weight: 700; display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-lightbulb"></i> Practical Use
                        </h3>
                        <p style="margin: 0; line-height: 1.8; font-size: 0.95rem; opacity: 0.95;">${info.useCase}</p>
                    </div>

                    <button onclick="this.closest('div[style*=fixed]').remove()" 
                        style="width: 100%; margin-top: 24px; padding: 14px; background: #D97706; color: white; border: none; border-radius: 12px; font-weight: 700; cursor: pointer; font-size: 1.05rem; transition: all 0.3s;"
                        onmouseover="this.style.background='#B45309'; this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(217,119,6,0.4)'"
                        onmouseout="this.style.background='#D97706'; this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                        <i class="fas fa-times-circle"></i> Close
                    </button>
                </div>
            `;
            
            // Add animation styles
            const style = document.createElement('style');
            style.textContent = `
                @keyframes fadeIn {
                    from { opacity: 0; }
                    to { opacity: 1; }
                }
                @keyframes slideUp {
                    from { transform: translateY(50px); opacity: 0; }
                    to { transform: translateY(0); opacity: 1; }
                }
            `;
            document.head.appendChild(style);
            
            document.body.appendChild(modal);
            modal.onclick = (e) => { 
                if (e.target === modal) modal.remove(); 
            };
        }
    </script>
</body>
</html>


