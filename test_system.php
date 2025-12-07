<?php
// Quick test of the board passer system for all departments
include 'db_config.php';

$departments = [
    'Engineering' => [
        'default_courses' => [
            'Bachelor of Science in Electronics Engineering (BSECE)',
            'Bachelor of Science in Electrical Engineering (BSEE)',
            'Bachelor of Science in Computer Engineering (BSCpE)',
            'Bachelor of Science in Civil Engineering (BSCE)',
            'Bachelor of Science in Mechanical Engineering (BSME)'
        ],
        'default_exams' => [
            'Registered Electrical Engineer Licensure Exam (REELE)',
            'Electronics Engineer Licensure Exam (EELE)',
            'Computer Engineer Licensure Exam (CpELE)',
            'Civil Engineer Licensure Exam (CELE)',
            'Mechanical Engineer Licensure Exam (MELE)'
        ]
    ],
    'Arts and Science' => [
        'default_courses' => ['Bachelor of Arts', 'Bachelor of Science in Biology', 'Bachelor of Science in Mathematics'],
        'default_exams' => ['Arts and Science Licensure Exam']
    ],
    'Business Administration and Accountancy' => [
        'default_courses' => ['Bachelor of Science in Accountancy', 'Bachelor of Science in Business Administration'],
        'default_exams' => ['Accountancy Licensure Exam', 'Business Administration Exam']
    ],
    'Criminal Justice Education' => [
        'default_courses' => ['Bachelor of Science in Criminal Justice'],
        'default_exams' => ['Criminal Justice Licensure Exam']
    ],
    'Teacher Education' => [
        'default_courses' => ['Bachelor of Elementary Education', 'Bachelor of Secondary Education'],
        'default_exams' => ['Licensure Examination for Teachers (LET)']
    ]
];

echo "<h2>🔧 System Test - Board Passer Module (All Departments)</h2>";

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

// Loop through all departments
foreach ($departments as $dept => $data) {
    echo "<h3>🏛 Department: $dept</h3>";

    // Courses
    $res = $conn->query("SELECT * FROM courses WHERE department = '$dept'");
    if ($res && $res->num_rows > 0) {
        echo "<ul>";
        while ($row = $res->fetch_assoc()) {
            echo "<li>{$row['course_name']}</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>⚠️ No courses found for $dept</p>";
        foreach ($data['default_courses'] as $course) {
            $stmt = $conn->prepare("INSERT INTO courses (course_name, department) VALUES (?, ?)");
            $stmt->bind_param("ss", $course, $dept);
            $stmt->execute();
        }
        echo "<p>✅ Added default courses for $dept</p>";
    }

    // Board exam types
    $res = $conn->query("SELECT * FROM board_exam_types WHERE department = '$dept'");
    if ($res && $res->num_rows > 0) {
        echo "<ul>";
        while ($row = $res->fetch_assoc()) {
            echo "<li>{$row['exam_name']}</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>⚠️ No board exam types found for $dept</p>";
        foreach ($data['default_exams'] as $exam) {
            $stmt = $conn->prepare("INSERT INTO board_exam_types (exam_name, department) VALUES (?, ?)");
            $stmt->bind_param("ss", $exam, $dept);
            $stmt->execute();
        }
        echo "<p>✅ Added default board exam types for $dept</p>";
    }

    // Count board passers
    $res = $conn->query("SELECT COUNT(*) as count FROM board_passers WHERE department = '$dept'");
    $count = 0;
    if ($res) {
        $row = $res->fetch_assoc();
        $count = $row['count'];
    }
    echo "<p>👥 Current Board Passers for $dept: $count</p>";
}

echo "<hr>";
echo "<h3>🚀 Ready to Test Forms & Management!</h3>";
foreach ($departments as $dept => $data) {
    $prefix = strtolower(substr($dept, 0, 3)); // e.g., 'eng', 'art', 'bus', 'cre', 'tea'
    echo "<p><a href='add_board_passer_{$prefix}.php' style='background: #3182ce; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Test Add Board Passer Form - $dept</a></p>";
    echo "<p><a href='manage_courses_{$prefix}.php' style='background: #2c5aa0; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Manage Courses & Exam Types - $dept</a></p>";
}

$conn->close();
?>