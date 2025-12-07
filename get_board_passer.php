<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db_config.php';

// Allowed departments
$allowedDepartments = [
    'Engineering',
    'Arts and Science',
    'Business Administration and Accountancy',
    'Criminal Justice Education',
    'Teacher Education'
];

try {
    $conn = getDbConnection();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'DB connection failed']);
    exit;
}

// Get department parameter (default to Engineering)
$department = isset($_GET['department']) ? trim($_GET['department']) : 'Engineering';
if (!in_array($department, $allowedDepartments)) {
    echo json_encode(['success' => false, 'error' => 'Invalid department']);
    exit;
}

// Filters
$boardExamType = isset($_GET['boardExamType']) ? trim($_GET['boardExamType']) : '';
$examDate = isset($_GET['examDate']) ? trim($_GET['examDate']) : '';
$boardExamTypeId = isset($_GET['boardExamTypeId']) ? trim($_GET['boardExamTypeId']) : '';
$examDateId = isset($_GET['examDateId']) ? trim($_GET['examDateId']) : '';
$fromDate = isset($_GET['fromDate']) ? trim($_GET['fromDate']) : '';
$toDate = isset($_GET['toDate']) ? trim($_GET['toDate']) : '';
$filterType = isset($_GET['filterType']) ? trim($_GET['filterType']) : '';
$filterValue = isset($_GET['filterValue']) ? trim($_GET['filterValue']) : '';
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 500;
if ($limit <= 0) $limit = 500;

$notPassed = isset($_GET['notPassed']) && ($_GET['notPassed'] === '1' || $_GET['notPassed'] === 'true' || $_GET['notPassed'] === 'on');
$resultParam = isset($_GET['result']) ? trim($_GET['result']) : '';
$notResult = isset($_GET['notResult']) ? trim($_GET['notResult']) : '';

// Base SELECT
$select = "CONCAT_WS(' ', NULLIF(first_name,''), NULLIF(middle_name,''), NULLIF(last_name,'')) AS full_name, ";
$select .= "COALESCE(NULLIF(CONCAT_WS(' ', NULLIF(first_name,''), NULLIF(middle_name,''), NULLIF(last_name,'')), ''), name) AS full_name_final, ";
$select .= "sex, course, year_graduated, result, exam_type, board_exam_date";

$sql = "SELECT $select FROM board_passers WHERE department = ?";
$params = [$department];
$types = 's';

// Filters
if ($boardExamType !== '') {
    $sql .= " AND (exam_type = ? OR board_exam_type = ? OR exam_type LIKE ?)";
    $params[] = $boardExamType;
    $params[] = $boardExamType;
    $params[] = "%$boardExamType%";
    $types .= 'sss';
}

if ($boardExamTypeId !== '') {
    $stmt2 = $conn->prepare("SELECT exam_type_name FROM board_exam_types WHERE id = ? LIMIT 1");
    if ($stmt2) {
        $stmt2->bind_param('i', $boardExamTypeId);
        $stmt2->execute();
        $r2 = $stmt2->get_result()->fetch_assoc();
        if ($r2 && !empty($r2['exam_type_name'])) {
            $sql .= " AND (exam_type = ? OR board_exam_type = ? OR exam_type LIKE ?)";
            $params[] = $r2['exam_type_name'];
            $params[] = $r2['exam_type_name'];
            $params[] = "%" . $r2['exam_type_name'] . "%";
            $types .= 'sss';
        }
        $stmt2->close();
    }
}

if ($notResult !== '' && !(strtolower($filterType) === 'result' && $filterValue !== '') && $resultParam === '') {
    $sql .= " AND result <> ?";
    $params[] = $notResult;
    $types .= 's';
}

if ($notPassed && !(strtolower($filterType) === 'result' && $filterValue !== '') && $resultParam === '') {
    $sql .= " AND result <> 'Passed'";
}

if ($examDate !== '') {
    $sql .= " AND board_exam_date = ?";
    $params[] = $examDate;
    $types .= 's';
}

if ($examDateId !== '') {
    $stmt3 = $conn->prepare("SELECT exam_date FROM board_exam_dates WHERE id = ? LIMIT 1");
    if ($stmt3) {
        $stmt3->bind_param('i', $examDateId);
        $stmt3->execute();
        $r3 = $stmt3->get_result()->fetch_assoc();
        if ($r3 && !empty($r3['exam_date'])) {
            $sql .= " AND board_exam_date = ?";
            $params[] = $r3['exam_date'];
            $types .= 's';
        }
        $stmt3->close();
    }
}

if ($fromDate !== '' && $toDate !== '' && $examDate === '' && $examDateId === '') {
    $sql .= " AND board_exam_date BETWEEN ? AND ?";
    $params[] = $fromDate;
    $params[] = $toDate;
    $types .= 'ss';
}

if ($filterType !== '' && $filterValue !== '') {
    switch (strtolower($filterType)) {
        case 'gender':
        case 'sex':
            $sql .= " AND sex = ?";
            $params[] = $filterValue;
            $types .= 's';
            break;
        case 'result':
            $sql .= " AND result = ?";
            $params[] = $filterValue;
            $types .= 's';
            break;
        case 'exam_type':
            $sql .= " AND (exam_type = ? OR exam_type LIKE ? OR board_exam_type = ?)";
            $params[] = $filterValue;
            $params[] = "%$filterValue%";
            $params[] = $filterValue;
            $types .= 'sss';
            break;
        case 'year':
            if ($fromDate === '' || $toDate === '') {
                $sql .= " AND YEAR(board_exam_date) = ?";
                $params[] = intval($filterValue);
                $types .= 'i';
            }
            break;
        default:
            $sql .= " AND (CONCAT_WS(' ', first_name, middle_name, last_name) LIKE ? OR name LIKE ?)";
            $params[] = "%$filterValue%";
            $params[] = "%$filterValue%";
            $types .= 'ss';
    }
}

if ($resultParam !== '' && !(strtolower($filterType) === 'result' && $filterValue !== '')) {
    $sql .= " AND result = ?";
    $params[] = $resultParam;
    $types .= 's';
}

$sql .= " ORDER BY last_name ASC, first_name ASC LIMIT ?";
$params[] = $limit;
$types .= 'i';

// Prepare and execute
$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Prepare failed', 'sql' => $sql]);
    exit;
}

if (!empty($params)) {
    $bind_names[] = $types;
    for ($i=0; $i<count($params); $i++) {
        $bind_name = 'bind' . $i;
        $$bind_name = $params[$i];
        $bind_names[] = &$$bind_name;
    }
    call_user_func_array([$stmt, 'bind_param'], $bind_names);
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
        'exam_date' => $r['board_exam_date'] ?? ''
    ];
}

echo json_encode([
    'success' => true,
    'count' => count($rows),
    'data' => $rows
]);

$stmt->close();
$conn->close();
?>