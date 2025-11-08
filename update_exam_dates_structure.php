<?php
// Update database structure to link exam dates with exam types
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "project_db";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h2>🔧 Updating Board Exam Dates Structure</h2>";

// First, check if we need to add the exam_type_id column
$check_column = $conn->query("SHOW COLUMNS FROM board_exam_dates LIKE 'exam_type_id'");
if ($check_column->num_rows == 0) {
    // Add exam_type_id column
    $add_column_sql = "ALTER TABLE board_exam_dates ADD COLUMN exam_type_id INT DEFAULT NULL AFTER exam_description";
    if ($conn->query($add_column_sql)) {
        echo "<p>✅ Added exam_type_id column to board_exam_dates table</p>";
    } else {
        echo "<p>❌ Error adding exam_type_id column: " . $conn->error . "</p>";
    }
} else {
    echo "<p>ℹ️ exam_type_id column already exists</p>";
}

// Create board_exam_types table if it doesn't exist
$create_exam_types_table = "CREATE TABLE IF NOT EXISTS board_exam_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    exam_type_name VARCHAR(255) NOT NULL,
    department VARCHAR(100) NOT NULL DEFAULT 'Engineering',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_exam_type_dept (exam_type_name, department)
)";

if ($conn->query($create_exam_types_table)) {
    echo "<p>✅ Board exam types table created/verified</p>";
} else {
    echo "<p>❌ Error creating board_exam_types table: " . $conn->error . "</p>";
}

// Insert default exam types for Engineering if they don't exist
$default_exam_types = [
    'Civil Engineer Licensure Exam (CELE)',
    'Electrical Engineer Licensure Exam (REELE)', 
    'Mechanical Engineer Licensure Exam (MELE)',
    'Electronics Engineer Licensure Exam (EELE)',
    'Computer Engineer Licensure Exam (CPLE)'
];

foreach ($default_exam_types as $exam_type) {
    $check_exists = $conn->prepare("SELECT id FROM board_exam_types WHERE exam_type_name = ? AND department = 'Engineering'");
    $check_exists->bind_param("s", $exam_type);
    $check_exists->execute();
    
    if ($check_exists->get_result()->num_rows == 0) {
        $insert_type = $conn->prepare("INSERT INTO board_exam_types (exam_type_name, department) VALUES (?, 'Engineering')");
        $insert_type->bind_param("s", $exam_type);
        if ($insert_type->execute()) {
            echo "<p>✅ Added exam type: $exam_type</p>";
        } else {
            echo "<p>❌ Error adding exam type $exam_type: " . $conn->error . "</p>";
        }
    }
    $check_exists->close();
}

// Add foreign key constraint if it doesn't exist
$check_fk = $conn->query("SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                         WHERE TABLE_NAME = 'board_exam_dates' 
                         AND COLUMN_NAME = 'exam_type_id' 
                         AND CONSTRAINT_NAME LIKE 'fk_%'");

if ($check_fk->num_rows == 0) {
    $add_fk_sql = "ALTER TABLE board_exam_dates 
                   ADD CONSTRAINT fk_exam_type 
                   FOREIGN KEY (exam_type_id) REFERENCES board_exam_types(id) 
                   ON DELETE SET NULL ON UPDATE CASCADE";
    
    if ($conn->query($add_fk_sql)) {
        echo "<p>✅ Added foreign key constraint</p>";
    } else {
        echo "<p>❌ Error adding foreign key: " . $conn->error . "</p>";
    }
} else {
    echo "<p>ℹ️ Foreign key constraint already exists</p>";
}

echo "<h3>🎉 Database structure update completed!</h3>";
echo "<p><a href='manage_courses_engineering.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Manage Courses</a></p>";

$conn->close();
?>