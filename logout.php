<?php
// logout.php

// 1. Start the session
session_start();

// 2. Unset all session variables
$_SESSION = array();

// 3. Destroy the session completely
session_destroy();

// 4. Redirect the user back to the main page
header("Location: mainpage.php");
exit();
?>
