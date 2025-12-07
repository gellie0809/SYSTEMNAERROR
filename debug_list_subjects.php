<?php
$c = new mysqli('localhost', 'root', '', 'project_db');
if ($c->connect_error) {
    echo "DB error: " . $c->connect_error;
    exit(1);
}

$departments = [
    'Engineering',
    'Arts and Science',
    'Business Administration and Accountancy',
    'Criminal Justice Education',
    'Teacher Education'
];

$all_departments_data = [];

foreach ($departments as $dept) {
    $res = $c->query("SELECT id, subject_name, total_items 
                      FROM subjects 
                      WHERE department='$dept' 
                      ORDER BY subject_name, id");
    $out = [];
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $out[] = $r;
        }
    }
    $all_departments_data[$dept] = $out;
}

echo json_encode($all_departments_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;

$c->close();
?>