<?php
session_start();

// Check if user is logged in - use the same session check as dashboard
if (!isset($_SESSION["users"]) || $_SESSION["users"] !== 'eng_admin@lspu.edu.ph') {
    header("Location: index.php");
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
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

    .required {
        color: #ef4444;
        font-weight: 700;
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
                        <label for="first_name">First Name <span class="required">*</span></label>
                        <input type="text" id="first_name" name="first_name" required pattern="[a-zA-Z\s,.-]+"
                            title="Only letters, spaces, commas, periods, and hyphens allowed"
                            value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label for="last_name">Last Name <span class="required">*</span></label>
                        <input type="text" id="last_name" name="last_name" required pattern="[a-zA-Z\s,.-]+"
                            title="Only letters, spaces, commas, periods, and hyphens allowed"
                            value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label for="middle_name">Middle Name</label>
                        <input type="text" id="middle_name" name="middle_name" pattern="[a-zA-Z\s,.-]*"
                            title="Only letters, spaces, commas, periods, and hyphens allowed"
                            value="<?= htmlspecialchars($_POST['middle_name'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label for="suffix">Suffix</label>
                        <input type="text" id="suffix" name="suffix" pattern="[a-zA-Z\s,.-]*"
                            title="Only letters, spaces, commas, periods, and hyphens allowed"
                            placeholder="Jr., Sr., III, etc." value="<?= htmlspecialchars($_POST['suffix'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label for="sex">Sex <span class="required">*</span></label>
                        <select id="sex" name="sex" required>
                            <option value="">Select Sex</option>
                            <option value="Male" <?= (($_POST['sex'] ?? '') === 'Male') ? 'selected' : '' ?>>Male
                            </option>
                            <option value="Female" <?= (($_POST['sex'] ?? '') === 'Female') ? 'selected' : '' ?>>Female
                            </option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="course">Course <span class="required">*</span></label>
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
                        <label for="year_graduated">Year Graduated <span class="required">*</span></label>
                        <input type="number" id="year_graduated" name="year_graduated" required min="1950"
                            max="<?= date('Y') ?>" value="<?= htmlspecialchars($_POST['year_graduated'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label for="board_exam_date">Board Exam Date <span class="required">*</span></label>
                        <input type="date" id="board_exam_date" name="board_exam_date" required
                            value="<?= htmlspecialchars($_POST['board_exam_date'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label for="result">Result <span class="required">*</span></label>
                        <select id="result" name="result" required>
                            <option value="">Select Result</option>
                            <option value="Passed" <?= (($_POST['result'] ?? '') === 'Passed') ? 'selected' : '' ?>>
                                Passed</option>
                            <option value="Failed" <?= (($_POST['result'] ?? '') === 'Failed') ? 'selected' : '' ?>>
                                Failed</option>
                            <option value="Conditional"
                                <?= (($_POST['result'] ?? '') === 'Conditional') ? 'selected' : '' ?>>Conditional
                            </option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="exam_type">Exam Type <span class="required">*</span></label>
                        <select id="exam_type" name="exam_type" required>
                            <option value="">Select Exam Type</option>
                            <option value="First Timer"
                                <?= (($_POST['exam_type'] ?? '') === 'First Timer') ? 'selected' : '' ?>>First Timer
                            </option>
                            <option value="Repeater"
                                <?= (($_POST['exam_type'] ?? '') === 'Repeater') ? 'selected' : '' ?>>Repeater</option>
                        </select>
                    </div>

                    <div class="form-group full-width">
                        <label for="board_exam_type">Board Exam Type <span class="required">*</span></label>
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