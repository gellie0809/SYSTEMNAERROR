<?php
session_start();

// Only allow CBAA admin
if (!isset($_SESSION["users"]) || $_SESSION["users"] !== 'cte_admin@lspu.edu.ph') {
  http_response_code(403);
  header('Content-Type: application/json');
  echo json_encode([ 'success' => false, 'error' => 'Unauthorized' ]);
  exit();
}

header('Content-Type: application/json');

// Database connection
$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "project_db";
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
  http_response_code(500);
  echo json_encode([ 'success' => false, 'error' => 'DB connection failed' ]);
  exit();
}

// Helper to bind params dynamically
function bindParams($stmt, $params) {
  if (empty($params)) return;
  $types = '';
  $values = [];
  foreach ($params as $p) {
    if (is_int($p)) $types .= 'i';
    elseif (is_float($p)) $types .= 'd';
    else $types .= 's';
    $values[] = $p;
  }
  $refs = [&$types];
  foreach ($values as &$v) $refs[] = &$v;
  call_user_func_array([$stmt, 'bind_param'], $refs);
}

// Sanitize & collect filters
$action = $_GET['action'] ?? 'trend';
$nameSearch = trim($_GET['name'] ?? '');
$course = trim($_GET['course'] ?? '');
$year = isset($_GET['year']) && $_GET['year'] !== '' ? (int)$_GET['year'] : null;
$yearStart = isset($_GET['yearStart']) && $_GET['yearStart'] !== '' ? (int)$_GET['yearStart'] : null;
$yearEnd = isset($_GET['yearEnd']) && $_GET['yearEnd'] !== '' ? (int)$_GET['yearEnd'] : null;
$fromDate = trim($_GET['fromDate'] ?? '');
$toDate = trim($_GET['toDate'] ?? '');
$exactExamDate = trim($_GET['examDate'] ?? '');
$result = trim($_GET['result'] ?? '');
$examType = trim($_GET['examType'] ?? '');
$boardExamType = trim($_GET['boardExamType'] ?? '');
$boardExamTypeId = isset($_GET['boardExamTypeId']) ? (int)$_GET['boardExamTypeId'] : 0;
$examDateId = isset($_GET['examDateId']) ? (int)$_GET['examDateId'] : 0;

// Build WHERE clause
$where = ["department = 'Teacher Education'"];
$params = [];

if ($nameSearch !== '') {
  $where[] = "(CONCAT_WS(' ', first_name, middle_name, last_name) LIKE ? OR name LIKE ?)";
  $params[] = '%' . $nameSearch . '%';
  $params[] = '%' . $nameSearch . '%';
}
if ($course !== '') { $where[] = "course = ?"; $params[] = $course; }
if ($year !== null) { $where[] = "year_graduated = ?"; $params[] = $year; }
if ($yearStart !== null && $yearEnd !== null) { $where[] = "year_graduated BETWEEN ? AND ?"; $params[] = $yearStart; $params[] = $yearEnd; }
if ($exactExamDate !== '') { $where[] = "board_exam_date = ?"; $params[] = $exactExamDate; }
if ($fromDate !== '' && $toDate !== '') { $where[] = "board_exam_date BETWEEN ? AND ?"; $params[] = $fromDate; $params[] = $toDate; }
if ($result !== '') { $where[] = "result = ?"; $params[] = $result; }
if ($examType !== '') { $where[] = "exam_type = ?"; $params[] = $examType; }
if ($boardExamType !== '') { $where[] = "board_exam_type = ?"; $params[] = $boardExamType; }

if ($boardExamTypeId > 0) {
  $tstmt = $conn->prepare("SELECT exam_type_name FROM board_exam_types WHERE id = ? AND department = 'Teacher Education' LIMIT 1");
  if ($tstmt) {
    $tstmt->bind_param('i', $boardExamTypeId);
    $tstmt->execute();
    $tr = $tstmt->get_result()->fetch_assoc();
    if ($tr && !empty($tr['exam_type_name'])) {
      $where[] = "(exam_type = ? OR board_exam_type = ? OR exam_type LIKE ?)";
      $params[] = $tr['exam_type_name'];
      $params[] = $tr['exam_type_name'];
      $params[] = "%" . $tr['exam_type_name'] . "%";
    }
    $tstmt->close();
  }
}

if ($examDateId > 0) {
  $dstmt = $conn->prepare("SELECT exam_date FROM board_exam_dates WHERE id = ? AND department = 'Teacher Education' LIMIT 1");
  if ($dstmt) {
    $dstmt->bind_param('i', $examDateId);
    $dstmt->execute();
    $dr = $dstmt->get_result()->fetch_assoc();
    if ($dr && !empty($dr['exam_date'])) {
      $where[] = "board_exam_date = ?";
      $params[] = $dr['exam_date'];
    }
    $dstmt->close();
  }
}

$whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
$debug = isset($_GET['debug']) && in_array($_GET['debug'], ['1', 'true']);

try {
  // === SIGNATURE CHECK ===
  if ($action === 'signature') {
    $sql = "SELECT CONCAT(COALESCE(MAX(id),0),':',COALESCE(MAX(board_exam_date),'0000-00-00'),':',COUNT(*)) AS signature FROM board_passers $whereSql";
    $stmt = $conn->prepare($sql);
    bindParams($stmt, $params);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc() ?: ['signature' => '0:0000-00-00:0'];
    echo json_encode(['success' => true, 'signature' => $row['signature']]);
    exit();
  }

  // === MONTHLY TREND ===
  if ($action === 'trend') {
    $sql = "SELECT DATE_FORMAT(board_exam_date, '%Y-%m') AS ym,
                   COUNT(*) AS total,
                   SUM(CASE WHEN result='PASSED' THEN 1 ELSE 0 END) AS passed,
                   SUM(CASE WHEN result='FAILED' THEN 1 ELSE 0 END) AS failed,
                   SUM(CASE WHEN result='CONDITIONAL' THEN 1 ELSE 0 END) AS conditional
            FROM board_passers $whereSql
            GROUP BY ym ORDER BY ym ASC";
    $stmt = $conn->prepare($sql);
    bindParams($stmt, $params);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $out = ['success' => true, 'data' => $data];
    if ($debug) $out['debug'] = ['sql' => $sql, 'params' => $params];
    echo json_encode($out);
    exit();
  }

  // === BY COURSE ===
  if ($action === 'by_course') {
    $limit = max(1, min(50, (int)($_GET['limit'] ?? 10)));
    $sql = "SELECT course,
                   COUNT(*) AS total,
                   SUM(CASE WHEN result='PASSED' THEN 1 ELSE 0 END) AS passed
            FROM board_passers $whereSql
            GROUP BY course ORDER BY passed DESC LIMIT ?";
    $stmt = $conn->prepare($sql);
    $params[] = $limit;
    bindParams($stmt, $params);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    echo json_encode(['success' => true, 'data' => $data]);
    exit();
  }

  // === COMPOSITION ===
  if ($action === 'composition') {
    $sql = "SELECT result, COUNT(*) AS count FROM board_passers $whereSql GROUP BY result";
    $stmt = $conn->prepare($sql);
    bindParams($stmt, $params);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    echo json_encode(['success' => true, 'data' => $data]);
    exit();
  }

  // === GENDER ===
  if ($action === 'gender') {
    $sql = "SELECT COALESCE(NULLIF(TRIM(sex), ''), 'Unknown') AS gender,
                   COUNT(*) AS total,
                   SUM(CASE WHEN result='PASSED' THEN 1 ELSE 0 END) AS passed
            FROM board_passers $whereSql GROUP BY gender";
    $stmt = $conn->prepare($sql);
    bindParams($stmt, $params);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    echo json_encode(['success' => true, 'data' => $data]);
    exit();
  }

  // === KPIs ===
  if ($action === 'kpis') {
    $sql = "SELECT COUNT(*) AS total, SUM(CASE WHEN result='PASSED' THEN 1 ELSE 0 END) AS passed FROM board_passers $whereSql";
    $stmt = $conn->prepare($sql);
    bindParams($stmt, $params);
    $stmt->execute();
    $tot = $stmt->get_result()->fetch_assoc() ?? ['total' => 0, 'passed' => 0];

    $sql = "SELECT COALESCE(NULLIF(TRIM(sex), ''), 'Unknown') AS gender,
                   COUNT(*) AS total,
                   SUM(CASE WHEN result='PASSED' THEN 1 ELSE 0 END) AS passed
            FROM board_passers $whereSql GROUP BY gender";
    $stmt = $conn->prepare($sql);
    bindParams($stmt, $params);
    $stmt->execute();
    $gender = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $sql = "SELECT
                   SUM(CASE WHEN exam_type='Repeater' THEN 1 ELSE 0 END) AS repeater_total,
                   SUM(CASE WHEN exam_type='Repeater' AND result='PASSED' THEN 1 ELSE 0 END) AS repeater_passed,
                   SUM(CASE WHEN exam_type='First Timer' THEN 1 ELSE 0 END) AS first_total,
                   SUM(CASE WHEN exam_type='First Timer' AND result='PASSED' THEN 1 ELSE 0 END) AS first_passed
            FROM board_passers $whereSql";
    $stmt = $conn->prepare($sql);
    bindParams($stmt, $params);
    $stmt->execute();
    $rep = $stmt->get_result()->fetch_assoc() ?? ['repeater_total'=>0, 'repeater_passed'=>0, 'first_total'=>0, 'first_passed'=>0];

    $sql = "SELECT year_graduated AS year, COUNT(*) AS total, SUM(CASE WHEN result='PASSED' THEN 1 ELSE 0 END) AS passed
            FROM board_passers $whereSql GROUP BY year_graduated ORDER BY year DESC";
    $stmt = $conn->prepare($sql);
    bindParams($stmt, $params);
    $stmt->execute();
    $years = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    echo json_encode([
      'success' => true,
      'data' => [
        'total' => (int)$tot['total'],
        'passed' => (int)$tot['passed'],
        'gender' => $gender,
        'repeater' => ['total' => (int)$rep['repeater_total'], 'passed' => (int)$rep['repeater_passed']],
        'first_timer' => ['total' => (int)$rep['first_total'], 'passed' => (int)$rep['first_passed']],
        'year_distribution' => $years
      ]
    ]);
    exit();
  }

  // === SUBJECTS (CPA-specific) ===
  if ($action === 'subjects') {
    if ($boardExamTypeId <= 0) {
      echo json_encode(['success' => false, 'error' => 'boardExamTypeId required']);
      exit();
    }

    $typeName = $dateStr = '';
    $tstmt = $conn->prepare("SELECT exam_type_name FROM board_exam_types WHERE id = ? AND department = 'Teacher Education' LIMIT 1");
    if ($tstmt) { $tstmt->bind_param('i', $boardExamTypeId); $tstmt->execute(); $tr = $tstmt->get_result()->fetch_assoc(); $typeName = $tr['exam_type_name'] ?? ''; $tstmt->close(); }

    if ($examDateId > 0) {
      $dstmt = $conn->prepare("SELECT exam_date FROM board_exam_dates WHERE id = ? AND department = 'Teacher Education' LIMIT 1");
      if ($dstmt) { $dstmt->bind_param('i', $examDateId); $dstmt->execute(); $dr = $dstmt->get_result()->fetch_assoc(); $dateStr = $dr['exam_date'] ?? ''; $dstmt->close(); }
    }

    $passerWhere = "bp.department = 'Teacher Education'";
    $subParams = []; $subTypes = '';
    if ($typeName) { $passerWhere .= " AND (bp.exam_type = ? OR bp.board_exam_type = ?)"; $subParams[] = $typeName; $subParams[] = $typeName; $subTypes .= 'ss'; }
    if ($dateStr) { $passerWhere .= " AND bp.board_exam_date = ?"; $subParams[] = $dateStr; $subTypes .= 's'; }

    $sql = "SELECT s.id AS subject_id,
                   COALESCE(NULLIF(TRIM(s.subject_name), ''), 'Unknown Subject') AS subject_name,
                   COUNT(fp.id) AS total,
                   SUM(CASE WHEN bps.rating >= 75 THEN 1 ELSE 0 END) AS passed,
                   SUM(CASE WHEN bps.rating < 75 AND bps.rating IS NOT NULL THEN 1 ELSE 0 END) AS failed
            FROM subjects s
            JOIN subject_exam_types se ON se.subject_id = s.id AND se.exam_type_id = ?
            LEFT JOIN (SELECT bp.id FROM board_passers bp WHERE $passerWhere) AS fp ON 1=1
            LEFT JOIN board_passer_subjects bps ON bps.subject_id = s.id AND bps.board_passer_id = fp.id
            WHERE s.department = 'Teacher Education'
            GROUP BY s.id, s.subject_name
            ORDER BY total DESC";
    
    $stmt = $conn->prepare($sql);
    $params2 = array_merge([$boardExamTypeId], $subParams);
    $types2 = 'i' . $subTypes;
    $stmt->bind_param($types2, ...$params2);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    echo json_encode(['success' => true, 'data' => $data]);
    exit();
  }

  // === ALL SUBJECTS LIST ===
  if ($action === 'subjects_all') {
    $sql = "SELECT bet.id AS exam_type_id, bet.exam_type_name,
                   s.id AS subject_id, COALESCE(NULLIF(TRIM(s.subject_name), ''), 'Unknown') AS subject_name
            FROM board_exam_types bet
            LEFT JOIN subject_exam_types se ON se.exam_type_id = bet.id
            LEFT JOIN subjects s ON s.id = se.subject_id AND s.department = 'Teacher Education'
            WHERE bet.department = 'Teacher Education'
            ORDER BY bet.exam_type_name, s.subject_name";
    $res = $conn->query($sql);
    $groups = [];
    while ($row = $res->fetch_assoc()) {
      $tid = (int)$row['exam_type_id'];
      if (!isset($groups[$tid])) $groups[$tid] = ['exam_type_id' => $tid, 'exam_type_name' => $row['exam_type_name'], 'subjects' => []];
      if ($row['subject_id']) $groups[$tid]['subjects'][] = ['subject_id' => (int)$row['subject_id'], 'subject_name' => $row['subject_name']];
    }
    echo json_encode(['success' => true, 'data' => array_values($groups)]);
    exit();
  }

  // === FORECAST & TRENDS (simplified for CBAA) ===
  if (in_array($action, ['trend_passing_rate', 'dept_passing_rate', 'passing_rate_forecast'])) {
    $yearStart = max(2019, min(2030, (int)($_GET['yearStart'] ?? 2019)));
    $yearEnd = max($yearStart, min(2030, (int)($_GET['yearEnd'] ?? 2024)));
    $from = "$yearStart-01-01"; $to = "$yearEnd-12-31";

    $filter = "department = 'Teacher Education' AND board_exam_date BETWEEN ? AND ?";
    $bind = [$from, $to]; $types = 'ss';

    if ($boardExamTypeId > 0) {
      $tstmt = $conn->prepare("SELECT exam_type_name FROM board_exam_types WHERE id = ?");
      $tstmt->bind_param('i', $boardExamTypeId); $tstmt->execute();
      $typeName = $tstmt->get_result()->fetch_assoc()['exam_type_name'] ?? '';
      $tstmt->close();
      if ($typeName) { $filter .= " AND (exam_type = ? OR board_exam_type = ?)"; $bind[] = $typeName; $bind[] = $typeName; $types .= 'ss'; }
    }

    $sql = "SELECT YEAR(board_exam_date) AS y, COUNT(*) AS total, SUM(CASE WHEN result='PASSED' THEN 1 ELSE 0 END) AS passed
            FROM board_passers WHERE $filter GROUP BY y ORDER BY y";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$bind);
    $stmt->execute();
    $res = $stmt->get_result();

    $known = []; $points = [];
    while ($row = $res->fetch_assoc()) {
      $y = (int)$row['y']; $total = (int)$row['total']; $passed = (int)$row['passed'];
      $rate = $total ? round(($passed/$total)*100, 2) : 0;
      $known[] = $y;
      $points[$y] = ['rate' => $rate, 'passed' => $passed, 'total' => $total];
    }

    $years = range($yearStart, $yearEnd);
    $rates = array_map(fn($y) => $points[$y]['rate'] ?? 0, $years);

    if ($action === 'passing_rate_forecast') {
      $n = count($known); $slope = 0; $intercept = 0;
      if ($n >= 2) {
        $x = range(0, $n-1); $y = array_values(array_intersect_key($points, array_flip($known)));
        $sumX = array_sum($x); $sumY = array_sum($y); $sumXY = 0; $sumX2 = 0;
        foreach ($x as $i) { $sumXY += $x[$i] * $y[$i]; $sumX2 += $x[$i] ** 2; }
        $den = $n * $sumX2 - $sumX ** 2;
        $slope = $den ? ($n * $sumXY - $sumX * $sumY) / $den : 0;
        $intercept = ($sumY - $slope * $sumX) / $n;
      }
      $forecast = [];
      for ($h=1; $h<=2; $h++) {
        $pred = max(0, min(100, $intercept + $slope * ($n - 1 + $h)));
        $forecast[] = ['year' => end($known) + $h, 'rate' => round($pred, 2)];
      }
      echo json_encode(['success' => true, 'data' => compact('known', 'points', 'forecast')]);
    } else {
      echo json_encode(['success' => true, 'data' => ['years' => $years, 'rates' => $rates, 'points' => $points]]);
    }
    exit();
  }

  http_response_code(400);
  echo json_encode(['success' => false, 'error' => 'Invalid action']);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['success' => false, 'error' => 'Server error', 'details' => $e->getMessage()]);
}
?>