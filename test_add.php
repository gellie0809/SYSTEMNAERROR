<?php
// Test the add_board_passer_engineering.php with sample data
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';

$_POST = [
    'first_name' => 'John',
    'last_name' => 'Doe',
    'middle_name' => 'M',
    'suffix' => '',
    'sex' => 'Male',
    'course' => 'Bachelor of Science in Electrical Engineering',
    'year_graduated' => '2023',
    'board_exam_date' => '2023-06-15',
    'result' => 'Passed',
    'exam_type' => 'First Timer',
    'board_exam_type' => 'Registered Electrical Engineer Licensure Exam (REELE)'
];

// Start session for the test
session_start();
$_SESSION["users"] = 'eng_admin@lspu.edu.ph';

// Capture output
ob_start();
include 'add_board_passer_engineering.php';
$output = ob_get_clean();

echo "Test output:\n";
echo $output;
?>
