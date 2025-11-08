<?php
$conn = new mysqli('localhost', 'root', '', 'project_db');

echo "All unique result values in Engineering department:\n";
echo "================================================\n";

$result = $conn->query("SELECT result, COUNT(*) as count FROM board_passers WHERE department='Engineering' GROUP BY result");
while ($row = $result->fetch_assoc()) {
    echo "Result: '" . $row['result'] . "' - Count: " . $row['count'] . "\n";
}

$conn->close();
?>
