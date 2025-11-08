<?php
session_start();

// Redirect to index.php if not logged in
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
  <title>BOARD PASSING RATE SYSTEM</title>
  <link rel="stylesheet" href="style.css" />
  <link href="https://fonts.googleapis.com/css?family=Poppins:400,500,600,700&display=swap" rel="stylesheet">

</head>
<body>

  <!-- HEADER -->
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
        <h1>LSPU- SPCC</h1>
        <h2>Admin Dashboard</h2>
        <p>Explore trends, pass rates, top programs, and batch performance.</p>
        <p>Designed for Students, Faculty, and Public </p>
    </main>
  

  <script src="script.js"></script>
    
    <!-- These are for the icons -->
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    <script>

        // --- Function 1: Show error message on failed login ---
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('error')) {
            alert("Invalid email or password. Please try again.");
        }
        function confirmLogout(event) {
            event.preventDefault();
            var userConfirmation = confirm("Are you sure you want to log out?");
            if (userConfirmation) {
              window.location.href = 'logout.php';
             }
    }
  </script>
  </body>
</html><?php
session_start();

// Redirect to index.php if not logged in
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
  <title>BOARD PASSING RATE SYSTEM</title>
  <link rel="stylesheet" href="style.css" />
  <link href="https://fonts.googleapis.com/css?family=Poppins:400,500,600,700&display=swap" rel="stylesheet">
  <style>
    body {
      background: linear-gradient(120deg, #e0e7ef 0%, #f7fafc 100%);
      min-height: 100vh;
    }
    .admin-card {
      background: #fff;
      box-shadow: 0 8px 32px rgba(22,41,56,0.12);
      border-radius: 18px;
      max-width: 480px;
      margin: 120px auto 0 auto;
      padding: 40px 32px 32px 32px;
      text-align: center;
      position: relative;
    }
    .admin-card h1 {
      font-size: 2.7rem;
      font-weight: 700;
      color: #162938;
      margin-bottom: 8px;
      letter-spacing: 1px;
    }
    .admin-card h2 {
      font-size: 2rem;
      font-weight: 600;
      color: #1a2a36;
      margin-bottom: 18px;
    }
    .admin-card .divider {
      width: 60px;
      height: 4px;
      background: #1a2a36;
      border-radius: 2px;
      margin: 0 auto 18px auto;
      opacity: 0.12;
    }
    .admin-card p {
      font-size: 1.08rem;
      color: #3a4a5a;
      margin-bottom: 10px;
    }
    .welcome-admin {
      font-size: 1.08rem;
      color: #1a2a36;
      margin-top: 18px;
      font-weight: 500;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
    }
    .welcome-admin ion-icon {
      font-size: 1.3em;
      color: #1a2a36;
    }
    .btnLogout-header {
      border: none;
      border-radius: 12px;
      padding: 10px 32px;
      color: #fff;
      font-size: 1.05rem;
      font-weight: 500;
      background: linear-gradient(90deg, #1a2a36 0%, #3a4a5a 100%);
      box-shadow: 0 2px 8px rgba(22,41,56,0.10);
      cursor: pointer;
      transition: background 0.3s, box-shadow 0.3s, transform 0.2s;
      outline: none;
    }
    .btnLogout-header:hover {
      background: linear-gradient(90deg, #3a4a5a 0%, #1a2a36 100%);
      box-shadow: 0 4px 16px rgba(22,41,56,0.18);
      transform: translateY(-2px) scale(1.04);
    }
    @media (max-width: 600px) {
      .admin-card {
        margin: 90px 8px 0 8px;
        padding: 28px 10px 18px 10px;
      }
      .admin-card h1 {
        font-size: 1.3rem;
      }
    }
  </style>
</head>
<body>
  <!-- HEADER -->
  <header>
    <h2 class="logo"></h2>
    <nav class="navigation">
        <a href="homepage_admin.php" onclick="smoothNavigate(event, 'homepage_admin.php')">Home</a>
        <a href="about.php" onclick="smoothNavigate(event, 'about.php')">About</a>
        <a href="service.php" onclick="smoothNavigate(event, 'service.php')">Service</a>
        <a href="#" onclick="confirmLogout(event)" class="btnLogout-header">Logout</a>
    </nav>
  </header>
  <main>
    <div class="admin-card">
      <h1>LSPU- SPCC</h1>
      <h2>Admin Dashboard</h2>
      <div class="divider"></div>
      <p>Explore trends, pass rates, top programs, and batch performance.</p>
      <p>Designed for Students, Faculty, and Public</p>
      <div class="welcome-admin">
        <ion-icon name="person-circle-outline"></ion-icon>
        Welcome, <strong><?php echo htmlspecialchars($_SESSION["users"]); ?></strong>
      </div>
    </div>
  </main>
  <script src="script.js"></script>
  <!-- These are for the icons -->
  <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
  <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
  <script>
    // --- Function 1: Show error message on failed login ---
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('error')) {
      alert("Invalid email or password. Please try again.");
    }
    function confirmLogout(event) {
      event.preventDefault();
      var userConfirmation = confirm("Are you sure you want to log out?");
      if (userConfirmation) {
        window.location.href = 'logout.php';
      }
    }
    function smoothNavigate(event, url) {
      event.preventDefault();
      document.body.classList.add('fade-out');
      setTimeout(function() {
        window.location.href = url;
      }, 500);
    }
  </script>
</body>
</html>