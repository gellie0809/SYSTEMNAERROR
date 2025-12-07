<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
// Only allow CCJE admin
if (!isset($_SESSION["users"]) || $_SESSION["users"] !== 'ccje_admin@lspu.edu.ph') {
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
if (!empty($_GET['success'])) { $flash['type'] = 'success'; $flash['msg'] = htmlspecialchars($_GET['success']); }
if (!empty($_GET['error'])) { $flash['type'] = 'error'; $flash['msg'] = htmlspecialchars($_GET['error']); }

// Handle add exam type
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["add_exam_type"])) {
  $name = trim($_POST['new_exam_type'] ?? '');
  if ($name === '') {
    header("Location: manage_data_ccje.php?error=empty_exam_type"); exit();
  }
  $chk = $conn->prepare("SELECT id FROM board_exam_types WHERE LOWER(TRIM(exam_type_name)) = LOWER(TRIM(?)) AND department='Criminal Justice Education'");
  $chk->bind_param('s', $name); $chk->execute(); $chk->store_result();
  if ($chk->num_rows > 0) { $chk->close(); header("Location: manage_data_ccje.php?error=exam_type_exists"); exit(); }
  $chk->close();
  $ins = $conn->prepare("INSERT INTO board_exam_types (exam_type_name, department) VALUES (?, 'Criminal Justice Education')");
  $ins->bind_param('s', $name);
  if ($ins->execute()) { $ins->close(); header("Location: manage_data_ccje.php?success=exam_type_added"); exit(); }
  $ins->close(); header("Location: manage_data_ccje.php?error=exam_type_add_failed"); exit();
}

// Handle edit exam type
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['edit_exam_type'])) {
  $id = intval($_POST['exam_type_id'] ?? 0);
  $newname = trim($_POST['edit_exam_type_name'] ?? '');
  if ($id <= 0 || $newname === '') { header("Location: manage_data_ccje.php?error=invalid_edit"); exit(); }
  $chk = $conn->prepare("SELECT id FROM board_exam_types WHERE LOWER(TRIM(exam_type_name)) = LOWER(TRIM(?)) AND department='Criminal Justice Education' AND id != ?");
  $chk->bind_param('si', $newname, $id); $chk->execute(); $res = $chk->get_result();
  if ($res && $res->num_rows > 0) { $chk->close(); header("Location: manage_data_ccje.php?error=exam_type_exists"); exit(); }
  $chk->close();
  $up = $conn->prepare("UPDATE board_exam_types SET exam_type_name = ? WHERE id = ? AND department='Criminal Justice Education'");
  $up->bind_param('si', $newname, $id);
  if ($up->execute() && $up->affected_rows > 0) { $up->close(); header("Location: manage_data_ccje.php?success=exam_type_updated"); exit(); }
  $up->close(); header("Location: manage_data_ccje.php?error=exam_type_update_failed"); exit();
}

// Handle delete exam type (SOFT DELETE)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_exam_type'])) {
  $id = intval($_POST['exam_type_id'] ?? 0);
  if ($id <= 0) { header("Location: manage_data_ccje.php?error=invalid_delete"); exit(); }
  // Soft delete: set is_deleted = 1 and deleted_at = NOW()
  $del = $conn->prepare("UPDATE board_exam_types SET is_deleted = 1, deleted_at = NOW() WHERE id = ? AND department='Criminal Justice Education'"); $del->bind_param('i', $id);
  if ($del->execute() && $del->affected_rows > 0) { $del->close(); header("Location: manage_data_ccje.php?success=exam_type_deleted"); exit(); }
  $del->close(); header("Location: manage_data_ccje.php?error=exam_type_delete_failed"); exit();
}

// Handle add subject
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['add_subject'])) {
  $exam_type_id = intval($_POST['subject_exam_type'] ?? 0);
  $subject_name = trim($_POST['new_subject'] ?? '');
  $total_items = intval($_POST['new_subject_total_items'] ?? 50);
  if ($exam_type_id <= 0 || $subject_name === '') { header("Location: manage_data_ccje.php?error=invalid_subject"); exit(); }
  $ins = $conn->prepare("INSERT INTO subjects (subject_name, total_items, department) VALUES (?, ?, 'Criminal Justice Education')");
  $ins->bind_param('si', $subject_name, $total_items);
  if (!$ins->execute()) { $ins->close(); header("Location: manage_data_ccje.php?error=subject_add_failed"); exit(); }
  $sid = $ins->insert_id; $ins->close();
  $m = $conn->prepare("INSERT INTO subject_exam_types (subject_id, exam_type_id) VALUES (?, ?)"); $m->bind_param('ii', $sid, $exam_type_id); $m->execute(); $m->close();
  header("Location: manage_data_ccje.php?success=subject_added&exam_type_id=" . $exam_type_id);
  exit();
}

// Handle edit subject
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['edit_subject'])) {
  $sid = intval($_POST['subject_id'] ?? 0);
  $name = trim($_POST['edit_subject_name'] ?? '');
  $items = intval($_POST['edit_subject_items'] ?? 50);
  if ($sid <= 0 || $name === '') { header("Location: manage_data_ccje.php?error=invalid_subject_edit"); exit(); }
  $up = $conn->prepare("UPDATE subjects SET subject_name = ?, total_items = ? WHERE id = ? AND department = 'Criminal Justice Education'"); $up->bind_param('sii', $name, $items, $sid);
  if ($up->execute() && $up->affected_rows > 0) { $up->close(); header("Location: manage_data_ccje.php?success=subject_updated"); exit(); }
  $up->close(); header("Location: manage_data_ccje.php?error=subject_update_failed"); exit();
}

// Handle delete subject (SOFT DELETE)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_subject'])) {
  $sid = intval($_POST['subject_id'] ?? 0);
  if ($sid <= 0) { header("Location: manage_data_ccje.php?error=invalid_subject_delete"); exit(); }
  // Soft delete: set is_deleted = 1 and deleted_at = NOW()
  $del = $conn->prepare("UPDATE subjects SET is_deleted = 1, deleted_at = NOW() WHERE id = ? AND department = 'Criminal Justice Education'"); $del->bind_param('i', $sid);
  if ($del->execute() && $del->affected_rows > 0) { $del->close(); header("Location: manage_data_ccje.php?success=subject_deleted"); exit(); }
  $del->close(); header("Location: manage_data_ccje.php?error=subject_delete_failed"); exit();
}

// Handle add exam date
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['add_exam_date'])) {
  $exam_type_id = intval($_POST['exam_type_id'] ?? 0);
  $exam_date = trim($_POST['new_exam_date'] ?? '');
  $exam_desc = trim($_POST['exam_description'] ?? '');
  // Convert YYYY-MM to YYYY-MM-01 for database
  if ($exam_date !== '' && preg_match('/^\d{4}-\d{2}$/', $exam_date)) { $exam_date .= '-01'; }
  if ($exam_type_id <= 0 || $exam_date === '') { header("Location: manage_data_ccje.php?error=invalid_exam_date"); exit(); }
  $chk = $conn->prepare("SELECT id FROM board_exam_dates WHERE exam_date = ? AND exam_type_id = ? AND department = 'Criminal Justice Education'");
  $chk->bind_param('si', $exam_date, $exam_type_id); $chk->execute(); $chk->store_result();
  if ($chk->num_rows > 0) { $chk->close(); header("Location: manage_data_ccje.php?error=exam_date_exists&exam_type_id=" . $exam_type_id); exit(); }
  $chk->close();
  $ins = $conn->prepare("INSERT INTO board_exam_dates (exam_date, exam_description, exam_type_id, department) VALUES (?, ?, ?, 'Criminal Justice Education')");
  $ins->bind_param('ssi', $exam_date, $exam_desc, $exam_type_id);
  if ($ins->execute()) { $ins->close(); header("Location: manage_data_ccje.php?success=exam_date_added&exam_type_id=" . $exam_type_id); exit(); }
  $ins->close(); header("Location: manage_data_ccje.php?error=exam_date_add_failed&exam_type_id=" . $exam_type_id); exit();
}

// Handle edit exam date
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['edit_exam_date'])) {
  $eid = intval($_POST['exam_date_id'] ?? 0);
  $edate = trim($_POST['edit_exam_date_value'] ?? '');
  $edesc = trim($_POST['edit_exam_date_description'] ?? '');
  // Convert YYYY-MM to YYYY-MM-01 for database
  if ($edate !== '' && preg_match('/^\d{4}-\d{2}$/', $edate)) { $edate .= '-01'; }
  if ($eid <= 0 || $edate === '') { header("Location: manage_data_ccje.php?error=invalid_exam_date_edit"); exit(); }
  $g = $conn->prepare("SELECT exam_type_id FROM board_exam_dates WHERE id = ? AND department = 'Criminal Justice Education'"); $g->bind_param('i', $eid); $g->execute(); $gres = $g->get_result(); $grow = $gres ? $gres->fetch_assoc() : null; $g->close();
  $exam_type_id = intval($grow['exam_type_id'] ?? 0);
  if ($exam_type_id <= 0) { header("Location: manage_data_ccje.php?error=exam_date_not_found"); exit(); }
  $chk = $conn->prepare("SELECT id FROM board_exam_dates WHERE exam_date = ? AND exam_type_id = ? AND id != ? AND department = 'Criminal Justice Education'"); $chk->bind_param('sii', $edate, $exam_type_id, $eid); $chk->execute(); $res = $chk->get_result();
  if ($res && $res->num_rows > 0) { $chk->close(); header("Location: manage_data_ccje.php?error=exam_date_exists&exam_type_id=" . $exam_type_id); exit(); }
  $chk->close();
  $up = $conn->prepare("UPDATE board_exam_dates SET exam_date = ?, exam_description = ? WHERE id = ? AND department = 'Criminal Justice Education'"); $up->bind_param('ssi', $edate, $edesc, $eid);
  if ($up->execute() && $up->affected_rows > 0) { $up->close(); header("Location: manage_data_ccje.php?success=exam_date_updated&exam_type_id=" . $exam_type_id); exit(); }
  $up->close(); header("Location: manage_data_ccje.php?error=exam_date_update_failed&exam_type_id=" . $exam_type_id); exit();
}

// Handle delete exam date (SOFT DELETE)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_exam_date'])) {
  $eid = intval($_POST['exam_date_id'] ?? 0);
  if ($eid <= 0) { header("Location: manage_data_ccje.php?error=invalid_exam_date_delete"); exit(); }
  // Soft delete: set is_deleted = 1 and deleted_at = NOW()
  $del = $conn->prepare("UPDATE board_exam_dates SET is_deleted = 1, deleted_at = NOW() WHERE id = ? AND department = 'Criminal Justice Education'"); $del->bind_param('i', $eid);
  if ($del->execute() && $del->affected_rows > 0) { $del->close(); header("Location: manage_data_ccje.php?success=exam_date_deleted"); exit(); }
  $del->close(); header("Location: manage_data_ccje.php?error=exam_date_delete_failed"); exit();
}

// Handle add course
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['add_course'])) {
  $new_course = trim($_POST['new_course'] ?? '');
  if ($new_course === '') { header("Location: manage_data_ccje.php?error=empty_course"); exit(); }
  $chk = $conn->prepare("SELECT id FROM courses WHERE LOWER(TRIM(course_name)) = LOWER(TRIM(?)) AND department = 'Criminal Justice Education' AND (is_deleted = 0 OR is_deleted IS NULL)");
  $chk->bind_param('s', $new_course); $chk->execute(); $chk->store_result();
  if ($chk->num_rows > 0) { $chk->close(); header("Location: manage_data_ccje.php?error=course_exists"); exit(); }
  $chk->close();
  $ins = $conn->prepare("INSERT INTO courses (course_name, department) VALUES (?, 'Criminal Justice Education')"); $ins->bind_param('s', $new_course);
  if ($ins->execute()) { $ins->close(); header("Location: manage_data_ccje.php?success=course_added"); exit(); }
  $ins->close(); header("Location: manage_data_ccje.php?error=course_add_failed"); exit();
}

// Handle edit course
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['edit_course'])) {
  $cid = intval($_POST['course_id'] ?? 0);
  $cname = trim($_POST['edit_course_name'] ?? '');
  if ($cid <= 0 || $cname === '') { header("Location: manage_data_ccje.php?error=invalid_course_edit"); exit(); }
  $chk = $conn->prepare("SELECT id FROM courses WHERE LOWER(TRIM(course_name)) = LOWER(TRIM(?)) AND department = 'Criminal Justice Education' AND id != ? AND (is_deleted = 0 OR is_deleted IS NULL)"); $chk->bind_param('si', $cname, $cid); $chk->execute(); $res = $chk->get_result();
  if ($res && $res->num_rows > 0) { $chk->close(); header("Location: manage_data_ccje.php?error=course_exists"); exit(); }
  $chk->close();
  $up = $conn->prepare("UPDATE courses SET course_name = ? WHERE id = ? AND department = 'Criminal Justice Education' AND (is_deleted = 0 OR is_deleted IS NULL)"); $up->bind_param('si', $cname, $cid);
  if ($up->execute() && $up->affected_rows > 0) { $up->close(); header("Location: manage_data_ccje.php?success=course_updated"); exit(); }
  $up->close(); header("Location: manage_data_ccje.php?error=course_update_failed"); exit();
}

// Handle delete course (SOFT DELETE)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_course'])) {
  $cid = intval($_POST['course_id'] ?? 0);
  if ($cid <= 0) { header("Location: manage_data_ccje.php?error=invalid_course_delete"); exit(); }
  $g = $conn->prepare("SELECT course_name FROM courses WHERE id = ? AND department = 'Criminal Justice Education' AND (is_deleted = 0 OR is_deleted IS NULL)"); $g->bind_param('i', $cid); $g->execute(); $gres = $g->get_result(); $grow = $gres ? $gres->fetch_assoc() : null; $g->close();
  $cname = trim($grow['course_name'] ?? '');
  if ($cname === '') { header("Location: manage_data_ccje.php?error=course_not_found"); exit(); }
  // Soft delete: set is_deleted = 1 and deleted_at = NOW()
  $del = $conn->prepare("UPDATE courses SET is_deleted = 1, deleted_at = NOW() WHERE id = ? AND department = 'Criminal Justice Education'"); $del->bind_param('i', $cid);
  if ($del->execute() && $del->affected_rows > 0) { $del->close(); header("Location: manage_data_ccje.php?success=course_deleted"); exit(); }
  $del->close(); header("Location: manage_data_ccje.php?error=course_delete_failed"); exit();
}

// Fetch exam types
$etres = $conn->query("SELECT id, TRIM(exam_type_name) as name FROM board_exam_types WHERE department='Criminal Justice Education' AND (is_deleted = 0 OR is_deleted IS NULL) ORDER BY exam_type_name ASC");
$exam_types = [];
while ($r = $etres->fetch_assoc()) { $exam_types[] = $r; }

// Selected exam type
$selected_exam_type_id = intval($_GET['exam_type_id'] ?? 0);
if ($selected_exam_type_id === 0 && !empty($exam_types)) { $selected_exam_type_id = $exam_types[0]['id']; }

// Fetch subjects
$subjects = [];
if ($selected_exam_type_id > 0) {
  $stmt = $conn->prepare("SELECT s.id, TRIM(s.subject_name) as subject_name, s.total_items FROM subjects s JOIN subject_exam_types setmap ON setmap.subject_id = s.id WHERE setmap.exam_type_id = ? AND s.department = 'Criminal Justice Education' AND (s.is_deleted = 0 OR s.is_deleted IS NULL) ORDER BY s.subject_name ASC");
  $stmt->bind_param('i', $selected_exam_type_id); $stmt->execute(); $sres = $stmt->get_result();
  while ($sr = $sres->fetch_assoc()) $subjects[] = $sr;
  $stmt->close();
}

// Fetch courses
$courses = [];
$cres = $conn->query("SELECT id, TRIM(course_name) as name FROM courses WHERE department='Criminal Justice Education' AND (is_deleted = 0 OR is_deleted IS NULL) ORDER BY course_name ASC");
if ($cres) { while ($cr = $cres->fetch_assoc()) $courses[] = $cr; }

// Fetch exam dates
$exam_dates = [];
if ($selected_exam_type_id > 0) {
  $dstmt = $conn->prepare("SELECT id, exam_date, TRIM(exam_description) as exam_description FROM board_exam_dates WHERE exam_type_id = ? AND department = 'Criminal Justice Education' AND (is_deleted = 0 OR is_deleted IS NULL) ORDER BY exam_date ASC");
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
    <title>Manage Data - CCJE</title>
    <link rel="stylesheet" href="style.css" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/sidebar.css" />
    <style>
    /* Same styles as engineering version */
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Inter', sans-serif;
        background: linear-gradient(135deg, #FDF3E7 0%, #FAD6A5 100%);
        min-height: 100vh;
    }

    .topbar {
        position: fixed;
        top: 0;
        left: 260px;
        right: 0;
        background: linear-gradient(135deg, #D32F2F 0%, #800020 100%);
        height: 70px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 40px;
        box-shadow: 0 4px 20px rgba(211, 47, 47, 0.3);
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

    .right-column {
        display: grid;
        grid-template-rows: auto auto;
        gap: 24px;
    }

    .panel {
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 10px 40px rgba(211, 47, 47, 0.15);
        border: 2px solid rgba(211, 47, 47, 0.15);
        transition: all 0.3s ease;
    }

    .panel:hover {
        box-shadow: 0 15px 50px rgba(211, 47, 47, 0.25);
        border-color: rgba(211, 47, 47, 0.25);
        transform: translateY(-2px);
    }

    .panel h3 {
        margin: 0 0 14px 0;
        color: #800020;
        font-size: 1.1rem;
        padding-bottom: 8px;
        border-bottom: 2px solid #D32F2F;
    }

    .list-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 14px 16px;
        border-radius: 12px;
        background: linear-gradient(135deg, #FDF3E7 0%, #FAD6A5 100%);
        border: 1px solid rgba(211, 47, 47, 0.1);
        transition: all 0.2s ease;
    }

    .list-item:hover {
        background: linear-gradient(135deg, #FAD6A5 0%, #F4C89C 100%);
        border-color: rgba(211, 47, 47, 0.3);
        transform: translateX(4px);
        box-shadow: 0 4px 12px rgba(211, 47, 47, 0.15);
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
        background: linear-gradient(135deg, #D32F2F 0%, #C62828 100%);
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
        box-shadow: 0 4px 14px rgba(211, 47, 47, 0.2);
    }

    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(211, 47, 47, 0.4);
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
        border-color: #D32F2F;
        box-shadow: 0 0 0 3px rgba(211, 47, 47, 0.1);
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
        background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
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
        box-shadow: 0 4px 14px rgba(249, 115, 22, 0.2);
    }

    .edit-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(249, 115, 22, 0.4);
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
        background: linear-gradient(135deg, #FDF3E7, #FAD6A5);
        color: #800020;
        border: 2px solid #D32F2F;
    }

    .msg.success:before {
        content: "Success";
        font-size: 1.2rem;
        font-weight: bold;
    }

    .msg.error {
        background: linear-gradient(135deg, #fee2e2, #fecaca);
        color: #7f1d1d;
        border: 2px solid #fca5a5;
    }

    .msg.error:before {
        content: "Error";
        font-size: 1.2rem;
        font-weight: bold;
    }

    .view-btn {
        background: linear-gradient(135deg, #D32F2F 0%, #C62828 100%);
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
        box-shadow: 0 4px 14px rgba(211, 47, 47, 0.2);
    }

    .view-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(211, 47, 47, 0.4);
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
        background: linear-gradient(135deg, #ffffff 0%, #FDF3E7 100%);
        padding: 40px;
        border-radius: 24px;
        width: 520px;
        box-shadow: 0 30px 80px rgba(211, 47, 47, 0.4), 0 0 0 1px rgba(211, 47, 47, 0.2);
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
        background: linear-gradient(90deg, #D32F2F 0%, #C62828 50%, #D32F2F 100%);
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
        color: #800020;
        font-size: 1.4rem;
        margin-bottom: 24px;
        padding-bottom: 16px;
        border-bottom: 3px solid #D32F2F;
        display: flex;
        align-items: center;
        gap: 12px;
        font-weight: 800;
        letter-spacing: -0.5px;
    }

    .modal-content label {
        display: block;
        margin-bottom: 10px;
        color: #800020;
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
        border-color: #D32F2F;
        box-shadow: 0 0 0 4px rgba(211, 47, 47, 0.15), 0 4px 12px rgba(211, 47, 47, 0.2);
        transform: translateY(-2px);
    }

    .modal-content .btn {
        background: linear-gradient(135deg, #D32F2F 0%, #C62828 100%);
        padding: 14px 32px;
        border-radius: 14px;
        font-size: 1.05rem;
        font-weight: 700;
        box-shadow: 0 6px 20px rgba(211, 47, 47, 0.35);
        letter-spacing: 0.3px;
    }

    .modal-content .btn:hover {
        background: linear-gradient(135deg, #C62828 0%, #800020 100%);
        transform: translateY(-3px);
        box-shadow: 0 12px 30px rgba(211, 47, 47, 0.5);
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
        background: rgba(128, 0, 32, 0.15);
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
        background: linear-gradient(135deg, #FDF3E7 0%, #FAD6A5 100%);
        padding: 18px;
        border-radius: 12px;
        margin-bottom: 20px;
        border: 2px solid #D32F2F;
    }

    .confirm-modal-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.75) !important;
        backdrop-filter: blur(20px) saturate(180%) !important;
        align-items: center;
        justify-content: center;
        z-index: 2000;
        animation: overlayFadeIn 0.3s ease;
    }

    @keyframes overlayFadeIn {
        from {
            opacity: 0;
            backdrop-filter: blur(0px);
        }

        to {
            opacity: 1;
            backdrop-filter: blur(20px) saturate(180%);
        }
    }

    .confirm-modal-content {
        background: linear-gradient(145deg, #ffffff 0%, #FDF3E7 100%);
        padding: 48px 40px 40px;
        border-radius: 28px;
        width: 500px;
        max-width: 90vw;
        box-shadow: 
            0 40px 100px rgba(211, 47, 47, 0.5),
            0 20px 60px rgba(128, 0, 32, 0.35),
            0 0 0 1px rgba(211, 47, 47, 0.15),
            inset 0 1px 0 rgba(255, 255, 255, 0.9);
        animation: confirmBounceIn 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        text-align: center;
        position: relative;
        overflow: hidden;
    }

    .confirm-modal-content::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
        animation: shimmerSweep 3s infinite;
    }

    @keyframes shimmerSweep {
        0% {
            left: -100%;
        }
        50%, 100% {
            left: 100%;
        }
    }

    @keyframes confirmBounceIn {
        0% {
            opacity: 0;
            transform: scale(0.3) translateY(-60px) rotate(-5deg);
        }
        50% {
            transform: scale(1.05) translateY(5px) rotate(1deg);
        }
        70% {
            transform: scale(0.95) translateY(-2px) rotate(-0.5deg);
        }
        100% {
            opacity: 1;
            transform: scale(1) translateY(0) rotate(0);
        }
    }

    .confirm-icon {
        width: 96px;
        height: 96px;
        margin: 0 auto 28px;
        background: linear-gradient(145deg, #E63946 0%, #D32F2F 50%, #800020 100%);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 3.5rem;
        color: white;
        box-shadow: 
            0 15px 40px rgba(211, 47, 47, 0.6),
            0 8px 20px rgba(128, 0, 32, 0.4),
            inset 0 -3px 8px rgba(0, 0, 0, 0.2),
            inset 0 3px 8px rgba(255, 255, 255, 0.3);
        animation: iconFloat 2.5s ease-in-out infinite;
        position: relative;
        z-index: 1;
    }

    .confirm-icon::before {
        content: '';
        position: absolute;
        inset: -12px;
        border-radius: 50%;
        background: linear-gradient(145deg, rgba(211, 47, 47, 0.3) 0%, rgba(128, 0, 32, 0.2) 100%);
        animation: ringPulse2 2.5s ease-in-out infinite;
        z-index: -1;
    }

    .confirm-icon::after {
        content: '';
        position: absolute;
        inset: -20px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(211, 47, 47, 0.15) 0%, transparent 70%);
        animation: ringPulse2 2.5s ease-in-out infinite 0.3s;
        z-index: -2;
    }

    @keyframes iconFloat {
        0%, 100% {
            transform: translateY(0) scale(1);
            box-shadow: 
                0 15px 40px rgba(211, 47, 47, 0.6),
                0 8px 20px rgba(128, 0, 32, 0.4),
                inset 0 -3px 8px rgba(0, 0, 0, 0.2),
                inset 0 3px 8px rgba(255, 255, 255, 0.3);
        }
        50% {
            transform: translateY(-8px) scale(1.05);
            box-shadow: 
                0 20px 50px rgba(211, 47, 47, 0.7),
                0 12px 30px rgba(128, 0, 32, 0.5),
                inset 0 -3px 8px rgba(0, 0, 0, 0.2),
                inset 0 3px 8px rgba(255, 255, 255, 0.3);
        }
    }

    @keyframes ringPulse2 {
        0%, 100% {
            transform: scale(1);
            opacity: 0.5;
        }
        50% {
            transform: scale(1.2);
            opacity: 0.2;
        }
    }

    .confirm-title {
        color: #800020;
        font-size: 1.75rem;
        font-weight: 900;
        margin-bottom: 16px;
        letter-spacing: -0.5px;
        text-shadow: 0 2px 4px rgba(128, 0, 32, 0.1);
        animation: titleSlideIn 0.5s ease 0.2s backwards;
    }

    @keyframes titleSlideIn {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .confirm-message {
        color: #475569;
        font-size: 1.1rem;
        margin-bottom: 36px;
        line-height: 1.7;
        font-weight: 500;
        padding: 0 10px;
        animation: messageSlideIn 0.5s ease 0.3s backwards;
    }

    @keyframes messageSlideIn {
        from {
            opacity: 0;
            transform: translateY(10px);
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
        animation: buttonsSlideIn 0.5s ease 0.4s backwards;
    }

    @keyframes buttonsSlideIn {
        from {
            opacity: 0;
            transform: translateY(15px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .confirm-ok-btn {
        background: linear-gradient(145deg, #E63946 0%, #D32F2F 50%, #C62828 100%);
        color: #fff;
        padding: 16px 40px;
        border-radius: 16px;
        border: none;
        cursor: pointer;
        font-weight: 800;
        font-size: 1.1rem;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 
            0 8px 24px rgba(211, 47, 47, 0.45),
            0 4px 12px rgba(128, 0, 32, 0.3),
            inset 0 1px 0 rgba(255, 255, 255, 0.2);
        letter-spacing: 0.5px;
        text-transform: uppercase;
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
        transform: translateY(-4px) scale(1.03);
        box-shadow: 
            0 14px 35px rgba(211, 47, 47, 0.6),
            0 8px 20px rgba(128, 0, 32, 0.4),
            inset 0 1px 0 rgba(255, 255, 255, 0.3);
        background: linear-gradient(145deg, #F44336 0%, #E63946 50%, #D32F2F 100%);
    }

    .confirm-ok-btn:active {
        transform: translateY(-2px) scale(1.01);
        box-shadow: 
            0 6px 18px rgba(211, 47, 47, 0.5),
            0 3px 10px rgba(128, 0, 32, 0.3);
    }

    .confirm-cancel-btn {
        background: linear-gradient(145deg, #ffffff 0%, #f8fafc 100%);
        color: #64748b;
        padding: 16px 40px;
        border-radius: 16px;
        border: 2px solid #e2e8f0;
        cursor: pointer;
        font-weight: 800;
        font-size: 1.1rem;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        letter-spacing: 0.5px;
        text-transform: uppercase;
        box-shadow: 
            0 4px 14px rgba(100, 116, 139, 0.15),
            inset 0 1px 0 rgba(255, 255, 255, 0.8);
        position: relative;
        overflow: hidden;
    }

    .confirm-cancel-btn::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 0;
        height: 0;
        border-radius: 50%;
        background: rgba(100, 116, 139, 0.1);
        transform: translate(-50%, -50%);
        transition: width 0.6s, height 0.6s;
    }

    .confirm-cancel-btn:hover::before {
        width: 300px;
        height: 300px;
    }

    .confirm-cancel-btn:hover {
        transform: translateY(-4px) scale(1.03);
        box-shadow: 
            0 10px 28px rgba(100, 116, 139, 0.25),
            inset 0 1px 0 rgba(255, 255, 255, 0.9);
        background: linear-gradient(145deg, #f8fafc 0%, #f1f5f9 100%);
        border-color: #cbd5e1;
        color: #475569;
    }

    .confirm-cancel-btn:active {
        transform: translateY(-2px) scale(1.01);
        box-shadow: 
            0 4px 12px rgba(100, 116, 139, 0.2);
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
    </style>
</head>

<body>
    <?php include __DIR__ . '/includes/ccje_nav.php'; ?>

    <div class="topbar">
        <h1 class="dashboard-title">CCJE Admin Dashboard</h1>
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

        <!-- Courses panel -->
        <div class="panel" style="margin-bottom:24px;">
            <h3><i class="fas fa-graduation-cap"></i> Courses</h3>
            <form method="post" action="manage_data_ccje.php" class="add-form-section" id="addCourseForm">
                <div class="form-row"><input type="text" name="new_course" id="new_course"
                        placeholder="New course name (e.g., Bachelor of Arts in Communication)" required></div>
                <div class="form-row" style="margin-bottom:0;"><button class="btn" type="submit" name="add_course"><i
                            class="fas fa-plus"></i> Add Course</button></div>
            </form>

            <?php if (empty($courses)): ?>
            <div class="small muted" style="text-align:center; padding:20px;">No courses defined for CCJE yet.</div>
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
                <form method="post" action="manage_data_ccje.php" class="add-form-section" id="addExamTypeForm">
                    <div class="form-row">
                        <input type="text" name="new_exam_type" id="new_exam_type"
                            placeholder="New exam type name (e.g., LET)" required>
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
                            <form method="get" action="manage_data_ccje.php" style="display:inline;"><input
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
                        style="margin-bottom:16px; padding:12px; background:linear-gradient(135deg, #FDF3E7 0%, #FAD6A5 100%); border-radius:10px; border:2px solid #D32F2F;">
                        <strong style="color:#800020; font-size:1.05rem;"><i class="fas fa-check-circle"></i>
                            <?= htmlspecialchars($etname ?? 'Selected') ?></strong>
                    </div>

                    <form method="post" action="manage_data_ccje.php?exam_type_id=<?= $selected_exam_type_id ?>"
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
                    <form method="post" action="manage_data_ccje.php?exam_type_id=<?= $selected_exam_type_id ?>"
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

    <!-- Modals (same as engineering) -->
    <!-- Edit Exam Type Modal -->
    <div id="editExamTypeModal" class="modal-overlay">
        <div class="modal-content">
            <h3><i class="fas fa-clipboard-list"></i> Edit Exam Type</h3>
            <form id="editExamTypeForm" method="post" action="manage_data_ccje.php">
                <input type="hidden" name="exam_type_id" id="editExamTypeId">
                <div style="margin-bottom:20px;">
                    <label
                        style="display:block; margin-bottom:8px; color:#800020; font-weight:600; font-size:0.9rem;"><i
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
            <form id="editSubjectForm" method="post" action="manage_data_ccje.php">
                <input type="hidden" name="subject_id" id="editSubjectId">
                <div style="margin-bottom:18px;">
                    <label
                        style="display:block; margin-bottom:8px; color:#800020; font-weight:600; font-size:0.9rem;"><i
                            class="fas fa-tag"></i> Subject Name</label>
                    <input type="text" name="edit_subject_name" id="editSubjectName" placeholder="Enter subject name"
                        style="font-size:1rem;">
                </div>
                <div style="margin-bottom:20px;">
                    <label
                        style="display:block; margin-bottom:8px; color:#800020; font-weight:600; font-size:0.9rem;"><i
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
            <form id="editCourseForm" method="post" action="manage_data_ccje.php">
                <input type="hidden" name="course_id" id="editCourseId">
                <div style="margin-bottom:20px;">
                    <label
                        style="display:block; margin-bottom:8px; color:#800020; font-weight:600; font-size:0.9rem;"><i
                            class="fas fa-tag"></i> Course Name</label>
                    <input type="text" name="edit_course_name" id="editCourseName" placeholder="Enter course name"
                        style="font-size:1rem;">
                </div>
                <div style="display:flex; gap:12px; justify-content:flex-end;">
                    <button class="btn" type="submit" name="edit_course" id="saveCourseBtn"><i class="fas fa-save"></i>
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
            <form id="editExamDateForm" method="post" action="manage_data_ccje.php">
                <input type="hidden" name="exam_date_id" id="editExamDateId">
                <div style="margin-bottom:18px;">
                    <label
                        style="display:block; margin-bottom:8px; color:#800020; font-weight:600; font-size:0.9rem;"><i
                            class="fas fa-calendar-day"></i> Exam Date (Month & Year)</label>
                    <input type="month" name="edit_exam_date_value" id="editExamDateValue" style="font-size:1rem;">
                </div>
                <div style="margin-bottom:20px;">
                    <label
                        style="display:block; margin-bottom:8px; color:#800020; font-weight:600; font-size:0.9rem;"><i
                            class="fas fa-info-circle"></i> Description (Optional)</label>
                    <input type="text" name="edit_exam_date_description" id="editExamDateDescription"
                        placeholder="Enter description" style="font-size:1rem;">
                </div>
                <div style="display:flex; gap:12px; justify-content:flex-end;">
                    <button class="btn" type="submit" name="edit_exam_date" id="saveExamDateBtn"><i
                            class="fas fa-save"></i> Save Changes</button>
                    <button class="cancel-btn" type="button"
                        onclick="document.getElementById('editExamDateModal').style.display='none'"><i
                            class="fas fa-times"></i> Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Custom Confirmation Modal -->
    <div id="customConfirmModal" class="confirm-modal-overlay" style="display: none;">
        <div class="confirm-modal-content">
          <div class="confirm-icon">⚠️</div>
          <div class="confirm-title">Confirm Action</div>
          <div class="confirm-message" id="confirmMessage">Are you sure you want to proceed?</div>
          <div class="confirm-buttons">
            <button class="confirm-ok-btn" id="confirmOkBtn"><i class="fas fa-check"></i> Confirm</button>
            <button class="confirm-cancel-btn" id="confirmCancelBtn"><i class="fas fa-times"></i> Cancel</button>
          </div>
        </div>
    </div>

    <script>
    // Same JS as engineering version
    let confirmCallback = null;

    function showCustomConfirm(message, callback, icon) {
        const modal = document.getElementById('customConfirmModal');
        const messageEl = document.getElementById('confirmMessage');
        const iconEl = document.querySelector('.confirm-icon');
        const okBtn = document.getElementById('confirmOkBtn');
        const cancelBtn = document.getElementById('confirmCancelBtn');
        
        if (!modal || !messageEl || !iconEl || !okBtn || !cancelBtn) {
            console.error('Modal elements not found!', {modal, messageEl, iconEl, okBtn, cancelBtn});
            // Fallback to native confirm
            if (confirm(message)) {
                callback(true);
            } else {
                callback(false);
            }
            return;
        }
        
        messageEl.textContent = message;
        if (icon) {
            iconEl.textContent = icon;
            if (icon === 'Plus') {
                iconEl.style.background = 'linear-gradient(135deg, #D32F2F 0%, #800020 100%)';
            } else if (icon === 'Trash') {
                iconEl.style.background = 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)';
            }
        } else {
            iconEl.textContent = 'Warning';
        }
        modal.style.display = 'flex';
        confirmCallback = callback;
        okBtn.onclick = () => {
            modal.style.display = 'none';
            if (confirmCallback) confirmCallback(true);
            confirmCallback = null;
        };
        cancelBtn.onclick = () => {
            modal.style.display = 'none';
            if (confirmCallback) confirmCallback(false);
            confirmCallback = null;
        };
        modal.onclick = (e) => {
            if (e.target === modal) {
                modal.style.display = 'none';
                if (confirmCallback) confirmCallback(false);
                confirmCallback = null;
            }
        };
    }

    window.confirmLogout = (e) => {
        e.preventDefault();
        showCustomConfirm('Are you sure you want to logout?', (c) => {
            if (c) window.location.href = 'logout.php';
        });
        return false;
    };
    window.openEditExamType = (id, name) => {
        document.getElementById('editExamTypeId').value = id;
        document.getElementById('editExamTypeName').value = name;
        document.getElementById('editExamTypeModal').style.display = 'flex';
    };
    window.closeEditExamType = () => {
        document.getElementById('editExamTypeModal').style.display = 'none';
    };
    window.confirmDeleteExamType = (id, name) => {
        showCustomConfirm('Delete exam type "' + name + '"?', (c) => {
            if (!c) return;
            const f = document.createElement('form');
            f.method = 'post';
            f.action = 'manage_data_ccje.php';
            const i = document.createElement('input');
            i.type = 'hidden';
            i.name = 'exam_type_id';
            i.value = id;
            f.appendChild(i);
            const b = document.createElement('input');
            b.type = 'hidden';
            b.name = 'delete_exam_type';
            b.value = '1';
            f.appendChild(b);
            document.body.appendChild(f);
            f.submit();
        }, 'Trash');
    };
    window.openEditSubject = (id, name, items) => {
        document.getElementById('editSubjectId').value = id;
        document.getElementById('editSubjectName').value = name;
        document.getElementById('editSubjectItems').value = items;
        document.getElementById('editSubjectModal').style.display = 'flex';
    };
    window.closeEditSubject = () => {
        document.getElementById('editSubjectModal').style.display = 'none';
    };
    window.confirmDeleteSubject = (id, name) => {
        showCustomConfirm('Delete subject "' + name + '"?', (c) => {
            if (!c) return;
            const f = document.createElement('form');
            f.method = 'post';
            f.action = 'manage_data_ccje.php';
            const i = document.createElement('input');
            i.type = 'hidden';
            i.name = 'subject_id';
            i.value = id;
            f.appendChild(i);
            const b = document.createElement('input');
            b.type = 'hidden';
            b.name = 'delete_subject';
            b.value = '1';
            f.appendChild(b);
            document.body.appendChild(f);
            f.submit();
        }, 'Trash');
    };
    window.openEditCourse = (id, name) => {
        document.getElementById('editCourseId').value = id;
        document.getElementById('editCourseName').value = name;
        document.getElementById('editCourseModal').style.display = 'flex';
    };
    window.confirmDeleteCourse = (id, name) => {
        showCustomConfirm('Delete course "' + name + '"?', (c) => {
            if (!c) return;
            const f = document.createElement('form');
            f.method = 'post';
            f.action = 'manage_data_ccje.php';
            const i = document.createElement('input');
            i.type = 'hidden';
            i.name = 'course_id';
            i.value = id;
            f.appendChild(i);
            const b = document.createElement('input');
            b.type = 'hidden';
            b.name = 'delete_course';
            b.value = '1';
            f.appendChild(b);
            document.body.appendChild(f);
            f.submit();
        }, 'Trash');
    };
    window.openEditExamDate = (id, dateVal, desc) => {
        document.getElementById('editExamDateId').value = id;
        // Convert YYYY-MM-DD to YYYY-MM for month input
        const monthFormat = dateVal.substring(0, 7);
        document.getElementById('editExamDateValue').value = monthFormat;
        document.getElementById('editExamDateDescription').value = desc;
        document.getElementById('editExamDateModal').style.display = 'flex';
    };
    window.confirmDeleteExamDate = (id, dateVal) => {
        // Convert YYYY-MM-DD to readable month year format
        const date = new Date(dateVal);
        const monthYear = date.toLocaleDateString('en-US', { year: 'numeric', month: 'long' });
        showCustomConfirm('Delete exam date "' + monthYear + '"?', (c) => {
            if (!c) return;
            const f = document.createElement('form');
            f.method = 'post';
            f.action = 'manage_data_ccje.php';
            const i = document.createElement('input');
            i.type = 'hidden';
            i.name = 'exam_date_id';
            i.value = id;
            f.appendChild(i);
            const b = document.createElement('input');
            b.type = 'hidden';
            b.name = 'delete_exam_date';
            b.value = '1';
            f.appendChild(b);
            document.body.appendChild(f);
            f.submit();
        }, 'Trash');
    };

    document.addEventListener('DOMContentLoaded', function() {
        document.body.addEventListener('click', function(e) {
            const btn = e.target.closest('button[data-action]');
            if (!btn) return;
            const action = btn.getAttribute('data-action');
            const id = parseInt(btn.getAttribute('data-id'), 10);
            const name = btn.getAttribute('data-name');
            const items = parseInt(btn.getAttribute('data-items'), 10);
            if (action === 'edit-exam-type') {
                window.openEditExamType(id, name);
            } else if (action === 'delete-exam-type') {
                window.confirmDeleteExamType(id, name);
            } else if (action === 'edit-subject') {
                window.openEditSubject(id, name, items);
            } else if (action === 'delete-subject') {
                window.confirmDeleteSubject(id, name);
            } else if (action === 'edit-course') {
                window.openEditCourse(id, name);
            } else if (action === 'delete-course') {
                window.confirmDeleteCourse(id, name);
            } else if (action === 'edit-exam-date') {
                window.openEditExamDate(id, btn.getAttribute('data-date'), btn.getAttribute(
                    'data-desc'));
            } else if (action === 'delete-exam-date') {
                window.confirmDeleteExamDate(id, btn.getAttribute('data-date'));
            }
        });

        const saveCourseBtn = document.getElementById('saveCourseBtn');
        const saveExamDateBtn = document.getElementById('saveExamDateBtn');
        
        // TEMPORARY: Disable confirmation modals for edit forms too
        if (saveCourseBtn) saveCourseBtn.addEventListener('click', (e) => {
            // Direct submission without confirmation
            console.log('Submitting edit course form directly');
            // Don't prevent default, let form submit naturally
        });
        if (saveExamDateBtn) saveExamDateBtn.addEventListener('click', (e) => {
            // Direct submission without confirmation
            console.log('Submitting edit exam date form directly');
            // Don't prevent default, let form submit naturally
        });
        
        /*
        // Original code with confirmation modals:
        if (saveCourseBtn) saveCourseBtn.addEventListener('click', (e) => {
            e.preventDefault();
            showCustomConfirm('Save changes?', (c) => {
                if (c) document.getElementById('editCourseForm').submit();
            });
        });
        if (saveExamDateBtn) saveExamDateBtn.addEventListener('click', (e) => {
            e.preventDefault();
            showCustomConfirm('Save changes?', (c) => {
                if (c) document.getElementById('editExamDateForm').submit();
            });
        });
        */

        // TEMPORARY: Disable confirmation modals for testing
        /*
        const forms = ['addCourseForm', 'addExamTypeForm', 'addSubjectForm', 'addExamDateForm'];
        forms.forEach(id => {
            const form = document.getElementById(id);
            if (form) {
                console.log('Found form:', id);
                form.addEventListener('submit', (e) => {
                    e.preventDefault();
                    console.log('Form submit prevented for:', id);
                    
                    // Direct submission for testing - bypass modal
                    const modal = document.getElementById('customConfirmModal');
                    if (!modal) {
                        console.error('Modal not found, submitting directly');
                        form.submit();
                        return;
                    }
                    
                    showCustomConfirm('Confirm add?', (c) => {
                        console.log('Confirmation result:', c);
                        if (c) {
                            console.log('Submitting form:', id);
                            form.submit();
                        }
                    }, 'Plus');
                });
            } else {
                console.error('Form not found:', id);
            }
        });
        */
        
        // Direct form submission - no confirmation modal
        console.log('Form handlers disabled - forms will submit directly');
    });
    </script>
</body>

</html>