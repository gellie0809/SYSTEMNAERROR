<?php
session_start();
// Only allow College of Engineering admin
if (!isset($_SESSION["users"]) || $_SESSION["users"] !== 'eng_admin@lspu.edu.ph') {
    header("Location: mainpage.php");
    exit();
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "project_db";
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) { 
    die("Connection failed: " . $conn->connect_error); 
}

// Ensure table exists - auto-create if missing
function ensureDatabaseExists($conn) {
    $tableCheck = $conn->query("SHOW TABLES LIKE 'board_passers'");
    if (!$tableCheck || $tableCheck->num_rows == 0) {
        $createTable = "
        CREATE TABLE board_passers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            first_name VARCHAR(100) DEFAULT NULL,
            last_name VARCHAR(100) DEFAULT NULL,
            middle_name VARCHAR(100) DEFAULT NULL,
            sex VARCHAR(10) NOT NULL,
            course VARCHAR(255) NOT NULL,
            year_graduated INT NOT NULL,
            board_exam_date DATE NOT NULL,
            result VARCHAR(20) NOT NULL DEFAULT 'PASSED',
            department VARCHAR(100) NOT NULL DEFAULT 'Engineering',
            exam_type VARCHAR(255) DEFAULT NULL,
            board_exam_type VARCHAR(255) DEFAULT 'Board Exam',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        return $conn->query($createTable);
    }
    return true;
}

// Initialize database
ensureDatabaseExists($conn);

$message = '';
$error = '';
$stats = null;
$errorList = [];

// Function to process CSV import
function processCSVImport($filePath, $conn) {
    $stats = ['total' => 0, 'imported' => 0, 'errors' => 0, 'duplicates' => 0];
    $errors = [];
    $processedRecords = []; // Track records within this CSV to detect internal duplicates
    
    // Start transaction for data integrity
    $conn->autocommit(false);
    
    try {
        if (($handle = fopen($filePath, "r")) !== FALSE) {
            $header = fgetcsv($handle); // Skip header row
            $rowNumber = 1;
        
        while (($data = fgetcsv($handle)) !== FALSE) {
            $rowNumber++;
            $stats['total']++;
            
            // Validate required fields
            if (count($data) < 8) {
                $errors[] = "Row $rowNumber: Insufficient data columns";
                $stats['errors']++;
                continue;
            }
            
            $firstName = trim($data[0]);
            $lastName = trim($data[1]);
            $middleName = trim($data[2]);
            $course = trim($data[3]);
            $boardExamDate = trim($data[4]);
            $sex = trim($data[5]);
            $school = trim($data[6]);
            $examType = trim($data[7]);
            
            // Validate required fields
            if (empty($firstName) || empty($lastName) || empty($course) || empty($boardExamDate)) {
                $errors[] = "Row $rowNumber: Missing required fields (First Name, Last Name, Course, or Board Exam Date)";
                $stats['errors']++;
                continue;
            }
            
            // Validate date format and range
            $dateObj = DateTime::createFromFormat('Y-m-d', $boardExamDate);
            if (!$dateObj) {
                // Try alternative date formats
                $dateObj = DateTime::createFromFormat('m/d/Y', $boardExamDate);
                if (!$dateObj) {
                    $dateObj = DateTime::createFromFormat('d-m-Y', $boardExamDate);
                }
                if (!$dateObj) {
                    $dateObj = DateTime::createFromFormat('Y/m/d', $boardExamDate);
                }
            }
            
            if (!$dateObj) {
                $errors[] = "Row $rowNumber: Invalid date format '$boardExamDate'. Use YYYY-MM-DD format";
                $stats['errors']++;
                continue;
            }
            
            // Convert to standard format
            $boardExamDate = $dateObj->format('Y-m-d');
            
            $year = (int)$dateObj->format('Y');
            if ($year < 2019 || $year > 2024) {
                $errors[] = "Row $rowNumber: Board exam date must be between 2019-2024 (got $year)";
                $stats['errors']++;
                continue;
            }
            
            // Create full name
            $fullName = $firstName . ' ' . $middleName . ' ' . $lastName;
            $fullName = trim(str_replace('  ', ' ', $fullName)); // Remove double spaces
            
            // Create a unique key for this record
            $recordKey = $fullName . '|' . $course . '|' . $boardExamDate;
            
            // Check for duplicates within this CSV file first
            if (isset($processedRecords[$recordKey])) {
                $errors[] = "Row $rowNumber: Duplicate within CSV file - $fullName (Course: $course, Date: $boardExamDate)";
                $stats['duplicates']++;
                continue;
            }
            
            // Check for duplicates in database
            $checkStmt = $conn->prepare("SELECT id FROM board_passers WHERE name = ? AND course = ? AND board_exam_date = ?");
            $checkStmt->bind_param("sss", $fullName, $course, $boardExamDate);
            $checkStmt->execute();
            $result = $checkStmt->get_result();
            
            if ($result->num_rows > 0) {
                $errors[] = "Row $rowNumber: Already exists in database - $fullName (Course: $course, Date: $boardExamDate)";
                $stats['duplicates']++;
                $checkStmt->close();
                continue;
            }
            $checkStmt->close();
            
            // Mark this record as processed
            $processedRecords[$recordKey] = true;
            
            // Insert into database
            $insertStmt = $conn->prepare("INSERT INTO board_passers (name, sex, course, year_graduated, board_exam_date, result, department, exam_type, board_exam_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $yearGraduated = $year; // Assuming board exam year as graduation year
            $resultStatus = 'PASSED'; // Assuming all imported records are passers
            $department = 'Engineering'; // Fixed to match dashboard query
            $boardExamType = 'Board Exam'; // Default value
            
            $insertStmt->bind_param("sssisssss", $fullName, $sex, $course, $yearGraduated, $boardExamDate, $resultStatus, $department, $examType, $boardExamType);
            
            if ($insertStmt->execute()) {
                $stats['imported']++;
                $insertId = $conn->insert_id;
                error_log("SUCCESS - Imported: $fullName (ID: $insertId)");
                
                // Verify the record was actually inserted
                $verifyStmt = $conn->prepare("SELECT id FROM board_passers WHERE id = ?");
                $verifyStmt->bind_param("i", $insertId);
                $verifyStmt->execute();
                $verifyResult = $verifyStmt->get_result();
                if ($verifyResult->num_rows === 0) {
                    error_log("WARNING - Record not found immediately after insert!");
                }
                $verifyStmt->close();
                
            } else {
                $errors[] = "Row $rowNumber: Database error - " . $insertStmt->error;
                $stats['errors']++;
                error_log("ERROR - Failed to import $fullName: " . $insertStmt->error);
            }
            $insertStmt->close();
        }
        fclose($handle);
        
        // Commit transaction
        $conn->commit();
        error_log("TRANSACTION COMMITTED - Imported " . $stats['imported'] . " records");
        
        } else {
            $errors[] = "Could not open CSV file for reading";
        }
        
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        error_log("TRANSACTION ROLLED BACK - Error: " . $e->getMessage());
        $errors[] = "Transaction failed: " . $e->getMessage();
    }
    
    // Re-enable autocommit
    $conn->autocommit(true);
    
    return ['stats' => $stats, 'errors' => $errors];
}

// Handle file upload
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_FILES['csvFile'])) {
    $file = $_FILES['csvFile'];
    
    if ($file['error'] === 0) {
        $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if ($fileExt === 'csv') {
            if ($file['size'] < 10000000) { // 10MB limit
                $uploadDir = 'uploads/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                $newFileName = 'import_' . time() . '.csv';
                $fileDestination = $uploadDir . $newFileName;
                
                if (move_uploaded_file($file['tmp_name'], $fileDestination)) {
                    $result = processCSVImport($fileDestination, $conn);
                    $stats = $result['stats'];
                    $errorList = $result['errors'];
                    
                    // Final verification - count actual records in database
                    $verificationQuery = $conn->query("SELECT COUNT(*) as count FROM board_passers WHERE department = 'Engineering'");
                    $actualCount = $verificationQuery ? $verificationQuery->fetch_assoc()['count'] : 0;
                    
                    error_log("FINAL VERIFICATION - Database contains $actualCount Engineering records");
                    
                    if ($stats['imported'] > 0) {
                        $message = "Successfully imported {$stats['imported']} records out of {$stats['total']} total records.";
                        $message .= " Database verification: $actualCount total Engineering records.";
                    } else {
                        $error = "No records were imported. Please check your CSV file format.";
                    }
                    
                    // Clean up uploaded file
                    unlink($fileDestination);
                } else {
                    $error = "Failed to upload file.";
                }
            } else {
                $error = "File size too large. Maximum 10MB allowed.";
            }
        } else {
            $error = "Invalid file type. Please upload a CSV file.";
        }
    } else {
        $error = "File upload error occurred.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Import Data - Engineering Dashboard</title>
  <link rel="stylesheet" href="style.css" />
  <link rel="stylesheet" href="css/sidebar.css" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    body {
      background: linear-gradient(135deg, #ecfeff 0%, #cffafe 100%);
      margin: 0;
      font-family: 'Inter', sans-serif;
      min-height: 100vh;
      position: relative;
      overflow-x: hidden;
    }
    
    body::before {
      content: '';
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: 
        radial-gradient(circle at 20% 20%, rgba(6, 182, 212, 0.1) 0%, transparent 50%),
        radial-gradient(circle at 80% 60%, rgba(8, 145, 178, 0.08) 0%, transparent 50%),
        radial-gradient(circle at 40% 80%, rgba(165, 243, 252, 0.1) 0%, transparent 50%);
      pointer-events: none;
      z-index: 0;
    }
    /* Sidebar styling moved to css/sidebar.css (shared) */
    
    .topbar {
      position: fixed;
      top: 0;
      left: 260px;
      right: 0;
      background: linear-gradient(135deg, #06b6d4 0%, #0593b4 100%);
      height: 70px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0 40px;
      box-shadow: 0 4px 20px rgba(22, 41, 56, 0.1);
      z-index: 50;
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .dashboard-title {
      font-size: 1.4rem;
      color: #fff;
      font-weight: 700;
      letter-spacing: 1px;
      margin: 0;
    }
    .logout-btn {
      background: rgba(255, 255, 255, 0.1);
      color: #fff;
      border: 2px solid rgba(255, 255, 255, 0.3);
      border-radius: 12px;
      padding: 12px 24px;
      font-size: 0.95rem;
      font-weight: 600;
      font-family: 'Inter', sans-serif;
      cursor: pointer;
      transition: all 0.3s ease;
      text-decoration: none;
      display: flex;
      align-items: center;
      gap: 8px;
      backdrop-filter: blur(10px);
    }
    .logout-btn:hover {
      background: rgba(255, 255, 255, 0.2);
      border-color: rgba(255, 255, 255, 0.5);
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
    }
    .main-content {
      margin-left: 260px;
      margin-top: 70px;
      padding: 50px 60px;
      min-height: calc(100vh - 70px);
      position: relative;
      z-index: 2;
    }
    
    .import-container {
      max-width: 1200px;
      margin: 0 auto;
      animation: containerSlideIn 0.8s cubic-bezier(0.25, 0.8, 0.25, 1);
    }
    
    @keyframes containerSlideIn {
      0% {
        opacity: 0;
        transform: translateY(40px) scale(0.95);
      }
      100% {
        opacity: 1;
        transform: translateY(0) scale(1);
      }
    }
    
    .page-header {
      text-align: center;
      margin-bottom: 50px;
    }
    
    .page-title {
      font-size: 2.5rem;
      font-weight: 800;
      background: linear-gradient(135deg, #2c5aa0 0%, #3182ce 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      margin: 0 0 16px 0;
      letter-spacing: 1px;
    }
    
    .page-subtitle {
      font-size: 1.2rem;
      color: #6b7280;
      margin: 0;
      font-weight: 500;
    }
    
    .import-cards {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 40px;
      margin-bottom: 40px;
    }
    
    .import-card {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      border-radius: 28px;
      padding: 0;
      box-shadow: 
        0 32px 64px rgba(44, 90, 160, 0.15),
        0 0 0 1px rgba(255, 255, 255, 0.2),
        inset 0 1px 0 rgba(255, 255, 255, 0.8);
      border: 2px solid rgba(44, 90, 160, 0.1);
      overflow: hidden;
      position: relative;
      transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
      animation: cardSlideIn 0.6s cubic-bezier(0.25, 0.8, 0.25, 1);
    }
    
    @keyframes cardSlideIn {
      0% {
        opacity: 0;
        transform: translateY(30px) scale(0.95);
      }
      100% {
        opacity: 1;
        transform: translateY(0) scale(1);
      }
    }
    
    .import-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: linear-gradient(135deg, rgba(44, 90, 160, 0.02) 0%, rgba(58, 141, 222, 0.03) 100%);
      pointer-events: none;
      z-index: 1;
    }
    
    .import-card:hover {
      transform: translateY(-8px) scale(1.02);
      box-shadow: 
        0 40px 80px rgba(44, 90, 160, 0.2),
        0 0 0 1px rgba(255, 255, 255, 0.3),
        inset 0 1px 0 rgba(255, 255, 255, 0.9);
    }
    
    .card-header {
      padding: 40px 50px;
      background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);
      color: white;
      position: relative;
      overflow: hidden;
      z-index: 2;
    }
    
    .card-header::before {
      content: '';
      position: absolute;
      top: -50%;
      right: -50%;
      width: 100%;
      height: 200%;
      background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.15), transparent);
      animation: shimmer 4s infinite;
      z-index: 1;
    }
    
    .header-content {
      display: flex;
      flex-direction: column;
      align-items: center;
      text-align: center;
      gap: 20px;
      position: relative;
      z-index: 2;
    }
    
    .header-icon {
      width: 80px;
      height: 80px;
      background: rgba(255, 255, 255, 0.25);
      border-radius: 20px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 2.5rem;
      flex-shrink: 0;
      backdrop-filter: blur(10px);
      transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
      animation: iconFloat 3s ease-in-out infinite;
      box-shadow: 
        0 15px 35px rgba(6, 182, 212, 0.35),
        inset 0 1px 0 rgba(255, 255, 255, 0.4);
      border: 2px solid rgba(255, 255, 255, 0.25);
    }
    
    .header-title {
      font-size: 2rem;
      font-weight: 800;
      margin: 0 0 8px 0;
      text-align: center;
    }
    
    .header-subtitle {
      font-size: 1.1rem;
      font-weight: 400;
      margin: 0;
      opacity: 0.95;
      text-align: center;
    }
    
    @keyframes shimmer {
      0% { transform: translateX(-100%) translateY(-100%) rotate(45deg); opacity: 0; }
      50% { opacity: 1; }
      100% { transform: translateX(100%) translateY(100%) rotate(45deg); opacity: 0; }
    }
    
    .card-icon {
      width: 80px;
      height: 80px;
      background: rgba(255, 255, 255, 0.25);
      border-radius: 20px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 24px;
      font-size: 2.2rem;
      position: relative;
      z-index: 2;
      backdrop-filter: blur(10px);
      transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
      animation: iconFloat 3s ease-in-out infinite;
      box-shadow: 
        0 15px 35px rgba(6, 182, 212, 0.35),
        inset 0 1px 0 rgba(255, 255, 255, 0.4);
      border: 2px solid rgba(255, 255, 255, 0.25);
    }
    
    @keyframes iconFloat {
      0%, 100% { transform: translateY(0px); }
      50% { transform: translateY(-8px); }
    }
    
    .card-icon:hover {
      transform: scale(1.1) rotate(5deg);
      box-shadow: 
        0 20px 40px rgba(44, 90, 160, 0.4),
        inset 0 1px 0 rgba(255, 255, 255, 0.5);
    }
    
    .card-title {
      font-size: 1.8rem;
      font-weight: 800;
      margin: 0;
      text-align: center;
      position: relative;
      z-index: 2;
    }
    
    .card-content {
      padding: 50px;
      position: relative;
      z-index: 2;
      background: linear-gradient(145deg, rgba(255, 255, 255, 0.8) 0%, rgba(248, 250, 252, 0.6) 100%);
    }
    
    /* File Upload Styles */
    .upload-area {
      border: 3px dashed rgba(6, 182, 212, 0.4);
      border-radius: 20px;
      padding: 50px;
      text-align: center;
      transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
      background: linear-gradient(145deg, rgba(255, 255, 255, 0.95) 0%, rgba(240, 253, 250, 0.9) 100%);
      cursor: pointer;
      position: relative;
      overflow: hidden;
      box-shadow: 0 8px 30px rgba(6, 182, 212, 0.1);
    }
    
    .upload-area::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: linear-gradient(135deg, rgba(6, 182, 212, 0.05) 0%, rgba(14, 116, 144, 0.03) 100%);
      opacity: 0;
      transition: all 0.4s ease;
    }
    
    .upload-area:hover {
      border-color: #06b6d4;
      background: linear-gradient(145deg, rgba(255, 255, 255, 1) 0%, rgba(236, 254, 255, 0.95) 100%);
      transform: translateY(-4px) scale(1.01);
      box-shadow: 0 15px 45px rgba(6, 182, 212, 0.2);
    }
    
    .upload-area:hover::before {
      opacity: 1;
    }
    
    .upload-area.dragover {
      border-color: #0891b2;
      background: linear-gradient(145deg, rgba(236, 254, 255, 0.98) 0%, rgba(207, 250, 254, 0.95) 100%);
      transform: scale(1.03);
      box-shadow: 0 20px 60px rgba(6, 182, 212, 0.3);
    }
    
    .upload-icon {
      font-size: 4rem;
      color: #06b6d4;
      margin-bottom: 24px;
      animation: uploadFloat 3s ease-in-out infinite;
      filter: drop-shadow(0 4px 12px rgba(6, 182, 212, 0.3));
    }
    
    @keyframes uploadFloat {
      0%, 100% { transform: translateY(0px) scale(1); }
      50% { transform: translateY(-12px) scale(1.05); }
    }
    
    .upload-text {
      font-size: 1.3rem;
      color: #0e7490;
      font-weight: 700;
      margin-bottom: 10px;
      letter-spacing: 0.3px;
    }
    
    .upload-hint {
      font-size: 1rem;
      color: #64748b;
      margin-bottom: 24px;
      font-weight: 500;
    }
    
    .file-input {
      display: none;
    }
    
    .upload-btn {
      background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);
      color: white;
      border: none;
      border-radius: 14px;
      padding: 14px 32px;
      font-size: 1.05rem;
      font-weight: 700;
      cursor: pointer;
      transition: all 0.3s ease;
      box-shadow: 0 6px 20px rgba(6, 182, 212, 0.35);
      letter-spacing: 0.3px;
    }
    
    .upload-btn:hover {
      background: linear-gradient(135deg, #0891b2 0%, #0e7490 100%);
      transform: translateY(-3px);
      box-shadow: 0 12px 30px rgba(6, 182, 212, 0.45);
    }
    
    .upload-btn:active {
      transform: translateY(-1px);
    }
    
    /* File Info Display */
    .file-info {
      background: linear-gradient(145deg, rgba(16, 185, 129, 0.1) 0%, rgba(5, 150, 105, 0.05) 100%);
      border: 2px solid rgba(16, 185, 129, 0.2);
      border-radius: 16px;
      padding: 20px;
      margin-top: 20px;
      display: none;
    }
    
    .file-name {
      font-weight: 600;
      color: #059669;
      margin-bottom: 8px;
    }
    
    .file-size {
      font-size: 0.9rem;
      color: #6b7280;
    }
    
    /* Alert Styles */
    .alert {
      padding: 20px 24px;
      border-radius: 16px;
      margin-bottom: 24px;
      display: flex;
      align-items: center;
      gap: 12px;
      font-weight: 600;
      backdrop-filter: blur(10px);
      -webkit-backdrop-filter: blur(10px);
      animation: alertSlideIn 0.4s ease;
    }
    
    @keyframes alertSlideIn {
      0% { opacity: 0; transform: translateY(-20px); }
      100% { opacity: 1; transform: translateY(0); }
    }
    
    .alert-success {
      background: linear-gradient(145deg, rgba(16, 185, 129, 0.15) 0%, rgba(5, 150, 105, 0.1) 100%);
      border: 2px solid rgba(16, 185, 129, 0.3);
      color: #059669;
      box-shadow: 0 8px 25px rgba(16, 185, 129, 0.2);
    }
    
    .alert-error {
      background: linear-gradient(145deg, rgba(239, 68, 68, 0.15) 0%, rgba(220, 38, 38, 0.1) 100%);
      border: 2px solid rgba(239, 68, 68, 0.3);
      color: #dc2626;
      box-shadow: 0 8px 25px rgba(239, 68, 68, 0.2);
    }
    
    /* Import Stats */
    .import-stats {
      background: linear-gradient(145deg, rgba(255, 255, 255, 0.9) 0%, rgba(236, 254, 255, 0.8) 100%);
      border-radius: 20px;
      padding: 30px;
      margin-top: 30px;
      border: 2px solid rgba(6, 182, 212, 0.2);
      box-shadow: 0 8px 25px rgba(6, 182, 212, 0.15);
      backdrop-filter: blur(10px);
      -webkit-backdrop-filter: blur(10px);
    }
    
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 20px;
      margin-bottom: 20px;
    }
    
    .stat-item {
      text-align: center;
      padding: 20px;
      background: rgba(255, 255, 255, 0.8);
      border-radius: 12px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    }
    
    .stat-value {
      font-size: 2rem;
      font-weight: 800;
      color: #0891b2;
      margin-bottom: 8px;
    }
    
    .stat-label {
      font-size: 0.9rem;
      color: #6b7280;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    
    .error-list {
      background: rgba(239, 68, 68, 0.05);
      border-radius: 12px;
      padding: 20px;
      max-height: 200px;
      overflow-y: auto;
    }
    
    .error-item {
      padding: 8px 0;
      border-bottom: 1px solid rgba(239, 68, 68, 0.1);
      color: #dc2626;
      font-size: 0.9rem;
    }
    
    .error-item:last-child {
      border-bottom: none;
    }
    
    /* Template Download */
    .template-section {
      background: linear-gradient(145deg, rgba(255, 255, 255, 0.9) 0%, rgba(254, 249, 195, 0.3) 100%);
      border-radius: 20px;
      padding: 30px;
      text-align: center;
      border: 2px solid rgba(245, 158, 11, 0.2);
    }
    /* Template Section */
    .template-section {
      background: linear-gradient(135deg, #fff7ed 0%, #fed7aa 100%);
      border-radius: 20px;
      padding: 40px;
      margin-bottom: 35px;
      text-align: center;
      border: 2px solid #fdba74;
      box-shadow: 0 10px 35px rgba(249, 115, 22, 0.15);
      animation: fadeInScale 0.5s ease-out;
      position: relative;
      overflow: hidden;
    }
    
    .template-section::before {
      content: '';
      position: absolute;
      top: -50%;
      right: -50%;
      width: 200%;
      height: 200%;
      background: radial-gradient(circle, rgba(251, 191, 36, 0.1) 0%, transparent 70%);
      animation: rotate 20s linear infinite;
    }
    
    @keyframes rotate {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
    
    @keyframes fadeInScale {
      0% { opacity: 0; transform: scale(0.95); }
      100% { opacity: 1; transform: scale(1); }
    }
    
    .template-icon {
      font-size: 3.5rem;
      color: #ea580c;
      margin-bottom: 24px;
      animation: bounce 2s ease-in-out infinite;
      filter: drop-shadow(0 4px 12px rgba(234, 88, 12, 0.3));
      position: relative;
      z-index: 1;
    }
    
    @keyframes bounce {
      0%, 100% { transform: translateY(0px); }
      50% { transform: translateY(-10px); }
    }
    
    .template-title {
      font-size: 1.6rem;
      font-weight: 800;
      color: #9a3412;
      margin-bottom: 14px;
      position: relative;
      z-index: 1;
      letter-spacing: -0.3px;
    }
    
    .template-description {
      font-size: 1.05rem;
      color: #78350f;
      margin-bottom: 28px;
      line-height: 1.7;
      font-weight: 500;
      position: relative;
      z-index: 1;
    }
    
    .template-btn {
      background: linear-gradient(135deg, #ea580c 0%, #f97316 100%);
      color: white;
      border: none;
      border-radius: 14px;
      padding: 16px 36px;
      font-size: 1.1rem;
      font-weight: 700;
      cursor: pointer;
      transition: all 0.3s ease;
      box-shadow: 0 6px 20px rgba(234, 88, 12, 0.4);
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 10px;
      position: relative;
      z-index: 1;
      letter-spacing: 0.3px;
    }
    
    .template-btn:hover {
      background: linear-gradient(135deg, #c2410c 0%, #ea580c 100%);
      transform: translateY(-3px) scale(1.02);
      box-shadow: 0 12px 35px rgba(234, 88, 12, 0.5);
    }
    
    .template-btn:active {
      transform: translateY(-1px) scale(1.01);
    }
    .card {
      background: #ffffff;
      border-radius: 24px;
      box-shadow: 0 20px 60px rgba(22, 41, 56, 0.1);
      padding: 48px;
      margin: 0;
      border: 1px solid #e2e8f0;
      text-align: center;
    }
    .card h2 {
      font-size: 2.2rem;
      font-weight: 700;
      margin-bottom: 40px;
      color: #fff;
      background: linear-gradient(135deg, #2c5aa0 0%, #3182ce 100%);
      padding: 24px 40px;
      border-radius: 16px;
      box-shadow: 0 12px 40px rgba(44, 90, 160, 0.3);
      text-align: center;
      letter-spacing: 1.2px;
      text-transform: uppercase;
      font-family: 'Inter', sans-serif;
      margin: 0 0 40px 0;
    }
    .coming-soon {
      font-size: 1.5rem;
      color: #4a5568;
      margin-bottom: 20px;
    }
    .feature-icon {
      font-size: 4rem;
      color: #3a8dde;
      margin-bottom: 30px;
    }
    @media (max-width: 1200px) {
      .main-content { padding: 24px; }
      .card { padding: 32px 24px; }
    }
    /* Responsive sidebar behavior moved to css/sidebar.css */
    
    /* Import Confirmation Modal Styles */
    #importConfirmModal.modal {
      display: none !important;
      position: fixed !important;
      z-index: 100000 !important;
      left: 0 !important;
      top: 0 !important;
      width: 100% !important;
      height: 100% !important;
      background: linear-gradient(135deg, rgba(44, 90, 160, 0.95) 0%, rgba(49, 130, 206, 0.9) 100%) !important;
      backdrop-filter: blur(15px) !important;
      -webkit-backdrop-filter: blur(15px) !important;
      transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1) !important;
      opacity: 0 !important;
      animation: none !important;
    }
    
    #importConfirmModal.modal[style*="flex"] {
      display: flex !important;
      align-items: center !important;
      justify-content: center !important;
      opacity: 1 !important;
      animation: modalFadeIn 0.4s ease !important;
    }
    
    #importConfirmModal .modal-content {
      background: linear-gradient(145deg, rgba(255, 255, 255, 0.98) 0%, rgba(248, 250, 252, 0.95) 100%) !important;
      padding: 0 !important;
      border: none !important;
      border-radius: 28px !important;
      width: 90% !important;
      max-width: 520px !important;
      box-shadow: 
        0 25px 50px rgba(6, 182, 212, 0.4),
        0 15px 35px rgba(0, 0, 0, 0.1),
        inset 0 1px 0 rgba(255, 255, 255, 0.9) !important;
      backdrop-filter: blur(20px) !important;
      -webkit-backdrop-filter: blur(20px) !important;
      transform: scale(0.7) translateY(50px) !important;
      animation: slideInImport 0.5s cubic-bezier(0.34, 1.56, 0.64, 1) !important;
      border: 3px solid rgba(6, 182, 212, 0.2) !important;
      position: relative !important;
      overflow: hidden !important;
    }
    
    #importConfirmModal .modal-content::before {
      content: '' !important;
      position: absolute !important;
      top: -2px !important;
      left: -2px !important;
      right: -2px !important;
      bottom: -2px !important;
      background: linear-gradient(135deg, #06b6d4 0%, #0891b2 25%, #0e7490 50%, #0891b2 75%, #06b6d4 100%) !important;
      border-radius: 30px !important;
      z-index: -1 !important;
      opacity: 0.6 !important;
      animation: borderShimmer 3s ease-in-out infinite !important;
    }
    
    @keyframes slideInImport {
      0% { transform: scale(0.7) translateY(50px); opacity: 0; }
      100% { transform: scale(1) translateY(0); opacity: 1; }
    }
    
    @keyframes borderShimmer {
      0%, 100% { opacity: 0.6; }
      50% { opacity: 0.9; }
    }
    
    #importConfirmModal .modal-header {
      margin-bottom: 24px !important;
      background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%) !important;
      padding: 32px 28px !important;
      border-radius: 24px !important;
      border: 2px solid rgba(44, 90, 160, 0.15) !important;
      position: relative !important;
      overflow: hidden !important;
      box-shadow: 0 8px 25px rgba(44, 90, 160, 0.1) !important;
    }
    
    #importConfirmModal .modal-icon {
      width: 75px !important;
      height: 75px !important;
      background: linear-gradient(135deg, #2c5aa0 0%, #3182ce 100%) !important;
      border-radius: 20px !important;
      display: flex !important;
      align-items: center !important;
      justify-content: center !important;
      margin: 0 auto 20px !important;
      font-size: 2rem !important;
      color: white !important;
      position: relative !important;
      overflow: hidden !important;
      box-shadow: 
        0 15px 35px rgba(44, 90, 160, 0.4),
        inset 0 1px 0 rgba(255, 255, 255, 0.2) !important;
      animation: iconPulse 2s ease-in-out infinite !important;
    }
    
    @keyframes iconPulse {
      0%, 100% { transform: scale(1); box-shadow: 0 15px 35px rgba(44, 90, 160, 0.4); }
      50% { transform: scale(1.05); box-shadow: 0 20px 40px rgba(44, 90, 160, 0.5); }
    }
    
    .import-file-preview {
      background: linear-gradient(145deg, rgba(16, 185, 129, 0.1) 0%, rgba(5, 150, 105, 0.05) 100%) !important;
      border: 2px solid rgba(16, 185, 129, 0.2) !important;
      border-radius: 16px !important;
      padding: 20px !important;
      margin: 20px 32px !important;
      display: flex !important;
      align-items: center !important;
      gap: 16px !important;
    }
    
    .file-preview-icon {
      width: 50px !important;
      height: 50px !important;
      background: linear-gradient(135deg, #059669 0%, #10b981 100%) !important;
      border-radius: 12px !important;
      display: flex !important;
      align-items: center !important;
      justify-content: center !important;
      color: white !important;
      font-size: 1.5rem !important;
      box-shadow: 0 8px 16px rgba(5, 150, 105, 0.3) !important;
    }
    
    .file-preview-info {
      flex: 1 !important;
    }
    
    .file-preview-name {
      font-weight: 700 !important;
      color: #059669 !important;
      font-size: 1.1rem !important;
      margin-bottom: 4px !important;
    }
    
    .file-preview-size {
      font-size: 0.9rem !important;
      color: #6b7280 !important;
    }
    
    .import-confirm {
      background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%) !important;
      border: 2px solid rgba(6, 182, 212, 0.3) !important;
      color: white !important;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
    }
    
    .import-confirm:hover {
      background: linear-gradient(135deg, #0891b2 0%, #0e7490 100%) !important;
      border-color: rgba(8, 145, 178, 0.5) !important;
      transform: translateY(-2px) scale(1.02) !important;
      box-shadow: 0 12px 30px rgba(6, 182, 212, 0.45) !important;
    }
    
    .import-cancel {
      background: linear-gradient(135deg, rgba(107, 114, 128, 0.1) 0%, rgba(156, 163, 175, 0.05) 100%) !important;
      border: 2px solid rgba(107, 114, 128, 0.3) !important;
      color: #374151 !important;
    }
    
    .import-cancel:hover {
      background: linear-gradient(135deg, rgba(107, 114, 128, 0.15) 0%, rgba(156, 163, 175, 0.1) 100%) !important;
      border-color: rgba(107, 114, 128, 0.4) !important;
      transform: translateY(-1px) !important;
    }
    
    #importConfirmModal .modal-btn {
      padding: 16px 32px !important;
      border-radius: 16px !important;
      font-size: 1.1rem !important;
      font-weight: 700 !important;
      font-family: 'Inter', sans-serif !important;
      cursor: pointer !important;
      transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1) !important;
      text-decoration: none !important;
      display: inline-flex !important;
      align-items: center !important;
      gap: 10px !important;
      position: relative !important;
      overflow: hidden !important;
      backdrop-filter: blur(10px) !important;
      -webkit-backdrop-filter: blur(10px) !important;
      min-width: 160px !important;
      justify-content: center !important;
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1) !important;
    }
    
    #importConfirmModal .modal-buttons {
      display: flex !important;
      gap: 16px !important;
      justify-content: center !important;
      margin-top: 32px !important;
      padding: 0 32px 32px !important;
    }
    
    #importConfirmModal .modal-title {
      font-size: 1.9rem !important;
      font-weight: 800 !important;
      color: #1f2937 !important;
      margin-bottom: 8px !important;
      font-family: 'Inter', sans-serif !important;
      text-align: center !important;
    }
    
    #importConfirmModal .modal-subtitle {
      font-size: 1.1rem !important;
      color: #6b7280 !important;
      margin: 0 !important;
      font-weight: 500 !important;
      text-align: center !important;
    }
    
    #importConfirmModal .modal-text {
      color: #4b5563 !important;
      font-size: 1rem !important;
      line-height: 1.6 !important;
      margin: 20px 32px !important;
      text-align: center !important;
      background: rgba(49, 130, 206, 0.05) !important;
      padding: 16px !important;
      border-radius: 12px !important;
      border: 1px solid rgba(49, 130, 206, 0.1) !important;
    }

    /* Logout Modal Styles - Beautiful Blue Theme Design */
    #logoutModal.modal {
      position: fixed !important;
      top: 0 !important;
      left: 0 !important;
      width: 100vw !important;
      height: 100vh !important;
      background: rgba(15, 23, 42, 0.8) !important;
      backdrop-filter: blur(16px) !important;
      -webkit-backdrop-filter: blur(16px) !important;
      z-index: 9998 !important;
      display: none !important;
      justify-content: center !important;
      align-items: center !important;
      animation: fadeInOverlay 0.4s cubic-bezier(0.25, 0.8, 0.25, 1) !important;
    }
    
    #logoutModal.modal[style*="flex"] {
      display: flex !important;
    }
    
    @keyframes fadeInOverlay {
      from { 
        opacity: 0;
        backdrop-filter: blur(0px);
      }
      to { 
        opacity: 1;
        backdrop-filter: blur(16px);
      }
    }
    
    @keyframes slideInLogout {
      from { 
        opacity: 0;
        transform: translateY(-40px) scale(0.9);
        filter: blur(4px);
      }
      to { 
        opacity: 1;
        transform: translateY(0) scale(1);
        filter: blur(0px);
      }
    }
    
    #logoutModal .modal-content {
      background: rgba(255, 255, 255, 0.98) !important;
      backdrop-filter: blur(20px) !important;
      -webkit-backdrop-filter: blur(20px) !important;
      padding: 48px 44px !important;
      border-radius: 28px !important;
      box-shadow: 
        0 32px 64px -12px rgba(6, 182, 212, 0.3),
        inset 0 1px 0 rgba(255, 255, 255, 0.9) !important;
      max-width: 480px !important;
      width: 92% !important;
      text-align: center !important;
      animation: slideInLogout 0.5s cubic-bezier(0.34, 1.56, 0.64, 1) !important;
      border: none !important;
      outline: none !important;
      position: relative !important;
      overflow: visible !important;
    }
    
    #logoutModal .modal-content::before {
      content: '' !important;
      position: absolute !important;
      top: -2px !important;
      left: -2px !important;
      right: -2px !important;
      bottom: -2px !important;
      background: linear-gradient(135deg, #06b6d4 0%, #0891b2 25%, #22d3ee 50%, #0891b2 75%, #06b6d4 100%) !important;
      border-radius: 30px !important;
      z-index: -1 !important;
      opacity: 0.8 !important;
      animation: borderGradientRotate 4s linear infinite !important;
    }
    
    @keyframes borderGradientRotate {
      0% { background-position: 0% 50%; }
      50% { background-position: 100% 50%; }
      100% { background-position: 0% 50%; }
    }
    
    #logoutModal .modal-header {
      margin-bottom: 32px !important;
      background: linear-gradient(135deg, #ecfeff 0%, #cffafe 100%) !important;
      padding: 32px 28px !important;
      border-radius: 20px !important;
      border: 2px solid #a5f3fc !important;
      position: relative !important;
      overflow: hidden !important;
      box-shadow: 0 8px 25px rgba(6, 182, 212, 0.2) !important;
    }
    
    #logoutModal .modal-header::before {
      content: '' !important;
      position: absolute !important;
      top: 0 !important;
      left: 0 !important;
      right: 0 !important;
      height: 4px !important;
      background: linear-gradient(90deg, #06b6d4 0%, #0891b2 50%, #22d3ee 100%) !important;
      border-radius: 20px 20px 0 0 !important;
    }
    
    #logoutModal .modal-header::after {
      content: '' !important;
      position: absolute !important;
      top: -50px !important;
      right: -50px !important;
      width: 120px !important;
      height: 120px !important;
      background: linear-gradient(135deg, rgba(6, 182, 212, 0.15) 0%, rgba(34, 211, 238, 0.08) 100%) !important;
      border-radius: 50% !important;
      z-index: 0 !important;
    }
    
    #logoutModal .modal-icon {
      width: 88px !important;
      height: 88px !important;
      background: linear-gradient(135deg, #06b6d4 0%, #0891b2 50%, #0e7490 100%) !important;
      border-radius: 50% !important;
      display: flex !important;
      align-items: center !important;
      justify-content: center !important;
      margin: 0 auto 24px !important;
      color: white !important;
      font-size: 2.2rem !important;
      box-shadow: 
        0 20px 40px rgba(6, 182, 212, 0.45),
        0 0 0 4px rgba(255, 255, 255, 0.8),
        0 0 0 6px rgba(6, 182, 212, 0.25) !important;
      position: relative !important;
      z-index: 1 !important;
      animation: iconPulse 3s ease-in-out infinite !important;
    }
    
    @keyframes iconPulse {
      0%, 100% {
        box-shadow: 
          0 20px 40px rgba(6, 182, 212, 0.45),
          0 0 0 4px rgba(255, 255, 255, 0.8),
          0 0 0 6px rgba(6, 182, 212, 0.25);
        transform: scale(1);
      }
      50% {
        box-shadow: 
          0 25px 50px rgba(6, 182, 212, 0.65),
          0 0 0 6px rgba(255, 255, 255, 0.9),
          0 0 0 8px rgba(6, 182, 212, 0.35);
        transform: scale(1.05);
      }
    }
    
    #logoutModal .modal-icon::before {
      content: '' !important;
      position: absolute !important;
      top: -4px !important;
      left: -4px !important;
      right: -4px !important;
      bottom: -4px !important;
      background: linear-gradient(135deg, #60a5fa, #3182ce, #1e40af, #2563eb) !important;
      border-radius: 50% !important;
      z-index: -1 !important;
      opacity: 0.6 !important;
      animation: rotateGradient 6s linear infinite !important;
    }
    
    @keyframes rotateGradient {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
    
    #logoutModal .modal-title {
      font-size: 1.75rem !important;
      font-weight: 800 !important;
      background: linear-gradient(135deg, #1e40af 0%, #2563eb 100%) !important;
      -webkit-background-clip: text !important;
      -webkit-text-fill-color: transparent !important;
      background-clip: text !important;
      margin: 0 0 12px 0 !important;
      letter-spacing: 0.5px !important;
      position: relative !important;
      z-index: 1 !important;
    }
    
    #logoutModal .modal-subtitle {
      font-size: 1.1rem !important;
      color: #2563eb !important;
      margin: 0 !important;
      line-height: 1.6 !important;
      font-weight: 500 !important;
      position: relative !important;
      z-index: 1 !important;
    }
    
    #logoutModal .modal-text {
      font-size: 1rem !important;
      color: #334155 !important;
      margin-bottom: 36px !important;
      line-height: 1.7 !important;
      padding: 24px !important;
      background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%) !important;
      border-radius: 16px !important;
      border: 1px solid #e2e8f0 !important;
      position: relative !important;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05) !important;
    }
    
    #logoutModal .modal-text::before {
      content: '⚠️' !important;
      position: absolute !important;
      top: -12px !important;
      left: 50% !important;
      transform: translateX(-50%) !important;
      background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%) !important;
      border-radius: 50% !important;
      width: 24px !important;
      height: 24px !important;
      display: flex !important;
      align-items: center !important;
      justify-content: center !important;
      font-size: 0.8rem !important;
      box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3) !important;
    }
    
    #logoutModal .modal-buttons {
      display: flex !important;
      gap: 20px !important;
      justify-content: center !important;
      align-items: center !important;
      flex-wrap: nowrap !important;
      border: none !important;
      border-top: none !important;
      border-bottom: none !important;
      border-left: none !important;
      border-right: none !important;
      outline: none !important;
      background: transparent !important;
      margin-top: 0 !important;
      padding: 0 !important;
      box-shadow: none !important;
    }
    
    #logoutModal .modal-btn {
      padding: 16px 32px !important;
      border: none !important;
      border-radius: 16px !important;
      font-size: 1rem !important;
      font-weight: 700 !important;
      font-family: 'Inter', sans-serif !important;
      cursor: pointer !important;
      transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1) !important;
      display: flex !important;
      align-items: center !important;
      gap: 10px !important;
      min-width: 150px !important;
      justify-content: center !important;
      text-transform: none !important;
      letter-spacing: 0.3px !important;
      visibility: visible !important;
      opacity: 1 !important;
      pointer-events: auto !important;
      position: relative !important;
      z-index: 10001 !important;
      overflow: hidden !important;
      outline: none !important;
      box-shadow: none !important;
      background-clip: padding-box !important;
    }
    
    #logoutModal .modal-btn::before {
      content: '' !important;
      position: absolute !important;
      top: 50% !important;
      left: 50% !important;
      width: 0 !important;
      height: 0 !important;
      background: rgba(255, 255, 255, 0.25) !important;
      border-radius: 50% !important;
      transform: translate(-50%, -50%) !important;
      transition: all 0.6s cubic-bezier(0.25, 0.8, 0.25, 1) !important;
      z-index: 0 !important;
      opacity: 0 !important;
    }
    
    #logoutModal .modal-btn:hover::before {
      width: 300px !important;
      height: 300px !important;
      opacity: 1 !important;
    }
    
    #logoutModal .modal-btn:active::before {
      width: 350px !important;
      height: 350px !important;
      opacity: 0.8 !important;
      transition: all 0.2s ease !important;
    }
    
    #logoutModal .modal-btn > * {
      position: relative !important;
      z-index: 1 !important;
      transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1) !important;
    }
    
    #logoutModal .modal-btn:hover > i {
      transform: scale(1.15) rotate(5deg) !important;
    }
    
    #logoutModal .modal-btn:active > i {
      transform: scale(0.9) rotate(-5deg) !important;
    }
    
    /* Interactive button content states */
    #logoutModal .modal-btn .btn-text {
      transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1) !important;
      opacity: 1 !important;
      transform: translateX(0) !important;
    }
    
    #logoutModal .modal-btn .btn-spinner {
      position: absolute !important;
      left: 50% !important;
      top: 50% !important;
      transform: translate(-50%, -50%) translateX(20px) !important;
      opacity: 0 !important;
      transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1) !important;
      font-size: 1rem !important;
      animation: spin 1s linear infinite !important;
    }
    
    #logoutModal .modal-btn .btn-check {
      position: absolute !important;
      left: 50% !important;
      top: 50% !important;
      transform: translate(-50%, -50%) translateX(20px) scale(0.8) !important;
      opacity: 0 !important;
      transition: all 0.5s cubic-bezier(0.34, 1.56, 0.64, 1) !important;
      font-size: 1.1rem !important;
      color: white !important;
    }
    
    @keyframes spin {
      0% { transform: translate(-50%, -50%) rotate(0deg); }
      100% { transform: translate(-50%, -50%) rotate(360deg); }
    }
    
    /* Pulse effect for logout button */
    #logoutModal .modal-btn.logout-confirm:focus {
      animation: logoutPulse 0.6s ease-in-out !important;
    }
    
    @keyframes logoutPulse {
      0% { 
        box-shadow: none;
        transform: scale(1);
      }
      50% { 
        box-shadow: none;
        transform: scale(1.02);
      }
      100% { 
        box-shadow: none;
        transform: scale(1);
      }
    }
    
    #logoutModal .modal-btn.logout-confirm {
      background: linear-gradient(135deg, #06b6d4 0%, #0891b2 50%, #0e7490 100%) !important;
      color: #ffffff !important;
      border: none !important;
      outline: none !important;
      box-shadow: none !important;
      position: relative !important;
      overflow: hidden !important;
    }
    
    #logoutModal .modal-btn.logout-confirm::after {
      content: '' !important;
      position: absolute !important;
      top: 50% !important;
      left: 50% !important;
      width: 0 !important;
      height: 0 !important;
      background: radial-gradient(circle, rgba(255, 255, 255, 0.4) 0%, rgba(255, 255, 255, 0.1) 70%, transparent 100%) !important;
      border-radius: 50% !important;
      transform: translate(-50%, -50%) !important;
      transition: all 0.8s cubic-bezier(0.25, 0.8, 0.25, 1) !important;
      z-index: 0 !important;
      opacity: 0 !important;
    }
    
    #logoutModal .modal-btn.logout-confirm:hover {
      background: linear-gradient(135deg, #0891b2 0%, #0e7490 50%, #155e75 100%) !important;
      transform: translateY(-3px) scale(1.05) !important;
      box-shadow: none !important;
    }
    
    #logoutModal .modal-btn.logout-confirm:hover::after {
      width: 120px !important;
      height: 120px !important;
      opacity: 1 !important;
    }
    
    #logoutModal .modal-btn.logout-confirm:active {
      transform: translateY(-1px) scale(1.02) !important;
      background: linear-gradient(135deg, #0e7490 0%, #155e75 50%, #0891b2 100%) !important;
    }
    
    #logoutModal .modal-btn.logout-confirm:active::after {
      width: 200px !important;
      height: 200px !important;
      opacity: 0.8 !important;
      transition: all 0.3s ease !important;
    }
    
    /* Loading state for logout button */
    #logoutModal .modal-btn.logout-confirm.loading {
      background: linear-gradient(135deg, #64748b 0%, #475569 50%, #374151 100%) !important;
      cursor: not-allowed !important;
      transform: translateY(0) scale(1) !important;
      pointer-events: none !important;
    }
    
    #logoutModal .modal-btn.logout-confirm.loading::after {
      display: none !important;
    }
    
    #logoutModal .modal-btn.logout-confirm.loading .btn-text {
      opacity: 0 !important;
      transform: translateX(-20px) !important;
    }
    
    #logoutModal .modal-btn.logout-confirm.loading .btn-spinner {
      opacity: 1 !important;
      transform: translateX(0) !important;
    }
    
    /* Success state for logout button */
    #logoutModal .modal-btn.logout-confirm.success {
      background: linear-gradient(135deg, #10b981 0%, #059669 50%, #047857 100%) !important;
      transform: translateY(-2px) scale(1.05) !important;
      box-shadow: 0 12px 30px rgba(16, 185, 129, 0.4) !important;
    }
    
    #logoutModal .modal-btn.logout-confirm.success .btn-text {
      opacity: 0 !important;
      transform: translateX(-20px) !important;
    }
    
    #logoutModal .modal-btn.logout-confirm.success .btn-check {
      opacity: 1 !important;
      transform: translateX(0) scale(1.2) !important;
    }
    
    #logoutModal .modal-btn.logout-cancel {
      background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%) !important;
      color: #64748b !important;
      border: none !important;
      outline: none !important;
      box-shadow: 
        0 4px 12px rgba(0, 0, 0, 0.08),
        0 0 0 0px transparent !important;
    }
    
    #logoutModal .modal-btn.logout-cancel:hover {
      background: linear-gradient(135deg, #e2e8f0 0%, #cbd5e0 100%) !important;
      color: #475569 !important;
      transform: translateY(-2px) scale(1.05) !important;
      box-shadow: 
        0 8px 20px rgba(0, 0, 0, 0.12),
        0 0 0 0px transparent !important;
    }

    /* Force logout buttons to show with highest specificity - Clean Design */
    #logoutModal #logoutConfirmYes,
    #logoutModal #logoutConfirmNo {
      display: flex !important;
      visibility: visible !important;
      opacity: 1 !important;
      position: relative !important;
      z-index: 10001 !important;
      min-height: 48px !important;
      min-width: 150px !important;
      border: none !important;
      outline: none !important;
      box-shadow: none !important;
      background-clip: padding-box !important;
    }

    /* Additional responsive design for logout modal */
    @media (max-width: 640px) {
      #logoutModal .modal-content {
        width: 95% !important;
        padding: 36px 32px !important;
        margin: 20px !important;
      }
      
      #logoutModal .modal-buttons {
        flex-direction: column !important;
        gap: 16px !important;
      }
      
      #logoutModal .modal-btn {
        width: 100% !important;
        min-width: auto !important;
      }
      
      #logoutModal .modal-icon {
        width: 76px !important;
        height: 76px !important;
        font-size: 2rem !important;
      }
      
      #logoutModal .modal-title {
        font-size: 1.6rem !important;
      }
    }

    /* Enhanced glassmorphism and loading animations */
    #logoutModal.show .modal-content {
      animation: slideInLogout 0.6s cubic-bezier(0.34, 1.56, 0.64, 1) !important;
    }
    
    #logoutModal.show .modal-icon {
      animation: iconPulse 3s ease-in-out infinite, iconEntranceScale 0.8s cubic-bezier(0.34, 1.56, 0.64, 1) !important;
    }
    
    @keyframes iconEntranceScale {
      0% { 
        transform: scale(0) rotate(-180deg);
        opacity: 0;
      }
      70% { 
        transform: scale(1.1) rotate(10deg);
        opacity: 1;
      }
      100% { 
        transform: scale(1) rotate(0deg);
        opacity: 1;
      }
    }
    
    #logoutModal.show .modal-title {
      animation: textSlideUp 0.8s cubic-bezier(0.25, 0.8, 0.25, 1) 0.2s both !important;
    }
    
    #logoutModal.show .modal-subtitle {
      animation: textSlideUp 0.8s cubic-bezier(0.25, 0.8, 0.25, 1) 0.3s both !important;
    }
    
    #logoutModal.show .modal-text {
      animation: textSlideUp 0.8s cubic-bezier(0.25, 0.8, 0.25, 1) 0.4s both !important;
    }
    
    #logoutModal.show .modal-btn {
      animation: buttonSlideUp 0.8s cubic-bezier(0.25, 0.8, 0.25, 1) both !important;
    }
    
    #logoutModal.show .modal-btn:nth-child(1) {
      animation-delay: 0.5s !important;
    }
    
    #logoutModal.show .modal-btn:nth-child(2) {
      animation-delay: 0.6s !important;
    }
    
    @keyframes textSlideUp {
      0% {
        opacity: 0;
        transform: translateY(20px);
      }
      100% {
        opacity: 1;
        transform: translateY(0);
      }
    }
    
    @keyframes buttonSlideUp {
      0% {
        opacity: 0;
        transform: translateY(30px) scale(0.9);
      }
      100% {
        opacity: 1;
        transform: translateY(0) scale(1);
      }
    }
  </style>
</head>
<body>
  <?php include __DIR__ . '/includes/sidebar_common.php'; ?>
        <i class="fas fa-upload"></i>
        <span>Import Data</span>
      </a>
      <a href="export_data_engineering.php">
        <i class="fas fa-download"></i>
        <span>Export Data</span>
      </a>
      <a href="view_statistics_engineering.php">
        <i class="fas fa-chart-bar"></i>
        <span>View Statistics</span>
      </a>
    </div>
  </div>
  <div class="topbar">
    <h1 class="dashboard-title">Engineering Admin Dashboard</h1>
    <a href="logout.php" class="logout-btn" onclick="return confirmLogout(event)">
      <i class="fas fa-sign-out-alt"></i>
      Logout
    </a>
  </div>
  <div class="main-content">
    <div class="card">
      <div class="card-header">
        <div class="header-content">
          <i class="fas fa-file-import header-icon"></i>
          <div>
            <h1 class="header-title">Import Board Passers Data</h1>
            <p class="header-subtitle">Upload CSV files to import board passer records efficiently</p>
          </div>
        </div>
      </div>
      
      <div class="card-content">
        <?php if (isset($message)): ?>
          <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?= $message ?>
          </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
          <div class="alert alert-error">
            <i class="fas fa-exclamation-triangle"></i>
            <?= $error ?>
          </div>
        <?php endif; ?>
        
        <!-- Template Download Section -->
        <div class="template-section">
          <div class="template-icon">
            <i class="fas fa-download"></i>
          </div>
          <h3 class="template-title">Download Template</h3>
          <p class="template-description">
            Download the CSV template with the correct format and sample data to ensure proper import
          </p>
          <a href="#" class="template-btn" onclick="downloadTemplate()">
            <i class="fas fa-file-csv"></i>
            Download CSV Template
          </a>
        </div>
        
        <!-- File Upload Section -->
        <form action="" method="POST" enctype="multipart/form-data" id="importForm">
          <div class="upload-area" onclick="document.getElementById('csvFile').click()" 
               ondrop="dropHandler(event);" ondragover="dragOverHandler(event);" ondragleave="dragLeaveHandler(event);">
            <div class="upload-icon">
              <i class="fas fa-cloud-upload-alt"></i>
            </div>
            <div class="upload-text">Drop your CSV file here</div>
            <div class="upload-hint">or click to browse files</div>
            <input type="file" id="csvFile" name="csvFile" accept=".csv" class="file-input" onchange="fileSelected(event)" required>
            <button type="button" class="upload-btn" onclick="document.getElementById('csvFile').click()">
              <i class="fas fa-folder-open"></i>
              Choose File
            </button>
          </div>
          
          <div class="file-info" id="fileInfo">
            <div class="file-name" id="fileName"></div>
            <div class="file-size" id="fileSize"></div>
          </div>
          
          <div style="text-align: center; margin-top: 30px;">
            <button type="submit" class="btn btn-primary" id="importBtn" disabled>
              <i class="fas fa-upload"></i>
              Import Data
            </button>
          </div>
        </form>
        
        <!-- Import Statistics -->
        <?php if (isset($stats)): ?>
        <div class="import-stats">
          <h3 style="color: #2c5aa0; margin-bottom: 20px; font-weight: 700;">Import Results</h3>
          <div class="stats-grid">
            <div class="stat-item">
              <div class="stat-value"><?= $stats['total'] ?></div>
              <div class="stat-label">Total Records</div>
            </div>
            <div class="stat-item">
              <div class="stat-value" style="color: #059669;"><?= $stats['imported'] ?></div>
              <div class="stat-label">Successfully Imported</div>
            </div>
            <div class="stat-item">
              <div class="stat-value" style="color: #dc2626;"><?= $stats['errors'] ?></div>
              <div class="stat-label">Errors</div>
            </div>
            <div class="stat-item">
              <div class="stat-value" style="color: #d97706;"><?= $stats['duplicates'] ?></div>
              <div class="stat-label">Duplicates Skipped</div>
            </div>
          </div>
          
          <?php if (!empty($errorList)): ?>
          <h4 style="color: #dc2626; margin-bottom: 15px;">Error Details:</h4>
          <div class="error-list">
            <?php foreach ($errorList as $error): ?>
              <div class="error-item"><?= htmlspecialchars($error) ?></div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Import Confirmation Modal -->
  <div id="importConfirmModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <div class="modal-icon" style="background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);">
          <i class="fas fa-file-upload"></i>
        </div>
        <h2 class="modal-title">Confirm Data Import</h2>
        <p class="modal-subtitle">Are you ready to import the selected file?</p>
      </div>
      <div class="import-file-preview" id="importFilePreview">
        <div class="file-preview-icon">
          <i class="fas fa-file-csv"></i>
        </div>
        <div class="file-preview-info">
          <div class="file-preview-name" id="previewFileName">No file selected</div>
          <div class="file-preview-size" id="previewFileSize">0 KB</div>
        </div>
      </div>
      <p class="modal-text">
        <i class="fas fa-info-circle" style="color: #0891b2; margin-right: 8px;"></i>
        This action will import board passer data from your CSV file. Make sure the file follows the correct format and contains valid data for years 2019-2024.
      </p>
      <div class="modal-buttons">
        <button id="importConfirmYes" class="modal-btn import-confirm">
          <i class="fas fa-upload"></i>
          <span class="btn-text">Yes, Import Data</span>
          <i class="fas fa-spinner btn-spinner"></i>
          <i class="fas fa-check-circle btn-check"></i>
        </button>
        <button id="importConfirmNo" class="modal-btn import-cancel">
          <i class="fas fa-times"></i> Cancel
        </button>
      </div>
    </div>
  </div>

  <!-- Logout Confirmation Modal -->
  <div id="logoutModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <div class="modal-icon">
          <i class="fas fa-sign-out-alt"></i>
        </div>
        <h2 class="modal-title">Confirm Logout</h2>
        <p class="modal-subtitle">Are you sure you want to sign out?</p>
      </div>
      <p class="modal-text">You will be redirected to the login page and any unsaved changes will be lost.</p>
      <div class="modal-buttons">
        <button id="logoutConfirmYes" class="modal-btn logout-confirm">
          <i class="fas fa-check"></i>
          <span class="btn-text">Yes, Logout</span>
          <i class="fas fa-spinner btn-spinner"></i>
          <i class="fas fa-check-circle btn-check"></i>
        </button>
        <button id="logoutConfirmNo" class="modal-btn logout-cancel">
          <i class="fas fa-times"></i> Cancel
        </button>
      </div>
    </div>
  </div>

  <script src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
  <script>
    // Logout confirmation functionality
    function confirmLogout(event) {
      event.preventDefault();
      console.log('confirmLogout called');
      const modal = document.getElementById('logoutModal');
      console.log('Modal found:', modal);
      if (modal) {
        console.log('Modal current display:', window.getComputedStyle(modal).display);
        modal.style.display = 'flex';
        modal.style.zIndex = '9999';
        modal.style.position = 'fixed';
        modal.style.top = '0';
        modal.style.left = '0';
        modal.style.width = '100vw';
        modal.style.height = '100vh';
        
        // Add show class for our beautiful animations
        modal.classList.add('show');
        console.log('Added show class to modal with beautiful animations');
        
        // Check button visibility
        const yesBtn = document.getElementById('logoutConfirmYes');
        const noBtn = document.getElementById('logoutConfirmNo');
        const modalButtons = modal.querySelector('.modal-buttons');
        
        console.log('Yes button found:', yesBtn);
        console.log('No button found:', noBtn);
        console.log('Modal buttons container found:', modalButtons);
        
        // The buttons will be styled by our beautiful CSS, no need for forced styling
        if (yesBtn) {
          yesBtn.style.display = 'flex';
          yesBtn.style.visibility = 'visible';
          yesBtn.style.opacity = '1';
          yesBtn.removeAttribute('hidden');
          
          // Add interactive logout functionality
          yesBtn.onclick = function(e) {
            e.preventDefault();
            handleInteractiveLogout(this);
          };
          
          console.log('Yes button made visible for beautiful theme with interactive logout');
        }
        
        if (noBtn) {
          noBtn.style.display = 'flex';
          noBtn.style.visibility = 'visible';
          noBtn.style.opacity = '1';
          noBtn.removeAttribute('hidden');
          console.log('No button made visible for beautiful theme');
        }
        
        if (modalButtons) {
          modalButtons.style.display = 'flex';
          console.log('Modal buttons container set to flex for beautiful layout');
        }
        
        console.log('Beautiful logout modal displayed with premium blue theme');
        console.log('Modal after display change:', window.getComputedStyle(modal).display);
      } else {
        console.error('Logout modal not found!');
      }
      return false;
    }
    
    // Interactive logout function with enhanced animations
    function handleInteractiveLogout(button) {
      console.log('🚀 Interactive logout initiated!');
      
      // Prevent double clicks
      if (button.classList.contains('loading')) {
        return;
      }
      
      // Add loading state
      button.classList.add('loading');
      
      // Disable cancel button during logout
      const cancelBtn = document.getElementById('logoutConfirmNo');
      if (cancelBtn) {
        cancelBtn.style.opacity = '0.5';
        cancelBtn.style.pointerEvents = 'none';
      }
      
      // Show beautiful loading animation for 2 seconds
      setTimeout(() => {
        // Remove loading state and add success state
        button.classList.remove('loading');
        button.classList.add('success');
        
        // Show success message
        showLogoutSuccessMessage();
        
        // Wait for success animation, then redirect
        setTimeout(() => {
          console.log('✅ Logout successful! Redirecting to login page...');
          window.location.href = 'mainpage.php';
        }, 1500);
        
      }, 2000);
    }
    
    // Beautiful logout success message
    function showLogoutSuccessMessage() {
      const messageDiv = document.createElement('div');
      messageDiv.innerHTML = `
        <div style="
          position: fixed;
          top: 50%;
          left: 50%;
          transform: translate(-50%, -50%);
          background: linear-gradient(135deg, #10b981 0%, #059669 100%);
          color: white;
          padding: 20px 32px;
          border-radius: 16px;
          box-shadow: 0 16px 40px rgba(16, 185, 129, 0.4);
          z-index: 10002;
          font-family: 'Inter', sans-serif;
          font-weight: 700;
          text-align: center;
          min-width: 300px;
          backdrop-filter: blur(10px);
          border: 2px solid rgba(255, 255, 255, 0.2);
          animation: successSlideIn 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
        ">
          <div style="
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            font-size: 1.1rem;
          ">
            <i class="fas fa-check-circle" style="
              font-size: 1.3rem;
              animation: successCheckBounce 0.8s cubic-bezier(0.68, -0.55, 0.265, 1.55) 0.3s both;
            "></i>
            Logout Successful!
          </div>
          <div style="
            font-size: 0.9rem;
            font-weight: 500;
            margin-top: 8px;
            opacity: 0.9;
          ">
            Redirecting to login page...
          </div>
        </div>
        <style>
          @keyframes successSlideIn {
            0% { 
              opacity: 0;
              transform: translate(-50%, -50%) scale(0.8) translateY(20px);
            }
            100% { 
              opacity: 1;
              transform: translate(-50%, -50%) scale(1) translateY(0);
            }
          }
          @keyframes successCheckBounce {
            0% { 
              transform: scale(0) rotate(-180deg);
            }
            70% { 
              transform: scale(1.2) rotate(10deg);
            }
            100% { 
              transform: scale(1) rotate(0deg);
            }
          }
        </style>
      `;
      document.body.appendChild(messageDiv);
      
      // Remove the message after animation
      setTimeout(() => {
        document.body.removeChild(messageDiv);
      }, 3000);
    }
    
    // Handle logout confirmation
    document.getElementById('logoutConfirmNo').onclick = function() {
      document.getElementById('logoutModal').style.display = 'none';
    };
    
    // Close logout modal when clicking outside
    document.getElementById('logoutModal').onclick = function(e) {
      if (e.target === this) {
        this.style.display = 'none';
      }
    };
    
    // Import Data Functionality
    function dragOverHandler(ev) {
      ev.preventDefault();
      ev.currentTarget.classList.add('dragover');
    }
    
    function dragLeaveHandler(ev) {
      ev.currentTarget.classList.remove('dragover');
    }
    
    function dropHandler(ev) {
      ev.preventDefault();
      ev.currentTarget.classList.remove('dragover');
      
      if (ev.dataTransfer.items) {
        for (let i = 0; i < ev.dataTransfer.items.length; i++) {
          if (ev.dataTransfer.items[i].kind === 'file') {
            const file = ev.dataTransfer.items[i].getAsFile();
            if (file.type === 'text/csv' || file.name.endsWith('.csv')) {
              document.getElementById('csvFile').files = ev.dataTransfer.files;
              fileSelected({ target: { files: [file] } });
              break;
            } else {
              alert('Please upload only CSV files.');
            }
          }
        }
      }
    }
    
    function fileSelected(event) {
      const file = event.target.files[0];
      const fileInfo = document.getElementById('fileInfo');
      const fileName = document.getElementById('fileName');
      const fileSize = document.getElementById('fileSize');
      const importBtn = document.getElementById('importBtn');
      
      if (file) {
        fileName.textContent = file.name;
        fileSize.textContent = `Size: ${(file.size / 1024).toFixed(2)} KB`;
        fileInfo.style.display = 'block';
        importBtn.disabled = false;
        
        // Add file validation animation
        fileInfo.style.animation = 'alertSlideIn 0.4s ease';
      } else {
        fileInfo.style.display = 'none';
        importBtn.disabled = true;
      }
    }
    
    function downloadTemplate() {
      // Create sample CSV data with headers only
      const csvContent = 'First Name,Last Name,Middle Name,Course,Date of Taking Board Exam,Sex,School,Exam Type\n';
      
      // Create and download file
      const blob = new Blob([csvContent], { type: 'text/csv' });
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = 'board_passers_template.csv';
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      window.URL.revokeObjectURL(url);
      
      // Show success message
      const templateBtn = event.target.closest('.template-btn');
      const originalText = templateBtn.innerHTML;
      templateBtn.innerHTML = '<i class="fas fa-check"></i> Downloaded!';
      templateBtn.style.background = 'linear-gradient(135deg, #059669 0%, #10b981 100%)';
      
      setTimeout(() => {
        templateBtn.innerHTML = originalText;
        templateBtn.style.background = 'linear-gradient(135deg, #d97706 0%, #f59e0b 100%)';
      }, 2000);
    }
    
    // Form submission with confirmation modal
    document.getElementById('importForm').addEventListener('submit', function(e) {
      e.preventDefault(); // Prevent default submission
      
      const fileInput = document.getElementById('csvFile');
      const file = fileInput.files[0];
      
      if (!file) {
        alert('Please select a file first.');
        return;
      }
      
      // Update modal preview
      document.getElementById('previewFileName').textContent = file.name;
      document.getElementById('previewFileSize').textContent = `${(file.size / 1024).toFixed(2)} KB`;
      
      // Show confirmation modal
      showImportConfirmModal();
    });
    
    function showImportConfirmModal() {
      const modal = document.getElementById('importConfirmModal');
      if (modal) {
        modal.style.display = 'flex';
        console.log('Import confirmation modal displayed');
      }
    }
    
    function hideImportConfirmModal() {
      const modal = document.getElementById('importConfirmModal');
      if (modal) {
        modal.style.display = 'none';
        console.log('Import confirmation modal hidden');
      }
    }
    
    // Handle import confirmation
    document.getElementById('importConfirmYes').addEventListener('click', function() {
      const button = this;
      const originalText = button.innerHTML;
      
      // Add loading state
      button.classList.add('loading');
      button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span class="btn-text">Importing...</span>';
      
      // Disable cancel button
      document.getElementById('importConfirmNo').style.opacity = '0.5';
      document.getElementById('importConfirmNo').style.pointerEvents = 'none';
      
      // Submit the form
      setTimeout(() => {
        document.getElementById('importForm').submit();
      }, 500);
    });
    
    // Handle import cancellation
    document.getElementById('importConfirmNo').addEventListener('click', function() {
      hideImportConfirmModal();
    });
    
    // Close modal when clicking outside
    document.getElementById('importConfirmModal').onclick = function(e) {
      if (e.target === this) {
        hideImportConfirmModal();
      }
    };
  </script>
</body>
</html>
