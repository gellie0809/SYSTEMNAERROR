<?php
/**
 * Check CBAA anonymous data for prediction system
 */

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "project_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h2>CBAA Anonymous Board Passer Data Check</h2>";

// Check total records
$result = $conn->query("SELECT COUNT(*) as total FROM anonymous_board_passers 
                       WHERE department = 'Business Administration and Accountancy' 
                       AND (is_deleted IS NULL OR is_deleted = 0)");
$row = $result->fetch_assoc();
echo "<p><strong>Total CBAA Records:</strong> " . $row['total'] . "</p>";

// Check by exam type and year
$result = $conn->query("SELECT 
    board_exam_type,
    YEAR(board_exam_date) as exam_year,
    COUNT(*) as count,
    SUM(CASE WHEN result = 'Passed' THEN 1 ELSE 0 END) as passed,
    ROUND((SUM(CASE WHEN result = 'Passed' THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as passing_rate
FROM anonymous_board_passers
WHERE department = 'Business Administration and Accountancy'
AND (is_deleted IS NULL OR is_deleted = 0)
GROUP BY board_exam_type, YEAR(board_exam_date)
ORDER BY exam_year DESC, board_exam_type");

echo "<table border='1' cellpadding='8' style='border-collapse: collapse;'>";
echo "<tr style='background: #AA4C0A; color: white;'>
        <th>Exam Type</th>
        <th>Year</th>
        <th>Total Takers</th>
        <th>Passed</th>
        <th>Passing Rate</th>
      </tr>";

$total_records = 0;
while($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['board_exam_type']) . "</td>";
    echo "<td>" . $row['exam_year'] . "</td>";
    echo "<td>" . $row['count'] . "</td>";
    echo "<td>" . $row['passed'] . "</td>";
    echo "<td>" . $row['passing_rate'] . "%</td>";
    echo "</tr>";
    $total_records += $row['count'];
}

echo "</table>";

echo "<hr>";
echo "<h3>Summary</h3>";
echo "<p>✅ Total Records Available for Training: <strong>$total_records</strong></p>";

if ($total_records >= 10) {
    echo "<p style='color: green;'><strong>✓ Sufficient data available for training!</strong></p>";
    echo "<p>You can proceed to train the models using <code>train.bat</code></p>";
} else {
    echo "<p style='color: orange;'><strong>⚠ Limited data available</strong></p>";
    echo "<p>You may need to add more anonymous data records for better model performance.</p>";
}

$conn->close();
?>
