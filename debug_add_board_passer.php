<?php
$conn = new mysqli('localhost', 'root', '', 'project_db');
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

echo "DEBUGGING ADD BOARD PASSER ISSUE\n";
echo "================================\n\n";

// 1. Check current table structure
echo "1. CURRENT TABLE STRUCTURE:\n";
echo "---------------------------\n";
$structure = $conn->query("DESCRIBE board_passers");
$columns = [];
if ($structure) {
    while ($row = $structure->fetch_assoc()) {
        $columns[] = $row['Field'];
        echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }
} else {
    echo "❌ Cannot describe table: " . $conn->error . "\n";
    die();
}

// 2. Check what columns the add_board_passer is trying to use
echo "\n2. COLUMNS USED IN ADD_BOARD_PASSER:\n";
echo "-----------------------------------\n";
$expectedColumns = ['name', 'sex', 'course', 'year_graduated', 'board_exam_date', 'result', 'exam_type', 'board_exam_type', 'department'];
foreach ($expectedColumns as $col) {
    if (in_array($col, $columns)) {
        echo "✅ $col - EXISTS\n";
    } else {
        echo "❌ $col - MISSING\n";
    }
}

// 3. Test manual insert like add_board_passer does
echo "\n3. TESTING ADD_BOARD_PASSER INSERT:\n";
echo "----------------------------------\n";
$stmt = $conn->prepare("INSERT INTO board_passers (name, sex, course, year_graduated, board_exam_date, result, exam_type, board_exam_type, department) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

if ($stmt) {
    $name = "TEST STUDENT " . date('H:i:s');
    $sex = "Male";
    $course = "Computer Engineering";
    $year_graduated = 2023;
    $board_exam_date = "2023-08-15";
    $result = "PASSED";
    $exam_type = "Computer Engineer Licensure Exam";
    $board_exam_type = "Board Exam";
    $department = "Engineering";
    
    $stmt->bind_param("sssisssss", $name, $sex, $course, $year_graduated, $board_exam_date, $result, $exam_type, $board_exam_type, $department);
    
    if ($stmt->execute()) {
        $insertId = $conn->insert_id;
        echo "✅ Insert successful! ID: $insertId\n";
        
        // Verify immediately
        $verify = $conn->query("SELECT * FROM board_passers WHERE id = $insertId");
        if ($verify && $verify->num_rows > 0) {
            $record = $verify->fetch_assoc();
            echo "✅ Record verified: " . $record['name'] . "\n";
        } else {
            echo "❌ Record not found after insert!\n";
        }
        
        // Count total records
        $count = $conn->query("SELECT COUNT(*) as count FROM board_passers");
        if ($count) {
            $total = $count->fetch_assoc()['count'];
            echo "✅ Total records now: $total\n";
        }
        
    } else {
        echo "❌ Insert failed: " . $stmt->error . "\n";
    }
    $stmt->close();
} else {
    echo "❌ Cannot prepare statement: " . $conn->error . "\n";
}

// 4. Check if there are any triggers or events affecting the table
echo "\n4. CHECKING FOR TRIGGERS:\n";
echo "-------------------------\n";
$triggers = $conn->query("SHOW TRIGGERS LIKE 'board_passers'");
if ($triggers && $triggers->num_rows > 0) {
    while ($row = $triggers->fetch_assoc()) {
        echo "⚠️ Trigger found: " . $row['Trigger'] . " (" . $row['Event'] . ")\n";
    }
} else {
    echo "✅ No triggers found\n";
}

// 5. Check MySQL error log for any issues
echo "\n5. CHECKING RECENT ERRORS:\n";
echo "--------------------------\n";
$result = $conn->query("SHOW GLOBAL STATUS LIKE 'Aborted%'");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo $row['Variable_name'] . ": " . $row['Value'] . "\n";
    }
}

$conn->close();
echo "\nDEBUG COMPLETE!\n";
?>
