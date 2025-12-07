<?php
// Test add_board_passer for all departments

$departments = [
    'Engineering' => 'eng',
    'Arts and Science' => 'cas',
    'Business Administration and Accountancy' => 'cbaa',
    'Criminal Justice Education' => 'ccje',
    'Teacher Education' => 'cte'
];

session_start();

// Sample POST data template
$sample_data = [
    'first_name' => 'John',
    'last_name' => 'Doe',
    'middle_name' => 'M',
    'suffix' => '',
    'sex' => 'Male',
    'course' => '', // will set per department
    'year_graduated' => '2023',
    'board_exam_date' => '2023-06-15',
    'result' => 'Passed',
    'exam_type' => 'First Timer',
    'board_exam_type' => '' // will set per department
];

// Map sample courses and exam types per department
$department_courses = [
    'Engineering' => 'Bachelor of Science in Electrical Engineering',
    'Arts and Science' => 'Bachelor of Arts in Psychology',
    'Business Administration and Accountancy' => 'Bachelor of Science in Accountancy',
    'Criminal Justice Education' => 'Bachelor of Science in Criminology',
    'Teacher Education' => 'Bachelor of Secondary Education'
];

$department_exam_types = [
    'Engineering' => 'Registered Electrical Engineer Licensure Exam (REELE)',
    'Arts and Science' => 'Psychologist Licensure Exam',
    'Business Administration and Accountancy' => 'CPA Licensure Exam',
    'Criminal Justice Education' => 'Criminologist Licensure Exam',
    'Teacher Education' => 'Teacher Licensure Exam'
];

// Set session user dynamically per department
$department_admins = [
    'Engineering' => 'eng_admin@lspu.edu.ph',
    'Arts and Science' => 'cas_admin@lspu.edu.ph',
    'Business Administration and Accountancy' => 'cbaa_admin@lspu.edu.ph',
    'Criminal Justice Education' => 'ccje_admin@lspu.edu.ph',
    'Teacher Education' => 'cte_admin@lspu.edu.ph'
];

foreach ($departments as $dept_name => $dept_key) {
    echo "=== Testing $dept_name ===\n";

    // Set POST data
    $_POST = $sample_data;
    $_POST['course'] = $department_courses[$dept_name];
    $_POST['board_exam_type'] = $department_exam_types[$dept_name];

    // Set session user for department admin
    $_SESSION['users'] = $department_admins[$dept_name];

    // Set request method and AJAX header
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';

    // Include the department-specific add_board_passer file
    $file_path = "add_board_passer_{$dept_key}.php";
    if (file_exists($file_path)) {
        ob_start();
        include $file_path;
        $output = ob_get_clean();
        echo $output . "\n";
    } else {
        echo "❌ File $file_path not found\n";
    }

    echo "\n";
}

echo "✅ All department tests completed.\n";