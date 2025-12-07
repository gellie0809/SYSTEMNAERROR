<?php
$conn = new mysqli('localhost', 'root', '', 'project_db');

/* ===============================
   ENGINEERING
   ===============================*/
echo "Checking all result values in Engineering department:\n";
echo "===================================================\n";

$result = $conn->query("SELECT id, name, result FROM board_passers WHERE department='Engineering' ORDER BY id");
while ($row = $result->fetch_assoc()) {
    echo "ID: " . $row['id'] . " | Name: " . $row['name'] . " | Result: '" . $row['result'] . "'\n";
}

echo "\nUnique result values count:\n";
echo "==========================\n";
$result = $conn->query("SELECT result, COUNT(*) as count FROM board_passers WHERE department='Engineering' GROUP BY result");
while ($row = $result->fetch_assoc()) {
    echo "Result: '" . $row['result'] . "' - Count: " . $row['count'] . "\n";
}

echo "\n\n";


/* ===============================
   ARTS AND SCIENCE
   ===============================*/
echo "Checking all result values in Arts and Science department:\n";
echo "===================================================\n";

$result = $conn->query("SELECT id, name, result FROM board_passers WHERE department='Arts and Science' ORDER BY id");
while ($row = $result->fetch_assoc()) {
    echo "ID: " . $row['id'] . " | Name: " . $row['name'] . " | Result: '" . $row['result'] . "'\n";
}

echo "\nUnique result values count:\n";
echo "==========================\n";
$result = $conn->query("SELECT result, COUNT(*) as count FROM board_passers WHERE department='Arts and Science' GROUP BY result");
while ($row = $result->fetch_assoc()) {
    echo "Result: '" . $row['result'] . "' - Count: " . $row['count'] . "\n";
}

echo "\n\n";


/* ===============================
   BUSINESS ADMINISTRATION AND ACCOUNTANCY
   ===============================*/
echo "Checking all result values in Business Administration and Accountancy department:\n";
echo "===================================================\n";

$result = $conn->query("SELECT id, name, result FROM board_passers WHERE department='Business Administration and Accountancy' ORDER BY id");
while ($row = $result->fetch_assoc()) {
    echo "ID: " . $row['id'] . " | Name: " . $row['name'] . " | Result: '" . $row['result'] . "'\n";
}

echo "\nUnique result values count:\n";
echo "==========================\n";
$result = $conn->query("SELECT result, COUNT(*) as count FROM board_passers WHERE department='Business Administration and Accountancy' GROUP BY result");
while ($row = $result->fetch_assoc()) {
    echo "Result: '" . $row['result'] . "' - Count: " . $row['count'] . "\n";
}

echo "\n\n";


/* ===============================
   CRIMINAL JUSTICE EDUCATION
   ===============================*/
echo "Checking all result values in Criminal Justice Education department:\n";
echo "===================================================\n";

$result = $conn->query("SELECT id, name, result FROM board_passers WHERE department='Criminal Justice Education' ORDER BY id");
while ($row = $result->fetch_assoc()) {
    echo "ID: " . $row['id'] . " | Name: " . $row['name'] . " | Result: '" . $row['result'] . "'\n";
}

echo "\nUnique result values count:\n";
echo "==========================\n";
$result = $conn->query("SELECT result, COUNT(*) as count FROM board_passers WHERE department='Criminal Justice Education' GROUP BY result");
while ($row = $result->fetch_assoc()) {
    echo "Result: '" . $row['result'] . "' - Count: " . $row['count'] . "\n";
}

echo "\n\n";


/* ===============================
   TEACHER EDUCATION
   ===============================*/
echo "Checking all result values in Teacher Education department:\n";
echo "===================================================\n";

$result = $conn->query("SELECT id, name, result FROM board_passers WHERE department='Teacher Education' ORDER BY id");
while ($row = $result->fetch_assoc()) {
    echo "ID: " . $row['id'] . " | Name: " . $row['name'] . " | Result: '" . $row['result'] . "'\n";
}

echo "\nUnique result values count:\n";
echo "==========================\n";
$result = $conn->query("SELECT result, COUNT(*) as count FROM board_passers WHERE department='Teacher Education' GROUP BY result");
while ($row = $result->fetch_assoc()) {
    echo "Result: '" . $row['result'] . "' - Count: " . $row['count'] . "\n";
}

$conn->close();
?>