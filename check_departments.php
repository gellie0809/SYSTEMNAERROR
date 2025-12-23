<?php
$conn = new mysqli('localhost', 'root', '', 'project_db');
$result = $conn->query('SELECT DISTINCT department FROM anonymous_board_passers');
echo "Departments in database:\n";
while($row = $result->fetch_assoc()) {
    echo "- " . $row['department'] . "\n";
}
$conn->close();
