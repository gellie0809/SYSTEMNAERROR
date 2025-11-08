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

// Base select - return a friendly full_name field (tries first_name/middle_name/last_name, falls back to name)
$select = "CONCAT_WS(' ', NULLIF(first_name,''), NULLIF(middle_name,''), NULLIF(last_name,'')) AS full_name, ";
$select .= "COALESCE(NULLIF(CONCAT_WS(' ', NULLIF(first_name,''), NULLIF(middle_name,''), NULLIF(last_name,'')), ''), name) AS full_name_fallback, ";
$select .= "COALESCE(NULLIF(CONCAT_WS(' ', NULLIF(first_name,''), NULLIF(middle_name,''), NULLIF(last_name,'')), ''), name) AS full_name_final, ";
$select .= "sex, course, year_graduated, result, exam_type, board_exam_date";

$sql = "SELECT $select FROM board_passers WHERE department = 'Engineering'";
$params = [];
$types = '';

// apply boardExamType filter if provided (match either exam_type or board_exam_type columns)
if ($boardExamType !== '') {
    $sql .= " AND (exam_type = ? OR board_exam_type = ? OR exam_type LIKE ?)";
    $params[] = $boardExamType; $params[] = $boardExamType; $params[] = "%$boardExamType%";
    $types .= 'sss';
}

// if an authoritative boardExamTypeId was provided, translate it to the DB value and prefer it
if ($boardExamTypeId !== '') {
    $stmt2 = $conn->prepare("SELECT exam_type_name FROM board_exam_types WHERE id = ? LIMIT 1");
    if ($stmt2) {
        $stmt2->bind_param('i', $boardExamTypeId);
        $stmt2->execute();
        $r2 = $stmt2->get_result()->fetch_assoc();
        if ($r2 && !empty($r2['exam_type_name'])) {
            // prefer exact match on exam_type column
            $sql .= " AND (exam_type = ? OR board_exam_type = ? OR exam_type LIKE ?)";
            $params[] = $r2['exam_type_name']; $params[] = $r2['exam_type_name']; $params[] = "%" . $r2['exam_type_name'] . "%";
            $types .= 'sss';
        }
        $stmt2->close();
    }
}

// if client asked for a notResult (exclude a specific result value) and no explicit result filter was provided, bind and exclude
if ($notResult !== '' && !(strtolower($filterType) === 'result' && $filterValue !== '') && $resultParam === '') {
    $sql .= " AND result <> ?";
    $params[] = $notResult; $types .= 's';
}

// if client asked for notPassed (group) and no explicit result filter was provided, show rows where result <> 'Passed'
if ($notPassed && !(strtolower($filterType) === 'result' && $filterValue !== '') && $resultParam === '') {
    $sql .= " AND result <> 'Passed'";
}

// apply examDate filter if provided (board_exam_date field expected to match the date string)
if ($examDate !== '') {
    $sql .= " AND board_exam_date = ?";
    $params[] = $examDate; $types .= 's';
}

// if an examDateId was provided, look up the canonical date and use it
if ($examDateId !== '') {
    $stmt3 = $conn->prepare("SELECT exam_date FROM board_exam_dates WHERE id = ? LIMIT 1");
    if ($stmt3) {
        $stmt3->bind_param('i', $examDateId);
        $stmt3->execute();
        $r3 = $stmt3->get_result()->fetch_assoc();
        if ($r3 && !empty($r3['exam_date'])) {
            $sql .= " AND board_exam_date = ?";
            $params[] = $r3['exam_date']; $types .= 's';
        }
        $stmt3->close();
    }
}

// apply date range if provided (typically for year-wide selection); only when no exact examDate/examDateId filter is set
if ($fromDate !== '' && $toDate !== '' && $examDate === '' && $examDateId === '') {
    $sql .= " AND board_exam_date BETWEEN ? AND ?";
    $params[] = $fromDate; $params[] = $toDate; $types .= 'ss';
}

// apply click-filter (gender/result/exam_type/year)
if ($filterType !== '' && $filterValue !== '') {
    if (strtolower($filterType) === 'gender' || strtolower($filterType) === 'sex') {
        $sql .= " AND sex = ?";
        $params[] = $filterValue; $types .= 's';
    } elseif (strtolower($filterType) === 'result') {
        $sql .= " AND result = ?";
        $params[] = $filterValue; $types .= 's';
    } elseif (strtolower($filterType) === 'exam_type') {
        $sql .= " AND (exam_type = ? OR exam_type LIKE ? OR board_exam_type = ?)";
        $params[] = $filterValue; $params[] = "%$filterValue%"; $params[] = $filterValue;
        $types .= 'sss';
    } elseif (strtolower($filterType) === 'year') {
        // Year selections are typically supplied with fromDate/toDate already.
        // If not, fall back to YEAR(board_exam_date) = ?.
        if ($fromDate === '' || $toDate === '') {
            $sql .= " AND YEAR(board_exam_date) = ?";
            $params[] = intval($filterValue); $types .= 'i';
        }
    } else {
        // fallback: try to match name
        $sql .= " AND (CONCAT_WS(' ', first_name, middle_name, last_name) LIKE ? OR name LIKE ?)";
        $params[] = "%$filterValue%"; $params[] = "%$filterValue%";
        $types .= 'ss';
    }
}

// explicit result param (e.g. ?result=Passed) - respect it unless filterType already applied a result
if ($resultParam !== '' && !(strtolower($filterType) === 'result' && $filterValue !== '')) {
    $sql .= " AND result = ?";
    $params[] = $resultParam; $types .= 's';
}

// keep original query but add limit
$sql .= " ORDER BY last_name ASC, first_name ASC LIMIT ?";
$params[] = $limit; $types .= 'i';

// Build a COUNT(*) query with the same WHERE filters (exclude the LIMIT)
$countSql = preg_replace('/^SELECT\s[\s\S]*?FROM\sboard_passers\s/i', 'SELECT COUNT(*) AS total FROM board_passers ', $sql);
$countSql = preg_replace('/ORDER BY[\s\S]*/i', '', $countSql);
$countParams = $params; // includes limit at end
array_pop($countParams); // remove limit for count

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Prepare failed', 'sql' => $sql]);
    exit;
}

if (!empty($params)) {
    // bind params dynamically
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
    // prefer full_name_final if available otherwise full_name_fallback
    $full = $r['full_name_final'] ?? ($r['full_name_fallback'] ?? '');
    $rows[] = [
        'full_name' => $full,
        'sex' => $r['sex'] ?? '',
        'course' => $r['course'] ?? '',
        'year_graduated' => $r['year_graduated'] ?? '',
        'result' => $r['result'] ?? '',
        // include exam date for clarity in the modal
        'exam_date' => $r['board_exam_date'] ?? ''
    ];
}

// execute count query to get total matching rows
$totalCount = null;
$cstmt = $conn->prepare($countSql);
if ($cstmt) {
    if (!empty($countParams)) {
        $bind_names = [];
        $typesCnt = '';
        foreach ($countParams as $p) {
            if (is_int($p)) $typesCnt .= 'i'; elseif (is_float($p)) $typesCnt .= 'd'; else $typesCnt .= 's';
        }
        $bind_names[] = $typesCnt;
        for ($i=0;$i<count($countParams);$i++){
            $bn = 'bparam'.$i; $$bn = $countParams[$i]; $bind_names[] = &$$bn;
        }
        call_user_func_array([$cstmt, 'bind_param'], $bind_names);
    }
    if ($cstmt->execute()) {
        $cres = $cstmt->get_result();
        $crow = $cres->fetch_assoc();
        $totalCount = intval($crow['total'] ?? 0);
    }
    $cstmt->close();
}

echo json_encode(['success' => true, 'count' => count($rows), 'total_count' => $totalCount, 'data' => $rows]);

$stmt->close();
$conn->close();
