<?php
$conn = new mysqli('localhost', 'root', '', 'project_db');
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

echo "Fixing department values in board_passers table...\n";

// Define correct department names
$departments = [
    'Engineering',
    'Arts and Science',
    'Business Administration and Accountancy',
    'Criminal Justice Education',
    'Teacher Education'
];

// Optional: map common incorrect names to correct ones
$fixMap = [
    'College of Engineering' => 'Engineering',
    'Eng' => 'Engineering',
    'Arts & Science' => 'Arts and Science',
    'Business Admin' => 'Business Administration and Accountancy',
    'Criminal Justice' => 'Criminal Justice Education',
    'Teacher Ed' => 'Teacher Education'
];

// Apply fixes
foreach ($fixMap as $wrong => $correct) {
    $updateQuery = "UPDATE board_passers SET department = '$correct' WHERE department = '$wrong'";
    $result = $conn->query($updateQuery);
    if ($result) {
        $affected_rows = $conn->affected_rows;
        if ($affected_rows > 0) {
            echo "Updated $affected_rows records from '$wrong' to '$correct'\n";
        }
    } else {
        echo "Error updating '$wrong' to '$correct': " . $conn->error . "\n";
    }
}

// Verify current department values
echo "\nVerifying update - Current department values:\n";
$result = $conn->query('SELECT department, COUNT(*) as count FROM board_passers GROUP BY department ORDER BY department ASC');
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo 'Department: "' . $row['department'] . '" - Count: ' . $row['count'] . "\n";
    }
} else {
    echo "No records found in board_passers table\n";
}

$conn->close();
echo "\nDatabase fix completed!\n";
?>