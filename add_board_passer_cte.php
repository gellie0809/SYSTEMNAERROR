<?php
session_start();

// Only allow College of Teacher Education admin
if (!isset($_SESSION["users"]) || $_SESSION["users"] !== 'cte_admin@lspu.edu.ph') {
  header("Location: index.php");
  exit();
}

// Database connection
$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "project_db";
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) { 
    die("Connection failed: " . $conn->connect_error); 
}
    
// Load courses for dropdown (Teacher Education - LET)
$courses = [];
$course_stmt = $conn->prepare("SELECT course_name FROM courses WHERE department='Teacher Education' AND (is_deleted = 0 OR is_deleted IS NULL) ORDER BY course_name");
if ($course_stmt && $course_stmt->execute()) {
  $course_result = $course_stmt->get_result();
  while ($row = $course_result->fetch_assoc()) {
    $courses[] = $row['course_name'];
  }
  $course_stmt->close();
}
// Fallback defaults for CTE
if (empty($courses)) {
  $courses = [
    'Bachelor of Elementary Education (BEEd)',
    'Bachelor of Secondary Education (BSEd) - English',
    'Bachelor of Secondary Education (BSEd) - Mathematics',
    'Bachelor of Secondary Education (BSEd) - Science',
    'Bachelor of Secondary Education (BSEd) - Social Studies',
    'Bachelor of Secondary Education (BSEd) - Filipino',
    'Bachelor of Physical Education (BPEd)'
  ];
}

// Load board exam types (LET - Elementary & Secondary)
$board_exam_types = [];
$type_stmt = $conn->prepare("SELECT id, exam_type_name FROM board_exam_types WHERE department='Teacher Education' AND (is_deleted = 0 OR is_deleted IS NULL) ORDER BY exam_type_name");
if ($type_stmt && $type_stmt->execute()) {
  $type_result = $type_stmt->get_result();
  while ($row = $type_result->fetch_assoc()) {
    $board_exam_types[] = $row;
  }
  $type_stmt->close();
}
// Default LET types
if (empty($board_exam_types)) {
  $board_exam_types = [
    ['id' => 1, 'exam_type_name' => 'Licensure Examination for Teachers (LET) - Elementary'],
    ['id' => 2, 'exam_type_name' => 'Licensure Examination for Teachers (LET) - Secondary']
  ];
}

// Load board exam dates (2019–2024) grouped by type
$exam_dates_by_type = [];
$dates_sql = "SELECT d.exam_date, d.exam_description, d.exam_type_id
        FROM board_exam_dates d
        JOIN board_exam_types t ON t.id = d.exam_type_id
        WHERE d.department='Teacher Education' AND YEAR(d.exam_date) BETWEEN 2019 AND 2024 
        AND (d.is_deleted = 0 OR d.is_deleted IS NULL) AND (t.is_deleted = 0 OR t.is_deleted IS NULL)
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

// Load LET subjects (Professional Education, General Education, Major)
$subjects = [];
$colCheck = $conn->query("SHOW COLUMNS FROM subjects LIKE 'total_items'");
if ($colCheck && $colCheck->num_rows === 0) {
  $conn->query("ALTER TABLE subjects ADD COLUMN total_items INT NOT NULL DEFAULT 150 AFTER subject_name");
}

$selected_exam_type = intval($_REQUEST['selected_exam_type'] ?? 0);
if ($selected_exam_type > 0) {
  $sql = "SELECT DISTINCT s.id, TRIM(s.subject_name) as subject_name, COALESCE(s.total_items,150) as total_items
          FROM subjects s
          LEFT JOIN subject_exam_types m ON m.subject_id = s.id
          WHERE s.department='Teacher Education' AND TRIM(s.subject_name) != '' 
            AND (s.is_deleted = 0 OR s.is_deleted IS NULL)
            AND (m.exam_type_id IS NULL OR m.exam_type_id = ?)
          ORDER BY s.subject_name ASC";
  $sub_q = $conn->prepare($sql);
  $sub_q->bind_param("i", $selected_exam_type);
  $sub_q->execute();
  $sub_res = $sub_q->get_result();
} else {
  $sub_res = $conn->query("SELECT id, TRIM(subject_name) AS subject_name, COALESCE(total_items,150) AS total_items 
                           FROM subjects 
                           WHERE department='Teacher Education' AND TRIM(subject_name) != '' 
                           AND (is_deleted = 0 OR is_deleted IS NULL)
                           ORDER BY subject_name ASC");
}

if ($sub_res) {
  while ($r = $sub_res->fetch_assoc()) {
    $subjects[] = $r;
  }
}

// Messages
$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $first_name      = trim($_POST['first_name'] ?? '');
  $last_name       = trim($_POST['last_name'] ?? '');
  $middle_name     = trim($_POST['middle_name'] ?? '');
  $suffix          = trim($_POST['suffix'] ?? '');
  $sex             = $_POST['sex'] ?? '';
  $course          = trim($_POST['course'] ?? '');
  $year_graduated  = intval($_POST['year_graduated'] ?? 0);
  $board_exam_date = $_POST['board_exam_date'] ?? '';
  if ($board_exam_date === 'other' && !empty($_POST['custom_board_exam_date'])) {
    $board_exam_date = $_POST['custom_board_exam_date'];
  }
  $result          = $_POST['result'] ?? '';
  $exam_type       = $_POST['exam_type'] ?? '';
  $board_exam_type = $_POST['board_exam_type'] ?? '';

  // Build full name (LSPU format)
  $name = $last_name . ', ' . $first_name;
  if (!empty($middle_name)) $name .= ' ' . $middle_name;
  if (!empty($suffix))      $name .= ' ' . $suffix;

  // Validation
  $errors = [];
  if (empty($first_name))  $errors[] = 'First name is required';
  if (empty($last_name))   $errors[] = 'Last name is required';
  if (empty($sex))         $errors[] = 'Sex is required';
  if (empty($course))      $errors[] = 'Course is required';
  if ($year_graduated < 1950 || $year_graduated > date('Y')) 
    $errors[] = 'Invalid graduation year';
  if (empty($board_exam_date)) 
    $errors[] = 'LET exam date is required';
  else {
    $exam_year = date('Y', strtotime($board_exam_date));
    if ($exam_year < 2019 || $exam_year > 2024)
      $errors[] = 'LET date must be 2019–2024';
  }
  if (!in_array($result, ['Passed','Failed','Conditional']))
    $errors[] = 'Invalid result';
  if (empty($exam_type))   $errors[] = 'Exam type (First Timer/Repeater) is required';
  if (empty($board_exam_type)) $errors[] = 'LET Level is required';

  if (empty($errors)) {
    $conn->begin_transaction();

    // Insert main record
    $stmt = $conn->prepare("
      INSERT INTO board_passers 
      (name, first_name, middle_name, last_name, suffix, sex, course, year_graduated, 
       board_exam_date, result, exam_type, board_exam_type, department) 
      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Teacher Education')
    ");
    $stmt->bind_param("sssssssissss", 
      $name, $first_name, $middle_name, $last_name, $suffix, $sex, $course, 
      $year_graduated, $board_exam_date, $result, $exam_type, $board_exam_type
    );

    if ($stmt->execute()) {
      $passer_id = $stmt->insert_id;

      // Insert per-subject ratings (LET has 150 items per major part)
      if (!empty($subjects)) {
        $ins = $conn->prepare("
          INSERT INTO board_passer_subjects 
          (board_passer_id, subject_id, grade, passed) 
          VALUES (?, ?, ?, ?)
          ON DUPLICATE KEY UPDATE grade = VALUES(grade), passed = VALUES(passed)
        ");

        foreach ($subjects as $s) {
          $grade = intval($_POST["subject_grade_{$s['id']}"] ?? 0);
          $max   = intval($s['total_items'] ?? 150);

          if ($grade < 0 || $grade > $max) {
            $errors[] = "Grade for {$s['subject_name']} must be 0–{$max}";
            break;
          }

          $passed = ($grade / $max) >= 0.75 ? 1 : 0;
          $ins->bind_param("iiii", $passer_id, $s['id'], $grade, $passed);
          $ins->execute();
        }
        $ins->close();
      }

      if (empty($errors)) {
        $conn->commit();
        $success_message = "LET passer <strong>" . htmlspecialchars($name) . "</strong> added successfully!";
        $_POST = []; // Clear form
      } else {
        $conn->rollback();
      }
    } else {
      $conn->rollback();
      $error_message = "Database error: " . $stmt->error;
    }
    $stmt->close();
  }

  if (!empty($errors)) {
    $error_message = implode('<br>• ', $errors);
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Add New Board Examinee - CTE Dashboard</title>
    <link rel="stylesheet" href="style.css" />
    <link rel="stylesheet" href="css/sidebar.css" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
    .remark-pass {
        color: #065f46;
        background: #ecfdf5;
        padding: 4px 8px;
        border-radius: 6px;
    }

    .remark-fail {
        color: #991b1b;
        background: #fff1f2;
        padding: 4px 8px;
        border-radius: 6px;
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
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

    .main-content {
        margin-left: 260px;
        margin-top: 70px;
        padding: 50px 60px;
        min-height: calc(100vh - 70px);
        position: relative;
        z-index: 2;
    }

    .form-container {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border-radius: 28px;
        padding: 0;
        box-shadow:
            0 32px 64px rgba(44, 90, 160, 0.15),
            0 0 0 1px rgba(255, 255, 255, 0.2),
            inset 0 1px 0 rgba(255, 255, 255, 0.8);
        border: 2px solid rgba(44, 90, 160, 0.1);
        overflow: hidden;
        width: 100%;
        max-width: none;
        margin: 0;
        position: relative;
        animation: containerSlideIn 0.8s cubic-bezier(0.25, 0.8, 0.25, 1);
    }

    @keyframes containerSlideIn {
        0% {
            opacity: 0;
            transform: translateY(40px) scale(0.95);
        }

        100% {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    .form-container::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(135deg, rgba(70, 99, 172, 0.02) 0%, rgba(193, 216, 240, 0.03) 100%);
        pointer-events: none;
        z-index: 1;
    }

    .form-header {
        background: linear-gradient(135deg, #4663ac 0%, #c1d8f0 100%);
        /* CTE Blue */
        color: white;
        padding: 40px;
        text-align: center;
        position: relative;
        overflow: hidden;
        z-index: 2;
    }

    .form-header::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -50%;
        width: 100%;
        height: 200%;
        background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.15), transparent);
        animation: shimmer 4s infinite;
        z-index: 1;
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

    .form-header h2 {
        margin: 0 0 12px 0;
        font-size: 2.2rem;
        font-weight: 800;
        letter-spacing: 1px;
        position: relative;
        z-index: 2;
    }

    .form-header p {
        margin: 0;
        font-size: 1.1rem;
        opacity: 0.95;
        font-weight: 400;
        position: relative;
        z-index: 2;
    }



    .form-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: radial-gradient(circle 200px at var(--x, 50%) var(--y, 50%), rgba(255, 255, 255, 0.15) 0%, transparent 70%);
        opacity: 0;
        transition: opacity 0.3s ease;
        pointer-events: none;
    }

    .form-header:hover::before {
        opacity: 1;
    }

    .form-icon {
        width: 90px;
        height: 90px;
        border-radius: 22px;
        background: rgba(255, 255, 255, 0.25);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 24px;
        font-size: 2.2rem;
        box-shadow:
            0 15px 35px rgba(70, 99, 172, 0.3),
            inset 0 1px 0 rgba(255, 255, 255, 0.4);
        border: 2px solid rgba(255, 255, 255, 0.25);
        position: relative;
        z-index: 2;
        backdrop-filter: blur(10px);
        transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
        animation: iconFloat 3s ease-in-out infinite;
    }

    @keyframes iconFloat {

        0%,
        100% {
            transform: translateY(0px);
        }

        50% {
            transform: translateY(-8px);
        }
    }

    .form-icon:hover {
        transform: scale(1.1) rotate(5deg);
        box-shadow:
            0 20px 40px rgba(44, 90, 160, 0.4),
            inset 0 1px 0 rgba(255, 255, 255, 0.5);
    }

    .tab-navigation {
        display: flex;
        background: linear-gradient(135deg, rgba(249, 250, 251, 0.95) 0%, rgba(243, 244, 246, 0.9) 100%);
        backdrop-filter: blur(15px);
        -webkit-backdrop-filter: blur(15px);
        border-bottom: 1px solid rgba(44, 90, 160, 0.1);
        position: relative;
        z-index: 2;
    }

    .tab-btn {
        flex: 1;
        padding: 20px 24px;
        border: none;
        background: transparent;
        cursor: pointer;
        font-weight: 600;
        color: #6b7280;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
        position: relative;
        font-size: 1rem;
    }

    .tab-btn::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(135deg, rgba(44, 90, 160, 0.05) 0%, rgba(58, 141, 222, 0.03) 100%);
        opacity: 0;
        transition: all 0.3s ease;
    }

    .tab-btn:hover::before {
        opacity: 1;
    }

    .tab-btn.active {
        color: #4663ac;
        /* CTE Blue */
        background: linear-gradient(135deg, rgba(70, 99, 172, 0.12) 0%, rgba(193, 216, 240, 0.08) 100%);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        border-bottom: 3px solid #4663ac;
    }

    .tab-btn.active::after {
        content: '';
        position: absolute;
        bottom: -1px;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(90deg, #4663ac 0%, #6B8DD6 100%);
        /* CTE Blue gradient */
        box-shadow: 0 2px 8px rgba(70, 99, 172, 0.3);
    }

    .tab-btn i {
        font-size: 1.1rem;
        transition: all 0.3s ease;
    }

    .tab-btn:hover i {
        transform: scale(1.1);
    }

    .tab-btn.active i {
        color: #4663ac;
        /* CTE Blue */
        transform: scale(1.15);
    }

    .tab-content {
        display: none;
        opacity: 0;
        transform: translateY(20px);
        transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
    }

    .tab-content.active {
        display: block;
        opacity: 1;
        transform: translateY(0);
        animation: tabSlideIn 0.5s ease;
    }

    @keyframes tabSlideIn {
        0% {
            opacity: 0;
            transform: translateY(20px);
        }

        100% {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .tab-header {
        padding: 50px 60px 40px;
        text-align: center;
        background: linear-gradient(135deg, rgba(44, 90, 160, 0.05) 0%, rgba(58, 141, 222, 0.03) 100%);
        position: relative;
        overflow: hidden;
        z-index: 2;
    }

    .tab-header::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background:
            radial-gradient(circle, rgba(44, 90, 160, 0.08) 0%, transparent 70%);
        animation: tabFloat 6s ease-in-out infinite;
        z-index: 1;
    }

    @keyframes tabFloat {

        0%,
        100% {
            transform: translateY(-10px) rotate(0deg);
        }

        50% {
            transform: translateY(10px) rotate(180deg);
        }
    }

    .tab-icon {
        width: 80px;
        height: 80px;
        border-radius: 20px;
        background: linear-gradient(135deg, #4663ac 0%, #c1d8f0 100%);
        /* CTE Blue */
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 24px;
        font-size: 2rem;
        box-shadow:
            0 15px 35px rgba(70, 99, 172, 0.4),
            inset 0 1px 0 rgba(255, 255, 255, 0.3);
        border: 2px solid rgba(255, 255, 255, 0.2);
        position: relative;
        z-index: 2;
        transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
        animation: iconPulse 2s ease-in-out infinite;
    }

    @keyframes iconPulse {

        0%,
        100% {
            transform: scale(1);
            box-shadow: 0 15px 35px rgba(44, 90, 160, 0.4);
        }

        50% {
            transform: scale(1.05);
            box-shadow: 0 20px 40px rgba(44, 90, 160, 0.5);
        }
    }

    .tab-icon:hover {
        transform: scale(1.1) rotate(5deg);
        box-shadow: 0 20px 40px rgba(44, 90, 160, 0.6);
    }


    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 40px 50px;
        padding: 50px 60px;
        background: linear-gradient(145deg, rgba(255, 255, 255, 0.8) 0%, rgba(248, 250, 252, 0.6) 100%);
        position: relative;
        z-index: 2;
    }

    .form-group.full-width {
        grid-column: 1 / -1;
    }

    .form-group {
        position: relative;
        margin-bottom: 8px;
        transition: all 0.3s ease;
    }

    .form-group:hover {
        transform: translateY(-2px);
    }

    .form-group::before {
        content: '';
        position: absolute;
        top: -5px;
        left: -5px;
        right: -5px;
        bottom: -5px;
        background: linear-gradient(135deg, rgba(44, 90, 160, 0.05) 0%, rgba(58, 141, 222, 0.03) 100%);
        border-radius: 20px;
        opacity: 0;
        transition: all 0.3s ease;
        z-index: -1;
    }

    .form-group:hover::before {
        opacity: 1;
    }

    .form-group.focused::before {
        opacity: 1;
        background: linear-gradient(135deg, rgba(44, 90, 160, 0.08) 0%, rgba(58, 141, 222, 0.05) 100%);
    }

    .form-group label {
        display: block;
        font-weight: 700;
        color: #374151;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: 1.05rem;
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .form-group label:hover {
        color: #4663ac;
        transform: translateX(2px);
    }

    .form-group label i {
        color: #4663ac;
        filter: drop-shadow(0 2px 4px rgba(44, 90, 160, 0.2));
        transition: all 0.3s ease;
        font-size: 1.1rem;
    }

    .form-group label:hover i {
        transform: scale(1.1) rotate(5deg);
        color: #6B8DD6;
    }

    .form-group input,
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 18px 24px;
        border: 2px solid rgba(226, 232, 240, 0.8);
        border-radius: 16px;
        font-size: 1.05rem;
        transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        box-shadow:
            0 4px 6px -1px rgba(44, 90, 160, 0.1),
            inset 0 1px 0 rgba(255, 255, 255, 0.9);
        font-family: 'Inter', sans-serif;
        resize: vertical;
        position: relative;
    }

    .form-group input::placeholder,
    .form-group select::placeholder,
    .form-group textarea::placeholder {
        color: #9ca3af;
        opacity: 0.8;
        font-style: italic;
    }

    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: #4663ac;
        box-shadow:
            0 0 0 4px rgba(70, 99, 172, 0.15),
            0 8px 25px rgba(70, 99, 172, 0.2);
        transform: translateY(-3px) scale(1.01);
        background: rgba(255, 255, 255, 1);
    }

    .form-group input:hover,
    .form-group select:hover,
    .form-group textarea:hover {
        border-color: #6B8DD6;
        transform: translateY(-1px);
        box-shadow:
            0 6px 12px rgba(44, 90, 160, 0.15),
            inset 0 1px 0 rgba(255, 255, 255, 0.9);
    }

    .tab-footer {
        padding: 40px 60px 50px;
        display: flex;
        gap: 24px;
        justify-content: space-between;
        background: linear-gradient(135deg, rgba(249, 250, 251, 0.95) 0%, rgba(243, 244, 246, 0.9) 100%);
        backdrop-filter: blur(15px);
        -webkit-backdrop-filter: blur(15px);
        border-top: 1px solid rgba(44, 90, 160, 0.1);
        position: relative;
        z-index: 2;
    }

    .btn {
        padding: 18px 40px;
        border: none;
        border-radius: 16px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
        display: flex;
        align-items: center;
        gap: 12px;
        min-width: 160px;
        justify-content: center;
        text-decoration: none;
        font-size: 1.05rem;
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        border: 2px solid rgba(255, 255, 255, 0.2);
        position: relative;
        overflow: hidden;
    }

    .btn::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
        transition: left 0.6s ease;
    }

    .btn:hover::before {
        left: 100%;
    }

    .btn-primary {
        background: linear-gradient(135deg, #4663ac 0%, #6B8DD6 100%);
        /* CTE Blue */
        color: white;
        box-shadow:
            0 8px 25px rgba(70, 99, 172, 0.4),
            inset 0 1px 0 rgba(255, 255, 255, 0.3);
        margin-left: auto;
        border-color: rgba(255, 255, 255, 0.2);
    }

    .btn-primary:hover {
        background: linear-gradient(135deg, #3d5a8c 0%, #4663ac 100%);
        transform: translateY(-3px) scale(1.02);
        box-shadow:
            0 12px 35px rgba(70, 99, 172, 0.5),
            inset 0 1px 0 rgba(255, 255, 255, 0.4);
    }

    .btn-primary:active {
        transform: translateY(-1px) scale(1.01);
        transition: all 0.1s ease;
    }

    .btn-secondary {
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.9) 0%, rgba(243, 244, 246, 0.9) 100%);
        color: #374151;
        box-shadow:
            0 4px 12px rgba(0, 0, 0, 0.08),
            inset 0 1px 0 rgba(255, 255, 255, 0.8);
        border-color: rgba(226, 232, 240, 0.5);
    }

    .btn-secondary:hover {
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(229, 231, 235, 0.95) 100%);
        transform: translateY(-2px) scale(1.01);
        box-shadow:
            0 8px 20px rgba(0, 0, 0, 0.12),
            inset 0 1px 0 rgba(255, 255, 255, 0.9);
        color: #4663ac;
        /* CTE Blue on hover */
    }

    .btn-secondary:active {
        transform: translateY(0) scale(1);
        transition: all 0.1s ease;
    }

    .alert {
        padding: 16px 20px;
        border-radius: 12px;
        margin-bottom: 24px;
        display: flex;
        align-items: center;
        gap: 12px;
        font-weight: 500;
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.3);
    }

    .alert-success {
        background: linear-gradient(145deg, rgba(70, 99, 172, 0.15) 0%, rgba(107, 141, 214, 0.1) 100%);
        border: 1px solid rgba(70, 99, 172, 0.3);
        color: #4663ac;
        box-shadow: 0 4px 12px rgba(70, 99, 172, 0.2);
    }

    .alert-error {
        background: linear-gradient(145deg, rgba(239, 68, 68, 0.15) 0%, rgba(220, 38, 38, 0.1) 100%);
        border: 1px solid rgba(239, 68, 68, 0.3);
        color: #dc2626;
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.2);
    }

    /* Modal Base Styles */
    .modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        background: rgba(15, 23, 42, 0.6);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        z-index: 9999;
        display: none;
        justify-content: center;
        align-items: center;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
    }

    .modal.show {
        display: flex;
        opacity: 1;
        visibility: visible;
    }

    .modal-content {
        background: rgba(255, 255, 255, 0.95);
        border-radius: 20px;
        padding: 0;
        max-width: 500px;
        width: 90%;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border: 1px solid rgba(255, 255, 255, 0.2);
        transform: scale(0.9) translateY(20px);
        transition: all 0.3s ease;
    }

    /* Improved modal sizes and readable layout */
    @media (min-width: 1280px) {
        .modal-content {
            max-width: 620px;
            width: 70%;
        }
    }

    @media (min-width: 1600px) {
        .modal-content {
            max-width: 760px;
            width: 56%;
        }
    }

    .modal.show .modal-content {
        transform: scale(1) translateY(0);
    }

    @media (max-width: 1200px) {
        .form-grid {
            grid-template-columns: 1fr;
            gap: 30px;
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


        .form-grid {
            grid-template-columns: 1fr;
            padding: 30px;
            gap: 25px;
        }

        .tab-header {
            padding: 30px;
        }

        .tab-footer {
            flex-direction: column;
            padding: 30px;
        }

        .btn-primary {
            margin-left: 0;
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

    /* Validation modal - Professional Design */
    .validation-modal {
        max-width: 600px;
        border-radius: 24px;
        overflow: hidden;
        box-shadow:
            0 30px 70px rgba(0, 0, 0, 0.3),
            0 0 0 1px rgba(255, 255, 255, 0.1);
        border: none;
        max-height: 90vh;
        display: flex;
        flex-direction: column;
    }

    /* Elegant header for validation modal */
    .validation-modal .modal-header {
        background: linear-gradient(135deg, #4663ac 0%, #6B8DD6 100%);
        color: #ffffff;
        padding: 32px 36px;
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        gap: 18px;
        box-shadow: 0 8px 24px rgba(70, 99, 172, 0.25);
        position: relative;
        overflow: hidden;
        flex-shrink: 0;
    }

    .validation-modal .modal-header .header-text {
        flex: 1;
        z-index: 2;
        position: relative;
        width: 100%;
    }

    .validation-modal .modal-header::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -50%;
        width: 200%;
        height: 200%;
        background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.12), transparent);
        animation: shimmer 3s infinite;
    }

    .validation-modal .modal-header::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 1px;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.5), transparent);
    }

    .validation-modal .modal-header .header-icon-badge {
        width: 64px;
        height: 64px;
        border-radius: 18px;
        background: rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(10px);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #ffffff;
        font-size: 1.8rem;
        font-weight: 800;
        flex: 0 0 64px;
        box-shadow:
            0 8px 20px rgba(0, 0, 0, 0.2),
            inset 0 1px 0 rgba(255, 255, 255, 0.4);
        border: 2px solid rgba(255, 255, 255, 0.3);
        z-index: 2;
        position: relative;
    }

    .validation-modal .modal-title {
        margin: 0;
        font-weight: 800;
        font-size: 1.5rem;
        line-height: 1.3;
        color: #ffffff;
        z-index: 2;
        position: relative;
        letter-spacing: 0.3px;
    }

    .validation-modal .modal-subtitle {
        margin: 8px 0 0 0;
        opacity: 0.95;
        font-size: 0.98rem;
        font-weight: 400;
        color: rgba(255, 255, 255, 0.9);
        z-index: 2;
        position: relative;
        line-height: 1.5;
    }

    .validation-modal .field-list {
        padding: 32px 36px;
        background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
        flex: 1;
        overflow-y: auto;
        max-height: 400px;
    }

    .validation-modal .field-list::-webkit-scrollbar {
        width: 8px;
    }

    .validation-modal .field-list::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 10px;
    }

    .validation-modal .field-list::-webkit-scrollbar-thumb {
        background: linear-gradient(180deg, #6B8DD6 0%, #4663ac 100%);
        border-radius: 10px;
    }

    .validation-modal .field-list::-webkit-scrollbar-thumb:hover {
        background: linear-gradient(180deg, #4663ac 0%, #3d5a8c 100%);
    }

    .validation-modal .field-list h4 {
        margin: 0 0 20px 0;
        color: #1e293b;
        font-weight: 700;
        font-size: 1.1rem;
        display: flex;
        align-items: center;
        gap: 10px;
        padding-bottom: 12px;
        border-bottom: 2px solid #e2e8f0;
    }

    .validation-modal .field-list h4::before {
        content: '📋';
        font-size: 1.3rem;
    }

    .validation-modal #missingFieldsList {
        display: flex;
        flex-direction: column;
        gap: 14px;
        margin-bottom: 24px;
    }

    .validation-modal .field-item {
        background: #ffffff;
        padding: 18px 20px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        gap: 16px;
        box-shadow:
            0 2px 8px rgba(0, 0, 0, 0.06),
            0 0 0 1px #e2e8f0;
        border: 2px solid transparent;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .validation-modal .field-item:hover {
        border-color: #4663ac;
        box-shadow:
            0 4px 16px rgba(70, 99, 172, 0.2),
            0 0 0 1px #4663ac;
        transform: translateX(6px);
        background: linear-gradient(90deg, #ffffff 0%, #E8F0FC 100%);
    }

    .validation-modal .field-item i {
        color: #4663ac;
        font-size: 1.3rem;
        min-width: 28px;
        text-align: center;
    }

    .validation-modal .field-item span {
        flex: 1;
        font-weight: 600;
        color: #1e293b;
        font-size: 1rem;
    }

    .validation-modal .field-item small {
        color: #64748b;
        font-size: 0.88rem;
        font-weight: 500;
        background: #f1f5f9;
        padding: 4px 10px;
        border-radius: 6px;
    }

    .validation-modal .field-list .info-note {
        margin-top: 0;
        padding: 18px 20px;
        background: linear-gradient(135deg, #E8F0FC 0%, #D6E4F9 100%);
        border-left: 4px solid #4663ac;
        border-radius: 12px;
        display: flex;
        align-items: flex-start;
        gap: 14px;
        box-shadow: 0 2px 8px rgba(70, 99, 172, 0.1);
    }

    .validation-modal .field-list .info-note i {
        color: #4663ac;
        font-size: 1.2rem;
        margin-top: 2px;
        flex-shrink: 0;
    }

    .validation-modal .field-list .info-note p {
        margin: 0;
        color: #4663ac;
        font-size: 0.93rem;
        line-height: 1.6;
        font-weight: 500;
    }

    .validation-modal .field-list .info-note strong {
        color: #4663ac;
        font-weight: 700;
    }

    .validation-modal .modal-buttons {
        padding: 24px 36px;
        background: #ffffff;
        display: flex;
        justify-content: flex-end;
        border-top: 1px solid #e2e8f0;
        flex-shrink: 0;
    }

    .validation-modal .modal-btn.validation-ok {
        background: linear-gradient(135deg, #4663ac 0%, #6B8DD6 100%);
        color: #ffffff;
        padding: 14px 32px;
        border-radius: 12px;
        border: none;
        font-weight: 700;
        font-size: 1rem;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow:
            0 4px 14px rgba(70, 99, 172, 0.35),
            inset 0 1px 0 rgba(255, 255, 255, 0.3);
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .validation-modal .modal-btn.validation-ok:hover {
        background: linear-gradient(135deg, #3d5a8c 0%, #4663ac 100%);
        transform: translateY(-2px);
        box-shadow:
            0 6px 20px rgba(70, 99, 172, 0.45),
            inset 0 1px 0 rgba(255, 255, 255, 0.4);
    }

    .validation-modal .modal-btn.validation-ok:active {
        transform: translateY(0);
        box-shadow: 0 2px 8px rgba(70, 99, 172, 0.3);
    }

    .validation-modal .modal-btn.validation-ok i {
        font-size: 1.15rem;
    }


    /* Confirmation modal tweaks */
    .confirmation-modal {
        max-width: 700px;
        border-radius: 14px;
        overflow: hidden
    }

    /* Polished header for confirmation modal */
    .confirmation-modal .modal-header {
        background: linear-gradient(90deg, #4663ac, #c1d8f0);
        color: white;
        padding: 14px 18px;
        display: flex;
        align-items: center;
        gap: 12px;
        border-top-left-radius: 14px;
        border-top-right-radius: 14px;
        box-shadow: 0 10px 30px rgba(14, 46, 110, 0.08)
    }

    .confirmation-modal .modal-header .header-icon-badge {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        background: rgba(255, 255, 255, 0.12);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 1.1rem;
        flex: 0 0 40px
    }

    .confirmation-modal .modal-title {
        margin: 0;
        font-weight: 800;
        font-size: 1.05rem
    }

    .receipt-container {
        padding: 18px 20px;
        background: linear-gradient(180deg, #fff, #f8fbff);
    }

    .receipt-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px
    }

    .receipt-header h4 {
        margin: 0;
        color: #1f2937
    }

    .receipt-section {
        margin-top: 12px;
        padding-top: 12px;
        border-top: 1px dashed rgba(44, 90, 160, 0.06)
    }

    .receipt-row {
        display: flex;
        justify-content: space-between;
        padding: 8px 0;
        align-items: center
    }

    .receipt-row .label {
        color: #6b7280;
        font-weight: 700
    }

    .receipt-row .value {
        color: #111827;
        font-weight: 800
    }

    .receipt-footer {
        margin-top: 12px;
        padding-top: 12px;
        border-top: 1px solid rgba(0, 0, 0, 0.02);
        color: #6b7280
    }

    .confirmation-modal .modal-buttons {
        padding: 14px 20px;
        display: flex;
        gap: 12px;
        justify-content: flex-end
    }

    .modal-btn.confirmation-confirm {
        background: linear-gradient(90deg, #4663ac, #6B8DD6);
        color: #fff;
        padding: 10px 16px;
        border-radius: 10px;
        border: none;
        font-weight: 800
    }

    .modal-btn.confirmation-cancel {
        background: transparent;
        border: 1px solid rgba(44, 90, 160, 0.12);
        padding: 10px 16px;
        border-radius: 10px
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
        background: linear-gradient(135deg, #c1d8f0 0%, #4663ac 25%, #6B8DD6 50%, #4663ac 75%, #c1d8f0 100%) !important;
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
        background: linear-gradient(90deg, #c1d8f0 0%, #4663ac 50%, #6B8DD6 100%) !important;
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
        background: linear-gradient(135deg, #4663ac 0%, #6B8DD6 50%, #c1d8f0 100%) !important;
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
        background: linear-gradient(135deg, #6B8DD6, #4663ac, #c1d8f0, #4663ac) !important;
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
        background: linear-gradient(135deg, #4663ac 0%, #6B8DD6 100%) !important;
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
        color: #4663ac !important;
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
        background: linear-gradient(135deg, #4663ac 0%, #6B8DD6 50%, #c1d8f0 100%) !important;
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
        background: linear-gradient(135deg, #3d5a8c 0%, #4663ac 50%, #6B8DD6 100%) !important;
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
        background: linear-gradient(135deg, #3d5a8c 0%, #6B8DD6 50%, #4663ac 100%) !important;
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
        background: linear-gradient(135deg, #4663ac 0%, #6B8DD6 50%, #c1d8f0 100%) !important;
        transform: translateY(-2px) scale(1.05) !important;
        box-shadow: 0 12px 30px rgba(70, 99, 172, 0.4) !important;
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

    /* Validation Warning Modal Styles */
    .validation-modal {
        max-width: 480px;
        width: 95%;
        max-height: calc(100vh - 40px);
        overflow-y: auto;
        margin: 20px auto;
    }

    .validation-icon {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        box-shadow: 0 15px 35px rgba(245, 158, 11, 0.3);
    }

    .validation-icon::before {
        background: linear-gradient(135deg, #fbbf24, #f59e0b, #d97706);
    }

    .field-list {
        background: linear-gradient(145deg, rgba(254, 243, 199, 0.8) 0%, rgba(253, 230, 138, 0.6) 100%);
        border: 2px solid rgba(245, 158, 11, 0.3);
        border-radius: 12px;
        padding: 20px;
        margin: 20px 0;
        backdrop-filter: blur(5px);
        -webkit-backdrop-filter: blur(5px);
    }

    .field-list h4 {
        margin: 0 0 16px 0;
        color: #92400e;
        font-size: 1.1rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .field-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 16px;
        margin-bottom: 8px;
        background: rgba(255, 255, 255, 0.8);
        border-radius: 8px;
        border: 1px solid rgba(245, 158, 11, 0.2);
        transition: all 0.3s ease;
    }

    .field-item:last-child {
        margin-bottom: 0;
    }

    .field-item:hover {
        background: rgba(255, 255, 255, 0.95);
        transform: translateX(4px);
        box-shadow: 0 4px 12px rgba(245, 158, 11, 0.2);
    }

    .field-item i {
        color: #f59e0b;
        font-size: 1.1rem;
        width: 20px;
        text-align: center;
    }

    .field-item span {
        color: #92400e;
        font-weight: 500;
        flex: 1;
    }

    .validation-tip {
        background: linear-gradient(145deg, rgba(59, 130, 246, 0.1) 0%, rgba(29, 78, 216, 0.05) 100%);
        border: 1px solid rgba(59, 130, 246, 0.3);
        border-radius: 12px;
        padding: 16px 20px;
        margin: 20px 0;
        display: flex;
        align-items: center;
        gap: 12px;
        backdrop-filter: blur(5px);
        -webkit-backdrop-filter: blur(5px);
    }

    .validation-tip i {
        color: #4663ac;
        font-size: 1.2rem;
    }

    .validation-tip p {
        margin: 0;
        color: #4663ac;
        font-weight: 500;
        line-height: 1.5;
    }

    .modal-btn.validation-ok {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        color: #ffffff;
        box-shadow: 0 4px 15px rgba(245, 158, 11, 0.3);
        min-width: 140px;
    }

    .modal-btn.validation-ok:hover {
        background: linear-gradient(135deg, #d97706 0%, #b45309 100%);
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(245, 158, 11, 0.4);
    }

    /* Confirmation Modal Styles - Ultra Clean & Beautiful Design */
    .confirmation-modal {
        max-width: 680px;
        width: 95%;
        max-height: calc(100vh - 60px);
        overflow-y: auto;
        overflow-x: hidden;
        margin: 30px auto;
        background: rgba(255, 255, 255, 0.98);
        backdrop-filter: blur(25px);
        -webkit-backdrop-filter: blur(25px);
        border-radius: 28px;
        box-shadow:
            0 32px 64px rgba(44, 90, 160, 0.15),
            0 16px 32px rgba(16, 185, 129, 0.08),
            inset 0 1px 0 rgba(255, 255, 255, 0.9);
        border: 1px solid rgba(44, 90, 160, 0.15);
        position: relative;
        scrollbar-width: thin;
        scrollbar-color: rgba(44, 90, 160, 0.2) transparent;
    }

    /* Enhanced Custom Scrollbar */
    .confirmation-modal::-webkit-scrollbar {
        width: 6px;
    }

    .confirmation-modal::-webkit-scrollbar-track {
        background: rgba(248, 250, 252, 0.8);
        border-radius: 10px;
    }

    .confirmation-modal::-webkit-scrollbar-thumb {
        background: linear-gradient(135deg, rgba(44, 90, 160, 0.4) 0%, rgba(16, 185, 129, 0.4) 100%);
        border-radius: 10px;
        border: 1px solid rgba(255, 255, 255, 0.5);
    }

    .confirmation-modal::-webkit-scrollbar-thumb:hover {
        background: linear-gradient(135deg, rgba(44, 90, 160, 0.6) 0%, rgba(16, 185, 129, 0.6) 100%);
    }

    /* Clean Modal Header */
    .confirmation-modal .modal-header {
        padding: 32px 32px 24px;
        text-align: center;
        border-bottom: 1px solid rgba(44, 90, 160, 0.08);
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.9) 0%, rgba(248, 250, 252, 0.95) 100%);
        border-radius: 28px 28px 0 0;
    }

    .confirmation-modal .modal-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: #1e293b;
        margin: 16px 0 8px;
        letter-spacing: -0.025em;
    }

    .confirmation-modal .modal-subtitle {
        font-size: 1rem;
        color: #64748b;
        margin: 0;
        font-weight: 500;
    }

    .confirmation-modal::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg,
                #4663ac 0%,
                #6B8DD6 25%,
                #c1d8f0 50%,
                #6B8DD6 75%,
                #4663ac 100%);
        animation: gradientShift 3s ease-in-out infinite;
    }

    @keyframes gradientShift {

        0%,
        100% {
            opacity: 1;
        }

        50% {
            opacity: 0.8;
        }
    }

    .confirmation-icon {
        background: linear-gradient(135deg, #4663ac 0%, #6B8DD6 100%);
        width: 72px;
        height: 72px;
        border-radius: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
        color: white;
        font-size: 1.75rem;
        box-shadow:
            0 8px 32px rgba(16, 185, 129, 0.25),
            inset 0 2px 0 rgba(255, 255, 255, 0.2);
        position: relative;
        transition: all 0.3s ease;
    }

    .confirmation-icon:hover {
        transform: translateY(-2px);
        box-shadow:
            0 12px 40px rgba(16, 185, 129, 0.3),
            inset 0 2px 0 rgba(255, 255, 255, 0.2);
    }

    @keyframes confirmationIconPulse {

        0%,
        100% {
            transform: scale(1);
        }

        50% {
            transform: scale(1.05);
        }
    }

    @keyframes iconRotate {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
    }

    .receipt-container {
        background: #ffffff;
        border: 1px solid rgba(226, 232, 240, 0.8);
        border-radius: 16px;
        margin: 32px 0;
        overflow: hidden;
        box-shadow:
            0 4px 20px rgba(44, 90, 160, 0.08),
            0 1px 3px rgba(0, 0, 0, 0.05);
    }

    .receipt-header {
        background: linear-gradient(135deg, #4663ac 0%, #6B8DD6 100%);
        color: white;
        padding: 24px 28px;
        text-align: center;
        position: relative;
    }

    .receipt-header h4 {
        margin: 0 0 8px 0;
        font-size: 1.25rem;
        font-weight: 700;
        letter-spacing: -0.025em;
    }

    .receipt-header p {
        margin: 0 0 12px 0;
        opacity: 0.9;
        font-size: 0.95rem;
        font-weight: 500;
    }

    .receipt-date {
        font-size: 0.875rem;
        opacity: 0.85;
        background: rgba(255, 255, 255, 0.15);
        padding: 6px 12px;
        border-radius: 12px;
        display: inline-block;
        font-weight: 500;
    }

    .receipt-section {
        padding: 24px 28px;
        border-bottom: 1px solid rgba(226, 232, 240, 0.6);
    }

    .receipt-section:last-child {
        border-bottom: none;
    }

    .receipt-section h5 {
        margin: 0 0 20px 0;
        color: #1e293b;
        font-size: 1rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 10px;
        letter-spacing: -0.025em;
        text-transform: uppercase;
        font-size: 0.875rem;
    }

    .receipt-section h5 i {
        background: linear-gradient(135deg, #4663ac 0%, #6B8DD6 100%);
        color: white;
        width: 28px;
        height: 28px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.75rem;
        box-shadow: 0 2px 8px rgba(44, 90, 160, 0.2);
    }

    .receipt-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 12px;
        padding: 8px 0;
    }

    .receipt-row:last-child {
        margin-bottom: 0;
    }

    .receipt-row:last-child {
        margin-bottom: 0;
        border-bottom: none;
    }

    .receipt-row .label {
        font-weight: 600;
        color: #64748b;
        font-size: 0.875rem;
    }

    .receipt-row .value {
        font-weight: 600;
        color: #1e293b;
        text-align: right;
        max-width: 60%;
        word-break: break-word;
    }

    .result-badge {
        padding: 8px 16px;
        border-radius: 25px;
        font-size: 0.9rem !important;
        font-weight: 700 !important;
        text-transform: uppercase;
        letter-spacing: 1px;
        position: relative;
        overflow: hidden;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .result-badge::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg,
                transparent 0%,
                rgba(255, 255, 255, 0.3) 50%,
                transparent 100%);
        animation: badgeShine 2s ease-in-out infinite;
    }

    @keyframes badgeShine {
        0% {
            left: -100%;
        }

        50% {
            left: 100%;
        }

        100% {
            left: 100%;
        }
    }

    .result-passed {
        background: linear-gradient(135deg, #4663ac 0%, #6B8DD6 100%);
        color: white;
        border: 2px solid #4663ac;
    }

    .result-failed {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        color: white;
        border: 2px solid #b91c1c;
    }

    .result-conditional {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        color: white;
        border: 2px solid #b45309;
    }

    .receipt-footer {
        padding: 20px 28px;
        background: linear-gradient(135deg,
                rgba(241, 245, 249, 0.8) 0%,
                rgba(248, 250, 252, 0.9) 100%);
        text-align: center;
        font-size: 1rem;
        color: #64748b;
        border-top: 2px solid rgba(44, 90, 160, 0.1);
    }

    .receipt-footer i {
        color: #4663ac;
        margin-right: 8px;
        font-size: 1.1rem;
    }

    .modal-btn.confirmation-confirm {
        background: linear-gradient(135deg, #4663ac 0%, #6B8DD6 100%);
        color: #ffffff;
        border: none;
        font-weight: 600;
        padding: 12px 24px;
        border-radius: 12px;
        transition: all 0.2s ease;
        box-shadow: 0 4px 12px rgba(70, 99, 172, 0.3);
    }

    .modal-btn.confirmation-confirm:hover {
        background: linear-gradient(135deg, #3d5a8c 0%, #4663ac 100%);
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(70, 99, 172, 0.4);
    }

    .modal-btn.confirmation-cancel {
        background: #f8fafc;
        color: #64748b;
        border: 1px solid rgba(226, 232, 240, 0.8);
        font-weight: 600;
        padding: 12px 24px;
        border-radius: 12px;
        transition: all 0.2s ease;
    }

    .modal-btn.confirmation-cancel:hover {
        background: #f1f5f9;
        color: #475569;
        border-color: #cbd5e1;
    }

    @media (max-width: 768px) {
        .confirmation-modal {
            max-width: 95%;
            margin: 10px auto;
            max-height: calc(100vh - 20px);
            border-radius: 16px;
        }

        .modal {
            padding: 10px 0;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
        }

        .modal-content {
            margin: 10px auto;
            max-height: calc(100vh - 20px);
            padding: 20px 16px;
        }

        .receipt-row {
            flex-direction: column;
            align-items: flex-start;
            gap: 4px;
        }

        .receipt-row .value {
            text-align: left;
            max-width: 100%;
        }

        .receipt-container {
            margin: 15px 0;
        }

        .receipt-header {
            padding: 20px 16px;
        }

        .receipt-header h4 {
            font-size: 1.2rem;
        }

        .receipt-section {
            padding: 16px 20px;
        }

        .receipt-section h5 {
            font-size: 1rem;
        }

        .modal-btn.confirmation-confirm,
        .modal-btn.confirmation-cancel {
            width: 100%;
            margin: 8px 0;
        }
    }

    /* Beautiful Confirmation Modal Animations */
    @keyframes fadeIn {
        from {
            opacity: 0;
            backdrop-filter: blur(0px);
        }

        to {
            opacity: 1;
            backdrop-filter: blur(8px);
        }
    }

    @keyframes confirmationSlideIn {
        from {
            opacity: 0;
            transform: translateY(30px) scale(0.95);
        }

        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    @keyframes confirmationSlideOut {
        from {
            opacity: 1;
            transform: translateY(0) scale(1);
        }

        to {
            opacity: 0;
            transform: translateY(-20px) scale(0.98);
        }
    }

    @keyframes confirmationBounceIn {
        0% {
            opacity: 0;
            transform: scale(0.3) translateY(50px);
        }

        50% {
            opacity: 1;
            transform: scale(1.05) translateY(-10px);
        }

        70% {
            transform: scale(0.98) translateY(5px);
        }

        100% {
            transform: scale(1) translateY(0);
        }
    }

    #confirmationModal.show .modal-content {
        animation: confirmationBounceIn 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    #confirmationModal.show .confirmation-icon {
        animation: confirmationIconPulse 3s ease-in-out infinite,
            iconEntranceScale 0.8s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    /* Enhanced modal entrance effects */
    .confirmation-modal.entering {
        animation: confirmationSlideIn 0.5s cubic-bezier(0.25, 0.8, 0.25, 1);
    }
    </style>
</head>

<body>
    <!-- Sidebar -->
    <?php include __DIR__ . '/includes/cte_nav.php'; ?>

    <!-- Top bar -->
    <div class="topbar">
        <h1 class="dashboard-title">College of Teacher Education Admin Dashboard</h1>
        <a href="logout.php" class="logout-btn" onclick="return confirmLogout(event)">
            <i class="fas fa-sign-out-alt"></i>
            Logout
        </a>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <?php if ($success_message): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?= $success_message ?>
        </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-triangle"></i>
            <?= $error_message ?>
        </div>
        <?php endif; ?>

        <div class="form-container">
            <div class="form-header">
                <div class="form-icon">
                    <i class="fas fa-user-plus"></i>
                </div>
                <h2>Add New Board Examinee</h2>
                <p>Enter student information below</p>
            </div>

            <!-- Board Examinee Type Selection -->
            <div class="examinee-type-section" style="margin-bottom: 24px; padding: 24px; background: linear-gradient(145deg, #E3ECF5 0%, #C1D8F0 100%); border-radius: 16px; border: 2px solid rgba(70, 99, 172, 0.3);">
                <h4 style="margin-bottom: 16px; color: #1e3a5f; font-size: 1.1rem; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-user-tag"></i>
                    Board Examinee Type
                </h4>
                <div style="display: flex; gap: 16px; flex-wrap: wrap;">
                    <label style="flex: 1; min-width: 250px; cursor: pointer; padding: 16px; background: white; border: 2px solid #c1d8f0; border-radius: 12px; transition: all 0.3s;" class="examinee-type-label" data-type="named">
                        <input type="radio" name="examinee_type_radio" value="named" checked onchange="toggleExamineeFields()" style="margin-right: 8px;">
                        <span style="font-weight: 600; color: #334155; font-size: 1rem;">
                            <i class="fas fa-user-graduate" style="color: #4663AC; margin-right: 6px;"></i>
                            Named Student
                        </span>
                        <p style="font-size: 0.85rem; color: #64748b; margin: 8px 0 0 28px; line-height: 1.4;">Full personal information and exam details required</p>
                    </label>
                    <label style="flex: 1; min-width: 250px; cursor: pointer; padding: 16px; background: white; border: 2px solid #c1d8f0; border-radius: 12px; transition: all 0.3s;" class="examinee-type-label" data-type="anonymous">
                        <input type="radio" name="examinee_type_radio" value="anonymous" onchange="toggleExamineeFields()" style="margin-right: 8px;">
                        <span style="font-weight: 600; color: #334155; font-size: 1rem;">
                            <i class="fas fa-user-secret" style="color: #4663AC; margin-right: 6px;"></i>
                            Data Entry
                        </span>
                        <p style="font-size: 0.85rem; color: #64748b; margin: 8px 0 0 28px; line-height: 1.4;">Exam data only, no personal information collected</p>
                    </label>
                </div>
            </div>

            <!-- Tab Navigation -->
            <div class="tab-navigation" id="tabNavigation">
                <button class="tab-btn active" data-tab="personal" onclick="switchTab('personal')" id="personalTabBtn">
                    <i class="fas fa-user"></i>
                    Personal Info
                </button>
                <button class="tab-btn" data-tab="exam" onclick="switchTab('exam')">
                    <i class="fas fa-graduation-cap"></i>
                    Exam Info
                </button>
            </div>

            <form method="POST" action="">
                <input type="hidden" name="examinee_type" id="examineeTypeHidden" value="named">
                
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
                            <label for="last_name">
                                <i class="fas fa-user"></i>Last Name *
                            </label>
                            <input type="text" id="last_name" name="last_name" required
                                value="<?= isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : '' ?>"
                                pattern="[a-zA-Z\s,.-]+"
                                title="Only letters, spaces, commas, periods, and hyphens allowed">
                        </div>

                        <div class="form-group">
                            <label for="first_name">
                                <i class="fas fa-user"></i>First Name *
                            </label>
                            <input type="text" id="first_name" name="first_name" required
                                value="<?= isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : '' ?>"
                                pattern="[a-zA-Z\s,.-]+"
                                title="Only letters, spaces, commas, periods, and hyphens allowed">
                        </div>

                        <div class="form-group">
                            <label for="middle_name">
                                <i class="fas fa-user"></i>Middle Name
                            </label>
                            <input type="text" id="middle_name" name="middle_name"
                                value="<?= isset($_POST['middle_name']) ? htmlspecialchars($_POST['middle_name']) : '' ?>"
                                pattern="[a-zA-Z\s,.-]+"
                                title="Only letters, spaces, commas, periods, and hyphens allowed">
                        </div>

                        <div class="form-group">
                            <label for="suffix">
                                <i class="fas fa-award"></i>Suffix
                            </label>
                            <input type="text" id="suffix" name="suffix"
                                value="<?= isset($_POST['suffix']) ? htmlspecialchars($_POST['suffix']) : '' ?>"
                                pattern="[a-zA-Z\s,.-]+"
                                title="Only letters, spaces, commas, periods, and hyphens allowed">
                        </div>

                        <div class="form-group">
                            <label for="sex">
                                <i class="fas fa-venus-mars"></i>Sex *
                            </label>
                            <select id="sex" name="sex" required>
                                <option value="">Select Sex</option>
                                <option value="Male"
                                    <?= (isset($_POST['sex']) && $_POST['sex'] == 'Male') ? 'selected' : '' ?>>Male
                                </option>
                                <option value="Female"
                                    <?= (isset($_POST['sex']) && $_POST['sex'] == 'Female') ? 'selected' : '' ?>>Female
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="tab-footer">
                        <a href="dashboard_cte.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i>
                            Cancel
                        </a>
                        <button type="button" onclick="nextTab()" class="btn btn-primary">
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
                            <label for="course_exam">
                                <i class="fas fa-graduation-cap"></i>Course *
                            </label>
                            <select id="course_exam" name="course" required>
                                <option value="">Select Course</option>
                                <?php foreach($courses as $course): ?>
                                <option value="<?= htmlspecialchars($course) ?>"
                                    <?= (isset($_POST['course']) && $_POST['course'] == $course) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($course) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group named-only-field">
                            <label for="year_graduated">
                                <i class="fas fa-calendar"></i>Year Graduated *
                            </label>
                            <select id="year_graduated" name="year_graduated" required>
                                <option value="">Select Year</option>
                                <?php
                $current_year = date('Y');
                $selected_year = isset($_POST['year_graduated']) ? $_POST['year_graduated'] : '';
                for ($year = $current_year; $year >= 1950; $year--) {
                    $selected = ($selected_year == $year) ? 'selected' : '';
                    echo "<option value=\"$year\" $selected>$year</option>";
                }
                ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="board_exam_date">
                                <i class="fas fa-calendar-check"></i>Board Exam Date *
                            </label>
                            <select id="board_exam_date" name="board_exam_date" required disabled>
                                <option value="">Select Board Exam Type First</option>
                            </select>
                            <div style="
                font-size: 0.85rem;
                color: #6b7280;
                margin-top: 8px;
                display: flex;
                align-items: center;
                gap: 6px;
                font-style: italic;
              ">
                                <i class="fas fa-info-circle" style="color: #2c5aa0;"></i>
                                Available dates will appear after selecting a board exam type
                            </div>
                        </div>

                        <div class="form-group" id="customDateGroup" style="display: none;">
                            <label for="custom_board_exam_date">
                                <i class="fas fa-calendar-alt"></i>Custom Board Exam Date *
                            </label>
                            <input type="date" id="custom_board_exam_date" name="custom_board_exam_date"
                                min="2019-01-01" max="2024-12-31"
                                value="<?= isset($_POST['custom_board_exam_date']) ? htmlspecialchars($_POST['custom_board_exam_date']) : '' ?>">
                            <div style="
                font-size: 0.85rem;
                color: #6b7280;
                margin-top: 8px;
                display: flex;
                align-items: center;
                gap: 6px;
                font-style: italic;
              ">
                                <i class="fas fa-info-circle" style="color: #2c5aa0;"></i>
                                Date must be between January 1, 2019 and December 31, 2024
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="result">
                                <i class="fas fa-award"></i>Result *
                            </label>
                            <select id="result" name="result" required>
                                <option value="">Select Result</option>
                                <option value="Passed"
                                    <?= (isset($_POST['result']) && $_POST['result'] == 'Passed') ? 'selected' : '' ?>>
                                    Passed</option>
                                <option value="Failed"
                                    <?= (isset($_POST['result']) && $_POST['result'] == 'Failed') ? 'selected' : '' ?>>
                                    Failed</option>
                                <option value="Conditional"
                                    <?= (isset($_POST['result']) && $_POST['result'] == 'Conditional') ? 'selected' : '' ?>>
                                    Conditional</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="exam_type">
                                <i class="fas fa-redo"></i>Take Attempts *
                            </label>
                            <select id="exam_type" name="exam_type" required>
                                <option value="">Select Take Attempts</option>
                                <option value="First Timer"
                                    <?= (isset($_POST['exam_type']) && $_POST['exam_type'] == 'First Timer') ? 'selected' : '' ?>>
                                    First Timer</option>
                                <option value="Repeater"
                                    <?= (isset($_POST['exam_type']) && $_POST['exam_type'] == 'Repeater') ? 'selected' : '' ?>>
                                    Repeater</option>
                            </select>
                        </div>

                        <div class="form-group named-only-field">
                            <label for="rating">
                                <i class="fas fa-star"></i>Rating
                            </label>
                            <input type="number" id="rating" name="rating"
                                value="<?= isset($_POST['rating']) ? htmlspecialchars($_POST['rating']) : '' ?>" min="0"
                                max="100" step="0.01" placeholder="Exam rating/score (optional)">
                        </div>

                        <div class="form-group">
                            <label for="board_exam_type">
                                <i class="fas fa-certificate"></i>Board Exam Type *
                            </label>
                            <select id="board_exam_type" name="board_exam_type" required
                                onchange="updateAvailableDates()">
                                <option value="">Select Board Exam Type</option>
                                <?php foreach ($board_exam_types as $exam_type): ?>
                                <option value="<?= htmlspecialchars($exam_type['exam_type_name']) ?>"
                                    data-type-id="<?= $exam_type['id'] ?>"
                                    <?= (isset($_POST['board_exam_type']) && $_POST['board_exam_type'] == $exam_type['exam_type_name']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($exam_type['exam_type_name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Subjects: initially hidden and loaded after selecting board exam type + date -->
                        <div id="subjectsContainer" class="form-group full-width"
                            style="grid-column: 1 / -1; display: none;">
                            <label>
                                <i class="fas fa-book-open"></i>Subjects
                            </label>
                            <div id="subjectsList" style="display:flex;flex-direction:column;gap:10px;margin-top:8px;">
                                <!-- subjects will be injected here via JS -->
                            </div>
                        </div>
                        <div id="noSubjectsPlaceholder" class="form-group full-width"
                            style="grid-column: 1 / -1; color: #6b7280; display: none;">
                            No subjects available for the selected take attempts/date. Please add subjects in Manage
                            Courses &gt; Subjects.
                        </div>
                    </div>

                    <div class="tab-footer">
                        <button type="button" onclick="prevTab()" class="btn btn-secondary" id="prevTabBtn">
                            <i class="fas fa-arrow-left"></i>
                            Previous
                        </button>
                        <button type="button" id="addStudentBtn" onclick="addStudentDirect()" class="btn btn-primary">
                            <i class="fas fa-plus"></i>
                            <span id="submitBtnText">Add New Board Examinee</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Validation Warning Modal -->
    <div id="validationModal" class="modal" style="display: none;">
        <div class="modal-content validation-modal">
            <div class="modal-header">
                <div class="header-icon-badge"><i class="fas fa-clipboard-list"></i></div>
                <div class="header-text">
                    <h3 class="modal-title">Incomplete Form Submission</h3>
                    <p class="modal-subtitle">Required information is missing. Please review and complete the
                        highlighted fields.</p>
                </div>
            </div>

            <div class="field-list">
                <h4>Missing Required Fields</h4>
                <div id="missingFieldsList"></div>

                <div class="info-note">
                    <i class="fas fa-info-circle"></i>
                    <p>Fields marked with an asterisk (<strong>*</strong>) are mandatory. Kindly ensure all required
                        information is provided before proceeding to the next step.</p>
                </div>
            </div>

            <div class="modal-buttons">
                <button id="validationOk" class="modal-btn validation-ok">
                    <i class="fas fa-check-circle"></i>
                    Understood
                </button>
            </div>
        </div>
    </div>

    <!-- Logout Confirmation Modal -->
    <?php include "./components/logout-modal.php" ?>


    <!-- Confirmation Modal -->
    <div id="confirmationModal" class="modal" style="display: none; overflow-y: auto;">
        <div class="modal-content confirmation-modal">
            <div class="modal-header">
                <div class="header-icon-badge"><i class="fas fa-receipt"></i></div>
                <div>
                    <h3 class="modal-title">Confirm Student Details</h3>
                    <p class="modal-subtitle">Please review the information before adding to database</p>
                </div>
            </div>

            <div class="receipt-container">
                <div class="receipt-header">
                    <h4><i class="fas fa-university"></i> LSPU CTE Department</h4>
                    <p>Board Examinee Registration</p>
                    <div class="receipt-date">Date: <span id="receiptDate"></span></div>
                </div>

                <div class="receipt-section">
                    <h5><i class="fas fa-user"></i> Personal Information</h5>
                    <div class="receipt-row">
                        <span class="label">Full Name:</span>
                        <span class="value" id="confirmName"></span>
                    </div>
                    <div class="receipt-row">
                        <span class="label">Sex:</span>
                        <span class="value" id="confirmSex"></span>
                    </div>
                    <div class="receipt-row">
                        <span class="label">Course:</span>
                        <span class="value" id="confirmCourse"></span>
                    </div>
                </div>

                <div class="receipt-section">
                    <h5><i class="fas fa-graduation-cap"></i> Exam Information</h5>
                    <div class="receipt-row">
                        <span class="label">Year Graduated:</span>
                        <span class="value" id="confirmYear"></span>
                    </div>
                    <div class="receipt-row">
                        <span class="label">Board Exam Date:</span>
                        <span class="value" id="confirmExamDate"></span>
                    </div>
                    <div class="receipt-row">
                        <span class="label">Result:</span>
                        <span class="value result-badge" id="confirmResult"></span>
                    </div>
                    <div class="receipt-row">
                        <span class="label">Take Attempts:</span>
                        <span class="value" id="confirmExamType"></span>
                    </div>
                    <div class="receipt-row">
                        <span class="label">Board Exam Type:</span>
                        <span class="value" id="confirmBoardExamType"></span>
                    </div>
                    <div class="receipt-row" id="ratingRow" style="display: none;">
                        <span class="label">Rating:</span>
                        <span class="value" id="confirmRating"></span>
                    </div>
                </div>

                <div class="receipt-footer">
                    <p><i class="fas fa-info-circle"></i> Please verify all information is correct before proceeding</p>
                </div>
            </div>

            <div class="modal-buttons"
                style="padding: 20px 28px; margin-top: 0; background: rgba(248, 250, 252, 0.8); border-top: 2px solid rgba(44, 90, 160, 0.1); border-radius: 0 0 20px 20px;">
                <button id="confirmAddStudent" class="modal-btn confirmation-confirm">
                    <i class="fas fa-check"></i>
                    Add to Database
                </button>
                <button id="confirmCancel" class="modal-btn confirmation-cancel">
                    <i class="fas fa-edit"></i>
                    Cancel
                </button>
            </div>
        </div>
    </div>

    <script>
    // Exam dates data grouped by exam type
    const examDatesByType = <?= json_encode($exam_dates_by_type) ?>;

    // Function to update available dates based on selected exam type
    function updateAvailableDates() {
        const examTypeSelect = document.getElementById('board_exam_type');
        const boardExamDateSelect = document.getElementById('board_exam_date');
        const boardExamTypeSelect = document.getElementById('board_exam_type');
        const subjectsContainer = document.getElementById('subjectsContainer');
        const subjectsList = document.getElementById('subjectsList');
        const noSubjectsPlaceholder = document.getElementById('noSubjectsPlaceholder');

        function clearSubjectsUI() {
            if (subjectsList) subjectsList.innerHTML = '';
            if (subjectsContainer) subjectsContainer.style.display = 'none';
            if (noSubjectsPlaceholder) noSubjectsPlaceholder.style.display = 'none';
        }

        async function fetchAndRenderSubjectsForSelectedType() {
            try {
                clearSubjectsUI();
                if (!boardExamTypeSelect || !boardExamDateSelect) return;
                const typeOption = boardExamTypeSelect.options[boardExamTypeSelect.selectedIndex];
                const dateValue = boardExamDateSelect.value;
                if (!typeOption || !typeOption.dataset.typeId || !dateValue) {
                    return; // need both type and date
                }
                const typeId = typeOption.dataset.typeId;
                const resp = await fetch('fetch_subjects_department.php?exam_type_id=' + encodeURIComponent(
                    typeId));
                if (!resp.ok) {
                    console.error('Failed to fetch subjects', resp.statusText);
                    return;
                }
                const subjects = await resp.json();
                console.debug('fetchAndRenderSubjectsForSelectedType: received', subjects.length,
                    'subjects for type', typeId);
                if (!subjects || subjects.length === 0) {
                    if (noSubjectsPlaceholder) noSubjectsPlaceholder.style.display = 'block';
                    return;
                }

                // Render subjects
                subjects.forEach(s => {
                    // avoid duplicate rendering if a row for this subject id already exists
                    try {
                        const existing = document.getElementById('subject_row_' + s.id);
                        if (existing) {
                            console.debug('Skipping duplicate subject render for id', s.id);
                            return;
                        }
                    } catch (e) {}
                    const row = document.createElement('div');
                    row.id = 'subject_row_' + s.id;
                    row.style.cssText = 'display:flex;gap:12px;align-items:center;';

                    const hid = document.createElement('input');
                    hid.type = 'hidden';
                    hid.name = 'subject_id_' + s.id;
                    hid.value = s.id;

                    const title = document.createElement('div');
                    title.style.cssText =
                        'flex:1;padding:12px;border-radius:10px;border:1px solid #e5e7eb;background:#f8fafc;font-weight:600;';
                    title.textContent = s.subject_name;

                    const grade = document.createElement('input');
                    grade.type = 'number';
                    grade.name = 'subject_grade_' + s.id;
                    grade.id = 'subject_grade_' + s.id;
                    grade.placeholder = 'Grade';
                    grade.min = '0';
                    grade.max = String(parseInt(s.total_items || 100, 10));
                    grade.step = '1';
                    grade.required = true;
                    grade.style.cssText =
                        'width:140px;padding:12px;border-radius:10px;border:1px solid #e5e7eb;';

                    // Show computed result as readonly remarks (label + value) and include a hidden input so server receives the value
                    const resultWrapper = document.createElement('div');
                    resultWrapper.style.cssText =
                        'width:160px;padding:6px;border-radius:10px;border:1px solid #e5e7eb;background:#fff;display:flex;flex-direction:column;align-items:center;justify-content:center;font-weight:600;';

                    const resultLabel = document.createElement('div');
                    resultLabel.style.cssText = 'font-size:12px;color:#6b7280;margin-bottom:4px;';
                    resultLabel.textContent = 'Remarks';

                    const resultValue = document.createElement('div');
                    resultValue.id = 'subject_result_display_' + s.id;
                    resultValue.style.cssText = 'font-weight:700;color:#111827;';
                    resultValue.textContent = '';

                    const resultHidden = document.createElement('input');
                    resultHidden.type = 'hidden';
                    resultHidden.name = 'subject_result_' + s.id;
                    resultHidden.id = 'subject_result_' + s.id;
                    resultHidden.value = '';

                    resultWrapper.appendChild(resultLabel);
                    resultWrapper.appendChild(resultValue);

                    row.appendChild(hid);
                    row.appendChild(title);
                    row.appendChild(grade);
                    row.appendChild(resultWrapper);
                    row.appendChild(resultHidden);

                    subjectsList.appendChild(row);
                    // Attach immediate clamping behavior for this new grade input
                    (function(ginput, smax) {
                        ginput.addEventListener('input', function() {
                            if (this.value === '') return;
                            const v = parseInt(this.value.replace(/[^0-9-]/g, ''), 10);
                            if (isNaN(v)) {
                                this.value = '';
                                return;
                            }
                            if (v > smax) this.value = String(smax);
                            else if (v < 0) this.value = '0';
                            else this.value = String(v);
                            // compute remark immediately while typing
                            try {
                                const sid = ginput.id.replace('subject_grade_', '');
                                const hidden = document.getElementById('subject_result_' + sid);
                                const disp = document.getElementById('subject_result_display_' +
                                    sid);
                                if (hidden && disp) {
                                    const gg = parseInt(this.value, 10);
                                    if (!isNaN(gg)) {
                                        const pct = (gg / smax) * 100;
                                        const rr = (pct >= 75) ? 'Passed' : 'Failed';
                                        hidden.value = rr;
                                        disp.textContent = rr;
                                        disp.classList.remove('remark-pass', 'remark-fail');
                                        if (rr === 'Passed') disp.classList.add('remark-pass');
                                        else disp.classList.add('remark-fail');
                                    }
                                }
                            } catch (e) {
                                console.warn('remark compute failed', e);
                            }
                        });
                        ginput.addEventListener('change', function() {
                            if (this.value === '') return;
                            const v = parseInt(this.value.replace(/[^0-9-]/g, ''), 10);
                            if (isNaN(v)) {
                                this.value = '';
                                return;
                            }
                            if (v > smax) this.value = String(smax);
                            else if (v < 0) this.value = '0';
                            else this.value = String(v);
                            // compute remark on change as well
                            try {
                                const sid = ginput.id.replace('subject_grade_', '');
                                const hidden = document.getElementById('subject_result_' + sid);
                                const disp = document.getElementById('subject_result_display_' +
                                    sid);
                                if (hidden && disp) {
                                    const gg = parseInt(this.value, 10);
                                    if (!isNaN(gg)) {
                                        const pct = (gg / smax) * 100;
                                        const rr = (pct >= 75) ? 'Passed' : 'Failed';
                                        hidden.value = rr;
                                        disp.textContent = rr;
                                        disp.classList.remove('remark-pass', 'remark-fail');
                                        if (rr === 'Passed') disp.classList.add('remark-pass');
                                        else disp.classList.add('remark-fail');
                                    }
                                }
                            } catch (e) {
                                console.warn('remark compute failed', e);
                            }
                        });
                    })(grade, parseInt(s.total_items || 100, 10));
                });

                // Show container
                if (subjectsContainer) subjectsContainer.style.display = 'block';
            } catch (err) {
                console.error('Error fetching or rendering subjects', err);
            }
        }

        // Listen for changes: attach listeners only once to avoid duplicate fetches
        if (boardExamTypeSelect && boardExamDateSelect && !updateAvailableDates._bound) {
            boardExamTypeSelect.addEventListener('change', function() {
                // when type changes, clear date selection and subjects
                clearSubjectsUI();
                updateAvailableDates();
            });

            boardExamDateSelect.addEventListener('change', function() {
                if (this._suppressChange) return;
                fetchAndRenderSubjectsForSelectedType();
            });
            // mark as bound so subsequent calls won't re-bind
            updateAvailableDates._bound = true;
        }
        const selectedOption = examTypeSelect.options[examTypeSelect.selectedIndex];

        // Clear current options
        boardExamDateSelect.innerHTML = '<option value="">Select Board Exam Date</option>';

        if (selectedOption && selectedOption.dataset.typeId) {
            const typeId = selectedOption.dataset.typeId;
            const availableDates = examDatesByType[typeId] || [];

            if (availableDates.length > 0) {
                // Enable the select and add available dates
                boardExamDateSelect.disabled = false;

                availableDates.forEach(examDate => {
                    const option = document.createElement('option');
                    option.value = examDate.date;
                    option.textContent = new Date(examDate.date).toLocaleDateString('en-US', {
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric'
                    });
                    if (examDate.description) {
                        option.textContent += ' - ' + examDate.description;
                    }
                    boardExamDateSelect.appendChild(option);
                });

                // Auto-select the first available date and fetch subjects immediately
                if (boardExamDateSelect.options.length > 1) {
                    // options[0] is the placeholder; select the first real date (index 1)
                    try {
                        boardExamDateSelect._suppressChange = true;
                        boardExamDateSelect.selectedIndex = 1;
                    } catch (e) {
                        console.warn('auto-select date failed', e);
                    }
                    // now release suppression and trigger a single change event to fetch subjects
                    try {
                        boardExamDateSelect._suppressChange = false;
                        boardExamDateSelect.dispatchEvent(new Event('change'));
                    } catch (e) {
                        // fallback: call directly
                        try {
                            fetchAndRenderSubjectsForSelectedType();
                        } catch (ex) {
                            console.warn('auto-fetch subjects failed', ex);
                        }
                    }
                }

                // Update info text
                const infoDiv = boardExamDateSelect.parentElement.querySelector('div');
                if (infoDiv) {
                    infoDiv.innerHTML = `
              <i class="fas fa-check-circle" style="color: #4663ac;"></i>
              ${availableDates.length} date(s) available for ${selectedOption.text}
            `;
                }
            } else {
                // No dates available for this exam type
                boardExamDateSelect.disabled = true;
                boardExamDateSelect.innerHTML = '<option value="">No dates available for this exam type</option>';

                // Update info text
                const infoDiv = boardExamDateSelect.parentElement.querySelector('div');
                if (infoDiv) {
                    infoDiv.innerHTML = `
              <i class="fas fa-exclamation-triangle" style="color: #f59e0b;"></i>
              No exam dates available for ${selectedOption.text}. Please contact admin to add dates.
            `;
                }
            }
        } else {
            // No exam type selected
            boardExamDateSelect.disabled = true;
            boardExamDateSelect.innerHTML = '<option value="">Select Board Exam Type First</option>';

            // Reset info text
            const infoDiv = boardExamDateSelect.parentElement.querySelector('div');
            if (infoDiv) {
                infoDiv.innerHTML = `
            <i class="fas fa-info-circle" style="color: #2c5aa0;"></i>
            Available dates will appear after selecting a board exam type
          `;
            }
        }
    }

    // Add cursor tracking to form header
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize exam dates if a board exam type was previously selected (postback)
        try {
            const typeSelect = document.getElementById('board_exam_type');
            const dateSelect = document.getElementById('board_exam_date');
            if (typeSelect) {
                updateAvailableDates();
                // Re-select previously chosen date if present from POST
                const prevDate =
                    '<?= isset($_POST['board_exam_date']) ? htmlspecialchars($_POST['board_exam_date']) : '' ?>';
                if (prevDate && dateSelect) {
                    const opt = Array.from(dateSelect.options).find(o => o.value === prevDate);
                    if (opt) {
                        dateSelect.value = prevDate;
                    }
                }
            }
        } catch (e) {
            /* no-op */
        }
        const formHeader = document.querySelector('.form-header');

        if (formHeader) {
            formHeader.addEventListener('mousemove', function(e) {
                const rect = this.getBoundingClientRect();
                const x = ((e.clientX - rect.left) / rect.width) * 100;
                const y = ((e.clientY - rect.top) / rect.height) * 100;

                this.style.setProperty('--x', x + '%');
                this.style.setProperty('--y', y + '%');
            });

            formHeader.addEventListener('mouseleave', function() {
                this.style.setProperty('--x', '50%');
                this.style.setProperty('--y', '50%');
            });
        }

        // Add interactive form enhancements
        const formGroups = document.querySelectorAll('.form-group');
        const inputs = document.querySelectorAll('.form-group input, .form-group select, .form-group textarea');

        // Add focus/blur effects for form groups
        inputs.forEach(input => {
            const formGroup = input.closest('.form-group');

            input.addEventListener('focus', function() {
                formGroup.classList.add('focused');
                this.parentElement.style.transform = 'translateY(-2px)';
            });

            input.addEventListener('blur', function() {
                formGroup.classList.remove('focused');
                this.parentElement.style.transform = 'translateY(0)';
            });

            // Add validation visual feedback
            input.addEventListener('input', function() {
                if (this.value.trim() !== '') {
                    this.style.borderColor = '#4663ac';
                    this.style.boxShadow = '0 0 0 4px rgba(70, 99, 172, 0.15)';
                } else if (this.hasAttribute('required')) {
                    this.style.borderColor = '#f59e0b';
                    this.style.boxShadow = '0 0 0 4px rgba(245, 158, 11, 0.15)';
                }
            });
        });

        // Add specific validation for board exam date (2019-2024 only)
        const boardExamDateSelect = document.getElementById('board_exam_date');
        const customDateGroup = document.getElementById('customDateGroup');
        const customDateInput = document.getElementById('custom_board_exam_date');

        if (boardExamDateSelect) {
            boardExamDateSelect.addEventListener('change', function() {
                if (this.value === 'other') {
                    // Show custom date input
                    customDateGroup.style.display = 'block';
                    customDateInput.setAttribute('required', 'required');
                    customDateInput.name = 'board_exam_date';
                    boardExamDateSelect.removeAttribute('required');
                    boardExamDateSelect.name = 'board_exam_date_select';
                } else {
                    // Hide custom date input
                    customDateGroup.style.display = 'none';
                    customDateInput.removeAttribute('required');
                    customDateInput.name = 'custom_board_exam_date';
                    boardExamDateSelect.setAttribute('required', 'required');
                    boardExamDateSelect.name = 'board_exam_date';
                }
            });

            // Check initial state
            if (boardExamDateSelect.value === 'other') {
                customDateGroup.style.display = 'block';
                customDateInput.setAttribute('required', 'required');
                customDateInput.name = 'board_exam_date';
                boardExamDateSelect.removeAttribute('required');
                boardExamDateSelect.name = 'board_exam_date_select';
            }
        }

        if (customDateInput) {
            customDateInput.addEventListener('input', function() {
                validateBoardExamDate(this);
            });

            customDateInput.addEventListener('change', function() {
                validateBoardExamDate(this);
            });
        }

        // Add hover effects to labels
        const labels = document.querySelectorAll('.form-group label');
        labels.forEach(label => {
            label.addEventListener('click', function() {
                const input = this.parentElement.querySelector('input, select, textarea');
                if (input) {
                    input.focus();
                }
            });
        });

        // Add tab switching animations
        const tabBtns = document.querySelectorAll('.tab-btn');
        tabBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                // Add ripple effect
                const ripple = document.createElement('span');
                ripple.className = 'ripple';
                ripple.style.cssText = `
            position: absolute;
            border-radius: 50%;
            background: rgba(44, 90, 160, 0.3);
            transform: scale(0);
            animation: ripple 0.6s ease-out;
            width: 20px;
            height: 20px;
            left: 50%;
            top: 50%;
            margin-left: -10px;
            margin-top: -10px;
          `;

                this.appendChild(ripple);

                setTimeout(() => {
                    ripple.remove();
                }, 600);
            });
        });

        // Add button click animations
        const buttons = document.querySelectorAll('.btn');
        buttons.forEach(btn => {
            btn.addEventListener('mousedown', function() {
                this.style.transform = 'translateY(-1px) scale(0.98)';
            });

            btn.addEventListener('mouseup', function() {
                this.style.transform = '';
            });

            btn.addEventListener('mouseleave', function() {
                this.style.transform = '';
            });
        });
    });

    // Add ripple animation keyframes
    const style = document.createElement('style');
    style.textContent = `
      @keyframes ripple {
        to {
          transform: scale(4);
          opacity: 0;
        }
      }
    `;
    document.head.appendChild(style);

    // Handle validation modal
    document.getElementById('validationOk').onclick = function() {
        document.getElementById('validationModal').style.display = 'none';
    };

    // Close validation modal when clicking outside
    document.getElementById('validationModal').onclick = function(e) {
        if (e.target === this) {
            this.style.display = 'none';
        }
    };

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
          background: linear-gradient(135deg, #4663ac 0%, #6B8DD6 100%);
          color: white;
          padding: 20px 32px;
          border-radius: 16px;
          box-shadow: 0 16px 40px rgba(70, 99, 172, 0.4);
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



    // Enhanced add new board examinee function with beautiful confirmation modal
    function addStudentDirect() {
        console.log('🚀 ADD NEW BOARD EXAMINEE: Starting validation and confirmation');

        // Quick validation of required fields
        const form = document.querySelector('form');
        const requiredFields = form.querySelectorAll('input[required], select[required]');
        let isValid = true;
        let missingFields = [];
        let firstEmptyField = null;

        for (let field of requiredFields) {
            let isEmpty = false;
            if (field.tagName === 'SELECT') {
                isEmpty = !field.value || field.value === '' || field.value.includes('Select');
            } else {
                isEmpty = !field.value || field.value.trim() === '';
            }

            if (isEmpty) {
                isValid = false;
                if (!firstEmptyField) firstEmptyField = field;
                const label = field.closest('.form-group')?.querySelector('label')?.textContent?.replace('*', '')
                    .trim() || field.name || field.id;
                const elementKey = field.name || field.id || '';
                missingFields.push({
                    name: label,
                    element: elementKey,
                    icon: getFieldIcon(elementKey)
                });
                field.style.borderColor = '#ef4444';
                field.style.boxShadow = '0 0 0 4px rgba(239, 68, 68, 0.15)';
            } else {
                field.style.borderColor = '#4663ac';
                field.style.boxShadow = '0 0 0 4px rgba(70, 99, 172, 0.15)';
            }
        }

        if (!isValid) {
            showValidationModal(missingFields, firstEmptyField);
            return;
        }

        // Validate board exam date
        const boardExamDateSelect = document.getElementById('board_exam_date');
        const customDateInput = document.getElementById('custom_board_exam_date');
        let boardExamDateValue = '';

        if (boardExamDateSelect && boardExamDateSelect.value) {
            if (boardExamDateSelect.value === 'other') {
                // Use custom date
                if (customDateInput && customDateInput.value) {
                    boardExamDateValue = customDateInput.value;
                } else {
                    showValidationModal([{
                        name: 'Board Exam Date',
                        element: 'board_exam_date',
                        icon: 'fas fa-calendar'
                    }], customDateInput);
                    return;
                }
            } else {
                // Use predefined date
                boardExamDateValue = boardExamDateSelect.value;
            }

            // Validate date range (2019-2024)
            const date = new Date(boardExamDateValue);
            const year = date.getFullYear();
            if (year < 2019 || year > 2024) {
                showValidationModal([{
                    name: 'Board Exam Date (2019-2024)',
                    element: 'board_exam_date',
                    icon: 'fas fa-calendar'
                }], boardExamDateSelect.value === 'other' ? customDateInput : boardExamDateSelect);
                return;
            }
        }

        // Show beautiful confirmation modal with receipt
        try {
            populateConfirmationModal();
            const confirmModal = document.getElementById('confirmationModal');
            if (confirmModal) {
                // Add robust display properties and entrance animation
                confirmModal.style.display = 'flex';
                confirmModal.style.opacity = '1';
                confirmModal.style.visibility = 'visible';
                confirmModal.classList.add('show');
                // lock page scroll
                try {
                    document.body.style.overflow = 'hidden';
                } catch (e) {}
                const modalContent = confirmModal.querySelector('.modal-content');
                if (modalContent) {
                    modalContent.classList.add('entering');
                    // ensure it's above other content
                    modalContent.style.zIndex = '10011';
                }

                console.log('✨ Beautiful confirmation modal displayed with animations');
            } else {
                // Fallback: show the existing confirmation modal if present
                const fallbackModal = document.getElementById('confirmationModal');
                if (fallbackModal) {
                    fallbackModal.style.display = 'flex';
                    fallbackModal.classList.add('show');
                    try {
                        document.body.style.overflow = 'hidden';
                    } catch (e) {}
                } else {
                    // last-resort programmatic submit
                    console.log('Fallback submitting form');
                    form.submit();
                }
            }
        } catch (error) {
            console.error('Error showing confirmation modal:', error);
            // Fallback: show the existing confirmation modal if present
            const fallbackModal2 = document.getElementById('confirmationModal');
            if (fallbackModal2) {
                fallbackModal2.style.display = 'flex';
                fallbackModal2.classList.add('show');
                try {
                    document.body.style.overflow = 'hidden';
                } catch (e) {}
            } else {
                console.log('Fallback submitting form');
                form.submit();
            }
        }
    }

    function toggleExamineeFields() {
        const examineeType = document.querySelector('input[name="examinee_type_radio"]:checked').value;
        const personalTab = document.getElementById('personalTab');
        const personalTabBtn = document.getElementById('personalTabBtn');
        const tabNavigation = document.getElementById('tabNavigation');
        const examTab = document.getElementById('examTab');
        const hiddenInput = document.getElementById('examineeTypeHidden');
        const prevTabBtn = document.getElementById('prevTabBtn');
        const submitBtnText = document.getElementById('submitBtnText');
        
        if (hiddenInput) {
            hiddenInput.value = examineeType;
        }
        
        document.querySelectorAll('.examinee-type-label').forEach(label => {
            const type = label.getAttribute('data-type');
            if (type === examineeType) {
                label.style.borderColor = '#4663AC';
                label.style.background = 'linear-gradient(145deg, #E3ECF5 0%, #ffffff 100%)';
                label.style.boxShadow = '0 4px 15px rgba(70, 99, 172, 0.2)';
            } else {
                label.style.borderColor = '#c1d8f0';
                label.style.background = 'white';
                label.style.boxShadow = 'none';
            }
        });
        
        if (examineeType === 'anonymous') {
            if (personalTab) personalTab.style.display = 'none';
            if (personalTabBtn) personalTabBtn.style.display = 'none';
            if (prevTabBtn) prevTabBtn.style.display = 'none';
            if (submitBtnText) submitBtnText.textContent = 'Add Exam Data';
            
            if (examTab) {
                examTab.style.display = 'block';
                examTab.classList.add('active');
            }
            
            document.querySelectorAll('.named-only-field').forEach(field => {
                field.style.display = 'none';
            });
            
            const personalFields = personalTab ? personalTab.querySelectorAll('input[required], select[required]') : [];
            personalFields.forEach(field => {
                field.removeAttribute('required');
            });
            
            const yearField = document.getElementById('year_graduated');
            const ratingField = document.getElementById('rating');
            
            if (yearField) yearField.removeAttribute('required');
            if (ratingField) ratingField.removeAttribute('required');
        } else {
            if (personalTab) personalTab.style.display = 'block';
            if (personalTabBtn) personalTabBtn.style.display = 'inline-flex';
            if (prevTabBtn) prevTabBtn.style.display = 'inline-flex';
            if (submitBtnText) submitBtnText.textContent = 'Add New Board Examinee';
            
            document.querySelectorAll('.named-only-field').forEach(field => {
                field.style.display = 'block';
            });
            
            const personalFields = personalTab ? personalTab.querySelectorAll('input, select') : [];
            personalFields.forEach(field => {
                const fieldName = field.getAttribute('name');
                if (fieldName && !['middle_name', 'suffix', 'rating'].includes(fieldName)) {
                    field.setAttribute('required', 'required');
                }
            });
            
            const yearField = document.getElementById('year_graduated');
            if (yearField) yearField.setAttribute('required', 'required');
        }
    }

    function populateConfirmationModal() {
        const examineeType = document.getElementById('examineeTypeHidden').value;
        
        const boardExamDateSelect = document.getElementById('board_exam_date');
        const customDateInput = document.getElementById('custom_board_exam_date');
        let boardExamDate = '';

        if (boardExamDateSelect && boardExamDateSelect.value) {
            if (boardExamDateSelect.value === 'other') {
                boardExamDate = customDateInput ? customDateInput.value : '';
            } else {
                boardExamDate = boardExamDateSelect.value;
            }
        }

        const result = document.getElementById('result').value;
        const examType = document.getElementById('exam_type').value;
        const boardExamType = document.getElementById('board_exam_type').value;

        const examDate = new Date(boardExamDate);
        const formattedExamDate = examDate.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });

        const currentDate = new Date().toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });

        document.getElementById('receiptDate').textContent = currentDate;
        
        if (examineeType === 'anonymous') {
            const course = document.getElementById('course_exam').value;
            
            document.getElementById('confirmName').textContent = 'Data Entry';
            document.getElementById('confirmSex').textContent = 'N/A';
            document.getElementById('confirmCourse').textContent = course;
            document.getElementById('confirmYear').textContent = 'N/A';
            
            const ratingRow = document.getElementById('ratingRow');
            if (ratingRow) ratingRow.style.display = 'none';
        } else {
            const firstName = document.getElementById('first_name').value.trim();
            const lastName = document.getElementById('last_name').value.trim();
            const middleName = document.getElementById('middle_name').value.trim();
            const suffix = document.getElementById('suffix').value.trim();
            const sex = document.getElementById('sex').value;
            const course = document.getElementById('course_exam').value;
            const yearGraduated = document.getElementById('year_graduated').value;
            const rating = document.getElementById('rating').value;

            let fullName = lastName + ', ' + firstName;
            if (middleName) {
                fullName += ' ' + middleName;
            }
            if (suffix) {
                fullName += ' ' + suffix;
            }

            document.getElementById('confirmName').textContent = fullName;
            document.getElementById('confirmSex').textContent = sex;
            document.getElementById('confirmCourse').textContent = course;
            document.getElementById('confirmYear').textContent = yearGraduated;
            
            const ratingRow = document.getElementById('ratingRow');
            if (rating && rating.trim() !== '') {
                document.getElementById('confirmRating').textContent = rating + '%';
                ratingRow.style.display = 'flex';
            } else {
                ratingRow.style.display = 'none';
            }
        }
        
        document.getElementById('confirmExamDate').textContent = formattedExamDate;
        document.getElementById('confirmResult').textContent = result;
        document.getElementById('confirmExamType').textContent = examType;
        document.getElementById('confirmBoardExamType').textContent = boardExamType;

        // Style result badge
        const resultElement = document.getElementById('confirmResult');
        resultElement.className = 'value result-badge';
        if (result === 'Passed') {
            resultElement.classList.add('result-passed');
        } else if (result === 'Failed') {
            resultElement.classList.add('result-failed');
        } else if (result === 'Conditional') {
            resultElement.classList.add('result-conditional');
        }
    }

    // Handle confirmation modal buttons - SIMPLIFIED
    document.getElementById('confirmAddStudent').onclick = function() {
        console.log('✅ Admin confirmed - submitting form');
        // restore scrolling
        try {
            document.body.style.overflow = '';
        } catch (e) {}
        document.querySelector('form').submit();
    };

    document.getElementById('confirmCancel').onclick = function() {
        // Close confirmation modal with exit animation
        const confirmModal = document.getElementById('confirmationModal');
        const modalContent = confirmModal.querySelector('.modal-content');

        // Add exit animation
        if (modalContent) modalContent.style.animation = 'confirmationSlideOut 0.3s ease-in-out';
        setTimeout(() => {
            confirmModal.style.display = 'none';
            confirmModal.style.opacity = '0';
            confirmModal.style.visibility = 'hidden';
            confirmModal.classList.remove('show');
            if (modalContent) {
                modalContent.classList.remove('entering');
                modalContent.style.animation = '';
            }
            try {
                document.body.style.overflow = '';
            } catch (e) {}
        }, 300);
    };

    // Close confirmation modal when clicking outside with animation
    document.getElementById('confirmationModal').onclick = function(e) {
        if (e.target === this) {
            const modalContent = this.querySelector('.modal-content');

            // Add exit animation
            if (modalContent) modalContent.style.animation = 'confirmationSlideOut 0.3s ease-in-out';
            setTimeout(() => {
                this.style.display = 'none';
                this.style.opacity = '0';
                this.style.visibility = 'hidden';
                this.classList.remove('show');
                if (modalContent) {
                    modalContent.classList.remove('entering');
                    modalContent.style.animation = '';
                }
                try {
                    document.body.style.overflow = '';
                } catch (e) {}
            }, 300);
        }
    };

    // Tab navigation functions
    function switchTab(tabName) {
        // Hide all tabs first
        document.querySelectorAll('.tab-content').forEach(tab => {
            tab.style.display = 'none';
            tab.classList.remove('active');
        });

        // Remove active class from all buttons
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('active');
        });

        // Show the selected tab with animation
        const targetTab = document.getElementById(tabName + 'Tab');
        const targetBtn = document.querySelector(`[data-tab="${tabName}"]`);

        if (targetTab && targetBtn) {
            // Add active class to button
            targetBtn.classList.add('active');

            // Show tab with smooth transition
            targetTab.style.display = 'block';
            setTimeout(() => {
                targetTab.classList.add('active');
            }, 10);
        }
    }

    function nextTab() {
        // Validate current tab before proceeding
        const personalTab = document.getElementById('personalTab');
        if (personalTab.classList.contains('active')) {
            // Validate required fields in personal tab
            const requiredFields = personalTab.querySelectorAll('input[required], select[required]');
            let isValid = true;
            let firstEmptyField = null;
            let missingFields = [];

            for (let field of requiredFields) {
                if (!field.value.trim()) {
                    if (!firstEmptyField) {
                        firstEmptyField = field;
                    }
                    field.style.borderColor = '#ef4444';
                    isValid = false;

                    // Get field label text
                    const label = field.closest('.form-group').querySelector('label');
                    const labelText = label ? label.textContent.replace('*', '').trim() : field.name;
                    missingFields.push({
                        name: labelText,
                        icon: getFieldIcon(field.name)
                    });
                } else {
                    field.style.borderColor = '#4663ac';
                }
            }

            if (!isValid) {
                // Show beautiful validation modal instead of alert
                showValidationModal(missingFields, firstEmptyField);
                return;
            }

            // All fields valid, switch to exam tab
            switchTab('exam');
            // Focus on first field in exam tab
            setTimeout(() => {
                const yearField = document.getElementById('year_graduated');
                if (yearField) {
                    yearField.focus();
                }
            }, 300);
        }
    }

    function getFieldIcon(fieldName) {
        const iconMap = {
            'first_name': 'fas fa-user',
            'last_name': 'fas fa-user',
            'middle_name': 'fas fa-user',
            'suffix': 'fas fa-award',
            'sex': 'fas fa-venus-mars',
            'course': 'fas fa-graduation-cap',
            'year_graduated': 'fas fa-calendar',
            'board_exam_date': 'fas fa-calendar-check',
            'result': 'fas fa-award',
            'exam_type': 'fas fa-redo',
            'board_exam_type': 'fas fa-certificate'
        };
        return iconMap[fieldName] || 'fas fa-exclamation-circle';
    }

    function showValidationModal(missingFields, firstEmptyField) {
        console.log('🚨 VALIDATION MODAL: Showing modal for', missingFields.length, 'missing fields');

        try {
            const missingFieldsList = document.getElementById('missingFieldsList');
            if (!missingFieldsList) {
                console.error('❌ Missing fields list element not found!');
                // fallback: log and focus
                console.log('Please fill in all required fields:', missingFields.map(f => f.name).join(', '));
                if (firstEmptyField) firstEmptyField.focus();
                return;
            }

            missingFieldsList.innerHTML = '';

            // Group fields by tab for better UX
            const personalTabFields = ['first_name', 'last_name', 'sex', 'course'];
            const examTabFields = ['year_graduated', 'board_exam_date', 'result', 'exam_type', 'board_exam_type'];

            let hasPersonalTabMissing = false;
            let hasExamTabMissing = false;

            missingFields.forEach(field => {
                console.log('➕ Adding missing field to modal:', field.name, '(', field.element, ')');

                if (personalTabFields.includes(field.element)) {
                    hasPersonalTabMissing = true;
                }
                if (examTabFields.includes(field.element)) {
                    hasExamTabMissing = true;
                }

                const fieldItem = document.createElement('div');
                fieldItem.className = 'field-item';
                fieldItem.innerHTML = `
            <i class="${field.icon}"></i>
            <span>${field.name}</span>
            <small style="opacity: 0.7; font-size: 0.8em;">${personalTabFields.includes(field.element) ? '(Personal Info tab)' : '(Exam Info tab)'}</small>
          `;
                missingFieldsList.appendChild(fieldItem);
            });

            // Add helpful instruction about which tab to check
            let instruction = '';
            if (hasPersonalTabMissing && hasExamTabMissing) {
                instruction = 'Please check both Personal Info and Exam Info tabs to fill in the missing fields.';
            } else if (hasPersonalTabMissing) {
                instruction = 'Please go to the Personal Info tab to fill in the missing fields.';
            } else if (hasExamTabMissing) {
                instruction = 'Please stay on the Exam Info tab to fill in the missing fields.';
            }

            // Update modal subtitle with helpful instruction
            const modalSubtitle = document.querySelector('#validationModal .modal-subtitle');
            if (modalSubtitle && instruction) {
                modalSubtitle.textContent = instruction;
            }

            const validationModal = document.getElementById('validationModal');
            if (validationModal) {
                // ensure visible by adding 'show' class and inline overrides
                validationModal.style.display = 'flex';
                validationModal.style.opacity = '1';
                validationModal.style.visibility = 'visible';
                validationModal.classList.add('show');
                try {
                    document.body.style.overflow = 'hidden';
                } catch (e) {}
                console.log('✅ Validation modal displayed with', missingFields.length, 'missing fields');

                const validationOkBtn = document.getElementById('validationOk');
                if (validationOkBtn) {
                    validationOkBtn.onclick = function() {
                        console.log('👆 Validation OK clicked - closing modal');
                        // hide with animation
                        validationModal.classList.remove('show');
                        validationModal.style.opacity = '0';
                        validationModal.style.visibility = 'hidden';
                        validationModal.style.display = 'none';
                        try {
                            document.body.style.overflow = '';
                        } catch (e) {}

                        // Smart navigation: go to the appropriate tab
                        if (hasPersonalTabMissing) {
                            console.log('🔄 Switching to Personal Info tab (has missing fields)');
                            switchTab('personal');
                        }

                        if (firstEmptyField) {
                            setTimeout(() => {
                                console.log('🎯 Focusing on first empty field:', firstEmptyField.id);
                                firstEmptyField.focus();
                                firstEmptyField.scrollIntoView({
                                    behavior: 'smooth',
                                    block: 'center'
                                });
                            }, 300);
                        }
                    };
                }
            } else {
                console.error('❌ Validation modal not found!');
                console.log('Please fill in all required fields:', missingFields.map(f => f.name).join(', '));
                if (firstEmptyField) firstEmptyField.focus();
            }
        } catch (error) {
            console.error('❌ Error in showValidationModal:', error);
            console.log('Please fill in all required fields:', missingFields.map(f => f.name).join(', '));
            if (firstEmptyField) firstEmptyField.focus();
        }
    }

    function prevTab() {
        switchTab('personal');
        // Focus on first field in personal tab
        setTimeout(() => {
            const firstNameField = document.getElementById('first_name');
            if (firstNameField) {
                firstNameField.focus();
            }
        }, 300);
    }

    // Initialize page
    document.addEventListener('DOMContentLoaded', function() {
        // Ensure only the first tab is visible on load
        switchTab('personal');

        // Auto-focus first field after a brief delay
        setTimeout(() => {
            const firstNameField = document.getElementById('first_name');
            if (firstNameField) {
                firstNameField.focus();
            }
        }, 100);

        // Add input validation feedback
        const allInputs = document.querySelectorAll('input[required], select[required], textarea[required]');
        allInputs.forEach(input => {
            input.addEventListener('blur', function() {
                if (this.value.trim()) {
                    this.style.borderColor = '#2c5aa0';
                } else {
                    this.style.borderColor = '#ef4444';
                }
            });

            input.addEventListener('input', function() {
                if (this.value.trim()) {
                    this.style.borderColor = '#2c5aa0';
                }
            });
        });

        // Ensure Add New Board Examinee button has event listener
        const addBtn = document.getElementById('addStudentBtn');
        if (addBtn && !addBtn.dataset.listenerAttached) {
            addBtn.addEventListener('click', addStudentDirect);
            addBtn.dataset.listenerAttached = '1';
        }
    });

    // Validate board exam date (2019-2024 only)
    function validateBoardExamDate(input) {
        const date = new Date(input.value);
        const year = date.getFullYear();
        const today = new Date();

        // Remove existing error messages
        const existingError = input.parentElement.querySelector('.date-error-message');
        if (existingError) {
            existingError.remove();
        }

        // Reset input styling
        input.style.borderColor = '';
        input.style.boxShadow = '';

        if (input.value) {
            if (year < 2019 || year > 2024) {
                showDateError(input, '⚠️ Board exam date must be between 2019-2024');
                input.style.borderColor = '#ef4444';
                input.style.boxShadow = '0 0 0 4px rgba(239, 68, 68, 0.15)';
                return false;
            } else if (date > today) {
                showDateError(input, '⚠️ Board exam date cannot be in the future');
                input.style.borderColor = '#ef4444';
                input.style.boxShadow = '0 0 0 4px rgba(239, 68, 68, 0.15)';
                return false;
            } else {
                // Valid date
                input.style.borderColor = '#4663ac';
                input.style.boxShadow = '0 0 0 4px rgba(70, 99, 172, 0.15)';
                return true;
            }
        }
        return true;
    }

    function showDateError(input, message) {
        const errorDiv = document.createElement('div');
        errorDiv.className = 'date-error-message';
        errorDiv.style.cssText = `
        color: #ef4444;
        font-size: 0.85rem;
        margin-top: 8px;
        font-weight: 600;
        animation: slideDown 0.3s ease;
        display: flex;
        align-items: center;
        gap: 6px;
      `;
        errorDiv.innerHTML = `<i class="fas fa-exclamation-triangle"></i>${message}`;

        input.parentElement.appendChild(errorDiv);
    }

    function showValidationError(message) {
        // Create and show a beautiful error modal
        const errorModal = document.createElement('div');
        errorModal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        background: rgba(15, 23, 42, 0.8);
        backdrop-filter: blur(8px);
        z-index: 9999;
        display: flex;
        justify-content: center;
        align-items: center;
        animation: fadeIn 0.3s ease;
      `;

        errorModal.innerHTML = `
        <div style="
          background: rgba(255, 255, 255, 0.98);
          backdrop-filter: blur(20px);
          padding: 40px 36px;
          border-radius: 24px;
          box-shadow: 0 25px 50px rgba(239, 68, 68, 0.25);
          max-width: 420px;
          width: 90%;
          text-align: center;
          animation: slideInError 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
          border: 2px solid rgba(239, 68, 68, 0.2);
        ">
          <div style="
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: white;
            font-size: 2rem;
            box-shadow: 0 15px 35px rgba(239, 68, 68, 0.3);
            animation: errorPulse 2s ease-in-out infinite;
          ">
            <i class="fas fa-exclamation-triangle"></i>
          </div>
          <h3 style="
            font-size: 1.5rem;
            font-weight: 700;
            color: #374151;
            margin: 0 0 8px 0;
            letter-spacing: 0.5px;
          ">Invalid Date Range</h3>
          <p style="
            font-size: 1rem;
            color: #6b7280;
            margin: 0 0 32px 0;
            line-height: 1.5;
          ">${message}</p>
          <button onclick="this.closest('div').remove()" style="
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 14px 28px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3);
          " onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 25px rgba(239, 68, 68, 0.4)'" 
             onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 15px rgba(239, 68, 68, 0.3)'">
            <i class="fas fa-check"></i> I Understand
          </button>
        </div>
        <style>
          @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
          @keyframes slideInError { 
            from { opacity: 0; transform: translateY(-30px) scale(0.95); } 
            to { opacity: 1; transform: translateY(0) scale(1); } 
          }
          @keyframes errorPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
          }
        </style>
      `;

        document.body.appendChild(errorModal);

        // Remove modal when clicking outside
        errorModal.addEventListener('click', function(e) {
            if (e.target === this) {
                this.remove();
            }
        });
    }

    // Immediately clamp subject grade inputs to their max (total_items)
    document.addEventListener('DOMContentLoaded', function() {
        try {
            const gradeInputs = Array.from(document.querySelectorAll('input[id^="subject_grade_"]'));
            gradeInputs.forEach(input => {
                // read max either from attribute or fallback to 100
                const maxAttr = input.getAttribute('max');
                const max = maxAttr ? parseInt(maxAttr, 10) : 100;

                // clamp while typing (immediate feedback)
                input.addEventListener('input', function(e) {
                    if (this.value === '') return;
                    // allow only integer part
                    const parsed = parseInt(this.value.replace(/[^0-9-]/g, ''), 10);
                    if (isNaN(parsed)) {
                        this.value = '';
                        return;
                    }
                    if (parsed > max) {
                        this.value = String(max);
                    } else if (parsed < 0) {
                        this.value = '0';
                    } else {
                        // keep the parsed integer (prevents decimals)
                        this.value = String(parsed);
                    }
                });

                // also ensure clamp on change (paste/blur)
                input.addEventListener('change', function() {
                    if (this.value === '') return;
                    const parsed = parseInt(this.value.replace(/[^0-9-]/g, ''), 10);
                    if (isNaN(parsed)) {
                        this.value = '';
                        return;
                    }
                    if (parsed > max) this.value = String(max);
                    else if (parsed < 0) this.value = '0';
                    else this.value = String(parsed);
                    // After clamping, compute pass/fail based on >=75%
                    try {
                        const sid = this.id.replace('subject_grade_', '');
                        const resultHidden = document.getElementById('subject_result_' + sid);
                        const resultSpan = document.getElementById('subject_result_display_' +
                            sid);
                        if (resultHidden && resultSpan) {
                            const g = parseInt(this.value, 10);
                            if (!isNaN(g)) {
                                const pct = (g / max) * 100;
                                const res = (pct >= 75) ? 'Passed' : 'Failed';
                                resultHidden.value = res;
                                resultSpan.textContent = res;
                                // color-coding
                                resultSpan.classList.remove('remark-pass', 'remark-fail');
                                if (res === 'Passed') resultSpan.classList.add('remark-pass');
                                else if (res === 'Failed') resultSpan.classList.add(
                                    'remark-fail');
                            }
                        }
                    } catch (e) {
                        console.warn('Failed to auto-set subject result', e);
                    }
                });
            });
        } catch (err) {
            console.error('Error attaching grade clamping handlers', err);
        }
    });
    </script>
</body>

</html>