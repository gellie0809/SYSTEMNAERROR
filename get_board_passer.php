<?php
// Always send JSON and log errors instead of printing them
header('Content-Type: application/json');
ini_set('display_errors', 0);
error_reporting(E_ALL);
session_start();
if (!isset($_SESSION['users']) || $_SESSION['users'] !== 'eng_admin@lspu.edu.ph') {
  http_response_code(403);
  echo json_encode(['success'=>false,'message'=>'Unauthorized']);
  exit();
}

$conn = new mysqli('localhost','root','','project_db');
if ($conn->connect_error) { http_response_code(500); echo json_encode(['success'=>false,'message'=>'DB connect error']); exit(); }

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) { echo json_encode(['success'=>false,'message'=>'Invalid id']); exit(); }

// Determine available columns and only select those (schema can vary across installs)
$desired = ['id','first_name','middle_name','last_name','suffix','sex','course','year_graduated','board_exam_date','exam_type','board_exam_type','result','rating'];
$available = [];
$colRes = $conn->query("SHOW COLUMNS FROM board_passers");
if ($colRes) {
  while ($c = $colRes->fetch_assoc()) { $available[strtolower($c['Field'])] = true; }
  $colRes->free();
}
$colsToSelect = [];
foreach ($desired as $d) { if (isset($available[strtolower($d)])) { $colsToSelect[] = $d; } }
// id is mandatory
if (!in_array('id', $colsToSelect, true)) { $colsToSelect[] = 'id'; }
$colSql = implode(', ', $colsToSelect);

$sql = "SELECT $colSql FROM board_passers WHERE id = " . intval($id) . " AND department = 'Engineering' LIMIT 1";
$res = $conn->query($sql);
if (!$res) { http_response_code(500); echo json_encode(['success'=>false,'message'=>'Query failed','error'=>$conn->error]); exit(); }
if ($res->num_rows === 0) { echo json_encode(['success'=>false,'message'=>'Not found']); $res->free(); exit(); }
$row = $res->fetch_assoc();
$res->free();

// Normalize keys: ensure expected keys exist
$defaults = [
  'first_name'=>'','middle_name'=>'','last_name'=>'','suffix'=>'','sex'=>'','course'=>'','year_graduated'=>'','board_exam_date'=>'','exam_type'=>'','board_exam_type'=>'','result'=>'','rating'=>''
];
foreach ($defaults as $k=>$v) { if (!array_key_exists($k,$row)) { $row[$k] = $v; } }

$subjects = [];
$qr = $conn->query("SELECT * FROM board_passer_subjects WHERE board_passer_id = " . intval($id));
if ($qr) { while ($r = $qr->fetch_assoc()) { $subjects[] = $r; } $qr->free(); }

echo json_encode(['success'=>true,'data'=>$row,'subjects'=>$subjects]);

$conn->close();
?>
