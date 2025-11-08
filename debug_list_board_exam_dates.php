<?php
// Quick debug script - prints board_exam_dates joined with board_exam_types as JSON
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
$sql = "SELECT bed.id, bed.exam_date, bed.exam_description, bed.exam_type_id, IFNULL(bet.exam_type_name,'') AS exam_type_name, bed.department FROM board_exam_dates bed LEFT JOIN board_exam_types bet ON bed.exam_type_id = bet.id WHERE bed.department = 'Engineering' ORDER BY bed.exam_date DESC";
$res = $conn->query($sql);
$out = [];
if ($res) {
    while ($r = $res->fetch_assoc()) {
        $out[] = $r;
    }
}
header('Content-Type: application/json');
echo json_encode($out, JSON_PRETTY_PRINT);
$conn->close();
