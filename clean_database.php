<?php
$conn = new mysqli('localhost', 'root', '', 'project_db');
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

echo "Cleaning up test data...\n";

// Delete any existing Engineering records (for testing purposes)
$deleteResult = $conn->query("DELETE FROM board_passers WHERE department = 'Engineering'");

if ($deleteResult) {
    $affected = $conn->affected_rows;
    echo "Deleted $affected Engineering records from database\n";
} else {
    echo "Error deleting records: " . $conn->error . "\n";
}

// Verify cleanup
$result = $conn->query("SELECT COUNT(*) as count FROM board_passers WHERE department = 'Engineering'");
if ($result) {
    $row = $result->fetch_assoc();
    echo "Remaining Engineering records: " . $row['count'] . "\n";
}

$conn->close();
echo "\nDatabase cleaned. You can now try importing again.\n";
?>
