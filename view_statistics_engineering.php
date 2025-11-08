<?php
session_start();

// Only allow College of Engineering admin
if (!isset($_SESSION["users"]) || $_SESSION["users"] !== 'eng_admin@lspu.edu.ph') {
    header("Location: mainpage.php");
    exit();
}

require_once __DIR__ . '/db_config.php';

try {
    $conn = getDbConnection();
} catch (Exception $e) {
    die('DB connection error: ' . $e->getMessage());
}

// Fetch board exam types and dates for filters
$betStmt = $conn->prepare("SELECT id, exam_type_name FROM board_exam_types WHERE department='Engineering' ORDER BY exam_type_name ASC");
$betStmt->execute();
$betRes = $betStmt->get_result();
$boardExamTypes = [];
while ($r = $betRes->fetch_assoc()) { $boardExamTypes[] = $r; }

$bedStmt = $conn->prepare("SELECT id, exam_date, exam_description, exam_type_id FROM board_exam_dates WHERE department='Engineering' ORDER BY exam_date DESC");
$bedStmt->execute();
$bedRes = $bedStmt->get_result();
$boardExamDates = [];
while ($r = $bedRes->fetch_assoc()) { $boardExamDates[] = $r; }

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>View Statistics - Engineering</title>
  <link rel="stylesheet" href="style.css" />
  <link rel="stylesheet" href="css/sidebar.css" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <style>
    /* Modern cyan/teal theme with animations */
    :root{
      --primary: #06b6d4;
      --primary-dark: #0891b2;
      --primary-darker: #0e7490;
      --magenta: #f472b6;
      --muted: #64748b;
      --success: #10b981;
      --danger: #ef4444;
      --indigo: #6366f1;
      --slate: #94a3b8;
      --card-bg: #ffffff;
    }
    
    @keyframes fadeIn {
      0% { opacity: 0; transform: translateY(20px); }
      100% { opacity: 1; transform: translateY(0); }
    }
    
    @keyframes shimmer {
      0% { transform: translateX(-100%) translateY(-100%) rotate(45deg); opacity: 0; }
      50% { opacity: 1; }
      100% { transform: translateX(100%) translateY(100%) rotate(45deg); opacity: 0; }
    }
    
    @keyframes float {
      0%, 100% { transform: translateY(0px); }
      50% { transform: translateY(-8px); }
    }
    
    @keyframes pulse {
      0%, 100% { transform: scale(1); opacity: 1; }
      50% { transform: scale(1.05); opacity: 0.9; }
    }
    
    *{box-sizing:border-box; margin: 0; padding: 0;}
    
    body { 
      font-family: 'Inter', sans-serif; 
      background: linear-gradient(135deg, #ecfeff 0%, #cffafe 100%);
      margin: 0;
      color: #0f1724;
      position: relative;
      min-height: 100vh;
    }
    
    body::before {
      content: '';
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: radial-gradient(circle at 20% 50%, rgba(6, 182, 212, 0.1) 0%, transparent 50%),
                  radial-gradient(circle at 80% 80%, rgba(6, 182, 212, 0.08) 0%, transparent 50%);
      pointer-events: none;
      z-index: 0;
    }

    .topbar {
      position: fixed;
      top: 0;
      left: 260px;
      right: 0;
      background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);
      height: 70px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0 40px;
      box-shadow: 0 4px 25px rgba(6, 182, 212, 0.25);
      z-index: 50;
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
      overflow: hidden;
    }
    
    .topbar::before {
      content: '';
      position: absolute;
      top: -50%;
      right: -50%;
      width: 100%;
      height: 200%;
      background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.1), transparent);
      animation: shimmer 4s infinite;
      z-index: 1;
    }
    
    .dashboard-title { 
      font-size: 1.4rem;
      color: #fff;
      font-weight: 700;
      letter-spacing: 0.5px;
      margin: 0;
      position: relative;
      z-index: 2;
    }
    
    .logout-btn { 
      background: rgba(255, 255, 255, 0.15);
      color: #fff;
      border: 2px solid rgba(255, 255, 255, 0.3);
      border-radius: 12px;
      padding: 12px 24px;
      font-size: 0.95rem;
      font-weight: 600;
      font-family: 'Inter', sans-serif;
      cursor: pointer;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      text-decoration: none;
      display: flex;
      align-items: center;
      gap: 8px;
      backdrop-filter: blur(10px);
      position: relative;
      z-index: 2;
    }
    
    .logout-btn:hover { 
      background: rgba(255, 255, 255, 0.25);
      border-color: rgba(255, 255, 255, 0.5);
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
    }

  .main { 
    margin-left: 260px;
    margin-top: 70px;
    padding: 32px;
    position: relative;
    z-index: 1;
    animation: fadeIn 0.6s ease;
  }
  
  .layout { 
    display: grid;
    grid-template-columns: 1fr;
    gap: 28px;
    align-items: start;
  }
  
  .filter-panel { 
    width: 100%;
    background: linear-gradient(145deg, rgba(255, 255, 255, 0.95) 0%, rgba(236, 254, 255, 0.9) 100%);
    border-radius: 20px;
    padding: 28px;
    box-shadow: 0 10px 35px rgba(6, 182, 212, 0.15);
    border: 2px solid rgba(6, 182, 212, 0.2);
    position: relative;
    overflow: hidden;
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    animation: fadeIn 0.7s ease 0.1s both;
  }
  
  .visual-panel { 
    background: linear-gradient(145deg, rgba(255, 255, 255, 0.9) 0%, rgba(236, 254, 255, 0.85) 100%);
    border-radius: 20px;
    padding: 28px;
    box-shadow: 0 14px 40px rgba(6, 182, 212, 0.2);
    border: 2px solid rgba(6, 182, 212, 0.15);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    animation: fadeIn 0.8s ease 0.2s both;
  }
  
  .step { 
    margin-bottom: 20px;
  }
  
  .step-header { 
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 8px;
  }
  
  .step h4 { 
    font-size: 1.05rem;
    margin: 0;
    font-weight: 700;
    letter-spacing: 0.3px;
    color: #0f1724;
    display: flex;
    align-items: center;
    gap: 8px;
  }
  
  .step h4 i {
    color: #06b6d4;
    font-size: 1.1rem;
  }
  
  select { 
    width: 100%;
    padding: 14px 16px;
    font-size: 0.98rem;
    border-radius: 14px;
    border: 2px solid #e0f2fe;
    background: #fff;
    font-family: 'Inter', sans-serif;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    appearance: none;
    background-image: linear-gradient(45deg, transparent 50%, #06b6d4 50%), 
                      linear-gradient(135deg, #06b6d4 50%, transparent 50%);
    background-position: calc(100% - 20px) calc(1em + 4px),
                        calc(100% - 14px) calc(1em + 4px);
    background-size: 6px 6px, 6px 6px;
    background-repeat: no-repeat;
    cursor: pointer;
  }
  
  select:hover {
    border-color: #06b6d4;
    background-color: #f0fdff;
  }
  
  select:focus {
    outline: none;
    border-color: #06b6d4;
    box-shadow: 0 0 0 3px rgba(6, 182, 212, 0.2);
    background-color: #fff;
  }
  
  option {
    padding: 12px 16px;
    font-size: 0.98rem;
    font-family: 'Inter', sans-serif;
    background: #fff;
    color: #0f1724;
    font-weight: 500;
  }
  
  option:hover, option:focus {
    background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);
    color: #fff;
  }
  
  option:checked {
    background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);
    color: #fff;
    font-weight: 700;
  }
  
  option[disabled] {
    color: #94a3b8;
    font-style: italic;
  }
  
  button { 
    padding: 14px 20px;
    font-size: 0.98rem;
    border-radius: 14px;
    border: none;
    font-family: 'Inter', sans-serif;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  }
  
  .hint { 
    font-size: 0.88rem;
    color: #64748b;
    margin-top: 8px;
    display: flex;
    align-items: center;
    gap: 6px;
  }
  
  .hint i {
    color: #06b6d4;
  }
  
  .filter-header { 
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-bottom: 16px;
    margin-bottom: 20px;
    border-bottom: 2px solid rgba(6, 182, 212, 0.15);
  }
  
  .filter-title { 
    margin: 0;
    font-size: 1.3rem;
    font-weight: 800;
    letter-spacing: 0.4px;
    color: #0f1724;
    display: flex;
    align-items: center;
    gap: 12px;
  }
  
  .filter-title i {
    color: #06b6d4;
    font-size: 1.4rem;
    animation: pulse 2s ease-in-out infinite;
  }
  
  .filter-header .print-btn { 
    padding: 10px 18px;
    border-radius: 12px;
    font-size: 0.95rem;
    width: auto;
    background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);
    color: #fff;
    border: none;
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 600;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 4px 15px rgba(6, 182, 212, 0.3);
  }
  
  .filter-header .print-btn:hover { 
    background: linear-gradient(135deg, #0891b2 0%, #0e7490 100%);
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(6, 182, 212, 0.4);
  }
  
  .filter-actions { 
    display: flex;
    gap: 12px;
    align-items: center;
    margin-top: 20px;
    justify-content: flex-end;
  }
  
  .filter-actions button { 
    width: auto;
  }
  
  .filter-actions .btn-primary { 
    flex: 0 0 auto;
  }
  
  .btn-clear { 
    padding: 10px 18px;
    font-size: 0.95rem;
    background: rgba(100, 116, 139, 0.1);
    color: #64748b;
    border: 2px solid rgba(100, 116, 139, 0.2);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  }
  
  .btn-clear:hover {
    background: rgba(100, 116, 139, 0.15);
    border-color: rgba(100, 116, 139, 0.3);
    transform: translateY(-2px);
  }
  
  .selected-pill { 
    display: inline-flex;
    align-items: center;
    gap: 12px;
    font-size: 1.05rem;
    font-weight: 700;
    padding: 10px 18px;
    border-radius: 999px;
    background: linear-gradient(145deg, #fff 0%, #f0fdff 100%);
    color: #0f1724;
    border: 2px solid #06b6d4;
    box-shadow: 0 4px 15px rgba(6, 182, 212, 0.2);
    animation: fadeIn 0.5s ease;
  }
  
  .selected-header { 
    display: flex;
    gap: 16px;
    flex-wrap: wrap;
    justify-content: center;
    margin-bottom: 16px;
  }
  
  .selected-pill i { 
    width: 32px;
    height: 32px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 999px;
    background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);
    color: #fff;
    box-shadow: 0 4px 12px rgba(6, 182, 212, 0.3);
    animation: float 3s ease-in-out infinite;
  }
  
  .tip-pill { 
    margin-top: 12px;
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 16px;
    border-radius: 14px;
    background: linear-gradient(145deg, #ecfeff 0%, #cffafe 100%);
    color: #0e7490;
    border: 2px solid #67e8f9;
    font-size: 0.92rem;
    font-weight: 600;
    animation: fadeIn 0.6s ease;
  }
  
  .tip-pill i {
    color: #06b6d4;
    font-size: 1.1rem;
  }
  
  .year-chips { 
    display: none;
    gap: 10px;
    flex-wrap: wrap;
    margin-top: 12px;
  }
  
  .chip { 
    padding: 8px 16px;
    border-radius: 999px;
    border: 2px solid #06b6d4;
    color: #06b6d4;
    background: #fff;
    cursor: pointer;
    font-weight: 700;
    font-size: 0.9rem;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  }
  
  .chip:hover { 
    background: #06b6d4;
    color: #fff;
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(6, 182, 212, 0.35);
  }
  
  .chip.active { 
    background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);
    color: #fff;
    border-color: transparent;
    box-shadow: 0 4px 15px rgba(6, 182, 212, 0.4);
  }
  
  .btn-secondary { 
    background: #fff;
    color: #06b6d4;
    border: 2px solid #06b6d4;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  }
  
  .btn-secondary:hover { 
    background: #06b6d4;
    color: #fff;
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(6, 182, 212, 0.35);
  }
  
  .btn-primary { 
    background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);
    color: #fff;
    border: none;
    padding: 12px 24px;
    border-radius: 14px;
    cursor: pointer;
    font-weight: 700;
    box-shadow: 0 8px 24px rgba(6, 182, 212, 0.3);
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  }
  
  .btn-primary:hover {
    background: linear-gradient(135deg, #0891b2 0%, #0e7490 100%);
    transform: translateY(-2px);
    box-shadow: 0 12px 30px rgba(6, 182, 212, 0.4);
  }
  
  .btn-primary i { 
    margin-right: 0;
  }
  
  .print-btn { 
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: #fff;
    border: none;
    padding: 12px 20px;
    border-radius: 14px;
    cursor: pointer;
    font-weight: 700;
    box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  }
  
  .print-btn:hover {
    background: linear-gradient(135deg, #059669 0%, #047857 100%);
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(16, 185, 129, 0.4);
  }
  
  .muted { 
    color: var(--muted);
    font-size: 0.92rem;
  }
  /* hide print header on screen; shown in @media print below */
  .print-header { display: none; }
    .stats-top { display:flex; gap:12px; align-items:center; justify-content:space-between; margin-bottom:12px; }
    .highlight { background: linear-gradient(135deg,var(--accent-1),var(--accent-2)); color:#fff; padding:8px 12px; border-radius:8px; font-weight:700; box-shadow:0 8px 20px rgba(49,130,206,0.12); }
  
  .charts-grid { 
    display: grid;
    grid-template-columns: repeat(12, minmax(0, 1fr));
    gap: 20px;
    grid-auto-flow: dense;
  }
  
  .chart-card { 
    background: linear-gradient(145deg, #fff 0%, #f0fdff 100%);
    padding: 20px;
    border-radius: 18px;
    border: 2px solid rgba(6, 182, 212, 0.15);
    box-shadow: 0 8px 25px rgba(6, 182, 212, 0.12);
    display: flex;
    flex-direction: column;
    gap: 12px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
  }
  
  .chart-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, #06b6d4 0%, #0891b2 50%, #22d3ee 100%);
    opacity: 0;
    transition: opacity 0.3s ease;
  }
  
  .chart-card:hover { 
    transform: translateY(-4px);
    box-shadow: 0 16px 40px rgba(6, 182, 212, 0.25);
    border-color: rgba(6, 182, 212, 0.3);
  }
  
  .chart-card:hover::before {
    opacity: 1;
  }
  
  .chart-card h4 { 
    margin: 0;
    font-size: 1.05rem;
    color: #0f1724;
    display: flex;
    align-items: center;
    gap: 10px;
    font-weight: 700;
    letter-spacing: 0.3px;
  }
  
  .chart-card h4 i {
    color: #06b6d4;
  }
  
  .group-divider { 
    grid-column: 1 / -1;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 24px;
    border-radius: 16px;
    background: linear-gradient(145deg, rgba(255, 255, 255, 0.95) 0%, rgba(236, 254, 255, 0.9) 100%);
    border: 2px solid rgba(6, 182, 212, 0.2);
    box-shadow: 0 8px 25px rgba(6, 182, 212, 0.15);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    animation: fadeIn 0.6s ease;
  }
  
  .group-badge { 
    background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);
    color: #fff;
    font-weight: 800;
    padding: 8px 16px;
    border-radius: 999px;
    letter-spacing: 0.5px;
    font-size: 0.95rem;
    box-shadow: 0 4px 15px rgba(6, 182, 212, 0.3);
  }
  
  .group-title { 
    font-weight: 800;
    color: #0f1724;
    font-size: 1.15rem;
  }
  
  .group-desc { 
    color: var(--muted);
    font-size: 0.92rem;
  }
  
  .group-left {
    display: flex;
    align-items: center;
    gap: 12px;
  }
  
  .group-context {
    margin-top: 6px;
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    align-items: center;
  }
  
  .group-section {
    display: contents;
  }
  
  /* Span helper classes */
  .span-12 { grid-column: span 12; }
  .span-8 { grid-column: span 8; }
  .span-6 { grid-column: span 6; }
  .span-4 { grid-column: span 4; }
  .span-3 { grid-column: span 3; }
  .span-2 { grid-column: span 2; }
  
  .stats-top {
    display: flex;
    gap: 12px;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 16px;
  }
  
  .highlight {
    background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);
    color: #fff;
    padding: 8px 16px;
    border-radius: 10px;
    font-weight: 700;
    box-shadow: 0 4px 15px rgba(6, 182, 212, 0.3);
  }
  
  .chart-legend {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    font-size: 0.88rem;
    color: var(--muted);
  }
  
  .compact-canvas {
    display: flex;
    align-items: center;
    justify-content: center;
  }
  
  .compact-canvas canvas {
    width: 100% !important;
    height: auto !important;
    max-width: 360px;
    max-height: 240px;
  }
  
  /* Responsive breakpoints */
  @media (max-width: 1200px) {
    .charts-grid {
      grid-template-columns: repeat(8, minmax(0, 1fr));
    }
  }
  
  @media (max-width: 900px) {
    .charts-grid {
      grid-template-columns: 1fr;
    }
    .charts-grid > * {
      grid-column: 1 / -1 !important;
    }
    .chart-card canvas {
      width: 100% !important;
      height: auto !important;
    }
  }
  
  @media (max-width: 560px) {
    .charts-grid {
      grid-template-columns: 1fr;
    }
    .chart-card {
      padding: 14px;
    }
    .chart-card h4 {
      font-size: 0.95rem;
      line-height: 1.2;
    }
  }
  
  /* print-only captions placed under canvases for clarity */
  .print-caption { display:none; font-size:0.95rem; color:var(--muted); text-align:center; margin-top:8px; }
  /* donut overlay (center percent + small caption) */
  .donut-wrapper{ position:relative; width:100%; height:160px; display:flex; align-items:center; justify-content:center }
  /* remove the inner mini-square (we only want the donut + top percent) */
  .donut-wrapper::before{ display:none }
  .donut-overlay{ position:absolute; text-align:center; pointer-events:none; z-index:2 }
  /* show a beautiful, centered value inside donuts */
  /* cleaner center number for donuts (solid color set from JS) */
  .donut-overlay .percent{ display:block; font-size:1.6rem; font-weight:900; line-height:1; color:#0f1724; }
  .donut-overlay .count{ display:block; font-size:0.8rem; color: var(--muted); margin-top:4px }
  /* hide the old top percent badge on screen (still appears in print) */
  .kpi-top{ display:none }
  .kpi-percent{ font-size:1.6rem; font-weight:800 }
  .kpi-title{ margin:8px 0 0 0; text-align:center; font-size:0.9rem; color:#0f1724; letter-spacing:0.6px; font-weight:700 }
  /* make the composition pie larger visually */
  .large-pie canvas{ height:260px !important }
  /* mini card appearance for small KPI donuts (rounded inset) */
  .chart-card.small-kpi{ display:flex; align-items:center; justify-content:center; }
  .chart-card.small-kpi .donut-wrapper::before{ width:96px; height:96px }
  /* prettier overall bars */
  .chart-card .overall-track{ background: linear-gradient(90deg,#eef2f7,#f8fafc); box-shadow: inset 0 1px 2px rgba(15,23,42,0.06); }
  .chart-card .seg-pass{ filter: saturate(110%); }
  .chart-card .seg-fail{ filter: saturate(110%); }
  .chart-card .seg-pass:hover, .chart-card .seg-fail:hover{ filter: brightness(1.03) saturate(120%); }
    @media (max-width:1100px){ .layout{ grid-template-columns: 1fr; } .topbar{ left:0 } .main{ margin-left:0 } .sidebar{ display:none } }
    @media print {
      /* make printed pages clean and white */
      body { background: #fff !important; color: #0f1724 !important; }
      /* hide everything except the printable area */
      body * { visibility: hidden; }
      .printable, .printable * { visibility: visible; }
      .printable { position: relative; left: 0; top: 0; width: 100%; padding: 12px; }

  /* print header styling */
  .print-header { display:block !important; text-align:center; margin-bottom:12px; }
  .print-header h1 { font-size: 22px; margin: 0 0 6px 0; }
  .print-header #printMeta { font-size: 0.95rem; color: #334155; }

  /* Ensure chart titles and KPI titles are visible and readable in print */
  .chart-card h4, .kpi-title { display:block !important; color: #0f1724 !important; }
  .chart-card h4 { font-size: 12pt; margin-bottom:8px; font-weight:800; }
  .kpi-title { font-size: 11pt; }

  /* Show any overlay text that is hidden on-screen */
  .donut-overlay { visibility: visible !important; opacity: 1 !important; }
  .donut-overlay .percent, .donut-overlay .count { display: block !important; color: #0f1724 !important; }
  .donut-overlay .percent { font-size: 22px; font-weight:900; }
  .donut-overlay .count { font-size: 12px; color: #475569; }

  /* Chart.js legend (rendered inside canvas) may be small; allow an external legend if present */
  .chart-legend { display:block !important; }

  /* Make small KPI titles center and larger for readability */
  .kpi-top .kpi-percent { font-size: 20px !important; }

  /* Keep the same layout as the on-screen dashboard */
  .charts-grid { grid-template-columns: repeat(12, minmax(0, 1fr)); gap: 18px; }
  .span-12{ grid-column: span 12; }
  .span-8 { grid-column: span 8; }
  .span-6 { grid-column: span 6; }
  .span-4 { grid-column: span 4; }
  .span-3 { grid-column: span 3; }
  .span-2 { grid-column: span 2; }
  .chart-card { box-shadow: none !important; border: 1px solid #e6eef8 !important; background: #fff !important; }
      .chart-card h4 { font-size: 1rem; margin-bottom:6px }

      /* avoid page breaks inside chart cards */
      .chart-card { break-inside: avoid; page-break-inside: avoid; }

      /* make canvas scale to available width and reasonable height */
  canvas { width: 100% !important; height: auto !important; }
  /* remove small on-screen cap for compact canvases when printing */
  .compact-canvas canvas{ max-width: 100% !important; max-height: none !important; }
  /* allow donut containers to grow naturally for print */
  .donut-wrapper{ height: auto !important; }

  /* remove sidebar/topbar and reset main margins for full-width print */
  .sidebar, .topbar, .btn-clear, .print-btn, #confirmBtn, .mobile-legend-row { display: none !important; }
  .main { margin: 0 !important; }
  /* group headers readable on paper */
  .group-divider { background: #f3fafc !important; border-color:#dbeafe !important; box-shadow:none !important; }

      /* small tweaks for page margins and footer area */
      @page { margin: 12mm 10mm; }
    }
  </style>
</head>
<body>
  <?php include __DIR__ . '/includes/sidebar_common.php'; ?>
  <div class="topbar">
    <div class="dashboard-title">Engineering Admin Dashboard</div>
    <div><a class="logout-btn" href="#" onclick="confirmLogout(event)">Logout</a></div>
  </div>

  <div class="main">
    <div class="layout">
      <div class="filter-panel">
        <div class="filter-header">
          <h3 class="filter-title"><i class="fa-solid fa-filter"></i> Filters</h3>
          <button id="printBtn" class="print-btn" title="Print the statistics"><i class="fas fa-print"></i> Print Data Statistics</button>
        </div>
        <div class="step">
          <div class="step-header"><h4><i class="fa-solid fa-clipboard-list" style="opacity:.9"></i>&nbsp; Choose Board Exam Type</h4></div>
          <select id="examTypeSelect">
            <option value="">Select exam type</option>
            <?php foreach ($boardExamTypes as $t): ?>
              <option value="<?= htmlspecialchars($t['id']) ?>"><?= htmlspecialchars($t['exam_type_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="step">
          <div class="step-header"><h4><i class="fa-solid fa-calendar-days" style="opacity:.9"></i>&nbsp; Choose Exam Date</h4></div>
          <select id="examDateSelect" disabled>
            <option value="">Choose exam type first</option>
          </select>
          <div class="tip-pill"><i class="fa-solid fa-lightbulb"></i><span>You can also click a year chip below to view all dates in that year.</span></div>
          <div id="yearChips" class="year-chips"></div>
        </div>

        <div class="filter-actions">
          <button id="confirmBtn" class="btn-primary" disabled title="Load charts for the selected type/date"><i class="fas fa-check-circle"></i> Confirm & View</button>
          <button id="clearBtn" class="btn-clear" title="Reset filters"><i class="fas fa-eraser"></i> Clear</button>
        </div>

        <div style="margin-top:14px;">
          <div class="selected-header" style="display:flex; gap:12px; flex-wrap:wrap;">
            <div class="selected-pill" style="display:flex;align-items:center;gap:8px;"><i class="fa-solid fa-layer-group"></i><span id="selectedType">Overall</span></div>
            <div class="selected-pill" style="display:flex;align-items:center;gap:8px;"><i class="fa-solid fa-calendar"></i><span id="selectedDate">Overall</span></div>
          </div>
        </div>
      </div>

      <div class="visual-panel">
        <div class="stats-top">
          <div style="font-weight:700;">Data Statistics</div>
          <div class="muted">Interactive visualizations by group</div>
        </div>

  <div id="statsContainer" class="printable" style="position:relative;">
          <!-- Print header (populated before printing) -->
          <div id="printHeader" class="print-header" style="margin-bottom:12px;">
            <h1 style="margin:0; font-size:20px;">Engineering Statistics</h1>
            <div id="printMeta" style="margin-top:6px; color:var(--muted);">&nbsp;</div>
          </div>
          <div class="charts-grid">
            <!-- Group 1 Section -->
            <div class="group-section group-1">
            <div class="group-divider">
              <div class="group-left">
                <div class="group-badge">Group 1</div>
                <div>
                  <div class="group-title">Sex Comparison & Rates</div>
                  <div class="group-desc">Distribution by sex and pass rates</div>
                </div>
              </div>
            </div>
            <!-- Group 1: Sex comparison + individual doughnuts -->
            <div class="chart-card span-6">
              <h4>Sex Comparison (Distribution)</h4>
              <div class="compact-canvas"><canvas id="chartGenderPie" height="160"></canvas></div>
              <div class="print-caption">Sex comparison by sex (distribution of male vs female)</div>
            </div>
            <div class="chart-card small-kpi span-3">
              <div class="kpi-top"><div id="femaleTopPercent" class="kpi-percent">—</div></div>
              <div class="donut-wrapper">
                <canvas id="chartFemaleDonut" height="160"></canvas>
                <div id="femaleOverlay" class="donut-overlay"><div class="percent">—</div><div class="count">— / —</div></div>
              </div>
              <div class="print-caption">Female passed rate (Passed / Total)</div>
              <h4 class="kpi-title">Female Passed Rate</h4>
            </div>
            <div class="chart-card small-kpi span-3">
              <div class="kpi-top"><div id="maleTopPercent" class="kpi-percent">—</div></div>
              <div class="donut-wrapper">
                <canvas id="chartMaleDonut" height="160"></canvas>
                <div id="maleOverlay" class="donut-overlay"><div class="percent">—</div><div class="count">— / —</div></div>
              </div>
              <div class="print-caption">Male passed rate (Passed / Total)</div>
              <h4 class="kpi-title">Male Passed Rate</h4>
            </div>
            </div>

            <!-- Group 2 Section -->
            <div class="group-section group-2">
            <div class="group-divider">
              <div class="group-left">
                <div class="group-badge">Group 2</div>
                <div>
                  <div class="group-title">Result Composition & Rates</div>
                  <div class="group-desc">Passed / Failed / Conditional with detailed rates</div>
                </div>
              </div>
            </div>

            <!-- Group 2: Composition + pass/fail/cond donuts -->
            <div class="chart-card large-pie span-6">
              <h4>Passed / Failed / Conditional</h4>
              <div style="display:flex;align-items:center;justify-content:center;flex-direction:column;">
                <canvas id="chartCompositionPie" height="260"></canvas>
              </div>
              <div class="print-caption">Composition of results: Passed, Failed, Conditional</div>
            </div>
            <div class="chart-card small-kpi span-2">
              <div class="kpi-top"><div id="passTopPercent" class="kpi-percent">—</div></div>
              <div class="donut-wrapper">
                <canvas id="chartPassDonut" height="160"></canvas>
                <div id="passOverlay" class="donut-overlay"><div class="percent">—</div><div class="count">— / —</div></div>
              </div>
              <div class="print-caption">Passing rate (Passed / Total)</div>
              <h4 class="kpi-title">Passing Rate</h4>
            </div>
            <div class="chart-card small-kpi span-2">
              <div class="kpi-top"><div id="failTopPercent" class="kpi-percent">—</div></div>
              <div class="donut-wrapper">
                <canvas id="chartFailDonut" height="160"></canvas>
                <div id="failOverlay" class="donut-overlay"><div class="percent">—</div><div class="count">— / —</div></div>
              </div>
              <div class="print-caption">Failed rate (Failed / Total)</div>
              <h4 class="kpi-title">Failed Rate</h4>
            </div>
            <div class="chart-card small-kpi span-2">
              <div class="kpi-top"><div id="condTopPercent" class="kpi-percent">—</div></div>
              <div class="donut-wrapper">
                <canvas id="chartCondDonut" height="160"></canvas>
                <div id="condOverlay" class="donut-overlay"><div class="percent">—</div><div class="count">— / —</div></div>
              </div>
              <div class="print-caption">Conditional rate (Conditional / Total)</div>
              <h4 class="kpi-title">Conditional Rate</h4>
            </div>
            </div>

            <!-- Group 3 Section -->
            <div class="group-section group-3">
            <div class="group-divider">
              <div class="group-left">
                <div class="group-badge">Group 3</div>
                <div>
                  <div class="group-title">First Timer vs Repeater</div>
                  <div class="group-desc">Passing rate comparison</div>
                </div>
              </div>
            </div>

            <!-- Group 3: First Timer vs Repeater donuts -->
            <div class="chart-card small-kpi span-6">
              <div class="kpi-top"><div id="firstTopPercent" class="kpi-percent">—</div></div>
              <div class="donut-wrapper">
                <canvas id="chartFirstTimerDonut" height="160"></canvas>
                <div id="firstOverlay" class="donut-overlay"><div class="percent">—</div><div class="count">— / —</div></div>
              </div>
              <h4 class="kpi-title">First Timer Passing Rate</h4>
            </div>
            <div class="chart-card small-kpi span-6">
              <div class="kpi-top"><div id="repeaterTopPercent" class="kpi-percent">—</div></div>
              <div class="donut-wrapper">
                <canvas id="chartRepeaterDonut" height="160"></canvas>
                <div id="repeaterOverlay" class="donut-overlay"><div class="percent">—</div><div class="count">— / —</div></div>
              </div>
              <h4 class="kpi-title">Repeater Passing Rate</h4>
            </div>
            </div>

            <!-- Overall Trends (shown in Overall view only) -->
            <div id="deptPassingCard" class="chart-card" style="grid-column: 1 / -1;">
              <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                <h4 style="margin: 0;">Board Passing Rate in the College of Engineering Department (2019–2024)</h4>
                <button id="openDeptDetails" class="btn-secondary" style="padding: 8px 16px; border-radius: 10px; font-size: 0.9rem;">
                  <i class="fas fa-info-circle"></i> View Details
                </button>
              </div>
              <div id="deptDescription" class="muted" style="display: none; margin-bottom: 16px; font-size: 0.92rem; line-height: 1.5; padding: 14px; background: linear-gradient(145deg, #ecfeff 0%, #cffafe 100%); border-radius: 12px; border-left: 4px solid #06b6d4;">
                <i class="fas fa-chart-line" style="color: #06b6d4; margin-right: 6px;"></i>
                This visualization shows the overall board exam passing rate trend for the College of Engineering department from 2019 to 2024. 
                The data reflects the percentage of students who successfully passed their respective board examinations across all engineering disciplines during this period.
              </div>
              <canvas id="chartDeptPassingRate" height="220"></canvas>
              <div class="print-caption">Board passing rate in the College of Engineering department, 2019–2024</div>
            </div>
            <div id="overallTrendsCard" class="chart-card" style="grid-column: 1 / -1;">
              <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                <h4 style="margin: 0;">Board Passing Rate Trends (2019–2024)</h4>
                <button id="openTrendInfo" class="btn-secondary" style="padding: 8px 16px; border-radius: 10px; font-size: 0.9rem;">
                  <i class="fas fa-info-circle"></i> View Details
                </button>
              </div>
              <div id="trendDescription" class="muted" style="display: none; margin-bottom: 16px; font-size: 0.92rem; line-height: 1.5; padding: 14px; background: linear-gradient(145deg, #ecfeff 0%, #cffafe 100%); border-radius: 12px; border-left: 4px solid #06b6d4;">
                <i class="fas fa-chart-bar" style="color: #06b6d4; margin-right: 6px;"></i>
                This bar chart displays the passing rate trends for different engineering board exam types from 2019 to 2024. Each colored bar represents a specific board exam (EELE, EELE Electronics, REELE, RME), making it easy to compare performance across different disciplines and identify which exams have higher or lower passing rates over the years. This visualization helps administrators quickly identify trends and areas that may need additional support or improvement.
              </div>
              <canvas id="chartPassingTrendLine" height="220"></canvas>
              <div class="print-caption">Average passing rate by board exam type, 2019–2024</div>
            </div>
            <div id="forecastTrendsCard" class="chart-card" style="grid-column: 1 / -1;">
              <h4>Forecast: Passing Rate (Next 2 Years)</h4>
              <canvas id="chartPassingForecastLine" height="220"></canvas>
              <div id="forecastSummary" class="muted" style="margin-top:6px;font-size:0.9rem;"></div>
              <div class="print-caption">Linear regression forecast of passing rates per board exam type using latest history.</div>
            </div>
            <div id="overallStackedCard" class="chart-card" style="grid-column: 1 / -1;">
              <h4>Overall Passing Rate Composition by Exam Type (2019–2024)</h4>
              <div id="compositionModeControls" style="display:flex;align-items:center;gap:8px;margin:6px 0 8px 0;">
                <span class="muted">View:</span>
                <button id="compModePercent" class="btn-secondary" style="padding:6px 10px;border-radius:8px;line-height:1;">Percent</button>
                <button id="compModeCounts" class="btn-secondary" style="padding:6px 10px;border-radius:8px;line-height:1;">Counts</button>
              </div>
              <canvas id="chartPassingStackedArea" height="220"></canvas>
              <div id="compositionSummary" class="muted" style="margin-top:6px;font-size:0.9rem;"></div>
              <div class="print-caption">Share of passed examinees by board exam type (percent), 2019–2024</div>
            </div>

            <!-- New: Overall Examinees by Year (stacked totals) -->
            <div id="overallTotalsCard" class="chart-card" style="grid-column: 1 / -1;">
              <h4>Examinees by Year and Board Exam Type (2019–2024)</h4>
              <canvas id="chartTotalsStackedBar" height="240"></canvas>
              <div id="totalsSummary" class="muted" style="margin-top:6px;font-size:0.9rem;"></div>
              <div class="print-caption">Total examinees per year, stacked by board exam type, 2019–2024</div>
            </div>

            <!-- Group 4: Subjects area (dynamic title based on filters) -->
            <div class="chart-card" style="grid-column: 1 / -1;">
              <h4 id="subjectsHeader">Overall Subjects by Board Exam Type</h4>
              <div id="subjectsInfo" class="muted" style="margin-bottom:6px;">Select a board exam type and date, then click Confirm to load subjects.</div>
              <!-- View mode toggle -->
              <div id="subjectsModeControls" style="display:flex;align-items:center;gap:8px;margin:6px 0 8px 0;">
                <span class="muted">View:</span>
                <button id="modeCounts" class="btn-secondary" style="padding:6px 10px;border-radius:8px;line-height:1;">Counts</button>
                <button id="modePercent" class="btn-secondary" style="padding:6px 10px;border-radius:8px;line-height:1;">Percent</button>
              </div>
              <!-- Overall subjects mode toggle (shown in overall view) -->
              <div id="overallSubjectsModeControls" style="display:none;align-items:center;gap:8px;margin:6px 0 8px 0;">
                <span class="muted">Overall Subjects View:</span>
                <button id="overallModeCounts" class="btn-secondary" style="padding:6px 10px;border-radius:8px;line-height:1;">Counts</button>
                <button id="overallModePercent" class="btn-secondary" style="padding:6px 10px;border-radius:8px;line-height:1;">Percent</button>
              </div>
              <canvas id="chartSubjectsBar" height="260"></canvas>
              <div class="print-caption">Subjects (Passed vs Failed) for the selected board exam type and date</div>
              <!-- Overall (no selection) subjects list grouped by exam type -->
              <div id="overallSubjectsList" style="display:none; margin-top:10px;"></div>
            </div>
            
            <!-- Students-Subjects Detailed Modal -->
            <div id="studentsSubjectsModal" style="display:none; position:fixed; left:0; top:0; right:0; bottom:0; background:rgba(2,6,23,0.5); align-items:center; justify-content:center; z-index:21000;">
              <div style="width:94%; max-width:1100px; background:#fff; border-radius:12px; padding:16px; box-shadow:0 14px 40px rgba(2,6,23,0.12); position:relative;">
                <button id="closeStudentsSubjects" style="position:absolute; right:12px; top:12px; background:transparent;border:none;font-size:20px;cursor:pointer;color:#374151">&times;</button>
                <h3 id="studentsSubjectsTitle" style="margin:0 0 10px 0; font-size:1.1rem;">Student Subject Grades</h3>
                <div id="studentsSubjectsBody" style="max-height:560px; overflow:auto;">
                  <!-- content injected dynamically -->
                </div>
              </div>
            </div>
          </div>

          <div style="margin-top:18px;">
            <div style="display:flex;gap:12px;flex-wrap:wrap;">
              <div class="chart-card" style="flex:1;min-width:220px;">
                <h4>KPIs</h4>
                <div id="kpiTotal" style="font-size:1.4rem;font-weight:700;">—</div>
                <div id="kpiPassed" class="muted">—</div>
              </div>

              <div class="chart-card" style="flex:1;min-width:220px;">
                <h4>Notes</h4>
                <div class="muted">Click chart slices or bars to highlight corresponding records in the table (future enhancement).</div>
              </div>
            </div>
          </div>
          
          <!-- Modal for showing records when clicking charts -->
          <div id="recordsModal" style="display:none; position:fixed; left:0; top:0; right:0; bottom:0; background:rgba(2,6,23,0.5); align-items:center; justify-content:center; z-index:20000;">
            <div style="width:90%; max-width:1100px; background:#fff; border-radius:12px; padding:16px; box-shadow:0 14px 40px rgba(2,6,23,0.12); position:relative;">
              <button id="closeRecordsModal" style="position:absolute; right:12px; top:12px; background:transparent;border:none;font-size:20px;cursor:pointer;color:#374151">&times;</button>
              <h3 id="recordsModalTitle" style="margin:0 0 12px 0; font-size:1.1rem;">Records</h3>
              <div id="recordsCount" class="muted" style="margin-bottom:8px">—</div>
              <div style="max-height:520px; overflow:auto;">
                <table id="recordsTable" style="width:100%; border-collapse:collapse; font-size:0.95rem;">
                  <thead>
                    <tr style="background:#f8fafc; position:sticky; top:0;">
                      <th style="padding:8px;border-bottom:1px solid #e6eef8;text-align:left">Name</th>
                      <th style="padding:8px;border-bottom:1px solid #e6eef8">Sex</th>
                      <th style="padding:8px;border-bottom:1px solid #e6eef8">Course</th>
                      <th style="padding:8px;border-bottom:1px solid #e6eef8">Year Graduated</th>
                      <th style="padding:8px;border-bottom:1px solid #e6eef8">Exam Date</th>
                      <th style="padding:8px;border-bottom:1px solid #e6eef8">Result</th>
                    </tr>
                  </thead>
                  <tbody></tbody>
                </table>
              </div>
            </div>
          </div>

          
          <style>
            @keyframes modalSlideIn {
              0% { transform: scale(0.8) translateY(-20px); opacity: 0; }
              100% { transform: scale(1) translateY(0); opacity: 1; }
            }
          </style>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script>
    // Lightweight 3D-like pie/doughnut effect using HTML5 Canvas: gradient fill + soft shadow
    // Note: Bootstrap is used for general layout; the charts use HTML5 Canvas (Chart.js) with a custom plugin.
  const PIE3D_PLUGIN = {
      id: 'pie3d',
      beforeDatasetDraw(chart, args, pluginOptions) {
        if (!pluginOptions || pluginOptions.enabled !== true) return;
        // apply to both pie and doughnut charts
        if (chart.config.type !== 'pie' && chart.config.type !== 'doughnut') return;
        const ctx = chart.ctx;
        ctx.save();
        ctx.shadowColor = 'rgba(15,23,42,0.18)';
        ctx.shadowBlur = 16;
        ctx.shadowOffsetY = 8;
      },
      afterDatasetDraw(chart, args, pluginOptions){
        if (!pluginOptions || pluginOptions.enabled !== true) return;
        if (chart.config.type !== 'pie' && chart.config.type !== 'doughnut') return;
        chart.ctx.restore();
      }
    };
    if (typeof Chart !== 'undefined' && Chart.register) { Chart.register(PIE3D_PLUGIN); }

    // Mobile detection and responsive helpers
    function isMobile(){ return (window.innerWidth || document.documentElement.clientWidth || 0) <= 560; }
    let __lastMobileState = isMobile();
    function debounce(fn, wait){ let t; return function(){ clearTimeout(t); const args=arguments, ctx=this; t=setTimeout(()=>fn.apply(ctx,args), wait); }; }

    // Build a small, collapsible legend under a chart card (mobile only)
    function maybeCreateLegendToggle(cardId, chart){
      try{
        const mobile = isMobile();
        const card = document.getElementById(cardId);
        if (!card) return;
        let row = card.querySelector('.mobile-legend-row');
        if (!mobile){ if (row) row.remove(); return; }
        if (!row){
          row = document.createElement('div');
          row.className = 'mobile-legend-row';
          row.style.marginTop = '6px';
          row.innerHTML = '<button class="btn-secondary" style="padding:6px 10px;border-radius:8px;line-height:1;display:inline-flex;gap:8px;align-items:center;"><i class="fa-solid fa-list"></i> Legend</button><div class="mobile-legend-list" style="margin-top:6px;"></div>';
          card.appendChild(row);
        }
        const btn = row.querySelector('button');
        const list = row.querySelector('.mobile-legend-list');
        const ds = (chart && chart.config && chart.config.data && chart.config.data.datasets) ? chart.config.data.datasets : [];
        list.innerHTML = ds.map(d => {
          const color = (d.borderColor || d.backgroundColor || '#94a3b8');
          const label = escapeHtml(d.label || '');
          return `<div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;white-space:nowrap;"><span style="display:inline-block;width:10px;height:10px;border-radius:3px;background:${color}"></span><span>${label}</span></div>`;
        }).join('');
        // collapsed by default
        list.classList.remove('open');
        btn.onclick = function(){
          if (list.classList.contains('open')) { list.classList.remove('open'); }
          else { list.classList.add('open'); }
        };
      }catch(_){}
    }

    function lighten(hex, amt){
      // hex like #rrggbb
      try{
        const c = hex.replace('#','');
        const r = Math.min(255, Math.max(0, parseInt(c.substring(0,2),16) + Math.round(255*amt)));
        const g = Math.min(255, Math.max(0, parseInt(c.substring(2,4),16) + Math.round(255*amt)));
        const b = Math.min(255, Math.max(0, parseInt(c.substring(4,6),16) + Math.round(255*amt)));
        return '#'+r.toString(16).padStart(2,'0')+g.toString(16).padStart(2,'0')+b.toString(16).padStart(2,'0');
      }catch(_){ return hex; }
    }
    function darken(hex, amt){ return lighten(hex, -Math.abs(amt)); }
    // Preloaded dates from PHP for fast client filtering
    const boardExamDates = <?= json_encode($boardExamDates) ?>;
    const boardExamTypes = <?= json_encode($boardExamTypes) ?>;

    // Common chart options used to make charts look consistent and animated
    // Added tooltip callback to always show absolute count and percent so tooltips match
    // server-provided dataset values (helps avoid perceived mismatches).
    const COMMON_CHART_OPTIONS = {
      responsive: true,
      animation: { duration: 650, easing: 'easeOutQuart' },
      plugins: {
        tooltip: {
          mode: 'nearest',
          intersect: true,
          padding: 8,
          callbacks: {
            // show label: value (percent%) using the underlying dataset values
            label: function(context) {
              const label = context.label || '';
              // `raw` is available for Chart.js v3+; fall back to dataset value
              const value = (context.raw !== undefined) ? context.raw : (context.dataset && context.dataset.data ? context.dataset.data[context.dataIndex] : '');
              // compute total for percent calculation using the first dataset
              let total = 0;
              try {
                const data = context.chart.data.datasets[0].data || [];
                for (let i = 0; i < data.length; i++) total += Number(data[i]) || 0;
              } catch (e) { total = 0; }
              const pct = total ? Math.round((Number(value) / total) * 100) : 0;
              return `${label}: ${value} (${pct}%)`;
            }
          }
        },
        legend: { position: 'bottom', labels: { boxWidth: 12, padding: 8, usePointStyle: true, pointStyle: 'circle' } }
      }
    };

    // helper to produce donut-specific options (larger cutout, no legend, colored tooltip)
    function donutOptions(customCutout){
      const o = JSON.parse(JSON.stringify(COMMON_CHART_OPTIONS));
      // create a large hole for the donut so centered label fits; allow override for thicker ring
      o.cutout = customCutout || '70%';
      o.plugins = o.plugins || {};
      o.plugins.legend = { display: false };
      o.plugins.tooltip = Object.assign({}, o.plugins.tooltip, { backgroundColor: '#0f1724', titleColor: '#fff', bodyColor: '#fff' });
      // disable soft shadow for donuts to avoid drop-shadow box around canvas
      o.plugins.pie3d = { enabled: false };
      // make arcs feel rounded and crisp
      o.elements = Object.assign({}, o.elements || {}, { arc: { borderWidth: 2 } });
      return o;
    }

    // options for the large composition pie: keep legend and show below
  const PIE_LARGE_OPTIONS = (function(){ const o = JSON.parse(JSON.stringify(COMMON_CHART_OPTIONS)); o.maintainAspectRatio = false; o.plugins = o.plugins || {}; o.plugins.legend = { position: 'bottom', labels:{ boxWidth:12, usePointStyle:true, pointStyle:'circle' } }; return o; })();

    const examTypeSelect = document.getElementById('examTypeSelect');
  const examDateSelect = document.getElementById('examDateSelect');
  const confirmBtn = document.getElementById('confirmBtn');
    const selectedTypeEl = document.getElementById('selectedType');
    const selectedDateEl = document.getElementById('selectedDate');
    const printBtn = document.getElementById('printBtn');

    let selectedTypeId = '';
  let selectedDateId = '';
  // Also allow filtering by year (from Year options added to Exam Date dropdown)
  let selectedYear = '';

    examTypeSelect.addEventListener('change', (e) => {
      selectedTypeId = e.target.value;
      // Update subjects header based on selection (type only)
      updateSubjectsHeader();
      // hide overall subjects list when a type is chosen
      const overallList = document.getElementById('overallSubjectsList');
      if (overallList) {
        overallList.style.display = 'none';
        overallList.innerHTML = '';
      }
      // destroy any existing overall mini charts
      if (window.__overallSubjectCharts && Array.isArray(window.__overallSubjectCharts)) {
        try { window.__overallSubjectCharts.forEach(ch => ch && ch.destroy && ch.destroy()); } catch(_){}
        window.__overallSubjectCharts = [];
      }
      // show the main subjects chart canvas again
      const chartCanvas = document.getElementById('chartSubjectsBar');
      if (chartCanvas) chartCanvas.style.display = '';
      // show the counts/percent toggle for subjects chart
      const modeCtl = document.getElementById('subjectsModeControls');
      if (modeCtl) modeCtl.style.display = '';
  // hide overall subjects toggle in filtered mode
  const overallCtl = document.getElementById('overallSubjectsModeControls');
  if (overallCtl) overallCtl.style.display = 'none';
      // populate dates that match exam_type_id
      examDateSelect.innerHTML = '';
      const placeholder = document.createElement('option'); placeholder.value = ''; placeholder.textContent = '-- Select exam date --';
      examDateSelect.appendChild(placeholder);
      let has = false;
      const yearsSet = new Set();
      boardExamDates.forEach(d => {
        if (String(d.exam_type_id) === String(selectedTypeId)) {
          const opt = document.createElement('option'); opt.value = d.id; opt.textContent = d.exam_date + (d.exam_description ? ' — ' + d.exam_description : '');
          examDateSelect.appendChild(opt); has = true;
          // collect unique years
          try { const y = String(d.exam_date).slice(0,4); if (y) yearsSet.add(y); } catch(_) {}
        }
      });
      // Add per-year options for convenience
      if (yearsSet.size > 0) {
        const sep = document.createElement('option'); sep.value = ''; sep.disabled = true; sep.textContent = '— By Year —';
        examDateSelect.appendChild(sep);
        Array.from(yearsSet).sort((a,b)=>Number(b)-Number(a)).forEach(y => {
          const o = document.createElement('option'); o.value = 'Y:'+y; o.textContent = `Year ${y} — All dates`;
          examDateSelect.appendChild(o);
        });
        has = true;
      }
      examDateSelect.disabled = !has;
      confirmBtn.disabled = true;
      examDateSelect.value = '';
      selectedTypeEl.textContent = '--'; selectedDateEl.textContent = '--';
      selectedYear = '';

      // Render quick year chips
      (function renderYearChips(){
        const wrap = document.getElementById('yearChips'); if (!wrap) return;
        wrap.innerHTML = '';
        const years = Array.from(yearsSet).sort((a,b)=>Number(b)-Number(a));
        if (!years.length) { wrap.style.display = 'none'; return; }
        years.forEach(y => {
          const chip = document.createElement('div');
          chip.className = 'chip'; chip.textContent = y; chip.title = `Show all dates in ${y}`;
          chip.addEventListener('click', () => {
            // toggle active
            wrap.querySelectorAll('.chip').forEach(c => c.classList.remove('active'));
            chip.classList.add('active');
            selectedYear = String(y);
            selectedDateId = '';
            examDateSelect.value = '';
            confirmBtn.disabled = !selectedTypeId || (!selectedDateId && !selectedYear);
            const t = boardExamTypes.find(x => String(x.id) === String(selectedTypeId));
            selectedTypeEl.textContent = t ? t.exam_type_name : '—';
            selectedDateEl.textContent = 'Year ' + selectedYear;
            updateSubjectsHeader();
          });
          wrap.appendChild(chip);
        });
        wrap.style.display = 'flex';
      })();

      // Subjects should auto-appear once exam type is selected (no date filter)
      if (selectedTypeId) {
        const base = 'stats_engineering.php';
        fetch(`${base}?action=subjects&boardExamTypeId=${encodeURIComponent(selectedTypeId)}`)
          .then(r => r.json())
          .then(subj => { if (subj && subj.success) { renderSubjectsChart(subj.data || []); } else { renderSubjectsChart([]); } })
          .catch(() => renderSubjectsChart([]));
      } else {
        renderSubjectsChart([]);
      }
    });

    // Note: exam type search was removed per latest UI; related JS helpers were cleaned up.

    examDateSelect.addEventListener('change', (e) => {
      const val = e.target.value;
      // detect Year option values like 'Y:2024'
      if (val && String(val).startsWith('Y:')) { selectedYear = String(val).split(':')[1] || ''; selectedDateId = ''; }
      else { selectedDateId = val; selectedYear = ''; }
      confirmBtn.disabled = (!selectedTypeId || (!selectedDateId && !selectedYear));
      // update selected labels
      const t = boardExamTypes.find(x => String(x.id) === String(selectedTypeId));
      const d = boardExamDates.find(x => String(x.id) === String(selectedDateId));
      selectedTypeEl.textContent = t ? t.exam_type_name : '—';
      selectedDateEl.textContent = selectedYear ? ('Year ' + selectedYear) : (d ? d.exam_date + (d.exam_description ? ' — ' + d.exam_description : '') : '—');
      updateSubjectsHeader();
      // deselect chips when date chosen
      if (selectedDateId) {
        const wrap = document.getElementById('yearChips'); if (wrap) wrap.querySelectorAll('.chip').forEach(c => c.classList.remove('active'));
      }
    });

    confirmBtn.addEventListener('click', () => {
      if (!selectedTypeId || (!selectedDateId && !selectedYear)) return;
      updateSubjectsHeader();
      // call stats endpoints
      const dateObj = selectedDateId ? boardExamDates.find(x => String(x.id) === String(selectedDateId)) : null;
      const typeObj = boardExamTypes.find(x => String(x.id) === String(selectedTypeId));
      const examDate = dateObj ? dateObj.exam_date : '';
      const boardExamType = typeObj ? typeObj.exam_type_name : '';

      const base = 'stats_engineering.php';

      // show loading overlay while fetching
      showLoading(true);

      // fetch all in parallel and update charts together to avoid flicker/disappearance
  // include authoritative IDs so server filters by DB records
  let pKpis, pGender, pComp, pSubjects;
  if (selectedYear) {
    const from = `${selectedYear}-01-01`; const to = `${selectedYear}-12-31`;
    pKpis = fetch(`${base}?action=kpis&boardExamType=${encodeURIComponent(boardExamType)}&fromDate=${encodeURIComponent(from)}&toDate=${encodeURIComponent(to)}&boardExamTypeId=${encodeURIComponent(selectedTypeId)}`).then(r=>r.json());
    pGender = fetch(`${base}?action=gender&boardExamType=${encodeURIComponent(boardExamType)}&fromDate=${encodeURIComponent(from)}&toDate=${encodeURIComponent(to)}&boardExamTypeId=${encodeURIComponent(selectedTypeId)}`).then(r=>r.json());
    pComp = fetch(`${base}?action=composition&boardExamType=${encodeURIComponent(boardExamType)}&fromDate=${encodeURIComponent(from)}&toDate=${encodeURIComponent(to)}&boardExamTypeId=${encodeURIComponent(selectedTypeId)}`).then(r=>r.json());
    // subjects endpoint supports examYear for year-wide filtering
    pSubjects = fetch(`${base}?action=subjects&boardExamTypeId=${encodeURIComponent(selectedTypeId)}&examYear=${encodeURIComponent(selectedYear)}`).then(r=>r.json());
  } else {
    pKpis = fetch(`${base}?action=kpis&boardExamType=${encodeURIComponent(boardExamType)}&examDate=${encodeURIComponent(examDate)}&boardExamTypeId=${encodeURIComponent(selectedTypeId)}&examDateId=${encodeURIComponent(selectedDateId)}`).then(r=>r.json());
    pGender = fetch(`${base}?action=gender&boardExamType=${encodeURIComponent(boardExamType)}&examDate=${encodeURIComponent(examDate)}&boardExamTypeId=${encodeURIComponent(selectedTypeId)}&examDateId=${encodeURIComponent(selectedDateId)}`).then(r=>r.json());
    pComp = fetch(`${base}?action=composition&boardExamType=${encodeURIComponent(boardExamType)}&examDate=${encodeURIComponent(examDate)}&boardExamTypeId=${encodeURIComponent(selectedTypeId)}&examDateId=${encodeURIComponent(selectedDateId)}`).then(r=>r.json());
    pSubjects = fetch(`${base}?action=subjects&boardExamTypeId=${encodeURIComponent(selectedTypeId)}&examDateId=${encodeURIComponent(selectedDateId)}`).then(r=>r.json());
  }

      Promise.all([pKpis, pGender, pComp, pSubjects]).then(([kpis, gender, comp, subjects]) => {
        if (kpis && kpis.success) {
          document.getElementById('kpiTotal').textContent = kpis.data.total + ' total records';
          document.getElementById('kpiPassed').textContent = kpis.data.passed + ' passed';
          renderFirstRepeaterDonuts(kpis.data);
        }
        if (gender && gender.success) {
        console.log('DEBUG: gender response', gender.data);
        renderGenderPie(gender.data);
        renderGenderDonuts(gender.data);
        }
        if (comp && comp.success) {
          renderCompositionPie(comp.data);
          renderPassFailCondDonuts(comp.data);
        }
        if (subjects && subjects.success) {
          renderSubjectsChart(subjects.data || []);
        } else {
          renderSubjectsChart([]);
        }
      }).catch(err => { console.error(err); }).finally(()=> {
        // Keep overall charts visible even when filters are applied
        const deptCard = document.getElementById('deptPassingCard'); if (deptCard) deptCard.style.display = '';
        const trendCard = document.getElementById('overallTrendsCard'); if (trendCard) trendCard.style.display = '';
        const forecastCard = document.getElementById('forecastTrendsCard'); if (forecastCard) forecastCard.style.display = '';
        const stackedCard = document.getElementById('overallStackedCard'); if (stackedCard) stackedCard.style.display = '';
        showLoading(false);
      });
    });

    printBtn.addEventListener('click', () => {
      // populate print header with current selection and timestamp, then print
      populatePrintHeader();
      // allow layout to update before triggering print
      setTimeout(() => window.print(), 120);
    });

    // Populate print header metadata with selected filters and timestamp
    function populatePrintHeader(){
      const hdr = document.getElementById('printHeader');
      const meta = document.getElementById('printMeta');
      if (!hdr || !meta) return;
      const typeText = selectedTypeEl ? selectedTypeEl.textContent : 'Overall';
      const dateText = selectedDateEl ? selectedDateEl.textContent : 'Overall';
      const ts = new Date().toLocaleString();
      meta.innerHTML = `Type: ${escapeHtml(typeText)} &nbsp; | &nbsp; Date: ${escapeHtml(dateText)} &nbsp; | &nbsp; Generated: ${escapeHtml(ts)}`;
    }

    // Keep header populated when user uses browser print controls
    if (window.matchMedia) {
      window.addEventListener('beforeprint', populatePrintHeader);
      window.addEventListener('afterprint', function(){ /* no-op for now */ });
    }

    // Clear filter button behaviour: reset selects and show overall stats
    const clearBtn = document.getElementById('clearBtn');
    clearBtn.addEventListener('click', () => {
      // reset selects
      examTypeSelect.value = '';
      examDateSelect.innerHTML = '<option value="">-- Choose exam type first --</option>';
      examDateSelect.disabled = true;
      selectedTypeEl.textContent = 'Overall';
      selectedDateEl.textContent = 'Overall';
      selectedTypeId = '';
      selectedDateId = '';
  selectedYear = '';
      confirmBtn.disabled = true;
      const wrap = document.getElementById('yearChips'); if (wrap){ wrap.innerHTML=''; wrap.style.display='none'; }
      // reload overall stats
      loadOverallStats();
    });

    // Dynamic title for subjects area
    function updateSubjectsHeader(){
      const hdr = document.getElementById('subjectsHeader'); if (!hdr) return;
      const t = boardExamTypes.find(x => String(x.id) === String(selectedTypeId));
      const d = boardExamDates.find(x => String(x.id) === String(selectedDateId));
      if (!selectedTypeId) {
        hdr.textContent = 'Overall Subjects by Board Exam Type';
        return;
      }
      if (selectedTypeId && !selectedDateId && !selectedYear) {
        hdr.textContent = `Subjects — ${t ? t.exam_type_name : 'Selected Type'}`;
        return;
      }
      // type + date or year selected
      if (selectedYear) {
        hdr.textContent = `Subjects — ${t ? t.exam_type_name : 'Selected Type'} (Year ${selectedYear})`;
      } else {
        hdr.textContent = `Subjects — ${t ? t.exam_type_name : 'Selected Type'} (${d ? d.exam_date : ''})`;
      }
    }

  // Chart instances
    let genderPie=null, compositionPie=null;
    let femaleDonut=null, maleDonut=null;
    let passDonut=null, failDonut=null, condDonut=null;
    let firstTimerDonut=null, repeaterDonut=null;
  let subjectsBar=null; let subjectsBarIds=[];
  // track overall-mode mini charts so we can destroy them when switching filters
  window.__overallSubjectCharts = [];
  // Subjects chart view mode and last data cache
  window.__subjectsMode = 'count';
  let subjectsRawRows = [];
  // Overall trends chart instances
  let passingTrendChart = null;
  let passingStackedChart = null;
  let deptPassingChart = null;
  let passingForecastChart = null;
  const FORECAST_HORIZON = 2;
  // Mode for composition chart (percent vs counts)
  window.__compositionMode = window.__compositionMode || 'percent';
  // Cache last payload so toggling does not require refetch
  let lastTrendPayload = null;
  let lastStackedPayload = null;
  let lastTotalsPayload = null;
  let lastDeptPayload = null;
  let totalsStackedChart = null;
  // Overall subjects mode (counts or percent)
  window.__overallSubjectsMode = window.__overallSubjectsMode || 'percent';

    // Loading overlay
    function showLoading(show) {
      let overlay = document.getElementById('loadingOverlay');
      if (!overlay) {
        overlay = document.createElement('div'); overlay.id = 'loadingOverlay';
        overlay.style.position = 'absolute'; overlay.style.left = '260px'; overlay.style.top = '70px'; overlay.style.right = '0'; overlay.style.bottom = '0'; overlay.style.background = 'rgba(255,255,255,0.6)'; overlay.style.display = 'flex'; overlay.style.alignItems = 'center'; overlay.style.justifyContent = 'center'; overlay.style.zIndex = '9999'; overlay.style.backdropFilter = 'blur(3px)';
        overlay.innerHTML = '<div style="padding:18px;background:#fff;border-radius:8px;box-shadow:0 6px 20px rgba(0,0,0,0.1);font-weight:700;">Loading statistics</div>';
        document.body.appendChild(overlay);
      }
      overlay.style.display = show ? 'flex' : 'none';
    }

    function updateOrCreateChart(instance, ctx, cfg) {
      // apply default options when none provided so charts share the same look
      if (!cfg.options) cfg.options = COMMON_CHART_OPTIONS;
      // if chart instance exists, update its config/data instead of recreating
      if (instance) {
        // update the underlying config which is more robust across Chart.js versions
        instance.config.data = cfg.data;
        instance.config.options = cfg.options;
        if (cfg.type && instance.config.type !== cfg.type) instance.config.type = cfg.type;
        instance.update();
        return instance;
      }
      return new Chart(ctx, cfg);
    }

    function renderGenderPie(arr) {
      // Normalize labels and totals defensively so 'M'/'F' or mixed-case values work
      const labels = arr.map(r=> String(r.gender || 'Unknown'));
      const totals = arr.map(r=> Number.parseInt(r.total || 0, 10) || 0);
      // map gender to requested colors: Female -> magenta, Male -> teal
      let bg = labels.map(l => {
        if (!l) return PALETTE[7];
        const s = String(l).trim().toLowerCase();
        // accept single-letter forms too
        if (s === 'female' || s === 'f' || s.startsWith('f')) return PALETTE[1];
        if (s === 'male' || s === 'm' || s.startsWith('m')) return PALETTE[0];
        return PALETTE[7];
      });

      // If there is no data (all totals are zero) show muted placeholders so the chart remains visible
      const totalSum = totals.reduce((a,b)=>a+(b||0),0);
      if (!totalSum) {
        // keep original labels if present, but show equal muted slices so pie is visible
        const safeLabels = labels.length ? labels : ['No data'];
        const safeTotals = safeLabels.map(()=>1);
        const muted = PALETTE[7];
        bg = safeLabels.map(()=>muted);
        // override data/labels for the no-data state
        genderPie = updateOrCreateChart(genderPie, document.getElementById('chartGenderPie').getContext('2d'), { type:'pie', data:{ labels: safeLabels, datasets:[{ data:safeTotals, backgroundColor: bg }] }, options: COMMON_CHART_OPTIONS });
        return;
      }
      const ctx = document.getElementById('chartGenderPie').getContext('2d');
  genderPie = updateOrCreateChart(genderPie, ctx, { type:'pie', data:{ labels, datasets:[{ data:totals, backgroundColor: bg }] }, options: COMMON_CHART_OPTIONS });

      // add click handler: show records for clicked gender
      ctx.canvas.onclick = function(evt){
        const points = genderPie.getElementsAtEventForMode(evt, 'nearest', { intersect: true }, true);
        if (!points.length) return;
        const idx = points[0].index; const label = genderPie.data.labels[idx];
        fetchRecords('gender', label, `Sex: ${label}`, {}, evt);
      };
    }

    function renderGenderDonuts(arr) {
      // find female and male totals/passed (handle 'M'/'F' or full words)
      function findBySex(list, test) {
        for (let i = 0; i < list.length; i++) {
          const g = String(list[i].gender || '').trim().toLowerCase();
          if (!g) continue;
          if (test(g)) return list[i];
        }
        return null;
      }
      const female = findBySex(arr, g => g === 'female' || g === 'f' || g.startsWith('f')) || { total:0, passed:0 };
      const male = findBySex(arr, g => g === 'male' || g === 'm' || g.startsWith('m')) || { total:0, passed:0 };

  let fTot = Number.parseInt(female.total || 0, 10) || 0, fPass = Number.parseInt(female.passed || 0, 10) || 0;
  let mTot = Number.parseInt(male.total || 0, 10) || 0, mPass = Number.parseInt(male.passed || 0, 10) || 0;

  // defensive clamp: passed should not exceed total; if it does, clamp and log for investigation
  if (fPass > fTot) { console.warn('Gender data inconsistency: female passed > female total', fPass, fTot); fPass = fTot; }
  if (mPass > mTot) { console.warn('Gender data inconsistency: male passed > male total', mPass, mTot); mPass = mTot; }

      const fData = fTot ? [fPass, Math.max(0,fTot-fPass)] : [0,1];
      const mData = mTot ? [mPass, Math.max(0,mTot-mPass)] : [0,1];

    const fCtx = document.getElementById('chartFemaleDonut').getContext('2d');
  femaleDonut = updateOrCreateChart(femaleDonut, fCtx, { type:'doughnut', data:{ labels:['Passed','Not Passed'], datasets:[{ data:fData, backgroundColor: [createVerticalGradient(fCtx, PALETTE[1]),'#f3f4f6'], borderWidth:2, borderColor:[darken(PALETTE[1],0.12),'#e5e7eb'], hoverOffset:8, spacing: 2 }] }, options: donutOptions('62%') });
  // set overlay and top percent for female
  const fPercent = fTot ? Math.round((fPass / fTot) * 100) : 0;
  setDonutOverlay('femaleOverlay', fPercent, `${fPass} / ${fTot}`, PALETTE[1]);
  setTopPercent('femaleTopPercent', fPercent, PALETTE[1]);
  // add/update a small legend under the female donut
  (function(){
    const wrapper = document.getElementById('chartFemaleDonut').parentElement;
    if (!wrapper) return;
    // insert legend just after wrapper if not present
    let leg = wrapper.nextElementSibling;
    if (!leg || !(leg.classList && leg.classList.contains('chart-legend'))) {
      leg = document.createElement('div');
      leg.className = 'chart-legend';
      leg.style.justifyContent = 'center';
      leg.style.marginTop = '4px';
      wrapper.parentElement.insertBefore(leg, wrapper.nextSibling);
    }
    leg.innerHTML = `<span style="display:inline-flex;align-items:center;gap:6px;"><span style="display:inline-block;width:12px;height:12px;background:${PALETTE[1]};border-radius:2px;border:1px solid ${darken(PALETTE[1],0.12)};"></span>Passed</span>
    <span style="display:inline-flex;align-items:center;gap:6px;"><span style="display:inline-block;width:12px;height:12px;background:#f3f4f6;border-radius:2px;border:1px solid #e5e7eb;"></span>Not Passed</span>`;
  })();

  const mCtx = document.getElementById('chartMaleDonut').getContext('2d');
  maleDonut = updateOrCreateChart(maleDonut, mCtx, { type:'doughnut', data:{ labels:['Passed','Not Passed'], datasets:[{ data:mData, backgroundColor: [createVerticalGradient(mCtx, PALETTE[0]),'#f3f4f6'], borderWidth:2, borderColor:[darken(PALETTE[0],0.12),'#e5e7eb'], hoverOffset:8, spacing: 2 }] }, options: donutOptions('62%') });
  const mPercent = mTot ? Math.round((mPass / mTot) * 100) : 0;
  setDonutOverlay('maleOverlay', mPercent, `${mPass} / ${mTot}`, PALETTE[0]);
  setTopPercent('maleTopPercent', mPercent, PALETTE[0]);
  // add/update a small legend under the male donut
  (function(){
    const wrapper = document.getElementById('chartMaleDonut').parentElement;
    if (!wrapper) return;
    let leg = wrapper.nextElementSibling;
    if (!leg || !(leg.classList && leg.classList.contains('chart-legend'))) {
      leg = document.createElement('div');
      leg.className = 'chart-legend';
      leg.style.justifyContent = 'center';
      leg.style.marginTop = '4px';
      wrapper.parentElement.insertBefore(leg, wrapper.nextSibling);
    }
    leg.innerHTML = `<span style="display:inline-flex;align-items:center;gap:6px;"><span style="display:inline-block;width:12px;height:12px;background:${PALETTE[0]};border-radius:2px;border:1px solid ${darken(PALETTE[0],0.12)};"></span>Passed</span>
    <span style="display:inline-flex;align-items:center;gap:6px;"><span style="display:inline-block;width:12px;height:12px;background:#f3f4f6;border-radius:2px;border:1px solid #e5e7eb;"></span>Not Passed</span>`;
  })();

      // clicking female/male donut shows passed/not passed records
      fCtx.canvas.onclick = function(evt){
        const points = femaleDonut.getElementsAtEventForMode(evt,'nearest',{intersect:true},true);
        if (!points.length) return;
        const i = points[0].index;
        const label = femaleDonut.data.labels[i];
        // label is 'Passed' or 'Not Passed' — map 'Not Passed' to a notPassed filter on server
        if (String(label).toLowerCase() === 'passed') {
          fetchRecords('gender', 'Female', `Female — ${label}`, { result: 'Passed' }, evt);
        } else {
          fetchRecords('gender', 'Female', `Female — ${label}`, { notPassed: 1 }, evt);
        }
      };

      mCtx.canvas.onclick = function(evt){
        const points = maleDonut.getElementsAtEventForMode(evt,'nearest',{intersect:true},true);
        if (!points.length) return;
        const i = points[0].index;
        const label = maleDonut.data.labels[i];
        if (String(label).toLowerCase() === 'passed') {
          fetchRecords('gender', 'Male', `Male — ${label}`, { result: 'Passed' }, evt);
        } else {
          fetchRecords('gender', 'Male', `Male — ${label}`, { notPassed: 1 }, evt);
        }
      };
    }

    function renderCompositionPie(arr) {
      // Polished doughnut with rounded arcs and percent in legend
      const labels = arr.map(r=> r.result || r[0]);
      const counts = arr.map(r=> parseInt(r.count|| (r[1]||0) ));
      const totalComp = counts.reduce((a,b)=>a+(b||0),0);
      const ctx = document.getElementById('chartCompositionPie').getContext('2d');

      // explicit base colors
      const baseColors = labels.map(l => {
        const s = String(l || '').toLowerCase();
        if (s === 'passed') return PALETTE[3];
        if (s === 'failed') return PALETTE[5];
        if (s.includes('cond')) return PALETTE[4];
        return PALETTE[2 + (labels.indexOf(l) % (PALETTE.length-2))];
      });
      const bg = baseColors.map(col => createVerticalGradient(ctx, col));
      const borders = baseColors.map(col => darken(col, 0.12));

      // large doughnut options with legend showing percents
      const opts = (function(){
        const o = JSON.parse(JSON.stringify(PIE_LARGE_OPTIONS));
        o.cutout = '58%';
        o.plugins = o.plugins || {}; o.plugins.legend = o.plugins.legend || {}; o.plugins.legend.labels = o.plugins.legend.labels || {};
        o.plugins.legend.labels.generateLabels = function(chart){
          const d = chart.data; const ds = d.datasets[0] || { data: [] };
          const tot = (ds.data||[]).reduce((a,b)=>a + (Number(b)||0), 0);
          return d.labels.map((l,i)=>{
            const val = Number(ds.data[i]||0); const pct = tot ? Math.round((val/tot)*100) : 0;
            const meta = chart.getDatasetMeta(0).data[i];
            return {
              text: `${l} — ${pct}%`,
              fillStyle: (ds.backgroundColor && ds.backgroundColor[i]) || '#e5e7eb',
              strokeStyle: '#fff',
              lineWidth: 0,
              hidden: meta && meta.hidden || false,
              index: i
            };
          });
        };
        return o;
      })();

      if (!totalComp) {
        const safeLabels = labels.length ? labels : ['No data'];
        const safeCounts = safeLabels.map(()=>1);
        const muted = PALETTE[7];
        const mutedBg = safeLabels.map(()=>muted);
        compositionPie = updateOrCreateChart(compositionPie, ctx, {
          type:'doughnut',
          data:{ labels: safeLabels, datasets:[{ data:safeCounts, backgroundColor: mutedBg, borderColor: '#e5e7eb', borderWidth: 1, spacing: 2, borderRadius: 6 }] },
          options: opts
        });
        return;
      }

      compositionPie = updateOrCreateChart(compositionPie, ctx, {
        type:'doughnut',
        data:{ labels, datasets:[{ data:counts, backgroundColor: bg, borderColor: borders, borderWidth: 2, spacing: 2, hoverOffset: 8, borderRadius: 6 }] },
        options: opts
      });

      ctx.canvas.onclick = function(evt){
        const points = compositionPie.getElementsAtEventForMode(evt,'nearest',{intersect:true},true);
        if(points.length){ const idx = points[0].index; const label = compositionPie.data.labels[idx]; fetchRecords('result', label, `Result: ${label}`, {}, evt); }
      };
    }

    function renderPassFailCondDonuts(arr) {
      // arr contains counts per result
      const passed = arr.find(r=> (r.result||'').toLowerCase() === 'passed');
      const failed = arr.find(r=> (r.result||'').toLowerCase() === 'failed');
      const cond = arr.find(r=> (r.result||'').toLowerCase().includes('cond'));
      const p = parseInt(passed?.count||0), f = parseInt(failed?.count||0), c = parseInt(cond?.count||0);
      const total = p+f+c || 0;

  const passData = total ? [p, total-p] : [0,1];
  const failData = total ? [f, total-f] : [0,1];
  const condData = total ? [c, total-c] : [0,1];

  (function(){ const c=document.getElementById('chartPassDonut').getContext('2d'); passDonut = updateOrCreateChart(passDonut, c, { type:'doughnut', data:{ labels:['Passed','Not Passed'], datasets:[{ data:passData, backgroundColor:[createVerticalGradient(c, PALETTE[3]),'#f3f4f6'], borderWidth:2, borderColor:[darken(PALETTE[3],0.12),'#e5e7eb'], hoverOffset:8, spacing:2, borderRadius:6 }] }, options: donutOptions('66%') }); })();
  (function(){ const c=document.getElementById('chartFailDonut').getContext('2d'); failDonut = updateOrCreateChart(failDonut, c, { type:'doughnut', data:{ labels:['Failed','Not Failed'], datasets:[{ data:failData, backgroundColor:[createVerticalGradient(c, PALETTE[5]),'#f3f4f6'], borderWidth:2, borderColor:[darken(PALETTE[5],0.12),'#e5e7eb'], hoverOffset:8, spacing:2, borderRadius:6 }] }, options: donutOptions('66%') }); })();
  (function(){ const c=document.getElementById('chartCondDonut').getContext('2d'); condDonut = updateOrCreateChart(condDonut, c, { type:'doughnut', data:{ labels:['Conditional','Not Conditional'], datasets:[{ data:condData, backgroundColor:[createVerticalGradient(c, PALETTE[4]),'#f3f4f6'], borderWidth:2, borderColor:[darken(PALETTE[4],0.12),'#e5e7eb'], hoverOffset:8, spacing:2, borderRadius:6 }] }, options: donutOptions('66%') }); })();

  // overlay values for pass/fail/cond and top percent badges
  const passPercent = total ? Math.round((p / total) * 100) : 0;
  const failPercent = total ? Math.round((f / total) * 100) : 0;
  const condPercent = total ? Math.round((c / total) * 100) : 0;
  setDonutOverlay('passOverlay', passPercent, `${p} / ${total}`, PALETTE[3]);
  setDonutOverlay('failOverlay', failPercent, `${f} / ${total}`, PALETTE[5]);
  setDonutOverlay('condOverlay', condPercent, `${c} / ${total}`, PALETTE[4]);
  setTopPercent('passTopPercent', passPercent, PALETTE[3]);
  setTopPercent('failTopPercent', failPercent, PALETTE[5]);
  setTopPercent('condTopPercent', condPercent, PALETTE[4]);

  // Inject mini legends under each KPI donut (pass/fail/cond)
  (function(){
    const wrap = document.getElementById('chartPassDonut').parentElement; if (wrap){ let leg=wrap.nextElementSibling; if(!leg||!leg.classList||!leg.classList.contains('chart-legend')){ leg=document.createElement('div'); leg.className='chart-legend'; leg.style.justifyContent='center'; leg.style.marginTop='4px'; wrap.parentElement.insertBefore(leg, wrap.nextSibling);} leg.innerHTML = `<span style="display:inline-flex;align-items:center;gap:6px;"><span style="display:inline-block;width:12px;height:12px;background:${PALETTE[3]};border-radius:2px;border:1px solid ${darken(PALETTE[3],0.12)};"></span>Passed</span> <span style=\"display:inline-flex;align-items:center;gap:6px;\"><span style=\"display:inline-block;width:12px;height:12px;background:#f3f4f6;border-radius:2px;border:1px solid #e5e7eb;\"></span>Not Passed</span>`; }
    const wrapF = document.getElementById('chartFailDonut').parentElement; if (wrapF){ let leg=wrapF.nextElementSibling; if(!leg||!leg.classList||!leg.classList.contains('chart-legend')){ leg=document.createElement('div'); leg.className='chart-legend'; leg.style.justifyContent='center'; leg.style.marginTop='4px'; wrapF.parentElement.insertBefore(leg, wrapF.nextSibling);} leg.innerHTML = `<span style="display:inline-flex;align-items:center;gap:6px;"><span style="display:inline-block;width:12px;height:12px;background:${PALETTE[5]};border-radius:2px;border:1px solid ${darken(PALETTE[5],0.12)};"></span>Failed</span> <span style=\"display:inline-flex;align-items:center;gap:6px;\"><span style=\"display:inline-block;width:12px;height:12px;background:#f3f4f6;border-radius:2px;border:1px solid #e5e7eb;\"></span>Not Failed</span>`; }
    const wrapC = document.getElementById('chartCondDonut').parentElement; if (wrapC){ let leg=wrapC.nextElementSibling; if(!leg||!leg.classList||!leg.classList.contains('chart-legend')){ leg=document.createElement('div'); leg.className='chart-legend'; leg.style.justifyContent='center'; leg.style.marginTop='4px'; wrapC.parentElement.insertBefore(leg, wrapC.nextSibling);} leg.innerHTML = `<span style="display:inline-flex;align-items:center;gap:6px;\"><span style=\"display:inline-block;width:12px;height:12px;background:${PALETTE[4]};border-radius:2px;border:1px solid ${darken(PALETTE[4],0.12)};\"></span>Conditional</span> <span style=\"display:inline-flex;align-items:center;gap:6px;\"><span style=\"display:inline-block;width:12px;height:12px;background:#f3f4f6;border-radius:2px;border:1px solid #e5e7eb;\"></span>Not Conditional</span>`; }
  })();

      // add click handlers
      document.getElementById('chartPassDonut').onclick = function(evt){
        const pts = passDonut.getElementsAtEventForMode(evt,'nearest',{intersect:true},true);
        if(!pts.length) return;
        const idx = pts[0].index;
        const label = passDonut.data.labels[idx];
        if (String(label).toLowerCase() === 'passed') {
          fetchRecords('result', 'Passed', 'Passed', {}, evt);
        } else {
          // 'Other' slice -> not Passed
          fetchRecords('result', 'Other', 'Not Passed', { notResult: 'Passed' }, evt);
        }
      };

      document.getElementById('chartFailDonut').onclick = function(evt){
        const pts = failDonut.getElementsAtEventForMode(evt,'nearest',{intersect:true},true);
        if(!pts.length) return;
        const idx = pts[0].index;
        const label = failDonut.data.labels[idx];
        if (String(label).toLowerCase() === 'failed') {
          fetchRecords('result','Failed','Failed', {}, evt);
        } else {
          // 'Other' slice -> not Failed
          fetchRecords('result','Other','Not Failed', { notResult: 'Failed' }, evt);
        }
      };

      document.getElementById('chartCondDonut').onclick = function(evt){
        const pts = condDonut.getElementsAtEventForMode(evt,'nearest',{intersect:true},true);
        if(!pts.length) return;
        const idx = pts[0].index;
        const label = condDonut.data.labels[idx];
        if (String(label).toLowerCase().includes('cond')) {
          fetchRecords('result','Conditional','Conditional', {}, evt);
        } else {
          fetchRecords('result','Other','Not Conditional', { notResult: 'Conditional' }, evt);
        }
      };
    }

    function renderFirstRepeaterDonuts(data) {
      const first_total = parseInt(data.first_timer.total || 0);
      const first_passed = parseInt(data.first_timer.passed || 0);
      const rep_total = parseInt(data.repeater.total || 0);
      const rep_passed = parseInt(data.repeater.passed || 0);

      const firstData = first_total ? [first_passed, Math.max(0, first_total-first_passed)] : [0,1];
      const repData = rep_total ? [rep_passed, Math.max(0, rep_total-rep_passed)] : [0,1];

  (function(){ const c=document.getElementById('chartFirstTimerDonut').getContext('2d'); firstTimerDonut = updateOrCreateChart(firstTimerDonut, c, { type:'doughnut', data:{ labels:['Passed','Not Passed'], datasets:[{ data:firstData, backgroundColor:[createVerticalGradient(c, PALETTE[2]),'#f3f4f6'], borderWidth:2, borderColor:[darken(PALETTE[2],0.12),'#e5e7eb'], hoverOffset:8, spacing:2 }] }, options: donutOptions('62%') }); })();
  (function(){ const c=document.getElementById('chartRepeaterDonut').getContext('2d'); repeaterDonut = updateOrCreateChart(repeaterDonut, c, { type:'doughnut', data:{ labels:['Passed','Not Passed'], datasets:[{ data:repData, backgroundColor:[createVerticalGradient(c, PALETTE[6]),'#f3f4f6'], borderWidth:2, borderColor:[darken(PALETTE[6],0.12),'#e5e7eb'], hoverOffset:8, spacing:2 }] }, options: donutOptions('62%') }); })();

  // Mini legends for First Timer / Repeater donuts
  (function(){
    const wf = document.getElementById('chartFirstTimerDonut').parentElement; if (wf){ let leg=wf.nextElementSibling; if(!leg||!leg.classList||!leg.classList.contains('chart-legend')){ leg=document.createElement('div'); leg.className='chart-legend'; leg.style.justifyContent='center'; leg.style.marginTop='4px'; wf.parentElement.insertBefore(leg, wf.nextSibling);} leg.innerHTML = `<span style="display:inline-flex;align-items:center;gap:6px;"><span style="display:inline-block;width:12px;height:12px;background:${PALETTE[2]};border-radius:2px;border:1px solid ${darken(PALETTE[2],0.12)};"></span>Passed</span> <span style=\"display:inline-flex;align-items:center;gap:6px;\"><span style=\"display:inline-block;width:12px;height:12px;background:#f3f4f6;border-radius:2px;border:1px solid #e5e7eb;\"></span>Not Passed</span>`; }
    const wr = document.getElementById('chartRepeaterDonut').parentElement; if (wr){ let leg=wr.nextElementSibling; if(!leg||!leg.classList||!leg.classList.contains('chart-legend')){ leg=document.createElement('div'); leg.className='chart-legend'; leg.style.justifyContent='center'; leg.style.marginTop='4px'; wr.parentElement.insertBefore(leg, wr.nextSibling);} leg.innerHTML = `<span style="display:inline-flex;align-items:center;gap:6px;"><span style="display:inline-block;width:12px;height:12px;background:${PALETTE[6]};border-radius:2px;border:1px solid ${darken(PALETTE[6],0.12)};"></span>Passed</span> <span style=\"display:inline-flex;align-items:center;gap:6px;\"><span style=\"display:inline-block;width:12px;height:12px;background:#f3f4f6;border-radius:2px;border:1px solid #e5e7eb;\"></span>Not Passed</span>`; }
  })();

  const firstPercent = first_total ? Math.round((first_passed / first_total) * 100) : 0;
  const repPercent = rep_total ? Math.round((rep_passed / rep_total) * 100) : 0;
  setDonutOverlay('firstOverlay', firstPercent, `${first_passed} / ${first_total}`, PALETTE[2]);
  setDonutOverlay('repeaterOverlay', repPercent, `${rep_passed} / ${rep_total}`, PALETTE[6]);
  setTopPercent('firstTopPercent', firstPercent, PALETTE[2]);
  setTopPercent('repeaterTopPercent', repPercent, PALETTE[6]);

  // ensure print captions under these KPI donuts reflect current numbers (optional)
  const firstCaption = document.querySelector('#chartFirstTimerDonut')?.parentElement?.nextElementSibling;
  if (firstCaption && firstCaption.classList && firstCaption.classList.contains('print-caption')) {
    firstCaption.textContent = `First Timer Passed: ${first_passed} / ${first_total}`;
  }
  const repCaption = document.querySelector('#chartRepeaterDonut')?.parentElement?.nextElementSibling;
  if (repCaption && repCaption.classList && repCaption.classList.contains('print-caption')) {
    repCaption.textContent = `Repeater Passed: ${rep_passed} / ${rep_total}`;
  }

  // When clicking a slice, determine whether user clicked 'Passed' or the other slice
  // and include the appropriate filter so modal shows only the intended subset.
  document.getElementById('chartFirstTimerDonut').onclick = function(evt){
    const pts = firstTimerDonut.getElementsAtEventForMode(evt,'nearest',{intersect:true},true);
    if(!pts.length) return;
    const idx = pts[0].index;
    const label = (firstTimerDonut.data.labels && firstTimerDonut.data.labels[idx]) ? firstTimerDonut.data.labels[idx] : '';
    if (String(label).toLowerCase() === 'passed') {
      fetchRecords('exam_type','First Timer','First Timer — Passed', { result: 'Passed' }, evt);
    } else {
      // other slice -> not passed
      fetchRecords('exam_type','First Timer','First Timer — Not Passed', { notPassed: 1 }, evt);
    }
  };

  document.getElementById('chartRepeaterDonut').onclick = function(evt){
    const pts = repeaterDonut.getElementsAtEventForMode(evt,'nearest',{intersect:true},true);
    if(!pts.length) return;
    const idx = pts[0].index;
    const label = (repeaterDonut.data.labels && repeaterDonut.data.labels[idx]) ? repeaterDonut.data.labels[idx] : '';
    if (String(label).toLowerCase() === 'passed') {
      fetchRecords('exam_type','Repeater','Repeater — Passed', { result: 'Passed' }, evt);
    } else {
      fetchRecords('exam_type','Repeater','Repeater — Not Passed', { notPassed: 1 }, evt);
    }
  };
    }

    // Initialize empty charts to avoid DOM blanking when first filtering
    // color palette for charts
    // Palette chosen to harmonize with primary teal (#06b6d4) and magenta (#f472b6)
    // Pastel color palette
    const PALETTE = [
      '#2dd4bf', // teal (Male / primary accent)
      '#f472b6', // pink/magenta (Female)
      '#60a5fa', // blue (charts accent)
      '#4ade80', // green (Passed)
      '#fbbf24', // amber (Conditional)
      '#fb7185', // red (Failed)
      '#818cf8', // indigo (accent)
      '#94a3b8'  // slate (muted)
    ];

    // Utility: create a subtle vertical gradient based on a base color for nicer fills
    function createVerticalGradient(ctx, base){
      try{
        const g = ctx.createLinearGradient(0, 0, 0, ctx.canvas.height);
        // light to base to slightly darker
        g.addColorStop(0, lighten(base, 0.22));
        g.addColorStop(0.55, base);
        g.addColorStop(1, darken(base, 0.18));
        return g;
      }catch(_){ return base; }
    }

    renderGenderPie([{gender:'Female',total:0},{gender:'Male',total:0}]);
    renderGenderDonuts([{gender:'Female',total:0,passed:0},{gender:'Male',total:0,passed:0}]);
    renderCompositionPie([{result:'Passed',count:0},{result:'Failed',count:0},{result:'Conditional',count:0}]);
    renderPassFailCondDonuts([{result:'Passed',count:0},{result:'Failed',count:0},{result:'Conditional',count:0}]);
    renderFirstRepeaterDonuts({ first_timer:{total:0,passed:0}, repeater:{total:0,passed:0} });
  // initialize subjects chart with empty state
  renderSubjectsChart([]);
  // Subjects chart mode toggle wiring
  (function initSubjectsModeToggle(){
    const btnCounts = document.getElementById('modeCounts');
    const btnPercent = document.getElementById('modePercent');
    if (!btnCounts || !btnPercent) return;
    function refresh(){
      const isPercent = (window.__subjectsMode === 'percent');
      // simple active styles
      btnCounts.style.background = isPercent ? '#fff' : '#06b6d4';
      btnCounts.style.color = isPercent ? '#06b6d4' : '#fff';
      btnPercent.style.background = isPercent ? '#06b6d4' : '#fff';
      btnPercent.style.color = isPercent ? '#fff' : '#06b6d4';
    }
    btnCounts.addEventListener('click', () => { window.__subjectsMode = 'count'; refresh(); renderSubjectsChart(subjectsRawRows); });
    btnPercent.addEventListener('click', () => { window.__subjectsMode = 'percent'; refresh(); renderSubjectsChart(subjectsRawRows); });
    refresh();
  })();

    // helper to position and set overlay values
    function setDonutOverlay(id, percent, countText, color){
      const el = document.getElementById(id);
      if (!el) return;
      const pct = (percent === null || percent === undefined) ? '—' : (String(percent) + '%');
      el.querySelector('.percent').textContent = pct;
      el.querySelector('.count').textContent = countText || '— / —';
      el.querySelector('.percent').style.color = color || 'inherit';
      // center overlay within its wrapper
      const wrap = el.parentElement;
      if (wrap) {
        const r = wrap.getBoundingClientRect();
        el.style.left = '50%'; el.style.top = '50%'; el.style.transform = 'translate(-50%,-50%)';
      }
    }

    function setTopPercent(id, percent, color){
      const el = document.getElementById(id);
      if (!el) return;
      el.textContent = (percent === null || percent === undefined) ? '—' : (String(percent) + '%');
      el.style.color = color || 'inherit';
    }

    function renderSubjectsChart(rows) {
      const infoEl = document.getElementById('subjectsInfo');
      const ctx = document.getElementById('chartSubjectsBar').getContext('2d');
      if (Array.isArray(rows)) { subjectsRawRows = rows; }
      const mode = (window.__subjectsMode === 'percent') ? 'percent' : 'count';
    // build labels and datasets
  const labels = [];
  const passed = [];
  const failed = [];
  const unknown = [];
  let maxTotal = 0;
      const totals = [];
      subjectsBarIds = [];
  const hasData = Array.isArray(rows) && rows.length > 0;
      if (!hasData) {
        if (infoEl) {
          if (selectedTypeId && selectedDateId) infoEl.textContent = 'No subject data for the selected filters.';
          else if (selectedTypeId) infoEl.textContent = 'No subject data found for the selected exam type (all dates).';
          else infoEl.textContent = 'Select a board exam type to load subjects.';
        }
        // show an empty chart with a muted placeholder bar so the card doesn’t collapse
        labels.push('—'); passed.push(0); failed.push(0); unknown.push(0); subjectsBarIds.push(null);
        totals.push(0);
      } else {
        if (infoEl) {
          const baseMsg = (selectedTypeId && !selectedDateId && !selectedYear)
            ? 'Showing subjects for selected exam type (all dates). Click a bar segment to view matching records.'
            : 'Click a bar segment to view matching records.';
          infoEl.textContent = baseMsg + ' Unknown = no recorded subject result for that subject.';
        }
        rows.forEach(r => {
          labels.push(String(r.subject_name || '—'));
          const p = parseInt(r.passed || 0);
          const f = parseInt(r.failed || 0);
          const u = parseInt(r.unknown || 0);
          passed.push(p);
          failed.push(f);
          unknown.push(u);
          const tot = p + f + u; if (tot > maxTotal) maxTotal = tot; totals.push(tot);
          subjectsBarIds.push(parseInt(r.subject_id || 0));
        });
      }

      // Keep the chart a reasonable, fixed size (no responsive reflow)
      const subjCanvas = document.getElementById('chartSubjectsBar');
      if (subjCanvas) {
        const rowsCount = Math.max(1, labels.length);
        const h = Math.min(480, Math.max(240, rowsCount * 20));
        // fix the canvas width to current container width to avoid resize jitter
        const parent = subjCanvas.parentElement;
        const w = parent ? Math.max(320, parent.clientWidth) : 800;
        subjCanvas.style.width = w + 'px';
        subjCanvas.style.height = h + 'px';
        subjCanvas.setAttribute('width', String(w));
        subjCanvas.setAttribute('height', String(h));
      }

      // If percent mode, transform data to percentages per subject (row)
      let dataPassed = passed.slice();
      let dataFailed = failed.slice();
      let dataUnknown = unknown.slice();
      if (mode === 'percent') {
        dataPassed = passed.map((v, i) => totals[i] ? Math.round((v / totals[i]) * 100) : 0);
        dataFailed = failed.map((v, i) => totals[i] ? Math.round((v / totals[i]) * 100) : 0);
        dataUnknown = unknown.map((v, i) => totals[i] ? Math.round((v / totals[i]) * 100) : 0);
      }

      const cfg = {
        type: 'bar',
        data: {
          labels,
          datasets: [
            { label: 'Passed', data: dataPassed, backgroundColor: PALETTE[3], stack: 'stack1' },
            { label: 'Failed', data: dataFailed, backgroundColor: PALETTE[5], stack: 'stack1' },
            { label: 'Unknown', data: dataUnknown, backgroundColor: '#cbd5e1', stack: 'stack1' }
          ]
        },
        options: (function(){
          const o = JSON.parse(JSON.stringify(COMMON_CHART_OPTIONS));
          // Make this chart steady: fixed-size, no responsive resizes, no animation
          o.responsive = false;
          o.animation = { duration: 0 };
          o.indexAxis = 'y';
          o.scales = {
            x: {
              stacked: true,
              grid: { display: false },
              beginAtZero: true,
              suggestedMax: (mode === 'percent') ? 100 : Math.max(1, maxTotal),
              ticks: (mode === 'percent')
                ? { precision: 0, stepSize: 10, callback: (v) => Number.isFinite(v) ? (Math.floor(v) + '%') : v }
                : { precision: 0, stepSize: 1, callback: (v) => Number.isFinite(v) ? Math.floor(v) : v },
              title: { display: true, text: (mode === 'percent') ? 'Percentage of examinees' : 'Count of examinees' }
            },
            y: { stacked: true, grid: { display: false } }
          };
          // Show counts and percent in tooltip when in percent mode
          if (mode === 'percent') {
            o.plugins.tooltip = o.plugins.tooltip || {};
            o.plugins.tooltip.callbacks = o.plugins.tooltip.callbacks || {};
            o.plugins.tooltip.callbacks.label = function(context){
              const label = context.dataset.label || '';
              const idx = context.dataIndex;
              const pct = context.raw ?? 0;
              const l = label.toLowerCase();
              const cnt = (l === 'passed') ? (passed[idx] || 0) : (l === 'failed' ? (failed[idx] || 0) : (unknown[idx] || 0));
              const tot = totals[idx] || 0;
              return `${label}: ${pct}% (${cnt}/${tot})`;
            };
          }
          o.plugins.legend = { 
            position: 'bottom',
            onClick: function(evt, legendItem, legend){
              // Open students-subjects details filtered by the clicked legend label
              const label = (legendItem && legendItem.text) ? String(legendItem.text) : '';
              if (!selectedTypeId) return;
              openStudentsSubjectsDetails(label);
            }
          };
          o.maintainAspectRatio = false; // honor explicit canvas height we set
          return o;
        })()
      };
  // Add sane bar thickness so one dataset doesn't fill the entire area visually
  cfg.data.datasets.forEach(ds => { ds.maxBarThickness = 18; ds.barThickness = 14; ds.borderRadius = 6; });
  subjectsBar = updateOrCreateChart(subjectsBar, ctx, cfg);

      // click -> open records filtered by subject and result (dataset)
      ctx.canvas.onclick = function(evt){
        if (!subjectsBar) return;
        const points = subjectsBar.getElementsAtEventForMode(evt, 'nearest', { intersect: true }, true);
        if (!points.length) return;
        const p = points[0];
        const rowIdx = p.index; const dsIdx = p.datasetIndex;
        const subjId = subjectsBarIds[rowIdx] || 0;
        const dsLabel = (subjectsBar.data.datasets[dsIdx] && subjectsBar.data.datasets[dsIdx].label) ? String(subjectsBar.data.datasets[dsIdx].label) : '';
        const subjName = labels[rowIdx] || 'Subject';
        if (!subjId || !selectedTypeId) return;
        const ds = dsLabel.toLowerCase();
        const res = ds.includes('pass') ? 'Passed' : (ds.includes('fail') ? 'Failed' : (ds.includes('unknown') ? 'Unknown' : ''));
        if (res === 'Unknown') {
          // Optional: If you'd like to drill into who is missing subject records
          // uncomment the next line. Backend supports 'Unknown' now.
          fetchSubjectRecords(subjId, subjName, res);
        } else {
          fetchSubjectRecords(subjId, subjName, res);
        }
      };
    }

    // Fetch and show Students-Subjects detailed view (optionally filtered by legend label)
    function openStudentsSubjectsDetails(legendLabel){
      const base = 'stats_engineering.php';
      const q = new URLSearchParams({ action: 'students_subjects', boardExamTypeId: selectedTypeId });
      if (selectedDateId) q.append('examDateId', selectedDateId);
      if (selectedYear) q.append('examYear', selectedYear);
      if (legendLabel) q.append('legendFilter', legendLabel);
      showLoading(true);
      fetch(`${base}?${q.toString()}`)
        .then(r=>r.json())
        .then(resp=>{
          if (!resp || !resp.success) { alert('Failed to load students'); return; }
          renderStudentsSubjectsModal(legendLabel, resp.data);
        })
        .catch(err=>{ console.error(err); alert('Failed to load students'); })
        .finally(()=> showLoading(false));
    }

    // Render the modal content for per-student subjects
    function renderStudentsSubjectsModal(legendLabel, payload){
      const modal = document.getElementById('studentsSubjectsModal'); if (!modal) return;
      // Ensure modal is attached to <body> to avoid fixed-position containment issues
      if (modal.parentElement !== document.body) { document.body.appendChild(modal); }
      const title = document.getElementById('studentsSubjectsTitle');
      const body = document.getElementById('studentsSubjectsBody'); if (!body) return;
      const t = (legendLabel && legendLabel.length) ? `Student Subject Grades — ${legendLabel}` : 'Student Subject Grades';
      if (title) title.textContent = t;

      const subjects = payload.subjects || [];
      const students = payload.students || [];
      const mappedCount = payload.mapped_count || 0;
      // Build a compact card per student
      const subHeader = subjects.map(s => `<th style="padding:6px;border-bottom:1px solid #e6eef8;text-align:center">${escapeHtml(s.name)}</th>`).join('');
      let html = '';
      if (!students.length) {
        html = '<div class="muted">No students found for the current filters.</div>';
      } else {
        html = '<table style="width:100%; border-collapse:collapse; font-size:0.95rem;">';
        html += `<thead><tr style="background:#f8fafc; position:sticky; top:0;"><th style="padding:6px;border-bottom:1px solid #e6eef8;text-align:left">Name</th><th style="padding:6px;border-bottom:1px solid #e6eef8">Sex</th><th style="padding:6px;border-bottom:1px solid #e6eef8">Course</th><th style="padding:6px;border-bottom:1px solid #e6eef8">Year Graduated</th><th style="padding:6px;border-bottom:1px solid #e6eef8">Exam Date</th>${subHeader}<th style="padding:6px;border-bottom:1px solid #e6eef8">Summary</th></tr></thead>`;
        html += '<tbody>';
        students.forEach(st => {
          const subsMap = {};
          (st.subjects||[]).forEach(su => { subsMap[String(su.subject_id)] = su; });
          let cells = '';
          subjects.forEach(s => {
            const su = subsMap[String(s.id)] || null;
            if (!su) {
              cells += `<td style="padding:6px;border-bottom:1px solid #eef2f7;text-align:center;color:#64748b">—</td>`;
            } else {
              const tagColor = (String(su.result||'').toLowerCase()==='passed') ? '#16a34a' : (String(su.result||'').toLowerCase()==='failed' ? '#dc2626' : '#64748b');
              const pct = (su.percent===null || su.percent===undefined) ? '—' : `${su.percent}%`;
              const score = (su.grade===null || su.grade===undefined) ? '—' : `${su.grade}/${su.total_items}`;
              cells += `<td style="padding:6px;border-bottom:1px solid #eef2f7;text-align:center"><span style="display:inline-block;padding:2px 6px;border-radius:999px;background:${tagColor}20;color:${tagColor};font-weight:600;margin-right:6px">${escapeHtml(su.result||'')}</span><span class="muted">${pct} (${score})</span></td>`;
            }
          });
          const summ = st.summary || { passed:0, failed:0, unknown: Math.max(0, mappedCount - (st.subjects||[]).length), avg_percent: null };
          const avgTxt = (summ.avg_percent===null || summ.avg_percent===undefined) ? '—' : `${summ.avg_percent}%`;
          html += `<tr><td style="padding:6px;border-bottom:1px solid #eef2f7">${escapeHtml(st.full_name||'')}</td><td style="padding:6px;border-bottom:1px solid #eef2f7;text-align:center">${escapeHtml(st.sex||'')}</td><td style="padding:6px;border-bottom:1px solid #eef2f7">${escapeHtml(st.course||'')}</td><td style="padding:6px;border-bottom:1px solid #eef2f7;text-align:center">${escapeHtml(st.year_graduated||'')}</td><td style="padding:6px;border-bottom:1px solid #eef2f7;text-align:center">${escapeHtml(st.exam_date||'')}</td>${cells}<td style="padding:6px;border-bottom:1px solid #eef2f7;text-align:center"><div style="font-size:0.85rem" class="muted">P:${summ.passed} F:${summ.failed} U:${summ.unknown}<br/>Avg: ${avgTxt}</div></td></tr>`;
        });
        html += '</tbody></table>';
      }
      body.innerHTML = html;
  modal.style.display = 'flex';
  document.body.dataset.prevOverflow = document.body.style.overflow || '';
  document.body.style.overflow = 'hidden';
    }

  (function(){ const btn = document.getElementById('closeStudentsSubjects'); if (btn){ btn.addEventListener('click', ()=>{ const m=document.getElementById('studentsSubjectsModal'); if (m){ m.style.display='none'; document.body.style.overflow = (document.body.dataset.prevOverflow || ''); } }); } })();
    // Allow closing the Students-Subjects modal by clicking the backdrop or pressing ESC
    (function(){
      const m = document.getElementById('studentsSubjectsModal');
      if (m) {
  m.addEventListener('click', (e)=>{ if (e.target === m) { m.style.display='none'; document.body.style.overflow = (document.body.dataset.prevOverflow || ''); } });
  document.addEventListener('keydown', (e)=>{ if (e.key === 'Escape' && m.style.display==='flex') { m.style.display='none'; document.body.style.overflow = (document.body.dataset.prevOverflow || ''); } });
      }
    })();

    function fetchSubjectRecords(subjectId, subjectName, subjectResult) {
      if (!selectedTypeId) { alert('Please select a board exam type first.'); return; }
      const q = new URLSearchParams({ boardExamTypeId: selectedTypeId, subjectId: subjectId, limit: 500 });
      if (selectedDateId) q.append('examDateId', selectedDateId);
      // when filtered by year, include examYear so records match the chart scope
      if (selectedYear) q.append('examYear', selectedYear);
      if (subjectResult) q.append('subjectResult', subjectResult);
      fetch('get_subject_records.php?' + q.toString())
        .then(async r => {
          if (!r.ok) {
            const text = await r.text();
            console.error('Subject records HTTP error', r.status, text);
            throw new Error('HTTP ' + r.status);
          }
          const text = await r.text();
          try { return JSON.parse(text); } catch(e){ console.error('Subject records non-JSON response:', text); throw e; }
        })
        .then(resp => {
          if (!resp || !resp.success) { alert('Failed to load subject records'); return; }
          const title = subjectResult ? `${subjectName} — ${subjectResult}` : `${subjectName}`;
          showRecordsModal(title, resp.data, resp.count, resp.total_count);
        })
        .catch(err => { console.error(err); alert('Failed to load subject records'); });
    }

    // Open records for a specific exam type (by id) and subject across all dates (used in overall view)
    function openRecordsForTypeSubject(typeId, subjectId, subjectName, subjectResult, evt) {
      const q = new URLSearchParams({ boardExamTypeId: typeId, subjectId: subjectId, limit: 500 });
      if (subjectResult) q.append('subjectResult', subjectResult);
      fetch('get_subject_records.php?' + q.toString())
        .then(async r => {
          if (!r.ok) { const text = await r.text(); console.error('Subject records HTTP error', r.status, text); throw new Error('HTTP ' + r.status); }
          const text = await r.text();
          try { return JSON.parse(text); } catch(e){ console.error('Subject records non-JSON response:', text); throw e; }
        })
        .then(resp => {
          if (!resp || !resp.success) { alert('Failed to load subject records'); return; }
          const title = subjectResult ? `${subjectName} — ${subjectResult}` : `${subjectName}`;
          // Always show centered modal
          showRecordsModal(title, resp.data, resp.count, resp.total_count);
        })
        .catch(err => { console.error(err); alert('Failed to load subject records'); });
    }

    // RECORDS modal handlers
  const recordsModal = document.getElementById('recordsModal');
  const closeRecordsModal = document.getElementById('closeRecordsModal');
  closeRecordsModal.addEventListener('click', ()=> { recordsModal.style.display = 'none'; document.body.style.overflow = (document.body.dataset.prevOverflow || ''); });
  // Close the centered modal when clicking outside its panel
  recordsModal.addEventListener('click', (e)=>{ if (e.target === recordsModal) { recordsModal.style.display = 'none'; document.body.style.overflow = (document.body.dataset.prevOverflow || ''); }});
  // ESC to close the centered modal
  document.addEventListener('keydown', (e)=>{ if (e.key === 'Escape' && recordsModal.style.display === 'flex') { recordsModal.style.display = 'none'; document.body.style.overflow = (document.body.dataset.prevOverflow || ''); } });

  function fetchRecords(filterType, filterValue, title, extraFilters = {}, evt){
      // boardExamType and examDate are optional (empty means overall)
      const selType = document.getElementById('examTypeSelect').value;
      const selDate = document.getElementById('examDateSelect').value;
      // get boardExamType name from id if provided
      const typeObj = boardExamTypes.find(x=> String(x.id) === String(selType));
      const boardExamType = typeObj ? typeObj.exam_type_name : '';
      const examDate = selDate ? (boardExamDates.find(d=>String(d.id)===String(selDate))?.exam_date || '') : '';

      const q = new URLSearchParams({ boardExamType: boardExamType, examDate: examDate, filterType, filterValue, limit:500 });
      // append any extraFilters (e.g. result or notPassed)
      for (const k in extraFilters) {
        if (Object.prototype.hasOwnProperty.call(extraFilters, k) && extraFilters[k] !== undefined && extraFilters[k] !== null) {
          q.append(k, extraFilters[k]);
        }
      }
      // If the current selection is by year (not exact date), include from/to so backend can filter records accordingly
      if (selectedYear && !q.has('fromDate') && !q.has('toDate')) {
        q.append('fromDate', `${selectedYear}-01-01`);
        q.append('toDate', `${selectedYear}-12-31`);
      }
      // also include ids for authoritative filtering
      if (selectedTypeId) q.append('boardExamTypeId', selectedTypeId);
      if (selectedDateId) q.append('examDateId', selectedDateId);
      fetch('get_stats_records.php?'+q.toString()).then(r=>r.json()).then(resp=>{
        if (!resp.success){ alert('Failed to load records'); return; }
        // resp.count = returned rows length, resp.total_count = total matching rows (may be > count when limited)
        // Always show centered modal
        showRecordsModal(title || 'Records', resp.data, resp.count, resp.total_count);
      }).catch(err=>{ console.error(err); alert('Failed to load records'); });
    }

  function showRecordsModal(title, rows, count, totalCount){
      // Ensure modal is attached to <body> so fixed positioning centers in the viewport
      if (recordsModal && recordsModal.parentElement !== document.body) {
        document.body.appendChild(recordsModal);
      }
      document.getElementById('recordsModalTitle').textContent = title;
      // show 'showing X of Y' when totalCount is provided and greater than returned rows
      if (typeof totalCount === 'number' && totalCount > rows.length) {
        document.getElementById('recordsCount').textContent = `Showing ${rows.length} of ${totalCount} record(s)`;
      } else {
        document.getElementById('recordsCount').textContent = `${rows.length} record(s)`;
      }
      const tbody = document.querySelector('#recordsTable tbody'); tbody.innerHTML = '';
      rows.forEach(r=>{
        const tr = document.createElement('tr');
        const examDate = (r.exam_date || r.board_exam_date || '') ? (r.exam_date || r.board_exam_date) : '';
        tr.innerHTML = `<td style="padding:8px;border-bottom:1px solid #eef2f7">${escapeHtml(r.full_name)}</td><td style="padding:8px;border-bottom:1px solid #eef2f7;text-align:center">${escapeHtml(r.sex)}</td><td style="padding:8px;border-bottom:1px solid #eef2f7">${escapeHtml(r.course)}</td><td style="padding:8px;border-bottom:1px solid #eef2f7;text-align:center">${escapeHtml(r.year_graduated)}</td><td style="padding:8px;border-bottom:1px solid #eef2f7;text-align:center">${escapeHtml(examDate)}</td><td style="padding:8px;border-bottom:1px solid #eef2f7;text-align:center">${escapeHtml(r.result)}</td>`;
        tbody.appendChild(tr);
      });
      recordsModal.style.display = 'flex';
      // Lock background scroll while modal is open
      document.body.dataset.prevOverflow = document.body.style.overflow || '';
      document.body.style.overflow = 'hidden';
    }

    // Popover-style quick viewer that appears where the user clicked and closes on outside click
    function showRecordsPopover(title, rows, count, totalCount, evt){
      const existing = document.getElementById('recordsPopover');
      if (existing) existing.remove();
      const pop = document.createElement('div');
      pop.id = 'recordsPopover';
      pop.style.position = 'fixed';
      pop.style.zIndex = '22000';
      pop.style.background = 'linear-gradient(145deg, #ffffff 0%, #f0fdff 100%)';
      pop.style.border = '2px solid rgba(6, 182, 212, 0.2)';
      pop.style.borderRadius = '20px';
      pop.style.boxShadow = '0 20px 60px rgba(6, 182, 212, 0.25)';
      pop.style.maxWidth = '900px';
      pop.style.maxHeight = '75vh';
      pop.style.overflow = 'hidden';
      pop.style.display = 'flex';
      pop.style.flexDirection = 'column';

      // header
      const header = document.createElement('div');
      header.style.display = 'flex';
      header.style.alignItems = 'center';
      header.style.justifyContent = 'space-between';
      header.style.gap = '16px';
      header.style.padding = '24px 32px';
      header.style.background = 'linear-gradient(135deg, #06b6d4 0%, #0891b2 100%)';
      header.style.color = 'white';
      header.style.borderBottom = 'none';
      header.style.borderRadius = '20px 20px 0 0';
      const h = document.createElement('div'); 
      h.textContent = title; 
      h.style.fontWeight = '800'; 
      h.style.fontSize = '1.5rem';
      h.style.textShadow = '0 2px 4px rgba(0,0,0,0.1)';
      const cls = document.createElement('button'); 
      cls.innerHTML = '&times;'; 
      cls.setAttribute('aria-label','Close'); 
      cls.style.background='rgba(255, 255, 255, 0.2)'; 
      cls.style.border='2px solid rgba(255, 255, 255, 0.3)'; 
      cls.style.width='36px';
      cls.style.height='36px';
      cls.style.borderRadius='50%';
      cls.style.fontSize='24px'; 
      cls.style.cursor='pointer'; 
      cls.style.color='white';
      cls.style.display='flex';
      cls.style.alignItems='center';
      cls.style.justifyContent='center';
      cls.style.transition='all 0.3s ease';
      cls.style.backdropFilter='blur(10px)';
      cls.onmouseover = () => { cls.style.background='rgba(255, 255, 255, 0.3)'; };
      cls.onmouseout = () => { cls.style.background='rgba(255, 255, 255, 0.2)'; };
      cls.onclick = () => { pop.remove(); detach(); };
      header.appendChild(h); header.appendChild(cls);
      pop.appendChild(header);

      const info = document.createElement('div'); 
      info.className='muted'; 
      info.style.padding='16px 32px'; 
      info.style.fontSize='0.95rem';
      info.style.fontWeight='600';
      info.style.color='#0e7490';
      info.style.background='linear-gradient(135deg, #ecfeff 0%, #cffafe 100%)';
      info.style.borderBottom='2px solid rgba(6, 182, 212, 0.1)';
      info.innerHTML = `<i class="fas fa-list" style="margin-right: 8px;"></i>${(typeof totalCount==='number' && totalCount>rows.length) ? `Showing ${rows.length} of ${totalCount} record(s)` : `${rows.length} record(s)`}`; 
      pop.appendChild(info);

      const body = document.createElement('div'); 
      body.style.overflow='auto'; 
      body.style.maxHeight='calc(75vh - 140px)';
      body.style.padding='24px 32px';
      const table = document.createElement('table'); 
      table.style.width='100%'; 
      table.style.borderCollapse='separate'; 
      table.style.borderSpacing='0';
      table.style.fontSize='0.95rem';
      table.innerHTML = `<thead><tr style="background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%); position:sticky; top:0; color: white; box-shadow: 0 4px 15px rgba(6, 182, 212, 0.2);"><th style="padding: 14px 12px; font-weight: 700; text-align:left; font-size: 0.9rem; border-bottom: 2px solid rgba(255, 255, 255, 0.2);">Name</th><th style="padding: 14px 12px; font-weight: 700; text-align:center; font-size: 0.9rem; border-bottom: 2px solid rgba(255, 255, 255, 0.2);">Sex</th><th style="padding: 14px 12px; font-weight: 700; text-align:left; font-size: 0.9rem; border-bottom: 2px solid rgba(255, 255, 255, 0.2);">Course</th><th style="padding: 14px 12px; font-weight: 700; text-align:center; font-size: 0.9rem; border-bottom: 2px solid rgba(255, 255, 255, 0.2);">Year Graduated</th><th style="padding: 14px 12px; font-weight: 700; text-align:center; font-size: 0.9rem; border-bottom: 2px solid rgba(255, 255, 255, 0.2);">Exam Date</th><th style="padding: 14px 12px; font-weight: 700; text-align:center; font-size: 0.9rem; border-bottom: 2px solid rgba(255, 255, 255, 0.2);">Result</th></tr></thead><tbody></tbody>`;
      const tb = table.querySelector('tbody');
      rows.forEach((r, idx) => {
        const examDate = (r.exam_date || r.board_exam_date || '') ? (r.exam_date || r.board_exam_date) : '';
        const tr = document.createElement('tr');
        tr.style.background = idx % 2 === 0 ? 'white' : 'rgba(6, 182, 212, 0.03)';
        tr.style.transition = 'all 0.3s ease';
        tr.onmouseover = () => { tr.style.background = 'rgba(6, 182, 212, 0.08)'; tr.style.transform = 'translateX(4px)'; };
        tr.onmouseout = () => { tr.style.background = idx % 2 === 0 ? 'white' : 'rgba(6, 182, 212, 0.03)'; tr.style.transform = 'translateX(0)'; };
        const resultColor = r.result === 'Passed' ? '#10b981' : '#ef4444';
        const resultBg = r.result === 'Passed' ? 'linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%)' : 'linear-gradient(135deg, #fee2e2 0%, #fecaca 100%)';
        tr.innerHTML = `<td style="padding: 12px; border-bottom: 1px solid rgba(6, 182, 212, 0.1); font-weight: 600; color: #0f1724;">${escapeHtml(r.full_name)}</td><td style="padding: 12px; border-bottom: 1px solid rgba(6, 182, 212, 0.1); text-align:center; color: #64748b;">${escapeHtml(r.sex)}</td><td style="padding: 12px; border-bottom: 1px solid rgba(6, 182, 212, 0.1); color: #475569; font-size: 0.9rem;">${escapeHtml(r.course)}</td><td style="padding: 12px; border-bottom: 1px solid rgba(6, 182, 212, 0.1); text-align:center; font-weight: 600; color: #0891b2;">${escapeHtml(r.year_graduated)}</td><td style="padding: 12px; border-bottom: 1px solid rgba(6, 182, 212, 0.1); text-align:center; color: #475569;">${escapeHtml(examDate)}</td><td style="padding: 12px; border-bottom: 1px solid rgba(6, 182, 212, 0.1); text-align:center;"><span style="padding: 6px 14px; border-radius: 999px; background: ${resultBg}; color: ${resultColor}; font-weight: 700; font-size: 0.85rem; display: inline-block; box-shadow: 0 2px 6px rgba(0,0,0,0.08);">${escapeHtml(r.result)}</span></td>`;
        tb.appendChild(tr);
      });
      body.appendChild(table); pop.appendChild(body);

      document.body.appendChild(pop);
      // position near click, keep inside viewport
      const pad = 12; const vw = window.innerWidth; const vh = window.innerHeight;
      const rectW = Math.min(pop.offsetWidth || 640, 900); const rectH = Math.min(pop.offsetHeight || 400, Math.floor(vh*0.75));
      let x = (evt && typeof evt.clientX==='number') ? evt.clientX + pad : Math.floor((vw-rectW)/2);
      let y = (evt && typeof evt.clientY==='number') ? evt.clientY + pad : Math.floor((vh-rectH)/2);
      // constrain
      if (x + rectW + pad > vw) x = vw - rectW - pad;
      if (y + rectH + pad > vh) y = vh - rectH - pad;
      if (x < pad) x = pad; if (y < pad) y = pad;
      pop.style.left = x + 'px';
      pop.style.top = y + 'px';

      // outside click / esc to close
      function onDocClick(e){ if (!pop.contains(e.target)) { pop.remove(); detach(); } }
      function onKey(e){ if (e.key === 'Escape') { pop.remove(); detach(); } }
      function detach(){ document.removeEventListener('mousedown', onDocClick, true); document.removeEventListener('keydown', onKey, true); }
      setTimeout(()=>{ document.addEventListener('mousedown', onDocClick, true); document.addEventListener('keydown', onKey, true); }, 0);
    }

    function escapeHtml(s){ if (s === null || s === undefined) return ''; return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

    // Logout confirmation function
    function confirmLogout(event) {
      event.preventDefault();
      
      // Create modal overlay
      const overlay = document.createElement('div');
      overlay.style.position = 'fixed';
      overlay.style.top = '0';
      overlay.style.left = '0';
      overlay.style.width = '100%';
      overlay.style.height = '100%';
      overlay.style.background = 'rgba(0, 0, 0, 0.5)';
      overlay.style.backdropFilter = 'blur(4px)';
      overlay.style.zIndex = '99999';
      overlay.style.display = 'flex';
      overlay.style.alignItems = 'center';
      overlay.style.justifyContent = 'center';
      overlay.style.animation = 'fadeIn 0.3s ease';
      
      // Create modal dialog
      const modal = document.createElement('div');
      modal.style.background = 'linear-gradient(145deg, #ffffff 0%, #f0fdff 100%)';
      modal.style.borderRadius = '20px';
      modal.style.border = '2px solid rgba(6, 182, 212, 0.2)';
      modal.style.boxShadow = '0 20px 60px rgba(6, 182, 212, 0.3)';
      modal.style.maxWidth = '450px';
      modal.style.width = '90%';
      modal.style.overflow = 'hidden';
      modal.style.animation = 'modalSlideIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1)';
      
      // Modal header
      const header = document.createElement('div');
      header.style.background = 'linear-gradient(135deg, #06b6d4 0%, #0891b2 100%)';
      header.style.padding = '24px 32px';
      header.style.color = 'white';
      header.innerHTML = '<div style="display: flex; align-items: center; gap: 12px;"><i class="fas fa-sign-out-alt" style="font-size: 1.5rem;"></i><h3 style="margin: 0; font-size: 1.3rem; font-weight: 800;">Confirm Logout</h3></div>';
      
      // Modal body
      const body = document.createElement('div');
      body.style.padding = '32px';
      body.innerHTML = '<p style="margin: 0; font-size: 1.05rem; color: #334155; line-height: 1.6;"><i class="fas fa-question-circle" style="color: #06b6d4; margin-right: 8px;"></i>Are you sure you want to log out?</p>';
      
      // Modal footer
      const footer = document.createElement('div');
      footer.style.padding = '20px 32px';
      footer.style.background = '#f8fafc';
      footer.style.display = 'flex';
      footer.style.gap = '12px';
      footer.style.justifyContent = 'flex-end';
      
      // Cancel button
      const cancelBtn = document.createElement('button');
      cancelBtn.textContent = 'Cancel';
      cancelBtn.style.padding = '10px 24px';
      cancelBtn.style.borderRadius = '12px';
      cancelBtn.style.border = '2px solid #cbd5e1';
      cancelBtn.style.background = 'white';
      cancelBtn.style.color = '#475569';
      cancelBtn.style.fontWeight = '600';
      cancelBtn.style.cursor = 'pointer';
      cancelBtn.style.transition = 'all 0.3s ease';
      cancelBtn.onmouseover = () => { cancelBtn.style.background = '#f1f5f9'; cancelBtn.style.borderColor = '#94a3b8'; };
      cancelBtn.onmouseout = () => { cancelBtn.style.background = 'white'; cancelBtn.style.borderColor = '#cbd5e1'; };
      cancelBtn.onclick = () => overlay.remove();
      
      // Logout button
      const logoutBtn = document.createElement('button');
      logoutBtn.innerHTML = '<i class="fas fa-sign-out-alt" style="margin-right: 6px;"></i>Logout';
      logoutBtn.style.padding = '10px 24px';
      logoutBtn.style.borderRadius = '12px';
      logoutBtn.style.border = 'none';
      logoutBtn.style.background = 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)';
      logoutBtn.style.color = 'white';
      logoutBtn.style.fontWeight = '600';
      logoutBtn.style.cursor = 'pointer';
      logoutBtn.style.transition = 'all 0.3s ease';
      logoutBtn.style.boxShadow = '0 4px 15px rgba(239, 68, 68, 0.3)';
      logoutBtn.onmouseover = () => { logoutBtn.style.transform = 'translateY(-2px)'; logoutBtn.style.boxShadow = '0 6px 20px rgba(239, 68, 68, 0.4)'; };
      logoutBtn.onmouseout = () => { logoutBtn.style.transform = 'translateY(0)'; logoutBtn.style.boxShadow = '0 4px 15px rgba(239, 68, 68, 0.3)'; };
      logoutBtn.onclick = () => window.location.href = 'logout.php';
      
      footer.appendChild(cancelBtn);
      footer.appendChild(logoutBtn);
      
      modal.appendChild(header);
      modal.appendChild(body);
      modal.appendChild(footer);
      overlay.appendChild(modal);
      document.body.appendChild(overlay);
      
      // Close on backdrop click
      overlay.addEventListener('click', (e) => {
        if (e.target === overlay) overlay.remove();
      });
      
      // Close on Escape key
      const escapeHandler = (e) => {
        if (e.key === 'Escape') {
          overlay.remove();
          document.removeEventListener('keydown', escapeHandler);
        }
      };
      document.addEventListener('keydown', escapeHandler);
    }

    // Load overall statistics (no filters) on first load
    function loadOverallStats(){
      const base = 'stats_engineering.php';
      showLoading(true);
  const pKpis = fetch(`${base}?action=kpis`).then(r=>r.json());
      const pGender = fetch(`${base}?action=gender`).then(r=>r.json());
      const pComp = fetch(`${base}?action=composition`).then(r=>r.json());
      const pAllSubjects = fetch(`${base}?action=subjects_all`).then(r=>r.json());
  const pDept = fetch(`${base}?action=dept_passing_rate&yearStart=2019&yearEnd=2024`).then(r=>r.json());
      const pTrend = fetch(`${base}?action=trend_passing_rate&yearStart=2019&yearEnd=2024`).then(r=>r.json());
      const pStacked = fetch(`${base}?action=stacked_passing_composition&yearStart=2019&yearEnd=2024`).then(r=>r.json());
      const pTotals = fetch(`${base}?action=stacked_totals_by_year&yearStart=2019&yearEnd=2024`).then(r=>r.json());
    Promise.all([pKpis, pGender, pComp, pAllSubjects, pDept, pTrend, pStacked, pTotals]).then(([kpis, gender, comp, allSubjects, dept, trend, stacked, totals])=>{
        if (kpis && kpis.success) {
          document.getElementById('kpiTotal').textContent = kpis.data.total + ' total records';
          document.getElementById('kpiPassed').textContent = kpis.data.passed + ' passed';
          renderFirstRepeaterDonuts(kpis.data);
        }
        if (gender && gender.success) {
        console.log('DEBUG: gender response (overall)', gender.data);
        renderGenderPie(gender.data);
        renderGenderDonuts(gender.data);
        }
        if (comp && comp.success) {
          renderCompositionPie(comp.data);
          renderPassFailCondDonuts(comp.data);
        }
        // render overall trend charts
  const deptCard = document.getElementById('deptPassingCard'); if (deptCard) deptCard.style.display = '';
  const trendCard = document.getElementById('overallTrendsCard'); if (trendCard) trendCard.style.display = '';
    const forecastCard = document.getElementById('forecastTrendsCard'); if (forecastCard) forecastCard.style.display = '';
        const stackedCard = document.getElementById('overallStackedCard'); if (stackedCard) stackedCard.style.display = '';
    const totalsCard = document.getElementById('overallTotalsCard'); if (totalsCard) totalsCard.style.display = '';
  if (dept && dept.success) { renderDeptPassingRate(dept.data || dept); }
        if (trend && trend.success) { renderPassingTrends(trend.data || trend); }
    // render forecasts in separate card using the same years/types from trend payload
    if (trend && trend.success) { renderPassingForecast((trend.data || trend)); }
        if (stacked && stacked.success) { renderPassingStacked(stacked.data || stacked); }
    if (totals && totals.success) { renderTotalsStacked(totals.data || totals); }
        // overall view: visualize subjects grouped by exam type using non-graph bars
        renderSubjectsChart([]);
        if (allSubjects && allSubjects.success) {
          renderOverallSubjectsBars(allSubjects.data || []);
        } else {
          renderOverallSubjectsBars([]);
        }
        // show overall in highlighted labels
        selectedTypeEl.textContent = 'Overall';
        selectedDateEl.textContent = 'Overall';
      }).catch(err=>{ console.error(err); }).finally(()=> showLoading(false));
    }

    function renderOverallSubjectsBars(groups){
      const container = document.getElementById('overallSubjectsList');
      if (!container) return;
      // Ensure header reflects overall mode
      updateSubjectsHeader();
      const chartCanvas = document.getElementById('chartSubjectsBar');
      // hide main chart and show the overall visualization container
      if (chartCanvas) chartCanvas.style.display = 'none';
  // hide subjects chart mode toggle; show overall subjects mode toggle in overall view
  const modeCtl = document.getElementById('subjectsModeControls'); if (modeCtl) modeCtl.style.display = 'none';
  const overallCtl = document.getElementById('overallSubjectsModeControls'); if (overallCtl) overallCtl.style.display = 'flex';
      const info = document.getElementById('subjectsInfo');
      if (info) info.textContent = 'Overall view — subjects per board exam type (Passed vs Failed across all dates)';

  // set container layout: one board exam type per line
  container.style.display = 'grid';
  container.style.gridTemplateColumns = '1fr';
      container.style.gap = '12px';
      container.innerHTML = '';

      if (!Array.isArray(groups) || groups.length === 0) {
        container.innerHTML = '<div class="muted">No subjects found.</div>';
        return;
      }

      // Helper to fetch subject totals (passed/failed) for a given exam type across all dates
      function fetchSubjectsTotalsForType(typeId){
        return fetch(`stats_engineering.php?action=subjects&boardExamTypeId=${encodeURIComponent(typeId)}`)
          .then(r=>r.json())
          .then(resp => (resp && resp.success) ? (resp.data || []) : [] )
          .catch(() => []);
      }

      // For each exam type group, fetch its subject totals and render a list of bars
      const tasks = groups.map(g => {
        const card = document.createElement('div');
        card.className = 'chart-card';
        card.innerHTML = `<h4>${escapeHtml(g.exam_type_name || 'Exam Type')}</h4>`;
        const body = document.createElement('div');
        body.style.padding = '4px 2px 8px 2px';
        card.appendChild(body);
        // append placeholder while loading
        body.innerHTML = '<div class="muted" style="padding:6px 0;">Loading subjects…</div>';
        container.appendChild(card);

        return fetchSubjectsTotalsForType(g.exam_type_id).then(rows => {
          // Clear placeholder
          body.innerHTML = '';
          if (!rows || !rows.length) {
            body.innerHTML = '<div class="muted">— No subject data —</div>';
          } else {
            // compute max total for counts mode
            let maxTotal = 0;
            rows.forEach(r => { const p = parseInt(r.passed||0,10); const f = parseInt(r.failed||0,10); const t = Math.max(0,p+f); if (t>maxTotal) maxTotal=t; });
            card.dataset.maxTotal = String(maxTotal);
            rows.forEach(r => {
              const name = String(r.subject_name || 'Subject');
              const p = parseInt(r.passed || 0, 10);
              const f = parseInt(r.failed || 0, 10);
              const sid = parseInt(r.subject_id || 0, 10);
              const t = Math.max(0, p + f);

              const row = document.createElement('div');
              row.style.display = 'grid';
              row.style.gridTemplateColumns = '1fr 220px 70px';
              row.style.alignItems = 'center';
              row.style.gap = '10px';
              row.style.margin = '6px 0';
              row.style.cursor = 'pointer';
              row.title = 'Show records';
              const label = document.createElement('div');
              label.textContent = name;
              label.style.whiteSpace = 'nowrap';
              label.style.overflow = 'hidden';
              label.style.textOverflow = 'ellipsis';

              const track = document.createElement('div');
              track.style.position = 'relative';
              track.style.height = '12px';
              track.className = 'overall-track';
              track.style.borderRadius = '8px';
              track.style.overflow = 'hidden';
              track.title = `${p} Passed / ${f} Failed`;

              const segPass = document.createElement('div'); segPass.className = 'seg-pass';
              const segFail = document.createElement('div'); segFail.className = 'seg-fail';
              // initial widths based on current mode (default percent)
              const mode = (window.__overallSubjectsMode === 'counts') ? 'counts' : 'percent';
              let pw = 0, fw = 0;
              if (mode === 'percent') { pw = t ? Math.round((p / t) * 100) : 0; fw = t ? Math.round((f / t) * 100) : 0; }
              else { const maxT = Math.max(1, maxTotal); pw = Math.round((p / maxT) * 100); fw = Math.round((f / maxT) * 100); }
              segPass.style.height = '100%';
              segPass.style.width = pw + '%';
              // cheerful green to match updated palette
              segPass.style.background = '#4ade80';
              segPass.style.display = 'inline-block';
              segPass.style.cursor = 'pointer';
              segPass.title = `${p} Passed — click to view records`;
              segFail.style.height = '100%';
              segFail.style.width = fw + '%';
              // cheerful red to match updated palette
              segFail.style.background = '#fb7185';
              segFail.style.display = 'inline-block';
              segFail.style.cursor = 'pointer';
              segFail.title = `${f} Failed — click to view records`;
              track.appendChild(segPass);
              track.appendChild(segFail);

              const counts = document.createElement('div');
              // add a specific class so we can update this text on mode toggle
              counts.className = 'muted overall-counts';
              counts.style.fontSize = '0.85rem';
              counts.style.textAlign = 'right';
              // show either counts or percent depending on current overall mode
              const pctNow = t ? Math.round((p / t) * 100) : 0;
              if (window.__overallSubjectsMode === 'counts') {
                counts.textContent = t ? `${p}/${t}` : '0/0';
              } else {
                counts.textContent = `${pctNow}%`;
              }

              row.appendChild(label);
              row.appendChild(track);
              row.appendChild(counts);
              body.appendChild(row);

              // annotate for mode switching without refetch
              row.dataset.p = String(p);
              row.dataset.f = String(f);
              row.dataset.t = String(t);
              row.dataset.pct = String(pctNow);

              // Click opens records (type + subject, across all dates)
              row.addEventListener('click', (ev) => {
                if (!sid) return;
                openRecordsForTypeSubject(g.exam_type_id, sid, name, undefined, ev);
              });

              // Segment-specific clicks for Passed/Failed
              segPass.addEventListener('click', (ev) => {
                ev.stopPropagation();
                if (!sid) return;
                openRecordsForTypeSubject(g.exam_type_id, sid, name, 'Passed', ev);
              });
              segFail.addEventListener('click', (ev) => {
                ev.stopPropagation();
                if (!sid) return;
                openRecordsForTypeSubject(g.exam_type_id, sid, name, 'Failed', ev);
              });
            });
          }
          // legend
          const legend = document.createElement('div');
          legend.className = 'chart-legend';
          legend.style.marginTop = '10px';
          // center the Passed/Failed legend under each card
          legend.style.display = 'flex';
          legend.style.justifyContent = 'center';
          legend.style.gap = '12px';
          legend.innerHTML = `<span style="display:inline-flex;align-items:center;gap:6px;"><span style="display:inline-block;width:16px;height:8px;background:#4ade80;border-radius:2px;"></span>Passed</span><span style="display:inline-flex;align-items:center;gap:6px;">
          <span style="display:inline-block;width:16px;height:8px;background:#fb7185;border-radius:2px;"></span>Failed</span>`;
          body.appendChild(legend);
        });
      });

      // wait for all to complete (non-blocking UI since each card updates as its data arrives)
      Promise.allSettled(tasks).then(()=>{/* no-op */});
    }

    // Toggle behavior for overall subjects list (Counts vs Percent)
    (function initOverallSubjectsMode(){
      const btnC = document.getElementById('overallModeCounts');
      const btnP = document.getElementById('overallModePercent');
      if (!btnC || !btnP) return;
      function applyButtons(){
        const isCounts = (window.__overallSubjectsMode === 'counts');
        btnC.style.background = isCounts ? '#06b6d4' : '#fff';
        btnC.style.color = isCounts ? '#fff' : '#06b6d4';
        btnP.style.background = isCounts ? '#fff' : '#06b6d4';
        btnP.style.color = isCounts ? '#06b6d4' : '#fff';
      }
      function applyMode(){
        const container = document.getElementById('overallSubjectsList');
        if (!container) return;
        const cards = container.querySelectorAll('.chart-card');
        cards.forEach(card => {
          const maxT = Math.max(1, parseInt(card.dataset.maxTotal || '1', 10));
          const rows = card.querySelectorAll('div[style*="grid-template-columns"]');
          rows.forEach(row => {
            const p = parseInt(row.dataset.p || '0', 10);
            const f = parseInt(row.dataset.f || '0', 10);
            const t = Math.max(0, p + f);
            const segPass = row.querySelector('.seg-pass');
            const segFail = row.querySelector('.seg-fail');
            const countsEl = row.querySelector('.overall-counts');
            if (!segPass || !segFail) return;
            if (window.__overallSubjectsMode === 'counts') {
              const pw = Math.round((p / maxT) * 100);
              const fw = Math.round((f / maxT) * 100);
              segPass.style.width = pw + '%';
              segFail.style.width = fw + '%';
              if (countsEl) countsEl.textContent = t ? `${p}/${t}` : '0/0';
            } else {
              const pw = t ? Math.round((p / t) * 100) : 0;
              const fw = t ? Math.round((f / t) * 100) : 0;
              segPass.style.width = pw + '%';
              segFail.style.width = fw + '%';
              if (countsEl) countsEl.textContent = `${pw}%`;
            }
          });
        });
      }
      btnC.addEventListener('click', ()=>{ window.__overallSubjectsMode = 'counts'; applyButtons(); applyMode(); });
      btnP.addEventListener('click', ()=>{ window.__overallSubjectsMode = 'percent'; applyButtons(); applyMode(); });
      applyButtons();
    })();

    // Render Passing Rate Trends (line)
    function renderDeptPassingRate(payload){
      lastDeptPayload = payload;
      const el = document.getElementById('chartDeptPassingRate'); if (!el) return;
      const ctx = el.getContext('2d');
      const years = (payload && payload.years) ? payload.years : [];
      const series = (payload && payload.series) ? payload.series : [];
      const s = series[0] || { label: 'Passing Rate', values_by_year: {}, points_details: {} };
      const data = years.map(y => (s.values_by_year && s.values_by_year[String(y)]) ?? 0);
      let cfg = {
        type: 'line',
        data: { labels: years, datasets: [{
          label: s.label,
          data,
          borderColor: PALETTE[2],
          backgroundColor: PALETTE[2] + '55',
          tension: 0.35,
          pointRadius: 4,
          pointHoverRadius: 5,
          fill: true
        }] },
        options: (function(){
          const o = JSON.parse(JSON.stringify(COMMON_CHART_OPTIONS));
          o.scales = {
            x: { grid: { display: false } },
            y: { beginAtZero: true, suggestedMax: 100, ticks: { callback: v => `${v}%` } }
          };
          o.plugins.legend = { display: !isMobile(), position: 'bottom', labels: { usePointStyle: true, boxWidth: 12, padding: 12, filter: function(item){ return !/\(Forecast\)$/.test(String(item.text||'')); } } };
          o.plugins.tooltip = o.plugins.tooltip || {};
          o.plugins.tooltip.callbacks = o.plugins.tooltip.callbacks || {};
          o.plugins.tooltip.callbacks.label = function(context){
            const year = years[context.dataIndex];
            const point = (s.points_details && s.points_details[String(year)]) || { rate: context.raw, passed: 0, total: 0 };
            return `${s.label}: ${Math.round(point.rate)}% (${point.passed}/${point.total})`;
          };
          return o;
        })()
      };
      deptPassingChart = updateOrCreateChart(deptPassingChart, ctx, cfg);
      // Mobile legend toggle
      maybeCreateLegendToggle('deptPassingCard', deptPassingChart);

      // Click handler: open all department records for that year
      ctx.canvas.onclick = function(evt){
        const points = deptPassingChart.getElementsAtEventForMode(evt, 'nearest', { intersect: true }, true);
        if (!points.length) return;
        const p = points[0]; const year = years[p.index];
        const from = `${year}-01-01`; const to = `${year}-12-31`;
        fetchRecords('year', String(year), `Department — ${year}`, { fromDate: from, toDate: to });
      };

      // Forecast overlay removed (separated into its own chart below)
    }

    // Render Passing Rate Trends (bar chart for better comparison)
    function renderPassingTrends(payload){
      lastTrendPayload = payload;
      const el = document.getElementById('chartPassingTrendLine'); if (!el) return;
      const ctx = el.getContext('2d');
      const years = (payload && payload.years) ? payload.years : [];
      const series = (payload && payload.series) ? payload.series : [];
      const datasets = series.map((s, i) => ({
        label: s.label,
        data: years.map(y => (s.values_by_year && s.values_by_year[String(y)]) ?? 0),
        backgroundColor: PALETTE[i % PALETTE.length],
        borderColor: PALETTE[i % PALETTE.length],
        borderWidth: 2,
        borderRadius: 6,
        borderSkipped: false
      }));
      const cfg = {
        type: 'bar',
        data: { labels: years, datasets },
        options: (function(){
          const o = JSON.parse(JSON.stringify(COMMON_CHART_OPTIONS));
          o.scales = {
            x: { 
              grid: { display: false },
              stacked: false
            },
            y: { 
              beginAtZero: true, 
              suggestedMax: 100, 
              ticks: { callback: v => `${v}%` },
              grid: { color: 'rgba(0, 0, 0, 0.05)' }
            }
          };
          o.plugins.legend = { display: !isMobile(), position: 'bottom' };
          o.plugins.tooltip = o.plugins.tooltip || {};
          o.plugins.tooltip.callbacks = o.plugins.tooltip.callbacks || {};
          o.plugins.tooltip.callbacks.label = function(context){
            const s = series[context.datasetIndex];
            const year = years[context.dataIndex];
            const point = (s.points_details && s.points_details[String(year)]) || { rate: context.raw, passed: 0, total: 0 };
            return `${s.label}: ${Math.round(point.rate)}% (${point.passed}/${point.total})`;
          };
          return o;
        })()
      };
      passingTrendChart = updateOrCreateChart(passingTrendChart, ctx, cfg);
  // Mobile legend toggle
  maybeCreateLegendToggle('overallTrendsCard', passingTrendChart);

      // Click handler: open records for that exam type and year (all results)
      ctx.canvas.onclick = function(evt){
        const points = passingTrendChart.getElementsAtEventForMode(evt, 'nearest', { intersect: true }, true);
        if (!points.length) return;
        const p = points[0];
        const year = passingTrendChart.config.data.labels[p.index];
        const maxActualYear = years.length ? years[years.length-1] : null;
        const dsLabel = (passingTrendChart.config.data.datasets[p.datasetIndex] && passingTrendChart.config.data.datasets[p.datasetIndex].label) || '';
        // Ignore forecast clicks or future years
        if ((typeof year === 'number' && maxActualYear !== null && year > maxActualYear) || /\(Forecast\)$/.test(String(dsLabel))) return;
        const s = series.find(x => x.label === dsLabel) || series[p.datasetIndex] || null;
        if (!s) return;
        const from = `${year}-01-01`; const to = `${year}-12-31`;
        fetchRecords('year', String(year), `${s.label} — ${year}`, { fromDate: from, toDate: to, boardExamTypeId: s.exam_type_id });
      };

      // Forecast overlay removed (separated into its own chart below)
    }

    // Render separate Forecast chart (per exam type) using linear regression from backend
    function renderPassingForecast(trendPayload){
      try{
        const card = document.getElementById('forecastTrendsCard'); if (card) card.style.display = '';
        const el = document.getElementById('chartPassingForecastLine'); if (!el) return;
        const ctx = el.getContext('2d');
        const yearsActual = (trendPayload && trendPayload.years) ? trendPayload.years : [];
        const series = (trendPayload && trendPayload.series) ? trendPayload.series : [];
        if (!yearsActual.length || !series.length) {
          // clear chart and summary when no data
          passingForecastChart = updateOrCreateChart(passingForecastChart, ctx, { type: 'line', data: { labels: [], datasets: [] }, options: (function(){ const o = JSON.parse(JSON.stringify(COMMON_CHART_OPTIONS)); o.plugins.legend = { position: 'bottom' }; return o; })() });
          const cont = document.getElementById('forecastSummary'); if (cont) cont.textContent = 'No forecast available.';
          return;
        }
        const firstYear = yearsActual[0];
        const lastYear = yearsActual[yearsActual.length - 1];
        const horizon = (typeof FORECAST_HORIZON === 'number' && FORECAST_HORIZON > 0) ? FORECAST_HORIZON : 2;
        const years = Array.from({ length: horizon }, (_, i) => lastYear + 1 + i);
        const base = 'stats_engineering.php';
        const fetches = series.map((s) => {
          const typeId = s.exam_type_id;
          if (!typeId) return Promise.resolve({ s, res: null });
          const url = `${base}?action=passing_rate_forecast&yearStart=${encodeURIComponent(firstYear)}&yearEnd=${encodeURIComponent(lastYear)}&horizon=${encodeURIComponent(horizon)}&boardExamTypeId=${encodeURIComponent(typeId)}`;
          return fetch(url).then(r => r.json()).then(res => ({ s, res })).catch(() => ({ s, res: null }));
        });
        Promise.all(fetches).then(results => {
          const datasets = [];
          const r2List = [];
          results.forEach((item, idx) => {
            if (!item || !item.res || !item.res.success || !item.res.data) return;
            const fArr = Array.isArray(item.res.data.forecast) ? item.res.data.forecast : [];
            const mapF = {}; fArr.forEach(f => { mapF[String(f.year)] = (f && typeof f.rate !== 'undefined') ? Number(f.rate) : null; });
            const vals = years.map(y => (mapF.hasOwnProperty(String(y)) ? mapF[String(y)] : null));
            const color = PALETTE[idx % PALETTE.length];
            datasets.push({
              label: item.s.label,
              data: vals,
              borderColor: color,
              backgroundColor: color,
              borderDash: [6, 4],
              tension: 0.35,
              pointRadius: 3,
              pointHoverRadius: 4,
              fill: false
            });
            const r2 = (item.res.data.model && item.res.data.model.r2 != null) ? Number(item.res.data.model.r2) : null;
            if (!isNaN(r2) && r2 !== null) r2List.push(r2);
          });
          const cfg = {
            type: 'line',
            data: { labels: years, datasets },
            options: (function(){
              const o = JSON.parse(JSON.stringify(COMMON_CHART_OPTIONS));
              o.scales = {
                x: { grid: { display: false } },
                y: { beginAtZero: true, suggestedMax: 100, ticks: { callback: v => `${v}%` } }
              };
              o.plugins.legend = { display: !isMobile(), position: 'bottom' };
              o.plugins.tooltip = o.plugins.tooltip || {};
              o.plugins.tooltip.callbacks = o.plugins.tooltip.callbacks || {};
              o.plugins.tooltip.callbacks.label = function(context){
                const dsLabel = (context && context.dataset && context.dataset.label) ? context.dataset.label : '';
                const year = years[context.dataIndex];
                const val = context.raw;
                const valText = (val === null || typeof val === 'undefined') ? '—' : (Math.round(val) + '%');
                return `${dsLabel}: ${valText} (forecast for ${year})`;
              };
              return o;
            })()
          };
          passingForecastChart = updateOrCreateChart(passingForecastChart, ctx, cfg);
          // Mobile legend toggle
          maybeCreateLegendToggle('forecastTrendsCard', passingForecastChart);
          const cont = document.getElementById('forecastSummary');
          if (cont) {
            if (!datasets.length) { cont.textContent = 'No forecast available.'; }
            else {
              const r2Avg = r2List.length ? Math.round((r2List.reduce((a,b)=>a+b,0) / r2List.length) * 100) / 100 : null;
              // Short, mobile-friendly summary
              const parts = [
                `H: ${horizon}`,
                `${years[0]}–${years[years.length-1]}`,
                `${datasets.length} types`
              ];
              if (r2Avg !== null) parts.push(`Avg R²: ${r2Avg}`);
              cont.textContent = parts.join(' • ');
            }
          }
        });
      } catch (e) {
        console.warn('renderPassingForecast failed', e);
      }
    }

    // Render Stacked Area Composition (percent)
    function renderPassingStacked(payload){
      lastStackedPayload = payload;
      const el = document.getElementById('chartPassingStackedArea'); if (!el) return;
      const ctx = el.getContext('2d');
      const years = (payload && payload.years) ? payload.years : [];
      const series = (payload && payload.series) ? payload.series : [];
      // Build datasets based on mode
      const mode = (window.__compositionMode === 'counts') ? 'counts' : 'percent';
      const datasets = series.map((s, i) => {
        const vals = years.map(y => {
          if (mode === 'counts') return (s.passed_by_year && s.passed_by_year[String(y)]) ?? 0;
          return (s.percent_by_year && s.percent_by_year[String(y)]) ?? 0;
        });
        return {
          label: s.label,
          data: vals,
          borderColor: PALETTE[i % PALETTE.length],
          backgroundColor: PALETTE[i % PALETTE.length] + '55',
          tension: 0.35,
          pointRadius: 0,
          fill: true,
          stack: 'stack1'
        };
      });
      const cfg = {
        type: 'line',
        data: { labels: years, datasets },
        options: (function(){
          const o = JSON.parse(JSON.stringify(COMMON_CHART_OPTIONS));
          // compute suggested max for counts mode by summing per year
          let suggestedMax = 100;
          if (mode === 'counts') {
            let maxSum = 0;
            years.forEach(y => {
              let sum = 0;
              series.forEach(s => { sum += (s.passed_by_year && s.passed_by_year[String(y)]) ? Number(s.passed_by_year[String(y)]) : 0; });
              if (sum > maxSum) maxSum = sum;
            });
            suggestedMax = Math.max(1, maxSum);
          }
          o.scales = {
            x: { grid: { display: false } },
            y: {
              beginAtZero: true,
              suggestedMax: suggestedMax,
              stacked: true,
              ticks: (mode === 'counts') ? { callback: v => `${v}` } : { callback: v => `${v}%` }
            }
          };
          o.plugins.legend = { display: !isMobile(), position: 'bottom' };
          o.plugins.tooltip = o.plugins.tooltip || {};
          o.plugins.tooltip.callbacks = o.plugins.tooltip.callbacks || {};
          o.plugins.tooltip.callbacks.label = function(context){
            const s = series[context.datasetIndex];
            const year = years[context.dataIndex];
            const val = context.raw ?? 0;
            const cnt = (s.passed_by_year && s.passed_by_year[String(year)]) ?? 0;
            if (mode === 'counts') {
              // also compute share for the year
              let total = 0; series.forEach(ss => { total += (ss.passed_by_year && ss.passed_by_year[String(year)]) ? Number(ss.passed_by_year[String(year)]) : 0; });
              const share = total ? Math.round((cnt/total)*100) : 0;
              return `${s.label}: ${cnt} passed (${share}%)`;
            }
            return `${s.label}: ${Math.round(val)}% (${cnt} passed)`;
          };
          return o;
        })()
      };
      passingStackedChart = updateOrCreateChart(passingStackedChart, ctx, cfg);
  // Mobile legend toggle
  maybeCreateLegendToggle('overallStackedCard', passingStackedChart);

      // Click handler: open passed records for that exam type and year
      ctx.canvas.onclick = function(evt){
        const points = passingStackedChart.getElementsAtEventForMode(evt, 'nearest', { intersect: true }, true);
        if (!points.length) return;
        const p = points[0]; const year = years[p.index]; const s = series[p.datasetIndex];
        const from = `${year}-01-01`; const to = `${year}-12-31`;
        fetchRecords('year', String(year), `${s.label} — Passed in ${year}`, { fromDate: from, toDate: to, boardExamTypeId: s.exam_type_id, result: 'Passed' });
      };

      // Render a compact summary for latest year
      (function(){
        const cont = document.getElementById('compositionSummary'); if (!cont) return;
        if (!years.length || !series.length) { cont.textContent = 'No data.'; return; }
        const latest = String(years[years.length - 1]);
        // compute total passed latest year for share
        let total = 0; series.forEach(s => { total += (s.passed_by_year && s.passed_by_year[latest]) ? Number(s.passed_by_year[latest]) : 0; });
        const parts = series.map((s, i) => {
          const cnt = (s.passed_by_year && s.passed_by_year[latest]) ? Number(s.passed_by_year[latest]) : 0;
          const pct = (s.percent_by_year && s.percent_by_year[latest]) ? Math.round(Number(s.percent_by_year[latest])) : (total ? Math.round((cnt/total)*100) : 0);
          const color = PALETTE[i % PALETTE.length];
          const valText = (window.__compositionMode === 'counts') ? `${cnt} passed` : `${pct}% (${cnt})`;
          return `<span style="display:inline-flex;align-items:center;gap:6px;margin-right:12px;white-space:nowrap;"><span style="display:inline-block;width:10px;height:10px;background:${color};border-radius:3px;"></span>${escapeHtml(s.label)}: <strong>${valText}</strong> <span class="muted">in ${latest}</span></span>`;
        });
        cont.innerHTML = parts.join('');
      })();
    }

    // Render Stacked Bar of TOTAL examinees by year and exam type
    function renderTotalsStacked(payload){
      lastTotalsPayload = payload;
      const el = document.getElementById('chartTotalsStackedBar'); if (!el) return;
      const ctx = el.getContext('2d');
      const years = (payload && payload.years) ? payload.years : [];
      const series = (payload && payload.series) ? payload.series : [];
      // Build datasets (counts)
      const datasets = series.map((s, i) => ({
        label: s.label,
        data: years.map(y => (s.totals_by_year && s.totals_by_year[String(y)]) ? Number(s.totals_by_year[String(y)]) : 0),
        backgroundColor: PALETTE[i % PALETTE.length],
        borderColor: PALETTE[i % PALETTE.length],
        stack: 'stack1'
      }));
      // compute suggested max by summing per year
      let maxSum = 0;
      years.forEach(y => {
        let sum = 0;
        series.forEach(s => { sum += (s.totals_by_year && s.totals_by_year[String(y)]) ? Number(s.totals_by_year[String(y)]) : 0; });
        if (sum > maxSum) maxSum = sum;
      });
      const cfg = {
        type: 'bar',
        data: { labels: years, datasets },
        options: (function(){
          const o = JSON.parse(JSON.stringify(COMMON_CHART_OPTIONS));
          o.scales = {
            x: { grid: { display: false } },
            y: { beginAtZero: true, stacked: true, suggestedMax: Math.max(1, maxSum) }
          };
          o.plugins.legend = { display: !isMobile(), position: 'bottom' };
          o.plugins.tooltip = o.plugins.tooltip || {}; o.plugins.tooltip.callbacks = o.plugins.tooltip.callbacks || {};
          o.plugins.tooltip.callbacks.label = function(context){
            const s = series[context.datasetIndex];
            const year = years[context.dataIndex];
            const cnt = (s.totals_by_year && s.totals_by_year[String(year)]) ? Number(s.totals_by_year[String(year)]) : 0;
            // compute share for the year
            let total = 0; series.forEach(ss => { total += (ss.totals_by_year && ss.totals_by_year[String(year)]) ? Number(ss.totals_by_year[String(year)]) : 0; });
            const share = total ? Math.round((cnt/total)*100) : 0;
            return `${s.label}: ${cnt} (${share}%)`;
          };
          return o;
        })()
      };
      totalsStackedChart = updateOrCreateChart(totalsStackedChart, ctx, cfg);
  // Mobile legend toggle
  maybeCreateLegendToggle('overallTotalsCard', totalsStackedChart);

      // Click handler: open all records for that exam type and year
      ctx.canvas.onclick = function(evt){
        const points = totalsStackedChart.getElementsAtEventForMode(evt, 'nearest', { intersect: true }, true);
        if (!points.length) return;
        const p = points[0]; const year = years[p.index]; const s = series[p.datasetIndex];
        const from = `${year}-01-01`; const to = `${year}-12-31`;
        fetchRecords('year', String(year), `${s.label} — ${year}`, { fromDate: from, toDate: to, boardExamTypeId: s.exam_type_id });
      };

      // Summary for latest year
      (function(){
        const cont = document.getElementById('totalsSummary'); if (!cont) return;
        if (!years.length || !series.length) { cont.textContent = 'No data.'; return; }
        const latest = String(years[years.length - 1]);
        let total = 0; series.forEach(s => { total += (s.totals_by_year && s.totals_by_year[latest]) ? Number(s.totals_by_year[latest]) : 0; });
        const parts = series.map((s, i) => {
          const cnt = (s.totals_by_year && s.totals_by_year[latest]) ? Number(s.totals_by_year[latest]) : 0;
          const share = total ? Math.round((cnt/total)*100) : 0;
          const color = PALETTE[i % PALETTE.length];
          return `<span style="display:inline-flex;align-items:center;gap:6px;margin-right:12px;white-space:nowrap;"><span style="display:inline-block;width:10px;height:10px;background:${color};border-radius:3px;"></span>${escapeHtml(s.label)}: <strong>${cnt}</strong> <span class=\"muted\">(${share}% of ${latest})</span></span>`;
        });
        cont.innerHTML = parts.join('') + (total ? ` <span class="muted" style="margin-left:8px;white-space:nowrap;">Total in ${latest}: <strong>${total}</strong></span>` : '');
      })();
    }

    // Initialize composition mode toggle
    (function initCompositionToggle(){
      const btnP = document.getElementById('compModePercent');
      const btnC = document.getElementById('compModeCounts');
      if (!btnP || !btnC) return;
      function applyButtons(){
        const isPercent = (window.__compositionMode !== 'counts');
        btnP.style.background = isPercent ? '#06b6d4' : '#fff';
        btnP.style.color = isPercent ? '#fff' : '#06b6d4';
        btnC.style.background = isPercent ? '#fff' : '#06b6d4';
        btnC.style.color = isPercent ? '#06b6d4' : '#fff';
      }
      btnP.addEventListener('click', ()=>{ window.__compositionMode = 'percent'; applyButtons(); if (lastStackedPayload) renderPassingStacked(lastStackedPayload); });
      btnC.addEventListener('click', ()=>{ window.__compositionMode = 'counts'; applyButtons(); if (lastStackedPayload) renderPassingStacked(lastStackedPayload); });
      applyButtons();
    })();

    // run initial overall load
    loadOverallStats();

    // Toggle Department Details Description
    (function(){
      const btn = document.getElementById('openDeptDetails');
      const desc = document.getElementById('deptDescription');
      if (!btn || !desc) return;
      
      btn.addEventListener('click', function(){
        if (desc.style.display === 'none' || desc.style.display === '') {
          desc.style.display = 'block';
          btn.innerHTML = '<i class="fas fa-eye-slash"></i> Hide Details';
        } else {
          desc.style.display = 'none';
          btn.innerHTML = '<i class="fas fa-info-circle"></i> View Details';
        }
      });
    })();

    // Toggle Trend Chart Description
    (function(){
      const btn = document.getElementById('openTrendInfo');
      const desc = document.getElementById('trendDescription');
      if (!btn || !desc) return;
      
      btn.addEventListener('click', function(){
        if (desc.style.display === 'none' || desc.style.display === '') {
          desc.style.display = 'block';
          btn.innerHTML = '<i class="fas fa-eye-slash"></i> Hide Details';
        } else {
          desc.style.display = 'none';
          btn.innerHTML = '<i class="fas fa-info-circle"></i> View Details';
        }
      });
    })();

    // Re-render charts when crossing the mobile breakpoint
    window.addEventListener('resize', debounce(function(){
      const now = isMobile();
      if (now === __lastMobileState) return;
      __lastMobileState = now;
      try {
        if (lastDeptPayload) renderDeptPassingRate(lastDeptPayload);
        if (lastTrendPayload) renderPassingTrends(lastTrendPayload);
        if (lastTrendPayload) renderPassingForecast(lastTrendPayload);
        if (lastStackedPayload) renderPassingStacked(lastStackedPayload);
        if (lastTotalsPayload) renderTotalsStacked(lastTotalsPayload);
      } catch(e){ console.warn('resize re-render failed', e); }
    }, 150));
  </script>
</body>
</html>
