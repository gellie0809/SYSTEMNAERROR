<?php
$conn = new mysqli('localhost', 'root', '', 'project_db');
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

echo "TESTING MANUAL INSERT\n";
echo "====================\n";

// Test inserting a record manually to see if it persists
$testInsert = $conn->prepare("INSERT INTO board_passers (name, sex, course, year_graduated, board_exam_date, result, department, exam_type, board_exam_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

$name = "Test User Manual";
$sex = "Male";
$course = "Test Engineering";
$year = 2023;
$date = "2023-01-01";
$result = "PASSED";
$dept = "Engineering";
$examType = "Test Exam";
$boardExamType = "Board Exam";

$testInsert->bind_param("sssisssss", $name, $sex, $course, $year, $date, $result, $dept, $examType, $boardExamType);

if ($testInsert->execute()) {
    $insertId = $conn->insert_id;
    echo "✅ Manual insert successful! Insert ID: $insertId\n";
    
    // Immediately check if it's there
    $checkResult = $conn->query("SELECT * FROM board_passers WHERE id = $insertId");
    if ($checkResult && $checkResult->num_rows > 0) {
        $row = $checkResult->fetch_assoc();
        echo "✅ Record found after insert: " . $row['name'] . " (Department: '" . $row['department'] . "')\n";
    } else {
        echo "❌ Record NOT found immediately after insert!\n";
    }
    
    // Check total count
    $countResult = $conn->query("SELECT COUNT(*) as count FROM board_passers WHERE department = 'Engineering'");
    if ($countResult) {
        $countRow = $countResult->fetch_assoc();
        echo "✅ Total Engineering records now: " . $countRow['count'] . "\n";
    }
    
} else {
    echo "❌ Manual insert failed: " . $testInsert->error . "\n";
}

$testInsert->close();

// Test if autocommit is enabled
echo "\n\nCHECKING DATABASE SETTINGS:\n";
echo "===========================\n";
$autocommitResult = $conn->query("SELECT @@autocommit");
if ($autocommitResult) {
    $autocommitRow = $autocommitResult->fetch_assoc();
    echo "Autocommit status: " . $autocommitRow['@@autocommit'] . " (1 = enabled, 0 = disabled)\n";
}

$conn->close();
echo "\nTest completed!\n";
?>
