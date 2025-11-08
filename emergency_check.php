<?php
$conn = new mysqli('localhost', 'root', '', 'project_db');
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

echo "EMERGENCY DATABASE INVESTIGATION\n";
echo "================================\n\n";

// Check all records in board_passers table
echo "1. ALL RECORDS IN board_passers TABLE:\n";
echo "--------------------------------------\n";
$result = $conn->query("SELECT id, name, course, department, board_exam_date, result FROM board_passers ORDER BY id");
if ($result && $result->num_rows > 0) {
    echo "Total records found: " . $result->num_rows . "\n\n";
    while ($row = $result->fetch_assoc()) {
        echo "ID: " . $row['id'] . " | Name: " . $row['name'] . " | Course: " . $row['course'] . " | Dept: '" . $row['department'] . "' | Date: " . $row['board_exam_date'] . " | Result: " . $row['result'] . "\n";
    }
} else {
    echo "❌ NO RECORDS FOUND IN THE ENTIRE TABLE!\n";
}

echo "\n\n2. CHECKING BY DEPARTMENT:\n";
echo "-------------------------\n";

// Check Engineering department specifically
$engResult = $conn->query("SELECT COUNT(*) as count FROM board_passers WHERE department = 'Engineering'");
if ($engResult) {
    $row = $engResult->fetch_assoc();
    echo "Engineering department records: " . $row['count'] . "\n";
}

// Check College of Engineering department
$collegeResult = $conn->query("SELECT COUNT(*) as count FROM board_passers WHERE department = 'College of Engineering'");
if ($collegeResult) {
    $row = $collegeResult->fetch_assoc();
    echo "College of Engineering department records: " . $row['count'] . "\n";
}

// Check all department values
echo "\n3. ALL DEPARTMENT VALUES:\n";
echo "------------------------\n";
$deptResult = $conn->query("SELECT department, COUNT(*) as count FROM board_passers GROUP BY department");
if ($deptResult && $deptResult->num_rows > 0) {
    while ($row = $deptResult->fetch_assoc()) {
        echo "Department: '" . $row['department'] . "' - Count: " . $row['count'] . "\n";
    }
} else {
    echo "No department groups found\n";
}

echo "\n\n4. CHECKING TABLE STRUCTURE:\n";
echo "----------------------------\n";
$structure = $conn->query("DESCRIBE board_passers");
if ($structure) {
    while ($row = $structure->fetch_assoc()) {
        echo "Column: " . $row['Field'] . " | Type: " . $row['Type'] . " | Null: " . $row['Null'] . " | Key: " . $row['Key'] . "\n";
    }
}

echo "\n\n5. RECENT ACTIVITY CHECK:\n";
echo "------------------------\n";
// Check if there are any records with recent timestamps (if there's a timestamp column)
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
echo "\n\nINVESTIGATION COMPLETE!\n";
?>
