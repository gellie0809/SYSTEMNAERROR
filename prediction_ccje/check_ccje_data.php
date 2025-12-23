<?php
$conn = new mysqli('localhost', 'root', '', 'project_db');

$result = $conn->query("SELECT board_exam_type, YEAR(board_exam_date) as year, COUNT(*) as total, SUM(CASE WHEN result='Passed' THEN 1 ELSE 0 END) as passed FROM anonymous_board_passers WHERE board_exam_type LIKE '%Criminology%' GROUP BY board_exam_type, YEAR(board_exam_date) ORDER BY year");

echo "CCJE Historical Data:\n";
echo "Year | Passing Rate | Passed/Total\n";
echo "-----+-------------+-------------\n";

while($row = $result->fetch_assoc()) {
    $rate = round(($row['passed']/$row['total'])*100, 2);
    echo $row['year'] . " | " . $rate . "% | " . $row['passed'] . "/" . $row['total'] . "\n";
}
