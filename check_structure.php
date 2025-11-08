<?php
$conn = new mysqli('localhost', 'root', '', 'project_db');
echo "board_passers table structure:\n";
$result = $conn->query('DESCRIBE board_passers');
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . ' - ' . $row['Type'] . ' - Null: ' . $row['Null'] . ' - Default: ' . ($row['Default'] ?? 'NULL') . "\n";
}
?>
