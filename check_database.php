<?php
$conn = new mysqli('localhost', 'root', '', 'project_db');
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

echo "Checking department values in board_passers table:\n";
$result = $conn->query('SELECT department, COUNT(*) as count FROM board_passers GROUP BY department');
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo 'Department: "' . $row['department'] . '" - Count: ' . $row['count'] . "\n";
    }
} else {
    echo "No records found in board_passers table\n";
}

echo "\nChecking for records with 'College of Engineering' department:\n";
$result2 = $conn->query("SELECT COUNT(*) as count FROM board_passers WHERE department = 'College of Engineering'");
if ($result2) {
    $row = $result2->fetch_assoc();
    echo "Records with 'College of Engineering': " . $row['count'] . "\n";
}

echo "\nChecking for records with 'Engineering' department:\n";
$result3 = $conn->query("SELECT COUNT(*) as count FROM board_passers WHERE department = 'Engineering'");
if ($result3) {
    $row = $result3->fetch_assoc();
    echo "Records with 'Engineering': " . $row['count'] . "\n";
}

// Show sample records if any exist
echo "\nSample records from board_passers table:\n";
$result4 = $conn->query("SELECT name, department FROM board_passers LIMIT 5");
if ($result4 && $result4->num_rows > 0) {
    while ($row = $result4->fetch_assoc()) {
        echo "Name: " . $row['name'] . " - Department: '" . $row['department'] . "'\n";
    }
} else {
    echo "No sample records found\n";
}

$conn->close();
?>
