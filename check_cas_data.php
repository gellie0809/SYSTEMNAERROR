<?php
$conn = new mysqli('localhost', 'root', '', 'project_db');

echo "CAS Anonymous Data Analysis:\n\n";

// Check what the current query returns
$query = "SELECT 
    board_exam_type,
    YEAR(board_exam_date) as exam_year,
    COUNT(*) as total_takers,
    SUM(CASE WHEN result = 'Passed' THEN 1 ELSE 0 END) as total_passers,
    (SUM(CASE WHEN result = 'Passed' THEN 1 ELSE 0 END) / COUNT(*)) * 100 as passing_rate
FROM anonymous_board_passers
WHERE (is_deleted IS NULL OR is_deleted = 0)
AND department = 'Arts and Sciences'
GROUP BY board_exam_type, YEAR(board_exam_date)
ORDER BY exam_year, board_exam_type";

$result = $conn->query($query);
echo "Records returned (grouped by exam type and year):\n";
while ($row = $result->fetch_assoc()) {
    echo "Year: {$row['exam_year']} | Exam: {$row['board_exam_type']} | Takers: {$row['total_takers']} | Passers: {$row['total_passers']} | Rate: " . number_format($row['passing_rate'], 2) . "%\n";
}

// Check total raw records
$result2 = $conn->query("SELECT COUNT(*) as cnt FROM anonymous_board_passers WHERE department = 'Arts and Sciences' AND (is_deleted IS NULL OR is_deleted = 0)");
$row = $result2->fetch_assoc();
echo "\nTotal raw CAS records: {$row['cnt']}\n";

// Check unique years
$result3 = $conn->query("SELECT DISTINCT YEAR(board_exam_date) as yr FROM anonymous_board_passers WHERE department = 'Arts and Sciences' AND (is_deleted IS NULL OR is_deleted = 0) ORDER BY yr");
echo "\nYears with data: ";
while ($row = $result3->fetch_assoc()) {
    echo $row['yr'] . " ";
}
echo "\n";
?>
