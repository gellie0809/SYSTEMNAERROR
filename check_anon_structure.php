<?php
$conn = new mysqli('localhost', 'root', '', 'project_db');
echo "anonymous_board_passers table structure:\n";
$result = $conn->query('DESCRIBE anonymous_board_passers');
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . ' - ' . $row['Type'] . "\n";
}

echo "\n\nCount by department:\n";
$result = $conn->query('SELECT department, COUNT(*) as cnt FROM anonymous_board_passers WHERE (is_deleted IS NULL OR is_deleted = 0) GROUP BY department');
while ($row = $result->fetch_assoc()) {
    echo $row['department'] . ': ' . $row['cnt'] . "\n";
}
?>
