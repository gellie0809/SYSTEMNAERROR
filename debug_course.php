<?php
session_start();

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== DEBUG INFO ===\n";
echo "REQUEST_METHOD: " . $_SERVER["REQUEST_METHOD"] . "\n";
echo "POST data: " . print_r($_POST, true) . "\n";
echo "SESSION: " . print_r($_SESSION, true) . "\n";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["add_course"])) {
    echo "Form submitted for adding course!\n";
    
    $new_course = trim($_POST["new_course"]);
    echo "Course name received: '" . $new_course . "'\n";
    
    if (empty($new_course)) {
        echo "ERROR: Course name is empty!\n";
    } else {
        echo "Course name is valid, proceeding with database insert...\n";
        
        $servername = "localhost";
        $username = "root";
        $password = "";
        $dbname = "project_db";
        $conn = new mysqli($servername, $username, $password, $dbname);
        
        if ($conn->connect_error) {
            echo "DB Connection failed: " . $conn->connect_error . "\n";
        } else {
            echo "DB Connection successful\n";
            
            // Check if course already exists
            $check_stmt = $conn->prepare("SELECT id FROM courses WHERE course_name = ? AND department = 'Engineering'");
            $check_stmt->bind_param("s", $new_course);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            
            if ($result->num_rows > 0) {
                echo "Course already exists\n";
            } else {
                echo "Course doesn't exist, inserting...\n";
                
                $stmt = $conn->prepare("INSERT INTO courses (course_name, department) VALUES (?, 'Engineering')");
                $stmt->bind_param("s", $new_course);
                
                if ($stmt->execute()) {
                    echo "SUCCESS: Course inserted successfully!\n";
                    echo "Insert ID: " . $conn->insert_id . "\n";
                } else {
                    echo "FAILED: " . $conn->error . "\n";
                }
                $stmt->close();
            }
            $check_stmt->close();
        }
        $conn->close();
    }
} else {
    echo "No form submission detected\n";
}

echo "=== END DEBUG ===\n";
?>
