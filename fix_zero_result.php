<?php
$conn = new mysqli('localhost', 'root', '', 'project_db');

echo "Updating record with ID 17...\n";

// Update the record
$update_result = $conn->query("UPDATE board_passers SET result='Failed' WHERE id=17 AND result='0'");

if ($update_result) {
    echo "Successfully updated record ID 17\n";
    echo "Rows affected: " . $conn->affected_rows . "\n";
} else {
    echo "Error updating record: " . $conn->error . "\n";
}

// Verify the update
echo "\nVerifying update:\n";
$result = $conn->query("SELECT name, result FROM board_passers WHERE id=17");
if ($row = $result->fetch_assoc()) {
    echo "Name: " . $row['name'] . "\n";
    echo "Result: '" . $row['result'] . "'\n";
}

$conn->close();
?>
