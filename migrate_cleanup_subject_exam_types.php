<?php
// Migration: remove duplicate subject_exam_types rows and add unique constraint
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "project_db";
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) { die("DB connect error: " . $conn->connect_error); }

echo "Starting dedupe...\n";
// Delete duplicates keeping the smallest id
$delSql = "DELETE t1 FROM subject_exam_types t1
JOIN subject_exam_types t2
ON t1.subject_id = t2.subject_id AND t1.exam_type_id = t2.exam_type_id
WHERE t1.id > t2.id";
if ($conn->query($delSql) === TRUE) {
  echo "Duplicates removed (if any)\n";
} else {
  echo "Delete duplicates failed: " . $conn->error . "\n";
}

// Add unique constraint if not exists
$idxCheck = $conn->query("SHOW INDEX FROM subject_exam_types WHERE Key_name = 'subject_exam_unique'");
if ($idxCheck && $idxCheck->num_rows > 0) {
  echo "Unique index already exists\n";
} else {
  $alter = "ALTER TABLE subject_exam_types ADD UNIQUE KEY subject_exam_unique (subject_id, exam_type_id)";
  if ($conn->query($alter) === TRUE) {
    echo "Unique index added\n";
  } else {
    echo "Failed to add unique index: " . $conn->error . "\n";
  }
}

$conn->close();
?>