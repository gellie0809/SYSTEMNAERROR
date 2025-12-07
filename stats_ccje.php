<?php
session_start();

// Only allow College of Criminal Justice Education admin
if (!isset($_SESSION["users"]) || $_SESSION["users"] !== 'ccje_admin@lspu.edu.ph') {
  http_response_code(403);
  header('Content-Type: application/json');
  echo json_encode([ 'success' => false, 'error' => 'Unauthorized' ]);
  exit();
}

header('Content-Type: application/json');

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "project_db";
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
  http_response_code(500);
  echo json_encode([ 'success' => false, 'error' => 'DB connection failed' ]);
  exit();
}

// Helper to dynamically bind params with references
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
  $refs = [];
  $refs[] = &$types;
  for ($i = 0; $i < count($values); $i++) {
    $refs[] = &$values[$i];
  }
  call_user_func_array([$stmt, 'bind_param'], $refs);
}

// Collect and sanitize filters
$action = $_GET['action'] ?? 'trend';
$nameSearch = trim($_GET['name'] ?? '');
$course = trim($_GET['course'] ?? '');
$year = isset($_GET['year']) && $_GET['year'] !== '' ? intval($_GET['year']) : null;
$yearStart = isset($_GET['yearStart']) && $_GET['yearStart'] !== '' ? intval($_GET['yearStart']) : null;
$yearEnd = isset($_GET['yearEnd']) && $_GET['yearEnd'] !== '' ? intval($_GET['yearEnd']) : null;
$fromDate = trim($_GET['fromDate'] ?? '');
$toDate = trim($_GET['toDate'] ?? '');
$exactExamDate = trim($_GET['examDate'] ?? '');
$result = trim($_GET['result'] ?? '');
$examType = trim($_GET['examType'] ?? '');
$boardExamType = trim($_GET['boardExamType'] ?? '');
$boardExamTypeId = isset($_GET['boardExamTypeId']) ? trim($_GET['boardExamTypeId']) : '';
$examDateId = isset($_GET['examDateId']) ? trim($_GET['examDateId']) : '';

// Build WHERE clause and params
$where = ["department = 'Criminal Justice Education'", "(is_deleted = 0 OR is_deleted IS NULL)"];
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

if ($boardExamTypeId !== '') {
  $tstmt = $conn->prepare("SELECT exam_type_name FROM board_exam_types WHERE id = ? AND (is_deleted = 0 OR is_deleted IS NULL) LIMIT 1");
  if ($tstmt) {
    $tstmt->bind_param('i', $boardExamTypeId);
    $tstmt->execute();
    $tr = $tstmt->get_result()->fetch_assoc();
    if ($tr && !empty($tr['exam_type_name'])) {
      $where[] = "(exam_type = ? OR board_exam_type = ? OR exam_type LIKE ?)";
      $params[] = $tr['exam_type_name']; $params[] = $tr['exam_type_name']; $params[] = "%".$tr['exam_type_name'].'%';
    }
    $tstmt->close();
  }
}

if ($examDateId !== '') {
  $dstmt = $conn->prepare("SELECT exam_date FROM board_exam_dates WHERE id = ? AND (is_deleted = 0 OR is_deleted IS NULL) LIMIT 1");
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

$whereSql = '';
if (!empty($where)) { $whereSql = 'WHERE ' . implode(' AND ', $where); }
$debug = isset($_GET['debug']) && ($_GET['debug'] === '1' || $_GET['debug'] === 'true');

try {
  if ($action === 'signature') {
    $sql = "SELECT CONCAT(COALESCE(MAX(id),0),':',COALESCE(MAX(board_exam_date),'0000-00-00'),':',COUNT(*)) AS signature FROM board_passers $whereSql";
    $stmt = $conn->prepare($sql);
    bindParams($stmt, $params);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc() ?: ['signature' => '0:0000-00-00:0'];
    echo json_encode(['success' => true, 'signature' => (string)$row['signature']]);
    exit();
  }

  if ($action === 'trend') {
    $sql = "SELECT DATE_FORMAT(board_exam_date, '%Y-%m') AS ym,
                   COUNT(*) AS total,
                   SUM(CASE WHEN result='Passed' THEN 1 ELSE 0 END) AS passed,
                   SUM(CASE WHEN result='Failed' THEN 1 ELSE 0 END) AS failed,
                   SUM(CASE WHEN result='Conditional' THEN 1 ELSE 0 END) AS conditional
            FROM board_passers
            $whereSql
            GROUP BY ym
            ORDER BY ym ASC";
    $stmt = $conn->prepare($sql);
    bindParams($stmt, $params);
    $stmt->execute();
    $res = $stmt->get_result();
    $data = [];
    while ($row = $res->fetch_assoc()) { $data[] = $row; }
    $out = ['success' => true, 'data' => $data];
    if ($debug) $out['debug'] = ['sql' => $sql, 'where' => $whereSql, 'params' => $params];
    echo json_encode($out);
    exit();
  }

  if ($action === 'by_course') {
    $limit = isset($_GET['limit']) && intval($_GET['limit']) > 0 ? intval($_GET['limit']) : 10;
    $sql = "SELECT course,
                   COUNT(*) AS total,
                   SUM(CASE WHEN result='Passed' THEN 1 ELSE 0 END) AS passed,
                   SUM(CASE WHEN result='Failed' THEN 1 ELSE 0 END) AS failed,
                   SUM(CASE WHEN result='Conditional' THEN 1 ELSE 0 END) AS conditional
            FROM board_passers
            $whereSql
            GROUP BY course
            ORDER BY total DESC
            LIMIT ?";
    $stmt = $conn->prepare($sql);
    $params2 = $params; $params2[] = (int)$limit;
    bindParams($stmt, $params2);
    $stmt->execute();
    $res = $stmt->get_result();
    $data = [];
    while ($row = $res->fetch_assoc()) { $data[] = $row; }
    echo json_encode(['success' => true, 'data' => $data]);
    exit();
  }

  if ($action === 'composition') {
    $sql = "SELECT result, COUNT(*) AS count
            FROM board_passers
            $whereSql
            GROUP BY result";
    $stmt = $conn->prepare($sql);
    bindParams($stmt, $params);
    $stmt->execute();
    $res = $stmt->get_result();
    $data = [];
    while ($row = $res->fetch_assoc()) { $data[] = $row; }
    echo json_encode(['success' => true, 'data' => $data]);
    exit();
  }

  if ($action === 'gender') {
    $sql = "SELECT COALESCE(NULLIF(TRIM(sex), ''), 'Unknown') AS gender,
         COUNT(*) AS total,
         SUM(CASE WHEN result='Passed' THEN 1 ELSE 0 END) AS passed
       FROM board_passers
       $whereSql
       GROUP BY gender";
    $stmt = $conn->prepare($sql);
    bindParams($stmt, $params);
    $stmt->execute();
    $res = $stmt->get_result();
    $data = [];
    while ($row = $res->fetch_assoc()) { $data[] = $row; }
    $out = ['success' => true, 'data' => $data];
    if ($debug) $out['debug'] = ['sql' => $sql, 'where' => $whereSql, 'params' => $params];
    echo json_encode($out);
    exit();
  }

  if ($action === 'kpis') {
    $sql = "SELECT COUNT(*) AS total, SUM(CASE WHEN result='Passed' THEN 1 ELSE 0 END) AS passed
            FROM board_passers
            $whereSql";
    $stmt = $conn->prepare($sql);
    bindParams($stmt, $params);
    $stmt->execute();
    $res = $stmt->get_result();
    $tot = $res->fetch_assoc() ?: ['total' => 0, 'passed' => 0];

    $sql = "SELECT COALESCE(NULLIF(TRIM(sex), ''), 'Unknown') AS gender,
                   COUNT(*) AS total,
                   SUM(CASE WHEN result='Passed' THEN 1 ELSE 0 END) AS passed
            FROM board_passers
            $whereSql
            GROUP BY gender";
    $stmt = $conn->prepare($sql);
    bindParams($stmt, $params);
    $stmt->execute();
    $res = $stmt->get_result();
    $gender = [];
    while ($row = $res->fetch_assoc()) { $gender[] = $row; }

    $sql = "SELECT
                   SUM(CASE WHEN exam_type='Repeater' THEN 1 ELSE 0 END) AS repeater_total,
                   SUM(CASE WHEN exam_type='Repeater' AND result='Passed' THEN 1 ELSE 0 END) AS repeater_passed,
                   SUM(CASE WHEN exam_type='First Timer' THEN 1 ELSE 0 END) AS first_total,
                   SUM(CASE WHEN exam_type='First Timer' AND result='Passed' THEN 1 ELSE 0 END) AS first_passed
            FROM board_passers
            $whereSql";
    $stmt = $conn->prepare($sql);
    bindParams($stmt, $params);
    $stmt->execute();
    $res = $stmt->get_result();
    $rep = $res->fetch_assoc() ?: ['repeater_total' => 0, 'repeater_passed' => 0, 'first_total' => 0, 'first_passed' => 0];

    $sql = "SELECT year_graduated AS year, COUNT(*) AS total, SUM(CASE WHEN result='Passed' THEN 1 ELSE 0 END) AS passed
            FROM board_passers
            $whereSql
            GROUP BY year_graduated
            ORDER BY year_graduated DESC";
    $stmt = $conn->prepare($sql);
    bindParams($stmt, $params);
    $stmt->execute();
    $res = $stmt->get_result();
    $years = [];
    while ($row = $res->fetch_assoc()) { $years[] = $row; }

    $out = [
      'success' => true,
      'data' => [
        'total' => (int)$tot['total'],
        'passed' => (int)$tot['passed'],
        'gender' => $gender,
        'repeater' => [ 'total' => (int)$rep['repeater_total'], 'passed' => (int)$rep['repeater_passed'] ],
        'first_timer' => [ 'total' => (int)$rep['first_total'], 'passed' => (int)$rep['first_passed'] ],
        'year_distribution' => $years
      ]
    ];
    if ($debug) $out['debug'] = ['where' => $whereSql, 'params' => $params];
    echo json_encode($out);
    exit();
  }

  if ($action === 'subjects') {
    $typeId = isset($_GET['boardExamTypeId']) ? intval($_GET['boardExamTypeId']) : 0;
    $dateId = isset($_GET['examDateId']) ? intval($_GET['examDateId']) : 0;
    $examYearParam = isset($_GET['examYear']) ? intval($_GET['examYear']) : 0;
    if ($typeId <= 0) {
      echo json_encode(['success' => false, 'error' => 'boardExamTypeId is required for subjects action']);
      exit();
    }

    $typeName = '';
    $dateStr = '';
    $tstmt = $conn->prepare("SELECT exam_type_name FROM board_exam_types WHERE id=? LIMIT 1");
    if ($tstmt) { $tstmt->bind_param('i', $typeId); $tstmt->execute(); $tr = $tstmt->get_result()->fetch_assoc(); if ($tr && !empty($tr['exam_type_name'])) $typeName = $tr['exam_type_name']; $tstmt->close(); }
    if ($dateId > 0) {
      $dstmt = $conn->prepare("SELECT exam_date FROM board_exam_dates WHERE id=? LIMIT 1");
      if ($dstmt) { $dstmt->bind_param('i', $dateId); $dstmt->execute(); $dr = $dstmt->get_result()->fetch_assoc(); if ($dr && !empty($dr['exam_date'])) $dateStr = $dr['exam_date']; $dstmt->close(); }
    }
    $fromYear = '';
    $toYear = '';
    if ($examYearParam > 0 && $dateStr === '') { $fromYear = $examYearParam.'-01-01'; $toYear = $examYearParam.'-12-31'; }

    $hasRes = false; $hasPassedCol = false;
    if ($cr = $conn->query("SHOW COLUMNS FROM board_passer_subjects LIKE 'result'")) { $hasRes = ($cr->num_rows > 0); }
    if ($cp = $conn->query("SHOW COLUMNS FROM board_passer_subjects LIKE 'passed'")) { $hasPassedCol = ($cp->num_rows > 0); }
    $exprPassed = $hasRes ? "(bps.result = 'Passed')" : ($hasPassedCol ? "(bps.passed IN ('1',1))" : "0");
    $exprFailed = $hasRes ? "(bps.result = 'Failed')" : ($hasPassedCol ? "(bps.passed IN ('0',0))" : "0");

    $passerWhere = "bp.department = 'Criminal Justice Education'";
    $subParams = [];
    $subTypes = '';
    if ($typeName !== '') {
      $passerWhere .= " AND (bp.exam_type = ? OR bp.board_exam_type = ? OR bp.exam_type LIKE ?)";
      $subParams[] = $typeName; $subParams[] = $typeName; $subParams[] = "%".$typeName."%"; $subTypes .= 'sss';
    }
    if ($dateStr !== '') {
      $passerWhere .= " AND bp.board_exam_date = ?";
      $subParams[] = $dateStr; $subTypes .= 's';
    }
    if ($fromYear !== '' && $toYear !== '') {
      $passerWhere .= " AND bp.board_exam_date BETWEEN ? AND ?";
      $subParams[] = $fromYear; $subParams[] = $toYear; $subTypes .= 'ss';
    }

    $sql = "SELECT s.id AS subject_id,
         COALESCE(NULLIF(TRIM(s.subject_name), ''), CONCAT('Subject ', s.id)) AS subject_name,
         COUNT(fp.id) AS total,
         SUM(CASE WHEN $exprPassed THEN 1 ELSE 0 END) AS passed,
         SUM(CASE WHEN $exprFailed THEN 1 ELSE 0 END) AS failed,
         (COUNT(fp.id) - COUNT(bps.id)) AS unknown
       FROM subjects s
            JOIN subject_exam_types se ON se.subject_id = s.id AND se.exam_type_id = ?
            LEFT JOIN (
              SELECT bp.id, bp.exam_type, bp.board_exam_type, bp.board_exam_date
              FROM board_passers bp
              WHERE $passerWhere
            ) AS fp ON 1=1
            LEFT JOIN board_passer_subjects bps
              ON bps.subject_id = s.id AND bps.board_passer_id = fp.id
       WHERE s.department = 'Criminal Justice Education'
       GROUP BY s.id, s.subject_name
       ORDER BY total DESC, s.subject_name ASC";

    $stmt = $conn->prepare($sql);
    if (!$stmt) { echo json_encode(['success' => false, 'error' => 'Prepare failed']); exit(); }
    $types2 = 'i' . $subTypes;
    $params2 = array_merge([$typeId], $subParams);
    $stmt->bind_param($types2, ...$params2);
    $stmt->execute();
    $res = $stmt->get_result();
    $data = [];
    while ($row = $res->fetch_assoc()) {
      $row['total'] = (int)($row['total'] ?? 0);
      $row['passed'] = (int)($row['passed'] ?? 0);
      $row['failed'] = (int)($row['failed'] ?? 0);
      $row['unknown'] = (int)($row['unknown'] ?? 0);
      $data[] = $row;
    }
    $out = ['success' => true, 'data' => $data];
    if ($debug) $out['debug'] = ['sql' => $sql, 'params' => $params2, 'has_result' => $hasRes, 'has_passed_col' => $hasPassedCol];
    echo json_encode($out);
    exit();
  }

  if ($action === 'subjects_all') {
    $sql = "SELECT bet.id AS exam_type_id,
                   bet.exam_type_name,
                   s.id AS subject_id,
                   COALESCE(NULLIF(TRIM(s.subject_name), ''), CONCAT('Subject ', s.id)) AS subject_name
            FROM board_exam_types bet
            LEFT JOIN subject_exam_types se ON se.exam_type_id = bet.id
            LEFT JOIN subjects s ON s.id = se.subject_id AND s.department = 'Criminal Justice Education'
            WHERE bet.department = 'Criminal Justice Education'
            ORDER BY bet.exam_type_name ASC, s.subject_name ASC";
    $res = $conn->query($sql);
    if (!$res) { echo json_encode(['success' => false, 'error' => 'Query failed']); exit(); }
    $groups = [];
    while ($row = $res->fetch_assoc()) {
      $tid = (int)$row['exam_type_id'];
      if (!isset($groups[$tid])) {
        $groups[$tid] = [
          'exam_type_id' => $tid,
          'exam_type_name' => $row['exam_type_name'],
          'subjects' => []
        ];
      }
      if (!empty($row['subject_id'])) {
        $groups[$tid]['subjects'][] = [ 'subject_id' => (int)$row['subject_id'], 'subject_name' => $row['subject_name'] ];
      }
    }
    $res2 = $conn->query("SELECT id, exam_type_name FROM board_exam_types WHERE department='Criminal Justice Education' ORDER BY exam_type_name ASC");
    if ($res2) {
      while ($r2 = $res2->fetch_assoc()) {
        $tid = (int)$r2['id'];
        if (!isset($groups[$tid])) {
          $groups[$tid] = [ 'exam_type_id' => $tid, 'exam_type_name' => $r2['exam_type_name'], 'subjects' => [] ];
        }
      }
    }
    $data = array_values($groups);
    echo json_encode(['success' => true, 'data' => $data]);
    exit();
  }

  if ($action === 'students_subjects') {
    $typeId = isset($_GET['boardExamTypeId']) ? intval($_GET['boardExamTypeId']) : 0;
    $dateId = isset($_GET['examDateId']) ? intval($_GET['examDateId']) : 0;
    $examYearParam = isset($_GET['examYear']) ? intval($_GET['examYear']) : 0;
    $legendFilter = trim($_GET['legendFilter'] ?? '');
    if ($typeId <= 0) { echo json_encode(['success' => false, 'error' => 'boardExamTypeId is required']); exit(); }

    $typeName = '';
    $dateStr = '';
    $tstmt = $conn->prepare("SELECT exam_type_name FROM board_exam_types WHERE id=? LIMIT 1");
    if ($tstmt) { $tstmt->bind_param('i',$typeId); $tstmt->execute(); $tr=$tstmt->get_result()->fetch_assoc(); if($tr) $typeName = $tr['exam_type_name'] ?? ''; $tstmt->close(); }
    if ($dateId > 0) {
      $dstmt = $conn->prepare("SELECT exam_date FROM board_exam_dates WHERE id=? LIMIT 1");
      if ($dstmt) { $dstmt->bind_param('i',$dateId); $dstmt->execute(); $dr=$dstmt->get_result()->fetch_assoc(); if($dr) $dateStr = $dr['exam_date'] ?? ''; $dstmt->close(); }
    }
    $fromYear = ''; $toYear = '';
    if ($examYearParam > 0 && $dateStr === '') { $fromYear = $examYearParam.'-01-01'; $toYear = $examYearParam.'-12-31'; }

    $subjects = [];
    $subStmt = $conn->prepare("SELECT s.id, COALESCE(NULLIF(TRIM(s.subject_name),''), CONCAT('Subject ', s.id)) AS subject_name, COALESCE(s.total_items,50) AS total_items
                               FROM subject_exam_types se
                               JOIN subjects s ON s.id = se.subject_id AND s.department='Criminal Justice Education'
                               WHERE se.exam_type_id = ?");
    if ($subStmt) { $subStmt->bind_param('i',$typeId); $subStmt->execute(); $rs=$subStmt->get_result(); while($r=$rs->fetch_assoc()){ $subjects[(int)$r['id']] = [ 'id'=>(int)$r['id'], 'name'=>$r['subject_name'], 'total_items'=>(int)$r['total_items'] ]; } $subStmt->close(); }
    $subjectIds = array_keys($subjects);

    $pw = "bp.department='Criminal Justice Education'"; $pp=[]; $pt='';
    if ($typeName !== '') { $pw .= " AND (bp.exam_type = ? OR bp.board_exam_type = ? OR bp.exam_type LIKE ?)"; $pp[]=$typeName; $pp[]=$typeName; $pp[]='%'.$typeName.'%'; $pt.='sss'; }
    if ($dateStr !== '') { $pw .= " AND bp.board_exam_date = ?"; $pp[]=$dateStr; $pt.='s'; }
    if ($fromYear !== '' && $toYear !== '') { $pw .= " AND bp.board_exam_date BETWEEN ? AND ?"; $pp[]=$fromYear; $pp[]=$toYear; $pt.='ss'; }

    $inClause = '';
    if (!empty($subjectIds)) {
      $placeholders = implode(',', array_fill(0, count($subjectIds), '?'));
      $inClause = " AND bps.subject_id IN ($placeholders)";
    }

    $sql = "SELECT bp.id AS passer_id, bp.first_name, bp.middle_name, bp.last_name, bp.name, bp.sex, bp.course, bp.year_graduated, bp.board_exam_date,
                   bps.subject_id, bps.grade,
                   CASE
                     WHEN bps.result IS NOT NULL THEN bps.result
                     WHEN bps.passed IS NOT NULL THEN CASE WHEN bps.passed IN ('1',1) THEN 'Passed' WHEN bps.passed IN ('0',0) THEN 'Failed' ELSE '' END
                     ELSE ''
                   END AS subj_result
            FROM board_passers bp
            LEFT JOIN board_passer_subjects bps ON bps.board_passer_id = bp.id" . ($inClause !== '' ? $inClause : '') .
           " WHERE $pw" .
           " ORDER BY bp.last_name ASC, bp.first_name ASC";

    $stmt = $conn->prepare($sql);
    if (!$stmt) { echo json_encode(['success'=>false,'error'=>'Prepare failed']); exit(); }
    $types = $pt . str_repeat('i', count($subjectIds));
    $bind = [];
    if ($types !== '') { $bind[] = &$types; }
    for ($i=0; $i<count($pp); $i++) { $bind[] = &$pp[$i]; }
    for ($i=0; $i<count($subjectIds); $i++) { $sid = $subjectIds[$i]; $bind[] = &$subjectIds[$i]; }
    if ($types !== '') { call_user_func_array([$stmt,'bind_param'],$bind); }
    $stmt->execute();
    $res = $stmt->get_result();

    $byPasser = [];
    while ($row = $res->fetch_assoc()) {
      $pid = (int)$row['passer_id'];
      if (!isset($byPasser[$pid])) {
        $full = trim(implode(' ', array_filter([trim($row['first_name']??''), trim($row['middle_name']??''), trim($row['last_name']??'')])));
        if ($full === '') $full = (string)($row['name'] ?? '');
        $byPasser[$pid] = [
          'passer_id' => $pid,
          'full_name' => $full,
          'sex' => $row['sex'] ?? '',
          'course' => $row['course'] ?? '',
          'year_graduated' => $row['year_graduated'] ?? '',
          'exam_date' => $row['board_exam_date'] ?? '',
          'subjects' => []
        ];
      }
      $sid = isset($row['subject_id']) ? (int)$row['subject_id'] : 0;
      if ($sid && isset($subjects[$sid])) {
        $grade = is_null($row['grade']) ? null : (float)$row['grade'];
        $ti = (int)$subjects[$sid]['total_items']; if ($ti <= 0) $ti = 50;
        $pct = is_null($grade) ? null : max(0, min(100, round(($grade / $ti) * 100)));
        $byPasser[$pid]['subjects'][] = [
          'subject_id' => $sid,
          'subject_name' => $subjects[$sid]['name'],
          'result' => (string)($row['subj_result'] ?? ''),
          'grade' => $grade,
          'total_items' => $ti,
          'percent' => $pct
        ];
      }
    }
    $stmt->close();

    $mappedCount = count($subjectIds);
    $students = [];
    foreach ($byPasser as $pid => $p) {
      $passedCnt = 0; $failedCnt = 0; $knownCnt = 0; $sumPct = 0; $pctN = 0;
      foreach ($p['subjects'] as $sr) {
        $r = strtolower($sr['result'] ?? '');
        if ($r === 'passed') $passedCnt++;
        elseif ($r === 'failed') $failedCnt++;
        if (!is_null($sr['percent'])) { $sumPct += (int)$sr['percent']; $pctN++; }
        $knownCnt++;
      }
      $unknownCnt = max(0, $mappedCount - $knownCnt);
      $avgPct = $pctN ? round($sumPct / $pctN) : null;

      $include = true;
      $lf = strtolower($legendFilter);
      if ($lf === 'passed') { $include = ($passedCnt > 0); }
      elseif ($lf === 'failed') { $include = ($failedCnt > 0); }
      elseif ($lf === 'unknown') { $include = ($unknownCnt > 0); }

      if ($include) {
        $p['summary'] = [ 'passed' => $passedCnt, 'failed' => $failedCnt, 'unknown' => $unknownCnt, 'avg_percent' => $avgPct ];
        $students[] = $p;
      }
    }

    echo json_encode(['success' => true, 'data' => [ 'subjects' => array_values($subjects), 'students' => $students, 'mapped_count' => $mappedCount ]]);
    exit();
  }

  if ($action === 'trend_passing_rate') {
    $yearStart = isset($_GET['yearStart']) ? intval($_GET['yearStart']) : 2019;
    $yearEnd = isset($_GET['yearEnd']) ? intval($_GET['yearEnd']) : 2024;
    if ($yearStart > $yearEnd) { $t = $yearStart; $yearStart = $yearEnd; $yearEnd = $t; }
    $types = [];
    $res = $conn->query("SELECT id, exam_type_name FROM board_exam_types WHERE department='Criminal Justice Education' ORDER BY exam_type_name ASC");
    while ($r = $res->fetch_assoc()) { $types[] = [ 'id' => (int)$r['id'], 'name' => $r['exam_type_name'] ]; }
    $years = [];
    for ($y=$yearStart; $y<=$yearEnd; $y++) $years[] = $y;

    $series = [];
    foreach ($types as $t) {
      $sql = "SELECT YEAR(board_exam_date) AS y, COUNT(*) AS total, SUM(CASE WHEN result='Passed' THEN 1 ELSE 0 END) AS passed
              FROM board_passers
              WHERE department='Criminal Justice Education' AND board_exam_date BETWEEN ? AND ?
                AND (exam_type = ? OR board_exam_type = ? OR exam_type LIKE ?)
              GROUP BY y ORDER BY y ASC";
      $stmt = $conn->prepare($sql);
      $from = $yearStart.'-01-01'; $to = $yearEnd.'-12-31';
      $like = '%'.$t['name'].'%';
      $stmt->bind_param('sssss', $from, $to, $t['name'], $t['name'], $like);
      $stmt->execute();
      $res2 = $stmt->get_result();
      $byYear = [];
      while ($row = $res2->fetch_assoc()) {
        $yy = (int)$row['y']; $total = (int)$row['total']; $passed = (int)$row['passed'];
        $rate = $total ? round(($passed/$total)*100) : 0;
        $byYear[$yy] = [ 'rate' => $rate, 'passed' => $passed, 'total' => $total ];
      }
      $valuesByYear = [];
      foreach ($years as $yy) { $valuesByYear[(string)$yy] = isset($byYear[$yy]) ? $byYear[$yy]['rate'] : 0; }
      $series[] = [
        'exam_type_id' => $t['id'],
        'label' => $t['name'],
        'values_by_year' => $valuesByYear,
        'points_details' => array_combine(array_map('strval', $years), array_map(function($yy) use($byYear){ return isset($byYear[$yy]) ? $byYear[$yy] : ['rate'=>0,'passed'=>0,'total'=>0]; }, $years))
      ];
    }
    echo json_encode(['success' => true, 'data' => [ 'years' => $years, 'series' => $series ]]);
    exit();
  }

  if ($action === 'stacked_passing_composition') {
    $yearStart = isset($_GET['yearStart']) ? intval($_GET['yearStart']) : 2019;
    $yearEnd = isset($_GET['yearEnd']) ? intval($_GET['yearEnd']) : 2024;
    if ($yearStart > $yearEnd) { $t = $yearStart; $yearStart = $yearEnd; $yearEnd = $t; }
    $types = [];
    $res = $conn->query("SELECT id, exam_type_name FROM board_exam_types WHERE department='Criminal Justice Education' ORDER BY exam_type_name ASC");
    while ($r = $res->fetch_assoc()) { $types[] = [ 'id' => (int)$r['id'], 'name' => $r['exam_type_name'] ]; }
    $years = [];
    for ($y=$yearStart; $y<=$yearEnd; $y++) $years[] = $y;

    $passedByTypeYear = [];
    foreach ($types as $t) {
      $sql = "SELECT YEAR(board_exam_date) AS y, SUM(CASE WHEN result='Passed' THEN 1 ELSE 0 END) AS passed
              FROM board_passers
              WHERE department='Criminal Justice Education' AND board_exam_date BETWEEN ? AND ?
                AND (exam_type = ? OR board_exam_type = ? OR exam_type LIKE ?)
              GROUP BY y ORDER BY y ASC";
      $stmt = $conn->prepare($sql);
      $from = $yearStart.'-01-01'; $to = $yearEnd.'-12-31'; $like = '%'.$t['name'].'%';
      $stmt->bind_param('sssss', $from, $to, $t['name'], $t['name'], $like);
      $stmt->execute();
      $res2 = $stmt->get_result();
      while ($row = $res2->fetch_assoc()) {
        $yy = (int)$row['y']; $passed = (int)$row['passed'];
        if (!isset($passedByTypeYear[$t['id']])) $passedByTypeYear[$t['id']] = [];
        $passedByTypeYear[$t['id']][$yy] = $passed;
      }
    }

    $series = [];
    foreach ($types as $t) {
      $percentByYear = [];
      $passedByYear = [];
      foreach ($years as $yy) {
        $totalYear = 0;
        foreach ($types as $t2) { $totalYear += (int)($passedByTypeYear[$t2['id']][$yy] ?? 0); }
        $pv = (int)($passedByTypeYear[$t['id']][$yy] ?? 0);
        $pct = $totalYear ? round(($pv / $totalYear) * 100) : 0;
        $percentByYear[(string)$yy] = $pct;
        $passedByYear[(string)$yy] = $pv;
      }
      $series[] = [ 'exam_type_id' => $t['id'], 'label' => $t['name'], 'percent_by_year' => $percentByYear, 'passed_by_year' => $passedByYear ];
    }
    echo json_encode(['success' => true, 'data' => [ 'years' => $years, 'series' => $series ]]);
    exit();
  }

  if ($action === 'stacked_totals_by_year') {
    $yearStart = isset($_GET['yearStart']) ? intval($_GET['yearStart']) : 2019;
    $yearEnd = isset($_GET['yearEnd']) ? intval($_GET['yearEnd']) : 2024;
    if ($yearStart > $yearEnd) { $t = $yearStart; $yearStart = $yearEnd; $yearEnd = $t; }

    $types = [];
    $res = $conn->query("SELECT id, exam_type_name FROM board_exam_types WHERE department='Criminal Justice Education' ORDER BY exam_type_name ASC");
    while ($r = $res->fetch_assoc()) { $types[] = [ 'id' => (int)$r['id'], 'name' => $r['exam_type_name'] ]; }

    $years = [];
    for ($y=$yearStart; $y<=$yearEnd; $y++) $years[] = $y;

    $totalsByTypeYear = [];
    foreach ($types as $t) {
      $sql = "SELECT YEAR(board_exam_date) AS y, COUNT(*) AS total
              FROM board_passers
              WHERE department='Criminal Justice Education' AND board_exam_date BETWEEN ? AND ?
                AND (exam_type = ? OR board_exam_type = ? OR exam_type LIKE ?)
              GROUP BY y ORDER BY y ASC";
      $stmt = $conn->prepare($sql);
      $from = $yearStart.'-01-01'; $to = $yearEnd.'-12-31'; $like = '%'.$t['name'].'%';
      $stmt->bind_param('sssss', $from, $to, $t['name'], $t['name'], $like);
      $stmt->execute();
      $res2 = $stmt->get_result();
      while ($row = $res2->fetch_assoc()) {
        $yy = (int)$row['y']; $total = (int)$row['total'];
        if (!isset($totalsByTypeYear[$t['id']])) $totalsByTypeYear[$t['id']] = [];
        $totalsByTypeYear[$t['id']][$yy] = $total;
      }
    }

    $series = [];
    foreach ($types as $t) {
      $totalsByYear = [];
      foreach ($years as $yy) {
        $totalsByYear[(string)$yy] = (int)($totalsByTypeYear[$t['id']][$yy] ?? 0);
      }
      $series[] = [ 'exam_type_id' => $t['id'], 'label' => $t['name'], 'totals_by_year' => $totalsByYear ];
    }

    echo json_encode(['success' => true, 'data' => [ 'years' => $years, 'series' => $series ]]);
    exit();
  }

  if ($action === 'dept_passing_rate') {
    $yearStart = isset($_GET['yearStart']) ? intval($_GET['yearStart']) : 2019;
    $yearEnd = isset($_GET['yearEnd']) ? intval($_GET['yearEnd']) : 2024;
    if ($yearStart > $yearEnd) { $t = $yearStart; $yearStart = $yearEnd; $yearEnd = $t; }
    $from = $yearStart.'-01-01';
    $to = $yearEnd.'-12-31';

    $sql = "SELECT YEAR(board_exam_date) AS y,
                   COUNT(*) AS total,
                   SUM(CASE WHEN result='Passed' THEN 1 ELSE 0 END) AS passed
            FROM board_passers
            WHERE department='Criminal Justice Education' AND board_exam_date BETWEEN ? AND ?
            GROUP BY y ORDER BY y ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $from, $to);
    $stmt->execute();
    $res2 = $stmt->get_result();
    $byYear = [];
    while ($row = $res2->fetch_assoc()) {
      $yy = (int)$row['y']; $total = (int)$row['total']; $passed = (int)$row['passed'];
      $rate = $total ? round(($passed/$total)*100) : 0;
      $byYear[$yy] = [ 'rate' => $rate, 'passed' => $passed, 'total' => $total ];
    }

    $years = [];
    for ($y=$yearStart; $y<=$yearEnd; $y++) $years[] = $y;
    $valuesByYear = [];
    foreach ($years as $yy) { $valuesByYear[(string)$yy] = isset($byYear[$yy]) ? $byYear[$yy]['rate'] : 0; }

    $series = [[
      'label' => 'Passing Rate',
      'values_by_year' => $valuesByYear,
      'points_details' => array_combine(array_map('strval', $years), array_map(function($yy) use($byYear){ return isset($byYear[$yy]) ? $byYear[$yy] : ['rate'=>0,'passed'=>0,'total'=>0]; }, $years))
    ]];

    echo json_encode(['success' => true, 'data' => [ 'years' => $years, 'series' => $series ]]);
    exit();
  }

  if ($action === 'passing_rate_forecast') {
    $yearStart = isset($_GET['yearStart']) ? intval($_GET['yearStart']) : 2019;
    $yearEnd   = isset($_GET['yearEnd'])   ? intval($_GET['yearEnd'])   : 2024;
    $horizon   = isset($_GET['horizon'])   ? max(1, min(5, intval($_GET['horizon']))) : 2;
    if ($yearStart > $yearEnd) { $t = $yearStart; $yearStart = $yearEnd; $yearEnd = $t; }

    $boardExamTypeId = isset($_GET['boardExamTypeId']) ? intval($_GET['boardExamTypeId']) : 0;
    $typeName = '';
    if ($boardExamTypeId > 0) {
      $tstmt = $conn->prepare("SELECT exam_type_name FROM board_exam_types WHERE id=? LIMIT 1");
      if ($tstmt) { $tstmt->bind_param('i', $boardExamTypeId); $tstmt->execute(); $tr = $tstmt->get_result()->fetch_assoc(); $typeName = $tr['exam_type_name'] ?? ''; $tstmt->close(); }
    }

    $from = $yearStart.'-01-01';
    $to   = $yearEnd.'-12-31';

    $filterSql = "WHERE department='Criminal Justice Education' AND board_exam_date BETWEEN ? AND ?";
    $bindTypes = 'ss';
    $bindVals  = [ $from, $to ];
    if ($typeName !== '') {
      $filterSql .= " AND (exam_type = ? OR board_exam_type = ? OR exam_type LIKE ?)";
      $bindTypes .= 'sss';
      $bindVals[]  = $typeName; $bindVals[] = $typeName; $bindVals[] = '%'.$typeName.'%';
    }

    $sql = "SELECT YEAR(board_exam_date) AS y,
                   COUNT(*) AS total,
                   SUM(CASE WHEN result='Passed' THEN 1 ELSE 0 END) AS passed
            FROM board_passers
            $filterSql
            GROUP BY y
            ORDER BY y ASC";
    $stmt = $conn->prepare($sql);
    if (!$stmt) { echo json_encode(['success'=>false,'error'=>'Prepare failed']); exit(); }
    $refs = []; $refs[] = &$bindTypes; foreach ($bindVals as $k => $v) { $refs[] = &$bindVals[$k]; }
    call_user_func_array([$stmt, 'bind_param'], $refs);
    $stmt->execute();
    $res = $stmt->get_result();

    $knownYears = [];
    $actualRatesByYear = [];
    $pointDetails = [];
    while ($row = $res->fetch_assoc()) {
      $yy = (int)$row['y'];
      $total = (int)$row['total'];
      $passed = (int)$row['passed'];
      $rate = $total ? round(($passed/$total)*100, 2) : 0.0;
      $knownYears[] = $yy;
      $actualRatesByYear[(string)$yy] = $rate;
      $pointDetails[(string)$yy] = ['rate'=>$rate,'passed'=>$passed,'total'=>$total];
    }
    $stmt->close();

    $n = count($knownYears);
    $x = []; $y = [];
    for ($i=0; $i<$n; $i++) { $x[] = $i; $y[] = (float)$actualRatesByYear[(string)$knownYears[$i]]; }
    $slope = 0.0; $intercept = $n ? $y[$n-1] : 0.0; $r2 = null;
    if ($n >= 2) {
      $sumX = 0.0; $sumY = 0.0; $sumXY = 0.0; $sumX2 = 0.0; $sumY2 = 0.0;
      for ($i=0; $i<$n; $i++) { $sumX += $x[$i]; $sumY += $y[$i]; $sumXY += $x[$i]*$y[$i]; $sumX2 += $x[$i]*$x[$i]; $sumY2 += $y[$i]*$y[$i]; }
      $den = ($n * $sumX2 - $sumX * $sumX);
      $slope = ($den != 0.0) ? (($n * $sumXY - $sumX * $sumY) / $den) : 0.0;
      $intercept = ($n > 0) ? (($sumY - $slope * $sumX) / $n) : 0.0;
      $ssTot = 0.0; $ssRes = 0.0; $meanY = $sumY / $n;
      for ($i=0; $i<$n; $i++) { $yhat = $intercept + $slope * $x[$i]; $ssTot += ($y[$i]-$meanY)*($y[$i]-$meanY); $ssRes += ($y[$i]-$yhat)*($y[$i]-$yhat); }
      $r2 = ($ssTot != 0.0) ? max(0.0, min(1.0, 1.0 - ($ssRes / $ssTot))) : null;
    }

    $startYear = $n ? $knownYears[$n-1] : $yearEnd;
    $forecast = [];
    for ($h=1; $h <= $horizon; $h++) {
      $t = $n - 1 + $h;
      $ratePred = $intercept + $slope * $t;
      $ratePred = round(max(0, min(100, $ratePred)), 2);
      $forecast[] = [ 'year' => $startYear + $h, 'rate' => $ratePred ];
    }

    echo json_encode([
      'success' => true,
      'data' => [
        'scope' => ($typeName !== '' ? 'exam_type' : 'department'),
        'exam_type' => ($typeName !== '' ? $typeName : null),
        'actual' => [ 'years' => $knownYears, 'rates_by_year' => $actualRatesByYear, 'points_details' => $pointDetails ],
        'forecast' => $forecast,
        'model' => [ 'slope' => round($slope, 6), 'intercept' => round($intercept, 6), 'r2' => is_null($r2) ? null : round($r2, 4), 'n' => $n ]
      ]
    ]);
    exit();
  }

  http_response_code(400);
  echo json_encode(['success' => false, 'error' => 'Invalid action']);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['success' => false, 'error' => 'Query failed', 'details' => $e->getMessage()]);
}
?>