<?php
// Migration: make board_exam_dates unique index include exam_type_id so same date can be used for different exam types
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "project_db";
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) { die("DB connect error: " . $conn->connect_error); }

echo "Current indexes on board_exam_dates:\n";
$res = $conn->query("SHOW INDEX FROM board_exam_dates");
while ($row = $res->fetch_assoc()) {
    echo json_encode($row) . "\n";
}

// Try to drop old unique index if present (unique_exam_date_dept)
$idxName = 'unique_exam_date_dept';
$check = $conn->query("SHOW INDEX FROM board_exam_dates WHERE Key_name = '" . $idxName . "'");
if ($check && $check->num_rows > 0) {
    echo "Dropping index $idxName\n";
    if ($conn->query("ALTER TABLE board_exam_dates DROP INDEX `" . $idxName . "`") === TRUE) {
        echo "Dropped $idxName\n";
    } else {
        echo "Failed to drop $idxName: " . $conn->error . "\n";
    }
} else {
    echo "Index $idxName not present\n";
}

// Add new unique index on (exam_date, exam_type_id, department) if not exists
$newIdx = 'unique_exam_date_type_dept';
$check2 = $conn->query("SHOW INDEX FROM board_exam_dates WHERE Key_name = '" . $newIdx . "'");
if (!($check2 && $check2->num_rows > 0)) {
    $alter = "ALTER TABLE board_exam_dates ADD UNIQUE KEY `" . $newIdx . "` (exam_date, exam_type_id, department)";
    if ($conn->query($alter) === TRUE) {
        echo "Added unique index $newIdx\n";
    } else {
        echo "Failed to add $newIdx: " . $conn->error . "\n";
    }
} else {
    echo "Index $newIdx already exists\n";
}

// Display final indexes
echo "Final indexes on board_exam_dates:\n";
$res = $conn->query("SHOW INDEX FROM board_exam_dates");
while ($row = $res->fetch_assoc()) {
    echo json_encode($row) . "\n";
}

$conn->close();
?>