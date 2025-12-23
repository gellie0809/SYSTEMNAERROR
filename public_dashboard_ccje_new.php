<?php
// PUBLIC Dashboard - No login required - College of Criminal Justice Education
session_start();

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "project_db";
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

// Fetch all CCJE board passers
$passers = $conn->query("SELECT * FROM anonymous_board_passers WHERE department='Criminal Justice Education' AND (is_deleted IS NULL OR is_deleted = 0) ORDER BY board_exam_date DESC");

// Initialize statistics
$stats = ['total' => 0, 'passed' => 0, 'failed' => 0, 'conditional' => 0, 'first_timer' => 0, 'repeater' => 0, 'first_timer_passed' => 0, 'repeater_passed' => 0];
$yearly_data = [];
$board_exam_stats = [];
$yearly_by_exam = [];

if ($passers) {
    while ($row = $passers->fetch_assoc()) {
        if (isset($row['is_deleted']) && $row['is_deleted'] == 1) continue;
        
        $stats['total']++;
        $year = date('Y', strtotime($row['board_exam_date']));
        $exam_type = $row['board_exam_type'] ?? 'Unknown';
        $result = $row['result'];
        $attempt = $row['exam_type'] ?? 'Unknown';
        
        // Overall results
        if ($result === 'Passed') $stats['passed']++;
        elseif ($result === 'Failed') $stats['failed']++;
        elseif ($result === 'Conditional') $stats['conditional']++;
        
        // First timer vs Repeater
        if ($attempt === 'First Timer') { 
            $stats['first_timer']++; 
            if ($result === 'Passed') $stats['first_timer_passed']++; 
        } elseif ($attempt === 'Repeater') { 
            $stats['repeater']++; 
            if ($result === 'Passed') $stats['repeater_passed']++; 
        }
        
        // Yearly data
        if (!isset($yearly_data[$year])) $yearly_data[$year] = ['passed' => 0, 'failed' => 0, 'total' => 0];
        $yearly_data[$year]['total']++;
        if ($result === 'Passed') $yearly_data[$year]['passed']++; else $yearly_data[$year]['failed']++;
        
        // Board exam type stats
        if (!isset($board_exam_stats[$exam_type])) $board_exam_stats[$exam_type] = ['passed' => 0, 'failed' => 0, 'total' => 0, 'first_timer' => 0, 'repeater' => 0, 'ft_passed' => 0, 'rp_passed' => 0];
        $board_exam_stats[$exam_type]['total']++;
        if ($result === 'Passed') $board_exam_stats[$exam_type]['passed']++; else $board_exam_stats[$exam_type]['failed']++;
        if ($attempt === 'First Timer') { $board_exam_stats[$exam_type]['first_timer']++; if ($result === 'Passed') $board_exam_stats[$exam_type]['ft_passed']++; }
        if ($attempt === 'Repeater') { $board_exam_stats[$exam_type]['repeater']++; if ($result === 'Passed') $board_exam_stats[$exam_type]['rp_passed']++; }
        
        // Yearly by exam type
        if (!isset($yearly_by_exam[$exam_type])) $yearly_by_exam[$exam_type] = [];
        if (!isset($yearly_by_exam[$exam_type][$year])) $yearly_by_exam[$exam_type][$year] = ['passed' => 0, 'failed' => 0, 'total' => 0];
        $yearly_by_exam[$exam_type][$year]['total']++;
        if ($result === 'Passed') $yearly_by_exam[$exam_type][$year]['passed']++; else $yearly_by_exam[$exam_type][$year]['failed']++;
    }
    $passers->data_seek(0);
}

ksort($yearly_data);
foreach ($yearly_by_exam as &$years) ksort($years);

// Calculate rates
$passing_rate = $stats['total'] > 0 ? round(($stats['passed'] / $stats['total']) * 100, 1) : 0;
$first_timer_rate = $stats['first_timer'] > 0 ? round(($stats['first_timer_passed'] / $stats['first_timer']) * 100, 1) : 0;
$repeater_rate = $stats['repeater'] > 0 ? round(($stats['repeater_passed'] / $stats['repeater']) * 100, 1) : 0;

// Get year range
$years = array_keys($yearly_data);
$year_range = count($years) > 0 ? min($years) . ' - ' . max($years) : 'N/A';

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <title>College of Criminal Justice Education - Board Performance Analytics | LSPU</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
    <style>
        :root {
            /* CCJE Maroon Color Palette */
            --maroon-1: #E8B4B4;  /* Light maroon */
            --maroon-2: #D88888;  /* Pale maroon */
            --maroon-3: #991B1B;  /* Medium maroon */
            --maroon-4: #7F1D1D;  /* Dark maroon */
            --maroon-5: #FEE2E2;  /* Very light maroon */
            
            --primary: #7F1D1D;
            --primary-dark: #5C1414;
            --primary-light: #991B1B;
            --primary-lighter: #DC2626;
            --primary-bg: #FEE2E2;
            --primary-pale: #FEF2F2;
            
            --gradient: linear-gradient(135deg, #7F1D1D 0%, #991B1B 100%);
            --gradient-dark: linear-gradient(135deg, #5C1414 0%, #7F1D1D 100%);
            --gradient-light: linear-gradient(135deg, #E8B4B4 0%, #FEE2E2 100%);
            
            --text-dark: #450A0A;
            --text-medium: #7F1D1D;
            --text-light: #991B1B;
            --white: #ffffff;
            --shadow: rgba(127, 29, 29, 0.15);
            --shadow-strong: rgba(127, 29, 29, 0.25);
        }
        
        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(180deg, var(--primary-pale) 0%, var(--primary-bg) 50%, var(--primary-pale) 100%);
            color: var(--text-dark);
            min-height: 100vh;
            overflow-x: hidden;
        }
        
        /* Animated Background */
        .bg-pattern {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 0;
            opacity: 0.4;
            background-image: 
                radial-gradient(circle at 20% 80%, var(--maroon-3) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, var(--maroon-2) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, var(--maroon-5) 0%, transparent 30%);
        }
        
        /* Header */
        .header {
            background: var(--gradient-dark);
            padding: 0;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 4px 30px var(--shadow-strong);
        }
        
        .header-top {
            background: rgba(0,0,0,0.1);
            padding: 8px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.85rem;
        }
        
        .header-top span {
            color: rgba(255,255,255,0.8);
        }
        
        .header-main {
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1600px;
            margin: 0 auto;
        }
        
        .header-brand {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        
        .header-logo {
            width: 50px;
            height: 50px;
            background: rgba(255,255,255,0.2);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        
        .header-title h1 {
            color: var(--white);
            font-size: 1.4rem;
            font-weight: 700;
            letter-spacing: -0.5px;
        }
        
        .header-title p {
            color: rgba(255,255,255,0.8);
            font-size: 0.85rem;
            margin-top: 2px;
        }
        
        .header-badges {
            display: flex;
            gap: 12px;
            align-items: center;
        }
        
        .badge {
            background: rgba(255,255,255,0.15);
            color: var(--white);
            padding: 8px 16px;
            border-radius: 30px;
            font-size: 0.8rem;
            font-weight: 600;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .badge.highlight {
            background: rgba(255,255,255,0.25);
        }
        
        .back-btn {
            background: rgba(255,255,255,0.15);
            color: var(--white);
            border: 2px solid rgba(255,255,255,0.3);
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .back-btn:hover {
            background: rgba(255,255,255,0.25);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        
        /* Main Content */
        .main {
            position: relative;
            z-index: 1;
            max-width: 1600px;
            margin: 0 auto;
            padding: 32px 24px;
        }
        
        /* Section Headers */
        .section-header {
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 2px solid var(--primary-lighter);
        }
        
        .section-header h2 {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--primary-dark);
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .section-header h2 i {
            color: var(--primary);
        }
        
        .section-header p {
            color: var(--text-light);
            margin-top: 4px;
            font-size: 0.9rem;
        }
        
        /* KPI Grid */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 32px;
        }
        
        .kpi-card {
            background: var(--white);
            border-radius: 20px;
            padding: 24px;
            box-shadow: 0 4px 20px var(--shadow);
            border: 1px solid var(--primary-lighter);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .kpi-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient);
        }
        
        .kpi-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px var(--shadow-strong);
        }
        
        .kpi-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
        }
        
        .kpi-icon {
            width: 48px;
            height: 48px;
            background: var(--gradient-light);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .kpi-icon i {
            font-size: 1.3rem;
            color: var(--primary-dark);
        }
        
        .kpi-trend {
            font-size: 0.75rem;
            padding: 4px 10px;
            border-radius: 20px;
            font-weight: 600;
        }
        
        .kpi-trend.up {
            background: #dcfce7;
            color: #166534;
        }
        
        .kpi-trend.neutral {
            background: var(--primary-bg);
            color: var(--primary-dark);
        }
        
        .kpi-value {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--primary-dark);
            line-height: 1;
            margin-bottom: 8px;
        }
        
        .kpi-label {
            color: var(--text-light);
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        .kpi-sublabel {
            font-size: 0.8rem;
            color: var(--text-medium);
            margin-top: 8px;
            padding-top: 8px;
            border-top: 1px dashed var(--primary-lighter);
        }
        
        /* Charts Grid */
        .charts-section {
            margin-bottom: 40px;
        }
        
        .charts-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 24px;
        }
        
        .chart-card {
            background: var(--white);
            border-radius: 24px;
            padding: 28px;
            box-shadow: 0 4px 20px var(--shadow);
            border: 1px solid var(--primary-lighter);
            transition: all 0.3s ease;
        }
        
        .chart-card:hover {
            box-shadow: 0 8px 30px var(--shadow-strong);
        }
        
        .chart-card.full-width {
            grid-column: span 2;
        }
        
        .chart-card.triple {
            grid-column: span 1;
        }
        
        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 2px solid var(--primary-bg);
        }
        
        .chart-title-group h3 {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .chart-title-group h3 i {
            color: var(--primary);
        }
        
        .chart-title-group p {
            font-size: 0.8rem;
            color: var(--text-light);
            margin-top: 4px;
        }
        
        .chart-legend-custom {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
        }
        
        .legend-item {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.8rem;
            color: var(--text-medium);
        }
        
        .legend-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
        }
        
        .chart-container {
            position: relative;
            height: 300px;
        }
        
        .chart-container.tall {
            height: 400px;
        }
        
        /* Board Exam Comparison Cards */
        .exam-comparison-section {
            margin-bottom: 40px;
        }
        
        .exam-cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }
        
        .exam-card {
            background: var(--white);
            border-radius: 20px;
            padding: 24px;
            box-shadow: 0 4px 20px var(--shadow);
            border: 1px solid var(--primary-lighter);
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .exam-card:hover {
            transform: translateY(-5px) scale(1.02);
            box-shadow: 0 12px 40px var(--shadow-strong);
        }
        
        .exam-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
        }
        
        .exam-card-title {
            font-size: 1rem;
            font-weight: 700;
            color: var(--text-dark);
        }
        
        .exam-card-badge {
            background: var(--gradient);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
        }
        
        .exam-card-stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            margin-bottom: 16px;
        }
        
        .exam-stat {
            text-align: center;
            padding: 12px;
            background: var(--primary-pale);
            border-radius: 12px;
        }
        
        .exam-stat-value {
            font-size: 1.4rem;
            font-weight: 800;
            color: var(--primary-dark);
        }
        
        .exam-stat-label {
            font-size: 0.7rem;
            color: var(--text-light);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .exam-card-bar {
            height: 8px;
            background: #e5e7eb;
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 8px;
        }
        
        .exam-card-bar-fill {
            height: 100%;
            background: var(--gradient);
            border-radius: 4px;
            transition: width 1s ease;
        }
        
        .exam-card-footer {
            display: flex;
            justify-content: space-between;
            font-size: 0.8rem;
            color: var(--text-light);
        }
        
        /* Prediction Section */
        .prediction-section {
            background: var(--gradient-dark);
            border-radius: 24px;
            padding: 40px;
            margin-bottom: 40px;
            color: var(--white);
            position: relative;
            overflow: hidden;
        }
        
        .prediction-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 60%;
            height: 200%;
            background: radial-gradient(ellipse, rgba(255,255,255,0.1) 0%, transparent 70%);
            pointer-events: none;
        }
        
        .prediction-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
        }
        
        .prediction-title {
            font-size: 1.5rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .prediction-subtitle {
            opacity: 0.8;
            font-size: 0.9rem;
            margin-top: 4px;
        }
        
        .prediction-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
        }
        
        .prediction-card {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 24px;
            text-align: center;
            border: 1px solid rgba(255,255,255,0.15);
            transition: all 0.3s ease;
        }
        
        .prediction-card:hover {
            background: rgba(255,255,255,0.15);
            transform: translateY(-3px);
        }
        
        .prediction-icon {
            width: 48px;
            height: 48px;
            background: rgba(255,255,255,0.15);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
            font-size: 1.3rem;
        }
        
        .prediction-value {
            font-size: 2.2rem;
            font-weight: 800;
            margin-bottom: 8px;
        }
        
        .prediction-label {
            font-size: 0.85rem;
            opacity: 0.9;
        }
        
        .prediction-status {
            display: inline-block;
            margin-top: 12px;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            background: rgba(255,255,255,0.2);
        }
        
        /* Data Table */
        .table-section {
            margin-bottom: 40px;
        }
        
        .table-card {
            background: var(--white);
            border-radius: 24px;
            padding: 28px;
            box-shadow: 0 4px 20px var(--shadow);
            overflow: hidden;
        }
        
        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .table-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .table-title i {
            color: var(--primary);
        }
        
        .table-filters {
            display: flex;
            gap: 12px;
        }
        
        .filter-btn {
            background: var(--primary-bg);
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 0.85rem;
            color: var(--text-medium);
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .filter-btn:hover, .filter-btn.active {
            background: var(--primary);
            color: white;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .data-table th {
            background: var(--primary-bg);
            color: var(--primary-dark);
            padding: 14px 16px;
            text-align: left;
            font-weight: 600;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .data-table th:first-child { border-radius: 12px 0 0 12px; }
        .data-table th:last-child { border-radius: 0 12px 12px 0; }
        
        .data-table td {
            padding: 14px 16px;
            border-bottom: 1px solid var(--primary-pale);
            font-size: 0.9rem;
        }
        
        .data-table tr:hover td {
            background: var(--primary-pale);
        }
        
        .status-badge {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .status-badge.passed {
            background: var(--primary-bg);
            color: var(--primary-dark);
        }
        
        .status-badge.failed {
            background: #fee2e2;
            color: #dc2626;
        }
        
        .status-badge.conditional {
            background: #fef3c7;
            color: #d97706;
        }
        
        /* Filter Section */
        .filter-section {
            background: var(--white);
            border-radius: 24px;
            padding: 28px;
            margin-bottom: 32px;
            box-shadow: 0 4px 20px var(--shadow);
            border: 1px solid var(--primary-lighter);
        }
        
        .filter-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 2px solid var(--primary-bg);
        }
        
        .filter-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .filter-title i {
            color: var(--primary);
        }
        
        .filter-actions {
            display: flex;
            gap: 12px;
        }
        
        .export-btn {
            background: var(--gradient);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 10px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .export-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px var(--shadow-strong);
        }
        
        .export-btn.secondary {
            background: var(--primary-bg);
            color: var(--primary-dark);
        }
        
        .export-btn.secondary:hover {
            background: var(--primary-lighter);
        }
        
        .filter-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .filter-label {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-medium);
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .filter-label i {
            color: var(--primary);
            font-size: 0.8rem;
        }
        
        .filter-select, .filter-input {
            padding: 12px 16px;
            border: 2px solid var(--primary-lighter);
            border-radius: 10px;
            font-size: 0.9rem;
            color: var(--text-dark);
            background: var(--white);
            transition: all 0.2s ease;
            font-family: 'Inter', sans-serif;
        }
        
        .filter-select:focus, .filter-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px var(--primary-bg);
        }
        
        .filter-select {
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%237F1D1D' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            padding-right: 36px;
        }
        
        /* Multi-select dropdown styles */
        .multi-select-container {
            position: relative;
        }
        
        .multi-select-trigger {
            padding: 12px 16px;
            border: 2px solid var(--primary-lighter);
            border-radius: 10px;
            font-size: 0.9rem;
            color: var(--text-dark);
            background: var(--white);
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            min-height: 48px;
            transition: all 0.2s ease;
        }
        
        .multi-select-trigger:hover {
            border-color: var(--primary-light);
        }
        
        .multi-select-trigger.active {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px var(--primary-bg);
        }
        
        .multi-select-trigger .selected-text {
            flex: 1;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .multi-select-trigger .arrow {
            color: var(--primary);
            transition: transform 0.2s ease;
        }
        
        .multi-select-trigger.active .arrow,
        .arrow.open {
            transform: rotate(180deg);
        }
        
        .selected-text.has-selection {
            color: var(--primary);
            font-weight: 600;
        }
        
        .multi-select-dropdown {
            position: absolute;
            top: calc(100% + 4px);
            left: 0;
            right: 0;
            background: var(--white);
            border: 2px solid var(--primary-lighter);
            border-radius: 10px;
            box-shadow: 0 8px 24px var(--shadow-strong);
            z-index: 100;
            max-height: 250px;
            overflow-y: auto;
            display: none;
        }
        
        .multi-select-dropdown.show {
            display: block;
            animation: dropdownSlide 0.2s ease;
        }
        
        @keyframes dropdownSlide {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .multi-select-option {
            padding: 10px 16px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: background 0.15s ease;
        }
        
        .multi-select-option:hover {
            background: var(--primary-pale);
        }
        
        .multi-select-option.selected {
            background: var(--primary-bg);
        }
        
        .multi-select-option input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: var(--primary);
            cursor: pointer;
        }
        
        .multi-select-option label {
            flex: 1;
            cursor: pointer;
            font-size: 0.9rem;
        }
        
        .multi-select-all {
            border-bottom: 1px solid var(--primary-lighter);
            font-weight: 600;
            background: var(--primary-pale);
        }
        
        .selected-count {
            background: var(--primary);
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-left: 8px;
        }
        
        .filter-buttons {
            display: flex;
            gap: 12px;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px dashed var(--primary-lighter);
        }
        
        .apply-filter-btn {
            background: var(--gradient);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 10px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .apply-filter-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px var(--shadow-strong);
        }
        
        .reset-filter-btn {
            background: var(--primary-bg);
            color: var(--primary-dark);
            border: none;
            padding: 12px 24px;
            border-radius: 10px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .reset-filter-btn:hover {
            background: var(--primary-lighter);
        }
        
        .active-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 16px;
        }
        
        .filter-tag {
            background: var(--primary-bg);
            color: var(--primary-dark);
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .filter-tag .remove-tag {
            cursor: pointer;
            opacity: 0.7;
            transition: opacity 0.2s ease;
        }
        
        .filter-tag .remove-tag:hover {
            opacity: 1;
        }
        
        /* Export Modal */
        .export-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
            backdrop-filter: blur(4px);
        }
        
        .export-modal.active {
            display: flex;
        }
        
        .export-modal-content {
            background: var(--white);
            border-radius: 24px;
            padding: 32px;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: modalSlideIn 0.3s ease;
        }
        
        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-20px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        
        .export-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 2px solid var(--primary-bg);
        }
        
        .export-modal-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .export-modal-title i {
            color: var(--primary);
        }
        
        .close-modal {
            background: var(--primary-bg);
            border: none;
            width: 36px;
            height: 36px;
            border-radius: 10px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            color: var(--text-medium);
        }
        
        .close-modal:hover {
            background: var(--primary-lighter);
            color: var(--primary-dark);
        }
        
        .export-options {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }
        
        .export-option {
            background: var(--primary-pale);
            border: 2px solid transparent;
            border-radius: 16px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .export-option:hover {
            border-color: var(--primary-light);
            transform: translateY(-2px);
        }
        
        .export-option.selected {
            border-color: var(--primary);
            background: var(--primary-bg);
        }
        
        .export-option-icon {
            width: 48px;
            height: 48px;
            background: var(--white);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 12px;
            font-size: 1.3rem;
            color: var(--primary);
        }
        
        .export-option-title {
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 4px;
        }
        
        .export-option-desc {
            font-size: 0.8rem;
            color: var(--text-light);
        }
        
        .export-settings {
            background: var(--primary-pale);
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 24px;
        }
        
        .export-setting {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px 0;
        }
        
        .export-setting input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: var(--primary);
        }
        
        .export-setting label {
            font-size: 0.9rem;
            color: var(--text-medium);
        }
        
        .export-modal-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
        }
        
        /* Footer */
        .footer {
            background: var(--gradient-dark);
            color: var(--white);
            padding: 32px;
            text-align: center;
            margin-top: 40px;
        }
        
        .footer p {
            opacity: 0.9;
            font-size: 0.9rem;
        }
        
        /* Animations */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes countUp {
            from { opacity: 0; transform: scale(0.5); }
            to { opacity: 1; transform: scale(1); }
        }
        
        .kpi-card, .chart-card, .exam-card, .prediction-section, .table-card {
            animation: fadeInUp 0.6s ease forwards;
        }
        
        .kpi-card:nth-child(1) { animation-delay: 0.1s; }
        .kpi-card:nth-child(2) { animation-delay: 0.2s; }
        .kpi-card:nth-child(3) { animation-delay: 0.3s; }
        .kpi-card:nth-child(4) { animation-delay: 0.4s; }
        
        /* Responsive */
        @media (max-width: 1200px) {
            .kpi-grid { grid-template-columns: repeat(2, 1fr); }
            .prediction-grid { grid-template-columns: repeat(2, 1fr); }
            .filter-grid { grid-template-columns: repeat(2, 1fr); }
        }
        
        @media (max-width: 1024px) {
            .charts-grid { grid-template-columns: 1fr; }
            .chart-card.full-width { grid-column: span 1; }
            .export-options { grid-template-columns: 1fr; }
        }
        
        @media (max-width: 768px) {
            .header-main { flex-direction: column; gap: 16px; padding: 16px 20px; }
            .header-badges { display: none; }
            .header-top { padding: 8px 20px; font-size: 0.75rem; }
            .kpi-grid { grid-template-columns: 1fr; }
            .prediction-grid { grid-template-columns: 1fr; }
            .main { padding: 20px 16px; }
            .prediction-section { padding: 24px; }
            .filter-grid { grid-template-columns: 1fr; }
            .filter-header { flex-direction: column; gap: 16px; align-items: flex-start; }
            .filter-actions { width: 100%; }
            .filter-actions .export-btn { flex: 1; justify-content: center; }
            .filter-buttons { flex-direction: column; }
            .apply-filter-btn, .reset-filter-btn { width: 100%; justify-content: center; }
            .export-modal-content { padding: 20px; }
        }
        
        /* Interactive tooltip */
        .tooltip {
            position: absolute;
            background: var(--text-dark);
            color: white;
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 0.8rem;
            pointer-events: none;
            z-index: 1000;
            opacity: 0;
            transition: opacity 0.2s ease;
        }
        
        .tooltip.visible {
            opacity: 1;
        }
    </style>
</head>
<body>
    <div class="bg-pattern"></div>
    
    <header class="header">
        <div class="header-top">
            <span><i class="fas fa-university"></i> Laguna State Polytechnic University - San Pablo City Campus</span>
            <span><i class="fas fa-calendar"></i> Data Period: <?php echo $year_range; ?></span>
        </div>
        <div class="header-main">
            <div class="header-brand">
                <div class="header-logo">🛡️</div>
                <div class="header-title">
                    <h1>College of Criminal Justice Education</h1>
                    <p>Board Examination Performance Analytics Dashboard</p>
                </div>
            </div>
            <div class="header-badges">
                <span class="badge"><i class="fas fa-database"></i> <?php echo $stats['total']; ?> Total Records</span>
                <span class="badge highlight"><i class="fas fa-chart-line"></i> <?php echo $passing_rate; ?>% Pass Rate</span>
            </div>
            <a href="index.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Home</a>
        </div>
    </header>
    
    <main class="main">
        <!-- KPI Section -->
        <section class="kpi-section">
            <div class="section-header">
                <h2><i class="fas fa-chart-bar"></i> Key Performance Indicators</h2>
                <p>Overall board examination statistics for College of Criminal Justice Education (<?php echo $year_range; ?>)</p>
            </div>
            
            <div class="kpi-grid">
                <div class="kpi-card">
                    <div class="kpi-header">
                        <div class="kpi-icon"><i class="fas fa-users"></i></div>
                        <span class="kpi-trend neutral"><i class="fas fa-database"></i> Total</span>
                    </div>
                    <div class="kpi-value"><?php echo number_format($stats['total']); ?></div>
                    <div class="kpi-label">Total Examinees</div>
                    <div class="kpi-sublabel"><?php echo count($board_exam_stats); ?> Board Exam Types Tracked</div>
                </div>
                
                <div class="kpi-card">
                    <div class="kpi-header">
                        <div class="kpi-icon"><i class="fas fa-trophy"></i></div>
                        <span class="kpi-trend up"><i class="fas fa-arrow-up"></i> Overall</span>
                    </div>
                    <div class="kpi-value"><?php echo $passing_rate; ?>%</div>
                    <div class="kpi-label">Overall Passing Rate</div>
                    <div class="kpi-sublabel"><?php echo number_format($stats['passed']); ?> Passed / <?php echo number_format($stats['failed']); ?> Failed</div>
                </div>
                
                <div class="kpi-card">
                    <div class="kpi-header">
                        <div class="kpi-icon"><i class="fas fa-star"></i></div>
                        <span class="kpi-trend up"><i class="fas fa-user-check"></i> 1st Timer</span>
                    </div>
                    <div class="kpi-value"><?php echo $first_timer_rate; ?>%</div>
                    <div class="kpi-label">First Timer Pass Rate</div>
                    <div class="kpi-sublabel"><?php echo $stats['first_timer_passed']; ?> of <?php echo $stats['first_timer']; ?> First Timers</div>
                </div>
                
                <div class="kpi-card">
                    <div class="kpi-header">
                        <div class="kpi-icon"><i class="fas fa-redo"></i></div>
                        <span class="kpi-trend neutral"><i class="fas fa-sync"></i> Repeater</span>
                    </div>
                    <div class="kpi-value"><?php echo $repeater_rate; ?>%</div>
                    <div class="kpi-label">Repeater Pass Rate</div>
                    <div class="kpi-sublabel"><?php echo $stats['repeater_passed']; ?> of <?php echo $stats['repeater']; ?> Repeaters</div>
                </div>
            </div>
        </section>
        
        <!-- Filter & Export Section -->
        <section class="filter-section" id="filterSection">
            <div class="filter-header">
                <div class="filter-title">
                    <i class="fas fa-filter"></i> Filter & Export Data
                </div>
                <div class="filter-actions">
                    <button class="export-btn secondary" onclick="openExportModal()">
                        <i class="fas fa-download"></i> Export Data
                    </button>
                    <button class="export-btn" onclick="printDashboard()">
                        <i class="fas fa-print"></i> Print Report
                    </button>
                </div>
            </div>
            
            <div class="filter-grid">
                <!-- Year Multi-Select -->
                <div class="filter-group">
                    <label class="filter-label"><i class="fas fa-calendar"></i> Year</label>
                    <div class="multi-select-container" id="yearMultiSelect">
                        <div class="multi-select-trigger" onclick="toggleMultiSelect('yearMultiSelect')">
                            <span class="selected-text">All Years</span>
                            <i class="fas fa-chevron-down arrow"></i>
                        </div>
                        <div class="multi-select-dropdown">
                            <div class="multi-select-option multi-select-all">
                                <input type="checkbox" id="yearSelectAll" onchange="toggleSelectAll('yearMultiSelect')">
                                <label for="yearSelectAll">Select All</label>
                            </div>
                            <?php foreach (array_keys($yearly_data) as $yr): ?>
                            <div class="multi-select-option">
                                <input type="checkbox" id="year_<?php echo $yr; ?>" value="<?php echo $yr; ?>" onchange="updateMultiSelect('yearMultiSelect')">
                                <label for="year_<?php echo $yr; ?>"><?php echo $yr; ?></label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Board Exam Type Multi-Select -->
                <div class="filter-group">
                    <label class="filter-label"><i class="fas fa-clipboard-list"></i> Board Exam Type</label>
                    <div class="multi-select-container" id="examTypeMultiSelect">
                        <div class="multi-select-trigger" onclick="toggleMultiSelect('examTypeMultiSelect')">
                            <span class="selected-text">All Exam Types</span>
                            <i class="fas fa-chevron-down arrow"></i>
                        </div>
                        <div class="multi-select-dropdown">
                            <div class="multi-select-option multi-select-all">
                                <input type="checkbox" id="examTypeSelectAll" onchange="toggleSelectAll('examTypeMultiSelect')">
                                <label for="examTypeSelectAll">Select All</label>
                            </div>
                            <?php foreach (array_keys($board_exam_stats) as $exam): ?>
                            <div class="multi-select-option">
                                <input type="checkbox" id="exam_<?php echo md5($exam); ?>" value="<?php echo htmlspecialchars($exam); ?>" onchange="updateMultiSelect('examTypeMultiSelect')">
                                <label for="exam_<?php echo md5($exam); ?>"><?php echo htmlspecialchars($exam); ?></label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Result Multi-Select -->
                <div class="filter-group">
                    <label class="filter-label"><i class="fas fa-check-circle"></i> Result</label>
                    <div class="multi-select-container" id="resultMultiSelect">
                        <div class="multi-select-trigger" onclick="toggleMultiSelect('resultMultiSelect')">
                            <span class="selected-text">All Results</span>
                            <i class="fas fa-chevron-down arrow"></i>
                        </div>
                        <div class="multi-select-dropdown">
                            <div class="multi-select-option multi-select-all">
                                <input type="checkbox" id="resultSelectAll" onchange="toggleSelectAll('resultMultiSelect')">
                                <label for="resultSelectAll">Select All</label>
                            </div>
                            <div class="multi-select-option">
                                <input type="checkbox" id="result_passed" value="Passed" onchange="updateMultiSelect('resultMultiSelect')">
                                <label for="result_passed">Passed</label>
                            </div>
                            <div class="multi-select-option">
                                <input type="checkbox" id="result_failed" value="Failed" onchange="updateMultiSelect('resultMultiSelect')">
                                <label for="result_failed">Failed</label>
                            </div>
                            <div class="multi-select-option">
                                <input type="checkbox" id="result_conditional" value="Conditional" onchange="updateMultiSelect('resultMultiSelect')">
                                <label for="result_conditional">Conditional</label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Attempt Type Multi-Select -->
                <div class="filter-group">
                    <label class="filter-label"><i class="fas fa-user"></i> Attempt Type</label>
                    <div class="multi-select-container" id="attemptMultiSelect">
                        <div class="multi-select-trigger" onclick="toggleMultiSelect('attemptMultiSelect')">
                            <span class="selected-text">All Types</span>
                            <i class="fas fa-chevron-down arrow"></i>
                        </div>
                        <div class="multi-select-dropdown">
                            <div class="multi-select-option multi-select-all">
                                <input type="checkbox" id="attemptSelectAll" onchange="toggleSelectAll('attemptMultiSelect')">
                                <label for="attemptSelectAll">Select All</label>
                            </div>
                            <div class="multi-select-option">
                                <input type="checkbox" id="attempt_first" value="First Timer" onchange="updateMultiSelect('attemptMultiSelect')">
                                <label for="attempt_first">First Timer</label>
                            </div>
                            <div class="multi-select-option">
                                <input type="checkbox" id="attempt_repeater" value="Repeater" onchange="updateMultiSelect('attemptMultiSelect')">
                                <label for="attempt_repeater">Repeater</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="filter-buttons">
                <button class="apply-filter-btn" onclick="applyFilters()">
                    <i class="fas fa-search"></i> Apply Filters
                </button>
                <button class="reset-filter-btn" onclick="resetFilters()">
                    <i class="fas fa-undo"></i> Reset Filters
                </button>
            </div>
            
            <div class="active-filters" id="activeFilters" style="display: none;">
                <!-- Active filter tags will be added here dynamically -->
            </div>
        </section>
        
        <!-- Board Exam Types Comparison -->
        <section class="exam-comparison-section">
            <div class="section-header">
                <h2><i class="fas fa-clipboard-list"></i> Board Examination Types Performance</h2>
                <p>Comparative analysis of different CCJE licensure examinations</p>
            </div>
            
            <div class="exam-cards-grid">
                <?php foreach ($board_exam_stats as $exam_name => $exam_data): 
                    $exam_rate = $exam_data['total'] > 0 ? round(($exam_data['passed'] / $exam_data['total']) * 100, 1) : 0;
                    $ft_rate = $exam_data['first_timer'] > 0 ? round(($exam_data['ft_passed'] / $exam_data['first_timer']) * 100, 1) : 0;
                    $rp_rate = $exam_data['repeater'] > 0 ? round(($exam_data['rp_passed'] / $exam_data['repeater']) * 100, 1) : 0;
                ?>
                <div class="exam-card" data-exam="<?php echo htmlspecialchars($exam_name); ?>">
                    <div class="exam-card-header">
                        <div class="exam-card-title"><?php echo htmlspecialchars($exam_name); ?></div>
                        <span class="exam-card-badge"><?php echo $exam_data['total']; ?> Records</span>
                    </div>
                    <div class="exam-card-stats">
                        <div class="exam-stat">
                            <div class="exam-stat-value"><?php echo $exam_rate; ?>%</div>
                            <div class="exam-stat-label">Pass Rate</div>
                        </div>
                        <div class="exam-stat">
                            <div class="exam-stat-value"><?php echo $exam_data['passed']; ?></div>
                            <div class="exam-stat-label">Passed</div>
                        </div>
                        <div class="exam-stat">
                            <div class="exam-stat-value"><?php echo $ft_rate; ?>%</div>
                            <div class="exam-stat-label">1st Timer Rate</div>
                        </div>
                        <div class="exam-stat">
                            <div class="exam-stat-value"><?php echo $rp_rate; ?>%</div>
                            <div class="exam-stat-label">Repeater Rate</div>
                        </div>
                    </div>
                    <div class="exam-card-bar">
                        <div class="exam-card-bar-fill" style="width: <?php echo $exam_rate; ?>%"></div>
                    </div>
                    <div class="exam-card-footer">
                        <span><i class="fas fa-check-circle"></i> <?php echo $exam_data['passed']; ?> Passed</span>
                        <span><i class="fas fa-times-circle"></i> <?php echo $exam_data['failed']; ?> Failed</span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        
        <!-- Charts Section -->
        <section class="charts-section">
            <div class="section-header">
                <h2><i class="fas fa-chart-pie"></i> Interactive Data Visualizations</h2>
                <p>Comprehensive analysis of board examination performance trends and distributions</p>
            </div>
            
            <div class="charts-grid">
                <!-- Overall Results Distribution -->
                <div class="chart-card">
                    <div class="chart-header">
                        <div class="chart-title-group">
                            <h3><i class="fas fa-chart-pie"></i> Overall Results Distribution</h3>
                            <p>Pass/Fail breakdown for all CCJE board exams (<?php echo $year_range; ?>)</p>
                        </div>
                    </div>
                    <div class="chart-container">
                        <canvas id="resultsChart"></canvas>
                    </div>
                </div>
                
                <!-- Board Exam Comparison -->
                <div class="chart-card">
                    <div class="chart-header">
                        <div class="chart-title-group">
                            <h3><i class="fas fa-balance-scale"></i> Board Exam Types Comparison</h3>
                            <p>Passing rate comparison across different licensure examinations</p>
                        </div>
                    </div>
                    <div class="chart-container">
                        <canvas id="examComparisonChart"></canvas>
                    </div>
                </div>
                
                <!-- Yearly Trend -->
                <div class="chart-card full-width">
                    <div class="chart-header">
                        <div class="chart-title-group">
                            <h3><i class="fas fa-chart-line"></i> Yearly Performance Trend</h3>
                            <p>Historical passing rate trends across all CCJE board examinations</p>
                        </div>
                        <div class="chart-legend-custom">
                            <div class="legend-item"><span class="legend-dot" style="background: #7F1D1D;"></span> Passed</div>
                            <div class="legend-item"><span class="legend-dot" style="background: #94a3b8;"></span> Failed</div>
                            <div class="legend-item"><span class="legend-dot" style="background: #991B1B;"></span> Pass Rate %</div>
                        </div>
                    </div>
                    <div class="chart-container tall">
                        <canvas id="yearlyTrendChart"></canvas>
                    </div>
                </div>
                
                <!-- First Timer vs Repeater -->
                <div class="chart-card">
                    <div class="chart-header">
                        <div class="chart-title-group">
                            <h3><i class="fas fa-users"></i> First Timer vs Repeater Performance</h3>
                            <p>Comparative analysis by examination attempt type</p>
                        </div>
                    </div>
                    <div class="chart-container">
                        <canvas id="attemptChart"></canvas>
                    </div>
                </div>
                
                <!-- Exam Type Breakdown -->
                <div class="chart-card">
                    <div class="chart-header">
                        <div class="chart-title-group">
                            <h3><i class="fas fa-th-large"></i> Examinees by Board Exam Type</h3>
                            <p>Distribution of examinees across different CCJE boards</p>
                        </div>
                    </div>
                    <div class="chart-container">
                        <canvas id="examDistributionChart"></canvas>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- AI Prediction Section -->
        <section class="prediction-section">
            <div class="prediction-header">
                <div>
                    <div class="prediction-title"><i class="fas fa-brain"></i> AI-Powered Performance Predictions</div>
                    <div class="prediction-subtitle">Machine learning analysis based on historical board examination data</div>
                </div>
            </div>
            
            <div class="prediction-grid">
                <div class="prediction-card">
                    <div class="prediction-icon"><i class="fas fa-chart-line"></i></div>
                    <div class="prediction-value" id="pred-next-rate">--</div>
                    <div class="prediction-label">Predicted Next Exam Pass Rate</div>
                    <span class="prediction-status" id="pred-trend"><i class="fas fa-spinner fa-spin"></i> Analyzing...</span>
                </div>
                
                <div class="prediction-card">
                    <div class="prediction-icon"><i class="fas fa-star"></i></div>
                    <div class="prediction-value" id="pred-first-timer">--</div>
                    <div class="prediction-label">Expected First Timer Rate</div>
                    <span class="prediction-status"><i class="fas fa-lightbulb"></i> Projected</span>
                </div>
                
                <div class="prediction-card">
                    <div class="prediction-icon"><i class="fas fa-users"></i></div>
                    <div class="prediction-value" id="pred-examinees">--</div>
                    <div class="prediction-label">Expected Examinees</div>
                    <span class="prediction-status"><i class="fas fa-calculator"></i> Estimated</span>
                </div>
                
                <div class="prediction-card">
                    <div class="prediction-icon"><i class="fas fa-check-circle"></i></div>
                    <div class="prediction-value" id="pred-confidence">--</div>
                    <div class="prediction-label">Model Confidence</div>
                    <span class="prediction-status"><i class="fas fa-robot"></i> AI Powered</span>
                </div>
            </div>
        </section>
        
        <!-- Recent Data Table -->
        <section class="table-section">
            <div class="table-card">
                <div class="table-header">
                    <div class="table-title"><i class="fas fa-table"></i> Recent Board Examination Results</div>
                    <div class="table-filters">
                        <button class="filter-btn active" onclick="filterTable('all')">All Results</button>
                        <button class="filter-btn" onclick="filterTable('passed')">Passed Only</button>
                        <button class="filter-btn" onclick="filterTable('failed')">Failed Only</button>
                    </div>
                </div>
                <table class="data-table" id="resultsTable">
                    <thead>
                        <tr>
                            <th>Board Exam Type</th>
                            <th>Exam Date</th>
                            <th>Attempt Type</th>
                            <th>Result</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $passers->data_seek(0);
                        $count = 0;
                        while ($row = $passers->fetch_assoc()) {
                            if (isset($row['is_deleted']) && $row['is_deleted'] == 1) continue;
                            if ($count >= 15) break;
                            $count++;
                            $statusClass = strtolower($row['result']);
                            if ($statusClass === 'conditional') $statusClass = 'conditional';
                            elseif ($statusClass !== 'passed') $statusClass = 'failed';
                        ?>
                        <tr data-result="<?php echo strtolower($row['result']); ?>">
                            <td><strong><?php echo htmlspecialchars($row['board_exam_type'] ?? 'N/A'); ?></strong></td>
                            <td><?php echo date('M d, Y', strtotime($row['board_exam_date'])); ?></td>
                            <td><?php echo htmlspecialchars($row['exam_type']); ?></td>
                            <td><span class="status-badge <?php echo $statusClass; ?>"><?php echo htmlspecialchars($row['result']); ?></span></td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
    
    <!-- Export Modal -->
    <div class="export-modal" id="exportModal">
        <div class="export-modal-content">
            <div class="export-modal-header">
                <div class="export-modal-title">
                    <i class="fas fa-download"></i> Export Data
                </div>
                <button class="close-modal" onclick="closeExportModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="export-options">
                <div class="export-option selected" data-format="csv" onclick="selectExportFormat('csv')">
                    <div class="export-option-icon"><i class="fas fa-file-csv"></i></div>
                    <div class="export-option-title">CSV File</div>
                    <div class="export-option-desc">Excel compatible spreadsheet</div>
                </div>
                <div class="export-option" data-format="json" onclick="selectExportFormat('json')">
                    <div class="export-option-icon"><i class="fas fa-file-code"></i></div>
                    <div class="export-option-title">JSON File</div>
                    <div class="export-option-desc">Structured data format</div>
                </div>
                <div class="export-option" data-format="pdf" onclick="selectExportFormat('pdf')">
                    <div class="export-option-icon"><i class="fas fa-file-pdf"></i></div>
                    <div class="export-option-title">PDF Report</div>
                    <div class="export-option-desc">Printable document</div>
                </div>
                <div class="export-option" data-format="excel" onclick="selectExportFormat('excel')">
                    <div class="export-option-icon"><i class="fas fa-file-excel"></i></div>
                    <div class="export-option-title">Excel File</div>
                    <div class="export-option-desc">Native Excel format</div>
                </div>
            </div>
            
            <div class="export-settings">
                <div class="export-setting">
                    <input type="checkbox" id="exportFiltered" checked>
                    <label for="exportFiltered">Export only filtered data</label>
                </div>
                <div class="export-setting">
                    <input type="checkbox" id="exportStats" checked>
                    <label for="exportStats">Include statistics summary</label>
                </div>
                <div class="export-setting">
                    <input type="checkbox" id="exportCharts">
                    <label for="exportCharts">Include chart images (PDF only)</label>
                </div>
            </div>
            
            <div class="export-modal-actions">
                <button class="reset-filter-btn" onclick="closeExportModal()">Cancel</button>
                <button class="apply-filter-btn" onclick="executeExport()">
                    <i class="fas fa-download"></i> Download
                </button>
            </div>
        </div>
    </div>
    
    <footer class="footer">
        <p>&copy; 2024 Laguna State Polytechnic University - San Pablo City Campus</p>
        <p style="margin-top: 8px; opacity: 0.7; font-size: 0.8rem;">College of Criminal Justice Education Board Performance Analytics Dashboard</p>
    </footer>
    
    <script>
        // Chart.js Configuration
        Chart.defaults.font.family = "'Inter', sans-serif";
        Chart.defaults.font.size = 12;
        
        // Color Palette (Maroon)
        const colors = {
            primary: '#7F1D1D',
            primaryDark: '#5C1414',
            primaryLight: '#991B1B',
            primaryLighter: '#DC2626',
            primaryBg: '#FEE2E2',
            gray: '#94a3b8',
            red: '#ef4444',
            yellow: '#f59e0b'
        };
        
        // 1. Results Distribution Chart (Doughnut)
        new Chart(document.getElementById('resultsChart').getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['Passed', 'Failed', 'Conditional'],
                datasets: [{
                    data: [<?php echo $stats['passed']; ?>, <?php echo $stats['failed']; ?>, <?php echo $stats['conditional']; ?>],
                    backgroundColor: [colors.primary, colors.gray, colors.yellow],
                    borderWidth: 0,
                    hoverOffset: 15
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { padding: 20, usePointStyle: true, font: { size: 12, weight: '500' } }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((context.raw / total) * 100).toFixed(1);
                                return `${context.label}: ${context.raw} (${percentage}%)`;
                            }
                        }
                    }
                },
                cutout: '60%'
            }
        });
        
        // 2. Board Exam Types Comparison (Horizontal Bar)
        const examTypes = <?php echo json_encode(array_keys($board_exam_stats)); ?>;
        const examPassRates = <?php echo json_encode(array_map(function($d) { return $d['total'] > 0 ? round(($d['passed'] / $d['total']) * 100, 1) : 0; }, $board_exam_stats)); ?>;
        
        new Chart(document.getElementById('examComparisonChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: examTypes,
                datasets: [{
                    label: 'Passing Rate (%)',
                    data: Object.values(examPassRates),
                    backgroundColor: examTypes.map((_, i) => {
                        const shades = [colors.primary, colors.primaryLight, colors.primaryLighter, colors.primaryDark, '#7A9B79'];
                        return shades[i % shades.length];
                    }),
                    borderRadius: 8,
                    borderSkipped: false
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `Pass Rate: ${context.raw}%`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        max: 100,
                        grid: { color: 'rgba(0,0,0,0.05)' },
                        ticks: { callback: value => value + '%' }
                    },
                    y: {
                        grid: { display: false },
                        ticks: { font: { weight: '500' } }
                    }
                }
            }
        });
        
        // 3. Yearly Trend Chart (Mixed - Bar + Line)
        const yearlyLabels = <?php echo json_encode(array_keys($yearly_data)); ?>;
        const yearlyPassed = <?php echo json_encode(array_column($yearly_data, 'passed')); ?>;
        const yearlyFailed = <?php echo json_encode(array_column($yearly_data, 'failed')); ?>;
        const yearlyRates = <?php echo json_encode(array_map(function($d) { return $d['total'] > 0 ? round(($d['passed'] / $d['total']) * 100, 1) : 0; }, $yearly_data)); ?>;
        
        new Chart(document.getElementById('yearlyTrendChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: yearlyLabels,
                datasets: [
                    {
                        label: 'Passed',
                        data: yearlyPassed,
                        backgroundColor: colors.primary,
                        borderRadius: 6,
                        order: 2
                    },
                    {
                        label: 'Failed',
                        data: yearlyFailed,
                        backgroundColor: colors.gray,
                        borderRadius: 6,
                        order: 3
                    },
                    {
                        label: 'Pass Rate (%)',
                        data: Object.values(yearlyRates),
                        type: 'line',
                        borderColor: colors.primaryLight,
                        backgroundColor: 'rgba(153, 27, 27, 0.2)',
                        fill: true,
                        tension: 0.4,
                        pointRadius: 6,
                        pointHoverRadius: 8,
                        pointBackgroundColor: colors.primaryLight,
                        yAxisID: 'y1',
                        order: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { intersect: false, mode: 'index' },
                plugins: {
                    legend: { position: 'bottom', labels: { padding: 20, usePointStyle: true } },
                    tooltip: {
                        callbacks: {
                            afterBody: function(context) {
                                const idx = context[0].dataIndex;
                                const total = yearlyPassed[idx] + yearlyFailed[idx];
                                return `Total Examinees: ${total}`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        position: 'left',
                        grid: { color: 'rgba(0,0,0,0.05)' },
                        title: { display: true, text: 'Number of Examinees' }
                    },
                    y1: {
                        beginAtZero: true,
                        max: 100,
                        position: 'right',
                        grid: { display: false },
                        title: { display: true, text: 'Pass Rate (%)' },
                        ticks: { callback: value => value + '%' }
                    },
                    x: { grid: { display: false } }
                }
            }
        });
        
        // 4. First Timer vs Repeater Chart
        new Chart(document.getElementById('attemptChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: ['First Timer', 'Repeater'],
                datasets: [
                    {
                        label: 'Passed',
                        data: [<?php echo $stats['first_timer_passed']; ?>, <?php echo $stats['repeater_passed']; ?>],
                        backgroundColor: colors.primary,
                        borderRadius: 8
                    },
                    {
                        label: 'Failed',
                        data: [<?php echo $stats['first_timer'] - $stats['first_timer_passed']; ?>, <?php echo $stats['repeater'] - $stats['repeater_passed']; ?>],
                        backgroundColor: colors.gray,
                        borderRadius: 8
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { padding: 20, usePointStyle: true } },
                    tooltip: {
                        callbacks: {
                            afterBody: function(context) {
                                const label = context[0].label;
                                if (label === 'First Timer') return `Pass Rate: <?php echo $first_timer_rate; ?>%`;
                                return `Pass Rate: <?php echo $repeater_rate; ?>%`;
                            }
                        }
                    }
                },
                scales: {
                    y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' } },
                    x: { grid: { display: false } }
                }
            }
        });
        
        // 5. Exam Distribution Chart (Polar Area)
        const examTotals = <?php echo json_encode(array_column($board_exam_stats, 'total')); ?>;
        
        new Chart(document.getElementById('examDistributionChart').getContext('2d'), {
            type: 'polarArea',
            data: {
                labels: examTypes,
                datasets: [{
                    data: Object.values(examTotals),
                    backgroundColor: [
                        'rgba(127, 29, 29, 0.8)',
                        'rgba(153, 27, 27, 0.8)',
                        'rgba(220, 38, 38, 0.8)',
                        'rgba(92, 20, 20, 0.8)',
                        'rgba(185, 28, 28, 0.8)',
                        'rgba(239, 68, 68, 0.8)'
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { padding: 15, usePointStyle: true, font: { size: 11 } } }
                }
            }
        });
        
        // Table Filter Function
        function filterTable(filter) {
            const rows = document.querySelectorAll('#resultsTable tbody tr');
            const buttons = document.querySelectorAll('.filter-btn');
            
            buttons.forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
            
            rows.forEach(row => {
                const result = row.dataset.result;
                if (filter === 'all' || result === filter) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }
        
        // Fetch AI Predictions
        async function fetchPredictions() {
            try {
                const response = await fetch('http://localhost:5001/api/predict', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ department: 'Criminal Justice Education', historical_rate: <?php echo $passing_rate; ?> })
                });
                
                if (response.ok) {
                    const data = await response.json();
                    document.getElementById('pred-next-rate').textContent = (data.predicted_pass_rate || <?php echo $passing_rate; ?>).toFixed(1) + '%';
                    document.getElementById('pred-first-timer').textContent = (data.first_timer_prediction || <?php echo $first_timer_rate; ?>).toFixed(1) + '%';
                    document.getElementById('pred-examinees').textContent = data.expected_examinees || Math.round(<?php echo $stats['total']; ?> / Math.max(1, Object.keys(yearlyLabels).length));
                    document.getElementById('pred-confidence').textContent = (data.confidence || 82).toFixed(0) + '%';
                    document.getElementById('pred-trend').innerHTML = '<i class="fas fa-check"></i> Analysis Complete';
                } else {
                    throw new Error('API unavailable');
                }
            } catch (error) {
                // Fallback to historical data
                document.getElementById('pred-next-rate').textContent = '<?php echo $passing_rate; ?>%';
                document.getElementById('pred-first-timer').textContent = '<?php echo $first_timer_rate; ?>%';
                document.getElementById('pred-examinees').textContent = '~' + Math.round(<?php echo $stats['total']; ?> / Math.max(1, <?php echo count($yearly_data); ?>));
                document.getElementById('pred-confidence').textContent = '75%';
                document.getElementById('pred-trend').innerHTML = '<i class="fas fa-history"></i> Based on Historical Data';
            }
        }
        
        // Animate exam cards on scroll
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, { threshold: 0.1 });
        
        document.querySelectorAll('.exam-card').forEach(card => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            card.style.transition = 'all 0.5s ease';
            observer.observe(card);
        });
        
        // Initialize predictions
        fetchPredictions();
        
        // Add interactivity to exam cards
        document.querySelectorAll('.exam-card').forEach(card => {
            card.addEventListener('click', function() {
                const examName = this.dataset.exam;
                alert(`Detailed analytics for ${examName} coming soon!`);
            });
        });
        
        // ==================== FILTER & EXPORT FUNCTIONS ====================
        
        // Store all data for filtering
        const allData = <?php 
            $passers->data_seek(0);
            $all_records = [];
            while ($row = $passers->fetch_assoc()) {
                if (isset($row['is_deleted']) && $row['is_deleted'] == 1) continue;
                $all_records[] = [
                    'board_exam_type' => $row['board_exam_type'] ?? 'N/A',
                    'board_exam_date' => $row['board_exam_date'],
                    'year' => date('Y', strtotime($row['board_exam_date'])),
                    'exam_type' => $row['exam_type'],
                    'result' => $row['result']
                ];
            }
            echo json_encode($all_records);
        ?>;
        
        let filteredData = [...allData];
        let selectedExportFormat = 'csv';
        
        // ==================== MULTI-SELECT FUNCTIONS ====================
        
        // Toggle multi-select dropdown visibility
        function toggleMultiSelect(containerId) {
            const container = document.getElementById(containerId);
            const dropdown = container.querySelector('.multi-select-dropdown');
            const arrow = container.querySelector('.arrow');
            
            // Close all other dropdowns first
            document.querySelectorAll('.multi-select-container').forEach(c => {
                if (c.id !== containerId) {
                    c.querySelector('.multi-select-dropdown').classList.remove('show');
                    c.querySelector('.arrow')?.classList.remove('open');
                }
            });
            
            dropdown.classList.toggle('show');
            arrow?.classList.toggle('open');
        }
        
        // Toggle select all option
        function toggleSelectAll(containerId) {
            const container = document.getElementById(containerId);
            const selectAllCheckbox = container.querySelector('.multi-select-all input');
            const optionCheckboxes = container.querySelectorAll('.multi-select-option:not(.multi-select-all) input');
            
            optionCheckboxes.forEach(cb => {
                cb.checked = selectAllCheckbox.checked;
            });
            
            updateMultiSelect(containerId);
        }
        
        // Update multi-select display when options change
        function updateMultiSelect(containerId) {
            const container = document.getElementById(containerId);
            const selectedText = container.querySelector('.selected-text');
            const checkboxes = container.querySelectorAll('.multi-select-option:not(.multi-select-all) input:checked');
            const allCheckboxes = container.querySelectorAll('.multi-select-option:not(.multi-select-all) input');
            const selectAllCheckbox = container.querySelector('.multi-select-all input');
            
            // Update select all state
            if (selectAllCheckbox) {
                selectAllCheckbox.checked = checkboxes.length === allCheckboxes.length && allCheckboxes.length > 0;
            }
            
            // Update display text
            if (checkboxes.length === 0) {
                selectedText.textContent = getDefaultText(containerId);
                selectedText.classList.remove('has-selection');
            } else if (checkboxes.length === allCheckboxes.length) {
                selectedText.textContent = 'All Selected';
                selectedText.classList.add('has-selection');
            } else if (checkboxes.length <= 2) {
                selectedText.textContent = Array.from(checkboxes).map(cb => cb.value).join(', ');
                selectedText.classList.add('has-selection');
            } else {
                selectedText.textContent = `${checkboxes.length} selected`;
                selectedText.classList.add('has-selection');
            }
        }
        
        // Get default placeholder text for each filter
        function getDefaultText(containerId) {
            const defaults = {
                'yearMultiSelect': 'All Years',
                'examTypeMultiSelect': 'All Exam Types',
                'resultMultiSelect': 'All Results',
                'attemptMultiSelect': 'All Types'
            };
            return defaults[containerId] || 'Select...';
        }
        
        // Get selected values from a multi-select container
        function getSelectedValues(containerId) {
            const container = document.getElementById(containerId);
            const checkboxes = container.querySelectorAll('.multi-select-option:not(.multi-select-all) input:checked');
            return Array.from(checkboxes).map(cb => cb.value);
        }
        
        // Close dropdowns when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.multi-select-container')) {
                document.querySelectorAll('.multi-select-dropdown.show').forEach(d => {
                    d.classList.remove('show');
                });
                document.querySelectorAll('.arrow.open').forEach(a => {
                    a.classList.remove('open');
                });
            }
        });
        
        // Apply Filters (Multi-Select Version)
        function applyFilters() {
            const years = getSelectedValues('yearMultiSelect');
            const examTypes = getSelectedValues('examTypeMultiSelect');
            const results = getSelectedValues('resultMultiSelect');
            const attempts = getSelectedValues('attemptMultiSelect');
            
            filteredData = allData.filter(item => {
                // If array is empty, don't filter by that criteria
                if (years.length > 0 && !years.includes(item.year)) return false;
                if (examTypes.length > 0 && !examTypes.includes(item.board_exam_type)) return false;
                if (results.length > 0 && !results.includes(item.result)) return false;
                if (attempts.length > 0 && !attempts.includes(item.exam_type)) return false;
                return true;
            });
            
            // Update table
            updateTable(filteredData);
            
            // Update active filters display
            updateActiveFilters({ years, examTypes, results, attempts });
            
            // Show notification
            showNotification(`Found ${filteredData.length} records matching your criteria`);
        }
        
        // Reset Filters (Multi-Select Version)
        function resetFilters() {
            // Uncheck all checkboxes
            document.querySelectorAll('.multi-select-container input[type="checkbox"]').forEach(cb => {
                cb.checked = false;
            });
            
            // Reset all display texts
            ['yearMultiSelect', 'examTypeMultiSelect', 'resultMultiSelect', 'attemptMultiSelect'].forEach(id => {
                updateMultiSelect(id);
            });
            
            filteredData = [...allData];
            updateTable(filteredData);
            
            document.getElementById('activeFilters').style.display = 'none';
            document.getElementById('activeFilters').innerHTML = '';
            
            showNotification('Filters reset successfully');
        }
        
        // Update Table with filtered data
        function updateTable(data) {
            const tbody = document.querySelector('#resultsTable tbody');
            tbody.innerHTML = '';
            
            const displayData = data.slice(0, 50); // Show first 50 records
            
            displayData.forEach(item => {
                const tr = document.createElement('tr');
                tr.dataset.result = item.result.toLowerCase();
                
                let statusClass = item.result.toLowerCase();
                if (statusClass === 'conditional') statusClass = 'conditional';
                else if (statusClass !== 'passed') statusClass = 'failed';
                
                tr.innerHTML = `
                    <td><strong>${item.board_exam_type}</strong></td>
                    <td>${new Date(item.board_exam_date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })}</td>
                    <td>${item.exam_type}</td>
                    <td><span class="status-badge ${statusClass}">${item.result}</span></td>
                `;
                tbody.appendChild(tr);
            });
            
            if (data.length > 50) {
                const tr = document.createElement('tr');
                tr.innerHTML = `<td colspan="4" style="text-align: center; color: var(--text-light); font-style: italic;">Showing 50 of ${data.length} records. Export to see all data.</td>`;
                tbody.appendChild(tr);
            }
        }
        
        // Update Active Filters Display (Multi-Select Version)
        function updateActiveFilters(filters) {
            const container = document.getElementById('activeFilters');
            container.innerHTML = '';
            
            const filterLabels = {
                years: 'Year',
                examTypes: 'Exam Type',
                results: 'Result',
                attempts: 'Attempt'
            };
            
            const filterIds = {
                years: 'yearMultiSelect',
                examTypes: 'examTypeMultiSelect',
                results: 'resultMultiSelect',
                attempts: 'attemptMultiSelect'
            };
            
            let hasFilters = false;
            
            Object.entries(filters).forEach(([key, values]) => {
                if (values && values.length > 0) {
                    hasFilters = true;
                    values.forEach(value => {
                        const tag = document.createElement('span');
                        tag.className = 'filter-tag';
                        tag.innerHTML = `${filterLabels[key]}: ${value} <i class="fas fa-times remove-tag" onclick="removeFilterTag('${filterIds[key]}', '${value}')"></i>`;
                        container.appendChild(tag);
                    });
                }
            });
            
            container.style.display = hasFilters ? 'flex' : 'none';
        }
        
        // Remove single filter tag (Multi-Select Version)
        function removeFilterTag(containerId, value) {
            const container = document.getElementById(containerId);
            const checkbox = container.querySelector(`input[value="${value}"]`);
            if (checkbox) {
                checkbox.checked = false;
                updateMultiSelect(containerId);
            }
            applyFilters();
        }
        
        // Export Modal Functions
        function openExportModal() {
            document.getElementById('exportModal').classList.add('active');
        }
        
        function closeExportModal() {
            document.getElementById('exportModal').classList.remove('active');
        }
        
        function selectExportFormat(format) {
            selectedExportFormat = format;
            document.querySelectorAll('.export-option').forEach(opt => {
                opt.classList.remove('selected');
            });
            document.querySelector(`[data-format="${format}"]`).classList.add('selected');
        }
        
        // Execute Export
        function executeExport() {
            const useFiltered = document.getElementById('exportFiltered').checked;
            const includeStats = document.getElementById('exportStats').checked;
            const dataToExport = useFiltered ? filteredData : allData;
            
            switch (selectedExportFormat) {
                case 'csv':
                    exportCSV(dataToExport, includeStats);
                    break;
                case 'json':
                    exportJSON(dataToExport, includeStats);
                    break;
                case 'excel':
                    exportExcel(dataToExport, includeStats);
                    break;
                case 'pdf':
                    exportPDF(dataToExport, includeStats);
                    break;
            }
            
            closeExportModal();
            showNotification(`Exporting ${dataToExport.length} records as ${selectedExportFormat.toUpperCase()}`);
        }
        
        // Export to CSV
        function exportCSV(data, includeStats) {
            let csv = '';
            
            if (includeStats) {
                csv += 'COLLEGE OF CRIMINAL JUSTICE EDUCATION - BOARD EXAMINATION STATISTICS\n';
                csv += `Generated: ${new Date().toLocaleString()}\n`;
                csv += `Total Records: ${data.length}\n`;
                const passed = data.filter(d => d.result === 'Passed').length;
                const failed = data.filter(d => d.result === 'Failed').length;
                csv += `Passed: ${passed}, Failed: ${failed}, Pass Rate: ${((passed / data.length) * 100).toFixed(1)}%\n\n`;
            }
            
            csv += 'Board Exam Type,Exam Date,Year,Attempt Type,Result\n';
            data.forEach(item => {
                csv += `"${item.board_exam_type}","${item.board_exam_date}","${item.year}","${item.exam_type}","${item.result}"\n`;
            });
            
            downloadFile(csv, 'ccje_board_exam_data.csv', 'text/csv');
        }
        
        // Export to JSON
        function exportJSON(data, includeStats) {
            const exportData = {
                department: 'College of Criminal Justice Education',
                generated: new Date().toISOString(),
                totalRecords: data.length,
                records: data
            };
            
            if (includeStats) {
                const passed = data.filter(d => d.result === 'Passed').length;
                const failed = data.filter(d => d.result === 'Failed').length;
                exportData.statistics = {
                    passed,
                    failed,
                    passRate: ((passed / data.length) * 100).toFixed(1) + '%'
                };
            }
            
            downloadFile(JSON.stringify(exportData, null, 2), 'ccje_board_exam_data.json', 'application/json');
        }
        
        // Export to Excel (CSV with BOM for Excel compatibility)
        function exportExcel(data, includeStats) {
            let csv = '\uFEFF'; // BOM for Excel
            
            if (includeStats) {
                csv += 'COLLEGE OF CRIMINAL JUSTICE EDUCATION - BOARD EXAMINATION STATISTICS\n';
                csv += `Generated:,${new Date().toLocaleString()}\n`;
                csv += `Total Records:,${data.length}\n`;
                const passed = data.filter(d => d.result === 'Passed').length;
                const failed = data.filter(d => d.result === 'Failed').length;
                csv += `Passed:,${passed}\n`;
                csv += `Failed:,${failed}\n`;
                csv += `Pass Rate:,${((passed / data.length) * 100).toFixed(1)}%\n\n`;
            }
            
            csv += 'Board Exam Type,Exam Date,Year,Attempt Type,Result\n';
            data.forEach(item => {
                csv += `"${item.board_exam_type}","${item.board_exam_date}","${item.year}","${item.exam_type}","${item.result}"\n`;
            });
            
            downloadFile(csv, 'ccje_board_exam_data.xlsx', 'application/vnd.ms-excel');
        }
        
        // Export to PDF (generates printable HTML)
        function exportPDF(data, includeStats) {
            const passed = data.filter(d => d.result === 'Passed').length;
            const failed = data.filter(d => d.result === 'Failed').length;
            const passRate = ((passed / data.length) * 100).toFixed(1);
            
            let html = `
                <!DOCTYPE html>
                <html>
                <head>
                    <title>CCJE Board Exam Report</title>
                    <style>
                        body { font-family: Arial, sans-serif; padding: 20px; }
                        h1 { color: #7F1D1D; border-bottom: 2px solid #7F1D1D; padding-bottom: 10px; }
                        .stats { display: flex; gap: 20px; margin: 20px 0; }
                        .stat-box { background: #FEE2E2; padding: 15px 25px; border-radius: 8px; text-align: center; }
                        .stat-value { font-size: 24px; font-weight: bold; color: #7F1D1D; }
                        .stat-label { font-size: 12px; color: #666; }
                        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                        th { background: #7F1D1D; color: white; padding: 10px; text-align: left; }
                        td { padding: 8px 10px; border-bottom: 1px solid #ddd; }
                        tr:nth-child(even) { background: #f9f9f9; }
                        .passed { color: #16a34a; font-weight: bold; }
                        .failed { color: #dc2626; }
                        .footer { margin-top: 30px; text-align: center; color: #666; font-size: 12px; }
                    </style>
                </head>
                <body>
                    <h1>🛡️ College of Criminal Justice Education - Board Examination Report</h1>
                    <p>Generated: ${new Date().toLocaleString()}</p>
                    
                    ${includeStats ? `
                    <div class="stats">
                        <div class="stat-box">
                            <div class="stat-value">${data.length}</div>
                            <div class="stat-label">Total Records</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-value">${passed}</div>
                            <div class="stat-label">Passed</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-value">${failed}</div>
                            <div class="stat-label">Failed</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-value">${passRate}%</div>
                            <div class="stat-label">Pass Rate</div>
                        </div>
                    </div>
                    ` : ''}
                    
                    <table>
                        <thead>
                            <tr>
                                <th>Board Exam Type</th>
                                <th>Exam Date</th>
                                <th>Year</th>
                                <th>Attempt Type</th>
                                <th>Result</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${data.map(item => `
                                <tr>
                                    <td>${item.board_exam_type}</td>
                                    <td>${new Date(item.board_exam_date).toLocaleDateString()}</td>
                                    <td>${item.year}</td>
                                    <td>${item.exam_type}</td>
                                    <td class="${item.result.toLowerCase()}">${item.result}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                    
                    <div class="footer">
                        <p>LSPU San Pablo City Campus - College of Criminal Justice Education</p>
                    </div>
                </body>
                </html>
            `;
            
            const printWindow = window.open('', '_blank');
            printWindow.document.write(html);
            printWindow.document.close();
            printWindow.print();
        }
        
        // Download file helper
        function downloadFile(content, filename, mimeType) {
            const blob = new Blob([content], { type: mimeType });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        }
        
        // Print Dashboard
        function printDashboard() {
            window.print();
        }
        
        // Show notification
        function showNotification(message) {
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                bottom: 20px;
                right: 20px;
                background: var(--gradient);
                color: white;
                padding: 16px 24px;
                border-radius: 12px;
                box-shadow: 0 4px 20px rgba(127, 29, 29, 0.3);
                z-index: 1001;
                animation: slideInRight 0.3s ease;
                display: flex;
                align-items: center;
                gap: 10px;
            `;
            notification.innerHTML = `<i class="fas fa-check-circle"></i> ${message}`;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.animation = 'slideOutRight 0.3s ease forwards';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }
        
        // Close modal on outside click
        document.getElementById('exportModal').addEventListener('click', function(e) {
            if (e.target === this) closeExportModal();
        });
        
        // Add animation keyframes
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideInRight {
                from { opacity: 0; transform: translateX(100px); }
                to { opacity: 1; transform: translateX(0); }
            }
            @keyframes slideOutRight {
                from { opacity: 1; transform: translateX(0); }
                to { opacity: 0; transform: translateX(100px); }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>