<?php
// Database initialization - ensures table always exists
function ensureDatabaseExists($conn) {
    // Check if table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'board_passers'");
    
    if (!$tableCheck || $tableCheck->num_rows == 0) {
        // Table doesn't exist, create it
        $createTable = "
        CREATE TABLE board_passers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            first_name VARCHAR(100) DEFAULT NULL,
            last_name VARCHAR(100) DEFAULT NULL,
            middle_name VARCHAR(100) DEFAULT NULL,
            sex VARCHAR(10) NOT NULL,
            course VARCHAR(255) NOT NULL,
            year_graduated INT NOT NULL,
            board_exam_date DATE NOT NULL,
            result VARCHAR(20) NOT NULL DEFAULT 'PASSED',
            department VARCHAR(100) NOT NULL DEFAULT 'Engineering',
            exam_type VARCHAR(255) DEFAULT NULL,
            board_exam_type VARCHAR(255) DEFAULT 'Board Exam',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        if ($conn->query($createTable)) {
            error_log("Auto-created board_passers table");
            return true;
        } else {
            error_log("Failed to auto-create board_passers table: " . $conn->error);
            return false;
        }
    }
    return true;
}

// Test the function
$conn = new mysqli('localhost', 'root', '', 'project_db');
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

echo "DATABASE AUTO-INITIALIZATION\n";
echo "============================\n\n";

if (ensureDatabaseExists($conn)) {
    echo "✅ Database table ready\n";
} else {
    echo "❌ Failed to initialize database\n";
}

// Test adding a record
echo "\nTesting record addition:\n";
echo "------------------------\n";

$stmt = $conn->prepare("INSERT INTO board_passers (name, sex, course, year_graduated, board_exam_date, result, department, exam_type, board_exam_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

if ($stmt) {
    $name = "Auto Test " . date('Y-m-d H:i:s');
    $sex = "Male";
    $course = "Computer Engineering";
    $year = 2023;
    $date = "2023-01-01";
    $result = "PASSED";
    $dept = "Engineering";
    $examType = "Test";
    $boardType = "Board Exam";
    
    $stmt->bind_param("sssisssss", $name, $sex, $course, $year, $date, $result, $dept, $examType, $boardType);
    
    if ($stmt->execute()) {
        echo "✅ Record added successfully (ID: " . $conn->insert_id . ")\n";
    } else {
        echo "❌ Failed to add record: " . $stmt->error . "\n";
    }
    $stmt->close();
} else {
    echo "❌ Cannot prepare statement: " . $conn->error . "\n";
}

$conn->close();
echo "\nAuto-initialization complete!\n";
?>
