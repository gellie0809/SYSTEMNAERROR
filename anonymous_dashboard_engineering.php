<?php
session_start();

// Allow Engineering admin or ICTS admin
if (!isset($_SESSION["users"]) || ($_SESSION["users"] !== 'eng_admin@lspu.edu.ph' && $_SESSION["users"] !== 'icts_admin@lspu.edu.ph')) {
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

// Add is_deleted column if it doesn't exist
$check_column = $conn->query("SHOW COLUMNS FROM anonymous_board_passers LIKE 'is_deleted'");
if ($check_column->num_rows == 0) {
    $conn->query("ALTER TABLE anonymous_board_passers ADD COLUMN is_deleted TINYINT(1) DEFAULT 0");
}

// Handle delete request (soft delete)
if (isset($_POST['delete_id'])) {
    $delete_id = intval($_POST['delete_id']);
    $stmt = $conn->prepare("UPDATE anonymous_board_passers SET is_deleted = 1 WHERE id = ? AND department = 'Engineering'");
    $stmt->bind_param('i', $delete_id);
    $stmt->execute();
    $stmt->close();
    header("Location: anonymous_dashboard_engineering.php");
    exit();
}

// Handle edit request
if (isset($_POST['edit_id']) && isset($_POST['edit_result'])) {
    $edit_id = intval($_POST['edit_id']);
    $edit_result = $_POST['edit_result'];
    $edit_exam_type = $_POST['edit_exam_type'];
    
    if (in_array($edit_result, ['Passed', 'Failed', 'Conditional']) && in_array($edit_exam_type, ['First Timer', 'Repeater'])) {
        $stmt = $conn->prepare("UPDATE anonymous_board_passers SET result = ?, exam_type = ? WHERE id = ? AND department = 'Engineering'");
        $stmt->bind_param('ssi', $edit_result, $edit_exam_type, $edit_id);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: anonymous_dashboard_engineering.php");
    exit();
}

// Fetch anonymous data (excluding soft deleted)
$passers = $conn->query("SELECT * FROM anonymous_board_passers WHERE department='Engineering' AND (is_deleted IS NULL OR is_deleted = 0) ORDER BY id ASC");
$total_records = $passers ? $passers->num_rows : 0;

// Calculate statistics (excluding soft deleted)
$stats = [
    'total' => 0,
    'passed' => 0,
    'failed' => 0,
    'conditional' => 0,
    'first_timer' => 0,
    'repeater' => 0,
    'first_timer_passed' => 0,
    'repeater_passed' => 0
];

if ($passers) {
    $passers->data_seek(0);
    while ($row = $passers->fetch_assoc()) {
        // Skip soft deleted records
        if (isset($row['is_deleted']) && $row['is_deleted'] == 1) continue;
        
        $stats['total']++;
        
        if ($row['result'] === 'Passed') $stats['passed']++;
        elseif ($row['result'] === 'Failed') $stats['failed']++;
        elseif ($row['result'] === 'Conditional') $stats['conditional']++;
        
        if ($row['exam_type'] === 'First Timer') {
            $stats['first_timer']++;
            if ($row['result'] === 'Passed') $stats['first_timer_passed']++;
        } elseif ($row['exam_type'] === 'Repeater') {
            $stats['repeater']++;
            if ($row['result'] === 'Passed') $stats['repeater_passed']++;
        }
    }
    $passers->data_seek(0);
}

// Calculate percentages
$passing_rate = $stats['total'] > 0 ? number_format(($stats['passed'] / $stats['total']) * 100, 2) : '0.00';
$first_timer_rate = $stats['first_timer'] > 0 ? number_format(($stats['first_timer_passed'] / $stats['first_timer']) * 100, 2) : '0.00';
$repeater_rate = $stats['repeater'] > 0 ? number_format(($stats['repeater_passed'] / $stats['repeater']) * 100, 2) : '0.00';

// Get unique board exam types and dates for filters
$board_exam_types = [];
$exam_dates = [];
if ($passers) {
    $passers->data_seek(0);
    while ($row = $passers->fetch_assoc()) {
        if (isset($row['is_deleted']) && $row['is_deleted'] == 1) continue;
        if (!in_array($row['board_exam_type'], $board_exam_types)) {
            $board_exam_types[] = $row['board_exam_type'];
        }
        $date_formatted = date('F Y', strtotime($row['board_exam_date']));
        if (!in_array($date_formatted, $exam_dates)) {
            $exam_dates[] = $date_formatted;
        }
    }
    $passers->data_seek(0);
}
sort($board_exam_types);
sort($exam_dates);

// Prepare legend data for breakdown by exam type and date
$legend_data = [
    'passed' => [],
    'failed' => [],
    'conditional' => [],
    'first_timer' => [],
    'repeater' => [],
    'totals' => [] // Track totals per exam type and date for percentage calculation
];

if ($passers) {
    $passers->data_seek(0);
    while ($row = $passers->fetch_assoc()) {
        if (isset($row['is_deleted']) && $row['is_deleted'] == 1) continue;
        
        $exam_type = $row['board_exam_type'];
        $exam_date = date('F Y', strtotime($row['board_exam_date']));
        $result = strtolower($row['result']);
        $take_attempts = strtolower(str_replace(' ', '_', $row['exam_type']));
        
        // Track totals per exam type and date
        if (!isset($legend_data['totals'][$exam_type])) {
            $legend_data['totals'][$exam_type] = [];
        }
        if (!isset($legend_data['totals'][$exam_type][$exam_date])) {
            $legend_data['totals'][$exam_type][$exam_date] = 0;
        }
        $legend_data['totals'][$exam_type][$exam_date]++;
        
        // Group by result
        if (!isset($legend_data[$result][$exam_type])) {
            $legend_data[$result][$exam_type] = [];
        }
        if (!isset($legend_data[$result][$exam_type][$exam_date])) {
            $legend_data[$result][$exam_type][$exam_date] = 0;
        }
        $legend_data[$result][$exam_type][$exam_date]++;
        
        // Group by take attempts
        if (!isset($legend_data[$take_attempts][$exam_type])) {
            $legend_data[$take_attempts][$exam_type] = [];
        }
        if (!isset($legend_data[$take_attempts][$exam_type][$exam_date])) {
            $legend_data[$take_attempts][$exam_type][$exam_date] = 0;
        }
        $legend_data[$take_attempts][$exam_type][$exam_date]++;
    }
    $passers->data_seek(0);
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <title>Data Dashboard - Engineering</title>
    <link rel="stylesheet" href="style.css"/>
    <link rel="stylesheet" href="css/sidebar.css"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #8BA49A;
            --primary-dark: #3B6255;
            --success: #8BA49A;
            --danger: #64748b;
            --warning: #a8c5a5;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        /* Engineering-specific sidebar color overrides - COE Earthy Harmony */
        body .sidebar .logo,
        html body .sidebar .logo {
            color: #8BA49A !important;
        }
        body .sidebar-nav a,
        html body .sidebar-nav a {
            color: #3B6255 !important;
        }
        body .sidebar-nav i,
        body .sidebar-nav ion-icon,
        html body .sidebar-nav i,
        html body .sidebar-nav ion-icon {
            color: #8BA49A !important;
        }
        body .sidebar-nav a.active,
        body .sidebar-nav a:hover,
        html body .sidebar-nav a.active,
        html body .sidebar-nav a:hover {
            background: linear-gradient(90deg, #8BA49A 0%, #CBDED3 100%) !important;
            color: #3B6255 !important;
            box-shadow: 0 4px 12px rgba(139, 164, 154, 0.3) !important;
        }
        
        body .sidebar-nav a.active i,
        body .sidebar-nav a.active ion-icon,
        body .sidebar-nav a:hover i,
        body .sidebar-nav a:hover ion-icon,
        html body .sidebar-nav a.active i,
        html body .sidebar-nav a.active ion-icon,
        html body .sidebar-nav a:hover i,
        html body .sidebar-nav a:hover ion-icon {
            color: #3B6255 !important;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #E2DFDA 0%, #CBDED3 100%);
            color: #0f1724;
            min-height: 100vh;
        }

        .topbar {
            position: fixed;
            top: 0;
            left: 260px;
            right: 0;
            background: linear-gradient(135deg, #8BA49A 0%, #3B6255 100%);
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 40px;
            box-shadow: 0 4px 20px rgba(22, 41, 56, 0.1);
            z-index: 50;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .dashboard-title {
        font-size: 1.4rem;
        color: #fff;
        font-weight: 700;
        letter-spacing: 1px;
        margin: 0;
        }

        .logout-btn {
            background: rgba(255, 255, 255, 0.15);
            color: #fff;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 12px;
            padding: 12px 24px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
        }

        .logout-btn:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: translateY(-2px);
        }

        .main {
            margin-left: 260px;
            margin-top: 70px;
            padding: 32px;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
        }

        .page-header h2 {
            font-size: 1.8rem;
            font-weight: 800;
            color: #0f1724;
        }

        .add-data-btn {
            background: linear-gradient(135deg, #8BA49A 0%, #3B6255 100%);
            color: #fff;
            border: none;
            border-radius: 12px;
            padding: 12px 24px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .add-data-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(145, 179, 142, 0.4);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
            margin-bottom: 40px;
            max-width: 100%;
        }

        .stat-card {
            background: linear-gradient(145deg, rgba(255, 255, 255, 0.98) 0%, rgba(245, 251, 245, 0.95) 100%);
            border-radius: 18px;
            padding: 28px;
            box-shadow: 0 6px 20px rgba(145, 179, 142, 0.1);
            border: 2px solid rgba(145, 179, 142, 0.2);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            cursor: pointer;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #8BA49A, #3B6255);
            opacity: 0;
            transition: opacity 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 12px 35px rgba(145, 179, 142, 0.25);
            border-color: rgba(145, 179, 142, 0.35);
        }

        .stat-card:hover::before {
            opacity: 1;
        }

        .stat-card .icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 16px;
        }

        .stat-card.total .icon {
            background: linear-gradient(135deg, #8BA49A 0%, #3B6255 100%);
            color: #fff;
        }

        .stat-card.passed .icon {
            background: linear-gradient(135deg, #8BA49A 0%, #3B6255 100%);
            color: #fff;
        }

        .stat-card.failed .icon {
            background: linear-gradient(135deg, #7a9d77 0%, #64748b 100%);
            color: #fff;
        }

        .stat-card.conditional .icon {
            background: linear-gradient(135deg, #a8c5a5 0%, #8b9c88 100%);
            color: #fff;
        }

        .stat-card .label {
            font-size: 0.9rem;
            color: #64748b;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .stat-card .value {
            font-size: 2rem;
            font-weight: 800;
            color: #0f1724;
        }

        .stat-card .percentage {
            font-size: 0.85rem;
            color: #8BA49A;
            font-weight: 600;
            margin-top: 4px;
        }

        /* Filter Section */
        .filter-section {
            background: linear-gradient(145deg, rgba(255, 255, 255, 0.98) 0%, rgba(245, 251, 245, 0.95) 100%);
            border-radius: 20px;
            padding: 0;
            box-shadow: 0 6px 20px rgba(139, 164, 154, 0.12);
            border: 2px solid rgba(139, 164, 154, 0.2);
            margin-bottom: 32px;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .filter-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 24px 28px;
            background: linear-gradient(135deg, #8BA49A 0%, #3B6255 100%);
            cursor: pointer;
            user-select: none;
        }

        .filter-header h3 {
            color: white;
            font-size: 1.2rem;
            font-weight: 700;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .filter-header h3 i {
            font-size: 1.3rem;
        }

        .btn-collapse {
            background: rgba(255, 255, 255, 0.2);
            border: 2px solid rgba(255, 255, 255, 0.3);
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
        }

        .btn-collapse:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.1);
        }

        .btn-collapse i {
            transition: transform 0.3s ease;
        }

        .filter-section.collapsed .btn-collapse i {
            transform: rotate(180deg);
        }

        .filter-content {
            padding: 28px;
            max-height: 500px;
            opacity: 1;
            transition: all 0.3s ease;
        }

        .filter-section.collapsed .filter-content {
            max-height: 0;
            opacity: 0;
            padding: 0 28px;
            overflow: hidden;
        }

        .filter-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 20px;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
        }

        .filter-group label {
            font-weight: 600;
            font-size: 0.95rem;
            margin-bottom: 8px;
            color: #3B6255;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .filter-group label i {
            color: #8BA49A;
        }

        .filter-group select {
            width: 100%;
            padding: 12px 16px;
            font-size: 0.95rem;
            border-radius: 12px;
            border: 2px solid rgba(139, 164, 154, 0.3);
            background: white;
            font-family: 'Inter', sans-serif;
            transition: all 0.3s ease;
            color: #334155;
            cursor: pointer;
            min-height: 120px;
        }

        .filter-group select[multiple] option {
            padding: 10px 12px;
            margin: 2px 0;
            border-radius: 6px;
            cursor: pointer;
        }

        .filter-group select[multiple] option:hover {
            background: linear-gradient(135deg, rgba(139, 164, 154, 0.2) 0%, rgba(203, 222, 211, 0.3) 100%);
        }

        .filter-group select[multiple] option:checked {
            background: linear-gradient(135deg, #8BA49A 0%, #3B6255 100%);
            color: white;
            font-weight: 600;
        }

        .filter-group select:hover {
            border-color: #8BA49A;
        }

        .filter-group select:focus {
            outline: none;
            border-color: #3B6255;
            box-shadow: 0 0 0 4px rgba(139, 164, 154, 0.15);
        }

        .filter-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 20px;
            border-top: 2px solid rgba(139, 164, 154, 0.15);
            flex-wrap: wrap;
            gap: 12px;
        }

        .filter-actions-left {
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .btn-clear-filters {
            background: linear-gradient(135deg, #64748b 0%, #475569 100%);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 12px 24px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-export-filters {
            background: linear-gradient(135deg, #8BA49A 0%, #3B6255 100%);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 12px 24px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-export-filters:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(139, 164, 154, 0.4);
        }

        .btn-clear-filters:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(100, 116, 139, 0.4);
        }

        .filter-count {
            font-size: 0.9rem;
            color: #64748b;
            font-weight: 600;
        }

        @media (max-width: 1200px) {
            .filter-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .filter-grid {
                grid-template-columns: 1fr;
            }
        }

        .stat-card .percentage {
            font-size: 0.9rem;
            color: #10b981;
            font-weight: 600;
            margin-top: 4px;
        }

        .table-card {
            background: linear-gradient(145deg, rgba(255, 255, 255, 0.98) 0%, rgba(245, 251, 245, 0.95) 100%);
            border-radius: 20px;
            padding: 32px;
            box-shadow: 0 8px 30px rgba(145, 179, 142, 0.12);
            border: 2px solid rgba(145, 179, 142, 0.25);
            margin-top: 8px;
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            padding-bottom: 20px;
            border-bottom: 2px solid rgba(145, 179, 142, 0.15);
        }

        .table-header h3 {
            font-size: 1.4rem;
            font-weight: 700;
            color: #0f1724;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: linear-gradient(135deg, #8BA49A 0%, #3B6255 100%);
        }

        thead th {
            padding: 16px;
            text-align: left;
            font-weight: 600;
            font-size: 0.9rem;
            color: #fff;
            border: none;
        }

        thead th:first-child {
            border-radius: 12px 0 0 0;
        }

        thead th:last-child {
            border-radius: 0 12px 0 0;
        }

        tbody tr {
            border-bottom: 1px solid rgba(145, 179, 142, 0.1);
            transition: all 0.2s;
        }

        tbody tr:hover {
            background: rgba(145, 179, 142, 0.05);
        }

        tbody td {
            padding: 16px;
            font-size: 0.95rem;
            color: #334155;
        }

        .badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .badge.passed {
            background: linear-gradient(135deg, #d3ecdc 0%, #c5dcc2 100%);
            color: #2d5a2e;
        }

        .badge.failed {
            background: linear-gradient(135deg, #e2e8f0 0%, #cbd5e1 100%);
            color: #475569;
        }

        .badge.conditional {
            background: linear-gradient(135deg, #e8f5e9 0%, #d1e7dd 100%);
            color: #3B6255;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
            justify-content: center;
        }

        .btn-edit,
        .btn-delete {
            padding: 8px 12px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .btn-edit {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
        }

        .btn-edit:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
        }

        .btn-delete {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
        }

        .btn-delete:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
        }

        /* Modal Styles */
        .modal {
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
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 20px;
            padding: 32px;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }

        .modal-header h3 {
            font-size: 1.4rem;
            color: #0f1724;
            font-weight: 700;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #64748b;
            cursor: pointer;
            padding: 4px 8px;
            transition: all 0.3s;
        }

        .modal-close:hover {
            color: #ef4444;
            transform: rotate(90deg);
        }

        .modal-body {
            margin-bottom: 24px;
        }

        .modal-footer {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
        }

        .btn-modal-cancel,
        .btn-modal-save {
            padding: 12px 24px;
            border: none;
            border-radius: 12px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-modal-cancel {
            background: #e2e8f0;
            color: #475569;
        }

        .btn-modal-cancel:hover {
            background: #cbd5e1;
        }

        .btn-modal-save {
            background: linear-gradient(135deg, #8BA49A 0%, #3B6255 100%);
            color: white;
        }

        .btn-modal-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(145, 179, 142, 0.4);
        }

        .form-group-modal {
            margin-bottom: 20px;
        }

        .form-group-modal label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #334155;
        }

        .form-group-modal select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #d1e7dd;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .form-group-modal select:focus {
            outline: none;
            border-color: #8BA49A;
            box-shadow: 0 0 0 4px rgba(145, 179, 142, 0.15);
        }

        /* Legend Modal Styles */
        .legend-modal {
            max-width: 700px;
        }

        .legend-content {
            max-height: 500px;
            overflow-y: auto;
        }

        .legend-section {
            margin-bottom: 24px;
        }

        .legend-section h4 {
            color: #0f1724;
            font-size: 1.1rem;
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 2px solid rgba(145, 179, 142, 0.2);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .legend-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 16px;
            background: #f8faf9;
            border-radius: 10px;
            margin-bottom: 8px;
            border-left: 4px solid #8BA49A;
        }

        .legend-item-date {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 16px;
            background: white;
            border-radius: 8px;
            margin: 4px 0 4px 20px;
            font-size: 0.9rem;
            gap: 12px;
        }

        .legend-exam-type {
            font-weight: 600;
            color: #334155;
            font-size: 1rem;
        }

        .legend-date {
            color: #64748b;
            font-size: 0.9rem;
            flex: 1;
        }

        .legend-stats {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .legend-percentage {
            color: #64748b;
            font-size: 0.85rem;
            font-weight: 600;
            min-width: 50px;
            text-align: right;
        }

        .legend-count {
            background: linear-gradient(135deg, #8BA49A 0%, #3B6255 100%);
            color: white;
            padding: 4px 12px;
            border-radius: 999px;
            font-weight: 700;
            font-size: 0.9rem;
        }

        .legend-total {
            background: linear-gradient(135deg, #e8f5e9 0%, #d1e7dd 100%);
            border: 2px solid #8BA49A;
            padding: 16px;
            border-radius: 12px;
            margin-top: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .legend-total-label {
            font-weight: 700;
            color: #1e4620;
            font-size: 1.1rem;
        }

        .legend-total-value {
            background: linear-gradient(135deg, #8BA49A 0%, #3B6255 100%);
            color: white;
            padding: 8px 20px;
            border-radius: 999px;
            font-weight: 800;
            font-size: 1.2rem;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #64748b;
        }

        .empty-state i {
            font-size: 4rem;
            color: #cbd5e1;
            margin-bottom: 20px;
        }

        /* Export Modal Styles */
        .export-modal {
            max-width: 650px;
            background: white;
            animation: modalSlideIn 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-30px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .export-modal .modal-header {
            background: linear-gradient(135deg, #8BA49A 0%, #3B6255 100%);
            color: white;
            padding: 28px 35px;
            border-radius: 20px 20px 0 0;
            position: relative;
            overflow: hidden;
        }

        .export-modal .modal-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -100px;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.15) 0%, transparent 70%);
            border-radius: 50%;
        }

        .export-modal .modal-header h3 {
            color: white;
            margin: 0;
            font-size: 1.5rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 12px;
            position: relative;
            z-index: 1;
        }

        .export-modal .modal-header h3 i {
            font-size: 1.8rem;
            animation: downloadPulse 2s ease-in-out infinite;
        }

        @keyframes downloadPulse {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-4px); }
        }

        .export-modal .modal-close {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: none;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            font-size: 1.3rem;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            z-index: 2;
        }

        .export-modal .modal-close:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: rotate(90deg) scale(1.1);
        }

        .export-modal .modal-body {
            padding: 35px;
            background: linear-gradient(to bottom, #ffffff 0%, #f8faf9 100%);
        }

        .export-format-subtitle {
            text-align: center;
            color: #64748b;
            margin-bottom: 28px;
            font-size: 1rem;
            font-weight: 500;
        }

        .export-options {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .export-option {
            display: flex;
            align-items: center;
            padding: 20px 22px;
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: 18px;
            cursor: pointer;
            transition: all 0.35s cubic-bezier(0.16, 1, 0.3, 1);
            position: relative;
            overflow: hidden;
        }

        .export-option::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 0;
            height: 100%;
            background: linear-gradient(90deg, rgba(203, 222, 211, 0.3) 0%, rgba(226, 223, 218, 0.2) 100%);
            transition: width 0.4s ease;
            z-index: 0;
        }

        .export-option:hover::before {
            width: 100%;
        }

        .export-option:hover {
            border-color: #8BA49A;
            transform: translateX(6px) scale(1.02);
            box-shadow: 0 8px 24px rgba(139, 164, 154, 0.25);
        }

        .export-option:active {
            transform: translateX(6px) scale(0.98);
        }

        .export-icon {
            width: 52px;
            height: 52px;
            min-width: 52px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 18px;
            font-size: 1.4rem;
            color: white;
            position: relative;
            z-index: 1;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .export-option:hover .export-icon {
            transform: scale(1.1) rotate(-5deg);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
        }

        .export-icon.csv {
            background: linear-gradient(135deg, #8BA49A 0%, #3B6255 100%);
        }

        .export-icon.excel {
            background: linear-gradient(135deg, #CBDED3 0%, #8BA49A 100%);
        }

        .export-icon.pdf {
            background: linear-gradient(135deg, #D2C49E 0%, #8BA49A 100%);
        }

        .export-icon.json {
            background: linear-gradient(135deg, #3B6255 0%, #2a4840 100%);
        }

        .export-info {
            flex: 1;
            position: relative;
            z-index: 1;
        }

        .export-title {
            font-weight: 700;
            color: #1e293b;
            font-size: 1.1rem;
            margin-bottom: 6px;
            transition: color 0.3s ease;
        }

        .export-option:hover .export-title {
            color: #3B6255;
        }

        .export-description {
            color: #64748b;
            font-size: 0.88rem;
            line-height: 1.4;
        }

        .export-arrow {
            color: #cbd5e1;
            font-size: 1.3rem;
            transition: all 0.35s ease;
            position: relative;
            z-index: 1;
        }

        .export-option:hover .export-arrow {
            color: #8BA49A;
            transform: translateX(6px);
        }

        @media (max-width: 768px) {
            .export-modal {
                max-width: 95%;
            }

            .export-modal .modal-body {
                padding: 25px 20px;
            }

            .export-option {
                padding: 16px 18px;
            }

            .export-icon {
                width: 44px;
                height: 44px;
                min-width: 44px;
                font-size: 1.2rem;
            }
        }

        .empty-state h3 {
            font-size: 1.3rem;
            margin-bottom: 12px;
            color: #475569;
        }

        @media (max-width: 1024px) {
            .stats-grid {
                grid-template-columns: repeat(3, 1fr);
                gap: 20px;
            }
        }

        @media (max-width: 900px) {
            .main {
                margin-left: 80px;
            }
            .topbar {
                left: 80px;
            }
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 16px;
            }
            .stats-grid {
                grid-template-columns: 1fr;
            }
            .table-card {
                padding: 20px;
            }
        }

        @media (max-width: 600px) {
            .main {
                margin-left: 0;
                padding: 16px;
                margin-top: 80px;
            }
            .topbar {
                left: 0;
                padding: 0 20px;
                height: 80px;
                flex-direction: column;
                justify-content: center;
                gap: 8px;
            }
            .dashboard-title {
                font-size: 1.1rem;
            }
            .sidebar {
                display: none;
            }
            .stat-card {
                padding: 20px;
            }
            table {
                font-size: 0.85rem;
            }
            thead th,
            tbody td {
                padding: 12px 8px;
            }
            .table-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/includes/sidebar_common.php'; ?>
    
    <div class="topbar">
        <div class="dashboard-title">Engineering Admin Dashboard</div>
        <div><a class="logout-btn" href="#" onclick="confirmLogout(event)">Logout</a></div>
    </div>

    <div class="main">
        <div class="page-header">
            <h2><i class="fas fa-chart-pie" style="margin-right: 12px;"></i>Data Dashboard</h2>
            <div style="display: flex; gap: 12px;">
                <a href="prediction_engineering.php" class="add-data-btn" style="background: linear-gradient(135deg, #3B6255 0%, #8BA49A 100%);">
                    <i class="fas fa-brain"></i> Predictions
                </a>
                <a href="testing_anonymous_data.php" class="add-data-btn">
                    <i class="fas fa-plus-circle"></i> Add Data
                </a>
            </div>
        </div>

        <!-- Statistics Grid -->
        <div class="stats-grid">
            <div class="stat-card total" onclick="showLegend('total')">
                <div class="icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="label">Total Records</div>
                <div class="value"><?php echo number_format($stats['total']); ?></div>
            </div>

            <div class="stat-card passed" onclick="showLegend('passed')">
                <div class="icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="label">Passed</div>
                <div class="value"><?php echo number_format($stats['passed']); ?></div>
                <div class="percentage"><?php echo $passing_rate; ?>% passing rate</div>
            </div>

            <div class="stat-card failed" onclick="showLegend('failed')">
                <div class="icon">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div class="label">Failed</div>
                <div class="value"><?php echo number_format($stats['failed']); ?></div>
            </div>

            <div class="stat-card conditional" onclick="showLegend('conditional')">
                <div class="icon">
                    <i class="fas fa-exclamation-circle"></i>
                </div>
                <div class="label">Conditional</div>
                <div class="value"><?php echo number_format($stats['conditional']); ?></div>
            </div>

            <div class="stat-card total" onclick="showLegend('first_timer')">
                <div class="icon">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <div class="label">First Timers</div>
                <div class="value"><?php echo number_format($stats['first_timer']); ?></div>
                <div class="percentage"><?php echo $first_timer_rate; ?>% passing rate</div>
            </div>

            <div class="stat-card total" onclick="showLegend('repeater')">
                <div class="icon">
                    <i class="fas fa-redo"></i>
                </div>
                <div class="label">Repeaters</div>
                <div class="value"><?php echo number_format($stats['repeater']); ?></div>
                <div class="percentage"><?php echo $repeater_rate; ?>% passing rate</div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="filter-section">
            <div class="filter-header">
                <h3><i class="fas fa-filter"></i> Filter Records</h3>
                <button class="btn-collapse" onclick="toggleFilters()" id="filterToggle">
                    <i class="fas fa-chevron-up"></i>
                </button>
            </div>
            <div class="filter-content" id="filterContent">
                <div class="filter-grid">
                    <div class="filter-group">
                        <label for="filterExamType">
                            <i class="fas fa-graduation-cap"></i> Board Exam Type
                        </label>
                        <select id="filterExamType" onchange="applyFilters()" multiple size="4">
                            <?php foreach ($board_exam_types as $type): ?>
                                <option value="<?php echo htmlspecialchars($type); ?>"><?php echo htmlspecialchars($type); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="filterDate">
                            <i class="fas fa-calendar"></i> Exam Date
                        </label>
                        <select id="filterDate" onchange="applyFilters()" multiple size="4">
                            <?php foreach ($exam_dates as $date): ?>
                                <option value="<?php echo htmlspecialchars($date); ?>"><?php echo htmlspecialchars($date); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="filterTakeAttempts">
                            <i class="fas fa-redo"></i> Take Attempts
                        </label>
                        <select id="filterTakeAttempts" onchange="applyFilters()" multiple size="2">
                            <option value="First Timer">First Timer</option>
                            <option value="Repeater">Repeater</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="filterResult">
                            <i class="fas fa-check-circle"></i> Result
                        </label>
                        <select id="filterResult" onchange="applyFilters()" multiple size="3">
                            <option value="Passed">Passed</option>
                            <option value="Failed">Failed</option>
                            <option value="Conditional">Conditional</option>
                        </select>
                    </div>
                </div>
                <div class="filter-actions">
                    <div class="filter-actions-left">
                        <button class="btn-clear-filters" onclick="clearFilters()">
                            <i class="fas fa-times-circle"></i> Clear Filters
                        </button>
                        <button class="btn-export-filters" onclick="exportFilteredData()">
                            <i class="fas fa-download"></i> Export Filtered Data
                        </button>
                    </div>
                    <span class="filter-count" id="filterCount"></span>
                </div>
            </div>
        </div>

        <!-- Data Table -->
        <div class="table-card">
            <div class="table-header">
                <h3><i class="fas fa-table"></i> Data Records</h3>
                <span style="color: #64748b; font-size: 0.9rem;">
                    <?php echo number_format($total_records); ?> total records
                </span>
            </div>

            <?php if ($total_records > 0): ?>
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Board Exam Type</th>
                                <th>Exam Date</th>
                                <th>Take Attempts</th>
                                <th>Result</th>
                                <th>Created At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $passers->fetch_assoc()): ?>
                                <?php if (isset($row['is_deleted']) && $row['is_deleted'] == 1) continue; ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['id']); ?></td>
                                    <td><?php echo htmlspecialchars($row['board_exam_type']); ?></td>
                                    <td>
                                        <?php 
                                        $date = new DateTime($row['board_exam_date']);
                                        echo $date->format('F Y');
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['exam_type']); ?></td>
                                    <td>
                                        <span class="badge <?php echo strtolower($row['result']); ?>">
                                            <?php echo htmlspecialchars($row['result']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php 
                                        $created = new DateTime($row['created_at']);
                                        echo $created->format('M d, Y g:i A');
                                        ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button onclick="openEditModal(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['exam_type']); ?>', '<?php echo htmlspecialchars($row['result']); ?>')" class="btn-edit" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button onclick="confirmDelete(<?php echo $row['id']; ?>)" class="btn-delete" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h3>No Data Yet</h3>
                    <p>Start by adding board examinee data using the form.</p>
                    <br>
                    <a href="testing_anonymous_data.php" class="add-data-btn">
                        <i class="fas fa-plus-circle"></i> Add First Record
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-edit"></i> Edit Record</h3>
                <button class="modal-close" onclick="closeEditModal()">&times;</button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="edit_id" id="edit_id">
                <div class="modal-body">
                    <div class="form-group-modal">
                        <label for="edit_exam_type">
                            <i class="fas fa-redo"></i> Take Attempts
                        </label>
                        <select name="edit_exam_type" id="edit_exam_type" required>
                            <option value="First Timer">First Timer</option>
                            <option value="Repeater">Repeater</option>
                        </select>
                    </div>
                    <div class="form-group-modal">
                        <label for="edit_result">
                            <i class="fas fa-clipboard-check"></i> Result
                        </label>
                        <select name="edit_result" id="edit_result" required>
                            <option value="Passed">Passed</option>
                            <option value="Failed">Failed</option>
                            <option value="Conditional">Conditional</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-modal-cancel" onclick="closeEditModal()">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="btn-modal-save">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Legend Modal -->
    <div id="legendModal" class="modal">
        <div class="modal-content legend-modal">
            <div class="modal-header">
                <h3 id="legendTitle"><i class="fas fa-chart-bar"></i> Data Breakdown</h3>
                <button class="modal-close" onclick="closeLegendModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div id="legendContent" class="legend-content"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-modal-cancel" onclick="closeLegendModal()">
                    <i class="fas fa-times"></i> Close
                </button>
            </div>
        </div>
    </div>

    <!-- Export Modal -->
    <div id="exportModal" class="modal">
        <div class="modal-content export-modal">
            <div class="modal-header">
                <h3><i class="fas fa-download"></i> Export Data</h3>
                <button class="modal-close" onclick="closeExportModal()">&times;</button>
            </div>
            <div class="modal-body">
                <p class="export-format-subtitle">Choose your preferred export format</p>
                <div class="export-options">
                    <div class="export-option" onclick="exportAs('csv')">
                        <div class="export-icon csv">
                            <i class="fas fa-file-csv"></i>
                        </div>
                        <div class="export-info">
                            <div class="export-title">Export as CSV</div>
                            <div class="export-description">Comma-separated values for spreadsheet applications</div>
                        </div>
                        <i class="fas fa-chevron-right export-arrow"></i>
                    </div>
                    
                    <div class="export-option" onclick="exportAs('excel')">
                        <div class="export-icon excel">
                            <i class="fas fa-file-excel"></i>
                        </div>
                        <div class="export-info">
                            <div class="export-title">Export as Excel</div>
                            <div class="export-description">Microsoft Excel format with formatting</div>
                        </div>
                        <i class="fas fa-chevron-right export-arrow"></i>
                    </div>
                    
                    <div class="export-option" onclick="exportAs('pdf')">
                        <div class="export-icon pdf">
                            <i class="fas fa-file-pdf"></i>
                        </div>
                        <div class="export-info">
                            <div class="export-title">Export as PDF</div>
                            <div class="export-description">Portable document format for reports</div>
                        </div>
                        <i class="fas fa-chevron-right export-arrow"></i>
                    </div>
                    
                    <div class="export-option" onclick="exportAs('json')">
                        <div class="export-icon json">
                            <i class="fas fa-code"></i>
                        </div>
                        <div class="export-info">
                            <div class="export-title">Export as JSON</div>
                            <div class="export-description">JavaScript Object Notation for developers</div>
                        </div>
                        <i class="fas fa-chevron-right export-arrow"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Export Modal -->
    <div id="exportModal" class="modal">
        <div class="modal-content export-modal">
            <div class="modal-header">
                <h3><i class="fas fa-download"></i> Export Data</h3>
                <button class="modal-close" onclick="closeExportModal()">&times;</button>
            </div>
            <div class="modal-body">
                <p class="export-format-subtitle">Choose your preferred export format</p>
                <div class="export-options">
                    <div class="export-option" onclick="exportAs('csv')">
                        <div class="export-icon csv">
                            <i class="fas fa-file-csv"></i>
                        </div>
                        <div class="export-info">
                            <div class="export-title">Export as CSV</div>
                            <div class="export-description">Comma-separated values for spreadsheet applications</div>
                        </div>
                        <i class="fas fa-chevron-right export-arrow"></i>
                    </div>
                    
                    <div class="export-option" onclick="exportAs('excel')">
                        <div class="export-icon excel">
                            <i class="fas fa-file-excel"></i>
                        </div>
                        <div class="export-info">
                            <div class="export-title">Export as Excel</div>
                            <div class="export-description">Microsoft Excel format with formatting</div>
                        </div>
                        <i class="fas fa-chevron-right export-arrow"></i>
                    </div>
                    
                    <div class="export-option" onclick="exportAs('pdf')">
                        <div class="export-icon pdf">
                            <i class="fas fa-file-pdf"></i>
                        </div>
                        <div class="export-info">
                            <div class="export-title">Export as PDF</div>
                            <div class="export-description">Portable document format for reports</div>
                        </div>
                        <i class="fas fa-chevron-right export-arrow"></i>
                    </div>
                    
                    <div class="export-option" onclick="exportAs('json')">
                        <div class="export-icon json">
                            <i class="fas fa-code"></i>
                        </div>
                        <div class="export-info">
                            <div class="export-title">Export as JSON</div>
                            <div class="export-description">JavaScript Object Notation for developers</div>
                        </div>
                        <i class="fas fa-chevron-right export-arrow"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Form (hidden) -->
    <form id="deleteForm" method="POST" action="" style="display: none;">
        <input type="hidden" name="delete_id" id="delete_id">
    </form>

    <script>
        // Legend data from PHP
        const legendData = <?php echo json_encode($legend_data); ?>;
        const statsData = <?php echo json_encode($stats); ?>;

        function showLegend(type) {
            const titles = {
                'total': 'Total Records Breakdown',
                'passed': 'Passed Examinees by Board Exam Type & Date',
                'failed': 'Failed Examinees by Board Exam Type & Date',
                'conditional': 'Conditional Examinees by Board Exam Type & Date',
                'first_timer': 'First Timers by Board Exam Type & Date',
                'repeater': 'Repeaters by Board Exam Type & Date'
            };

            const icons = {
                'total': 'fa-users',
                'passed': 'fa-check-circle',
                'failed': 'fa-times-circle',
                'conditional': 'fa-exclamation-circle',
                'first_timer': 'fa-user-graduate',
                'repeater': 'fa-redo'
            };

            document.getElementById('legendTitle').innerHTML = 
                `<i class="fas ${icons[type]}"></i> ${titles[type]}`;

            let content = '';
            let totalCount = 0;
            
            if (type === 'total') {
                // Show all data combined - use only result categories to avoid double counting
                const allData = {};
                ['passed', 'failed', 'conditional'].forEach(category => {
                    if (legendData[category]) {
                        Object.keys(legendData[category]).forEach(examType => {
                            if (!allData[examType]) allData[examType] = {};
                            Object.keys(legendData[category][examType]).forEach(date => {
                                if (!allData[examType][date]) allData[examType][date] = 0;
                                allData[examType][date] += legendData[category][examType][date];
                            });
                        });
                    }
                });
                
                Object.keys(allData).sort().forEach(examType => {
                    content += `<div class="legend-section">`;
                    content += `<h4><i class="fas fa-graduation-cap"></i> ${examType}</h4>`;
                    
                    Object.keys(allData[examType]).sort().forEach(date => {
                        const count = allData[examType][date];
                        const dateTotal = legendData.totals[examType] && legendData.totals[examType][date] ? legendData.totals[examType][date] : count;
                        const passedCount = legendData.passed[examType] && legendData.passed[examType][date] ? legendData.passed[examType][date] : 0;
                        const percentage = dateTotal > 0 ? ((passedCount / dateTotal) * 100).toFixed(2) : '0.00';
                        totalCount += count;
                        content += `<div class="legend-item-date">`;
                        content += `<span class="legend-date"><i class="fas fa-calendar"></i> ${date}</span>`;
                        content += `<div class="legend-stats">`;
                        content += `<span class="legend-percentage">${percentage}% passed</span>`;
                        content += `<span class="legend-count">${count}</span>`;
                        content += `</div>`;
                        content += `</div>`;
                    });
                    
                    content += `</div>`;
                });
            } else {
                const data = legendData[type];
                
                if (data && Object.keys(data).length > 0) {
                    Object.keys(data).sort().forEach(examType => {
                        content += `<div class="legend-section">`;
                        content += `<h4><i class="fas fa-graduation-cap"></i> ${examType}</h4>`;
                        
                        Object.keys(data[examType]).sort().forEach(date => {
                            const count = data[examType][date];
                            const dateTotal = legendData.totals[examType] && legendData.totals[examType][date] ? legendData.totals[examType][date] : count;
                            const percentage = dateTotal > 0 ? ((count / dateTotal) * 100).toFixed(2) : '0.00';
                            totalCount += count;
                            content += `<div class="legend-item-date">`;
                            content += `<span class="legend-date"><i class="fas fa-calendar"></i> ${date}</span>`;
                            content += `<div class="legend-stats">`;
                            content += `<span class="legend-percentage">${percentage}%</span>`;
                            content += `<span class="legend-count">${count}</span>`;
                            content += `</div>`;
                            content += `</div>`;
                        });
                        
                        content += `</div>`;
                    });
                } else {
                    content = `<div class="empty-state" style="padding: 40px 20px;">
                        <i class="fas fa-inbox"></i>
                        <h3>No Data Available</h3>
                        <p>There are no records in this category.</p>
                    </div>`;
                }
            }
            
            if (totalCount > 0) {
                content += `<div class="legend-total">`;
                content += `<span class="legend-total-label"><i class="fas fa-calculator"></i> Total Count</span>`;
                content += `<span class="legend-total-value">${totalCount.toLocaleString()}</span>`;
                content += `</div>`;
            }

            document.getElementById('legendContent').innerHTML = content;
            document.getElementById('legendModal').classList.add('active');
        }

        function closeLegendModal() {
            document.getElementById('legendModal').classList.remove('active');
        }

        function openEditModal(id, examType, result) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_exam_type').value = examType;
            document.getElementById('edit_result').value = result;
            document.getElementById('editModal').classList.add('active');
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.remove('active');
        }

        function confirmDelete(id) {
            if (confirm('Are you sure you want to delete this record? This action cannot be undone.')) {
                document.getElementById('delete_id').value = id;
                document.getElementById('deleteForm').submit();
            }
        }

        function confirmLogout(event) {
            event.preventDefault();
            document.getElementById('logoutModal').classList.add('active');
        }

        // Filter Functions
        function toggleFilters() {
            const filterSection = document.querySelector('.filter-section');
            filterSection.classList.toggle('collapsed');
        }

        function applyFilters() {
            const examTypeSelect = document.getElementById('filterExamType');
            const dateSelect = document.getElementById('filterDate');
            const takeAttemptsSelect = document.getElementById('filterTakeAttempts');
            const resultSelect = document.getElementById('filterResult');

            const examTypes = Array.from(examTypeSelect.selectedOptions).map(opt => opt.value.toLowerCase());
            const dates = Array.from(dateSelect.selectedOptions).map(opt => opt.value.toLowerCase());
            const takeAttempts = Array.from(takeAttemptsSelect.selectedOptions).map(opt => opt.value.toLowerCase());
            const results = Array.from(resultSelect.selectedOptions).map(opt => opt.value.toLowerCase());

            const rows = document.querySelectorAll('tbody tr');
            let visibleCount = 0;

            rows.forEach(row => {
                const rowExamType = row.cells[1].textContent.toLowerCase();
                const rowDate = row.cells[2].textContent.toLowerCase();
                const rowTakeAttempts = row.cells[3].textContent.toLowerCase();
                const rowResult = row.cells[4].textContent.toLowerCase();

                const matchExamType = examTypes.length === 0 || examTypes.some(type => rowExamType.includes(type));
                const matchDate = dates.length === 0 || dates.some(date => rowDate.includes(date));
                const matchTakeAttempts = takeAttempts.length === 0 || takeAttempts.some(attempt => rowTakeAttempts.includes(attempt));
                const matchResult = results.length === 0 || results.some(result => rowResult.includes(result));

                if (matchExamType && matchDate && matchTakeAttempts && matchResult) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            const filterCount = document.getElementById('filterCount');
            if (examTypes.length > 0 || dates.length > 0 || takeAttempts.length > 0 || results.length > 0) {
                filterCount.textContent = `Showing ${visibleCount} of ${rows.length} records`;
            } else {
                filterCount.textContent = '';
            }
        }

        function clearFilters() {
            const selects = ['filterExamType', 'filterDate', 'filterTakeAttempts', 'filterResult'];
            selects.forEach(id => {
                const select = document.getElementById(id);
                Array.from(select.options).forEach(option => option.selected = false);
            });
            applyFilters();
        }

        function exportFilteredData() {
            document.getElementById('exportModal').classList.add('active');
        }

        function closeExportModal() {
            document.getElementById('exportModal').classList.remove('active');
        }

        function getFilteredData() {
            const rows = document.querySelectorAll('tbody tr');
            const visibleRows = Array.from(rows).filter(row => row.style.display !== 'none');

            if (visibleRows.length === 0) {
                alert('No data to export. Please adjust your filters.');
                return null;
            }

            const data = visibleRows.map(row => ({
                id: row.cells[0].textContent.trim(),
                examType: row.cells[1].textContent.trim(),
                examDate: row.cells[2].textContent.trim(),
                takeAttempts: row.cells[3].textContent.trim(),
                result: row.cells[4].textContent.trim()
            }));

            // Calculate summary statistics
            const summary = {
                total: data.length,
                passed: data.filter(r => r.result.toLowerCase().includes('passed')).length,
                failed: data.filter(r => r.result.toLowerCase().includes('failed')).length,
                conditional: data.filter(r => r.result.toLowerCase().includes('conditional')).length,
                firstTimer: data.filter(r => r.takeAttempts.toLowerCase().includes('first timer')).length,
                repeater: data.filter(r => r.takeAttempts.toLowerCase().includes('repeater')).length,
                passingRate: 0
            };

            summary.passingRate = summary.total > 0 
                ? ((summary.passed / summary.total) * 100).toFixed(2) 
                : 0;

            return { data, summary };
        }

        function exportAs(format) {
            const result = getFilteredData();
            if (!result) return;

            const { data, summary } = result;
            closeExportModal();
            const timestamp = new Date().toISOString().slice(0, 10);

            switch(format) {
                case 'csv':
                    exportAsCSV(data, summary, timestamp);
                    break;
                case 'excel':
                    exportAsExcel(data, summary, timestamp);
                    break;
                case 'pdf':
                    exportAsPDF(data, summary, timestamp);
                    break;
                case 'json':
                    exportAsJSON(data, summary, timestamp);
                    break;
            }
        }

        function exportAsCSV(data, summary, timestamp) {
            let csv = '"Laguna State Polytechnic University - San Pablo City Campus"\n';
            csv += '"College of Engineering - Board Exam Performance Report"\n';
            csv += `"Export Date: ${new Date().toLocaleString()}"\n\n`;
            
            csv += '"SUMMARY STATISTICS"\n';
            csv += `"Total Records:",${summary.total}\n`;
            csv += `"Passed:",${summary.passed}\n`;
            csv += `"Failed:",${summary.failed}\n`;
            csv += `"Conditional:",${summary.conditional}\n`;
            csv += `"First Timers:",${summary.firstTimer}\n`;
            csv += `"Repeaters:",${summary.repeater}\n`;
            csv += `"Passing Rate:","${summary.passingRate}%"\n\n`;
            
            csv += '"DETAILED RECORDS"\n';
            csv += 'ID,Board Exam Type,Exam Date,Take Attempts,Result\n';
            data.forEach(row => {
                csv += `"${row.id}","${row.examType}","${row.examDate}","${row.takeAttempts}","${row.result}"\n`;
            });

            downloadFile(csv, `lspu_engineering_board_exam_data_${timestamp}.csv`, 'text/csv;charset=utf-8;');
        }

        function exportAsExcel(data, summary, timestamp) {
            // Create HTML table for Excel with beautiful styling
            let html = '<html><head><meta charset="utf-8">';
            html += '<style>';
            html += 'body { font-family: Calibri, Arial, sans-serif; }';
            html += '.header { text-align: center; margin-bottom: 20px; padding: 20px; background: linear-gradient(135deg, #CBDED3 0%, #E2DFDA 100%); border-bottom: 4px solid #8BA49A; }';
            html += '.school-name { color: #3B6255; font-size: 24px; font-weight: bold; margin: 5px 0; }';
            html += '.campus { color: #8BA49A; font-size: 18px; font-weight: 600; margin: 5px 0; }';
            html += '.department { color: #3B6255; font-size: 20px; font-weight: bold; margin: 10px 0; padding: 8px 16px; background: #CBDED3; display: inline-block; border-radius: 4px; }';
            html += '.meta { color: #64748b; font-size: 12px; margin: 5px 0; }';
            html += '.summary { margin: 20px 0; padding: 15px; background: #f8faf9; border: 2px solid #8BA49A; border-radius: 8px; }';
            html += '.summary h3 { color: #3B6255; margin-bottom: 10px; }';
            html += '.summary-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; }';
            html += '.summary-item { padding: 8px; background: white; border-left: 3px solid #8BA49A; }';
            html += '.summary-label { font-size: 11px; color: #64748b; }';
            html += '.summary-value { font-size: 18px; font-weight: bold; color: #3B6255; }';
            html += 'table { border-collapse: collapse; width: 100%; margin-top: 20px; }';
            html += 'th { background: linear-gradient(135deg, #8BA49A 0%, #3B6255 100%); color: white; padding: 12px 10px; text-align: left; font-weight: bold; border: 1px solid #3B6255; }';
            html += 'td { border: 1px solid #ddd; padding: 10px; text-align: left; }';
            html += 'tr:nth-child(even) { background-color: rgba(203, 222, 211, 0.2); }';
            html += 'tr:hover { background-color: rgba(139, 164, 154, 0.3); }';
            html += '</style></head><body>';
            html += '<div class="header">';
            html += '<div class="school-name">LAGUNA STATE POLYTECHNIC UNIVERSITY</div>';
            html += '<div class="campus">San Pablo City Campus</div>';
            html += '<div class="department">College of Engineering</div>';
            html += `<div class="meta">Board Exam Performance Data Report</div>`;
            html += `<div class="meta">Generated: ${new Date().toLocaleString('en-US')} | Total Records: ${summary.total}</div>`;
            html += '</div>';
            
            html += '<div class="summary">';
            html += '<h3>Summary Statistics</h3>';
            html += '<div class="summary-grid">';
            html += `<div class="summary-item"><div class="summary-label">Total Records</div><div class="summary-value">${summary.total}</div></div>`;
            html += `<div class="summary-item"><div class="summary-label">Passed</div><div class="summary-value">${summary.passed}</div></div>`;
            html += `<div class="summary-item"><div class="summary-label">Failed</div><div class="summary-value">${summary.failed}</div></div>`;
            html += `<div class="summary-item"><div class="summary-label">Conditional</div><div class="summary-value">${summary.conditional}</div></div>`;
            html += `<div class="summary-item"><div class="summary-label">First Timers</div><div class="summary-value">${summary.firstTimer}</div></div>`;
            html += `<div class="summary-item"><div class="summary-label">Repeaters</div><div class="summary-value">${summary.repeater}</div></div>`;
            html += `<div class="summary-item" style="grid-column: span 3; background: linear-gradient(135deg, #CBDED3 0%, #E2DFDA 100%);"><div class="summary-label">Passing Rate</div><div class="summary-value" style="font-size: 24px;">${summary.passingRate}%</div></div>`;
            html += '</div></div>';
            
            html += '<h3 style="color: #3B6255; margin-top: 20px;">Detailed Records</h3>';
            html += '<table><thead><tr><th>ID</th><th>Board Exam Type</th><th>Exam Date</th><th>Take Attempts</th><th>Result</th></tr></thead><tbody>';
            
            data.forEach(row => {
                html += `<tr><td>${row.id}</td><td>${row.examType}</td><td>${row.examDate}</td><td>${row.takeAttempts}</td><td>${row.result}</td></tr>`;
            });
            
            html += '</tbody></table></body></html>';
            downloadFile(html, `lspu_engineering_board_exam_data_${timestamp}.xls`, 'application/vnd.ms-excel');
        }

        function exportAsPDF(data, summary, timestamp) {
            // Since browsers don't natively support direct PDF generation,
            // we'll create a well-formatted printable page that opens print dialog
            // where users can select "Save as PDF" or "Microsoft Print to PDF"
            
            const printWindow = window.open('', '_blank', 'width=1200,height=800');
            
            let htmlContent = `
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Board Exam Performance Report</title>
    <style>
        @page { 
            size: A4; 
            margin: 15mm; 
        }
        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
        }
        body { 
            font-family: "Segoe UI", Arial, sans-serif; 
            padding: 20px; 
            background: white;
            color: #000;
        }
        .container { 
            max-width: 100%; 
            margin: 0 auto; 
        }
        .header { 
            text-align: center; 
            margin-bottom: 30px; 
            padding-bottom: 20px; 
            border-bottom: 4px solid #8BA49A; 
        }
        .school-name { 
            color: #3B6255; 
            font-size: 24px; 
            font-weight: 800; 
            margin-bottom: 8px; 
            text-transform: uppercase; 
        }
        .campus { 
            color: #8BA49A; 
            font-size: 18px; 
            font-weight: 600; 
            margin-bottom: 10px; 
        }
        .department { 
            color: #3B6255; 
            font-size: 20px; 
            font-weight: 700; 
            margin: 10px 0; 
            padding: 8px 20px; 
            background: #CBDED3; 
            display: inline-block; 
            border-radius: 6px; 
        }
        .report-title { 
            color: #64748b; 
            font-size: 14px; 
            font-weight: 600; 
            margin: 12px 0 6px 0; 
        }
        .meta-info { 
            display: flex; 
            justify-content: center; 
            gap: 20px; 
            margin-top: 15px; 
            flex-wrap: wrap; 
            font-size: 12px;
            color: #64748b;
        }
        .meta-item { 
            padding: 6px 12px; 
            border: 1px solid #e2e8f0; 
            border-radius: 4px; 
        }
        .meta-label { 
            font-weight: 700; 
            color: #3B6255; 
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin: 25px 0; 
            font-size: 11px;
        }
        thead { 
            background: #8BA49A; 
        }
        th { 
            color: white; 
            padding: 12px 8px; 
            text-align: left; 
            font-weight: 700; 
            text-transform: uppercase; 
            border: 1px solid #3B6255; 
        }
        tbody tr { 
            border: 1px solid #e2e8f0; 
            page-break-inside: avoid;
        }
        tbody tr:nth-child(even) { 
            background: rgba(203, 222, 211, 0.2); 
        }
        td { 
            padding: 10px 8px; 
            color: #334155; 
            border: 1px solid #e2e8f0;
        }
        td:first-child { 
            font-weight: 600; 
            color: #3B6255; 
        }
        .footer { 
            margin-top: 30px; 
            padding-top: 20px; 
            border-top: 2px solid #e2e8f0; 
            text-align: center; 
            font-size: 11px;
        }
        .footer-logo { 
            color: #8BA49A; 
            font-weight: 700; 
            margin-bottom: 6px; 
        }
        .footer-text { 
            color: #94a3b8; 
            font-size: 10px; 
        }
        .summary-section {
            margin: 25px 0;
            padding: 20px;
            background: linear-gradient(135deg, rgba(203, 222, 211, 0.3) 0%, rgba(226, 223, 218, 0.2) 100%);
            border: 2px solid #8BA49A;
            border-radius: 8px;
            page-break-inside: avoid;
        }
        .summary-title {
            color: #3B6255;
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 15px;
            text-align: center;
        }
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
        }
        .summary-item {
            background: white;
            padding: 12px;
            border-radius: 6px;
            border-left: 4px solid #8BA49A;
            text-align: center;
        }
        .summary-label {
            font-size: 10px;
            color: #64748b;
            margin-bottom: 4px;
            text-transform: uppercase;
        }
        .summary-value {
            font-size: 20px;
            font-weight: 800;
            color: #3B6255;
        }
        .summary-item.highlight {
            grid-column: span 3;
            background: linear-gradient(135deg, #CBDED3 0%, #E2DFDA 100%);
            border-left-color: #3B6255;
            border-left-width: 6px;
        }
        .summary-item.highlight .summary-value {
            font-size: 28px;
        }
        .print-instructions {
            position: fixed;
            top: 10px;
            right: 10px;
            background: #3B6255;
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            z-index: 9999;
            max-width: 300px;
        }
        .print-instructions h4 {
            margin-bottom: 10px;
            font-size: 14px;
        }
        .print-instructions ol {
            margin-left: 20px;
            font-size: 12px;
            line-height: 1.6;
        }
        .print-instructions button {
            background: white;
            color: #3B6255;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
            margin-top: 10px;
            width: 100%;
        }
        @media print {
            .print-instructions {
                display: none;
            }
            body {
                padding: 0;
            }
            .header {
                page-break-after: avoid;
            }
        }
    </style>
</head>
<body>
    <div class="print-instructions">
        <h4>📄 Save as PDF</h4>
        <ol>
            <li>Press Ctrl+P (Windows) or Cmd+P (Mac)</li>
            <li>Select "Save as PDF" or "Microsoft Print to PDF"</li>
            <li>Click Save</li>
        </ol>
        <button onclick="window.print()">Print / Save as PDF</button>
    </div>
    
    <div class="container">
        <div class="header">
            <div class="school-name">Laguna State Polytechnic University</div>
            <div class="campus">San Pablo City Campus</div>
            <div class="department">College of Engineering</div>
            <div class="report-title">Board Exam Performance Data Report</div>
            <div class="meta-info">
                <div class="meta-item"><span class="meta-label">Export Date:</span> ${new Date().toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' })}</div>
                <div class="meta-item"><span class="meta-label">Time:</span> ${new Date().toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit' })}</div>
                <div class="meta-item"><span class="meta-label">Total Records:</span> ${summary.total}</div>
            </div>
        </div>
        
        <div class="summary-section">
            <div class="summary-title">📊 Summary Statistics</div>
            <div class="summary-grid">
                <div class="summary-item">
                    <div class="summary-label">Total Records</div>
                    <div class="summary-value">${summary.total}</div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Passed</div>
                    <div class="summary-value" style="color: #16a34a;">${summary.passed}</div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Failed</div>
                    <div class="summary-value" style="color: #dc2626;">${summary.failed}</div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Conditional</div>
                    <div class="summary-value" style="color: #d97706;">${summary.conditional}</div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">First Timers</div>
                    <div class="summary-value">${summary.firstTimer}</div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Repeaters</div>
                    <div class="summary-value">${summary.repeater}</div>
                </div>
                <div class="summary-item highlight">
                    <div class="summary-label">Passing Rate</div>
                    <div class="summary-value">${summary.passingRate}%</div>
                </div>
            </div>
        </div>
        
        <h3 style="color: #3B6255; margin-top: 20px; font-size: 14px;">Detailed Records</h3>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Board Exam Type</th>
                    <th>Exam Date</th>
                    <th>Take Attempts</th>
                    <th>Result</th>
                </tr>
            </thead>
            <tbody>`;
            
            data.forEach(row => {
                htmlContent += `<tr><td>${row.id}</td><td>${row.examType}</td><td>${row.examDate}</td><td>${row.takeAttempts}</td><td>${row.result}</td></tr>`;
            });
            
            htmlContent += `
            </tbody>
        </table>
        <div class="footer">
            <div class="footer-logo">🎓 LSPU Board Exam Management System</div>
            <div class="footer-text">Laguna State Polytechnic University - San Pablo City Campus</div>
            <div class="footer-text">Document generated on ${new Date().toLocaleString('en-US')}</div>
        </div>
    </div>
</body>
</html>`;

            printWindow.document.open();
            printWindow.document.write(htmlContent);
            printWindow.document.close();
            
            // Auto-trigger print dialog after page loads
            printWindow.onload = function() {
                setTimeout(() => {
                    printWindow.focus();
                    printWindow.print();
                }, 500);
            };
        }

        function exportAsJSON(data, summary, timestamp) {
            const jsonData = {
                school: 'Laguna State Polytechnic University',
                campus: 'San Pablo City Campus',
                department: 'College of Engineering',
                reportTitle: 'Board Exam Performance Data',
                exportDate: new Date().toISOString(),
                exportDateFormatted: new Date().toLocaleString('en-US'),
                summary: {
                    totalRecords: summary.total,
                    passed: summary.passed,
                    failed: summary.failed,
                    conditional: summary.conditional,
                    firstTimers: summary.firstTimer,
                    repeaters: summary.repeater,
                    passingRate: summary.passingRate + '%'
                },
                records: data
            };
            
            const json = JSON.stringify(jsonData, null, 2);
            downloadFile(json, `lspu_engineering_board_exam_data_${timestamp}.json`, 'application/json');
        }

        function downloadFile(content, filename, mimeType) {
            const blob = new Blob([content], { type: mimeType });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            
            link.setAttribute('href', url);
            link.setAttribute('download', filename);
            link.style.visibility = 'hidden';
            
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        // Close modal when clicking outside
        document.getElementById('editModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeEditModal();
            }
        });

        document.getElementById('legendModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeLegendModal();
            }
        });

        document.getElementById('exportModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeExportModal();
            }
        });

        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeEditModal();
                closeLegendModal();
                closeExportModal();
            }
        });
    </script>
</body>
</html>
