<?php
$conn = new mysqli('localhost', 'root', '', 'project_db');
if ($conn->connect_error) die('Connection failed: ' . $conn->connect_error);

$departments = [
    'Engineering',
    'Arts and Science',
    'Business Administration and Accountancy',
    'Criminal Justice Education',
    'Teacher Education'
];

echo "CRITICAL DATABASE INVESTIGATION\n";
echo str_repeat("=", 40) . "\n\n";

// 1. DATABASE CONNECTION
echo "1. DATABASE CONNECTION:\n";
echo str_repeat("-", 40) . "\n";
echo "✅ Connection successful\n\n";

// 2. TABLE EXISTENCE
echo "2. TABLE EXISTENCE CHECK:\n";
echo str_repeat("-", 40) . "\n";
$tableCheck = $conn->query("SHOW TABLES LIKE 'board_passers'");
if ($tableCheck && $tableCheck->num_rows > 0) {
    echo "✅ board_passers table exists\n";
} else {
    echo "❌ board_passers table NOT FOUND!\n";
    exit;
}

// 3. TABLE STRUCTURE
echo "\n3. TABLE STRUCTURE:\n";
echo str_repeat("-", 40) . "\n";
$structureCheck = $conn->query("DESCRIBE board_passers");
if ($structureCheck) {
    while ($row = $structureCheck->fetch_assoc()) {
        echo "Column: {$row['Field']} | Type: {$row['Type']} | Null: {$row['Null']}\n";
    }
}

// 4. TOTAL RECORD COUNT
echo "\n4. RECORD COUNT:\n";
echo str_repeat("-", 40) . "\n";
$totalCount = $conn->query("SELECT COUNT(*) as count FROM board_passers");
$total = $totalCount ? $totalCount->fetch_assoc()['count'] : 0;
echo "Total records in board_passers: $total\n";

// 5. RECORDS BY DEPARTMENT
echo "\n5. RECORDS BY DEPARTMENT:\n";
echo str_repeat("-", 40) . "\n";
foreach ($departments as $dept) {
    $deptCount = $conn->query("SELECT COUNT(*) as count FROM board_passers WHERE department='$dept'");
    $count = $deptCount ? $deptCount->fetch_assoc()['count'] : 0;
    echo "$dept department records: $count\n";
}

// 6. RECENT RECORDS
echo "\n6. RECENT RECORDS (LAST 5):\n";
echo str_repeat("-", 40) . "\n";
$recentRecords = $conn->query("SELECT id, name, course, department FROM board_passers ORDER BY id DESC LIMIT 5");
if ($recentRecords && $recentRecords->num_rows > 0) {
    while ($row = $recentRecords->fetch_assoc()) {
        echo "ID: {$row['id']} | Name: {$row['name']} | Course: {$row['course']} | Dept: '{$row['department']}'\n";
    }
} else {
    echo "❌ No recent records found\n";
}

// 7. TEST MANUAL INSERT FOR EACH DEPARTMENT
echo "\n7. TESTING MANUAL INSERT (per department):\n";
echo str_repeat("-", 40) . "\n";
foreach ($departments as $dept) {
    $stmt = $conn->prepare("INSERT INTO board_passers (name, sex, course, year_graduated, board_exam_date, result, department, exam_type, board_exam_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $name = "TEST " . date('Y-m-d H:i:s') . " ($dept)";
    $sex = "Male";
    $course = "Test Course";
    $year = 2023;
    $date = "2023-01-01";
    $result = "PASSED";
    $examType = "Test";
    $boardExamType = "Board Exam";
    $stmt->bind_param("sssisssss", $name, $sex, $course, $year, $date, $result, $dept, $examType, $boardExamType);
    if ($stmt->execute()) {
        echo "✅ Insert successful for $dept (ID: {$conn->insert_id})\n";
    } else {
        echo "❌ Insert failed for $dept: " . $stmt->error . "\n";
    }
    $stmt->close();
}

// 8. DATABASE ENGINE INFO
echo "\n8. DATABASE ENGINE INFO:\n";
echo str_repeat("-", 40) . "\n";
$engineInfo = $conn->query("SHOW TABLE STATUS LIKE 'board_passers'");
if ($engineInfo && $engineInfo->num_rows > 0) {
    $engine = $engineInfo->fetch_assoc();
    echo "Engine: {$engine['Engine']}\n";
    echo "Rows: {$engine['Rows']}\n";
    echo "Auto_increment: {$engine['Auto_increment']}\n";
} else {
    echo "Cannot get engine info\n";
}

$conn->close();
echo "\nINVESTIGATION COMPLETE!\n";
?>