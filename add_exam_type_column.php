<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "project_db";
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "Checking and adding exam_type column...\n";

// Check if exam_type column exists
$check_exam_type = $conn->query("SHOW COLUMNS FROM board_passers LIKE 'exam_type'");
if ($check_exam_type->num_rows == 0) {
    echo "Adding exam_type column to board_passers table...\n";
    $result = $conn->query("ALTER TABLE board_passers ADD COLUMN exam_type VARCHAR(20) DEFAULT 'First Timer'");
    
    if ($result) {
        echo "Successfully added exam_type column\n";
        
        // Update existing records to have default value
        $update_result = $conn->query("UPDATE board_passers SET exam_type = 'First Timer' WHERE exam_type IS NULL");
        echo "Updated " . $conn->affected_rows . " existing records with default exam_type\n";
    } else {
        echo "Error adding exam_type column: " . $conn->error . "\n";
    }
} else {
    echo "exam_type column already exists\n";
}

echo "\nCurrent table structure:\n";
$result = $conn->query("DESCRIBE board_passers");
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . " - " . $row['Type'] . " - Default: " . ($row['Default'] ?? 'NULL') . "\n";
}

echo "\nTesting exam_type values:\n";
$result = $conn->query("SELECT name, exam_type FROM board_passers WHERE department='Engineering' LIMIT 5");
while ($row = $result->fetch_assoc()) {
    echo "Name: " . $row['name'] . " - Exam Type: " . ($row['exam_type'] ?? 'NULL') . "\n";
}

$conn->close();
?>
