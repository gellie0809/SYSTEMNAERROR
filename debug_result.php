<?php
$conn = new mysqli('localhost', 'root', '', 'project_db');
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

$departments = [
    'Engineering',
    'Arts and Science',
    'Business Administration and Accountancy',
    'Criminal Justice Education',
    'Teacher Education'
];

foreach ($departments as $dept) {
    echo "\n=== Checking result values in database for $dept ===\n";
    echo str_repeat("=", 50) . "\n";

    $result = $conn->query("SELECT name, result FROM board_passers WHERE department='$dept' LIMIT 10");
    if ($result) {
        if ($result->num_rows === 0) {
            echo "No records found for $dept\n";
            continue;
        }
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
        echo 'Query failed for ' . $dept . ': ' . $conn->error . "\n";
    }

    echo str_repeat("-", 50) . "\n";
}

$conn->close();
?>