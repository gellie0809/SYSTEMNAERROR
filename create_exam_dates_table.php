<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "project_db";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create board_exam_dates table
$sql = "CREATE TABLE IF NOT EXISTS board_exam_dates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    exam_date DATE NOT NULL,
    exam_description VARCHAR(255) DEFAULT NULL,
    department VARCHAR(100) NOT NULL DEFAULT 'Engineering',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_exam_date_dept (exam_date, department)
)";

if ($conn->query($sql)) {
    echo "Table board_exam_dates created successfully\n";
} else {
    echo "Error creating table: " . $conn->error . "\n";
}

$conn->close();
?>
