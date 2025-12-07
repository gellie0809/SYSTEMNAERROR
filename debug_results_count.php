<?php
$conn = new mysqli('localhost', 'root', '', 'project_db');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$departments = [
    'Engineering',
    'Arts and Science',
    'Business Administration and Accountancy',
    'Criminal Justice Education',
    'Teacher Education'
];

foreach ($departments as $dept) {
    echo "\n=== All unique result values in $dept department ===\n";
    echo str_repeat("=", 50) . "\n";

    $result = $conn->query("SELECT result, COUNT(*) as count 
                            FROM board_passers 
                            WHERE department='$dept' 
                            GROUP BY result");

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "Result: '" . $row['result'] . "' - Count: " . $row['count'] . "\n";
        }
    } else {
        echo "No records found for $dept\n";
    }
}

$conn->close();
?>