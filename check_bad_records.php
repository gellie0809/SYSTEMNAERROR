<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "project_db";
$conn = new mysqli($servername, $username, $password, $dbname);

echo "All board passers in database:\n";
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

echo "\nRecords with course = '0':\n";
$bad_records = $conn->query("SELECT id, name, course FROM board_passers WHERE course = '0' AND department='Engineering'");
$count = 0;
while ($row = $bad_records->fetch_assoc()) {
    echo "ID: " . $row['id'] . " - Name: " . $row['name'] . " - Course: " . $row['course'] . "\n";
    $count++;
}

echo "\nFound $count records with course = '0'\n";

if ($count > 0) {
    echo "\nDo you want to delete these records? (This script will show what would be deleted)\n";
    echo "To actually delete, uncomment the DELETE query below.\n";
    // Uncomment the line below to actually delete the bad records:
    // $conn->query("DELETE FROM board_passers WHERE course = '0' AND department='Engineering'");
    // echo "Deleted $count records with course = '0'\n";
}

$conn->close();
?>
