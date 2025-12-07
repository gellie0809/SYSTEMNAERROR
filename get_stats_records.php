<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
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

// Get department from query param (default: Engineering)
$department = isset($_GET['department']) ? trim($_GET['department']) : 'Engineering';
if (!in_array($department, $allowedDepartments)) {
    echo json_encode(['success' => false, 'error' => 'Invalid department']);
    exit;
}

// Filters
$boardExamType   = $_GET['boardExamType'] ?? '';
$examDate        = $_GET['examDate'] ?? '';
$boardExamTypeId = $_GET['boardExamTypeId'] ?? '';
$examDateId      = $_GET['examDateId'] ?? '';
$fromDate        = $_GET['fromDate'] ?? '';
$toDate          = $_GET['toDate'] ?? '';
$filterType      = $_GET['filterType'] ?? '';
$filterValue     = $_GET['filterValue'] ?? '';
$limit           = max(1, intval($_GET['limit'] ?? 500));

$notPassed       = isset($_GET['notPassed']) && in_array($_GET['notPassed'], ['1','true','on']);
$resultParam     = $_GET['result'] ?? '';
$notResult       = $_GET['notResult'] ?? '';

// Base SELECT
$select = "
    CONCAT_WS(' ', NULLIF(first_name,''), NULLIF(middle_name,''), NULLIF(last_name,'')) AS full_name,
    COALESCE(NULLIF(CONCAT_WS(' ', NULLIF(first_name,''), NULLIF(middle_name,''), NULLIF(last_name,'')), ''), name) AS full_name_final,
    sex, course, year_graduated, result, exam_type, board_exam_date
";

$sql = "SELECT $select FROM board_passers WHERE department = ? AND is_deleted = 0";
$params = [$department];
$types  = 's';

// Apply filters
if ($boardExamType) {
    $sql .= " AND (exam_type = ? OR board_exam_type = ? OR exam_type LIKE ?)";
    $params[] = $boardExamType; $params[] = $boardExamType; $params[] = "%$boardExamType%";
    $types .= 'sss';
}

if ($boardExamTypeId) {
    $stmt2 = $conn->prepare("SELECT exam_type_name FROM board_exam_types WHERE id = ? AND (is_deleted = 0 OR is_deleted IS NULL) LIMIT 1");
    if ($stmt2) {
        $stmt2->bind_param('i', $boardExamTypeId);
        $stmt2->execute();
        $r2 = $stmt2->get_result()->fetch_assoc();
        if ($r2 && $r2['exam_type_name']) {
            $sql .= " AND (exam_type = ? OR board_exam_type = ? OR exam_type LIKE ?)";
            $params[] = $r2['exam_type_name']; $params[] = $r2['exam_type_name']; $params[] = "%".$r2['exam_type_name']."%";
            $types .= 'sss';
        }
        $stmt2->close();
    }
}

if ($notResult && !$resultParam && strtolower($filterType) !== 'result') {
    $sql .= " AND result <> ?";
    $params[] = $notResult;
    $types .= 's';
}

if ($notPassed && !$resultParam && strtolower($filterType) !== 'result') {
    $sql .= " AND result <> 'Passed'";
}

if ($examDate) {
    $sql .= " AND board_exam_date = ?";
    $params[] = $examDate;
    $types .= 's';
}

if ($examDateId) {
    $stmt3 = $conn->prepare("SELECT exam_date FROM board_exam_dates WHERE id = ? AND (is_deleted = 0 OR is_deleted IS NULL) LIMIT 1");
    if ($stmt3) {
        $stmt3->bind_param('i', $examDateId);
        $stmt3->execute();
        $r3 = $stmt3->get_result()->fetch_assoc();
        if ($r3 && $r3['exam_date']) {
            $sql .= " AND board_exam_date = ?";
            $params[] = $r3['exam_date'];
            $types .= 's';
        }
        $stmt3->close();
    }
}

if ($fromDate && $toDate && !$examDate && !$examDateId) {
    $sql .= " AND board_exam_date BETWEEN ? AND ?";
    $params[] = $fromDate; $params[] = $toDate;
    $types .= 'ss';
}

// Click-filters
if ($filterType && $filterValue) {
    switch (strtolower($filterType)) {
        case 'gender':
        case 'sex':
            $sql .= " AND sex = ?";
            $params[] = $filterValue; $types .= 's';
            break;
        case 'result':
            $sql .= " AND result = ?";
            $params[] = $filterValue; $types .= 's';
            break;
        case 'exam_type':
            $sql .= " AND (exam_type = ? OR exam_type LIKE ? OR board_exam_type = ?)";
            $params[] = $filterValue; $params[] = "%$filterValue%"; $params[] = $filterValue; $types .= 'sss';
            break;
        case 'year':
            if (!$fromDate || !$toDate) {
                $sql .= " AND YEAR(board_exam_date) = ?";
                $params[] = intval($filterValue); $types .= 'i';
            }
            break;
        default:
            $sql .= " AND (CONCAT_WS(' ', first_name, middle_name, last_name) LIKE ? OR name LIKE ?)";
            $params[] = "%$filterValue%"; $params[] = "%$filterValue%"; $types .= 'ss';
    }
}

if ($resultParam && strtolower($filterType) !== 'result') {
    $sql .= " AND result = ?";
    $params[] = $resultParam; $types .= 's';
}

// Order and limit
$sql .= " ORDER BY last_name ASC, first_name ASC LIMIT ?";
$params[] = $limit; $types .= 'i';

// Prepare and bind
$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Prepare failed', 'sql'=>$sql]);
    exit;
}

if ($params) {
    $bind_names[] = $types;
    for ($i=0; $i<count($params); $i++) {
        $bind_name = 'bind'.$i; $$bind_name = $params[$i]; $bind_names[] = &$$bind_name;
    }
    call_user_func_array([$stmt, 'bind_param'], $bind_names);
}

if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'error'=>'Execute failed: '.$stmt->error]);
    exit;
}

$res = $stmt->get_result();
$rows = [];
while ($r = $res->fetch_assoc()) {
    $full = $r['full_name_final'] ?? $r['full_name_fallback'] ?? '';
    $rows[] = [
        'full_name' => $full,
        'sex' => $r['sex'] ?? '',
        'course' => $r['course'] ?? '',
        'year_graduated' => $r['year_graduated'] ?? '',
        'result' => $r['result'] ?? '',
        'exam_date' => $r['board_exam_date'] ?? ''
    ];
}

// Count total
$countSql = preg_replace('/^SELECT\s[\s\S]*?FROM\sboard_passers\s/i', 'SELECT COUNT(*) AS total FROM board_passers ', $sql);
$countSql = preg_replace('/ORDER BY[\s\S]*/i','', $countSql);
$countParams = $params; array_pop($countParams); // remove limit

$cstmt = $conn->prepare($countSql);
$totalCount = null;
if ($cstmt) {
    if ($countParams) {
        $bind_names=[]; $typesCnt='';
        foreach ($countParams as $p) { $typesCnt .= is_int($p)?'i':(is_float($p)?'d':'s'); }
        $bind_names[] = $typesCnt;
        for ($i=0;$i<count($countParams);$i++){ $bn='bparam'.$i; $$bn=$countParams[$i]; $bind_names[]=&$$bn; }
        call_user_func_array([$cstmt,'bind_param'],$bind_names);
    }
    if ($cstmt->execute()) {
        $crow=$cstmt->get_result()->fetch_assoc();
        $totalCount=intval($crow['total']??0);
    }
    $cstmt->close();
}

echo json_encode([
    'success'=>true,
    'count'=>count($rows),
    'total_count'=>$totalCount,
    'data'=>$rows
]);

$stmt->close();
$conn->close();