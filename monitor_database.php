<?php
$conn = new mysqli('localhost', 'root', '', 'project_db');
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

echo "CURRENT DATABASE STATE\n";
echo "=====================\n\n";

// Check if table exists
$tableCheck = $conn->query("SHOW TABLES LIKE 'board_passers'");
if ($tableCheck && $tableCheck->num_rows > 0) {
    echo "✅ Table exists\n";
} else {
    echo "❌ TABLE DOES NOT EXIST!\n";
    echo "This is the root cause - table keeps getting deleted!\n\n";
    
    // Recreate table immediately
    echo "RECREATING TABLE NOW:\n";
    echo "--------------------\n";
    
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
        echo "✅ Table recreated successfully\n";
    } else {
        echo "❌ Failed to recreate table: " . $conn->error . "\n";
    }
}

// Count records
$count = $conn->query("SELECT COUNT(*) as count FROM board_passers");
if ($count) {
    $total = $count->fetch_assoc()['count'];
    echo "Current record count: $total\n";
} else {
    echo "Cannot count records: " . $conn->error . "\n";
}

// Show all records
echo "\nALL RECORDS:\n";
echo "------------\n";
$records = $conn->query("SELECT id, name, course, department FROM board_passers ORDER BY id");
if ($records && $records->num_rows > 0) {
    while ($row = $records->fetch_assoc()) {
        echo "ID: " . $row['id'] . " | Name: " . $row['name'] . " | Course: " . $row['course'] . " | Dept: " . $row['department'] . "\n";
    }
} else {
    echo "No records found\n";
}

// Test adding a record with all possible safety measures
echo "\nTESTING SECURE INSERT:\n";
echo "----------------------\n";

// Disable autocommit and use transaction
$conn->autocommit(false);
$conn->begin_transaction();

try {
    $stmt = $conn->prepare("INSERT INTO board_passers (name, sex, course, year_graduated, board_exam_date, result, department, exam_type, board_exam_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $name = "EMERGENCY TEST " . date('Y-m-d H:i:s');
    $sex = "Male";
    $course = "Emergency Engineering";
    $year = 2023;
    $date = "2023-01-01";
    $result = "PASSED";
    $dept = "Engineering";
    $examType = "Emergency Test";
    $boardType = "Board Exam";
    
    $stmt->bind_param("sssisssss", $name, $sex, $course, $year, $date, $result, $dept, $examType, $boardType);
    
    if ($stmt->execute()) {
        $insertId = $conn->insert_id;
        echo "✅ Insert successful (ID: $insertId)\n";
        
        // Commit transaction
        $conn->commit();
        echo "✅ Transaction committed\n";
        
        // Verify after commit
        $verify = $conn->query("SELECT COUNT(*) as count FROM board_passers WHERE id = $insertId");
        if ($verify) {
            $verifyCount = $verify->fetch_assoc()['count'];
            if ($verifyCount > 0) {
                echo "✅ Record persists after commit\n";
            } else {
                echo "❌ Record disappeared after commit!\n";
            }
        }
        
    } else {
        echo "❌ Insert failed: " . $stmt->error . "\n";
        $conn->rollback();
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
    $conn->rollback();
}

// Re-enable autocommit
$conn->autocommit(true);

// Final count
$finalCount = $conn->query("SELECT COUNT(*) as count FROM board_passers");
if ($finalCount) {
    $total = $finalCount->fetch_assoc()['count'];
    echo "\nFinal record count: $total\n";
}

$conn->close();
echo "\nMONITORING COMPLETE!\n";
?>
