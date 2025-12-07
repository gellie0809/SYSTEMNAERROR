<?php
// Test script to verify course insertion
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "project_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h2>Database Connection Test</h2>";
echo "Connected successfully to database: $dbname<br><br>";

// Check if courses table exists
$result = $conn->query("SHOW TABLES LIKE 'courses'");
if ($result->num_rows > 0) {
    echo "✓ 'courses' table exists<br><br>";
    
    // Show table structure
    echo "<h3>Table Structure:</h3>";
    $structure = $conn->query("DESCRIBE courses");
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $structure->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['Field']}</td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>{$row['Key']}</td>";
        echo "<td>{$row['Default']}</td>";
        echo "<td>{$row['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table><br>";
    
    // Show existing courses
    echo "<h3>Existing Courses:</h3>";
    $courses = $conn->query("SELECT * FROM courses WHERE department = 'Engineering'");
    if ($courses->num_rows > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Course Name</th><th>Department</th></tr>";
        while ($row = $courses->fetch_assoc()) {
            echo "<tr>";
            echo "<td>{$row['id']}</td>";
            echo "<td>{$row['course_name']}</td>";
            echo "<td>{$row['department']}</td>";
            echo "</tr>";
        }
        echo "</table><br>";
    } else {
        echo "No courses found for Engineering department<br><br>";
    }
    
    // Test insert
    echo "<h3>Test Insert:</h3>";
    $test_course = "TEST COURSE - " . date('Y-m-d H:i:s');
    $stmt = $conn->prepare("INSERT INTO courses (course_name, department) VALUES (?, 'Engineering')");
    $stmt->bind_param('s', $test_course);
    
    if ($stmt->execute()) {
        $insert_id = $stmt->insert_id;
        echo "✓ Successfully inserted test course!<br>";
        echo "Insert ID: $insert_id<br>";
        echo "Course Name: $test_course<br><br>";
        
        // Verify the insert
        $verify = $conn->prepare("SELECT * FROM courses WHERE id = ?");
        $verify->bind_param('i', $insert_id);
        $verify->execute();
        $result = $verify->get_result();
        if ($row = $result->fetch_assoc()) {
            echo "✓ Verified - Record exists in database:<br>";
            echo "ID: {$row['id']}<br>";
            echo "Course Name: {$row['course_name']}<br>";
            echo "Department: {$row['department']}<br><br>";
        }
        $verify->close();
        
        // Clean up test record
        $delete = $conn->prepare("DELETE FROM courses WHERE id = ?");
        $delete->bind_param('i', $insert_id);
        $delete->execute();
        echo "✓ Test record cleaned up<br>";
        $delete->close();
    } else {
        echo "✗ Insert failed: " . $stmt->error . "<br>";
    }
    $stmt->close();
    
} else {
    echo "✗ 'courses' table does NOT exist!<br>";
    echo "Creating courses table...<br>";
    
    $create_sql = "CREATE TABLE courses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        course_name VARCHAR(255) NOT NULL,
        department VARCHAR(100) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($create_sql)) {
        echo "✓ Table created successfully<br>";
    } else {
        echo "✗ Error creating table: " . $conn->error . "<br>";
    }
}

$conn->close();
?>
