<?php
require '../db_config.php';

$conn = getDbConnection();

echo "=== SAMPLE ANONYMOUS BOARD PASSER DATA ===\n\n";

$result = $conn->query("SELECT * FROM anonymous_board_passers WHERE department='Engineering' LIMIT 5");
echo "Sample Records:\n";
while($row = $result->fetch_assoc()) {
    print_r($row);
    echo "\n";
}

echo "\n=== DATA SUMMARY ===\n";
$result2 = $conn->query("
    SELECT 
        board_exam_type,
        exam_type,
        result,
        COUNT(*) as count
    FROM anonymous_board_passers 
    WHERE department='Engineering'
    GROUP BY board_exam_type, exam_type, result
    ORDER BY board_exam_type, exam_type, result
");

echo "\nBreakdown by Exam Type and Result:\n";
while($row = $result2->fetch_assoc()) {
    echo sprintf("%-50s | %-15s | %-12s | %d\n", 
        $row['board_exam_type'], 
        $row['exam_type'], 
        $row['result'], 
        $row['count']
    );
}

$conn->close();
?>
