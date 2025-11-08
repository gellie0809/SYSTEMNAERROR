<?php
$conn = new mysqli('localhost', 'root', '', 'project_db');
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

echo "CRITICAL DATABASE INVESTIGATION\n";
echo "===============================\n\n";

// 1. Check database connection
echo "1. DATABASE CONNECTION:\n";
echo "----------------------\n";
echo "✅ Connection successful\n\n";

// 2. Check if table exists
echo "2. TABLE EXISTENCE CHECK:\n";
echo "------------------------\n";
$tableCheck = $conn->query("SHOW TABLES LIKE 'board_passers'");
if ($tableCheck && $tableCheck->num_rows > 0) {
    echo "✅ board_passers table exists\n";
} else {
    echo "❌ board_passers table NOT FOUND!\n";
    echo "This could be the source of the problem!\n\n";
    
    // Show all tables
    echo "Available tables:\n";
    $allTables = $conn->query("SHOW TABLES");
    if ($allTables) {
        while ($row = $allTables->fetch_array()) {
            echo "- " . $row[0] . "\n";
        }
    }
}

// 3. Check table structure if it exists
echo "\n3. TABLE STRUCTURE:\n";
echo "-------------------\n";
$structureCheck = $conn->query("DESCRIBE board_passers");
if ($structureCheck) {
    while ($row = $structureCheck->fetch_assoc()) {
        echo "Column: " . $row['Field'] . " | Type: " . $row['Type'] . " | Null: " . $row['Null'] . "\n";
    }
} else {
    echo "❌ Cannot describe table: " . $conn->error . "\n";
}

// 4. Count all records
echo "\n4. RECORD COUNT:\n";
echo "----------------\n";
$totalCount = $conn->query("SELECT COUNT(*) as count FROM board_passers");
if ($totalCount) {
    $total = $totalCount->fetch_assoc()['count'];
    echo "Total records in board_passers: $total\n";
} else {
    echo "❌ Cannot count records: " . $conn->error . "\n";
}

// 5. Count by department
echo "\n5. RECORDS BY DEPARTMENT:\n";
echo "-------------------------\n";
$deptCount = $conn->query("SELECT department, COUNT(*) as count FROM board_passers GROUP BY department");
if ($deptCount && $deptCount->num_rows > 0) {
    while ($row = $deptCount->fetch_assoc()) {
        echo "Department: '" . $row['department'] . "' - Count: " . $row['count'] . "\n";
    }
} else {
    echo "No records found or error: " . $conn->error . "\n";
}

// 6. Show recent records if any
echo "\n6. RECENT RECORDS (if any):\n";
echo "---------------------------\n";
$recentRecords = $conn->query("SELECT id, name, course, department FROM board_passers ORDER BY id DESC LIMIT 5");
if ($recentRecords && $recentRecords->num_rows > 0) {
    while ($row = $recentRecords->fetch_assoc()) {
        echo "ID: " . $row['id'] . " | Name: " . $row['name'] . " | Course: " . $row['course'] . " | Dept: '" . $row['department'] . "'\n";
    }
} else {
    echo "❌ No records found\n";
}

// 7. Test manual insert
echo "\n7. TESTING MANUAL INSERT:\n";
echo "-------------------------\n";
$testInsert = $conn->prepare("INSERT INTO board_passers (name, sex, course, year_graduated, board_exam_date, result, department, exam_type, board_exam_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

if ($testInsert) {
    $name = "EMERGENCY TEST " . date('Y-m-d H:i:s');
    $sex = "Male";
    $course = "Test Course";
    $year = 2023;
    $date = "2023-01-01";
    $result = "PASSED";
    $dept = "Engineering";
    $examType = "Test";
    $boardExamType = "Board Exam";
    
    $testInsert->bind_param("sssisssss", $name, $sex, $course, $year, $date, $result, $dept, $examType, $boardExamType);
    
    if ($testInsert->execute()) {
        $insertId = $conn->insert_id;
        echo "✅ Manual insert successful! ID: $insertId\n";
        
        // Verify immediately
        $verify = $conn->query("SELECT COUNT(*) as count FROM board_passers WHERE id = $insertId");
        if ($verify) {
            $verifyRow = $verify->fetch_assoc();
            if ($verifyRow['count'] > 0) {
                echo "✅ Record verified in database\n";
            } else {
                echo "❌ Record disappeared immediately after insert!\n";
            }
        }
    } else {
        echo "❌ Manual insert failed: " . $testInsert->error . "\n";
    }
    $testInsert->close();
} else {
    echo "❌ Cannot prepare insert statement: " . $conn->error . "\n";
}

// 8. Check database engine and configuration
echo "\n8. DATABASE ENGINE INFO:\n";
echo "------------------------\n";
$engineInfo = $conn->query("SHOW TABLE STATUS LIKE 'board_passers'");
if ($engineInfo && $engineInfo->num_rows > 0) {
    $engine = $engineInfo->fetch_assoc();
    echo "Engine: " . $engine['Engine'] . "\n";
    echo "Rows: " . $engine['Rows'] . "\n";
    echo "Auto_increment: " . $engine['Auto_increment'] . "\n";
} else {
    echo "Cannot get engine info\n";
}

$conn->close();
echo "\n\nINVESTIGATION COMPLETE!\n";
echo "If records are disappearing, this output will help identify the cause.\n";
?>
