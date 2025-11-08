<?php
session_start();

// Log errors to the server, but don't echo HTML to the client to keep JSON clean
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Log all incoming data for debugging
error_log("Update request received. POST data: " . print_r($_POST, true));
error_log("Session data: " . print_r($_SESSION, true));

// Handle test requests for debugging
if (isset($_POST['test'])) {
    if ($_POST['test'] === 'auth_check') {
        if (isset($_SESSION["users"]) && $_SESSION["users"] === 'eng_admin@lspu.edu.ph') {
            echo json_encode([
                'success' => true, 
                'message' => 'Authentication successful',
                'user' => $_SESSION["users"],
                'test_type' => 'auth_check'
            ]);
        } else {
            echo json_encode([
                'success' => false, 
                'message' => 'Authentication failed',
                'session_user' => $_SESSION["users"] ?? 'No session',
                'test_type' => 'auth_check'
            ]);
        }
        exit();
    }
}

// Only allow College of Engineering admin
if (!isset($_SESSION["users"]) || $_SESSION["users"] !== 'eng_admin@lspu.edu.ph') {
    http_response_code(403);
    error_log("Unauthorized access attempt. Session user: " . ($_SESSION["users"] ?? 'No session'));
    echo json_encode([
        'success' => false, 
        'message' => 'Unauthorized access - session not found or wrong user',
        'session_user' => $_SESSION["users"] ?? 'No session',
        'required_user' => 'eng_admin@lspu.edu.ph'
    ]);
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "project_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    http_response_code(500);
    error_log("Database connection failed: " . $conn->connect_error);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get student ID - this is the primary key for updates
    $student_id = isset($_POST['student_id']) ? intval($_POST['student_id']) : 0;
    
    // Get updated data (now accepting separate name fields and extra optional fields)
    $new_first = trim($_POST['first_name'] ?? '');
    $new_middle = trim($_POST['middle_name'] ?? '');
    $new_last = trim($_POST['last_name'] ?? '');
    $new_suffix = trim($_POST['suffix'] ?? '');
    $new_sex = trim($_POST['sex'] ?? '');
    $new_rating = trim($_POST['rating'] ?? '');
    $new_course = trim($_POST['course'] ?? '');
    $new_year = trim($_POST['year_graduated'] ?? '');
    // board_exam_date may be omitted from edit form (we preserve existing value in that case)
    $new_date = isset($_POST['board_exam_date']) ? trim($_POST['board_exam_date']) : null;
    $new_result = trim($_POST['result'] ?? '');
    $new_exam_type = trim($_POST['exam_type'] ?? '');
    $new_board_exam_type = trim($_POST['board_exam_type'] ?? '');
    
    // Compose full name for logs and response
    $composed_name = trim($new_last . ', ' . $new_first . ' ' . $new_middle . ' ' . $new_suffix);
    error_log("Update request - Student ID: $student_id, Name: $composed_name, Course: $new_course");
    
    // Validate required fields
    error_log("Validation - Student ID: '$student_id', Course: '$new_course'");
    if ($student_id <= 0) {
        error_log("Student ID validation failed: '$student_id'");
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Invalid student ID provided: '$student_id'"]);
        exit();
    }
    
    if (empty($new_first) || empty($new_last) || empty($new_course) || empty($new_year) || empty($new_sex)) {
        error_log("Missing required fields");
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit();
    }
    
    // Validate name parts format
    foreach ([['First name',$new_first],['Middle name',$new_middle],['Last name',$new_last],['Suffix',$new_suffix]] as $pair) {
        list($label,$val) = $pair;
        if ($val !== '' && !preg_match('/^[A-Za-z ,.\'-]+$/', $val)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $label.' has invalid characters.']);
            exit();
        }
    }
    
    // Validate year
    $current_year = date('Y');
    if (!is_numeric($new_year) || $new_year < 1950 || $new_year > $current_year) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Year must be between 1950 and $current_year"]);
        exit();
    }
    
    // Validate date format if a new date was provided; otherwise we'll keep the existing date
    if ($new_date !== null) {
        error_log("Date validation - Received date: '$new_date'");
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $new_date)) {
            error_log("Date format validation failed for: '$new_date'");
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => "Invalid date format. Expected YYYY-MM-DD, received: '$new_date'"]);
            exit();
        }
    }
    
    try {
        // First, check if the record exists using the ID
        $check_query = "SELECT id, CONCAT(first_name, ' ', IFNULL(middle_name, ''), ' ', last_name) as full_name, 
                               course, year_graduated, board_exam_date 
                        FROM board_passers 
                        WHERE department = 'Engineering' AND id = ?";
        
        $check_stmt = $conn->prepare($check_query);
        if (!$check_stmt) {
            throw new Exception('Failed to prepare check statement: ' . $conn->error);
        }
        
        $check_stmt->bind_param('i', $student_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows === 0) {
            error_log("No record found with ID: $student_id");
            echo json_encode(['success' => false, 'message' => 'Record not found']);
            exit();
        }
        
        $existing_record = $check_result->fetch_assoc();
        error_log("Found record to update: ID " . $existing_record['id'] . " - " . $existing_record['full_name']);
        
        // Determine effective date (keep existing if not provided)
        $effective_date = ($new_date !== null && $new_date !== '') ? $new_date : ($existing_record['board_exam_date'] ?? null);

        // Build update list dynamically based on existing columns (avoid errors like unknown column 'suffix')
        $colsRes = $conn->query("SHOW COLUMNS FROM board_passers");
        if (!$colsRes) { throw new Exception('Failed to read table columns: ' . $conn->error); }
        $existing_cols = [];
        while ($cr = $colsRes->fetch_assoc()) { $existing_cols[$cr['Field']] = true; }

        // Candidate fields and values
        $field_values = [
            'first_name'      => $new_first,
            'middle_name'     => $new_middle,
            'last_name'       => $new_last,
            'suffix'          => $new_suffix, // optional; will be skipped if column doesn't exist
            'sex'             => $new_sex,
            'rating'          => $new_rating,
            'course'          => $new_course,
            'year_graduated'  => $new_year,
            'board_exam_date' => $effective_date,
            'result'          => $new_result,
            'exam_type'       => $new_exam_type,
            'board_exam_type' => $new_board_exam_type
        ];

        $set_parts = [];
        $bind_values = [];
        $bind_types  = '';

        foreach ($field_values as $col => $val) {
            if (isset($existing_cols[$col])) {
                $set_parts[] = "$col = ?";
                $bind_values[] = $val;
                // crude typing: year_graduated as int, others string
                if ($col === 'year_graduated') { $bind_types .= 'i'; }
                else { $bind_types .= 's'; }
            }
        }

        if (empty($set_parts)) { throw new Exception('No updatable columns found in table schema'); }

        $update_query = "UPDATE board_passers SET " . implode(', ', $set_parts) . " WHERE department = 'Engineering' AND id = ?";
        $stmt = $conn->prepare($update_query);
        if (!$stmt) { throw new Exception('Failed to prepare update statement: ' . $conn->error); }

        // Add ID binding
        $bind_types .= 'i';
        $bind_values[] = $student_id;

        error_log("Executing update with ID: $student_id, dynamic fields: " . implode(', ', array_keys($field_values)));

        // Build references for bind_param
        $bind_params = [];
        $bind_params[] = & $bind_types;
        foreach ($bind_values as $k => $v) { $bind_params[] = & $bind_values[$k]; }
        // Call bind_param dynamically
        call_user_func_array([$stmt, 'bind_param'], $bind_params);
        
                // Perform everything in a transaction so the main record update and subject rows are atomic
                $conn->begin_transaction();

                if (!$stmt->execute()) {
                        $conn->rollback();
                        throw new Exception('Failed to update record: ' . $stmt->error);
                }

                error_log("Update executed. Affected rows: " . $stmt->affected_rows);

                // Collect submitted subject grades/results from the edit modal
                $subject_updates = [];
                foreach ($_POST as $k => $v) {
                    if (preg_match('/^edit_subject_grade_(\d+)$/', $k, $m)) {
                        $sid = intval($m[1]);
                        $grade_val = is_numeric($v) ? intval($v) : null;
                        $res_key = 'edit_subject_result_' . $sid;
                        $result_val = isset($_POST[$res_key]) ? trim($_POST[$res_key]) : '';
                        // Only include if grade provided; result will be computed if DB expects numeric or normalized
                        if ($grade_val !== null) {
                            $subject_updates[] = ['subject_id' => $sid, 'grade' => $grade_val, 'result' => $result_val];
                        }
                    }
                }

                // Update subject rows if any were provided
                try {
                    // Remove existing subject rows for this passer
                    $del_sql = $conn->prepare("DELETE FROM board_passer_subjects WHERE board_passer_id = ?");
                    if ($del_sql) { $del_sql->bind_param('i', $student_id); $del_sql->execute(); $del_sql->close(); }

                    // Check schema for subject_id column
                    $colCheck = $conn->query("SHOW COLUMNS FROM board_passer_subjects LIKE 'subject_id'");
                    $has_subject_id = ($colCheck && $colCheck->num_rows > 0);

                    if (!empty($subject_updates)) {
                        // detect whether subject table has a 'result' or 'passed' column and its type
                        $colRes = $conn->query("SHOW COLUMNS FROM board_passer_subjects LIKE 'result'");
                        $colPassed = $conn->query("SHOW COLUMNS FROM board_passer_subjects LIKE 'passed'");
                        $subject_result_col = null; $subject_result_is_numeric = false;
                        if ($colRes && $colRes->num_rows > 0) { $subject_result_col = 'result'; $ci = $colRes->fetch_assoc(); if (preg_match('/^(tinyint|int|smallint)/i',$ci['Type'])) $subject_result_is_numeric=true; }
                        elseif ($colPassed && $colPassed->num_rows > 0) { $subject_result_col = 'passed'; $ci = $colPassed->fetch_assoc(); if (preg_match('/^(tinyint|int|smallint)/i',$ci['Type'])) $subject_result_is_numeric=true; }

                        if ($has_subject_id) {
                            if ($subject_result_col) {
                                $ins = $conn->prepare("INSERT INTO board_passer_subjects (board_passer_id, subject_id, grade, " . $subject_result_col . ") VALUES (?, ?, ?, ?)");
                            } else {
                                $ins = $conn->prepare("INSERT INTO board_passer_subjects (board_passer_id, subject_id, grade) VALUES (?, ?, ?)");
                            }
                        } else {
                            if ($subject_result_col) {
                                $ins = $conn->prepare("INSERT INTO board_passer_subjects (board_passer_id, grade, " . $subject_result_col . ") VALUES (?, ?, ?)");
                            } else {
                                $ins = $conn->prepare("INSERT INTO board_passer_subjects (board_passer_id, grade) VALUES (?, ?)");
                            }
                        }

                        if (!$ins) throw new Exception('Prepare insert subject failed: ' . $conn->error);

                        foreach ($subject_updates as $su) {
                            // compute result from grade vs subject total_items if possible
                            $resv = $su['result'];
                            // try to fetch subject total_items
                            $total_items = 0;
                            $q = $conn->prepare('SELECT COALESCE(total_items,50) AS t FROM subjects WHERE id = ? LIMIT 1');
                            if ($q) { $q->bind_param('i', $su['subject_id']); $q->execute(); $qr = $q->get_result(); if ($qr && $r=$qr->fetch_assoc()) $total_items=intval($r['t']); $q->close(); }
                            if ($total_items > 0) {
                                $pct = ($su['grade'] / $total_items) * 100;
                                $resv = ($pct >= 75) ? 'Passed' : 'Failed';
                            }

                            if ($has_subject_id) {
                                if ($subject_result_col) {
                                    if ($subject_result_is_numeric) {
                                        $ins->bind_param('iiii', $student_id, $su['subject_id'], $su['grade'], ($resv === 'Passed' ? 1 : 0));
                                    } else {
                                        $ins->bind_param('iiis', $student_id, $su['subject_id'], $su['grade'], $resv);
                                    }
                                } else {
                                    $ins->bind_param('iii', $student_id, $su['subject_id'], $su['grade']);
                                }
                            } else {
                                if ($subject_result_col) {
                                    if ($subject_result_is_numeric) {
                                        $ins->bind_param('iii', $student_id, $su['grade'], ($resv === 'Passed' ? 1 : 0));
                                    } else {
                                        $ins->bind_param('iis', $student_id, $su['grade'], $resv);
                                    }
                                } else {
                                    $ins->bind_param('ii', $student_id, $su['grade']);
                                }
                            }
                            if (!$ins->execute()) {
                                throw new Exception('Failed to insert subject row: ' . $ins->error);
                            }
                        }
                        $ins->close();
                    }

                    // Commit transaction
                    $conn->commit();

                } catch (Exception $subEx) {
                    $conn->rollback();
                    throw $subEx;
                }

                // If we reach here, success
        echo json_encode([
            'success' => true,
            'message' => 'Record updated successfully',
            'updated_name' => $composed_name,
            'student_id' => $student_id
        ]);
                exit();
        
        $stmt->close();
        $check_stmt->close();
        
    } catch (Exception $e) {
        error_log("Update error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit();
    }
    
} else {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

$conn->close();
?>
