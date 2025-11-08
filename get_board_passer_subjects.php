<?php
header('Content-Type: application/json; charset=utf-8');
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "project_db";
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
  http_response_code(500);
  echo json_encode(['error' => 'DB connect failed']);
  exit;
}

$pid = intval($_GET['board_passer_id'] ?? 0);
if ($pid <= 0) { echo json_encode([]); exit; }


$colCheck = $conn->query("SHOW COLUMNS FROM board_passer_subjects LIKE 'subject_id'");
$has_subject_id = ($colCheck && $colCheck->num_rows > 0);

// detect whether the table stores textual 'result' or numeric 'passed'
$colRes = $conn->query("SHOW COLUMNS FROM board_passer_subjects LIKE 'result'");
$colPassed = $conn->query("SHOW COLUMNS FROM board_passer_subjects LIKE 'passed'");
$has_result = ($colRes && $colRes->num_rows > 0);
$has_passed = ($colPassed && $colPassed->num_rows > 0);

if ($has_subject_id) {
  if ($has_result) {
    $sql = "SELECT id, subject_id, grade, result FROM board_passer_subjects WHERE board_passer_id = " . intval($pid) . " ORDER BY id ASC";
  } elseif ($has_passed) {
    // normalize numeric passed -> textual result for the client (1 -> Passed, 0 -> Failed)
    $sql = "SELECT id, subject_id, grade, CASE WHEN passed IN ('1',1) THEN 'Passed' WHEN passed IN ('0',0) THEN 'Failed' ELSE '' END AS result FROM board_passer_subjects WHERE board_passer_id = " . intval($pid) . " ORDER BY id ASC";
  } else {
    $sql = "SELECT id, subject_id, grade, '' AS result FROM board_passer_subjects WHERE board_passer_id = " . intval($pid) . " ORDER BY id ASC";
  }
} else {
  // older schema - return rows without subject id
  if ($has_result) {
    $sql = "SELECT id, NULL as subject_id, grade, result FROM board_passer_subjects WHERE board_passer_id = " . intval($pid) . " ORDER BY id ASC";
  } elseif ($has_passed) {
    $sql = "SELECT id, NULL as subject_id, grade, CASE WHEN passed IN ('1',1) THEN 'Passed' WHEN passed IN ('0',0) THEN 'Failed' ELSE '' END AS result FROM board_passer_subjects WHERE board_passer_id = " . intval($pid) . " ORDER BY id ASC";
  } else {
    $sql = "SELECT id, NULL as subject_id, grade, '' AS result FROM board_passer_subjects WHERE board_passer_id = " . intval($pid) . " ORDER BY id ASC";
  }
}

$out = [];
$res = $conn->query($sql);
if ($res) {
  while ($r = $res->fetch_assoc()) $out[] = $r;
}

echo json_encode($out);
$conn->close();
?>
