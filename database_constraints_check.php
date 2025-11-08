<?php
$conn = new mysqli('localhost', 'root', '', 'project_db');
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

echo "DATABASE CONSTRAINTS AND TRIGGERS CHECK\n";
echo "=======================================\n\n";

// Check for triggers
echo "1. CHECKING TRIGGERS:\n";
echo "--------------------\n";
$triggers = $conn->query("SHOW TRIGGERS LIKE 'board_passers'");
if ($triggers && $triggers->num_rows > 0) {
    echo "⚠️ Found triggers on board_passers table:\n";
    while ($row = $triggers->fetch_assoc()) {
        echo "- Trigger: " . $row['Trigger'] . " | Event: " . $row['Event'] . " | Timing: " . $row['Timing'] . "\n";
    }
} else {
    echo "✅ No triggers found on board_passers table\n";
}

// Check for foreign key constraints
echo "\n2. CHECKING FOREIGN KEYS:\n";
echo "-------------------------\n";
$fkeys = $conn->query("
    SELECT 
        CONSTRAINT_NAME,
        COLUMN_NAME,
        REFERENCED_TABLE_NAME,
        REFERENCED_COLUMN_NAME
    FROM 
        information_schema.KEY_COLUMN_USAGE 
    WHERE 
        TABLE_SCHEMA = 'project_db' 
        AND TABLE_NAME = 'board_passers' 
        AND REFERENCED_TABLE_NAME IS NOT NULL
");

if ($fkeys && $fkeys->num_rows > 0) {
    echo "⚠️ Found foreign key constraints:\n";
    while ($row = $fkeys->fetch_assoc()) {
        echo "- " . $row['CONSTRAINT_NAME'] . ": " . $row['COLUMN_NAME'] . " -> " . $row['REFERENCED_TABLE_NAME'] . "." . $row['REFERENCED_COLUMN_NAME'] . "\n";
    }
} else {
    echo "✅ No foreign key constraints found\n";
}

// Check MySQL logs for errors
echo "\n3. CHECKING MYSQL ERROR LOG:\n";
echo "----------------------------\n";
$errorLog = $conn->query("SHOW VARIABLES LIKE 'log_error'");
if ($errorLog) {
    $logPath = $errorLog->fetch_assoc();
    echo "MySQL error log location: " . $logPath['Value'] . "\n";
}

// Check if binary logging is enabled (which could cause issues)
echo "\n4. CHECKING BINARY LOGGING:\n";
echo "---------------------------\n";
$binLog = $conn->query("SHOW VARIABLES LIKE 'log_bin'");
if ($binLog) {
    $binLogStatus = $binLog->fetch_assoc();
    echo "Binary logging: " . $binLogStatus['Value'] . "\n";
}

// Check autocommit status
echo "\n5. CHECKING AUTOCOMMIT:\n";
echo "-----------------------\n";
$autocommit = $conn->query("SELECT @@autocommit");
if ($autocommit) {
    $autocommitStatus = $autocommit->fetch_assoc();
    echo "Autocommit: " . $autocommitStatus['@@autocommit'] . " (1=enabled, 0=disabled)\n";
}

// Check isolation level
echo "\n6. CHECKING TRANSACTION ISOLATION:\n";
echo "----------------------------------\n";
$isolation = $conn->query("SELECT @@transaction_isolation");
if ($isolation) {
    $isolationLevel = $isolation->fetch_assoc();
    echo "Transaction isolation: " . $isolationLevel['@@transaction_isolation'] . "\n";
}

// Check if there are any scheduled events
echo "\n7. CHECKING SCHEDULED EVENTS:\n";
echo "-----------------------------\n";
$events = $conn->query("SHOW EVENTS FROM project_db");
if ($events && $events->num_rows > 0) {
    echo "⚠️ Found scheduled events:\n";
    while ($row = $events->fetch_assoc()) {
        echo "- Event: " . $row['Name'] . " | Status: " . $row['Status'] . "\n";
    }
} else {
    echo "✅ No scheduled events found\n";
}

// Test a simple transaction
echo "\n8. TESTING TRANSACTION BEHAVIOR:\n";
echo "--------------------------------\n";
$conn->autocommit(false);
$conn->begin_transaction();

$testQuery = "INSERT INTO board_passers (name, sex, course, year_graduated, board_exam_date, result, department, exam_type, board_exam_type) VALUES ('TRANSACTION_TEST', 'Male', 'Test', 2023, '2023-01-01', 'PASSED', 'Engineering', 'Test', 'Test')";

if ($conn->query($testQuery)) {
    $insertId = $conn->insert_id;
    echo "✅ Test insert successful (ID: $insertId)\n";
    
    // Check if record exists before commit
    $checkBefore = $conn->query("SELECT COUNT(*) as count FROM board_passers WHERE id = $insertId");
    $beforeCount = $checkBefore->fetch_assoc()['count'];
    echo "Records before commit: $beforeCount\n";
    
    // Commit
    $conn->commit();
    echo "✅ Transaction committed\n";
    
    // Check if record exists after commit
    $checkAfter = $conn->query("SELECT COUNT(*) as count FROM board_passers WHERE id = $insertId");
    $afterCount = $checkAfter->fetch_assoc()['count'];
    echo "Records after commit: $afterCount\n";
    
    if ($afterCount == 0) {
        echo "❌ CRITICAL: Record disappeared after commit!\n";
    }
    
} else {
    echo "❌ Test insert failed: " . $conn->error . "\n";
    $conn->rollback();
}

$conn->autocommit(true);
$conn->close();
echo "\nTRANSACTION TEST COMPLETE!\n";
?>
