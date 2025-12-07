<?php
// Script to add soft delete columns to all relevant tables
error_reporting(E_ALL);
ini_set('display_errors', 1);

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "project_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h2>Adding Soft Delete Columns</h2>";
echo "<p>This will add 'is_deleted' and 'deleted_at' columns to tables if they don't exist.</p>";

$tables = [
    'courses',
    'board_exam_types',
    'subjects',
    'board_exam_dates',
    'board_passers'
];

foreach ($tables as $table) {
    echo "<h3>Processing table: $table</h3>";
    
    // Check if table exists
    $check = $conn->query("SHOW TABLES LIKE '$table'");
    if ($check->num_rows == 0) {
        echo "<p style='color:orange;'>⚠ Table $table does not exist - skipping</p>";
        continue;
    }
    
    // Check if is_deleted column exists
    $check_col = $conn->query("SHOW COLUMNS FROM `$table` LIKE 'is_deleted'");
    if ($check_col->num_rows == 0) {
        $sql = "ALTER TABLE `$table` ADD COLUMN `is_deleted` TINYINT(1) DEFAULT 0 NOT NULL";
        if ($conn->query($sql)) {
            echo "<p style='color:green;'>✓ Added 'is_deleted' column to $table</p>";
        } else {
            echo "<p style='color:red;'>✗ Error adding 'is_deleted' to $table: " . $conn->error . "</p>";
        }
    } else {
        echo "<p>• 'is_deleted' column already exists in $table</p>";
    }
    
    // Check if deleted_at column exists
    $check_col2 = $conn->query("SHOW COLUMNS FROM `$table` LIKE 'deleted_at'");
    if ($check_col2->num_rows == 0) {
        $sql = "ALTER TABLE `$table` ADD COLUMN `deleted_at` TIMESTAMP NULL DEFAULT NULL";
        if ($conn->query($sql)) {
            echo "<p style='color:green;'>✓ Added 'deleted_at' column to $table</p>";
        } else {
            echo "<p style='color:red;'>✗ Error adding 'deleted_at' to $table: " . $conn->error . "</p>";
        }
    } else {
        echo "<p>• 'deleted_at' column already exists in $table</p>";
    }
}

echo "<hr>";
echo "<h3>Verification</h3>";

foreach ($tables as $table) {
    $check = $conn->query("SHOW TABLES LIKE '$table'");
    if ($check->num_rows > 0) {
        echo "<h4>$table structure:</h4>";
        $result = $conn->query("DESCRIBE `$table`");
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        while ($row = $result->fetch_assoc()) {
            $highlight = ($row['Field'] == 'is_deleted' || $row['Field'] == 'deleted_at') ? 'style="background-color:#d4edda;"' : '';
            echo "<tr $highlight>";
            echo "<td>{$row['Field']}</td>";
            echo "<td>{$row['Type']}</td>";
            echo "<td>{$row['Null']}</td>";
            echo "<td>{$row['Key']}</td>";
            echo "<td>{$row['Default']}</td>";
            echo "</tr>";
        }
        echo "</table><br>";
    }
}

$conn->close();

echo "<hr>";
echo "<p><strong>Next Steps:</strong></p>";
echo "<ol>";
echo "<li>Review the changes above</li>";
echo "<li>The manage_data_engineering.php file will be updated to use soft deletes</li>";
echo "<li>Deleted items will be hidden from the website but remain in the database</li>";
echo "</ol>";
echo "<p><a href='manage_data_engineering.php'>Go to Manage Data</a></p>";
?>
