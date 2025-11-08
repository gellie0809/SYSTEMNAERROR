<?php
$c=new mysqli('localhost','root','','project_db');
if ($c->connect_error) { echo "DB error: " . $c->connect_error; exit(1);} 
$res=$c->query('SELECT id, subject_name, total_items FROM subjects WHERE department="Engineering" ORDER BY subject_name, id');
$out=[]; while($r=$res->fetch_assoc()) $out[]=$r; echo json_encode($out, JSON_PRETTY_PRINT), PHP_EOL; $c->close();
?>