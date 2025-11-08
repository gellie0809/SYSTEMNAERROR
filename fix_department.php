<?php
$conn = new mysqli('localhost', 'root', '', 'project_db');
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

echo "Fixing department values in board_passers table...\n";

// Update records with 'College of Engineering' to 'Engineering'
$updateQuery = "UPDATE board_passers SET department = 'Engineering' WHERE department = 'College of Engineering'";
$result = $conn->query($updateQuery);

if ($result) {
    $affected_rows = $conn->affected_rows;
    echo "Successfully updated $affected_rows records from 'College of Engineering' to 'Engineering'\n";
} else {
    echo "Error updating records: " . $conn->error . "\n";
}

// Verify the update
echo "\nVerifying update - Current department values:\n";
$result = $conn->query('SELECT department, COUNT(*) as count FROM board_passers GROUP BY department');
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo 'Department: "' . $row['department'] . '" - Count: ' . $row['count'] . "\n";
    }
} else {
    echo "No records found in board_passers table\n";
}

$conn->close();
echo "\nDatabase fix completed!\n";
?>
