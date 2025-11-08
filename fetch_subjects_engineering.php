<?php
// Returns JSON array of subjects filtered by exam_type_id for Engineering department
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

$exam_type_id = intval($_GET['exam_type_id'] ?? 0);
if ($exam_type_id <= 0) {
  echo json_encode([]);
  exit;
}

$sql = "SELECT s.id, TRIM(s.subject_name) as subject_name, COALESCE(s.total_items,50) as total_items
  FROM subjects s
  LEFT JOIN subject_exam_types m ON m.subject_id = s.id
  WHERE s.department='Engineering' AND TRIM(s.subject_name) != '' AND (m.exam_type_id IS NULL OR m.exam_type_id = " . intval($exam_type_id) . ")
  GROUP BY s.id
  ORDER BY subject_name ASC";

$res = $conn->query($sql);
$out = [];
if ($res) {
  while ($r = $res->fetch_assoc()) {
    $out[] = $r;
  }
}

echo json_encode($out);
$conn->close();
