<?php
session_start();

// Allow CBAA admin or ICTS admin
if (!isset($_SESSION["users"]) || ($_SESSION["users"] !== 'cbaa_admin@lspu.edu.ph' && $_SESSION["users"] !== 'icts_admin@lspu.edu.ph')) {
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
    $stmt = $conn->prepare("UPDATE anonymous_board_passers SET is_deleted = 1 WHERE id = ? AND department = 'Business Administration and Accountancy'");
    $stmt->bind_param('i', $delete_id);
    $stmt->execute();
    $stmt->close();
    header("Location: anonymous_dashboard_cbaa.php");
    exit();
}

// Handle edit request
if (isset($_POST['edit_id']) && isset($_POST['edit_result'])) {
    $edit_id = intval($_POST['edit_id']);
    $edit_result = $_POST['edit_result'];
    $edit_exam_type = $_POST['edit_exam_type'];
    
    if (in_array($edit_result, ['Passed', 'Failed', 'Conditional']) && in_array($edit_exam_type, ['First Timer', 'Repeater'])) {
        $stmt = $conn->prepare("UPDATE anonymous_board_passers SET result = ?, exam_type = ? WHERE id = ? AND department = 'Business Administration and Accountancy'");
        $stmt->bind_param('ssi', $edit_result, $edit_exam_type, $edit_id);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: anonymous_dashboard_cbaa.php");
    exit();
}

// Fetch anonymous data (excluding soft deleted)
$passers = $conn->query("SELECT * FROM anonymous_board_passers WHERE department='Business Administration and Accountancy' AND (is_deleted IS NULL OR is_deleted = 0) ORDER BY id ASC");
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
    <title>Anonymous Dashboard - Business Administration and Accountancy</title>
    <link rel="stylesheet" href="style.css"/>
    <link rel="stylesheet" href="css/sidebar.css"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #F59E0B;
            --primary-dark: #D97706;
            --success: #F59E0B;
            --danger: #64748b;
            --warning: #a8c5a5;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
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
            background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%);
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
            box-shadow: 0 8px 25px rgba(254, 227, 43, 0.4);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
            margin-bottom: 40px;
            max-width: 100%;
        }

        .stat-card {
            background: linear-gradient(145deg, rgba(255, 255, 255, 0.98) 0%, rgba(245, 251, 245, 0.95) 100%);
            border-radius: 18px;
            padding: 28px;
            box-shadow: 0 6px 20px rgba(254, 227, 43, 0.1);
            border: 2px solid rgba(254, 227, 43, 0.2);
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
            background: linear-gradient(90deg, #F59E0B, #D97706);
            opacity: 0;
            transition: opacity 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 12px 35px rgba(254, 227, 43, 0.25);
            border-color: rgba(254, 227, 43, 0.35);
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
            background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%);
            color: #fff;
        }

        .stat-card.passed .icon {
            background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%);
            color: #fff;
        }

        .stat-card.failed .icon {
            background: linear-gradient(135deg, #94a3af 0%, #6b7280 100%);
            color: #fff;
        }

        .stat-card.conditional .icon {
            background: linear-gradient(135deg, #FDE68A 0%, #FCD34D 100%);
            color: #78350F;
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
            font-size: 0.9rem;
            color: #D97706;
            font-weight: 600;
            margin-top: 4px;
        }

        .table-card {
            background: linear-gradient(145deg, rgba(255, 255, 255, 0.98) 0%, rgba(245, 251, 245, 0.95) 100%);
            border-radius: 20px;
            padding: 32px;
            box-shadow: 0 8px 30px rgba(254, 227, 43, 0.12);
            border: 2px solid rgba(254, 227, 43, 0.25);
            margin-top: 8px;
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            padding-bottom: 20px;
            border-bottom: 2px solid rgba(254, 227, 43, 0.15);
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
            background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%);
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
            border-bottom: 1px solid rgba(254, 227, 43, 0.1);
            transition: all 0.2s;
        }

        tbody tr:hover {
            background: rgba(254, 227, 43, 0.05);
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
            background: linear-gradient(135deg, #FEF3C7 0%, #FDE68A 100%);
            color: #92400E;
        }

        .badge.failed {
            background: linear-gradient(135deg, #e2e8f0 0%, #cbd5e1 100%);
            color: #475569;
        }

        .badge.conditional {
            background: linear-gradient(135deg, #FFFBEA 0%, #FEF3C7 100%);
            color: #D97706;
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
            background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%);
            color: white;
        }

        .btn-modal-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(254, 227, 43, 0.4);
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
            border-color: #F59E0B;
            box-shadow: 0 0 0 4px rgba(254, 227, 43, 0.15);
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
            border-bottom: 2px solid rgba(254, 227, 43, 0.2);
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
            border-left: 4px solid #F59E0B;
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
            background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%);
            color: white;
            padding: 4px 12px;
            border-radius: 999px;
            font-weight: 700;
            font-size: 0.9rem;
        }

        .legend-total {
            background: linear-gradient(135deg, #e8f5e9 0%, #d1e7dd 100%);
            border: 2px solid #F59E0B;
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
            background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%);
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

        .empty-state h3 {
            font-size: 1.3rem;
            margin-bottom: 12px;
            color: #475569;
        }

        @media (max-width: 1024px) {
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
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

    /* CBAA-specific sidebar color overrides for golden theme */
    html body .sidebar {
        background: #ffffff !important;
        box-shadow: 0 2px 8px rgba(135, 121, 40, 0.08) !important;
        border-right: 1px solid rgba(135, 121, 40, 0.1) !important;
    }

    html body .sidebar .logo {
        color: #AA4C0A !important;
    }

    html body .sidebar-nav a {
        color: #AA4C0A !important;
    }

    html body .sidebar-nav i,
    html body .sidebar-nav ion-icon {
        color: #E08600 !important;
    }

    html body .sidebar-nav a.active,
    html body .sidebar-nav a:hover {
        background: #E08600 !important;
        color: #FFFFFF !important;
        box-shadow: 0 8px 25px rgba(135, 121, 40, 0.25) !important;
    }

    html body .sidebar-nav a.active i,
    html body .sidebar-nav a.active ion-icon,
    html body .sidebar-nav a:hover i,
    html body .sidebar-nav a:hover ion-icon {
        color: #FFFFFF !important;
    }

    /* Mobile Responsive Styles */
    @media (max-width: 768px) {
        .main-content {
            margin-left: 0 !important;
            padding: 15px !important;
        }

        .topbar {
            left: 0 !important;
            padding: 12px 15px !important;
        }

        .dashboard-title {
            font-size: 16px !important;
        }

        .stats-grid {
            grid-template-columns: 1fr !important;
            gap: 15px !important;
        }

        .data-table-container {
            padding: 15px !important;
        }

        table {
            font-size: 13px !important;
        }

        table th,
        table td {
            padding: 8px !important;
        }

        .action-buttons {
            flex-direction: column !important;
            gap: 10px !important;
        }

        .action-buttons button {
            width: 100% !important;
        }
    }

    @media (max-width: 480px) {
        .topbar {
            padding: 10px !important;
        }

        .dashboard-title {
            font-size: 14px !important;
        }

        .stat-card {
            padding: 12px !important;
        }

        .stat-number {
            font-size: 24px !important;
        }

        .stat-label {
            font-size: 12px !important;
        }

        table {
            font-size: 12px !important;
        }

        table th,
        table td {
            padding: 6px 4px !important;
        }

        /* Hide less important columns on small screens */
        .hide-mobile {
            display: none !important;
        }
    }
    </style>
</head>
<body>
    <?php include __DIR__ . '/includes/cbaa_nav.php'; ?>
    
    <div class="topbar">
        <div class="dashboard-title">Business Administration and Accountancy Admin Dashboard</div>
        <div><a class="logout-btn" href="#" onclick="confirmLogout(event)">Logout</a></div>
    </div>

    <div class="main">
        <div class="page-header">
            <h2><i class="fas fa-chart-pie" style="margin-right: 12px;"></i>Data Dashboard</h2>
            <a href="testing_anonymous_data_cbaa.php" class="add-data-btn">
                <i class="fas fa-plus-circle"></i> Add Anonymous Data
            </a>
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
                    <a href="testing_anonymous_data_cbaa.php" class="add-data-btn">
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
                // Show all data combined - use totals to avoid double counting
                const allData = legendData.totals;
                
                if (allData && Object.keys(allData).length > 0) {
                    Object.keys(allData).sort().forEach(examType => {
                        content += `<div class="legend-section">`;
                        content += `<h4><i class="fas fa-graduation-cap"></i> ${examType}</h4>`;
                        
                        Object.keys(allData[examType]).sort().forEach(date => {
                            const count = allData[examType][date];
                            const dateTotal = count;
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
                }
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
            if (confirm('Are you sure you want to log out?')) {
                window.location.href = 'logout.php';
            }
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

        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeEditModal();
                closeLegendModal();
            }
        });
    </script>
</body>
</html>

