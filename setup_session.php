<?php
session_start();

// Set up engineering admin session for testing
$_SESSION["users"] = 'eng_admin@lspu.edu.ph';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Session Setup</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5; text-align: center; }
        .container { background: white; padding: 40px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); max-width: 500px; margin: 0 auto; }
        .success { color: #22c55e; font-size: 1.2em; margin-bottom: 20px; }
        .button { background: #3182ce; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 10px; }
        .button:hover { background: #2c5aa0; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔧 Engineering Dashboard Setup</h1>
        <div class='success'>✅ Session configured successfully!</div>
        <p>Engineering admin session has been set up.</p>
        <p><strong>Session User:</strong> " . htmlspecialchars($_SESSION["users"]) . "</p>
        
        <a href='dashboard_engineering.php' class='button'>🚀 Open Dashboard</a>
        <a href='test_dashboard.php' class='button'>🧪 Run Tests</a>
        
        <hr style='margin: 30px 0;'>
        <h3>Debug Information:</h3>
        <p><small>Session ID: " . session_id() . "</small></p>
        <p><small>Session Data: " . print_r($_SESSION, true) . "</small></p>
    </div>
</body>
</html>";
?>
