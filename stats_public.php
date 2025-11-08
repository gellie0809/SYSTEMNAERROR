<?php
header('Content-Type: application/json');

require_once __DIR__ . '/db_config.php';

// Allow only aggregate, non-sensitive queries
$action = $_GET['action'] ?? 'dept_passing_rate';
$department = trim($_GET['department'] ?? '');
$yearStart = isset($_GET['yearStart']) ? intval($_GET['yearStart']) : 2019;
$yearEnd   = isset($_GET['yearEnd'])   ? intval($_GET['yearEnd'])   : 2024;
if ($yearStart > $yearEnd) { $t = $yearStart; $yearStart = $yearEnd; $yearEnd = $t; }
$from = $yearStart . '-01-01';
$to   = $yearEnd   . '-12-31';

// Whitelist known departments to prevent arbitrary SQL injection via department param
$DEPTS = [
  'Engineering' => [ 'label' => 'College of Engineering', 'theme' => 'green' ],
  'Arts and Science' => [ 'label' => 'College of Arts and Science', 'theme' => 'pink' ],
  'Business Administration and Accountancy' => [ 'label' => 'College of Business Administration and Accountancy', 'theme' => 'yellow' ],
  'Criminal Justice Education' => [ 'label' => 'College of Criminal Justice Education', 'theme' => 'red' ],
  'Teacher Education' => [ 'label' => 'College of Teacher Education', 'theme' => 'blue' ],
];

try {
  $conn = getDbConnection();

  if ($action === 'departments') {
    // Provide list of departments and their presentation details
    $out = [];
    foreach ($DEPTS as $key => $meta) {
      $out[] = [ 'key' => $key, 'label' => $meta['label'], 'theme' => $meta['theme'] ];
    }
    echo json_encode([ 'success' => true, 'data' => $out ]);
    exit();
  }

  // Resolve and validate department for data actions
  if (!isset($DEPTS[$department])) {
    // If not provided, attempt to detect available departments from data and intersect with whitelist
    $res = $conn->query("SELECT DISTINCT department FROM board_passers");
    $available = [];
    if ($res) { while ($r = $res->fetch_assoc()) { $d = trim($r['department']); if (isset($DEPTS[$d])) $available[] = $d; } }
    echo json_encode([ 'success' => false, 'error' => 'Invalid or missing department', 'available' => $available ]);
    exit();
  }

  if ($action === 'dept_passing_rate') {
    $sql = "SELECT YEAR(board_exam_date) AS y,
                   COUNT(*) AS total,
                   SUM(CASE WHEN result='Passed' THEN 1 ELSE 0 END) AS passed
            FROM board_passers
            WHERE department = ? AND board_exam_date BETWEEN ? AND ?
            GROUP BY y ORDER BY y ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sss', $department, $from, $to);
    $stmt->execute();
    $res = $stmt->get_result();
    $byYear = [];
    while ($row = $res->fetch_assoc()) {
      $yy = (int)$row['y']; $total = (int)$row['total']; $passed = (int)$row['passed'];
      $rate = $total ? round(($passed / $total) * 100) : 0;
      $byYear[$yy] = [ 'rate' => $rate, 'passed' => $passed, 'total' => $total ];
    }
    $years = []; for ($y=$yearStart; $y<=$yearEnd; $y++) $years[] = $y;
    $valuesByYear = [];
    foreach ($years as $yy) { $valuesByYear[(string)$yy] = isset($byYear[$yy]) ? $byYear[$yy]['rate'] : 0; }
    echo json_encode([ 'success' => true, 'data' => [ 'years' => $years, 'series' => [[ 'label' => 'Passing Rate', 'values_by_year' => $valuesByYear, 'points_details' => array_combine(array_map('strval',$years), array_map(function($yy) use($byYear){ return isset($byYear[$yy]) ? $byYear[$yy] : ['rate'=>0,'passed'=>0,'total'=>0]; }, $years)) ]] ] ]);
    exit();
  }

  if ($action === 'examtype_totals_by_year') {
    // Totals per year by board exam type for the department
    $types = [];
    $stmt = $conn->prepare("SELECT id, exam_type_name FROM board_exam_types WHERE department = ? ORDER BY exam_type_name ASC");
    $stmt->bind_param('s', $department);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) { $types[] = [ 'id' => (int)$r['id'], 'name' => $r['exam_type_name'] ]; }
    $years = []; for ($y=$yearStart; $y<=$yearEnd; $y++) $years[] = $y;

    $series = [];
    foreach ($types as $t) {
      $sql = "SELECT YEAR(board_exam_date) AS y, COUNT(*) AS total
              FROM board_passers
              WHERE department = ? AND board_exam_date BETWEEN ? AND ?
                AND (exam_type = ? OR board_exam_type = ? OR exam_type LIKE ?)
              GROUP BY y ORDER BY y ASC";
      $stmt2 = $conn->prepare($sql);
      $like = '%' . $t['name'] . '%';
      $stmt2->bind_param('ssssss', $department, $from, $to, $t['name'], $t['name'], $like);
      $stmt2->execute();
      $res2 = $stmt2->get_result();
      $totByYear = [];
      while ($row = $res2->fetch_assoc()) { $totByYear[(int)$row['y']] = (int)$row['total']; }
      $vals = [];
      foreach ($years as $yy) { $vals[(string)$yy] = (int)($totByYear[$yy] ?? 0); }
      $series[] = [ 'exam_type_id' => $t['id'], 'label' => $t['name'], 'totals_by_year' => $vals ];
    }

    echo json_encode([ 'success' => true, 'data' => [ 'years' => $years, 'series' => $series ] ]);
    exit();
  }

  // Default invalid
  http_response_code(400);
  echo json_encode([ 'success' => false, 'error' => 'Invalid action' ]);
  exit();

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode([ 'success' => false, 'error' => 'Server error', 'details' => $e->getMessage() ]);
  exit();
}
