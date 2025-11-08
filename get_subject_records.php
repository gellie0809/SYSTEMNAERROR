<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
// Prevent PHP notices/warnings from corrupting JSON output
@ini_set('display_errors', '0');
@ini_set('html_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_STRICT & ~E_DEPRECATED);
require_once __DIR__ . '/db_config.php';

try {
    $conn = getDbConnection();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'DB connection failed']);
    exit;
}

$boardExamTypeId = isset($_GET['boardExamTypeId']) ? intval($_GET['boardExamTypeId']) : 0;
$examDateId = isset($_GET['examDateId']) ? intval($_GET['examDateId']) : 0;
$examYearParam = isset($_GET['examYear']) ? intval($_GET['examYear']) : 0;
$subjectId = isset($_GET['subjectId']) ? intval($_GET['subjectId']) : 0;
$subjectResult = isset($_GET['subjectResult']) ? trim($_GET['subjectResult']) : '';
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 500;
if ($limit <= 0) $limit = 500;

if ($boardExamTypeId <= 0 || $subjectId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Missing required parameters: boardExamTypeId, subjectId']);
    exit;
}

// Look up canonical exam type name and optional date string
$typeName = '';
$dateStr = '';
$tstmt = null; $dstmt = null;
try {
    $tstmt = $conn->prepare("SELECT exam_type_name FROM board_exam_types WHERE id = ? LIMIT 1");
    if ($tstmt) { $tstmt->bind_param('i', $boardExamTypeId); $tstmt->execute(); $tr = $tstmt->get_result()->fetch_assoc(); if ($tr && !empty($tr['exam_type_name'])) $typeName = $tr['exam_type_name']; $tstmt->close(); }
    if ($examDateId > 0) {
        $dstmt = $conn->prepare("SELECT exam_date FROM board_exam_dates WHERE id = ? LIMIT 1");
        if ($dstmt) { $dstmt->bind_param('i', $examDateId); $dstmt->execute(); $dr = $dstmt->get_result()->fetch_assoc(); if ($dr && !empty($dr['exam_date'])) $dateStr = $dr['exam_date']; $dstmt->close(); }
    }
} catch (Throwable $e) { /* ignore lookup errors; fall back to no filters */ }

// If year provided and no exact date, convert to a date range
$fromYear = '';
$toYear = '';
if ($examYearParam > 0 && $dateStr === '') { $fromYear = $examYearParam.'-01-01'; $toYear = $examYearParam.'-12-31'; }

// Build base SELECT for people-friendly fields
$select = "CONCAT_WS(' ', NULLIF(first_name,''), NULLIF(middle_name,''), NULLIF(last_name,'')) AS full_name, ";
$select .= "COALESCE(NULLIF(CONCAT_WS(' ', NULLIF(first_name,''), NULLIF(middle_name,''), NULLIF(last_name,'')), ''), name) AS full_name_final, ";
$select .= "sex, course, year_graduated, bp.result AS result, bp.board_exam_date AS exam_date";

// Detect available columns in board_passer_subjects for portable filters
$hasRes = false; $hasPassedCol = false;
try {
    if ($cr = $conn->query("SHOW COLUMNS FROM board_passer_subjects LIKE 'result'")) { $hasRes = ($cr->num_rows > 0); }
    if ($cp = $conn->query("SHOW COLUMNS FROM board_passer_subjects LIKE 'passed'")) { $hasPassedCol = ($cp->num_rows > 0); }
} catch (Throwable $e) { /* ignore */ }
// Build condition snippets based on existing columns
$condPassedSQL = $hasRes ? "(bps.result = 'Passed')" : ($hasPassedCol ? "(bps.passed IN ('1',1))" : '0');
$condFailedSQL = $hasRes ? "(bps.result = 'Failed')" : ($hasPassedCol ? "(bps.passed IN ('0',0))" : '0');

// Join passers and filter by department, exam type name (canonical), and optional date string
$sql = "SELECT $select
    FROM board_passers bp
    LEFT JOIN board_passer_subjects bps ON bps.board_passer_id = bp.id AND bps.subject_id = ?
    WHERE bp.department = 'Engineering'";
$params = [$subjectId];
$types = 'i';

if ($typeName !== '') {
    $sql .= " AND (bp.exam_type = ? OR bp.board_exam_type = ? OR bp.exam_type LIKE ?)";
    $params[] = $typeName; $params[] = $typeName; $params[] = "%".$typeName."%"; $types .= 'sss';
}
if ($dateStr !== '') {
    $sql .= " AND bp.board_exam_date = ?";
    $params[] = $dateStr; $types .= 's';
}
if ($fromYear !== '' && $toYear !== '') {
    $sql .= " AND bp.board_exam_date BETWEEN ? AND ?";
    $params[] = $fromYear; $params[] = $toYear; $types .= 'ss';
}

// Optional filter: only those who got a particular subject result (normalize for legacy 'passed' boolean)
if ($subjectResult !== '') {
    if (strcasecmp($subjectResult, 'Passed') === 0) {
        $sql .= " AND bps.id IS NOT NULL AND $condPassedSQL";
    } elseif (strcasecmp($subjectResult, 'Failed') === 0) {
        $sql .= " AND bps.id IS NOT NULL AND $condFailedSQL";
    } elseif (strcasecmp($subjectResult, 'Unknown') === 0) {
        // Missing subject record
        $sql .= " AND bps.id IS NULL";
    } else if ($hasRes) {
        $sql .= " AND bps.id IS NOT NULL AND bps.result = ?";
        $params[] = $subjectResult; $types .= 's';
    }
} else {
    // When no specific subjectResult requested, default to only those with a subject record
    $sql .= " AND bps.id IS NOT NULL";
}

$sql .= " ORDER BY bp.last_name ASC, bp.first_name ASC LIMIT ?";
$params[] = $limit; $types .= 'i';

// Build count query (same filters, no ORDER BY/LIMIT)
$countSql = "SELECT COUNT(*) AS total
             FROM board_passers bp
             LEFT JOIN board_passer_subjects bps ON bps.board_passer_id = bp.id AND bps.subject_id = ?
             WHERE bp.department = 'Engineering'";
$countParams = [$subjectId];
$countTypes = 'i';
if ($typeName !== '') { $countSql .= " AND (bp.exam_type = ? OR bp.board_exam_type = ? OR bp.exam_type LIKE ?)"; $countParams[] = $typeName; $countParams[] = $typeName; $countParams[] = "%".$typeName."%"; $countTypes .= 'sss'; }
if ($dateStr !== '') { $countSql .= " AND bp.board_exam_date = ?"; $countParams[] = $dateStr; $countTypes .= 's'; }
if ($fromYear !== '' && $toYear !== '') { $countSql .= " AND bp.board_exam_date BETWEEN ? AND ?"; $countParams[] = $fromYear; $countParams[] = $toYear; $countTypes .= 'ss'; }
if ($subjectResult !== '') {
    if (strcasecmp($subjectResult, 'Passed') === 0) {
        $countSql .= " AND bps.id IS NOT NULL AND $condPassedSQL";
    } elseif (strcasecmp($subjectResult, 'Failed') === 0) {
        $countSql .= " AND bps.id IS NOT NULL AND $condFailedSQL";
    } elseif (strcasecmp($subjectResult, 'Unknown') === 0) {
        $countSql .= " AND bps.id IS NULL";
    } else if ($hasRes) {
        $countSql .= " AND bps.id IS NOT NULL AND bps.result = ?"; $countParams[] = $subjectResult; $countTypes .= 's';
    }
} else {
    $countSql .= " AND bps.id IS NOT NULL";
}
// note: do not append legacy OR conditions again; the portable conditions above already handle both schemas

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Prepare failed']);
    exit;
}
// bind with references to satisfy mysqli
{
    $bind = [];
    $bind[] = &$types;
    for ($i=0; $i<count($params); $i++) { $bind[] = &$params[$i]; }
    call_user_func_array([$stmt, 'bind_param'], $bind);
}
if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'error' => 'Execute failed: ' . $stmt->error]);
    exit;
}
$res = $stmt->get_result();
$rows = [];
while ($r = $res->fetch_assoc()) {
    $full = $r['full_name_final'] ?? '';
    $rows[] = [
        'full_name' => $full,
        'sex' => $r['sex'] ?? '',
        'course' => $r['course'] ?? '',
        'year_graduated' => $r['year_graduated'] ?? '',
        'result' => $r['result'] ?? '',
        'exam_date' => $r['exam_date'] ?? ''
    ];
}

$totalCount = 0;
$cstmt = $conn->prepare($countSql);
if ($cstmt) {
    // bind with references for count query too
    $bind2 = [];
    $bind2[] = &$countTypes;
    for ($i=0;$i<count($countParams);$i++){ $bind2[] = &$countParams[$i]; }
    call_user_func_array([$cstmt, 'bind_param'], $bind2);
    if ($cstmt->execute()) {
        $cres = $cstmt->get_result();
        $crow = $cres->fetch_assoc();
        $totalCount = intval($crow['total'] ?? 0);
    }
    $cstmt->close();
}

$stmt->close();
$conn->close();

echo json_encode(['success' => true, 'count' => count($rows), 'total_count' => $totalCount, 'data' => $rows]);
