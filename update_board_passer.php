<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

error_reporting(E_ALL);
ini_set('display_errors', 0);

// Listahan ng lahat ng valid admin emails (5 colleges + ICTS)
$valid_admins = [
    'eng_admin@lspu.edu.ph',   // Engineering
    'cas_admin@lspu.edu.ph',   // Arts and Science
    'cbaa_admin@lspu.edu.ph',  // Business Administration
    'ccje_admin@lspu.edu.ph',  // Criminal Justice
    'cte_admin@lspu.edu.ph',   // Teacher Education
    'icts_admin@lspu.edu.ph'   // ICTS (can access all departments)
];

// PALITAN MO LANG ANG MGA VALUE DITO (base sa actual sa DB mo)
$admin_to_department = [
    'eng_admin@lspu.edu.ph'  => 'Engineering',
    'cas_admin@lspu.edu.ph'  => 'Arts and Sciences',
    'cbaa_admin@lspu.edu.ph' => 'Business Administration and Accountancy',
    'ccje_admin@lspu.edu.ph' => 'Criminal Justice Education',
    'cte_admin@lspu.edu.ph'  => 'Teacher Education',
    'icts_admin@lspu.edu.ph' => 'ALL'  // ICTS can access all departments
];

// AUTHENTICATION CHECK
if (!isset($_SESSION['users']) || !in_array($_SESSION['users'], $valid_admins)) {
    http_response_code(403);
    error_log("Unauthorized access attempt from: " . ($_SESSION['users'] ?? 'No session'));
    echo json_encode([
        'success' => false,
        'message' => 'Access denied. You are not authorized to edit records.'
    ]);
    exit();
}

$current_admin_email = $_SESSION['users'];
$current_department   = $admin_to_department[$current_admin_email];

// Database connection (consistent sa db_config.php)
require_once __DIR__ . '/db_config.php';

try {
    $conn = getDbConnection();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Get and sanitize inputs
$student_id = intval($_POST['student_id'] ?? 0);
if ($student_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid student ID']);
    exit();
}

$first_name      = trim($_POST['first_name'] ?? '');
$middle_name     = trim($_POST['middle_name'] ?? '');
$last_name       = trim($_POST['last_name'] ?? '');
$suffix          = trim($_POST['suffix'] ?? '');
$sex             = strtoupper(trim($_POST['sex'] ?? ''));
$course          = trim($_POST['course'] ?? '');
$year_graduated  = trim($_POST['year_graduated'] ?? '');
$result          = trim($_POST['result'] ?? '');
$exam_type       = trim($_POST['exam_type'] ?? '');
$rating          = trim($_POST['rating'] ?? '');
$board_exam_type = trim($_POST['board_exam_type'] ?? '');
$board_exam_date = $_POST['board_exam_date'] ?? null; // could be 'other' or actual date

// Custom date fallback
if ($board_exam_date === 'other') {
    $board_exam_date = trim($_POST['custom_exam_date'] ?? '');
}

// Validation
if (empty($first_name) || empty($last_name) || empty($course) || empty($year_graduated) || empty($sex)) {
    echo json_encode(['success' => false, 'message' => 'Required fields missing']);
    exit();
}

if (!preg_match('/^\d{4}$/', $year_graduated) || $year_graduated < 1900 || $year_graduated > date('Y')+5) {
    echo json_encode(['success' => false, 'message' => 'Invalid graduation year']);
    exit();
}

if ($board_exam_date && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $board_exam_date)) {
    echo json_encode(['success' => false, 'message' => 'Invalid exam date format']);
    exit();
}

$full_name = trim("$last_name, $first_name $middle_name $suffix");

try {
    $conn->autocommit(false);
    $conn->begin_transaction();

    // 1. Update main record (ICTS can edit all departments, others only their own)
    $update_sql = "UPDATE board_passers SET 
                    first_name = ?, middle_name = ?, last_name = ?, suffix = ?, 
                    sex = ?, course = ?, year_graduated = ?, result = ?, 
                    exam_type = ?, rating = ?, board_exam_type = ?";

    $params = [$first_name, $middle_name, $last_name, $suffix, $sex, $course, $year_graduated, $result, $exam_type, $rating, $board_exam_type];
    $types  = 'sssssssssss';

    if ($board_exam_date !== null) {
        $update_sql .= ", board_exam_date = ?";
        $params[] = $board_exam_date;
        $types .= 's';
    }

    // ICTS admin can edit all departments, others only their own
    if ($current_department !== 'ALL') {
        $update_sql .= " WHERE id = ? AND department = ? LIMIT 1";
        $params[] = $student_id;
        $params[] = $current_department;
        $types .= 'is';
    } else {
        $update_sql .= " WHERE id = ? LIMIT 1";
        $params[] = $student_id;
        $types .= 'i';
    }

    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param($types, ...$params);

    if (!$stmt->execute()) {
        throw new Exception("Failed to update main record: " . $stmt->error);
    }

    if ($stmt->affected_rows === 0) {
        throw new Exception("Record not found or you don't have permission to edit it.");
    }

    // 2. Handle subject grades (optional)
    $subject_updates = [];
    foreach ($_POST as $key => $value) {
        if (preg_match('/^edit_subject_grade_(\d+)$/', $key, $m)) {
            $subject_id = intval($m[1]);
            $grade = $value === '' ? null : intval($value);
            $result_key = "edit_subject_result_$subject_id";
            $result_val = $_POST[$result_key] ?? '';

            if ($grade !== null) {
                $subject_updates[] = [
                    'subject_id' => $subject_id,
                    'grade'      => $grade,
                    'result'     => $result_val
                ];
            }
        }
    }

    // Delete old subject records
    $del = $conn->prepare("DELETE FROM board_passer_subjects WHERE board_passer_id = ?");
    $del->bind_param('i', $student_id);
    $del->execute();
    $del->close();

    // Insert new ones
    if (!empty($subject_updates)) {
        $insert_subject = $conn->prepare("
            INSERT INTO board_passer_subjects 
            (board_passer_id, subject_id, grade, result) 
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE grade = VALUES(grade), result = VALUES(result)
        ");

        foreach ($subject_updates as $su) {
            $res = ($su['result'] === 'Passed') ? 'Passed' : 'Failed';
            $insert_subject->bind_param('iiis', $student_id, $su['subject_id'], $su['grade'], $res);
            $insert_subject->execute();
        }
        $insert_subject->close();
    }

    $conn->commit();
    $conn->autocommit(true);

    echo json_encode([
        'success' => true,
        'message' => 'Record updated successfully!',
        'updated_name' => $full_name,
        'department' => $current_department
    ]);

} catch (Exception $e) {
    $conn->rollback();
    error_log("Update failed for ID $student_id by {$current_admin_email}: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>