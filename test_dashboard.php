<?php
// Test dashboard functionality for all departments
session_start();

$departments = [
    'Engineering' => 'eng_admin@lspu.edu.ph',
    'Arts and Science' => 'cas_admin@lspu.edu.ph',
    'Business Administration and Accountancy' => 'cbaa_admin@lspu.edu.ph',
    'Criminal Justice Education' => 'ccje_admin@lspu.edu.ph',
    'Teacher Education' => 'cte_admin@lspu.edu.ph'
];

echo "<!DOCTYPE html>
<html>
<head>
    <title>Dashboard Test Results</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .test-result { margin: 10px 0; padding: 15px; border-radius: 8px; }
        .pass { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .fail { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
        .info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; }
        h1 { color: #333; }
        h2 { color: #666; margin-top: 30px; }
        .status { font-weight: bold; }
    </style>
</head>
<body>
    <h1>🧪 Dashboard Functionality Test Results (All Departments)</h1>";

try {
    $conn = new mysqli("localhost", "root", "", "project_db");
    if ($conn->connect_error) throw new Exception("Connection failed: " . $conn->connect_error);
    
    echo "<div class='test-result pass'>✅ <span class='status'>PASS:</span> Database connection successful</div>";

    foreach ($departments as $dept => $admin) {
        echo "<h2>Department: $dept</h2>";

        // Set session
        $_SESSION['users'] = $admin;

        // Table check
        $result = $conn->query("DESCRIBE board_passers");
        if ($result && $result->num_rows > 0) {
            echo "<div class='test-result pass'>✅ board_passers table exists</div>";

            $columns = [];
            while ($row = $result->fetch_assoc()) $columns[] = $row['Field'];
            $required_columns = ['id','first_name','last_name','course','year_graduated','board_exam_date','result','exam_type','board_exam_type','department'];
            $missing_columns = array_diff($required_columns, $columns);
            echo empty($missing_columns) 
                ? "<div class='test-result pass'>✅ All required columns exist</div>"
                : "<div class='test-result fail'>❌ Missing columns: " . implode(', ', $missing_columns) . "</div>";
        } else {
            echo "<div class='test-result fail'>❌ board_passers table does not exist</div>";
        }

        // Sample data
        $res = $conn->query("SELECT COUNT(*) as count FROM board_passers WHERE department='$dept'");
        $row = $res->fetch_assoc();
        echo $row['count'] > 0 
            ? "<div class='test-result pass'>✅ Found {$row['count']} student records</div>"
            : "<div class='test-result info'>ℹ️ No student records found</div>";

        // Courses
        $res = $conn->query("SELECT COUNT(*) as count FROM courses WHERE department='$dept'");
        $row = $res->fetch_assoc();
        echo $row['count'] > 0
            ? "<div class='test-result pass'>✅ Found {$row['count']} courses</div>"
            : "<div class='test-result info'>ℹ️ No courses found</div>";

        // Exam types
        $res = $conn->query("SELECT COUNT(*) as count FROM board_exam_types WHERE department='$dept'");
        $row = $res->fetch_assoc();
        echo $row['count'] > 0
            ? "<div class='test-result pass'>✅ Found {$row['count']} exam types</div>"
            : "<div class='test-result info'>ℹ️ No exam types found</div>";

        // Dashboard files
        $files = [
            "dashboard_" . strtolower(substr($dept,0,3)) . ".php",
            "update_board_passer.php",
            "delete_board_passer.php"
        ];
        foreach ($files as $file) {
            echo (file_exists($file) && is_readable($file))
                ? "<div class='test-result pass'>✅ $file is accessible</div>"
                : "<div class='test-result fail'>❌ $file is not accessible</div>";
        }
    }

} catch (Exception $e) {
    echo "<div class='test-result fail'>❌ Error: " . $e->getMessage() . "</div>";
}

echo "<h2>🎯 Dashboard Test Summary</h2>
<p>✅ Tests completed for all departments</p>
</body></html>";

$conn->close();
?>