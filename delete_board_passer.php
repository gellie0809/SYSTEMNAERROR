<?php
session_start();
require_once __DIR__ . '/db_config.php';


// Define admin emails per department
$department_admins = [
    'Engineering' => 'eng_admin@lspu.edu.ph',
    'Arts and Sciences' => 'cas_admin@lspu.edu.ph',
    'Business Administration and Accountancy' => 'cbaa_admin@lspu.edu.ph',
    'Criminal Justice Education' => 'ccje_admin@lspu.edu.ph',
    'Teacher Education' => 'cte_admin@lspu.edu.ph',
    'ALL' => 'icts_admin@lspu.edu.ph'  // ICTS can delete from all departments
];

// Check user session and determine department
$user_email = $_SESSION["users"] ?? '';
$department = array_search($user_email, $department_admins, true);

// ICTS admin has special ALL access
$is_icts_admin = ($user_email === 'icts_admin@lspu.edu.ph');

if (!$department && !$is_icts_admin) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Database connection
$conn = new mysqli("localhost", "root", "", "project_db");
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

// Get the student ID from POST
$student_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
if ($student_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid student ID provided']);
    exit();
}

// Fetch student name for confirmation (ICTS can delete from any department)
if ($is_icts_admin) {
    $find_stmt = $conn->prepare("
        SELECT id, CONCAT(first_name, ' ', IFNULL(middle_name, ''), ' ', last_name) AS full_name, department
        FROM board_passers
        WHERE id = ?
    ");
    $find_stmt->bind_param("i", $student_id);
} else {
    $find_stmt = $conn->prepare("
        SELECT id, CONCAT(first_name, ' ', IFNULL(middle_name, ''), ' ', last_name) AS full_name, department
        FROM board_passers
        WHERE id = ? AND department = ?
    ");
    $find_stmt->bind_param("is", $student_id, $department);
}
$find_stmt->execute();
$result = $find_stmt->get_result();

if ($result->num_rows === 0) {
    $find_stmt->close();
    $conn->close();
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Record not found']);
    exit();
}

$record = $result->fetch_assoc();
$student_name = $record['full_name'];
$record_department = $record['department'];
$find_stmt->close();

// Soft delete the record (ICTS can delete from any department)
if ($is_icts_admin) {
    $delete_stmt = $conn->prepare("UPDATE board_passers SET is_deleted = 1, deleted_at = NOW() WHERE id = ?");
    $delete_stmt->bind_param("i", $student_id);
} else {
    $delete_stmt = $conn->prepare("UPDATE board_passers SET is_deleted = 1, deleted_at = NOW() WHERE id = ? AND department = ?");
    $delete_stmt->bind_param("is", $student_id, $department);
}

if ($delete_stmt->execute()) {
    if ($delete_stmt->affected_rows > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Record removed from view (data preserved in database)',
            'deleted_name' => $student_name,
            'department' => $is_icts_admin ? $record_department : $department
        ]);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Record not found or already deleted']);
    }
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to delete record: ' . $conn->error]);
}

$delete_stmt->close();
$conn->close();
?>