<?php
echo "<h2>PHP Configuration</h2>";
echo "<p><strong>Error Log Location:</strong> " . ini_get('error_log') . "</p>";
echo "<p><strong>Display Errors:</strong> " . ini_get('display_errors') . "</p>";
echo "<p><strong>Log Errors:</strong> " . ini_get('log_errors') . "</p>";
echo "<p><strong>Error Reporting:</strong> " . error_reporting() . "</p>";

// Trigger a test log entry
error_log("TEST LOG ENTRY - " . date('Y-m-d H:i:s'));
echo "<p>✓ Test log entry written</p>";

// Check if file exists
$log_file = ini_get('error_log');
if ($log_file && file_exists($log_file)) {
    echo "<p>✓ Log file exists: $log_file</p>";
    $recent_logs = file_get_contents($log_file);
    echo "<h3>Recent Log Entries (Last 2000 chars):</h3>";
    echo "<pre>" . htmlspecialchars(substr($recent_logs, -2000)) . "</pre>";
} else {
    echo "<p>Log file not found or not configured</p>";
}
?>
