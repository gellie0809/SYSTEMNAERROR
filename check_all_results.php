<?php
$conn = new mysqli('localhost', 'root', '', 'project_db');

echo "Checking all result values in Engineering department:\n";
echo "===================================================\n";

$result = $conn->query("SELECT id, name, result FROM board_passers WHERE department='Engineering' ORDER BY id");
while ($row = $result->fetch_assoc()) {
    echo "ID: " . $row['id'] . " | Name: " . $row['name'] . " | Result: '" . $row['result'] . "'\n";
}

echo "\nUnique result values count:\n";
echo "==========================\n";
$result = $conn->query("SELECT result, COUNT(*) as count FROM board_passers WHERE department='Engineering' GROUP BY result");
while ($row = $result->fetch_assoc()) {
    echo "Result: '" . $row['result'] . "' - Count: " . $row['count'] . "\n";
}

$conn->close();
?>
