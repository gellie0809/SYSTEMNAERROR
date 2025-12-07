<?php
session_start();
header('Content-Type: application/json');

// Only allow College of Business Administration and Accountancy admin
if (!isset($_SESSION["users"]) || $_SESSION["users"] !== 'cbaa_admin@lspu.edu.ph') {
    echo json_encode(['error' => 'Unauthorized', 'total' => 0]);
    exit();
}

// Database connection
require_once 'db_config.php';
$conn = getDbConnection();

$stats = [
    'total' => 0,
    'passed' => 0,
    'failed' => 0,
    'conditional' => 0,
    'first_timer' => 0,
    'repeater' => 0,
    'first_timer_passed' => 0,
    'repeater_passed' => 0,
    'by_exam_type' => [],
    'by_date' => [],
    'by_year' => []
];

try {
    // Get overall statistics
    $query = "SELECT 
                result,
                exam_type,
                board_exam_type,
                board_exam_date,
                COUNT(*) as count
              FROM anonymous_board_passers 
              WHERE department='Business Administration and Accountancy' 
              AND (is_deleted IS NULL OR is_deleted = 0)
              GROUP BY result, exam_type, board_exam_type, board_exam_date";
    
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        throw new Exception('Query failed: ' . mysqli_error($conn));
    }
    
    while ($row = mysqli_fetch_assoc($result)) {
        $count = (int)$row['count'];
        $result_type = $row['result'];
        $take_attempts = $row['exam_type']; // First Timer or Repeater
        $board_exam_type = $row['board_exam_type'];
        $exam_date = $row['board_exam_date'];
        
        // Total count
        $stats['total'] += $count;
        
        // Result counts
        if ($result_type === 'Passed') {
            $stats['passed'] += $count;
        } elseif ($result_type === 'Failed') {
            $stats['failed'] += $count;
        } elseif ($result_type === 'Conditional') {
            $stats['conditional'] += $count;
        }
        
        // Take attempts counts
        if ($take_attempts === 'First Timer') {
            $stats['first_timer'] += $count;
            if ($result_type === 'Passed') {
                $stats['first_timer_passed'] += $count;
            }
        } elseif ($take_attempts === 'Repeater') {
            $stats['repeater'] += $count;
            if ($result_type === 'Passed') {
                $stats['repeater_passed'] += $count;
            }
        }
        
        // By exam type
        if (!isset($stats['by_exam_type'][$board_exam_type])) {
            $stats['by_exam_type'][$board_exam_type] = [
                'total' => 0,
                'passed' => 0,
                'failed' => 0,
                'conditional' => 0
            ];
        }
        $stats['by_exam_type'][$board_exam_type]['total'] += $count;
        if ($result_type === 'Passed') {
            $stats['by_exam_type'][$board_exam_type]['passed'] += $count;
        } elseif ($result_type === 'Failed') {
            $stats['by_exam_type'][$board_exam_type]['failed'] += $count;
        } elseif ($result_type === 'Conditional') {
            $stats['by_exam_type'][$board_exam_type]['conditional'] += $count;
        }
        
        // By date
        if (!isset($stats['by_date'][$exam_date])) {
            $stats['by_date'][$exam_date] = [
                'total' => 0,
                'passed' => 0,
                'failed' => 0,
                'conditional' => 0
            ];
        }
        $stats['by_date'][$exam_date]['total'] += $count;
        if ($result_type === 'Passed') {
            $stats['by_date'][$exam_date]['passed'] += $count;
        } elseif ($result_type === 'Failed') {
            $stats['by_date'][$exam_date]['failed'] += $count;
        } elseif ($result_type === 'Conditional') {
            $stats['by_date'][$exam_date]['conditional'] += $count;
        }
        
        // By year with board exam types
        $year = substr($exam_date, 0, 4); // Extract year from date
        if (!isset($stats['by_year'][$year])) {
            $stats['by_year'][$year] = [
                'total' => 0,
                'passed' => 0,
                'failed' => 0,
                'conditional' => 0,
                'first_timer' => 0,
                'repeater' => 0,
                'board_exams' => []
            ];
        }
        
        // Initialize board exam type if not exists
        if (!isset($stats['by_year'][$year]['board_exams'][$board_exam_type])) {
            $stats['by_year'][$year]['board_exams'][$board_exam_type] = [
                'total' => 0,
                'passed' => 0,
                'failed' => 0,
                'conditional' => 0
            ];
        }
        
        // Update year totals
        $stats['by_year'][$year]['total'] += $count;
        
        // Track take attempts
        if ($take_attempts === 'First Timer') {
            $stats['by_year'][$year]['first_timer'] += $count;
        } elseif ($take_attempts === 'Repeater') {
            $stats['by_year'][$year]['repeater'] += $count;
        }
        
        if ($result_type === 'Passed') {
            $stats['by_year'][$year]['passed'] += $count;
            $stats['by_year'][$year]['board_exams'][$board_exam_type]['passed'] += $count;
        } elseif ($result_type === 'Failed') {
            $stats['by_year'][$year]['failed'] += $count;
            $stats['by_year'][$year]['board_exams'][$board_exam_type]['failed'] += $count;
        } elseif ($result_type === 'Conditional') {
            $stats['by_year'][$year]['conditional'] += $count;
            $stats['by_year'][$year]['board_exams'][$board_exam_type]['conditional'] += $count;
        }
        $stats['by_year'][$year]['board_exams'][$board_exam_type]['total'] += $count;
    }
    
    mysqli_close($conn);
    echo json_encode($stats);

} catch (Exception $e) {
    echo json_encode([
        'error' => $e->getMessage(),
        'total' => 0
    ]);
}
?>

