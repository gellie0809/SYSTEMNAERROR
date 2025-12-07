<?php
// System Status Checker for All Departments

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
    $conn = getDbConnection();
    $status['database'] = true;
    echo "<p>✅ Database connection: <strong>OK</strong></p>";

    // Check required tables
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
    $status['tables'] = ($tables_exist == count($required_tables));

    // Check users
    $user_count = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'] ?? 0;
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

    // Check courses by department dynamically
    $course_count = $conn->query("SELECT COUNT(*) as count FROM courses")->fetch_assoc()['count'] ?? 0;
    if ($course_count > 0) {
        $status['courses'] = true;
        echo "<p>✅ Courses: <strong>$course_count courses found</strong></p>";

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
    $exam_type_count = $conn->query("SELECT COUNT(*) as count FROM board_exam_types")->fetch_assoc()['count'] ?? 0;
    if ($exam_type_count > 0) {
        $status['exam_types'] = true;
        echo "<p>✅ Board Exam Types: <strong>$exam_type_count types found</strong></p>";
    } else {
        echo "<p>❌ Board Exam Types: <strong>No exam types found</strong></p>";
    }

    // Check critical files dynamically for all departments
    $departments = ['Engineering','Arts and Science','Business Administration and Accountancy','Criminal Justice Education','Teacher Education'];
    $critical_files = [
        'index.php' => 'Login page',
        'process_login.php' => 'Login processor',
    ];
    foreach ($departments as $dept) {
        $dept_key = strtolower(str_replace(' ','_',$dept));
        $critical_files["dashboard_{$dept_key}.php"] = "$dept Dashboard";
        $critical_files["add_board_passer_{$dept_key}.php"] = "Add $dept Board Passer Form";
        $critical_files["manage_courses_{$dept_key}.php"] = "$dept Course Management";
    }

    $files_ok = 0;
    echo "<p>📁 Critical files check:</p><ul>";
    foreach ($critical_files as $file => $desc) {
        if (file_exists($file)) {
            echo "<li>✅ $desc ($file)</li>";
            $files_ok++;
        } else {
            echo "<li>❌ $desc ($file) - <strong>MISSING</strong></li>";
        }
    }
    echo "</ul>";
    $status['files'] = ($files_ok == count($critical_files));

    // Board passers data summary
    $passer_count = $conn->query("SELECT COUNT(*) as count FROM board_passers")->fetch_assoc()['count'] ?? 0;
    echo "<p>📊 Board Passers: <strong>$passer_count records found</strong></p>";

    if ($passer_count > 0) {
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

        $results = $conn->query("SELECT result, COUNT(*) as count FROM board_passers GROUP BY result ORDER BY count DESC");
        echo "<p>📈 Results breakdown:</p><ul>";
        while ($r = $results->fetch_assoc()) {
            echo "<li>" . htmlspecialchars($r['result']) . ": " . $r['count'] . " records</li>";
        }
        echo "</ul>";
    }

} catch (Exception $e) {
    echo "<p>❌ <strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Overall system status
$all_good = array_reduce($status, fn($carry,$item)=>$carry && $item,true);
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
} else {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3>⚠️ System Status: NEEDS ATTENTION</h3>";
    echo "<ul>";
    if (!$status['database']) echo "<li>❌ Database connection failed</li>";
    if (!$status['tables']) echo "<li>❌ Database tables missing or incomplete</li>";
    if (!$status['users']) echo "<li>❌ No admin users found</li>";
    if (!$status['courses']) echo "<li>❌ No courses configured</li>";
    if (!$status['exam_types']) echo "<li>❌ No exam types configured</li>";
    if (!$status['files']) echo "<li>❌ Critical files missing</li>";
    echo "</ul></div>";
}

echo "<hr><p style='text-align:center;color:#6c757d;font-size:.9em;'>Board Passer Management System v2.0 | Generated on " . date('Y-m-d H:i:s') . "</p>";
?>