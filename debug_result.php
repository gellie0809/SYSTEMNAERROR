<?php
$conn = new mysqli('localhost', 'root', '', 'project_db');
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

echo "Checking result values in database:\n";
echo "================================\n";

$result = $conn->query("SELECT name, result FROM board_passers WHERE department='Engineering' LIMIT 10");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "Name: " . $row['name'] . "\n";
        echo "Result: '" . $row['result'] . "'\n";
        echo "Type: " . gettype($row['result']) . "\n";
        echo "Length: " . strlen($row['result']) . "\n";
        echo "Is numeric: " . (is_numeric($row['result']) ? 'Yes' : 'No') . "\n";
        echo "Raw value: ";
        var_dump($row['result']);
        echo "---\n";
    }
} else {
    echo 'Query failed: ' . $conn->error . "\n";
}

$conn->close();
?>
