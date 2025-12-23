<?php
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Only allow College of Engineering admin
if (!isset($_SESSION["users"]) || $_SESSION["users"] !== 'eng_admin@lspu.edu.ph') {
    header("Location: index.php");
    exit();
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "project_db";
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

// Utility: flash messages via query params
$flash = ['type' => '', 'msg' => ''];
if (!empty($_GET['success'])) { 
    $flash['type'] = 'success'; 
    $flash['msg'] = $_GET['success']; // Don't htmlspecialchars here, do it in output
}
if (!empty($_GET['error'])) { 
    $flash['type'] = 'error'; 
    $flash['msg'] = $_GET['error']; // Don't htmlspecialchars here, do it in output
}

// Handle add exam type
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["add_exam_type"])) {
  $name = trim($_POST['new_exam_type'] ?? '');
  if ($name === '') {
    header("Location: manage_data_engineering.php?error=empty_exam_type"); exit();
  }
  // Check duplicates
  $chk = $conn->prepare("SELECT id FROM board_exam_types WHERE LOWER(TRIM(exam_type_name)) = LOWER(TRIM(?)) AND department='Engineering'");
  $chk->bind_param('s', $name); $chk->execute(); $chk->store_result();
  if ($chk->num_rows > 0) { $chk->close(); header("Location: manage_data_engineering.php?error=exam_type_exists"); exit(); }
  $chk->close();
  $ins = $conn->prepare("INSERT INTO board_exam_types (exam_type_name, department) VALUES (?, 'Engineering')");
  $ins->bind_param('s', $name);
  if ($ins->execute()) { $ins->close(); header("Location: manage_data_engineering.php?success=exam_type_added"); exit(); }
  $ins->close(); header("Location: manage_data_engineering.php?error=exam_type_add_failed"); exit();
}

// Handle edit exam type
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['edit_exam_type'])) {
  $id = intval($_POST['exam_type_id'] ?? 0);
  $newname = trim($_POST['edit_exam_type_name'] ?? '');
  if ($id <= 0 || $newname === '') { header("Location: manage_data_engineering.php?error=invalid_edit"); exit(); }
  // duplicate check
  $chk = $conn->prepare("SELECT id FROM board_exam_types WHERE LOWER(TRIM(exam_type_name)) = LOWER(TRIM(?)) AND department='Engineering' AND id != ?");
  $chk->bind_param('si', $newname, $id); $chk->execute(); $res = $chk->get_result();
  if ($res && $res->num_rows > 0) { $chk->close(); header("Location: manage_data_engineering.php?error=exam_type_exists"); exit(); }
  $chk->close();
  $up = $conn->prepare("UPDATE board_exam_types SET exam_type_name = ? WHERE id = ? AND department='Engineering'");
  $up->bind_param('si', $newname, $id);
  if ($up->execute()) { $up->close(); header("Location: manage_data_engineering.php?success=exam_type_updated"); exit(); }
  $up->close(); header("Location: manage_data_engineering.php?error=exam_type_update_failed"); exit();
}

// Handle delete exam type (soft delete)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_exam_type'])) {
  $id = intval($_POST['exam_type_id'] ?? 0);
  if ($id <= 0) { header("Location: manage_data_engineering.php?error=invalid_delete"); exit(); }
  // Soft delete - mark as deleted instead of removing
  $del = $conn->prepare("UPDATE board_exam_types SET is_deleted = 1, deleted_at = NOW() WHERE id = ? AND department='Engineering'"); 
  $del->bind_param('i', $id);
  if ($del->execute()) { $del->close(); header("Location: manage_data_engineering.php?success=Exam type removed from view"); exit(); }
  $del->close(); header("Location: manage_data_engineering.php?error=exam_type_delete_failed"); exit();
}

// Handle add subject (for a given exam type)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['add_subject'])) {
  $exam_type_id = intval($_POST['subject_exam_type'] ?? 0);
  $subject_name = trim($_POST['new_subject'] ?? '');
  $total_items = intval($_POST['new_subject_total_items'] ?? 50);
  if ($exam_type_id <= 0 || $subject_name === '') { header("Location: manage_data_engineering.php?error=invalid_subject"); exit(); }
  // insert subject
  $ins = $conn->prepare("INSERT INTO subjects (subject_name, total_items, department) VALUES (?, ?, 'Engineering')");
  $ins->bind_param('si', $subject_name, $total_items);
  if (!$ins->execute()) { $ins->close(); header("Location: manage_data_engineering.php?error=subject_add_failed"); exit(); }
  $sid = $ins->insert_id; $ins->close();
  // mapping
  $m = $conn->prepare("INSERT INTO subject_exam_types (subject_id, exam_type_id) VALUES (?, ?)"); $m->bind_param('ii', $sid, $exam_type_id); $m->execute(); $m->close();
  header("Location: manage_data_engineering.php?success=subject_added&exam_type_id=" . $exam_type_id);
  exit();
}

// Handle edit subject
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['edit_subject'])) {
  $sid = intval($_POST['subject_id'] ?? 0);
  $name = trim($_POST['edit_subject_name'] ?? '');
  $items = intval($_POST['edit_subject_items'] ?? 50);
  if ($sid <= 0 || $name === '') { header("Location: manage_data_engineering.php?error=invalid_subject_edit"); exit(); }
  $chk = $conn->prepare("SELECT COUNT(*) as cnt FROM board_passer_subjects WHERE subject_id = ?"); $chk->bind_param('i', $sid); $chk->execute(); $cres = $chk->get_result(); $crow = $cres ? $cres->fetch_assoc() : null; $countUsage = intval($crow['cnt'] ?? 0); $chk->close();
  if ($countUsage > 0) { header("Location: manage_data_engineering.php?error=subject_in_use"); exit(); }
  $up = $conn->prepare("UPDATE subjects SET subject_name = ?, total_items = ? WHERE id = ? AND department = 'Engineering'"); $up->bind_param('sii', $name, $items, $sid);
  if ($up->execute()) { $up->close(); header("Location: manage_data_engineering.php?success=subject_updated"); exit(); }
  $up->close(); header("Location: manage_data_engineering.php?error=subject_update_failed"); exit();
}

// Handle delete subject (soft delete)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_subject'])) {
  $sid = intval($_POST['subject_id'] ?? 0);
  if ($sid <= 0) { header("Location: manage_data_engineering.php?error=invalid_subject_delete"); exit(); }
  // Soft delete - mark as deleted instead of removing
  $del = $conn->prepare("UPDATE subjects SET is_deleted = 1, deleted_at = NOW() WHERE id = ? AND department = 'Engineering'"); 
  $del->bind_param('i', $sid);
  if ($del->execute()) { $del->close(); header("Location: manage_data_engineering.php?success=Subject removed from view"); exit(); }
  $del->close(); header("Location: manage_data_engineering.php?error=subject_delete_failed"); exit();
}

// -------------------------
// Handle board exam dates CRUD (per exam type)
// -------------------------
// Add exam date
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['add_exam_date'])) {
  $exam_type_id = intval($_POST['exam_type_id'] ?? 0);
  $exam_date = trim($_POST['new_exam_date'] ?? '');
  // Convert YYYY-MM to YYYY-MM-01 for database storage
  if (preg_match('/^\d{4}-\d{2}$/', $exam_date)) {
    $exam_date .= '-01';
  }
  $exam_desc = trim($_POST['exam_description'] ?? '');
  if ($exam_type_id <= 0 || $exam_date === '') { header("Location: manage_data_engineering.php?error=invalid_exam_date"); exit(); }
  // duplicate check for same date and exam type
  $chk = $conn->prepare("SELECT id FROM board_exam_dates WHERE exam_date = ? AND exam_type_id = ? AND department = 'Engineering'");
  $chk->bind_param('si', $exam_date, $exam_type_id); $chk->execute(); $chk->store_result();
  if ($chk->num_rows > 0) { $chk->close(); header("Location: manage_data_engineering.php?error=exam_date_exists&exam_type_id=" . $exam_type_id); exit(); }
  $chk->close();
  $ins = $conn->prepare("INSERT INTO board_exam_dates (exam_date, exam_description, exam_type_id, department) VALUES (?, ?, ?, 'Engineering')");
  $ins->bind_param('ssi', $exam_date, $exam_desc, $exam_type_id);
  if ($ins->execute()) { $ins->close(); header("Location: manage_data_engineering.php?success=exam_date_added&exam_type_id=" . $exam_type_id); exit(); }
  $ins->close(); header("Location: manage_data_engineering.php?error=exam_date_add_failed&exam_type_id=" . $exam_type_id); exit();
}

// Edit exam date
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['edit_exam_date'])) {
  $eid = intval($_POST['exam_date_id'] ?? 0);
  $edate = trim($_POST['edit_exam_date_value'] ?? '');
  // Convert YYYY-MM to YYYY-MM-01 for database storage
  if (preg_match('/^\d{4}-\d{2}$/', $edate)) {
    $edate .= '-01';
  }
  $edesc = trim($_POST['edit_exam_date_description'] ?? '');
  if ($eid <= 0 || $edate === '') { header("Location: manage_data_engineering.php?error=invalid_exam_date_edit"); exit(); }
  // fetch exam_type_id for redirect and duplicate checks
  $g = $conn->prepare("SELECT exam_type_id FROM board_exam_dates WHERE id = ? AND department = 'Engineering'"); $g->bind_param('i', $eid); $g->execute(); $gres = $g->get_result(); $grow = $gres ? $gres->fetch_assoc() : null; $g->close();
  $exam_type_id = intval($grow['exam_type_id'] ?? 0);
  if ($exam_type_id <= 0) { header("Location: manage_data_engineering.php?error=exam_date_not_found"); exit(); }
  // duplicate check: another row with same date for same exam type
  $chk = $conn->prepare("SELECT id FROM board_exam_dates WHERE exam_date = ? AND exam_type_id = ? AND id != ? AND department = 'Engineering'"); $chk->bind_param('sii', $edate, $exam_type_id, $eid); $chk->execute(); $res = $chk->get_result();
  if ($res && $res->num_rows > 0) { $chk->close(); header("Location: manage_data_engineering.php?error=exam_date_exists&exam_type_id=" . $exam_type_id); exit(); }
  $chk->close();
  $up = $conn->prepare("UPDATE board_exam_dates SET exam_date = ?, exam_description = ? WHERE id = ? AND department = 'Engineering'"); $up->bind_param('ssi', $edate, $edesc, $eid);
  if ($up->execute()) { $up->close(); header("Location: manage_data_engineering.php?success=exam_date_updated&exam_type_id=" . $exam_type_id); exit(); }
  $up->close(); header("Location: manage_data_engineering.php?error=exam_date_update_failed&exam_type_id=" . $exam_type_id); exit();
}

// Delete exam date (soft delete)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_exam_date'])) {
  $eid = intval($_POST['exam_date_id'] ?? 0);
  if ($eid <= 0) { header("Location: manage_data_engineering.php?error=invalid_exam_date_delete"); exit(); }
  // Soft delete - mark as deleted instead of removing
  $del = $conn->prepare("UPDATE board_exam_dates SET is_deleted = 1, deleted_at = NOW() WHERE id = ? AND department = 'Engineering'"); 
  $del->bind_param('i', $eid);
  if ($del->execute()) { $del->close(); header("Location: manage_data_engineering.php?success=Exam date removed from view"); exit(); }
  $del->close(); header("Location: manage_data_engineering.php?error=exam_date_delete_failed"); exit();
}

// -------------------------
// Handle courses CRUD (add / edit / delete)
// -------------------------
// Handle add course
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['add_course'])) {
  error_log("ADD COURSE REQUEST RECEIVED");
  error_log("POST data: " . print_r($_POST, true));
  
  $new_course = trim($_POST['new_course'] ?? '');
  error_log("Course name after trim: '$new_course'");
  
  if ($new_course === '') { 
    error_log("Empty course name - redirecting");
    header("Location: manage_data_engineering.php?error=empty_course"); 
    exit(); 
  }
  
  // duplicate check
  $chk = $conn->prepare("SELECT id FROM courses WHERE LOWER(TRIM(course_name)) = LOWER(TRIM(?)) AND department = 'Engineering'");
  $chk->bind_param('s', $new_course); 
  $chk->execute(); 
  $chk->store_result();
  
  if ($chk->num_rows > 0) { 
    error_log("Duplicate course found - redirecting");
    $chk->close(); 
    header("Location: manage_data_engineering.php?error=course_exists"); 
    exit(); 
  }
  $chk->close();
  
  // Insert
  $ins = $conn->prepare("INSERT INTO courses (course_name, department) VALUES (?, 'Engineering')"); 
  $ins->bind_param('s', $new_course);
  
  if ($ins->execute()) { 
    $insert_id = $ins->insert_id;
    error_log("Course inserted successfully with ID: $insert_id");
    $ins->close(); 
    header("Location: manage_data_engineering.php?success=Course added successfully"); 
    exit(); 
  } else {
    error_log("Insert failed: " . $ins->error);
    $ins->close(); 
    header("Location: manage_data_engineering.php?error=course_add_failed: " . $conn->error); 
    exit();
  }
}

// Edit course
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['edit_course'])) {
  $cid = intval($_POST['course_id'] ?? 0);
  $cname = trim($_POST['edit_course_name'] ?? '');
  if ($cid <= 0 || $cname === '') { header("Location: manage_data_engineering.php?error=invalid_course_edit"); exit(); }
  $chk = $conn->prepare("SELECT id FROM courses WHERE LOWER(TRIM(course_name)) = LOWER(TRIM(?)) AND department = 'Engineering' AND id != ?"); $chk->bind_param('si', $cname, $cid); $chk->execute(); $res = $chk->get_result();
  if ($res && $res->num_rows > 0) { $chk->close(); header("Location: manage_data_engineering.php?error=course_exists"); exit(); }
  $chk->close();
  $up = $conn->prepare("UPDATE courses SET course_name = ? WHERE id = ? AND department = 'Engineering'"); $up->bind_param('si', $cname, $cid);
  if ($up->execute()) { $up->close(); header("Location: manage_data_engineering.php?success=course_updated"); exit(); }
  $up->close(); header("Location: manage_data_engineering.php?error=course_update_failed"); exit();
}

// Delete course (soft delete)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_course'])) {
  $cid = intval($_POST['course_id'] ?? 0);
  if ($cid <= 0) { header("Location: manage_data_engineering.php?error=invalid_course_delete"); exit(); }
  // Soft delete - mark as deleted instead of removing
  $del = $conn->prepare("UPDATE courses SET is_deleted = 1, deleted_at = NOW() WHERE id = ? AND department = 'Engineering'"); 
  $del->bind_param('i', $cid);
  if ($del->execute()) { $del->close(); header("Location: manage_data_engineering.php?success=Course removed from view"); exit(); }
  $del->close(); header("Location: manage_data_engineering.php?error=course_delete_failed"); exit();
}

// Fetch exam types for display (exclude deleted)
$etres = $conn->query("SELECT id, TRIM(exam_type_name) as name FROM board_exam_types WHERE department='Engineering' AND is_deleted = 0 ORDER BY exam_type_name ASC");
$exam_types = [];
while ($r = $etres->fetch_assoc()) { $exam_types[] = $r; }

// determine selected exam type if provided by query param
$selected_exam_type_id = intval($_GET['exam_type_id'] ?? 0);
if ($selected_exam_type_id === 0 && !empty($exam_types)) { $selected_exam_type_id = $exam_types[0]['id']; }

// Fetch subjects for selected exam type (exclude deleted)
$subjects = [];
if ($selected_exam_type_id > 0) {
  $stmt = $conn->prepare("SELECT s.id, TRIM(s.subject_name) as subject_name, s.total_items FROM subjects s JOIN subject_exam_types setmap ON setmap.subject_id = s.id WHERE setmap.exam_type_id = ? AND s.department = 'Engineering' AND s.is_deleted = 0 ORDER BY s.subject_name ASC");
  $stmt->bind_param('i', $selected_exam_type_id); $stmt->execute(); $sres = $stmt->get_result();
  while ($sr = $sres->fetch_assoc()) $subjects[] = $sr;
  $stmt->close();
}

// Fetch courses list for display (exclude deleted)
$courses = [];
$cres = $conn->query("SELECT id, TRIM(course_name) as name FROM courses WHERE department='Engineering' AND is_deleted = 0 ORDER BY course_name ASC");
if ($cres) { while ($cr = $cres->fetch_assoc()) $courses[] = $cr; }

// Fetch exam dates for selected exam type (exclude deleted)
$exam_dates = [];
if ($selected_exam_type_id > 0) {
  $dstmt = $conn->prepare("SELECT id, exam_date, TRIM(exam_description) as exam_description FROM board_exam_dates WHERE exam_type_id = ? AND department = 'Engineering' AND is_deleted = 0 ORDER BY exam_date ASC");
  $dstmt->bind_param('i', $selected_exam_type_id); $dstmt->execute(); $dres = $dstmt->get_result();
  while ($dr = $dres->fetch_assoc()) $exam_dates[] = $dr;
  $dstmt->close();
}

?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manage Data - Engineering</title>
    <!-- <link rel="stylesheet" href="style.css" /> -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/sidebar.css" />
    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Inter', sans-serif;
        background: linear-gradient(135deg, #E2DFDA 0%, #CBDED3 100%);
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
        padding: 32px;
        min-height: calc(100vh - 70px);
    }

    .grid {
        display: grid;
        grid-template-columns: 420px 1fr;
        gap: 24px;
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

        .grid {
            grid-template-columns: 1fr;
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

    .right-column {
        display: grid;
        grid-template-rows: auto auto;
        gap: 24px;
    }

    .panel {
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 10px 40px rgba(145, 179, 142, 0.15);
        border: 2px solid rgba(145, 179, 142, 0.15);
        transition: all 0.3s ease;
    }

    .panel:hover {
        box-shadow: 0 15px 50px rgba(145, 179, 142, 0.25);
        border-color: rgba(145, 179, 142, 0.25);
        transform: translateY(-2px);
    }

    .panel h3 {
        margin: 0 0 14px 0;
        color: #2d5a2e;
        font-size: 1.1rem;
        padding-bottom: 8px;
        border-bottom: 2px solid #8BA49A;
    }

    .list-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 14px 16px;
        border-radius: 12px;
        background: linear-gradient(135deg, #E2DFDA 0%, #CBDED3 100%);
        border: 1px solid rgba(145, 179, 142, 0.2);
        transition: all 0.2s ease;
    }

    .list-item:hover {
        background: linear-gradient(135deg, #c5dcc2 0%, #a8c5a5 100%);
        border-color: rgba(145, 179, 142, 0.4);
        transform: translateX(4px);
        box-shadow: 0 4px 12px rgba(145, 179, 142, 0.2);
    }

    .list-item+.list-item {
        margin-top: 10px;
    }

    .small {
        font-size: 0.85rem;
        color: #64748b;
        font-weight: 500;
    }

    .btn {
        background: linear-gradient(135deg, #8BA49A 0%, #3B6255 100%);
        color: #fff;
        padding: 10px 16px;
        border-radius: 10px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        border: none;
        cursor: pointer;
        font-weight: 600;
        font-size: 0.9rem;
        transition: all 0.3s ease;
        box-shadow: 0 4px 14px rgba(145, 179, 142, 0.3);
    }

    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(145, 179, 142, 0.4);
    }

    .muted {
        color: #64748b;
        font-style: italic;
    }

    .form-row {
        margin-bottom: 12px;
    }

    input[type="text"],
    input[type="number"],
    input[type="date"],
    input[type="month"],
    select {
        width: 100%;
        padding: 12px 16px;
        border: 2px solid #e2e8f0;
        border-radius: 10px;
        font-size: 0.95rem;
        transition: all 0.2s ease;
        background: #ffffff;
    }

    input[type="text"]:focus,
    input[type="number"]:focus,
    input[type="date"]:focus,
    input[type="month"]:focus,
    select:focus {
        outline: none;
        border-color: #8BA49A;
        box-shadow: 0 0 0 3px rgba(145, 179, 142, 0.15);
    }

    .actions {
        display: flex;
        gap: 10px;
    }

    .danger {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        box-shadow: 0 4px 14px rgba(239, 68, 68, 0.2);
    }

    .danger:hover {
        box-shadow: 0 8px 25px rgba(239, 68, 68, 0.4);
    }

    .edit-btn {
        background: linear-gradient(135deg, #8BA49A 0%, #3B6255 100%);
        color: #fff;
        padding: 10px 16px;
        border-radius: 10px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        border: none;
        cursor: pointer;
        font-weight: 600;
        font-size: 0.9rem;
        transition: all 0.3s ease;
        box-shadow: 0 4px 14px rgba(145, 179, 142, 0.3);
    }

    .edit-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(145, 179, 142, 0.4);
    }

    .msg {
        padding: 16px 20px;
        border-radius: 12px;
        margin-bottom: 20px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 10px;
        box-shadow: 0 4px 14px rgba(0, 0, 0, 0.1);
    }

    .msg.success {
        background: linear-gradient(135deg, #d1fae5, #a7f3d0);
        color: #065f46;
        border: 2px solid #6ee7b7;
    }

    .msg.success:before {
        content: "✓";
        font-size: 1.2rem;
        font-weight: bold;
    }

    .msg.error {
        background: linear-gradient(135deg, #fee2e2, #fecaca);
        color: #7f1d1d;
        border: 2px solid #fca5a5;
    }

    .msg.error:before {
        content: "✗";
        font-size: 1.2rem;
        font-weight: bold;
    }

    .view-btn {
        background: linear-gradient(135deg, #8BA49A 0%, #3B6255 100%);
        color: #fff;
        padding: 10px 16px;
        border-radius: 10px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        border: none;
        cursor: pointer;
        font-weight: 600;
        font-size: 0.9rem;
        transition: all 0.3s ease;
        box-shadow: 0 4px 14px rgba(145, 179, 142, 0.3);
    }

    .view-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(145, 179, 142, 0.4);
    }

    .cancel-btn {
        background: linear-gradient(135deg, #64748b 0%, #475569 100%);
        color: #fff;
        padding: 10px 16px;
        border-radius: 10px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        border: none;
        cursor: pointer;
        font-weight: 600;
        font-size: 0.9rem;
        transition: all 0.3s ease;
        box-shadow: 0 4px 14px rgba(100, 116, 139, 0.2);
    }

    .cancel-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(100, 116, 139, 0.4);
    }

    .modal-content {
        background: linear-gradient(135deg, #ffffff 0%, #E2DFDA 100%);
        padding: 40px;
        border-radius: 24px;
        width: 520px;
        box-shadow: 0 30px 80px rgba(145, 179, 142, 0.4), 0 0 0 1px rgba(145, 179, 142, 0.2);
        border: none;
        animation: modalSlideIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        position: relative;
        overflow: hidden;
    }

    .modal-content::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, #8BA49A 0%, #3B6255 50%, #8BA49A 100%);
        background-size: 200% 100%;
        animation: shimmer 3s linear infinite;
    }

    @keyframes shimmer {
        0% {
            background-position: -200% 0;
        }

        100% {
            background-position: 200% 0;
        }
    }

    @keyframes modalSlideIn {
        from {
            opacity: 0;
            transform: scale(0.9) translateY(-40px);
        }

        to {
            opacity: 1;
            transform: scale(1) translateY(0);
        }
    }

    .modal-content h3 {
        color: #2d5a2e;
        font-size: 1.4rem;
        margin-bottom: 24px;
        padding-bottom: 16px;
        border-bottom: 3px solid #8BA49A;
        display: flex;
        align-items: center;
        gap: 12px;
        font-weight: 800;
        letter-spacing: -0.5px;
    }

    .modal-content label {
        display: block;
        margin-bottom: 10px;
        color: #2d5a2e;
        font-weight: 700;
        font-size: 0.95rem;
        letter-spacing: 0.3px;
    }

    .modal-content input[type="text"],
    .modal-content input[type="number"],
    .modal-content input[type="date"],
    .modal-content input[type="month"] {
        width: 100%;
        padding: 14px 18px;
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        font-size: 1.05rem;
        transition: all 0.3s ease;
        background: #ffffff;
        font-weight: 500;
    }

    .modal-content input[type="text"]:focus,
    .modal-content input[type="number"]:focus,
    .modal-content input[type="date"]:focus,
    .modal-content input[type="month"]:focus {
        outline: none;
        border-color: #06b6d4;
        box-shadow: 0 0 0 4px rgba(145, 179, 142, 0.15), 0 4px 12px rgba(145, 179, 142, 0.2);
        transform: translateY(-2px);
    }

    .modal-content .btn {
        background: linear-gradient(135deg, #8BA49A 0%, #3B6255 100%);
        padding: 14px 32px;
        border-radius: 14px;
        font-size: 1.05rem;
        font-weight: 700;
        box-shadow: 0 6px 20px rgba(145, 179, 142, 0.35);
        letter-spacing: 0.3px;
    }

    .modal-content .btn:hover {
        background: linear-gradient(135deg, #3B6255 0%, #2d5a2e 100%);
        transform: translateY(-3px);
        box-shadow: 0 12px 30px rgba(145, 179, 142, 0.5);
    }

    .modal-content .btn:active {
        transform: translateY(-1px);
    }

    .modal-content .cancel-btn {
        background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
        color: #475569;
        padding: 14px 32px;
        border-radius: 14px;
        border: 2px solid #cbd5e1;
        font-size: 1.05rem;
        font-weight: 700;
        letter-spacing: 0.3px;
    }

    .modal-content .cancel-btn:hover {
        background: linear-gradient(135deg, #e2e8f0 0%, #cbd5e1 100%);
        border-color: #94a3b8;
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(100, 116, 139, 0.25);
    }

    .modal-content .cancel-btn:active {
        transform: translateY(-1px);
    }

    .modal-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(6, 182, 212, 0.15);
        align-items: center;
        justify-content: center;
        z-index: 1000;
        backdrop-filter: blur(12px);
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

    .item-name {
        font-weight: 600;
        font-size: 1rem;
        color: #0f172a;
    }

    .add-form-section {
        background: linear-gradient(135deg, #CBDED3 0%, #c5dcc2 100%);
        padding: 18px;
        border-radius: 12px;
        margin-bottom: 20px;
        border: 2px solid #a8c5a5;
    }

    /* Custom Confirmation Modal */
    .confirm-modal-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.8) !important;
        backdrop-filter: blur(16px) !important;
        align-items: center;
        justify-content: center;
        z-index: 2000;
        backdrop-filter: blur(12px);
        animation: fadeIn 0.25s ease;
    }

    .confirm-modal-content {
        background: linear-gradient(145deg, #ffffff 0%, #f8fdf8 100%);
        padding: 48px 44px;
        border-radius: 28px;
        width: 520px;
        max-width: 90vw;
        box-shadow: 
            0 40px 100px rgba(45, 90, 46, 0.25),
            0 20px 50px rgba(91, 133, 95, 0.15),
            0 0 0 1px rgba(145, 179, 142, 0.1);
        animation: confirmSlideIn 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
        text-align: center;
        position: relative;
        overflow: hidden;
    }

    .confirm-modal-content::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, #8BA49A 0%, #3B6255 50%, #8BA49A 100%);
        background-size: 200% 100%;
        animation: shimmer 3s linear infinite;
    }

    @keyframes shimmer {
        0% { background-position: -200% 0; }
        100% { background-position: 200% 0; }
    }

    @keyframes confirmSlideIn {
        from {
            opacity: 0;
            transform: scale(0.85) translateY(-40px);
        }

        to {
            opacity: 1;
            transform: scale(1) translateY(0);
        }
    }

    .confirm-icon {
        width: 96px;
        height: 96px;
        margin: 0 auto 28px;
        background: linear-gradient(135deg, #8BA49A 0%, #3B6255 100%);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 3.5rem;
        color: white;
        box-shadow: 
            0 15px 45px rgba(145, 179, 142, 0.6),
            0 8px 20px rgba(91, 133, 95, 0.4),
            inset 0 -4px 8px rgba(0, 0, 0, 0.1),
            inset 0 4px 8px rgba(255, 255, 255, 0.3);
        animation: iconBounce 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        position: relative;
    }

    @keyframes iconBounce {
        0% {
            transform: scale(0) rotate(-180deg);
            opacity: 0;
        }
        50% {
            transform: scale(1.15) rotate(10deg);
        }
        100% {
            transform: scale(1) rotate(0deg);
            opacity: 1;
        }
    }

    .confirm-icon::before {
        content: '';
        position: absolute;
        inset: -12px;
        border-radius: 50%;
        background: linear-gradient(135deg, rgba(145, 179, 142, 0.3) 0%, rgba(91, 133, 95, 0.2) 100%);
        animation: ripple 2s ease-out infinite;
        z-index: -1;
    }

    .confirm-icon::after {
        content: '';
        position: absolute;
        inset: -20px;
        border-radius: 50%;
        background: linear-gradient(135deg, rgba(145, 179, 142, 0.15) 0%, rgba(91, 133, 95, 0.1) 100%);
        animation: ripple 2s ease-out infinite 0.5s;
        z-index: -2;
    }

    @keyframes ripple {
        0% {
            transform: scale(0.95);
            opacity: 0.8;
        }
        50% {
            transform: scale(1.05);
            opacity: 0.4;
        }
        100% {
            transform: scale(1.15);
            opacity: 0;
        }
    }



    .confirm-title {
        color: #2d5a2e;
        font-size: 1.75rem;
        font-weight: 800;
        margin-bottom: 16px;
        letter-spacing: -0.5px;
        text-shadow: 0 2px 4px rgba(45, 90, 46, 0.1);
        animation: fadeInDown 0.5s ease-out 0.2s both;
    }

    .confirm-message {
        color: #64748b;
        font-size: 1.08rem;
        margin-bottom: 36px;
        line-height: 1.8;
        font-weight: 500;
        animation: fadeInUp 0.5s ease-out 0.3s both;
    }

    @keyframes fadeInDown {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .confirm-buttons {
        display: flex;
        gap: 16px;
        justify-content: center;
        animation: fadeInUp 0.5s ease-out 0.4s both;
    }

    .confirm-ok-btn {
        background: linear-gradient(135deg, #8BA49A 0%, #3B6255 100%);
        color: #fff;
        padding: 16px 42px;
        border-radius: 16px;
        border: none;
        cursor: pointer;
        font-weight: 700;
        font-size: 1.08rem;
        transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 
            0 8px 24px rgba(145, 179, 142, 0.4),
            0 4px 12px rgba(91, 133, 95, 0.3),
            inset 0 1px 0 rgba(255, 255, 255, 0.2);
        letter-spacing: 0.4px;
        position: relative;
        overflow: hidden;
    }

    .confirm-ok-btn::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 0;
        height: 0;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.3);
        transform: translate(-50%, -50%);
        transition: width 0.6s, height 0.6s;
    }

    .confirm-ok-btn:hover::before {
        width: 300px;
        height: 300px;
    }

    .confirm-ok-btn:hover {
        transform: translateY(-4px) scale(1.02);
        box-shadow: 
            0 14px 36px rgba(145, 179, 142, 0.55),
            0 8px 18px rgba(91, 133, 95, 0.4),
            inset 0 1px 0 rgba(255, 255, 255, 0.3);
    }

    .confirm-ok-btn:active {
        transform: translateY(-2px) scale(0.98);
        box-shadow: 
            0 6px 20px rgba(145, 179, 142, 0.4),
            0 3px 10px rgba(91, 133, 95, 0.3);
    }

    .confirm-cancel-btn {
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        color: #64748b;
        padding: 16px 42px;
        border-radius: 16px;
        border: 2px solid #e2e8f0;
        cursor: pointer;
        font-weight: 700;
        font-size: 1.08rem;
        transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        letter-spacing: 0.4px;
        box-shadow: 0 4px 12px rgba(100, 116, 139, 0.1);
        position: relative;
        overflow: hidden;
    }

    .confirm-cancel-btn::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(148, 163, 184, 0.1), transparent);
        transition: left 0.5s;
    }

    .confirm-cancel-btn:hover::before {
        left: 100%;
    }

    .confirm-cancel-btn:hover {
        transform: translateY(-4px) scale(1.02);
        box-shadow: 0 10px 28px rgba(100, 116, 139, 0.2);
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
        border-color: #cbd5e1;
        color: #475569;
    }

    .confirm-cancel-btn:active {
        transform: translateY(-2px) scale(0.98);
        box-shadow: 0 4px 12px rgba(100, 116, 139, 0.15);
    }

    .confirm-cancel-btn:active {
        transform: translateY(-1px);
    }
    </style>
</head>

<body>
    <?php include __DIR__ . '/includes/sidebar_common.php'; ?>

    <div class="topbar">
        <h1 class="dashboard-title">Engineering Admin Dashboard</h1>
        <div style="display:flex;align-items:center;gap:12px;">
            <button onclick="showKeyboardShortcutsHelp && showKeyboardShortcutsHelp()" class="shortcuts-btn"
                title="Ctrl + H"
                style="background:rgba(255,255,255,0.08); color:#fff; border-radius:8px; padding:8px 12px; border:1px solid rgba(255,255,255,0.08);">
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
        <?php if ($flash['msg']): ?>
        <div class="msg <?= $flash['type'] === 'success' ? 'success' : 'error' ?>">
            <?= htmlspecialchars($flash['msg']) ?></div>
        <?php endif; ?>

        <!-- Courses panel (moved to top) -->
        <div class="panel" style="margin-bottom:24px;">
            <h3><i class="fas fa-graduation-cap"></i> Courses</h3>
            <form method="post" action="manage_data_engineering.php" class="add-form-section" id="addCourseForm">
                <div class="form-row"><input type="text" name="new_course" id="new_course"
                        placeholder="New course name (e.g., BS Civil Engineering)" required></div>
                <div class="form-row" style="margin-bottom:0;"><button class="btn" type="submit" name="add_course"><i
                            class="fas fa-plus"></i> Add Course</button></div>
            </form>

            <?php if (empty($courses)): ?>
            <div class="small muted" style="text-align:center; padding:20px;">No courses defined for Engineering yet.
            </div>
            <?php else: ?>
            <?php foreach ($courses as $c): ?>
            <div class="list-item">
                <div>
                    <div class="item-name"><?= htmlspecialchars($c['name']) ?></div>
                    <div class="small">ID: <?= intval($c['id']) ?></div>
                </div>
                <div class="actions">
                    <button class="edit-btn" data-action="edit-course" data-id="<?= intval($c['id']) ?>"
                        data-name="<?= htmlspecialchars($c['name'], ENT_QUOTES) ?>" title="Edit"><i
                            class="fas fa-edit"></i></button>
                    <button class="btn danger" data-action="delete-course" data-id="<?= intval($c['id']) ?>"
                        data-name="<?= htmlspecialchars($c['name'], ENT_QUOTES) ?>" title="Delete"><i
                            class="fas fa-trash"></i></button>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="grid">
            <div class="panel" style="min-height:480px;">
                <h3><i class="fas fa-clipboard-list"></i> Board Exam Types</h3>
                <form method="post" action="manage_data_engineering.php" class="add-form-section" id="addExamTypeForm">
                    <div class="form-row">
                        <input type="text" name="new_exam_type" id="new_exam_type"
                            placeholder="New exam type name (e.g., Civil Eng)" required>
                    </div>
                    <div class="form-row" style="margin-bottom:0;">
                        <button class="btn" type="submit" name="add_exam_type"><i class="fas fa-plus"></i> Add Exam
                            Type</button>
                    </div>
                </form>

                <div style="margin-top:6px;">
                    <?php if (empty($exam_types)): ?>
                    <div class="small muted" style="text-align:center; padding:20px;">No board exam types created yet.
                    </div>
                    <?php else: ?>
                    <?php foreach($exam_types as $et): ?>
                    <div class="list-item">
                        <div>
                            <div class="item-name"><?= htmlspecialchars($et['name']) ?></div>
                            <div class="small">ID: <?= $et['id'] ?></div>
                        </div>
                        <div class="actions">
                            <form method="get" action="manage_data_engineering.php" style="display:inline;"><input
                                    type="hidden" name="exam_type_id" value="<?= intval($et['id']) ?>"><button
                                    class="view-btn" type="submit"><i class="fas fa-eye"></i> View</button></form>
                            <button class="edit-btn" data-action="edit-exam-type" data-id="<?= intval($et['id']) ?>"
                                data-name="<?= htmlspecialchars($et['name'], ENT_QUOTES) ?>" title="Edit"><i
                                    class="fas fa-edit"></i></button>
                            <button class="btn danger" data-action="delete-exam-type" data-id="<?= intval($et['id']) ?>"
                                data-name="<?= htmlspecialchars($et['name'], ENT_QUOTES) ?>" title="Delete"><i
                                    class="fas fa-trash"></i></button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="right-column">
                <div class="panel">
                    <h3><i class="fas fa-book"></i> Subjects for Exam Type</h3>
                    <?php if ($selected_exam_type_id <= 0): ?>
                    <div class="small muted" style="text-align:center; padding:20px;">Select an exam type to view and
                        manage subjects.</div>
                    <?php else: ?>
                    <?php $etname = null; foreach ($exam_types as $e) if ($e['id']==$selected_exam_type_id) $etname=$e['name']; ?>
                    <div
                        style="margin-bottom:16px; padding:12px; background:linear-gradient(135deg, #CBDED3 0%, #c5dcc2 100%); border-radius:10px; border:2px solid #a8c5a5;">
                        <strong style="color:#2d5a2e; font-size:1.05rem;"><i class="fas fa-check-circle"></i>
                            <?= htmlspecialchars($etname ?? 'Selected') ?></strong>
                    </div>

                    <form method="post" action="manage_data_engineering.php?exam_type_id=<?= $selected_exam_type_id ?>"
                        class="add-form-section" id="addSubjectForm">
                        <input type="hidden" name="subject_exam_type" value="<?= $selected_exam_type_id ?>">
                        <div class="form-row"><input type="text" name="new_subject" id="new_subject"
                                placeholder="New subject name" required></div>
                        <div class="form-row"><input type="number" name="new_subject_total_items"
                                id="new_subject_total_items" value="50" min="1" placeholder="Total items"></div>
                        <div class="form-row" style="margin-bottom:0;"><button class="btn" type="submit"
                                name="add_subject"><i class="fas fa-plus"></i> Add Subject</button></div>
                    </form>


                    <?php if (empty($subjects)): ?>
                    <div class="small muted" style="text-align:center; padding:20px;">No subjects mapped to this exam
                        type yet.</div>
                    <?php else: ?>
                    <?php foreach($subjects as $s): ?>
                    <div class="list-item">
                        <div>
                            <div class="item-name"><?= htmlspecialchars($s['subject_name']) ?></div>
                            <div class="small"><i class="fas fa-list-ol"></i> Items: <?= intval($s['total_items']) ?>
                            </div>
                        </div>
                        <div class="actions">
                            <button class="edit-btn" data-action="edit-subject" data-id="<?= intval($s['id']) ?>"
                                data-name="<?= htmlspecialchars($s['subject_name'], ENT_QUOTES) ?>"
                                data-items="<?= intval($s['total_items']) ?>" title="Edit"><i
                                    class="fas fa-edit"></i></button>
                            <button class="btn danger" data-action="delete-subject" data-id="<?= intval($s['id']) ?>"
                                data-name="<?= htmlspecialchars($s['subject_name'], ENT_QUOTES) ?>" title="Delete"><i
                                    class="fas fa-trash"></i></button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>

                <div class="panel">
                    <h3><i class="fas fa-calendar-alt"></i> Available Dates for
                        <?= htmlspecialchars($etname ?? 'Selected') ?></h3>
                    <form method="post" action="manage_data_engineering.php?exam_type_id=<?= $selected_exam_type_id ?>"
                        class="add-form-section" id="addExamDateForm">
                        <input type="hidden" name="exam_type_id" value="<?= $selected_exam_type_id ?>">
                        <div class="form-row"><input type="month" name="new_exam_date" id="new_exam_date" required></div>
                        <div class="form-row"><input type="text" name="exam_description" id="exam_description"
                                placeholder="Description (optional)"></div>
                        <div class="form-row" style="margin-bottom:0;"><button class="btn" type="submit"
                                name="add_exam_date"><i class="fas fa-plus"></i> Add Date</button></div>
                    </form>

                    <?php if (empty($exam_dates)): ?>
                    <div class="small muted" style="text-align:center; padding:20px;">No available dates for this exam
                        type.</div>
                    <?php else: ?>
                    <?php foreach ($exam_dates as $ed): ?>
                    <div class="list-item">
                        <div>
                            <div class="item-name"><i class="fas fa-calendar-day"></i>
                                <?= htmlspecialchars(date('F Y', strtotime($ed['exam_date']))) ?></div>
                            <?php if (!empty($ed['exam_description'])): ?><div class="small">
                                <?= htmlspecialchars($ed['exam_description']) ?></div><?php endif; ?>
                        </div>
                        <div class="actions">
                            <button class="edit-btn" data-action="edit-exam-date" data-id="<?= intval($ed['id']) ?>"
                                data-date="<?= htmlspecialchars($ed['exam_date'], ENT_QUOTES) ?>"
                                data-desc="<?= htmlspecialchars($ed['exam_description'], ENT_QUOTES) ?>" title="Edit"><i
                                    class="fas fa-edit"></i></button>
                            <button class="btn danger" data-action="delete-exam-date" data-id="<?= intval($ed['id']) ?>"
                                data-date="<?= htmlspecialchars($ed['exam_date'], ENT_QUOTES) ?>" title="Delete"><i
                                    class="fas fa-trash"></i></button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Exam Type Modal (simple) -->
    <div id="editExamTypeModal" class="modal-overlay">
        <div class="modal-content">
            <h3><i class="fas fa-clipboard-list"></i> Edit Exam Type</h3>
            <form id="editExamTypeForm" method="post" action="manage_data_engineering.php">
                <input type="hidden" name="exam_type_id" id="editExamTypeId">
                <div style="margin-bottom:20px;">
                    <label
                        style="display:block; margin-bottom:8px; color:#0e7490; font-weight:600; font-size:0.9rem;"><i
                            class="fas fa-tag"></i> Exam Type Name</label>
                    <input type="text" name="edit_exam_type_name" id="editExamTypeName"
                        placeholder="Enter exam type name" style="font-size:1rem;">
                </div>
                <div style="display:flex; gap:12px; justify-content:flex-end;"><button class="btn" type="submit"
                        name="edit_exam_type"><i class="fas fa-save"></i> Save Changes</button><button
                        class="cancel-btn" type="button" onclick="closeEditExamType()"><i class="fas fa-times"></i>
                        Cancel</button></div>
            </form>
        </div>
    </div>

    <!-- Edit Subject Modal -->
    <div id="editSubjectModal" class="modal-overlay">
        <div class="modal-content">
            <h3><i class="fas fa-book"></i> Edit Subject</h3>
            <form id="editSubjectForm" method="post" action="manage_data_engineering.php">
                <input type="hidden" name="subject_id" id="editSubjectId">
                <div style="margin-bottom:18px;">
                    <label
                        style="display:block; margin-bottom:8px; color:#0e7490; font-weight:600; font-size:0.9rem;"><i
                            class="fas fa-tag"></i> Subject Name</label>
                    <input type="text" name="edit_subject_name" id="editSubjectName" placeholder="Enter subject name"
                        style="font-size:1rem;">
                </div>
                <div style="margin-bottom:20px;">
                    <label
                        style="display:block; margin-bottom:8px; color:#0e7490; font-weight:600; font-size:0.9rem;"><i
                            class="fas fa-list-ol"></i> Total Items</label>
                    <input type="number" name="edit_subject_items" id="editSubjectItems" min="1"
                        placeholder="Enter total items" style="font-size:1rem;">
                </div>
                <div style="display:flex; gap:12px; justify-content:flex-end;"><button class="btn" type="submit"
                        name="edit_subject"><i class="fas fa-save"></i> Save Changes</button><button class="cancel-btn"
                        type="button" onclick="closeEditSubject()"><i class="fas fa-times"></i> Cancel</button></div>
            </form>
        </div>
    </div>

    <!-- Edit Course Modal -->
    <div id="editCourseModal" class="modal-overlay">
        <div class="modal-content">
            <h3><i class="fas fa-graduation-cap"></i> Edit Course</h3>
            <form id="editCourseForm" method="post" action="manage_data_engineering.php">
                <input type="hidden" name="course_id" id="editCourseId">
                <div style="margin-bottom:20px;">
                    <label
                        style="display:block; margin-bottom:8px; color:#0e7490; font-weight:600; font-size:0.9rem;"><i
                            class="fas fa-tag"></i> Course Name</label>
                    <input type="text" name="edit_course_name" id="editCourseName" placeholder="Enter course name"
                        style="font-size:1rem;">
                </div>
                <div style="display:flex; gap:12px; justify-content:flex-end;">
                    <button class="btn" type="submit" name="edit_course"><i class="fas fa-save"></i>
                        Save Changes</button>
                    <button class="cancel-btn" type="button"
                        onclick="document.getElementById('editCourseModal').style.display='none'"><i
                            class="fas fa-times"></i> Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Exam Date Modal -->
    <div id="editExamDateModal" class="modal-overlay">
        <div class="modal-content">
            <h3><i class="fas fa-calendar-alt"></i> Edit Exam Date</h3>
            <form id="editExamDateForm" method="post" action="manage_data_engineering.php">
                <input type="hidden" name="exam_date_id" id="editExamDateId">
                <div style="margin-bottom:18px;">
                    <label
                        style="display:block; margin-bottom:8px; color:#0e7490; font-weight:600; font-size:0.9rem;"><i
                            class="fas fa-calendar-day"></i> Exam Date</label>
                    <input type="month" name="edit_exam_date_value" id="editExamDateValue" style="font-size:1rem;">
                </div>
                <div style="margin-bottom:20px;">
                    <label
                        style="display:block; margin-bottom:8px; color:#0e7490; font-weight:600; font-size:0.9rem;"><i
                            class="fas fa-info-circle"></i> Description (Optional)</label>
                    <input type="text" name="edit_exam_date_description" id="editExamDateDescription"
                        placeholder="Enter description" style="font-size:1rem;">
                </div>
                <div style="display:flex; gap:12px; justify-content:flex-end;">
                    <button class="btn" type="submit" name="edit_exam_date"><i
                            class="fas fa-save"></i> Save Changes</button>
                    <button class="cancel-btn" type="button"
                        onclick="document.getElementById('editExamDateModal').style.display='none'"><i
                            class="fas fa-times"></i> Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Custom Confirmation Modal -->
    <?php include "./components/manage-data-logout.php" ?>
    <!-- <?php include "./components/logout-modal.php" ?> -->


    <!-- <div id="customConfirmModal" class="confirm-modal-overlay">
        <div class="confirm-modal-content">
          <div class="confirm-icon">⚠️</div>
          <div class="confirm-title">Confirm Action</div>
          <div class="confirm-message" id="confirmMessage">Are you sure you want to proceed?</div>
          <div class="confirm-buttons">
            <button class="confirm-ok-btn" id="confirmOkBtn"><i class="fas fa-check"></i> Confirm</button>
            <button class="confirm-cancel-btn" id="confirmCancelBtn"><i class="fas fa-times"></i> Cancel</button>
          </div>
        </div>
      </div> -->

    <script>
    // Custom Confirm Modal Functions
    let confirmCallback = null;

    function showCustomConfirm(message, callback, icon) {
        const modal = document.getElementById('customConfirmModal');
        const messageEl = document.getElementById('confirmMessage');
        const okBtn = document.getElementById('confirmOkBtn');
        const cancelBtn = document.getElementById('confirmCancelBtn');

        messageEl.textContent = message;

        modal.style.display = 'flex';
        confirmCallback = callback;

        okBtn.onclick = function() {
            modal.style.display = 'none';
            if (confirmCallback) confirmCallback(true);
            confirmCallback = null;
        };

        cancelBtn.onclick = function() {
            modal.style.display = 'none';
            if (confirmCallback) confirmCallback(false);
            confirmCallback = null;
        };

        // Close on backdrop click
        modal.onclick = function(e) {
            if (e.target === modal) {
                modal.style.display = 'none';
                if (confirmCallback) confirmCallback(false);
                confirmCallback = null;
            }
        };
    }

    // Expose helper functions on window and add simple logging for debugging
    window.confirmLogout = function(e) {
        e.preventDefault();
        showCustomConfirm('Are you sure you want to logout?', function(confirmed) {
            if (confirmed) window.location.href = 'logout.php';
        });
        return false;
    };
    window.openEditExamType = function(id, name) {
        console.log('openEditExamType', id, name);
        var elId = document.getElementById('editExamTypeId');
        var elName = document.getElementById('editExamTypeName');
        var modal = document.getElementById('editExamTypeModal');
        if (elId) elId.value = id;
        if (elName) elName.value = name;
        if (modal) modal.style.display = 'flex';
    };
    window.closeEditExamType = function() {
        var modal = document.getElementById('editExamTypeModal');
        if (modal) modal.style.display = 'none';
    };
    window.confirmDeleteExamType = function(id, name) {
        console.log('confirmDeleteExamType', id, name);
        showCustomConfirm('Delete exam type "' + name + '"? This will fail if subjects are still mapped.', function(
            confirmed) {
            if (!confirmed) return;
            var f = document.createElement('form');
            f.method = 'post';
            f.action = 'manage_data_engineering.php';
            var i = document.createElement('input');
            i.type = 'hidden';
            i.name = 'exam_type_id';
            i.value = id;
            f.appendChild(i);
            var b = document.createElement('input');
            b.type = 'hidden';
            b.name = 'delete_exam_type';
            b.value = '1';
            f.appendChild(b);
            document.body.appendChild(f);
            f.submit();
        }, '🗑️');
    };
    window.openEditSubject = function(id, name, items) {
        console.log('openEditSubject', id, name, items);
        var elId = document.getElementById('editSubjectId');
        var elName = document.getElementById('editSubjectName');
        var elItems = document.getElementById('editSubjectItems');
        var modal = document.getElementById('editSubjectModal');
        if (elId) elId.value = id;
        if (elName) elName.value = name;
        if (elItems) elItems.value = items;
        if (modal) modal.style.display = 'flex';
    };
    window.closeEditSubject = function() {
        var modal = document.getElementById('editSubjectModal');
        if (modal) modal.style.display = 'none';
    };
    window.confirmDeleteSubject = function(id, name) {
        console.log('confirmDeleteSubject', id, name);
        showCustomConfirm('Delete subject "' + name + '"? This will be blocked if subject has recorded results.',
            function(confirmed) {
                if (!confirmed) return;
                var f = document.createElement('form');
                f.method = 'post';
                f.action = 'manage_data_engineering.php';
                var i = document.createElement('input');
                i.type = 'hidden';
                i.name = 'subject_id';
                i.value = id;
                f.appendChild(i);
                var b = document.createElement('input');
                b.type = 'hidden';
                b.name = 'delete_subject';
                b.value = '1';
                f.appendChild(b);
                document.body.appendChild(f);
                f.submit();
            }, '🗑️');
    };
    // Courses: open edit modal and confirm delete
    window.openEditCourse = function(id, name) {
        console.log('openEditCourse', id, name);
        var elId = document.getElementById('editCourseId');
        var elName = document.getElementById('editCourseName');
        var modal = document.getElementById('editCourseModal');
        if (elId) elId.value = id;
        if (elName) elName.value = name;
        if (modal) modal.style.display = 'flex';
    };
    window.confirmDeleteCourse = function(id, name) {
        console.log('confirmDeleteCourse', id, name);
        showCustomConfirm('Delete course "' + name +
            '"? This will be blocked if the course has recorded board passers.',
            function(confirmed) {
                if (!confirmed) return;
                var f = document.createElement('form');
                f.method = 'post';
                f.action = 'manage_data_engineering.php';
                var i = document.createElement('input');
                i.type = 'hidden';
                i.name = 'course_id';
                i.value = id;
                f.appendChild(i);
                var b = document.createElement('input');
                b.type = 'hidden';
                b.name = 'delete_course';
                b.value = '1';
                f.appendChild(b);
                document.body.appendChild(f);
                f.submit();
            }, '🗑️');
    };
    // Exam dates: open edit modal and confirm delete
    window.openEditExamDate = function(id, dateVal, desc) {
        console.log('openEditExamDate', id, dateVal, desc);
        var elId = document.getElementById('editExamDateId');
        var elDate = document.getElementById('editExamDateValue');
        var elDesc = document.getElementById('editExamDateDescription');
        var modal = document.getElementById('editExamDateModal');
        if (elId) elId.value = id;
        if (elDate) elDate.value = dateVal;
        if (elDesc) elDesc.value = desc;
        if (modal) modal.style.display = 'flex';
    };
    window.confirmDeleteExamDate = function(id, dateVal) {
        console.log('confirmDeleteExamDate', id, dateVal);
        showCustomConfirm('Delete exam date "' + dateVal +
            '"? This will be blocked if it has recorded board passers.',
            function(confirmed) {
                if (!confirmed) return;
                var f = document.createElement('form');
                f.method = 'post';
                f.action = 'manage_data_engineering.php';
                var i = document.createElement('input');
                i.type = 'hidden';
                i.name = 'exam_date_id';
                i.value = id;
                f.appendChild(i);
                var b = document.createElement('input');
                b.type = 'hidden';
                b.name = 'delete_exam_date';
                b.value = '1';
                f.appendChild(b);
                document.body.appendChild(f);
                f.submit();
            }, '🗑️');
    };

    // Event delegation for buttons using data-action attributes
    document.addEventListener('DOMContentLoaded', function() {
        document.body.addEventListener('click', function(e) {
            var btn = e.target.closest && e.target.closest('button[data-action]');
            if (!btn) return;
            var action = btn.getAttribute('data-action');
            var id = btn.getAttribute('data-id');
            var name = btn.getAttribute('data-name');
            var items = btn.getAttribute('data-items');
            // normalize id/items
            var nid = id ? parseInt(id, 10) : 0;
            var nitems = items ? parseInt(items, 10) : 0;
            console.log('button clicked', action, nid, name, nitems);
            if (action === 'edit-exam-type') {
                window.openEditExamType(nid, name);
                return;
            }
            if (action === 'delete-exam-type') {
                window.confirmDeleteExamType(nid, name);
                return;
            }
            if (action === 'edit-subject') {
                window.openEditSubject(nid, name, nitems);
                return;
            }
            if (action === 'delete-subject') {
                window.confirmDeleteSubject(nid, name);
                return;
            }
            if (action === 'edit-course') {
                window.openEditCourse(nid, name);
                return;
            }
            if (action === 'delete-course') {
                window.confirmDeleteCourse(nid, name);
                return;
            }
            if (action === 'edit-exam-date') {
                window.openEditExamDate(nid, btn.getAttribute('data-date') || '', btn.getAttribute(
                    'data-desc') || '');
                return;
            }
            if (action === 'delete-exam-date') {
                window.confirmDeleteExamDate(nid, btn.getAttribute('data-date') || '');
                return;
            }
        }, false);

        // Add confirmation for save buttons in edit forms
        const editCourseForm = document.getElementById('editCourseForm');
        const editExamTypeForm = document.getElementById('editExamTypeForm');
        const editSubjectForm = document.getElementById('editSubjectForm');
        const editExamDateForm = document.getElementById('editExamDateForm');

        if (editCourseForm) {
            editCourseForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const courseName = document.getElementById('editCourseName').value;
                showCustomConfirm('Save changes to course "' + courseName + '"?', function(confirmed) {
                    if (confirmed) {
                        // Create a new form element to bypass the event listener
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = 'manage_data_engineering.php';
                        
                        const courseId = document.createElement('input');
                        courseId.type = 'hidden';
                        courseId.name = 'course_id';
                        courseId.value = document.getElementById('editCourseId').value;
                        form.appendChild(courseId);
                        
                        const courseNameInput = document.createElement('input');
                        courseNameInput.type = 'hidden';
                        courseNameInput.name = 'edit_course_name';
                        courseNameInput.value = courseName;
                        form.appendChild(courseNameInput);
                        
                        const editCourseInput = document.createElement('input');
                        editCourseInput.type = 'hidden';
                        editCourseInput.name = 'edit_course';
                        editCourseInput.value = '1';
                        form.appendChild(editCourseInput);
                        
                        document.body.appendChild(form);
                        form.submit();
                    }
                });
            });
        }

        if (editExamTypeForm) {
            editExamTypeForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const examTypeName = document.getElementById('editExamTypeName').value;
                showCustomConfirm('Save changes to exam type "' + examTypeName + '"?', function(confirmed) {
                    if (confirmed) {
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = 'manage_data_engineering.php';
                        
                        const examTypeId = document.createElement('input');
                        examTypeId.type = 'hidden';
                        examTypeId.name = 'exam_type_id';
                        examTypeId.value = document.getElementById('editExamTypeId').value;
                        form.appendChild(examTypeId);
                        
                        const examTypeNameInput = document.createElement('input');
                        examTypeNameInput.type = 'hidden';
                        examTypeNameInput.name = 'edit_exam_type_name';
                        examTypeNameInput.value = examTypeName;
                        form.appendChild(examTypeNameInput);
                        
                        const editExamTypeInput = document.createElement('input');
                        editExamTypeInput.type = 'hidden';
                        editExamTypeInput.name = 'edit_exam_type';
                        editExamTypeInput.value = '1';
                        form.appendChild(editExamTypeInput);
                        
                        document.body.appendChild(form);
                        form.submit();
                    }
                });
            });
        }

        if (editSubjectForm) {
            editSubjectForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const subjectName = document.getElementById('editSubjectName').value;
                const subjectItems = document.getElementById('editSubjectItems').value;
                showCustomConfirm('Save changes to subject "' + subjectName + '"?', function(confirmed) {
                    if (confirmed) {
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = 'manage_data_engineering.php';
                        
                        const subjectId = document.createElement('input');
                        subjectId.type = 'hidden';
                        subjectId.name = 'subject_id';
                        subjectId.value = document.getElementById('editSubjectId').value;
                        form.appendChild(subjectId);
                        
                        const subjectNameInput = document.createElement('input');
                        subjectNameInput.type = 'hidden';
                        subjectNameInput.name = 'edit_subject_name';
                        subjectNameInput.value = subjectName;
                        form.appendChild(subjectNameInput);
                        
                        const subjectItemsInput = document.createElement('input');
                        subjectItemsInput.type = 'hidden';
                        subjectItemsInput.name = 'edit_subject_items';
                        subjectItemsInput.value = subjectItems;
                        form.appendChild(subjectItemsInput);
                        
                        const editSubjectInput = document.createElement('input');
                        editSubjectInput.type = 'hidden';
                        editSubjectInput.name = 'edit_subject';
                        editSubjectInput.value = '1';
                        form.appendChild(editSubjectInput);
                        
                        document.body.appendChild(form);
                        form.submit();
                    }
                });
            });
        }

        if (editExamDateForm) {
            editExamDateForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const examDate = document.getElementById('editExamDateValue').value;
                const examDesc = document.getElementById('editExamDateDescription').value;
                showCustomConfirm('Save changes to exam date "' + examDate + '"?', function(confirmed) {
                    if (confirmed) {
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = 'manage_data_engineering.php';
                        
                        const examDateId = document.createElement('input');
                        examDateId.type = 'hidden';
                        examDateId.name = 'exam_date_id';
                        examDateId.value = document.getElementById('editExamDateId').value;
                        form.appendChild(examDateId);
                        
                        const examDateValue = document.createElement('input');
                        examDateValue.type = 'hidden';
                        examDateValue.name = 'edit_exam_date_value';
                        examDateValue.value = examDate;
                        form.appendChild(examDateValue);
                        
                        const examDateDesc = document.createElement('input');
                        examDateDesc.type = 'hidden';
                        examDateDesc.name = 'edit_exam_date_description';
                        examDateDesc.value = examDesc;
                        form.appendChild(examDateDesc);
                        
                        const editExamDateInput = document.createElement('input');
                        editExamDateInput.type = 'hidden';
                        editExamDateInput.name = 'edit_exam_date';
                        editExamDateInput.value = '1';
                        form.appendChild(editExamDateInput);
                        
                        document.body.appendChild(form);
                        form.submit();
                    }
                });
            });
        }

        // Add confirmation for all ADD forms
        const addCourseForm = document.getElementById('addCourseForm');
        const addExamTypeForm = document.getElementById('addExamTypeForm');
        const addSubjectForm = document.getElementById('addSubjectForm');
        const addExamDateForm = document.getElementById('addExamDateForm');

        if (addCourseForm) {
            addCourseForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const courseName = document.getElementById('new_course').value;
                showCustomConfirm('Add new course "' + courseName + '"?', function(confirmed) {
                    if (confirmed) {
                        // Create a new form programmatically and submit
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = 'manage_data_engineering.php';
                        
                        const courseInput = document.createElement('input');
                        courseInput.type = 'hidden';
                        courseInput.name = 'new_course';
                        courseInput.value = courseName;
                        form.appendChild(courseInput);
                        
                        const addCourseInput = document.createElement('input');
                        addCourseInput.type = 'hidden';
                        addCourseInput.name = 'add_course';
                        addCourseInput.value = '1';
                        form.appendChild(addCourseInput);
                        
                        document.body.appendChild(form);
                        form.submit();
                    }
                }, '➕');
            });
        }

        if (addExamTypeForm) {
            addExamTypeForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const examTypeName = document.getElementById('new_exam_type').value;
                showCustomConfirm('Add new exam type "' + examTypeName + '"?', function(confirmed) {
                    if (confirmed) {
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = 'manage_data_engineering.php';
                        
                        const examTypeInput = document.createElement('input');
                        examTypeInput.type = 'hidden';
                        examTypeInput.name = 'new_exam_type';
                        examTypeInput.value = examTypeName;
                        form.appendChild(examTypeInput);
                        
                        const addExamTypeInput = document.createElement('input');
                        addExamTypeInput.type = 'hidden';
                        addExamTypeInput.name = 'add_exam_type';
                        addExamTypeInput.value = '1';
                        form.appendChild(addExamTypeInput);
                        
                        document.body.appendChild(form);
                        form.submit();
                    }
                }, '➕');
            });
        }

        if (addSubjectForm) {
            addSubjectForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const subjectName = document.getElementById('new_subject').value;
                const totalItems = document.getElementById('new_subject_total_items').value;
                const examTypeId = document.querySelector('input[name="subject_exam_type"]').value;
                showCustomConfirm('Add new subject "' + subjectName + '" with ' + totalItems +
                    ' items?',
                    function(confirmed) {
                        if (confirmed) {
                            const form = document.createElement('form');
                            form.method = 'POST';
                            form.action = 'manage_data_engineering.php?exam_type_id=' + examTypeId;
                            
                            const subjectInput = document.createElement('input');
                            subjectInput.type = 'hidden';
                            subjectInput.name = 'new_subject';
                            subjectInput.value = subjectName;
                            form.appendChild(subjectInput);
                            
                            const itemsInput = document.createElement('input');
                            itemsInput.type = 'hidden';
                            itemsInput.name = 'new_subject_total_items';
                            itemsInput.value = totalItems;
                            form.appendChild(itemsInput);
                            
                            const examTypeInput = document.createElement('input');
                            examTypeInput.type = 'hidden';
                            examTypeInput.name = 'subject_exam_type';
                            examTypeInput.value = examTypeId;
                            form.appendChild(examTypeInput);
                            
                            const addSubjectInput = document.createElement('input');
                            addSubjectInput.type = 'hidden';
                            addSubjectInput.name = 'add_subject';
                            addSubjectInput.value = '1';
                            form.appendChild(addSubjectInput);
                            
                            document.body.appendChild(form);
                            form.submit();
                        }
                    }, '➕');
            });
        }

        if (addExamDateForm) {
            addExamDateForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const examDate = document.getElementById('new_exam_date').value;
                const description = document.getElementById('exam_description').value;
                const examTypeId = document.querySelector('#addExamDateForm input[name="exam_type_id"]').value;
                const msg = description ?
                    'Add exam date "' + examDate + '" (' + description + ')?' :
                    'Add exam date "' + examDate + '"?';
                showCustomConfirm(msg, function(confirmed) {
                    if (confirmed) {
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = 'manage_data_engineering.php?exam_type_id=' + examTypeId;
                        
                        const dateInput = document.createElement('input');
                        dateInput.type = 'hidden';
                        dateInput.name = 'new_exam_date';
                        dateInput.value = examDate;
                        form.appendChild(dateInput);
                        
                        const descInput = document.createElement('input');
                        descInput.type = 'hidden';
                        descInput.name = 'exam_description';
                        descInput.value = description;
                        form.appendChild(descInput);
                        
                        const examTypeInput = document.createElement('input');
                        examTypeInput.type = 'hidden';
                        examTypeInput.name = 'exam_type_id';
                        examTypeInput.value = examTypeId;
                        form.appendChild(examTypeInput);
                        
                        const addDateInput = document.createElement('input');
                        addDateInput.type = 'hidden';
                        addDateInput.name = 'add_exam_date';
                        addDateInput.value = '1';
                        form.appendChild(addDateInput);
                        
                        document.body.appendChild(form);
                        form.submit();
                    }
                }, '➕');
            });
        }
    });
    </script>
</body>

</html>