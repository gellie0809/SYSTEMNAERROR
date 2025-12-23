<?php
/**
 * Check CTE Anonymous Data
 * This script checks if there's enough anonymous data for training the CTE prediction models.
 */

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "project_db";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "==============================================\n";
echo "CTE Anonymous Data Check\n";
echo "==============================================\n\n";

// Check total CTE anonymous records
$result = $conn->query("SELECT COUNT(*) as count FROM anonymous_board_passers 
    WHERE department = 'Teacher Education' 
    AND (is_deleted IS NULL OR is_deleted = 0)");
$row = $result->fetch_assoc();
echo "Total CTE Anonymous Records: " . $row['count'] . "\n\n";

// Check records by year
echo "Records by Year:\n";
echo "----------------\n";
$result = $conn->query("SELECT YEAR(board_exam_date) as exam_year, COUNT(*) as count 
    FROM anonymous_board_passers 
    WHERE department = 'Teacher Education' 
    AND (is_deleted IS NULL OR is_deleted = 0)
    GROUP BY YEAR(board_exam_date) 
    ORDER BY exam_year");

while ($row = $result->fetch_assoc()) {
    echo "  {$row['exam_year']}: {$row['count']} records\n";
}

// Check records by exam type
echo "\nRecords by Exam Type:\n";
echo "---------------------\n";
$result = $conn->query("SELECT board_exam_type, COUNT(*) as count 
    FROM anonymous_board_passers 
    WHERE department = 'Teacher Education' 
    AND (is_deleted IS NULL OR is_deleted = 0)
    GROUP BY board_exam_type 
    ORDER BY count DESC");

while ($row = $result->fetch_assoc()) {
    echo "  {$row['board_exam_type']}: {$row['count']} records\n";
}

// Check passing rates by year and exam type
echo "\nPassing Rates by Year and Exam Type:\n";
echo "------------------------------------\n";
$result = $conn->query("SELECT 
    board_exam_type,
    YEAR(board_exam_date) as exam_year,
    COUNT(*) as total_takers,
    SUM(CASE WHEN result = 'Passed' THEN 1 ELSE 0 END) as total_passers,
    ROUND((SUM(CASE WHEN result = 'Passed' THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as passing_rate
FROM anonymous_board_passers
WHERE department = 'Teacher Education'
AND (is_deleted IS NULL OR is_deleted = 0)
GROUP BY board_exam_type, YEAR(board_exam_date)
ORDER BY board_exam_type, exam_year");

$current_exam_type = '';
while ($row = $result->fetch_assoc()) {
    if ($current_exam_type != $row['board_exam_type']) {
        echo "\n  {$row['board_exam_type']}:\n";
        $current_exam_type = $row['board_exam_type'];
    }
    echo "    {$row['exam_year']}: {$row['passing_rate']}% ({$row['total_passers']}/{$row['total_takers']})\n";
}

echo "\n==============================================\n";
echo "Data Check Complete\n";
echo "==============================================\n";

$conn->close();
?>
