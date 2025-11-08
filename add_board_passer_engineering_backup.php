<?php
session_start();

// Check if user is logged in - use the same session check as dashboard
if (!isset($_SESSION["users"]) || $_SESSION["users"] !== 'eng_admin@lspu.edu.ph') {
    header("Location: mainpage.php");
    exit();
}

// Database connection
$conn = new mysqli("localhost", "root", "", "project_db");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get courses for dropdown
$courses = [];
$course_query = $conn->query("SELECT DISTINCT course FROM board_passers WHERE department='Engineering' ORDER BY course");
while($row = $course_query->fetch_assoc()) {
    $courses[] = $row['course'];
}

// Add default courses if none exist
if (empty($courses)) {
    $courses = [
        'Bachelor of Science in Electrical Engineering',
        'Bachelor of Science in Electronics Engineering',
        'Bachelor of Science in Computer Engineering',
        'Bachelor of Science in Civil Engineering',
        'Bachelor of Science in Mechanical Engineering'
    ];
}

// Handle form submission
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $middle_name = trim($_POST['middle_name']);
    $suffix = trim($_POST['suffix']);
    $sex = $_POST['sex'];
    $course = trim($_POST['course']);
    $year_graduated = intval($_POST['year_graduated']);
    $board_exam_date = $_POST['board_exam_date'];
    $result = $_POST['result'];
    $exam_type = $_POST['exam_type'];
    $board_exam_type = $_POST['board_exam_type'];
    
    // Build full name
    $name = $last_name . ', ' . $first_name;
    if (!empty($middle_name)) {
        $name .= ' ' . $middle_name;
    }
    if (!empty($suffix)) {
        $name .= ' ' . $suffix;
    }
    
    // Validation
    $errors = [];
    
    if (empty($first_name)) {
        $errors[] = "First name is required";
    } elseif (!preg_match('/^[a-zA-Z\s,.-]+$/', $first_name)) {
        $errors[] = "First name can only contain letters, spaces, commas, periods, and hyphens";
    }
    
    if (empty($last_name)) {
        $errors[] = "Last name is required";
    } elseif (!preg_match('/^[a-zA-Z\s,.-]+$/', $last_name)) {
        $errors[] = "Last name can only contain letters, spaces, commas, periods, and hyphens";
    }
    
    if (!empty($middle_name) && !preg_match('/^[a-zA-Z\s,.-]+$/', $middle_name)) {
        $errors[] = "Middle name can only contain letters, spaces, commas, periods, and hyphens";
    }
    
    if (!empty($suffix) && !preg_match('/^[a-zA-Z\s,.-]+$/', $suffix)) {
        $errors[] = "Suffix can only contain letters, spaces, commas, periods, and hyphens";
    }
    
    if (empty($sex)) {
        $errors[] = "Sex is required";
    }
    
    if (empty($course)) {
        $errors[] = "Course is required";
    }
    
    if ($year_graduated < 1950 || $year_graduated > date('Y')) {
        $errors[] = "Year must be between 1950 and " . date('Y');
    }
    
    if (empty($board_exam_date)) {
        $errors[] = "Board exam date is required";
    }
    
    if (empty($result)) {
        $errors[] = "Result is required";
    }
    
    if (empty($exam_type)) {
        $errors[] = "Exam type is required";
    }
    
    if (empty($board_exam_type)) {
        $errors[] = "Board exam type is required";
    }
    
    // Check for duplicate
    if (empty($errors)) {
        $check_duplicate = $conn->prepare("SELECT id FROM board_passers WHERE first_name = ? AND last_name = ? AND course = ? AND year_graduated = ? AND department = 'Engineering'");
        $check_duplicate->bind_param("sssi", $first_name, $last_name, $course, $year_graduated);
        $check_duplicate->execute();
        if ($check_duplicate->get_result()->num_rows > 0) {
            $errors[] = "A record with this name, course, and graduation year already exists";
        }
    }
    
    if (empty($errors)) {
        // Insert new record
        $stmt = $conn->prepare("INSERT INTO board_passers (first_name, last_name, middle_name, suffix, name, sex, course, year_graduated, board_exam_date, result, exam_type, board_exam_type, department) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Engineering')");
        $stmt->bind_param("ssssssssissss", $first_name, $last_name, $middle_name, $suffix, $name, $sex, $course, $year_graduated, $board_exam_date, $result, $exam_type, $board_exam_type);
        
        if ($stmt->execute()) {
            $success_message = "Board passer added successfully!";
            // Clear form
            $_POST = [];
        } else {
            $error_message = "Error adding record: " . $conn->error;
        }
    } else {
        $error_message = implode(", ", $errors);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Add Board Passer - LSPU Engineering</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Inter', sans-serif;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      min-height: 100vh;
      display: flex;
    }

    .sidebar {
      width: 280px;
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(20px);
      border-right: 1px solid rgba(255, 255, 255, 0.2);
      padding: 2rem 0;
      position: fixed;
      height: 100vh;
      overflow-y: auto;
    }

    .logo {
      text-align: center;
      margin-bottom: 3rem;
      padding: 0 2rem;
    }

    .logo h1 {
      font-size: 2rem;
      font-weight: 800;
      color: #2c5aa0;
      margin-bottom: 0.5rem;
    }

    .logo span {
      color: #64748b;
      font-size: 0.9rem;
      font-weight: 400;
    }

    .sidebar-nav {
      padding: 0 1rem;
    }

    .sidebar-nav a {
      display: flex;
      align-items: center;
      padding: 1rem 1.5rem;
      color: #64748b;
      text-decoration: none;
      border-radius: 12px;
      margin-bottom: 0.5rem;
      transition: all 0.3s ease;
      font-weight: 500;
    }

    .sidebar-nav a:hover {
      background: rgba(49, 130, 206, 0.1);
      color: #3182ce;
      transform: translateX(4px);
    }

    .sidebar-nav a.active {
      background: linear-gradient(135deg, #3182ce 0%, #2c5aa0 100%);
      color: white;
      box-shadow: 0 4px 15px rgba(49, 130, 206, 0.3);
    }

    .sidebar-nav a ion-icon {
      font-size: 1.2rem;
      margin-right: 1rem;
    }

    .main-content {
      margin-left: 280px;
      flex: 1;
      padding: 2rem;
    }

    .topbar {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(20px);
      border-radius: 16px;
      padding: 1.5rem 2rem;
      margin-bottom: 2rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    }

    .dashboard-title {
      font-size: 1.8rem;
      font-weight: 700;
      color: #1e293b;
    }

    .logout-btn {
      background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
      color: white;
      border: none;
      padding: 0.75rem 1.5rem;
      border-radius: 12px;
      text-decoration: none;
      font-weight: 600;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .logout-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(239, 68, 68, 0.4);
    }

    .form-container {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(20px);
      border-radius: 20px;
      padding: 3rem;
      box-shadow: 0 8px 40px rgba(0, 0, 0, 0.1);
      max-width: 800px;
      margin: 0 auto;
    }

    .form-header {
      text-align: center;
      margin-bottom: 3rem;
    }

    .form-header h2 {
      font-size: 2.5rem;
      font-weight: 700;
      color: #1e293b;
      margin-bottom: 0.5rem;
    }

    .form-header p {
      color: #64748b;
      font-size: 1.1rem;
    }

    .form-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 2rem;
      margin-bottom: 2rem;
    }

    .form-group {
      margin-bottom: 1.5rem;
    }

    .form-group.full-width {
      grid-column: 1 / -1;
    }

    .form-group label {
      display: block;
      margin-bottom: 0.5rem;
      font-weight: 600;
      color: #374151;
      font-size: 0.95rem;
    }

    .form-group input,
    .form-group select {
      width: 100%;
      padding: 1rem 1.25rem;
      border: 2px solid #e5e7eb;
      border-radius: 12px;
      font-size: 1rem;
      font-family: 'Inter', sans-serif;
      transition: all 0.3s ease;
      background: white;
    }

    .form-group input:focus,
    .form-group select:focus {
      outline: none;
      border-color: #3182ce;
      box-shadow: 0 0 0 3px rgba(49, 130, 206, 0.1);
    }

    .form-group input.error,
    .form-group select.error {
      border-color: #ef4444;
    }

    .form-group input.success,
    .form-group select.success {
      border-color: #10b981;
    }

    .btn-group {
      display: flex;
      gap: 1rem;
      justify-content: center;
      margin-top: 2rem;
    }

    .btn {
      padding: 1rem 2rem;
      border: none;
      border-radius: 12px;
      font-size: 1rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      gap: 0.5rem;
      min-width: 150px;
      justify-content: center;
    }

    .btn-primary {
      background: linear-gradient(135deg, #3182ce 0%, #2c5aa0 100%);
      color: white;
      box-shadow: 0 4px 15px rgba(49, 130, 206, 0.3);
    }

    .btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(49, 130, 206, 0.4);
    }

    .btn-secondary {
      background: #f8fafc;
      color: #64748b;
      border: 2px solid #e2e8f0;
    }

    .btn-secondary:hover {
      background: #f1f5f9;
      border-color: #cbd5e1;
    }

    .alert {
      padding: 1rem 1.5rem;
      border-radius: 12px;
      margin-bottom: 2rem;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .alert-success {
      background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
      color: #065f46;
      border: 1px solid #10b981;
    }

    .alert-error {
      background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
      color: #991b1b;
      border: 1px solid #ef4444;
    }

    @media (max-width: 768px) {
      .sidebar {
        transform: translateX(-100%);
      }
      
      .main-content {
        margin-left: 0;
        padding: 1rem;
      }
      
      .form-container {
        padding: 2rem;
      }
      
      .form-grid {
        grid-template-columns: 1fr;
      }
      
      .btn-group {
        flex-direction: column;
      }
    }
  </style>
</head>
<body>
  <?php include __DIR__ . '/includes/sidebar_common.php'; ?>

  <div class="main-content">
    <div class="topbar">
      <h1 class="dashboard-title">Add New Board Passer</h1>
      <a href="logout.php" class="logout-btn" onclick="return confirmLogout()">
        <i class="fas fa-sign-out-alt"></i>
        Logout
      </a>
    </div>

    <div class="form-container">
      <div class="form-header">
        <h2><i class="fas fa-user-plus"></i> Add Board Passer</h2>
        <p>Enter the details of the new board passer below</p>
      </div>

      <?php if ($success_message): ?>
        <div class="alert alert-success">
          <i class="fas fa-check-circle"></i>
          <?= htmlspecialchars($success_message) ?>
        </div>
      <?php endif; ?>

      <?php if ($error_message): ?>
        <div class="alert alert-error">
          <i class="fas fa-exclamation-triangle"></i>
          <?= htmlspecialchars($error_message) ?>
        </div>
      <?php endif; ?>

      <form method="POST" id="addPasserForm">
        <div class="form-grid">
          <div class="form-group">
            <label for="name">Full Name *</label>
            <input type="text" id="name" name="name" required 
                   pattern="[a-zA-Z\s,.-]+" 
                   title="Only letters, spaces, commas, periods, and hyphens allowed"
                   value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
          </div>

          <div class="form-group">
            <label for="course">Course *</label>
            <select id="course" name="course" required>
              <option value="">Select Course</option>
              <?php foreach($courses as $course): ?>
                <option value="<?= htmlspecialchars($course) ?>" 
                        <?= (($_POST['course'] ?? '') === $course) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($course) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <label for="year_graduated">Year Graduated *</label>
            <input type="number" id="year_graduated" name="year_graduated" required 
                   min="1950" max="<?= date('Y') ?>"
                   value="<?= htmlspecialchars($_POST['year_graduated'] ?? '') ?>">
          </div>

          <div class="form-group">
            <label for="board_exam_date">Board Exam Date *</label>
            <input type="date" id="board_exam_date" name="board_exam_date" required
                   value="<?= htmlspecialchars($_POST['board_exam_date'] ?? '') ?>">
          </div>

          <div class="form-group">
            <label for="result">Result *</label>
            <select id="result" name="result" required>
              <option value="">Select Result</option>
              <option value="Passed" <?= (($_POST['result'] ?? '') === 'Passed') ? 'selected' : '' ?>>Passed</option>
              <option value="Failed" <?= (($_POST['result'] ?? '') === 'Failed') ? 'selected' : '' ?>>Failed</option>
              <option value="Conditional" <?= (($_POST['result'] ?? '') === 'Conditional') ? 'selected' : '' ?>>Conditional</option>
            </select>
          </div>

          <div class="form-group">
            <label for="exam_type">Exam Type *</label>
            <select id="exam_type" name="exam_type" required>
              <option value="">Select Exam Type</option>
              <option value="First Timer" <?= (($_POST['exam_type'] ?? '') === 'First Timer') ? 'selected' : '' ?>>First Timer</option>
              <option value="Repeater" <?= (($_POST['exam_type'] ?? '') === 'Repeater') ? 'selected' : '' ?>>Repeater</option>
            </select>
          </div>

          <div class="form-group full-width">
            <label for="board_exam_type">Board Exam Type *</label>
            <select id="board_exam_type" name="board_exam_type" required>
              <option value="">Select Board Exam Type</option>
              <option value="Registered Electrical Engineer Licensure Exam (REELE)" 
                      <?= (($_POST['board_exam_type'] ?? '') === 'Registered Electrical Engineer Licensure Exam (REELE)') ? 'selected' : '' ?>>
                Registered Electrical Engineer Licensure Exam (REELE)
              </option>
              <option value="Registered Master Electrician (RME)" 
                      <?= (($_POST['board_exam_type'] ?? '') === 'Registered Master Electrician (RME)') ? 'selected' : '' ?>>
                Registered Master Electrician (RME)
              </option>
            </select>
          </div>
        </div>

        <div class="btn-group">
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i>
            Add Board Passer
          </button>
          <button type="button" class="btn btn-secondary" onclick="resetForm()">
            <i class="fas fa-undo"></i>
            Reset Form
          </button>
        </div>
      </form>
    </div>
  </div>

  <script src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
  <script>
    function confirmLogout() {
      return confirm('Are you sure you want to logout?');
    }

    function resetForm() {
      if (confirm('Are you sure you want to reset the form? All entered data will be lost.')) {
        document.getElementById('addPasserForm').reset();
      }
    }

    // Real-time validation
    document.addEventListener('DOMContentLoaded', function() {
      const form = document.getElementById('addPasserForm');
      const inputs = form.querySelectorAll('input, select');

      inputs.forEach(input => {
        input.addEventListener('input', function() {
          validateField(this);
        });

        input.addEventListener('blur', function() {
          validateField(this);
        });
      });

      function validateField(field) {
        field.classList.remove('error', 'success');
        
        if (field.checkValidity() && field.value.trim() !== '') {
          field.classList.add('success');
        } else if (field.value.trim() !== '') {
          field.classList.add('error');
        }
      }

      // Form submission validation
      form.addEventListener('submit', function(e) {
        let isValid = true;
        
        inputs.forEach(input => {
          if (!input.checkValidity()) {
            isValid = false;
            input.classList.add('error');
          }
        });

        if (!isValid) {
          e.preventDefault();
          alert('Please fill in all required fields correctly.');
        }
      });
    });
  </script>
</body>
</html>
      padding: 0;
      box-sizing: border-box;
    }
    body {
      background: linear-gradient(120deg, #e0e7ef 0%, #f7fafc 100%);
      margin: 0;
      font-family: 'Inter', sans-serif;
      min-height: 100vh;
    }
    .sidebar {
      position: fixed;
      left: 0; top: 0; bottom: 0;
      width: 260px;
      background: linear-gradient(135deg, #e0e7ef 0%, #b3c6e0 100%);
      box-shadow: 4px 0 30px rgba(22, 41, 56, 0.08);
      padding-top: 32px;
      z-index: 100;
      display: flex;
      flex-direction: column;
      gap: 20px;
      border-right: 1px solid #b3c6e0;
      height: 100vh;
    }
    .sidebar .logo {
      text-align: center;
      margin-bottom: 40px;
      font-size: 1.5rem;
      font-weight: 700;
      color: #1a2a36;
      letter-spacing: 1.5px;
    }
    .sidebar-nav {
      display: flex;
      flex-direction: column;
      gap: 12px;
      padding: 0 24px;
    }
    .sidebar-nav a {
      display: flex;
      align-items: center;
      gap: 16px;
      padding: 16px 20px;
      border-radius: 12px;
      color: #1a2a36;
      text-decoration: none;
      font-size: 0.95rem;
      font-weight: 500;
      transition: all 0.3s ease;
      position: relative;
    }
    .sidebar-nav a.active, .sidebar-nav a:hover {
      background: linear-gradient(90deg, #3a8dde 0%, #b3c6e0 100%);
      color: #fff;
      font-weight: 600;
      transform: translateX(8px);
      box-shadow: 0 8px 25px rgba(58, 141, 222, 0.3);
    }
    .sidebar-nav ion-icon {
      font-size: 1.4em;
      color: #3a8dde;
      transition: color 0.3s;
    }
    .sidebar-nav a.active ion-icon, .sidebar-nav a:hover ion-icon {
      color: #fff;
    }
    
    .topbar {
      position: fixed;
      top: 0;
      left: 260px;
      right: 0;
      background: linear-gradient(135deg, #2c5aa0 0%, #3182ce 100%);
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
      padding: 40px;
      min-height: calc(100vh - 70px);
      background: transparent;
    }
    .card {
      background: #ffffff;
      border-radius: 24px;
      box-shadow: 0 20px 60px rgba(22, 41, 56, 0.1);
      padding: 48px;
      margin: 0;
      border: 1px solid #e2e8f0;
      max-height: fit-content;
      display: flex;
      flex-direction: column;
      overflow: visible;
      position: relative;
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
      flex-shrink: 0;
    }
    .form-sections {
      flex: 1;
      overflow-y: auto;
      padding-right: 12px;
      min-height: 0;
    }
    .section {
      margin-bottom: 60px;
      transition: all 0.3s ease;
      scroll-margin-top: 40px;
    }
    .section:last-child {
      margin-bottom: 40px;
    }
    .section-header {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 24px;
      padding-bottom: 16px;
      border-bottom: 2px solid rgba(44, 90, 160, 0.1);
    }
    .section-header h3 {
      font-size: 1.3rem;
      font-weight: 700;
      color: #2d3748;
      margin: 0;
    }
    .section-header .icon {
      width: 36px;
      height: 36px;
      border-radius: 50%;
      background: linear-gradient(135deg, #2c5aa0 0%, #3182ce 100%);
      display: flex;
      align-items: center;
      justify-content: center;
      color: #fff;
      font-size: 1.1rem;
    }
    .form-container {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 24px;
      margin-bottom: 32px;
    }
    .form-group {
      display: flex;
      flex-direction: column;
      position: relative;
    }
    .form-group.full-width {
      grid-column: 1 / -1;
    }
    .input-wrapper {
      position: relative;
      display: flex;
      align-items: center;
    }
    .input-icon {
      position: absolute;
      left: 16px;
      color: #a0aec0;
      font-size: 1.1rem;
      z-index: 2;
      transition: color 0.3s ease;
    }
    .form-group label {
      font-size: 0.85rem;
      font-weight: 600;
      color: #4a5568;
      margin-bottom: 8px;
      text-transform: uppercase;
      letter-spacing: 0.8px;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .required {
      color: #e53e3e;
      font-weight: 700;
    }
    .form-group input,
    .form-group select {
      width: 100%;
      padding: 16px 20px 16px 48px;
      border: 2px solid #e2e8f0;
      border-radius: 12px;
      font-size: 1rem;
      font-family: 'Inter', sans-serif;
      font-weight: 500;
      transition: all 0.3s ease;
      background: #fff;
      box-sizing: border-box;
      height: 56px;
      outline: none;
      color: #2d3748;
    }
    .form-group input:focus,
    .form-group select:focus {
      border-color: #3182ce;
      box-shadow: 0 0 0 4px rgba(49, 130, 206, 0.1);
      transform: translateY(-2px);
    }
    .form-group input:focus + .input-icon,
    .form-group select:focus + .input-icon {
      color: #3182ce;
    }
    .form-group input:hover,
    .form-group select:hover {
      border-color: #cbd5e0;
    }
    .form-group select {
      appearance: none;
      background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
      background-position: right 16px center;
      background-repeat: no-repeat;
      background-size: 16px;
      padding-right: 48px;
    }
    .validation-error {
      border-color: #e53e3e !important;
      box-shadow: 0 0 0 4px rgba(229, 62, 62, 0.1) !important;
    }
    .error-message {
      color: #e53e3e;
      font-size: 0.8rem;
      margin-top: 4px;
      font-weight: 500;
    }
    .submit-container {
      flex-shrink: 0;
      padding-top: 32px;
      border-top: 2px solid rgba(44, 90, 160, 0.1);
      margin-top: 24px;
    }
    .submit-btn {
      width: 100%;
      background: linear-gradient(135deg, #2c5aa0 0%, #3182ce 100%);
      color: #ffffff;
      border: none;
      border-radius: 16px;
      padding: 20px 40px;
      font-size: 1.1rem;
      font-weight: 700;
      font-family: 'Inter', sans-serif;
      cursor: pointer;
      transition: all 0.3s ease;
      text-transform: uppercase;
      letter-spacing: 1.2px;
      box-shadow: 0 12px 40px rgba(44, 90, 160, 0.3);
      position: relative;
      overflow: hidden;
    }
    .submit-btn:hover {
      background: linear-gradient(135deg, #1a365d 0%, #2c5aa0 100%);
      transform: translateY(-3px);
      box-shadow: 0 20px 60px rgba(44, 90, 160, 0.4);
    }
    .submit-btn:active {
      transform: translateY(-1px);
    }
    .submit-btn:before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
      transition: left 0.5s;
    }
    .submit-btn:hover:before {
      left: 100%;
    }
    @media (max-width: 1200px) {
      .main-content { padding: 24px; }
      .card { height: calc(100vh - 48px); padding: 32px 24px; }
      .form-container { gap: 20px; }
    }
    @media (max-width: 900px) {
      .sidebar { width: 80px; }
      .main-content, .topbar { margin-left: 80px; }
      .sidebar-nav a span { display: none; }
      .sidebar .logo { font-size: 1.2rem; }
      .dashboard-title { font-size: 1.1rem; }
      .logout-btn { padding: 10px 16px; font-size: 0.9rem; }
      .form-container { grid-template-columns: 1fr; gap: 16px; }
      .card { padding: 24px 16px; height: calc(100vh - 24px); }
    }
    @media (max-width: 600px) {
      .sidebar { display: none; }
      .topbar, .main-content { margin-left: 0; }
      .topbar { padding: 16px 20px; }
      .dashboard-title { font-size: 1rem; }
      .logout-btn { 
        padding: 8px 12px; 
        font-size: 0.85rem; 
      }
      .card { padding: 20px 16px; margin: 20px 16px; height: calc(100vh - 40px); }
      .form-container { gap: 16px; }
    }
    
    /* Modal Styles */
    .modal {
      position: fixed;
      top: 0;
      left: 0;
      width: 100vw;
      height: 100vh;
      background: rgba(22, 41, 56, 0.5);
      z-index: 9998;
      display: none;
      justify-content: center;
      align-items: center;
    }
    .modal-content {
      background: #ffffff;
      padding: 32px 28px;
      border-radius: 16px;
      box-shadow: 0 8px 32px rgba(22, 41, 56, 0.3);
      max-width: 500px;
      text-align: center;
    }
    .modal-text {
      font-size: 1.1rem;
      color: #2d3748;
      margin-bottom: 24px;
      line-height: 1.5;
    }
    .student-details-container {
      background: #f8fafc;
      border: 2px solid #e2e8f0;
      border-radius: 12px;
      padding: 20px;
      margin: 20px 0;
      text-align: left;
    }
    .student-details-container .detail-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 8px 0;
      border-bottom: 1px solid #e2e8f0;
    }
    .student-details-container .detail-row:last-child {
      border-bottom: none;
    }
    .student-details-container .detail-label {
      font-weight: 600;
      color: #4a5568;
      font-size: 0.9rem;
      min-width: 120px;
    }
    .student-details-container .detail-value {
      color: #2d3748;
      font-size: 0.9rem;
      flex: 1;
      text-align: right;
      word-break: break-word;
    }
    .modal-buttons {
      display: flex;
      gap: 12px;
      justify-content: center;
    }
    .modal-btn {
      padding: 10px 24px;
      border: none;
      border-radius: 8px;
      font-size: 1rem;
      font-weight: 500;
      cursor: pointer;
      transition: all 0.2s ease;
    }
    .modal-btn:hover {
      opacity: 0.9;
      transform: translateY(-1px);
    }
  </style>
</head>
<body>
  <?php include __DIR__ . '/includes/sidebar_common.php'; ?>
  <div class="topbar">
    <h1 class="dashboard-title">Engineering Admin Dashboard</h1>
    <a href="logout.php" class="logout-btn" onclick="return confirmLogout()">
      <i class="fas fa-sign-out-alt"></i>
      Logout
    </a>
  </div>
  <div class="main-content">
    <?php if (isset($_GET['error'])): ?>
      <div style="background: #fed7d7; border: 1px solid #fc8181; color: #c53030; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; text-align: center;">
        <?php
          switch($_GET['error']) {
            case 'missing_fields':
              echo "Error: Last name, first name, and sex are required fields.";
              break;
            case 'missing_data':
              echo "Error: Please fill in all required fields (course, year, exam date, result, exam type).";
              break;
            case 'db_error':
              echo "Error: Database connection problem. Please try again.";
              break;
            case 'insert_failed':
              echo "Error: Failed to save data. Please try again.";
              break;
            default:
              echo "An error occurred. Please try again.";
          }
        ?>
      </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['success'])): ?>
      <div style="background: #c6f6d5; border: 1px solid #68d391; color: #2f855a; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; text-align: center;">
        Board passer added successfully!
      </div>
    <?php endif; ?>
    
    <div class="card">
      <h2>Add New Board Passer</h2>
      
      <form method="post" action="add_board_passer_engineering.php">
        <div class="form-sections">
          <div class="section" id="personal-info-section">
            <div class="section-header">
              <div class="icon">
                <i class="fas fa-user"></i>
              </div>
              <h3>Personal Information</h3>
            </div>
            <div class="form-container">
              <div class="form-group">
                <label for="last_name">Last Name <span class="required">*</span></label>
                <div class="input-wrapper">
                  <input type="text" id="last_name" name="last_name" required placeholder="Enter last name">
                  <i class="fas fa-user input-icon"></i>
                </div>
              </div>
              <div class="form-group">
                <label for="first_name">First Name <span class="required">*</span></label>
                <div class="input-wrapper">
                  <input type="text" id="first_name" name="first_name" required placeholder="Enter first name">
                  <i class="fas fa-user input-icon"></i>
                </div>
              </div>
              <div class="form-group">
                <label for="middle_name">Middle Name</label>
                <div class="input-wrapper">
                  <input type="text" id="middle_name" name="middle_name" placeholder="Enter middle name (optional)">
                  <i class="fas fa-user input-icon"></i>
                </div>
              </div>
              <div class="form-group">
                <label for="suffix">Suffix</label>
                <div class="input-wrapper">
                  <input type="text" id="suffix" name="suffix" placeholder="Jr., Sr., III, etc. (optional)">
                  <i class="fas fa-certificate input-icon"></i>
                </div>
              </div>
              <div class="form-group">
                <label for="sex">Sex <span class="required">*</span></label>
                <div class="input-wrapper">
                  <select id="sex" name="sex" required>
                    <option value="" disabled selected>Select sex</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                  </select>
                  <i class="fas fa-venus-mars input-icon"></i>
                </div>
              </div>
              <div class="form-group">
                <label for="course">Course <span class="required">*</span></label>
                <div class="input-wrapper">
                  <select id="course" name="course" required>
                    <option value="" disabled selected>Select course</option>
                    <?php foreach($courses as $course): ?>
                      <option value="<?= htmlspecialchars($course) ?>"><?= htmlspecialchars($course) ?></option>
                    <?php endforeach; ?>
                  </select>
                  <i class="fas fa-graduation-cap input-icon"></i>
                </div>
              </div>
            </div>
          </div>
          
          <div class="section" id="exam-info-section">
            <div class="section-header">
              <div class="icon">
                <i class="fas fa-graduation-cap"></i>
              </div>
              <h3>Examination Information</h3>
            </div>
            <div class="form-container">
              <div class="form-group">
                <label for="year_graduated">Year Graduated <span class="required">*</span></label>
                <div class="input-wrapper">
                  <select id="year_graduated" name="year_graduated" required>
                    <option value="" disabled selected>Select year graduated</option>
                    <?php
                      $current_year = date('Y');
                      for ($year = $current_year; $year >= 1990; $year--) {
                        echo "<option value='$year'>$year</option>";
                      }
                    ?>
                  </select>
                  <i class="fas fa-calendar input-icon"></i>
                </div>
              </div>
              <div class="form-group">
                <label for="board_exam_date">Board Exam Date <span class="required">*</span></label>
                <div class="input-wrapper">
                  <input type="date" id="board_exam_date" name="board_exam_date" required>
                  <i class="fas fa-calendar-alt input-icon"></i>
                </div>
              </div>
              <div class="form-group">
                <label for="result">Exam Result <span class="required">*</span></label>
                <div class="input-wrapper">
                  <select id="result" name="result" required>
                    <option value="" disabled selected>Select result</option>
                    <option value="Passed">Passed</option>
                    <option value="Failed">Failed</option>
                    <option value="Cond">Cond</option>
                  </select>
                  <i class="fas fa-trophy input-icon"></i>
                </div>
              </div>
              <div class="form-group">
                <label for="exam_type">Board Exam Attempt Status <span class="required">*</span></label>
                <div class="input-wrapper">
                  <select id="exam_type" name="exam_type" required>
                    <option value="" disabled selected>Select attempt status</option>
                    <option value="First Timer">First Timer</option>
                    <option value="Repeater">Repeater</option>
                  </select>
                  <i class="fas fa-redo input-icon"></i>
                </div>
              </div>
              <div class="form-group full-width">
                <label for="board_exam_type">Type of Board Exam <span class="required">*</span></label>
                <div class="input-wrapper">
                  <select id="board_exam_type" name="board_exam_type" required>
                    <option value="" disabled selected>Select board exam type</option>
                    <option value="Registered Electrical Engineer Licensure Exam (REELE)">Registered Electrical Engineer Licensure Exam (REELE)</option>
                    <option value="Registered Master Electrician (RME)">Registered Master Electrician (RME)</option>
                  </select>
                  <i class="fas fa-clipboard-list input-icon"></i>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <div class="submit-container">
          <button type="submit" class="submit-btn">
            <i class="fas fa-plus"></i> Add Board Passer
          </button>
        </div>
      </form>
    </div>
  </div>
  
  <!-- Confirmation Modal -->
  <div id="confirmModal" class="modal">
    <div class="modal-content">
      <div class="modal-text">
        <div style="display: flex; align-items: center; justify-content: center; margin-bottom: 16px;">
          <div style="width: 60px; height: 60px; background: linear-gradient(135deg, #3182ce 0%, #2c5aa0 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.5rem;">
            <i class="fas fa-user-plus"></i>
          </div>
        </div>
        <div style="font-size: 1.3rem; font-weight: 600; color: #2d3748; margin-bottom: 8px;">
          Confirm Add Board Passer
        </div>
        <div id="confirmDetails" style="font-size: 0.95rem; color: #718096; line-height: 1.5;">
          Are you sure you want to add this board passer to the database?
        </div>
      </div>
      <div class="modal-buttons">
        <button id="confirmYes" class="modal-btn" style="background: linear-gradient(135deg, #3182ce 0%, #2c5aa0 100%); color: #fff; padding: 12px 32px; font-weight: 600;">Yes, Add</button>
        <button id="confirmNo" class="modal-btn" style="background: #e2e8f0; color: #4a5568; padding: 12px 32px; font-weight: 600;">Cancel</button>
      </div>
    </div>
  </div>
  
  <script src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
  <script>
    function importData() {
      alert('Import PRC data feature coming soon!');
    }
    function exportData() {
      alert('Export data feature coming soon!');
    }
    function viewStats() {
      alert('Statistics/Analytics feature coming soon!');
    }
    
    function confirmLogout() {
      return confirm('Are you sure you want to logout?');
    }
    
    // Simple form handling with confirmation
    document.addEventListener('DOMContentLoaded', function() {
      const form = document.querySelector('form');
      const submitBtn = document.querySelector('.submit-btn');
      let allowFormSubmission = false;
      let formData = null;
      
      if (form) {
        form.addEventListener('submit', function(e) {
          // If form submission is already approved, let it go through
          if (allowFormSubmission) {
            allowFormSubmission = false; // Reset flag
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
            submitBtn.disabled = true;
            return true;
          }
          
          e.preventDefault(); // Prevent default form submission
          
          // Get form data for confirmation
          const firstName = document.getElementById('first_name').value.trim();
          const lastName = document.getElementById('last_name').value.trim();
          const middleName = document.getElementById('middle_name').value.trim();
          const suffix = document.getElementById('suffix').value.trim();
          const sex = document.getElementById('sex').value;
          const course = document.getElementById('course').value;
          const yearGraduated = document.getElementById('year_graduated').value;
          const boardExamDate = document.getElementById('board_exam_date').value;
          const result = document.getElementById('result').value;
          const examType = document.getElementById('exam_type').value;
          const boardExamType = document.getElementById('board_exam_type').value;
          
          // Validate required fields
          if (!firstName || !lastName || !sex || !course || !yearGraduated || !boardExamDate || !result || !examType || !boardExamType) {
            alert('Please fill in all required fields marked with *');
            return false;
          }
          
          // Build full name for display
          let fullName = lastName + ', ' + firstName;
          if (middleName) fullName += ' ' + middleName;
          if (suffix) fullName += ' ' + suffix;
          
          // Store form data
          formData = {
            firstName, lastName, middleName, suffix, sex, course, 
            yearGraduated, boardExamDate, result, examType, boardExamType
          };
          
          // Show confirmation details
          const confirmDetails = document.getElementById('confirmDetails');
          confirmDetails.innerHTML = `
            <div class="student-details-container">
              <div class="detail-row">
                <span class="detail-label">Name:</span>
                <span class="detail-value">${fullName}</span>
              </div>
              <div class="detail-row">
                <span class="detail-label">Sex:</span>
                <span class="detail-value">${sex}</span>
              </div>
              <div class="detail-row">
                <span class="detail-label">Course:</span>
                <span class="detail-value">${course}</span>
              </div>
              <div class="detail-row">
                <span class="detail-label">Year Graduated:</span>
                <span class="detail-value">${yearGraduated}</span>
              </div>
              <div class="detail-row">
                <span class="detail-label">Board Exam Date:</span>
                <span class="detail-value">${boardExamDate}</span>
              </div>
              <div class="detail-row">
                <span class="detail-label">Result:</span>
                <span class="detail-value">${result}</span>
              </div>
              <div class="detail-row">
                <span class="detail-label">Exam Type:</span>
                <span class="detail-value">${examType}</span>
              </div>
              <div class="detail-row">
                <span class="detail-label">Board Exam:</span>
                <span class="detail-value">${boardExamType}</span>
              </div>
            </div>
          `;
          
          // Show confirmation modal
          document.getElementById('confirmModal').style.display = 'flex';
        });
      }
      
      // Handle confirmation
      document.getElementById('confirmYes').onclick = function() {
        if (!formData) return;
        
        // Show loading state
        this.textContent = 'Adding...';
        this.disabled = true;
        
        // Allow form submission and submit
        allowFormSubmission = true;
        
        // Create a new form element with the data
        const newForm = document.createElement('form');
        newForm.method = 'POST';
        newForm.action = 'add_board_passer_engineering.php';
        
        // Add all form fields as hidden inputs
        const fields = [
          {name: 'first_name', value: formData.firstName},
          {name: 'last_name', value: formData.lastName},
          {name: 'middle_name', value: formData.middleName},
          {name: 'suffix', value: formData.suffix},
          {name: 'sex', value: formData.sex},
          {name: 'course', value: formData.course},
          {name: 'year_graduated', value: formData.yearGraduated},
          {name: 'board_exam_date', value: formData.boardExamDate},
          {name: 'result', value: formData.result},
          {name: 'exam_type', value: formData.examType},
          {name: 'board_exam_type', value: formData.boardExamType}
        ];
        
        fields.forEach(field => {
          const input = document.createElement('input');
          input.type = 'hidden';
          input.name = field.name;
          input.value = field.value || '';
          newForm.appendChild(input);
        });
        
        document.body.appendChild(newForm);
        newForm.submit();
      };
      
      document.getElementById('confirmNo').onclick = function() {
        document.getElementById('confirmModal').style.display = 'none';
        formData = null;
        document.getElementById('confirmYes').textContent = 'Yes, Add';
        document.getElementById('confirmYes').disabled = false;
      };
      
      // Close modal when clicking outside
      document.getElementById('confirmModal').onclick = function(e) {
        if (e.target === this) {
          this.style.display = 'none';
          formData = null;
          document.getElementById('confirmYes').textContent = 'Yes, Add';
          document.getElementById('confirmYes').disabled = false;
        }
      };
    });
  </script>
</body>
</html>
