<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "project_db";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create board_exam_dates table
$sql = "CREATE TABLE IF NOT EXISTS board_exam_dates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    exam_date DATE NOT NULL,
    exam_description VARCHAR(255) DEFAULT NULL,
    department VARCHAR(100) NOT NULL DEFAULT 'Engineering',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_exam_date_dept (exam_date, department)
)";

if ($conn->query($sql)) {
    echo "Table board_exam_dates created successfully\n";
} else {
    echo "Error creating table: " . $conn->error . "\n";
}

/* ======================================================
   OPTIONAL: Insert initial exam dates per department
   ====================================================== */
$departments = [
    'Engineering',
    'Arts and Science',
    'Business Administration and Accountancy',
    'Criminal Justice Education',
    'Teacher Education'
];

foreach ($departments as $dept) {
    // Example: insert a sample exam date if not exists
    $exam_date = '2025-12-15'; // adjust as needed
    $exam_description = 'Sample board exam';
    $stmt = $conn->prepare("INSERT IGNORE INTO board_exam_dates (exam_date, exam_description, department) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $exam_date, $exam_description, $dept);
    $stmt->execute();
    echo "Inserted sample exam date for department: $dept\n";
    $stmt->close();
}

$conn->close();
?>