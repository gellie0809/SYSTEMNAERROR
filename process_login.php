<?php
// process_login.php - SECURE VERSION (With Password Hashing)

// Connect to the database
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "project_db";
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"]);
    $pass_from_form = trim($_POST["password"]); // This is the plain password from the form

    // SECURE: Query only by email first
    $sql = "SELECT id, email, password FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    
    // Bind only the email
    $stmt->bind_param("s", $email);
    
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if user exists
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // SECURE: Use password_verify() to check the hashed password
        if (password_verify($pass_from_form, $user['password'])) {
            // SUCCESS! Password matches
            $_SESSION["users"] = $email;
            
            // Redirect based on department email
            switch ($email) {
                case 'eng_admin@lspu.edu.ph':
                    header("Location: dashboard_engineering.php");
                    break;
                case 'cas_admin@lspu.edu.ph':
                    header("Location: dashboard_cas.php");
                    break;
                case 'cbaa_admin@lspu.edu.ph':
                    header("Location: dashboard_cbaa.php");
                    break;
                case 'ccje_admin@lspu.edu.ph':
                    header("Location: dashboard_ccje.php");
                    break;
                case 'cte_admin@lspu.edu.ph':
                    header("Location: dashboard_cte.php");
                    break;
                case 'icts_admin@lspu.edu.ph':
                    header("Location: dashboard_icts.php");
                    break;
                case 'president@lspu.edu.ph':
                    header("Location: dashboard_president.php");
                    break;
                default:
                    header("Location: homepage_admin.php");
            }
            exit();
        } else {
            // FAILURE! Password doesn't match
            header("Location: index.php?error=1");
            exit();
        }
    } else {
        // FAILURE! No user found with that email
        header("Location: index.php?error=1");
        exit();
    }
    
    $stmt->close();
}

$conn->close();
?>