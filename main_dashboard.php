<?php
// MAIN DASHBOARD - University-Wide Board Exam Analytics
session_start();

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "project_db";
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

// Define departments with their display names and colors
$departments = [
    'Engineering' => ['name' => 'College of Engineering', 'abbr' => 'COE', 'color' => '#5B7B5A', 'icon' => '⚙️', 'light' => '#D6E5D6'],
    'Arts and Sciences' => ['name' => 'College of Arts and Sciences', 'abbr' => 'CAS', 'color' => '#BF3853', 'icon' => '🔬', 'light' => '#FDB3C2'],
    'Business Administration and Accountancy' => ['name' => 'College of Business Administration', 'abbr' => 'CBAA', 'color' => '#D97706', 'icon' => '📊', 'light' => '#FCD34D'],
    'Criminal Justice Education' => ['name' => 'College of Criminal Justice Education', 'abbr' => 'CCJE', 'color' => '#7F1D1D', 'icon' => '🛡️', 'light' => '#FEE2E2'],
    'Teacher Education' => ['name' => 'College of Teacher Education', 'abbr' => 'CTE', 'color' => '#1D4ED8', 'icon' => '📚', 'light' => '#DBEAFE']
];

// Initialize university-wide stats
$university_stats = [
    'total' => 0, 'passed' => 0, 'failed' => 0, 'conditional' => 0,
    'first_timer' => 0, 'repeater' => 0, 'first_timer_passed' => 0, 'repeater_passed' => 0
];
$department_stats = [];
$yearly_data = [];
$department_yearly = [];
$board_exam_types = [];

// Fetch all data
$query = "SELECT * FROM anonymous_board_passers WHERE (is_deleted IS NULL OR is_deleted = 0) ORDER BY board_exam_date DESC";
$result = $conn->query($query);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $dept = $row['department'] ?? 'Unknown';
        $year = date('Y', strtotime($row['board_exam_date']));
        $res = $row['result'];
        $attempt = $row['exam_type'] ?? 'Unknown';
        $exam_type = $row['board_exam_type'] ?? 'Unknown';
        
        // University-wide stats
        $university_stats['total']++;
        if ($res === 'Passed') $university_stats['passed']++;
        elseif ($res === 'Failed') $university_stats['failed']++;
        elseif ($res === 'Conditional') $university_stats['conditional']++;
        
        if ($attempt === 'First Timer') {
            $university_stats['first_timer']++;
            if ($res === 'Passed') $university_stats['first_timer_passed']++;
        } elseif ($attempt === 'Repeater') {
            $university_stats['repeater']++;
            if ($res === 'Passed') $university_stats['repeater_passed']++;
        }
        
        // Department stats
        if (!isset($department_stats[$dept])) {
            $department_stats[$dept] = ['total' => 0, 'passed' => 0, 'failed' => 0, 'first_timer' => 0, 'repeater' => 0, 'ft_passed' => 0, 'rp_passed' => 0];
        }
        $department_stats[$dept]['total']++;
        if ($res === 'Passed') $department_stats[$dept]['passed']++;
        else $department_stats[$dept]['failed']++;
        if ($attempt === 'First Timer') { $department_stats[$dept]['first_timer']++; if ($res === 'Passed') $department_stats[$dept]['ft_passed']++; }
        if ($attempt === 'Repeater') { $department_stats[$dept]['repeater']++; if ($res === 'Passed') $department_stats[$dept]['rp_passed']++; }
        
        // Yearly data (university-wide)
        if (!isset($yearly_data[$year])) $yearly_data[$year] = ['passed' => 0, 'failed' => 0, 'total' => 0];
        $yearly_data[$year]['total']++;
        if ($res === 'Passed') $yearly_data[$year]['passed']++; else $yearly_data[$year]['failed']++;
        
        // Department yearly data
        if (!isset($department_yearly[$dept])) $department_yearly[$dept] = [];
        if (!isset($department_yearly[$dept][$year])) $department_yearly[$dept][$year] = ['passed' => 0, 'failed' => 0, 'total' => 0];
        $department_yearly[$dept][$year]['total']++;
        if ($res === 'Passed') $department_yearly[$dept][$year]['passed']++; else $department_yearly[$dept][$year]['failed']++;
        
        // Board exam types
        if (!isset($board_exam_types[$exam_type])) $board_exam_types[$exam_type] = ['total' => 0, 'passed' => 0, 'dept' => $dept];
        $board_exam_types[$exam_type]['total']++;
        if ($res === 'Passed') $board_exam_types[$exam_type]['passed']++;
    }
}

ksort($yearly_data);
foreach ($department_yearly as &$years) ksort($years);

// Calculate rates
$pass_rate = $university_stats['total'] > 0 ? round(($university_stats['passed'] / $university_stats['total']) * 100, 1) : 0;
$ft_rate = $university_stats['first_timer'] > 0 ? round(($university_stats['first_timer_passed'] / $university_stats['first_timer']) * 100, 1) : 0;
$rp_rate = $university_stats['repeater'] > 0 ? round(($university_stats['repeater_passed'] / $university_stats['repeater']) * 100, 1) : 0;

// Get year range
$years_list = array_keys($yearly_data);
$year_range = count($years_list) > 0 ? min($years_list) . ' - ' . max($years_list) : 'N/A';

// Top performing department
$top_dept = '';
$top_rate = 0;
$lowest_dept = '';
$lowest_rate = 100;
foreach ($department_stats as $dept => $stats) {
    $rate = $stats['total'] > 0 ? ($stats['passed'] / $stats['total']) * 100 : 0;
    if ($rate > $top_rate) { $top_rate = $rate; $top_dept = $dept; }
    if ($rate < $lowest_rate && $stats['total'] > 0) { $lowest_rate = $rate; $lowest_dept = $dept; }
}

// Calculate trend (comparing recent years)
$yearly_rates = [];
foreach ($yearly_data as $year => $data) {
    $yearly_rates[$year] = $data['total'] > 0 ? round(($data['passed'] / $data['total']) * 100, 1) : 0;
}
$recent_years = array_slice($yearly_rates, -3, 3, true); // Last 3 years
$trend_direction = 'stable';
$trend_value = 0;
if (count($recent_years) >= 2) {
    $values = array_values($recent_years);
    $trend_value = end($values) - reset($values);
    if ($trend_value > 2) $trend_direction = 'improving';
    elseif ($trend_value < -2) $trend_direction = 'declining';
}

// Predict next year pass rate using simple linear regression
$predicted_rate = $pass_rate;
if (count($yearly_rates) >= 2) {
    $x_vals = array_keys($yearly_rates);
    $y_vals = array_values($yearly_rates);
    $n = count($x_vals);
    $sum_x = array_sum($x_vals);
    $sum_y = array_sum($y_vals);
    $sum_xy = 0;
    $sum_xx = 0;
    for ($i = 0; $i < $n; $i++) {
        $sum_xy += $x_vals[$i] * $y_vals[$i];
        $sum_xx += $x_vals[$i] * $x_vals[$i];
    }
    $slope = ($n * $sum_xy - $sum_x * $sum_y) / ($n * $sum_xx - $sum_x * $sum_x);
    $intercept = ($sum_y - $slope * $sum_x) / $n;
    $next_year = max($x_vals) + 1;
    $predicted_rate = round($slope * $next_year + $intercept, 1);
    $predicted_rate = max(0, min(100, $predicted_rate)); // Clamp between 0-100
}

// Calculate average examinees per year
$avg_examinees = count($yearly_data) > 0 ? round($university_stats['total'] / count($yearly_data)) : 0;

// Calculate improvement potential
$improvement_potential = 100 - $pass_rate;

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <title>LSPU Board Examination Analytics | University Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
    <style>
        :root {
            --primary: #0f172a;
            --primary-light: #1e293b;
            --accent: #3b82f6;
            --accent-light: #60a5fa;
            --success: #22c55e;
            --warning: #f59e0b;
            --danger: #ef4444;
            --eng: #5B7B5A;
            --cas: #BF3853;
            --cbaa: #D97706;
            --ccje: #7F1D1D;
            --cte: #1D4ED8;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --bg-light: #f8fafc;
            --white: #ffffff;
            --border: #e2e8f0;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.1);
        }
        
        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            color: var(--text-primary);
            min-height: 100vh;
            line-height: 1.6;
        }
        
        /* Header */
        .header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: var(--shadow-lg);
        }
        
        .header-top {
            background: rgba(0,0,0,0.2);
            padding: 8px 24px;
            font-size: 0.8rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .header-top a {
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: color 0.2s;
        }
        
        .header-top a:hover { color: white; }
        
        .header-main {
            padding: 20px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .header-brand {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        
        .header-logo {
            width: 56px;
            height: 56px;
            background: rgba(255,255,255,0.15);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            backdrop-filter: blur(10px);
        }
        
        .header-title h1 {
            font-size: 1.5rem;
            font-weight: 800;
            margin-bottom: 2px;
        }
        
        .header-title p {
            font-size: 0.85rem;
            opacity: 0.85;
        }
        
        .header-nav {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        
        .nav-btn {
            padding: 10px 20px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .nav-btn.primary {
            background: linear-gradient(135deg, var(--accent), var(--accent-light));
            color: white;
            border: none;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.4);
        }
        
        .nav-btn.primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(59, 130, 246, 0.5);
        }
        
        .nav-btn.secondary {
            background: rgba(255,255,255,0.1);
            color: white;
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .nav-btn.secondary:hover {
            background: rgba(255,255,255,0.2);
        }
        
        /* Main Content */
        .main {
            max-width: 1600px;
            margin: 0 auto;
            padding: 32px 24px;
        }
        
        /* University KPIs */
        .kpi-section {
            margin-bottom: 40px;
        }
        
        .section-header {
            margin-bottom: 24px;
        }
        
        .section-header h2 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .section-header h2 i {
            color: var(--accent);
        }
        
        .section-header p {
            color: var(--text-secondary);
            margin-top: 4px;
        }
        
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
        }
        
        .kpi-card {
            background: var(--white);
            border-radius: 16px;
            padding: 24px;
            box-shadow: var(--shadow);
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }
        
        .kpi-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }
        
        .kpi-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
        }
        
        .kpi-card.total::before { background: linear-gradient(90deg, var(--accent), var(--accent-light)); }
        .kpi-card.passed::before { background: linear-gradient(90deg, #22c55e, #86efac); }
        .kpi-card.failed::before { background: linear-gradient(90deg, #ef4444, #fca5a5); }
        .kpi-card.rate::before { background: linear-gradient(90deg, #8b5cf6, #c4b5fd); }
        .kpi-card.first::before { background: linear-gradient(90deg, #14b8a6, #5eead4); }
        .kpi-card.repeat::before { background: linear-gradient(90deg, #f59e0b, #fcd34d); }
        
        .kpi-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            margin-bottom: 16px;
        }
        
        .kpi-card.total .kpi-icon { background: rgba(59, 130, 246, 0.1); color: var(--accent); }
        .kpi-card.passed .kpi-icon { background: rgba(34, 197, 94, 0.1); color: var(--success); }
        .kpi-card.failed .kpi-icon { background: rgba(239, 68, 68, 0.1); color: var(--danger); }
        .kpi-card.rate .kpi-icon { background: rgba(139, 92, 246, 0.1); color: #8b5cf6; }
        .kpi-card.first .kpi-icon { background: rgba(20, 184, 166, 0.1); color: #14b8a6; }
        .kpi-card.repeat .kpi-icon { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
        
        .kpi-value {
            font-size: 2rem;
            font-weight: 800;
            color: var(--text-primary);
            line-height: 1.2;
        }
        
        .kpi-label {
            font-size: 0.85rem;
            color: var(--text-secondary);
            margin-top: 4px;
        }
        
        .kpi-trend {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 0.75rem;
            font-weight: 600;
            padding: 4px 8px;
            border-radius: 20px;
            margin-top: 12px;
        }
        
        .kpi-trend.up { background: rgba(34, 197, 94, 0.1); color: var(--success); }
        .kpi-trend.down { background: rgba(239, 68, 68, 0.1); color: var(--danger); }
        .kpi-trend.neutral { background: rgba(100, 116, 139, 0.1); color: var(--text-secondary); }
        
        /* Department Cards */
        .dept-section {
            margin-bottom: 40px;
        }
        
        .dept-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 24px;
        }
        
        .dept-card {
            background: var(--white);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: var(--shadow);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            text-decoration: none;
            color: inherit;
            display: block;
        }
        
        .dept-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }
        
        .dept-card-header {
            padding: 24px;
            color: white;
            position: relative;
            overflow: hidden;
        }
        
        .dept-card-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
        }
        
        .dept-card-header .icon {
            font-size: 2.5rem;
            margin-bottom: 12px;
        }
        
        .dept-card-header h3 {
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 4px;
        }
        
        .dept-card-header .abbr {
            font-size: 0.8rem;
            opacity: 0.85;
            font-weight: 500;
        }
        
        .dept-card-body {
            padding: 24px;
        }
        
        .dept-stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
            margin-bottom: 20px;
        }
        
        .dept-stat {
            text-align: center;
        }
        
        .dept-stat-value {
            font-size: 1.5rem;
            font-weight: 800;
        }
        
        .dept-stat-label {
            font-size: 0.75rem;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .dept-progress {
            height: 8px;
            background: var(--border);
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 12px;
        }
        
        .dept-progress-fill {
            height: 100%;
            border-radius: 4px;
            transition: width 1s ease;
        }
        
        .dept-card-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 16px;
            border-top: 1px solid var(--border);
        }
        
        .view-details {
            font-size: 0.85rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: gap 0.3s;
        }
        
        .dept-card:hover .view-details {
            gap: 10px;
        }
        
        /* Charts Section */
        .charts-section {
            margin-bottom: 40px;
        }
        
        .charts-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 24px;
        }
        
        .chart-card {
            background: var(--white);
            border-radius: 20px;
            padding: 24px;
            box-shadow: var(--shadow);
        }
        
        .chart-card.full-width {
            grid-column: span 2;
        }
        
        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 12px;
        }
        
        .chart-title h3 {
            font-size: 1.1rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .chart-title h3 i {
            color: var(--accent);
        }
        
        .chart-title p {
            font-size: 0.85rem;
            color: var(--text-secondary);
            margin-top: 4px;
        }
        
        .chart-container {
            position: relative;
            height: 300px;
        }
        
        .chart-container.tall {
            height: 400px;
        }
        
        .chart-legend {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            margin-top: 16px;
            justify-content: center;
        }
        
        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.85rem;
            color: var(--text-secondary);
        }
        
        .legend-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
        }
        
        /* Predictions Section */
        .predictions-section {
            margin-bottom: 40px;
        }
        
        .predictions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }
        
        .prediction-card {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            border-radius: 20px;
            padding: 24px;
            color: white;
            position: relative;
            overflow: hidden;
        }
        
        .prediction-card::before {
            content: '';
            position: absolute;
            top: -30%;
            right: -30%;
            width: 60%;
            height: 60%;
            background: radial-gradient(circle, rgba(59, 130, 246, 0.3) 0%, transparent 70%);
        }
        
        .prediction-icon {
            width: 48px;
            height: 48px;
            background: rgba(255,255,255,0.15);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            margin-bottom: 16px;
        }
        
        .prediction-value {
            font-size: 2.2rem;
            font-weight: 800;
            margin-bottom: 4px;
        }
        
        .prediction-label {
            font-size: 0.9rem;
            opacity: 0.85;
            margin-bottom: 12px;
        }
        
        .prediction-status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.8rem;
            background: rgba(255,255,255,0.15);
            padding: 6px 12px;
            border-radius: 20px;
        }
        
        /* Comparison Table */
        .comparison-section {
            margin-bottom: 40px;
        }
        
        .comparison-card {
            background: var(--white);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: var(--shadow);
        }
        
        .comparison-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            padding: 20px 24px;
        }
        
        .comparison-header h3 {
            font-size: 1.1rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .comparison-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .comparison-table th,
        .comparison-table td {
            padding: 16px 20px;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }
        
        .comparison-table th {
            background: var(--bg-light);
            font-weight: 600;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-secondary);
        }
        
        .comparison-table tr:hover {
            background: var(--bg-light);
        }
        
        .comparison-table .dept-name {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .comparison-table .dept-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }
        
        .comparison-table .rate-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.85rem;
        }
        
        .comparison-table .mini-bar {
            width: 120px;
            height: 8px;
            background: var(--border);
            border-radius: 4px;
            overflow: hidden;
        }
        
        .comparison-table .mini-bar-fill {
            height: 100%;
            border-radius: 4px;
        }
        
        /* Footer */
        .footer {
            background: var(--primary);
            color: white;
            padding: 32px 24px;
            text-align: center;
        }
        
        .footer p {
            opacity: 0.85;
            font-size: 0.9rem;
        }
        
        /* Responsive */
        @media (max-width: 1200px) {
            .charts-grid {
                grid-template-columns: 1fr;
            }
            .chart-card.full-width {
                grid-column: span 1;
            }
        }
        
        @media (max-width: 768px) {
            .header-main {
                flex-direction: column;
                text-align: center;
            }
            .header-brand {
                flex-direction: column;
            }
            .kpi-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .dept-grid {
                grid-template-columns: 1fr;
            }
            .main {
                padding: 20px 16px;
            }
            .comparison-table {
                font-size: 0.85rem;
            }
            .comparison-table th,
            .comparison-table td {
                padding: 12px 10px;
            }
        }
        
        @media (max-width: 480px) {
            .kpi-grid {
                grid-template-columns: 1fr;
            }
        }
        
        /* Chart Info Button */
        .chart-info-btn {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            border: none;
            background: rgba(59, 130, 246, 0.1);
            color: var(--accent);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
            font-size: 0.85rem;
        }
        
        .chart-info-btn:hover {
            background: var(--accent);
            color: white;
            transform: scale(1.1);
        }
        
        /* Visualization Info Modal */
        .viz-info-modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(15, 23, 42, 0.7);
            backdrop-filter: blur(4px);
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .viz-info-modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .viz-info-content {
            background: white;
            border-radius: 20px;
            max-width: 500px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
            animation: slideUp 0.3s ease;
        }
        
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .viz-info-header {
            padding: 24px;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .viz-info-header h3 {
            font-size: 1.2rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--text-primary);
        }
        
        .viz-info-header h3 i {
            color: var(--accent);
        }
        
        .viz-info-close {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            border: none;
            background: var(--bg-light);
            color: var(--text-secondary);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
            font-size: 1.1rem;
        }
        
        .viz-info-close:hover {
            background: var(--danger);
            color: white;
        }
        
        .viz-info-body {
            padding: 24px;
        }
        
        .viz-type-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: linear-gradient(135deg, var(--accent), var(--accent-light));
            color: white;
            border-radius: 30px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 16px;
        }
        
        .viz-info-body h4 {
            font-size: 1rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 8px;
        }
        
        .viz-info-body p {
            color: var(--text-secondary);
            font-size: 0.9rem;
            line-height: 1.7;
            margin-bottom: 16px;
        }
        
        .viz-features {
            background: var(--bg-light);
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 16px;
        }
        
        .viz-features h5 {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .viz-features ul {
            margin: 0;
            padding-left: 20px;
        }
        
        .viz-features li {
            font-size: 0.85rem;
            color: var(--text-secondary);
            margin-bottom: 6px;
        }
        
        .viz-use-case {
            padding: 12px 16px;
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
            border-radius: 10px;
            border-left: 4px solid var(--accent);
        }
        
        .viz-use-case p {
            margin: 0;
            font-size: 0.85rem;
            color: #1e40af;
        }
        
        /* Animations */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .animate-in {
            animation: fadeInUp 0.6s ease forwards;
        }
        
        .delay-1 { animation-delay: 0.1s; }
        .delay-2 { animation-delay: 0.2s; }
        .delay-3 { animation-delay: 0.3s; }
        .delay-4 { animation-delay: 0.4s; }
        .delay-5 { animation-delay: 0.5s; }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-top">
            <span><i class="fas fa-university"></i> Laguna State Polytechnic University - San Pablo City Campus</span>
            <div>
                <a href="index.php"><i class="fas fa-home"></i> Home</a>
                <span style="margin: 0 12px; opacity: 0.5;">|</span>
                <a href="#"><i class="fas fa-info-circle"></i> About</a>
            </div>
        </div>
        <div class="header-main">
            <div class="header-brand">
                <div class="header-logo">🎓</div>
                <div class="header-title">
                    <h1>University Board Exam Analytics</h1>
                    <p>Comprehensive Performance Dashboard • <?php echo $year_range; ?></p>
                </div>
            </div>
            <div class="header-nav">
                <a href="index.php" class="nav-btn secondary"><i class="fas fa-arrow-left"></i> Back to Home</a>
                <button class="nav-btn primary" onclick="window.print()"><i class="fas fa-print"></i> Print Report</button>
            </div>
        </div>
    </header>
    
    <main class="main">
        <!-- University KPIs -->
        <section class="kpi-section">
            <div class="section-header">
                <h2><i class="fas fa-chart-line"></i> University-Wide Performance Overview</h2>
                <p>Aggregated statistics across all colleges and departments</p>
            </div>
            
            <div class="kpi-grid">
                <div class="kpi-card total animate-in delay-1">
                    <div class="kpi-icon"><i class="fas fa-users"></i></div>
                    <div class="kpi-value"><?php echo number_format($university_stats['total']); ?></div>
                    <div class="kpi-label">Total Board Examinees</div>
                    <span class="kpi-trend neutral"><i class="fas fa-database"></i> All Departments</span>
                </div>
                
                <div class="kpi-card passed animate-in delay-2">
                    <div class="kpi-icon"><i class="fas fa-check-circle"></i></div>
                    <div class="kpi-value"><?php echo number_format($university_stats['passed']); ?></div>
                    <div class="kpi-label">Total Passed</div>
                    <span class="kpi-trend up"><i class="fas fa-trophy"></i> Success Stories</span>
                </div>
                
                <div class="kpi-card failed animate-in delay-3">
                    <div class="kpi-icon"><i class="fas fa-times-circle"></i></div>
                    <div class="kpi-value"><?php echo number_format($university_stats['failed']); ?></div>
                    <div class="kpi-label">Total Failed</div>
                    <span class="kpi-trend down"><i class="fas fa-redo"></i> Needs Improvement</span>
                </div>
                
                <div class="kpi-card rate animate-in delay-4">
                    <div class="kpi-icon"><i class="fas fa-percentage"></i></div>
                    <div class="kpi-value"><?php echo $pass_rate; ?>%</div>
                    <div class="kpi-label">Overall Pass Rate</div>
                    <span class="kpi-trend <?php echo $pass_rate >= 70 ? 'up' : ($pass_rate >= 50 ? 'neutral' : 'down'); ?>">
                        <i class="fas fa-chart-line"></i> University Average
                    </span>
                </div>
                
                <div class="kpi-card first animate-in delay-5">
                    <div class="kpi-icon"><i class="fas fa-star"></i></div>
                    <div class="kpi-value"><?php echo $ft_rate; ?>%</div>
                    <div class="kpi-label">First Timer Pass Rate</div>
                    <span class="kpi-trend up"><i class="fas fa-medal"></i> <?php echo number_format($university_stats['first_timer']); ?> Examinees</span>
                </div>
                
                <div class="kpi-card repeat animate-in delay-5">
                    <div class="kpi-icon"><i class="fas fa-redo"></i></div>
                    <div class="kpi-value"><?php echo $rp_rate; ?>%</div>
                    <div class="kpi-label">Repeater Pass Rate</div>
                    <span class="kpi-trend neutral"><i class="fas fa-sync"></i> <?php echo number_format($university_stats['repeater']); ?> Examinees</span>
                </div>
            </div>
        </section>
        
        <!-- Department Cards -->
        <section class="dept-section">
            <div class="section-header">
                <h2><i class="fas fa-building"></i> College Performance Breakdown</h2>
                <p>Click on any department to view detailed analytics</p>
            </div>
            
            <div class="dept-grid">
                <?php 
                $dept_links = [
                    'Engineering' => 'public_dashboard_engineering.php',
                    'Arts and Sciences' => 'public_dashboard_cas.php',
                    'Business Administration and Accountancy' => 'public_dashboard_cbaa.php',
                    'Criminal Justice Education' => 'public_dashboard_ccje.php',
                    'Teacher Education' => 'public_dashboard_cte.php'
                ];
                
                foreach ($departments as $dept_key => $dept_info):
                    $stats = $department_stats[$dept_key] ?? ['total' => 0, 'passed' => 0, 'failed' => 0, 'first_timer' => 0, 'repeater' => 0, 'ft_passed' => 0, 'rp_passed' => 0];
                    $dept_rate = $stats['total'] > 0 ? round(($stats['passed'] / $stats['total']) * 100, 1) : 0;
                    $ft_dept_rate = $stats['first_timer'] > 0 ? round(($stats['ft_passed'] / $stats['first_timer']) * 100, 1) : 0;
                    $link = $dept_links[$dept_key] ?? '#';
                ?>
                <a href="<?php echo $link; ?>" class="dept-card animate-in">
                    <div class="dept-card-header" style="background: linear-gradient(135deg, <?php echo $dept_info['color']; ?> 0%, <?php echo $dept_info['color']; ?>dd 100%);">
                        <div class="icon"><?php echo $dept_info['icon']; ?></div>
                        <h3><?php echo $dept_info['name']; ?></h3>
                        <div class="abbr"><?php echo $dept_info['abbr']; ?></div>
                    </div>
                    <div class="dept-card-body">
                        <div class="dept-stats">
                            <div class="dept-stat">
                                <div class="dept-stat-value" style="color: <?php echo $dept_info['color']; ?>;"><?php echo number_format($stats['total']); ?></div>
                                <div class="dept-stat-label">Total</div>
                            </div>
                            <div class="dept-stat">
                                <div class="dept-stat-value" style="color: var(--success);"><?php echo number_format($stats['passed']); ?></div>
                                <div class="dept-stat-label">Passed</div>
                            </div>
                            <div class="dept-stat">
                                <div class="dept-stat-value" style="color: <?php echo $dept_info['color']; ?>;"><?php echo $dept_rate; ?>%</div>
                                <div class="dept-stat-label">Pass Rate</div>
                            </div>
                            <div class="dept-stat">
                                <div class="dept-stat-value" style="color: var(--accent);"><?php echo $ft_dept_rate; ?>%</div>
                                <div class="dept-stat-label">1st Timer Rate</div>
                            </div>
                        </div>
                        <div class="dept-progress">
                            <div class="dept-progress-fill" style="width: <?php echo $dept_rate; ?>%; background: <?php echo $dept_info['color']; ?>;"></div>
                        </div>
                        <div class="dept-card-footer">
                            <span style="font-size: 0.8rem; color: var(--text-secondary);">
                                <i class="fas fa-user-graduate"></i> <?php echo number_format($stats['first_timer']); ?> First Timers
                            </span>
                            <span class="view-details" style="color: <?php echo $dept_info['color']; ?>;">
                                View Details <i class="fas fa-arrow-right"></i>
                            </span>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </section>
        
        <!-- Charts Section -->
        <section class="charts-section">
            <div class="section-header">
                <h2><i class="fas fa-chart-pie"></i> Interactive Data Visualizations</h2>
                <p>Comprehensive analysis of university-wide board examination performance</p>
            </div>
            
            <div class="charts-grid">
                <!-- Department Comparison Chart -->
                <div class="chart-card">
                    <div class="chart-header">
                        <div class="chart-title">
                            <h3><i class="fas fa-balance-scale"></i> Department Pass Rate Comparison</h3>
                            <p>Comparing performance across all colleges</p>
                        </div>
                        <button class="chart-info-btn" onclick="showVizInfo('barChart')" title="About this visualization">
                            <i class="fas fa-info"></i>
                        </button>
                    </div>
                    <div class="chart-container">
                        <canvas id="deptComparisonChart"></canvas>
                    </div>
                </div>
                
                <!-- Overall Distribution -->
                <div class="chart-card">
                    <div class="chart-header">
                        <div class="chart-title">
                            <h3><i class="fas fa-chart-pie"></i> University Results Distribution</h3>
                            <p>Overall pass/fail breakdown</p>
                        </div>
                        <button class="chart-info-btn" onclick="showVizInfo('doughnutChart')" title="About this visualization">
                            <i class="fas fa-info"></i>
                        </button>
                    </div>
                    <div class="chart-container">
                        <canvas id="resultsDistChart"></canvas>
                    </div>
                </div>
                
                <!-- Yearly Trend -->
                <div class="chart-card full-width">
                    <div class="chart-header">
                        <div class="chart-title">
                            <h3><i class="fas fa-chart-line"></i> University Performance Trend</h3>
                            <p>Year-over-year passing rate trends across all departments</p>
                        </div>
                        <button class="chart-info-btn" onclick="showVizInfo('lineBarChart')" title="About this visualization">
                            <i class="fas fa-info"></i>
                        </button>
                    </div>
                    <div class="chart-container tall">
                        <canvas id="yearlyTrendChart"></canvas>
                    </div>
                </div>
                
                <!-- Department Yearly Comparison -->
                <div class="chart-card full-width">
                    <div class="chart-header">
                        <div class="chart-title">
                            <h3><i class="fas fa-layer-group"></i> Department Performance by Year</h3>
                            <p>Stacked comparison of examinees per department over time</p>
                        </div>
                        <button class="chart-info-btn" onclick="showVizInfo('stackedBarChart')" title="About this visualization">
                            <i class="fas fa-info"></i>
                        </button>
                    </div>
                    <div class="chart-container tall">
                        <canvas id="deptYearlyChart"></canvas>
                    </div>
                </div>
                
                <!-- First Timer vs Repeater -->
                <div class="chart-card">
                    <div class="chart-header">
                        <div class="chart-title">
                            <h3><i class="fas fa-users"></i> First Timer vs Repeater</h3>
                            <p>Performance comparison by attempt type</p>
                        </div>
                        <button class="chart-info-btn" onclick="showVizInfo('groupedBarChart')" title="About this visualization">
                            <i class="fas fa-info"></i>
                        </button>
                    </div>
                    <div class="chart-container">
                        <canvas id="attemptChart"></canvas>
                    </div>
                </div>
                
                <!-- Examinees Distribution -->
                <div class="chart-card">
                    <div class="chart-header">
                        <div class="chart-title">
                            <h3><i class="fas fa-graduation-cap"></i> Examinees per Department</h3>
                            <p>Distribution of board examinees</p>
                        </div>
                        <button class="chart-info-btn" onclick="showVizInfo('polarChart')" title="About this visualization">
                            <i class="fas fa-info"></i>
                        </button>
                    </div>
                    <div class="chart-container">
                        <canvas id="examineesChart"></canvas>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- AI Predictions & Analytics -->
        <section class="predictions-section">
            <div class="section-header">
                <h2><i class="fas fa-brain"></i> AI-Powered University Performance Analysis</h2>
                <p>Predictive analytics and insights based on <?php echo number_format($university_stats['total']); ?> historical board examination records</p>
            </div>
            
            <div class="predictions-grid">
                <!-- Predicted Pass Rate -->
                <div class="prediction-card" style="background: linear-gradient(135deg, #059669 0%, #10b981 100%);">
                    <div class="prediction-icon"><i class="fas fa-chart-line"></i></div>
                    <div class="prediction-value"><?php echo $predicted_rate; ?>%</div>
                    <div class="prediction-label">Predicted Next Year Pass Rate</div>
                    <span class="prediction-status">
                        <?php if ($trend_direction === 'improving'): ?>
                            <i class="fas fa-arrow-up"></i> +<?php echo abs(round($trend_value, 1)); ?>% Trend
                        <?php elseif ($trend_direction === 'declining'): ?>
                            <i class="fas fa-arrow-down"></i> <?php echo round($trend_value, 1); ?>% Trend
                        <?php else: ?>
                            <i class="fas fa-minus"></i> Stable Trend
                        <?php endif; ?>
                    </span>
                </div>
                
                <!-- Current Performance -->
                <div class="prediction-card" style="background: linear-gradient(135deg, #7c3aed 0%, #a78bfa 100%);">
                    <div class="prediction-icon"><i class="fas fa-percentage"></i></div>
                    <div class="prediction-value"><?php echo $pass_rate; ?>%</div>
                    <div class="prediction-label">Current Overall Pass Rate</div>
                    <span class="prediction-status">
                        <i class="fas fa-database"></i> Based on <?php echo number_format($university_stats['total']); ?> Records
                    </span>
                </div>
                
                <!-- Top Performing College -->
                <div class="prediction-card" style="background: linear-gradient(135deg, #d97706 0%, #fbbf24 100%);">
                    <div class="prediction-icon"><i class="fas fa-trophy"></i></div>
                    <div class="prediction-value"><?php echo $departments[$top_dept]['abbr'] ?? 'N/A'; ?></div>
                    <div class="prediction-label">Top Performing College</div>
                    <span class="prediction-status">
                        <i class="fas fa-star"></i> <?php echo round($top_rate, 1); ?>% Pass Rate
                    </span>
                </div>
                
                <!-- Needs Improvement -->
                <div class="prediction-card" style="background: linear-gradient(135deg, #dc2626 0%, #f87171 100%);">
                    <div class="prediction-icon"><i class="fas fa-exclamation-triangle"></i></div>
                    <div class="prediction-value"><?php echo $departments[$lowest_dept]['abbr'] ?? 'N/A'; ?></div>
                    <div class="prediction-label">Needs Most Improvement</div>
                    <span class="prediction-status">
                        <i class="fas fa-target"></i> <?php echo round($lowest_rate, 1); ?>% Pass Rate
                    </span>
                </div>
                
                <!-- Expected Examinees -->
                <div class="prediction-card" style="background: linear-gradient(135deg, #0891b2 0%, #22d3ee 100%);">
                    <div class="prediction-icon"><i class="fas fa-users"></i></div>
                    <div class="prediction-value">~<?php echo number_format(round($avg_examinees * 1.05)); ?></div>
                    <div class="prediction-label">Expected Examinees Next Year</div>
                    <span class="prediction-status">
                        <i class="fas fa-calculator"></i> +5% Growth Estimate
                    </span>
                </div>
                
                <!-- First Timer Success -->
                <div class="prediction-card" style="background: linear-gradient(135deg, #4f46e5 0%, #818cf8 100%);">
                    <div class="prediction-icon"><i class="fas fa-user-graduate"></i></div>
                    <div class="prediction-value"><?php echo $ft_rate; ?>%</div>
                    <div class="prediction-label">First Timer Success Rate</div>
                    <span class="prediction-status">
                        <i class="fas fa-medal"></i> <?php echo number_format($university_stats['first_timer_passed']); ?> Passed
                    </span>
                </div>
                
                <!-- Repeater Recovery -->
                <div class="prediction-card" style="background: linear-gradient(135deg, #be185d 0%, #f472b6 100%);">
                    <div class="prediction-icon"><i class="fas fa-redo"></i></div>
                    <div class="prediction-value"><?php echo $rp_rate; ?>%</div>
                    <div class="prediction-label">Repeater Recovery Rate</div>
                    <span class="prediction-status">
                        <i class="fas fa-sync"></i> <?php echo number_format($university_stats['repeater_passed']); ?> Recovered
                    </span>
                </div>
                
                <!-- Improvement Potential -->
                <div class="prediction-card" style="background: linear-gradient(135deg, #0f172a 0%, #334155 100%);">
                    <div class="prediction-icon"><i class="fas fa-rocket"></i></div>
                    <div class="prediction-value"><?php echo round($improvement_potential, 1); ?>%</div>
                    <div class="prediction-label">Improvement Potential</div>
                    <span class="prediction-status">
                        <i class="fas fa-lightbulb"></i> Room for Growth
                    </span>
                </div>
            </div>
            
            <!-- Performance Summary Card -->
            <div class="performance-summary" style="margin-top: 24px; background: var(--white); border-radius: 20px; padding: 24px; box-shadow: var(--shadow);">
                <h3 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 16px; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-clipboard-check" style="color: var(--accent);"></i> University Performance Summary
                </h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px;">
                    <div style="padding: 16px; background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border-radius: 12px; border-left: 4px solid #22c55e;">
                        <div style="font-weight: 600; color: #166534; margin-bottom: 8px;"><i class="fas fa-check-circle"></i> Strengths</div>
                        <ul style="font-size: 0.9rem; color: #15803d; margin: 0; padding-left: 20px;">
                            <li><?php echo $departments[$top_dept]['name'] ?? 'Top College'; ?> leads with <?php echo round($top_rate, 1); ?>% pass rate</li>
                            <li>First timers performing at <?php echo $ft_rate; ?>% success rate</li>
                            <?php if ($trend_direction === 'improving'): ?>
                            <li>Overall performance trending upward</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    <div style="padding: 16px; background: linear-gradient(135deg, #fef2f2 0%, #fecaca 100%); border-radius: 12px; border-left: 4px solid #ef4444;">
                        <div style="font-weight: 600; color: #991b1b; margin-bottom: 8px;"><i class="fas fa-exclamation-circle"></i> Areas for Improvement</div>
                        <ul style="font-size: 0.9rem; color: #b91c1c; margin: 0; padding-left: 20px;">
                            <li><?php echo $departments[$lowest_dept]['name'] ?? 'Lowest College'; ?> needs support (<?php echo round($lowest_rate, 1); ?>%)</li>
                            <li>Repeater recovery at <?php echo $rp_rate; ?>% - target higher</li>
                            <li><?php echo round($improvement_potential, 1); ?>% potential for overall improvement</li>
                        </ul>
                    </div>
                    <div style="padding: 16px; background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); border-radius: 12px; border-left: 4px solid #3b82f6;">
                        <div style="font-weight: 600; color: #1e40af; margin-bottom: 8px;"><i class="fas fa-lightbulb"></i> Recommendations</div>
                        <ul style="font-size: 0.9rem; color: #1d4ed8; margin: 0; padding-left: 20px;">
                            <li>Focus resources on <?php echo $departments[$lowest_dept]['abbr'] ?? 'underperforming'; ?> department</li>
                            <li>Implement targeted repeater support programs</li>
                            <li>Share best practices from <?php echo $departments[$top_dept]['abbr'] ?? 'top'; ?> department</li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- Comparison Table -->
        <section class="comparison-section">
            <div class="comparison-card">
                <div class="comparison-header">
                    <h3><i class="fas fa-table"></i> Detailed Department Comparison</h3>
                </div>
                <div style="overflow-x: auto;">
                    <table class="comparison-table">
                        <thead>
                            <tr>
                                <th>Department</th>
                                <th>Total</th>
                                <th>Passed</th>
                                <th>Failed</th>
                                <th>Pass Rate</th>
                                <th>1st Timer Rate</th>
                                <th>Repeater Rate</th>
                                <th>Performance</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($departments as $dept_key => $dept_info):
                                $stats = $department_stats[$dept_key] ?? ['total' => 0, 'passed' => 0, 'failed' => 0, 'first_timer' => 0, 'repeater' => 0, 'ft_passed' => 0, 'rp_passed' => 0];
                                $dept_rate = $stats['total'] > 0 ? round(($stats['passed'] / $stats['total']) * 100, 1) : 0;
                                $ft_dept_rate = $stats['first_timer'] > 0 ? round(($stats['ft_passed'] / $stats['first_timer']) * 100, 1) : 0;
                                $rp_dept_rate = $stats['repeater'] > 0 ? round(($stats['rp_passed'] / $stats['repeater']) * 100, 1) : 0;
                            ?>
                            <tr>
                                <td>
                                    <div class="dept-name">
                                        <div class="dept-icon" style="background: <?php echo $dept_info['light']; ?>;"><?php echo $dept_info['icon']; ?></div>
                                        <div>
                                            <strong><?php echo $dept_info['abbr']; ?></strong>
                                            <div style="font-size: 0.75rem; color: var(--text-secondary);"><?php echo $dept_info['name']; ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td><strong><?php echo number_format($stats['total']); ?></strong></td>
                                <td style="color: var(--success);"><strong><?php echo number_format($stats['passed']); ?></strong></td>
                                <td style="color: var(--danger);"><strong><?php echo number_format($stats['failed']); ?></strong></td>
                                <td>
                                    <span class="rate-badge" style="background: <?php echo $dept_info['light']; ?>; color: <?php echo $dept_info['color']; ?>;">
                                        <?php echo $dept_rate; ?>%
                                    </span>
                                </td>
                                <td><?php echo $ft_dept_rate; ?>%</td>
                                <td><?php echo $rp_dept_rate; ?>%</td>
                                <td>
                                    <div class="mini-bar">
                                        <div class="mini-bar-fill" style="width: <?php echo $dept_rate; ?>%; background: <?php echo $dept_info['color']; ?>;"></div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </main>
    
    <footer class="footer">
        <p>&copy; <?php echo date('Y'); ?> Laguna State Polytechnic University - San Pablo City Campus</p>
        <p style="margin-top: 8px; opacity: 0.7; font-size: 0.8rem;">Board Examination Performance Analytics System</p>
    </footer>
    
    <script>
        // Chart.js defaults
        Chart.defaults.font.family = "'Inter', sans-serif";
        Chart.defaults.plugins.legend.labels.usePointStyle = true;
        
        // Department data with correct colors
        const deptData = [
            <?php foreach ($departments as $dept_key => $dept_info): 
                $stats = $department_stats[$dept_key] ?? ['total' => 0, 'passed' => 0, 'failed' => 0];
                $rate = $stats['total'] > 0 ? round(($stats['passed'] / $stats['total']) * 100, 1) : 0;
            ?>
            {
                name: '<?php echo $dept_info['abbr']; ?>',
                fullName: '<?php echo $dept_info['name']; ?>',
                color: '<?php echo $dept_info['color']; ?>',
                total: <?php echo $stats['total']; ?>,
                passed: <?php echo $stats['passed']; ?>,
                failed: <?php echo $stats['failed']; ?>,
                rate: <?php echo $rate; ?>
            },
            <?php endforeach; ?>
        ];
        
        // Yearly data
        const yearlyData = <?php echo json_encode($yearly_data); ?>;
        const deptYearlyData = <?php echo json_encode($department_yearly); ?>;
        
        // 1. Department Comparison Chart
        new Chart(document.getElementById('deptComparisonChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: deptData.map(d => d.name),
                datasets: [{
                    label: 'Pass Rate (%)',
                    data: deptData.map(d => d.rate),
                    backgroundColor: deptData.map(d => d.color),
                    borderRadius: 8,
                    borderSkipped: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            title: (items) => deptData[items[0].dataIndex].fullName,
                            label: (item) => `Pass Rate: ${item.raw}%`
                        }
                    }
                },
                scales: {
                    y: { beginAtZero: true, max: 100, grid: { color: '#e2e8f0' } },
                    x: { grid: { display: false } }
                }
            }
        });
        
        // 2. Results Distribution Chart
        new Chart(document.getElementById('resultsDistChart').getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['Passed', 'Failed', 'Conditional'],
                datasets: [{
                    data: [<?php echo $university_stats['passed']; ?>, <?php echo $university_stats['failed']; ?>, <?php echo $university_stats['conditional']; ?>],
                    backgroundColor: ['#22c55e', '#ef4444', '#f59e0b'],
                    borderWidth: 0,
                    hoverOffset: 15
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { padding: 20 } },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((context.raw / total) * 100).toFixed(1);
                                return `${context.label}: ${context.raw.toLocaleString()} (${percentage}%)`;
                            }
                        }
                    }
                },
                cutout: '60%'
            }
        });
        
        // 3. Yearly Trend Chart
        const years = Object.keys(yearlyData).sort();
        new Chart(document.getElementById('yearlyTrendChart').getContext('2d'), {
            type: 'line',
            data: {
                labels: years,
                datasets: [
                    {
                        label: 'Passed',
                        data: years.map(y => yearlyData[y].passed),
                        borderColor: '#22c55e',
                        backgroundColor: 'rgba(34, 197, 94, 0.1)',
                        fill: true,
                        tension: 0.4
                    },
                    {
                        label: 'Failed',
                        data: years.map(y => yearlyData[y].failed),
                        borderColor: '#ef4444',
                        backgroundColor: 'rgba(239, 68, 68, 0.1)',
                        fill: true,
                        tension: 0.4
                    },
                    {
                        label: 'Pass Rate (%)',
                        data: years.map(y => yearlyData[y].total > 0 ? ((yearlyData[y].passed / yearlyData[y].total) * 100).toFixed(1) : 0),
                        borderColor: '#3b82f6',
                        backgroundColor: 'transparent',
                        borderDash: [5, 5],
                        tension: 0.4,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: { legend: { position: 'bottom', labels: { padding: 20 } } },
                scales: {
                    y: { beginAtZero: true, grid: { color: '#e2e8f0' } },
                    y1: { position: 'right', beginAtZero: true, max: 100, grid: { display: false } },
                    x: { grid: { display: false } }
                }
            }
        });
        
        // 4. Department Yearly Stacked Chart
        const allYears = [...new Set(Object.values(deptYearlyData).flatMap(d => Object.keys(d)))].sort();
        const deptKeys = Object.keys(deptYearlyData);
        
        // Proper color mapping for departments
        const deptColorMap = {
            'Engineering': '#5B7B5A',
            'Arts and Sciences': '#BF3853',
            'Business Administration and Accountancy': '#D97706',
            'Criminal Justice Education': '#7F1D1D',
            'Teacher Education': '#1D4ED8'
        };
        
        const deptAbbrMap = {
            'Engineering': 'COE',
            'Arts and Sciences': 'CAS',
            'Business Administration and Accountancy': 'CBAA',
            'Criminal Justice Education': 'CCJE',
            'Teacher Education': 'CTE'
        };
        
        new Chart(document.getElementById('deptYearlyChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: allYears,
                datasets: deptKeys.map((dept) => ({
                    label: deptAbbrMap[dept] || dept,
                    data: allYears.map(y => deptYearlyData[dept][y]?.total || 0),
                    backgroundColor: deptColorMap[dept] || '#666',
                    borderRadius: 4
                }))
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom', labels: { padding: 15 } } },
                scales: {
                    x: { stacked: true, grid: { display: false } },
                    y: { stacked: true, grid: { color: '#e2e8f0' } }
                }
            }
        });
        
        // 5. First Timer vs Repeater Chart
        new Chart(document.getElementById('attemptChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: ['First Timer', 'Repeater'],
                datasets: [
                    {
                        label: 'Passed',
                        data: [<?php echo $university_stats['first_timer_passed']; ?>, <?php echo $university_stats['repeater_passed']; ?>],
                        backgroundColor: '#22c55e',
                        borderRadius: 8
                    },
                    {
                        label: 'Failed',
                        data: [<?php echo $university_stats['first_timer'] - $university_stats['first_timer_passed']; ?>, <?php echo $university_stats['repeater'] - $university_stats['repeater_passed']; ?>],
                        backgroundColor: '#94a3b8',
                        borderRadius: 8
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } },
                scales: {
                    y: { beginAtZero: true, grid: { color: '#e2e8f0' } },
                    x: { grid: { display: false } }
                }
            }
        });
        
        // 6. Examinees per Department Chart
        new Chart(document.getElementById('examineesChart').getContext('2d'), {
            type: 'polarArea',
            data: {
                labels: deptData.map(d => d.name),
                datasets: [{
                    data: deptData.map(d => d.total),
                    backgroundColor: deptData.map(d => d.color + 'cc'),
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom', labels: { padding: 15 } } }
            }
        });
        
        // Animate elements on scroll
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, { threshold: 0.1 });
        
        document.querySelectorAll('.animate-in, .dept-card, .chart-card, .prediction-card').forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(20px)';
            el.style.transition = 'all 0.6s ease';
            observer.observe(el);
        });
        
        // Visualization Info Data
        const vizInfoData = {
            barChart: {
                type: 'Bar Chart',
                icon: 'fa-chart-bar',
                title: 'Department Pass Rate Comparison',
                description: 'A vertical bar chart that displays the pass rate percentage for each college/department. Each bar represents one department, with height proportional to its pass rate.',
                features: [
                    'Easy comparison of pass rates across departments',
                    'Color-coded bars for each college identity',
                    'Hover tooltips showing exact percentages',
                    'Y-axis scaled from 0-100% for accurate comparison'
                ],
                useCase: 'Best for comparing a single metric (pass rate) across multiple categories (departments) at a glance.'
            },
            doughnutChart: {
                type: 'Doughnut Chart',
                icon: 'fa-chart-pie',
                title: 'University Results Distribution',
                description: 'A circular chart with a hollow center that shows the proportion of Passed, Failed, and Conditional results across the entire university.',
                features: [
                    'Visual representation of pass/fail ratio',
                    'Color-coded segments (green=passed, red=failed, orange=conditional)',
                    'Percentage labels for each segment',
                    'Interactive hover effects for detailed counts'
                ],
                useCase: 'Perfect for showing part-to-whole relationships and understanding the overall success distribution.'
            },
            lineBarChart: {
                type: 'Combo Line & Bar Chart',
                icon: 'fa-chart-line',
                title: 'University Performance Trend',
                description: 'A combination chart that overlays a line graph (pass rate trend) on top of bar graphs (passed/failed counts) to show both volume and performance over time.',
                features: [
                    'Dual-axis visualization for different metrics',
                    'Line shows pass rate percentage trend',
                    'Stacked bars show actual passed/failed counts',
                    'Year-over-year comparison capabilities'
                ],
                useCase: 'Ideal for tracking historical performance and identifying trends or patterns over multiple years.'
            },
            stackedBarChart: {
                type: 'Stacked Bar Chart',
                icon: 'fa-layer-group',
                title: 'Department Performance by Year',
                description: 'A horizontal or vertical bar chart where each bar is divided into segments representing different departments, stacked on top of each other.',
                features: [
                    'Shows total examinees per year',
                    'Color-coded segments for each department',
                    'Easy identification of department contribution',
                    'Comparison of department sizes over time'
                ],
                useCase: 'Excellent for showing how each department contributes to the university total over different time periods.'
            },
            groupedBarChart: {
                type: 'Grouped Bar Chart',
                icon: 'fa-users',
                title: 'First Timer vs Repeater',
                description: 'Side-by-side bars comparing passed and failed counts for first-time examinees versus repeaters.',
                features: [
                    'Direct comparison between two categories',
                    'Green bars for passed, gray bars for failed',
                    'Clear visualization of success rates by attempt type',
                    'Helps identify if repeaters improve'
                ],
                useCase: 'Best for comparing multiple metrics across two or more distinct groups.'
            },
            polarChart: {
                type: 'Polar Area Chart',
                icon: 'fa-compass',
                title: 'Examinees per Department',
                description: 'A circular chart similar to a pie chart, but with equal angles and varying radius based on the value. Shows the distribution of examinees across departments.',
                features: [
                    'Radial representation of department sizes',
                    'Color-coded wedges for each department',
                    'Area proportional to number of examinees',
                    'Good for showing relative magnitude differences'
                ],
                useCase: 'Useful for displaying the relative size or volume of different categories in an engaging circular format.'
            }
        };
        
        // Show Visualization Info Modal
        function showVizInfo(chartType) {
            const info = vizInfoData[chartType];
            if (!info) return;
            
            const modal = document.getElementById('vizInfoModal');
            document.getElementById('vizInfoType').innerHTML = `<i class="fas ${info.icon}"></i> ${info.type}`;
            document.getElementById('vizInfoTitle').textContent = info.title;
            document.getElementById('vizInfoDesc').textContent = info.description;
            
            const featuresList = document.getElementById('vizInfoFeatures');
            featuresList.innerHTML = info.features.map(f => `<li>${f}</li>`).join('');
            
            document.getElementById('vizInfoUseCase').textContent = info.useCase;
            
            modal.classList.add('active');
        }
        
        // Close modal
        function closeVizInfo() {
            document.getElementById('vizInfoModal').classList.remove('active');
        }
        
        // Close on background click
        document.getElementById('vizInfoModal')?.addEventListener('click', function(e) {
            if (e.target === this) closeVizInfo();
        });
        
        // Close on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeVizInfo();
        });
    </script>
    
    <!-- Visualization Info Modal -->
    <div id="vizInfoModal" class="viz-info-modal">
        <div class="viz-info-content">
            <div class="viz-info-header">
                <h3><i class="fas fa-info-circle"></i> About This Visualization</h3>
                <button class="viz-info-close" onclick="closeVizInfo()"><i class="fas fa-times"></i></button>
            </div>
            <div class="viz-info-body">
                <div class="viz-type-badge" id="vizInfoType">
                    <i class="fas fa-chart-bar"></i> Bar Chart
                </div>
                <h4 id="vizInfoTitle">Chart Title</h4>
                <p id="vizInfoDesc">Description goes here.</p>
                
                <div class="viz-features">
                    <h5><i class="fas fa-star"></i> Key Features</h5>
                    <ul id="vizInfoFeatures">
                        <li>Feature 1</li>
                    </ul>
                </div>
                
                <div class="viz-use-case">
                    <p><strong><i class="fas fa-lightbulb"></i> Best Used For:</strong> <span id="vizInfoUseCase">Use case description.</span></p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>