<?php
session_start();

// Only allow College of Teacher Education admin
if (!isset($_SESSION["users"]) || $_SESSION["users"] !== 'cte_admin@lspu.edu.ph') {
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

// Create anonymous_board_passers table if it doesn't exist
$create_table_sql = "CREATE TABLE IF NOT EXISTS anonymous_board_passers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    board_exam_type VARCHAR(255) NOT NULL,
    board_exam_date DATE NOT NULL,
    exam_type VARCHAR(100) NOT NULL COMMENT 'First Timer or Repeater',
    result VARCHAR(50) NOT NULL,
    department VARCHAR(100) DEFAULT 'Teacher Education',
    is_deleted TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_dept (department),
    INDEX idx_exam_type (board_exam_type),
    INDEX idx_result (result),
    INDEX idx_date (board_exam_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
$conn->query($create_table_sql);

// Load board exam types (Teacher Education only, exclude deleted)
$board_exam_types = [];
$type_stmt = $conn->prepare("SELECT id, exam_type_name FROM board_exam_types WHERE department='Teacher Education' AND (is_deleted IS NULL OR is_deleted = 0) ORDER BY exam_type_name");
if ($type_stmt && $type_stmt->execute()) {
    $type_result = $type_stmt->get_result();
    while ($row = $type_result->fetch_assoc()) {
        $board_exam_types[] = $row;
    }
    $type_stmt->close();
}

// Load board exam dates grouped by type (2019-2024)
$exam_dates_by_type = [];
$dates_sql = "SELECT d.exam_date, d.exam_description, d.exam_type_id
        FROM board_exam_dates d
        JOIN board_exam_types t ON t.id = d.exam_type_id
        WHERE d.department='Teacher Education' AND YEAR(d.exam_date) BETWEEN 2019 AND 2024
        ORDER BY d.exam_date DESC";
$dates_result = $conn->query($dates_sql);
if ($dates_result) {
    while ($row = $dates_result->fetch_assoc()) {
        $type_id = (string)$row['exam_type_id'];
        if (!isset($exam_dates_by_type[$type_id])) {
            $exam_dates_by_type[$type_id] = [];
        }
        $exam_dates_by_type[$type_id][] = [
            'date' => $row['exam_date'],
            'description' => $row['exam_description']
        ];
    }
}

// Messages
$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_anonymous'])) {
    $board_exam_type = trim($_POST['board_exam_type'] ?? '');
    $board_exam_date = $_POST['board_exam_date'] ?? '';
    $exam_type = $_POST['exam_type'] ?? '';
    $result = $_POST['result'] ?? '';
    $number_of_takers = intval($_POST['number_of_takers'] ?? 1);

    // Convert YYYY-MM format to YYYY-MM-01 for database storage
    if (!empty($board_exam_date) && preg_match('/^\d{4}-\d{2}$/', $board_exam_date)) {
        $board_exam_date .= '-01';
    }

    // Validation
    $errors = [];
    if (empty($board_exam_type)) {
        $errors[] = 'Board exam type is required';
    }
    if (empty($board_exam_date)) {
        $errors[] = 'Board exam date is required';
    } else {
        $exam_year = date('Y', strtotime($board_exam_date));
        if ($exam_year < 2019 || $exam_year > 2024) {
            $errors[] = 'Board exam date must be between January 1, 2019 and December 31, 2024';
        }
    }
    if (empty($exam_type) || !in_array($exam_type, ['First Timer', 'Repeater'])) {
        $errors[] = "Take attempts must be 'First Timer' or 'Repeater'";
    }
    if (empty($result) || !in_array($result, ['Passed', 'Failed', 'Conditional'])) {
        $errors[] = "Result must be 'Passed', 'Failed', or 'Conditional'";
    }
    if ($number_of_takers < 1 || $number_of_takers > 1000) {
        $errors[] = 'Number of takers must be between 1 and 1000';
    }

    if (empty($errors)) {
        // Insert anonymous data (bulk insert for multiple takers)
        $stmt = $conn->prepare("INSERT INTO anonymous_board_passers (board_exam_type, board_exam_date, exam_type, result, department) VALUES (?, ?, ?, ?, 'Teacher Education')");
        if ($stmt) {
            $stmt->bind_param('ssss', $board_exam_type, $board_exam_date, $exam_type, $result);
            $success_count = 0;
            $failed_count = 0;
            
            // Insert the specified number of records
            for ($i = 0; $i < $number_of_takers; $i++) {
                if ($stmt->execute()) {
                    $success_count++;
                } else {
                    $failed_count++;
                }
            }
            
            $stmt->close();
            
            if ($success_count > 0) {
                $success_message = "Successfully added {$success_count} anonymous record(s)!";
                if ($failed_count > 0) {
                    $success_message .= " ({$failed_count} failed)";
                }
                // Clear form
                $_POST = [];
            } else {
                $error_message = 'Error adding anonymous data';
            }
        } else {
            $error_message = 'Database error: ' . $conn->error;
        }
    } else {
        $error_message = implode('<br>', $errors);
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <title>Testing Anonymous Data - Teacher Education</title>
    <link rel="stylesheet" href="style.css"/>
    <link rel="stylesheet" href="css/sidebar.css?v=<?php echo time(); ?>"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #4663ac;
            --primary-dark: #c1d8f0;
            --success: #4663ac;
            --danger: #ef4444;
            --warning: #f59e0b;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

         /* CTE-specific sidebar color overrides */
    .sidebar .logo {
        color: #4663ac !important;
    }
    .sidebar-nav a {
        color: #c1d8f0 !important;
    }
    .sidebar-nav i,
    .sidebar-nav ion-icon {
        color: #4663ac !important;
    }
    .sidebar-nav a.active,
    .sidebar-nav a:hover {
        background: linear-gradient(90deg, #4663ac 0%, #c1d8f0 100%) !important;
        color: #fff !important;
    }
    
    .sidebar-nav a.active i,
    .sidebar-nav a.active ion-icon,
    .sidebar-nav a:hover i,
    .sidebar-nav a:hover ion-icon {
        color: #fff !important;
    }

    .topbar {
        position: fixed;
        top: 0;
        left: 260px;
        right: 0;
        background: linear-gradient(135deg, #4663ac 0%, #c1d8f0 100%);
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
        background: rgba(255, 255, 255, 0.1);
        color: #fff;
        border: 2px solid rgba(255, 255, 255, 0.3);
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
        background: rgba(255, 255, 255, 0.2);
        border-color: rgba(255, 255, 255, 0.5);
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
            color: #1a1a1a;
        }

        .view-dashboard-btn {
            background: #4663ac;
            color: #ffffff;
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

        .view-dashboard-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(254, 227, 43, 0.4);
        }

        .card {
            background: rgba(255, 255, 255, 0.98);
            border-radius: 20px;
            padding: 36px;
            box-shadow: 0 8px 30px rgba(254, 227, 43, 0.12);
            border: 2px solid rgba(254, 227, 43, 0.25);
            margin-bottom: 28px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 28px;
            margin-bottom: 28px;
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        label {
            font-weight: 600;
            font-size: 0.95rem;
            margin-bottom: 8px;
            color: #1a1a1a;
        }

        label .required {
            color: var(--danger);
            margin-left: 4px;
        }

        input[type="text"],
        input[type="date"],
        input[type="month"],
        select {
            width: 100%;
            padding: 15px 18px;
            font-size: 1rem;
            border-radius: 12px;
            border: 2px solid #c1d8f0;
            background: #ffffff;
            font-family: 'Inter', sans-serif;
            transition: all 0.3s ease;
            color: #334155;
        }

        input:hover,
        select:hover {
            border-color: #4663ac;
        }

        input:focus,
        select:focus {
            outline: none;
            border-color: #c1d8f0;
            box-shadow: 0 0 0 4px rgba(254, 227, 43, 0.15);
            background: #fafffe;
        }

        select {
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23877928' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 16px center;
            padding-right: 40px;
        }

        .btn-container {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
        }

        .btn-primary,
        .btn-secondary {
            padding: 14px 28px;
            font-size: 1rem;
            border-radius: 14px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: #4663ac;
            color: #1a1a1a;
            box-shadow: 0 4px 15px rgba(254, 227, 43, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(254, 227, 43, 0.4);
        }

        .btn-secondary {
            background: #fff;
            color: #c1d8f0;
            border: 2px solid #c1d8f0;
        }

        .btn-secondary:hover {
            background: #FDFDF9;
        }

        .alert {
            padding: 16px 20px;
            border-radius: 14px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 500;
        }

        .alert-success {
            background: #c1d8f0;
            color: #c1d8f0;
            border: 2px solid #4663ac;
        }

        .alert-error {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: #991b1b;
            border: 2px solid #ef4444;
        }

        .info-box {
            background: #FDFDF9;
            border: 2px solid rgba(254, 227, 43, 0.5);
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 28px;
            border-left: 5px solid #4663ac;
        }

        .info-box h3 {
            color: #c1d8f0;
            font-size: 1.15rem;
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 700;
        }

        .info-box p {
            color: #334155;
            line-height: 1.7;
            margin-bottom: 8px;
            font-size: 0.95rem;
        }

        .info-box p:last-child {
            margin-bottom: 0;
        }

        .btn-adjust,
        .btn-quick {
            padding: 10px 14px;
            border: 2px solid #c1d8f0;
            background: white;
            color: #c1d8f0;
            border-radius: 10px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 40px;
        }

        .btn-adjust:hover,
        .btn-quick:hover {
            background: #4663ac;
            color: #1a1a1a;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(254, 227, 43, 0.3);
        }

        .btn-adjust:active,
        .btn-quick:active {
            transform: translateY(0);
        }

        input[type="number"]::-webkit-inner-spin-button,
        input[type="number"]::-webkit-outer-spin-button {
            opacity: 1;
            height: 40px;
        }

        @media (max-width: 900px) {
            .main {
                margin-left: 80px;
            }
            .topbar {
                left: 80px;
            }
        }

        @media (max-width: 768px) {
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 16px;
            }
            .card {
                padding: 24px;
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
            .card {
                padding: 20px;
            }
            .btn-container {
                flex-direction: column;
            }
            .btn-primary,
            .btn-secondary {
                width: 100%;
                justify-content: center;
            }
        }

    /* Logout Modal Styles - Beautiful Yellow Theme Design */
    #logoutModal.modal {
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        width: 100vw !important;
        height: 100vh !important;
        background: rgba(15, 23, 42, 0.8) !important;
        backdrop-filter: blur(16px) !important;
        -webkit-backdrop-filter: blur(16px) !important;
        z-index: 9998 !important;
        display: none !important;
        justify-content: center !important;
        align-items: center !important;
        animation: fadeInOverlay 0.4s cubic-bezier(0.25, 0.8, 0.25, 1) !important;
    }

    #logoutModal.modal[style*="flex"] {
        display: flex !important;
    }

    @keyframes fadeInOverlay {
        from {
            opacity: 0;
            backdrop-filter: blur(0px);
        }
        to {
            opacity: 1;
            backdrop-filter: blur(16px);
        }
    }

    @keyframes slideInLogout {
        from {
            opacity: 0;
            transform: translateY(-40px) scale(0.9);
            filter: blur(4px);
        }
        to {
            opacity: 1;
            transform: translateY(0) scale(1);
            filter: blur(0px);
        }
    }

    #logoutModal .modal-content {
        background: rgba(255, 255, 255, 0.98) !important;
        backdrop-filter: blur(20px) !important;
        -webkit-backdrop-filter: blur(20px) !important;
        padding: 48px 44px !important;
        border-radius: 28px !important;
        box-shadow:
            0 32px 64px -12px rgba(254, 227, 43, 0.25),
            inset 0 1px 0 rgba(255, 255, 255, 0.9) !important;
        max-width: 480px !important;
        width: 92% !important;
        text-align: center !important;
        animation: slideInLogout 0.5s cubic-bezier(0.34, 1.56, 0.64, 1) !important;
        border: none !important;
        outline: none !important;
        position: relative !important;
        overflow: visible !important;
    }

    #logoutModal .modal-content::before {
        content: '' !important;
        position: absolute !important;
        top: -2px !important;
        left: -2px !important;
        right: -2px !important;
        bottom: -2px !important;
        background: linear-gradient(135deg, #c1d8f0 0%, #4663ac 25%, #c1d8f0 50%, #4663ac 75%, #c1d8f0 100%) !important;
        border-radius: 30px !important;
        z-index: -1 !important;
        opacity: 0.8 !important;
        animation: borderGradientRotate 4s linear infinite !important;
    }

    @keyframes borderGradientRotate {
        0% {
            background-position: 0% 50%;
        }
        50% {
            background-position: 100% 50%;
        }
        100% {
            background-position: 0% 50%;
        }
    }

    #logoutModal .modal-header {
        margin-bottom: 32px !important;
        background: #FDFDF9 !important;
        padding: 32px 28px !important;
        border-radius: 20px !important;
        border: 2px solid #4663ac !important;
        position: relative !important;
        overflow: hidden !important;
        box-shadow: 0 8px 25px rgba(254, 227, 43, 0.15) !important;
    }

    #logoutModal .modal-header::before {
        content: '' !important;
        position: absolute !important;
        top: 0 !important;
        left: 0 !important;
        right: 0 !important;
        height: 4px !important;
        background: linear-gradient(90deg, #c1d8f0 0%, #4663ac 50%, #c1d8f0 100%) !important;
        border-radius: 20px 20px 0 0 !important;
    }

    #logoutModal .modal-header::after {
        content: '' !important;
        position: absolute !important;
        top: -50px !important;
        right: -50px !important;
        width: 120px !important;
        height: 120px !important;
        background: linear-gradient(135deg, rgba(254, 227, 43, 0.1) 0%, rgba(251, 239, 156, 0.05) 100%) !important;
        border-radius: 50% !important;
        z-index: 0 !important;
    }

    #logoutModal .modal-icon {
        width: 88px !important;
        height: 88px !important;
        background: linear-gradient(135deg, #c1d8f0 0%, #4663ac 50%, #c1d8f0 100%) !important;
        border-radius: 50% !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        margin: 0 auto 24px !important;
        color: #1a1a1a !important;
        font-size: 2.2rem !important;
        box-shadow:
            0 20px 40px rgba(254, 227, 43, 0.4),
            0 0 0 4px rgba(255, 255, 255, 0.8),
            0 0 0 6px rgba(254, 227, 43, 0.2) !important;
        position: relative !important;
        z-index: 1 !important;
        animation: iconPulse 3s ease-in-out infinite !important;
    }

    @keyframes iconPulse {
        0%, 100% {
            box-shadow:
                0 20px 40px rgba(254, 227, 43, 0.4),
                0 0 0 4px rgba(255, 255, 255, 0.8),
                0 0 0 6px rgba(254, 227, 43, 0.2);
            transform: scale(1);
        }
        50% {
            box-shadow:
                0 25px 50px rgba(254, 227, 43, 0.6),
                0 0 0 6px rgba(255, 255, 255, 0.9),
                0 0 0 8px rgba(254, 227, 43, 0.3);
            transform: scale(1.05);
        }
    }

    #logoutModal .modal-icon::before {
        content: '' !important;
        position: absolute !important;
        top: -4px !important;
        left: -4px !important;
        right: -4px !important;
        bottom: -4px !important;
        background: linear-gradient(135deg, #c1d8f0, #4663ac, #c1d8f0) !important;
        border-radius: 50% !important;
        z-index: -1 !important;
        opacity: 0.6 !important;
        animation: rotateGradient 6s linear infinite !important;
    }

    @keyframes rotateGradient {
        0% {
            transform: rotate(0deg);
        }
        100% {
            transform: rotate(360deg);
        }
    }

    #logoutModal .modal-title {
        font-size: 1.75rem !important;
        font-weight: 800 !important;
        background: linear-gradient(135deg, #c1d8f0 0%, #1a1a1a 100%) !important;
        -webkit-background-clip: text !important;
        -webkit-text-fill-color: transparent !important;
        background-clip: text !important;
        margin: 0 0 12px 0 !important;
        letter-spacing: 0.5px !important;
        position: relative !important;
        z-index: 1 !important;
    }

    #logoutModal .modal-subtitle {
        font-size: 1.1rem !important;
        color: #c1d8f0 !important;
        margin: 0 !important;
        line-height: 1.6 !important;
        font-weight: 500 !important;
        position: relative !important;
        z-index: 1 !important;
    }

    #logoutModal .modal-text {
        font-size: 1rem !important;
        color: #334155 !important;
        margin-bottom: 36px !important;
        line-height: 1.7 !important;
        padding: 24px !important;
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%) !important;
        border-radius: 16px !important;
        border: 1px solid #e2e8f0 !important;
        position: relative !important;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05) !important;
    }

    #logoutModal .modal-text::before {
        content: '⚠️' !important;
        position: absolute !important;
        top: -12px !important;
        left: 50% !important;
        transform: translateX(-50%) !important;
        background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%) !important;
        border-radius: 50% !important;
        width: 24px !important;
        height: 24px !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        font-size: 0.8rem !important;
        box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3) !important;
    }

    #logoutModal .modal-buttons {
        display: flex !important;
        gap: 20px !important;
        justify-content: center !important;
        align-items: center !important;
        flex-wrap: nowrap !important;
    }

    #logoutModal .modal-btn {
        padding: 16px 32px !important;
        border: none !important;
        border-radius: 16px !important;
        font-size: 1rem !important;
        font-weight: 700 !important;
        font-family: 'Inter', sans-serif !important;
        cursor: pointer !important;
        transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1) !important;
        display: flex !important;
        align-items: center !important;
        gap: 10px !important;
        min-width: 150px !important;
        justify-content: center !important;
        position: relative !important;
        overflow: hidden !important;
    }

    #logoutModal .modal-btn::before {
        content: '' !important;
        position: absolute !important;
        top: 50% !important;
        left: 50% !important;
        width: 0 !important;
        height: 0 !important;
        background: rgba(255, 255, 255, 0.25) !important;
        border-radius: 50% !important;
        transform: translate(-50%, -50%) !important;
        transition: all 0.6s cubic-bezier(0.25, 0.8, 0.25, 1) !important;
        z-index: 0 !important;
        opacity: 0 !important;
    }

    #logoutModal .modal-btn:hover::before {
        width: 300px !important;
        height: 300px !important;
        opacity: 1 !important;
    }

    #logoutModal .modal-btn>* {
        position: relative !important;
        z-index: 1 !important;
    }

    #logoutModal .modal-btn:hover>i {
        transform: scale(1.15) rotate(5deg) !important;
    }

    #logoutModal .modal-btn .btn-text {
        transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1) !important;
    }

    #logoutModal .modal-btn .btn-spinner {
        position: absolute !important;
        left: 50% !important;
        top: 50% !important;
        transform: translate(-50%, -50%) translateX(20px) !important;
        opacity: 0 !important;
        transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1) !important;
        animation: spin 1s linear infinite !important;
    }

    #logoutModal .modal-btn .btn-check {
        position: absolute !important;
        left: 50% !important;
        top: 50% !important;
        transform: translate(-50%, -50%) translateX(20px) scale(0.8) !important;
        opacity: 0 !important;
        transition: all 0.5s cubic-bezier(0.34, 1.56, 0.64, 1) !important;
        color: #1a1a1a !important;
    }

    @keyframes spin {
        0% { transform: translate(-50%, -50%) rotate(0deg); }
        100% { transform: translate(-50%, -50%) rotate(360deg); }
    }

    #logoutModal .modal-btn.logout-confirm {
        background: linear-gradient(135deg, #c1d8f0 0%, #4663ac 50%, #c1d8f0 100%) !important;
        color: #1a1a1a !important;
    }

    #logoutModal .modal-btn.logout-confirm:hover {
        background: linear-gradient(135deg, #c1d8f0 0%, #4663ac 50%, #c1d8f0 100%) !important;
        transform: translateY(-3px) scale(1.05) !important;
    }

    #logoutModal .modal-btn.logout-confirm.loading {
        background: linear-gradient(135deg, #64748b 0%, #475569 50%, #374151 100%) !important;
        cursor: not-allowed !important;
        pointer-events: none !important;
    }

    #logoutModal .modal-btn.logout-confirm.loading .btn-text {
        opacity: 0 !important;
        transform: translateX(-20px) !important;
    }

    #logoutModal .modal-btn.logout-confirm.loading .btn-spinner {
        opacity: 1 !important;
        transform: translateX(0) !important;
    }

    #logoutModal .modal-btn.logout-confirm.success {
        background: linear-gradient(135deg, #4663ac 0%, #c1d8f0 50%, #c1d8f0 100%) !important;
        transform: translateY(-2px) scale(1.05) !important;
        box-shadow: 0 12px 30px rgba(254, 227, 43, 0.4) !important;
    }

    #logoutModal .modal-btn.logout-confirm.success .btn-text {
        opacity: 0 !important;
        transform: translateX(-20px) !important;
    }

    #logoutModal .modal-btn.logout-confirm.success .btn-check {
        opacity: 1 !important;
        transform: translateX(0) scale(1.2) !important;
    }

    #logoutModal .modal-btn.logout-cancel {
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%) !important;
        color: #64748b !important;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08) !important;
    }

    #logoutModal .modal-btn.logout-cancel:hover {
        background: linear-gradient(135deg, #e2e8f0 0%, #cbd5e0 100%) !important;
        color: #475569 !important;
        transform: translateY(-2px) scale(1.05) !important;
    }

    @media (max-width: 640px) {
        #logoutModal .modal-content {
            width: 95% !important;
            padding: 36px 32px !important;
        }
        #logoutModal .modal-buttons {
            flex-direction: column !important;
            gap: 16px !important;
        }
        #logoutModal .modal-btn {
            width: 100% !important;
        }
    }
    </style>
</head>
<body>
    <?php include __DIR__ . '/includes/CTE_nav.php'; ?>
    
    <div class="topbar">
        <div class="dashboard-title">Teacher Education Admin Dashboard</div>
        <div><a class="logout-btn" href="#" onclick="confirmLogout(event)">Logout</a></div>
    </div>

    <div class="main">
        <div class="page-header">
            <h2><i class="fas fa-flask" style="margin-right: 12px;"></i>Add Anonymous Board Examinee Data</h2>
            <a href="anonymous_dashboard_CTE.php" class="view-dashboard-btn">
                <i class="fas fa-chart-pie"></i> View Anonymous Dashboard
            </a>
        </div>

        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <div class="info-box">
            <h3><i class="fas fa-info-circle"></i> About Testing Anonymous Data</h3>
            <p>This feature allows you to add anonymous board examinee data for testing and statistical purposes.</p>
            <p><strong>Note:</strong> No personal information (name, course, etc.) is required. Only exam-related data is collected.</p>
        </div>

        <div class="card">
            <form method="POST" id="anonymousForm">
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label for="board_exam_type">
                            Board Exam Type <span class="required">*</span>
                        </label>
                        <select name="board_exam_type" id="board_exam_type" required>
                            <option value="">-- Select Board Exam Type --</option>
                            <?php foreach ($board_exam_types as $type): ?>
                                <option value="<?php echo htmlspecialchars($type['exam_type_name']); ?>"
                                        data-type-id="<?php echo $type['id']; ?>"
                                        <?php echo (isset($_POST['board_exam_type']) && $_POST['board_exam_type'] === $type['exam_type_name']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($type['exam_type_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="board_exam_date">
                            Board Exam Date <span class="required">*</span>
                        </label>
                        <select name="board_exam_date" id="board_exam_date" required disabled>
                            <option value="">-- Select exam type first --</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="exam_type">
                            Take Attempts <span class="required">*</span>
                        </label>
                        <select name="exam_type" id="exam_type" required>
                            <option value="">-- Select Take Attempts --</option>
                            <option value="First Timer" <?php echo (isset($_POST['exam_type']) && $_POST['exam_type'] === 'First Timer') ? 'selected' : ''; ?>>First Timer</option>
                            <option value="Repeater" <?php echo (isset($_POST['exam_type']) && $_POST['exam_type'] === 'Repeater') ? 'selected' : ''; ?>>Repeater</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="result">
                            Result <span class="required">*</span>
                        </label>
                        <select name="result" id="result" required>
                            <option value="">-- Select Result --</option>
                            <option value="Passed" <?php echo (isset($_POST['result']) && $_POST['result'] === 'Passed') ? 'selected' : ''; ?>>Passed</option>
                            <option value="Failed" <?php echo (isset($_POST['result']) && $_POST['result'] === 'Failed') ? 'selected' : ''; ?>>Failed</option>
                            <option value="Conditional" <?php echo (isset($_POST['result']) && $_POST['result'] === 'Conditional') ? 'selected' : ''; ?>>Conditional</option>
                        </select>
                    </div>

                    <div class="form-group full-width">
                        <label for="number_of_takers">
                            <i class="fas fa-users"></i>Number of Takers <span class="required">*</span>
                        </label>
                        <div style="display: flex; align-items: center; gap: 16px; flex-wrap: wrap;">
                            <input type="number" id="number_of_takers" name="number_of_takers" 
                                value="<?php echo isset($_POST['number_of_takers']) ? intval($_POST['number_of_takers']) : 1; ?>" 
                                min="1" max="1000" required
                                style="flex: 1; min-width: 150px;">
                            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                <button type="button" onclick="adjustTakers(-1)" class="btn-adjust" title="Decrease">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <button type="button" onclick="adjustTakers(1)" class="btn-adjust" title="Increase">
                                    <i class="fas fa-plus"></i>
                                </button>
                                <button type="button" onclick="setTakers(10)" class="btn-quick" title="Set to 10">
                                    10
                                </button>
                                <button type="button" onclick="setTakers(50)" class="btn-quick" title="Set to 50">
                                    50
                                </button>
                                <button type="button" onclick="setTakers(100)" class="btn-quick" title="Set to 100">
                                    100
                                </button>
                            </div>
                        </div>
                        <p style="font-size: 0.85rem; color: #64748b; margin-top: 8px;">
                            <i class="fas fa-info-circle"></i> Add multiple examinees with the same exam details and result (1-1000)
                        </p>
                    </div>
                </div>

                <div class="btn-container">
                    <button type="reset" class="btn-secondary">
                        <i class="fas fa-eraser"></i> Clear Form
                    </button>
                    <button type="submit" name="add_anonymous" class="btn-primary">
                        <i class="fas fa-plus-circle"></i> Add Data
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function adjustTakers(delta) {
            const input = document.getElementById('number_of_takers');
            const currentValue = parseInt(input.value) || 1;
            const newValue = Math.max(1, Math.min(1000, currentValue + delta));
            input.value = newValue;
            input.focus();
        }

        function setTakers(value) {
            const input = document.getElementById('number_of_takers');
            input.value = Math.max(1, Math.min(1000, value));
            input.focus();
        }

        // Board exam dates data from PHP
        const examDatesByType = <?php echo json_encode($exam_dates_by_type); ?>;

        // Handle board exam type change to populate dates
        const boardExamTypeSelect = document.getElementById('board_exam_type');
        const boardExamDateSelect = document.getElementById('board_exam_date');

        boardExamTypeSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const typeId = selectedOption.getAttribute('data-type-id');
            
            // Clear and reset date select
            boardExamDateSelect.innerHTML = '<option value="">-- Select Exam Date --</option>';
            
            if (typeId && examDatesByType[typeId]) {
                const dates = examDatesByType[typeId];
                dates.forEach(dateObj => {
                    const option = document.createElement('option');
                    // Format date as YYYY-MM for display
                    const dateStr = dateObj.date.substring(0, 7); // Get YYYY-MM
                    option.value = dateStr;
                    
                    // Format display date
                    const [year, month] = dateStr.split('-');
                    const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
                                       'July', 'August', 'September', 'October', 'November', 'December'];
                    const displayDate = monthNames[parseInt(month) - 1] + ' ' + year;
                    
                    option.textContent = displayDate + (dateObj.description ? ' - ' + dateObj.description : '');
                    boardExamDateSelect.appendChild(option);
                });
                boardExamDateSelect.disabled = false;
            } else {
                boardExamDateSelect.disabled = true;
            }
        });
    </script>

    <!-- Logout Confirmation Modal -->
    <div id="logoutModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-icon">
                    <i class="fas fa-sign-out-alt"></i>
                </div>
                <h2 class="modal-title">Confirm Logout</h2>
                <p class="modal-subtitle">Are you sure you want to sign out?</p>
            </div>
            <p class="modal-text">You will be redirected to the login page and any unsaved changes will be lost.</p>
            <div class="modal-buttons">
                <button id="logoutConfirmYes" class="modal-btn logout-confirm">
                    <i class="fas fa-check"></i>
                    <span class="btn-text">Yes, Logout</span>
                    <i class="fas fa-spinner btn-spinner"></i>
                    <i class="fas fa-check-circle btn-check"></i>
                </button>
                <button id="logoutConfirmNo" class="modal-btn logout-cancel">
                    <i class="fas fa-times"></i> Cancel
                </button>
            </div>
        </div>
    </div>

    <script>
    function confirmLogout(event) {
        event.preventDefault();
        const modal = document.getElementById('logoutModal');
        if (modal) {
            modal.style.display = 'flex';
            modal.classList.add('show');
            
            const yesBtn = document.getElementById('logoutConfirmYes');
            const noBtn = document.getElementById('logoutConfirmNo');
            
            if (yesBtn) {
                yesBtn.onclick = function(e) {
                    e.preventDefault();
                    handleInteractiveLogout(this);
                };
            }
            
            if (noBtn) {
                noBtn.onclick = function() {
                    modal.style.display = 'none';
                };
            }
        }
        return false;
    }

    function handleInteractiveLogout(button) {
        if (button.classList.contains('loading')) return;
        button.classList.add('loading');

        const cancelBtn = document.getElementById('logoutConfirmNo');
        if (cancelBtn) {
            cancelBtn.style.opacity = '0.5';
            cancelBtn.style.pointerEvents = 'none';
        }

        setTimeout(() => {
            button.classList.remove('loading');
            button.classList.add('success');
            showLogoutSuccessMessage();
            setTimeout(() => {
                window.location.href = 'logout.php';
            }, 1500);
        }, 2000);
    }

    function showLogoutSuccessMessage() {
        const messageDiv = document.createElement('div');
        messageDiv.innerHTML = `
        <div style="
          position: fixed;
          top: 50%;
          left: 50%;
          transform: translate(-50%, -50%);
          background: linear-gradient(135deg, #4663ac 0%, #c1d8f0 100%);
          color: #1a1a1a;
          padding: 20px 32px;
          border-radius: 16px;
          box-shadow: 0 16px 40px rgba(254, 227, 43, 0.4);
          z-index: 10002;
          font-family: 'Inter', sans-serif;
          font-weight: 700;
          text-align: center;
          min-width: 300px;
          animation: successSlideIn 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
        ">
          <div style="display: flex; align-items: center; justify-content: center; gap: 12px; font-size: 1.1rem;">
            <i class="fas fa-check-circle" style="font-size: 1.3rem; animation: successCheckBounce 0.8s cubic-bezier(0.68, -0.55, 0.265, 1.55) 0.3s both;"></i>
            Logout Successful!
          </div>
          <div style="font-size: 0.9rem; font-weight: 500; margin-top: 8px; opacity: 0.9;">
            Redirecting to login page...
          </div>
        </div>
        <style>
          @keyframes successSlideIn {
            0% { opacity: 0; transform: translate(-50%, -50%) scale(0.8) translateY(20px); }
            100% { opacity: 1; transform: translate(-50%, -50%) scale(1) translateY(0); }
          }
          @keyframes successCheckBounce {
            0% { transform: scale(0) rotate(-180deg); }
            70% { transform: scale(1.2) rotate(10deg); }
            100% { transform: scale(1) rotate(0deg); }
          }
        </style>
      `;
        document.body.appendChild(messageDiv);
        setTimeout(() => {
            document.body.removeChild(messageDiv);
        }, 3000);
    }

    document.getElementById('logoutModal').onclick = function(e) {
        if (e.target === this) {
            this.style.display = 'none';
        }
    };
    </script>

    <script>
        // Form validation
        document.getElementById('anonymousForm').addEventListener('submit', function(e) {
            const boardExamType = document.getElementById('board_exam_type').value;
            const boardExamDate = document.getElementById('board_exam_date').value;
            const examType = document.getElementById('exam_type').value;
            const result = document.getElementById('result').value;

            if (!boardExamType || !boardExamDate || !examType || !result) {
                e.preventDefault();
                alert('Please fill in all required fields');
                return false;
            }
        });
    </script>
</body>
</html>



