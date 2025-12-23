<?php
session_start();
// Only allow CAS admin
if (!isset($_SESSION["users"]) || $_SESSION["users"] !== 'cas_admin@lspu.edu.ph') {
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
    header("Location: manage_data_cas.php?error=empty_exam_type"); exit();
  }
  $chk = $conn->prepare("SELECT id FROM board_exam_types WHERE LOWER(TRIM(exam_type_name)) = LOWER(TRIM(?)) AND department='Arts and Sciences'");
  $chk->bind_param('s', $name); $chk->execute(); $chk->store_result();
  if ($chk->num_rows > 0) { $chk->close(); header("Location: manage_data_cas.php?error=exam_type_exists"); exit(); }
  $chk->close();
  $ins = $conn->prepare("INSERT INTO board_exam_types (exam_type_name, department) VALUES (?, 'Arts and Sciences')");
  $ins->bind_param('s', $name);
  if ($ins->execute()) { $ins->close(); header("Location: manage_data_cas.php?success=exam_type_added"); exit(); }
  $ins->close(); header("Location: manage_data_cas.php?error=exam_type_add_failed"); exit();
}

// Handle edit exam type
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['edit_exam_type'])) {
  $id = intval($_POST['exam_type_id'] ?? 0);
  $newname = trim($_POST['edit_exam_type_name'] ?? '');
  if ($id <= 0 || $newname === '') { header("Location: manage_data_cas.php?error=invalid_edit"); exit(); }
  $chk = $conn->prepare("SELECT id FROM board_exam_types WHERE LOWER(TRIM(exam_type_name)) = LOWER(TRIM(?)) AND department='Arts and Sciences' AND id != ?");
  $chk->bind_param('si', $newname, $id); $chk->execute(); $res = $chk->get_result();
  if ($res && $res->num_rows > 0) { $chk->close(); header("Location: manage_data_cas.php?error=exam_type_exists"); exit(); }
  $chk->close();
  $up = $conn->prepare("UPDATE board_exam_types SET exam_type_name = ? WHERE id = ? AND department='Arts and Sciences'");
  $up->bind_param('si', $newname, $id);
  if ($up->execute()) { $up->close(); header("Location: manage_data_cas.php?success=exam_type_updated"); exit(); }
  $up->close(); header("Location: manage_data_cas.php?error=exam_type_update_failed"); exit();
}

// Handle delete exam type
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_exam_type'])) {
  $id = intval($_POST['exam_type_id'] ?? 0);
  if ($id <= 0) { header("Location: manage_data_cas.php?error=invalid_delete"); exit(); }
  $chk = $conn->prepare("SELECT COUNT(*) as cnt FROM subject_exam_types WHERE exam_type_id = ?");
  $chk->bind_param('i', $id); $chk->execute(); $cres = $chk->get_result(); $crow = $cres ? $cres->fetch_assoc() : null; $cnt = intval($crow['cnt'] ?? 0); $chk->close();
  if ($cnt > 0) { header("Location: manage_data_cas.php?error=exam_type_in_use"); exit(); }
  $del = $conn->prepare("UPDATE board_exam_types SET is_deleted = 1, deleted_at = NOW() WHERE id = ? AND department='Arts and Sciences'"); $del->bind_param('i', $id);
  if ($del->execute()) { $del->close(); header("Location: manage_data_cas.php?success=exam_type_deleted"); exit(); }
  $del->close(); header("Location: manage_data_cas.php?error=exam_type_delete_failed"); exit();
}

// Handle add subject
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['add_subject'])) {
  $exam_type_id = intval($_POST['subject_exam_type'] ?? 0);
  $subject_name = trim($_POST['new_subject'] ?? '');
  $total_items = intval($_POST['new_subject_total_items'] ?? 50);
  if ($exam_type_id <= 0 || $subject_name === '') { header("Location: manage_data_cas.php?error=invalid_subject"); exit(); }
  $ins = $conn->prepare("INSERT INTO subjects (subject_name, total_items, department) VALUES (?, ?, 'Arts and Sciences')");
  $ins->bind_param('si', $subject_name, $total_items);
  if (!$ins->execute()) { $ins->close(); header("Location: manage_data_cas.php?error=subject_add_failed"); exit(); }
  $sid = $ins->insert_id; $ins->close();
  $m = $conn->prepare("INSERT INTO subject_exam_types (subject_id, exam_type_id) VALUES (?, ?)"); $m->bind_param('ii', $sid, $exam_type_id); $m->execute(); $m->close();
  header("Location: manage_data_cas.php?success=subject_added&exam_type_id=" . $exam_type_id);
  exit();
}

// Handle edit subject
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['edit_subject'])) {
  $sid = intval($_POST['subject_id'] ?? 0);
  $name = trim($_POST['edit_subject_name'] ?? '');
  $items = intval($_POST['edit_subject_items'] ?? 50);
  if ($sid <= 0 || $name === '') { header("Location: manage_data_cas.php?error=invalid_subject_edit"); exit(); }
  $chk = $conn->prepare("SELECT COUNT(*) as cnt FROM board_passer_subjects WHERE subject_id = ?"); $chk->bind_param('i', $sid); $chk->execute(); $cres = $chk->get_result(); $crow = $cres ? $cres->fetch_assoc() : null; $countUsage = intval($crow['cnt'] ?? 0); $chk->close();
  if ($countUsage > 0) { header("Location: manage_data_cas.php?error=subject_in_use"); exit(); }
  $up = $conn->prepare("UPDATE subjects SET subject_name = ?, total_items = ? WHERE id = ? AND department = 'Arts and Sciences'"); $up->bind_param('sii', $name, $items, $sid);
  if ($up->execute()) { $up->close(); header("Location: manage_data_cas.php?success=subject_updated"); exit(); }
  $up->close(); header("Location: manage_data_cas.php?error=subject_update_failed"); exit();
}

// Handle delete subject
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_subject'])) {
  $sid = intval($_POST['subject_id'] ?? 0);
  if ($sid <= 0) { header("Location: manage_data_cas.php?error=invalid_subject_delete"); exit(); }
  $chk = $conn->prepare("SELECT COUNT(*) as cnt FROM board_passer_subjects WHERE subject_id = ?"); $chk->bind_param('i', $sid); $chk->execute(); $cres = $chk->get_result(); $crow = $cres ? $cres->fetch_assoc() : null; $countUsage = intval($crow['cnt'] ?? 0); $chk->close();
  if ($countUsage > 0) { header("Location: manage_data_cas.php?error=subject_in_use"); exit(); }
  $del = $conn->prepare("UPDATE subjects SET is_deleted = 1, deleted_at = NOW() WHERE id = ? AND department = 'Arts and Sciences'"); $del->bind_param('i', $sid);
  if ($del->execute()) { $del->close(); header("Location: manage_data_cas.php?success=subject_deleted"); exit(); }
  $del->close(); header("Location: manage_data_cas.php?error=subject_delete_failed"); exit();
}

// Handle add exam date
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['add_exam_date'])) {
  $exam_type_id = intval($_POST['exam_type_id'] ?? 0);
  $exam_date = trim($_POST['new_exam_date'] ?? '');
  if (!empty($exam_date)) { $exam_date = $exam_date . '-01'; }
  $exam_desc = trim($_POST['exam_description'] ?? '');
  if ($exam_type_id <= 0 || $exam_date === '') { header("Location: manage_data_cas.php?error=invalid_exam_date"); exit(); }
  $chk = $conn->prepare("SELECT id FROM board_exam_dates WHERE exam_date = ? AND exam_type_id = ? AND department = 'Arts and Sciences'");
  $chk->bind_param('si', $exam_date, $exam_type_id); $chk->execute(); $chk->store_result();
  if ($chk->num_rows > 0) { $chk->close(); header("Location: manage_data_cas.php?error=exam_date_exists&exam_type_id=" . $exam_type_id); exit(); }
  $chk->close();
  $ins = $conn->prepare("INSERT INTO board_exam_dates (exam_date, exam_description, exam_type_id, department) VALUES (?, ?, ?, 'Arts and Sciences')");
  $ins->bind_param('ssi', $exam_date, $exam_desc, $exam_type_id);
  if ($ins->execute()) { $ins->close(); header("Location: manage_data_cas.php?success=exam_date_added&exam_type_id=" . $exam_type_id); exit(); }
  $ins->close(); header("Location: manage_data_cas.php?error=exam_date_add_failed&exam_type_id=" . $exam_type_id); exit();
}

// Handle edit exam date
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['edit_exam_date'])) {
  $eid = intval($_POST['exam_date_id'] ?? 0);
  $edate = trim($_POST['edit_exam_date_value'] ?? '');
  if (!empty($edate)) { $edate = $edate . '-01'; }
  $edesc = trim($_POST['edit_exam_date_description'] ?? '');
  if ($eid <= 0 || $edate === '') { header("Location: manage_data_cas.php?error=invalid_exam_date_edit"); exit(); }
  $g = $conn->prepare("SELECT exam_type_id FROM board_exam_dates WHERE id = ? AND department = 'Arts and Sciences'"); $g->bind_param('i', $eid); $g->execute(); $gres = $g->get_result(); $grow = $gres ? $gres->fetch_assoc() : null; $g->close();
  $exam_type_id = intval($grow['exam_type_id'] ?? 0);
  if ($exam_type_id <= 0) { header("Location: manage_data_cas.php?error=exam_date_not_found"); exit(); }
  $chk = $conn->prepare("SELECT id FROM board_exam_dates WHERE exam_date = ? AND exam_type_id = ? AND id != ? AND department = 'Arts and Sciences'"); $chk->bind_param('sii', $edate, $exam_type_id, $eid); $chk->execute(); $res = $chk->get_result();
  if ($res && $res->num_rows > 0) { $chk->close(); header("Location: manage_data_cas.php?error=exam_date_exists&exam_type_id=" . $exam_type_id); exit(); }
  $chk->close();
  $up = $conn->prepare("UPDATE board_exam_dates SET exam_date = ?, exam_description = ? WHERE id = ? AND department = 'Arts and Sciences'"); $up->bind_param('ssi', $edate, $edesc, $eid);
  if ($up->execute()) { $up->close(); header("Location: manage_data_cas.php?success=exam_date_updated&exam_type_id=" . $exam_type_id); exit(); }
  $up->close(); header("Location: manage_data_cas.php?error=exam_date_update_failed&exam_type_id=" . $exam_type_id); exit();
}

// Handle delete exam date
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_exam_date'])) {
  $eid = intval($_POST['exam_date_id'] ?? 0);
  if ($eid <= 0) { header("Location: manage_data_cas.php?error=invalid_exam_date_delete"); exit(); }
  $check_usage = $conn->prepare("SELECT COUNT(*) as cnt FROM board_passers WHERE board_exam_date = (SELECT exam_date FROM board_exam_dates WHERE id = ?) AND department = 'Arts and Sciences'");
  $check_usage->bind_param('i', $eid); $check_usage->execute(); $usage_res = $check_usage->get_result(); $usage_row = $usage_res ? $usage_res->fetch_assoc() : null; $check_usage->close();
  $used = intval($usage_row['cnt'] ?? 0);
  if ($used > 0) { header("Location: manage_data_cas.php?error=exam_date_in_use&count=" . $used); exit(); }
  $del = $conn->prepare("UPDATE board_exam_dates SET is_deleted = 1, deleted_at = NOW() WHERE id = ? AND department = 'Arts and Sciences'"); $del->bind_param('i', $eid);
  if ($del->execute()) { $del->close(); header("Location: manage_data_cas.php?success=exam_date_deleted"); exit(); }
  $del->close(); header("Location: manage_data_cas.php?error=exam_date_delete_failed"); exit();
}

// Handle add course
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['add_course'])) {
  $new_course = trim($_POST['new_course'] ?? '');
  if ($new_course === '') { header("Location: manage_data_cas.php?error=empty_course"); exit(); }
  $chk = $conn->prepare("SELECT id FROM courses WHERE LOWER(TRIM(course_name)) = LOWER(TRIM(?)) AND department = 'Arts and Sciences'");
  $chk->bind_param('s', $new_course); $chk->execute(); $chk->store_result();
  if ($chk->num_rows > 0) { $chk->close(); header("Location: manage_data_cas.php?error=course_exists"); exit(); }
  $chk->close();
  $ins = $conn->prepare("INSERT INTO courses (course_name, department) VALUES (?, 'Arts and Sciences')"); $ins->bind_param('s', $new_course);
  if ($ins->execute()) { $ins->close(); header("Location: manage_data_cas.php?success=course_added"); exit(); }
  $ins->close(); header("Location: manage_data_cas.php?error=course_add_failed"); exit();
}

// Handle edit course
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['edit_course'])) {
  $cid = intval($_POST['course_id'] ?? 0);
  $cname = trim($_POST['edit_course_name'] ?? '');
  if ($cid <= 0 || $cname === '') { header("Location: manage_data_cas.php?error=invalid_course_edit"); exit(); }
  $chk = $conn->prepare("SELECT id FROM courses WHERE LOWER(TRIM(course_name)) = LOWER(TRIM(?)) AND department = 'Arts and Sciences' AND id != ?"); $chk->bind_param('si', $cname, $cid); $chk->execute(); $res = $chk->get_result();
  if ($res && $res->num_rows > 0) { $chk->close(); header("Location: manage_data_cas.php?error=course_exists"); exit(); }
  $chk->close();
  $up = $conn->prepare("UPDATE courses SET course_name = ? WHERE id = ? AND department = 'Arts and Sciences'"); $up->bind_param('si', $cname, $cid);
  if ($up->execute()) { $up->close(); header("Location: manage_data_cas.php?success=course_updated"); exit(); }
  $up->close(); header("Location: manage_data_cas.php?error=course_update_failed"); exit();
}

// Handle delete course
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_course'])) {
  $cid = intval($_POST['course_id'] ?? 0);
  if ($cid <= 0) { header("Location: manage_data_cas.php?error=invalid_course_delete"); exit(); }
  $g = $conn->prepare("SELECT course_name FROM courses WHERE id = ? AND department = 'Arts and Sciences'"); $g->bind_param('i', $cid); $g->execute(); $gres = $g->get_result(); $grow = $gres ? $gres->fetch_assoc() : null; $g->close();
  $cname = trim($grow['course_name'] ?? '');
  if ($cname === '') { header("Location: manage_data_cas.php?error=course_not_found"); exit(); }
  $chk = $conn->prepare("SELECT COUNT(*) as cnt FROM board_passers WHERE course = ? AND department = 'Arts and Sciences'"); $chk->bind_param('s', $cname); $chk->execute(); $cres = $chk->get_result(); $crow = $cres ? $cres->fetch_assoc() : null; $countUsage = intval($crow['cnt'] ?? 0); $chk->close();
  if ($countUsage > 0) { header("Location: manage_data_cas.php?error=course_in_use"); exit(); }
  $del = $conn->prepare("UPDATE courses SET is_deleted = 1, deleted_at = NOW() WHERE id = ? AND department = 'Arts and Sciences'"); $del->bind_param('i', $cid);
  if ($del->execute()) { $del->close(); header("Location: manage_data_cas.php?success=course_deleted"); exit(); }
  $del->close(); header("Location: manage_data_cas.php?error=course_delete_failed"); exit();
}

// Fetch exam types
$etres = $conn->query("SELECT id, TRIM(exam_type_name) as name FROM board_exam_types WHERE department='Arts and Sciences' AND (is_deleted = 0 OR is_deleted IS NULL) ORDER BY exam_type_name ASC");
$exam_types = [];
while ($r = $etres->fetch_assoc()) { $exam_types[] = $r; }

// Selected exam type
$selected_exam_type_id = intval($_GET['exam_type_id'] ?? 0);
if ($selected_exam_type_id === 0 && !empty($exam_types)) { $selected_exam_type_id = $exam_types[0]['id']; }

// Fetch subjects
$subjects = [];
if ($selected_exam_type_id > 0) {
  $stmt = $conn->prepare("SELECT s.id, TRIM(s.subject_name) as subject_name, s.total_items FROM subjects s JOIN subject_exam_types setmap ON setmap.subject_id = s.id WHERE setmap.exam_type_id = ? AND s.department = 'Arts and Sciences' AND (s.is_deleted = 0 OR s.is_deleted IS NULL) ORDER BY s.subject_name ASC");
  $stmt->bind_param('i', $selected_exam_type_id); $stmt->execute(); $sres = $stmt->get_result();
  while ($sr = $sres->fetch_assoc()) $subjects[] = $sr;
  $stmt->close();
}

// Fetch courses
$courses = [];
$cres = $conn->query("SELECT id, TRIM(course_name) as name FROM courses WHERE department='Arts and Sciences' AND (is_deleted = 0 OR is_deleted IS NULL) ORDER BY course_name ASC");
if ($cres) { while ($cr = $cres->fetch_assoc()) $courses[] = $cr; }

// Fetch exam dates
$exam_dates = [];
if ($selected_exam_type_id > 0) {
  $dstmt = $conn->prepare("SELECT id, exam_date, TRIM(exam_description) as exam_description FROM board_exam_dates WHERE exam_type_id = ? AND department = 'Arts and Sciences' AND (is_deleted = 0 OR is_deleted IS NULL) ORDER BY exam_date ASC");
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
    <title>Manage Data - CAS</title>
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

    /* CAS-specific sidebar color overrides */
    .sidebar .logo {
        color: #4F0024;
        font-weight: 800;
    }

    .sidebar-nav a {
        color: #830034;
    }

    .sidebar-nav a i {
        color: #830034;
    }

    .sidebar-nav a:hover {
        background: linear-gradient(135deg, rgba(255, 161, 195, 0.2) 0%, rgba(131, 0, 52, 0.2) 100%);
        color: #4F0024;
        border-left-color: #830034;
    }

    .sidebar-nav a:hover i {
        color: #4F0024;
    }

    .sidebar-nav a.active {
        background: linear-gradient(135deg, #FFA1C3 0%, #830034 100%);
        color: #fff;
        border-left-color: #4F0024;
        box-shadow: 0 4px 12px rgba(131, 0, 52, 0.3);
    }

    .sidebar-nav a.active i {
        color: #fff;
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


    .topbar {
        position: fixed;
        top: 0;
        left: 260px;
        right: 0;
        background: linear-gradient(135deg, #4F0024 0%, #830034 100%);
        height: 70px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 40px;
        box-shadow: 0 4px 20px rgba(79, 0, 36, 0.3);
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
        background: linear-gradient(135deg, #ffffff 0%, #fdf2f8 100%);
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 10px 40px rgba(131, 0, 52, 0.15);
        border: 2px solid rgba(236, 72, 153, 0.15);
        transition: all 0.3s ease;
    }

    .panel:hover {
        box-shadow: 0 15px 50px rgba(131, 0, 52, 0.25);
        border-color: rgba(236, 72, 153, 0.35);
        transform: translateY(-2px);
    }

    .panel h3 {
        margin: 0 0 14px 0;
        color: #4F0024;
        font-size: 1.1rem;
        padding-bottom: 8px;
        border-bottom: 2px solid #830034;
    }

    .list-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 14px 16px;
        border-radius: 12px;
        background: linear-gradient(135deg, #FFF0FC 0%, #FFA1C3 100%);
        border: 1px solid rgba(131, 0, 52, 0.1);
        transition: all 0.2s ease;
    }

    .list-item:hover {
        background: linear-gradient(135deg, #fce7f3 0%, #fbcfe8 100%);
        border-color: rgba(236, 72, 153, 0.3);
        transform: translateX(4px);
        box-shadow: 0 4px 12px rgba(236, 72, 153, 0.2);
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
        background: linear-gradient(135deg, #ec4899 0%, #db2777 100%);
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
        box-shadow: 0 4px 14px rgba(236, 72, 153, 0.3);
    }

    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(236, 72, 153, 0.5);
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
    select:focus {
        outline: none;
        border-color: #830034;
        box-shadow: 0 0 0 3px rgba(131, 0, 52, 0.1);
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
        background: linear-gradient(135deg, #ec4899 0%, #db2777 100%);
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
        box-shadow: 0 4px 14px rgba(236, 72, 153, 0.3);
    }

    .edit-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(236, 72, 153, 0.5);
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
        background: linear-gradient(135deg, #c026d3 0%, #a21caf 100%);
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
        box-shadow: 0 4px 14px rgba(192, 38, 211, 0.3);
    }

    .view-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(192, 38, 211, 0.5);
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
        background: linear-gradient(135deg, #ffffff 0%, #f0fdfa 100%);
        padding: 40px;
        border-radius: 24px;
        width: 520px;
        box-shadow: 0 30px 80px rgba(6, 182, 212, 0.4), 0 0 0 1px rgba(6, 182, 212, 0.2);
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
        background: linear-gradient(90deg, #830034 0%, #4F0024 50%, #830034 100%);
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
        color: #4F0024;
        font-size: 1.4rem;
        margin-bottom: 24px;
        padding-bottom: 16px;
        border-bottom: 3px solid #830034;
        display: flex;
        align-items: center;
        gap: 12px;
        font-weight: 800;
        letter-spacing: -0.5px;
    }

    .modal-content label {
        display: block;
        margin-bottom: 10px;
        color: #4F0024;
        font-weight: 700;
        font-size: 0.95rem;
        letter-spacing: 0.3px;
    }

    .modal-content input[type="text"],
    .modal-content input[type="number"],
    .modal-content input[type="date"] {
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
    .modal-content input[type="date"]:focus {
        outline: none;
        border-color: #830034;
        box-shadow: 0 0 0 4px rgba(131, 0, 52, 0.15), 0 4px 12px rgba(131, 0, 52, 0.2);
        transform: translateY(-2px);
    }

    .modal-content .btn {
        background: linear-gradient(135deg, #ec4899 0%, #db2777 100%);
        padding: 14px 32px;
        border-radius: 14px;
        font-size: 1.05rem;
        font-weight: 700;
        box-shadow: 0 6px 20px rgba(236, 72, 153, 0.35);
        letter-spacing: 0.3px;
    }

    .modal-content .btn:hover {
        background: linear-gradient(135deg, #db2777 0%, #be185d 100%);
        transform: translateY(-3px);
        box-shadow: 0 12px 30px rgba(236, 72, 153, 0.5);
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
        background: rgba(131, 0, 52, 0.15);
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
        background: linear-gradient(135deg, #FFF0FC 0%, #FFA1C3 100%);
        padding: 18px;
        border-radius: 12px;
        margin-bottom: 20px;
        border: 2px solid #FFA1C3;
    }

    .confirm-modal-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.8) !important;
        backdrop-filter: blur(16px) !important;
        align-items: center;
        justify-content: center;
        z-index: 2000;
        animation: fadeIn 0.25s ease;
    }

    .confirm-modal-content {
        background: linear-gradient(135deg, #ffffff 0%, #FFF0FC 100%);
        padding: 40px;
        border-radius: 24px;
        width: 480px;
        box-shadow: 0 30px 80px rgba(131, 0, 52, 0.4), 0 0 0 1px rgba(131, 0, 52, 0.2);
        animation: confirmSlideIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        text-align: center;
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
        width: 80px;
        height: 80px;
        margin: 0 auto 24px;
        background: linear-gradient(135deg, #830034 0%, #4F0024 100%);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 3rem;
        color: white;
        box-shadow: 0 12px 35px rgba(131, 0, 52, 0.5);
        animation: iconPulse 2.5s ease-in-out infinite;
        position: relative;
    }

    .confirm-icon::before {
        content: '';
        position: absolute;
        inset: -8px;
        border-radius: 50%;
        background: linear-gradient(135deg, #830034 0%, #4F0024 100%);
        opacity: 0.2;
        animation: ringPulse 2.5s ease-in-out infinite;
    }

    @keyframes iconPulse {

        0%,
        100% {
            transform: scale(1);
        }

        50% {
            transform: scale(1.08);
        }
    }

    @keyframes ringPulse {

        0%,
        100% {
            transform: scale(1);
            opacity: 0.2;
        }

        50% {
            transform: scale(1.15);
            opacity: 0.1;
        }
    }

    .confirm-title {
        color: #4F0024;
        font-size: 1.6rem;
        font-weight: 800;
        margin-bottom: 14px;
        letter-spacing: -0.5px;
    }

    .confirm-message {
        color: #475569;
        font-size: 1.05rem;
        margin-bottom: 32px;
        line-height: 1.7;
        font-weight: 500;
    }

    .confirm-buttons {
        display: flex;
        gap: 14px;
        justify-content: center;
    }

    .confirm-ok-btn {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: #fff;
        padding: 14px 36px;
        border-radius: 14px;
        border: none;
        cursor: pointer;
        font-weight: 700;
        font-size: 1.05rem;
        transition: all 0.3s ease;
        box-shadow: 0 6px 20px rgba(16, 185, 129, 0.35);
        letter-spacing: 0.3px;
    }

    .confirm-ok-btn:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 30px rgba(16, 185, 129, 0.5);
        background: linear-gradient(135deg, #059669 0%, #047857 100%);
    }

    .confirm-ok-btn:active {
        transform: translateY(-1px);
    }

    .confirm-cancel-btn {
        background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
        color: #475569;
        padding: 14px 36px;
        border-radius: 14px;
        border: 2px solid #cbd5e1;
        cursor: pointer;
        font-weight: 700;
        font-size: 1.05rem;
        transition: all 0.3s ease;
        letter-spacing: 0.3px;
    }

    .confirm-cancel-btn:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(100, 116, 139, 0.25);
        background: linear-gradient(135deg, #e2e8f0 0%, #cbd5e1 100%);
        border-color: #94a3b8;
    }

    .confirm-cancel-btn:active {
        transform: translateY(-1px);
    }
    </style>
</head>

<body>
    <?php include __DIR__ . '/includes/cas_nav.php'; ?>

    <div class="topbar">
        <h1 class="dashboard-title">College of Arts and Science Admin Dashboard</h1>
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
            <form method="post" action="manage_data_cas.php" class="add-form-section" id="addCourseForm">
                <div class="form-row"><input type="text" name="new_course" id="new_course"
                        placeholder="New course name (e.g., Bachelor of Arts in Communication)" required></div>
                <div class="form-row" style="margin-bottom:0;"><button class="btn" type="submit" name="add_course"><i
                            class="fas fa-plus"></i> Add Course</button></div>
            </form>

            <?php if (empty($courses)): ?>
            <div class="small muted" style="text-align:center; padding:20px;">No courses defined for CAS yet.</div>
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
                <form method="post" action="manage_data_cas.php" class="add-form-section" id="addExamTypeForm">
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
                            <form method="get" action="manage_data_cas.php" style="display:inline;"><input type="hidden"
                                    name="exam_type_id" value="<?= intval($et['id']) ?>"><button class="view-btn"
                                    type="submit"><i class="fas fa-eye"></i> View</button></form>
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
                        style="margin-bottom:16px; padding:12px; background:linear-gradient(135deg, #FFF0FC 0%, #FFA1C3 100%); border-radius:10px; border:2px solid #FFA1C3;">
                        <strong style="color:#4F0024; font-size:1.05rem;"><i class="fas fa-check-circle"></i>
                            <?= htmlspecialchars($etname ?? 'Selected') ?></strong>
                    </div>

                    <form method="post" action="manage_data_cas.php?exam_type_id=<?= $selected_exam_type_id ?>"
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
                    <form method="post" action="manage_data_cas.php?exam_type_id=<?= $selected_exam_type_id ?>"
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
            <form id="editExamTypeForm" method="post" action="manage_data_cas.php">
                <input type="hidden" name="exam_type_id" id="editExamTypeId">
                <div style="margin-bottom:20px;">
                    <label
                        style="display:block; margin-bottom:8px; color:#4F0024; font-weight:600; font-size:0.9rem;"><i
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
            <form id="editSubjectForm" method="post" action="manage_data_cas.php">
                <input type="hidden" name="subject_id" id="editSubjectId">
                <div style="margin-bottom:18px;">
                    <label
                        style="display:block; margin-bottom:8px; color:#4F0024; font-weight:600; font-size:0.9rem;"><i
                            class="fas fa-tag"></i> Subject Name</label>
                    <input type="text" name="edit_subject_name" id="editSubjectName" placeholder="Enter subject name"
                        style="font-size:1rem;">
                </div>
                <div style="margin-bottom:20px;">
                    <label
                        style="display:block; margin-bottom:8px; color:#4F0024; font-weight:600; font-size:0.9rem;"><i
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
            <form id="editCourseForm" method="post" action="manage_data_cas.php">
                <input type="hidden" name="course_id" id="editCourseId">
                <div style="margin-bottom:20px;">
                    <label
                        style="display:block; margin-bottom:8px; color:#4F0024; font-weight:600; font-size:0.9rem;"><i
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
            <form id="editExamDateForm" method="post" action="manage_data_cas.php">
                <input type="hidden" name="exam_date_id" id="editExamDateId">
                <div style="margin-bottom:18px;">
                    <label
                        style="display:block; margin-bottom:8px; color:#4F0024; font-weight:600; font-size:0.9rem;"><i
                            class="fas fa-calendar-day"></i> Exam Date</label>
                    <input type="month" name="edit_exam_date_value" id="editExamDateValue" style="font-size:1rem;">
                </div>
                <div style="margin-bottom:20px;">
                    <label
                        style="display:block; margin-bottom:8px; color:#4F0024; font-weight:600; font-size:0.9rem;"><i
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
    <?php include "./components/manage-data-logout.php" ?>

    <script>
    // Same JS as engineering version
    let confirmCallback = null;

    function showCustomConfirm(message, callback, icon) {
        const modal = document.getElementById('customConfirmModal');
        const messageEl = document.getElementById('confirmMessage');
        const iconEl = document.querySelector('.confirm-icon');
        const okBtn = document.getElementById('confirmOkBtn');
        const cancelBtn = document.getElementById('confirmCancelBtn');
        messageEl.textContent = message;
        if (icon) {
            iconEl.textContent = icon;
            if (icon === 'Plus') {
                iconEl.style.background = 'linear-gradient(135deg, #06b6d4 0%, #0891b2 100%)';
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
            f.action = 'manage_data_cas.php';
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
            f.action = 'manage_data_cas.php';
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
            f.action = 'manage_data_cas.php';
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
        const monthValue = dateVal ? dateVal.substring(0, 7) : '';
        document.getElementById('editExamDateValue').value = monthValue;
        document.getElementById('editExamDateDescription').value = desc;
        document.getElementById('editExamDateModal').style.display = 'flex';
    };
    window.confirmDeleteExamDate = (id, dateVal) => {
        showCustomConfirm('Delete exam date "' + dateVal + '"?', (c) => {
            if (!c) return;
            const f = document.createElement('form');
            f.method = 'post';
            f.action = 'manage_data_cas.php';
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
    });
    </script>
</body>

</html>