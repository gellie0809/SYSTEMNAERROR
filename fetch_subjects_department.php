<?php
// Returns JSON array of subjects filtered by exam_type_id for a given department
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

// Accept department via GET, or default to Engineering
$allowed_departments = [
  'Engineering',
  'Arts and Science',
  'Business Administration and Accountancy',
  'Criminal Justice Education',
  'Teacher Education'
];

$dept = $_GET['department'] ?? 'Engineering';
if (!in_array($dept, $allowed_departments)) {
    echo json_encode(['error' => 'Invalid department']);
    exit;
}

$exam_type_id = intval($_GET['exam_type_id'] ?? 0);
if ($exam_type_id <= 0) {
  echo json_encode([]);
  exit;
}

$sql = "
SELECT s.id, TRIM(s.subject_name) AS subject_name, COALESCE(s.total_items,50) AS total_items
FROM subjects s
LEFT JOIN subject_exam_types m ON m.subject_id = s.id
WHERE s.department = ? 
  AND TRIM(s.subject_name) != '' 
  AND (m.exam_type_id IS NULL OR m.exam_type_id = ?)
GROUP BY s.id
ORDER BY subject_name ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $dept, $exam_type_id);
$stmt->execute();
$res = $stmt->get_result();

$out = [];
while ($r = $res->fetch_assoc()) {
  $out[] = $r;
}

echo json_encode($out);
$stmt->close();
$conn->close();