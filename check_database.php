<?php
$conn = new mysqli('localhost', 'root', '', 'project_db');
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

echo "Checking department values in board_passers table:\n";
$result = $conn->query('SELECT department, COUNT(*) as count FROM board_passers GROUP BY department');
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo 'Department: "' . $row['department'] . '" - Count: ' . $row['count'] . "\n";
    }
} else {
    echo "No records found in board_passers table\n";
}

echo "\nChecking for records with 'College of Engineering' department:\n";
$result2 = $conn->query("SELECT COUNT(*) as count FROM board_passers WHERE department = 'College of Engineering'");
if ($result2) {
    $row = $result2->fetch_assoc();
    echo "Records with 'College of Engineering': " . $row['count'] . "\n";
}

echo "\nChecking for records with 'Engineering' department:\n";
$result3 = $conn->query("SELECT COUNT(*) as count FROM board_passers WHERE department = 'Engineering'");
if ($result3) {
    $row = $result3->fetch_assoc();
    echo "Records with 'Engineering': " . $row['count'] . "\n";
}

/* ==========================================================
   EXTRA CHECKS (ADDED DEPARTMENTS – SAME FORMAT)
   ========================================================== */

echo "\nChecking for records with 'Arts and Science' department:\n";
$res4 = $conn->query("SELECT COUNT(*) as count FROM board_passers WHERE department = 'Arts and Science'");
if ($res4) {
    $row = $res4->fetch_assoc();
    echo "Records with 'Arts and Science': " . $row['count'] . "\n";
}

echo "\nChecking for records with 'Business Administration and Accountancy' department:\n";
$res5 = $conn->query("SELECT COUNT(*) as count FROM board_passers WHERE department = 'Business Administration and Accountancy'");
if ($res5) {
    $row = $res5->fetch_assoc();
    echo "Records with 'Business Administration and Accountancy': " . $row['count'] . "\n";
}

echo "\nChecking for records with 'Criminal Justice Education' department:\n";
$res6 = $conn->query("SELECT COUNT(*) as count FROM board_passers WHERE department = 'Criminal Justice Education'");
if ($res6) {
    $row = $res6->fetch_assoc();
    echo "Records with 'Criminal Justice Education': " . $row['count'] . "\n";
}

echo "\nChecking for records with 'Teacher Education' department:\n";
$res7 = $conn->query("SELECT COUNT(*) as count FROM board_passers WHERE department = 'Teacher Education'");
if ($res7) {
    $row = $res7->fetch_assoc();
    echo "Records with 'Teacher Education': " . $row['count'] . "\n";
}


/* ==========================================================
   SAMPLE RECORDS (UNCHANGED)
   ========================================================== */

echo "\nSample records from board_passers table:\n";
$result4 = $conn->query("SELECT name, department FROM board_passers LIMIT 5");
if ($result4 && $result4->num_rows > 0) {
    while ($row = $result4->fetch_assoc()) {
        echo "Name: " . $row['name'] . " - Department: '" . $row['department'] . "'\n";
    }
} else {
    echo "No sample records found\n";
}

$conn->close();
?>