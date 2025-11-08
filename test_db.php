<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "project_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    echo "Connection failed: " . $conn->connect_error . "\n";
} else {
    echo "Database connection successful\n";
    
    // Check if courses table exists
    $result = $conn->query("SHOW TABLES LIKE 'courses'");
    if ($result->num_rows > 0) {
        echo "Courses table exists\n";
        
        // Check table structure
        $result = $conn->query("DESCRIBE courses");
        echo "Table structure:\n";
        while ($row = $result->fetch_assoc()) {
            echo $row['Field'] . " - " . $row['Type'] . "\n";
        }
        
        // Test inserting a course
        echo "\nTesting course insertion...\n";
        $test_course = "Test Course " . date('Y-m-d H:i:s');
        $stmt = $conn->prepare("INSERT INTO courses (course_name, department) VALUES (?, 'Engineering')");
        $stmt->bind_param("s", $test_course);
        
        if ($stmt->execute()) {
            echo "SUCCESS: Test course inserted: " . $test_course . "\n";
            
            // Clean up - delete the test course
            $stmt2 = $conn->prepare("DELETE FROM courses WHERE course_name = ?");
            $stmt2->bind_param("s", $test_course);
            $stmt2->execute();
            echo "Test course deleted\n";
            $stmt2->close();
        } else {
            echo "FAILED: Could not insert test course: " . $conn->error . "\n";
        }
        $stmt->close();
        
    } else {
        echo "Courses table does NOT exist!\n";
        echo "Creating courses table...\n";
        
        $create_sql = "CREATE TABLE courses (
            id int(11) NOT NULL AUTO_INCREMENT,
            course_name varchar(255) NOT NULL,
            department varchar(100) NOT NULL,
            PRIMARY KEY (id)
        )";
        
        if ($conn->query($create_sql) === TRUE) {
            echo "Courses table created successfully\n";
        } else {
            echo "Error creating table: " . $conn->error . "\n";
        }
    }
}

$conn->close();
?>
