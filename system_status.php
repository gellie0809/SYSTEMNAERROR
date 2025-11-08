<?php
// System Status Checker
// Run this to verify your Board Passer Management System is working correctly

require_once 'db_config.php';

echo "<h1>🔍 Board Passer Management System - Status Check</h1>";

$status = [
    'database' => false,
    'tables' => false,
    'users' => false,
    'courses' => false,
    'exam_types' => false,
    'files' => false
];

try {
    // Check database connection
    $conn = getDbConnection();
    $status['database'] = true;
    echo "<p>✅ Database connection: <strong>OK</strong></p>";
    
    // Check tables exist
    $required_tables = ['users', 'board_passers', 'courses', 'board_exam_types'];
    $tables_exist = 0;
    
    foreach ($required_tables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        if ($result->num_rows > 0) {
            $tables_exist++;
            echo "<p>✅ Table '$table': <strong>EXISTS</strong></p>";
        } else {
            echo "<p>❌ Table '$table': <strong>MISSING</strong></p>";
        }
    }
    
    if ($tables_exist == count($required_tables)) {
        $status['tables'] = true;
    }
    
    // Check users
    $user_count = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
    if ($user_count > 0) {
        $status['users'] = true;
        echo "<p>✅ Users: <strong>$user_count users found</strong></p>";
        
        // List admin users
        $admin_result = $conn->query("SELECT email FROM users WHERE email LIKE '%@lspu.edu.ph'");
        echo "<p>📋 Admin accounts:</p><ul>";
        while ($admin = $admin_result->fetch_assoc()) {
            echo "<li>" . htmlspecialchars($admin['email']) . "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>❌ Users: <strong>No users found</strong></p>";
    }
    
    // Check courses
    $course_count = $conn->query("SELECT COUNT(*) as count FROM courses")->fetch_assoc()['count'];
    if ($course_count > 0) {
        $status['courses'] = true;
        echo "<p>✅ Courses: <strong>$course_count courses found</strong></p>";
        
        // Show courses by department
        $dept_result = $conn->query("SELECT department, COUNT(*) as count FROM courses GROUP BY department");
        echo "<p>📋 Courses by department:</p><ul>";
        while ($dept = $dept_result->fetch_assoc()) {
            echo "<li>" . htmlspecialchars($dept['department']) . ": " . $dept['count'] . " courses</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>❌ Courses: <strong>No courses found</strong></p>";
    }
    
    // Check exam types
    $exam_type_count = $conn->query("SELECT COUNT(*) as count FROM board_exam_types")->fetch_assoc()['count'];
    if ($exam_type_count > 0) {
        $status['exam_types'] = true;
        echo "<p>✅ Board Exam Types: <strong>$exam_type_count types found</strong></p>";
    } else {
        echo "<p>❌ Board Exam Types: <strong>No exam types found</strong></p>";
    }
    
    // Check critical files
    $critical_files = [
        'mainpage.php' => 'Login page',
        'process_login.php' => 'Login processor',
        'dashboard_engineering.php' => 'Engineering dashboard',
        'add_board_passer_engineering.php' => 'Add board passer form',
        'manage_courses_engineering.php' => 'Course management'
    ];
    
    $files_ok = 0;
    echo "<p>📁 Critical files check:</p><ul>";
    foreach ($critical_files as $file => $description) {
        if (file_exists($file)) {
            echo "<li>✅ $description ($file)</li>";
            $files_ok++;
        } else {
            echo "<li>❌ $description ($file) - <strong>MISSING</strong></li>";
        }
    }
    echo "</ul>";
    
    if ($files_ok == count($critical_files)) {
        $status['files'] = true;
    }
    
    // Check board passers data
    $passer_count = $conn->query("SELECT COUNT(*) as count FROM board_passers")->fetch_assoc()['count'];
    echo "<p>📊 Board Passers: <strong>$passer_count records found</strong></p>";
    
    if ($passer_count > 0) {
        // Check date ranges
        $date_check = $conn->query("
            SELECT 
                MIN(YEAR(board_exam_date)) as min_year,
                MAX(YEAR(board_exam_date)) as max_year,
                COUNT(CASE WHEN YEAR(board_exam_date) < 2019 OR YEAR(board_exam_date) > 2024 THEN 1 END) as invalid_dates
            FROM board_passers
        ")->fetch_assoc();
        
        echo "<p>📅 Date range: {$date_check['min_year']} - {$date_check['max_year']}</p>";
        
        if ($date_check['invalid_dates'] > 0) {
            echo "<p>⚠️ Found {$date_check['invalid_dates']} records with dates outside 2019-2024 range</p>";
        } else {
            echo "<p>✅ All board exam dates are within valid range (2019-2024)</p>";
        }
        
        // Results breakdown
        $results = $conn->query("
            SELECT result, COUNT(*) as count 
            FROM board_passers 
            GROUP BY result 
            ORDER BY count DESC
        ");
        
        echo "<p>📈 Results breakdown:</p><ul>";
        while ($result = $results->fetch_assoc()) {
            echo "<li>" . htmlspecialchars($result['result']) . ": " . $result['count'] . " records</li>";
        }
        echo "</ul>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ <strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Overall status
$all_good = array_reduce($status, function($carry, $item) { return $carry && $item; }, true);

echo "<h2>🎯 Overall System Status</h2>";

if ($all_good) {
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3>🎉 System Status: HEALTHY</h3>";
    echo "<p>Your Board Passer Management System is working correctly!</p>";
    echo "<ul>";
    echo "<li>✅ Database connection established</li>";
    echo "<li>✅ All required tables exist</li>";
    echo "<li>✅ Admin users are configured</li>";
    echo "<li>✅ Courses and exam types are set up</li>";
    echo "<li>✅ All critical files are present</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<div style='margin: 20px 0;'>";
    echo "<a href='mainpage.php' style='background: #007bff; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; margin-right: 10px;'>🚀 Go to Login</a>";
    echo "<a href='dashboard_engineering.php' style='background: #28a745; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px;'>📊 Engineering Dashboard</a>";
    echo "</div>";
    
} else {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3>⚠️ System Status: NEEDS ATTENTION</h3>";
    echo "<p>Some components need to be fixed:</p>";
    echo "<ul>";
    if (!$status['database']) echo "<li>❌ Database connection failed</li>";
    if (!$status['tables']) echo "<li>❌ Database tables missing or incomplete</li>";
    if (!$status['users']) echo "<li>❌ No admin users found</li>";
    if (!$status['courses']) echo "<li>❌ No courses configured</li>";
    if (!$status['exam_types']) echo "<li>❌ No exam types configured</li>";
    if (!$status['files']) echo "<li>❌ Critical files missing</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<p><a href='setup_database.php' style='background: #ffc107; color: #212529; padding: 12px 24px; text-decoration: none; border-radius: 6px;'>🔧 Run Database Setup</a></p>";
}

echo "<hr style='margin: 40px 0;'>";
echo "<p style='text-align: center; color: #6c757d; font-size: 0.9em;'>";
echo "Board Passer Management System v2.0 | Status Check Tool<br>";
echo "Generated on " . date('Y-m-d H:i:s');
echo "</p>";
?>

<style>
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    max-width: 1000px;
    margin: 0 auto;
    padding: 20px;
    background: #f8f9fa;
    line-height: 1.6;
}
h1, h2, h3 {
    color: #343a40;
}
p {
    margin: 8px 0;
}
ul {
    margin: 10px 0;
    padding-left: 30px;
}
li {
    margin: 5px 0;
}
a {
    display: inline-block;
    transition: all 0.3s ease;
}
a:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}
</style>
