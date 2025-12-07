<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "project_db";
$conn = new mysqli($servername, $username, $password, $dbname);

/* ===============================
   ENGINEERING
   ===============================*/
echo "All board passers in database (Engineering):\n";
echo "================================\n";
$result = $conn->query("SELECT * FROM board_passers WHERE department='Engineering' ORDER BY id ASC");
while ($row = $result->fetch_assoc()) {
    echo "ID: " . $row['id'] . "\n";
    echo "Name: " . $row['name'] . "\n";
    echo "Course: '" . $row['course'] . "'\n";
    echo "Year: " . $row['year_graduated'] . "\n";
    echo "Date: " . $row['board_exam_date'] . "\n";
    echo "Result: " . $row['result'] . "\n";
    echo "Sex: " . ($row['sex'] ?? 'NULL') . "\n";
    echo "---\n";
}

echo "\nRecords with course = '0' (Engineering):\n";
$bad_records = $conn->query("SELECT id, name, course FROM board_passers WHERE course = '0' AND department='Engineering'");
$count = 0;
while ($row = $bad_records->fetch_assoc()) {
    echo "ID: " . $row['id'] . " - Name: " . $row['name'] . " - Course: " . $row['course'] . "\n";
    $count++;
}

echo "\nFound $count records with course = '0' in Engineering\n";

echo "\n------------------------------------------------------------------\n\n";


/* ===============================
   ARTS AND SCIENCE
   ===============================*/
echo "All board passers in database (Arts and Science):\n";
echo "================================\n";
$result = $conn->query("SELECT * FROM board_passers WHERE department='Arts and Science' ORDER BY id ASC");
while ($row = $result->fetch_assoc()) {
    echo "ID: " . $row['id'] . "\n";
    echo "Name: " . $row['name'] . "\n";
    echo "Course: '" . $row['course'] . "'\n";
    echo "Year: " . $row['year_graduated'] . "\n";
    echo "Date: " . $row['board_exam_date'] . "\n";
    echo "Result: " . $row['result'] . "\n";
    echo "Sex: " . ($row['sex'] ?? 'NULL') . "\n";
    echo "---\n";
}

echo "\nRecords with course = '0' (Arts and Science):\n";
$bad_records = $conn->query("SELECT id, name, course FROM board_passers WHERE course = '0' AND department='Arts and Science'");
$count = 0;
while ($row = $bad_records->fetch_assoc()) {
    echo "ID: " . $row['id'] . " - Name: " . $row['name'] . " - Course: " . $row['course'] . "\n";
    $count++;
}

echo "\nFound $count records with course = '0' in Arts and Science\n";

echo "\n------------------------------------------------------------------\n\n";


/* ===============================
   BUSINESS ADMINISTRATION AND ACCOUNTANCY
   ===============================*/
echo "All board passers in database (Business Administration and Accountancy):\n";
echo "================================\n";
$result = $conn->query("SELECT * FROM board_passers WHERE department='Business Administration and Accountancy' ORDER BY id ASC");
while ($row = $result->fetch_assoc()) {
    echo "ID: " . $row['id'] . "\n";
    echo "Name: " . $row['name'] . "\n";
    echo "Course: '" . $row['course'] . "'\n";
    echo "Year: " . $row['year_graduated'] . "\n";
    echo "Date: " . $row['board_exam_date'] . "\n";
    echo "Result: " . $row['result'] . "\n";
    echo "Sex: " . ($row['sex'] ?? 'NULL') . "\n";
    echo "---\n";
}

echo "\nRecords with course = '0' (Business Administration and Accountancy):\n";
$bad_records = $conn->query("SELECT id, name, course FROM board_passers WHERE course = '0' AND department='Business Administration and Accountancy'");
$count = 0;
while ($row = $bad_records->fetch_assoc()) {
    echo "ID: " . $row['id'] . " - Name: " . $row['name'] . " - Course: " . $row['course'] . "\n";
    $count++;
}

echo "\nFound $count records with course = '0' in Business Administration and Accountancy\n";

echo "\n------------------------------------------------------------------\n\n";


/* ===============================
   CRIMINAL JUSTICE EDUCATION
   ===============================*/
echo "All board passers in database (Criminal Justice Education):\n";
echo "================================\n";
$result = $conn->query("SELECT * FROM board_passers WHERE department='Criminal Justice Education' ORDER BY id ASC");
while ($row = $result->fetch_assoc()) {
    echo "ID: " . $row['id'] . "\n";
    echo "Name: " . $row['name'] . "\n";
    echo "Course: '" . $row['course'] . "'\n";
    echo "Year: " . $row['year_graduated'] . "\n";
    echo "Date: " . $row['board_exam_date'] . "\n";
    echo "Result: " . $row['result'] . "\n";
    echo "Sex: " . ($row['sex'] ?? 'NULL') . "\n";
    echo "---\n";
}

echo "\nRecords with course = '0' (Criminal Justice Education):\n";
$bad_records = $conn->query("SELECT id, name, course FROM board_passers WHERE course = '0' AND department='Criminal Justice Education'");
$count = 0;
while ($row = $bad_records->fetch_assoc()) {
    echo "ID: " . $row['id'] . " - Name: " . $row['name'] . " - Course: " . $row['course'] . "\n";
    $count++;
}

echo "\nFound $count records with course = '0' in Criminal Justice Education\n";

echo "\n------------------------------------------------------------------\n\n";


/* ===============================
   TEACHER EDUCATION
   ===============================*/
echo "All board passers in database (Teacher Education):\n";
echo "================================\n";
$result = $conn->query("SELECT * FROM board_passers WHERE department='Teacher Education' ORDER BY id ASC");
while ($row = $result->fetch_assoc()) {
    echo "ID: " . $row['id'] . "\n";
    echo "Name: " . $row['name'] . "\n";
    echo "Course: '" . $row['course'] . "'\n";
    echo "Year: " . $row['year_graduated'] . "\n";
    echo "Date: " . $row['board_exam_date'] . "\n";
    echo "Result: " . $row['result'] . "\n";
    echo "Sex: " . ($row['sex'] ?? 'NULL') . "\n";
    echo "---\n";
}

echo "\nRecords with course = '0' (Teacher Education):\n";
$bad_records = $conn->query("SELECT id, name, course FROM board_passers WHERE course = '0' AND department='Teacher Education'");
$count = 0;
while ($row = $bad_records->fetch_assoc()) {
    echo "ID: " . $row['id'] . " - Name: " . $row['name'] . " - Course: " . $row['course'] . "\n";
    $count++;
}

echo "\nFound $count records with course = '0' in Teacher Education\n";

$conn->close();
?>