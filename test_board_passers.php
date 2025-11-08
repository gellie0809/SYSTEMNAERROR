<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "project_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "Database connection successful\n";

// Check if board_passers table exists
$result = $conn->query("SHOW TABLES LIKE 'board_passers'");
if ($result->num_rows > 0) {
    echo "board_passers table exists\n";
    
    // Show table structure
    echo "\nTable structure:\n";
    $result = $conn->query("DESCRIBE board_passers");
    while ($row = $result->fetch_assoc()) {
        echo $row['Field'] . " - " . $row['Type'] . " - " . $row['Null'] . " - " . $row['Key'] . "\n";
    }
    
    // Check if sex column exists
    $check_sex = $conn->query("SHOW COLUMNS FROM board_passers LIKE 'sex'");
    if ($check_sex->num_rows == 0) {
        echo "\nAdding sex column...\n";
        $conn->query("ALTER TABLE board_passers ADD COLUMN sex VARCHAR(10) DEFAULT NULL");
        echo "Sex column added\n";
    } else {
        echo "\nSex column already exists\n";
    }
    
    // Test insert
    echo "\nTesting insert...\n";
    $test_name = "Test, User " . date('Y-m-d H:i:s');
    $test_sex = "Male";
    $test_course = "Computer Engineering";
    $test_year = 2024;
    $test_date = "2024-05-15";
    $test_result = "Passed";
    $test_dept = "Engineering";
    
    $stmt = $conn->prepare("INSERT INTO board_passers (name, sex, course, year_graduated, board_exam_date, result, department) VALUES (?, ?, ?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("ssissss", $test_name, $test_sex, $test_course, $test_year, $test_date, $test_result, $test_dept);
        
        if ($stmt->execute()) {
            $insert_id = $conn->insert_id;
            echo "SUCCESS: Test record inserted with ID: " . $insert_id . "\n";
            
            // Clean up - delete test record
            $stmt2 = $conn->prepare("DELETE FROM board_passers WHERE id = ?");
            $stmt2->bind_param("i", $insert_id);
            $stmt2->execute();
            echo "Test record deleted\n";
            $stmt2->close();
        } else {
            echo "FAILED: Could not insert test record: " . $stmt->error . "\n";
        }
        $stmt->close();
    } else {
        echo "FAILED: Could not prepare statement: " . $conn->error . "\n";
    }
    
} else {
    echo "board_passers table does NOT exist!\n";
    echo "Creating table...\n";
    
    $create_sql = "CREATE TABLE board_passers (
        id int(11) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        sex varchar(10) DEFAULT NULL,
        course varchar(255) NOT NULL,
        year_graduated int(4) NOT NULL,
        board_exam_date date NOT NULL,
        result varchar(50) NOT NULL,
        department varchar(100) NOT NULL,
        PRIMARY KEY (id)
    )";
    
    if ($conn->query($create_sql) === TRUE) {
        echo "board_passers table created successfully\n";
    } else {
        echo "Error creating table: " . $conn->error . "\n";
    }
}

$conn->close();
?>
