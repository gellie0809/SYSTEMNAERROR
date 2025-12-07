<?php
// Quick debug script - prints board_exam_dates joined with board_exam_types as JSON per department
error_reporting(E_ALL);
ini_set('display_errors', 1);

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "project_db";
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    echo json_encode(['error' => 'Connection failed: ' . $conn->connect_error]);
    exit;
}

$departments = [
    'Engineering',
    'Arts and Science',
    'Business Administration and Accountancy',
    'Criminal Justice Education',
    'Teacher Education'
];

header('Content-Type: application/json');

$all_departments_data = [];

foreach ($departments as $dept) {
    $sql = "SELECT bed.id, bed.exam_date, bed.exam_description, bed.exam_type_id, 
                   IFNULL(bet.exam_type_name,'') AS exam_type_name, bed.department
            FROM board_exam_dates bed
            LEFT JOIN board_exam_types bet ON bed.exam_type_id = bet.id
            WHERE bed.department = '$dept'
            ORDER BY bed.exam_date DESC";

    $res = $conn->query($sql);
    $out = [];

    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $out[] = $r;
        }
    }

    $all_departments_data[$dept] = $out;
}

echo json_encode($all_departments_data, JSON_PRETTY_PRINT);
$conn->close();
?>