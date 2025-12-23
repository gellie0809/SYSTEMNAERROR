<?php
session_start();

// Allow CAS admin or ICTS admin
if (!isset($_SESSION["users"]) || ($_SESSION["users"] !== 'cas_admin@lspu.edu.ph' && $_SESSION["users"] !== 'icts_admin@lspu.edu.ph')) {
    header("Location: index.php");
    exit();
}

function callPredictionAPI($endpoint, $method = 'GET', $data = null) {
    $url = "http://localhost:5000/api/cas/" . $endpoint;
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
    <title>AI Board Exam Predictions - CAS</title>
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
        background: linear-gradient(135deg, #FFF0FC 0%, #FFA1C3 100%);
        /* Pink gradient background */
        margin: 0;
        font-family: 'Inter', sans-serif;
        min-height: 100vh;
        position: relative;
        overflow-x: hidden;
    }

    body::before {
        content: '';
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background:
            radial-gradient(circle at 20% 20%, rgba(255, 161, 195, 0.1) 0%, transparent 50%),
            radial-gradient(circle at 80% 60%, rgba(131, 0, 52, 0.08) 0%, transparent 50%),
            radial-gradient(circle at 40% 80%, rgba(79, 0, 36, 0.1) 0%, transparent 50%);
        pointer-events: none;
        z-index: 0;
    }

        
        /* Sidebar CAS colors */
        body .sidebar .logo, html body .sidebar .logo { color: #9f1239 !important; }
        body .sidebar-nav a, html body .sidebar-nav a { color: #4F0024 !important; }
        body .sidebar-nav i, html body .sidebar-nav i { color: #9f1239 !important; }
        body .sidebar-nav a.active, body .sidebar-nav a:hover,
        html body .sidebar-nav a.active, html body .sidebar-nav a:hover {
            background: linear-gradient(90deg, #9f1239 0%, #fecdd3 100%) !important;
            color: #4F0024 !important;
        }
        
        .topbar {
            position: fixed;
            top: 0;
            left: 260px;
            right: 0;
            background: linear-gradient(135deg, #9f1239 0%, #4F0024 100%);
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 40px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            z-index: 50;
        }
        
        .dashboard-title {
            font-size: 1.4rem;
            color: #fff;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 12px;
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
            box-shadow: 0 10px 40px rgba(159, 18, 57, 0.12);
            border: 2px solid rgba(159, 18, 57, 0.15);
        }
        
        .prediction-card h2 {
            color: #4F0024;
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
            background: linear-gradient(135deg, #fef2f2 0%, #fecdd3 100%);
            padding: 24px;
            border-radius: 16px;
            border-left: 4px solid #9f1239;
            transition: all 0.3s;
        }
        
        .prediction-item:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 30px rgba(159, 18, 57, 0.3);
        }
        
        .prediction-item h3 {
            color: #4F0024;
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
            color: #9f1239;
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
            background: rgba(159, 18, 57, 0.3);
            color: #9f1239;
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
            background: linear-gradient(90deg, #fecdd3, #9f1239);
            border-radius: 8px;
            position: relative;
            margin: 10px 0;
        }
        
        .ci-marker {
            position: absolute;
            top: -5px;
            width: 3px;
            height: 40px;
            background: #4F0024;
            box-shadow: 0 0 10px rgba(79, 0, 36, 0.5);
        }
        
        .ci-labels {
            display: flex;
            justify-content: space-between;
            font-size: 0.85rem;
            color: #64748b;
            margin-top: 4px;
        }
        
        .model-info-card {
            background: linear-gradient(135deg, #9f1239 0%, #4F0024 100%);
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
            background: linear-gradient(90deg, rgba(159, 18, 57, 0.1), rgba(254, 205, 211, 0.1));
            border-left-color: #9f1239;
        }
        
        .algorithm-name {
            font-weight: 600;
            color: #4F0024;
        }
        
        .algorithm-score {
            font-size: 0.9rem;
            color: #64748b;
        }
        
        .btn {
            background: linear-gradient(135deg, #9f1239 0%, #4F0024 100%);
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
            box-shadow: 0 8px 25px rgba(159, 18, 57, 0.4);
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
            color: #9f1239;
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
    <?php include __DIR__ . '/includes/sidebar_common.php'; ?>
    
    <div class="topbar">
        <div class="dashboard-title">
            <i class="fas fa-brain"></i>
            AI Board Exam Predictions - College of Arts and Sciences
        </div>
        <a href="anonymous_dashboard_cas.php" class="btn" style="background: rgba(255,255,255,0.2);">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>
    
    <div class="main">
        <?php if ($predictionsData && $predictionsData['success']): ?>
            <!-- Main Predictions -->
            <div class="prediction-card">
                <h2>
                    <i class="fas fa-chart-line"></i>
                    Passing Rate Predictions for <?php echo $predictionsData['data']['predictions'][0]['prediction_year']; ?>
                </h2>
                
                <div class="btn-group">
                    <button onclick="exportToPDF()" class="btn">
                        <i class="fas fa-file-pdf"></i> Export to PDF
                    </button>
                    <button onclick="downloadTrainingReport()" class="btn" style="background: linear-gradient(135deg, #4F0024 0%, #9f1239 100%);">
                        <i class="fas fa-book"></i> Download Training Report
                    </button>
                    <button onclick="retrainModel()" class="btn btn-secondary">
                        <i class="fas fa-sync-alt"></i> Retrain Models
                    </button>
                </div>
                
                <div class="prediction-grid">
                    <?php foreach ($predictionsData['data']['predictions'] as $pred): ?>
                        <?php 
                            $change = $pred['predicted_passing_rate'] - $pred['historical_avg'];
                            $changeClass = $change >= 0 ? 'positive' : 'negative';
                            $ci = $pred['confidence_interval_95'];
                            $ci_range = $ci['upper'] - $ci['lower'];
                            $marker_pos = $ci_range > 0 ? (($pred['predicted_passing_rate'] - $ci['lower']) / $ci_range * 100) : 50;
                        ?>
                        <div class="prediction-item">
                            <h3><?php echo htmlspecialchars($pred['board_exam_type']); ?></h3>
                            
                            <div class="rate-comparison">
                                <div class="rate-box">
                                    <div class="rate-label"><?php echo $pred['current_year']; ?></div>
                                    <div class="rate-value"><?php echo $pred['historical_avg']; ?>%</div>
                                </div>
                                
                                <div>
                                    <i class="fas fa-arrow-right" style="color: #9f1239; font-size: 1.5rem;"></i>
                                </div>
                                
                                <div class="rate-box">
                                    <div class="rate-label"><?php echo $pred['prediction_year']; ?></div>
                                    <div class="rate-value"><?php echo $pred['predicted_passing_rate']; ?>%</div>
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
                                    <span><?php echo $ci['lower']; ?>%</span>
                                    <span><strong><?php echo $pred['predicted_passing_rate']; ?>%</strong></span>
                                    <span><?php echo $ci['upper']; ?>%</span>
                                </div>
                                <p style="font-size: 0.8rem; color: #64748b; margin-top: 8px; text-align: center;">
                                    ±<?php echo $pred['std_deviation']; ?>% standard deviation
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Model Information -->
            <?php if ($modelInfo && $modelInfo['success']): ?>
                <div class="model-info-card" style="margin-top: 40px; border: 3px solid #9f1239;">
                    <h3 style="background: linear-gradient(135deg, #9f1239 0%, #4F0024 100%); color: white; padding: 16px; margin: -32px -32px 24px -32px; border-radius: 18px 18px 0 0;"><i class="fas fa-robot"></i> AI Model Performance</h3>
                    <p style="opacity: 0.95;">
                        Algorithm: <strong><?php echo $modelInfo['data']['best_model']; ?></strong><br>
                        Trained: <?php echo date('F d, Y', strtotime($modelInfo['data']['trained_date'])); ?><br>
                        Training Data: <?php echo $modelInfo['data']['training_records']; ?> records
                    </p>
                    
                    <div class="model-metrics">
                        <div class="metric">
                            <div class="metric-label">Accuracy (R²)</div>
                            <div class="metric-value"><?php echo number_format($modelInfo['data']['best_model_metrics']['r2_score'], 4); ?></div>
                        </div>
                        <div class="metric">
                            <div class="metric-label">Avg Error</div>
                            <div class="metric-value"><?php echo number_format($modelInfo['data']['best_model_metrics']['mae'], 2); ?>%</div>
                        </div>
                        <div class="metric">
                            <div class="metric-label">CV Score</div>
                            <div class="metric-value"><?php echo number_format($modelInfo['data']['best_model_metrics']['cv_mean'], 3); ?></div>
                        </div>
                    </div>
                    
                    <!-- Algorithm Comparison -->
                    <div class="algorithm-comparison" style="margin-top:32px;">
                        <h4 style="color: #9f1239; margin-bottom: 12px;">
                            <i class="fas fa-layer-group"></i> Algorithm Comparison
                        </h4>
                        <table style="width:100%; border-collapse:collapse; background:#fff; border-radius:12px; overflow:hidden; box-shadow:0 2px 8px rgba(159,18,57,0.08);">
                            <thead style="background:linear-gradient(90deg,#fecdd3 0%,#fff 100%);">
                                <tr style="color:#9f1239; font-weight:600;">
                                    <th style="padding:12px 8px; text-align:left;">Algorithm</th>
                                    <th style="padding:12px 8px; text-align:center;">Accuracy (R²)</th>
                                    <th style="padding:12px 8px; text-align:center;">Avg Error (MAE)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $allModels = $modelInfo['data']['all_models'];
                                usort($allModels, function($a, $b) {
                                    return $b['test_r2'] <=> $a['test_r2'];
                                });
                                foreach ($allModels as $idx => $model): 
                                    $isBest = $idx === 0;
                                ?>
                                <tr style="<?php echo $isBest ? 'background:#fecdd3;' : 'background:#fff;'; ?>">
                                    <td style="padding:10px 8px; text-align:left; font-weight:<?php echo $isBest ? '700' : '400'; ?>; color:<?php echo $isBest ? '#9f1239' : '#4F0024'; ?>;">
                                        <?php if ($isBest): ?><i class="fas fa-trophy" style="color:#f59e0b; margin-right:6px;"></i><?php endif; ?>
                                        <?php echo $model['model']; ?>
                                    </td>
                                    <td style="padding:10px 8px; text-align:center; color:#4F0024;">
                                        <?php echo number_format($model['test_r2'], 4); ?>
                                    </td>
                                    <td style="padding:10px 8px; text-align:center; color:#4F0024;">
                                        <?php echo number_format($model['test_mae'], 2); ?>%
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Visualization Graphs -->
                <div class="prediction-card">
                    <h2>
                        <i class="fas fa-chart-bar"></i>
                        Performance Visualization
                    </h2>
                    
                    <div class="graph-container">
                        <h3 style="color: #4F0024; margin-bottom: 16px;">Model Comparison Charts</h3>
                        <img src="http://localhost:5000/api/cas/graphs/model_comparison" alt="Model Comparison" 
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                        <div class="loading" style="display: none;">
                            <i class="fas fa-chart-line"></i>
                            <p>Graph not available. Please retrain the models.</p>
                        </div>
                    </div>
                    
                    <div class="graph-container">
                        <h3 style="color: #4F0024; margin-bottom: 16px;">Residual Analysis</h3>
                        <img src="http://localhost:5000/api/cas/graphs/residuals" alt="Residuals" 
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                        <div class="loading" style="display: none;">
                            <i class="fas fa-chart-scatter"></i>
                            <p>Graph not available. Please retrain the models.</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
        <?php else: ?>
            <div class="error-message">
                <h3>⚠️ Prediction Service Unavailable</h3>
                <p style="margin: 8px 0;">The CAS prediction service is not running or the model needs to be trained.</p>
                <p style="margin: 8px 0;"><strong>Setup Instructions:</strong></p>
                <ol style="margin-left: 20px; margin-top: 8px;">
                    <li>Open a terminal/command prompt</li>
                    <li>Navigate to: <code>C:\laragon\www\SYSTEMNAERROR-3\FINALSYSTEMNAERROR\prediction\</code></li>
                    <li>Run: <code>python advanced_predictor_cas.py</code> to train the CAS models</li>
                    <li>Run: <code>start_api.bat</code> to start the API server (if not already running)</li>
                    <li>Refresh this page</li>
                </ol>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        async function exportToPDF() {
            alert('PDF export feature for CAS predictions will be added soon!');
            // Future implementation with CAS-specific PDF generator
        }
        
        async function downloadTrainingReport() {
            const btn = event.target;
            const originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating Report...';
            
            try {
                const response = await fetch('http://localhost:5000/api/cas/export/training-report', {
                    method: 'GET'
                });
                
                if (response.ok) {
                    const blob = await response.blob();
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = `LSPU_CAS_Training_Report_${new Date().getTime()}.pdf`;
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                    document.body.removeChild(a);
                    
                    alert('✅ CAS Training Report downloaded successfully!\n\nThis report includes:\n• Complete CAS training records\n• Model training process\n• Algorithm comparison\n• Validation results\n• Accuracy metrics\n• Historical validation');
                } else {
                    const error = await response.json();
                    alert('Error: ' + (error.error || 'Failed to generate CAS training report'));
                }
            } catch (error) {
                alert('Error: ' + error.message + '\n\nMake sure the Python API is running.');
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        }
        
        async function retrainModel() {
            if (!confirm('This will retrain all 7 prediction algorithms with the latest CAS data. This may take a few minutes. Continue?')) {
                return;
            }
            
            const btn = event.target;
            const originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Training Models...';
            
            try {
                const response = await fetch('http://localhost:5000/api/cas/train', {
                    method: 'POST'
                });
                const data = await response.json();
                
                if (data.success) {
                    alert('✅ All CAS models retrained successfully!\n\nBest Model: ' + data.metadata.best_model + '\nR² Score: ' + data.metadata.best_model_metrics.r2_score.toFixed(4));
                    location.reload();
                } else {
                    alert('Training failed: ' + data.error);
                }
            } catch (error) {
                alert('Error: ' + error.message + '\n\nMake sure the Python API is running.');
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        }
    </script>
</body>
</html>
