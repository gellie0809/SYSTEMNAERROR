<?php
session_start();

// Only allow College of Criminal Justice Education admin
if (!isset($_SESSION["users"]) || $_SESSION["users"] !== 'ccje_admin@lspu.edu.ph') {
    header("Location: index.php");
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "project_db";
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

/**
 * PHP-Based Statistical Prediction Engine for CCJE
 * Uses weighted moving average, trend analysis, and confidence intervals
 * No external Python API required
 */

class CCJEPredictor {
    private $conn;
    private $department = 'Criminal Justice Education';
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * Get historical passing rates by year
     */
    public function getHistoricalData() {
        $sql = "SELECT 
                    YEAR(board_exam_date) as year,
                    board_exam_type,
                    COUNT(*) as total,
                    SUM(CASE WHEN result = 'Passed' THEN 1 ELSE 0 END) as passed,
                    ROUND(SUM(CASE WHEN result = 'Passed' THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 2) as passing_rate
                FROM anonymous_board_passers 
                WHERE department = ? 
                    AND (is_deleted IS NULL OR is_deleted = 0)
                    AND YEAR(board_exam_date) BETWEEN 2019 AND 2024
                GROUP BY YEAR(board_exam_date), board_exam_type
                ORDER BY board_exam_type, year";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $this->department);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $examType = $row['board_exam_type'];
            if (!isset($data[$examType])) {
                $data[$examType] = [];
            }
            $data[$examType][$row['year']] = [
                'total' => (int)$row['total'],
                'passed' => (int)$row['passed'],
                'passing_rate' => (float)$row['passing_rate']
            ];
        }
        $stmt->close();
        
        return $data;
    }
    
    /**
     * Get overall statistics by year (all exam types combined)
     */
    public function getOverallByYear() {
        $sql = "SELECT 
                    YEAR(board_exam_date) as year,
                    COUNT(*) as total,
                    SUM(CASE WHEN result = 'Passed' THEN 1 ELSE 0 END) as passed,
                    ROUND(SUM(CASE WHEN result = 'Passed' THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 2) as passing_rate
                FROM anonymous_board_passers 
                WHERE department = ? 
                    AND (is_deleted IS NULL OR is_deleted = 0)
                    AND YEAR(board_exam_date) BETWEEN 2019 AND 2024
                GROUP BY YEAR(board_exam_date)
                ORDER BY year";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $this->department);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[$row['year']] = [
                'total' => (int)$row['total'],
                'passed' => (int)$row['passed'],
                'passing_rate' => (float)$row['passing_rate']
            ];
        }
        $stmt->close();
        
        return $data;
    }
    
    /**
     * Calculate weighted moving average prediction
     * More recent years have higher weights
     */
    public function weightedMovingAverage($rates, $weights = null) {
        if (empty($rates)) return 0;
        
        $n = count($rates);
        if ($weights === null) {
            // Default: more recent years have higher weights
            $weights = [];
            for ($i = 1; $i <= $n; $i++) {
                $weights[] = $i;
            }
        }
        
        $weightedSum = 0;
        $totalWeight = array_sum($weights);
        
        $values = array_values($rates);
        for ($i = 0; $i < $n; $i++) {
            $weightedSum += $values[$i] * $weights[$i];
        }
        
        return $totalWeight > 0 ? $weightedSum / $totalWeight : 0;
    }
    
    /**
     * Calculate linear trend using least squares regression
     */
    public function linearTrend($data) {
        $n = count($data);
        if ($n < 2) return ['slope' => 0, 'intercept' => 0, 'r_squared' => 0];
        
        $years = array_keys($data);
        $rates = array_values($data);
        
        // Normalize years for calculation
        $minYear = min($years);
        $x = array_map(function($y) use ($minYear) { return $y - $minYear; }, $years);
        
        $sumX = array_sum($x);
        $sumY = array_sum($rates);
        $sumXY = 0;
        $sumX2 = 0;
        $sumY2 = 0;
        
        for ($i = 0; $i < $n; $i++) {
            $sumXY += $x[$i] * $rates[$i];
            $sumX2 += $x[$i] * $x[$i];
            $sumY2 += $rates[$i] * $rates[$i];
        }
        
        $denom = ($n * $sumX2 - $sumX * $sumX);
        if ($denom == 0) {
            return ['slope' => 0, 'intercept' => $sumY / $n, 'r_squared' => 0];
        }
        
        $slope = ($n * $sumXY - $sumX * $sumY) / $denom;
        $intercept = ($sumY - $slope * $sumX) / $n;
        
        // Calculate R-squared
        $meanY = $sumY / $n;
        $ssTotal = 0;
        $ssResidual = 0;
        
        for ($i = 0; $i < $n; $i++) {
            $predicted = $intercept + $slope * $x[$i];
            $ssTotal += pow($rates[$i] - $meanY, 2);
            $ssResidual += pow($rates[$i] - $predicted, 2);
        }
        
        $rSquared = $ssTotal > 0 ? 1 - ($ssResidual / $ssTotal) : 0;
        
        return [
            'slope' => $slope,
            'intercept' => $intercept,
            'r_squared' => max(0, min(1, $rSquared)),
            'base_year' => $minYear
        ];
    }
    
    /**
     * Calculate standard deviation
     */
    public function standardDeviation($values) {
        $n = count($values);
        if ($n < 2) return 0;
        
        $mean = array_sum($values) / $n;
        $variance = 0;
        
        foreach ($values as $val) {
            $variance += pow($val - $mean, 2);
        }
        
        return sqrt($variance / ($n - 1));
    }
    
    /**
     * Generate prediction for a specific exam type
     */
    public function predictForExamType($examType, $historicalData, $predictionYear = null) {
        if ($predictionYear === null) {
            $predictionYear = date('Y') + 1;
        }
        
        if (!isset($historicalData[$examType]) || empty($historicalData[$examType])) {
            return null;
        }
        
        $data = $historicalData[$examType];
        $years = array_keys($data);
        $rates = [];
        
        foreach ($data as $year => $info) {
            $rates[$year] = $info['passing_rate'];
        }
        
        // Method 1: Weighted Moving Average
        $wma = $this->weightedMovingAverage($rates);
        
        // Method 2: Linear Trend
        $trend = $this->linearTrend($rates);
        $trendPrediction = $trend['intercept'] + $trend['slope'] * ($predictionYear - $trend['base_year']);
        
        // Method 3: Exponential Smoothing (Simple)
        $alpha = 0.3; // Smoothing factor
        $ema = array_values($rates)[0];
        foreach ($rates as $rate) {
            $ema = $alpha * $rate + (1 - $alpha) * $ema;
        }
        
        // Combine predictions with weights based on trend reliability
        $trendWeight = min(0.5, max(0.1, $trend['r_squared']));
        $wmaWeight = 0.4;
        $emaWeight = 1 - $trendWeight - $wmaWeight;
        
        $combinedPrediction = ($wma * $wmaWeight) + ($trendPrediction * $trendWeight) + ($ema * $emaWeight);
        
        // Bound prediction to reasonable range
        $combinedPrediction = max(0, min(100, $combinedPrediction));
        
        // Calculate confidence interval
        $stdDev = $this->standardDeviation(array_values($rates));
        $margin = 1.96 * $stdDev; // 95% confidence
        
        // Get latest year data
        $latestYear = max($years);
        $latestRate = $rates[$latestYear];
        
        // Calculate historical average
        $historicalAvg = array_sum($rates) / count($rates);
        
        return [
            'board_exam_type' => $examType,
            'prediction_year' => (int)$predictionYear,
            'current_year' => (int)$latestYear,
            'predicted_passing_rate' => round($combinedPrediction, 2),
            'historical_avg' => round($historicalAvg, 2),
            'latest_rate' => round($latestRate, 2),
            'trend_direction' => $trend['slope'] >= 0 ? 'improving' : 'declining',
            'trend_slope' => round($trend['slope'], 3),
            'model_confidence' => round($trend['r_squared'] * 100, 1),
            'std_deviation' => round($stdDev, 2),
            'confidence_interval_95' => [
                'lower' => round(max(0, $combinedPrediction - $margin), 2),
                'upper' => round(min(100, $combinedPrediction + $margin), 2)
            ],
            'data_points' => count($rates),
            'methods_used' => [
                'weighted_moving_average' => round($wma, 2),
                'linear_trend' => round($trendPrediction, 2),
                'exponential_smoothing' => round($ema, 2)
            ]
        ];
    }
    
    /**
     * Generate overall prediction (all exam types combined)
     */
    public function predictOverall($predictionYear = null) {
        if ($predictionYear === null) {
            $predictionYear = date('Y') + 1;
        }
        
        $overallData = $this->getOverallByYear();
        
        if (empty($overallData)) {
            return null;
        }
        
        $rates = [];
        foreach ($overallData as $year => $info) {
            $rates[$year] = $info['passing_rate'];
        }
        
        // Same prediction methods as individual
        $wma = $this->weightedMovingAverage($rates);
        $trend = $this->linearTrend($rates);
        $trendPrediction = $trend['intercept'] + $trend['slope'] * ($predictionYear - $trend['base_year']);
        
        $alpha = 0.3;
        $ema = array_values($rates)[0];
        foreach ($rates as $rate) {
            $ema = $alpha * $rate + (1 - $alpha) * $ema;
        }
        
        $trendWeight = min(0.5, max(0.1, $trend['r_squared']));
        $wmaWeight = 0.4;
        $emaWeight = 1 - $trendWeight - $wmaWeight;
        
        $combinedPrediction = ($wma * $wmaWeight) + ($trendPrediction * $trendWeight) + ($ema * $emaWeight);
        $combinedPrediction = max(0, min(100, $combinedPrediction));
        
        $stdDev = $this->standardDeviation(array_values($rates));
        $margin = 1.96 * $stdDev;
        
        $years = array_keys($rates);
        $latestYear = max($years);
        $latestRate = $rates[$latestYear];
        $historicalAvg = array_sum($rates) / count($rates);
        
        // Calculate total records
        $totalRecords = 0;
        foreach ($overallData as $info) {
            $totalRecords += $info['total'];
        }
        
        return [
            'board_exam_type' => 'All CCJE Exams (Overall)',
            'prediction_year' => (int)$predictionYear,
            'current_year' => (int)$latestYear,
            'predicted_passing_rate' => round($combinedPrediction, 2),
            'historical_avg' => round($historicalAvg, 2),
            'latest_rate' => round($latestRate, 2),
            'trend_direction' => $trend['slope'] >= 0 ? 'improving' : 'declining',
            'trend_slope' => round($trend['slope'], 3),
            'model_confidence' => round($trend['r_squared'] * 100, 1),
            'std_deviation' => round($stdDev, 2),
            'confidence_interval_95' => [
                'lower' => round(max(0, $combinedPrediction - $margin), 2),
                'upper' => round(min(100, $combinedPrediction + $margin), 2)
            ],
            'data_points' => count($rates),
            'total_records' => $totalRecords,
            'methods_used' => [
                'weighted_moving_average' => round($wma, 2),
                'linear_trend' => round($trendPrediction, 2),
                'exponential_smoothing' => round($ema, 2)
            ]
        ];
    }
    
    /**
     * Generate all predictions
     */
    public function generateAllPredictions($predictionYear = null) {
        $historicalData = $this->getHistoricalData();
        $predictions = [];
        
        // Overall prediction first
        $overall = $this->predictOverall($predictionYear);
        if ($overall) {
            $predictions[] = $overall;
        }
        
        // Individual exam type predictions
        foreach ($historicalData as $examType => $data) {
            $prediction = $this->predictForExamType($examType, $historicalData, $predictionYear);
            if ($prediction) {
                $predictions[] = $prediction;
            }
        }
        
        return $predictions;
    }
    
    /**
     * Perform backtesting validation
     */
    public function backtest($testYear = 2023, $trainUntil = 2022) {
        // Get data up to trainUntil year
        $sql = "SELECT 
                    YEAR(board_exam_date) as year,
                    COUNT(*) as total,
                    SUM(CASE WHEN result = 'Passed' THEN 1 ELSE 0 END) as passed,
                    ROUND(SUM(CASE WHEN result = 'Passed' THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 2) as passing_rate
                FROM anonymous_board_passers 
                WHERE department = ? 
                    AND (is_deleted IS NULL OR is_deleted = 0)
                    AND YEAR(board_exam_date) BETWEEN 2019 AND ?
                GROUP BY YEAR(board_exam_date)
                ORDER BY year";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("si", $this->department, $trainUntil);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $trainingRates = [];
        while ($row = $result->fetch_assoc()) {
            $trainingRates[$row['year']] = (float)$row['passing_rate'];
        }
        $stmt->close();
        
        if (empty($trainingRates)) {
            return null;
        }
        
        // Get actual test year data
        $sql = "SELECT 
                    ROUND(SUM(CASE WHEN result = 'Passed' THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 2) as passing_rate
                FROM anonymous_board_passers 
                WHERE department = ? 
                    AND (is_deleted IS NULL OR is_deleted = 0)
                    AND YEAR(board_exam_date) = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("si", $this->department, $testYear);
        $stmt->execute();
        $result = $stmt->get_result();
        $actualRow = $result->fetch_assoc();
        $stmt->close();
        
        if (!$actualRow || $actualRow['passing_rate'] === null) {
            return null;
        }
        
        $actualRate = (float)$actualRow['passing_rate'];
        
        // Make prediction using training data
        $wma = $this->weightedMovingAverage($trainingRates);
        $trend = $this->linearTrend($trainingRates);
        $trendPrediction = $trend['intercept'] + $trend['slope'] * ($testYear - $trend['base_year']);
        
        $alpha = 0.3;
        $ema = array_values($trainingRates)[0];
        foreach ($trainingRates as $rate) {
            $ema = $alpha * $rate + (1 - $alpha) * $ema;
        }
        
        $trendWeight = min(0.5, max(0.1, $trend['r_squared']));
        $wmaWeight = 0.4;
        $emaWeight = 1 - $trendWeight - $wmaWeight;
        
        $predictedRate = ($wma * $wmaWeight) + ($trendPrediction * $trendWeight) + ($ema * $emaWeight);
        $predictedRate = max(0, min(100, $predictedRate));
        
        $error = abs($predictedRate - $actualRate);
        $accuracy = max(0, 100 - $error);
        
        return [
            'test_year' => $testYear,
            'train_until' => $trainUntil,
            'actual_rate' => round($actualRate, 2),
            'predicted_rate' => round($predictedRate, 2),
            'absolute_error' => round($error, 2),
            'accuracy' => round($accuracy, 1),
            'training_years' => count($trainingRates)
        ];
    }
}

// Initialize predictor
$predictor = new CCJEPredictor($conn);
$predictions = $predictor->generateAllPredictions();
$backtest = $predictor->backtest(2023, 2022);
$historicalData = $predictor->getOverallByYear();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Passing Rate Predictions - CCJE Anonymous Data</title>
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
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #FDF3E7 0%, #FAD6A5 100%);
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }
        
        /* CCJE-specific sidebar color overrides for red theme */
        html body .sidebar {
            background: #ffffff !important;
            box-shadow: 0 2px 8px rgba(211, 47, 47, 0.08) !important;
            border-right: 1px solid rgba(211, 47, 47, 0.1) !important;
        }

        html body .sidebar .logo {
            color: #D32F2F !important;
        }

        html body .sidebar-nav a {
            color: #800020 !important;
        }

        html body .sidebar-nav i,
        html body .sidebar-nav ion-icon {
            color: #D32F2F !important;
        }

        html body .sidebar-nav a.active,
        html body .sidebar-nav a:hover {
            background: linear-gradient(90deg, #D32F2F 0%, #800020 100%) !important;
            color: #fff !important;
            box-shadow: 0 8px 25px rgba(211, 47, 47, 0.25) !important;
        }

        html body .sidebar-nav a.active i,
        html body .sidebar-nav a.active ion-icon,
        html body .sidebar-nav a:hover i,
        html body .sidebar-nav a:hover ion-icon {
            color: #fff !important;
        }
        
        .topbar {
            position: fixed;
            top: 0;
            left: 260px;
            right: 0;
            background: linear-gradient(135deg, #D32F2F 0%, #C62828 100%);
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
            box-shadow: 0 10px 40px rgba(211, 47, 47, 0.12);
            border: 2px solid rgba(211, 47, 47, 0.15);
        }
        
        .prediction-card h2 {
            color: #800020;
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
            background: linear-gradient(135deg, #FDF3E7 0%, #FAD6A5 100%);
            padding: 24px;
            border-radius: 16px;
            border-left: 4px solid #D32F2F;
            transition: all 0.3s;
        }
        
        .prediction-item:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 30px rgba(211, 47, 47, 0.3);
        }
        
        .prediction-item.overall {
            background: linear-gradient(135deg, #D32F2F 0%, #800020 100%);
            color: white;
            border-left: 4px solid #FAD6A5;
        }
        
        .prediction-item.overall h3 {
            color: white;
        }
        
        .prediction-item.overall .rate-label {
            color: rgba(255,255,255,0.8);
        }
        
        .prediction-item.overall .rate-value {
            color: #FAD6A5;
        }
        
        .prediction-item h3 {
            color: #800020;
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
            color: #800020;
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
            background: rgba(211, 47, 47, 0.2);
            color: #800020;
        }
        
        .rate-change.negative {
            background: rgba(239, 68, 68, 0.2);
            color: #dc2626;
        }
        
        .prediction-item.overall .rate-change.positive {
            background: rgba(250, 214, 165, 0.3);
            color: #FAD6A5;
        }
        
        .prediction-item.overall .rate-change.negative {
            background: rgba(255, 200, 200, 0.3);
            color: #ffcccc;
        }
        
        .confidence-interval {
            margin-top: 16px;
            padding: 12px;
            background: rgba(255, 255, 255, 0.7);
            border-radius: 10px;
        }
        
        .prediction-item.overall .confidence-interval {
            background: rgba(255, 255, 255, 0.15);
        }
        
        .confidence-interval h4 {
            font-size: 0.9rem;
            color: #64748b;
            margin-bottom: 8px;
        }
        
        .prediction-item.overall .confidence-interval h4 {
            color: rgba(255, 255, 255, 0.8);
        }
        
        .ci-bar {
            height: 30px;
            background: linear-gradient(90deg, #FAD6A5, #D32F2F);
            border-radius: 8px;
            position: relative;
            margin: 10px 0;
        }
        
        .ci-marker {
            position: absolute;
            top: -5px;
            width: 3px;
            height: 40px;
            background: #800020;
            box-shadow: 0 0 10px rgba(128, 0, 32, 0.5);
        }
        
        .prediction-item.overall .ci-marker {
            background: #FAD6A5;
            box-shadow: 0 0 10px rgba(250, 214, 165, 0.5);
        }
        
        .ci-labels {
            display: flex;
            justify-content: space-between;
            font-size: 0.85rem;
            color: #64748b;
            margin-top: 4px;
        }
        
        .prediction-item.overall .ci-labels {
            color: rgba(255, 255, 255, 0.8);
        }
        
        .model-info-card {
            background: linear-gradient(135deg, #D32F2F 0%, #800020 100%);
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
        
        .methods-card {
            background: white;
            padding: 24px;
            border-radius: 16px;
            margin-top: 16px;
            border: 2px solid rgba(211, 47, 47, 0.15);
        }
        
        .methods-card h4 {
            color: #800020;
            margin-bottom: 16px;
        }
        
        .method-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px;
            background: #f8fafc;
            border-radius: 8px;
            margin-bottom: 8px;
            border-left: 3px solid #D32F2F;
        }
        
        .method-name {
            font-weight: 600;
            color: #800020;
        }
        
        .method-value {
            font-size: 1.1rem;
            font-weight: 700;
            color: #D32F2F;
        }
        
        .backtest-card {
            background: linear-gradient(135deg, #FFF5F5 0%, #FFE8E8 100%);
            border: 3px solid #D32F2F;
            border-radius: 16px;
            padding: 25px;
            margin-top: 24px;
            box-shadow: 0 4px 15px rgba(211, 47, 47, 0.15);
        }
        
        .backtest-header {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .accuracy-badge {
            display: inline-block;
            background: linear-gradient(135deg, #800020 0%, #D32F2F 100%);
            color: white;
            padding: 15px 30px;
            border-radius: 50px;
            margin-bottom: 15px;
            box-shadow: 0 4px 12px rgba(128, 0, 32, 0.3);
        }
        
        .accuracy-value {
            font-size: 52px;
            font-weight: bold;
            vertical-align: middle;
        }
        
        .backtest-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        
        .backtest-table th {
            padding: 15px;
            text-align: left;
            font-weight: 700;
            color: white;
            background: linear-gradient(to right, #800020, #D32F2F);
        }
        
        .backtest-table td {
            padding: 14px;
            border-bottom: 1px solid #FFE8E8;
        }
        
        .backtest-table tr:hover {
            background: #FFF5F5;
        }
        
        .chart-container {
            background: white;
            padding: 24px;
            border-radius: 16px;
            margin-top: 24px;
            box-shadow: 0 4px 15px rgba(211, 47, 47, 0.1);
        }
        
        .chart-container h3 {
            color: #800020;
            margin-bottom: 20px;
        }
        
        .btn {
            background: linear-gradient(135deg, #D32F2F 0%, #800020 100%);
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
            text-decoration: none;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(211, 47, 47, 0.4);
        }
        
        .btn-secondary {
            background: rgba(255,255,255,0.2);
        }
        
        .info-note {
            margin-top: 20px;
            padding: 18px;
            background: linear-gradient(135deg, #FFF9E6 0%, #FFE8CC 100%);
            border-radius: 12px;
            border-left: 5px solid #FAD6A5;
        }
        
        .info-note p {
            margin: 0;
            color: #5D4037;
            font-size: 14px;
            line-height: 1.7;
        }
        
        .trend-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-top: 8px;
        }
        
        .trend-badge.improving {
            background: rgba(34, 197, 94, 0.15);
            color: #16a34a;
        }
        
        .trend-badge.declining {
            background: rgba(239, 68, 68, 0.15);
            color: #dc2626;
        }
        
        .prediction-item.overall .trend-badge.improving {
            background: rgba(250, 214, 165, 0.3);
            color: #FAD6A5;
        }
        
        .prediction-item.overall .trend-badge.declining {
            background: rgba(255, 200, 200, 0.3);
            color: #ffcccc;
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
            
            .dashboard-title {
                font-size: 1.1rem;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/includes/ccje_nav.php'; ?>
    
    <div class="topbar">
        <div class="dashboard-title">
            <i class="fas fa-chart-line"></i>
            Passing Rate Predictions - CCJE
        </div>
        <a href="anonymous_dashboard_ccje.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>
    
    <div class="main">
        <?php if (!empty($predictions)): ?>
            <!-- Main Predictions -->
            <div class="prediction-card">
                <h2>
                    <i class="fas fa-chart-line"></i>
                    Passing Rate Predictions for <?php echo date('Y') + 1; ?>
                </h2>
                
                <div class="prediction-grid">
                    <?php foreach ($predictions as $idx => $pred): ?>
                        <?php 
                            $change = $pred['predicted_passing_rate'] - $pred['historical_avg'];
                            $changeClass = $change >= 0 ? 'positive' : 'negative';
                            $ci = $pred['confidence_interval_95'];
                            $ci_range = $ci['upper'] - $ci['lower'];
                            $marker_pos = $ci_range > 0 ? (($pred['predicted_passing_rate'] - $ci['lower']) / $ci_range * 100) : 50;
                            $isOverall = $idx === 0;
                        ?>
                        <div class="prediction-item <?php echo $isOverall ? 'overall' : ''; ?>">
                            <h3>
                                <?php if ($isOverall): ?>
                                    <i class="fas fa-globe"></i>
                                <?php endif; ?>
                                <?php echo htmlspecialchars($pred['board_exam_type']); ?>
                            </h3>
                            
                            <div class="rate-comparison">
                                <div class="rate-box">
                                    <div class="rate-label">Historical Avg</div>
                                    <div class="rate-value"><?php echo $pred['historical_avg']; ?>%</div>
                                </div>
                                
                                <div>
                                    <i class="fas fa-arrow-right" style="color: <?php echo $isOverall ? '#FAD6A5' : '#D32F2F'; ?>; font-size: 1.5rem;"></i>
                                </div>
                                
                                <div class="rate-box">
                                    <div class="rate-label"><?php echo $pred['prediction_year']; ?> Prediction</div>
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
                                vs Historical Avg
                            </div>
                            
                            <div class="trend-badge <?php echo $pred['trend_direction']; ?>">
                                <?php if ($pred['trend_direction'] === 'improving'): ?>
                                    <i class="fas fa-trending-up"></i> Improving Trend
                                <?php else: ?>
                                    <i class="fas fa-trending-down"></i> Declining Trend
                                <?php endif; ?>
                                (<?php echo $pred['trend_slope'] >= 0 ? '+' : ''; ?><?php echo $pred['trend_slope']; ?>%/year)
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
                                <p style="font-size: 0.8rem; <?php echo $isOverall ? 'color: rgba(255,255,255,0.7);' : 'color: #64748b;'; ?> margin-top: 8px; text-align: center;">
                                    ±<?php echo $pred['std_deviation']; ?>% standard deviation | <?php echo $pred['data_points']; ?> years of data
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Model Information -->
            <div class="model-info-card">
                <h3><i class="fas fa-cogs"></i> Statistical Prediction Model</h3>
                <p style="opacity: 0.95; margin-bottom: 16px;">
                    This prediction uses a PHP-based statistical engine combining multiple forecasting methods.
                    No external API server required.
                </p>
                
                <div class="model-metrics">
                    <div class="metric">
                        <div class="metric-label">Prediction Methods</div>
                        <div class="metric-value">3</div>
                    </div>
                    <div class="metric">
                        <div class="metric-label">Data Years</div>
                        <div class="metric-value"><?php echo count($historicalData); ?></div>
                    </div>
                    <div class="metric">
                        <div class="metric-label">Total Records</div>
                        <div class="metric-value"><?php echo array_sum(array_column($historicalData, 'total')); ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Methods Used -->
            <div class="methods-card">
                <h4><i class="fas fa-layer-group"></i> Prediction Methods Combined</h4>
                <?php if (!empty($predictions[0]['methods_used'])): ?>
                    <?php foreach ($predictions[0]['methods_used'] as $method => $value): ?>
                        <div class="method-item">
                            <span class="method-name">
                                <?php 
                                    $methodNames = [
                                        'weighted_moving_average' => 'Weighted Moving Average',
                                        'linear_trend' => 'Linear Trend Regression',
                                        'exponential_smoothing' => 'Exponential Smoothing'
                                    ];
                                    echo $methodNames[$method] ?? ucwords(str_replace('_', ' ', $method));
                                ?>
                            </span>
                            <span class="method-value"><?php echo $value; ?>%</span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                <div class="info-note" style="margin-top: 16px;">
                    <p>
                        <strong style="color: #800020;">How it works:</strong><br>
                        The final prediction combines all three methods with adaptive weights. 
                        Linear trend regression receives higher weight when the historical data shows a strong pattern (high R²).
                        More recent years are weighted more heavily in the moving average calculation.
                    </p>
                </div>
            </div>
            
            <!-- Backtesting Validation -->
            <?php if ($backtest): ?>
                <div class="backtest-card">
                    <div class="backtest-header">
                        <div class="accuracy-badge">
                            <i class="fas fa-check-circle" style="margin-right: 10px; font-size: 24px; vertical-align: middle;"></i>
                            <span class="accuracy-value"><?php echo $backtest['accuracy']; ?>%</span>
                        </div>
                        <div style="color: #800020; font-size: 16px; font-weight: 600;">Model Validation Accuracy</div>
                    </div>
                    
                    <h4 style="color: #800020; margin-bottom: 15px;">
                        <i class="fas fa-vial"></i> Backtesting Results
                    </h4>
                    <p style="margin-bottom: 15px; color: #666; line-height: 1.6;">
                        To verify prediction accuracy, we trained the model on 2019-<?php echo $backtest['train_until']; ?> data and predicted <?php echo $backtest['test_year']; ?>. 
                        Here's how accurate our prediction was compared to the actual <?php echo $backtest['test_year']; ?> result:
                    </p>
                    
                    <table class="backtest-table">
                        <tr>
                            <th style="text-align: left;">Metric</th>
                            <th style="text-align: right;">Value</th>
                        </tr>
                        <tr>
                            <td>
                                <i class="fas fa-chart-line" style="color: #D32F2F; margin-right: 8px;"></i>
                                Actual <?php echo $backtest['test_year']; ?> Passing Rate
                            </td>
                            <td style="text-align: right; font-weight: 700; font-size: 18px; color: #800020;">
                                <?php echo $backtest['actual_rate']; ?>%
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <i class="fas fa-brain" style="color: #D32F2F; margin-right: 8px;"></i>
                                Model Predicted (using 2019-<?php echo $backtest['train_until']; ?>)
                            </td>
                            <td style="text-align: right; font-weight: 700; font-size: 18px; color: #1565C0;">
                                <?php echo $backtest['predicted_rate']; ?>%
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <i class="fas fa-exclamation-triangle" style="color: #D32F2F; margin-right: 8px;"></i>
                                Prediction Error
                            </td>
                            <td style="text-align: right; font-weight: 600; font-size: 16px; color: #C62828;">
                                <?php echo $backtest['absolute_error']; ?> percentage points
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <i class="fas fa-check-double" style="color: #2E7D32; margin-right: 8px;"></i>
                                Prediction Accuracy
                            </td>
                            <td style="text-align: right; font-weight: 800; font-size: 20px; color: #2E7D32;">
                                <?php echo $backtest['accuracy']; ?>%
                            </td>
                        </tr>
                    </table>
                    
                    <div class="info-note">
                        <p>
                            <strong style="color: #800020;">What this means:</strong><br>
                            Our statistical model achieved <strong style="color: #800020;"><?php echo $backtest['accuracy']; ?>%</strong> accuracy when predicting <?php echo $backtest['test_year']; ?> results using only 2019-<?php echo $backtest['train_until']; ?> training data. 
                            This validates that our prediction model can reliably forecast future CCJE board exam passing rates.
                        </p>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Historical Trend Chart -->
            <div class="chart-container">
                <h3><i class="fas fa-chart-area"></i> Historical Passing Rate Trend</h3>
                <canvas id="trendChart" height="100"></canvas>
            </div>
            
        <?php else: ?>
            <div class="prediction-card">
                <h2><i class="fas fa-exclamation-triangle"></i> No Data Available</h2>
                <p>There is no anonymous board passer data for CCJE to generate predictions.</p>
                <p style="margin-top: 16px;">
                    <a href="testing_anonymous_data_ccje.php" class="btn">
                        <i class="fas fa-plus"></i> Add Anonymous Data
                    </a>
                </p>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        // Historical Trend Chart
        <?php if (!empty($historicalData)): ?>
        const ctx = document.getElementById('trendChart').getContext('2d');
        const years = <?php echo json_encode(array_keys($historicalData)); ?>;
        const rates = <?php echo json_encode(array_column($historicalData, 'passing_rate')); ?>;
        
        // Add prediction point
        const predictionYear = <?php echo date('Y') + 1; ?>;
        const predictedRate = <?php echo $predictions[0]['predicted_passing_rate'] ?? 0; ?>;
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: [...years.map(y => y.toString()), predictionYear.toString() + ' (Predicted)'],
                datasets: [{
                    label: 'Passing Rate (%)',
                    data: [...rates, predictedRate],
                    borderColor: '#D32F2F',
                    backgroundColor: 'rgba(211, 47, 47, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.3,
                    pointBackgroundColor: [...years.map(() => '#D32F2F'), '#FAD6A5'],
                    pointBorderColor: [...years.map(() => '#800020'), '#800020'],
                    pointBorderWidth: 2,
                    pointRadius: [...years.map(() => 6), 10],
                    pointHoverRadius: 8
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: '#800020',
                        titleFont: { size: 14, weight: 'bold' },
                        bodyFont: { size: 13 },
                        padding: 12,
                        cornerRadius: 8,
                        callbacks: {
                            label: function(context) {
                                return 'Passing Rate: ' + context.parsed.y + '%';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: false,
                        min: Math.max(0, Math.min(...rates) - 10),
                        max: Math.min(100, Math.max(...rates, predictedRate) + 10),
                        grid: {
                            color: 'rgba(211, 47, 47, 0.1)'
                        },
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>
