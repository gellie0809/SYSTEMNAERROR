<?php
$conn = new mysqli('localhost', 'root', '', 'project_db');

echo "Updating Nguyen, Kim record (ID: 18) with '0' result...\n";

// Update the record
$update_result = $conn->query("UPDATE board_passers SET result='Failed' WHERE id=18 AND result='0'");

if ($update_result) {
    echo "Successfully updated record ID 18\n";
    echo "Rows affected: " . $conn->affected_rows . "\n";
} else {
    echo "Error updating record: " . $conn->error . "\n";
}

// Verify all records are now correct
echo "\nVerifying all Engineering department results:\n";
echo "===========================================\n";
$result = $conn->query("SELECT id, name, result FROM board_passers WHERE department='Engineering' ORDER BY id");
while ($row = $result->fetch_assoc()) {
    echo "ID: " . $row['id'] . " | Name: " . $row['name'] . " | Result: '" . $row['result'] . "'\n";
}

echo "\nFinal result counts:\n";
echo "===================\n";
$result = $conn->query("SELECT result, COUNT(*) as count FROM board_passers WHERE department='Engineering' GROUP BY result");
while ($row = $result->fetch_assoc()) {
    echo "Result: '" . $row['result'] . "' - Count: " . $row['count'] . "\n";
}

$conn->close();
?>
