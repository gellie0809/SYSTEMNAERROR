<?php
session_start();
$_SESSION["users"] = 'eng_admin@lspu.edu.ph';

require_once 'db_config.php';
$conn = getDbConnection();

echo "<h1>Checking Anonymous Board Passers Data</h1>";

// Check if table exists
$result = $conn->query("SHOW TABLES LIKE 'anonymous_board_passers'");
if ($result->num_rows === 0) {
    echo "<p style='color:red;'>Table 'anonymous_board_passers' does NOT exist!</p>";
    echo "<p>Creating table now...</p>";
    
    $create_sql = "CREATE TABLE IF NOT EXISTS anonymous_board_passers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        board_exam_type VARCHAR(255) NOT NULL,
        board_exam_date DATE NOT NULL,
        exam_type VARCHAR(100) NOT NULL COMMENT 'First Timer or Repeater',
        result VARCHAR(50) NOT NULL,
        department VARCHAR(100) DEFAULT 'Engineering',
        is_deleted TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_dept (department),
        INDEX idx_exam_type (board_exam_type),
        INDEX idx_result (result),
        INDEX idx_date (board_exam_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    if ($conn->query($create_sql)) {
        echo "<p style='color:green;'>Table created successfully!</p>";
    } else {
        echo "<p style='color:red;'>Error creating table: " . $conn->error . "</p>";
    }
} else {
    echo "<p style='color:green;'>Table 'anonymous_board_passers' exists!</p>";
}

// Check data count
$result = $conn->query("SELECT COUNT(*) as total FROM anonymous_board_passers");
$row = $result->fetch_assoc();
echo "<h2>Total Records: " . $row['total'] . "</h2>";

// Check non-deleted count
$result = $conn->query("SELECT COUNT(*) as total FROM anonymous_board_passers WHERE (is_deleted IS NULL OR is_deleted = 0)");
$row = $result->fetch_assoc();
echo "<h2>Non-Deleted Records: " . $row['total'] . "</h2>";

// Show sample data
echo "<h2>Sample Records (First 10):</h2>";
$result = $conn->query("SELECT * FROM anonymous_board_passers ORDER BY id DESC LIMIT 10");
if ($result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Board Exam Type</th><th>Exam Date</th><th>Exam Type</th><th>Result</th><th>Is Deleted</th><th>Created At</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['board_exam_type'] . "</td>";
        echo "<td>" . $row['board_exam_date'] . "</td>";
        echo "<td>" . $row['exam_type'] . "</td>";
        echo "<td>" . $row['result'] . "</td>";
        echo "<td>" . ($row['is_deleted'] ?? '0') . "</td>";
        echo "<td>" . $row['created_at'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:red;'>NO DATA FOUND! You need to add anonymous data first.</p>";
    echo "<p><a href='testing_anonymous_data.php'>Click here to add anonymous data</a></p>";
}

// Test the stats endpoint
echo "<h2>Testing Stats Endpoint:</h2>";
echo "<pre>";
include 'stats_anonymous_engineering.php';
echo "</pre>";

$conn->close();
?>
