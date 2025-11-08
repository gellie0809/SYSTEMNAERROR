<?php
$conn = new mysqli('localhost', 'root', '', 'project_db');
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

echo "REAL-TIME MONITORING\n";
echo "===================\n";

// Count before
$before = $conn->query("SELECT COUNT(*) as count FROM board_passers WHERE department = 'Engineering'");
$beforeCount = $before->fetch_assoc()['count'];
echo "Records BEFORE test insert: $beforeCount\n";

// Insert a test record
$stmt = $conn->prepare("INSERT INTO board_passers (name, sex, course, year_graduated, board_exam_date, result, department, exam_type, board_exam_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

$name = "MONITOR TEST " . date('H:i:s');
$sex = "Male";
$course = "Civil Engineering";
$year = 2023;
$date = "2023-05-15";
$result = "PASSED";
$dept = "Engineering";
$examType = "Board Exam";
$boardExamType = "Board Exam";

$stmt->bind_param("sssisssss", $name, $sex, $course, $year, $date, $result, $dept, $examType, $boardExamType);

if ($stmt->execute()) {
    $insertId = $conn->insert_id;
    echo "✅ Insert successful! ID: $insertId\n";
    
    // Count immediately after
    $after = $conn->query("SELECT COUNT(*) as count FROM board_passers WHERE department = 'Engineering'");
    $afterCount = $after->fetch_assoc()['count'];
    echo "Records AFTER insert: $afterCount\n";
    
    // Wait 2 seconds and check again
    sleep(2);
    $delayed = $conn->query("SELECT COUNT(*) as count FROM board_passers WHERE department = 'Engineering'");
    $delayedCount = $delayed->fetch_assoc()['count'];
    echo "Records AFTER 2 seconds: $delayedCount\n";
    
    // Check if our specific record still exists
    $checkSpecific = $conn->query("SELECT * FROM board_passers WHERE id = $insertId");
    if ($checkSpecific && $checkSpecific->num_rows > 0) {
        echo "✅ Our test record still exists\n";
    } else {
        echo "❌ Our test record DISAPPEARED!\n";
    }
    
} else {
    echo "❌ Insert failed: " . $stmt->error . "\n";
}

$stmt->close();
$conn->close();
?>
