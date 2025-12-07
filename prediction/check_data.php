<?php
require '../db_config.php';

$conn = getDbConnection();

// Check total records
$result = $conn->query("SELECT COUNT(*) as total FROM anonymous_board_passers WHERE department='Engineering'");
$data = $result->fetch_assoc();
echo "Total Engineering records: " . $data['total'] . "\n\n";

// Check by exam type
$result2 = $conn->query("SELECT board_exam_type, COUNT(*) as count FROM anonymous_board_passers WHERE department='Engineering' GROUP BY board_exam_type ORDER BY count DESC");
echo "Records by Exam Type:\n";
while($row = $result2->fetch_assoc()) {
    echo "  " . $row['board_exam_type'] . ": " . $row['count'] . "\n";
}

// Check year range
$result3 = $conn->query("SELECT MIN(exam_year) as min_year, MAX(exam_year) as max_year FROM anonymous_board_passers WHERE department='Engineering'");
$years = $result3->fetch_assoc();
echo "\nYear Range: " . $years['min_year'] . " to " . $years['max_year'] . "\n";

$conn->close();
?>
