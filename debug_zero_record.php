<?php
$conn = new mysqli('localhost', 'root', '', 'project_db');

echo "Record with '0' as result:\n";
echo "==========================\n";

$result = $conn->query("SELECT * FROM board_passers WHERE department='Engineering' AND result='0'");
while ($row = $result->fetch_assoc()) {
    echo "ID: " . $row['id'] . "\n";
    echo "Name: " . $row['name'] . "\n";
    echo "Result: '" . $row['result'] . "'\n";
    echo "Exam Type: " . $row['exam_type'] . "\n";
    echo "Course: " . $row['course'] . "\n";
    echo "Year Graduated: " . $row['year_graduated'] . "\n";
    echo "Board Exam Date: " . $row['board_exam_date'] . "\n";
    echo "Department: " . $row['department'] . "\n";
    echo "========================\n";
}

$conn->close();
?>
