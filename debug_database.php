<?php
$conn = new mysqli('localhost', 'root', '', 'project_db');
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

echo "Current records in board_passers table (Engineering department):\n";
echo "==============================================================\n";

$result = $conn->query("SELECT id, name, course, board_exam_date FROM board_passers WHERE department = 'Engineering' ORDER BY name");

if ($result && $result->num_rows > 0) {
    echo "Total records found: " . $result->num_rows . "\n\n";
    echo "ID | Name | Course | Board Exam Date\n";
    echo "---|------|--------|----------------\n";
    
    while ($row = $result->fetch_assoc()) {
        echo $row['id'] . " | " . $row['name'] . " | " . $row['course'] . " | " . $row['board_exam_date'] . "\n";
    }
} else {
    echo "No records found in Engineering department\n";
}

// Check for any records that might be causing duplicates
echo "\n\nChecking for potential duplicate patterns:\n";
echo "==========================================\n";

$duplicateCheck = $conn->query("
    SELECT name, course, board_exam_date, COUNT(*) as count 
    FROM board_passers 
    WHERE department = 'Engineering' 
    GROUP BY name, course, board_exam_date 
    HAVING COUNT(*) > 1
");

if ($duplicateCheck && $duplicateCheck->num_rows > 0) {
    echo "Found actual duplicates:\n";
    while ($row = $duplicateCheck->fetch_assoc()) {
        echo "- " . $row['name'] . " (" . $row['course'] . ") - " . $row['board_exam_date'] . " [" . $row['count'] . " times]\n";
    }
} else {
    echo "No actual duplicates found in database\n";
}

$conn->close();
?>
