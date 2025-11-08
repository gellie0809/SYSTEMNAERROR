<?php
// Simple migration runner for local use. Run from CLI or browser (prefer CLI):
// php apply_migrations.php

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "project_db";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

$errors = [];
$messages = [];

// 1) Ensure subjects.total_items exists
$col = $conn->query("SHOW COLUMNS FROM subjects LIKE 'total_items'");
if ($col && $col->num_rows === 0) {
  if ($conn->query("ALTER TABLE subjects ADD COLUMN total_items INT NOT NULL DEFAULT 50 AFTER subject_name")) {
    $messages[] = "Added subjects.total_items column (default 50).";
  } else {
    $errors[] = "Failed to add subjects.total_items: " . $conn->error;
  }
} else {
  $messages[] = "subjects.total_items already exists.";
}

// 2) Ensure board_passer_subjects.subject_id exists
$col2 = $conn->query("SHOW COLUMNS FROM board_passer_subjects LIKE 'subject_id'");
if ($col2 && $col2->num_rows === 0) {
  if ($conn->query("ALTER TABLE board_passer_subjects ADD COLUMN subject_id INT NULL AFTER board_passer_id")) {
    $messages[] = "Added board_passer_subjects.subject_id column.";
  } else {
    $errors[] = "Failed to add board_passer_subjects.subject_id: " . $conn->error;
  }
} else {
  $messages[] = "board_passer_subjects.subject_id already exists.";
}

// 3) Try to add foreign key for subject_id -> subjects(id) if not already present
// Check if constraint exists by searching information_schema
$fk_check = $conn->query("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = '" . $conn->real_escape_string($dbname) . "' AND TABLE_NAME = 'board_passer_subjects' AND COLUMN_NAME = 'subject_id' AND REFERENCED_TABLE_NAME = 'subjects'");
if ($fk_check && $fk_check->num_rows === 0) {
  // ensure referenced column exists and is indexed
  $ensure_index = $conn->query("ALTER TABLE subjects ADD PRIMARY KEY (id)"); // harmless if already primary key
  // Attempt to add FK
  $add_fk_sql = "ALTER TABLE board_passer_subjects ADD CONSTRAINT fk_bps_subject FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE RESTRICT";
  if ($conn->query($add_fk_sql)) {
    $messages[] = "Added foreign key fk_bps_subject on board_passer_subjects(subject_id) -> subjects(id).";
  } else {
    $errors[] = "Failed to add foreign key: " . $conn->error;
  }
} else {
  $messages[] = "Foreign key for board_passer_subjects.subject_id already present or could not be detected.";
}

// 4) Ensure subjects.exam_type_id exists and add FK to board_exam_types(id)
$col3 = $conn->query("SHOW COLUMNS FROM subjects LIKE 'exam_type_id'");
if ($col3 && $col3->num_rows === 0) {
  if ($conn->query("ALTER TABLE subjects ADD COLUMN exam_type_id INT NULL AFTER total_items")) {
    $messages[] = "Added subjects.exam_type_id column.";
  } else {
    $errors[] = "Failed to add subjects.exam_type_id: " . $conn->error;
  }
} else {
  $messages[] = "subjects.exam_type_id already exists.";
}

// Try to add FK from subjects.exam_type_id -> board_exam_types(id)
$fk_check2 = $conn->query("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = '" . $conn->real_escape_string($dbname) . "' AND TABLE_NAME = 'subjects' AND COLUMN_NAME = 'exam_type_id' AND REFERENCED_TABLE_NAME = 'board_exam_types'");
if ($fk_check2 && $fk_check2->num_rows === 0) {
  $add_fk2 = "ALTER TABLE subjects ADD CONSTRAINT fk_subjects_examtype FOREIGN KEY (exam_type_id) REFERENCES board_exam_types(id) ON DELETE SET NULL";
  if ($conn->query($add_fk2)) {
    $messages[] = "Added foreign key fk_subjects_examtype on subjects(exam_type_id) -> board_exam_types(id).";
  } else {
    $errors[] = "Failed to add foreign key subjects.exam_type_id: " . $conn->error;
  }
} else {
  $messages[] = "Foreign key for subjects.exam_type_id already present or could not be detected.";
}

// Output
if (!empty($messages)) {
  echo "SUCCESS:\n" . implode("\n", $messages) . "\n";
}
if (!empty($errors)) {
  echo "ERRORS:\n" . implode("\n", $errors) . "\n";
}

$conn->close();
