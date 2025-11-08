<?php
// Diagnostic endpoint: lists distinct department values and counts to help align filters.
// Access is restricted to localhost for safety.
header('Content-Type: application/json');

require_once __DIR__ . '/db_config.php';

// Allow only local access (127.0.0.1 / ::1)
$remote = $_SERVER['REMOTE_ADDR'] ?? '';
if (!in_array($remote, ['127.0.0.1', '::1'])) {
  http_response_code(403);
  echo json_encode(['success' => false, 'error' => 'Forbidden']);
  exit;
}

try {
  $conn = getDbConnection();

  // Detect columns
  $hasDepartment = false; $hasResult = false; $hasExamType = false; $hasBoardExamType = false;
  if ($res = $conn->query("SHOW COLUMNS FROM board_passers LIKE 'department'")) { $hasDepartment = ($res->num_rows > 0); $res->close(); }
  if ($res = $conn->query("SHOW COLUMNS FROM board_passers LIKE 'result'")) { $hasResult = ($res->num_rows > 0); $res->close(); }
  if ($res = $conn->query("SHOW COLUMNS FROM board_passers LIKE 'exam_type'")) { $hasExamType = ($res->num_rows > 0); $res->close(); }
  if ($res = $conn->query("SHOW COLUMNS FROM board_passers LIKE 'board_exam_type'")) { $hasBoardExamType = ($res->num_rows > 0); $res->close(); }

  if (!$hasDepartment) {
    echo json_encode([
      'success' => false,
      'error' => "Column 'department' not found in board_passers",
      'columns' => [
        'department' => $hasDepartment,
        'result' => $hasResult,
        'exam_type' => $hasExamType,
        'board_exam_type' => $hasBoardExamType
      ]
    ]);
    exit;
  }

  // Distinct departments with counts
  $rows = [];
  $sql = "SELECT department, COUNT(*) AS cnt FROM board_passers GROUP BY department ORDER BY cnt DESC";
  if ($r = $conn->query($sql)) {
    while ($row = $r->fetch_assoc()) {
      $rows[] = [ 'department' => (string)$row['department'], 'count' => (int)$row['cnt'] ];
    }
    $r->close();
  }

  // Distinct result values (no PII)
  $results = [];
  if ($hasResult) {
    if ($r = $conn->query("SELECT result, COUNT(*) AS cnt FROM board_passers GROUP BY result ORDER BY cnt DESC")) {
      while ($row = $r->fetch_assoc()) { $results[] = [ 'result' => (string)$row['result'], 'count' => (int)$row['cnt'] ]; }
      $r->close();
    }
  }

  // Distinct exam_type values (attempts) and board_exam_type labels
  $examTypes = [];
  if ($hasExamType) {
    if ($r = $conn->query("SELECT exam_type, COUNT(*) AS cnt FROM board_passers GROUP BY exam_type ORDER BY cnt DESC LIMIT 50")) {
      while ($row = $r->fetch_assoc()) { $examTypes[] = [ 'exam_type' => (string)$row['exam_type'], 'count' => (int)$row['cnt'] ]; }
      $r->close();
    }
  }
  $boardExamTypes = [];
  if ($hasBoardExamType) {
    if ($r = $conn->query("SELECT board_exam_type, COUNT(*) AS cnt FROM board_passers GROUP BY board_exam_type ORDER BY cnt DESC LIMIT 50")) {
      while ($row = $r->fetch_assoc()) { $boardExamTypes[] = [ 'board_exam_type' => (string)$row['board_exam_type'], 'count' => (int)$row['cnt'] ]; }
      $r->close();
    }
  }

  echo json_encode([
    'success' => true,
    'columns' => [
      'department' => $hasDepartment,
      'result' => $hasResult,
      'exam_type' => $hasExamType,
      'board_exam_type' => $hasBoardExamType
    ],
    'departments' => $rows,
    'result_values' => $results,
    'exam_type_values' => $examTypes,
    'board_exam_type_values' => $boardExamTypes
  ]);
  exit;

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['success' => false, 'error' => 'Server error', 'details' => $e->getMessage() ]);
  exit;
}
