<?php
$conn = new mysqli('localhost', 'root', '', 'project_db');
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

echo "DATABASE REPAIR AND RECREATION\n";
echo "==============================\n\n";

// First, let's backup any existing data
echo "1. BACKING UP EXISTING DATA:\n";
echo "----------------------------\n";
$backup = $conn->query("SELECT * FROM board_passers");
$backupData = [];
if ($backup && $backup->num_rows > 0) {
    while ($row = $backup->fetch_assoc()) {
        $backupData[] = $row;
    }
    echo "✅ Backed up " . count($backupData) . " existing records\n";
} else {
    echo "No existing data to backup\n";
}

// Check table status
echo "\n2. CHECKING TABLE STATUS:\n";
echo "-------------------------\n";
$checkTable = $conn->query("CHECK TABLE board_passers");
if ($checkTable) {
    while ($row = $checkTable->fetch_assoc()) {
        echo "Table: " . $row['Table'] . " | Op: " . $row['Op'] . " | Msg_type: " . $row['Msg_type'] . " | Msg_text: " . $row['Msg_text'] . "\n";
    }
}

// Repair table if needed
echo "\n3. REPAIRING TABLE:\n";
echo "-------------------\n";
$repairTable = $conn->query("REPAIR TABLE board_passers");
if ($repairTable) {
    while ($row = $repairTable->fetch_assoc()) {
        echo "Repair result: " . $row['Msg_text'] . "\n";
    }
}

// Recreate table with proper structure
echo "\n4. RECREATING TABLE WITH PROPER STRUCTURE:\n";
echo "------------------------------------------\n";

// Drop and recreate the table
$dropTable = "DROP TABLE IF EXISTS board_passers_backup";
$conn->query($dropTable);

$createBackup = "CREATE TABLE board_passers_backup AS SELECT * FROM board_passers";
$conn->query($createBackup);

$dropOriginal = "DROP TABLE board_passers";
if ($conn->query($dropOriginal)) {
    echo "✅ Dropped original table\n";
} else {
    echo "❌ Failed to drop table: " . $conn->error . "\n";
}

// Create new table with proper structure
$createTable = "
CREATE TABLE board_passers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    middle_name VARCHAR(100),
    sex ENUM('Male', 'Female') NOT NULL,
    course VARCHAR(255) NOT NULL,
    year_graduated INT NOT NULL,
    board_exam_date DATE NOT NULL,
    result ENUM('PASSED', 'FAILED', 'Passed', 'Failed') NOT NULL,
    department VARCHAR(100) NOT NULL,
    exam_type VARCHAR(255),
    board_exam_type VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($createTable)) {
    echo "✅ Created new table with proper structure\n";
} else {
    echo "❌ Failed to create table: " . $conn->error . "\n";
}

// Restore data if any
if (!empty($backupData)) {
    echo "\n5. RESTORING DATA:\n";
    echo "------------------\n";
    
    $insertStmt = $conn->prepare("INSERT INTO board_passers (name, first_name, last_name, middle_name, sex, course, year_graduated, board_exam_date, result, department, exam_type, board_exam_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $restored = 0;
    foreach ($backupData as $record) {
        $insertStmt->bind_param("ssssssssssss", 
            $record['name'],
            $record['first_name'] ?? '',
            $record['last_name'] ?? '',
            $record['middle_name'] ?? '',
            $record['sex'],
            $record['course'],
            $record['year_graduated'],
            $record['board_exam_date'],
            $record['result'],
            $record['department'],
            $record['exam_type'] ?? '',
            $record['board_exam_type'] ?? ''
        );
        
        if ($insertStmt->execute()) {
            $restored++;
        }
    }
    
    echo "✅ Restored $restored out of " . count($backupData) . " records\n";
    $insertStmt->close();
}

// Test the new table
echo "\n6. TESTING NEW TABLE:\n";
echo "---------------------\n";
$testInsert = $conn->prepare("INSERT INTO board_passers (name, sex, course, year_graduated, board_exam_date, result, department, exam_type, board_exam_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

$testName = "TEST RECORD " . date('H:i:s');
$testSex = "Male";
$testCourse = "Test Engineering";
$testYear = 2023;
$testDate = "2023-01-01";
$testResult = "PASSED";
$testDept = "Engineering";
$testExamType = "Test";
$testBoardType = "Board Exam";

$testInsert->bind_param("sssisssss", $testName, $testSex, $testCourse, $testYear, $testDate, $testResult, $testDept, $testExamType, $testBoardType);

if ($testInsert->execute()) {
    $testId = $conn->insert_id;
    echo "✅ Test insert successful (ID: $testId)\n";
    
    // Verify the record persists
    sleep(1);
    $verify = $conn->query("SELECT COUNT(*) as count FROM board_passers WHERE id = $testId");
    if ($verify) {
        $count = $verify->fetch_assoc()['count'];
        if ($count > 0) {
            echo "✅ Test record persists in database\n";
        } else {
            echo "❌ Test record disappeared!\n";
        }
    }
} else {
    echo "❌ Test insert failed: " . $testInsert->error . "\n";
}

$testInsert->close();

// Final count
$finalCount = $conn->query("SELECT COUNT(*) as count FROM board_passers");
if ($finalCount) {
    $total = $finalCount->fetch_assoc()['count'];
    echo "\nFinal record count: $total\n";
}

// Clean up backup table
$conn->query("DROP TABLE IF EXISTS board_passers_backup");

$conn->close();
echo "\nDATABASE REPAIR COMPLETE!\n";
echo "Try adding records now through the web interface.\n";
?>
