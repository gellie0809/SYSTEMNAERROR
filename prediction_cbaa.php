<?php
session_start();

// Allow CBAA admin or ICTS admin
if (!isset($_SESSION["users"]) || ($_SESSION["users"] !== 'cbaa_admin@lspu.edu.ph' && $_SESSION["users"] !== 'icts_admin@lspu.edu.ph')) {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CBAA Prediction - Coming Soon</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 24px;
            padding: 60px 40px;
            max-width: 600px;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
        }
        .icon {
            font-size: 80px;
            margin-bottom: 20px;
        }
        h1 {
            font-size: 2rem;
            color: #1e293b;
            margin-bottom: 16px;
        }
        p {
            font-size: 1.1rem;
            color: #64748b;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        .btn {
            display: inline-block;
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
            padding: 14px 32px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            margin: 5px;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(245, 158, 11, 0.4);
        }
        .btn-secondary {
            background: linear-gradient(135deg, #e2e8f0 0%, #cbd5e1 100%);
            color: #475569;
        }
        .btn-secondary:hover {
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">🚧</div>
        <h1>AI Predictions - Coming Soon</h1>
        <p>The AI Board Exam Prediction feature for Business Administration and Accountancy is currently under development. Please check back later.</p>
        <div>
            <a href="dashboard_cbaa.php" class="btn">Back to Dashboard</a>
            <a href="dashboard_icts.php?dept=CBAA" class="btn btn-secondary">ICTS Dashboard</a>
        </div>
    </div>
</body>
</html>
