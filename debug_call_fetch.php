<?php
// Returns JSON array of subjects filtered by exam_type_id and department
header('Content-Type: application/json; charset=utf-8');

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "project_db";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'DB connection failed']);
    exit;
}

// Get department and exam_type_id from GET
$department = trim($_GET['department'] ?? '');
$exam_type_id = intval($_GET['exam_type_id'] ?? 0);

if ($department === '' || $exam_type_id <= 0) {
    echo json_encode([]);
    exit;
}

// Prevent SQL injection by allowing only known departments
$allowedDepartments = [
    'Engineering',
    'Arts and Science',
    'Business Administration and Accountancy',
    'Criminal Justice Education',
    'Teacher Education'
];

if (!in_array($department, $allowedDepartments)) {
    echo json_encode(['error' => 'Invalid department']);
    exit;
}

// Query subjects
$sql = "SELECT s.id, TRIM(s.subject_name) AS subject_name, COALESCE(s.total_items,50) AS total_items
        FROM subjects s
        LEFT JOIN subject_exam_types m ON m.subject_id = s.id
        WHERE s.department = ? 
          AND TRIM(s.subject_name) != '' 
          AND (m.exam_type_id IS NULL OR m.exam_type_id = ?)
        GROUP BY s.id
        ORDER BY subject_name ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $department, $exam_type_id);
$stmt->execute();
$res = $stmt->get_result();

$out = [];
while ($r = $res->fetch_assoc()) {
    $out[] = $r;
}

echo json_encode($out);
$stmt->close();
$conn->close();