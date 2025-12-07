<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "project_db";
$conn = new mysqli($servername, $username, $password, $dbname);


/* ======================================================
   ENGINEERING
   ====================================================== */
echo "Deleting records with course = '0' (Engineering)...\n";

$result = $conn->query("DELETE FROM board_passers WHERE course = '0' AND department='Engineering'");

if ($result) {
    echo "Successfully deleted " . $conn->affected_rows . " records with course = '0' (Engineering)\n";
} else {
    echo "Error deleting records: " . $conn->error . "\n";
}

echo "\nRemaining records after cleanup (Engineering):\n";
echo "=================================\n";
$result = $conn->query("SELECT * FROM board_passers WHERE department='Engineering' ORDER BY id ASC");
while ($row = $result->fetch_assoc()) {
    echo "ID: " . $row['id'] . " - Name: " . $row['name'] . " - Course: " . $row['course'] . "\n";
}

echo "\n------------------------------------------\n\n";


/* ======================================================
   ARTS AND SCIENCE
   ====================================================== */
echo "Deleting records with course = '0' (Arts and Science)...\n";

$result2 = $conn->query("DELETE FROM board_passers WHERE course = '0' AND department='Arts and Science'");

if ($result2) {
    echo "Successfully deleted " . $conn->affected_rows . " records with course = '0' (Arts and Science)\n";
} else {
    echo "Error deleting records: " . $conn->error . "\n";
}

echo "\nRemaining records after cleanup (Arts and Science):\n";
echo "=================================\n";
$result2 = $conn->query("SELECT * FROM board_passers WHERE department='Arts and Science' ORDER BY id ASC");
while ($row = $result2->fetch_assoc()) {
    echo "ID: " . $row['id'] . " - Name: " . $row['name'] . " - Course: " . $row['course'] . "\n";
}

echo "\n------------------------------------------\n\n";


/* ======================================================
   BUSINESS ADMINISTRATION AND ACCOUNTANCY
   ====================================================== */
echo "Deleting records with course = '0' (Business Administration and Accountancy)...\n";

$result3 = $conn->query("DELETE FROM board_passers WHERE course = '0' AND department='Business Administration and Accountancy'");

if ($result3) {
    echo "Successfully deleted " . $conn->affected_rows . " records with course = '0' (Business Administration and Accountancy)\n";
} else {
    echo "Error deleting records: " . $conn->error . "\n";
}

echo "\nRemaining records after cleanup (Business Administration and Accountancy):\n";
echo "=================================\n";
$result3 = $conn->query("SELECT * FROM board_passers WHERE department='Business Administration and Accountancy' ORDER BY id ASC");
while ($row = $result3->fetch_assoc()) {
    echo "ID: " . $row['id'] . " - Name: " . $row['name'] . " - Course: " . $row['course'] . "\n";
}

echo "\n------------------------------------------\n\n";


/* ======================================================
   CRIMINAL JUSTICE EDUCATION
   ====================================================== */
echo "Deleting records with course = '0' (Criminal Justice Education)...\n";

$result4 = $conn->query("DELETE FROM board_passers WHERE course = '0' AND department='Criminal Justice Education'");

if ($result4) {
    echo "Successfully deleted " . $conn->affected_rows . " records with course = '0' (Criminal Justice Education)\n";
} else {
    echo "Error deleting records: " . $conn->error . "\n";
}

echo "\nRemaining records after cleanup (Criminal Justice Education):\n";
echo "=================================\n";
$result4 = $conn->query("SELECT * FROM board_passers WHERE department='Criminal Justice Education' ORDER BY id ASC");
while ($row = $result4->fetch_assoc()) {
    echo "ID: " . $row['id'] . " - Name: " . $row['name'] . " - Course: " . $row['course'] . "\n";
}

echo "\n------------------------------------------\n\n";


/* ======================================================
   TEACHER EDUCATION
   ====================================================== */
echo "Deleting records with course = '0' (Teacher Education)...\n";

$result5 = $conn->query("DELETE FROM board_passers WHERE course = '0' AND department='Teacher Education'");

if ($result5) {
    echo "Successfully deleted " . $conn->affected_rows . " records with course = '0' (Teacher Education)\n";
} else {
    echo "Error deleting records: " . $conn->error . "\n";
}

echo "\nRemaining records after cleanup (Teacher Education):\n";
echo "=================================\n";
$result5 = $conn->query("SELECT * FROM board_passers WHERE department='Teacher Education' ORDER BY id ASC");
while ($row = $result5->fetch_assoc()) {
    echo "ID: " . $row['id'] . " - Name: " . $row['name'] . " - Course: " . $row['course'] . "\n";
}

$conn->close();
?>