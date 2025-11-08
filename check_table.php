<?php
$conn = new mysqli('localhost', 'root', '', 'project_db');
if ($conn->connect_error) {
    echo 'Connection failed: ' . $conn->connect_error . PHP_EOL;
} else {
    echo 'Database connection successful' . PHP_EOL;
    
    // Show all tables
    $result = $conn->query('SHOW TABLES');
    if ($result) {
        echo 'Tables in database:' . PHP_EOL;
        while($row = $result->fetch_array()) {
            echo '- ' . $row[0] . PHP_EOL;
        }
    }
    
    // Check board_passers table
    $desc = $conn->query('DESCRIBE board_passers');
    if ($desc) {
        echo PHP_EOL . 'board_passers table structure:' . PHP_EOL;
        while($row = $desc->fetch_array()) {
            echo '- ' . $row[0] . ' (' . $row[1] . ')' . PHP_EOL;
        }
    } else {
        echo PHP_EOL . 'board_passers table does not exist!' . PHP_EOL;
        echo 'Creating board_passers table...' . PHP_EOL;
        
        // Create the table
        $sql = "CREATE TABLE IF NOT EXISTS board_passers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            first_name VARCHAR(100) NOT NULL,
            middle_name VARCHAR(100) DEFAULT '',
            last_name VARCHAR(100) NOT NULL,
            sex VARCHAR(10) DEFAULT NULL,
            course VARCHAR(255) NOT NULL,
            year_graduated INT NOT NULL,
            board_exam_date DATE NOT NULL,
            result VARCHAR(10) NOT NULL,
            department VARCHAR(100) NOT NULL,
            exam_type VARCHAR(20) DEFAULT 'First Timer',
            board_exam_type VARCHAR(100) DEFAULT 'Registered Electrical Engineer Licensure Exam (REELE)'
        )";
        
        if ($conn->query($sql)) {
            echo 'board_passers table created successfully!' . PHP_EOL;
        } else {
            echo 'Error creating table: ' . $conn->error . PHP_EOL;
        }
    }
    
    $conn->close();
}
?>
