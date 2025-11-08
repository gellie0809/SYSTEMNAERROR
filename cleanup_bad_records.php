<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "project_db";
$conn = new mysqli($servername, $username, $password, $dbname);

echo "Deleting records with course = '0'...\n";

// Delete the bad records
$result = $conn->query("DELETE FROM board_passers WHERE course = '0' AND department='Engineering'");

if ($result) {
    echo "Successfully deleted " . $conn->affected_rows . " records with course = '0'\n";
} else {
    echo "Error deleting records: " . $conn->error . "\n";
}

echo "\nRemaining records after cleanup:\n";
echo "=================================\n";
$result = $conn->query("SELECT * FROM board_passers WHERE department='Engineering' ORDER BY id ASC");
while ($row = $result->fetch_assoc()) {
    echo "ID: " . $row['id'] . " - Name: " . $row['name'] . " - Course: " . $row['course'] . "\n";
}

$conn->close();
?>
