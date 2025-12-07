<?php
$conn = new mysqli('localhost', 'root', '', 'project_db');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h2>All CCJE Courses</h2>";
$result = $conn->query("SELECT id, course_name, department, is_deleted, deleted_at FROM courses WHERE department='Criminal Justice Education'");
echo "<table border='1'><tr><th>ID</th><th>Course Name</th><th>Department</th><th>Is Deleted</th><th>Deleted At</th></tr>";
while($row = $result->fetch_assoc()) {
    echo "<tr><td>{$row['id']}</td><td>{$row['course_name']}</td><td>{$row['department']}</td><td>{$row['is_deleted']}</td><td>{$row['deleted_at']}</td></tr>";
}
echo "</table>";

echo "<h2>Active CCJE Courses (what the page should show)</h2>";
$result2 = $conn->query("SELECT id, TRIM(course_name) as name FROM courses WHERE department='Criminal Justice Education' AND (is_deleted = 0 OR is_deleted IS NULL) ORDER BY course_name ASC");
echo "<table border='1'><tr><th>ID</th><th>Course Name</th></tr>";
while($row = $result2->fetch_assoc()) {
    echo "<tr><td>{$row['id']}</td><td>{$row['name']}</td></tr>";
}
echo "</table>";

$conn->close();
?>
