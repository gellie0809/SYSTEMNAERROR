<?php
// Debug helper: dump subjects and their mapped exam types
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "project_db";
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
  echo "DB connect error: " . $conn->connect_error . "\n";
  exit(1);
}

$sql = "SELECT s.id as subject_id, s.subject_name, s.total_items, m.exam_type_id, bet.exam_type_name
        FROM subjects s
        LEFT JOIN subject_exam_types m ON m.subject_id = s.id
        LEFT JOIN board_exam_types bet ON bet.id = m.exam_type_id
        WHERE s.department='Engineering'
        ORDER BY s.subject_name ASC";

$res = $conn->query($sql);
if (!$res) {
  echo "Query failed: " . $conn->error . "\n";
  exit(1);
}
$out = [];
while ($r = $res->fetch_assoc()) {
  $out[] = $r;
}
echo json_encode($out, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) . "\n";
$conn->close();
?>