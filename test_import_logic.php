<?php
// Let's create a test to understand the duplicate issue better

// Test the name construction logic
function testNameConstruction($firstName, $lastName, $middleName) {
    $fullName = $firstName . ' ' . $middleName . ' ' . $lastName;
    $fullName = trim(str_replace('  ', ' ', $fullName));
    return $fullName;
}

echo "Testing name construction:\n";
echo "=========================\n";

// Test cases based on the screenshot
$testCases = [
    ['Aeiro', 'Dela Cruz', 'Santos'],
    ['Babae', 'Garcia', 'Lopez'],
    ['Lalaki', 'Santos', 'Reyes']
];

foreach ($testCases as $i => $case) {
    $fullName = testNameConstruction($case[0], $case[1], $case[2]);
    echo ($i + 1) . ". Name: '$fullName'\n";
}

echo "\n\nTesting date parsing:\n";
echo "====================\n";

// Test various date formats that might be in the CSV
$testDates = [
    '2023-05-15',
    '05/15/2023',
    '15-05-2023',
    '2023/05/15',
    'invalid_date',
    '########',
    ''
];

foreach ($testDates as $testDate) {
    $dateObj = DateTime::createFromFormat('Y-m-d', $testDate);
    if ($dateObj) {
        $year = (int)$dateObj->format('Y');
        $isValid = ($year >= 2019 && $year <= 2024);
        echo "Date: '$testDate' -> Valid: " . ($isValid ? "YES" : "NO") . " (Year: $year)\n";
    } else {
        echo "Date: '$testDate' -> INVALID FORMAT\n";
    }
}

// Check current database state
echo "\n\nDatabase check:\n";
echo "===============\n";

$conn = new mysqli('localhost', 'root', '', 'project_db');
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

$result = $conn->query("SELECT COUNT(*) as count FROM board_passers WHERE department = 'Engineering'");
if ($result) {
    $row = $result->fetch_assoc();
    echo "Total Engineering records in database: " . $row['count'] . "\n";
}

$conn->close();
?>
