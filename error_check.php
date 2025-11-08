<?php
echo "PHP ERROR LOG CHECK\n";
echo "==================\n\n";

// Get PHP error log location
$errorLogPath = ini_get('error_log');
if ($errorLogPath) {
    echo "Error log path: $errorLogPath\n\n";
    
    if (file_exists($errorLogPath)) {
        echo "Recent errors:\n";
        echo "-------------\n";
        $lines = file($errorLogPath);
        $recentLines = array_slice($lines, -20); // Last 20 lines
        foreach ($recentLines as $line) {
            echo $line;
        }
    } else {
        echo "Error log file not found\n";
    }
} else {
    echo "Error log path not configured\n";
}

// Also check for any MySQL errors
echo "\n\nMySQL Connection Test:\n";
echo "=====================\n";
$conn = new mysqli('localhost', 'root', '', 'project_db');
if ($conn->connect_error) {
    echo "❌ Connection failed: " . $conn->connect_error . "\n";
} else {
    echo "✅ MySQL connection successful\n";
    
    // Check if table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'board_passers'");
    if ($tableCheck && $tableCheck->num_rows > 0) {
        echo "✅ board_passers table exists\n";
        
        // Check table structure
        $structure = $conn->query("DESCRIBE board_passers");
        echo "\nTable structure:\n";
        while ($row = $structure->fetch_assoc()) {
            echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
        }
    } else {
        echo "❌ board_passers table NOT found\n";
    }
}

$conn->close();
?>
