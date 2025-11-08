<?php
echo "EMERGENCY FULL SYSTEM CHECK\n";
echo "===========================\n\n";

// 1. Check database connection
$conn = new mysqli('localhost', 'root', '', 'project_db');
if ($conn->connect_error) {
    echo "❌ CRITICAL: Database connection failed: " . $conn->connect_error . "\n";
    die();
} else {
    echo "✅ Database connection: OK\n";
}

// 2. Check if database exists
$dbCheck = $conn->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = 'project_db'");
if ($dbCheck && $dbCheck->num_rows > 0) {
    echo "✅ Database 'project_db': EXISTS\n";
} else {
    echo "❌ Database 'project_db': MISSING\n";
    echo "Creating database...\n";
    if ($conn->query("CREATE DATABASE project_db")) {
        echo "✅ Database created\n";
        $conn->select_db('project_db');
    } else {
        echo "❌ Failed to create database\n";
        die();
    }
}

// 3. Force table creation
echo "\n2. FORCE CREATING TABLE:\n";
echo "------------------------\n";

$dropTable = "DROP TABLE IF EXISTS board_passers";
if ($conn->query($dropTable)) {
    echo "✅ Dropped existing table (if any)\n";
}

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
    echo "✅ Table created successfully\n";
} else {
    echo "❌ Table creation failed: " . $conn->error . "\n";
    die();
}

// 4. Insert test data immediately
echo "\n3. INSERTING TEST DATA:\n";
echo "-----------------------\n";

$testData = [
    ["John Doe Test", "Male", "Computer Engineering", 2023, "2023-05-15", "PASSED", "Engineering", "Board Exam", "Board Exam"],
    ["Jane Smith Test", "Female", "Civil Engineering", 2023, "2023-06-20", "PASSED", "Engineering", "Board Exam", "Board Exam"],
    ["Bob Johnson Test", "Male", "Electrical Engineering", 2023, "2023-07-10", "PASSED", "Engineering", "Board Exam", "Board Exam"]
];

$stmt = $conn->prepare("INSERT INTO board_passers (name, sex, course, year_graduated, board_exam_date, result, department, exam_type, board_exam_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

$inserted = 0;
foreach ($testData as $data) {
    $stmt->bind_param("sssisssss", $data[0], $data[1], $data[2], $data[3], $data[4], $data[5], $data[6], $data[7], $data[8]);
    if ($stmt->execute()) {
        $inserted++;
        echo "✅ Inserted: " . $data[0] . " (ID: " . $conn->insert_id . ")\n";
    } else {
        echo "❌ Failed to insert " . $data[0] . ": " . $stmt->error . "\n";
    }
}
$stmt->close();

// 5. Verify data persistence
echo "\n4. VERIFYING DATA:\n";
echo "------------------\n";
$count = $conn->query("SELECT COUNT(*) as count FROM board_passers");
if ($count) {
    $total = $count->fetch_assoc()['count'];
    echo "✅ Total records: $total\n";
} else {
    echo "❌ Cannot count records: " . $conn->error . "\n";
}

// 6. Test the exact query dashboard uses
echo "\n5. TESTING DASHBOARD QUERY:\n";
echo "---------------------------\n";
$dashQuery = $conn->query("SELECT * FROM board_passers WHERE department='Engineering' ORDER BY name ASC");
if ($dashQuery) {
    echo "✅ Dashboard query works: " . $dashQuery->num_rows . " records found\n";
    
    // Show the actual records
    echo "\nRecords found:\n";
    while ($row = $dashQuery->fetch_assoc()) {
        echo "- ID: " . $row['id'] . " | Name: " . $row['name'] . " | Course: " . $row['course'] . "\n";
    }
} else {
    echo "❌ Dashboard query failed: " . $conn->error . "\n";
}

// 7. Test add board passer functionality
echo "\n6. TESTING ADD FUNCTIONALITY:\n";
echo "-----------------------------\n";
$addStmt = $conn->prepare("INSERT INTO board_passers (name, sex, course, year_graduated, board_exam_date, result, department, exam_type, board_exam_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

$testName = "LIVE TEST " . date('H:i:s');
$testSex = "Male";
$testCourse = "Mechanical Engineering";
$testYear = 2023;
$testDate = "2023-08-15";
$testResult = "PASSED";
$testDept = "Engineering";
$testExamType = "Mechanical Engineer Licensure Exam";
$testBoardType = "Board Exam";

$addStmt->bind_param("sssisssss", $testName, $testSex, $testCourse, $testYear, $testDate, $testResult, $testDept, $testExamType, $testBoardType);

if ($addStmt->execute()) {
    $newId = $conn->insert_id;
    echo "✅ Add test successful (ID: $newId)\n";
    
    // Verify immediately
    $verify = $conn->query("SELECT * FROM board_passers WHERE id = $newId");
    if ($verify && $verify->num_rows > 0) {
        echo "✅ New record verified in database\n";
    } else {
        echo "❌ New record disappeared!\n";
    }
} else {
    echo "❌ Add test failed: " . $addStmt->error . "\n";
}
$addStmt->close();

// 8. Final count
$finalCount = $conn->query("SELECT COUNT(*) as count FROM board_passers");
if ($finalCount) {
    $total = $finalCount->fetch_assoc()['count'];
    echo "\n✅ FINAL COUNT: $total records\n";
} else {
    echo "\n❌ Cannot get final count\n";
}

$conn->close();

echo "\n" . str_repeat("=", 50) . "\n";
echo "SYSTEM CHECK COMPLETE!\n";
echo "Now try refreshing the dashboard and add board passer pages.\n";
echo str_repeat("=", 50) . "\n";
?>
