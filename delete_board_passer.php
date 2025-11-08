<?php
session_start();

// Only allow College of Engineering admin
if (!isset($_SESSION["users"]) || $_SESSION["users"] !== 'eng_admin@lspu.edu.ph') {
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

// First, get the student name for confirmation
$find_stmt = $conn->prepare("SELECT id, CONCAT(first_name, ' ', IFNULL(middle_name, ''), ' ', last_name) as full_name FROM board_passers WHERE id = ? AND department = 'Engineering'");
$find_stmt->bind_param("i", $student_id);
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
$find_stmt->close();

// Delete the record
$delete_stmt = $conn->prepare("DELETE FROM board_passers WHERE id = ? AND department = 'Engineering'");
$delete_stmt->bind_param("i", $student_id);

if ($delete_stmt->execute()) {
    if ($delete_stmt->affected_rows > 0) {
        $delete_stmt->close();
        $conn->close();
        echo json_encode([
            'success' => true, 
            'message' => 'Record deleted successfully',
            'deleted_name' => $student_name
        ]);
    } else {
        $delete_stmt->close();
        $conn->close();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Record not found or already deleted']);
    }
} else {
    $delete_stmt->close();
    $conn->close();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to delete record: ' . $conn->error]);
}
?>
