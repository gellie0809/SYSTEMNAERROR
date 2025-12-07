<?php
$conn = new mysqli('localhost', 'root', '', 'project_db');
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

$departments = [
    'Engineering',
    'Arts and Science',
    'Business Administration and Accountancy',
    'Criminal Justice Education',
    'Teacher Education'
];

echo "EMERGENCY DATABASE INVESTIGATION\n";
echo str_repeat("=", 40) . "\n\n";

// 1. ALL RECORDS IN board_passers TABLE
echo "1. ALL RECORDS IN board_passers TABLE:\n";
echo str_repeat("-", 40) . "\n";
$result = $conn->query("SELECT id, name, course, department, board_exam_date, result FROM board_passers ORDER BY id");
if ($result && $result->num_rows > 0) {
    echo "Total records found: " . $result->num_rows . "\n\n";
    while ($row = $result->fetch_assoc()) {
        echo "ID: " . $row['id'] . " | Name: " . $row['name'] . " | Course: " . $row['course'] . " | Dept: '" . $row['department'] . "' | Date: " . $row['board_exam_date'] . " | Result: " . $row['result'] . "\n";
    }
} else {
    echo "❌ NO RECORDS FOUND IN THE ENTIRE TABLE!\n";
}

echo "\n2. CHECKING BY DEPARTMENT:\n";
echo str_repeat("-", 40) . "\n";
foreach ($departments as $dept) {
    $deptResult = $conn->query("SELECT COUNT(*) as count FROM board_passers WHERE department='$dept'");
    if ($deptResult) {
        $row = $deptResult->fetch_assoc();
        echo "$dept department records: " . $row['count'] . "\n";
    }
}

// 3. ALL DEPARTMENT VALUES
echo "\n3. ALL DEPARTMENT VALUES:\n";
echo str_repeat("-", 40) . "\n";
$allDeptResult = $conn->query("SELECT department, COUNT(*) as count FROM board_passers GROUP BY department");
if ($allDeptResult && $allDeptResult->num_rows > 0) {
    while ($row = $allDeptResult->fetch_assoc()) {
        echo "Department: '" . $row['department'] . "' - Count: " . $row['count'] . "\n";
    }
} else {
    echo "No department groups found\n";
}

// 4. TABLE STRUCTURE
echo "\n4. CHECKING TABLE STRUCTURE:\n";
echo str_repeat("-", 40) . "\n";
$structure = $conn->query("DESCRIBE board_passers");
if ($structure) {
    while ($row = $structure->fetch_assoc()) {
        echo "Column: " . $row['Field'] . " | Type: " . $row['Type'] . " | Null: " . $row['Null'] . " | Key: " . $row['Key'] . "\n";
    }
}

// 5. RECENT ACTIVITY CHECK
echo "\n5. RECENT ACTIVITY CHECK:\n";
echo str_repeat("-", 40) . "\n";
$recentCheck = $conn->query("SELECT * FROM board_passers ORDER BY id DESC LIMIT 5");
if ($recentCheck && $recentCheck->num_rows > 0) {
    echo "Last 5 records:\n";
    while ($row = $recentCheck->fetch_assoc()) {
        echo "ID: " . $row['id'] . " | Name: " . $row['name'] . " | Department: '" . $row['department'] . "'\n";
    }
} else {
    echo "No recent records found\n";
}

$conn->close();
echo "\nINVESTIGATION COMPLETE!\n";
?>