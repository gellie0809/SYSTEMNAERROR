<?php
session_start();
// Only allow College of Engineering admin
if (!isset($_SESSION["users"]) || $_SESSION["users"] !== 'eng_admin@lspu.edu.ph') {
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

// Ensure table exists - auto-create if missing
function ensureDatabaseExists($conn) {
    $tableCheck = $conn->query("SHOW TABLES LIKE 'board_passers'");
    if (!$tableCheck || $tableCheck->num_rows == 0) {
        $createTable = "
        CREATE TABLE board_passers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            first_name VARCHAR(100) DEFAULT NULL,
            last_name VARCHAR(100) DEFAULT NULL,
            middle_name VARCHAR(100) DEFAULT NULL,
            sex VARCHAR(10) NOT NULL,
            course VARCHAR(255) NOT NULL,
            year_graduated INT NOT NULL,
            board_exam_date DATE NOT NULL,
            result VARCHAR(20) NOT NULL DEFAULT 'PASSED',
            department VARCHAR(100) NOT NULL DEFAULT 'Engineering',
            exam_type VARCHAR(255) DEFAULT NULL,
            board_exam_type VARCHAR(255) DEFAULT 'Board Exam',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        return $conn->query($createTable);
    }
    return true;
}

// Initialize database
ensureDatabaseExists($conn);

// Handle CSV export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $query = "SELECT name, course, year_graduated, board_exam_date, result, exam_type, board_exam_type 
              FROM board_passers 
              WHERE department = 'Engineering' 
              ORDER BY name ASC";
    
    $result = $conn->query($query);
    
    if ($result->num_rows > 0) {
        // Set headers for CSV download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="board_passers_export_' . date('Y-m-d_H-i-s') . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Create file pointer
        $output = fopen('php://output', 'w');
        
        // Add CSV headers
        fputcsv($output, ['Name', 'Course', 'Year Graduated', 'Board Exam Date', 'Result', 'Exam Type', 'Board Exam Type']);
        
        // Add data rows
        while ($row = $result->fetch_assoc()) {
            // Format the date properly to avoid hashtag issue
            $formattedDate = date('Y-m-d', strtotime($row['board_exam_date']));
            
            fputcsv($output, [
                $row['name'],
                $row['course'],
                $row['year_graduated'],
                $formattedDate, // Properly formatted date
                $row['result'],
                $row['exam_type'],
                $row['board_exam_type']
            ]);
        }
        
        fclose($output);
        exit();
    } else {
        $error_message = "No data found to export.";
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Export Data - Engineering Dashboard</title>
    <link rel="stylesheet" href="style.css" />
    <link rel="stylesheet" href="css/sidebar.css" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        background: linear-gradient(135deg, #E2DFDA 0%, #CBDED3 100%);
        margin: 0;
        font-family: 'Inter', sans-serif;
        min-height: 100vh;
    }

    /* Sidebar styling moved to css/sidebar.css (shared) */
    
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

    .main-content {
        margin-left: 260px;
        margin-top: 70px;
        padding: 40px;
        min-height: calc(100vh - 70px);
        background: transparent;
    }

    .card {
        background: #ffffff;
        border-radius: 24px;
        box-shadow: 0 20px 60px rgba(22, 41, 56, 0.1);
        padding: 48px;
        margin: 0;
        border: 1px solid #e2e8f0;
        text-align: center;
    }

    .card h2 {
        font-size: 2.2rem;
        font-weight: 700;
        margin-bottom: 40px;
        color: #fff;
        background: linear-gradient(135deg, #3B6255 0%, #8BA49A 100%);
        padding: 24px 40px;
        border-radius: 16px;
        box-shadow: 0 12px 40px rgba(90, 133, 95, 0.3);
        text-align: center;
        letter-spacing: 1.2px;
        text-transform: uppercase;
        font-family: 'Inter', sans-serif;
        margin: 0 0 40px 0;
    }

    .coming-soon {
        font-size: 1.5rem;
        color: #4a5568;
        margin-bottom: 20px;
    }

    .feature-icon {
        font-size: 4rem;
        color: #3B6255;
        margin-bottom: 30px;
    }

    /* Export Page Styles */
    .card-header {
        padding: 40px 50px;
        background: linear-gradient(135deg, #3B6255 0%, #8BA49A 100%);
        color: white;
        position: relative;
        overflow: hidden;
    }

    .card-header::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -50%;
        width: 100%;
        height: 200%;
        background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.15), transparent);
        animation: shimmer 4s infinite;
    }

    @keyframes shimmer {
        0% {
            transform: translateX(-100%) translateY(-100%) rotate(45deg);
            opacity: 0;
        }

        50% {
            opacity: 1;
        }

        100% {
            transform: translateX(100%) translateY(100%) rotate(45deg);
            opacity: 0;
        }
    }

    .header-content {
        display: flex;
        align-items: center;
        gap: 20px;
        position: relative;
        z-index: 2;
    }

    .header-icon {
        font-size: 2.5rem;
        color: white;
        opacity: 0.9;
    }

    .header-title {
        font-size: 2rem;
        font-weight: 800;
        margin: 0;
        letter-spacing: 1px;
    }

    .header-subtitle {
        font-size: 1.1rem;
        margin: 8px 0 0 0;
        opacity: 0.9;
        font-weight: 500;
    }

    .card-content {
        padding: 50px;
    }

    .export-options {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 30px;
        margin-bottom: 40px;
    }

    .export-card {
        background: linear-gradient(145deg, rgba(255, 255, 255, 0.95) 0%, rgba(248, 250, 252, 0.9) 100%);
        backdrop-filter: blur(20px);
        border-radius: 20px;
        padding: 30px;
        text-align: center;
        border: 2px solid rgba(90, 133, 95, 0.1);
        box-shadow: 0 15px 35px rgba(90, 133, 95, 0.1);
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .export-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 25px 50px rgba(90, 133, 95, 0.15);
        border-color: rgba(90, 133, 95, 0.2);
    }

    .export-icon {
        width: 80px;
        height: 80px;
        margin: 0 auto 20px;
        background: linear-gradient(135deg, #3B6255 0%, #8BA49A 100%);
        border-radius: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        color: white;
        box-shadow: 0 10px 25px rgba(90, 133, 95, 0.3);
    }

    .export-title {
        font-size: 1.4rem;
        font-weight: 700;
        color: #1f2937;
        margin: 0 0 12px 0;
    }

    .export-description {
        font-size: 1rem;
        color: #6b7280;
        line-height: 1.6;
        margin: 0 0 24px 0;
    }

    .export-btn {
        background: linear-gradient(135deg, #3B6255 0%, #8BA49A 100%);
        color: white;
        border: none;
        border-radius: 12px;
        padding: 14px 28px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 4px 15px rgba(90, 133, 95, 0.3);
    }

    .export-btn:hover {
        background: linear-gradient(135deg, #2d5a2e 0%, #3B6255 100%);
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(90, 133, 95, 0.4);
    }

    .export-csv {
        background: linear-gradient(135deg, #059669 0%, #10b981 100%) !important;
        box-shadow: 0 4px 15px rgba(5, 150, 105, 0.3) !important;
    }

    .export-csv:hover {
        background: linear-gradient(135deg, #047857 0%, #059669 100%) !important;
        box-shadow: 0 8px 25px rgba(5, 150, 105, 0.4) !important;
    }

    .export-info {
        background: linear-gradient(145deg, rgba(255, 255, 255, 0.9) 0%, rgba(248, 250, 252, 0.8) 100%);
        border-radius: 16px;
        padding: 30px;
        border: 2px solid rgba(90, 133, 95, 0.1);
    }

    .info-card h4 {
        color: #3B6255;
        font-size: 1.2rem;
        font-weight: 700;
        margin: 0 0 20px 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .info-card ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .info-card li {
        padding: 8px 0;
        color: #4b5563;
        font-size: 1rem;
        line-height: 1.6;
        border-bottom: 1px solid rgba(44, 90, 160, 0.1);
    }

    .info-card li:last-child {
        border-bottom: none;
    }

    .alert {
        padding: 20px 24px;
        border-radius: 16px;
        margin-bottom: 24px;
        display: flex;
        align-items: center;
        gap: 12px;
        font-weight: 600;
    }

    .alert-error {
        background: linear-gradient(145deg, rgba(239, 68, 68, 0.15) 0%, rgba(220, 38, 38, 0.1) 100%);
        border: 2px solid rgba(239, 68, 68, 0.3);
        color: #dc2626;
    }

    @media (max-width: 1200px) {
        .main-content {
            padding: 24px;
        }

        .card {
            padding: 32px 24px;
        }
    }

    @media (max-width: 900px) {
        .main-content {
            margin-left: 80px;
        }

        .topbar {
            left: 80px;
        }

        .sidebar {
            width: 80px !important;

        }
    }

    @media (max-width: 600px) {
        .sidebar {
            display: none;
        }

        .topbar,
        .main-content {
            margin-left: 80px;
        }

        .topbar {
            padding: 16px 20px;
        }

        .topbar {
            left: 0px;
        }
    }

    /* Responsive sidebar behavior moved to css/sidebar.css */

    /* Logout Modal Styles - Beautiful Blue Theme Design */
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
            0 32px 64px -12px rgba(90, 133, 95, 0.25),
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
        background: linear-gradient(135deg, #3B6255 0%, #8BA49A 25%, #a8c5a5 50%, #8BA49A 75%, #3B6255 100%) !important;
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
        background: linear-gradient(135deg, #E2DFDA 0%, #CBDED3 100%) !important;
        padding: 32px 28px !important;
        border-radius: 20px !important;
        border: 2px solid #c5dcc2 !important;
        position: relative !important;
        overflow: hidden !important;
        box-shadow: 0 8px 25px rgba(145, 179, 142, 0.15) !important;
    }

    #logoutModal .modal-header::before {
        content: '' !important;
        position: absolute !important;
        top: 0 !important;
        left: 0 !important;
        right: 0 !important;
        height: 4px !important;
        background: linear-gradient(90deg, #3B6255 0%, #8BA49A 50%, #a8c5a5 100%) !important;
        border-radius: 20px 20px 0 0 !important;
    }

    #logoutModal .modal-header::after {
        content: '' !important;
        position: absolute !important;
        top: -50px !important;
        right: -50px !important;
        width: 120px !important;
        height: 120px !important;
        background: linear-gradient(135deg, rgba(145, 179, 142, 0.1) 0%, rgba(168, 197, 165, 0.05) 100%) !important;
        border-radius: 50% !important;
        z-index: 0 !important;
    }

    #logoutModal .modal-icon {
        width: 88px !important;
        height: 88px !important;
        background: linear-gradient(135deg, #3B6255 0%, #8BA49A 50%, #2d5a2e 100%) !important;
        border-radius: 50% !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        margin: 0 auto 24px !important;
        color: white !important;
        font-size: 2.2rem !important;
        box-shadow:
            0 20px 40px rgba(90, 133, 95, 0.4),
            0 0 0 4px rgba(255, 255, 255, 0.8),
            0 0 0 6px rgba(145, 179, 142, 0.2) !important;
        position: relative !important;
        z-index: 1 !important;
        animation: iconPulse 3s ease-in-out infinite !important;
    }

    @keyframes iconPulse {

        0%,
        100% {
            box-shadow:
                0 20px 40px rgba(90, 133, 95, 0.4),
                0 0 0 4px rgba(255, 255, 255, 0.8),
                0 0 0 6px rgba(145, 179, 142, 0.2);
            transform: scale(1);
        }

        50% {
            box-shadow:
                0 25px 50px rgba(90, 133, 95, 0.6),
                0 0 0 6px rgba(255, 255, 255, 0.9),
                0 0 0 8px rgba(145, 179, 142, 0.3);
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
        background: linear-gradient(135deg, #a8c5a5, #8BA49A, #3B6255, #2d5a2e) !important;
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
        background: linear-gradient(135deg, #3B6255 0%, #2d5a2e 100%) !important;
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
        color: #2d5a2e !important;
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
        border: none !important;
        border-top: none !important;
        border-bottom: none !important;
        border-left: none !important;
        border-right: none !important;
        outline: none !important;
        background: transparent !important;
        margin-top: 0 !important;
        padding: 0 !important;
        box-shadow: none !important;
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
        text-transform: none !important;
        letter-spacing: 0.3px !important;
        visibility: visible !important;
        opacity: 1 !important;
        pointer-events: auto !important;
        position: relative !important;
        z-index: 10001 !important;
        overflow: hidden !important;
        outline: none !important;
        box-shadow: none !important;
        background-clip: padding-box !important;
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

    #logoutModal .modal-btn:active::before {
        width: 350px !important;
        height: 350px !important;
        opacity: 0.8 !important;
        transition: all 0.2s ease !important;
    }

    #logoutModal .modal-btn>* {
        position: relative !important;
        z-index: 1 !important;
        transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1) !important;
    }

    #logoutModal .modal-btn:hover>i {
        transform: scale(1.15) rotate(5deg) !important;
    }

    #logoutModal .modal-btn:active>i {
        transform: scale(0.9) rotate(-5deg) !important;
    }

    /* Interactive button content states */
    #logoutModal .modal-btn .btn-text {
        transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1) !important;
        opacity: 1 !important;
        transform: translateX(0) !important;
    }

    #logoutModal .modal-btn .btn-spinner {
        position: absolute !important;
        left: 50% !important;
        top: 50% !important;
        transform: translate(-50%, -50%) translateX(20px) !important;
        opacity: 0 !important;
        transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1) !important;
        font-size: 1rem !important;
        animation: spin 1s linear infinite !important;
    }

    #logoutModal .modal-btn .btn-check {
        position: absolute !important;
        left: 50% !important;
        top: 50% !important;
        transform: translate(-50%, -50%) translateX(20px) scale(0.8) !important;
        opacity: 0 !important;
        transition: all 0.5s cubic-bezier(0.34, 1.56, 0.64, 1) !important;
        font-size: 1.1rem !important;
        color: white !important;
    }

    @keyframes spin {
        0% {
            transform: translate(-50%, -50%) rotate(0deg);
        }

        100% {
            transform: translate(-50%, -50%) rotate(360deg);
        }
    }

    /* Pulse effect for logout button */
    #logoutModal .modal-btn.logout-confirm:focus {
        animation: logoutPulse 0.6s ease-in-out !important;
    }

    @keyframes logoutPulse {
        0% {
            box-shadow: none;
            transform: scale(1);
        }

        50% {
            box-shadow: none;
            transform: scale(1.02);
        }

        100% {
            box-shadow: none;
            transform: scale(1);
        }
    }

    #logoutModal .modal-btn.logout-confirm {
        background: linear-gradient(135deg, #3B6255 0%, #8BA49A 50%, #2d5a2e 100%) !important;
        color: #ffffff !important;
        border: none !important;
        outline: none !important;
        box-shadow: none !important;
        position: relative !important;
        overflow: hidden !important;
    }

    #logoutModal .modal-btn.logout-confirm::after {
        content: '' !important;
        position: absolute !important;
        top: 50% !important;
        left: 50% !important;
        width: 0 !important;
        height: 0 !important;
        background: radial-gradient(circle, rgba(255, 255, 255, 0.4) 0%, rgba(255, 255, 255, 0.1) 70%, transparent 100%) !important;
        border-radius: 50% !important;
        transform: translate(-50%, -50%) !important;
        transition: all 0.8s cubic-bezier(0.25, 0.8, 0.25, 1) !important;
        z-index: 0 !important;
        opacity: 0 !important;
    }

    #logoutModal .modal-btn.logout-confirm:hover {
        background: linear-gradient(135deg, #2d5a2e 0%, #3B6255 50%, #8BA49A 100%) !important;
        transform: translateY(-3px) scale(1.05) !important;
        box-shadow: none !important;
    }

    #logoutModal .modal-btn.logout-confirm:hover::after {
        width: 120px !important;
        height: 120px !important;
        opacity: 1 !important;
    }

    #logoutModal .modal-btn.logout-confirm:active {
        transform: translateY(-1px) scale(1.02) !important;
        background: linear-gradient(135deg, #2d5a2e 0%, #8BA49A 50%, #3B6255 100%) !important;
    }

    #logoutModal .modal-btn.logout-confirm:active::after {
        width: 200px !important;
        height: 200px !important;
        opacity: 0.8 !important;
        transition: all 0.3s ease !important;
    }

    /* Loading state for logout button */
    #logoutModal .modal-btn.logout-confirm.loading {
        background: linear-gradient(135deg, #64748b 0%, #475569 50%, #374151 100%) !important;
        cursor: not-allowed !important;
        transform: translateY(0) scale(1) !important;
        pointer-events: none !important;
    }

    #logoutModal .modal-btn.logout-confirm.loading::after {
        display: none !important;
    }

    #logoutModal .modal-btn.logout-confirm.loading .btn-text {
        opacity: 0 !important;
        transform: translateX(-20px) !important;
    }

    #logoutModal .modal-btn.logout-confirm.loading .btn-spinner {
        opacity: 1 !important;
        transform: translateX(0) !important;
    }

    /* Success state for logout button */
    #logoutModal .modal-btn.logout-confirm.success {
        background: linear-gradient(135deg, #10b981 0%, #059669 50%, #047857 100%) !important;
        transform: translateY(-2px) scale(1.05) !important;
        box-shadow: 0 12px 30px rgba(16, 185, 129, 0.4) !important;
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
        border: none !important;
        outline: none !important;
        box-shadow:
            0 4px 12px rgba(0, 0, 0, 0.08),
            0 0 0 0px transparent !important;
    }

    #logoutModal .modal-btn.logout-cancel:hover {
        background: linear-gradient(135deg, #e2e8f0 0%, #cbd5e0 100%) !important;
        color: #475569 !important;
        transform: translateY(-2px) scale(1.05) !important;
        box-shadow:
            0 8px 20px rgba(0, 0, 0, 0.12),
            0 0 0 0px transparent !important;
    }

    /* Force logout buttons to show with highest specificity - Clean Design */
    #logoutModal #logoutConfirmYes,
    #logoutModal #logoutConfirmNo {
        display: flex !important;
        visibility: visible !important;
        opacity: 1 !important;
        position: relative !important;
        z-index: 10001 !important;
        min-height: 48px !important;
        min-width: 150px !important;
        border: none !important;
        outline: none !important;
        box-shadow: none !important;
        background-clip: padding-box !important;
    }

    /* Additional responsive design for logout modal */
    @media (max-width: 640px) {
        #logoutModal .modal-content {
            width: 95% !important;
            padding: 36px 32px !important;
            margin: 20px !important;
        }

        #logoutModal .modal-buttons {
            flex-direction: column !important;
            gap: 16px !important;
        }

        #logoutModal .modal-btn {
            width: 100% !important;
            min-width: auto !important;
        }

        #logoutModal .modal-icon {
            width: 76px !important;
            height: 76px !important;
            font-size: 2rem !important;
        }

        #logoutModal .modal-title {
            font-size: 1.6rem !important;
        }
    }

    /* Enhanced glassmorphism and loading animations */
    #logoutModal.show .modal-content {
        animation: slideInLogout 0.6s cubic-bezier(0.34, 1.56, 0.64, 1) !important;
    }

    #logoutModal.show .modal-icon {
        animation: iconPulse 3s ease-in-out infinite, iconEntranceScale 0.8s cubic-bezier(0.34, 1.56, 0.64, 1) !important;
    }

    @keyframes iconEntranceScale {
        0% {
            transform: scale(0) rotate(-180deg);
            opacity: 0;
        }

        70% {
            transform: scale(1.1) rotate(10deg);
            opacity: 1;
        }

        100% {
            transform: scale(1) rotate(0deg);
            opacity: 1;
        }
    }

    #logoutModal.show .modal-title {
        animation: textSlideUp 0.8s cubic-bezier(0.25, 0.8, 0.25, 1) 0.2s both !important;
    }

    #logoutModal.show .modal-subtitle {
        animation: textSlideUp 0.8s cubic-bezier(0.25, 0.8, 0.25, 1) 0.3s both !important;
    }

    #logoutModal.show .modal-text {
        animation: textSlideUp 0.8s cubic-bezier(0.25, 0.8, 0.25, 1) 0.4s both !important;
    }

    #logoutModal.show .modal-btn {
        animation: buttonSlideUp 0.8s cubic-bezier(0.25, 0.8, 0.25, 1) both !important;
    }

    #logoutModal.show .modal-btn:nth-child(1) {
        animation-delay: 0.5s !important;
    }

    #logoutModal.show .modal-btn:nth-child(2) {
        animation-delay: 0.6s !important;
    }

    @keyframes textSlideUp {
        0% {
            opacity: 0;
            transform: translateY(20px);
        }

        100% {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes buttonSlideUp {
        0% {
            opacity: 0;
            transform: translateY(30px) scale(0.9);
        }

        100% {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }
    </style>
</head>

<body>
    <?php include __DIR__ . '/includes/sidebar_common.php'; ?>
    <div class="topbar">
        <h1 class="dashboard-title">Engineering Admin Dashboard</h1>
        <a href="logout.php" class="logout-btn" onclick="return confirmLogout(event)">
            <i class="fas fa-sign-out-alt"></i>
            Logout
        </a>
    </div>
    <div class="main-content">
        <div class="card">
            <div class="card-header">
                <div class="header-content">
                    <i class="fas fa-download header-icon"></i>
                    <div>
                        <h1 class="header-title">Export Board Passers Data</h1>
                        <p class="header-subtitle">Download board passer records in various formats</p>
                    </div>
                </div>
            </div>

            <div class="card-content">
                <?php if (isset($error_message)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?= $error_message ?>
                </div>
                <?php endif; ?>

                <div class="export-options">
                    <div class="export-card">
                        <div class="export-icon">
                            <i class="fas fa-file-csv"></i>
                        </div>
                        <h3 class="export-title">CSV Export</h3>
                        <p class="export-description">
                            Download data in CSV format with properly formatted dates (compatible with Excel and Google
                            Sheets)
                        </p>
                        <a href="?export=csv" class="export-btn export-csv">
                            <i class="fas fa-download"></i>
                            Download CSV
                        </a>
                    </div>

                    <div class="export-card">
                        <div class="export-icon">
                            <i class="fas fa-file-excel"></i>
                        </div>
                        <h3 class="export-title">Excel Export</h3>
                        <p class="export-description">
                            Download data in Excel format with advanced formatting and calculations
                        </p>
                        <button class="export-btn export-excel" onclick="showComingSoon('Excel')">
                            <i class="fas fa-download"></i>
                            Download Excel
                        </button>
                    </div>

                    <div class="export-card">
                        <div class="export-icon">
                            <i class="fas fa-file-pdf"></i>
                        </div>
                        <h3 class="export-title">PDF Export</h3>
                        <p class="export-description">
                            Generate a formatted PDF report with statistics and data summary
                        </p>
                        <button class="export-btn export-pdf" onclick="showComingSoon('PDF')">
                            <i class="fas fa-download"></i>
                            Download PDF
                        </button>
                    </div>
                </div>

                <div class="export-info">
                    <div class="info-card">
                        <h4><i class="fas fa-info-circle"></i> Export Information</h4>
                        <ul>
                            <li><strong>CSV Format:</strong> Properly formatted dates (YYYY-MM-DD) to prevent hashtag
                                display issues</li>
                            <li><strong>Data Included:</strong> Name, Course, Graduation Year, Board Exam Date, Result,
                                Exam Type</li>
                            <li><strong>Filtering:</strong> Only College of Engineering records</li>
                            <li><strong>File Naming:</strong> Includes timestamp for easy organization</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

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

    <script src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    <script>
    // Logout confirmation functionality
    function confirmLogout(event) {
        event.preventDefault();
        console.log('confirmLogout called');
        const modal = document.getElementById('logoutModal');
        console.log('Modal found:', modal);
        if (modal) {
            console.log('Modal current display:', window.getComputedStyle(modal).display);
            modal.style.display = 'flex';
            modal.style.zIndex = '9999';
            modal.style.position = 'fixed';
            modal.style.top = '0';
            modal.style.left = '0';
            modal.style.width = '100vw';
            modal.style.height = '100vh';

            // Add show class for our beautiful animations
            modal.classList.add('show');
            console.log('Added show class to modal with beautiful animations');

            // Check button visibility
            const yesBtn = document.getElementById('logoutConfirmYes');
            const noBtn = document.getElementById('logoutConfirmNo');
            const modalButtons = modal.querySelector('.modal-buttons');

            console.log('Yes button found:', yesBtn);
            console.log('No button found:', noBtn);
            console.log('Modal buttons container found:', modalButtons);

            // The buttons will be styled by our beautiful CSS, no need for forced styling
            if (yesBtn) {
                yesBtn.style.display = 'flex';
                yesBtn.style.visibility = 'visible';
                yesBtn.style.opacity = '1';
                yesBtn.removeAttribute('hidden');

                // Add interactive logout functionality
                yesBtn.onclick = function(e) {
                    e.preventDefault();
                    handleInteractiveLogout(this);
                };

                console.log('Yes button made visible for beautiful theme with interactive logout');
            }

            if (noBtn) {
                noBtn.style.display = 'flex';
                noBtn.style.visibility = 'visible';
                noBtn.style.opacity = '1';
                noBtn.removeAttribute('hidden');
                console.log('No button made visible for beautiful theme');
            }

            if (modalButtons) {
                modalButtons.style.display = 'flex';
                console.log('Modal buttons container set to flex for beautiful layout');
            }

            console.log('Beautiful logout modal displayed with premium blue theme');
            console.log('Modal after display change:', window.getComputedStyle(modal).display);
        } else {
            console.error('Logout modal not found!');
        }
        return false;
    }

    // Interactive logout function with enhanced animations
    function handleInteractiveLogout(button) {
        console.log('🚀 Interactive logout initiated!');

        // Prevent double clicks
        if (button.classList.contains('loading')) {
            return;
        }

        // Add loading state
        button.classList.add('loading');

        // Disable cancel button during logout
        const cancelBtn = document.getElementById('logoutConfirmNo');
        if (cancelBtn) {
            cancelBtn.style.opacity = '0.5';
            cancelBtn.style.pointerEvents = 'none';
        }

        // Show beautiful loading animation for 2 seconds
        setTimeout(() => {
            // Remove loading state and add success state
            button.classList.remove('loading');
            button.classList.add('success');

            // Show success message
            showLogoutSuccessMessage();

            // Wait for success animation, then redirect
            setTimeout(() => {
                console.log('✅ Logout successful! Redirecting to login page...');
                window.location.href = 'index.php';
            }, 1500);

        }, 2000);
    }

    // Beautiful logout success message
    function showLogoutSuccessMessage() {
        const messageDiv = document.createElement('div');
        messageDiv.innerHTML = `
        <div style="
          position: fixed;
          top: 50%;
          left: 50%;
          transform: translate(-50%, -50%);
          background: linear-gradient(135deg, #10b981 0%, #059669 100%);
          color: white;
          padding: 20px 32px;
          border-radius: 16px;
          box-shadow: 0 16px 40px rgba(16, 185, 129, 0.4);
          z-index: 10002;
          font-family: 'Inter', sans-serif;
          font-weight: 700;
          text-align: center;
          min-width: 300px;
          backdrop-filter: blur(10px);
          border: 2px solid rgba(255, 255, 255, 0.2);
          animation: successSlideIn 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
        ">
          <div style="
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            font-size: 1.1rem;
          ">
            <i class="fas fa-check-circle" style="
              font-size: 1.3rem;
              animation: successCheckBounce 0.8s cubic-bezier(0.68, -0.55, 0.265, 1.55) 0.3s both;
            "></i>
            Logout Successful!
          </div>
          <div style="
            font-size: 0.9rem;
            font-weight: 500;
            margin-top: 8px;
            opacity: 0.9;
          ">
            Redirecting to login page...
          </div>
        </div>
        <style>
          @keyframes successSlideIn {
            0% { 
              opacity: 0;
              transform: translate(-50%, -50%) scale(0.8) translateY(20px);
            }
            100% { 
              opacity: 1;
              transform: translate(-50%, -50%) scale(1) translateY(0);
            }
          }
          @keyframes successCheckBounce {
            0% { 
              transform: scale(0) rotate(-180deg);
            }
            70% { 
              transform: scale(1.2) rotate(10deg);
            }
            100% { 
              transform: scale(1) rotate(0deg);
            }
          }
        </style>
      `;
        document.body.appendChild(messageDiv);

        // Remove the message after animation
        setTimeout(() => {
            document.body.removeChild(messageDiv);
        }, 3000);
    }

    // Handle logout confirmation
    document.getElementById('logoutConfirmNo').onclick = function() {
        document.getElementById('logoutModal').style.display = 'none';
    };

    // Close logout modal when clicking outside
    document.getElementById('logoutModal').onclick = function(e) {
        if (e.target === this) {
            this.style.display = 'none';
        }
    };

    // Coming soon function for export formats
    function showComingSoon(format) {
        alert(
            `${format} export feature is coming soon! For now, please use the CSV export which includes properly formatted dates.`
        );
    }
    </script>
</body>

</html>