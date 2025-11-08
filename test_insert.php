<?php
// Simulate form submission
$_POST = array(
    'last_name' => 'Test',
    'first_name' => 'User', 
    'middle_name' => '',
    'suffix' => '',
    'sex' => 'Male',
    'course' => 'Bachelor of Science in Electronics Engineering (BSECE)',
    'year_graduated' => '2024',
    'board_exam_date' => '2024-05-15',
    'result' => 'Passed',
    'exam_type' => 'Repeater'
);

echo "Testing form data:\n";
print_r($_POST);

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "project_db";
$conn = new mysqli($servername, $username, $password, $dbname);

$last_name = trim($_POST["last_name"]);
$first_name = trim($_POST["first_name"]);
$middle_name = trim($_POST["middle_name"]);
$suffix = trim($_POST["suffix"]);
$sex = trim($_POST["sex"]);

// Combine names into full name
$name_parts = array($last_name, $first_name);
if (!empty($middle_name)) {
    $name_parts[] = $middle_name;
}
if (!empty($suffix)) {
    $name_parts[] = $suffix;
}
$name = implode(", ", $name_parts);

$course = trim($_POST["course"]);
$year_graduated = intval($_POST["year_graduated"]);
$board_exam_date = $_POST["board_exam_date"];
$result = $_POST["result"];
$exam_type = trim($_POST["exam_type"]);
$department = "Engineering";

echo "\nProcessed variables:\n";
echo "Name: '$name'\n";
echo "Sex: '$sex'\n";
echo "Course: '$course'\n";
echo "Year: $year_graduated\n";
echo "Date: '$board_exam_date'\n";
echo "Result: '$result'\n";
echo "Exam Type: '$exam_type'\n";
echo "Department: '$department'\n";

// Test the insert
$stmt = $conn->prepare("INSERT INTO board_passers (name, course, year_graduated, board_exam_date, result, department, sex, exam_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssisssss", $name, $course, $year_graduated, $board_exam_date, $result, $department, $sex, $exam_type);

if ($stmt->execute()) {
    $insert_id = $conn->insert_id;
    echo "\nSUCCESS: Test record inserted with ID: $insert_id\n";
    
    // Check what was actually stored
    $check = $conn->query("SELECT * FROM board_passers WHERE id = $insert_id");
    $row = $check->fetch_assoc();
    echo "\nWhat was stored in database:\n";
    print_r($row);
    
    // Clean up
    $conn->query("DELETE FROM board_passers WHERE id = $insert_id");
    echo "\nTest record deleted\n";
} else {
    echo "\nFAILED: " . $stmt->error . "\n";
}

$stmt->close();
$conn->close();
?>
<?php
// Simulate form submission
$_POST = array(
    'last_name' => 'Test',
    'first_name' => 'User', 
    'middle_name' => '',
    'suffix' => '',
    'sex' => 'Male',
    'course' => 'Bachelor of Science in Electronics Engineering (BSECE)',
    'year_graduated' => '2024',
    'board_exam_date' => '2024-05-15',
    'result' => 'Passed',
    'exam_type' => 'Repeater'
);

echo "Testing form data:\n";
print_r($_POST);

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "project_db";
$conn = new mysqli($servername, $username, $password, $dbname);

$last_name = trim($_POST["last_name"]);
$first_name = trim($_POST["first_name"]);
$middle_name = trim($_POST["middle_name"]);
$suffix = trim($_POST["suffix"]);
$sex = trim($_POST["sex"]);

// Combine names into full name
$name_parts = array($last_name, $first_name);
if (!empty($middle_name)) {
    $name_parts[] = $middle_name;
}
if (!empty($suffix)) {
    $name_parts[] = $suffix;
}
$name = implode(", ", $name_parts);

$course = trim($_POST["course"]);
$year_graduated = intval($_POST["year_graduated"]);
$board_exam_date = $_POST["board_exam_date"];
$result = $_POST["result"];
$exam_type = trim($_POST["exam_type"]);
$department = "Engineering";

echo "\nProcessed variables:\n";
echo "Name: '$name'\n";
echo "Sex: '$sex'\n";
echo "Course: '$course'\n";
echo "Year: $year_graduated\n";
echo "Date: '$board_exam_date'\n";
echo "Result: '$result'\n";
echo "Exam Type: '$exam_type'\n";
echo "Department: '$department'\n";

// Test the insert
$stmt = $conn->prepare("INSERT INTO board_passers (name, course, year_graduated, board_exam_date, result, department, sex, exam_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssisssss", $name, $course, $year_graduated, $board_exam_date, $result, $department, $sex, $exam_type);

if ($stmt->execute()) {
    $insert_id = $conn->insert_id;
    echo "\nSUCCESS: Test record inserted with ID: $insert_id\n";
    
    // Check what was actually stored
    $check = $conn->query("SELECT * FROM board_passers WHERE id = $insert_id");
    $row = $check->fetch_assoc();
    echo "\nWhat was stored in database:\n";
    print_r($row);
    
    // Clean up
    $conn->query("DELETE FROM board_passers WHERE id = $insert_id");
    echo "\nTest record deleted\n";
} else {
    echo "\nFAILED: " . $stmt->error . "\n";
}

$stmt->close();
$conn->close();
?>
