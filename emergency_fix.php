<?php
echo "EMERGENCY FULL SYSTEM CHECK (MULTI-DEPARTMENT)\n";
echo str_repeat("=", 50) . "\n\n";

// Departments to check
$departments = [
    'Engineering',
    'Arts and Science',
    'Business Administration and Accountancy',
    'Criminal Justice Education',
    'Teacher Education'
];

// 1. Check database connection
$conn = new mysqli('localhost', 'root', '', 'project_db');
if ($conn->connect_error) {
    die("❌ CRITICAL: Database connection failed: " . $conn->connect_error . "\n");
}
echo "✅ Database connection: OK\n";

// 2. Ensure database exists
$dbCheck = $conn->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = 'project_db'");
if (!($dbCheck && $dbCheck->num_rows > 0)) {
    echo "❌ Database 'project_db' missing, creating...\n";
    if ($conn->query("CREATE DATABASE project_db")) {
        echo "✅ Database created\n";
        $conn->select_db('project_db');
    } else {
        die("❌ Failed to create database\n");
    }
} else {
    echo "✅ Database 'project_db': EXISTS\n";
}

// 3. Force create table
echo "\n2. FORCE CREATING TABLE 'board_passers'\n";
$conn->query("DROP TABLE IF EXISTS board_passers");
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
    department VARCHAR(100) NOT NULL,
    exam_type VARCHAR(255) DEFAULT NULL,
    board_exam_type VARCHAR(255) DEFAULT 'Board Exam',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
if ($conn->query($createTable)) echo "✅ Table created successfully\n";

// 4. Insert test data for each department
echo "\n3. INSERTING TEST DATA FOR ALL DEPARTMENTS\n";
$stmt = $conn->prepare("INSERT INTO board_passers (name, sex, course, year_graduated, board_exam_date, result, department, exam_type, board_exam_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

foreach ($departments as $dept) {
    $name = "TEST " . $dept . " " . date('H:i:s');
    $sex = "Male";
    $course = "Sample Course " . $dept;
    $year = 2023;
    $date = "2023-01-01";
    $result = "PASSED";
    $examType = "Test Exam";
    $boardExamType = "Board Exam";
    $stmt->bind_param("sssisssss", $name, $sex, $course, $year, $date, $result, $dept, $examType, $boardExamType);
    if ($stmt->execute()) {
        echo "✅ Inserted for $dept (ID: " . $conn->insert_id . ")\n";
    } else {
        echo "❌ Insert failed for $dept: " . $stmt->error . "\n";
    }
}
$stmt->close();

// 5. Verify dashboard query per department
echo "\n4. DASHBOARD QUERY TEST\n";
foreach ($departments as $dept) {
    $res = $conn->query("SELECT COUNT(*) as count FROM board_passers WHERE department='$dept'");
    $count = $res ? $res->fetch_assoc()['count'] : 0;
    echo "✅ $dept records: $count\n";
}

// 6. Test add board passer functionality for all departments
echo "\n5. ADD BOARD PASSER TEST\n";
$stmtAdd = $conn->prepare("INSERT INTO board_passers (name, sex, course, year_graduated, board_exam_date, result, department, exam_type, board_exam_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
foreach ($departments as $dept) {
    $name = "LIVE ADD TEST " . $dept . " " . date('H:i:s');
    $sex = "Female";
    $course = "Add Course " . $dept;
    $year = 2023;
    $date = "2023-02-01";
    $result = "PASSED";
    $examType = "Add Exam";
    $boardExamType = "Board Exam";
    $stmtAdd->bind_param("sssisssss", $name, $sex, $course, $year, $date, $result, $dept, $examType, $boardExamType);
    if ($stmtAdd->execute()) {
        $newId = $conn->insert_id;
        echo "✅ Add test successful for $dept (ID: $newId)\n";
    } else {
        echo "❌ Add test failed for $dept: " . $stmtAdd->error . "\n";
    }
}
$stmtAdd->close();

// 7. Final count
$resFinal = $conn->query("SELECT COUNT(*) as count FROM board_passers");
$totalFinal = $resFinal ? $resFinal->fetch_assoc()['count'] : 0;
echo "\n✅ FINAL RECORD COUNT: $totalFinal\n";

$conn->close();
echo "\n" . str_repeat("=", 50) . "\nSYSTEM CHECK COMPLETE!\n";
?>