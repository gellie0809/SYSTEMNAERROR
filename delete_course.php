<?php
// delete_course.php

session_start();
require_once __DIR__ . '/db_config.php';

// Map admin emails to departments
$department_admins = [
    'Engineering' => 'eng_admin@lspu.edu.ph',
    'Arts and Science' => 'cas_admin@lspu.edu.ph',
    'Business Administration and Accountancy' => 'cbaa_admin@lspu.edu.ph',
    'Criminal Justice Education' => 'ccje_admin@lspu.edu.ph',
    'Teacher Education' => 'cte_admin@lspu.edu.ph'
];

// Check session user
$user_email = $_SESSION['users'] ?? '';
$department = array_search($user_email, $department_admins, true);

if (!$department) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Only POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Check course ID
if (!isset($_POST['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No course ID provided.']);
    exit();
}

$course_id = intval($_POST['id']);

// Database connection
$conn = new mysqli('localhost', 'root', '', 'project_db');
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit();
}

// Delete the course, restricting to the user's department
$stmt = $conn->prepare('DELETE FROM courses WHERE id = ? AND department = ?');
$stmt->bind_param('is', $course_id, $department);

$success = $stmt->execute();

if ($success && $stmt->affected_rows > 0) {
    echo json_encode([
        'success' => true,
        'message' => "Course deleted successfully in $department department."
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Course not found or already deleted.'
    ]);
}

$stmt->close();
$conn->close();
?>