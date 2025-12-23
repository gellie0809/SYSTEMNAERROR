<?php
session_start();

// Suppress deprecation warnings for production
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
ini_set('display_errors', 0);

// Only allow ICTS admin
if (!isset($_SESSION["users"]) || $_SESSION["users"] !== 'icts_admin@lspu.edu.ph') {
    header("Location: index.php");
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "project_db";
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

// Handle form submissions
$message = '';
$message_type = '';

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Add new user
    if (isset($_POST['action']) && $_POST['action'] === 'add_user') {
        $email = trim($_POST['email']);
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if (empty($email) || empty($new_password)) {
            $message = 'Email and password are required.';
            $message_type = 'error';
        } elseif ($new_password !== $confirm_password) {
            $message = 'Passwords do not match.';
            $message_type = 'error';
        } elseif (strlen($new_password) < 6) {
            $message = 'Password must be at least 6 characters.';
            $message_type = 'error';
        } else {
            // Check if user already exists
            $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $check->bind_param("s", $email);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                $message = 'User with this email already exists.';
                $message_type = 'error';
            } else {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (email, password) VALUES (?, ?)");
                $stmt->bind_param("ss", $email, $hashed_password);
                if ($stmt->execute()) {
                    $message = 'User added successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Error adding user.';
                    $message_type = 'error';
                }
            }
        }
    }
    
    // Reset user password
    if (isset($_POST['action']) && $_POST['action'] === 'reset_password') {
        $user_id = intval($_POST['user_id']);
        $new_password = $_POST['reset_password'];
        $confirm_password = $_POST['reset_confirm_password'];
        
        if (empty($new_password)) {
            $message = 'Password is required.';
            $message_type = 'error';
        } elseif ($new_password !== $confirm_password) {
            $message = 'Passwords do not match.';
            $message_type = 'error';
        } elseif (strlen($new_password) < 6) {
            $message = 'Password must be at least 6 characters.';
            $message_type = 'error';
        } else {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $hashed_password, $user_id);
            if ($stmt->execute()) {
                $message = 'Password reset successfully!';
                $message_type = 'success';
            } else {
                $message = 'Error resetting password.';
                $message_type = 'error';
            }
        }
    }
    
    // Delete user
    if (isset($_POST['action']) && $_POST['action'] === 'delete_user') {
        $user_id = intval($_POST['user_id']);
        
        // Prevent deleting ICTS admin
        $check = $conn->prepare("SELECT email FROM users WHERE id = ?");
        $check->bind_param("i", $user_id);
        $check->execute();
        $result = $check->get_result();
        $user = $result->fetch_assoc();
        
        if ($user && $user['email'] === 'icts_admin@lspu.edu.ph') {
            $message = 'Cannot delete ICTS admin account.';
            $message_type = 'error';
        } else {
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            if ($stmt->execute()) {
                $message = 'User deleted successfully!';
                $message_type = 'success';
            } else {
                $message = 'Error deleting user.';
                $message_type = 'error';
            }
        }
    }
    
    // Clean duplicate users
    if (isset($_POST['action']) && $_POST['action'] === 'clean_duplicates') {
        // Keep only the latest user for each email
        $result = $conn->query("SELECT email, MAX(id) as keep_id FROM users GROUP BY email HAVING COUNT(*) > 1");
        $cleaned = 0;
        while ($row = $result->fetch_assoc()) {
            $email = $row['email'];
            $keep_id = $row['keep_id'];
            $delete_stmt = $conn->prepare("DELETE FROM users WHERE email = ? AND id != ?");
            $delete_stmt->bind_param("si", $email, $keep_id);
            $delete_stmt->execute();
            $cleaned += $delete_stmt->affected_rows;
        }
        $message = "Cleaned $cleaned duplicate user records.";
        $message_type = 'success';
    }
    
    // Database backup
    if (isset($_POST['action']) && $_POST['action'] === 'backup_database') {
        $backup_dir = __DIR__ . '/database_backups';
        if (!file_exists($backup_dir)) {
            mkdir($backup_dir, 0777, true);
        }
        
        $tables = ['users', 'board_passers', 'anonymous_board_passers', 'board_exam_types', 'board_exam_dates', 'courses', 'subjects', 'subject_exam_types', 'board_passer_subjects'];
        $backup_file = $backup_dir . '/backup_' . date('Y-m-d_H-i-s') . '.sql';
        
        $backup_content = "-- LSPU Board Performance System Database Backup\n";
        $backup_content .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
        $backup_content .= "-- Database: $dbname\n\n";
        
        foreach ($tables as $table) {
            $result = $conn->query("SELECT * FROM $table");
            if ($result && $result->num_rows > 0) {
                $backup_content .= "-- Table: $table\n";
                $backup_content .= "TRUNCATE TABLE `$table`;\n";
                
                while ($row = $result->fetch_assoc()) {
                    $values = array_map(function($v) use ($conn) {
                        return $v === null ? 'NULL' : "'" . $conn->real_escape_string($v) . "'";
                    }, array_values($row));
                    $columns = implode('`, `', array_keys($row));
                    $backup_content .= "INSERT INTO `$table` (`$columns`) VALUES (" . implode(', ', $values) . ");\n";
                }
                $backup_content .= "\n";
            }
        }
        
        if (file_put_contents($backup_file, $backup_content)) {
            $message = 'Database backup created successfully: ' . basename($backup_file);
            $message_type = 'success';
        } else {
            $message = 'Error creating backup file.';
            $message_type = 'error';
        }
    }
}

// Get unique users (latest entry for each email)
$users_query = "SELECT u1.* FROM users u1 
                INNER JOIN (SELECT email, MAX(id) as max_id FROM users GROUP BY email) u2 
                ON u1.email = u2.email AND u1.id = u2.max_id 
                ORDER BY u1.email";
$users = $conn->query($users_query);

// Get database statistics
$stats = [];
$tables_to_check = [
    'users' => 'Admin Users',
    'board_passers' => 'Board Passers',
    'anonymous_board_passers' => 'Anonymous Records',
    'board_exam_types' => 'Board Exam Types',
    'courses' => 'Courses',
    'subjects' => 'Subjects'
];

foreach ($tables_to_check as $table => $label) {
    $result = $conn->query("SELECT COUNT(*) as count FROM $table");
    $stats[$table] = ['label' => $label, 'count' => $result ? $result->fetch_assoc()['count'] : 0];
}

// Get backup files
$backup_files = [];
$backup_dir = __DIR__ . '/database_backups';
if (file_exists($backup_dir)) {
    $files = glob($backup_dir . '/*.sql');
    foreach ($files as $file) {
        $backup_files[] = [
            'name' => basename($file),
            'size' => filesize($file),
            'date' => filemtime($file)
        ];
    }
    usort($backup_files, function($a, $b) { return $b['date'] - $a['date']; });
}

// Department user mapping
$dept_users = [
    'eng_admin@lspu.edu.ph' => ['name' => 'Engineering Admin', 'dept' => 'Engineering', 'color' => '#16a34a'],
    'cas_admin@lspu.edu.ph' => ['name' => 'CAS Admin', 'dept' => 'Arts & Sciences', 'color' => '#ec4899'],
    'cbaa_admin@lspu.edu.ph' => ['name' => 'CBAA Admin', 'dept' => 'Business & Accountancy', 'color' => '#f59e0b'],
    'ccje_admin@lspu.edu.ph' => ['name' => 'CCJE Admin', 'dept' => 'Criminal Justice', 'color' => '#dc2626'],
    'cte_admin@lspu.edu.ph' => ['name' => 'CTE Admin', 'dept' => 'Teacher Education', 'color' => '#3b82f6'],
    'icts_admin@lspu.edu.ph' => ['name' => 'ICTS Admin', 'dept' => 'ICTS', 'color' => '#6366f1'],
    'president@lspu.edu.ph' => ['name' => 'President', 'dept' => 'Executive', 'color' => '#8b5cf6']
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings - ICTS Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Merriweather:wght@700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: #0f172a;
            min-height: 100vh;
            color: #e2e8f0;
        }
        
        /* Sidebar */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 220px;
            height: 100vh;
            background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
            border-right: 1px solid rgba(255,255,255,0.1);
            padding: 20px 0;
            z-index: 1000;
        }
        .logo {
            text-align: center;
            font-family: 'Merriweather', serif;
            font-size: 1.5rem;
            font-weight: 700;
            color: #fff;
            padding: 0 20px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 20px;
        }
        .sidebar-nav a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 20px;
            color: #94a3b8;
            text-decoration: none;
            transition: all 0.3s;
            font-size: 0.9rem;
        }
        .sidebar-nav a:hover, .sidebar-nav a.active {
            background: rgba(99, 102, 241, 0.1);
            color: #fff;
            border-left: 3px solid #6366f1;
        }
        .sidebar-nav a i { width: 20px; text-align: center; }
        
        /* Main Content */
        .main-content {
            margin-left: 220px;
            padding: 20px;
            min-height: 100vh;
        }
        
        /* Topbar */
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .topbar h1 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #fff;
        }
        .topbar-actions {
            display: flex;
            gap: 12px;
        }
        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
            text-decoration: none;
        }
        .btn-primary { background: linear-gradient(135deg, #6366f1, #818cf8); color: #fff; }
        .btn-success { background: linear-gradient(135deg, #16a34a, #22c55e); color: #fff; }
        .btn-danger { background: linear-gradient(135deg, #dc2626, #ef4444); color: #fff; }
        .btn-secondary { background: #475569; color: #fff; }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.3); }
        
        /* Alert Messages */
        .alert {
            padding: 14px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.9rem;
        }
        .alert-success { background: rgba(34, 197, 94, 0.15); border: 1px solid rgba(34, 197, 94, 0.3); color: #22c55e; }
        .alert-error { background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.3); color: #ef4444; }
        
        /* Cards */
        .card {
            background: #1e293b;
            border-radius: 12px;
            padding: 20px;
            border: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 20px;
        }
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .card-header h3 {
            color: #fff;
            font-size: 1.1rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .card-header h3 i { color: #60a5fa; }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 16px;
            margin-bottom: 20px;
        }
        .stat-card {
            background: linear-gradient(135deg, #334155, #1e293b);
            padding: 16px;
            border-radius: 10px;
            text-align: center;
            border: 1px solid rgba(255,255,255,0.05);
        }
        .stat-card .label {
            color: #94a3b8;
            font-size: 0.75rem;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 4px;
        }
        .stat-card .value {
            color: #fff;
            font-size: 1.5rem;
            font-weight: 700;
        }
        
        /* Users Table */
        .users-table {
            width: 100%;
            border-collapse: collapse;
        }
        .users-table th, .users-table td {
            padding: 12px 16px;
            text-align: left;
        }
        .users-table th {
            background: linear-gradient(135deg, #334155, #1e293b);
            color: #fff;
            font-weight: 600;
            font-size: 0.8rem;
            text-transform: uppercase;
        }
        .users-table th:first-child { border-radius: 8px 0 0 0; }
        .users-table th:last-child { border-radius: 0 8px 0 0; }
        .users-table td {
            border-bottom: 1px solid rgba(255,255,255,0.05);
            font-size: 0.9rem;
        }
        .users-table tr:hover td { background: rgba(99, 102, 241, 0.05); }
        
        .user-badge {
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        /* Modal */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.7);
            z-index: 2000;
            justify-content: center;
            align-items: center;
        }
        .modal-overlay.active { display: flex; }
        .modal {
            background: #1e293b;
            border-radius: 16px;
            padding: 24px;
            width: 100%;
            max-width: 450px;
            border: 1px solid rgba(255,255,255,0.1);
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .modal-header h3 { color: #fff; font-size: 1.1rem; }
        .modal-close {
            background: none;
            border: none;
            color: #94a3b8;
            font-size: 1.25rem;
            cursor: pointer;
        }
        .modal-close:hover { color: #fff; }
        
        /* Form */
        .form-group {
            margin-bottom: 16px;
        }
        .form-group label {
            display: block;
            color: #94a3b8;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 6px;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 12px 14px;
            background: #0f172a;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 8px;
            color: #fff;
            font-size: 0.9rem;
        }
        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: #6366f1;
        }
        
        /* Backup Files */
        .backup-list {
            max-height: 200px;
            overflow-y: auto;
        }
        .backup-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 14px;
            background: #0f172a;
            border-radius: 8px;
            margin-bottom: 8px;
        }
        .backup-item .name {
            color: #fff;
            font-size: 0.85rem;
            font-weight: 500;
        }
        .backup-item .meta {
            color: #64748b;
            font-size: 0.75rem;
        }
        
        /* Tabs */
        .tabs {
            display: flex;
            gap: 8px;
            margin-bottom: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            padding-bottom: 12px;
        }
        .tab {
            padding: 10px 20px;
            background: transparent;
            border: none;
            color: #94a3b8;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            border-radius: 8px;
            transition: all 0.3s;
        }
        .tab:hover { background: rgba(99, 102, 241, 0.1); color: #fff; }
        .tab.active { background: #6366f1; color: #fff; }
        
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        
        /* Action buttons in table */
        .action-btn {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            border: none;
            margin-right: 4px;
            transition: all 0.2s;
        }
        .action-btn:hover { transform: scale(1.05); }
        .action-btn-reset { background: rgba(59, 130, 246, 0.2); color: #60a5fa; }
        .action-btn-delete { background: rgba(239, 68, 68, 0.2); color: #ef4444; }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="logo">LSPU<br><span style="font-size:9px;font-weight:400;">ICTS Admin</span></div>
        <div class="sidebar-nav">
            <a href="dashboard_icts.php?dept=all">
                <i class="fas fa-chart-pie"></i> <span>All Departments</span>
            </a>
            <a href="dashboard_icts.php?dept=Engineering">
                <i class="fas fa-cogs"></i> <span>Engineering</span>
            </a>
            <a href="dashboard_icts.php?dept=CAS">
                <i class="fas fa-flask"></i> <span>Arts & Sciences</span>
            </a>
            <a href="dashboard_icts.php?dept=CBAA">
                <i class="fas fa-briefcase"></i> <span>Business & Accountancy</span>
            </a>
            <a href="dashboard_icts.php?dept=CCJE">
                <i class="fas fa-gavel"></i> <span>Criminal Justice</span>
            </a>
            <a href="dashboard_icts.php?dept=CTE">
                <i class="fas fa-chalkboard-teacher"></i> <span>Teacher Education</span>
            </a>
            <a href="manage_system_settings.php" class="active">
                <i class="fas fa-cog"></i> <span>System Settings</span>
            </a>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Topbar -->
        <div class="topbar">
            <h1><i class="fas fa-cog" style="color: #6366f1; margin-right: 12px;"></i>System Settings</h1>
            <div class="topbar-actions">
                <a href="dashboard_icts.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
                <a href="logout.php" class="btn btn-danger">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
        
        <?php if ($message): ?>
        <div class="alert alert-<?= $message_type ?>">
            <i class="fas fa-<?= $message_type === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
            <?= htmlspecialchars($message) ?>
        </div>
        <?php endif; ?>
        
        <!-- Database Statistics -->
        <div class="stats-grid">
            <?php foreach ($stats as $key => $stat): ?>
            <div class="stat-card">
                <div class="label"><?= $stat['label'] ?></div>
                <div class="value"><?= number_format($stat['count']) ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Tabs -->
        <div class="tabs">
            <button class="tab active" onclick="switchTab('users')">
                <i class="fas fa-users"></i> User Management
            </button>
            <button class="tab" onclick="switchTab('database')">
                <i class="fas fa-database"></i> Database
            </button>
            <button class="tab" onclick="switchTab('maintenance')">
                <i class="fas fa-tools"></i> Maintenance
            </button>
        </div>
        
        <!-- User Management Tab -->
        <div id="tab-users" class="tab-content active">
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-users"></i> Admin Users</h3>
                    <button class="btn btn-primary" onclick="openModal('addUserModal')">
                        <i class="fas fa-plus"></i> Add User
                    </button>
                </div>
                
                <div style="overflow-x: auto;">
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Department</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($user = $users->fetch_assoc()): 
                                $user_info = $dept_users[$user['email']] ?? ['name' => 'Unknown', 'dept' => 'N/A', 'color' => '#64748b'];
                            ?>
                            <tr>
                                <td style="color: #64748b;"><?= $user['id'] ?></td>
                                <td style="color: #fff;"><?= htmlspecialchars($user['email']) ?></td>
                                <td>
                                    <span class="user-badge" style="background: <?= $user_info['color'] ?>20; color: <?= $user_info['color'] ?>;">
                                        <?= $user_info['name'] ?>
                                    </span>
                                </td>
                                <td style="color: #94a3b8;"><?= $user_info['dept'] ?></td>
                                <td>
                                    <button class="action-btn action-btn-reset" onclick="openResetModal(<?= $user['id'] ?>, '<?= htmlspecialchars($user['email']) ?>')">
                                        <i class="fas fa-key"></i> Reset
                                    </button>
                                    <?php if ($user['email'] !== 'icts_admin@lspu.edu.ph'): ?>
                                    <button class="action-btn action-btn-delete" onclick="confirmDelete(<?= $user['id'] ?>, '<?= htmlspecialchars($user['email']) ?>')">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Database Tab -->
        <div id="tab-database" class="tab-content">
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-database"></i> Database Backup</h3>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="backup_database">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-download"></i> Create Backup
                        </button>
                    </form>
                </div>
                
                <h4 style="color: #94a3b8; font-size: 0.85rem; margin-bottom: 12px;">Recent Backups</h4>
                
                <?php if (empty($backup_files)): ?>
                <div style="text-align: center; padding: 30px; color: #64748b;">
                    <i class="fas fa-folder-open" style="font-size: 2rem; margin-bottom: 12px; display: block;"></i>
                    <p>No backup files found.</p>
                </div>
                <?php else: ?>
                <div class="backup-list">
                    <?php foreach (array_slice($backup_files, 0, 10) as $file): ?>
                    <div class="backup-item">
                        <div>
                            <div class="name"><i class="fas fa-file-code" style="color: #6366f1; margin-right: 8px;"></i><?= $file['name'] ?></div>
                            <div class="meta"><?= date('M d, Y H:i', $file['date']) ?> • <?= number_format($file['size'] / 1024, 1) ?> KB</div>
                        </div>
                        <a href="database_backups/<?= $file['name'] ?>" download class="btn btn-secondary" style="padding: 6px 12px; font-size: 0.75rem;">
                            <i class="fas fa-download"></i>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-table"></i> Database Tables</h3>
                </div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px;">
                    <?php foreach ($stats as $key => $stat): ?>
                    <div style="background: #0f172a; padding: 14px; border-radius: 8px; display: flex; justify-content: space-between; align-items: center;">
                        <span style="color: #fff; font-size: 0.85rem;"><?= $key ?></span>
                        <span style="color: #6366f1; font-weight: 600;"><?= number_format($stat['count']) ?> rows</span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <!-- Maintenance Tab -->
        <div id="tab-maintenance" class="tab-content">
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-broom"></i> Data Cleanup</h3>
                </div>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px;">
                    <div style="background: #0f172a; padding: 20px; border-radius: 10px;">
                        <h4 style="color: #fff; font-size: 0.95rem; margin-bottom: 8px;">
                            <i class="fas fa-user-times" style="color: #f59e0b; margin-right: 8px;"></i>Remove Duplicate Users
                        </h4>
                        <p style="color: #64748b; font-size: 0.8rem; margin-bottom: 12px;">
                            Clean up duplicate user accounts, keeping only the latest entry for each email.
                        </p>
                        <form method="POST" onsubmit="return confirm('Are you sure you want to remove duplicate users?');">
                            <input type="hidden" name="action" value="clean_duplicates">
                            <button type="submit" class="btn btn-secondary" style="width: 100%;">
                                <i class="fas fa-broom"></i> Clean Duplicates
                            </button>
                        </form>
                    </div>
                    
                    <div style="background: #0f172a; padding: 20px; border-radius: 10px;">
                        <h4 style="color: #fff; font-size: 0.95rem; margin-bottom: 8px;">
                            <i class="fas fa-info-circle" style="color: #3b82f6; margin-right: 8px;"></i>System Information
                        </h4>
                        <div style="color: #94a3b8; font-size: 0.8rem;">
                            <p style="margin-bottom: 6px;"><strong>PHP Version:</strong> <?= phpversion() ?></p>
                            <p style="margin-bottom: 6px;"><strong>MySQL:</strong> <?= $conn->server_info ?></p>
                            <p style="margin-bottom: 6px;"><strong>Server:</strong> <?= $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown' ?></p>
                            <p><strong>Database:</strong> <?= $dbname ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-shield-alt"></i> Default Admin Credentials</h3>
                </div>
                <p style="color: #64748b; font-size: 0.85rem; margin-bottom: 16px;">
                    Reference for department admin accounts. Use "Reset Password" to change passwords.
                </p>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 12px;">
                    <?php foreach ($dept_users as $email => $info): ?>
                    <div style="background: #0f172a; padding: 12px 16px; border-radius: 8px; border-left: 3px solid <?= $info['color'] ?>;">
                        <div style="color: #fff; font-size: 0.85rem; font-weight: 600;"><?= $info['name'] ?></div>
                        <div style="color: #64748b; font-size: 0.8rem;"><?= $email ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add User Modal -->
    <div class="modal-overlay" id="addUserModal">
        <div class="modal">
            <div class="modal-header">
                <h3><i class="fas fa-user-plus" style="color: #6366f1; margin-right: 8px;"></i>Add New User</h3>
                <button class="modal-close" onclick="closeModal('addUserModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add_user">
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" required placeholder="user@example.com">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="new_password" required minlength="6" placeholder="Minimum 6 characters">
                </div>
                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" name="confirm_password" required placeholder="Confirm password">
                </div>
                <div style="display: flex; gap: 12px; margin-top: 20px;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addUserModal')" style="flex: 1;">Cancel</button>
                    <button type="submit" class="btn btn-primary" style="flex: 1;">Add User</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Reset Password Modal -->
    <div class="modal-overlay" id="resetPasswordModal">
        <div class="modal">
            <div class="modal-header">
                <h3><i class="fas fa-key" style="color: #3b82f6; margin-right: 8px;"></i>Reset Password</h3>
                <button class="modal-close" onclick="closeModal('resetPasswordModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="reset_password">
                <input type="hidden" name="user_id" id="reset_user_id">
                <div class="form-group">
                    <label>User</label>
                    <input type="text" id="reset_user_email" disabled style="background: #334155;">
                </div>
                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="reset_password" required minlength="6" placeholder="Minimum 6 characters">
                </div>
                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" name="reset_confirm_password" required placeholder="Confirm password">
                </div>
                <div style="display: flex; gap: 12px; margin-top: 20px;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('resetPasswordModal')" style="flex: 1;">Cancel</button>
                    <button type="submit" class="btn btn-primary" style="flex: 1;">Reset Password</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Delete User Modal -->
    <div class="modal-overlay" id="deleteUserModal">
        <div class="modal">
            <div class="modal-header">
                <h3><i class="fas fa-exclamation-triangle" style="color: #ef4444; margin-right: 8px;"></i>Delete User</h3>
                <button class="modal-close" onclick="closeModal('deleteUserModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="delete_user">
                <input type="hidden" name="user_id" id="delete_user_id">
                <p style="color: #94a3b8; margin-bottom: 16px;">
                    Are you sure you want to delete user <strong id="delete_user_email" style="color: #fff;"></strong>?
                </p>
                <p style="color: #ef4444; font-size: 0.85rem; margin-bottom: 20px;">
                    <i class="fas fa-warning"></i> This action cannot be undone.
                </p>
                <div style="display: flex; gap: 12px;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('deleteUserModal')" style="flex: 1;">Cancel</button>
                    <button type="submit" class="btn btn-danger" style="flex: 1;">Delete User</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function switchTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
            document.querySelectorAll('.tab').forEach(btn => btn.classList.remove('active'));
            
            // Show selected tab
            document.getElementById('tab-' + tabName).classList.add('active');
            event.target.closest('.tab').classList.add('active');
        }
        
        function openModal(modalId) {
            document.getElementById(modalId).classList.add('active');
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }
        
        function openResetModal(userId, email) {
            document.getElementById('reset_user_id').value = userId;
            document.getElementById('reset_user_email').value = email;
            openModal('resetPasswordModal');
        }
        
        function confirmDelete(userId, email) {
            document.getElementById('delete_user_id').value = userId;
            document.getElementById('delete_user_email').textContent = email;
            openModal('deleteUserModal');
        }
        
        // Close modal on outside click
        document.querySelectorAll('.modal-overlay').forEach(overlay => {
            overlay.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.remove('active');
                }
            });
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>
