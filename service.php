<?php
session_start();

// Redirect to mainpage.php if not logged in as admin
if (!isset($_SESSION["users"])) {
    header("Location: mainpage.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Service - BOARD PASSING RATE SYSTEM</title>
  <link rel="stylesheet" href="style.css" />
  <link href="https://fonts.googleapis.com/css?family=Poppins:400,500,600,700&display=swap" rel="stylesheet">
</head>
<body>
  <header>
    <h2 class="logo"></h2>
    <nav class="navigation">
        <a href="homepage_admin.php">Home</a>
        <a href="about.php">About</a>
        <a href="service.php">Service</a>
        <a href="#" onclick="confirmLogout(event)" class="btnLogout-header">Logout</a>
    </nav>
  </header>
  <main class="content-area">
    <h1>School Contacts</h1>
    <p>Welcome, Admin: <strong><?php echo htmlspecialchars($_SESSION["users"]); ?></strong></p>
    <p>This page provides contact information for school departments and is only accessible to logged-in users.</p>
    <ul>
      <li>Registrar: registrar@lspu.edu.ph | (049) 501-1234</li>
      <li>ICTS: icts@lspu.edu.ph | (049) 501-5678</li>
      <li>Dean's Office: dean@lspu.edu.ph | (049) 501-9101</li>
      <li>President's Office: president@lspu.edu.ph | (049) 501-1122</li>
    </ul>
  </main>
  <script>
    function confirmLogout(event) {
      event.preventDefault();
      var userConfirmation = confirm("Are you sure you want to log out?");
      if (userConfirmation) {
        window.location.href = 'logout.php';
      }
    }
  </script>
</body>
</html>
