<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "project_db";
$conn = new mysqli($servername, $username, $password, $dbname);

echo "Recent board passers from database:\n";
$result = $conn->query("SELECT * FROM board_passers WHERE department='Engineering' ORDER BY id DESC LIMIT 5");
while ($row = $result->fetch_assoc()) {
    echo "ID: " . $row['id'] . "\n";
    echo "Name: " . $row['name'] . "\n";
    echo "Course: " . $row['course'] . "\n";
    echo "Sex: " . ($row['sex'] ?? 'NULL') . "\n";
    echo "Year: " . $row['year_graduated'] . "\n";
    echo "---\n";
}

echo "\nAvailable courses:\n";
$courses_result = $conn->query("SELECT course_name FROM courses WHERE department='Engineering'");
while ($course = $courses_result->fetch_assoc()) {
    echo "- " . $course['course_name'] . "\n";
}
?>
