<?php
$conn = new mysqli('localhost', 'root', '', 'project_db');
if ($conn->connect_error) die('Connection failed: ' . $conn->connect_error);

$departments = [
    'Engineering',
    'Arts and Science',
    'Business Administration and Accountancy',
    'Criminal Justice Education',
    'Teacher Education'
];

echo "Fixing '0' results in board_passers table...\n";

foreach ($departments as $dept) {
    // Update all '0' results to 'Failed' for this department
    $update = $conn->query("UPDATE board_passers SET result='Failed' WHERE result='0' AND department='$dept'");
    if ($update) {
        $affected = $conn->affected_rows;
        if ($affected > 0) {
            echo "Updated $affected record(s) in $dept department\n";
        }
    } else {
        echo "Error updating $dept: " . $conn->error . "\n";
    }
}

// Verify all results per department
echo "\nVerifying results for all departments:\n";
foreach ($departments as $dept) {
    echo "\nDepartment: $dept\n";
    echo "====================\n";
    $res = $conn->query("SELECT id, name, result FROM board_passers WHERE department='$dept' ORDER BY id");
    while ($row = $res->fetch_assoc()) {
        echo "ID: " . $row['id'] . " | Name: " . $row['name'] . " | Result: '" . $row['result'] . "'\n";
    }

    // Show counts
    $countRes = $conn->query("SELECT result, COUNT(*) as count FROM board_passers WHERE department='$dept' GROUP BY result");
    echo "\nResult counts for $dept:\n";
    while ($row = $countRes->fetch_assoc()) {
        echo "Result: '" . $row['result'] . "' - Count: " . $row['count'] . "\n";
    }
}

$conn->close();
echo "\nAll '0' results fixed!\n";
?>