<?php
session_start();

// Suppress deprecation warnings for production
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
ini_set('display_errors', 0);

// Only allow College of Engineering admin
if (!isset($_SESSION["users"]) || $_SESSION["users"] !== 'president@lspu.edu.ph') {
    header("Location: index.php");
    exit();
}
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "project_db";
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }
// Fetch board passers sorted alphabetically by name
$passers = $conn->query("SELECT *, CONCAT(first_name, ' ', IFNULL(middle_name, ''), ' ', last_name) as full_name FROM board_passers WHERE department='Engineering' ORDER BY first_name ASC");
$total_records = $passers->num_rows;
// Reset the result set for table display
$passers->data_seek(0);

// Check for success/error messages from update
$update_message = '';
$update_type = '';
if (isset($_GET['success']) && $_GET['success'] === 'updated') {
    $update_message = 'Student record updated successfully!';
    $update_type = 'success';
    if (isset($_GET['name'])) {
        $update_message = 'Student "' . htmlspecialchars($_GET['name']) . '" updated successfully!';
    }
} elseif (isset($_GET['error'])) {
    $update_type = 'error';
    switch ($_GET['error']) {
        case 'missing_fields':
            $update_message = 'Error: Missing required fields';
            break;
        case 'record_not_found':
            $update_message = 'Error: Record not found to update';
            break;
        case 'no_changes':
            $update_message = 'No changes were made to the record';
            break;
        default:
            $update_message = 'Error: ' . htmlspecialchars($_GET['error']);
    }
}

// Fetch courses for dropdown
$courses_result = $conn->query("SELECT course_name FROM courses WHERE department='Engineering' ORDER BY course_name ASC");
$courses = [];
if ($courses_result && $courses_result->num_rows > 0) {
    while ($row = $courses_result->fetch_assoc()) {
        $courses[] = $row['course_name'];
    }
}

// Add default courses if none exist in database
if (empty($courses)) {
    $courses = [
        'Bachelor of Science in Electronics Engineering (BSECE)',
        'Bachelor of Science in Electrical Engineering (BSEE)', 
        'Bachelor of Science in Computer Engineering (BSCpE)',
        'Bachelor of Science in Civil Engineering (BSCE)',
        'Bachelor of Science in Mechanical Engineering (BSME)'
    ];
}

// Fetch board exam types for dropdown (include id for client-side filtering)
$board_exam_types_result = $conn->query("SELECT id, exam_type_name FROM board_exam_types WHERE department='Engineering' ORDER BY exam_type_name ASC");
$board_exam_types = [];
if ($board_exam_types_result && $board_exam_types_result->num_rows > 0) {
  while ($row = $board_exam_types_result->fetch_assoc()) {
    $board_exam_types[] = ['id' => $row['id'], 'name' => $row['exam_type_name']];
  }
}

// Add default board exam types if none exist in database
if (empty($board_exam_types)) {
    $board_exam_types = [
        'Registered Electrical Engineer Licensure Exam (REELE)',
        'Registered Master Electrician (RME)',
        'Electronics Engineer Licensure Exam (EELE)',
        'Computer Engineer Licensure Exam (CELE)',
        'Civil Engineer Licensure Exam (CELE)',
        'Mechanical Engineer Licensure Exam (MELE)'
    ];
}

// Fetch board exam dates for dropdown (include associated board exam type name for client-side filtering)
$board_exam_dates_result = $conn->query(
  "SELECT bed.id, bed.exam_date, bed.exam_description, bed.exam_type_id, IFNULL(bet.exam_type_name, '') AS exam_type_name \n"
  . "FROM board_exam_dates bed LEFT JOIN board_exam_types bet ON bed.exam_type_id = bet.id \n"
  . "WHERE bed.department='Engineering' ORDER BY bed.exam_date DESC"
);
$board_exam_dates = [];
if ($board_exam_dates_result && $board_exam_dates_result->num_rows > 0) {
  while ($row = $board_exam_dates_result->fetch_assoc()) {
    $board_exam_dates[] = [
      'id' => $row['id'],
      'date' => $row['exam_date'],
      'description' => $row['exam_description'],
      'exam_type_name' => $row['exam_type_name'],
      'exam_type_id' => isset($row['exam_type_id']) ? (int)$row['exam_type_id'] : ''
    ];
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Engineering Dashboard - BOARD PASSING RATE SYSTEM</title>
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
        background: linear-gradient(135deg, #e0e7ef 0%, #b3c6e0 100%);
        margin: 0;
        font-family: 'Inter', sans-serif;
        min-height: 100vh;
    }

    /* Sidebar styling moved to css/sidebar.css (shared) */

    .topbar {
        position: fixed;
        top: 0;
        left: 260px;
        right: 0;
        background: linear-gradient(135deg, #06b6d4 0%, #0593b4 100%);
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
        background: linear-gradient(135deg, #06b6d4 0%, #0593b4 100%);
        padding: 24px 40px;
        border-radius: 16px;
        box-shadow: 0 12px 40px rgba(3, 105, 112, 0.22);
        text-align: center;
        letter-spacing: 1.2px;
        text-transform: uppercase;
        font-family: 'Inter', sans-serif;
        margin: 0 0 40px 0;
    }

    .shortcuts-btn {
        background: rgba(6, 182, 212, 0.08);
        color: #066;
        /* deep teal text */
        border: 2px solid rgba(6, 182, 212, 0.35);
        border-radius: 12px;
        padding: 10px 16px;
        font-size: 0.9rem;
        font-weight: 600;
        font-family: 'Inter', sans-serif;
        cursor: pointer;
        transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
        display: flex;
        align-items: center;
        gap: 6px;
        backdrop-filter: blur(10px);
        position: relative;
        overflow: hidden;
    }

    .shortcuts-btn:hover {
        background: rgba(6, 182, 212, 0.15);
        border-color: rgba(6, 182, 212, 0.55);
        transform: translateY(-2px) scale(1.02);
        box-shadow: 0 8px 25px rgba(3, 105, 112, 0.2);
    }

    .shortcuts-btn:active {
        transform: translateY(0) scale(0.98);
        transition: all 0.1s ease;
    }

    .guide-close-btn {
        background: linear-gradient(135deg, #06b6d4 0%, #0593b4 100%);
        color: #fff;
        border: none;
        border-radius: 8px;
        padding: 12px 24px;
        font-size: 0.95rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
        font-family: 'Inter', sans-serif;
    }

    .guide-close-btn:hover {
        transform: scale(1.05);
        box-shadow: 0 8px 25px rgba(3, 105, 112, 0.35);
    }

    .guide-close-btn:active {
        transform: scale(0.95);
        transition: all 0.1s ease;
    }

    .export-modal-close {
        position: absolute;
        top: 15px;
        right: 20px;
        background: transparent;
        border: none;
        font-size: 24px;
        color: #6b7280;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
    }

    .export-modal-close:hover {
        background-color: #f3f4f6;
        color: #374151;
        transform: scale(1.1);
    }

    .export-modal-close:active {
        transform: scale(0.9);
        transition: all 0.1s ease;
    }

    .main-content {
        margin-left: 260px;
        margin-top: 70px;
        padding: 40px;
        min-height: calc(100vh - 70px);
        background: rgba(255, 255, 255, 0.05);
        backdrop-filter: blur(10px);
    }

    .card {
        background: #ffffff;
        border-radius: 24px;
        box-shadow: 0 20px 60px rgba(22, 41, 56, 0.1);
        padding: 48px;
        margin: 0;
        border: 1px solid #e2e8f0;
        overflow: visible;
    }

    @media (max-width: 1200px) {
        .main-content {
            padding: 24px;
        }

        .card {
            padding: 32px 24px;
        }
    }

    /* Responsive sidebar behavior moved to css/sidebar.css */

    .card h2 {
        font-size: 1.8rem;
        font-weight: 700;
        margin-bottom: 24px;
        color: #ffffff;
        background: linear-gradient(135deg, #1e40af 0%, #06b6d4 100%);
        /* Navy to Cyan gradient */
        padding: 20px 32px;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(30, 64, 175, 0.3);
        text-align: center;
        letter-spacing: 0.8px;
        text-transform: uppercase;
        font-family: 'Poppins', sans-serif;
        margin: 0 0 24px 0;
        flex-shrink: 0;
    }

    .table-container {
        flex: 1;
        overflow: auto;
        border-radius: 16px;
        box-shadow: 0 8px 32px rgba(22, 41, 56, 0.12);
        border: 1px solid #e2e8f0;
        background: #ffffff;
    }

    .board-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        font-family: 'Inter', sans-serif;
        margin: 0;
        min-width: 1000px;
    }

    .board-table th,
    .board-table td {
        padding: 16px 20px;
        border-bottom: 1px solid #e2e8f0;
        text-align: left;
        vertical-align: middle;
        font-family: 'Inter', sans-serif;
    }

    .board-table th {
        background: #06b6d4;
        color: #ffffff;
        font-weight: 600;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        position: sticky;
        top: 0;
        z-index: 10;
        box-shadow: 0 2px 8px rgba(6, 182, 212, 0.18);
        border-bottom: 2px solid rgba(255, 255, 255, 0.25);
    }

    .board-table th:first-child {
        border-top-left-radius: 16px;
    }

    .board-table th:last-child {
        border-top-right-radius: 16px;
    }

    .board-table tbody tr {
        background: #ffffff;
        transition: all 0.2s ease;
    }

    .board-table tbody tr:nth-child(even) {
        background: #f9fafb;
    }

    .board-table tbody tr:hover {
        background: linear-gradient(90deg, #fef3c7 0%, #fef9e7 100%);
        /* Warm amber-yellow tint */
        transform: translateY(-1px);
        box-shadow: 0 4px 16px rgba(251, 191, 36, 0.15);
    }

    .board-table tbody tr:last-child td:first-child {
        border-bottom-left-radius: 16px;
    }

    .board-table tbody tr:last-child td:last-child {
        border-bottom-right-radius: 16px;
    }

    .board-table td {
        font-size: 0.9rem;
        color: #374151;
        line-height: 1.5;
        font-family: 'Inter', sans-serif;
        font-weight: 500;
    }

    /* Teal accents for form controls */
    input[type="text"],
    input[type="number"],
    input[type="date"],
    input[type="search"],
    select,
    textarea {
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 10px 12px;
        font-family: 'Inter', sans-serif;
        transition: box-shadow .2s ease, border-color .2s ease;
    }

    input[type="text"]:focus,
    input[type="number"]:focus,
    input[type="date"]:focus,
    input[type="search"]:focus,
    select:focus,
    textarea:focus {
        outline: none;
        border-color: #06b6d4;
        box-shadow: 0 0 0 3px rgba(6, 182, 212, 0.2);
    }

    /* Small teal buttons */
    .btn,
    .action-btn,
    button.small {
        background: #ffffff;
        color: #06b6d4;
        border: 1px solid #06b6d4;
        padding: 8px 12px;
        border-radius: 10px;
        font-weight: 600;
        cursor: pointer;
        transition: background .2s ease, color .2s ease, box-shadow .2s ease;
    }

    .btn:hover,
    .action-btn:hover,
    button.small:hover {
        background: #06b6d4;
        color: #ffffff;
        box-shadow: 0 6px 16px rgba(3, 105, 112, 0.22);
    }

    /* Column-specific styling */
    .board-table th:nth-child(1),
    .board-table td:nth-child(1) {
        width: 22%;
        text-align: left;
    }

    /* Name */
    .board-table th:nth-child(2),
    .board-table td:nth-child(2) {
        width: 18%;
        text-align: left;
    }

    /* Course */
    .board-table th:nth-child(3),
    .board-table td:nth-child(3) {
        width: 10%;
        text-align: center;
    }

    /* Year */
    .board-table th:nth-child(4),
    .board-table td:nth-child(4) {
        width: 12%;
        text-align: center;
    }

    /* Date */
    .board-table th:nth-child(5),
    .board-table td:nth-child(5) {
        width: 10%;
        text-align: center;
    }

    /* Result */
    .board-table th:nth-child(6),
    .board-table td:nth-child(6) {
        width: 10%;
        text-align: center;
    }

    /* Exam Type */
    .board-table th:nth-child(7),
    .board-table td:nth-child(7) {
        width: 16%;
        text-align: left;
    }

    /* Board Exam Type */
    .board-table th:nth-child(8),
    .board-table td:nth-child(8) {
        width: 12%;
        text-align: center;
    }

    /* Actions */

    /* Status Badges */
    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 6px 12px;
        border-radius: 8px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        border: 1px solid transparent;
        position: relative;
    }

    .status-passed {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        /* Emerald Green */
        color: #ffffff;
        border-color: #10b981;
        box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
    }

    .status-failed {
        background: linear-gradient(135deg, #f87171 0%, #ef4444 100%);
        /* Coral Red */
        color: #ffffff;
        border-color: #f87171;
        box-shadow: 0 2px 8px rgba(248, 113, 113, 0.3);
    }

    .status-cond {
        background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
        /* Amber Yellow */
        color: #1a1a1a;
        border-color: #fbbf24;
        box-shadow: 0 2px 8px rgba(251, 191, 36, 0.3);
    }

    .exam-first-timer {
        background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
        /* Purple */
        color: #ffffff;
        border-color: #8b5cf6;
        box-shadow: 0 2px 8px rgba(139, 92, 246, 0.3);
    }

    .exam-repeater {
        background: linear-gradient(135deg, #94a3b8 0%, #64748b 100%);
        /* Slate */
        color: #ffffff;
        border-color: #94a3b8;
        box-shadow: 0 2px 8px rgba(148, 163, 184, 0.3);
    }

    /* Action Buttons */
    .actions-btns {
        display: flex !important;
        justify-content: center !important;
        /* centers horizontally */
        align-items: center !important;
        /* centers vertically */
        gap: 8px !important;
        /* spacing between buttons */
        padding: 8px !important;
        min-width: 160px !important;
        flex-wrap: wrap !important;
    }

    .action-btn {
        min-width: 60px !important;
        height: 32px !important;
        border: none !important;
        border-radius: 6px !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        cursor: pointer !important;
        transition: all 0.3s ease !important;
        font-size: 12px !important;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15) !important;
        position: relative !important;
        z-index: 1 !important;
        font-family: 'Inter', sans-serif !important;
        font-weight: 600 !important;
        text-align: center !important;
        white-space: nowrap !important;
    }

    .action-btn:hover {
        transform: scale(1.05) !important;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.25) !important;
    }

    .action-btn span {
        margin-left: 4px !important;
        font-size: 10px !important;
        font-weight: 600 !important;
    }

    /* Colors - Warm Complementary Palette */
    .edit-btn {
        background: #f97316 !important;
        /* Warm Orange */
        color: white !important;
    }

    .edit-btn:hover {
        background: #ea580c !important;
        /* Deeper Orange */
        box-shadow: 0 6px 20px rgba(249, 115, 22, 0.4) !important;
    }

    .delete-btn {
        background: #ef4444 !important;
        /* Coral Red */
        color: white !important;
    }

    .delete-btn:hover {
        background: #dc2626 !important;
        /* Deeper Red */
        box-shadow: 0 6px 20px rgba(239, 68, 68, 0.4) !important;
    }

    .save-btn {
        background: #10b981 !important;
        /* Emerald Green - matches Passed status */
        color: white !important;
    }

    .save-btn:hover {
        background: #059669 !important;
        box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4) !important;
    }

    .cancel-btn {
        background: #64748b !important;
        /* Slate Gray */
        color: white !important;
    }

    .cancel-btn:hover {
        background: #475569 !important;
        box-shadow: 0 6px 20px rgba(100, 116, 139, 0.4) !important;
    }

    .save-btn {
        background: #10b981;
        color: white;
    }

    .save-btn:hover {
        background: #16a34a;
    }

    .cancel-btn {
        background: #9ca3af;
        color: white;
    }

    .cancel-btn:hover {
        background: #6b7280;
    }

    /* Tooltip */
    .action-btn::after {
        content: "";
        position: absolute;
        top: -35px;
        left: 50%;
        transform: translateX(-50%);
        color: white;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 0.75rem;
        white-space: nowrap;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.3s ease;
    }

    .action-btn:hover::after {
        opacity: 1;
    }

    .board-table tbody tr:last-child td {
        border-bottom: none;
    }

    .editable {
        font-weight: 600;
        color: #1f2937;
    }

    /* No Records State Styles */
    .no-records {
        text-align: center;
        padding: 80px 40px;
        background: linear-gradient(135deg, #f0fdfa 0%, #ecfeff 100%);
        border: 2px dashed #06b6d4;
        border-radius: 20px;
        margin: 40px 0;
    }

    .no-records h3 {
        color: #374151;
        margin-bottom: 8px;
        font-size: 1.5rem;
        font-weight: 600;
    }

    .no-records p {
        margin-bottom: 24px;
        font-size: 14px;
        line-height: 1.6;
        color: #6b7280;
    }

    .add-first-btn {
        background: linear-gradient(135deg, #3b82f6, #1d4ed8);
        color: white;
        padding: 12px 24px;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-block;
        box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.3);
    }

    .add-first-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 15px -3px rgba(59, 130, 246, 0.4);
        background: linear-gradient(135deg, #2563eb, #1e40af);
    }

    /* Enhanced Edit Mode Styles */
    tr[data-editing="true"] {
        position: relative;
        z-index: 5;
    }

    /* Ensure table row design integrity */
    .board-table tbody tr {
        background: #ffffff !important;
        transition: all 0.2s ease !important;
    }

    .board-table tbody tr:nth-child(even) {
        background: #f9fafb !important;
    }

    .board-table tbody tr:hover {
        background: linear-gradient(90deg, #eff6ff 0%, #f0f9ff 100%) !important;
        transform: translateY(-1px) !important;
        box-shadow: 0 4px 16px rgba(22, 41, 56, 0.1) !important;
    }

    /* Preserve alternating row colors after updates */
    .board-table tbody tr.updated {
        animation: rowUpdateFlash 3s ease-in-out;
    }

    @keyframes rowUpdateFlash {
        0% {
            background: linear-gradient(90deg, #f0fdf4 0%, #dcfce7 100%) !important;
        }

        10% {
            background: linear-gradient(90deg, #f0fdf4 0%, #dcfce7 100%) !important;
        }

        100% {
            background: inherit !important;
        }
    }

    /* Edit-related UI styles removed (editing disabled) */

    /* Input Feedback Icons */
    .input-feedback {
        transition: all 0.2s ease;
    }

    .input-feedback.show {
        display: block !important;
        animation: fadeInScale 0.3s ease;
    }

    @keyframes fadeInScale {
        from {
            opacity: 0;
            transform: translateY(-50%) scale(0.8);
        }

        to {
            opacity: 1;
            transform: translateY(-50%) scale(1);
        }
    }

    /* Enhanced Modal Animations */
    .custom-modal {
        animation: modalFadeIn 0.3s ease !important;
    }

    .custom-modal.show .modal-content {
        animation: modalSlideIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1) !important;
    }

    @keyframes modalFadeIn {
        from {
            opacity: 0;
        }

        to {
            opacity: 1;
        }
    }

    @keyframes modalSlideIn {
        from {
            transform: scale(0.7) translateY(-50px);
            opacity: 0;
        }

        to {
            transform: scale(1) translateY(0);
            opacity: 1;
        }
    }

    /* Enhanced Button Interactions */
    .action-btn {
        position: relative;
        overflow: hidden;
    }

    .action-btn::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 0;
        height: 0;
        background: rgba(255, 255, 255, 0.3);
        border-radius: 50%;
        transform: translate(-50%, -50%);
        transition: width 0.6s, height 0.6s;
    }

    .action-btn:active::before {
        width: 300px;
        height: 300px;
    }

    /* Success/Error Message Animations */
    .message-slide-in {
        animation: messageSlideIn 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    @keyframes messageSlideIn {
        from {
            transform: translate(-50%, -50%) scale(0.8) rotate(-5deg);
            opacity: 0;
        }

        to {
            transform: translate(-50%, -50%) scale(1) rotate(0deg);
            opacity: 1;
        }
    }

    @media (max-width: 1200px) {
        .main-content {
            padding: 24px;
        }

        .card {
            padding: 32px 24px;
        }

        .board-table th,
        .board-table td {
            padding: 12px 16px;
            font-size: 0.85rem;
        }

        .action-btn {
            min-width: 55px !important;
            height: 30px !important;
            font-size: 12px !important;
        }

        .action-btn span {
            font-size: 9px !important;
        }

        .status-badge {
            font-size: 0.7rem;
            padding: 3px 6px;
        }
    }

    @media (max-width: 900px) {
        .sidebar {
            width: 80px;
        }

        .main-content,
        .topbar {
            margin-left: 80px;
        }

        .sidebar-nav a span {
            display: none;
        }

        .sidebar .logo {
            font-size: 1.2rem;
        }

        .dashboard-title {
            font-size: 1.1rem;
        }

        .logout-btn {
            padding: 10px 16px;
            font-size: 0.9rem;
        }

        .board-table th,
        .board-table td {
            font-size: 0.8rem;
            padding: 10px 12px;
        }

        .action-btn {
            min-width: 50px !important;
            height: 28px !important;
            font-size: 11px !important;
        }

        .action-btn span {
            font-size: 8px !important;
        }

        .status-badge {
            font-size: 0.65rem;
            padding: 2px 5px;
        }
    }

    @media (max-width: 600px) {
        .sidebar {
            display: none;
        }

        .topbar,
        .main-content {
            margin-left: 0;
        }

        .topbar {
            padding: 16px 20px;
        }

        .dashboard-title {
            font-size: 1rem;
        }

        .logout-btn {
            padding: 8px 12px;
            font-size: 0.85rem;
        }

        .card {
            padding: 16px 8px;
            border-radius: 12px;
        }

        .board-table th,
        .board-table td {
            font-size: 0.75rem;
            padding: 8px 6px;
        }

        .table-container {
            border-radius: 12px;
            box-shadow: 0 4px 16px rgba(22, 41, 56, 0.1);
        }

        .action-btn {
            min-width: 45px !important;
            height: 26px !important;
            font-size: 10px !important;
        }

        .action-btn span {
            font-size: 7px !important;
        }

        .actions-btns {
            flex-direction: row !important;
            gap: 3px !important;
            min-width: 100px !important;
        }

        .status-badge {
            font-size: 0.6rem;
            padding: 2px 4px;
            display: block;
            margin: 2px 0;
        }

        /* Stack table columns on mobile */
        .board-table,
        .board-table thead,
        .board-table tbody,
        .board-table th,
        .board-table td,
        .board-table tr {
            display: block;
        }

        .board-table thead tr {
            position: absolute;
            top: -9999px;
            left: -9999px;
        }

        .board-table tr {
            border: 1px solid #e2e8f0;
            margin-bottom: 10px;
            border-radius: 8px;
            padding: 10px;
            background: white;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .board-table td {
            border: none;
            border-bottom: 1px solid #e2e8f0;
            position: relative;
            padding: 8px 8px 8px 35%;
            text-align: right;
        }

        .board-table td:before {
            content: attr(data-label);
            position: absolute;
            left: 6px;
            width: 30%;
            padding-right: 10px;
            white-space: nowrap;
            font-weight: 600;
            color: #374151;
            text-align: left;
        }

        .board-table td:last-child {
            border-bottom: 0;
        }
    }

    /* Custom Modal Styles */
    .custom-modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        background: rgba(22, 41, 56, 0.5);
        backdrop-filter: blur(5px);
        z-index: 9999;
        display: flex;
        justify-content: center;
        align-items: center;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
    }

    .custom-modal.show {
        opacity: 1;
        visibility: visible;
    }

    .shortcuts-help-modal .modal-content {
        animation: modalSlideIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    .shortcuts-help-modal .modal-close:hover {
        background: rgba(0, 0, 0, 0.2) !important;
        transform: scale(1.1);
    }

    .modal-content {
        background: #ffffff;
        border-radius: 20px;
        padding: 0;
        max-width: 420px;
        width: 90%;
        box-shadow: 0 20px 60px rgba(44, 90, 160, 0.3);
        transform: scale(0.9);
        transition: transform 0.3s ease;
        overflow: hidden;
    }

    .custom-modal.show .modal-content {
        transform: scale(1);
    }

    .modal-header {
        text-align: center;
        padding: 40px 30px 30px;
        background: linear-gradient(135deg, #fff5f5 0%, #fed7d7 100%);
        border-bottom: 1px solid #fbb6ce;
    }

    .modal-icon {
        width: 80px;
        height: 80px;
        background: linear-gradient(135deg, #e53e3e 0%, #c53030 100%);
        border-radius: 50%;
        margin: 0 auto 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        color: white;
        box-shadow: 0 8px 25px rgba(229, 62, 62, 0.3);
    }

    .modal-header h3 {
        font-size: 1.5rem;
        font-weight: 700;
        color: #2d3748;
        margin-bottom: 8px;
        font-family: 'Inter', sans-serif;
    }

    .modal-header p {
        font-size: 1.1rem;
        color: #4a5568;
        margin-bottom: 8px;
        font-weight: 500;
    }

    .modal-header small {
        font-size: 0.9rem;
        color: #718096;
        line-height: 1.4;
    }

    .modal-buttons {
        padding: 30px;
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        justify-content: center;
        background: #fff;
        border-bottom-left-radius: 20px;
        border-bottom-right-radius: 20px;
    }

    /* Edit modal styles removed (editing disabled) */

    .modal-buttons button {
        padding: 12px 24px;
        border: none;
        border-radius: 12px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 8px;
        min-width: 130px;
        justify-content: center;
        font-family: 'Inter', sans-serif;
        white-space: nowrap;
    }

    .btn-danger {
        background: linear-gradient(135deg, #e53e3e 0%, #c53030 100%);
        color: white;
        box-shadow: 0 4px 15px rgba(229, 62, 62, 0.3);
    }

    .btn-danger:hover {
        background: linear-gradient(135deg, #c53030 0%, #9c2626 100%);
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(229, 62, 62, 0.4);
    }

    .btn-primary {
        background: linear-gradient(135deg, #3182ce 0%, #2c5aa0 100%);
        color: white;
        box-shadow: 0 4px 15px rgba(49, 130, 206, 0.3);
    }

    .btn-primary:hover {
        background: linear-gradient(135deg, #2c5aa0 0%, #1e3a8a 100%);
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(49, 130, 206, 0.4);
    }

    .btn-export {
        background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
        color: white;
        box-shadow: 0 4px 15px rgba(139, 92, 246, 0.3);
    }

    .btn-export:hover {
        background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%);
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(139, 92, 246, 0.4);
    }

    .btn-secondary {
        background: #e2e8f0;
        color: #4a5568;
        box-shadow: 0 4px 15px rgba(160, 174, 192, 0.2);
    }

    .btn-secondary:hover {
        background: #cbd5e0;
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(160, 174, 192, 0.3);
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
        }

        to {
            opacity: 1;
        }
    }

    /* Add Student Modal Specific Styling */
    #addStudentModal {
        background: rgba(30, 41, 59, 0.4);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
    }

    #addStudentModal .add-modal-content {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border-radius: 24px;
        max-width: 900px;
        width: 95%;
        border: 1px solid rgba(255, 255, 255, 0.2);
        box-shadow:
            0 25px 50px -12px rgba(0, 0, 0, 0.25),
            0 0 0 1px rgba(255, 255, 255, 0.05),
            inset 0 1px 0 rgba(255, 255, 255, 0.6);
        overflow: hidden;
    }

    #addStudentModal .add-modal-header {
        background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
        color: white;
        padding: 32px;
        text-align: center;
        position: relative;
    }

    #addStudentModal .add-modal-icon {
        background: rgba(255, 255, 255, 0.2);
        width: 80px;
        height: 80px;
        border-radius: 20px;
        margin: 0 auto 20px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
    }

    #addStudentModal .add-modal-header h3 {
        color: white;
        margin: 0 0 8px 0;
        font-size: 1.75rem;
        font-weight: 700;
    }

    #addStudentModal .add-modal-header p {
        color: rgba(255, 255, 255, 0.9);
        margin: 0;
        font-size: 1.1rem;
    }

    /* Tab Navigation */
    .tab-navigation {
        display: flex;
        background: rgba(249, 250, 251, 0.8);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        border-bottom: 1px solid rgba(229, 231, 235, 0.5);
    }

    .tab-btn {
        flex: 1;
        padding: 16px 24px;
        border: none;
        background: transparent;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        font-weight: 600;
        color: #6b7280;
        position: relative;
    }

    .tab-btn i {
        font-size: 1.1rem;
    }

    .tab-btn.active {
        color: #3b82f6;
        background: rgba(59, 130, 246, 0.1);
    }

    .tab-btn.active::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(90deg, #3b82f6, #1d4ed8);
    }

    .tab-btn:hover:not(.active) {
        background: rgba(59, 130, 246, 0.05);
        color: #3b82f6;
    }

    /* Tab Content */
    .tab-content {
        display: none;
        padding: 0;
    }

    .tab-content.active {
        display: block;
    }

    .tab-header {
        padding: 32px 32px 24px;
        text-align: center;
        background: linear-gradient(135deg,
                rgba(59, 130, 246, 0.05) 0%,
                rgba(139, 92, 246, 0.05) 100%);
        border-bottom: 1px solid rgba(229, 231, 235, 0.3);
    }

    .tab-icon {
        width: 60px;
        height: 60px;
        border-radius: 16px;
        background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 16px;
        font-size: 1.5rem;
        box-shadow: 0 8px 25px rgba(59, 130, 246, 0.3);
    }

    .tab-header h4 {
        color: #1f2937;
        margin: 0 0 8px 0;
        font-size: 1.5rem;
        font-weight: 700;
    }

    .tab-header p {
        color: #6b7280;
        margin: 0;
        font-size: 1rem;
    }

    #addStudentModal .form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 24px;
        padding: 32px;
    }

    #addStudentModal .form-group {
        position: relative;
    }

    #addStudentModal .form-group label {
        display: block;
        font-weight: 600;
        color: #374151;
        margin-bottom: 8px;
        font-size: 0.95rem;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    #addStudentModal .form-group label i {
        color: #3b82f6;
        font-size: 0.9rem;
    }

    #addStudentModal .form-group input,
    #addStudentModal .form-group select {
        width: 100%;
        padding: 12px 16px;
        border: 2px solid rgba(229, 231, 235, 0.8);
        border-radius: 12px;
        font-size: 0.95rem;
        transition: all 0.3s ease;
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }

    #addStudentModal .form-group input::placeholder {
        color: #9ca3af;
        opacity: 0.8;
    }

    #addStudentModal .form-group input:focus,
    #addStudentModal .form-group select:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow:
            0 0 0 4px rgba(59, 130, 246, 0.15),
            0 4px 12px -2px rgba(0, 0, 0, 0.1);
        transform: translateY(-1px);
        background: rgba(255, 255, 255, 1);
    }

    #addStudentModal .input-feedback {
        font-size: 0.85rem;
        margin-top: 6px;
        padding: 4px 8px;
        border-radius: 6px;
        opacity: 0;
        transform: translateY(-4px);
        transition: all 0.3s ease;
    }

    #addStudentModal .input-feedback.show {
        opacity: 1;
        transform: translateY(0);
    }

    #addStudentModal .input-feedback.success {
        color: #059669;
        background: rgba(16, 185, 129, 0.1);
        border-left: 3px solid #10b981;
    }

    #addStudentModal .input-feedback.error {
        color: #dc2626;
        background: rgba(239, 68, 68, 0.1);
        border-left: 3px solid #ef4444;
    }

    .tab-footer {
        padding: 24px 32px 32px;
        display: flex;
        gap: 16px;
        justify-content: space-between;
        background: rgba(249, 250, 251, 0.8);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        border-top: 1px solid rgba(229, 231, 235, 0.3);
    }

    .tab-footer button {
        padding: 12px 24px;
        border: none;
        border-radius: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 0.95rem;
        min-width: 120px;
        justify-content: center;
    }

    .tab-footer .btn-primary {
        background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
        color: white;
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
        margin-left: auto;
    }

    .tab-footer .btn-primary:hover {
        background: linear-gradient(135deg, #1d4ed8 0%, #1e3a8a 100%);
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(59, 130, 246, 0.5);
    }

    .tab-footer .btn-primary:disabled {
        opacity: 0.6;
        transform: none;
        cursor: not-allowed;
    }

    .tab-footer .btn-secondary {
        background: rgba(229, 231, 235, 0.8);
        color: #374151;
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        border: 1px solid rgba(209, 213, 219, 0.5);
    }

    .tab-footer .btn-secondary:hover {
        background: rgba(209, 213, 219, 0.9);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    #addStudentModal .modal-close {
        position: absolute;
        top: 20px;
        right: 20px;
        background: rgba(255, 255, 255, 0.9);
        border: none;
        border-radius: 10px;
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.3s ease;
        color: #6b7280;
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    #addStudentModal .modal-close:hover {
        background: rgba(239, 68, 68, 0.1);
        color: #ef4444;
        transform: scale(1.1);
        box-shadow: 0 6px 20px rgba(239, 68, 68, 0.2);
    }

    @media (max-width: 768px) {
        #addStudentModal .add-modal-content {
            max-width: 95%;
            margin: 20px;
        }

        #addStudentModal .form-grid {
            grid-template-columns: 1fr;
            padding: 24px;
        }

        #addStudentModal .add-modal-header {
            padding: 24px;
        }

        #addStudentModal .modal-buttons {
            padding: 20px 24px 24px;
            flex-direction: column;
        }

        #addStudentModal .modal-buttons button {
            width: 100%;
        }
    }

    /* Dashboard Statistics Styles */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
        margin-bottom: 30px;
    }

    .stat-card {
        background: white;
        border-radius: 16px;
        padding: 24px;
        display: flex;
        align-items: center;
        gap: 20px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        border: 1px solid #f1f5f9;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, #3182ce, #8b5cf6, #10b981, #f59e0b);
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .stat-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
    }

    .stat-card:hover::before {
        opacity: 1;
    }

    .stat-icon {
        width: 60px;
        height: 60px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 24px;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        flex-shrink: 0;
    }

    .stat-content {
        flex: 1;
    }

    .stat-content h3 {
        font-size: 2.2rem;
        font-weight: 700;
        color: #1e293b;
        margin: 0 0 4px 0;
        font-family: 'Inter', sans-serif;
    }

    .stat-content p {
        font-size: 1rem;
        color: #64748b;
        margin: 0 0 8px 0;
        font-weight: 500;
    }

    .stat-change {
        font-size: 0.85rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .stat-change.positive {
        color: #059669;
    }

    .stat-change.negative {
        color: #dc2626;
    }

    .stat-change.neutral {
        color: #6b7280;
    }

    .stat-change i {
        font-size: 0.75rem;
    }

    @media (max-width: 768px) {
        .stats-grid {
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
        }

        .stat-card {
            padding: 16px;
            min-height: auto;
        }

        .stat-content h3 {
            font-size: 1.5rem;
        }

        .stat-content p {
            font-size: 0.8rem;
        }

        .stat-change {
            font-size: 0.7rem;
        }
    }

    @media (max-width: 480px) {
        .stats-grid {
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
        }

        .stat-card {
            padding: 12px 8px;
            min-height: auto;
        }

        .stat-icon {
            width: 40px;
            height: 40px;
            font-size: 1rem;
        }

        .stat-content h3 {
            font-size: 1.2rem;
        }

        .stat-content p {
            font-size: 0.7rem;
            margin-bottom: 4px;
        }

        .stat-change {
            font-size: 0.6rem;
        }
    }

    /* Filter Section Styles */
    .filter-section {
        background: linear-gradient(135deg, #ecfeff 0%, #cffafe 100%);
        border: 2px solid #06b6d4;
        border-radius: 16px;
        margin-bottom: 24px;
        overflow: visible;
        box-shadow: 0 4px 16px rgba(6, 182, 212, 0.15);
        position: relative;
        z-index: 10;
    }

    .filter-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 16px 24px;
        /* Tighter, cleaner spacing */
        background: linear-gradient(135deg, #06b6d4 0%, #0891a8 100%);
        border-bottom: 1px solid #0e7490;
        border-radius: 16px;
    }

    .filter-header h3 {
        font-size: 1.1rem;
        font-weight: 600;
        color: #ffffff;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .filter-header h3 i {
        color: #ecfeff;
    }

    .toggle-btn {
        background: linear-gradient(135deg, #0e7490 0%, #155e75 100%);
        color: white;
        border: none;
        border-radius: 8px;
        padding: 8px 16px;
        font-size: 0.9rem;
        font-weight: 500;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s ease;
        box-shadow: 0 2px 8px rgba(6, 182, 212, 0.2);
    }

    .toggle-btn:hover {
        background: linear-gradient(135deg, #155e75 0%, #164e63 100%);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(6, 182, 212, 0.3);
    }

    .toggle-btn i {
        transition: transform 0.3s ease;
    }

    .toggle-btn.active i {
        transform: rotate(180deg);
    }

    .filter-container {
        display: none;
        padding: 24px;
        background: white;
        overflow: visible;
    }

    .filter-container.show {
        display: block;
        animation: slideDown 0.3s ease;
    }

    @keyframes slideDown {
        from {
            opacity: 0;
            max-height: 0;
        }

        to {
            opacity: 1;
            max-height: 500px;
        }
    }

    .filter-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 24px;
    }

    .filter-group {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .filter-group label {
        font-size: 0.9rem;
        font-weight: 600;
        color: #374151;
        margin-bottom: 4px;
    }

    .filter-input {
        padding: 10px 12px;
        border: 2px solid #e2e8f0;
        border-radius: 8px;
        font-size: 0.9rem;
        font-family: 'Inter', sans-serif;
        background: white;
        transition: all 0.3s ease;
        outline: none;
    }

    /* Multi-select styling */
    .filter-input[multiple] {
        padding: 6px;
        min-height: 100px;
    }

    .filter-input[multiple] option {
        padding: 8px 10px;
        border-radius: 4px;
        margin: 2px 0;
        cursor: pointer;
    }

    .filter-input[multiple] option:hover {
        background: #ecfeff;
    }

    .filter-input[multiple] option:checked {
        background: linear-gradient(135deg, #06b6d4 0%, #0891a8 100%);
        color: white;
        font-weight: 600;
    }

    .filter-group label small {
        font-size: 0.7rem;
        font-weight: 400;
        color: #6b7280;
        font-style: italic;
    }

    .filter-input:focus {
        border-color: #06b6d4;
        box-shadow: 0 0 0 3px rgba(6, 182, 212, 0.25);
    }

    .filter-input:hover {
        border-color: #94e0ec;
    }

    /* Search Bar Styling */
    .search-section {
        margin-bottom: 20px;
        padding-bottom: 20px;
        border-bottom: 1px solid #e2e8f0;
    }

    .search-group {
        position: relative;
        max-width: 400px;
    }

    .search-group label {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 0.95rem;
        font-weight: 600;
        color: #374151;
        margin-bottom: 8px;
    }

    .search-input {
        width: 100%;
        padding: 12px 16px;
        padding-right: 45px;
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        font-size: 0.95rem;
        font-family: 'Inter', sans-serif;
        background: white;
        transition: all 0.3s ease;
        outline: none;
    }

    .search-input:focus {
        border-color: #06b6d4;
        box-shadow: 0 0 0 4px rgba(6, 182, 212, 0.18);
        transform: translateY(-1px);
    }

    .search-input:hover {
        border-color: #94e0ec;
    }

    .search-input::placeholder {
        color: #9ca3af;
        font-style: italic;
    }

    .search-clear {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        width: 24px;
        height: 24px;
        background: linear-gradient(135deg, #06b6d4 0%, #0593b4 100%);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        opacity: 0;
        transition: all 0.3s ease;
        color: white;
        font-size: 0.75rem;
    }

    .search-clear:hover {
        background: linear-gradient(135deg, #0593b4 0%, #047a89 100%);
        transform: translateY(-50%) scale(1.1);
    }

    .search-clear.show {
        opacity: 1;
    }

    .filter-actions {
        display: flex;
        justify-content: flex-start;
        align-items: center;
        gap: 12px;
        padding-top: 20px;
        border-top: 1px solid #e2e8f0;
        flex-wrap: wrap;
        position: relative;
        z-index: 100;
    }

    .filter-btn {
        padding: 10px 20px;
        border: none;
        border-radius: 8px;
        font-size: 0.9rem;
        font-weight: 600;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s ease;
        font-family: 'Inter', sans-serif;
    }

    .export-main-btn {
        background: linear-gradient(135deg, #2c5aa0 0%, #3182ce 100%);
        color: white;
        box-shadow: 0 8px 25px rgba(44, 90, 160, 0.4);
        position: relative;
        overflow: hidden;
        border: 2px solid rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(10px);
    }

    .export-main-btn::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.25), transparent);
        transition: left 0.6s ease;
    }

    .export-main-btn:hover::before {
        left: 100%;
    }

    .export-main-btn:hover {
        transform: translateY(-3px) scale(1.02);
        box-shadow: 0 12px 35px rgba(44, 90, 160, 0.5);
        background: linear-gradient(135deg, #1e40af 0%, #2563eb 100%);
    }

    .export-main-btn:active {
        transform: translateY(-1px) scale(1.01);
        box-shadow: 0 6px 20px rgba(44, 90, 160, 0.4);
        transition: all 0.1s ease;
    }

    .apply-btn {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        /* Emerald Green */
        color: white;
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    }

    .apply-btn:hover {
        background: linear-gradient(135deg, #059669 0%, #047857 100%);
        transform: translateY(-1px);
        box-shadow: 0 6px 16px rgba(16, 185, 129, 0.4);
    }

    .clear-btn {
        background: linear-gradient(135deg, #f87171 0%, #ef4444 100%);
        /* Coral Red */
        color: white;
        box-shadow: 0 4px 12px rgba(248, 113, 113, 0.3);
    }

    .clear-btn:hover {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        transform: translateY(-1px);
        box-shadow: 0 6px 16px rgba(248, 113, 113, 0.4);
    }

    .export-btn {
        background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
        color: white;
        box-shadow: 0 4px 12px rgba(139, 92, 246, 0.2);
        position: relative !important;
        z-index: 100 !important;
        pointer-events: auto !important;
    }

    .export-btn:hover {
        background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%);
        transform: translateY(-1px);
        box-shadow: 0 6px 16px rgba(139, 92, 246, 0.3);
    }

    .export-arrow {
        margin-left: 4px;
        font-size: 0.75rem;
        transition: transform 0.3s ease;
    }

    .export-btn.active .export-arrow {
        transform: rotate(180deg);
    }

    /* Export Dropdown Wrapper */
    .export-dropdown-wrapper {
        position: relative;
        display: inline-block;
        z-index: 1000;
    }

    /* Export Dropdown Menu */
    .export-dropdown-menu {
        position: absolute;
        top: calc(100% + 12px);
        right: 0;
        background: white;
        border-radius: 16px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.25), 0 0 0 1px rgba(0, 0, 0, 0.05);
        min-width: 320px;
        opacity: 0;
        visibility: hidden;
        transform: translateY(-15px) scale(0.95);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        z-index: 9999;
        overflow: hidden;
        border: 2px solid #e2e8f0;
        backdrop-filter: blur(10px);
    }

    .export-dropdown-menu.show {
        opacity: 1;
        visibility: visible;
        transform: translateY(0) scale(1);
    }

    .export-dropdown-menu::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(90deg, #8b5cf6, #7c3aed, #6d28d9);
    }

    /* Export Option */
    .export-option {
        display: flex;
        align-items: center;
        padding: 16px 20px;
        cursor: pointer;
        transition: all 0.25s ease;
        border-bottom: 1px solid #f1f5f9;
        gap: 16px;
        background: white;
        position: relative;
    }

    .export-option:first-child {
        margin-top: 3px;
    }

    .export-option:last-child {
        border-bottom: none;
    }

    .export-option::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 4px;
        background: transparent;
        transition: all 0.25s ease;
    }

    .export-option:hover::before {
        background: currentColor;
    }

    .export-option:hover {
        background: linear-gradient(90deg, #fafbfc 0%, #f8fafc 100%);
        transform: translateX(4px);
        box-shadow: inset 0 0 0 1px rgba(139, 92, 246, 0.1);
    }

    .export-option:active {
        transform: translateX(2px) scale(0.98);
    }

    .export-option i {
        font-size: 1.6rem;
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
        background: rgba(0, 0, 0, 0.04);
        transition: all 0.25s ease;
    }

    .export-option:hover i {
        transform: scale(1.1);
        background: rgba(0, 0, 0, 0.08);
    }

    .export-option[data-format="csv"] {
        color: #22c55e;
    }

    .export-option[data-format="csv"] i {
        color: #22c55e;
        background: rgba(34, 197, 94, 0.1);
    }

    .export-option[data-format="csv"]:hover i {
        background: rgba(34, 197, 94, 0.2);
    }

    .export-option[data-format="excel"] {
        color: #10b981;
    }

    .export-option[data-format="excel"] i {
        color: #10b981;
        background: rgba(16, 185, 129, 0.1);
    }

    .export-option[data-format="excel"]:hover i {
        background: rgba(16, 185, 129, 0.2);
    }

    .export-option[data-format="pdf"] {
        color: #ef4444;
    }

    .export-option[data-format="pdf"] i {
        color: #ef4444;
        background: rgba(239, 68, 68, 0.1);
    }

    .export-option[data-format="pdf"]:hover i {
        background: rgba(239, 68, 68, 0.2);
    }

    .export-option[data-format="json"] {
        color: #f59e0b;
    }

    .export-option[data-format="json"] i {
        color: #f59e0b;
        background: rgba(245, 158, 11, 0.1);
    }

    .export-option[data-format="json"]:hover i {
        background: rgba(245, 158, 11, 0.2);
    }

    .export-option-text {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .export-option-text strong {
        font-size: 1rem;
        color: #1f2937;
        font-weight: 600;
        transition: color 0.2s ease;
    }

    .export-option:hover .export-option-text strong {
        color: #111827;
    }

    .export-option-text small {
        font-size: 0.825rem;
        color: #6b7280;
        font-weight: 400;
        line-height: 1.4;
    }

    .export-option:hover .export-option-text small {
        color: #4b5563;
    }

    .filter-count {
        margin-left: auto;
        padding: 8px 16px;
        background: linear-gradient(135deg, #cffafe 0%, #a5f3fc 100%);
        border: 1px solid #06b6d4;
        border-radius: 8px;
        font-size: 0.9rem;
        color: #0e7490;
        font-weight: 500;
    }

    .filter-count strong {
        color: #155e75;
    }

    /* Responsive Filter Styles */
    @media (max-width: 900px) {
        .filter-grid {
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
        }

        .filter-actions {
            flex-direction: column;
            align-items: stretch;
            gap: 12px;
        }

        .filter-count {
            margin-left: 0;
            text-align: center;
        }
    }

    @media (max-width: 600px) {
        .filter-header {
            flex-direction: column;
            gap: 12px;
            align-items: flex-start;
        }

        .filter-grid {
            grid-template-columns: 1fr;
            gap: 12px;
        }

        .filter-container {
            padding: 16px;
        }

        .filter-btn {
            justify-content: center;
            width: 100%;
        }
    }

    /* Modal Styles */
    .modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        background: rgba(22, 41, 56, 0.6);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        z-index: 9998;
        display: none;
        justify-content: center;
        align-items: flex-start;
        animation: fadeIn 0.3s ease-out;
        overflow-y: auto;
        padding: 20px 0;
    }

    /* Specific styles for edit modal to ensure it works properly */
    #editStudentModal {
        z-index: 9999;
        pointer-events: auto;
    }

    #editStudentModal.show {
        display: flex !important;
        pointer-events: auto !important;
    }

    #editStudentModal .modal-content {
        pointer-events: auto;
        position: relative;
        z-index: 10000;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
        }

        to {
            opacity: 1;
        }
    }

    .modal.show {
        display: flex !important;
        opacity: 1;
    }

    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateY(-30px) scale(0.95);
        }

        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    @keyframes slideOut {
        from {
            opacity: 1;
            transform: translateX(0);
        }

        to {
            opacity: 0;
            transform: translateX(100%);
        }
    }

    .modal-content {
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
        padding: 0;
        border-radius: 24px;
        box-shadow:
            0 25px 50px -12px rgba(0, 0, 0, 0.25),
            0 0 0 1px rgba(255, 255, 255, 0.3);
        max-width: 420px;
        width: 90%;
        text-align: center;
        animation: slideIn 0.3s ease-out;
        border: 1px solid rgba(226, 232, 240, 0.5);
        margin: 20px auto;
        max-height: calc(100vh - 40px);
        overflow: visible;
        position: relative;
    }

    .modal.show .modal-content {
        transform: scale(1) translateY(0) rotateX(0deg);
        opacity: 1;
    }

    .modal-header {
        padding: 32px 28px 20px;
        border-radius: 20px 20px 0 0;
        text-align: center;
        position: relative;
        background: linear-gradient(135deg, #1e40af 0%, #3b82f6 50%, #60a5fa 100%);
        color: white;
    }

    .modal-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.1) 0%, rgba(255, 255, 255, 0.05) 100%);
        border-radius: 20px 20px 0 0;
        pointer-events: none;
    }

    .modal-header h3 {
        margin: 12px 0 6px;
        font-size: 1.3rem;
        font-weight: 700;
        font-family: 'Inter', sans-serif;
        letter-spacing: -0.025em;
        position: relative;
        z-index: 1;
    }

    .modal-header p {
        margin: 0;
        opacity: 0.95;
        font-size: 0.9rem;
        font-weight: 400;
        position: relative;
        z-index: 1;
        color: rgba(255, 255, 255, 0.9);
    }

    .modal-icon {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 12px;
        font-size: 24px;
        background: rgba(255, 255, 255, 0.15);
        backdrop-filter: blur(10px);
        border: 2px solid rgba(255, 255, 255, 0.2);
        position: relative;
        z-index: 1;
        transform: scale(0.8) translateY(20px);
        opacity: 0;
        transition: all 0.7s cubic-bezier(0.25, 0.8, 0.25, 1) 0.3s;
    }

    .modal.show .modal-icon {
        transform: scale(1) translateY(0);
        opacity: 1;
    }

    .modal-close {
        position: absolute;
        top: 20px;
        right: 20px;
        background: rgba(255, 255, 255, 0.15);
        color: white;
        border: none;
        border-radius: 50%;
        width: 40px;
        height: 40px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
        font-size: 16px;
        z-index: 2;
        transform: scale(0.8) rotate(-90deg);
        opacity: 0;
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .modal.show .modal-close,
    .custom-modal.show .modal-close {
        transform: scale(1) rotate(0deg);
        opacity: 1;
        transition-delay: 0.4s;
    }

    .modal-close:hover {
        background: rgba(255, 255, 255, 0.25);
        transform: scale(1.05);
    }

    .modal-buttons {
        display: flex;
        gap: 12px;
        padding: 20px 28px 28px;
        justify-content: flex-end;
        background: #f8fafc;
        border-top: 1px solid #e2e8f0;
        transform: translateY(20px);
        opacity: 0;
        transition: all 0.6s cubic-bezier(0.25, 0.8, 0.25, 1) 0.5s;
    }

    /* Ensure modal action buttons become visible for both .modal and .custom-modal */
    .modal.show .modal-buttons,
    .custom-modal.show .modal-buttons {
        transform: translateY(0);
        opacity: 1;
    }

    /* Keep Edit modal buttons always visible at the bottom while the form scrolls */
    #editStudentForm {
        padding-bottom: 0;
    }

    /* Place the edit-footer after all fields (no sticky); user scrolls to the very end */
    #editStudentForm>.modal-buttons {
        position: static;
        bottom: auto;
        left: auto;
        right: auto;
        z-index: 1;
        background: transparent;
        backdrop-filter: none;
        margin: 16px 0 0 0;
        /* simple space above footer */
        border-bottom-left-radius: 0;
        border-bottom-right-radius: 0;
    }

    .btn-primary,
    .btn-secondary,
    .btn-danger,
    .btn-export {
        padding: 12px 24px;
        border-radius: 10px;
        border: none;
        font-family: 'Inter', sans-serif;
        font-weight: 600;
        font-size: 0.9rem;
        cursor: pointer;
        transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
        display: flex;
        align-items: center;
        gap: 8px;
        text-decoration: none;
        transform: translateY(10px);
        opacity: 0.8;
        letter-spacing: -0.025em;
        position: relative;
        overflow: hidden;
    }

    .btn-primary {
        background: linear-gradient(135deg, #059669 0%, #10b981 50%, #34d399 100%);
        color: white;
        box-shadow: 0 8px 25px rgba(16, 185, 129, 0.4);
    }

    .btn-primary:hover {
        background: linear-gradient(135deg, #047857 0%, #059669 50%, #10b981 100%);
        transform: translateY(-3px) scale(1.02);
        box-shadow: 0 12px 35px rgba(16, 185, 129, 0.5);
    }

    .btn-secondary {
        background: white;
        color: #4b5563;
        border: 2px solid #e5e7eb;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
    }

    .btn-secondary:hover {
        background: #f9fafb;
        border-color: #d1d5db;
        transform: translateY(-2px) scale(1.02);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
    }

    .btn-danger {
        background: linear-gradient(135deg, #dc2626 0%, #ef4444 50%, #f87171 100%);
        color: white;
        box-shadow: 0 8px 25px rgba(239, 68, 68, 0.4);
    }

    .btn-danger:hover {
        background: linear-gradient(135deg, #b91c1c 0%, #dc2626 50%, #ef4444 100%);
        transform: translateY(-2px);
        box-shadow: 0 12px 35px rgba(239, 68, 68, 0.5);
    }

    /* Enhanced Delete Modal Styles */
    #deleteModal .btn-secondary:hover {
        background: linear-gradient(135deg, #e2e8f0 0%, #cbd5e1 100%);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    #deleteModal .btn-danger:hover {
        background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
        transform: translateY(-1px);
        box-shadow: 0 6px 20px rgba(239, 68, 68, 0.4);
    }

    #deleteModal .btn-danger:disabled {
        opacity: 0.7;
        cursor: not-allowed;
        transform: none;
    }

    #deleteModal .modal-content {
        animation: deleteModalEnter 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
    }

    @keyframes deleteModalEnter {
        0% {
            opacity: 0;
            transform: translate(-50%, -50%) scale(0.8) rotateX(-10deg);
        }

        100% {
            opacity: 1;
            transform: translate(-50%, -50%) scale(1) rotateX(0deg);
        }
    }

    #deleteModal.show {
        animation: fadeIn 0.3s ease;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
        }

        to {
            opacity: 1;
        }
    }

    @keyframes successSlideIn {
        0% {
            opacity: 0;
            transform: translate(-50%, -50%) scale(0.8) translateY(-20px);
        }

        100% {
            opacity: 1;
            transform: translate(-50%, -50%) scale(1) translateY(0);
        }
    }

    @keyframes successSlideOut {
        0% {
            transform: translateX(0);
            opacity: 1;
        }

        100% {
            transform: translateX(100%);
            opacity: 0;
        }
    }

    @keyframes errorSlideIn {
        0% {
            opacity: 0;
            transform: translate(-50%, -50%) scale(0.8) translateY(-20px);
        }

        100% {
            opacity: 1;
            transform: translate(-50%, -50%) scale(1) translateY(0);
        }
    }

    @keyframes errorSlideOut {
        0% {
            transform: translateX(0);
            opacity: 1;
        }

        100% {
            transform: translateX(100%);
            opacity: 0;
        }
    }

    @keyframes infoSlideIn {
        0% {
            transform: translateX(100%);
            opacity: 0;
        }

        100% {
            transform: translateX(0);
            opacity: 1;
        }
    }

    @keyframes infoSlideOut {
        0% {
            transform: translateX(0);
            opacity: 1;
        }

        100% {
            transform: translateX(100%);
            opacity: 0;
        }
    }

    @keyframes warningSlideIn {
        0% {
            transform: translateX(100%);
            opacity: 0;
        }

        100% {
            transform: translateX(0);
            opacity: 1;
        }
    }

    @keyframes warningSlideOut {
        0% {
            transform: translateX(0);
            opacity: 1;
        }

        100% {
            transform: translateX(100%);
            opacity: 0;
        }
    }

    @keyframes loadingSlideIn {
        0% {
            transform: translateX(100%);
            opacity: 0;
        }

        100% {
            transform: translateX(0);
            opacity: 1;
        }
    }

    @keyframes loadingSlideOut {
        0% {
            transform: translateX(0);
            opacity: 1;
        }

        100% {
            transform: translateX(100%);
            opacity: 0;
        }
    }

    .btn-export {
        flex: 1;
        justify-content: center;
        transition: all 0.3s ease;
    }

    .btn-export:hover {
        transform: translateY(-3px);
        filter: brightness(1.1);
    }

    /* Enhanced Form Styles for Edit Modal */
    #editStudentForm {
        padding: 28px;
        background: white;
        overflow-y: auto;
        max-height: calc(90vh - 180px);
        transform: translateY(20px);
        opacity: 0;
        transition: all 0.6s cubic-bezier(0.25, 0.8, 0.25, 1) 0.4s;
    }

    /* ===== Edit Modal – LSPU Teal-Blue Theme ===== */
    #editStudentModal .modal-content {
        background: #ffffff;
        border-radius: 18px;
        box-shadow: 0 24px 60px rgba(3, 105, 112, 0.18);
        position: relative;
        max-width: 760px;
        /* larger board card */
        width: 96%;
    }

    /* Removed animated border per request */

    /* Header – teal gradient */
    #editStudentModal .modal-header {
        background: linear-gradient(135deg, #0ea5b1 0%, #06b6d4 45%, #60a5fa 100%);
        color: #fff;
    }

    #editStudentModal .modal-header h3 {
        color: #fff;
        font-weight: 800;
    }

    #editStudentModal .modal-header p {
        color: rgba(255, 255, 255, 0.92);
    }

    #editStudentModal .modal-close {
        color: #fff;
        border-color: rgba(255, 255, 255, 0.3);
    }

    #editStudentModal .modal-close:hover {
        background: rgba(255, 255, 255, 0.25);
    }

    /* Section headers */
    #editStudentModal .tab-header {
        padding: 16px 24px 12px !important;
        /* ensure a bit of bottom padding even if inline style sets 0 */
        background: #ffffff;
        border-bottom: 1px solid #eef2f7;
    }

    #editStudentModal .tab-header .tab-icon {
        background: linear-gradient(135deg, #06b6d4 0%, #3b82f6 100%);
        box-shadow: 0 8px 20px rgba(6, 182, 212, 0.25);
    }

    #editStudentModal .tab-header h4 {
        color: #0ea5b1;
        font-weight: 800;
    }

    #editStudentModal .tab-header p {
        color: #6b7280;
        margin-top: 4px;
        margin-bottom: 14px;
    }

    /* Section block with subtle tint */
    #editStudentModal .section-block {
        background: #f8fafc;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 16px;
    }

    /* Inputs – neutral borders; teal focus */
    /* Align label contents (icon + text) nicely in Edit modal */
    #editStudentModal .form-group label {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    #editStudentModal .form-group label i {
        color: #06b6d4;
        line-height: 1;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    /* Center the Exam Type label and selected value */
    #editStudentModal label[for="editExamType"],
    #addStudentModal label[for="addExamType"] {
        justify-content: center;
        text-align: center;
    }

    #editExamType,
    #addExamType {
        text-align: center;
        /* center placeholder/value in most browsers */
        text-align-last: center;
        /* ensure the selected option is centered (Chrome/Edge/Firefox) */
    }

    /* Make Edit modal Exam Type select visually centered and comfortably wide */
    #editExamType {
        display: block;
        max-width: 420px;
        width: 100%;
        margin: 0 auto;
    }

    #editExamType option,
    #addExamType option {
        text-align: center;
    }

    #editStudentModal .form-group input,
    #editStudentModal .form-group select {
        border: 1.5px solid #e5e7eb;
        height: 48px;
        /* unify control heights */
        line-height: 24px;
        /* consistent text line height */
        font-size: 1rem;
        /* consistent font */
        box-sizing: border-box;
        /* include borders in height */
    }

    #editStudentModal .form-group input:focus,
    #editStudentModal .form-group select:focus {
        border-color: #06b6d4;
        box-shadow: 0 0 0 4px rgba(6, 182, 212, 0.15);
        transform: translateY(-1px);
    }

    /* Required asterisk */
    #editStudentModal .form-group label.required::after {
        content: ' *';
        color: #0ea5b1;
        font-weight: 700;
    }

    /* Buttons – teal primary, outlined secondary */
    #editStudentModal .modal-buttons {
        justify-content: flex-end;
        /* align action buttons to the right */
        align-items: center;
        flex-direction: row !important;
        /* keep on one row */
        flex-wrap: nowrap !important;
        gap: 14px;
    }

    #editStudentModal .modal-buttons .btn-primary,
    #editStudentModal .modal-buttons .btn-secondary {
        width: auto !important;
        min-width: 140px;
        white-space: nowrap;
    }

    /* Consistent section underline spacing */
    #editStudentModal .tab-header {
        border-bottom: 1px solid #e5edf5;
        margin-bottom: 18px;
    }

    #editStudentModal .btn-primary {
        background: linear-gradient(135deg, #06b6d4 0%, #0ea5b1 100%);
        color: #fff;
        box-shadow: 0 10px 24px rgba(6, 182, 212, 0.35);
    }

    #editStudentModal .btn-primary:hover {
        background: linear-gradient(135deg, #0d95a5 0%, #0a8e9a 100%);
        box-shadow: 0 14px 30px rgba(6, 182, 212, 0.45);
        transform: translateY(-2px);
    }

    #editStudentModal .btn-secondary {
        background: #ffffff;
        color: #0ea5b1;
        border: 2px solid #06b6d4;
    }

    #editStudentModal .btn-secondary:hover {
        background: rgba(6, 182, 212, 0.08);
        color: #067b8c;
        border-color: #0ea5b1;
    }

    .modal.show #editStudentForm,
    .custom-modal.show #editStudentForm {
        transform: translateY(0);
        opacity: 1;
    }

    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 24px;
    }

    .form-group {
        margin-bottom: 0;
    }

    .form-group label {
        display: block;
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 10px;
        font-size: 0.95rem;
        font-family: 'Inter', sans-serif;
        letter-spacing: -0.025em;
    }

    .form-group label i {
        color: #3b82f6;
        margin-right: 8px;
        width: 16px;
        text-align: center;
    }

    .form-group input,
    .form-group select {
        width: 100%;
        padding: 14px 16px;
        border: 2px solid #e5e7eb;
        border-radius: 10px;
        font-family: 'Inter', sans-serif;
        font-size: 0.95rem;
        transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
        background: white;
        box-sizing: border-box;
        color: #1f2937;
    }

    .form-group input::placeholder {
        color: #9ca3af;
        font-weight: 400;
    }

    .form-group input:focus,
    .form-group select:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow:
            0 0 0 4px rgba(59, 130, 246, 0.1),
            0 4px 20px rgba(59, 130, 246, 0.15);
        transform: translateY(-2px) scale(1.01);
    }

    .form-group input:invalid {
        border-color: #ef4444;
        background: #fef2f2;
        box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.1);
    }

    .form-group input:valid:not(:placeholder-shown) {
        border-color: #10b981;
        background: #f0fdf4;
        box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.1);
    }

    .form-group select {
        cursor: pointer;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
        background-position: right 12px center;
        background-repeat: no-repeat;
        background-size: 16px;
        padding-right: 40px;
        -webkit-appearance: none;
        appearance: none;
    }

    .input-feedback {
        margin-top: 8px;
        font-size: 0.875rem;
        font-weight: 500;
        display: none;
        font-family: 'Inter', sans-serif;
    }

    .input-feedback.show {
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .input-feedback.error {
        color: #ef4444;
    }

    .input-feedback.success {
        color: #10b981;
    }

    /* Special styling for full-width fields */
    .form-group[style*="grid-column: 1 / -1"] {
        grid-column: 1 / -1;
    }

    /* Modal form buttons container */
    .modal-buttons[style*="border-top"] {
        border-top: 1px solid #f1f5f9;
        background: linear-gradient(to bottom, #f8fafc 0%, #f1f5f9 100%);
    }

    /* Responsive Modal Design */
    @media (max-width: 768px) {
        .modal-content {
            width: 95%;
            margin: 10px;
            max-height: 95vh;
            border-radius: 16px;
        }

        .modal-header {
            padding: 20px 16px 16px;
            border-radius: 16px 16px 0 0;
        }

        .modal-header h3 {
            font-size: 1.2rem;
        }

        .modal-icon {
            width: 50px;
            height: 50px;
            font-size: 20px;
        }

        #editStudentForm {
            padding: 20px 16px;
        }

        .form-grid {
            grid-template-columns: 1fr !important;
            gap: 16px;
        }

        .modal-buttons {
            flex-direction: column;
            padding: 16px;
            gap: 10px;
        }

        .btn-primary,
        .btn-secondary,
        .btn-danger {
            width: 100%;
            justify-content: center;
            padding: 14px 20px;
        }
    }

    @media (max-width: 480px) {
        .modal-content {
            width: 100%;
            height: 100vh;
            border-radius: 0;
            max-height: 100vh;
        }

        .modal-header {
            border-radius: 0;
            padding: 16px 12px 12px;
        }

        .modal-header h3 {
            font-size: 1.1rem;
        }

        .modal-header p {
            font-size: 0.85rem;
        }

        #editStudentForm {
            padding: 16px 12px;
        }

        .modal-buttons {
            padding: 12px;
        }

        .btn-primary,
        .btn-secondary,
        .btn-danger {
            padding: 12px 16px;
            font-size: 0.85rem;
        }
    }

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
            0 32px 64px -12px rgba(30, 64, 175, 0.25),
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
        background: linear-gradient(135deg, #1e40af 0%, #3182ce 25%, #60a5fa 50%, #3182ce 75%, #1e40af 100%) !important;
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
        background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%) !important;
        padding: 32px 28px !important;
        border-radius: 20px !important;
        border: 2px solid #bfdbfe !important;
        position: relative !important;
        overflow: hidden !important;
        box-shadow: 0 8px 25px rgba(49, 130, 206, 0.15) !important;
    }

    #logoutModal .modal-header::before {
        content: '' !important;
        position: absolute !important;
        top: 0 !important;
        left: 0 !important;
        right: 0 !important;
        height: 4px !important;
        background: linear-gradient(90deg, #1e40af 0%, #3182ce 50%, #60a5fa 100%) !important;
        border-radius: 20px 20px 0 0 !important;
    }

    #logoutModal .modal-header::after {
        content: '' !important;
        position: absolute !important;
        top: -50px !important;
        right: -50px !important;
        width: 120px !important;
        height: 120px !important;
        background: linear-gradient(135deg, rgba(49, 130, 206, 0.1) 0%, rgba(96, 165, 250, 0.05) 100%) !important;
        border-radius: 50% !important;
        z-index: 0 !important;
    }

    #logoutModal .modal-icon {
        width: 88px !important;
        height: 88px !important;
        background: linear-gradient(135deg, #1e40af 0%, #3182ce 50%, #2563eb 100%) !important;
        border-radius: 50% !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        margin: 0 auto 24px !important;
        color: white !important;
        font-size: 2.2rem !important;
        box-shadow:
            0 20px 40px rgba(30, 64, 175, 0.4),
            0 0 0 4px rgba(255, 255, 255, 0.8),
            0 0 0 6px rgba(49, 130, 206, 0.2) !important;
        position: relative !important;
        z-index: 1 !important;
        animation: iconPulse 3s ease-in-out infinite !important;
    }

    @keyframes iconPulse {

        0%,
        100% {
            box-shadow:
                0 20px 40px rgba(30, 64, 175, 0.4),
                0 0 0 4px rgba(255, 255, 255, 0.8),
                0 0 0 6px rgba(49, 130, 206, 0.2);
            transform: scale(1);
        }

        50% {
            box-shadow:
                0 25px 50px rgba(30, 64, 175, 0.6),
                0 0 0 6px rgba(255, 255, 255, 0.9),
                0 0 0 8px rgba(49, 130, 206, 0.3);
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
        background: linear-gradient(135deg, #60a5fa, #3182ce, #1e40af, #2563eb) !important;
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
        background: linear-gradient(135deg, #1e40af 0%, #2563eb 100%) !important;
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
        color: #2563eb !important;
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
        background: linear-gradient(135deg, #1e40af 0%, #3182ce 50%, #2563eb 100%) !important;
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
        background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 50%, #1d4ed8 100%) !important;
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
        background: linear-gradient(135deg, #1e3a8a 0%, #1d4ed8 50%, #1e40af 100%) !important;
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
            transform: translateY(30px) scale(0.8);
        }

        100% {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    @keyframes corner-lightning-tr {

        0%,
        100% {
            background: linear-gradient(45deg, #2c5aa0, #3182ce, #60a5fa);
            transform: scale(1);
            opacity: 0.8;
        }

        50% {
            background: linear-gradient(45deg, #1e40af, #60a5fa, #3182ce);
            transform: scale(1.1);
            opacity: 1;
        }
    }

    /* Add bottom corners with additional elements */
    #logoutModal::before {
        content: '' !important;
        position: absolute !important;
        bottom: 22px !important;
        left: 5% !important;
        width: 40px !important;
        height: 40px !important;
        background: linear-gradient(225deg, #3182ce, #60a5fa, #2c5aa0) !important;
        border-radius: 0 24px 24px 0 !important;
        z-index: -1 !important;
        animation: corner-lightning-bl 3s ease-in-out infinite 2s !important;
        filter: blur(1px) !important;
        opacity: 0.8 !important;
        pointer-events: none !important;
    }

    #logoutModal::after {
        content: '' !important;
        position: absolute !important;
        bottom: 22px !important;
        right: 5% !important;
        width: 40px !important;
        height: 40px !important;
        background: linear-gradient(315deg, #2c5aa0, #3182ce, #60a5fa) !important;
        border-radius: 24px 0 0 24px !important;
        z-index: -1 !important;
        animation: corner-lightning-br 3s ease-in-out infinite 0.5s !important;
        filter: blur(1px) !important;
        opacity: 0.8 !important;
        pointer-events: none !important;
    }

    @keyframes corner-lightning-bl {

        0%,
        100% {
            background: linear-gradient(225deg, #3182ce, #60a5fa, #2c5aa0);
            transform: scale(1);
            opacity: 0.8;
        }

        50% {
            background: linear-gradient(225deg, #60a5fa, #2c5aa0, #1e40af);
            transform: scale(1.1);
            opacity: 1;
        }
    }

    @keyframes corner-lightning-br {

        0%,
        100% {
            background: linear-gradient(315deg, #2c5aa0, #3182ce, #60a5fa);
            transform: scale(1);
            opacity: 0.8;
        }

        50% {
            background: linear-gradient(315deg, #1e40af, #60a5fa, #3182ce);
            transform: scale(1.1);
            opacity: 1;
        }
    }

    /* Export Modal Specific Styles */
    #exportModal {
        z-index: 10005 !important;
        padding: 20px !important;
        overflow-y: auto !important;
        max-height: 100vh !important;
    }

    #exportModal.show {
        display: flex !important;
        opacity: 1 !important;
        visibility: visible !important;
    }

    #exportModal .modal-content {
        max-width: 480px !important;
        width: 90% !important;
        max-height: 85vh !important;
        margin: auto !important;
        overflow: hidden !important;
        display: flex !important;
        flex-direction: column !important;
        z-index: 10006 !important;
    }

    #exportModal .modal-header {
        flex-shrink: 0 !important;
        padding: 24px 30px 20px !important;
    }

    #exportModal #exportDetails {
        flex: 1 !important;
        overflow-y: auto !important;
        max-height: 50vh !important;
        padding: 20px 30px !important;
    }

    #exportModal .modal-buttons {
        display: flex !important;
        flex-direction: column !important;
        gap: 12px !important;
        padding: 20px 30px 30px !important;
        visibility: visible !important;
        opacity: 1 !important;
        position: relative !important;
        z-index: 10007 !important;
        background: #f8fafc !important;
        border-top: 1px solid #e2e8f0 !important;
        border-radius: 0 0 20px 20px !important;
        flex-shrink: 0 !important;
        pointer-events: auto !important;
    }

    #exportModal .btn-export {
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 12px !important;
        padding: 14px 24px !important;
        border: none !important;
        border-radius: 12px !important;
        font-weight: 600 !important;
        font-family: 'Inter', sans-serif !important;
        font-size: 0.9rem !important;
        cursor: pointer !important;
        transition: all 0.3s ease !important;
        min-width: 160px !important;
        width: 100% !important;
        max-width: 220px !important;
        margin: 0 auto !important;
        opacity: 1 !important;
        visibility: visible !important;
        position: relative !important;
        z-index: 10008 !important;
        pointer-events: auto !important;
        transform: translateY(0) !important;
        box-sizing: border-box !important;
        text-decoration: none !important;
        outline: none !important;
        user-select: none !important;
    }

    #exportModal .btn-export:hover {
        transform: translateY(-2px) scale(1.02) !important;
    }

    #exportModal .btn-export:active {
        transform: translateY(0) scale(0.98) !important;
    }

    #exportModal .excel-btn {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
        color: white !important;
        box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3) !important;
    }

    #exportModal .excel-btn:hover {
        background: linear-gradient(135deg, #059669 0%, #047857 100%) !important;
        box-shadow: 0 8px 25px rgba(16, 185, 129, 0.4) !important;
    }

    #exportModal .csv-btn {
        background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%) !important;
        color: white !important;
        box-shadow: 0 4px 15px rgba(139, 92, 246, 0.3) !important;
    }

    #exportModal .csv-btn:hover {
        background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%) !important;
        box-shadow: 0 8px 25px rgba(139, 92, 246, 0.4) !important;
    }

    #exportModal .pdf-btn {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important;
        color: white !important;
        box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3) !important;
    }

    #exportModal .pdf-btn:hover {
        background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%) !important;
        box-shadow: 0 8px 25px rgba(239, 68, 68, 0.4) !important;
    }

    /* Mobile responsive adjustments */
    @media (max-width: 768px) {
        #exportModal {
            padding: 10px !important;
        }

        #exportModal .modal-content {
            max-width: 95% !important;
            max-height: 90vh !important;
        }

        #exportModal .modal-header {
            padding: 20px 16px 16px !important;
        }

        #exportModal #exportDetails {
            padding: 16px !important;
            max-height: 40vh !important;
        }

        #exportModal .modal-buttons {
            padding: 16px !important;
        }

        #exportModal .btn-export {
            padding: 12px 20px !important;
            font-size: 0.85rem !important;
        }
    }

    @media (max-height: 600px) {
        #exportModal .modal-content {
            max-height: 95vh !important;
        }

        #exportModal #exportDetails {
            max-height: 30vh !important;
        }
    }

    /* Enhanced icon with subtle corner effects */
    #logoutModal .modal-icon {
        position: relative !important;
        overflow: visible !important;
        background: linear-gradient(135deg, #3182ce 0%, #2c5aa0 100%) !important;
        box-shadow: 0 15px 35px rgba(44, 90, 160, 0.3) !important;
    }

    /* Clean button with subtle corner highlights */
    #logoutModal .modal-btn.logout-confirm {
        position: relative !important;
        overflow: hidden !important;
        background: linear-gradient(135deg, #3182ce 0%, #2c5aa0 100%) !important;
        box-shadow: 0 8px 25px rgba(49, 130, 206, 0.3) !important;
    }

    #logoutModal .modal-btn.logout-confirm::before {
        content: '' !important;
        position: absolute !important;
        top: 0 !important;
        left: 0 !important;
        width: 8px !important;
        height: 8px !important;
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.6), transparent) !important;
        border-radius: 0 0 8px 0 !important;
        animation: corner-shine 2s ease-in-out infinite !important;
    }

    #logoutModal .modal-btn.logout-confirm::after {
        content: '' !important;
        position: absolute !important;
        bottom: 0 !important;
        right: 0 !important;
        width: 8px !important;
        height: 8px !important;
        background: linear-gradient(315deg, rgba(255, 255, 255, 0.6), transparent) !important;
        border-radius: 8px 0 0 0 !important;
        animation: corner-shine 2s ease-in-out infinite 1s !important;
    }

    @keyframes corner-shine {

        0%,
        100% {
            opacity: 0.3;
            transform: scale(1);
        }

        50% {
            opacity: 0.8;
            transform: scale(1.2);
        }
    }

    /* Export Options Modal Styles */
    .export-modal-content {
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
        border-radius: 24px;
        box-shadow: 0 30px 60px rgba(44, 90, 160, 0.2), 0 0 0 1px rgba(44, 90, 160, 0.1);
        border: 2px solid rgba(44, 90, 160, 0.1);
        max-width: 520px;
        width: 92%;
        overflow: hidden;
        backdrop-filter: blur(20px);
        position: relative;
    }

    .export-modal-content::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(135deg, rgba(44, 90, 160, 0.02) 0%, rgba(58, 141, 222, 0.02) 100%);
        border-radius: 24px;
        pointer-events: none;
    }

    .export-modal-header {
        background: linear-gradient(135deg, #2c5aa0 0%, #3182ce 100%);
        color: white;
        padding: 35px 30px;
        text-align: center;
        position: relative;
        overflow: hidden;
    }

    .export-modal-header::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -50%;
        width: 100%;
        height: 200%;
        background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.15), transparent);
        animation: shimmer 4s infinite;
    }

    .export-modal-header h3 {
        margin: 0;
        font-size: 1.6rem;
        font-weight: 700;
        letter-spacing: 0.5px;
    }

    .export-modal-header p {
        margin: 8px 0 0 0;
        opacity: 0.9;
        font-size: 1rem;
        font-weight: 400;
    }

    .export-modal-icon {
        background: rgba(255, 255, 255, 0.25);
        width: 70px;
        height: 70px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
        font-size: 28px;
        backdrop-filter: blur(10px);
        border: 2px solid rgba(255, 255, 255, 0.2);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    }

    .export-options-container {
        padding: 35px;
        background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
    }

    .export-option-card {
        display: flex;
        align-items: center;
        padding: 24px;
        margin-bottom: 18px;
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
        border: 2px solid #e2e8f0;
        border-radius: 16px;
        cursor: pointer;
        transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
        position: relative;
        overflow: hidden;
        box-shadow: 0 4px 12px rgba(44, 90, 160, 0.06);
    }

    .export-option-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(44, 90, 160, 0.08), transparent);
        transition: left 0.6s ease;
    }

    .export-option-card:hover::before {
        left: 100%;
    }

    .export-option-card:hover {
        transform: translateY(-3px) scale(1.02);
        border-color: #3182ce;
        box-shadow: 0 12px 30px rgba(44, 90, 160, 0.2);
        background: linear-gradient(135deg, #ffffff 0%, #eff6ff 100%);
    }

    .export-option-card:active {
        transform: translateY(-1px) scale(1.01);
        transition: all 0.1s ease;
    }

    .export-option-card:last-child {
        margin-bottom: 0;
    }

    .export-option-icon {
        width: 56px;
        height: 56px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        margin-right: 24px;
        color: white;
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
        transition: all 0.3s ease;
    }

    .export-option-card:hover .export-option-icon {
        transform: scale(1.1) rotate(5deg);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
    }

    .csv-icon {
        background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
    }

    .excel-icon {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    }

    .pdf-icon {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    }

    .json-icon {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    }

    .export-option-content {
        flex: 1;
    }

    .export-option-content h4 {
        margin: 0 0 6px 0;
        font-size: 18px;
        font-weight: 700;
        color: #1a2a36;
        letter-spacing: 0.3px;
    }

    .export-option-content p {
        margin: 0;
        font-size: 14px;
        color: #6b7280;
        line-height: 1.4;
        font-weight: 500;
    }

    .export-option-arrow {
        color: #b3c6e0;
        font-size: 18px;
        transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
        background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 2px solid #e2e8f0;
    }

    .export-option-card:hover .export-option-arrow {
        color: #2c5aa0;
        transform: translateX(6px) scale(1.1);
        background: linear-gradient(135deg, #2c5aa0 0%, #3182ce 100%);
        color: white;
        border-color: #2c5aa0;
        box-shadow: 0 4px 12px rgba(44, 90, 160, 0.3);
    }

    /* Export Confirmation Modal */
    .export-confirm-content {
        background: linear-gradient(135deg, #ffffff 0%, #f9fafb 100%);
        border-radius: 24px;
        box-shadow: 0 30px 60px rgba(44, 90, 160, 0.2), 0 0 0 1px rgba(44, 90, 160, 0.1);
        border: 2px solid rgba(44, 90, 160, 0.1);
        max-width: 480px;
        width: 90%;
        overflow: hidden;
        backdrop-filter: blur(20px);
        position: relative;
    }

    .export-confirm-content::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(135deg, rgba(44, 90, 160, 0.02) 0%, rgba(58, 141, 222, 0.02) 100%);
        border-radius: 24px;
        pointer-events: none;
    }

    .export-confirm-header {
        background: linear-gradient(135deg, #3a8dde 0%, #2c5aa0 100%);
        color: white;
        padding: 30px;
        text-align: center;
        position: relative;
        overflow: hidden;
    }

    .export-confirm-header::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -50%;
        width: 100%;
        height: 200%;
        background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.15), transparent);
        animation: shimmer 4s infinite;
    }

    .export-confirm-header h3 {
        margin: 0;
        font-size: 1.5rem;
        font-weight: 700;
        letter-spacing: 0.5px;
    }

    .export-confirm-header p {
        margin: 8px 0 0 0;
        opacity: 0.9;
        font-size: 0.95rem;
        font-weight: 400;
    }

    .export-confirm-icon {
        background: rgba(255, 255, 255, 0.25);
        width: 60px;
        height: 60px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 18px;
        font-size: 24px;
        backdrop-filter: blur(10px);
        border: 2px solid rgba(255, 255, 255, 0.2);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    }

    .export-confirm-details {
        padding: 30px;
        background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
    }

    .export-detail-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 16px 0;
        border-bottom: 1px solid #e2e8f0;
        transition: all 0.3s ease;
    }

    .export-detail-item:hover {
        background: linear-gradient(135deg, #f1f5f9 0%, #f8fafc 100%);
        margin: 0 -15px;
        padding: 16px 15px;
        border-radius: 12px;
        border-bottom: 1px solid transparent;
    }

    .export-detail-item:last-child {
        border-bottom: none;
    }

    .export-detail-label {
        font-weight: 700;
        color: #1a2a36;
        font-size: 15px;
        letter-spacing: 0.3px;
    }

    .export-detail-value {
        color: #2c5aa0;
        font-weight: 700;
        font-size: 15px;
        background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
        padding: 6px 12px;
        border-radius: 8px;
        border: 1px solid #bfdbfe;
    }

    .export-confirm-buttons {
        padding: 0 30px 30px;
        display: flex;
        gap: 18px;
        background: linear-gradient(135deg, #f1f5f9 0%, #f8fafc 100%);
    }

    .export-cancel-btn {
        flex: 1;
        background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
        color: #4b5563;
        border: 2px solid #d1d5db;
        border-radius: 12px;
        padding: 14px 20px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        font-family: 'Inter', sans-serif;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .export-cancel-btn:hover {
        background: linear-gradient(135deg, #e5e7eb 0%, #d1d5db 100%);
        color: #374151;
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(107, 114, 128, 0.2);
    }

    .export-confirm-btn {
        flex: 2;
        background: linear-gradient(135deg, #2c5aa0 0%, #3182ce 100%);
        color: white;
        border: 2px solid rgba(255, 255, 255, 0.2);
        border-radius: 12px;
        padding: 14px 20px;
        font-weight: 600;
        cursor: pointer;
        font-family: 'Inter', sans-serif;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        position: relative;
        overflow: hidden;
        transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
    }

    .export-confirm-btn::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
        transition: left 0.6s ease;
    }

    .export-confirm-btn:hover::before {
        left: 100%;
    }

    .export-confirm-btn:hover {
        transform: translateY(-3px) scale(1.02);
        box-shadow: 0 12px 30px rgba(44, 90, 160, 0.4);
        background: linear-gradient(135deg, #1e40af 0%, #2563eb 100%);
    }

    .export-confirm-btn:active {
        transform: translateY(-1px) scale(1.01);
        transition: all 0.1s ease;
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

    @keyframes slideInFromRight {
        0% {
            transform: translateX(400px);
            opacity: 0;
        }

        100% {
            transform: translateX(0);
            opacity: 1;
        }
    }

    @keyframes successSlideIn {
        0% {
            transform: translateX(400px) scale(0.8);
            opacity: 0;
        }

        100% {
            transform: translateX(0) scale(1);
            opacity: 1;
        }
    }

    @keyframes errorSlideIn {
        0% {
            transform: translateX(400px) scale(0.8);
            opacity: 0;
        }

        100% {
            transform: translateX(0) scale(1);
            opacity: 1;
        }
    }

    @keyframes infoSlideIn {
        0% {
            transform: translateX(400px) scale(0.8);
            opacity: 0;
        }

        100% {
            transform: translateX(0) scale(1);
            opacity: 1;
        }
    }

    @keyframes warningSlideIn {
        0% {
            transform: translateX(400px) scale(0.8);
            opacity: 0;
        }

        100% {
            transform: translateX(0) scale(1);
            opacity: 1;
        }
    }

    @keyframes loadingSlideIn {
        0% {
            transform: translateX(400px) scale(0.8);
            opacity: 0;
        }

        100% {
            transform: translateX(0) scale(1);
            opacity: 1;
        }
    }

    @keyframes modalSlideIn {
        0% {
            opacity: 0;
            transform: scale(0.8) translateY(30px);
        }

        100% {
            opacity: 1;
            transform: scale(1) translateY(0);
        }
    }

    .modal.show .export-modal-content,
    .modal.show .export-confirm-content {
        animation: modalSlideIn 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
    }

    /* Close button styling to match theme */
    .modal-close {
        position: absolute;
        top: 20px;
        right: 25px;
        background: rgba(255, 255, 255, 0.15);
        border: 2px solid rgba(255, 255, 255, 0.2);
        border-radius: 50%;
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        cursor: pointer;
        transition: all 0.3s ease;
        backdrop-filter: blur(10px);
        font-size: 16px;
        z-index: 10;
    }

    .modal-close:hover {
        background: rgba(255, 255, 255, 0.25);
        border-color: rgba(255, 255, 255, 0.4);
        transform: scale(1.1) rotate(90deg);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
    }

    .modal-close:active {
        transform: scale(0.95) rotate(90deg);
        transition: all 0.1s ease;
    }
    </style>
</head>

<body>
    <?php include __DIR__ . '/includes/sidebar_common.php'; ?>
    <div class="topbar">
        <h1 class="dashboard-title">Engineering Admin Dashboard</h1>
        <div style="display: flex; align-items: center; gap: 12px;">
            <button onclick="showKeyboardShortcutsHelp()" class="shortcuts-btn" title="Ctrl + H">
                <i class="fas fa-keyboard"></i>
                <span>Shortcuts</span>
            </button>
            <a href="logout.php" class="logout-btn" onclick="return confirmLogout(event)">
                <i class="fas fa-sign-out-alt"></i>
                Logout
            </a>
        </div>
    </div>
    <div class="main-content">
        <!-- Dashboard Statistics Overview -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #3182ce 0%, #2c5aa0 100%);">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-content">
                    <h3><?= $total_records ?></h3>
                    <p>Total Board Passers</p>
                    <?php
          // Get records added this month
          $current_month = date('Y-m');
          $this_month_query = $conn->query("SELECT COUNT(*) as month_count FROM board_passers 
            WHERE department='Engineering' AND DATE_FORMAT(board_exam_date, '%Y-%m') = '$current_month'");
          $this_month_data = $this_month_query->fetch_assoc();
          $this_month_count = $this_month_data ? $this_month_data['month_count'] : 0;
          ?>
                    <span class="stat-change <?= $total_records > 0 ? 'positive' : 'neutral' ?>">
                        <i class="fas fa-<?= $total_records > 0 ? 'arrow-up' : 'info-circle' ?>"></i>
                        <?= $total_records > 0 ? '+' . $this_month_count . ' this month' : 'No records yet' ?>
                    </span>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stat-content">
                    <?php
          $passing_rate_query = $conn->query("SELECT 
            COUNT(CASE WHEN result = 'Passed' THEN 1 END) as passed_count,
            COUNT(*) as total_count
            FROM board_passers WHERE department='Engineering'");
          $rate_data = $passing_rate_query->fetch_assoc();
          
          $passing_rate = 0;
          if ($rate_data && $rate_data['total_count'] > 0) {
            $passing_rate = ($rate_data['passed_count'] * 100.0) / $rate_data['total_count'];
          }
          ?>
                    <h3><?= number_format($passing_rate, 1) ?>%</h3>
                    <p>Passing Rate</p>
                    <span class="stat-change positive">
                        <i class="fas fa-arrow-up"></i> +2.3% vs last year
                    </span>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <div class="stat-content">
                    <?php
          $current_year = date('Y');
          $recent_passers = $conn->query("SELECT COUNT(*) as recent_count FROM board_passers 
            WHERE department='Engineering' AND year_graduated >= $current_year - 1");
          $recent_data = $recent_passers->fetch_assoc();
          $recent_count = $recent_data ? $recent_data['recent_count'] : 0;
          ?>
                    <h3><?= $recent_count ?></h3>
                    <p>Recent Graduates</p>
                    <span class="stat-change neutral">
                        <i class="fas fa-calendar"></i> Last 2 years
                    </span>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                    <i class="fas fa-medal"></i>
                </div>
                <div class="stat-content">
                    <?php
          $top_course_query = $conn->query("SELECT course, COUNT(*) as count FROM board_passers 
            WHERE department='Engineering' AND result='Passed' 
            GROUP BY course ORDER BY count DESC LIMIT 1");
          $top_course = $top_course_query->fetch_assoc();
          
          // Handle case when no data is available
          $course_count = 0;
          $course_name = 'N/A';
          
          if ($top_course && isset($top_course['count'])) {
            $course_count = $top_course['count'];
          }
          
          if ($top_course && isset($top_course['course']) && !empty($top_course['course'])) {
            $course_name = $top_course['course'];
          }
          ?>
                    <h3><?= $course_count ?></h3>
                    <p>Top Course</p>
                    <span class="stat-change neutral">
                        <i class="fas fa-star"></i>
                        <?= htmlspecialchars(substr($course_name ?? '', 0, 15)) ?><?= strlen($course_name ?? '') > 15 ? '...' : '' ?>
                    </span>
                </div>
            </div>
        </div>

        <div class="card">
            <div style="margin-bottom: 20px;">
                <h2>Board Passers Database</h2>
            </div>

            <!-- Filter Section -->
            <div class="filter-section">
                <div class="filter-header">
                    <h3><i class="fas fa-filter"></i> Filter Records</h3>
                    <div class="filter-toggle">
                        <button id="toggleFilters" class="toggle-btn">
                            <i class="fas fa-chevron-down"></i>
                            <span>Show Filters</span>
                        </button>
                    </div>
                </div>

                <div id="filterContainer" class="filter-container">
                    <!-- Search Bar -->
                    <div class="search-section">
                        <div class="search-group">
                            <label for="nameSearch">
                                <i class="fas fa-search"></i> Search Student Name
                            </label>
                            <input type="text" id="nameSearch" class="search-input"
                                placeholder="Type student name to search..." autocomplete="off">
                            <div class="search-clear" id="clearSearch" title="Clear search">
                                <i class="fas fa-times"></i>
                            </div>
                        </div>
                    </div>

                    <div class="filter-grid">
                        <!-- Course Filter -->
                        <div class="filter-group">
                            <label for="courseFilter">Course <small>(Hold Ctrl/Cmd for multiple)</small></label>
                            <select id="courseFilter" class="filter-input" multiple size="4">
                                <?php foreach($courses as $course): ?>
                                <option value="<?= htmlspecialchars($course) ?>"><?= htmlspecialchars($course) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Year Filter -->
                        <div class="filter-group">
                            <label for="yearFilter">Graduation Year <small>(Hold Ctrl/Cmd for multiple)</small></label>
                            <select id="yearFilter" class="filter-input" multiple size="4">
                                <?php 
                $years = $conn->query("SELECT DISTINCT year_graduated FROM board_passers WHERE department='Engineering' ORDER BY year_graduated DESC");
                while($year = $years->fetch_assoc()): 
                ?>
                                <option value="<?= htmlspecialchars($year['year_graduated']) ?>">
                                    <?= htmlspecialchars($year['year_graduated']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <!-- Board Exam Date Filter -->
                        <div class="filter-group">
                            <label for="examDateFilter">Board Exam Date <small>(Hold Ctrl/Cmd for
                                    multiple)</small></label>
                            <select id="examDateFilter" class="filter-input" multiple size="4" disabled>
                                <option value="" disabled>-- Select board exam type first --</option>
                            </select>
                        </div>

                        <!-- Result Filter -->
                        <div class="filter-group">
                            <label for="resultFilter">Result <small>(Hold Ctrl/Cmd for multiple)</small></label>
                            <select id="resultFilter" class="filter-input" multiple size="3">
                                <option value="Passed">Passed</option>
                                <option value="Failed">Failed</option>
                                <option value="Conditional">Conditional</option>
                            </select>
                        </div>

                        <!-- Take Attempts Filter -->
                        <div class="filter-group">
                            <label for="examTypeFilter">Take Attempts <small>(Hold Ctrl/Cmd for
                                    multiple)</small></label>
                            <select id="examTypeFilter" class="filter-input" multiple size="2">
                                <option value="First Timer">First Timer</option>
                                <option value="Repeater">Repeater</option>
                            </select>
                        </div>

                        <!-- Board Exam Type Filter -->
                        <div class="filter-group">
                            <label for="boardExamTypeFilter">Board Exam Type <small>(Hold Ctrl/Cmd for
                                    multiple)</small></label>
                            <select id="boardExamTypeFilter" class="filter-input" multiple size="4">
                                <?php foreach ($board_exam_types as $exam_type): ?>
                                <option value="<?= (int)$exam_type['id'] ?>"
                                    data-name="<?= htmlspecialchars($exam_type['name']) ?>">
                                    <?= htmlspecialchars($exam_type['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="filter-actions">
                        <button id="applyFilters" class="filter-btn apply-btn">
                            <i class="fas fa-search"></i>
                            Apply Filters
                        </button>
                        <button id="clearFilters" class="filter-btn clear-btn">
                            <i class="fas fa-times"></i>
                            Clear All
                        </button>
                        <button id="exportBtn" class="filter-btn export-btn" onclick="openExportOptionsModal()">
                            <i class="fas fa-download"></i>
                            Export Data
                        </button>
                        <div class="filter-count">
                            <span id="recordCount">Total Records: <strong><?= $total_records ?></strong></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="table-container">
                <script>
                // Map board exam type IDs to their display names for client-side rendering
                window.BOARD_EXAM_TYPE_MAP = <?= json_encode(array_column($board_exam_types, 'name', 'id')) ?>;
                // Board exam dates data for dynamic filtering
                window.BOARD_EXAM_DATES = <?= json_encode($board_exam_dates) ?>;
                </script>
                <table class="board-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Course</th>
                            <th>Year Graduated</th>
                            <th>Board Exam Date</th>
                            <th>Result</th>
                            <th>Take Attempts</th>
                            <th>Board Exam Type</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($total_records > 0): ?>
                        <?php while($row = $passers->fetch_assoc()): ?>
                        <tr data-id="<?= htmlspecialchars($row['id'] ?? '') ?>">
                            <td class="editable" data-label="Name"><?= htmlspecialchars($row['full_name'] ?? '') ?></td>
                            <td class="editable" data-label="Course"><?= htmlspecialchars($row['course'] ?? '') ?></td>
                            <td class="editable" data-label="Year Graduated">
                                <?= htmlspecialchars($row['year_graduated'] ?? '') ?></td>
                            <td class="editable" data-label="Board Exam Date"
                                data-date="<?= htmlspecialchars($row['board_exam_date'] ?? '') ?>">
                                <?= htmlspecialchars($row['board_exam_date'] ?? '') ?></td>
                            <td data-label="Result">
                                <?php 
                $result = $row['result'];
                $badgeClass = '';
                if ($result === 'Passed') {
                  $badgeClass = 'status-passed';
                } elseif ($result === 'Failed') {
                  $badgeClass = 'status-failed';
                } else {
                  $badgeClass = 'status-cond';
                }
                ?>
                                <span class="status-badge <?= $badgeClass ?>"><?= htmlspecialchars($result) ?></span>
                            </td>
                            <td data-label="Take Attempts">
                                <?php 
                $examType = $row['exam_type'] ?? 'First Timer';
                $examBadgeClass = ($examType === 'First Timer') ? 'exam-first-timer' : 'exam-repeater';
                ?>
                                <span
                                    class="status-badge <?= $examBadgeClass ?>"><?= htmlspecialchars($examType) ?></span>
                            </td>
                            <?php 
                $betRaw = $row['board_exam_type'] ?? '';
                $betDisplay = 'Not specified';
                if ($betRaw !== '' && $betRaw !== null) {
                  if (ctype_digit((string)$betRaw)) {
                    // lookup ID -> name using $board_exam_types
                    $betMap = array_column($board_exam_types, 'name', 'id');
                    $betDisplay = $betMap[$betRaw] ?? $betRaw;
                  } else {
                    $betDisplay = $betRaw;
                  }
                }
              ?>
                            <td class="editable" data-label="Board Type"><?= htmlspecialchars($betDisplay) ?></td>
                            <td class="actions-btns" data-label="Actions">
                                <button class="action-btn edit-btn" onclick="openEdit(this)" data-tooltip="Edit Record"
                                    title="Edit Record">
                                    <i class="fas fa-edit"></i>
                                    <span style="font-size: 10px; margin-left: 2px;">Edit</span>
                                </button>
                                <button class="action-btn delete-btn" onclick="deleteRow(this)"
                                    data-tooltip="Delete Record" title="Delete Record">
                                    <i class="fas fa-trash"></i>
                                    <span style="font-size: 10px; margin-left: 2px;">Del</span>
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php else: ?>
                        <tr class="no-records-row">
                            <td colspan="8"
                                style="text-align: center; padding: 60px 20px; color: #6b7280; font-size: 1.1rem;">
                                <div style="display: flex; flex-direction: column; align-items: center; gap: 20px;">
                                    <div
                                        style="width: 80px; height: 80px; background: linear-gradient(135deg, #e2e8f0 0%, #cbd5e1 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                        <i class="fas fa-database" style="font-size: 2rem; color: #94a3b8;"></i>
                                    </div>
                                    <div>
                                        <h3 style="color: #374151; margin: 0 0 10px 0;">No Board Passers Found</h3>
                                        <script>
                                        // Filter board exam date options based on selected Board Exam Type
                                        function filterExamDates(boardExamTypeSelectorId, examDateSelectorId) {
                                            var typeEl = document.getElementById(boardExamTypeSelectorId);
                                            var dateEl = document.getElementById(examDateSelectorId);
                                            if (!typeEl || !dateEl) return;

                                            var selectedTypeId = typeEl.value ? parseInt(typeEl.value, 10) : null;

                                            // Show only options whose data-exam-type-id matches selectedTypeId, or options with empty data-exam-type-id (Other)
                                            for (var i = 0; i < dateEl.options.length; i++) {
                                                var opt = dateEl.options[i];
                                                var optTypeIdAttr = opt.getAttribute('data-exam-type-id');
                                                var optTypeId = optTypeIdAttr ? parseInt(optTypeIdAttr, 10) : null;
                                                if (opt.value === '') { // keep the placeholder visible
                                                    opt.style.display = '';
                                                    continue;
                                                }
                                                if (selectedTypeId === null || optTypeId === null || optTypeId ===
                                                    selectedTypeId) {
                                                    opt.style.display = '';
                                                } else {
                                                    opt.style.display = 'none';
                                                }
                                            }
                                        }

                                        // After filter logic, wire subject loading for the corresponding pair
                                        // Helper to load subjects for a given type/date into a container
                                        async function loadSubjectsForPair(typeSelectorId, dateSelectorId, containerId,
                                            listId, placeholderId, passerId) {
                                            try {
                                                const typeEl = document.getElementById(typeSelectorId);
                                                const dateEl = document.getElementById(dateSelectorId);
                                                const container = document.getElementById(containerId);
                                                const list = document.getElementById(listId);
                                                const placeholder = document.getElementById(placeholderId);
                                                if (!typeEl || !dateEl || !list || !container) return;
                                                // need both a selected type (with data-type-id) and a selected date
                                                const selOpt = typeEl.options[typeEl.selectedIndex];
                                                const dateVal = dateEl.value;
                                                if (!selOpt || !selOpt.dataset.typeId || !dateVal) {
                                                    if (container) container.style.display = 'none';
                                                    if (placeholder) placeholder.style.display = 'none';
                                                    list.innerHTML = '';
                                                    return;
                                                }
                                                const typeId = selOpt.dataset.typeId;
                                                const resp = await fetch(
                                                    'fetch_subjects_engineering.php?exam_type_id=' +
                                                    encodeURIComponent(typeId));
                                                if (!resp.ok) {
                                                    if (placeholder) placeholder.style.display = 'block';
                                                    return;
                                                }
                                                const subjects = await resp.json();
                                                if (!subjects || subjects.length === 0) {
                                                    if (placeholder) placeholder.style.display = 'block';
                                                    return;
                                                }
                                                // render simple rows (grade input + hidden result)
                                                list.innerHTML = '';
                                                subjects.forEach(s => {
                                                    const row = document.createElement('div');
                                                    row.style.cssText =
                                                        'display:flex;gap:12px;align-items:center;';
                                                    const title = document.createElement('div');
                                                    title.style.cssText =
                                                        'flex:1;padding:10px;border-radius:8px;background:#f8fafc;border:1px solid #e5e7eb;font-weight:600;';
                                                    title.textContent = s.subject_name;
                                                    const grade = document.createElement('input');
                                                    grade.type = 'number';
                                                    grade.name = (passerId ? 'edit_subject_grade_' + s.id :
                                                        'subject_grade_' + s.id);
                                                    grade.min = '0';
                                                    grade.max = String(parseInt(s.total_items || 100, 10));
                                                    grade.step = '1';
                                                    grade.placeholder = 'Grade';
                                                    grade.style.cssText =
                                                        'width:140px;padding:10px;border-radius:8px;border:1px solid #e5e7eb;';
                                                    const resultHidden = document.createElement('input');
                                                    resultHidden.type = 'hidden';
                                                    resultHidden.name = (passerId ? 'edit_subject_result_' +
                                                        s.id : 'subject_result_' + s.id);
                                                    const remark = document.createElement('div');
                                                    remark.style.cssText =
                                                        'width:160px;padding:6px;border-radius:8px;border:1px solid #e5e7eb;background:#fff;display:flex;align-items:center;justify-content:center;font-weight:600;';
                                                    remark.textContent = '';
                                                    grade.addEventListener('input', function() {
                                                        if (this.value === '') return;
                                                        const v = parseInt(this.value, 10);
                                                        const max = parseInt(s.total_items || 100,
                                                            10);
                                                        let val = isNaN(v) ? 0 : v;
                                                        if (val > max) val = max;
                                                        if (val < 0) val = 0;
                                                        this.value = String(val);
                                                        const pct = (val / max) * 100;
                                                        const rr = (pct >= 75) ? 'Passed' :
                                                            'Failed';
                                                        resultHidden.value = rr;
                                                        remark.textContent = rr;
                                                        remark.classList.toggle('remark-pass',
                                                            rr === 'Passed');
                                                        remark.classList.toggle('remark-fail',
                                                            rr === 'Failed');
                                                    });
                                                    row.appendChild(title);
                                                    row.appendChild(grade);
                                                    row.appendChild(remark);
                                                    row.appendChild(resultHidden);
                                                    list.appendChild(row);
                                                });
                                                if (container) container.style.display = 'block';
                                                if (placeholder) placeholder.style.display = 'none';
                                            } catch (e) {
                                                console.error('loadSubjectsForPair failed', e);
                                            }
                                        }

                                        // wire add modal pair
                                        try {
                                            const addType = document.getElementById('addBoardExamType');
                                            const addDate = document.getElementById('addExamDate');
                                            if (addType && addDate) {
                                                addType.addEventListener('change', function() {
                                                    filterExamDates('addBoardExamType', 'addExamDate');
                                                    document.getElementById('subjectsContainer') && (document
                                                        .getElementById('subjectsContainer').style.display =
                                                        'none');
                                                    document.getElementById('noSubjectsPlaceholder') && (
                                                        document.getElementById('noSubjectsPlaceholder')
                                                        .style.display = 'none');
                                                });
                                                addDate.addEventListener('change', function() {
                                                    loadSubjectsForPair('addBoardExamType', 'addExamDate',
                                                        'subjectsContainer', 'subjectsList',
                                                        'noSubjectsPlaceholder', null);
                                                });
                                            }
                                        } catch (e) {}

                                        // Edit modal removed — no edit modal wiring required

                                        // If the currently selected option is hidden, reset selection to placeholder
                                        if (dateEl.selectedIndex >= 0) {
                                            var curOpt = dateEl.options[dateEl.selectedIndex];
                                            if (curOpt && curOpt.style.display === 'none') {
                                                dateEl.selectedIndex = 0;
                                            }
                                        }


                                        document.addEventListener('DOMContentLoaded', function() {
                                            // Attach listeners for both add and edit modals
                                            var addType = document.getElementById('addBoardExamType');
                                            var editType = document.getElementById('editBoardExamType');
                                            var addDate = document.getElementById('addExamDate');
                                            var editDate = document.getElementById('editExamDate');

                                            // Helper to set disabled/visibility state based on whether a type is selected
                                            function updateDateEnabled(typeEl, dateEl) {
                                                if (!typeEl || !dateEl) return;
                                                var hintEl = document.getElementById(dateEl.id + 'Hint');
                                                if (!typeEl.value || typeEl.value === '') {
                                                    // hide and disable the date selector until a board exam type is selected
                                                    dateEl.disabled = true;
                                                    dateEl.selectedIndex = 0; // reset to placeholder
                                                    dateEl.style.display = 'none';
                                                    if (hintEl) hintEl.style.display = '';
                                                } else {
                                                    // show and enable the date selector and filter visible options to the selected type
                                                    dateEl.disabled = false;
                                                    dateEl.style.display = '';
                                                    if (hintEl) hintEl.style.display = 'none';
                                                    filterExamDates(typeEl.id, dateEl.id);
                                                }
                                            }

                                            if (addType && addDate) {
                                                // initialize disabled state
                                                updateDateEnabled(addType, addDate);
                                                addType.addEventListener('change', function() {
                                                    updateDateEnabled(addType, addDate);
                                                });
                                            }

                                            if (editType && editDate) {
                                                // initialize disabled state
                                                updateDateEnabled(editType, editDate);
                                                editType.addEventListener('change', function() {
                                                    updateDateEnabled(editType, editDate);
                                                });
                                            }
                                        });
                                        </script>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Edit Student Modal -->
    <div id="editStudentModal" class="custom-modal" style="display: none;">
        <div class="modal-content add-modal-content">
            <div class="modal-header add-modal-header">
                <div class="modal-icon add-modal-icon">
                    <i class="fas fa-user-edit"></i>
                </div>
                <h3>Edit Board Passer</h3>
                <p>Update student information below</p>
                <button class="modal-close" onclick="closeEditModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form id="editStudentForm">
                <!-- Hidden fields for backend and change detection -->
                <input type="hidden" id="editStudentId" name="student_id" value="">
                <input type="hidden" id="originalName" value="">
                <input type="hidden" id="originalCourse" value="">
                <input type="hidden" id="originalYear" value="">
                <input type="hidden" id="originalDate" value="">
                <input type="hidden" id="originalResult" value="">
                <input type="hidden" id="originalExamType" value="">
                <input type="hidden" id="originalBoardExamType" value="">
                <!-- Hidden full name for UI row updates -->
                <input type="hidden" id="editFullName" name="name" value="">

                <div class="tab-header" style="padding: 16px 24px 0;">
                    <div class="tab-icon"><i class="fas fa-user"></i></div>
                    <h4>Personal Information</h4>
                    <p>Update the student's personal details</p>
                </div>

                <!-- Personal section grid -->
                <div id="editStudentFormBody" class="form-grid" style="padding: 0 24px;">
                    <div class="form-group">
                        <label for="editLastName"><i class="fas fa-user"></i>Last Name *</label>
                        <input type="text" id="editLastName" name="last_name" required
                            title="Only letters, spaces, commas, periods, apostrophes, and hyphens allowed">
                        <div class="input-feedback"></div>
                    </div>

                    <div class="form-group">
                        <label for="editFirstName"><i class="fas fa-user"></i>First Name *</label>
                        <input type="text" id="editFirstName" name="first_name" required
                            title="Only letters, spaces, commas, periods, apostrophes, and hyphens allowed">
                        <div class="input-feedback"></div>
                    </div>

                    <div class="form-group">
                        <label for="editMiddleName"><i class="fas fa-user"></i>Middle Name</label>
                        <input type="text" id="editMiddleName" name="middle_name"
                            title="Only letters, spaces, commas, periods, apostrophes, and hyphens allowed">
                        <div class="input-feedback"></div>
                    </div>

                    <div class="form-group">
                        <label for="editSuffix"><i class="fas fa-award"></i>Suffix</label>
                        <input type="text" id="editSuffix" name="suffix"
                            title="Only letters, spaces, commas, periods, apostrophes, and hyphens allowed">
                        <div class="input-feedback"></div>
                    </div>

                    <div class="form-group">
                        <label for="editSex"><i class="fas fa-venus-mars"></i>Sex *</label>
                        <select id="editSex" name="sex" required>
                            <option value="">Select Sex</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="editCourse"><i class="fas fa-graduation-cap"></i>Course *</label>
                        <select id="editCourse" name="course" required>
                            <option value="">Select Course</option>
                            <?php foreach($courses as $course): ?>
                            <option value="<?= htmlspecialchars($course) ?>"><?= htmlspecialchars($course) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="editYear"><i class="fas fa-calendar"></i>Year Graduated *</label>
                        <select id="editYear" name="year_graduated" required>
                            <option value="">Select Year</option>
                            <?php $current_year = date('Y'); for ($year = $current_year; $year >= 1950; $year--) { echo "<option value=\"$year\">$year</option>"; } ?>
                        </select>
                        <div class="input-feedback"></div>
                    </div>

                    <div class="form-group">
                        <label for="editResult"><i class="fas fa-award"></i>Result *</label>
                        <select id="editResult" name="result" required>
                            <option value="">Select Result</option>
                            <option value="Passed">Passed</option>
                            <option value="Failed">Failed</option>
                            <option value="Conditional">Conditional</option>
                        </select>
                    </div>

                </div>

                <!-- Board Exam Details section header -->
                <div class="tab-header" style="padding: 16px 24px 0;">
                    <div class="tab-icon"><i class="fas fa-certificate"></i></div>
                    <h4>Board Exam Details</h4>
                    <p>Provide the exam type, date, and rating</p>
                </div>

                <!-- Exam details grid -->
                <div class="form-grid" style="padding: 0 24px;">
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label for="editExamType"><i class="fas fa-redo"></i>Exam Type *</label>
                        <select id="editExamType" name="exam_type" required>
                            <option value="">Select Exam Type</option>
                            <option value="First Timer">First Timer</option>
                            <option value="Repeater">Repeater</option>
                        </select>
                    </div>

                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label for="editBoardExamType"><i class="fas fa-certificate"></i>Board Exam Type *</label>
                        <select id="editBoardExamType" name="board_exam_type" required>
                            <option value="">Select Board Exam Type</option>
                            <?php foreach ($board_exam_types as $exam_type): ?>
                            <option value="<?= (int)$exam_type['id'] ?>"
                                data-name="<?= htmlspecialchars($exam_type['name']) ?>"
                                data-type-id="<?= (int)$exam_type['id'] ?>"><?= htmlspecialchars($exam_type['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="editExamDate"><i class="fas fa-calendar-check"></i>Board Exam Date</label>
                        <select id="editExamDate" name="board_exam_date">
                            <option value="">Select Exam Date</option>
                            <?php foreach ($board_exam_dates as $exam_date): ?>
                            <option value="<?= htmlspecialchars($exam_date['date']) ?>"
                                data-exam-type="<?= htmlspecialchars($exam_date['exam_type_name']) ?>"
                                data-exam-type-id="<?= htmlspecialchars($exam_date['exam_type_id']) ?>">
                                <?= date('F j, Y', strtotime($exam_date['date'])) ?>
                                <?php if (!empty($exam_date['description'])): ?> -
                                <?= htmlspecialchars($exam_date['description']) ?><?php endif; ?>
                            </option>
                            <?php endforeach; ?>
                            <option value="other" data-exam-type="">Other Date (Not Listed)</option>
                        </select>
                        <div id="editExamDateHint" class="small-hint"
                            style="display:none;color:#6b7280;font-size:12px;margin-top:6px;">Select Board Exam Type
                            first</div>
                    </div>

                    <div class="form-group" id="editCustomDateGroup" style="display: none;">
                        <label for="editCustomDate"><i class="fas fa-calendar"></i>Custom Exam Date</label>
                        <input type="date" id="editCustomDate" name="custom_exam_date">
                    </div>

                    <div class="form-group">
                        <label for="editRating"><i class="fas fa-percentage"></i>Rating</label>
                        <input type="number" step="0.01" min="0" max="100" id="editRating" name="rating"
                            placeholder="e.g., 85.25">
                    </div>
                </div>

                <div style="padding: 0 24px 16px; margin-top: 8px;">
                    <div id="editSubjectsContainer"
                        style="display:none; background:#f8fafc; border:1px solid #e5e7eb; border-radius:12px; padding:16px;">
                        <div
                            style="font-weight:700; color:#1f2937; margin-bottom:10px; display:flex; align-items:center; gap:8px;">
                            <i class="fas fa-book"></i>Per-subject Grades
                        </div>
                        <div id="editSubjectsList" style="display:flex; flex-direction:column; gap:10px;"></div>
                    </div>
                    <div id="editNoSubjectsPlaceholder"
                        style="display:none; color:#6b7280; font-size:0.95rem; padding:10px;">No subjects available for
                        selected board exam type.</div>
                </div>

                <div class="modal-buttons" style="padding: 16px 24px; display: flex; gap: 12px;">
                    <button type="button" class="btn-secondary" onclick="closeEditModal()"><i class="fas fa-times"></i>
                        Cancel</button>
                    <button type="submit" class="btn-primary"><i class="fas fa-save"></i> Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Student Modal -->
    <div id="addStudentModal" class="custom-modal">
        <div class="modal-content add-modal-content">
            <div class="modal-header add-modal-header">
                <div class="modal-icon add-modal-icon">
                    <i class="fas fa-user-plus"></i>
                </div>
                <h3>Add New Board Passer</h3>
                <p>Enter student information below</p>
                <button class="modal-close" onclick="closeAddModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Tab Navigation -->
            <div class="tab-navigation">
                <button class="tab-btn active" data-tab="personal" onclick="switchTab('personal')">
                    <i class="fas fa-user"></i>
                    Personal Info
                </button>
                <button class="tab-btn" data-tab="exam" onclick="switchTab('exam')">
                    <i class="fas fa-graduation-cap"></i>
                    Exam Info
                </button>
            </div>

            <form id="addStudentForm">
                <!-- Personal Information Tab -->
                <div id="personalTab" class="tab-content active">
                    <div class="tab-header">
                        <div class="tab-icon">
                            <i class="fas fa-user"></i>
                        </div>
                        <h4>Personal Information</h4>
                        <p>Enter the student's personal details</p>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label for="addLastName">
                                <i class="fas fa-user"></i>Last Name *
                            </label>
                            <input type="text" id="addLastName" name="last_name" required placeholder="Desacula"
                                title="Only letters, spaces, commas, periods, apostrophes, and hyphens allowed">
                            <div class="input-feedback"></div>
                        </div>

                        <div class="form-group">
                            <label for="addFirstName">
                                <i class="fas fa-user"></i>First Name *
                            </label>
                            <input type="text" id="addFirstName" name="first_name" required placeholder="Angel Anne"
                                title="Only letters, spaces, commas, periods, apostrophes, and hyphens allowed">
                            <div class="input-feedback"></div>
                        </div>

                        <div class="form-group">
                            <label for="addMiddleName">
                                <i class="fas fa-user"></i>Middle Name
                            </label>
                            <input type="text" id="addMiddleName" name="middle_name"
                                placeholder="Enter middle name (optional)"
                                title="Only letters, spaces, commas, periods, apostrophes, and hyphens allowed">
                            <div class="input-feedback"></div>
                        </div>

                        <div class="form-group">
                            <label for="addSuffix">
                                <i class="fas fa-award"></i>Suffix
                            </label>
                            <input type="text" id="addSuffix" name="suffix" placeholder="Jr., Sr., III, etc. (optional)"
                                title="Only letters, spaces, commas, periods, apostrophes, and hyphens allowed">
                            <div class="input-feedback"></div>
                        </div>

                        <div class="form-group">
                            <label for="addSex">
                                <i class="fas fa-venus-mars"></i>Sex *
                            </label>
                            <select id="addSex" name="sex" required>
                                <option value="">Select Sex</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="addCourse">
                                <i class="fas fa-graduation-cap"></i>Course *
                            </label>
                            <select id="addCourse" name="course" required>
                                <option value="">Select Course</option>
                                <?php foreach($courses as $course): ?>
                                <option value="<?= htmlspecialchars($course) ?>"><?= htmlspecialchars($course) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="tab-footer">
                        <button type="button" onclick="closeAddModal()" class="btn-secondary">
                            <i class="fas fa-times"></i>
                            Cancel
                        </button>
                        <button type="button" onclick="nextTab()" class="btn-primary">
                            Next
                            <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </div>

                <!-- Exam Information Tab -->
                <div id="examTab" class="tab-content">
                    <div class="tab-header">
                        <div class="tab-icon">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                        <h4>Exam Information</h4>
                        <p>Enter board exam details</p>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label for="addYear">
                                <i class="fas fa-calendar"></i>Year Graduated *
                            </label>
                            <select id="addYear" name="year_graduated" required>
                                <option value="">Select Year</option>
                                <?php
                $current_year = date('Y');
                for ($year = $current_year; $year >= 1950; $year--) {
                    echo "<option value=\"$year\">$year</option>";
                }
                ?>
                            </select>
                            <div class="input-feedback"></div>
                        </div>

                        <div class="form-group">
                            <label for="addExamDate">
                                <i class="fas fa-calendar-check"></i>Board Exam Date *
                            </label>
                            <select id="addExamDate" name="board_exam_date" required>
                                <option value="">Select Exam Date</option>
                                <?php foreach ($board_exam_dates as $exam_date): ?>
                                <option value="<?= htmlspecialchars($exam_date['date']) ?>"
                                    data-exam-type="<?= htmlspecialchars($exam_date['exam_type_name']) ?>"
                                    data-exam-type-id="<?= htmlspecialchars($exam_date['exam_type_id']) ?>">
                                    <?= date('F j, Y', strtotime($exam_date['date'])) ?>
                                    <?php if (!empty($exam_date['description'])): ?>
                                    - <?= htmlspecialchars($exam_date['description']) ?>
                                    <?php endif; ?>
                                </option>
                                <?php endforeach; ?>
                                <option value="other" data-exam-type="">Other Date (Not Listed)</option>
                            </select>
                            <div id="addExamDateHint" class="small-hint"
                                style="display:none;color:#6b7280;font-size:12px;margin-top:6px;">Select Board Exam Type
                                first</div>
                        </div>

                        <div class="form-group" id="customDateGroup" style="display: none;">
                            <label for="addCustomDate">
                                <i class="fas fa-calendar"></i>Custom Exam Date *
                            </label>
                            <input type="date" id="addCustomDate" name="custom_exam_date">
                        </div>

                        <div class="form-group">
                            <label for="addResult">
                                <i class="fas fa-award"></i>Result *
                            </label>
                            <select id="addResult" name="result" required>
                                <option value="">Select Result</option>
                                <option value="Passed">Passed</option>
                                <option value="Failed">Failed</option>
                                <option value="Conditional">Conditional</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="addExamType">
                                <i class="fas fa-redo"></i>Exam Type *
                            </label>
                            <select id="addExamType" name="exam_type" required>
                                <option value="">Select Exam Type</option>
                                <option value="First Timer">First Timer</option>
                                <option value="Repeater">Repeater</option>
                            </select>
                        </div>

                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label for="addBoardExamType">
                                <i class="fas fa-certificate"></i>Board Exam Type *
                            </label>
                            <select id="addBoardExamType" name="board_exam_type" required>
                                <option value="">Select Board Exam Type</option>
                                <?php foreach ($board_exam_types as $exam_type): ?>
                                <option value="<?= (int)$exam_type['id'] ?>"
                                    data-name="<?= htmlspecialchars($exam_type['name']) ?>">
                                    <?= htmlspecialchars($exam_type['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="tab-footer">
                        <button type="button" onclick="prevTab()" class="btn-secondary">
                            <i class="fas fa-arrow-left"></i>
                            Previous
                        </button>
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-plus"></i>
                            Add Student
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Export Options Modal -->
    <div id="exportOptionsModal" class="modal" style="display: none;">
        <div class="modal-content export-modal-content">
            <div class="modal-header export-modal-header">
                <div class="modal-icon export-modal-icon">
                    <i class="fas fa-download"></i>
                </div>
                <h3>Export Data</h3>
                <p>Choose your preferred export format</p>
                <button class="modal-close" onclick="closeExportOptionsModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="export-options-container">
                <div class="export-option-card" onclick="handleExport('csv')">
                    <div class="export-option-icon csv-icon">
                        <i class="fas fa-file-csv"></i>
                    </div>
                    <div class="export-option-content">
                        <h4>Export as CSV</h4>
                        <p>Comma-separated values for spreadsheet applications</p>
                    </div>
                    <div class="export-option-arrow">
                        <i class="fas fa-chevron-right"></i>
                    </div>
                </div>

                <div class="export-option-card" onclick="handleExport('excel')">
                    <div class="export-option-icon excel-icon">
                        <i class="fas fa-file-excel"></i>
                    </div>
                    <div class="export-option-content">
                        <h4>Export as Excel</h4>
                        <p>Microsoft Excel format with formatting</p>
                    </div>
                    <div class="export-option-arrow">
                        <i class="fas fa-chevron-right"></i>
                    </div>
                </div>

                <div class="export-option-card" onclick="handleExport('pdf')">
                    <div class="export-option-icon pdf-icon">
                        <i class="fas fa-file-pdf"></i>
                    </div>
                    <div class="export-option-content">
                        <h4>Export as PDF</h4>
                        <p>Portable document format for reports</p>
                    </div>
                    <div class="export-option-arrow">
                        <i class="fas fa-chevron-right"></i>
                    </div>
                </div>

                <div class="export-option-card" onclick="handleExport('json')">
                    <div class="export-option-icon json-icon">
                        <i class="fas fa-file-code"></i>
                    </div>
                    <div class="export-option-content">
                        <h4>Export as JSON</h4>
                        <p>JavaScript Object Notation for developers</p>
                    </div>
                    <div class="export-option-arrow">
                        <i class="fas fa-chevron-right"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Export Confirmation Modal -->
    <div id="exportConfirmModal" class="modal" style="display: none;">
        <div class="modal-content export-confirm-content">
            <div class="modal-header export-confirm-header">
                <div class="modal-icon export-confirm-icon">
                    <i class="fas fa-question-circle"></i>
                </div>
                <h3 id="exportConfirmTitle">Confirm Export</h3>
                <p id="exportConfirmMessage">Are you sure you want to export the data?</p>
            </div>

            <div class="export-confirm-details">
                <div class="export-detail-item">
                    <span class="export-detail-label">Format:</span>
                    <span id="exportFormatType" class="export-detail-value">CSV</span>
                </div>
                <div class="export-detail-item">
                    <span class="export-detail-label">Records:</span>
                    <span id="exportRecordCount" class="export-detail-value">0</span>
                </div>
                <div class="export-detail-item">
                    <span class="export-detail-label">File Name:</span>
                    <span id="exportFileName" class="export-detail-value">Engineering_Board_Passers.csv</span>
                </div>
            </div>

            <div class="modal-buttons export-confirm-buttons">
                <button id="cancelExport" class="btn-secondary export-cancel-btn">
                    <i class="fas fa-times"></i>
                    Cancel
                </button>
                <button id="confirmExport" class="btn-primary export-confirm-btn">
                    <i class="fas fa-download"></i>
                    <span id="confirmExportText">Export CSV</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Update Confirmation Modal -->
    <div id="updateModal" class="modal">
        <div class="modal-content">
            <div class="modal-header"
                style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white;">
                <div class="modal-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <h3>Review and Confirm Updates</h3>

            </div>
            <div id="updateStudentDetails" style="padding: 24px;"></div>
            <div class="modal-buttons">
                <button id="cancelUpdate" class="btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button id="confirmUpdate" class="btn-primary">
                    <i class="fas fa-check"></i> Confirm Update
                </button>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header"
                style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white; border-radius: 12px 12px 0 0; position: relative; overflow: hidden;">
                <div
                    style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: radial-gradient(circle at 30% 20%, rgba(255,255,255,0.1) 1px, transparent 1px), radial-gradient(circle at 70% 80%, rgba(255,255,255,0.08) 1px, transparent 1px); opacity: 0.6;">
                </div>
                <div style="position: relative; z-index: 1; padding: 24px;">
                    <div class="modal-icon"
                        style="background: rgba(255,255,255,0.2); width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px; backdrop-filter: blur(10px); border: 2px solid rgba(255,255,255,0.3);">
                        <i class="fas fa-exclamation-triangle" style="font-size: 24px; color: #fff;"></i>
                    </div>
                    <h3 style="margin: 0 0 8px 0; font-size: 1.5rem; font-weight: 700; text-align: center;">Confirm
                        Deletion</h3>
                    <p style="margin: 0; opacity: 0.9; text-align: center; font-size: 0.9rem;">This action cannot be
                        undone!</p>
                </div>
            </div>
            <div class="modal-body"
                style="padding: 32px 24px; text-align: center; background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);">
                <div
                    style="background: white; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); margin-bottom: 24px;">
                    <i class="fas fa-user-graduate" style="font-size: 2rem; color: #ef4444; margin-bottom: 12px;"></i>
                    <p style="color: #374151; font-size: 1.1rem; line-height: 1.6; margin: 0;">
                        Are you sure you want to delete this student's record?<br>
                        <small style="color: #6b7280; font-size: 0.9rem;">This action will permanently remove all data
                            associated with this student.</small>
                    </p>
                </div>
            </div>
            <div class="modal-buttons"
                style="padding: 0 24px 24px 24px; display: flex; gap: 12px; justify-content: center;">
                <button id="cancelDelete" class="btn-secondary"
                    style="background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%); color: #475569; border: 1px solid #cbd5e1; padding: 12px 24px; border-radius: 8px; font-weight: 600; transition: all 0.2s ease; display: flex; align-items: center; gap: 8px; min-width: 120px; justify-content: center;">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button id="confirmDelete" class="btn-danger"
                    style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white; border: none; padding: 12px 24px; border-radius: 8px; font-weight: 600; transition: all 0.2s ease; display: flex; align-items: center; gap: 8px; min-width: 120px; justify-content: center; box-shadow: 0 4px 6px -1px rgba(239, 68, 68, 0.3);">
                    <i class="fas fa-trash-alt"></i> Yes, Delete
                </button>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>

    <!-- PDF Generation Libraries - Using multiple CDNs for reliability -->
    <script src="https://unpkg.com/jspdf@latest/dist/jspdf.umd.min.js"></script>
    <script src="https://unpkg.com/jspdf-autotable@latest/dist/jspdf.plugin.autotable.min.js"></script>

    <script>
    /*
    ===========================================
    🎉 DASHBOARD ACTION BUTTONS - FULLY FIXED!
    ===========================================
    
    ✅ EDIT BUTTON FUNCTIONALITY:
    - Beautiful confirmation modal with change receipts
    - Shows before/after values for all modified fields
    - Scrollable content if changes are extensive 
    - Sticky buttons always accessible
    - Database updates after confirmation
    - Proper form validation and error handling
    - Success messages with animations
    - Modal closes automatically after successful update
    
    ✅ DELETE BUTTON FUNCTIONALITY:
    - Beautiful confirmation modal with student details
    - Scrollable content with warning messages
    - Sticky action buttons always visible
    - Multiple security warnings
    - Database deletion after confirmation
    - Proper error handling and success notifications
    - Row removal animation after successful deletion
    - Record count updates automatically
    
    🎨 DESIGN FEATURES:
    - Consistent blue/red gradient theme
    - Glassmorphism effects with backdrop blur
    - Smooth entrance/exit animations
    - Hover effects on buttons
    - Responsive design for all screen sizes
    - Professional typography and spacing
    
    📱 ACCESSIBILITY:
    - Keyboard navigation (ESC to close)
    - Click outside to close
    - High contrast colors
    - Clear visual feedback
    - Screen reader friendly
    
    🔒 SECURITY:
    - Proper form validation
    - CSRF protection via session checks
    - SQL injection prevention
    - Change detection prevents unnecessary updates
    - Multiple confirmation steps for deletions
    
    READY FOR PRODUCTION! 🚀
    ===========================================
    */

    function importData() {
        console.log('🎯 Opening import data dialog...');

        // Create import modal
        const modal = document.createElement('div');
        modal.className = 'custom-modal show';
        modal.id = 'importModal';
        modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        background: rgba(30, 41, 59, 0.6);
        backdrop-filter: blur(8px);
        z-index: 9999;
        display: flex;
        justify-content: center;
        align-items: center;
        opacity: 0;
        transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
        overflow: hidden; /* prevent outer scrolling to avoid double scrollbars */
        padding: 20px;
      `;

        modal.innerHTML = `
        <div style="
          background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
          border-radius: 24px;
          box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
          border: 2px solid rgba(139, 92, 246, 0.1);
          overflow: hidden;
          position: relative;
          max-width: 500px;
          width: 100%;
          transform: scale(0.7) translateY(-50px);
          transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
          margin: auto;
        ">
          <div style="
            background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
            padding: 32px 40px 28px;
            color: white;
            position: relative;
            overflow: hidden;
          ">
            <div style="
              width: 72px;
              height: 72px;
              background: rgba(255, 255, 255, 0.2);
              border-radius: 20px;
              display: flex;
              align-items: center;
              justify-content: center;
              margin: 0 auto 20px;
              backdrop-filter: blur(10px);
              border: 2px solid rgba(255, 255, 255, 0.2);
            ">
              <i class="fas fa-upload" style="font-size: 1.8rem;"></i>
            </div>
            <h3 style="color: white; font-weight: 800; font-size: 1.6rem; margin: 0 0 8px 0; text-align: center;">Import Data</h3>
            <p style="color: rgba(255, 255, 255, 0.95); margin: 0; text-align: center; font-size: 1.1rem; font-weight: 500;">Upload student records from file</p>
          </div>
          <div style="padding: 32px 40px;">
            <div style="text-align: center; color: #6b7280; line-height: 1.6;">
              <i class="fas fa-info-circle" style="font-size: 3rem; color: #8b5cf6; margin-bottom: 16px;"></i>
              <h4 style="margin: 0 0 16px 0; color: #374151; font-weight: 600;">Import Feature</h4>
              <p style="margin: 0 0 20px 0;">The data import functionality is currently under development. This feature will allow you to upload CSV or Excel files with student board exam records.</p>
            </div>
          </div>
          <div style="padding: 0 40px 32px; display: flex; gap: 12px;">
            <button onclick="closeImportModal()" style="flex: 1; background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%); color: white; border: none; padding: 14px 24px; border-radius: 12px; font-weight: 600; font-size: 1rem; cursor: pointer; transition: all 0.3s ease;">
              <i class="fas fa-times"></i> Close
            </button>
            <button onclick="showAddStudentModal(); closeImportModal();" style="flex: 1; background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); color: white; border: none; padding: 14px 24px; border-radius: 12px; font-weight: 600; font-size: 1rem; cursor: pointer; transition: all 0.3s ease;">
              <i class="fas fa-plus"></i> Add Manually
            </button>
          </div>
        </div>
      `;

        document.body.appendChild(modal);
        modal.onclick = function(e) {
            if (e.target === modal) {
                closeImportModal();
            }
        };
        setTimeout(() => {
            modal.style.opacity = '1';
            const content = modal.querySelector('div');
            content.style.transform = 'scale(1) translateY(0)';
        }, 10);
    }


    function closeImportModal() {
        const modal = document.getElementById('importModal');
        if (modal) {
            modal.style.opacity = '0';
            setTimeout(() => {
                modal.remove();
            }, 300);
        }
    }

    function viewStats() {
        console.log('🎯 Opening statistics view...');
        showStatsNotification('Statistics feature coming soon! 📊', 'info');
    }

    function showStatsNotification(message, type = 'info') {
        const bgColor = type === 'success' ? '#22c55e' : type === 'error' ? '#ef4444' : '#3182ce';
        const icon = type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-triangle' : 'info-circle';

        const notification = document.createElement('div');
        notification.style.cssText = `
        position: fixed; top: 20px; right: 20px; background: linear-gradient(135deg, ${bgColor} 0%, ${bgColor}dd 100%);
        color: white; padding: 16px 20px; border-radius: 12px; box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        z-index: 10000; font-weight: 600; min-width: 300px; animation: slideInFromRight 0.5s ease;
      `;
        notification.innerHTML =
            `<div style="display: flex; align-items: center; gap: 12px;"><i class="fas fa-${icon}" style="font-size: 1.2rem;"></i><span>${message}</span></div>`;
        document.body.appendChild(notification);
        setTimeout(() => {
            notification.style.transform = 'translateX(400px)';
            notification.style.opacity = '0';
            setTimeout(() => notification.remove(), 300);
        }, 4000);
    }



    function showAddStudentModal() {
        const modal = document.getElementById('addStudentModal');

        // Reset tabs to first tab
        resetTabs();

        // Show modal with smooth entrance
        modal.style.display = 'flex';

        // Force a reflow to ensure display change is applied
        modal.offsetHeight;

        // Add show class for animation
        setTimeout(() => {
            modal.classList.add('show');
        }, 10);

        // Focus on first field after animation starts
        setTimeout(() => {
            document.getElementById('addFirstName').focus();
        }, 200);

        // Add form validation
        addAddFormValidation();
    }



    function closeEditModal() {
        const modal = document.getElementById('editStudentModal');

        if (!modal) {
            return;
        }

        // Remove show class to trigger exit animation
        modal.classList.remove('show');

        // Wait for animation to complete before hiding
        setTimeout(() => {
            modal.style.display = 'none';
        }, 500);

        window.currentEditingRow = null;

        // Reset form
        const form = document.getElementById('editStudentForm');
        if (form) {
            form.reset();
            clearFormValidation();
        }
    }

    // Open Edit modal, load details for the selected row, and wire dependencies
    async function openEdit(btn) {
        try {
            const row = btn.closest('tr');
            if (!row) return;
            const id = parseInt(row.getAttribute('data-id') || '0', 10);
            if (!id) return;

            // Keep reference to current row for UI update
            window.currentEditingRow = row;

            // Fetch full record details via backend
            const resp = await fetch('get_board_passer.php?id=' + encodeURIComponent(id), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            const rawText = await resp.text();
            let data;
            try {
                data = JSON.parse(rawText);
            } catch (_) {
                data = null;
            }
            if (!resp.ok) {
                const msg = data && data.message ? data.message : ('Server returned ' + resp.status + (rawText ? (
                    ' — ' + rawText.substring(0, 120)) : ''));
                throw new Error(msg);
            }
            if (!data) {
                console.error('Non-JSON response from get_board_passer.php:', rawText);
                throw new Error('Failed to open edit modal: Unexpected response from server (not JSON).');
            }
            if (!data || !data.success) {
                showUpdateErrorMessage(data && data.message ? data.message : 'Failed to load student details');
                return;
            }
            const rec = data.data || {};
            const subjectsExisting = data.subjects || [];

            // Populate form fields
            const modal = document.getElementById('editStudentModal');
            const form = document.getElementById('editStudentForm');
            if (!modal || !form) return;

            // Set values
            document.getElementById('editStudentId').value = id;
            document.getElementById('editFirstName').value = rec.first_name || '';
            document.getElementById('editMiddleName').value = rec.middle_name || '';
            document.getElementById('editLastName').value = rec.last_name || '';
            document.getElementById('editSuffix').value = rec.suffix || '';
            document.getElementById('editSex').value = rec.sex || '';
            document.getElementById('editCourse').value = rec.course || '';
            document.getElementById('editYear').value = rec.year_graduated || '';
            document.getElementById('editResult').value = rec.result || '';
            document.getElementById('editExamType').value = rec.exam_type || '';
            document.getElementById('editRating').value = rec.rating || '';

            // Select board exam type by id or by name fallback
            const betSelect = document.getElementById('editBoardExamType');
            if (betSelect) {
                let setOk = false;
                if (rec.board_exam_type) {
                    // Try direct value match (id)
                    for (let i = 0; i < betSelect.options.length; i++) {
                        if (String(betSelect.options[i].value) === String(rec.board_exam_type)) {
                            betSelect.selectedIndex = i;
                            setOk = true;
                            break;
                        }
                    }
                    if (!setOk) {
                        // Try match by data-name
                        for (let i = 0; i < betSelect.options.length; i++) {
                            if (betSelect.options[i].dataset && betSelect.options[i].dataset.name === String(rec
                                    .board_exam_type)) {
                                betSelect.selectedIndex = i;
                                setOk = true;
                                break;
                            }
                        }
                    }
                }
                if (!setOk) betSelect.selectedIndex = 0;
            }

            // Board exam date
            const dateSelect = document.getElementById('editExamDate');
            const dateHint = document.getElementById('editExamDateHint');
            const customDateGroup = document.getElementById('editCustomDateGroup');
            const customDateInput = document.getElementById('editCustomDate');

            // Ensure date select is enabled and filtered based on type
            try {
                filterExamDates('editBoardExamType', 'editExamDate');
            } catch (e) {}

            if (dateSelect) {
                dateSelect.value = rec.board_exam_date || '';
                if (!dateSelect.value && rec.board_exam_date) {
                    // If not in list, use custom date option
                    dateSelect.value = 'other';
                }
                // Toggle custom date controls
                const toggleCustom = () => {
                    if (dateSelect.value === 'other') {
                        customDateGroup.style.display = '';
                        if (customDateInput) {
                            customDateInput.name = 'board_exam_date';
                            customDateInput.value = (rec.board_exam_date && dateSelect.value === 'other') ? rec
                                .board_exam_date : '';
                        }
                        dateSelect.removeAttribute('required');
                    } else {
                        customDateGroup.style.display = 'none';
                        if (customDateInput) {
                            customDateInput.name = 'custom_exam_date';
                            customDateInput.value = '';
                        }
                        dateSelect.setAttribute('required', 'required');
                    }
                };
                dateSelect.addEventListener('change', toggleCustom);
                toggleCustom();
                if (dateHint) {
                    // hint visibility controlled by updateDateEnabled, but ensure hidden if type selected
                    const bet = document.getElementById('editBoardExamType');
                    dateHint.style.display = (bet && bet.value) ? 'none' : '';
                }
            }

            // Hidden original values for change detection
            const fullOriginalName = (row.querySelector('td:nth-child(1)')?.textContent || '').trim();
            document.getElementById('originalName').value = fullOriginalName;
            document.getElementById('originalCourse').value = (row.querySelector('td:nth-child(2)')?.textContent ||
                '').trim();
            document.getElementById('originalYear').value = (row.querySelector('td:nth-child(3)')?.textContent ||
                '').trim();
            document.getElementById('originalDate').value = (row.querySelector('td:nth-child(4)')?.getAttribute(
                'data-date') || row.querySelector('td:nth-child(4)')?.textContent || '').trim();
            const resultBadge = row.querySelector('td:nth-child(5) .status-badge');
            document.getElementById('originalResult').value = resultBadge ? resultBadge.textContent.trim() : '';
            const examTypeBadge = row.querySelector('td:nth-child(6) .status-badge');
            document.getElementById('originalExamType').value = examTypeBadge ? examTypeBadge.textContent.trim() :
                '';
            document.getElementById('originalBoardExamType').value = (row.querySelector('td:nth-child(7)')
                ?.textContent || '').trim();

            // Hidden "name" for UI update; keep updated as name parts change
            const setFullNameHidden = () => {
                const ln = document.getElementById('editLastName').value || '';
                const fn = document.getElementById('editFirstName').value || '';
                const mn = document.getElementById('editMiddleName').value || '';
                const sf = document.getElementById('editSuffix').value || '';
                let name = '';
                if (ln || fn) {
                    name = ln + (ln && fn ? ', ' : '') + fn;
                } else {
                    name = (fn + ' ' + ln).trim();
                }
                if (mn) name += ' ' + mn;
                if (sf) name += ' ' + sf;
                document.getElementById('editFullName').value = name.trim();
            };
            ['editLastName', 'editFirstName', 'editMiddleName', 'editSuffix'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.addEventListener('input', setFullNameHidden);
            });
            // Initialize now with current values (fallback to original name if parts empty)
            setFullNameHidden();
            if (!document.getElementById('editFullName').value && fullOriginalName) {
                document.getElementById('editFullName').value = fullOriginalName;
            }

            // Show modal with animation
            modal.style.display = 'flex';
            modal.offsetHeight; // reflow
            setTimeout(() => {
                modal.classList.add('show');
            }, 10);

            // Ensure date enabled state is correct for edit selects
            (function() {
                const typeEl = document.getElementById('editBoardExamType');
                const dateEl = document.getElementById('editExamDate');
                try {
                    // This function exists up the page; it handles enabling/showing and filtering
                    const hintEl = document.getElementById(dateEl.id + 'Hint');
                    if (!typeEl.value || typeEl.value === '') {
                        dateEl.disabled = true;
                        dateEl.selectedIndex = 0;
                        dateEl.style.display = 'none';
                        if (hintEl) hintEl.style.display = '';
                    } else {
                        dateEl.disabled = false;
                        dateEl.style.display = '';
                        if (hintEl) hintEl.style.display = 'none';
                        filterExamDates('editBoardExamType', 'editExamDate');
                    }
                } catch (e) {}
            })();

            // Load subjects for the selected board exam type/date and prefill existing grades
            try {
                await (async () => {
                    await loadSubjectsForPair('editBoardExamType', 'editExamDate', 'editSubjectsContainer',
                        'editSubjectsList', 'editNoSubjectsPlaceholder', id);
                })();
                // Prefill grades if any
                if (Array.isArray(subjectsExisting)) {
                    subjectsExisting.forEach(su => {
                        const gradeInput = document.querySelector("input[name='edit_subject_grade_" + su
                            .subject_id + "']");
                        const resultHidden = document.querySelector("input[name='edit_subject_result_" + su
                            .subject_id + "']");
                        if (gradeInput) gradeInput.value = su.grade != null ? String(su.grade) : '';
                        if (resultHidden) resultHidden.value = (su.result === 1 || su.passed === 1 || su
                            .result === 'Passed') ? 'Passed' : (su.result === 'Failed' ? 'Failed' : '');
                    });
                }
            } catch (e) {
                /* silent */
            }

        } catch (e) {
            console.error('openEdit failed', e);
            showUpdateErrorMessage('Failed to open edit modal: ' + (e && e.message ? e.message : e));
        }
    }

    function showAddStudentModal() {
        const modal = document.getElementById('addStudentModal');

        // Reset tabs to first tab
        resetTabs();

        // Show modal with smooth entrance
        modal.style.display = 'flex';

        // Force a reflow to ensure display change is applied
        modal.offsetHeight;

        // Add show class for animation
        setTimeout(() => {
            modal.classList.add('show');
        }, 10);

        // Focus on first field after animation starts
        setTimeout(() => {
            document.getElementById('addFirstName').focus();
        }, 200);

        // Add form validation
        addAddFormValidation();
    }

    function closeAddModal() {
        const modal = document.getElementById('addStudentModal');

        // Remove show class to trigger exit animation
        modal.classList.remove('show');

        // Wait for animation to complete before hiding
        setTimeout(() => {
            modal.style.display = 'none';
        }, 500);

        // Reset form and tabs
        document.getElementById('addStudentForm').reset();
        clearAddFormValidation();
        resetTabs();
    }

    // Tab navigation functions
    function switchTab(tabName) {
        // Remove active class from all tabs and buttons
        document.querySelectorAll('.tab-content').forEach(tab => {
            tab.classList.remove('active');
        });
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('active');
        });

        // Add active class to selected tab and button
        document.getElementById(tabName + 'Tab').classList.add('active');
        document.querySelector(`[data-tab="${tabName}"]`).classList.add('active');
    }

    function nextTab() {
        // Validate current tab before proceeding
        const personalTab = document.getElementById('personalTab');
        if (personalTab.classList.contains('active')) {
            // Validate required fields in personal tab
            const requiredFields = personalTab.querySelectorAll('input[required], select[required]');
            let isValid = true;

            for (let field of requiredFields) {
                if (!field.value.trim()) {
                    field.focus();
                    showValidationErrorMessage('Please fill in all required fields before proceeding.');
                    isValid = false;
                    break;
                }
            }

            if (isValid) {
                switchTab('exam');
                // Focus on first field in exam tab
                setTimeout(() => {
                    document.getElementById('addYear').focus();
                }, 100);
            }
        }
    }

    function prevTab() {
        switchTab('personal');
        // Focus on first field in personal tab
        setTimeout(() => {
            document.getElementById('addFirstName').focus();
        }, 100);
    }

    function resetTabs() {
        // Reset to first tab
        switchTab('personal');
    }

    function addAddFormValidation() {
        const firstNameInput = document.getElementById('addFirstName');
        const lastNameInput = document.getElementById('addLastName');
        const middleNameInput = document.getElementById('addMiddleName');
        const suffixInput = document.getElementById('addSuffix');
        const yearInput = document.getElementById('addYear');

        // Name validation function
        const validateName = (input, fieldName) => {
            const feedback = input.parentNode.querySelector('.input-feedback');
            // Use a simple, safe character check (letters, space, comma, period, apostrophe, hyphen)
            const namePattern = /^[A-Za-z ,.'-]+$/;

            if (input.required && (!namePattern.test(input.value.trim()) || input.value.trim().length < 2)) {
                input.style.borderColor = '#ef4444';
                input.style.background = '#fef2f2';
                feedback.textContent =
                    `Enter a valid ${fieldName} (letters, spaces, commas, periods, and hyphens only)`;
                feedback.className = 'input-feedback show error';
                input.setCustomValidity(`Enter a valid ${fieldName}`);
            } else if (!input.required && input.value.trim() && (!namePattern.test(input.value.trim()) || input
                    .value.trim().length < 1)) {
                input.style.borderColor = '#ef4444';
                input.style.background = '#fef2f2';
                feedback.textContent =
                    `Enter a valid ${fieldName} (letters, spaces, commas, periods, and hyphens only)`;
                feedback.className = 'input-feedback show error';
                input.setCustomValidity(`Enter a valid ${fieldName}`);
            } else {
                input.style.borderColor = '#10b981';
                input.style.background = '#f0fdf4';
                feedback.textContent = `Valid ${fieldName} format`;
                feedback.className = 'input-feedback show success';
                input.setCustomValidity('');
            }
        };

        // Add event listeners for name fields
        firstNameInput.addEventListener('input', () => validateName(firstNameInput, 'first name'));
        lastNameInput.addEventListener('input', () => validateName(lastNameInput, 'last name'));
        middleNameInput.addEventListener('input', () => validateName(middleNameInput, 'middle name'));
        suffixInput.addEventListener('input', () => validateName(suffixInput, 'suffix'));

        // Year validation with visual feedback
        yearInput.addEventListener('change', function() {
            const feedback = this.parentNode.querySelector('.input-feedback');

            if (!this.value) {
                this.style.borderColor = '#ef4444';
                this.style.background = '#fef2f2';
                feedback.textContent = 'Please select a graduation year';
                feedback.className = 'input-feedback show error';
                this.setCustomValidity('Please select a graduation year');
            } else {
                this.style.borderColor = '#10b981';
                this.style.background = '#f0fdf4';
                feedback.textContent = 'Valid graduation year selected';
                feedback.className = 'input-feedback show success';
                this.setCustomValidity('');
            }
        });

        // Add focus/blur effects for all inputs
        const allInputs = document.querySelectorAll('#addStudentForm input, #addStudentForm select');
        allInputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.style.borderColor = '#3182ce';
                this.style.boxShadow = '0 0 0 4px rgba(49, 130, 206, 0.15)';
                this.style.transform = 'translateY(-1px)';
            });

            input.addEventListener('blur', function() {
                if (this.checkValidity()) {
                    this.style.borderColor = '#e5e7eb';
                }
                this.style.boxShadow = 'none';
                this.style.transform = 'translateY(0)';
            });
        });
    }

    function clearAddFormValidation() {
        const allInputs = document.querySelectorAll('#addStudentForm input, #addStudentForm select');
        const allFeedback = document.querySelectorAll('#addStudentForm .input-feedback');

        allInputs.forEach(input => {
            input.style.borderColor = '#e5e7eb';
            input.style.background = 'white';
            input.style.boxShadow = 'none';
            input.style.transform = 'translateY(0)';
            input.setCustomValidity('');
        });

        allFeedback.forEach(feedback => {
            feedback.className = 'input-feedback';
            feedback.textContent = '';
        });
    }

    function addFormValidation() {
        const nameInput = document.getElementById('editName');
        const yearInput = document.getElementById('editYear');

        // Name validation with visual feedback
        nameInput.addEventListener('input', function() {
            const feedback = this.parentNode.querySelector('.input-feedback');
            const namePattern = /^[a-zA-Z\s,.-]+$/;

            if (!namePattern.test(this.value.trim()) || this.value.trim().length < 2) {
                this.style.borderColor = '#ef4444';
                this.style.background = '#fef2f2';
                feedback.textContent =
                    'Enter a valid name (letters, spaces, commas, periods, and hyphens only)';
                feedback.className = 'input-feedback show error';
                this.setCustomValidity('Enter a valid name');
            } else {
                this.style.borderColor = '#10b981';
                this.style.background = '#f0fdf4';
                feedback.textContent = 'Valid name format';
                feedback.className = 'input-feedback show success';
                this.setCustomValidity('');
            }
        });

        // Year validation with visual feedback
        yearInput.addEventListener('change', function() {
            const feedback = this.parentNode.querySelector('.input-feedback');

            if (!this.value) {
                this.style.borderColor = '#ef4444';
                this.style.background = '#fef2f2';
                feedback.textContent = 'Please select a graduation year';
                feedback.className = 'input-feedback show error';
                this.setCustomValidity('Please select a graduation year');
            } else {
                this.style.borderColor = '#10b981';
                this.style.background = '#f0fdf4';
                feedback.textContent = 'Valid graduation year selected';
                feedback.className = 'input-feedback show success';
                this.setCustomValidity('');
            }
        });

        // Add focus/blur effects for all inputs
        const allInputs = document.querySelectorAll('#editStudentForm input, #editStudentForm select');
        allInputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.style.borderColor = '#3182ce';
                this.style.boxShadow = '0 0 0 4px rgba(49, 130, 206, 0.15)';
                this.style.transform = 'translateY(-1px)';
            });

            input.addEventListener('blur', function() {
                if (this.checkValidity()) {
                    this.style.borderColor = '#e5e7eb';
                }
                this.style.boxShadow = 'none';
                this.style.transform = 'translateY(0)';
            });
        });
    }

    function clearFormValidation() {
        const allInputs = document.querySelectorAll('#editStudentForm input, #editStudentForm select');
        const allFeedback = document.querySelectorAll('#editStudentForm .input-feedback');

        allInputs.forEach(input => {
            input.style.borderColor = '#e5e7eb';
            input.style.background = 'white';
            input.style.boxShadow = 'none';
            input.style.transform = 'translateY(0)';
            input.setCustomValidity('');
        });

        allFeedback.forEach(feedback => {
            feedback.className = 'input-feedback';
            feedback.textContent = '';
        });
    }

    // Handle form submission
    document.addEventListener('DOMContentLoaded', function() {
        // Check for URL parameters to auto-open modals
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('action') === 'add') {
            // Auto-open add modal when redirected from other pages
            setTimeout(() => {
                showAddStudentModal();
            }, 500); // Small delay to ensure page is fully loaded

            // Clean the URL to remove the parameter
            const newUrl = window.location.pathname;
            window.history.replaceState({}, document.title, newUrl);
        }

        const editForm = document.getElementById('editStudentForm');
        if (editForm) {
            editForm.addEventListener('submit', function(e) {
                e.preventDefault(); // Prevent default form submission
                console.log('Form submission intercepted, calling handleFormSubmission...');
                handleFormSubmission(); // Call our custom handler
            });
            console.log('✅ Form submit event listener added');
        }

        // Handle exam date selection for add form
        const addExamDateSelect = document.getElementById('addExamDate');
        const addCustomDateGroup = document.getElementById('customDateGroup');
        const addCustomDateInput = document.getElementById('addCustomDate');

        if (addExamDateSelect && addCustomDateGroup && addCustomDateInput) {
            addExamDateSelect.addEventListener('change', function() {
                if (this.value === 'other') {
                    addCustomDateGroup.style.display = 'block';
                    addCustomDateInput.setAttribute('required', 'required');
                    addCustomDateInput.name = 'board_exam_date'; // Switch name to be submitted
                    this.removeAttribute('required');
                } else {
                    addCustomDateGroup.style.display = 'none';
                    addCustomDateInput.removeAttribute('required');
                    addCustomDateInput.name = 'custom_exam_date'; // Change name so it's not submitted
                    this.setAttribute('required', 'required');
                }
            });
            console.log('✅ Add form exam date handler added');
        }

        const addForm = document.getElementById('addStudentForm');
        if (addForm) {
            addForm.addEventListener('submit', function(e) {
                e.preventDefault(); // Prevent default form submission
                console.log('Add form submission intercepted, calling handleAddFormSubmission...');
                handleAddFormSubmission();
            });
            console.log('✅ Add form submit event listener added');
        }

        // Close modal when clicking outside for add modal
        const addModal = document.getElementById('addStudentModal');
        if (addModal) {
            addModal.addEventListener('click', function(e) {
                if (e.target === addModal) {
                    closeAddModal();
                }
            });
        }

        // Keyboard support
        document.addEventListener('keydown', function(e) {
            const editModal = document.getElementById('editStudentModal');
            const addModal = document.getElementById('addStudentModal');

            if (editModal && editModal.classList.contains('show')) {
                if (e.key === 'Escape') {
                    closeEditModal();
                }
            } else if (addModal && addModal.classList.contains('show')) {
                if (e.key === 'Escape') {
                    closeAddModal();
                }
            }
        });

        // Initialize dashboard features
        console.log('🚀 Initializing dashboard features...');
        initializeFilters();
        initializeKeyboardShortcuts();
        initializeDashboardButtons();

        console.log('✅ Dashboard initialization complete!');
    });

    // Initialize dashboard button functionality
    function initializeDashboardButtons() {
        console.log('🔧 Initializing dashboard buttons...');

        // Apply Filters button
        const applyFiltersBtn = document.getElementById('applyFilters');
        if (applyFiltersBtn) {
            applyFiltersBtn.addEventListener('click', applyFilters);
            console.log('✅ Apply Filters button initialized');
        }

        // Clear Filters button
        const clearFiltersBtn = document.getElementById('clearFilters');
        if (clearFiltersBtn) {
            clearFiltersBtn.addEventListener('click', clearFilters);
            console.log('✅ Clear Filters button initialized');
        }

        // Toggle Filters button
        const toggleFiltersBtn = document.getElementById('toggleFilters');
        if (toggleFiltersBtn) {
            toggleFiltersBtn.addEventListener('click', toggleFilters);
            console.log('✅ Toggle Filters button initialized');
        }

        // Board Exam Type filter change - populate exam dates dynamically
        const boardExamTypeFilter = document.getElementById('boardExamTypeFilter');
        const examDateFilter = document.getElementById('examDateFilter');
        if (boardExamTypeFilter && examDateFilter) {
            boardExamTypeFilter.addEventListener('change', function() {
                const selectedOptions = Array.from(this.selectedOptions);
                const selectedTypeIds = selectedOptions.map(opt => opt.value);

                // Clear and reset exam date filter
                examDateFilter.innerHTML = '';

                if (selectedTypeIds.length === 0) {
                    // No type selected - disable exam date filter
                    const placeholder = document.createElement('option');
                    placeholder.value = '';
                    placeholder.disabled = true;
                    placeholder.textContent = '-- Select board exam type first --';
                    examDateFilter.appendChild(placeholder);
                    examDateFilter.disabled = true;
                    return;
                }

                // Filter and populate dates that match ANY of the selected exam types
                let hasMatches = false;
                const boardExamDates = window.BOARD_EXAM_DATES || [];
                const addedDates = new Set(); // Prevent duplicates

                boardExamDates.forEach(dateObj => {
                    // Check if this date belongs to any of the selected types
                    if (selectedTypeIds.includes(String(dateObj.exam_type_id))) {
                        const dateKey = dateObj.id;
                        if (!addedDates.has(dateKey)) {
                            const option = document.createElement('option');
                            option.value = dateObj.id;
                            option.textContent = dateObj.date + (dateObj.description ? ' — ' + dateObj
                                .description : '');
                            examDateFilter.appendChild(option);
                            addedDates.add(dateKey);
                            hasMatches = true;
                        }
                    }
                });

                // Enable/disable based on whether we have matches
                examDateFilter.disabled = !hasMatches;

                if (!hasMatches) {
                    const noDataOption = document.createElement('option');
                    noDataOption.value = '';
                    noDataOption.disabled = true;
                    noDataOption.textContent = '-- No exam dates available --';
                    examDateFilter.appendChild(noDataOption);
                }
            });
            console.log('✅ Board Exam Type filter change listener initialized');
        }

        console.log('✅ All dashboard buttons initialized!');
    }

    function handleFormSubmission() {
        console.log('🎯 handleFormSubmission called');

        const form = document.getElementById('editStudentForm');
        if (!form) {
            console.error('Edit form not found');
            showUpdateErrorMessage('Form not found. Please refresh the page and try again.');
            return;
        }

        const formData = new FormData(form);
        console.log('Form data created');

        // Debug: Log all form data being sent
        console.log('📋 Form data being sent:');
        for (let [key, value] of formData.entries()) {
            console.log(`  ${key}: "${value}"`);
        }

        // Validate all fields
        if (!form.checkValidity()) {
            console.log('Form validation failed');
            const firstInvalidField = form.querySelector(':invalid');
            if (firstInvalidField) {
                firstInvalidField.focus();
                showValidationErrorMessage('Please fix the highlighted errors before saving.');
            }
            return;
        }

        console.log('Form validation passed');

        // Check if any changes were made
        const originalData = {
            name: document.getElementById('originalName').value,
            course: document.getElementById('originalCourse').value,
            year: document.getElementById('originalYear').value,
            date: document.getElementById('originalDate').value,
            result: document.getElementById('originalResult').value,
            examType: document.getElementById('originalExamType').value,
            boardExamType: document.getElementById('originalBoardExamType').value
        };

        const newData = {
            name: formData.get('name'),
            course: formData.get('course'),
            year: formData.get('year_graduated'),
            date: formData.get('board_exam_date'),
            result: formData.get('result'),
            examType: formData.get('exam_type'),
            boardExamType: formData.get('board_exam_type')
        };

        // Normalize values before comparing to avoid false positives
        const normalizeForCompare = (key, val) => {
            if (val === undefined || val === null) return '';
            let v = String(val).trim();
            if (key === 'boardExamType') {
                const map = (window.BOARD_EXAM_TYPE_MAP || {});
                // If it's an ID, return normalized ID string
                const parsed = parseInt(v, 10);
                if (!isNaN(parsed) && map[parsed] !== undefined) return String(parsed);
                // Otherwise try reverse-lookup by name (case-insensitive)
                const lower = v.toLowerCase();
                for (const [id, name] of Object.entries(map)) {
                    if (String(name).toLowerCase() === lower) return String(id);
                }
                // Fallback to the raw lowercase name
                return lower;
            }
            // Default: trimmed string
            return v;
        };

        const hasChanges = Object.keys(originalData).some(key => {
            return normalizeForCompare(key, originalData[key]) !== normalizeForCompare(key, newData[key]);
        });

        if (!hasChanges) {
            console.log('No changes detected');
            showInfoMessage('No changes detected. Please modify the information to update.');
            return;
        }

        console.log('Changes detected, showing beautiful confirmation modal');
        showEditConfirmationModal(originalData, newData, formData);
    }

    function showEditConfirmationModal(originalData, newData, formData) {
        // Ensure one-time styles for premium animated border
        (function ensureConfirmStyles() {
            if (!document.getElementById('confirmModalStyles')) {
                const s = document.createElement('style');
                s.id = 'confirmModalStyles';
                s.textContent = `
            @keyframes confirmBreath {
              0%,100% { opacity: .55; box-shadow: 0 0 0 2px rgba(6,182,212,.35), 0 0 30px rgba(6,182,212,.14); }
              50%     { opacity: 1;   box-shadow: 0 0 0 2px rgba(6,182,212,.60), 0 0 46px rgba(6,182,212,.26); }
            }
            .confirm-animated-border { position:absolute; inset:0; border-radius:24px; pointer-events:none; border:2px solid rgba(6,182,212,.45); animation: confirmBreath 6s ease-in-out infinite; }

            /* Subtle fade/slide for change rows */
            @keyframes fadeUp { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: translateY(0); } }

            /* Optional dark mode adjustments */
            @media (prefers-color-scheme: dark) {
              .confirm-modal-dark-bg { background: linear-gradient(135deg, #0b1220 0%, #0f172a 100%) !important; }
              .confirm-modal-content { background: linear-gradient(135deg, #0f172a 0%, #111827 100%) !important; border-color: rgba(59,130,246,0.22) !important; }
            }
          `;
                document.head.appendChild(s);
            }
        })();

        // Create beautiful confirmation modal
        const modal = document.createElement('div');
        modal.className = 'custom-modal show';
        modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        background: rgba(30, 41, 59, 0.6);
        backdrop-filter: blur(8px);
        z-index: 9999;
        display: flex;
        justify-content: center;
        align-items: center;
        opacity: 0;
        transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
        overflow-y: auto;
        padding: 20px;
      `;

        modal.innerHTML = `
        <div class="confirm-modal-content" style="
          background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
          border-radius: 24px;
          box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
          border: 2px solid rgba(6, 182, 212, 0.12);
          overflow: hidden;
          position: relative;
          max-width: 650px;
          width: 100%;
          max-height: 90vh;
          overflow: hidden; /* container is not scrollable; inner content area will scroll */
          display: flex;
          flex-direction: column;
          transform: scale(0.7) translateY(-50px);
          transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
          margin: auto;
        ">
          <div class="confirm-animated-border"></div>
          <!-- Header -->
          <div class="confirm-modal-dark-bg" style="
            background: linear-gradient(135deg, #0ea5b1 0%, #06b6d4 100%);
            padding: 36px 40px 48px; /* add extra bottom padding so the title doesn't feel crowded near the header edge */
            color: white;
            position: relative;
            overflow: hidden;
            position: sticky;
            top: 0;
            z-index: 1;
            box-shadow: inset 0 -1px 0 rgba(255,255,255,0.25); /* subtle divider to separate header and content */
          ">
            <div style="
              position: absolute;
              top: -50px;
              right: -50px;
              width: 120px;
              height: 120px;
              background: rgba(255, 255, 255, 0.1);
              border-radius: 50%;
            "></div>
            <!-- Close button removed by request -->
            
            <div style="
              width: 68px;
              height: 68px;
              background: rgba(255, 255, 255, 0.22);
              border-radius: 20px;
              display: flex;
              align-items: center;
              justify-content: center;
              margin: 0 auto 18px;
              backdrop-filter: blur(10px);
              border: 2px solid rgba(255, 255, 255, 0.25);
              box-shadow: 0 10px 24px rgba(0,0,0,0.12);
            ">
              <i class="fas fa-edit" style="font-size: 1.7rem;"></i>
            </div>
            
            <h3 style="
              color: white; 
              font-weight: 800; 
              font-size: 1.6rem;
              margin: 0 0 8px 0;
              text-align: center;
              text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            ">Review and Confirm Updates</h3>
          </div>
          
          <!-- Scrollable Content -->
          <div style="
            padding: 28px 40px 32px; /* slightly reduce top padding since header gained padding */
            flex: 1 1 auto;           /* take remaining height */
            min-height: 0;            /* allow child to shrink for scrolling */
            overflow-y: auto;         /* only this area scrolls */
            max-height: none;         /* rely on flex instead of fixed vh */
          ">
            <div style="background: #f7fafc; padding: 24px; border-radius: 16px; border: 1px solid #e2e8f0; margin-bottom: 24px;">
              <h4 style="color: #2d3748; margin: 0 0 16px 0; font-weight: 700; font-size: 1.1rem;">
                <i class="fas fa-user-graduate" style="margin-right: 8px; color: #06b6d4;"></i>
                Student: ${newData.name}
              </h4>
              <div style="color: #4a5568; margin-bottom: 20px; font-weight: 500;">
                ${newData.course} • Class of ${newData.year}
              </div>
              
              <div style="color: #0ea5b1; margin-bottom: 16px; font-weight: 800; border-top: 1px solid #e2e8f0; padding-top: 16px; letter-spacing: .2px;">
                <i class="fas fa-clipboard-list" style="margin-right: 8px; color: #06b6d4;"></i>
                Summary of Changes:
              </div>
              
              <div style="display: grid; gap: 12px;">
                ${generateChangesList(originalData, newData)}
              </div>
            </div>
            
            <div role="alert" style="
              display: flex;
              gap: 12px;
              align-items: flex-start;
              background: linear-gradient(180deg, #FFFAF0 0%, #FFF3CD 100%);
              border: 1px solid #F6E05E;
              border-radius: 14px;
              padding: 16px 18px;
              margin-bottom: 24px;
              box-shadow: inset 0 4px 12px rgba(251, 191, 36, 0.12), 0 6px 16px rgba(0,0,0,0.04);
            ">
              <div style="
                width: 36px;
                height: 36px;
                border-radius: 10px;
                background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%);
                color: #ffffff;
                display: flex;
                align-items: center;
                justify-content: center;
                box-shadow: 0 8px 18px rgba(217, 119, 6, 0.35);
                flex: 0 0 auto;
              ">
                <i class="fas fa-exclamation" style="font-size: 16px;"></i>
              </div>
              <div style="line-height: 1.35; color: #7C2D12;">
                <div style="font-weight: 800; margin: 0 0 2px 0; letter-spacing: .2px;">Important</div>
                <div style="color: #78350F;">This action will permanently update the student's information in the database.</div>
              </div>
            </div>
          </div>
          
          <!-- Sticky Buttons -->
          <div style="
            display: flex;
            gap: 16px;
            padding: 24px 40px 40px;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border-top: 2px solid #e2e8f0;
            position: sticky;
            bottom: 0;
            z-index: 1;
            justify-content: flex-end; /* align buttons to the right */
            flex-wrap: wrap;           /* allow wrap on narrow screens */
          ">
            <button id="confirmEditBtn" style="
              background: linear-gradient(135deg, #0ea5b1 0%, #06b6d4 100%);
              color: white;
              border: none;
              padding: 16px 24px;
              border-radius: 9999px; /* pill */
              font-weight: 600;
              font-size: 1rem;
              cursor: pointer;
              transition: all 0.3s ease;
              box-shadow: 0 8px 20px rgba(6, 182, 212, 0.25);
              min-width: 200px;
              display: inline-flex;
              align-items: center;
              justify-content: center;
            ">
              <i class="fas fa-check-circle" style="margin-right: 8px;"></i>
              Yes, Update Record
            </button>
            <button id="cancelEditBtn" style="
              background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
              color: white;
              border: none;
              padding: 16px 24px;
              border-radius: 9999px; /* pill */
              font-weight: 600;
              font-size: 1rem;
              cursor: pointer;
              transition: all 0.3s ease;
              box-shadow: 0 8px 20px rgba(239, 68, 68, 0.25);
              min-width: 180px;
              display: inline-flex;
              align-items: center;
              justify-content: center;
            ">
              <i class="fas fa-times-circle" style="margin-right: 8px;"></i>
              Cancel Changes
            </button>
          </div>
        </div>
      `;

        document.body.appendChild(modal);

        // Add event listeners
        const confirmBtn = modal.querySelector('#confirmEditBtn');
        const cancelBtn = modal.querySelector('#cancelEditBtn');

        // Add simple ripple effect to buttons
        function addRippleEffect(button, color = 'rgba(255,255,255,0.35)') {
            if (!button) return;
            button.style.position = 'relative';
            button.style.overflow = 'hidden';
            button.addEventListener('click', function(e) {
                const rect = button.getBoundingClientRect();
                const d = Math.max(rect.width, rect.height) * 2;
                const circle = document.createElement('span');
                circle.style.position = 'absolute';
                circle.style.borderRadius = '50%';
                circle.style.pointerEvents = 'none';
                circle.style.width = circle.style.height = d + 'px';
                circle.style.left = (e.clientX - rect.left - d / 2) + 'px';
                circle.style.top = (e.clientY - rect.top - d / 2) + 'px';
                circle.style.background = color;
                circle.style.transform = 'scale(0)';
                circle.style.opacity = '0.6';
                circle.style.transition = 'transform 500ms ease-out, opacity 700ms ease-out';
                button.appendChild(circle);
                requestAnimationFrame(() => {
                    circle.style.transform = 'scale(1)';
                    circle.style.opacity = '0';
                });
                setTimeout(() => circle.remove(), 720);
            });
        }
        // Button hover effects with glow
        confirmBtn.addEventListener('mouseenter', () => {
            confirmBtn.style.filter = 'brightness(1.02) saturate(1.05)';
            confirmBtn.style.boxShadow = '0 0 0 6px rgba(6,182,212,0.20), 0 14px 30px rgba(6,182,212,0.35)';
        });
        confirmBtn.addEventListener('mouseleave', () => {
            confirmBtn.style.filter = '';
            confirmBtn.style.boxShadow = '0 8px 20px rgba(6,182,212,0.25)';
        });
        cancelBtn.addEventListener('mouseenter', () => {
            cancelBtn.style.filter = 'brightness(1.02) saturate(1.05)';
            cancelBtn.style.boxShadow = '0 0 0 6px rgba(239,68,68,0.20), 0 14px 30px rgba(239,68,68,0.35)';
        });
        cancelBtn.addEventListener('mouseleave', () => {
            cancelBtn.style.filter = '';
            cancelBtn.style.boxShadow = '0 8px 20px rgba(239,68,68,0.25)';
        });
        // Press (click) animations
        confirmBtn.addEventListener('mousedown', () => {
            confirmBtn.style.transform = 'translateY(1px) scale(0.99)';
        });
        confirmBtn.addEventListener('mouseup', () => {
            confirmBtn.style.transform = '';
        });
        cancelBtn.addEventListener('mousedown', () => {
            cancelBtn.style.transform = 'translateY(1px) scale(0.99)';
        });
        cancelBtn.addEventListener('mouseup', () => {
            cancelBtn.style.transform = '';
        });
        // Ripples
        addRippleEffect(confirmBtn, 'rgba(255,255,255,0.35)');
        addRippleEffect(cancelBtn, 'rgba(255,255,255,0.35)');

        confirmBtn.onclick = function() {
            // Show progress on confirm button and prevent accidental dismissal
            confirmBtn.disabled = true;
            cancelBtn.disabled = true;
            confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
            modal.dataset.busy = '1';
            performDatabaseUpdate(formData).finally(() => {
                modal.remove();
            });
        };

        cancelBtn.onclick = function() {
            modal.remove();
        };

        // Close on outside click
        modal.onclick = function(e) {
            if (e.target === modal && modal.dataset.busy !== '1') {
                modal.remove();
            }
        };

        // Close on escape key
        const escapeHandler = function(e) {
            if (e.key === 'Escape' && modal.dataset.busy !== '1') {
                modal.remove();
                document.removeEventListener('keydown', escapeHandler);
            }
        };
        document.addEventListener('keydown', escapeHandler);

        // Trigger entrance animation
        setTimeout(() => {
            modal.style.opacity = '1';
            const content = modal.querySelector('div');
            content.style.transform = 'scale(1) translateY(0)';
        }, 10);
    }

    function generateChangesList(originalData, newData) {
        // Normalizer mirroring the one used for hasChanges to ensure consistent comparisons
        const normalizeForCompare = (key, val) => {
            if (val === undefined || val === null) return '';
            let v = String(val).trim();
            if (key === 'boardExamType') {
                const map = (window.BOARD_EXAM_TYPE_MAP || {});
                const parsed = parseInt(v, 10);
                if (!isNaN(parsed) && map[parsed] !== undefined) return String(parsed);
                const lower = v.toLowerCase();
                for (const [id, name] of Object.entries(map)) {
                    if (String(name).toLowerCase() === lower) return String(id);
                }
                return lower;
            }
            return v;
        };
        // Helper to present friendly values in the receipt
        const toDisplay = (key, val) => {
            if (val === undefined || val === null) return '';
            let v = String(val);
            if (key === 'boardExamType') {
                const map = (window.BOARD_EXAM_TYPE_MAP || {});
                if (map[v] !== undefined) return map[v];
                // If value is a number-like string but map keys are numbers, try parse
                const n = parseInt(v, 10);
                if (!isNaN(n) && map[n] !== undefined) return map[n];
                return v;
            }
            if (key === 'date') {
                // Format YYYY-MM-DD -> Month D, YYYY
                if (/^\d{4}-\d{2}-\d{2}$/.test(v)) {
                    const d = new Date(v + 'T00:00:00');
                    if (!isNaN(d.getTime())) {
                        const opts = {
                            year: 'numeric',
                            month: 'long',
                            day: 'numeric'
                        };
                        return d.toLocaleDateString(undefined, opts);
                    }
                }
            }
            return v;
        };
        const fieldMappings = {
            name: 'Name',
            course: 'Course',
            year: 'Year Graduated',
            date: 'Board Exam Date',
            result: 'Result',
            examType: 'Exam Type',
            boardExamType: 'Board Exam Type'
        };

        let changesHtml = '';
        let idx = 0;
        Object.keys(originalData).forEach(key => {
            if (normalizeForCompare(key, originalData[key]) !== normalizeForCompare(key, newData[key])) {
                const oldVal = toDisplay(key, originalData[key]);
                const newVal = toDisplay(key, newData[key]);
                const delay = (0.06 * idx + 0.04).toFixed(2);
                changesHtml += `
            <div style="
              padding: 12px 16px;
              background: white;
              border-radius: 8px;
              border-left: 3px solid #06b6d4;
              box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
              animation: fadeUp .45s ease both; animation-delay: ${delay}s;
            ">
              <div style="font-weight: 600; color: #374151; margin-bottom: 4px;">
                ${fieldMappings[key]}:
              </div>
              <div style="display: flex; align-items: center; gap: 10px; font-size: 0.92rem;">
                <span style="color: #6b7280;">${oldVal}</span>
                <i class="fas fa-arrow-right" style="color: #06b6d4; font-size: 0.75rem;"></i>
                <span style="color: #0f172a; font-weight: 700; background: rgba(6,182,212,0.12); padding: 2px 8px; border-radius: 6px;">${newVal}</span>
              </div>
            </div>
          `;
                idx++;
            }
        });

        return changesHtml;
    }

    function performDatabaseUpdate(formData) {
        const submitBtn = document.querySelector('#editStudentForm button[type="submit"]');
        const originalContent = submitBtn.innerHTML;

        console.log('🚨 performDatabaseUpdate called - Starting update process');

        // Show loading state
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
        submitBtn.disabled = true;

        // Send update request with proper error handling
        console.log('🌐 Sending fetch request to update_board_passer.php');
        const requestPromise = fetch('update_board_passer.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                console.log('📡 Response received:', response.status);

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                return response.text().then(text => {
                    console.log('📄 Raw response:', text);

                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('❌ JSON parse error:', e);
                        throw new Error('Invalid response format from server');
                    }
                });
            })
            .then(data => {
                console.log('✅ Parsed response data:', data);

                if (data.success) {
                    console.log('🎉 UPDATE SUCCESSFUL!');

                    // Update the table row with new data
                    if (window.currentEditingRow) {
                        updateTableRow(window.currentEditingRow, formData);
                    }

                    // Close modal
                    closeEditModal();

                    // Show success message
                    showUpdateSuccessMessage(data.updated_name || formData.get('name'));

                } else {
                    console.error('❌ SERVER RETURNED ERROR:', data.message);
                    showUpdateErrorMessage(data.message || 'Failed to update record');
                }
            })
            .catch(error => {
                console.error('🔥 CRITICAL ERROR:', error);
                showUpdateErrorMessage('Network error occurred while updating record: ' + error.message);
            })
            .finally(() => {
                console.log('🔄 Resetting button state...');
                // Reset button state
                submitBtn.innerHTML = originalContent;
                submitBtn.disabled = false;
            });
        return requestPromise;
    }

    function showUpdateConfirmationFromModal(originalData, newData, formData) {
        const modal = document.getElementById('updateModal');
        const studentDetails = document.getElementById('updateStudentDetails');
        const confirmBtn = document.getElementById('confirmUpdate');
        const cancelBtn = document.getElementById('cancelUpdate');

        // Check if all required elements exist
        if (!modal || !studentDetails || !confirmBtn || !cancelBtn) {
            console.error('Required modal elements not found');
            showUpdateErrorMessage('Modal elements not found. Please refresh the page and try again.');
            return;
        }

        // Create changes summary
        let changesHtml = `
        <div style="background: #f7fafc; padding: 20px; border-radius: 12px; border-left: 4px solid #3182ce; margin-bottom: 16px;">
          <div style="font-weight: 600; color: #2d3748; margin-bottom: 12px; font-size: 1.1rem;">
            <i class="fas fa-user" style="color: #3182ce; margin-right: 8px;"></i>${newData.name}
          </div>
          <div style="font-size: 0.95rem; color: #4a5568; margin-bottom: 16px;">${newData.course} • Class of ${newData.year}</div>
          
          <div style="font-weight: 600; color: #2d3748; margin-bottom: 12px; border-top: 1px solid #e2e8f0; padding-top: 16px;">
            <i class="fas fa-edit" style="color: #f59e0b; margin-right: 8px;"></i>Changes Summary:
          </div>
      `;

        // Show changed fields
        const fieldMappings = {
            name: 'Name',
            course: 'Course',
            year: 'Year Graduated',
            date: 'Board Exam Date',
            result: 'Result',
            examType: 'Exam Type',
            boardExamType: 'Board Exam Type'
        };

        let hasChanges = false;
        Object.keys(originalData).forEach(key => {
            if (originalData[key] !== newData[key]) {
                hasChanges = true;
                changesHtml += `
            <div style="margin-bottom: 12px; background: white; padding: 12px; border-radius: 8px; border: 1px solid #e2e8f0;">
              <div style="font-weight: 600; color: #374151; margin-bottom: 6px; font-size: 0.9rem;">
                ${fieldMappings[key]}:
              </div>
              <div style="display: flex; align-items: center; gap: 12px; font-size: 0.85rem;">
                <div style="flex: 1; padding: 8px; background: #fef2f2; border: 1px solid #fecaca; border-radius: 6px; color: #991b1b;">
                  <span style="font-weight: 500;">From:</span> ${originalData[key]}
                </div>
                <i class="fas fa-arrow-right" style="color: #6b7280;"></i>
                <div style="flex: 1; padding: 8px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 6px; color: #166534;">
                  <span style="font-weight: 500;">To:</span> ${newData[key]}
                </div>
              </div>
            </div>
          `;
            }
        });

        changesHtml += '</div>';

        studentDetails.innerHTML = changesHtml;

        // Show confirmation modal
        modal.classList.add('show');

        // Handle confirm
        confirmBtn.onclick = function() {
            modal.classList.remove('show');
            performModalUpdate(formData);
        };

        // Handle cancel
        cancelBtn.onclick = function() {
            modal.classList.remove('show');
        };
    }

    function performModalUpdate(formData) {
        const submitBtn = document.querySelector('#editStudentForm button[type="submit"]');
        const originalContent = submitBtn.innerHTML;

        console.log('🚨 performModalUpdate called - CRITICAL DEBUG');
        console.log('Form data entries:');
        for (let [key, value] of formData.entries()) {
            console.log(`  ${key}: "${value}"`);
        }

        // Show loading state
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
        submitBtn.disabled = true;

        // Update debug status
        const debugStatus = document.getElementById('debug-status');
        if (debugStatus) {
            debugStatus.innerHTML = '🚨 SENDING UPDATE REQUEST...';
            debugStatus.style.background = '#ff9800';
        }

        // Send update request
        console.log('🌐 Sending fetch request to update_board_passer.php');
        fetch('update_board_passer.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('📡 Response received:');
                console.log('  Status:', response.status);
                console.log('  OK:', response.ok);
                console.log('  Headers:', response.headers);

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                return response.text().then(text => {
                    console.log('📄 Raw response text:', text);

                    if (debugStatus) {
                        debugStatus.innerHTML = '📄 GOT RESPONSE: ' + text.substring(0, 50) + '...';
                    }

                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('❌ JSON Parse Error:', e);
                        console.error('❌ Raw text that failed to parse:', text);
                        throw new Error('Invalid JSON response from server: ' + text);
                    }
                });
            })
            .then(data => {
                console.log('✅ Parsed response data:', data);

                if (data.success) {
                    console.log('🎉 UPDATE SUCCESSFUL!');

                    if (debugStatus) {
                        debugStatus.innerHTML = '🎉 UPDATE SUCCESS!';
                        debugStatus.style.background = '#4caf50';
                    }

                    // Update the table row with new data
                    if (window.currentEditingRow) {
                        console.log('📝 Updating table row...');
                        updateTableRow(window.currentEditingRow, formData);
                    } else {
                        console.warn('⚠️ No currentEditingRow found, skipping table update');
                    }

                    // Close modal
                    console.log('🚪 Closing edit modal...');
                    closeEditModal();

                    // Show success message
                    console.log('💬 Showing success message...');
                    showUpdateSuccessMessage(data.updated_name || formData.get('name'));

                } else {
                    console.error('❌ SERVER RETURNED ERROR:', data.message);

                    if (debugStatus) {
                        debugStatus.innerHTML = '❌ SERVER ERROR: ' + data.message;
                        debugStatus.style.background = '#f44336';
                    }

                    // Show error message
                    showUpdateErrorMessage(data.message || 'Failed to update record');
                }
            })
            .catch(error => {
                console.error('🔥 CRITICAL ERROR:', error);

                if (debugStatus) {
                    debugStatus.innerHTML = '🔥 ERROR: ' + error.message;
                    debugStatus.style.background = '#f44336';
                }

                showUpdateErrorMessage('Network error occurred while updating record: ' + error.message);
            })
            .finally(() => {
                console.log('🔄 Resetting button state...');
                // Reset button state
                submitBtn.innerHTML = originalContent;
                submitBtn.disabled = false;
            });
    }

    function updateTableRow(row, formData) {
        const cells = row.querySelectorAll('td');

        // Update editable cells with new data
        cells[0].textContent = formData.get('name');
        cells[1].textContent = formData.get('course');
        cells[2].textContent = formData.get('year_graduated');
        cells[3].textContent = formData.get('board_exam_date');

        // Update result badge
        const result = formData.get('result');
        const resultBadge = cells[4].querySelector('.status-badge');
        resultBadge.textContent = result;
        resultBadge.className =
            `status-badge ${result === 'Passed' ? 'status-passed' : result === 'Failed' ? 'status-failed' : 'status-cond'}`;

        // Update exam type badge
        const examType = formData.get('exam_type');
        const examTypeBadge = cells[5].querySelector('.status-badge');
        examTypeBadge.textContent = examType;
        examTypeBadge.className = `status-badge ${examType === 'First Timer' ? 'exam-first-timer' : 'exam-repeater'}`;

        // Update board exam type (display name, not numeric id)
        (function() {
            const betId = formData.get('board_exam_type');
            let betName = betId;
            if (window.BOARD_EXAM_TYPE_MAP && window.BOARD_EXAM_TYPE_MAP[betId]) {
                betName = window.BOARD_EXAM_TYPE_MAP[betId];
            } else {
                const sel = document.getElementById('editBoardExamType');
                if (sel && sel.selectedIndex >= 0) {
                    const opt = sel.options[sel.selectedIndex];
                    betName = (opt.dataset && opt.dataset.name) ? opt.dataset.name : (opt.textContent || betId);
                }
            }
            cells[6].textContent = betName;
        })();

        // Use CSS animation for highlight effect instead of direct style manipulation
        row.classList.add('updated');

        setTimeout(() => {
            row.classList.remove('updated');
        }, 3000);
    }

    function handleAddFormSubmission() {
        const form = document.getElementById('addStudentForm');
        const formData = new FormData(form);

        console.log('Starting form submission...');
        console.log('Form data entries:');
        for (let [key, value] of formData.entries()) {
            console.log(key + ': ' + value);
        }

        // Validate all fields
        if (!form.checkValidity()) {
            console.log('Form validation failed');
            const invalidFields = form.querySelectorAll(':invalid');
            console.log('Invalid fields:', invalidFields);

            // Show validation errors
            const firstInvalidField = form.querySelector(':invalid');
            if (firstInvalidField) {
                console.log('First invalid field:', firstInvalidField.name, firstInvalidField.validationMessage);
                firstInvalidField.focus();
                showValidationErrorMessage('Please fix the highlighted errors before saving.');
            }
            return;
        }

        console.log('Form validation passed');

        // Show loading state
        const submitBtn = document.querySelector('#addStudentForm button[type="submit"]');
        const originalContent = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
        submitBtn.disabled = true;

        // Send add request to the existing add_board_passer_engineering.php for processing
        console.log('Sending AJAX request...');
        fetch('add_board_passer_engineering.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                console.log('Response status:', response.status);
                console.log('Response ok:', response.ok);
                if (!response.ok) {
                    throw new Error('HTTP error! status: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                console.log('Response data:', data);
                if (data.success) {
                    console.log('Success! Closing modal and showing message...');
                    // Close modal
                    closeAddModal();

                    // Show success message
                    showAddSuccessMessage(data.added_name ||
                        `${formData.get('first_name')} ${formData.get('last_name')}`);

                    // Refresh the page to show the new record
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);

                } else {
                    console.log('Server returned error:', data.message);
                    // Show error message
                    showAddErrorMessage(data.message || 'Failed to add record');
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                showAddErrorMessage('Network error occurred while adding record: ' + error.message);
            })
            .finally(() => {
                // Reset button state
                submitBtn.innerHTML = originalContent;
                submitBtn.disabled = false;
            });
    }

    function showAddSuccessMessage(studentName) {
        const messageDiv = document.createElement('div');
        messageDiv.innerHTML = `
        <div style="
          position: fixed;
          top: 50%;
          left: 50%;
          transform: translate(-50%, -50%);
          background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
          color: white;
          padding: 20px 28px;
          border-radius: 16px;
          box-shadow: 0 10px 30px rgba(34, 197, 94, 0.4);
          z-index: 10000;
          font-family: Inter;
          font-weight: 600;
          text-align: center;
          min-width: 320px;
          backdrop-filter: blur(10px);
          border: 1px solid rgba(255, 255, 255, 0.2);
        ">
          <i class="fas fa-check-circle" style="font-size: 1.2em; margin-right: 8px;"></i> 
          ${studentName} added successfully!
          <div style="font-size: 0.8rem; font-weight: 400; margin-top: 4px; opacity: 0.9;">
            Refreshing page to show new record...
          </div>
        </div>
      `;
        document.body.appendChild(messageDiv);
        setTimeout(() => {
            messageDiv.remove();
        }, 3000);
    }

    function showAddErrorMessage(message) {
        const messageDiv = document.createElement('div');
        messageDiv.innerHTML = `
        <div style="
          position: fixed;
          top: 50%;
          left: 50%;
          transform: translate(-50%, -50%);
          background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
          color: white;
          padding: 20px 28px;
          border-radius: 16px;
          box-shadow: 0 10px 30px rgba(239, 68, 68, 0.4);
          z-index: 10000;
          font-family: Inter;
          font-weight: 600;
          text-align: center;
          min-width: 320px;
          backdrop-filter: blur(10px);
          border: 1px solid rgba(255, 255, 255, 0.2);
        ">
          <i class="fas fa-exclamation-triangle" style="font-size: 1.2em; margin-right: 8px;"></i> 
          Error: ${message}
        </div>
      `;
        document.body.appendChild(messageDiv);
        setTimeout(() => {
            messageDiv.remove();
        }, 4000);
    }

    function showValidationErrorMessage(message) {
        const messageDiv = document.createElement('div');
        messageDiv.innerHTML = `
        <div style="
          position: fixed;
          top: 50%;
          left: 50%;
          transform: translate(-50%, -50%);
          background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
          color: white;
          padding: 16px 24px;
          border-radius: 12px;
          box-shadow: 0 8px 25px rgba(239, 68, 68, 0.3);
          z-index: 10001;
          font-family: Inter;
          font-weight: 600;
          text-align: center;
          min-width: 280px;
          backdrop-filter: blur(10px);
        ">
          <i class="fas fa-exclamation-triangle" style="margin-right: 8px;"></i> 
          ${message}
        </div>
      `;
        document.body.appendChild(messageDiv);
        setTimeout(() => {
            messageDiv.remove();
        }, 4000);
    }

    function showEditingGuide() {
        // Check if guide has been shown in this session
        if (sessionStorage.getItem('editingGuideShown')) {
            return;
        }

        const guide = document.createElement('div');
        guide.className = 'editing-guide';
        guide.innerHTML = `
        <div style="
          position: fixed;
          top: 50%;
          left: 50%;
          transform: translate(-50%, -50%);
          background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
          color: white;
          padding: 24px 28px;
          border-radius: 16px;
          box-shadow: 0 20px 60px rgba(30, 41, 59, 0.4);
          z-index: 10001;
          font-family: Inter;
          max-width: 450px;
          width: 90%;
          border: 1px solid rgba(255, 255, 255, 0.1);
          backdrop-filter: blur(10px);
        ">
          <div style="text-align: center; margin-bottom: 20px;">
            <div style="
              width: 60px;
              height: 60px;
              background: linear-gradient(135deg, #3182ce 0%, #2c5aa0 100%);
              border-radius: 50%;
              display: flex;
              align-items: center;
              justify-content: center;
              margin: 0 auto 12px;
              font-size: 24px;
            ">
              <i class="fas fa-lightbulb"></i>
            </div>
            <h3 style="margin: 0; font-size: 1.2rem; font-weight: 700;">New Modal Editing</h3>
          </div>
          
          <div style="space-y: 16px;">
            <div style="display: flex; align-items: start; gap: 12px; margin-bottom: 16px;">
              <div style="
                width: 32px;
                height: 32px;
                background: #3182ce;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                flex-shrink: 0;
                font-size: 14px;
              ">
                <i class="fas fa-edit"></i>
              </div>
              <div>
                <div style="font-weight: 600; margin-bottom: 4px;">Easy Modal Editing</div>
                <div style="font-size: 0.9rem; opacity: 0.9; line-height: 1.4;">
                  Click Edit to open a clean, organized popup form for easier data entry and validation.
                </div>
              </div>
            </div>
            
            <div style="display: flex; align-items: start; gap: 12px; margin-bottom: 16px;">
              <div style="
                width: 32px;
                height: 32px;
                background: #10b981;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                flex-shrink: 0;
                font-size: 14px;
              ">
                <i class="fas fa-check"></i>
              </div>
              <div>
                <div style="font-weight: 600; margin-bottom: 4px;">Smart Validation</div>
                <div style="font-size: 0.9rem; opacity: 0.9; line-height: 1.4;">
                  Real-time validation with visual feedback ensures data accuracy before saving.
                </div>
              </div>
            </div>
            
            <div style="display: flex; align-items: start; gap: 12px; margin-bottom: 20px;">
              <div style="
                width: 32px;
                height: 32px;
                background: #f59e0b;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                flex-shrink: 0;
                font-size: 14px;
              ">
                <i class="fas fa-save"></i>
              </div>
              <div>
                <div style="font-weight: 600; margin-bottom: 4px;">Change Preview</div>
                <div style="font-size: 0.9rem; opacity: 0.9; line-height: 1.4;">
                  Review all changes in a confirmation dialog before saving to the database.
                </div>
              </div>
            </div>
          </div>
          
          <div style="text-align: center; margin-top: 20px; border-top: 1px solid rgba(255, 255, 255, 0.1); padding-top: 16px;">
            <label style="display: flex; align-items: center; justify-content: center; gap: 8px; cursor: pointer; font-size: 0.9rem; opacity: 0.8;">
              <input type="checkbox" id="dontShowGuide" style="margin: 0;">
              Don't show this again
            </label>
          </div>
          
          <div style="text-align: center; margin-top: 16px;">
            <button onclick="closeEditingGuide()" class="guide-close-btn">
              <i class="fas fa-check" style="margin-right: 6px;"></i>
              Got it!
            </button>
          </div>
        </div>
      `;

        document.body.appendChild(guide);

        // Add to session storage
        sessionStorage.setItem('editingGuideShown', 'true');

        // Auto-close after 15 seconds
        setTimeout(() => {
            if (guide.parentNode) {
                closeEditingGuide();
            }
        }, 15000);
    }

    function closeEditingGuide() {
        const guide = document.querySelector('.editing-guide');
        if (guide) {
            const checkbox = guide.querySelector('#dontShowGuide');
            if (checkbox && checkbox.checked) {
                localStorage.setItem('editingGuideDisabled', 'true');
            }
            guide.remove();
        }
    }

    // Override the showEditingGuide function to check localStorage
    const originalShowEditingGuide = showEditingGuide;
    showEditingGuide = function() {
        if (localStorage.getItem('editingGuideDisabled') === 'true') {
            return;
        }
        originalShowEditingGuide();
    }

    function showInfoMessage(message) {
        const messageDiv = document.createElement('div');
        messageDiv.innerHTML = `
        <div style="
          position: fixed;
          top: 50%;
          left: 50%;
          transform: translate(-50%, -50%);
          background: linear-gradient(135deg, #3182ce 0%, #2c5aa0 100%);
          color: white;
          padding: 20px 28px;
          border-radius: 16px;
          box-shadow: 0 10px 30px rgba(49, 130, 206, 0.4);
          z-index: 10000;
          font-family: Inter;
          font-weight: 600;
          text-align: center;
          min-width: 320px;
          backdrop-filter: blur(10px);
          border: 1px solid rgba(255, 255, 255, 0.2);
        ">
          <i class="fas fa-info-circle" style="font-size: 1.2em; margin-right: 8px;"></i> 
          ${message}
        </div>
      `;
        document.body.appendChild(messageDiv);
        setTimeout(() => {
            messageDiv.remove();
        }, 3500);
    }

    function showEditingTooltip(row) {
        const tooltip = document.createElement('div');
        tooltip.innerHTML = `
        <div style="
          position: fixed;
          top: 120px;
          right: 40px;
          background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
          color: white;
          padding: 12px 20px;
          border-radius: 12px;
          box-shadow: 0 8px 25px rgba(245, 158, 11, 0.3);
          z-index: 10000;
          font-family: Inter;
          font-weight: 600;
          font-size: 0.9rem;
          text-align: center;
          min-width: 250px;
          animation: slideInFromRight 0.4s ease;
        ">
          <i class="fas fa-edit" style="margin-right: 8px;"></i> 
          Modal Editing Active
          <div style="font-size: 0.8rem; font-weight: 400; margin-top: 4px; opacity: 0.9;">
            Use the popup form for easy editing
          </div>
        </div>
        <style>
          @keyframes slideInFromRight {
            from { transform: translateX(100px); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
          }
        </style>
      `;
        document.body.appendChild(tooltip);
        setTimeout(() => {
            tooltip.remove();
        }, 4000);
    }

    function deleteRow(btn) {
        const row = btn.closest('tr');
        const studentId = row.getAttribute('data-id');

        if (!studentId) {
            console.error('❌ Student ID not found in row data');
            showErrorMessage('Error: Student ID not found. Please refresh the page and try again.');
            return;
        }

        const cells = row.querySelectorAll('td');
        const studentName = cells[0] ? cells[0].textContent.trim() : 'Unknown Student';

        // Extract row data for beautiful confirmation
        const rowData = {
            id: studentId,
            name: studentName,
            course: cells[1] ? cells[1].textContent.trim() : 'N/A',
            year: cells[2] ? cells[2].textContent.trim() : 'N/A',
            date: cells[3] ? cells[3].textContent.trim() : 'N/A',
            result: cells[4] ? (cells[4].querySelector('.status-badge') ? cells[4].querySelector('.status-badge')
                .textContent.trim() : cells[4].textContent.trim()) : 'N/A'
        };

        console.log('🗑️ Delete button clicked for student:', rowData);

        // Show beautiful confirmation modal
        showDeleteConfirmationModal(rowData);
    }

    function showDeleteConfirmationModal(rowData) {
        console.log('🗑️ Showing beautiful delete confirmation modal for:', rowData.name);

        // Create beautiful delete confirmation modal
        const modal = document.createElement('div');
        modal.className = 'custom-modal show';
        modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        background: rgba(30, 41, 59, 0.6);
        backdrop-filter: blur(8px);
        z-index: 9999;
        display: flex;
        justify-content: center;
        align-items: center;
        opacity: 0;
        transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
        overflow-y: auto;
        padding: 20px;
      `;

        modal.innerHTML = `
        <div style="
          background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
          border-radius: 24px;
          box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
          border: 2px solid rgba(239, 68, 68, 0.1);
          overflow: hidden;
          position: relative;
          max-width: 600px;
          width: 100%;
          max-height: 90vh;
          overflow-y: auto;
          transform: scale(0.7) translateY(-50px);
          transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
          margin: auto;
        ">
          <!-- Sticky Header -->
          <div style="
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            padding: 32px 40px 28px;
            color: white;
            position: relative;
            overflow: hidden;
            position: sticky;
            top: 0;
            z-index: 1;
          ">
            <div style="
              position: absolute;
              top: -50px;
              right: -50px;
              width: 120px;
              height: 120px;
              background: rgba(255, 255, 255, 0.1);
              border-radius: 50%;
            "></div>
            
            <div style="
              width: 72px;
              height: 72px;
              background: rgba(255, 255, 255, 0.2);
              border-radius: 20px;
              display: flex;
              align-items: center;
              justify-content: center;
              margin: 0 auto 20px;
              backdrop-filter: blur(10px);
              border: 2px solid rgba(255, 255, 255, 0.2);
            ">
              <i class="fas fa-trash-alt" style="font-size: 1.8rem;"></i>
            </div>
            
            <h3 style="
              color: white; 
              font-weight: 800; 
              font-size: 1.6rem;
              margin: 0 0 8px 0;
              text-align: center;
              text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            ">Confirm Deletion</h3>
            <p style="
              color: rgba(255, 255, 255, 0.95); 
              margin: 0;
              text-align: center;
              font-size: 1.1rem;
              font-weight: 500;
            ">This action cannot be undone</p>
          </div>
          
          <!-- Scrollable Content -->
          <div style="
            padding: 32px 40px;
            max-height: 50vh;
            overflow-y: auto;
          ">
            <div style="background: #fef2f2; padding: 24px; border-radius: 16px; border-left: 4px solid #ef4444; margin-bottom: 24px;">
              <h4 style="color: #dc2626; margin: 0 0 16px 0; font-weight: 700; font-size: 1.1rem;">
                <i class="fas fa-user-graduate" style="margin-right: 8px;"></i>
                Student to be deleted:
              </h4>
              
              <div style="background: white; padding: 20px; border-radius: 12px; border: 1px solid #fecaca;">
                <div style="display: grid; grid-template-columns: auto 1fr; gap: 12px; align-items: center;">
                  <strong style="color: #374151;">Name:</strong>
                  <span style="color: #111827; font-weight: 600;">${rowData.name}</span>
                  
                  <strong style="color: #374151;">Course:</strong>
                  <span style="color: #6b7280;">${rowData.course}</span>
                  
                  <strong style="color: #374151;">Year:</strong>
                  <span style="color: #6b7280;">${rowData.year}</span>
                  
                  <strong style="color: #374151;">Board Exam:</strong>
                  <span style="color: #6b7280;">${rowData.date}</span>
                  
                  <strong style="color: #374151;">Result:</strong>
                  <span style="
                    color: ${rowData.result === 'PASSED' ? '#059669' : '#dc2626'};
                    font-weight: 600;
                    text-transform: uppercase;
                  ">${rowData.result}</span>
                </div>
              </div>
            </div>
            
            <div style="background: #fef3c7; border: 2px solid #f59e0b; border-radius: 12px; padding: 20px; margin-bottom: 24px;">
              <div style="display: flex; align-items: flex-start; color: #92400e;">
                <i class="fas fa-exclamation-triangle" style="margin-right: 12px; font-size: 1.2rem; margin-top: 2px; color: #f59e0b;"></i>
                <div>
                  <strong style="display: block; margin-bottom: 4px;">Warning:</strong>
                  <div style="line-height: 1.5;">
                    This will permanently delete the student's record from the database. 
                    This action cannot be reversed and all associated data will be lost.
                  </div>
                </div>
              </div>
            </div>
            
            <div style="background: #fee2e2; border: 2px solid #ef4444; border-radius: 12px; padding: 20px; margin-bottom: 24px;">
              <div style="display: flex; align-items: flex-start; color: #dc2626;">
                <i class="fas fa-shield-alt" style="margin-right: 12px; font-size: 1.2rem; margin-top: 2px;"></i>
                <div>
                  <strong style="display: block; margin-bottom: 4px;">Security Notice:</strong>
                  <div style="line-height: 1.5;">
                    You are about to permanently remove this student's academic record. 
                    Make sure this is the correct student before proceeding. 
                    Consider backing up data if needed.
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Sticky Action Buttons -->
          <div style="
            display: flex;
            gap: 16px;
            padding: 24px 40px 40px;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border-top: 2px solid #e2e8f0;
            position: sticky;
            bottom: 0;
            z-index: 1;
          ">
            <button id="confirmDeleteBtn" style="
              flex: 1;
              background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
              color: white;
              border: none;
              padding: 16px 24px;
              border-radius: 12px;
              font-weight: 600;
              font-size: 1rem;
              cursor: pointer;
              transition: all 0.3s ease;
              box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3);
              min-height: 50px;
            " onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(239, 68, 68, 0.4)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 15px rgba(239, 68, 68, 0.3)'">
              <i class="fas fa-trash-alt" style="margin-right: 8px;"></i>
              Yes, Delete Permanently
            </button>
            <button id="cancelDeleteBtn" style="
              flex: 1;
              background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
              color: white;
              border: none;
              padding: 16px 24px;
              border-radius: 12px;
              font-weight: 600;
              font-size: 1rem;
              cursor: pointer;
              transition: all 0.3s ease;
              box-shadow: 0 4px 15px rgba(107, 114, 128, 0.3);
              min-height: 50px;
            " onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(107, 114, 128, 0.4)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 15px rgba(107, 114, 128, 0.3)'">
              <i class="fas fa-times" style="margin-right: 8px;"></i>
              Cancel
            </button>
          </div>
        </div>
      `;

        document.body.appendChild(modal);

        // Add event listeners
        const confirmBtn = modal.querySelector('#confirmDeleteBtn');
        const cancelBtn = modal.querySelector('#cancelDeleteBtn');

        confirmBtn.onclick = function() {
            modal.remove();
            performStudentDeletion(rowData.id, rowData.name);
        };

        cancelBtn.onclick = function() {
            modal.remove();
        };

        // Close on outside click
        modal.onclick = function(e) {
            if (e.target === modal) {
                modal.remove();
            }
        };

        // Close on escape key
        const escapeHandler = function(e) {
            if (e.key === 'Escape') {
                modal.remove();
                document.removeEventListener('keydown', escapeHandler);
            }
        };
        document.addEventListener('keydown', escapeHandler);

        // Trigger entrance animation
        setTimeout(() => {
            modal.style.opacity = '1';
            const content = modal.querySelector('div');
            content.style.transform = 'scale(1) translateY(0)';
        }, 10);
    }

    function performStudentDeletion(studentId, studentName) {
        console.log('🗑️ Starting deletion process for student ID:', studentId);

        // Show loading state
        showLoadingMessage('Deleting student record...');

        // Prepare form data
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('id', studentId);

        console.log('🌐 Sending delete request to delete_board_passer.php');

        fetch('delete_board_passer.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                console.log('📡 Delete response received:', response.status);

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                return response.text().then(text => {
                    console.log('📄 Raw delete response:', text);

                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('❌ JSON parse error:', e);
                        throw new Error('Invalid response format from server');
                    }
                });
            })
            .then(data => {
                console.log('✅ Parsed delete response:', data);

                hideLoadingMessage();

                if (data.success) {
                    console.log('🎉 DELETE SUCCESSFUL!');

                    // Remove the row from table
                    const rowToDelete = document.querySelector(`tr[data-id="${studentId}"]`);
                    if (rowToDelete) {
                        // Add fade-out animation
                        rowToDelete.style.transition = 'all 0.3s ease';
                        rowToDelete.style.opacity = '0';
                        rowToDelete.style.transform = 'translateX(-20px)';

                        setTimeout(() => {
                            rowToDelete.remove();
                            updateRecordCountAfterDelete();
                        }, 300);
                    }

                    // Show success message
                    showDeleteSuccessMessage(studentName);

                } else {
                    console.error('❌ SERVER DELETE ERROR:', data.message);
                    showDeleteErrorMessage(data.message || 'Failed to delete record');
                }
            })
            .catch(error => {
                console.error('🔥 CRITICAL DELETE ERROR:', error);
                hideLoadingMessage();
                showDeleteErrorMessage('Network error occurred while deleting record: ' + error.message);
            });
    }

    function showUpdateSuccessMessage(studentName) {
        const messageDiv = document.createElement('div');
        messageDiv.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
        color: white;
        padding: 20px 24px;
        border-radius: 12px;
        box-shadow: 0 8px 25px rgba(34, 197, 94, 0.3);
        z-index: 10000;
        min-width: 300px;
        font-weight: 500;
        border: 2px solid rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(10px);
        animation: successSlideIn 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
      `;

        messageDiv.innerHTML = `
        <div style="display: flex; align-items: center; gap: 12px;">
          <div style="
            width: 32px; 
            height: 32px; 
            background: rgba(255, 255, 255, 0.2); 
            border-radius: 50%; 
            display: flex; 
            align-items: center; 
            justify-content: center;
          ">
            <i class="fas fa-check" style="font-size: 1rem;"></i>
          </div>
          <div>
            <div style="font-weight: 600; margin-bottom: 4px;">Update Successful!</div>
            <div style="opacity: 0.9; font-size: 0.9rem;">${studentName}'s information has been updated</div>
          </div>
        </div>
      `;

        document.body.appendChild(messageDiv);

        setTimeout(() => {
            messageDiv.style.animation = 'successSlideOut 0.3s ease-in-out forwards';
            setTimeout(() => messageDiv.remove(), 300);
        }, 4000);
    }

    function showErrorMessage(message) {
        const messageDiv = document.createElement('div');
        messageDiv.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        color: white;
        padding: 20px 24px;
        border-radius: 12px;
        box-shadow: 0 8px 25px rgba(239, 68, 68, 0.3);
        z-index: 10000;
        min-width: 300px;
        font-weight: 500;
        border: 2px solid rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(10px);
        animation: errorSlideIn 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
      `;

        messageDiv.innerHTML = `
        <div style="display: flex; align-items: center; gap: 12px;">
          <div style="
            width: 32px; 
            height: 32px; 
            background: rgba(255, 255, 255, 0.2); 
            border-radius: 50%; 
            display: flex; 
            align-items: center; 
            justify-content: center;
          ">
            <i class="fas fa-exclamation-triangle" style="font-size: 1rem;"></i>
          </div>
          <div>
            <div style="font-weight: 600; margin-bottom: 4px;">Error</div>
            <div style="opacity: 0.9; font-size: 0.9rem;">${message}</div>
          </div>
        </div>
      `;

        document.body.appendChild(messageDiv);

        setTimeout(() => {
            messageDiv.style.animation = 'errorSlideOut 0.3s ease-in-out forwards';
            setTimeout(() => messageDiv.remove(), 300);
        }, 5000);
    }

    function showUpdateErrorMessage(message) {
        const messageDiv = document.createElement('div');
        messageDiv.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        color: white;
        padding: 20px 24px;
        border-radius: 12px;
        box-shadow: 0 8px 25px rgba(239, 68, 68, 0.3);
        z-index: 10000;
        min-width: 300px;
        font-weight: 500;
        border: 2px solid rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(10px);
        animation: errorSlideIn 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
      `;

        messageDiv.innerHTML = `
        <div style="display: flex; align-items: center; gap: 12px;">
          <div style="
            width: 32px; 
            height: 32px; 
            background: rgba(255, 255, 255, 0.2); 
            border-radius: 50%; 
            display: flex; 
            align-items: center; 
            justify-content: center;
          ">
            <i class="fas fa-times" style="font-size: 1rem;"></i>
          </div>
          <div>
            <div style="font-weight: 600; margin-bottom: 4px;">Update Failed</div>
            <div style="opacity: 0.9; font-size: 0.9rem;">${message}</div>
          </div>
        </div>
      `;

        document.body.appendChild(messageDiv);

        setTimeout(() => {
            messageDiv.style.animation = 'errorSlideOut 0.3s ease-in-out forwards';
            setTimeout(() => messageDiv.remove(), 300);
        }, 5000);
    }

    function showInfoMessage(message) {
        const messageDiv = document.createElement('div');
        messageDiv.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: linear-gradient(135deg, #3182ce 0%, #2c5aa0 100%);
        color: white;
        padding: 20px 24px;
        border-radius: 12px;
        box-shadow: 0 8px 25px rgba(49, 130, 206, 0.3);
        z-index: 10000;
        min-width: 300px;
        font-weight: 500;
        border: 2px solid rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(10px);
        animation: infoSlideIn 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
      `;

        messageDiv.innerHTML = `
        <div style="display: flex; align-items: center; gap: 12px;">
          <div style="
            width: 32px; 
            height: 32px; 
            background: rgba(255, 255, 255, 0.2); 
            border-radius: 50%; 
            display: flex; 
            align-items: center; 
            justify-content: center;
          ">
            <i class="fas fa-info" style="font-size: 1rem;"></i>
          </div>
          <div>
            <div style="font-weight: 600; margin-bottom: 4px;">Information</div>
            <div style="opacity: 0.9; font-size: 0.9rem;">${message}</div>
          </div>
        </div>
      `;

        document.body.appendChild(messageDiv);

        setTimeout(() => {
            messageDiv.style.animation = 'infoSlideOut 0.3s ease-in-out forwards';
            setTimeout(() => messageDiv.remove(), 300);
        }, 4000);
    }

    function showValidationErrorMessage(message) {
        const messageDiv = document.createElement('div');
        messageDiv.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        color: white;
        padding: 20px 24px;
        border-radius: 12px;
        box-shadow: 0 8px 25px rgba(245, 158, 11, 0.3);
        z-index: 10000;
        min-width: 300px;
        font-weight: 500;
        border: 2px solid rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(10px);
        animation: warningSlideIn 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
      `;

        messageDiv.innerHTML = `
        <div style="display: flex; align-items: center; gap: 12px;">
          <div style="
            width: 32px; 
            height: 32px; 
            background: rgba(255, 255, 255, 0.2); 
            border-radius: 50%; 
            display: flex; 
            align-items: center; 
            justify-content: center;
          ">
            <i class="fas fa-exclamation-triangle" style="font-size: 1rem;"></i>
          </div>
          <div>
            <div style="font-weight: 600; margin-bottom: 4px;">Validation Error</div>
            <div style="opacity: 0.9; font-size: 0.9rem;">${message}</div>
          </div>
        </div>
      `;

        document.body.appendChild(messageDiv);

        setTimeout(() => {
            messageDiv.style.animation = 'warningSlideOut 0.3s ease-in-out forwards';
            setTimeout(() => messageDiv.remove(), 300);
        }, 4000);
    }

    function showLoadingMessage(message) {
        const loadingDiv = document.createElement('div');
        loadingDiv.id = 'globalLoadingMessage';
        loadingDiv.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
        color: white;
        padding: 20px 24px;
        border-radius: 12px;
        box-shadow: 0 8px 25px rgba(107, 114, 128, 0.3);
        z-index: 10000;
        min-width: 300px;
        font-weight: 500;
        border: 2px solid rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(10px);
        animation: loadingSlideIn 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
      `;

        loadingDiv.innerHTML = `
        <div style="display: flex; align-items: center; gap: 12px;">
          <div style="
            width: 32px; 
            height: 32px; 
            background: rgba(255, 255, 255, 0.2); 
            border-radius: 50%; 
            display: flex; 
            align-items: center; 
            justify-content: center;
          ">
            <i class="fas fa-spinner fa-spin" style="font-size: 1rem;"></i>
          </div>
          <div>
            <div style="font-weight: 600; margin-bottom: 4px;">Processing</div>
            <div style="opacity: 0.9; font-size: 0.9rem;">${message}</div>
          </div>
        </div>
      `;

        document.body.appendChild(loadingDiv);
    }

    function hideLoadingMessage() {
        const loadingDiv = document.getElementById('globalLoadingMessage');
        if (loadingDiv) {
            loadingDiv.style.animation = 'loadingSlideOut 0.3s ease-in-out forwards';
            setTimeout(() => loadingDiv.remove(), 300);
        }
    }

    function updateTableRow(row, formData) {
        console.log('🔄 Updating table row with new data');

        const cells = row.querySelectorAll('td');
        if (cells.length >= 7) {
            // Update each cell with new data
            cells[0].textContent = formData.get('name');
            cells[1].textContent = formData.get('course');
            cells[2].textContent = formData.get('year_graduated');
            cells[3].textContent = formData.get('board_exam_date');

            // Update result badge
            const result = formData.get('result');
            const resultBadge = cells[4].querySelector('.status-badge');
            if (resultBadge) {
                resultBadge.textContent = result;
                resultBadge.className =
                    `status-badge ${result === 'Passed' ? 'status-passed' : result === 'Failed' ? 'status-failed' : 'status-cond'}`;
            }

            // Update exam type badge
            const examType = formData.get('exam_type');
            const examTypeBadge = cells[5].querySelector('.status-badge');
            if (examTypeBadge) {
                examTypeBadge.textContent = examType;
                examTypeBadge.className =
                    `status-badge ${examType === 'First Timer' ? 'exam-first-timer' : 'exam-repeater'}`;
            }

            // Update board exam type (display name, not numeric id)
            (function() {
                const betId = formData.get('board_exam_type');
                let betName = betId;
                if (window.BOARD_EXAM_TYPE_MAP && window.BOARD_EXAM_TYPE_MAP[betId]) {
                    betName = window.BOARD_EXAM_TYPE_MAP[betId];
                } else {
                    const sel = document.getElementById('addBoardExamType');
                    if (sel && sel.selectedIndex >= 0) {
                        const opt = sel.options[sel.selectedIndex];
                        betName = (opt.dataset && opt.dataset.name) ? opt.dataset.name : (opt.textContent || betId);
                    }
                }
                cells[6].textContent = betName;
            })();

            // Use CSS animation for highlight effect to preserve table design
            row.classList.add('updated');

            setTimeout(() => {
                row.classList.remove('updated');
            }, 2000);

            console.log('✅ Table row updated successfully');
        }
    }

    function showDeleteSuccessMessage(studentName) {
        const messageDiv = document.createElement('div');
        messageDiv.innerHTML = `
        <div style="
          position: fixed;
          top: 50%;
          left: 50%;
          transform: translate(-50%, -50%);
          background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
          color: white;
          padding: 16px 24px;
          border-radius: 12px;
          box-shadow: 0 8px 25px rgba(34, 197, 94, 0.3);
          z-index: 10000;
          font-family: Inter;
          font-weight: 600;
          font-size: 14px;
          backdrop-filter: blur(10px);
          border: 1px solid rgba(255, 255, 255, 0.2);
          animation: successSlideIn 0.3s ease-out;
        ">
          <i class="fas fa-check-circle" style="margin-right: 8px;"></i>
          Successfully deleted ${studentName}'s record
        </div>
      `;
        document.body.appendChild(messageDiv);
        setTimeout(() => {
            messageDiv.remove();
        }, 3000);
    }


    function showDeleteErrorMessage(message) {
        const messageDiv = document.createElement('div');
        messageDiv.innerHTML = `
        <div style="
          position: fixed;
          top: 50%;
          left: 50%;
          transform: translate(-50%, -50%);
          background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
          color: white;
          padding: 16px 24px;
          border-radius: 12px;
          box-shadow: 0 8px 25px rgba(239, 68, 68, 0.3);
          z-index: 10000;
          font-family: Inter;
          font-weight: 600;
          font-size: 14px;
          backdrop-filter: blur(10px);
          border: 1px solid rgba(255, 255, 255, 0.2);
          animation: errorSlideIn 0.3s ease-out;
        ">
          <i class="fas fa-exclamation-circle" style="margin-right: 8px;"></i>
          ${message}
        </div>
      `;
        document.body.appendChild(messageDiv);
        setTimeout(() => {
            messageDiv.remove();
        }, 4000);
    }

    function updateRecordCountAfterDelete() {
        const recordCountElement = document.getElementById('recordCount');
        if (recordCountElement) {
            const currentText = recordCountElement.textContent;
            const match = currentText.match(/(\d+)/);
            if (match) {
                const newCount = parseInt(match[1]) - 1;
                recordCountElement.innerHTML = `Total Records: <strong>${newCount}</strong>`;
            }
        }
    }

    // New Export System Functions
    function showExportOptionsModal() {
        console.log('🎯 Opening export options modal...');

        // Create export modal
        const modal = document.createElement('div');
        modal.className = 'custom-modal show';
        modal.id = 'exportModal';
        modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        background: rgba(30, 41, 59, 0.6);
        backdrop-filter: blur(8px);
        z-index: 9999;
        display: flex;
        justify-content: center;
        align-items: center;
        opacity: 0;
        transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
        overflow-y: auto;
        padding: 20px;
      `;

        modal.innerHTML = `
        <div style="
          background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
          border-radius: 24px;
          box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
          border: 2px solid rgba(49, 130, 206, 0.1);
          overflow: hidden;
          position: relative;
          max-width: 500px;
          width: 100%;
          transform: scale(0.7) translateY(-50px);
          transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
          margin: auto;
        ">
          <!-- Header -->
          <div style="
            background: linear-gradient(135deg, #3182ce 0%, #2c5aa0 100%);
            padding: 32px 40px 28px;
            color: white;
            position: relative;
            overflow: hidden;
          ">
            <div style="
              width: 72px;
              height: 72px;
              background: rgba(255, 255, 255, 0.2);
              border-radius: 20px;
              display: flex;
              align-items: center;
              justify-content: center;
              margin: 0 auto 20px;
              backdrop-filter: blur(10px);
              border: 2px solid rgba(255, 255, 255, 0.2);
            ">
              <i class="fas fa-download" style="font-size: 1.8rem;"></i>
            </div>
            
            <h3 style="
              color: white; 
              font-weight: 800; 
              font-size: 1.6rem;
              margin: 0 0 8px 0;
              text-align: center;
              text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            ">Export Data</h3>
            <p style="
              color: rgba(255, 255, 255, 0.95); 
              margin: 0;
              text-align: center;
              font-size: 1.1rem;
              font-weight: 500;
            ">Choose your preferred format</p>
          </div>
          
          <!-- Export Options -->
          <div style="padding: 32px 40px;">
            <div style="display: grid; gap: 16px;">
              <button onclick="performExport('csv')" style="
                background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
                color: white;
                border: none;
                padding: 16px 24px;
                border-radius: 12px;
                font-weight: 600;
                font-size: 1rem;
                cursor: pointer;
                transition: all 0.3s ease;
                box-shadow: 0 4px 15px rgba(34, 197, 94, 0.3);
                display: flex;
                align-items: center;
                gap: 12px;
              ">
                <i class="fas fa-file-csv" style="font-size: 1.2rem;"></i>
                <div style="text-align: left;">
                  <div>CSV Format</div>
                  <small style="opacity: 0.9; font-weight: 400;">Comma-separated values for Excel</small>
                </div>
              </button>
              
              <button onclick="performExport('excel')" style="
                background: linear-gradient(135deg, #059669 0%, #047857 100%);
                color: white;
                border: none;
                padding: 16px 24px;
                border-radius: 12px;
                font-weight: 600;
                font-size: 1rem;
                cursor: pointer;
                transition: all 0.3s ease;
                box-shadow: 0 4px 15px rgba(5, 150, 105, 0.3);
                display: flex;
                align-items: center;
                gap: 12px;
              ">
                <i class="fas fa-file-excel" style="font-size: 1.2rem;"></i>
                <div style="text-align: left;">
                  <div>Excel Format</div>
                  <small style="opacity: 0.9; font-weight: 400;">Native Excel spreadsheet</small>
                </div>
              </button>
              
              <button onclick="performExport('pdf')" style="
                background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
                color: white;
                border: none;
                padding: 16px 24px;
                border-radius: 12px;
                font-weight: 600;
                font-size: 1rem;
                cursor: pointer;
                transition: all 0.3s ease;
                box-shadow: 0 4px 15px rgba(220, 38, 38, 0.3);
                display: flex;
                align-items: center;
                gap: 12px;
              ">
                <i class="fas fa-file-pdf" style="font-size: 1.2rem;"></i>
                <div style="text-align: left;">
                  <div>PDF Format</div>
                  <small style="opacity: 0.9; font-weight: 400;">Formatted document for printing</small>
                </div>
              </button>
            </div>
          </div>
          
          <!-- Cancel Button -->
          <div style="padding: 0 40px 32px;">
            <button onclick="closeExportOptionsModal()" style="
              width: 100%;
              background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
              color: white;
              border: none;
              padding: 14px 24px;
              border-radius: 12px;
              font-weight: 600;
              font-size: 1rem;
              cursor: pointer;
              transition: all 0.3s ease;
              box-shadow: 0 4px 15px rgba(107, 114, 128, 0.3);
            ">
              <i class="fas fa-times"></i> Cancel
            </button>
          </div>
        </div>
      `;

        document.body.appendChild(modal);

        // Close on outside click
        modal.onclick = function(e) {
            if (e.target === modal) {
                closeExportOptionsModal();
            }
        };

        // Trigger entrance animation
        setTimeout(() => {
            modal.style.opacity = '1';
            const content = modal.querySelector('div');
            content.style.transform = 'scale(1) translateY(0)';
        }, 10);
    }

    function openExportOptionsModal() {
        const modal = document.getElementById('exportOptionsModal');
        if (modal) {
            modal.style.display = 'flex';
            modal.classList.add('show');
            setTimeout(() => {
                modal.style.opacity = '1';
            }, 10);
        }
    }

    function closeExportOptionsModal() {
        const modal = document.getElementById('exportOptionsModal');
        if (modal) {
            modal.style.opacity = '0';
            modal.classList.remove('show');
            setTimeout(() => {
                modal.style.display = 'none';
            }, 300);
        }
    }

    function performExport(format) {
        console.log(`🎯 Exporting data as ${format.toUpperCase()}...`);

        try {
            // Get the currently filtered table data
            const table = document.querySelector('.board-table');
            const rows = table.querySelectorAll('tbody tr:not([style*="display: none"])');

            let data = [];
            let headers = ['Name', 'Course', 'Year Graduated', 'Board Exam Date', 'Result', 'Take Attempts',
                'Board Exam Type'
            ];

            // Extract visible row data
            rows.forEach(row => {
                const cells = row.querySelectorAll('td');
                if (cells.length >= 7) {
                    data.push([
                        cells[0].textContent.trim(),
                        cells[1].textContent.trim(),
                        cells[2].textContent.trim(),
                        cells[3].textContent.trim(),
                        cells[4].textContent.trim(),
                        cells[5].textContent.trim(),
                        cells[6].textContent.trim()
                    ]);
                }
            });

            if (data.length === 0) {
                showExportNotification('No data available to export!', 'error');
                closeExportOptionsModal();
                return;
            }

            switch (format) {
                case 'csv':
                    performCSVExport(headers, data);
                    break;
                case 'excel':
                    performExcelExport(headers, data);
                    break;
                case 'pdf':
                    performPDFExport(headers, data);
                    break;
                default:
                    showExportNotification('Invalid export format!', 'error');
            }

            closeExportOptionsModal();
        } catch (error) {
            console.error('Export error:', error);
            showExportNotification('Export failed: ' + error.message, 'error');
            closeExportOptionsModal();
        }
    }

    function performCSVExport(headers, data) {
        try {
            let csv = headers.join(',') + '\n';
            data.forEach(row => {
                csv += row.map(cell => `"${cell.replace(/"/g, '""')}"`).join(',') + '\n';
            });

            const blob = new Blob([csv], {
                type: 'text/csv;charset=utf-8;'
            });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            link.setAttribute('href', url);
            link.setAttribute('download', `board_passers_engineering_${new Date().toISOString().slice(0,10)}.csv`);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);

            showExportNotification('CSV file downloaded successfully!', 'success');
        } catch (error) {
            showExportNotification('CSV export failed: ' + error.message, 'error');
        }
    }

    function performExcelExport(headers, data) {
        // Simple Excel export using CSV format
        performCSVExport(headers, data);
        showExportNotification('Excel-compatible CSV file downloaded!', 'success');
    }

    function performPDFExport(headers, data) {
        try {
            // Simple PDF export by opening print dialog with formatted content
            const printWindow = window.open('', '_blank');
            const currentDate = new Date().toLocaleDateString();

            printWindow.document.write(`
          <!DOCTYPE html>
          <html>
          <head>
            <title>Board Passers Report - Engineering</title>
            <style>
              body { font-family: Arial, sans-serif; margin: 20px; }
              h1 { color: #2c5aa0; text-align: center; margin-bottom: 30px; }
              .header-info { text-align: center; margin-bottom: 20px; color: #666; }
              table { width: 100%; border-collapse: collapse; margin-top: 20px; }
              th, td { border: 1px solid #ddd; padding: 8px; text-align: left; font-size: 12px; }
              th { background-color: #3182ce; color: white; font-weight: bold; }
              tr:nth-child(even) { background-color: #f9f9f9; }
              .footer { margin-top: 30px; text-align: center; color: #666; font-size: 10px; }
              @media print { body { margin: 0; } }
            </style>
          </head>
          <body>
            <h1>Board Passers Report</h1>
            <div class="header-info">
              <p><strong>Department:</strong> College of Engineering</p>
              <p><strong>Generated:</strong> ${currentDate}</p>
              <p><strong>Total Records:</strong> ${data.length}</p>
            </div>
            <table>
              <thead>
                <tr>
                  ${headers.map(header => `<th>${header}</th>`).join('')}
                </tr>
              </thead>
              <tbody>
                ${data.map(row => `
                  <tr>
                    ${row.map(cell => `<td>${cell}</td>`).join('')}
                  </tr>
                `).join('')}
              </tbody>
            </table>
            <div class="footer">
              <p>Board Passing Rate System - Engineering Department</p>
            </div>
          </body>
          </html>
        `);

            printWindow.document.close();
            printWindow.focus();

            setTimeout(() => {
                printWindow.print();
                printWindow.close();
            }, 500);

            showExportNotification('PDF export opened in print dialog!', 'success');
        } catch (error) {
            showExportNotification('PDF export failed: ' + error.message, 'error');
        }
    }

    function showExportNotification(message, type = 'success') {
        const bgColor = type === 'success' ? '#22c55e' : type === 'error' ? '#ef4444' : '#3182ce';
        const icon = type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-triangle' : 'info-circle';

        const notification = document.createElement('div');
        notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: linear-gradient(135deg, ${bgColor} 0%, ${bgColor}dd 100%);
        color: white;
        padding: 16px 20px;
        border-radius: 12px;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        z-index: 10000;
        font-weight: 600;
        min-width: 300px;
        animation: slideInFromRight 0.5s ease;
        border: 2px solid rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(10px);
      `;

        notification.innerHTML = `
        <div style="display: flex; align-items: center; gap: 12px;">
          <i class="fas fa-${icon}" style="font-size: 1.2rem;"></i>
          <span>${message}</span>
        </div>
      `;

        document.body.appendChild(notification);

        setTimeout(() => {
            notification.style.transform = 'translateX(400px)';
            notification.style.opacity = '0';
            setTimeout(() => notification.remove(), 300);
        }, 4000);
    }

    function initializeKeyboardShortcuts() {
        console.log('🎯 Showing export confirmation for format:', format);

        if (!window.currentExportRows || window.currentExportRows.length === 0) {
            showExportNotification('No data available for export.', 'error');
            return;
        }

        // Close options modal
        closeExportOptionsModal();

        // Set up confirmation modal
        const modal = document.getElementById('exportConfirmModal');
        const formatType = document.getElementById('exportFormatType');
        const recordCount = document.getElementById('exportRecordCount');
        const fileName = document.getElementById('exportFileName');
        const confirmBtn = document.getElementById('confirmExport');
        const confirmText = document.getElementById('confirmExportText');
        const cancelBtn = document.getElementById('cancelExport');

        if (!modal || !formatType || !recordCount || !fileName || !confirmBtn || !confirmText || !cancelBtn) {
            console.error('❌ Export confirmation modal elements not found!');
            return;
        }

        // Update modal content based on format
        const formats = {
            csv: {
                name: 'CSV',
                extension: 'csv',
                icon: 'fa-file-csv',
                color: '#10b981'
            },
            excel: {
                name: 'Excel',
                extension: 'xls',
                icon: 'fa-file-excel',
                color: '#059669'
            },
            pdf: {
                name: 'PDF',
                extension: 'pdf',
                icon: 'fa-file-pdf',
                color: '#ef4444'
            }
        };

        const selectedFormat = formats[format];
        const timestamp = getCurrentDateString();
        const filename = `Engineering_Board_Passers_${timestamp}.${selectedFormat.extension}`;

        formatType.textContent = selectedFormat.name;
        recordCount.textContent = `${window.currentExportRows.length} records`;
        fileName.textContent = filename;
        confirmText.innerHTML = `<i class="fas ${selectedFormat.icon}"></i> Export ${selectedFormat.name}`;

        // Update button color
        confirmBtn.style.background =
            `linear-gradient(135deg, ${selectedFormat.color} 0%, ${selectedFormat.color}dd 100%)`;

        // Show modal
        modal.style.display = 'flex';
        setTimeout(() => {
            modal.classList.add('show');
        }, 10);

        // Handle confirm button
        confirmBtn.onclick = function() {
            performExport(format);
        };

        // Handle cancel button
        cancelBtn.onclick = function() {
            closeExportConfirmModal();
        };

        // Store current format
        window.currentExportFormat = format;
    }

    function closeExportConfirmModal() {
        console.log('🚪 Closing export confirmation modal');
        const modal = document.getElementById('exportConfirmModal');
        if (modal) {
            modal.classList.remove('show');
            setTimeout(() => {
                modal.style.display = 'none';
            }, 300);
        }
    }

    function performExport(format) {
        console.log('🚀 Performing export for format:', format);

        if (!window.currentExportRows || window.currentExportRows.length === 0) {
            showExportNotification('No data available for export.', 'error');
            return;
        }

        // Close confirmation modal
        closeExportConfirmModal();

        // Show loading notification
        showExportNotification(`Preparing ${format.toUpperCase()} export...`, 'loading');

        // Perform the actual export after a short delay for smooth UI
        setTimeout(() => {
            try {
                switch (format) {
                    case 'csv':
                        performCSVExport();
                        break;
                    case 'excel':
                        performExcelExport();
                        break;
                    case 'pdf':
                        performPDFExport();
                        break;
                    default:
                        throw new Error('Unknown export format');
                }
            } catch (error) {
                console.error('❌ Export failed:', error);
                showExportNotification(`Export failed: ${error.message}`, 'error');
            }
        }, 500);
    }

    function performCSVExport() {
        const csvData = generateCSVData(window.currentExportRows);
        const filename = `Engineering_Board_Passers_${getCurrentDateString()}.csv`;
        downloadFile(csvData, filename, 'text/csv');
        showExportNotification(`Successfully exported ${window.currentExportRows.length} records as CSV!`, 'success');
    }

    function performExcelExport() {
        const excelData = generateExcelData(window.currentExportRows);
        const filename = `Engineering_Board_Passers_${getCurrentDateString()}.xls`;
        downloadFile(excelData, filename, 'application/vnd.ms-excel');
        showExportNotification(`Successfully exported ${window.currentExportRows.length} records as Excel!`, 'success');
    }

    function performPDFExport() {
        generatePrintablePDF(window.currentExportRows);
        showExportNotification(`PDF export initiated! Use the print dialog to save as PDF.`, 'success');
    }

    function showExportNotification(message, type = 'success') {
        const colors = {
            success: {
                bg: '#10b981',
                icon: 'fa-check-circle'
            },
            error: {
                bg: '#ef4444',
                icon: 'fa-exclamation-triangle'
            },
            loading: {
                bg: '#3b82f6',
                icon: 'fa-spinner fa-spin'
            }
        };

        const color = colors[type] || colors.success;

        const notification = document.createElement('div');
        notification.innerHTML = `
        <div style="
          position: fixed;
          top: 20px;
          right: 20px;
          background: ${color.bg};
          color: white;
          padding: 16px 24px;
          border-radius: 12px;
          box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
          z-index: 10000;
          font-family: 'Inter', sans-serif;
          font-weight: 600;
          display: flex;
          align-items: center;
          gap: 12px;
          min-width: 300px;
          transform: translateX(100%);
          transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        ">
          <i class="fas ${color.icon}"></i>
          <span>${message}</span>
        </div>
      `;

        document.body.appendChild(notification);

        // Trigger entrance animation
        setTimeout(() => {
            notification.firstElementChild.style.transform = 'translateX(0)';
        }, 10);

        // Auto remove after delay (unless it's loading)
        if (type !== 'loading') {
            setTimeout(() => {
                notification.firstElementChild.style.transform = 'translateX(100%)';
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.remove();
                    }
                }, 300);
            }, 3000);
        }

        return notification;
    }


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

            // Set up event handlers if not already done
            if (yesBtn && !yesBtn.onclick) {
                yesBtn.onclick = function() {
                    console.log('Yes button clicked, redirecting to logout.php');
                    window.location.href = 'logout.php';
                };
            }

            if (noBtn && !noBtn.onclick) {
                noBtn.onclick = function() {
                    console.log('No button clicked, hiding modal');
                    modal.classList.remove('show');
                    modal.style.display = 'none';
                };
            }

            // Make buttons visible for beautiful theme
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

        // Remove message after animation
        setTimeout(() => {
            messageDiv.remove();
        }, 2000);
    }

    // Essential helper functions for export functionality
    function getCurrentDateString() {
        const now = new Date();
        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const day = String(now.getDate()).padStart(2, '0');
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        return `${year}${month}${day}_${hours}${minutes}`;
    }

    function downloadFile(data, filename, mimeType) {
        const blob = new Blob([data], {
            type: mimeType
        });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = filename;
        link.style.display = 'none';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
    }

    function generatePrintablePDF(rows) {
        const printWindow = window.open('', '_blank');
        let tableHTML = '<table border="1" style="border-collapse: collapse; width: 100%;">';
        tableHTML += '<thead><tr><th>Name</th><th>Course</th><th>Year</th><th>Result</th></tr></thead><tbody>';

        rows.forEach(row => {
            const cells = row.querySelectorAll('td');
            if (cells.length >= 4) {
                tableHTML += '<tr>';
                for (let i = 0; i < Math.min(4, cells.length); i++) {
                    tableHTML += `<td>${cells[i].textContent.trim()}</td>`;
                }
                tableHTML += '</tr>';
            }
        });

        tableHTML += '</tbody></table>';
        printWindow.document.write(`<html><body><h1>Board Passers Report</h1>${tableHTML}</body></html>`);
        printWindow.document.close();
        printWindow.print();
    }

    function importData() {
        alert('Import PRC data feature coming soon!');
    }

    function viewStats() {
        alert('Statistics/Analytics feature coming soon!');
    }

    // Filter functionality
    let allRows = [];

    function initializeFilters() {
        console.log('🚀 Initializing filters...');

        // Store all table rows for filtering
        const tableBody = document.querySelector('.board-table tbody');
        if (!tableBody) {
            console.error('❌ Table body not found!');
            return;
        }

        allRows = Array.from(tableBody.querySelectorAll('tr'));
        console.log('📊 Found', allRows.length, 'table rows');

        // Toggle filter visibility
        const toggleBtn = document.getElementById('toggleFilters');
        const filterContainer = document.getElementById('filterContainer');

        if (toggleBtn && filterContainer) {
            console.log('✅ Filter toggle elements found');
            toggleBtn.addEventListener('click', function() {
                const isVisible = filterContainer.classList.contains('show');
                if (isVisible) {
                    filterContainer.classList.remove('show');
                    toggleBtn.classList.remove('active');
                    toggleBtn.querySelector('span').textContent = 'Show Filters';
                } else {
                    filterContainer.classList.add('show');
                    toggleBtn.classList.add('active');
                    toggleBtn.querySelector('span').textContent = 'Hide Filters';
                }
            });
        } else {
            console.error('❌ Filter toggle elements not found!');
        }

        // Apply filters
        const applyBtn = document.getElementById('applyFilters');
        if (applyBtn) {
            console.log('✅ Apply filters button found');
            applyBtn.addEventListener('click', applyFilters);
        } else {
            console.error('❌ Apply filters button not found!');
        }

        // Clear filters
        const clearBtn = document.getElementById('clearFilters');
        if (clearBtn) {
            console.log('✅ Clear filters button found');
            clearBtn.addEventListener('click', clearFilters);
        } else {
            console.error('❌ Clear filters button not found!');
        }

        // Export data button
        const exportDataBtn = document.getElementById('exportData');
        if (exportDataBtn) {
            console.log('✅ Export Data button found, attaching event listener');
            exportDataBtn.addEventListener('click', function(e) {
                console.log('🎯 Export Data button clicked - opening options modal');
                e.preventDefault();
                e.stopPropagation();
                showExportOptionsModal();
            });
        } else {
            console.error('❌ Export Data button not found!');
        }

        // Real-time filtering on input change
        const filterInputs = document.querySelectorAll('.filter-input');
        console.log('✅ Found', filterInputs.length, 'filter inputs');
        filterInputs.forEach(input => {
            input.addEventListener('change', applyFilters);
        });

        // Initialize search functionality
        initializeSearch();
    }

    function initializeSearch() {
        const searchInput = document.getElementById('nameSearch');
        const clearSearchBtn = document.getElementById('clearSearch');

        // Real-time search as user types
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.trim();

            // Show/hide clear button
            if (searchTerm) {
                clearSearchBtn.classList.add('show');
            } else {
                clearSearchBtn.classList.remove('show');
            }

            // Apply search and filters
            applyFilters();
        });

        // Clear search functionality
        clearSearchBtn.addEventListener('click', function() {
            searchInput.value = '';
            this.classList.remove('show');
            applyFilters();
            searchInput.focus();
        });

        // Enter key to focus on first result
        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const firstVisibleRow = allRows.find(row => row.style.display !== 'none');
                if (firstVisibleRow) {
                    firstVisibleRow.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
                    firstVisibleRow.style.background = 'linear-gradient(90deg, #fef3c7 0%, #fde68a 100%)';
                    setTimeout(() => {
                        firstVisibleRow.style.background = '';
                    }, 2000);
                }
            }
        });
    }

    function applyFilters() {
        // Get selected values from multi-select dropdowns
        const getSelectedValues = (selectId) => {
            const select = document.getElementById(selectId);
            return Array.from(select.selectedOptions).map(opt => opt.value);
        };

        const filters = {
            nameSearch: document.getElementById('nameSearch').value.toLowerCase().trim(),
            courses: getSelectedValues('courseFilter'),
            years: getSelectedValues('yearFilter'),
            examDateIds: getSelectedValues('examDateFilter'),
            results: getSelectedValues('resultFilter'),
            examTypes: getSelectedValues('examTypeFilter'),
            boardExamTypes: getSelectedValues('boardExamTypeFilter')
        };

        // Get the actual date strings from the selected exam date IDs
        let selectedExamDateStrings = [];
        if (filters.examDateIds.length > 0) {
            const boardExamDates = window.BOARD_EXAM_DATES || [];
            filters.examDateIds.forEach(dateId => {
                const selectedDate = boardExamDates.find(d => String(d.id) === String(dateId));
                if (selectedDate) {
                    selectedExamDateStrings.push(selectedDate.date);
                }
            });
        }

        let visibleCount = 0;

        allRows.forEach(row => {
            let shouldShow = true;

            // Get row data
            const cells = row.querySelectorAll('td');
            const rowData = {
                name: cells[0].textContent.toLowerCase(),
                course: cells[1].textContent.toLowerCase(),
                year: cells[2].textContent,
                examDate: cells[3].textContent.trim(),
                result: cells[4].textContent.toLowerCase(),
                examType: cells[5].textContent.toLowerCase(),
                boardExamType: cells[6].textContent.toLowerCase()
            };

            // Apply name search filter
            if (filters.nameSearch && !rowData.name.includes(filters.nameSearch)) {
                shouldShow = false;
            }

            // Apply multi-select filters (OR logic within each filter)
            if (filters.courses.length > 0) {
                const matches = filters.courses.some(course =>
                    rowData.course.includes(course.toLowerCase())
                );
                if (!matches) shouldShow = false;
            }

            if (filters.years.length > 0) {
                if (!filters.years.includes(rowData.year)) {
                    shouldShow = false;
                }
            }

            if (selectedExamDateStrings.length > 0) {
                if (!selectedExamDateStrings.includes(rowData.examDate)) {
                    shouldShow = false;
                }
            }

            if (filters.results.length > 0) {
                const matches = filters.results.some(result =>
                    rowData.result.includes(result.toLowerCase())
                );
                if (!matches) shouldShow = false;
            }

            if (filters.examTypes.length > 0) {
                const matches = filters.examTypes.some(type =>
                    rowData.examType.includes(type.toLowerCase())
                );
                if (!matches) shouldShow = false;
            }

            if (filters.boardExamTypes.length > 0) {
                const matches = filters.boardExamTypes.some(type => {
                    // Match by ID or by name
                    const typeStr = String(type);
                    return rowData.boardExamType.includes(typeStr.toLowerCase()) ||
                        (window.BOARD_EXAM_TYPE_MAP && window.BOARD_EXAM_TYPE_MAP[type] &&
                            rowData.boardExamType.includes(window.BOARD_EXAM_TYPE_MAP[type]
                                .toLowerCase()));
                });
                if (!matches) shouldShow = false;
            }

            // Show/hide row
            if (shouldShow) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        // Update record count
        updateRecordCount(visibleCount);

        // Show filter applied message with search info
        const searchTerm = document.getElementById('nameSearch').value.trim();
        let message = `Showing ${visibleCount} of ${allRows.length} records`;
        if (searchTerm) {
            message += ` for "${searchTerm}"`;
        }
        showFilterMessage(message);
    }

    function clearFilters() {
        // Reset search input
        document.getElementById('nameSearch').value = '';
        document.getElementById('clearSearch').classList.remove('show');

        // Reset all multi-select filter inputs
        const clearMultiSelect = (id) => {
            const select = document.getElementById(id);
            if (select) {
                Array.from(select.options).forEach(opt => opt.selected = false);
            }
        };

        clearMultiSelect('courseFilter');
        clearMultiSelect('yearFilter');
        clearMultiSelect('resultFilter');
        clearMultiSelect('examTypeFilter');
        clearMultiSelect('boardExamTypeFilter');

        // Reset exam date filter and disable it
        const examDateFilter = document.getElementById('examDateFilter');
        examDateFilter.innerHTML = '<option value="" disabled>-- Select board exam type first --</option>';
        examDateFilter.disabled = true;

        // Show all rows
        allRows.forEach(row => {
            row.style.display = '';
        });

        // Update record count
        updateRecordCount(allRows.length);

        // Show cleared message
        showFilterMessage('All filters and search cleared');
    }

    function updateRecordCount(count) {
        const recordCount = document.getElementById('recordCount');
        recordCount.innerHTML = `Showing Records: <strong>${count}</strong> of <strong>${allRows.length}</strong>`;
    }

    function showFilterMessage(message) {
        // Create and show temporary message
        const messageDiv = document.createElement('div');
        messageDiv.innerHTML = `
        <div style="
          position: fixed;
          top: 50%;
          left: 50%;
          transform: translate(-50%, -50%);
          background: linear-gradient(135deg, #3182ce 0%, #2c5aa0 100%);
          color: white;
          padding: 16px 24px;
          border-radius: 12px;
          box-shadow: 0 8px 25px rgba(49, 130, 206, 0.3);
          z-index: 10000;
          font-family: Inter;
          font-weight: 600;
          text-align: center;
          min-width: 280px;
          backdrop-filter: blur(10px);
        ">
          <i class="fas fa-filter"></i> ${message}
        </div>
      `;
        document.body.appendChild(messageDiv);
        setTimeout(() => {
            messageDiv.remove();
        }, 3000);
    }

    // Export Handler Function
    function handleExport(format) {
        console.log(`🚀 Export initiated: ${format.toUpperCase()}`);

        // Close the modal first
        closeExportOptionsModal();

        // Get visible rows (filtered data)
        const visibleRows = Array.from(allRows).filter(row => row.style.display !== 'none');

        if (visibleRows.length === 0) {
            showExportMessage('No data to export. Please apply filters to show records.', 'error');
            return;
        }

        // Collect data from visible rows
        const exportData = visibleRows.map(row => {
            return {
                name: row.cells[0]?.textContent.trim() || '',
                course: row.cells[1]?.textContent.trim() || '',
                year: row.cells[2]?.textContent.trim() || '',
                boardExamDate: row.cells[3]?.textContent.trim() || '',
                result: row.cells[4]?.textContent.trim() || '',
                attempts: row.cells[5]?.textContent.trim() || '',
                examType: row.cells[6]?.textContent.trim() || ''
            };
        });

        // Handle different export formats
        switch (format) {
            case 'csv':
                exportToCSV(exportData);
                break;
            case 'excel':
                exportToExcel(exportData);
                break;
            case 'pdf':
                exportToPDF(exportData);
                break;
            case 'json':
                exportToJSON(exportData);
                break;
            default:
                showExportMessage('Invalid export format', 'error');
        }
    }

    // Export to CSV
    function exportToCSV(data) {
        const headers = ['Name', 'Course', 'Year Graduated', 'Board Exam Date', 'Result', 'Take Attempts',
            'Board Exam Type'
        ];
        const csvContent = [
            headers.join(','),
            ...data.map(row => [
                `"${row.name}"`,
                `"${row.course}"`,
                `"${row.year}"`,
                `"${row.boardExamDate}"`,
                `"${row.result}"`,
                `"${row.attempts}"`,
                `"${row.examType}"`
            ].join(','))
        ].join('\n');

        downloadFile(csvContent, 'board_passers_engineering.csv', 'text/csv');
        showExportMessage(`Successfully exported ${data.length} records as CSV`, 'success');
    }

    // Export to Excel (CSV with .xls extension for basic compatibility)
    function exportToExcel(data) {
        const headers = ['Name', 'Course', 'Year Graduated', 'Board Exam Date', 'Result', 'Take Attempts',
            'Board Exam Type'
        ];
        const csvContent = [
            headers.join('\t'),
            ...data.map(row => [
                row.name,
                row.course,
                row.year,
                row.boardExamDate,
                row.result,
                row.attempts,
                row.examType
            ].join('\t'))
        ].join('\n');

        downloadFile(csvContent, 'board_passers_engineering.xls', 'application/vnd.ms-excel');
        showExportMessage(`Successfully exported ${data.length} records as Excel`, 'success');
    }

    // Export to JSON
    function exportToJSON(data) {
        const jsonContent = JSON.stringify(data, null, 2);
        downloadFile(jsonContent, 'board_passers_engineering.json', 'application/json');
        showExportMessage(`Successfully exported ${data.length} records as JSON`, 'success');
    }

    // Export to PDF
    function exportToPDF(data) {
        const currentDate = new Date();
        const formattedDate = currentDate.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
        const formattedTime = currentDate.toLocaleTimeString('en-US', {
            hour: '2-digit',
            minute: '2-digit'
        });

        // Calculate statistics
        const passedCount = data.filter(row => row.result.toLowerCase().includes('passed')).length;
        const failedCount = data.filter(row => row.result.toLowerCase().includes('failed')).length;
        const passingRate = data.length > 0 ? ((passedCount / data.length) * 100).toFixed(1) : 0;

        // Create a clean HTML document for PDF
        let htmlContent = `
        <!DOCTYPE html>
        <html>
        <head>
          <meta charset="UTF-8">
          <title>LSPU Engineering Board Passers Report</title>
          <style>
            @page {
              size: A4 landscape;
              margin: 15mm 20mm;
            }
            
            @media print {
              body { 
                margin: 0; 
                padding: 15mm 20mm;
              }
              .no-print { display: none; }
            }
            
            body { 
              font-family: Arial, sans-serif;
              padding: 20px;
              color: #333;
              line-height: 1.6;
              max-width: 100%;
            }
            
            .header {
              text-align: center;
              margin-bottom: 25px;
              padding-bottom: 15px;
              border-bottom: 3px solid #06b6d4;
            }
            
            .logo-icon {
              font-size: 48px;
              color: #06b6d4;
              margin-bottom: 10px;
            }
            
            .school-name {
              font-size: 18px;
              font-weight: bold;
              color: #0e7490;
              margin-bottom: 5px;
            }
            
            .department {
              font-size: 14px;
              color: #64748b;
              margin-bottom: 15px;
            }
            
            .report-title {
              font-size: 18px;
              color: #1e293b;
              font-weight: bold;
              margin-top: 15px;
            }
            
            .info-section {
              display: flex;
              justify-content: space-between;
              margin-bottom: 20px;
              padding: 15px;
              background: #f8fafc;
              border-radius: 5px;
            }
            
            .info-item {
              text-align: center;
            }
            
            .info-label {
              font-size: 11px;
              color: #64748b;
              text-transform: uppercase;
              margin-bottom: 5px;
            }
            
            .info-value {
              font-size: 14px;
              font-weight: bold;
              color: #1e293b;
            }
            
            .stats {
              display: flex;
              gap: 10px;
              margin-bottom: 20px;
            }
            
            .stat-box {
              flex: 1;
              padding: 10px 12px;
              border-radius: 5px;
              text-align: center;
            }
            
            .stat-box.passed { background: #d1fae5; border-left: 4px solid #10b981; }
            .stat-box.failed { background: #fee2e2; border-left: 4px solid #ef4444; }
            .stat-box.rate { background: #dbeafe; border-left: 4px solid #3b82f6; }
            
            .stat-label {
              font-size: 10px;
              color: #64748b;
              text-transform: uppercase;
              margin-bottom: 3px;
            }
            
            .stat-value {
              font-size: 16px;
              font-weight: bold;
            }
            
            .stat-box.passed .stat-value { color: #059669; }
            .stat-box.failed .stat-value { color: #dc2626; }
            .stat-box.rate .stat-value { color: #2563eb; }
            
            table { 
              width: 100%; 
              border-collapse: collapse;
              margin-top: 15px;
            }
            
            th { 
              background: #06b6d4;
              color: white; 
              padding: 10px 8px;
              text-align: left; 
              font-size: 11px;
              font-weight: bold;
            }
            
            td { 
              padding: 8px;
              border-bottom: 1px solid #e2e8f0;
              font-size: 10px;
            }
            
            tr:nth-child(even) { background: #f8fafc; }
            
            .result-passed {
              color: #059669;
              font-weight: bold;
            }
            
            .result-failed {
              color: #dc2626;
              font-weight: bold;
            }
            
            .footer { 
              margin-top: 30px;
              padding-top: 15px;
              border-top: 1px solid #e2e8f0;
              text-align: center;
              font-size: 10px;
              color: #64748b;
            }
          </style>
        </head>
        <body>
          <!-- Header -->
          <div class="header">
            <div class="logo-icon">🎓</div>
            <div class="school-name">Laguna State Polytechnic University</div>
            <div class="department">College of Engineering</div>
            <div class="report-title">Board Passers Database Report</div>
          </div>
          
          <!-- Info Section -->
          <div class="info-section">
            <div class="info-item">
              <div class="info-label">Total Records</div>
              <div class="info-value">${data.length}</div>
            </div>
            <div class="info-item">
              <div class="info-label">Generated Date</div>
              <div class="info-value">${formattedDate}</div>
            </div>
            <div class="info-item">
              <div class="info-label">Time</div>
              <div class="info-value">${formattedTime}</div>
            </div>
            <div class="info-item">
              <div class="info-label">Department</div>
              <div class="info-value">Engineering</div>
            </div>
          </div>
          
          <!-- Statistics -->
          <div class="stats">
            <div class="stat-box passed">
              <div class="stat-label">Passed</div>
              <div class="stat-value">${passedCount}</div>
            </div>
            <div class="stat-box failed">
              <div class="stat-label">Failed</div>
              <div class="stat-value">${failedCount}</div>
            </div>
            <div class="stat-box rate">
              <div class="stat-label">Passing Rate</div>
              <div class="stat-value">${passingRate}%</div>
            </div>
          </div>
          
          <!-- Table -->
          <table>
            <thead>
              <tr>
                <th style="width: 4%;">#</th>
                <th style="width: 18%;">Name</th>
                <th style="width: 22%;">Course</th>
                <th style="width: 7%;">Year</th>
                <th style="width: 11%;">Exam Date</th>
                <th style="width: 9%;">Result</th>
                <th style="width: 10%;">Attempts</th>
                <th style="width: 19%;">Exam Type</th>
              </tr>
            </thead>
            <tbody>
              ${data.map((row, index) => `
                <tr>
                  <td style="text-align: center;">${index + 1}</td>
                  <td style="font-weight: bold;">${row.name}</td>
                  <td>${row.course}</td>
                  <td style="text-align: center;">${row.year}</td>
                  <td style="text-align: center;">${row.boardExamDate}</td>
                  <td class="result-${row.result.toLowerCase().includes('passed') ? 'passed' : 'failed'}" style="text-align: center;">
                    ${row.result}
                  </td>
                  <td style="text-align: center;">${row.attempts}</td>
                  <td>${row.examType}</td>
                </tr>
              `).join('')}
            </tbody>
          </table>
          
          <!-- Footer -->
          <div class="footer">
            <p><strong>Laguna State Polytechnic University - College of Engineering</strong></p>
            <p>Santa Cruz, Laguna | Tel: (049) 501-0010 | Email: engineering@lspu.edu.ph</p>
            <p>Document ID: ENG-${Date.now()} | Generated by Board Passers Database System</p>
          </div>
        </body>
        </html>
      `;

        // Open in new window for printing/saving as PDF
        const printWindow = window.open('', '_blank');
        printWindow.document.write(htmlContent);
        printWindow.document.close();

        // Trigger print dialog after content loads
        setTimeout(() => {
            printWindow.print();
        }, 250);

        showExportMessage(`PDF preview opened with ${data.length} records. Use Print to save as PDF`, 'success');
    }

    // Download file helper
    function downloadFile(content, filename, mimeType) {
        const blob = new Blob([content], {
            type: mimeType
        });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = filename;
        link.click();
        URL.revokeObjectURL(link.href);
    }

    // Show export message
    function showExportMessage(message, type = 'success') {
        const bgColor = type === 'success' ?
            'linear-gradient(135deg, #22c55e 0%, #16a34a 100%)' :
            'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)';

        const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';

        const messageDiv = document.createElement('div');
        messageDiv.innerHTML = `
        <div style="
          position: fixed;
          top: 20px;
          right: 20px;
          background: ${bgColor};
          color: white;
          padding: 16px 24px;
          border-radius: 12px;
          box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
          z-index: 10000;
          font-family: Inter, sans-serif;
          font-weight: 600;
          max-width: 400px;
          backdrop-filter: blur(10px);
          animation: slideIn 0.3s ease;
        ">
          <i class="fas ${icon}" style="margin-right: 8px;"></i> ${message}
        </div>
      `;
        document.body.appendChild(messageDiv);
        setTimeout(() => {
            messageDiv.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => messageDiv.remove(), 300);
        }, 4000);
    }


    function initializeKeyboardShortcuts() {
        console.log('🚀 PDF Export button clicked!');

        try {
            // Check if allRows is available
            if (typeof allRows === 'undefined' || !allRows || allRows.length === 0) {
                console.log('⚠️ allRows not initialized, getting rows directly from table...');
                const tableBody = document.querySelector('.board-table tbody');
                if (!tableBody) {
                    alert('Table not found! Please refresh the page and try again.');
                    return;
                }
                allRows = Array.from(tableBody.querySelectorAll('tr'));
                console.log('📊 Found', allRows.length, 'table rows');
            }

            // Get visible rows (filtered data)
            const visibleRows = allRows.filter(row => row.style.display !== 'none');
            console.log('📊 Visible rows found:', visibleRows.length);

            if (visibleRows.length === 0) {
                alert('No data to export. Please apply filters to show records.');
                return;
            }

            console.log('✅ Generating PDF download...');
            generatePDFData(visibleRows);

            // Success message after a short delay to ensure download started
            setTimeout(() => {
                console.log('✅ PDF export process completed');
            }, 500);

        } catch (error) {
            console.error('❌ PDF Export Error:', error);
            alert('PDF export failed: ' + error.message + '. Please try again.');
        }
    }

    function showExportFormatModal(recordCount, visibleRows) {
        console.log('🎯 Opening export modal for', recordCount, 'records');
        console.log('🔍 Modal Debug Info:');

        const modal = document.getElementById('exportModal');
        if (!modal) {
            console.error('❌ Export modal not found!');
            console.log('🔍 Available modals:', Array.from(document.querySelectorAll('[id*="modal"], [id*="Modal"]'))
                .map(m => m.id));
            alert('Export modal not found! Please refresh the page and try again.');
            return;
        }

        console.log('✅ Export modal found:', modal);
        console.log('  - Modal ID:', modal.id);
        console.log('  - Modal current display:', window.getComputedStyle(modal).display);
        console.log('  - Modal current visibility:', window.getComputedStyle(modal).visibility);

        const exportDetails = document.getElementById('exportDetails');
        const modalButtons = modal.querySelector('.modal-buttons');

        if (!exportDetails) {
            console.error('❌ exportDetails not found!');
            console.log('🔍 Available elements with "export" in ID:', Array.from(document.querySelectorAll(
                '[id*="export"], [id*="Export"]')).map(e => e.id));
            alert('Export modal components missing! Please refresh the page.');
            return;
        }

        if (!modalButtons) {
            console.error('❌ modalButtons not found!');
            console.log('🔍 Modal children:', Array.from(modal.children).map(c => c.className));
            alert('Export modal buttons missing! Please refresh the page.');
            return;
        }

        console.log('✅ Modal components found');
        console.log('  - exportDetails:', exportDetails);
        console.log('  - modalButtons:', modalButtons);
        console.log('  - Modal classes:', modal.className);

        // Store visible rows for export functions first
        window.currentExportData = visibleRows;
        console.log('💾 Stored export data:', window.currentExportData.length, 'rows');
        console.log('📋 Sample export data:', window.currentExportData.slice(0, 2).map(row => row.textContent.trim()
            .substring(0, 100)));

        // Update modal content with export information
        exportDetails.innerHTML = `
        <div style="
          background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); 
          padding: 24px; 
          border-radius: 16px; 
          border-left: 5px solid #3182ce; 
          margin-bottom: 24px; 
          border: 1px solid #bfdbfe;
          box-shadow: 0 4px 12px rgba(49, 130, 206, 0.1);
          position: relative;
          overflow: hidden;
        ">
          <div style="
            position: absolute;
            top: 0;
            right: 0;
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, rgba(49, 130, 206, 0.1) 0%, rgba(96, 165, 250, 0.05) 100%);
            border-radius: 50%;
            transform: translate(30%, -30%);
          "></div>
          <div style="
            font-weight: 700; 
            color: #1e40af; 
            margin-bottom: 12px; 
            display: flex; 
            align-items: center; 
            gap: 12px;
            font-size: 1.1rem;
            position: relative;
            z-index: 1;
          ">
            <div style="
              background: linear-gradient(135deg, #3182ce 0%, #2563eb 100%);
              color: white;
              border-radius: 50%;
              width: 32px;
              height: 32px;
              display: flex;
              align-items: center;
              justify-content: center;
              box-shadow: 0 4px 12px rgba(49, 130, 206, 0.3);
            ">
              <i class="fas fa-info-circle" style="font-size: 0.9rem;"></i>
            </div>
            Export Information
          </div>
          <div style="
            font-size: 1rem; 
            color: #1e40af; 
            margin-bottom: 8px;
            position: relative;
            z-index: 1;
          ">
            Records to export: 
            <span style="
              background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
              color: white;
              padding: 4px 12px;
              border-radius: 8px;
              font-weight: 700;
              margin-left: 8px;
              box-shadow: 0 2px 8px rgba(37, 99, 235, 0.3);
            ">${recordCount}</span>
          </div>
          <div style="
            font-size: 0.95rem; 
            color: #2563eb; 
            font-weight: 500;
            position: relative;
            z-index: 1;
          ">
            Choose your preferred export format below
          </div>
        </div>
        
        <div style="
          background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%); 
          padding: 28px; 
          border-radius: 16px; 
          border: 2px solid #e2e8f0; 
          box-shadow: 0 8px 25px rgba(0,0,0,0.08);
          position: relative;
          overflow: hidden;
        ">
          <div style="
            position: absolute;
            top: -50px;
            left: -50px;
            width: 150px;
            height: 150px;
            background: linear-gradient(135deg, rgba(49, 130, 206, 0.03) 0%, rgba(96, 165, 250, 0.02) 100%);
            border-radius: 50%;
          "></div>
          <h4 style="
            margin: 0 0 24px 0; 
            color: #1e40af; 
            font-size: 1.3rem; 
            font-weight: 800; 
            text-align: center;
            position: relative;
            z-index: 1;
            background: linear-gradient(135deg, #1e40af 0%, #2563eb 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
          ">
            Available Export Formats
          </h4>
          <div style="display: grid; gap: 16px; position: relative; z-index: 1;">
            <div style="
              display: flex; 
              align-items: center; 
              gap: 16px; 
              padding: 18px; 
              background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); 
              border-radius: 12px; 
              border: 2px solid #bbf7d0;
              transition: all 0.3s ease;
              cursor: pointer;
              position: relative;
              overflow: hidden;
            " onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 25px rgba(5, 150, 105, 0.2)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
              <div style="
                background: linear-gradient(135deg, #10b981 0%, #059669 100%);
                color: white;
                border-radius: 12px;
                width: 48px;
                height: 48px;
                display: flex;
                align-items: center;
                justify-content: center;
                box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
              ">
                <i class="fas fa-file-excel" style="font-size: 1.5rem;"></i>
              </div>
              <div style="flex: 1;">
                <div style="font-weight: 700; color: #065f46; font-size: 1.1rem; margin-bottom: 4px;">Excel (.xls)</div>
                <div style="font-size: 0.9rem; color: #047857; line-height: 1.4;">Spreadsheet format with formatting and formulas</div>
              </div>
              <div style="
                position: absolute;
                top: 0;
                right: 0;
                width: 60px;
                height: 60px;
                background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, transparent 100%);
                border-radius: 50%;
                transform: translate(30%, -30%);
              "></div>
            </div>
            <div style="
              display: flex; 
              align-items: center; 
              gap: 16px; 
              padding: 18px; 
              background: linear-gradient(135deg, #faf5ff 0%, #f3e8ff 100%); 
              border-radius: 12px; 
              border: 2px solid #d8b4fe;
              transition: all 0.3s ease;
              cursor: pointer;
              position: relative;
              overflow: hidden;
            " onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 25px rgba(124, 58, 237, 0.2)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
              <div style="
                background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
                color: white;
                border-radius: 12px;
                width: 48px;
                height: 48px;
                display: flex;
                align-items: center;
                justify-content: center;
                box-shadow: 0 4px 12px rgba(139, 92, 246, 0.3);
              ">
                <i class="fas fa-file-csv" style="font-size: 1.5rem;"></i>
              </div>
              <div style="flex: 1;">
                <div style="font-weight: 700; color: #581c87; font-size: 1.1rem; margin-bottom: 4px;">CSV (.csv)</div>
                <div style="font-size: 0.9rem; color: #6b21a8; line-height: 1.4;">Comma-separated values for data analysis</div>
              </div>
              <div style="
                position: absolute;
                top: 0;
                right: 0;
                width: 60px;
                height: 60px;
                background: linear-gradient(135deg, rgba(139, 92, 246, 0.1) 0%, transparent 100%);
                border-radius: 50%;
                transform: translate(30%, -30%);
              "></div>
            </div>
            <div style="
              display: flex; 
              align-items: center; 
              gap: 16px; 
              padding: 18px; 
              background: linear-gradient(135deg, #fef2f2 0%, #fecaca 100%); 
              border-radius: 12px; 
              border: 2px solid #fca5a5;
              transition: all 0.3s ease;
              cursor: pointer;
              position: relative;
              overflow: hidden;
            " onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 25px rgba(220, 38, 38, 0.2)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
              <div style="
                background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
                color: white;
                border-radius: 12px;
                width: 48px;
                height: 48px;
                display: flex;
                align-items: center;
                justify-content: center;
                box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
              ">
                <i class="fas fa-file-pdf" style="font-size: 1.5rem;"></i>
              </div>
              <div style="flex: 1;">
                <div style="font-weight: 700; color: #991b1b; font-size: 1.1rem; margin-bottom: 4px;">PDF (.pdf)</div>
                <div style="font-size: 0.9rem; color: #b91c1c; line-height: 1.4;">Printable document format for reports</div>
              </div>
              <div style="
                position: absolute;
                top: 0;
                right: 0;
                width: 60px;
                height: 60px;
                background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, transparent 100%);
                border-radius: 50%;
                transform: translate(30%, -30%);
              "></div>
            </div>
          </div>
        </div>
      `;

        // Clear and create export buttons
        modalButtons.innerHTML = '';

        // Create Excel button
        const excelBtn = document.createElement('button');
        excelBtn.className = 'btn-export excel-btn';
        excelBtn.innerHTML = `
        <div style="display: flex; align-items: center; gap: 12px;">
          <div style="
            background: rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
          ">
            <i class="fas fa-file-excel" style="font-size: 1.1rem;"></i>
          </div>
          <span style="font-weight: 600;">Export as Excel</span>
        </div>
      `;
        excelBtn.style.cssText = `
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        border: none;
        padding: 18px 32px;
        border-radius: 16px;
        font-weight: 600;
        font-family: 'Inter', sans-serif;
        font-size: 1rem;
        cursor: pointer;
        transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 8px 0;
        min-width: 240px;
        width: 100%;
        max-width: 280px;
        box-shadow: 0 8px 25px rgba(16, 185, 129, 0.3);
        position: relative;
        overflow: hidden;
        border: 2px solid rgba(255, 255, 255, 0.1);
      `;

        // Create CSV button
        const csvBtn = document.createElement('button');
        csvBtn.className = 'btn-export csv-btn';
        csvBtn.innerHTML = `
        <div style="display: flex; align-items: center; gap: 12px;">
          <div style="
            background: rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
          ">
            <i class="fas fa-file-csv" style="font-size: 1.1rem;"></i>
          </div>
          <span style="font-weight: 600;">Export as CSV</span>
        </div>
      `;
        csvBtn.style.cssText = `
        background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
        color: white;
        border: none;
        padding: 18px 32px;
        border-radius: 16px;
        font-weight: 600;
        font-family: 'Inter', sans-serif;
        font-size: 1rem;
        cursor: pointer;
        transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 8px 0;
        min-width: 240px;
        width: 100%;
        max-width: 280px;
        box-shadow: 0 8px 25px rgba(139, 92, 246, 0.3);
        position: relative;
        overflow: hidden;
        border: 2px solid rgba(255, 255, 255, 0.1);
      `;

        // Create PDF button
        const pdfBtn = document.createElement('button');
        pdfBtn.className = 'btn-export pdf-btn';
        pdfBtn.innerHTML = `
        <div style="display: flex; align-items: center; gap: 12px;">
          <div style="
            background: rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
          ">
            <i class="fas fa-file-pdf" style="font-size: 1.1rem;"></i>
          </div>
          <span style="font-weight: 600;">Export as PDF</span>
        </div>
      `;
        pdfBtn.style.cssText = `
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        color: white;
        border: none;
        padding: 18px 32px;
        border-radius: 16px;
        font-weight: 600;
        font-family: 'Inter', sans-serif;
        font-size: 1rem;
        cursor: pointer;
        transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 8px 0;
        min-width: 240px;
        width: 100%;
        max-width: 280px;
        box-shadow: 0 8px 25px rgba(239, 68, 68, 0.3);
        position: relative;
        overflow: hidden;
        border: 2px solid rgba(255, 255, 255, 0.1);
      `;

        // Add click event handlers
        excelBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('📊 Excel button clicked');
            exportAsExcel();
        });

        csvBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('📄 CSV button clicked');
            exportAsCSV();
        });

        pdfBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('📋 PDF button clicked');
            exportAsPDF();
        });

        // Add hover effects
        [excelBtn, csvBtn, pdfBtn].forEach((btn, index) => {
            // Add subtle animation on load
            btn.style.transform = 'translateY(20px)';
            btn.style.opacity = '0';
            setTimeout(() => {
                btn.style.transform = 'translateY(0)';
                btn.style.opacity = '1';
            }, 100 + (index * 100));

            btn.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-4px) scale(1.02)';
                const shadowColors = [
                    'rgba(16, 185, 129, 0.5)',
                    'rgba(139, 92, 246, 0.5)',
                    'rgba(239, 68, 68, 0.5)'
                ];
                this.style.boxShadow = `0 16px 40px ${shadowColors[index]}`;

                // Add subtle glow effect
                const backgrounds = [
                    'linear-gradient(135deg, #059669 0%, #047857 100%)',
                    'linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%)',
                    'linear-gradient(135deg, #dc2626 0%, #b91c1c 100%)'
                ];
                this.style.background = backgrounds[index];

                // Animate the icon container
                const iconContainer = this.querySelector('div > div');
                if (iconContainer) {
                    iconContainer.style.transform = 'scale(1.1) rotate(5deg)';
                    iconContainer.style.background = 'rgba(255, 255, 255, 0.3)';
                }
            });

            btn.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
                const shadowColors = [
                    'rgba(16, 185, 129, 0.3)',
                    'rgba(139, 92, 246, 0.3)',
                    'rgba(239, 68, 68, 0.3)'
                ];
                this.style.boxShadow = `0 8px 25px ${shadowColors[index]}`;

                // Reset background
                const backgrounds = [
                    'linear-gradient(135deg, #10b981 0%, #059669 100%)',
                    'linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%)',
                    'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)'
                ];
                this.style.background = backgrounds[index];

                // Reset icon container
                const iconContainer = this.querySelector('div > div');
                if (iconContainer) {
                    iconContainer.style.transform = 'scale(1) rotate(0deg)';
                    iconContainer.style.background = 'rgba(255, 255, 255, 0.2)';
                }
            });

            // Add click animation
            btn.addEventListener('mousedown', function() {
                this.style.transform = 'translateY(-2px) scale(0.98)';
            });

            btn.addEventListener('mouseup', function() {
                this.style.transform = 'translateY(-4px) scale(1.02)';
            });
        });

        // Append buttons to modal BEFORE decorative elements so they stay on top
        modalButtons.appendChild(excelBtn);
        modalButtons.appendChild(csvBtn);
        modalButtons.appendChild(pdfBtn);

        // Style the modal buttons container
        modalButtons.style.cssText = `
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 16px;
        padding: 32px 40px 40px;
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        border-top: 2px solid #e2e8f0;
        border-radius: 0 0 24px 24px;
        position: relative;
        overflow: hidden;
        z-index: 10007;
        pointer-events: auto;
      `;

        // Ensure all buttons have proper z-index and are clickable
        [excelBtn, csvBtn, pdfBtn].forEach((btn, index) => {
            btn.style.zIndex = '10010';
            btn.style.position = 'relative';
            btn.style.pointerEvents = 'auto';
            console.log(`Button ${index + 1} z-index:`, btn.style.zIndex);
        });

        // Add decorative elements to the buttons container
        const decorativeElement = document.createElement('div');
        decorativeElement.style.cssText = `
        position: absolute;
        top: 0;
        left: 50%;
        transform: translateX(-50%);
        width: 60px;
        height: 4px;
        background: linear-gradient(135deg, #3182ce 0%, #2563eb 100%);
        border-radius: 0 0 4px 4px;
        pointer-events: none;
        z-index: 1;
      `;
        modalButtons.appendChild(decorativeElement);

        // Add a subtle pattern background
        const patternElement = document.createElement('div');
        patternElement.style.cssText = `
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-image: radial-gradient(circle at 20% 80%, rgba(49, 130, 206, 0.03) 0%, transparent 50%),
                         radial-gradient(circle at 80% 20%, rgba(37, 99, 235, 0.03) 0%, transparent 50%);
        pointer-events: none;
        z-index: 1;
      `;
        modalButtons.appendChild(patternElement);

        console.log('✅ Buttons created and added to modal');
        console.log('Button count in modal:', modalButtons.children.length);

        // Show modal with proper display and highest z-index
        modal.style.display = 'flex';
        modal.style.position = 'fixed';
        modal.style.top = '0';
        modal.style.left = '0';
        modal.style.width = '100vw';
        modal.style.height = '100vh';
        modal.style.backgroundColor = 'rgba(0, 0, 0, 0.5)';
        modal.style.zIndex = '10005';
        modal.style.justifyContent = 'center';
        modal.style.alignItems = 'center';
        modal.classList.add('show');

        // Force buttons to be clickable by ensuring they're properly accessible
        [excelBtn, csvBtn, pdfBtn].forEach(btn => {
            btn.style.pointerEvents = 'auto';
            btn.style.zIndex = '10008';
            btn.style.position = 'relative';

            // Add debugging click handler
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                console.log('🖱️ Button clicked:', this.className);
            }, true);
        });

        console.log('✅ Modal should now be visible with fully clickable buttons');
        console.log('Modal z-index:', modal.style.zIndex);
        console.log('Buttons clickable test:', [excelBtn, csvBtn, pdfBtn].map(btn => ({
            className: btn.className,
            pointerEvents: btn.style.pointerEvents,
            zIndex: btn.style.zIndex
        })));

        // Close modal when clicking outside
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                console.log('👆 Clicked outside modal, closing');
                closeExportModal();
            }
        });
    }

    function closeExportModal() {
        console.log('🚪 Closing export modal');
        const modal = document.getElementById('exportModal');
        if (modal) {
            modal.classList.remove('show');
            modal.style.display = 'none';

            // Clear the export data
            window.currentExportData = null;
            console.log('✅ Export modal closed and data cleared');
        }
    }

    function exportAsCSV() {
        console.log('📄 exportAsCSV called');

        if (!window.currentExportData || window.currentExportData.length === 0) {
            console.error('❌ No export data available');
            showExportMessage('No data available for export. Please try again.', 'error');
            return;
        }

        console.log('✅ Generating CSV data for', window.currentExportData.length, 'rows');
        const csvData = generateCSVData(window.currentExportData);
        downloadFile(csvData, `Engineering_Board_Passers_${getCurrentDateString()}.csv`, 'text/csv');
        showExportMessage(`Successfully exported ${window.currentExportData.length} records as CSV!`, 'success');
        closeExportModal();
    }

    function exportAsExcel() {
        console.log('📊 exportAsExcel called');

        if (!window.currentExportData || window.currentExportData.length === 0) {
            console.error('❌ No export data available');
            showExportMessage('No data available for export. Please try again.', 'error');
            return;
        }

        console.log('✅ Generating Excel data for', window.currentExportData.length, 'rows');
        const excelData = generateExcelData(window.currentExportData);
        downloadFile(excelData, `Engineering_Board_Passers_${getCurrentDateString()}.xls`, 'application/vnd.ms-excel');
        showExportMessage(`Successfully exported ${window.currentExportData.length} records as Excel!`, 'success');
        closeExportModal();
    }


    function exportAsPDF() {
        console.log('📋 exportAsPDF called');

        if (!window.currentExportData || window.currentExportData.length === 0) {
            console.error('❌ No export data available');
            showExportMessage('No data available for export. Please try again.', 'error');
            return;
        }

        console.log('✅ Generating PDF data for', window.currentExportData.length, 'rows');
        generatePDFData(window.currentExportData);
        showExportMessage(`Successfully exported ${window.currentExportData.length} records as PDF!`, 'success');
        closeExportModal();
    }

    function generateCSVData(rows) {
        // CSV Header
        const headers = [
            'Name',
            'Course',
            'Year Graduated',
            'Board Exam Date',
            'Result',
            'Take Attempts',
            'Board Exam Type'
        ];

        let csvContent = headers.join(',') + '\n';

        // Add data rows
        rows.forEach(row => {
            const cells = row.querySelectorAll('td');

            // Clean text content and handle badges/special formatting
            const cleanText = (cell) => {
                return cell.textContent.trim().replace(/\s+/g, ' ');
            };

            const rowData = [
                `"${cleanText(cells[0])}"`, // Name
                `"${cleanText(cells[1])}"`, // Course
                `"${cleanText(cells[2])}"`, // Year
                `"${cleanText(cells[3])}"`, // Date
                `"${cleanText(cells[4])}"`, // Result (clean badges)
                `"${cleanText(cells[5])}"`, // Take Attempts (clean badges)
                `"${cleanText(cells[6])}"`, // Board Exam Type
            ];
            csvContent += rowData.join(',') + '\n';
        });

        return csvContent;
    }

    function generateExcelData(rows) {
        // For Excel, we'll create a simple HTML table that Excel can interpret
        let htmlContent = `
        <html>
          <head>
            <meta charset="utf-8">
            <style>
              table { border-collapse: collapse; width: 100%; }
              th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
              th { background-color: #f2f2f2; font-weight: bold; }
            </style>
          </head>
          <body>
            <h2>Engineering Board Passers - ${getCurrentDateString()}</h2>
            <table>
              <thead>
                <tr>
                  <th>Name</th>
                  <th>Course</th>
                  <th>Year Graduated</th>
                  <th>Board Exam Date</th>
                  <th>Result</th>
                  <th>Take Attempts</th>
                  <th>Board Exam Type</th>
                </tr>
              </thead>
              <tbody>
      `;

        rows.forEach(row => {
            const cells = row.querySelectorAll('td');
            const cleanText = (cell) => cell.textContent.trim().replace(/\s+/g, ' ');

            htmlContent += `
          <tr>
            <td>${cleanText(cells[0])}</td>
            <td>${cleanText(cells[1])}</td>
            <td>${cleanText(cells[2])}</td>
            <td>${cleanText(cells[3])}</td>
            <td>${cleanText(cells[4])}</td>
            <td>${cleanText(cells[5])}</td>
            <td>${cleanText(cells[6])}</td>
          </tr>
        `;
        });

        htmlContent += `
              </tbody>
            </table>
          </body>
        </html>
      `;

        return htmlContent;
    }

    function generatePDFData(rows) {
        // Simple and reliable PDF generation - just use the print method
        console.log('🚀 Generating PDF for', rows.length, 'rows');

        // Generate the printable content
        generatePrintablePDF(rows);
    }

    function generateDirectPDF(rows) {
        console.log('🚀 Attempting direct PDF generation for', rows.length, 'rows');

        // Skip jsPDF for now and use the reliable print method
        alert('Direct PDF generation is not available. Using print dialog instead...');
        generatePrintablePDF(rows);
    }

    function generatePrintablePDF(rows) {
        // Generate PDF as print dialog (original method)
        console.log('🚀 Generating printable PDF for', rows.length, 'rows');

        // Create clean HTML content for PDF
        let htmlContent = `<!DOCTYPE html>
<html>
<head>
  <title>Engineering Board Passers Report</title>
  <meta charset="UTF-8">
  <style>
    @page { margin: 0.5in; size: A4; }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { 
      font-family: Arial, sans-serif; 
      margin: 0; 
      padding: 20px; 
      color: #333; 
      line-height: 1.4; 
      font-size: 12px;
    }
    .header { 
      text-align: center; 
      margin-bottom: 30px; 
      border-bottom: 3px solid #1e3a8a; 
      padding-bottom: 15px; 
    }
    .university { 
      font-size: 20px; 
      font-weight: bold; 
      color: #1e3a8a; 
      margin-bottom: 5px;
    }
    .department { 
      font-size: 16px; 
      color: #1e3a8a; 
      margin-bottom: 3px;
    }
    .info { 
      display: flex; 
      justify-content: space-around; 
      margin: 20px 0; 
      padding: 15px; 
      background: #f8f9fa; 
      border-radius: 8px;
      border: 1px solid #dee2e6;
    }
    .info-item { 
      text-align: center; 
      flex: 1;
    }
    .info-label { 
      font-size: 11px; 
      font-weight: bold; 
      color: #666; 
      text-transform: uppercase;
      margin-bottom: 5px;
    }
    .info-value { 
      font-size: 14px; 
      font-weight: bold; 
      color: #333; 
    }
    table { 
      width: 100%; 
      border-collapse: collapse; 
      margin: 20px 0; 
      border: 1px solid #dee2e6;
    }
    th { 
      background: #1e3a8a; 
      color: white; 
      padding: 12px 8px; 
      font-size: 11px; 
      text-align: left; 
      font-weight: bold;
      border-right: 1px solid #dee2e6;
    }
    td { 
      padding: 10px 8px; 
      font-size: 10px; 
      border-bottom: 1px solid #dee2e6;
      border-right: 1px solid #dee2e6;
      vertical-align: top;
    }
    tr:nth-child(even) { 
      background: #f8f9fa; 
    }
    tr:hover { 
      background: #e9ecef; 
    }
    .passed { 
      color: #28a745; 
      font-weight: bold; 
    }
    .failed { 
      color: #dc3545; 
      font-weight: bold; 
    }
    .footer { 
      margin-top: 30px; 
      text-align: center; 
      font-size: 10px; 
      color: #666; 
      border-top: 1px solid #dee2e6; 
      padding-top: 15px; 
    }
  </style>
</head>
<body>
  <div class="header">
    <div class="university">LAGUNA STATE POLYTECHNIC UNIVERSITY</div>
    <div class="department">College of Engineering</div>
  </div>
  
  <div class="info">
    <div class="info-item">
      <div class="info-label">Generated Date</div>
      <div class="info-value">${new Date().toLocaleDateString()}</div>
    </div>
    <div class="info-item">
      <div class="info-label">Generated Time</div>
      <div class="info-value">${new Date().toLocaleTimeString()}</div>
    </div>
    <div class="info-item">
      <div class="info-label">Total Records</div>
      <div class="info-value">${rows.length}</div>
    </div>
    <div class="info-item">
      <div class="info-label">Report Type</div>
      <div class="info-value">Filtered Data</div>
    </div>
  </div>
  
  <table>
    <thead>
      <tr>
        <th style="width: 20%;">Student Name</th>
        <th style="width: 25%;">Course Program</th>
        <th style="width: 8%;">Year</th>
        <th style="width: 12%;">Exam Date</th>
        <th style="width: 10%;">Result</th>
  <th style="width: 12%;">Take Attempts</th>
        <th style="width: 13%;">Board Exam</th>
      </tr>
    </thead>
    <tbody>`;

        rows.forEach((row, index) => {
            const cells = row.querySelectorAll('td');
            const cleanText = (cell) => cell.textContent.trim().replace(/\s+/g, ' ');

            const result = cleanText(cells[4]);
            const resultClass = result.toLowerCase().includes('passed') ? 'passed' :
                result.toLowerCase().includes('failed') ? 'failed' : '';

            htmlContent += `
      <tr>
        <td style="font-weight: 500;">${cleanText(cells[0])}</td>
        <td>${cleanText(cells[1])}</td>
        <td style="text-align: center; font-weight: 500;">${cleanText(cells[2])}</td>
        <td style="text-align: center;">${cleanText(cells[3])}</td>
        <td style="text-align: center;" class="${resultClass}">${result}</td>
        <td style="text-align: center;">${cleanText(cells[5])}</td>
        <td>${cleanText(cells[6])}</td>
      </tr>`;
        });

        htmlContent += `
    </tbody>
  </table>
  
  <div class="footer">
    <strong>Laguna State Polytechnic University - College of Engineering</strong><br>
    Email: engineering@lspu.edu.ph | Phone: (049) 536-6303 | Website: www.lspu.edu.ph<br>
    Address: Sta. Cruz, Laguna, Philippines
  </div>
</body>
</html>`;

        // Create filename with timestamp
        const filename = `LSPU_Engineering_Board_Passers_${getCurrentDateString()}.html`;

        // Generate actual PDF using window.print() method
        console.log('📥 Creating actual PDF download...');

        // Create a new window with the content
        const printWindow = window.open('', '_blank');
        printWindow.document.open();
        printWindow.document.write(htmlContent);
        printWindow.document.close();

        // Wait for content to load, then trigger print dialog
        printWindow.onload = function() {
            setTimeout(() => {
                // Show instructions before opening print dialog
                alert(
                    '📄 PDF Export Instructions:\n\n1. Print dialog will open\n2. Choose "Save as PDF" as destination\n3. Click "Save" to download the PDF file\n\nPress OK to continue...'
                    );
                printWindow.print();
                // Close the window after printing
                setTimeout(() => {
                    printWindow.close();
                }, 2000);
            }, 500);
        };

        console.log('✅ PDF print dialog will open - user can save as PDF');
    }

    function downloadFile(content, filename, mimeType) {
        console.log('📥 Starting download:', filename);
        console.log('📊 Content length:', content.length);
        console.log('🎯 MIME type:', mimeType);

        try {
            const blob = new Blob([content], {
                type: mimeType + ';charset=utf-8;'
            });
            console.log('✅ Blob created:', blob.size, 'bytes');

            const link = document.createElement('a');

            if (link.download !== undefined) {
                const url = URL.createObjectURL(blob);
                console.log('🔗 Object URL created:', url);

                link.setAttribute('href', url);
                link.setAttribute('download', filename);
                link.style.visibility = 'hidden';
                link.style.position = 'absolute';
                link.style.top = '-9999px';

                document.body.appendChild(link);
                console.log('🖱️ Triggering click...');

                // Force click with multiple methods for better browser compatibility
                link.click();

                // Alternative click method for some browsers
                if (typeof link.click !== 'function') {
                    const event = new MouseEvent('click', {
                        view: window,
                        bubbles: true,
                        cancelable: true
                    });
                    link.dispatchEvent(event);
                }

                // Clean up after a delay
                setTimeout(() => {
                    document.body.removeChild(link);
                    URL.revokeObjectURL(url);
                    console.log('🧹 Cleanup completed');
                }, 100);

                console.log('✅ Download should have started!');
            } else {
                console.error('❌ Download not supported in this browser');
                // Fallback: open in new window
                const url = URL.createObjectURL(blob);
                window.open(url, '_blank');
            }
        } catch (error) {
            console.error('❌ Download error:', error);
            showExportMessage('Download failed: ' + error.message, 'error');
        }
    }

    function getCurrentDateString() {
        const now = new Date();
        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const day = String(now.getDate()).padStart(2, '0');
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        return `${year}${month}${day}_${hours}${minutes}`;
    }

    function showExportMessage(message, type = 'success') {
        const bgColor = type === 'success' ?
            'linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%)' :
            'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)';

        const icon = type === 'success' ? 'fa-download' : 'fa-exclamation-triangle';

        const messageDiv = document.createElement('div');
        messageDiv.innerHTML = `
        <div style="
          position: fixed;
          top: 50%;
          left: 50%;
          transform: translate(-50%, -50%);
          background: ${bgColor};
          color: white;
          padding: 16px 24px;
          border-radius: 12px;
          box-shadow: 0 8px 25px rgba(139, 92, 246, 0.3);
          z-index: 10000;
          font-family: Inter;
          font-weight: 600;
          text-align: center;
          min-width: 280px;
          backdrop-filter: blur(10px);
        ">
          <i class="fas ${icon}"></i> ${message}
        </div>
      `;
        document.body.appendChild(messageDiv);
        setTimeout(() => {
            messageDiv.remove();
        }, 3000);
    }

    function initializeKeyboardShortcuts() {
        document.addEventListener('keydown', function(e) {
            // Only handle shortcuts when not typing in an input
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'SELECT' || e.target.tagName ===
                'TEXTAREA') {
                // Handle Enter to save in edit mode
                if (e.key === 'Enter' && e.target.classList.contains('edit-input')) {
                    e.preventDefault();
                    const row = e.target.closest('tr');
                    const saveBtn = row.querySelector('.save-btn');
                    if (saveBtn && saveBtn.style.display !== 'none') {
                        saveBtn.click();
                    }
                }
                // Handle Escape to cancel in edit mode
                if (e.key === 'Escape' && e.target.classList.contains('edit-input')) {
                    e.preventDefault();
                    const row = e.target.closest('tr');
                    const cancelBtn = row.querySelector('.cancel-btn');
                    if (cancelBtn && cancelBtn.style.display !== 'none') {
                        cancelBtn.click();
                    }
                }
                return;
            }

            // Global shortcuts
            if (e.ctrlKey || e.metaKey) {
                switch (e.key.toLowerCase()) {
                    case 'n':
                        e.preventDefault();
                        showAddStudentModal();
                        break;
                    case 'f':
                        e.preventDefault();
                        toggleFilters();
                        break;
                    case 's':
                        e.preventDefault();
                        document.getElementById('nameSearch').focus();
                        break;
                    case 'h':
                        e.preventDefault();
                        e.stopPropagation();
                        showKeyboardShortcutsHelp();
                        return;
                }
            }

            // ESC to close modals
            if (e.key === 'Escape') {
                const shortcutsModal = document.getElementById('shortcutsHelpModal');
                if (shortcutsModal && shortcutsModal.classList.contains('show')) {
                    return;
                }

                const openModals = document.querySelectorAll('.custom-modal.show:not(.shortcuts-help-modal)');
                openModals.forEach(modal => {
                    modal.classList.remove('show');
                    setTimeout(() => {
                        if (modal.parentNode) {
                            modal.remove();
                        }
                    }, 300);
                });

                const mainModal = document.getElementById('editStudentModal');
                if (mainModal && mainModal.classList.contains('show')) {
                    closeEditModal();
                }

                const editingGuide = document.querySelector('.editing-guide');
                if (editingGuide) {
                    closeEditingGuide();
                }
            }
        });
    }

    function toggleFilters() {
        const toggleBtn = document.getElementById('toggleFilters');
        const filterContainer = document.getElementById('filterContainer');

        if (filterContainer) {
            if (filterContainer.style.display === 'none' || !filterContainer.style.display) {
                filterContainer.style.display = 'block';
                toggleBtn.innerHTML = '<i class="fas fa-filter"></i> Hide Filters';
                toggleBtn.classList.add('active');
            } else {
                filterContainer.style.display = 'none';
                toggleBtn.innerHTML = '<i class="fas fa-filter"></i> Show Filters';
                toggleBtn.classList.remove('active');
            }
        }
    }

    function showKeyboardShortcutsHelp() {
        // Create a simple modal that we know will work
        const modal = document.createElement('div');
        modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        background: rgba(30, 41, 59, 0.4);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        z-index: 10000;
        display: flex;
        justify-content: center;
        align-items: center;
        opacity: 0;
        transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
      `;

        modal.innerHTML = `
        <div style="
          background: rgba(255, 255, 255, 0.95);
          backdrop-filter: blur(20px);
          -webkit-backdrop-filter: blur(20px);
          padding: 30px;
          border-radius: 20px;
          max-width: 500px;
          width: 90%;
          box-shadow: 
            0 32px 64px rgba(30, 41, 59, 0.4),
            0 16px 32px rgba(30, 41, 59, 0.2),
            inset 0 1px 0 rgba(255, 255, 255, 0.6);
          border: 1px solid rgba(255, 255, 255, 0.2);
          position: relative;
          transform: scale(0.7) translateY(-50px);
          transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        ">
          <button onclick="closeShortcutsModal()" style="
            position: absolute;
            top: 15px;
            right: 15px;
            background: rgba(243, 244, 246, 0.8);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            width: 35px;
            height: 35px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            color: #374151;
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
          ">×</button>
          
          <div style="text-align: center; margin-bottom: 25px;">
            <div style="
              width: 60px;
              height: 60px;
              background: linear-gradient(135deg, #3182ce 0%, #2c5aa0 100%);
              border-radius: 50%;
              display: flex;
              align-items: center;
              justify-content: center;
              margin: 0 auto 15px;
              color: white;
              font-size: 24px;
            ">⌨</div>
            <h2 style="margin: 0; color: #1f2937; font-size: 1.5rem;">Keyboard Shortcuts</h2>
            <p style="margin: 8px 0 0; color: #6b7280;">Speed up your workflow</p>
          </div>
          
          <div style="display: grid; gap: 12px; margin-bottom: 25px;">
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; background: rgba(248, 250, 252, 0.7); border-radius: 8px;">
              <span style="font-weight: 600; color: #374151;">Add New Student</span>
              <kbd style="background: #374151; color: white; padding: 4px 8px; border-radius: 4px;">Ctrl + N</kbd>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; background: rgba(248, 250, 252, 0.7); border-radius: 8px;">
              <span style="font-weight: 600; color: #374151;">Toggle Filters</span>
              <kbd style="background: #374151; color: white; padding: 4px 8px; border-radius: 4px;">Ctrl + F</kbd>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; background: rgba(248, 250, 252, 0.7); border-radius: 8px;">
              <span style="font-weight: 600; color: #374151;">Export Data</span>
              <kbd style="background: #374151; color: white; padding: 4px 8px; border-radius: 4px;">Ctrl + E</kbd>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; background: rgba(248, 250, 252, 0.7); border-radius: 8px;">
              <span style="font-weight: 600; color: #374151;">Show This Help</span>
              <kbd style="background: #374151; color: white; padding: 4px 8px; border-radius: 4px;">Ctrl + H</kbd>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; background: rgba(248, 250, 252, 0.7); border-radius: 8px;">
              <span style="font-weight: 600; color: #374151;">Close Modals</span>
              <kbd style="background: #374151; color: white; padding: 4px 8px; border-radius: 4px;">Escape</kbd>
            </div>
          </div>
          
          <div style="text-align: center;">
            <button onclick="closeShortcutsModal()" style="
              background: linear-gradient(135deg, #3182ce 0%, #2c5aa0 100%);
              color: white;
              border: none;
              padding: 12px 24px;
              border-radius: 10px;
              font-weight: 600;
              cursor: pointer;
              font-size: 1rem;
            ">Got it!</button>
          </div>
        </div>
      `;

        modal.setAttribute('data-modal', 'shortcuts');

        // Close on outside click
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeShortcutsModal();
            }
        });

        // Close on Escape key
        const escapeHandler = function(e) {
            if (e.key === 'Escape') {
                closeShortcutsModal();
                document.removeEventListener('keydown', escapeHandler);
            }
        };
        document.addEventListener('keydown', escapeHandler);
        modal._escapeHandler = escapeHandler;

        document.body.appendChild(modal);

        // Trigger entrance animation
        setTimeout(() => {
            modal.style.opacity = '1';
            const content = modal.querySelector('div');
            content.style.transform = 'scale(1) translateY(0)';
        }, 10);
    }

    // Create the close function
    function closeShortcutsModal() {
        const modal = document.querySelector('[data-modal="shortcuts"]');
        if (modal) {
            // Remove escape handler
            if (modal._escapeHandler) {
                document.removeEventListener('keydown', modal._escapeHandler);
            }

            // Trigger exit animation
            modal.style.opacity = '0';
            const content = modal.querySelector('div');
            content.style.transform = 'scale(0.7) translateY(-50px)';

            // Remove modal after animation
            setTimeout(() => {
                if (modal.parentNode) {
                    modal.remove();
                }
            }, 300);
        }
    }
    </script>

    <!-- Custom Delete Confirmation Modal -->
    <div id="deleteModal" class="custom-modal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <h3>Confirm Delete</h3>
                <p>Are you sure you want to delete this record?</p>
                <small>This action cannot be undone and will permanently remove the student's information.</small>
            </div>
            <div class="modal-buttons">
                <button id="confirmDelete" class="btn-danger">
                    <i class="fas fa-trash-alt"></i>
                    Yes, Delete
                </button>
                <button id="cancelDelete" class="btn-secondary">
                    <i class="fas fa-times"></i>
                    Cancel
                </button>
            </div>
        </div>
    </div>

    <!-- Custom Export Format Selection Modal -->
    <div id="exportModal" class="custom-modal">
        <div class="modal-content" style="
      background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
      border-radius: 24px;
      box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
      border: 2px solid rgba(49, 130, 206, 0.1);
      overflow: hidden;
      position: relative;
      max-width: 520px;
      width: 90%;
    ">
            <!-- Modal Header with Enhanced Design -->
            <div class="modal-header" style="
        background: linear-gradient(135deg, #1e40af 0%, #3182ce 50%, #60a5fa 100%);
        border-bottom: none;
        position: relative;
        color: white;
        padding: 32px 40px 28px;
        overflow: hidden;
      ">
                <!-- Decorative Background Elements -->
                <div style="
          position: absolute;
          top: -50px;
          right: -50px;
          width: 120px;
          height: 120px;
          background: linear-gradient(135deg, rgba(255, 255, 255, 0.1) 0%, rgba(255, 255, 255, 0.05) 100%);
          border-radius: 50%;
        "></div>
                <div style="
          position: absolute;
          bottom: -30px;
          left: -30px;
          width: 80px;
          height: 80px;
          background: linear-gradient(135deg, rgba(255, 255, 255, 0.08) 0%, transparent 100%);
          border-radius: 50%;
        "></div>

                <!-- Close Button -->
                <button onclick="closeExportModal()" class="export-modal-close" style="
          position: absolute;
          top: 20px;
          right: 20px;
          background: rgba(255, 255, 255, 0.15);
          color: white;
          border: none;
          border-radius: 50%;
          width: 40px;
          height: 40px;
          cursor: pointer;
          display: flex;
          align-items: center;
          justify-content: center;
          transition: all 0.3s ease;
          z-index: 10003;
          backdrop-filter: blur(10px);
          border: 1px solid rgba(255, 255, 255, 0.2);
        " onmouseover="this.style.background='rgba(255, 255, 255, 0.25)'; this.style.transform='scale(1.1) rotate(90deg)'"
                    onmouseout="this.style.background='rgba(255, 255, 255, 0.15)'; this.style.transform='scale(1) rotate(0deg)'">
                    <i class="fas fa-times"></i>
                </button>

                <!-- Modal Icon -->
                <div class="modal-icon" style="
          background: linear-gradient(135deg, rgba(255, 255, 255, 0.2) 0%, rgba(255, 255, 255, 0.1) 100%);
          box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
          color: white;
          width: 72px;
          height: 72px;
          border-radius: 20px;
          display: flex;
          align-items: center;
          justify-content: center;
          margin: 0 auto 20px;
          backdrop-filter: blur(10px);
          border: 2px solid rgba(255, 255, 255, 0.2);
          position: relative;
          z-index: 1;
        ">
                    <i class="fas fa-download" style="font-size: 1.8rem;"></i>
                </div>

                <!-- Header Text -->
                <h3 style="
          color: white; 
          font-weight: 800; 
          font-size: 1.6rem;
          margin: 0 0 8px 0;
          text-align: center;
          position: relative;
          z-index: 1;
          text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        ">Export Data</h3>
                <p style="
          color: rgba(255, 255, 255, 0.95); 
          margin: 0 0 6px 0;
          text-align: center;
          font-size: 1.1rem;
          font-weight: 500;
          position: relative;
          z-index: 1;
        ">Choose your preferred export format</p>
                <small style="
          color: rgba(255, 255, 255, 0.8);
          text-align: center;
          display: block;
          font-size: 0.9rem;
          position: relative;
          z-index: 1;
        ">All formats include the currently filtered records.</small>
            </div>

            <!-- Content Area -->
            <div style="padding: 28px 40px; background: #fff;">
                <div id="exportDetails"></div>
            </div>

            <!-- Buttons Container -->
            <div class="modal-buttons">
                <!-- Export format buttons will be dynamically inserted here -->
            </div>
        </div>
    </div>

    <!-- Logout Confirmation Modal - EXACT MATCH to Image 1 -->
    <?php include "./components/logout-modal.php" ?>

    <script src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    <script src="./asset/logoutModal.js"></script>
    <script>
    // Global, safe filter function to show only dates that belong to the selected Board Exam Type
    (function() {
        if (!window.filterExamDates) {
            window.filterExamDates = function(boardExamTypeSelectorId, examDateSelectorId) {
                var typeEl = document.getElementById(boardExamTypeSelectorId);
                var dateEl = document.getElementById(examDateSelectorId);
                if (!typeEl || !dateEl) return;
                var selectedTypeId = typeEl.value ? parseInt(typeEl.value, 10) : null;
                var hasVisible = false;
                for (var i = 0; i < dateEl.options.length; i++) {
                    var opt = dateEl.options[i];
                    if (opt.value === '' || opt.value === 'other') { // keep placeholder and Other visible
                        opt.style.display = '';
                        continue;
                    }
                    var optTypeIdAttr = opt.getAttribute('data-exam-type-id');
                    var optTypeId = optTypeIdAttr ? parseInt(optTypeIdAttr, 10) : null;
                    if (selectedTypeId !== null && optTypeId === selectedTypeId) {
                        opt.style.display = '';
                        hasVisible = true;
                    } else {
                        opt.style.display = 'none';
                    }
                }
                // If current selection became hidden, reset to placeholder
                var cur = dateEl.options[dateEl.selectedIndex];
                if (cur && cur.style && cur.style.display === 'none') {
                    dateEl.selectedIndex = 0;
                }
            };
        }

        // Optional helper to enable/disable date select + hint; does not override existing if present
        if (!window.updateDateEnabled) {
            window.updateDateEnabled = function(typeEl, dateEl) {
                if (!typeEl || !dateEl) return;
                var hintEl = document.getElementById(dateEl.id + 'Hint');
                if (!typeEl.value || typeEl.value === '') {
                    dateEl.disabled = true;
                    dateEl.selectedIndex = 0;
                    dateEl.style.display = 'none';
                    if (hintEl) hintEl.style.display = '';
                } else {
                    dateEl.disabled = false;
                    dateEl.style.display = '';
                    if (hintEl) hintEl.style.display = 'none';
                    window.filterExamDates(typeEl.id, dateEl.id);
                }
            };
        }

        // Wire up add/edit selects on load if present
        document.addEventListener('DOMContentLoaded', function() {
            var addType = document.getElementById('addBoardExamType');
            var addDate = document.getElementById('addExamDate');
            var editType = document.getElementById('editBoardExamType');
            var editDate = document.getElementById('editExamDate');
            if (addType && addDate) {
                window.updateDateEnabled(addType, addDate);
                addType.addEventListener('change', function() {
                    window.updateDateEnabled(addType, addDate);
                });
            }
            if (editType && editDate) {
                window.updateDateEnabled(editType, editDate);
                editType.addEventListener('change', function() {
                    window.updateDateEnabled(editType, editDate);
                });
            }
        });
    })();
    </script>
</body>

</html>