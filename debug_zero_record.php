<?php
$conn = new mysqli('localhost', 'root', '', 'project_db');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$departments = [
    'Engineering',
    'Arts and Science',
    'Business Administration and Accountancy',
    'Criminal Justice Education',
    'Teacher Education'
];

foreach ($departments as $dept) {
    echo "\n=== Records with '0' as result in $dept department ===\n";
    echo str_repeat("=", 50) . "\n";

    $result = $conn->query("SELECT * FROM board_passers WHERE department='$dept' AND result='0'");
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "ID: " . $row['id'] . "\n";
            echo "Name: " . $row['name'] . "\n";
            echo "Result: '" . $row['result'] . "'\n";
            echo "Exam Type: " . $row['exam_type'] . "\n";
            echo "Course: " . $row['course'] . "\n";
            echo "Year Graduated: " . $row['year_graduated'] . "\n";
            echo "Board Exam Date: " . $row['board_exam_date'] . "\n";
            echo "Department: " . $row['department'] . "\n";
            echo str_repeat("-", 30) . "\n";
        }
    } else {
        echo "No records with result='0' found for $dept\n";
    }
}

$conn->close();
?>