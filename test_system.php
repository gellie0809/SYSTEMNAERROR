<?php
// Quick test of the board passer system
include 'db_config.php';

echo "<h2>🔧 System Test - Board Passer Module</h2>";

// Test database connection
if ($conn->connect_error) {
    echo "<p>❌ Database connection failed: " . $conn->connect_error . "</p>";
    exit();
}
echo "<p>✅ Database connection successful</p>";

// Check table structure
echo "<h3>📋 Board Passers Table Structure:</h3>";
$result = $conn->query("DESCRIBE board_passers");
if ($result) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['Field']}</td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>{$row['Key']}</td>";
        echo "<td>{$row['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>❌ Could not retrieve table structure</p>";
}

// Test if courses exist
echo "<h3>📚 Available Courses:</h3>";
$result = $conn->query("SELECT * FROM courses WHERE department = 'Engineering'");
if ($result && $result->num_rows > 0) {
    echo "<ul>";
    while ($row = $result->fetch_assoc()) {
        echo "<li>{$row['course_name']}</li>";
    }
    echo "</ul>";
} else {
    echo "<p>⚠️ No courses found for Engineering department</p>";
    
    // Add default courses
    $default_courses = [
        'Bachelor of Science in Electronics Engineering (BSECE)',
        'Bachelor of Science in Electrical Engineering (BSEE)',
        'Bachelor of Science in Computer Engineering (BSCpE)',
        'Bachelor of Science in Civil Engineering (BSCE)',
        'Bachelor of Science in Mechanical Engineering (BSME)'
    ];
    
    foreach ($default_courses as $course) {
        $stmt = $conn->prepare("INSERT INTO courses (course_name, department) VALUES (?, 'Engineering')");
        $stmt->bind_param("s", $course);
        $stmt->execute();
    }
    echo "<p>✅ Added default engineering courses</p>";
}

// Test if board exam types exist
echo "<h3>🎓 Board Exam Types:</h3>";
$result = $conn->query("SELECT * FROM board_exam_types WHERE department = 'Engineering'");
if ($result && $result->num_rows > 0) {
    echo "<ul>";
    while ($row = $result->fetch_assoc()) {
        echo "<li>{$row['exam_name']}</li>";
    }
    echo "</ul>";
} else {
    echo "<p>⚠️ No board exam types found</p>";
    
    // Add default exam types
    $default_exams = [
        'Registered Electrical Engineer Licensure Exam (REELE)',
        'Electronics Engineer Licensure Exam (EELE)',
        'Computer Engineer Licensure Exam (CpELE)',
        'Civil Engineer Licensure Exam (CELE)',
        'Mechanical Engineer Licensure Exam (MELE)'
    ];
    
    foreach ($default_exams as $exam) {
        $stmt = $conn->prepare("INSERT INTO board_exam_types (exam_name, department) VALUES (?, 'Engineering')");
        $stmt->bind_param("s", $exam);
        $stmt->execute();
    }
    echo "<p>✅ Added default board exam types</p>";
}

// Count existing board passers
$result = $conn->query("SELECT COUNT(*) as count FROM board_passers WHERE department = 'Engineering'");
$count = 0;
if ($result) {
    $row = $result->fetch_assoc();
    $count = $row['count'];
}
echo "<h3>👥 Current Board Passers: $count</h3>";

echo "<hr>";
echo "<h3>🚀 Ready to Test!</h3>";
echo "<p><a href='add_board_passer_engineering.php' style='background: #3182ce; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Test Add Board Passer Form</a></p>";
echo "<p><a href='manage_courses_engineering.php' style='background: #2c5aa0; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Manage Courses & Exam Types</a></p>";

$conn->close();
?>
