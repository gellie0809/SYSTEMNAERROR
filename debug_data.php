<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "project_db";
$conn = new mysqli($servername, $username, $password, $dbname);

$departments = [
    'Engineering',
    'Arts and Science',
    'Business Administration and Accountancy',
    'Criminal Justice Education',
    'Teacher Education'
];

foreach ($departments as $dept) {
    echo "\n=== Recent board passers for department: $dept ===\n";
    
    $result = $conn->query("SELECT * FROM board_passers WHERE department='$dept' ORDER BY id DESC LIMIT 5");
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "ID: " . $row['id'] . "\n";
            echo "Name: " . $row['name'] . "\n";
            echo "Course: " . $row['course'] . "\n";
            echo "Sex: " . ($row['sex'] ?? 'NULL') . "\n";
            echo "Year: " . $row['year_graduated'] . "\n";
            echo "---\n";
        }
    } else {
        echo "No recent board passers found for $dept\n";
    }

    echo "\nAvailable courses for $dept:\n";
    $courses_result = $conn->query("SELECT course_name FROM courses WHERE department='$dept'");
    if ($courses_result && $courses_result->num_rows > 0) {
        while ($course = $courses_result->fetch_assoc()) {
            echo "- " . $course['course_name'] . "\n";
        }
    } else {
        echo "No courses found for $dept\n";
    }
    
    echo "\n--------------------------------------------\n";
}

$conn->close();
?>