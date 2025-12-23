<?php
/**
 * Password Hashing Migration Script
 * This script converts all plain text passwords in the users table to secure hashed passwords
 * Run this ONCE to migrate existing passwords
 */

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "project_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<!DOCTYPE html>
<html>
<head>
    <title>Password Migration</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { color: green; background: #d4edda; padding: 10px; margin: 10px 0; border-radius: 5px; }
        .error { color: red; background: #f8d7da; padding: 10px; margin: 10px 0; border-radius: 5px; }
        .info { color: #856404; background: #fff3cd; padding: 10px; margin: 10px 0; border-radius: 5px; }
        .warning { color: #856404; background: #fff3cd; padding: 15px; margin: 15px 0; border-radius: 5px; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background-color: #f2f2f2; }
        .btn { background-color: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 10px 0; }
        .btn:hover { background-color: #45a049; }
    </style>
</head>
<body>
    <h1>🔒 Password Hashing Migration</h1>";

// Check if password column is already hashed
$check_query = "SELECT email, password FROM users LIMIT 1";
$check_result = $conn->query($check_query);

if ($check_result->num_rows > 0) {
    $row = $check_result->fetch_assoc();
    $sample_password = $row['password'];
    
    // Check if password looks like it's already hashed (bcrypt starts with $2y$)
    if (strpos($sample_password, '$2y$') === 0) {
        echo "<div class='warning'>⚠️ PASSWORDS APPEAR TO BE ALREADY HASHED!</div>";
        echo "<div class='info'>The passwords in your database already seem to be hashed. Running this script again might cause issues.</div>";
        echo "<p><a href='index.php' class='btn'>Go to Login Page</a></p>";
        echo "</body></html>";
        exit();
    }
}

// Get all users
$sql = "SELECT id, email, password FROM users";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo "<div class='info'>📊 Found " . $result->num_rows . " user(s) to migrate</div>";
    echo "<table>";
    echo "<tr><th>Email</th><th>Old Password (Plain)</th><th>New Password (Hashed)</th><th>Status</th></tr>";
    
    $updated_count = 0;
    $failed_count = 0;
    
    while ($row = $result->fetch_assoc()) {
        $id = $row['id'];
        $email = $row['email'];
        $plain_password = $row['password'];
        
        // Hash the password using bcrypt (PASSWORD_DEFAULT uses bcrypt)
        $hashed_password = password_hash($plain_password, PASSWORD_DEFAULT);
        
        // Update the password in database
        $update_sql = "UPDATE users SET password = ? WHERE id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("si", $hashed_password, $id);
        
        if ($stmt->execute()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($email) . "</td>";
            echo "<td><code>" . htmlspecialchars($plain_password) . "</code></td>";
            echo "<td><code>" . substr($hashed_password, 0, 30) . "...</code></td>";
            echo "<td><span class='success'>✓ Updated</span></td>";
            echo "</tr>";
            $updated_count++;
        } else {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($email) . "</td>";
            echo "<td><code>" . htmlspecialchars($plain_password) . "</code></td>";
            echo "<td>-</td>";
            echo "<td><span class='error'>✗ Failed</span></td>";
            echo "</tr>";
            $failed_count++;
        }
        
        $stmt->close();
    }
    
    echo "</table>";
    
    echo "<div class='success'>";
    echo "<h3>✅ Migration Complete!</h3>";
    echo "<p>Successfully hashed <strong>$updated_count</strong> password(s)</p>";
    if ($failed_count > 0) {
        echo "<p>Failed to hash <strong>$failed_count</strong> password(s)</p>";
    }
    echo "</div>";
    
    echo "<div class='warning'>";
    echo "<h3>⚠️ IMPORTANT: Next Steps</h3>";
    echo "<ol>";
    echo "<li>The login system has been updated to use secure password hashing</li>";
    echo "<li>All existing passwords have been hashed using bcrypt</li>";
    echo "<li><strong>DO NOT run this script again</strong> - it will hash already-hashed passwords</li>";
    echo "<li>Test the login with each department account</li>";
    echo "<li>Delete or rename this file (hash_passwords_migration.php) after successful testing</li>";
    echo "</ol>";
    echo "</div>";
    
    echo "<p><a href='index.php' class='btn'>Go to Login Page to Test</a></p>";
    
} else {
    echo "<div class='error'>No users found in the database</div>";
}

$conn->close();

echo "</body></html>";
?>
