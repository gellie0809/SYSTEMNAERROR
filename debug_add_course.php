<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Debug Add Course Form</h2>";
echo "<p>Session user: " . ($_SESSION["users"] ?? 'NOT SET') . "</p>";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    echo "<h3>POST Request Received</h3>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
    
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "project_db";
    
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    echo "<p>✓ Database connected</p>";
    
    if (isset($_POST['add_course'])) {
        $new_course = trim($_POST['new_course'] ?? '');
        echo "<p>Course name: '$new_course'</p>";
        
        if ($new_course === '') {
            echo "<p style='color:red;'>✗ Course name is empty!</p>";
        } else {
            // Check duplicate
            $chk = $conn->prepare("SELECT id FROM courses WHERE LOWER(TRIM(course_name)) = LOWER(TRIM(?)) AND department = 'Engineering'");
            $chk->bind_param('s', $new_course);
            $chk->execute();
            $chk->store_result();
            
            if ($chk->num_rows > 0) {
                echo "<p style='color:orange;'>⚠ Course already exists!</p>";
            } else {
                // Try to insert
                $ins = $conn->prepare("INSERT INTO courses (course_name, department) VALUES (?, 'Engineering')");
                $ins->bind_param('s', $new_course);
                
                if ($ins->execute()) {
                    $insert_id = $ins->insert_id;
                    echo "<p style='color:green;'>✓ Course added successfully! ID: $insert_id</p>";
                    
                    // Verify
                    $verify = $conn->query("SELECT * FROM courses WHERE id = $insert_id");
                    if ($row = $verify->fetch_assoc()) {
                        echo "<p>Verified record:</p>";
                        echo "<pre>";
                        print_r($row);
                        echo "</pre>";
                    }
                } else {
                    echo "<p style='color:red;'>✗ Insert failed: " . $ins->error . "</p>";
                }
                $ins->close();
            }
            $chk->close();
        }
    }
    
    // Show all courses
    echo "<h3>Current Courses in Database:</h3>";
    $result = $conn->query("SELECT * FROM courses WHERE department = 'Engineering' ORDER BY id DESC LIMIT 10");
    if ($result->num_rows > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Course Name</th><th>Department</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr><td>{$row['id']}</td><td>{$row['course_name']}</td><td>{$row['department']}</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No courses found</p>";
    }
    
    $conn->close();
}
?>

<hr>
<h3>Test Form (No JavaScript)</h3>
<form method="POST" action="debug_add_course.php">
    <label>Course Name:</label><br>
    <input type="text" name="new_course" required><br><br>
    <button type="submit" name="add_course">Add Course</button>
</form>

<hr>
<p><a href="manage_data_engineering.php">Back to Manage Data</a></p>
