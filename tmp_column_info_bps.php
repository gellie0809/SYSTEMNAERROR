<?php
$c=new mysqli('localhost','root','','project_db');
if($c->connect_error){echo 'CONNECT ERROR: '.$c->connect_error; exit(1);} 
$res=$c->query("SHOW COLUMNS FROM board_passer_subjects LIKE 'passed'");
if(!$res){ echo 'QUERY_ERROR: '.$c->error; exit(1);} 
$row=$res->fetch_assoc();
print_r($row);
?>