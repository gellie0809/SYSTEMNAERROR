<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
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

// Allowed departments
$allowedDepartments = [
    'Engineering',
    'Arts and Science',
    'Business Administration and Accountancy',
    'Criminal Justice Education',
    'Teacher Education'
];

// GET parameters
$department       = $_GET['department'] ?? 'Engineering';
$boardExamTypeId  = intval($_GET['boardExamTypeId'] ?? 0);
$examDateId       = intval($_GET['examDateId'] ?? 0);
$examYearParam    = intval($_GET['examYear'] ?? 0);
$subjectId        = intval($_GET['subjectId'] ?? 0);
$subjectResult    = trim($_GET['subjectResult'] ?? '');
$limit            = max(1, intval($_GET['limit'] ?? 500));

// Validate required params
if (!in_array($department, $allowedDepartments)) {
    echo json_encode(['success' => false, 'error' => 'Invalid department']);
    exit;
}
if ($boardExamTypeId <= 0 || $subjectId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Missing required parameters: boardExamTypeId, subjectId']);
    exit;
}

// Lookup canonical exam type and exam date
$typeName = ''; $dateStr = '';
try {
    $tstmt = $conn->prepare("SELECT exam_type_name FROM board_exam_types WHERE id = ? AND (is_deleted = 0 OR is_deleted IS NULL) LIMIT 1");
    if ($tstmt) { $tstmt->bind_param('i', $boardExamTypeId); $tstmt->execute(); $r=$tstmt->get_result()->fetch_assoc(); if ($r) $typeName=$r['exam_type_name']; $tstmt->close(); }
    if ($examDateId > 0) {
        $dstmt = $conn->prepare("SELECT exam_date FROM board_exam_dates WHERE id = ? AND (is_deleted = 0 OR is_deleted IS NULL) LIMIT 1");
        if ($dstmt) { $dstmt->bind_param('i', $examDateId); $dstmt->execute(); $r=$dstmt->get_result()->fetch_assoc(); if ($r) $dateStr=$r['exam_date']; $dstmt->close(); }
    }
} catch (Throwable $e) {}

// Year range fallback
$fromYear = $toYear = '';
if ($examYearParam > 0 && $dateStr === '') {
    $fromYear = $examYearParam.'-01-01';
    $toYear   = $examYearParam.'-12-31';
}

// Detect legacy columns
$hasRes = $hasPassedCol = false;
try {
    $hasRes = ($conn->query("SHOW COLUMNS FROM board_passer_subjects LIKE 'result'")->num_rows > 0);
    $hasPassedCol = ($conn->query("SHOW COLUMNS FROM board_passer_subjects LIKE 'passed'")->num_rows > 0);
} catch (Throwable $e) {}

// Determine passed/failed conditions
$condPassedSQL = $hasRes ? "(bps.result = 'Passed')" : ($hasPassedCol ? "(bps.passed IN ('1',1))" : '0');
$condFailedSQL = $hasRes ? "(bps.result = 'Failed')" : ($hasPassedCol ? "(bps.passed IN ('0',0))" : '0');

// Base SELECT
$select = "
    CONCAT_WS(' ', NULLIF(bp.first_name,''), NULLIF(bp.middle_name,''), NULLIF(bp.last_name,'')) AS full_name,
    COALESCE(NULLIF(CONCAT_WS(' ', NULLIF(bp.first_name,''), NULLIF(bp.middle_name,''), NULLIF(bp.last_name,'')), ''), bp.name) AS full_name_final,
    bp.sex, bp.course, bp.year_graduated, bp.result AS result, bp.board_exam_date AS exam_date
";

// Base SQL
$sql = "SELECT $select
        FROM board_passers bp
        LEFT JOIN board_passer_subjects bps ON bps.board_passer_id = bp.id AND bps.subject_id = ?
        WHERE bp.department = ? AND (bp.is_deleted = 0 OR bp.is_deleted IS NULL)";
$params = [$subjectId, $department];
$types = 'is';

// Filters
if ($typeName) { $sql.=" AND (bp.exam_type=? OR bp.board_exam_type=? OR bp.exam_type LIKE ?)"; $params[]=$typeName;$params[]=$typeName;$params[]="%$typeName%"; $types.='sss'; }
if ($dateStr) { $sql.=" AND bp.board_exam_date=?"; $params[]=$dateStr; $types.='s'; }
if ($fromYear && $toYear) { $sql.=" AND bp.board_exam_date BETWEEN ? AND ?"; $params[]=$fromYear; $params[]=$toYear; $types.='ss'; }

// Subject result filter
if ($subjectResult !== '') {
    if (strcasecmp($subjectResult,'Passed')===0) { $sql.=" AND bps.id IS NOT NULL AND $condPassedSQL"; }
    elseif (strcasecmp($subjectResult,'Failed')===0) { $sql.=" AND bps.id IS NOT NULL AND $condFailedSQL"; }
    elseif (strcasecmp($subjectResult,'Unknown')===0) { $sql.=" AND bps.id IS NULL"; }
    elseif ($hasRes) { $sql.=" AND bps.id IS NOT NULL AND bps.result=?"; $params[]=$subjectResult; $types.='s'; }
} else { $sql.=" AND bps.id IS NOT NULL"; }

// Order and limit
$sql .= " ORDER BY bp.last_name ASC, bp.first_name ASC LIMIT ?";
$params[] = $limit; $types.='i';

// Count query
$countSql = preg_replace('/^SELECT\s[\s\S]*?FROM\sboard_passers\s/i','SELECT COUNT(*) AS total FROM board_passers ',$sql);
$countSql = preg_replace('/ORDER BY[\s\S]*/i','',$countSql);
$countParams = $params; array_pop($countParams);

// Prepare + execute main query
$stmt = $conn->prepare($sql);
if (!$stmt) { echo json_encode(['success'=>false,'error'=>'Prepare failed']); exit; }
$bind = [&$types]; foreach ($params as $p) { $bind[]=&$p; } call_user_func_array([$stmt,'bind_param'],$bind);
if (!$stmt->execute()) { echo json_encode(['success'=>false,'error'=>'Execute failed: '.$stmt->error]); exit; }
$res = $stmt->get_result();
$rows = [];
while ($r=$res->fetch_assoc()) {
    $rows[]= [
        'full_name'=>$r['full_name_final'] ?? '',
        'sex'=>$r['sex'] ?? '',
        'course'=>$r['course'] ?? '',
        'year_graduated'=>$r['year_graduated'] ?? '',
        'result'=>$r['result'] ?? '',
        'exam_date'=>$r['exam_date'] ?? ''
    ];
}

// Execute count query
$totalCount = 0;
$cstmt = $conn->prepare($countSql);
if ($cstmt) {
    $bind2=[&$countTypes]; foreach ($countParams as $p) { $bind2[]=&$p; } call_user_func_array([$cstmt,'bind_param'],$bind2);
    if ($cstmt->execute()) { $cres=$cstmt->get_result(); $crow=$cres->fetch_assoc(); $totalCount=intval($crow['total']??0); }
    $cstmt->close();
}

$stmt->close();
$conn->close();

echo json_encode(['success'=>true,'count'=>count($rows),'total_count'=>$totalCount,'data'=>$rows]);