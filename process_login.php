<?php
// process_login.php - INSECURE VERSION (No Hashing)

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

    // INSECURE: This query looks for an exact match of the plain password
    // in the database.
    $sql = "SELECT * FROM users WHERE email = ? AND password = ?";
    $stmt = $conn->prepare($sql);
    
    // Bind both the email and the plain password
    $stmt->bind_param("ss", $email, $pass_from_form);
    
    $stmt->execute();
    $result = $stmt->get_result();

    // If the query finds exactly one row, the login is successful
    if ($result->num_rows > 0) {
        // SUCCESS!
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
    }
    
    // FAILURE! No user was found with that email AND that exact password.
    header("Location: mainpage.php?error=1");
    exit();
}
?>