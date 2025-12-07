<?php
require_once 'db_config.php';

echo "<h2>Engineering Dashboard Statistics Check</h2>";

// Check total records
$total_query = $conn->query("SELECT COUNT(*) as total FROM board_passers WHERE department='Engineering'");
$total_data = $total_query->fetch_assoc();
$total_records = $total_data['total'];

echo "<p><strong>Total Engineering records:</strong> " . $total_records . "</p>";

// Check passing rate calculation (same as dashboard)
$passing_rate_query = $conn->query("SELECT 
    COUNT(CASE WHEN result = 'Passed' THEN 1 END) as passed_count,
    COUNT(*) as total_count
    FROM board_passers WHERE department='Engineering'");
$rate_data = $passing_rate_query->fetch_assoc();

echo "<p><strong>Passed count:</strong> " . $rate_data['passed_count'] . "</p>";
echo "<p><strong>Total count (from query):</strong> " . $rate_data['total_count'] . "</p>";

$passing_rate = 0;
if ($rate_data && $rate_data['total_count'] > 0) {
    $passing_rate = ($rate_data['passed_count'] * 100.0) / $rate_data['total_count'];
}

echo "<p><strong>Calculated Passing Rate:</strong> " . number_format($passing_rate, 1) . "%</p>";

// Show a few sample records if they exist
if ($total_records > 0) {
    echo "<h3>Sample Records:</h3>";
    $sample = $conn->query("SELECT id, name, result, year_graduated FROM board_passers WHERE department='Engineering' LIMIT 5");
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Name</th><th>Result</th><th>Year</th></tr>";
    while ($row = $sample->fetch_assoc()) {
        echo "<tr><td>{$row['id']}</td><td>{$row['name']}</td><td>{$row['result']}</td><td>{$row['year_graduated']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p><em>No records found in board_passers table for Engineering department.</em></p>";
}

// Check if there are records with different department values
$all_depts = $conn->query("SELECT DISTINCT department, COUNT(*) as count FROM board_passers GROUP BY department");
echo "<h3>All Departments in Database:</h3>";
echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>Department</th><th>Count</th></tr>";
while ($dept = $all_depts->fetch_assoc()) {
    echo "<tr><td>" . ($dept['department'] ?: '(empty)') . "</td><td>{$dept['count']}</td></tr>";
}
echo "</table>";

$conn->close();
?>
