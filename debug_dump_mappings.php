<?php
// Debug helper: dump subjects and their mapped exam types per department
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "project_db";
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    echo "DB connect error: " . $conn->connect_error . "\n";
    exit(1);
}

$departments = [
    'Engineering',
    'Arts and Science',
    'Business Administration and Accountancy',
    'Criminal Justice Education',
    'Teacher Education'
];

foreach ($departments as $dept) {
    echo "\n=== Subjects for department: $dept ===\n";

    $sql = "SELECT s.id as subject_id, s.subject_name, s.total_items, m.exam_type_id, bet.exam_type_name
            FROM subjects s
            LEFT JOIN subject_exam_types m ON m.subject_id = s.id
            LEFT JOIN board_exam_types bet ON bet.id = m.exam_type_id
            WHERE s.department='$dept'
            ORDER BY s.subject_name ASC";

    $res = $conn->query($sql);
    if (!$res) {
        echo "Query failed for $dept: " . $conn->error . "\n";
        continue;
    }

    $out = [];
    while ($r = $res->fetch_assoc()) {
        $out[] = $r;
    }

    echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    echo str_repeat("-", 50) . "\n";
}

$conn->close();
?>