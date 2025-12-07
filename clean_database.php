<?php
$conn = new mysqli('localhost', 'root', '', 'project_db');
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

echo "Cleaning up test data...\n";

/* ======================================================
   ENGINEERING
   ====================================================== */
echo "\n--- Engineering ---\n";
$deleteResult = $conn->query("DELETE FROM board_passers WHERE department = 'Engineering'");

if ($deleteResult) {
    $affected = $conn->affected_rows;
    echo "Deleted $affected Engineering records from database\n";
} else {
    echo "Error deleting records: " . $conn->error . "\n";
}

$result = $conn->query("SELECT COUNT(*) as count FROM board_passers WHERE department = 'Engineering'");
if ($result) {
    $row = $result->fetch_assoc();
    echo "Remaining Engineering records: " . $row['count'] . "\n";
}


/* ======================================================
   ARTS AND SCIENCE
   ====================================================== */
echo "\n--- Arts and Science ---\n";
$deleteResult2 = $conn->query("DELETE FROM board_passers WHERE department = 'Arts and Science'");

if ($deleteResult2) {
    $affected = $conn->affected_rows;
    echo "Deleted $affected Arts and Science records from database\n";
} else {
    echo "Error deleting records: " . $conn->error . "\n";
}

$result2 = $conn->query("SELECT COUNT(*) as count FROM board_passers WHERE department = 'Arts and Science'");
if ($result2) {
    $row = $result2->fetch_assoc();
    echo "Remaining Arts and Science records: " . $row['count'] . "\n";
}


/* ======================================================
   BUSINESS ADMINISTRATION AND ACCOUNTANCY
   ====================================================== */
echo "\n--- Business Administration and Accountancy ---\n";
$deleteResult3 = $conn->query("DELETE FROM board_passers WHERE department = 'Business Administration and Accountancy'");

if ($deleteResult3) {
    $affected = $conn->affected_rows;
    echo "Deleted $affected Business Administration and Accountancy records from database\n";
} else {
    echo "Error deleting records: " . $conn->error . "\n";
}

$result3 = $conn->query("SELECT COUNT(*) as count FROM board_passers WHERE department = 'Business Administration and Accountancy'");
if ($result3) {
    $row = $result3->fetch_assoc();
    echo "Remaining Business Administration and Accountancy records: " . $row['count'] . "\n";
}


/* ======================================================
   CRIMINAL JUSTICE EDUCATION
   ====================================================== */
echo "\n--- Criminal Justice Education ---\n";
$deleteResult4 = $conn->query("DELETE FROM board_passers WHERE department = 'Criminal Justice Education'");

if ($deleteResult4) {
    $affected = $conn->affected_rows;
    echo "Deleted $affected Criminal Justice Education records from database\n";
} else {
    echo "Error deleting records: " . $conn->error . "\n";
}

$result4 = $conn->query("SELECT COUNT(*) as count FROM board_passers WHERE department = 'Criminal Justice Education'");
if ($result4) {
    $row = $result4->fetch_assoc();
    echo "Remaining Criminal Justice Education records: " . $row['count'] . "\n";
}


/* ======================================================
   TEACHER EDUCATION
   ====================================================== */
echo "\n--- Teacher Education ---\n";
$deleteResult5 = $conn->query("DELETE FROM board_passers WHERE department = 'Teacher Education'");

if ($deleteResult5) {
    $affected = $conn->affected_rows;
    echo "Deleted $affected Teacher Education records from database\n";
} else {
    echo "Error deleting records: " . $conn->error . "\n";
}

$result5 = $conn->query("SELECT COUNT(*) as count FROM board_passers WHERE department = 'Teacher Education'");
if ($result5) {
    $row = $result5->fetch_assoc();
    echo "Remaining Teacher Education records: " . $row['count'] . "\n";
}


$conn->close();
echo "\nDatabase cleaned. You can now try importing again.\n";
?>