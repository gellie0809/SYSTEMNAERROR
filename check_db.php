<?php
$conn = new mysqli("localhost", "root", "", "project_db");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "Database connection successful!\n\n";

$result = $conn->query("SHOW COLUMNS FROM board_passers");
if ($result) {
    echo "Table columns:\n";
    while ($row = $result->fetch_assoc()) {
        echo $row['Field'] . " (" . $row['Type'] . ")\n";
    }
}

$conn->close();
?>
