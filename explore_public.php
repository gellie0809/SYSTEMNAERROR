<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db_config.php';
$conn = getDbConnection();

$allowedDepts = [
  'Engineering' => 'College of Engineering',
  'Arts and Science' => 'College of Arts and Science',
  'Business Administration and Accountancy' => 'College of Business Administration and Accountancy',
  'Criminal Justice Education' => 'College of Criminal Justice Education',
  'Teacher Education' => 'College of Teacher Education'
];
// Accept common aliases present in some databases
$deptAliases = [
  'Engineering' => ['Engineering', 'College of Engineering'],
  'Arts and Science' => ['Arts and Science', 'College of Arts and Science'],
  'Business Administration and Accountancy' => ['Business Administration and Accountancy', 'College of Business Administration and Accountancy', 'CBAA'],
  'Criminal Justice Education' => ['Criminal Justice Education', 'College of Criminal Justice Education'],
  'Teacher Education' => ['Teacher Education', 'College of Teacher Education']
];

$action = $_GET['action'] ?? 'list_passers';
$dept   = $_GET['dept']   ?? '';
if (!in_array($dept, array_keys($allowedDepts))) {
  echo json_encode(['success'=>false,'error'=>'Invalid or missing department']);
  exit;
}

function clean($s){ return trim((string)$s); }

if ($action === 'list_passers') {
  $page = max(1, (int)($_GET['page'] ?? 1));
  // Raise the cap so charts can aggregate across many rows per department
  $limit = max(1, min(10000, (int)($_GET['limit'] ?? 50)));
  $offset = ($page - 1) * $limit;
  $q = clean($_GET['q'] ?? '');
  $sort = $_GET['sort'] ?? 'board_exam_date';
  $dir  = strtolower($_GET['dir'] ?? 'desc');
  $dir  = ($dir === 'asc') ? 'ASC' : 'DESC';
  // Optional filters
  // - results: comma-separated values, e.g., Passed,Failed; '__none__' => return no rows
  // - sexes: comma-separated values, e.g., Male,Female; '__none__' => return no rows
  $resultsParam = isset($_GET['results']) ? trim((string)$_GET['results']) : '';
  $resultsNone = ($resultsParam !== '' && $resultsParam === '__none__');
  $resultsFilter = $resultsNone ? [] : array_values(array_filter(array_map('trim', explode(',', $resultsParam)), function($v){ return $v !== ''; }));
  $sexesParam = isset($_GET['sexes']) ? trim((string)$_GET['sexes']) : '';
  $sexesNone = ($sexesParam !== '' && $sexesParam === '__none__');
  $sexesFilter = $sexesNone ? [] : array_values(array_filter(array_map('trim', explode(',', $sexesParam)), function($v){ return $v !== ''; }));
  // Detect column presence (names may be split across first/middle/last)
  $hasName = false; $hasFirst = false; $hasMiddle = false; $hasLast = false;
  if ($res = $conn->query("SHOW COLUMNS FROM board_passers LIKE 'name'")) { $hasName = ($res->num_rows > 0); $res->close(); }
  if ($res = $conn->query("SHOW COLUMNS FROM board_passers LIKE 'first_name'")) { $hasFirst = ($res->num_rows > 0); $res->close(); }
  if ($res = $conn->query("SHOW COLUMNS FROM board_passers LIKE 'middle_name'")) { $hasMiddle = ($res->num_rows > 0); $res->close(); }
  if ($res = $conn->query("SHOW COLUMNS FROM board_passers LIKE 'last_name'")) { $hasLast = ($res->num_rows > 0); $res->close(); }
  // Build safe expressions for name selection and ordering/search
  if ($hasName) {
    $nameExpr = 'name';
  } elseif ($hasFirst && $hasLast) {
    // TRIM to avoid double-spaces if middle name is empty
    $nameExpr = "TRIM(CONCAT(first_name,' ',IFNULL(middle_name,''),' ',last_name))";
  } else {
    $nameExpr = "''"; // fallback empty
  }
  $sortable = [
    'name' => $nameExpr,
    'board_exam_type' => 'board_exam_type',
    'board_exam_date' => 'board_exam_date',
    'exam_type' => 'exam_type', // take attempts
    'result' => 'result'
  ];
  // Check optional columns (result/exam_type) availability
  $hasResult = false; $hasAttempt = false;
  if ($res = $conn->query("SHOW COLUMNS FROM board_passers LIKE 'result'")) { $hasResult = ($res->num_rows > 0); $res->close(); }
  if ($res = $conn->query("SHOW COLUMNS FROM board_passers LIKE 'exam_type'")) { $hasAttempt = ($res->num_rows > 0); $res->close(); }

  $orderBy = $sortable[$sort] ?? 'board_exam_date';
  if ($orderBy === 'result' && !$hasResult) $orderBy = 'board_exam_date';
  if ($orderBy === 'exam_type' && !$hasAttempt) $orderBy = 'board_exam_type';

  // Filters (department with aliases, using LIKE to be resilient to stored names)
  $aliases = $deptAliases[$dept] ?? [$dept];
  $likeParts = [];
  $params = [];
  $types = '';
  foreach ($aliases as $a) {
    $likeParts[] = 'department LIKE ?';
    $params[] = '%' . $a . '%';
    $types .= 's';
  }
  $where = 'WHERE (' . implode(' OR ', $likeParts) . ')';
  if ($q !== '') {
    $where .= ' AND ('.$nameExpr.' LIKE ? OR board_exam_type LIKE ?)';
    $kw = '%' . $q . '%';
    $params[] = $kw; $params[] = $kw; $types .= 'ss';
  }
  // Apply results filter if present and column exists
  if ($resultsNone) {
    $where .= ' AND 1=0';
  } elseif (!empty($resultsFilter)) {
    // We'll add placeholders dynamically
    $placeholders = implode(',', array_fill(0, count($resultsFilter), '?'));
    $where .= " AND result IN ($placeholders)";
    foreach ($resultsFilter as $rv) { $params[] = $rv; $types .= 's'; }
  }

  // Apply sexes filter if present
  if ($sexesNone) {
    $where .= ' AND 1=0';
  } elseif (!empty($sexesFilter)) {
    $placeholders = implode(',', array_fill(0, count($sexesFilter), '?'));
    $where .= " AND sex IN ($placeholders)";
    foreach ($sexesFilter as $sv) { $params[] = $sv; $types .= 's'; }
  }

  // Count total
  $sqlCnt = "SELECT COUNT(*) AS cnt FROM board_passers $where";
  $stmtCnt = $conn->prepare($sqlCnt);
  if ($types) { $stmtCnt->bind_param($types, ...$params); }
  $stmtCnt->execute();
  $resCnt = $stmtCnt->get_result()->fetch_assoc();
  $total = (int)($resCnt['cnt'] ?? 0);
  $stmtCnt->close();

  // Legends (unpaged counts) - adapt to missing columns
  $legResult = []; $legAttempts = []; $legSex = [];
  if ($hasResult || $hasAttempt) {
    $selR = $hasResult ? 'result' : "'Unknown' AS result";
    $selA = $hasAttempt ? 'exam_type' : "'Unspecified' AS exam_type";
    $sqlLeg = "SELECT $selR, $selA, COUNT(*) AS c FROM board_passers $where GROUP BY $selR, $selA";
    $stmtLeg = $conn->prepare($sqlLeg);
    if ($types) { $stmtLeg->bind_param($types, ...$params); }
    $stmtLeg->execute();
    $rsLeg = $stmtLeg->get_result();
    while ($row = $rsLeg->fetch_assoc()) {
      $r = $row['result'] ?? 'Unknown';
      $a = $row['exam_type'] ?? 'Unspecified';
      $c = (int)$row['c'];
      $legResult[$r] = ($legResult[$r] ?? 0) + $c;
      $legAttempts[$a] = ($legAttempts[$a] ?? 0) + $c;
    }
    $stmtLeg->close();
  }

  // Sex legend counts (independent of whether result/attempt columns exist)
  $sqlLegSex = "SELECT sex, COUNT(*) AS c FROM board_passers $where GROUP BY sex";
  $stmtLegSex = $conn->prepare($sqlLegSex);
  if ($types) { $stmtLegSex->bind_param($types, ...$params); }
  if ($stmtLegSex->execute()) {
    $rsSex = $stmtLegSex->get_result();
    while ($row = $rsSex->fetch_assoc()) {
      $s = trim($row['sex'] ?? '');
      $c = (int)($row['c'] ?? 0);
      if ($s !== '') { $legSex[$s] = ($legSex[$s] ?? 0) + $c; }
    }
  }
  $stmtLegSex->close();

  // Paged data
  $sel = $nameExpr . " AS name, board_exam_type, board_exam_date, sex";
  $sel .= $hasAttempt ? ", exam_type" : ", '' AS exam_type";
  $sel .= $hasResult ? ", result" : ", '' AS result";
  $sql = "SELECT $sel FROM board_passers $where ORDER BY $orderBy $dir LIMIT ? OFFSET ?";
  $stmt = $conn->prepare($sql);
  $types2 = $types . 'ii';
  $params2 = array_merge($params, [$limit, $offset]);
  $stmt->bind_param($types2, ...$params2);
  $stmt->execute();
  $rs = $stmt->get_result();
  $rows = [];
  while ($row = $rs->fetch_assoc()) {
    $rows[] = [
      'name' => $row['name'],
      'board_exam_type' => $row['board_exam_type'],
      'board_exam_date' => $row['board_exam_date'],
      'sex' => $row['sex'] ?? '',
      'exam_type' => $row['exam_type'],
      'result' => $row['result']
    ];
  }
  $stmt->close();

  echo json_encode([
    'success' => true,
    'meta' => [ 'page' => $page, 'limit' => $limit, 'total' => $total, 'pages' => ($limit? ceil($total/$limit) : 1) ],
    'data' => $rows,
    'legends' => [ 'result' => $legResult, 'attempts' => $legAttempts, 'sex' => $legSex ]
  ]);
  exit;
}

echo json_encode(['success'=>false,'error'=>'Unknown action']);
