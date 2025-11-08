<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "project_db";
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }
$res = $conn->query("SELECT COUNT(*) AS c FROM board_passers");
if ($res) { $r = $res->fetch_assoc(); echo $r['c']; }
else { echo 'ERR'; }
?>