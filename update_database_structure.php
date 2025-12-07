<?php
// Database update script to fix board_passers table structure for all departments
include 'db_config.php';

// Departments to loop through
$departments = [
    'Engineering',
    'Arts and Science',
    'Business Administration and Accountancy',
    'Criminal Justice Education',
    'Teacher Education'
];

// Function to check if column exists
function columnExists($conn, $table, $column) {
    $query = "SHOW COLUMNS FROM $table LIKE '$column'";
    $result = $conn->query($query);
    return $result && $result->num_rows > 0;
}

echo "<h2>Database Structure Update - All Departments</h2>\n";

try {
    // Check current table structure
    $result = $conn->query("DESCRIBE board_passers");
    $current_columns = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $current_columns[] = $row['Field'];
        }
    }

    echo "<h3>Current table structure:</h3>\n";
    echo "<pre>" . print_r($current_columns, true) . "</pre>\n";

    // Convert single 'name' to separate name fields
    if (in_array('name', $current_columns) && !in_array('first_name', $current_columns)) {
        echo "<p>Converting single 'name' field to first_name, middle_name, last_name...</p>\n";

        $conn->query("ALTER TABLE board_passers ADD COLUMN first_name VARCHAR(100) DEFAULT '' AFTER id");
        $conn->query("ALTER TABLE board_passers ADD COLUMN middle_name VARCHAR(100) DEFAULT '' AFTER first_name");
        $conn->query("ALTER TABLE board_passers ADD COLUMN last_name VARCHAR(100) DEFAULT '' AFTER middle_name");

        $result = $conn->query("SELECT id, name FROM board_passers WHERE name IS NOT NULL AND name != ''");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $fullName = trim($row['name']);
                $nameParts = explode(' ', $fullName);

                $firstName = $nameParts[0];
                $lastName = end($nameParts);
                $middleName = count($nameParts) > 2 ? implode(' ', array_slice($nameParts, 1, -1)) : '';

                $stmt = $conn->prepare("UPDATE board_passers SET first_name = ?, middle_name = ?, last_name = ? WHERE id = ?");
                $stmt->bind_param("sssi", $firstName, $middleName, $lastName, $row['id']);
                $stmt->execute();
            }
        }

        $conn->query("ALTER TABLE board_passers DROP COLUMN name");
        echo "<p>✅ Name field successfully converted</p>\n";
    }

    // Add missing columns if they don't exist
    $required_columns = [
        'first_name' => 'VARCHAR(100) NOT NULL DEFAULT ""',
        'middle_name' => 'VARCHAR(100) DEFAULT ""',
        'last_name' => 'VARCHAR(100) NOT NULL DEFAULT ""',
        'sex' => 'VARCHAR(10) DEFAULT NULL',
        'course' => 'VARCHAR(255) NOT NULL',
        'year_graduated' => 'INT NOT NULL',
        'board_exam_date' => 'DATE NOT NULL',
        'result' => 'VARCHAR(10) NOT NULL',
        'department' => 'VARCHAR(100) NOT NULL',
        'exam_type' => 'VARCHAR(20) DEFAULT "First Timer"',
        'board_exam_type' => 'VARCHAR(100) DEFAULT "General Board Exam"'
    ];

    foreach ($required_columns as $column => $definition) {
        if (!columnExists($conn, 'board_passers', $column)) {
            $sql = "ALTER TABLE board_passers ADD COLUMN $column $definition";
            if ($conn->query($sql)) {
                echo "<p>✅ Added column: $column</p>\n";
            } else {
                echo "<p>❌ Error adding column $column: " . $conn->error . "</p>\n";
            }
        }
    }

    // Create board_exam_types table if missing
    $conn->query("CREATE TABLE IF NOT EXISTS board_exam_types (
        id INT AUTO_INCREMENT PRIMARY KEY,
        exam_name VARCHAR(255) NOT NULL,
        department VARCHAR(100) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Show updated table structure
    $result = $conn->query("DESCRIBE board_passers");
    $updated_columns = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $updated_columns[] = $row['Field'];
        }
    }

    echo "<h3>Updated table structure:</h3>\n";
    echo "<pre>" . print_r($updated_columns, true) . "</pre>\n";

    echo "<h3>✅ Database structure update completed successfully!</h3>\n";

    // Links for testing add board passer form for each department
    foreach ($departments as $dept) {
        $prefix = strtolower(substr(str_replace(' ', '', $dept), 0, 3)); // e.g., 'eng', 'art', 'bus'
        echo "<p><a href='add_board_passer_{$prefix}.php'>Test Add Board Passer Form - $dept</a></p>\n";
    }

} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>\n";
}

$conn->close();
?>