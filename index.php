<?php
// Start or resume the session on EVERY page that includes this header.
session_start();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>BOARD PASSING RATE SYSTEM</title>
    <link rel="stylesheet" href="style.css" />
    <link href="https://fonts.googleapis.com/css?family=Poppins:400,500,600,700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Merriweather:wght@700;800&display=swap" rel="stylesheet">
    <style>
    /* =====================================================
       LANDING PAGE RESET - Override style.css body centering
       ===================================================== */
    body {
        display: block !important;
        justify-content: initial !important;
        align-items: initial !important;
        min-height: auto !important;
        background: #0f172a !important;
        margin: 0;
        padding: 0;
    }
    
    main {
        display: block;
        width: 100%;
    }
    
    /* =====================================================
       LOGIN MODAL CUSTOM STYLING
       ===================================================== */
    .wrapper {
        background: rgba(255, 255, 255, 0.98) !important;
        border: none !important;
        border-radius: 24px !important;
        box-shadow: 0 25px 60px rgba(0, 0, 0, 0.3) !important;
    }
    
    .wrapper .icon-close {
        background: linear-gradient(135deg, var(--eng-primary), var(--cte-primary)) !important;
        border-radius: 0 24px 0 16px !important;
    }
    
    .wrapper .form-box h2 {
        background: linear-gradient(135deg, #0f172a, #334155) !important;
        -webkit-background-clip: text !important;
        -webkit-text-fill-color: transparent !important;
        background-clip: text !important;
        font-weight: 800 !important;
    }
    
    .wrapper .input-box {
        border-bottom: 2px solid #e2e8f0 !important;
    }
    
    .wrapper .input-box:focus-within {
        border-bottom: 2px solid var(--cte-primary) !important;
    }
    
    .wrapper .input-box label {
        color: #64748b !important;
    }
    
    .wrapper .input-box input:focus ~ label,
    .wrapper .input-box input:not(:placeholder-shown) ~ label {
        color: var(--cte-primary) !important;
    }
    
    .wrapper .input-box .icon {
        color: #94a3b8 !important;
    }
    
    .wrapper .btn {
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%) !important;
        border-radius: 14px !important;
        height: 50px !important;
        font-weight: 700 !important;
        font-size: 1rem !important;
        letter-spacing: 0.5px !important;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.4) !important;
        border: 1px solid rgba(255,255,255,0.1) !important;
        position: relative !important;
        overflow: hidden !important;
        transition: all 0.3s ease !important;
    }
    
    .wrapper .btn::before {
        content: "" !important;
        position: absolute !important;
        inset: 0 !important;
        background: linear-gradient(135deg, var(--eng-primary), var(--cte-primary)) !important;
        opacity: 0 !important;
        transition: opacity 0.3s ease !important;
    }
    
    .wrapper .btn:hover {
        transform: translateY(-3px) !important;
        box-shadow: 0 12px 32px rgba(15, 23, 42, 0.5) !important;
    }
    
    .wrapper .btn:hover::before {
        opacity: 1 !important;
    }
    
    .wrapper .btn span,
    .wrapper .btn {
        position: relative !important;
        z-index: 1 !important;
    }
    
    .overlay.active {
        background: rgba(15, 23, 42, 0.6) !important;
        backdrop-filter: blur(8px) !important;
    }
    
    /* =====================================================
       DEPARTMENT THEME COLORS
       - Engineering: Green
       - CAS (Arts & Science): Pink/Rose
       - CBAA (Business Admin): Golden/Yellow
       - CCJE (Criminal Justice): Maroon/Crimson  
       - CTE (Teacher Education): Blue
       ===================================================== */
    :root {
        /* Engineering - Green */
        --eng-primary: #16a34a;
        --eng-secondary: #22c55e;
        --eng-light: #dcfce7;
        --eng-gradient: linear-gradient(135deg, #16a34a 0%, #22c55e 100%);
        
        /* CAS - Pink/Rose */
        --cas-primary: #BF3853;
        --cas-secondary: #E56D85;
        --cas-light: #FDB3C2;
        --cas-gradient: linear-gradient(135deg, #A41F39 0%, #E56D85 50%, #FDB3C2 100%);
        
        /* CBAA - Golden/Yellow */
        --cbaa-primary: #d97706;
        --cbaa-secondary: #f59e0b;
        --cbaa-light: #fef3c7;
        --cbaa-gradient: linear-gradient(135deg, #b45309 0%, #f59e0b 100%);
        
        /* CCJE - Maroon/Crimson */
        --ccje-primary: #7f1d1d;
        --ccje-secondary: #b91c1c;
        --ccje-light: #fecaca;
        --ccje-gradient: linear-gradient(135deg, #7f1d1d 0%, #dc2626 100%);
        
        /* CTE - Blue */
        --cte-primary: #1d4ed8;
        --cte-secondary: #3b82f6;
        --cte-light: #dbeafe;
        --cte-gradient: linear-gradient(135deg, #1d4ed8 0%, #60a5fa 100%);
        
        /* Universal Accent */
        --accent-teal: #14b8a6;
    }

    /* Top Navigation Bar */
    header {
        position: fixed !important;
        top: 0;
        left: 0;
        right: 0;
        width: 100%;
        z-index: 1000;
        font-family: 'Poppins', system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
        backdrop-filter: saturate(1.1) blur(10px);
        background: linear-gradient(90deg, #0f172a 0%, #1e293b 50%, #334155 100%) !important;
        box-shadow: 0 6px 18px rgba(2, 6, 23, 0.28);
        padding: 12px 20px !important;
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
    }

    @media (min-width: 900px) {
        header {
            padding: 14px 32px !important;
        }
    }

    .logo {
        display: flex;
        align-items: center;
        gap: 10px;
        color: #fff;
        margin: 0;
    }

    .logo .brand-abbrev {
        display: inline-block;
        font-weight: 800;
        letter-spacing: .5px;
        font-size: 20px;
        padding: 6px 10px;
        border-radius: 10px;
        background: rgba(255, 255, 255, .16);
        box-shadow: inset 0 0 0 1px rgba(255, 255, 255, .3);
    }

    .logo .brand-campus {
        display: none;
        font-size: 12px;
        opacity: .85;
    }

    @media (min-width: 900px) {
        .logo .brand-campus {
            display: inline-block;
        }
    }

    /* If an <img> is added inside .logo, size it nicely */
    .logo img {
        height: 28px;
        width: auto;
        display: block;
        filter: drop-shadow(0 1px 2px rgba(0, 0, 0, .25));
    }

    .navigation {
        display: flex;
        align-items: center;
        gap: 20px;
    }

    @media (min-width: 900px) {
        .navigation {
            gap: 28px;
        }
    }

    .navigation a {
        position: relative;
        color: #cbd5e1;
        text-decoration: none;
        font-weight: 600;
        letter-spacing: .2px;
        padding: 8px 2px;
        transition: color .18s ease, transform .18s ease;
    }

    .navigation a::after {
        content: "";
        position: absolute;
        left: 50%;
        bottom: 0;
        transform: translateX(-50%) scaleX(0);
        transform-origin: center;
        height: 2px;
        width: 70%;
        border-radius: 999px;
        background: linear-gradient(90deg, var(--eng-secondary), var(--cas-secondary), var(--cbaa-secondary), var(--ccje-secondary), var(--cte-secondary));
        transition: transform .18s ease;
    }

    .navigation a:hover {
        color: #ffffff;
        transform: scale(1.03);
    }

    .navigation a:hover::after {
        transform: translateX(-50%) scaleX(1);
    }

    /* Active link indicator */
    .navigation a.active {
        color: #ffffff;
    }

    .navigation a.active::after {
        transform: translateX(-50%) scaleX(1);
        background: #ffffff;
    }

    /* Login CTA */
    .btnLogin-popup {
        border: 2px solid transparent;
        color: #ffffff;
        background: linear-gradient(135deg, var(--eng-primary), var(--cte-primary));
        padding: 8px 16px;
        border-radius: 12px;
        font-weight: 700;
        cursor: pointer;
        transition: all .25s ease;
        box-shadow: 0 4px 15px rgba(22, 163, 74, 0.3);
    }

    .btnLogin-popup:hover {
        background: linear-gradient(135deg, var(--eng-secondary), var(--cte-secondary));
        color: #ffffff;
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(22, 163, 74, 0.4);
    }

    .btnLogin-popup:active {
        transform: translateY(0);
    }

    /* Subtle separator under header when hero is also blue */
    header::after {
        content: "";
        position: absolute;
        left: 0;
        right: 0;
        bottom: -1px;
        height: 1px;
        background: linear-gradient(90deg, rgba(255, 255, 255, .35), rgba(255, 255, 255, .1), rgba(255, 255, 255, .35));
        pointer-events: none;
    }

    /* Mobile navigation (collapsible) */
    .nav-toggle {
        display: none;
        background: transparent;
        border: 0;
        color: #e6f6ff;
        width: 40px;
        height: 40px;
        border-radius: 8px;
        cursor: pointer;
        position: relative;
    }

    .nav-toggle:focus {
        outline: 3px solid rgba(255, 255, 255, .5);
        outline-offset: 2px;
    }

    .nav-toggle .bar {
        position: absolute;
        left: 10px;
        right: 10px;
        height: 2px;
        background: #e6f6ff;
        border-radius: 3px;
        transition: transform .2s ease, opacity .2s ease;
    }

    .nav-toggle .bar:nth-child(1) {
        top: 12px;
    }

    .nav-toggle .bar:nth-child(2) {
        top: 19px;
    }

    .nav-toggle .bar:nth-child(3) {
        top: 26px;
    }

    header.menu-open .nav-toggle .bar:nth-child(1) {
        transform: translateY(7px) rotate(45deg);
    }

    header.menu-open .nav-toggle .bar:nth-child(2) {
        opacity: 0;
    }

    header.menu-open .nav-toggle .bar:nth-child(3) {
        transform: translateY(-7px) rotate(-45deg);
    }

    /* Collapse behaviour */
    @media (max-width: 820px) {
        .nav-toggle {
            display: inline-block;
        }

        nav.navigation {
            display: none;
        }

        header.menu-open nav.navigation {
            display: flex;
            flex-direction: column;
            position: absolute;
            left: 16px;
            right: 16px;
            top: 100%;
            background: linear-gradient(135deg, #1e468f, #0ea5e9);
            border-radius: 12px;
            padding: 16px;
            box-shadow: 0 16px 36px rgba(2, 6, 23, .35);
            gap: 14px;
        }

        header.menu-open nav.navigation a {
            padding: 10px 8px;
            text-align: center;
            font-size: 16px;
        }

        header.menu-open .btnLogin-popup {
            width: 100%;
            text-align: center;
            padding: 10px 16px;
            font-size: 16px;
        }
    }

    /* Smooth scroll */
    html {
        scroll-behavior: smooth;
    }

    .fade-out {
        opacity: 0;
        transition: opacity 0.5s ease;
    }

    /* Public Visualization Styles */
    .viz-section {
        padding: 60px 20px;
        background: #f8fafc;
    }

    .viz-container {
        max-width: 1200px;
        margin: 0 auto;
    }

    .viz-header {
        text-align: center;
        margin-bottom: 20px;
    }

    .viz-subtitle {
        color: #475569;
        font-size: 0.95rem;
    }

    .dept-grid {
        display: grid;
        grid-template-columns: repeat(12, minmax(0, 1fr));
        gap: 16px;
    }

    .overview-card {
        background: #fff;
        border: 1px solid #e6eef8;
        border-radius: 14px;
        box-shadow: 0 8px 18px rgba(2, 6, 23, 0.06);
        padding: 14px;
        display: flex;
        flex-direction: column;
        gap: 10px;
        margin-bottom: 16px;
    }

    .dept-card {
        grid-column: span 12;
        background: #fff;
        border: 1px solid #e6eef8;
        border-radius: 14px;
        box-shadow: 0 8px 18px rgba(2, 6, 23, 0.06);
        padding: 14px;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    /* One department per row on all screen sizes */
    @media (min-width: 900px) {
        .dept-card {
            grid-column: span 12;
        }
    }

    .dept-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
    }

    .dept-left {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .dept-badge {
        color: #fff;
        font-weight: 800;
        padding: 6px 10px;
        border-radius: 999px;
        letter-spacing: .5px;
        font-size: 0.85rem;
    }

    .dept-title {
        font-weight: 800;
        color: #0f172a;
    }

    .dept-desc {
        color: #64748b;
        font-size: 0.9rem;
    }

    .dept-charts {
        display: grid;
        grid-template-columns: 1fr;
        gap: 10px;
    }

    @media (min-width: 720px) {
        .dept-charts {
            grid-template-columns: 1fr 1fr;
        }
    }

    .chart-box {
        background: #fff;
        border: 1px solid #eef2f7;
        border-radius: 12px;
        padding: 10px;
        box-shadow: 0 6px 14px rgba(2, 6, 23, 0.06);
    }

    .chart-box h4 {
        margin: 0 0 6px 0;
        font-size: 0.95rem;
        color: #0f172a;
    }

    /* External legend as compact, scrollable grid */
    .legend {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 8px;
        font-size: 0.85rem;
        color: #475569;
        margin-top: 6px;
        max-height: 160px;
        overflow: auto;
        padding-right: 6px;
        position: relative;
        z-index: 2;
    }

    /* Center the Explore legend chips (Passed/Failed) */
    #explore_legends {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 16px;
        max-height: none;
        overflow: visible;
    }

    .legend-item {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        min-width: 0;
        cursor: pointer;
        user-select: none;
        padding: 6px 10px;
        border-radius: 999px;
        border: 1px solid transparent;
        transition: transform .12s ease, background-color .2s ease, opacity .2s ease, border-color .2s ease;
    }

    .legend-item:active {
        transform: scale(0.98);
    }

    .btn-secondary {
        transition: transform .12s ease, background-color .2s ease, color .2s ease;
    }

    .btn-secondary:active {
        transform: scale(0.98);
    }

    .legend-item .swatch {
        display: inline-block;
        width: 12px;
        height: 12px;
        border-radius: 3px;
        flex: 0 0 auto;
        background: currentColor;
    }

    .legend-item .legend-text {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .legend-item.inactive {
        opacity: .55;
    }

    /* Theme colors - Updated for 5 departments */
    .theme-green {
        /* Engineering */
        --main: #16a34a;
        --accent: #22c55e;
    }

    .theme-pink {
        /* CAS - Arts and Science */
        --main: #BF3853;
        --accent: #E56D85;
    }

    .theme-yellow {
        /* CBAA - Business Administration */
        --main: #d97706;
        --accent: #f59e0b;
    }

    .theme-red {
        /* CCJE - Criminal Justice Education */
        --main: #7f1d1d;
        --accent: #b91c1c;
    }

    .theme-blue {
        /* CTE - Teacher Education */
        --main: #1d4ed8;
        --accent: #3b82f6;
    }

    /* Department chip uses its theme color (green/pink/yellow/red/blue) */
    .dept-badge {
        background: linear-gradient(135deg, var(--main), var(--accent));
    }

    .line-accent {
        height: 4px;
        background: linear-gradient(90deg, var(--main), var(--accent));
        border-radius: 999px;
        opacity: 0.3
    }

    /* Give charts a stable height to prevent resize loops and scrolling jumps */
    /* Keep charts crisp without distortion: preserve aspect ratio */
    .chart-box canvas {
        width: 100% !important;
        height: auto !important;
        aspect-ratio: 16 / 9;
    }

    .legend {
        min-height: 18px;
    }

    /* Center the Pass vs Fail legends under each chart */
    [id^="pf_legend_"] {
        display: flex;
        justify-content: center;
        align-items: center;
        flex-wrap: wrap;
        gap: 8px 16px;
        max-height: none;
        overflow: visible;
        padding-right: 0;
    }

    /* Center the First Time vs Repeater legends under each chart */
    [id^="att_legend_"] {
        display: flex;
        justify-content: center;
        align-items: center;
        flex-wrap: wrap;
        gap: 8px 16px;
        max-height: none;
        overflow: visible;
        padding-right: 0;
    }

    /* Center Top 5 legend and let it wrap nicely */
    [id^="top_legend_"] {
        display: flex;
        justify-content: center;
        align-items: center;
        flex-wrap: wrap;
        gap: 8px 16px;
        max-height: none;
        overflow: visible;
        padding-right: 0;
    }

    /* Center Sex (Male vs Female) legend */
    [id^="sex_legend_"] {
        display: flex;
        justify-content: center;
        align-items: center;
        flex-wrap: wrap;
        gap: 8px 16px;
        max-height: none;
        overflow: visible;
        padding-right: 0;
    }

    /* Chart exports and controls in teal outline/solid styles */
    .chart-box .btn-secondary {
        border: 1px solid #0ea5e9;
        color: #0ea5e9;
        background: #fff;
    }

    .chart-box .btn-secondary:hover {
        background: #0ea5e9;
        color: #fff;
    }

    .chart-box .btn-secondary:active {
        transform: translateY(0);
    }

    /* Print to PDF primary outline button */
    .btn-outline-teal {
        border: 2px solid #0ea5e9;
        color: #0ea5e9;
        background: #ffffff;
        font-weight: 700;
        cursor: pointer;
        transition: background-color .2s ease, color .2s ease, box-shadow .2s ease, transform .12s ease;
    }

    .btn-outline-teal:hover {
        background: #0ea5e9;
        color: #ffffff;
        box-shadow: 0 6px 18px rgba(14, 165, 233, .25);
        transform: translateY(-1px);
    }

    .btn-outline-teal:active {
        transform: translateY(0);
        box-shadow: none;
    }

    /* Fade animations for Explore content */
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(4px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes fadeOut {
        from {
            opacity: 1;
            transform: translateY(0);
        }

        to {
            opacity: 0;
            transform: translateY(-4px);
        }
    }

    .fade-in {
        animation: fadeIn 180ms ease-out;
    }

    .fade-out {
        animation: fadeOut 140ms ease-in;
    }

    /* Explore table */
    .explore-buttons {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin: 8px 0 10px 0;
    }

    .explore-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 14px;
        border-radius: 14px;
        background: linear-gradient(180deg, #ffffff, #fdfdfd);
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 12px rgba(2, 6, 23, 0.06);
        cursor: pointer;
        transition: transform .12s ease, box-shadow .2s ease, background-color .2s ease, color .2s ease, border-color .2s ease;
    }

    .explore-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 10px 22px rgba(14, 165, 233, .12);
        border-color: #bae6fd;
    }

    .explore-btn .dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        box-shadow: 0 0 0 2px #fff inset;
    }

    .explore-btn.active {
        background: #0ea5e9;
        color: #fff;
        border-color: #0ea5e9;
        box-shadow: 0 12px 24px rgba(14, 165, 233, .26);
    }

    .explore-toolbar {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 6px;
    }

    .explore-search {
        flex: 1 1 260px;
    }

    .explore-search input {
        width: 100%;
        padding: 8px 10px;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        transition: box-shadow .15s ease, border-color .15s ease;
    }

    .explore-search input:focus {
        outline: none;
        border-color: #7dd3fc;
        box-shadow: 0 0 0 3px rgba(125, 211, 252, .45);
    }

    .explore-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }

    .explore-table th,
    .explore-table td {
        padding: 10px 12px;
        border-bottom: 1px solid #eef2f7;
        text-align: left;
        font-size: 14px;
    }

    .explore-table th {
        background: linear-gradient(180deg, #f0fdff, #ecfeff);
        color: #0f172a;
        font-weight: 800;
        position: sticky;
        top: 0;
        z-index: 1;
        cursor: pointer;
    }

    .explore-table tbody tr:nth-child(even) {
        background: #f8feff;
    }

    .explore-table tbody tr:hover {
        background: #ecfeff;
    }

    .table-wrap {
        max-height: 420px;
        overflow: auto;
        border: 1px solid #eef2f7;
        border-radius: 10px;
    }

    .status-badge {
        padding: 4px 10px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 800;
    }

    .status-pass {
        background: #0ea5e9;
        color: #ffffff;
    }

    .status-fail {
        background: #b91c1c;
        color: #ffffff;
    }

    .attempt-badge {
        background: #e0f2fe;
        color: #075985;
    }

    .pager {
        display: flex;
        align-items: center;
        gap: 8px;
        justify-content: flex-end;
        margin-top: 8px;
    }

    .pager button {
        padding: 6px 10px;
        border-radius: 8px;
        border: 1px solid #e2e8f0;
        background: #fff;
        cursor: pointer;
    }

    .explore-reset {
        cursor: pointer;
        color: #0369a1;
        font-size: 12px;
        padding: 4px 8px;
        border-radius: 999px;
        border: 1px solid #bae6fd;
        background: #e0f2fe;
    }

    .explore-reset:hover {
        background: #cff1ff;
    }

    /* OLD About section layout - REMOVED (using simplified version below) */

    .about-container {
        max-width: 1200px;
        margin: 0 auto;
        display: grid;
        grid-template-columns: 1fr;
        gap: 24px;
        align-items: start;
    }

    @media (min-width: 900px) {
        .about-container {
            grid-template-columns: 1.2fr .8fr;
        }
    }

    .about-eyebrow {
        text-transform: uppercase;
        letter-spacing: 0.15em;
        font-size: 13px;
        font-weight: 700;
        background: linear-gradient(90deg, var(--eng-primary), var(--cte-primary));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        margin-bottom: 8px;
    }

    .about-title {
        font-size: clamp(2rem, 4vw, 2.8rem);
        font-weight: 800;
        color: #0f172a;
        margin-bottom: 20px;
    }

    .about-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 18px;
        justify-content: center;
        justify-items: center;
        margin-top: 8px;
    }

    @media (min-width: 900px) {
        .about-grid {
            grid-template-columns: repeat(3, auto);
        }
    }

    .about-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 14px;
        box-shadow: 0 6px 14px rgba(2, 6, 23, 0.05);
    }

    .about-card h3 {
        margin: 0 0 6px 0;
        color: #0f172a;
    }

    .about-card p {
        margin: 0;
        color: #475569;
    }

    .about-contact {
        margin: 10px 0 16px 0;
        line-height: 1.6;
        color: #334155;
    }

    .about-link {
        color: var(--cte-primary);
        text-decoration: none;
        transition: color 0.2s ease;
    }
    
    .about-link:hover {
        color: var(--eng-primary);
    }

    .about-link:hover {
        text-decoration: underline;
    }

    .copy-btn {
        margin-left: 8px;
        font-size: 12px;
        padding: 2px 8px;
        border-radius: 999px;
        border: 1px solid #e2e8f0;
        background: #f8fafc;
        cursor: pointer;
    }

    .copy-btn:hover {
        background: #eef2f7;
    }

    /* Flip cards */
    .flip-card {
        --sky: #0ea5e9;
        --teal: #14b8a6;
        position: relative;
        perspective: 1000px;
        cursor: pointer;
        width: clamp(300px, 30vw, 380px);
        margin: 0 auto;
        border-radius: 14px;
    }

    /* gradient ring border, revealed on hover/focus */
    .flip-card::after {
        content: "";
        position: absolute;
        inset: -1px;
        border-radius: 14px;
        padding: 1px;
        background: linear-gradient(135deg, var(--sky), var(--teal));
        -webkit-mask: linear-gradient(#000 0 0) content-box, linear-gradient(#000 0 0);
        -webkit-mask-composite: xor;
        mask: linear-gradient(#000 0 0) content-box, linear-gradient(#000 0 0);
        mask-composite: exclude;
        opacity: 0;
        transition: opacity .25s ease;
        pointer-events: none;
    }

    .flip-card:hover::after,
    .flip-card:focus-within::after {
        opacity: 1;
    }

    .flip-card:focus-visible {
        outline: 2px solid #0ea5e9;
        outline-offset: 3px;
        border-radius: 12px;
    }

    .flip-inner {
        position: relative;
        width: 100%;
        min-height: 260px;
        transform-style: preserve-3d;
        transition: transform .5s cubic-bezier(.2, .65, .3, 1);
        border-radius: 14px;
    }

    .flip-card:hover .flip-face {
        box-shadow: 0 18px 40px rgba(14, 165, 233, .18);
    }

    @media (min-width: 900px) {
        .flip-inner {
            min-height: 320px;
        }
    }

    .flip-card.flipped .flip-inner {
        transform: rotateY(180deg);
    }

    .flip-face {
        position: absolute;
        inset: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        padding: 20px;
        backface-visibility: hidden;
        background: linear-gradient(180deg, #ffffff, #f7fbfd);
        border: 1px solid #e2eef3;
        border-radius: 14px;
        box-shadow: 0 14px 32px rgba(2, 6, 23, 0.08);
        overflow: auto;
    }

    .flip-front {
        overflow: hidden;
    }

    .flip-front h3 {
        margin: 12px 0 4px 0;
        color: #0f172a;
        font-weight: 800;
        font-size: 22px;
        letter-spacing: .02em;
    }

    .flip-hint {
        font-size: 12px;
        color: #94a3b8;
    }

    @media (min-width:900px) {
        .flip-front h3 {
            font-size: 24px;
        }
    }

    .flip-back {
        transform: rotateY(180deg);
        align-items: center;
        justify-content: flex-start;
        text-align: left;
        background:
            radial-gradient(120% 80% at 50% 10%, rgba(14, 165, 233, .08), rgba(20, 184, 166, .04) 50%, transparent 65%),
          
    }

    .about-grid .flip-card {
        animation: riseIn .7s ease both;
    }

    .about-grid .flip-card:nth-child(2) {
        animation-delay: .08s;
    }

    .about-grid .flip-card:nth-child(3) {
        animation-delay: .16s;
    }

    /* Reduced motion safety */
    @media (prefers-reduced-motion: reduce) {

        .flip-inner,
        .flip-card::after,
        .flip-icon {
            transition: none;
            animation: none;
        }
    }

    /* About hero aesthetics */
    .about-eyebrow {
        text-transform: uppercase;
        letter-spacing: .18em;
        font-weight: 600;
        font-size: 12px;
        color: #64748b;
    }

    .about-title {
        font-family: 'Merriweather', 'Poppins', system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
        font-size: clamp(36px, 6.5vw, 66px);
        line-height: 1;
        letter-spacing: .01em;
        font-weight: 800;
        color: #0f172a;
        margin: 6px 0 12px 0;
        position: relative;
    }

    .about-title::after {
        content: "";
        display: block;
        width: 96px;
        height: 8px;
        border-radius: 999px;
        background: linear-gradient(90deg, #0ea5e9, #14b8a6);
        margin-top: 12px;
        opacity: .95;
    }

    .about-intro {
        color: #475569;
        max-width: 56ch;
        margin: 6px 0 14px 0;
    }

    .about-maps {
        margin-top: 6px;
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    .about-maps .sep {
        color: #94a3b8;
    }

    .about-illustration {
        display: grid;
        place-items: center;
    }

    .analytics-chart {
        position: relative;
        width: 100%;
        aspect-ratio: 4/3;
        border-radius: 16px;
        background: linear-gradient(180deg, #f8fafc, #eef2f7);
        border: 1px solid #e2e8f0;
        box-shadow: 0 16px 30px rgba(2, 6, 23, 0.06);
        overflow: hidden;
    }

    .chart-element {
        position: absolute;
        border-radius: 12px;
        box-shadow: 0 8px 18px rgba(2, 6, 23, 0.05);
    }

    .bar-chart {
        left: 10%;
        bottom: 10%;
        width: 56%;
        height: 46%;
        background: linear-gradient(180deg, #bae6fd, #7dd3fc);
        border: 1px solid #93c5fd;
    }

    .pie-chart {
        right: 10%;
        top: 10%;
        width: 34%;
        height: 34%;
        border-radius: 50%;
        background: conic-gradient(#22c55e 0 40%, #3b82f6 40% 70%, #f59e0b 70% 100%);
        border: 1px solid #e2e8f0;
    }

    /* About meta with icons */
    .meta-list {
        margin: 10px 0 8px 0;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .meta-item {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .icon-circle {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        display: grid;
        place-items: center;
        background: linear-gradient(135deg, #0ea5e9, #14b8a6);
        box-shadow: 0 10px 20px rgba(14, 165, 233, .25);
        flex: 0 0 auto;
    }

    .icon-circle svg {
        width: 18px;
        height: 18px;
        color: #fff;
    }

    .meta-text {
        color: #334155;
    }

    .pill-list {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin: 10px 0 12px 0;
    }

    .pill {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 6px 10px;
        border-radius: 999px;
        border: 1px solid #cfeff2;
        background: linear-gradient(180deg, #f0fdff, #ecfeff);
        color: #0f172a;
        font-size: 13px;
        box-shadow: 0 6px 12px rgba(20, 184, 166, .08);
    }

    .pill svg {
        width: 16px;
        height: 16px;
        color: #0ea5e9;
    }

    .kpi-chip {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 5px 10px;
        border-radius: 999px;
        border: 1px solid #0ea5e9;
        background: linear-gradient(180deg, #f0fdff, #ecfeff);
        color: #0369a1;
        font-weight: 800;
        font-size: 12px;
        box-shadow: 0 6px 12px rgba(14, 165, 233, .10);
    }

    /* Hero banner for About */
    .about-hero {
        max-width: 1100px;
        margin: 0 auto 18px auto;
        height: clamp(140px, 24vw, 220px);
        border-radius: 16px;
        overflow: hidden;
        border: 1px solid #e2eef3;
        box-shadow: 0 16px 36px rgba(2, 6, 23, .06);
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        position: relative;
    }

    .about-hero::before {
        content: "";
        position: absolute;
        inset: 0;
        background: linear-gradient(180deg, rgba(14, 165, 233, .25), rgba(20, 184, 166, .18));
    }

    .about-hero .hero-credit {
        position: absolute;
        bottom: 8px;
        right: 10px;
        font-size: 11px;
        color: #475569;
        backdrop-filter: blur(3px);
        background: rgba(255, 255, 255, .4);
        padding: 2px 8px;
        border-radius: 999px;
    }

    /* Mission callout */
    .mission-callout {
        margin: 10px 0 16px 0;
        border: 1px solid #cfeff2;
        background: linear-gradient(180deg, #f0fdff, #ffffff);
        border-radius: 14px;
        padding: 16px 18px;
        text-align: center;
        box-shadow: 0 10px 24px rgba(14, 165, 233, .08);
    }

    .mission-callout p {
        font-size: clamp(16px, 1.2vw, 18px);
        color: #334155;
        margin: 0;
    }

    /* Values grid */
    .values-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 18px;
        margin: 10px 0 18px 0;
    }

    @media (min-width: 640px) {
        .values-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (min-width: 900px) {
        .values-grid {
            grid-template-columns: repeat(4, 1fr);
        }
    }

    .value-card {
        background: #fff;
        border: 1px solid #cfeff2;
        border-radius: 14px;
        padding: 16px;
        text-align: center;
        box-shadow: 0 12px 24px rgba(2, 6, 23, .06);
        transition: transform .18s ease, box-shadow .18s ease;
    }

    .value-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 16px 32px rgba(14, 165, 233, .12);
    }

    .value-icon {
        width: 56px;
        height: 56px;
        display: grid;
        place-items: center;
        border-radius: 14px;
        margin: 0 auto 10px auto;
        color: #0ea5e9;
        background: #f0fdff;
        border: 1px solid #cfeff2;
        transition: transform .18s ease, color .18s ease, filter .18s ease;
    }

    .value-card:hover .value-icon {
        transform: scale(1.04);
        color: #0284c7;
        filter: brightness(1.05);
    }

    .value-icon svg {
        width: 26px;
        height: 26px;
        color: currentColor;
    }

    .value-card h4 {
        margin: 4px 0 6px 0;
        font-weight: 800;
        color: #0ea5e9;
        letter-spacing: .01em;
    }

    .value-card p {
        margin: 0;
        color: #475569;
        font-size: 14px;
        line-height: 1.45;
    }

    /* Explore: Header styling */
    #explore .viz-header h2 {
        color: #0ea5e9;
        font-weight: 800;
    }

    #explore .viz-header h2::after {
        content: "";
        display: block;
        width: 96px;
        height: 8px;
        border-radius: 999px;
        background: linear-gradient(90deg, #0ea5e9, #14b8a6);
        margin: 10px auto 0 auto;
    }

    #explore .viz-subtitle {
        color: #667085;
    }

    /* Explore Passed/Failed legend as pill tags */
    #explore_legends .legend-item {
        border-radius: 999px;
        border-width: 1px;
        border-style: solid;
        padding: 6px 12px;
        font-weight: 700;
    }

    #explore_legends .legend-item .swatch {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        box-shadow: 0 0 0 2px rgba(255, 255, 255, .6);
    }

    #explore_legends .legend-item[data-result="Passed"] {
        --chip: #0ea5e9;
        background: var(--chip);
        color: #fff;
        border-color: var(--chip);
    }

    #explore_legends .legend-item[data-result="Failed"] {
        --chip: #b91c1c;
        background: var(--chip);
        color: #fff;
        border-color: var(--chip);
    }

    #explore_legends .legend-item.inactive[data-result="Passed"] {
        background: transparent;
        color: #0ea5e9;
        border-color: rgba(14, 165, 233, .45);
    }

    #explore_legends .legend-item.inactive[data-result="Failed"] {
        background: transparent;
        color: #b91c1c;
        border-color: rgba(185, 28, 28, .45);
    }

    /* Explore Sex chips (Male/Female) */
    #explore_legends .legend-item[data-sex="Male"] {
        --chip: #0284c7;
        background: var(--chip);
        color: #fff;
        border-color: var(--chip);
    }

    #explore_legends .legend-item[data-sex="Female"] {
        --chip: #ec4899;
        background: var(--chip);
        color: #fff;
        border-color: var(--chip);
    }

    #explore_legends .legend-item.inactive[data-sex="Male"] {
        background: transparent;
        color: #0284c7;
        border-color: rgba(2, 132, 199, .45);
    }

    #explore_legends .legend-item.inactive[data-sex="Female"] {
        background: transparent;
        color: #ec4899;
        border-color: rgba(236, 72, 153, .45);
    }

    /* Explore export buttons */
    #explore_csv {
        background: #fff;
        color: #0ea5e9;
        border: 1px solid #0ea5e9;
    }

    #explore_csv:hover {
        background: #f0fdff;
    }

    #explore_xlsx {
        background: #0ea5e9;
        color: #fff;
        border: 1px solid #0ea5e9;
    }

    #explore_xlsx:hover {
        background: #0284c7;
        border-color: #0284c7;
    }

    /* Modal for full policy */
    .modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(2, 6, 23, .55);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 2147483647;
    }

    body.no-scroll {
        overflow: hidden;
    }

    .modal {
        position: relative;
        background: #fff;
        border-radius: 18px;
        border: 1px solid #e2eef3;
        max-width: 760px;
        width: 92vw;
        max-height: 80vh;
        overflow: hidden;
        box-shadow: 0 20px 60px rgba(14, 165, 233, .18), 0 14px 40px rgba(2, 6, 23, .12);
        animation: breathe 6s ease-in-out infinite;
    }

    /* outer teal glow */
    .modal::before {
        content: "";
        position: absolute;
        inset: -12px;
        border-radius: 26px;
        background: radial-gradient(120% 120% at 50% 50%, rgba(14, 165, 233, .28), rgba(20, 184, 166, .18) 40%, transparent 65%);
        filter: blur(14px);
        opacity: .65;
        pointer-events: none;
        animation: breatheGlow 6s ease-in-out infinite;
    }

    /* animated flowing border */
    .modal::after {
        content: "";
        position: absolute;
        inset: -2px;
        border-radius: 18px;
        padding: 2px;
        background: linear-gradient(135deg, #0ea5e9, #14b8a6, #0ea5e9);
        background-size: 300% 300%;
        animation: borderflow 8s linear infinite;
        -webkit-mask: linear-gradient(#000 0 0) content-box, linear-gradient(#000 0 0);
        -webkit-mask-composite: xor;
        mask: linear-gradient(#000 0 0) content-box, linear-gradient(#000 0 0);
        mask-composite: exclude;
        pointer-events: none;
    }

    .modal header {
        position: sticky;
        top: 0;
        background: #ffffffcc;
        backdrop-filter: saturate(1.2) blur(6px);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 14px 18px;
        border-bottom: 1px solid #eef2f7;
        z-index: 1;
    }

    .modal header h4 {
        margin: 0;
        font-size: 20px;
        color: #0ea5e9;
        font-weight: 800;
        font-family: 'Merriweather', 'Poppins', system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
    }

    .modal .modal-body {
        padding: 20px;
        color: #334155;
        line-height: 1.7;
        font-size: 16px;
        text-align: left;
        max-height: calc(80vh - 64px);
        overflow: auto;
        overflow-x: hidden;
        scrollbar-width: none;
        -ms-overflow-style: none;
    }

    .modal .modal-body::-webkit-scrollbar {
        width: 0;
        height: 0;
    }

    .modal .close {
        position: absolute;
        right: 10px;
        top: 10px;
        background: transparent;
        border: 0;
        color: #0f172a;
        font-size: 22px;
        line-height: 1;
        cursor: pointer;
        padding: 6px;
        border-radius: 8px;
    }

    .modal .close:hover {
        background: #f1f5f9;
    }

    @keyframes borderflow {
        0% {
            background-position: 0% 50%;
        }

        50% {
            background-position: 100% 50%;
        }

        100% {
            background-position: 0% 50%;
        }
    }

    @keyframes breathe {

        0%,
        100% {
            box-shadow: 0 20px 60px rgba(14, 165, 233, .18), 0 14px 40px rgba(2, 6, 23, .12);
        }

        50% {
            box-shadow: 0 26px 70px rgba(14, 165, 233, .26), 0 18px 52px rgba(2, 6, 23, .14);
        }
    }

    @keyframes breatheGlow {

        0%,
        100% {
            opacity: .55;
        }

        50% {
            opacity: .75;
        }
    }

    /* Contact card */
    .contact-card {
        background: #fff;
        border: 1px solid #e2eef3;
        border-radius: 14px;
        padding: 14px;
        box-shadow: 0 12px 26px rgba(2, 6, 23, .06);
    }

    .contact-card h3 {
        margin: 0 0 8px 0;
        color: #0f172a;
    }

    /* Services: Multi-Department Focus Ring + premium card styling */
    .services-section {
        background: linear-gradient(180deg, #f1f5f9 0%, #e2e8f0 100%);
        padding: 80px 40px;
        position: relative;
    }
    
    .services-section h2 {
        text-align: center;
        font-size: clamp(2rem, 4vw, 2.8rem);
        font-weight: 800;
        background: linear-gradient(135deg, var(--eng-primary), var(--cte-primary));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        margin-bottom: 40px;
    }

    .services-section .services-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
        gap: 24px;
        max-width: 1200px;
        margin: 0 auto;
    }

    .services-section .service-card {
        background: linear-gradient(180deg, #ffffff, #f8fafc);
        border: 1px solid #e2e8f0;
        border-radius: 20px;
        padding: 30px 24px;
        text-align: center;
        box-shadow: 0 10px 24px rgba(2, 6, 23, .06);
        transition: all .3s cubic-bezier(0.34, 1.56, 0.64, 1);
        cursor: pointer;
        position: relative;
        overflow: hidden;
    }
    
    .services-section .service-card::before {
        content: "";
        position: absolute;
        inset: 0;
        border-radius: 20px;
        padding: 2px;
        background: linear-gradient(135deg, var(--eng-primary), var(--cas-primary), var(--cbaa-primary), var(--ccje-primary), var(--cte-primary));
        -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
        -webkit-mask-composite: xor;
        mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
        mask-composite: exclude;
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .services-section .service-card:hover::before {
        opacity: 1;
    }

    .services-section .service-card:hover {
        box-shadow: 0 20px 40px rgba(0, 0, 0, .12);
        transform: translateY(-8px);
    }

    .services-section .service-card h3 {
        margin: 10px 0 8px;
        font-weight: 800;
        color: #0f172a;
    }

    .services-section .service-card p {
        margin: 0;
        color: #64748b;
    }

    .services-section .service-icon {
        width: 72px;
        height: 72px;
        margin: 0 auto 12px auto;
        display: grid;
        place-items: center;
        border-radius: 18px;
        color: #fff;
        position: relative;
        box-shadow: 0 10px 25px rgba(0, 0, 0, .15);
        transition: transform 0.3s ease;
    }
    
    .services-section .service-card:hover .service-icon {
        transform: scale(1.1) rotate(5deg);
    }

    .services-section .service-icon i {
        font-size: 28px;
        line-height: 1;
    }

    .services-section .service-icon::after {
        content: "";
        position: absolute;
        inset: 0;
        border-radius: inherit;
        box-shadow: 0 0 0 0 currentColor;
        opacity: 0;
        pointer-events: none;
    }

    .services-section .service-card:hover .service-icon::after {
        animation: focusRing .7s ease-out forwards;
    }

    @keyframes focusRing {
        0% {
            opacity: .85;
            box-shadow: 0 0 0 0 rgba(22, 163, 74, .45);
        }

        70% {
            opacity: .3;
            box-shadow: 0 0 0 14px rgba(22, 163, 74, .25);
        }

        100% {
            opacity: 0;
            box-shadow: 0 0 0 20px rgba(22, 163, 74, 0);
        }
    }

    /* Icon background gradients by type - using department colors */
    .services-section .service-icon.statistics-icon {
        background: var(--eng-gradient);
    }

    .services-section .service-icon.search-icon {
        background: var(--cas-gradient);
    }

    .services-section .service-icon.reports-icon {
        background: var(--cbaa-gradient);
    }

    .services-section .service-icon.compare-icon {
        background: var(--cte-gradient);
    }

    /* Title color matching icon accent (icon + immediate h3 sibling) */
    .services-section .service-icon.statistics-icon+h3 {
        color: var(--eng-primary);
    }

    .services-section .service-icon.search-icon+h3 {
        color: var(--cas-primary);
    }

    .services-section .service-icon.reports-icon+h3 {
        color: var(--cbaa-primary);
    }

    .services-section .service-icon.compare-icon+h3 {
        color: var(--cte-primary);
    }

    /* =====================================================
       HERO SECTION - Multi-Department Theme Design
       ===================================================== */
    .hero-section {
        position: relative;
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
        min-height: auto;
        padding: 120px 0 60px; /* Added top padding for fixed header */
        overflow: hidden;
    }

    .hero-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 40px 20px;
        display: grid;
        grid-template-columns: 1fr;
        align-items: center;
        gap: 40px;
        position: relative;
        z-index: 2;
    }

    @media (min-width: 900px) {
        .hero-container {
            grid-template-columns: 1.2fr 0.8fr;
            padding: 60px 40px;
        }
    }

    /* Animated multi-color gradient background */
    .hero-section::before {
        content: "";
        position: absolute;
        inset: 0;
        pointer-events: none;
        background:
            radial-gradient(ellipse 80% 50% at 10% 20%, rgba(22, 163, 74, 0.15), transparent 50%),
            radial-gradient(ellipse 80% 50% at 90% 30%, rgba(191, 56, 83, 0.12), transparent 50%),
            radial-gradient(ellipse 60% 40% at 50% 80%, rgba(29, 78, 216, 0.1), transparent 50%),
            radial-gradient(ellipse 70% 45% at 30% 60%, rgba(217, 119, 6, 0.08), transparent 50%),
            radial-gradient(ellipse 70% 45% at 70% 70%, rgba(127, 29, 29, 0.08), transparent 50%);
        animation: heroGradientPulse 12s ease-in-out infinite;
        will-change: transform, opacity;
    }

    @keyframes heroGradientPulse {
        0%, 100% { opacity: 0.8; transform: scale(1); }
        50% { opacity: 1; transform: scale(1.05); }
    }

    /* Floating department color orbs */
    .hero-section .floating-orbs {
        position: absolute;
        inset: 0;
        overflow: hidden;
        pointer-events: none;
    }

    .hero-section .orb {
        position: absolute;
        border-radius: 50%;
        filter: blur(60px);
        opacity: 0.4;
        animation: floatOrb 20s ease-in-out infinite;
    }

    .hero-section .orb-eng {
        width: 300px;
        height: 300px;
        background: var(--eng-primary);
        top: 10%;
        left: 5%;
        animation-delay: 0s;
    }

    .hero-section .orb-cas {
        width: 250px;
        height: 250px;
        background: var(--cas-primary);
        top: 60%;
        right: 10%;
        animation-delay: -4s;
    }

    .hero-section .orb-cbaa {
        width: 200px;
        height: 200px;
        background: var(--cbaa-primary);
        bottom: 20%;
        left: 30%;
        animation-delay: -8s;
    }

    .hero-section .orb-ccje {
        width: 180px;
        height: 180px;
        background: var(--ccje-primary);
        top: 30%;
        right: 25%;
        animation-delay: -12s;
    }

    .hero-section .orb-cte {
        width: 220px;
        height: 220px;
        background: var(--cte-primary);
        bottom: 10%;
        right: 5%;
        animation-delay: -16s;
    }

    @keyframes floatOrb {
        0%, 100% { transform: translate(0, 0) scale(1); }
        25% { transform: translate(30px, -20px) scale(1.1); }
        50% { transform: translate(-20px, 30px) scale(0.95); }
        75% { transform: translate(-30px, -10px) scale(1.05); }
    }

    /* Grid pattern overlay */
    .hero-section::after {
        content: "";
        position: absolute;
        inset: 0;
        pointer-events: none;
        background-image: 
            linear-gradient(rgba(255,255,255,0.03) 1px, transparent 1px),
            linear-gradient(90deg, rgba(255,255,255,0.03) 1px, transparent 1px);
        background-size: 50px 50px;
        opacity: 0.5;
    }

    .hero-content {
        position: relative;
        z-index: 3;
    }

    .hero-content h1 {
        font-size: clamp(2.5rem, 6vw, 4rem);
        font-weight: 800;
        line-height: 1.1;
        margin: 0 0 20px 0;
        background: linear-gradient(135deg, #ffffff 0%, #e2e8f0 50%, #94a3b8 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .hero-content p {
        color: #94a3b8;
        font-size: 18px;
        line-height: 1.7;
        margin-bottom: 30px;
    }

    .hero-eyebrow {
        text-transform: uppercase;
        letter-spacing: .2em;
        font-weight: 600;
        font-size: 13px;
        background: linear-gradient(90deg, var(--eng-secondary), var(--cas-secondary), var(--cbaa-secondary), var(--ccje-secondary), var(--cte-secondary));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        margin-bottom: 12px;
        display: block;
        animation: shimmer 3s ease-in-out infinite;
    }

    @keyframes shimmer {
        0%, 100% { opacity: 0.8; }
        50% { opacity: 1; }
    }

    /* CTA Button with gradient animation */
    #btn_explore {
        position: relative;
        background: linear-gradient(135deg, var(--eng-primary), var(--cte-primary));
        color: #fff;
        border: 0;
        border-radius: 16px;
        padding: 16px 32px;
        font-size: 16px;
        font-weight: 700;
        cursor: pointer;
        box-shadow: 
            0 10px 30px rgba(22, 163, 74, 0.3),
            0 0 0 1px rgba(255,255,255,0.1) inset;
        transition: all 0.3s ease;
        overflow: hidden;
    }

    #btn_explore::before {
        content: "";
        position: absolute;
        inset: 0;
        background: linear-gradient(135deg, var(--cte-primary), var(--cas-primary), var(--eng-primary));
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    #btn_explore:hover {
        transform: translateY(-3px);
        box-shadow: 
            0 20px 40px rgba(22, 163, 74, 0.4),
            0 0 0 1px rgba(255,255,255,0.2) inset;
    }

    #btn_explore:hover::before {
        opacity: 1;
    }

    #btn_explore span {
        position: relative;
        z-index: 1;
    }

    #btn_explore:active {
        transform: translateY(-1px);
    }

    /* Hero illustration - Department cards showcase */
    .hero-illustration {
        display: grid;
        place-items: center;
        position: relative;
        z-index: 3;
    }

    .dashboard-mockup {
        width: clamp(280px, 45vw, 420px);
        height: clamp(200px, 30vw, 320px);
        background: rgba(30, 41, 59, 0.8);
        backdrop-filter: blur(20px);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 24px;
        box-shadow: 
            0 25px 50px rgba(0, 0, 0, 0.3),
            0 0 0 1px rgba(255,255,255,0.05) inset;
        position: relative;
        padding: 20px;
        overflow: hidden;
    }

    .mockup-header {
        height: 14px;
        border-radius: 7px;
        background: linear-gradient(90deg, 
            var(--eng-primary), 
            var(--cas-primary), 
            var(--cbaa-primary), 
            var(--ccje-primary), 
            var(--cte-primary));
        background-size: 200% 100%;
        animation: gradientSlide 4s ease-in-out infinite;
    }

    @keyframes gradientSlide {
        0%, 100% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
    }

    .mockup-charts {
        position: absolute;
        inset: auto 20px 20px 20px;
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        align-items: end;
        gap: 8px;
    }

    .chart-bar {
        position: relative;
        height: 40px;
        border-radius: 8px;
        transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        cursor: pointer;
    }

    .chart-bar:nth-child(1) {
        background: var(--eng-gradient);
        height: 70px;
        animation: barPulse 2s ease-in-out infinite;
        animation-delay: 0s;
    }

    .chart-bar:nth-child(2) {
        background: var(--cas-gradient);
        height: 55px;
        animation: barPulse 2s ease-in-out infinite;
        animation-delay: 0.2s;
    }

    .chart-bar:nth-child(3) {
        background: var(--cbaa-gradient);
        height: 85px;
        animation: barPulse 2s ease-in-out infinite;
        animation-delay: 0.4s;
    }

    .chart-bar:nth-child(4) {
        background: var(--ccje-gradient);
        height: 45px;
        animation: barPulse 2s ease-in-out infinite;
        animation-delay: 0.6s;
    }

    .chart-bar:nth-child(5) {
        background: var(--cte-gradient);
        height: 65px;
        animation: barPulse 2s ease-in-out infinite;
        animation-delay: 0.8s;
    }

    @keyframes barPulse {
        0%, 100% { transform: scaleY(1); }
        50% { transform: scaleY(1.1); }
    }

    .chart-bar:hover {
        transform: scaleY(1.2) translateY(-5px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.3);
    }

    /* Remove old chart-pie styling, replace with department icons */
    .chart-pie {
        display: none;
    }

    .user-avatar {
        position: absolute;
        right: -30px;
        bottom: 20px;
        width: 70px;
        height: 70px;
        border-radius: 50%;
        display: grid;
        place-items: center;
        background: linear-gradient(135deg, var(--eng-primary), var(--cte-primary));
        box-shadow: 0 10px 30px rgba(22, 163, 74, 0.4);
        animation: avatarFloat 4s ease-in-out infinite;
    }

    @keyframes avatarFloat {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-10px); }
    }

    .user-avatar::before {
        content: "📊";
        font-size: 28px;
    }

    .user-avatar::after {
        content: "";
        position: absolute;
        inset: -8px;
        border-radius: inherit;
        box-shadow: 0 0 0 0 rgba(22, 163, 74, 0.4);
        animation: pulseRing 2.5s ease-out infinite;
    }

    @keyframes pulseRing {
        0% {
            box-shadow: 0 0 0 0 rgba(22, 163, 74, 0.4);
        }
        70% {
            box-shadow: 0 0 0 20px rgba(22, 163, 74, 0);
        }
        100% {
            box-shadow: 0 0 0 0 rgba(22, 163, 74, 0);
        }
    }

    /* =====================================================
       DEPARTMENT SHOWCASE CARDS - Below Hero
       ===================================================== */
    .departments-showcase {
        background: linear-gradient(180deg, #0f172a 0%, #1e293b 100%);
        padding: 60px 20px 80px;
        position: relative;
    }

    .departments-showcase::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 1px;
        background: linear-gradient(90deg, 
            transparent, 
            var(--eng-primary), 
            var(--cas-primary), 
            var(--cbaa-primary), 
            var(--ccje-primary), 
            var(--cte-primary), 
            transparent);
    }

    .showcase-title {
        text-align: center;
        margin-bottom: 50px;
    }

    .showcase-title h2 {
        font-size: clamp(1.8rem, 4vw, 2.5rem);
        font-weight: 800;
        color: #ffffff;
        margin-bottom: 10px;
    }

    .showcase-title p {
        color: #94a3b8;
        font-size: 16px;
    }

    .dept-cards-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 24px;
        max-width: 1200px;
        margin: 0 auto;
    }

    @media (min-width: 1100px) {
        .dept-cards-grid {
            grid-template-columns: repeat(5, 1fr);
        }
    }

    .dept-showcase-card {
        position: relative;
        background: rgba(30, 41, 59, 0.6);
        backdrop-filter: blur(10px);
        border-radius: 20px;
        padding: 24px 20px;
        text-align: center;
        cursor: pointer;
        transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        overflow: hidden;
        border: 1px solid rgba(255,255,255,0.05);
    }

    .dept-showcase-card::before {
        content: "";
        position: absolute;
        inset: 0;
        border-radius: 20px;
        padding: 2px;
        background: var(--card-gradient);
        -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
        -webkit-mask-composite: xor;
        mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
        mask-composite: exclude;
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .dept-showcase-card:hover::before {
        opacity: 1;
    }

    .dept-showcase-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 20px 40px rgba(0,0,0,0.3);
    }

    .dept-showcase-card.card-eng { --card-gradient: var(--eng-gradient); --card-color: var(--eng-primary); }
    .dept-showcase-card.card-cas { --card-gradient: var(--cas-gradient); --card-color: var(--cas-primary); }
    .dept-showcase-card.card-cbaa { --card-gradient: var(--cbaa-gradient); --card-color: var(--cbaa-primary); }
    .dept-showcase-card.card-ccje { --card-gradient: var(--ccje-gradient); --card-color: var(--ccje-primary); }
    .dept-showcase-card.card-cte { --card-gradient: var(--cte-gradient); --card-color: var(--cte-primary); }

    .dept-showcase-card .card-icon {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        margin: 0 auto 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: var(--card-gradient);
        font-size: 28px;
        box-shadow: 0 8px 20px rgba(0,0,0,0.3);
        transition: transform 0.3s ease;
    }

    .dept-showcase-card:hover .card-icon {
        transform: scale(1.1) rotate(5deg);
    }

    .dept-showcase-card h3 {
        color: #ffffff;
        font-size: 14px;
        font-weight: 700;
        margin: 8px 0 0 0;
        line-height: 1.3;
    }

    .dept-showcase-card .card-abbrev {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 700;
        color: var(--card-color);
        background: rgba(255,255,255,0.1);
        margin-bottom: 8px;
    }

    /* =====================================================
       SIMPLIFIED ABOUT SECTION - Override all old styles
       ===================================================== */
    .about-section {
        padding: 50px 20px !important;
        background: #f8fafc !important;
        text-align: center !important;
        position: relative !important;
        overflow: visible !important;
        min-height: auto !important;
    }
    
    .about-section::before,
    .about-section::after {
        display: none !important;
    }

    .about-container-simple {
        max-width: 800px;
        margin: 0 auto;
    }

    .about-eyebrow {
        text-transform: uppercase;
        letter-spacing: 0.15em;
        font-size: 12px;
        font-weight: 700;
        background: linear-gradient(90deg, var(--eng-primary), var(--cte-primary)) !important;
        -webkit-background-clip: text !important;
        -webkit-text-fill-color: transparent !important;
        background-clip: text !important;
        margin-bottom: 8px !important;
    }

    .about-title {
        font-family: 'Poppins', sans-serif !important;
        font-size: 2rem !important;
        font-weight: 800 !important;
        color: #0f172a !important;
        -webkit-text-fill-color: #0f172a !important;
        margin: 0 0 16px 0 !important;
        position: relative !important;
        display: inline-block !important;
        line-height: 1.3 !important;
    }
    
    .about-title::after {
        content: "" !important;
        position: absolute !important;
        left: 50% !important;
        transform: translateX(-50%) !important;
        bottom: -8px !important;
        width: 60px !important;
        height: 4px !important;
        background: linear-gradient(90deg, var(--eng-primary), var(--cte-primary)) !important;
        border-radius: 4px !important;
        margin: 0 !important;
        opacity: 1 !important;
        display: block !important;
    }

    .about-brief {
        color: #64748b !important;
        font-size: 15px !important;
        line-height: 1.6 !important;
        margin-bottom: 24px !important;
        margin-top: 16px !important;
    }

    .about-quick-info {
        display: flex;
        justify-content: center;
        flex-wrap: wrap;
        gap: 24px;
    }

    .quick-info-item {
        display: flex;
        align-items: center;
        gap: 8px;
        color: #475569;
        font-size: 14px;
    }

    .info-icon {
        font-size: 18px;
    }

    /* =====================================================
       SIMPLIFIED SERVICES SECTION
       ===================================================== */
    .services-section {
        padding: 50px 20px;
        background: linear-gradient(180deg, #0f172a 0%, #1e293b 100%);
    }

    .services-container {
        max-width: 900px;
        margin: 0 auto;
        text-align: center;
    }

    .services-section h2 {
        font-size: 1.8rem;
        font-weight: 800;
        color: #ffffff;
        margin-bottom: 30px;
        background: linear-gradient(90deg, var(--eng-secondary), var(--cas-secondary), var(--cbaa-secondary), var(--ccje-secondary), var(--cte-secondary));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .services-grid-simple {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
    }

    .service-item {
        background: rgba(255,255,255,0.05);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 16px;
        padding: 24px 16px;
        transition: all 0.3s ease;
    }

    .service-item:hover {
        background: rgba(255,255,255,0.1);
        transform: translateY(-4px);
    }

    .service-emoji {
        font-size: 32px;
        display: block;
        margin-bottom: 12px;
    }

    .service-item h4 {
        color: #ffffff;
        font-size: 13px;
        font-weight: 600;
        margin: 0;
    }

    /* Mobile responsive for simplified sections */
    @media (max-width: 768px) {
        .about-quick-info {
            flex-direction: column;
            align-items: center;
            gap: 12px;
        }
        
        .services-grid-simple {
            grid-template-columns: repeat(2, 1fr);
        }
        
        .dept-cards-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
        }
        
        .dept-showcase-card {
            padding: 16px 12px;
        }
        
        .dept-showcase-card .card-icon {
            width: 48px;
            height: 48px;
            font-size: 22px;
            margin-bottom: 10px;
        }
        
        .dept-showcase-card h3 {
            font-size: 12px;
        }
        
        .dept-showcase-card .card-abbrev {
            font-size: 10px;
            padding: 3px 8px;
        }
    }

    @media (max-width: 480px) {
        .services-grid-simple {
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }
        
        .service-item {
            padding: 16px 10px;
        }
        
        .service-emoji {
            font-size: 24px;
        }
        
        .service-item h4 {
            font-size: 11px;
        }
        
        .departments-showcase {
            padding: 40px 16px 60px;
        }
    }

    @keyframes breatheBtn {
        0%,
        100% {
            transform: translateY(0);
        }
        50% {
            transform: translateY(-0.6px);
        }
    }

    /* floating background circles */
    .hero-container::before,
    .hero-container::after {
        content: "";
        position: absolute;
        border-radius: 50%;
        pointer-events: none;
        opacity: .15;
        filter: blur(0.2px);
    }

    .hero-container::before {
        width: 90px;
        height: 90px;
        left: 6%;
        top: 22%;
        background: radial-gradient(circle, rgba(14, 165, 233, .25), rgba(14, 165, 233, 0));
        animation: floatA 18s ease-in-out infinite alternate;
    }

    .hero-container::after {
        width: 120px;
        height: 120px;
        right: 8%;
        bottom: 18%;
        background: radial-gradient(circle, rgba(20, 184, 166, .22), rgba(20, 184, 166, 0));
        animation: floatB 22s ease-in-out infinite alternate;
    }

    @keyframes floatA {
        0% {
            transform: translate(0, 0);
        }

        100% {
            transform: translate(14px, -10px);
        }
    }

    @keyframes floatB {
        0% {
            transform: translate(0, 0);
        }

        100% {
            transform: translate(-12px, 12px);
        }
    }

    /* =====================================================
       COMPREHENSIVE MOBILE RESPONSIVENESS
       ===================================================== */
    
    /* Tablet */
    @media (max-width: 991px) {
        .hero-container {
            grid-template-columns: 1fr;
            text-align: center;
            padding: 60px 20px 40px;
        }
        
        .hero-content {
            order: 1;
        }
        
        .hero-illustration {
            order: 2;
        }
        
        .hero-content h1 {
            font-size: 2.5rem;
        }
        
        .hero-content p {
            font-size: 16px;
        }
        
        .dashboard-mockup {
            width: 320px;
            height: 200px;
        }
        
        .user-avatar {
            right: -10px;
            bottom: 10px;
            width: 50px;
            height: 50px;
        }
        
        .user-avatar::before {
            font-size: 20px;
        }
        
        .about-section {
            padding: 60px 20px;
        }
        
        .services-section {
            padding: 60px 20px;
        }
    }
    
    /* Mobile */
    @media (max-width: 767px) {
        header {
            padding: 10px 16px;
        }
        
        .logo .brand-abbrev {
            font-size: 16px;
            padding: 4px 8px;
        }
        
        .hero-section {
            min-height: auto;
            padding-bottom: 40px;
        }
        
        .hero-container {
            padding: 40px 16px 30px;
            gap: 30px;
        }
        
        .hero-content h1 {
            font-size: 1.8rem;
            line-height: 1.2;
        }
        
        .hero-eyebrow {
            font-size: 10px;
            letter-spacing: 0.1em;
        }
        
        .hero-content p {
            font-size: 14px;
            line-height: 1.6;
        }
        
        #btn_explore {
            padding: 12px 24px;
            font-size: 14px;
            border-radius: 12px;
        }
        
        .dashboard-mockup {
            width: 280px;
            height: 180px;
            padding: 14px;
        }
        
        .mockup-charts {
            gap: 5px;
        }
        
        .chart-bar:nth-child(1) { height: 50px; }
        .chart-bar:nth-child(2) { height: 40px; }
        .chart-bar:nth-child(3) { height: 60px; }
        .chart-bar:nth-child(4) { height: 35px; }
        .chart-bar:nth-child(5) { height: 45px; }
        
        .user-avatar {
            width: 45px;
            height: 45px;
            right: -5px;
            bottom: 5px;
        }
        
        .user-avatar::before {
            font-size: 18px;
        }
        
        .departments-showcase {
            padding: 40px 16px 50px;
        }
        
        .showcase-title h2 {
            font-size: 1.5rem;
        }
        
        .showcase-title p {
            font-size: 14px;
        }
        
        .dept-cards-grid {
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }
        
        .dept-showcase-card {
            padding: 16px 12px;
        }
        
        .dept-showcase-card .card-icon {
            width: 50px;
            height: 50px;
            font-size: 22px;
            margin-bottom: 12px;
        }
        
        .dept-showcase-card h3 {
            font-size: 12px;
        }
        
        .dept-showcase-card .card-abbrev {
            font-size: 10px;
            padding: 3px 8px;
        }
        
        .dept-showcase-card p {
            display: none; /* Hide description on small screens */
        }
        
        .dept-showcase-card .card-stats {
            margin-top: 12px;
            padding-top: 12px;
            gap: 12px;
        }
        
        .dept-showcase-card .stat-value {
            font-size: 16px;
        }
        
        .dept-showcase-card .stat-label {
            font-size: 9px;
        }
        
        .about-section {
            padding: 50px 16px;
        }
        
        .about-title {
            font-size: 1.8rem;
        }
        
        .values-grid {
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }
        
        .value-card {
            padding: 16px 12px;
        }
        
        .services-section {
            padding: 50px 16px;
        }
        
        .services-section h2 {
            font-size: 1.8rem;
            margin-bottom: 30px;
        }
        
        .services-section .services-grid {
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }
        
        .services-section .service-card {
            padding: 20px 12px;
        }
        
        .services-section .service-icon {
            width: 56px;
            height: 56px;
            border-radius: 14px;
        }
        
        .services-section .service-card h3 {
            font-size: 14px;
        }
        
        .services-section .service-card p {
            font-size: 12px;
            display: none; /* Hide on very small screens */
        }
        
        /* Hide floating orbs on mobile for performance */
        .floating-orbs {
            display: none;
        }
    }
    
    /* Extra small mobile */
    @media (max-width: 480px) {
        .hero-content h1 {
            font-size: 1.5rem;
        }
        
        .dashboard-mockup {
            width: 240px;
            height: 150px;
        }
        
        .dept-cards-grid {
            grid-template-columns: 1fr;
            gap: 12px;
        }
        
        .dept-showcase-card {
            display: flex;
            flex-direction: row;
            align-items: center;
            text-align: left;
            padding: 16px;
            gap: 16px;
        }
        
        .dept-showcase-card .card-icon {
            margin: 0;
            flex-shrink: 0;
        }
        
        .dept-showcase-card .card-content {
            flex: 1;
        }
        
        .dept-showcase-card .card-stats {
            margin-top: 0;
            padding-top: 0;
            border-top: none;
            flex-direction: column;
            align-items: flex-end;
            gap: 4px;
        }
        
        .values-grid {
            grid-template-columns: 1fr;
        }
        
        .services-section .services-grid {
            grid-template-columns: 1fr;
        }
        
        .services-section .service-card p {
            display: block;
        }
    }

    /* Reduced motion: disable ambient animations */
    @media (prefers-reduced-motion: reduce) {

        .hero-section::before,
        .chart-bar::after,
        #btn_explore,
        .hero-container::before,
        .hero-container::after,
        .chart-pie::before,
        .user-avatar::after,
        .floating-orbs .orb,
        .chart-bar {
            animation: none !important;
        }
    }
    </style>
</head>

<body>

    <!-- HEADER -->
    <header>
        <h2 class="logo"><span class="brand-abbrev">LSPU</span><span class="brand-campus">San Pablo City</span></h2>
        <button class="nav-toggle" aria-label="Toggle Navigation" aria-controls="site_nav" aria-expanded="false">
            <span class="bar"></span>
            <span class="bar"></span>
            <span class="bar"></span>
        </button>
        <nav class="navigation" id="site_nav">
            <a href="#home">Home</a>
            <a href="#about">About</a>
            <a href="#service">Service</a>
            <a href="main_dashboard.php" style="background: linear-gradient(135deg, #0f172a, #334155); padding: 8px 16px; border-radius: 8px; color: white; font-weight: 600; box-shadow: 0 2px 8px rgba(0,0,0,0.2);">📊 Analytics</a>
            <button class="btnLogin-popup">Login</button>
        </nav>
    </header>



    <!-- LOGIN MODAL -->
    <div class="wrapper">
        <span class="icon-close">
            <ion-icon name="close"></ion-icon>
        </span>
        <div class="form-box login">
            <h2>Login</h2>
            <form id="loginForm" action="process_login.php" method="post">
                <div class="input-box">
                    <span class="icon">
                        <ion-icon name="mail"></ion-icon>
                    </span>
                    <input type="email" name="email" required placeholder=" " />
                    <label>Email</label>
                </div>
                <div class="input-box">
                    <span class="icon">
                        <ion-icon name="lock-closed"></ion-icon>
                    </span>
                    <input type="password" name="password" id="password" required placeholder=" " />
                    <label>Password</label>
                </div>
                <button type="submit" class="btn">Login</button>
            </form>
        </div>
    </div>

    <!-- Dark overlay for modal -->
    <div class="overlay"></div>
    <!-- MAIN CONTENT -->
    <main>
        <!-- Home Section -->
        <section id="home" class="hero-section">
            <!-- Floating Orbs for visual interest -->
            <div class="floating-orbs">
                <div class="orb orb-eng"></div>
                <div class="orb orb-cas"></div>
                <div class="orb orb-cbaa"></div>
                <div class="orb orb-ccje"></div>
                <div class="orb orb-cte"></div>
            </div>
            
            <div class="hero-container">
                <div class="hero-content">
                    <span class="hero-eyebrow">Laguna State Polytechnic University • San Pablo City Campus</span>
                    <h1>Board Performance Dashboard</h1>
                    <p>Track, analyze, and predict board exam performance across all departments.<br>
                        Empowering Students, Faculty, and the Community with data-driven insights.</p>
                    <button id="btn_explore" class="cta-button"><span>Explore Dashboard</span></button>
                </div>
                <div class="hero-illustration">
                    <div class="dashboard-mockup">
                        <div class="mockup-header"></div>
                        <div class="mockup-charts">
                            <div class="chart-bar" title="Engineering"></div>
                            <div class="chart-bar" title="CAS"></div>
                            <div class="chart-bar" title="CBAA"></div>
                            <div class="chart-bar" title="CCJE"></div>
                            <div class="chart-bar" title="CTE"></div>
                        </div>
                    </div>
                    <div class="user-avatar">
                        <div class="avatar-person"></div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Department Showcase Section -->
        <section class="departments-showcase" id="departments">
            <div class="showcase-title">
                <h2>Select a Department</h2>
                <p>Click on a department card to view statistics and predictions</p>
            </div>
            <div class="dept-cards-grid">
                <!-- Engineering -->
                <div class="dept-showcase-card card-eng" onclick="window.location.href='public_dashboard_engineering.php'">
                    <div class="card-icon">⚙️</div>
                    <span class="card-abbrev">COE</span>
                    <h3>Engineering</h3>
                </div>
                
                <!-- CAS -->
                <div class="dept-showcase-card card-cas" onclick="window.location.href='public_dashboard_cas.php'">
                    <div class="card-icon">🔬</div>
                    <span class="card-abbrev">CAS</span>
                    <h3>Arts & Sciences</h3>
                </div>
                
                <!-- CBAA -->
                <div class="dept-showcase-card card-cbaa" onclick="window.location.href='public_dashboard_cbaa.php'">
                    <div class="card-icon">💼</div>
                    <span class="card-abbrev">CBAA</span>
                    <h3>Business Administration</h3>
                </div>
                
                <!-- CCJE -->
                <div class="dept-showcase-card card-ccje" onclick="window.location.href='public_dashboard_ccje.php'">
                    <div class="card-icon">⚖️</div>
                    <span class="card-abbrev">CCJE</span>
                    <h3>Criminal Justice</h3>
                </div>
                
                <!-- CTE -->
                <div class="dept-showcase-card card-cte" onclick="window.location.href='public_dashboard_cte.php'">
                    <div class="card-icon">📚</div>
                    <span class="card-abbrev">CTE</span>
                    <h3>Teacher Education</h3>
                </div>
            </div>
        </section>

        <!-- About Section - Simplified -->
        <section id="about" class="about-section">
            <div class="about-container-simple">
                <div class="about-eyebrow">Laguna State Polytechnic University</div>
                <h2 class="about-title">About Us</h2>
                <p class="about-brief">We provide quality, efficient, and effective services through responsive instruction, distinctive research, and sustainable community engagement.</p>
                
                <div class="about-quick-info">
                    <div class="quick-info-item">
                        <span class="info-icon">📍</span>
                        <span>Del Remedio, San Pablo City, Laguna</span>
                    </div>
                    <div class="quick-info-item">
                        <span class="info-icon">📞</span>
                        <span>(049) 554-9910</span>
                    </div>
                    <div class="quick-info-item">
                        <span class="info-icon">✉️</span>
                        <span>info@lspu.edu.ph</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Services Section - Simplified -->
        <section id="service" class="services-section">
            <div class="services-container">
                <h2>Our Services</h2>
                <div class="services-grid-simple">
                    <div class="service-item">
                        <span class="service-emoji">📊</span>
                        <h4>View Statistics</h4>
                    </div>
                    <div class="service-item">
                        <span class="service-emoji">🔍</span>
                        <h4>Search Records</h4>
                    </div>
                    <div class="service-item">
                        <span class="service-emoji">📈</span>
                        <h4>Performance Reports</h4>
                    </div>
                    <div class="service-item">
                        <span class="service-emoji">🤖</span>
                        <h4>Predictions</h4>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- FOOTER -->
    <footer class="site-footer">
        <div class="footer-gradient-line"></div>
        <div class="footer-simple">
            <div class="footer-dept-dots">
                <span class="dot dot-eng" title="Engineering"></span>
                <span class="dot dot-cas" title="CAS"></span>
                <span class="dot dot-cbaa" title="CBAA"></span>
                <span class="dot dot-ccje" title="CCJE"></span>
                <span class="dot dot-cte" title="CTE"></span>
            </div>
            <p class="footer-text">© 2024 LSPU San Pablo City Campus • Board Performance Dashboard</p>
        </div>
    </footer>

    <style>
    /* Footer Styling - Simplified */
    .site-footer {
        background: #0f172a;
        color: #94a3b8;
        padding-top: 0;
    }
    
    .footer-gradient-line {
        height: 4px;
        background: linear-gradient(90deg, 
            var(--eng-primary), 
            var(--cas-primary), 
            var(--cbaa-primary), 
            var(--ccje-primary), 
            var(--cte-primary));
    }
    
    .footer-simple {
        padding: 24px 20px;
        text-align: center;
    }
    
    .footer-dept-dots {
        display: flex;
        justify-content: center;
        gap: 10px;
        margin-bottom: 12px;
    }
    
    .footer-dept-dots .dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        transition: transform 0.2s ease;
    }
    
    .footer-dept-dots .dot:hover {
        transform: scale(1.3);
    }
    
    .dot-eng { background: var(--eng-primary); }
    .dot-cas { background: var(--cas-primary); }
    .dot-cbaa { background: var(--cbaa-primary); }
    .dot-ccje { background: var(--ccje-primary); }
    .dot-cte { background: var(--cte-primary); }
    
    .footer-text {
        font-size: 13px;
        color: #64748b;
        margin: 0;
    }
    </style>

    <!-- SCRIPTS -->
    <div id="login-toast"
        style="display:none;position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);z-index:9999;background:#ff6f61;color:#fff;padding:20px 40px;border-radius:16px;box-shadow:0 4px 16px rgba(0,0,0,0.12);font-size:18px;opacity:0;transition:opacity 0.5s;display:flex;align-items:center;gap:16px;">
        <span
            style="font-size:28px;display:flex;align-items:center;justify-content:center;background:#fff2ee;color:#ff6f61;border-radius:50%;width:40px;height:40px;">
            <ion-icon name="sad-outline"></ion-icon>
        </span>
        <span><strong>Oops!</strong> Incorrect email or password. Please try again.</span>
    </div>
    <script src="script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    <script>
    // Show smooth toast and clear fields if login failed
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('error')) {
        const toast = document.getElementById('login-toast');
        toast.style.display = 'flex';
        setTimeout(() => {
            toast.style.opacity = 1;
        }, 100);
        setTimeout(() => {
            toast.style.opacity = 0;
            setTimeout(() => {
                toast.style.display = 'none';
            }, 500);
        }, 2500);
        document.querySelector('input[name="email"]').value = '';
        document.querySelector('input[name="password"]').value = '';

        // Clear the error parameter from URL to prevent showing toast on refresh
        const url = new URL(window.location);
        url.searchParams.delete('error');
        window.history.replaceState({}, document.title, url.pathname);
    }

    // Smooth fade-out on login submit
    document.getElementById('loginForm').addEventListener('submit', function(e) {
        // Only trigger fade-out if form is valid
        if (this.checkValidity()) {
            document.body.classList.add('fade-out');
        }
    });

    // Escape HTML helper used by Explore renderers (define in global scope for reuse)
    function escapeHtml(s) {
        return String(s).replace(/[&<>"']/g, function(c) {
            return ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                '\'': '&#39;'
            } [c]);
        });
    }

    // === Explore: client state & helpers ===
    const EXPLORE = {
        dept: null,
        page: 1,
        limit: 50,
        q: '',
        sort: 'board_exam_date',
        dir: 'desc',
        total: 0,
        results: ['Passed', 'Failed'],
        sexes: ['Male', 'Female']
    };

    // Persist Explore state in the URL (deep-linking without reload)
    function writeExploreToURL() {
        try {
            const url = new URL(window.location);
            if (EXPLORE.dept) url.searchParams.set('dept', EXPLORE.dept);
            else url.searchParams.delete('dept');
            if (EXPLORE.page && EXPLORE.page > 1) url.searchParams.set('page', String(EXPLORE.page));
            else url.searchParams.delete('page');
            if (EXPLORE.limit && EXPLORE.limit !== 50) url.searchParams.set('limit', String(EXPLORE.limit));
            else url.searchParams.delete('limit');
            if (EXPLORE.q) url.searchParams.set('q', EXPLORE.q);
            else url.searchParams.delete('q');
            if (EXPLORE.sort && EXPLORE.sort !== 'board_exam_date') url.searchParams.set('sort', EXPLORE.sort);
            else url.searchParams.delete('sort');
            if (EXPLORE.dir && EXPLORE.dir !== 'desc') url.searchParams.set('dir', EXPLORE.dir);
            else url.searchParams.delete('dir');
            if (EXPLORE.results && EXPLORE.results.length === 0) {
                url.searchParams.set('results', '__none__');
            } else if (EXPLORE.results && EXPLORE.results.length && EXPLORE.results.length < 2) {
                url.searchParams.set('results', EXPLORE.results.join(','));
            } else {
                url.searchParams.delete('results');
            }
            if (EXPLORE.sexes && EXPLORE.sexes.length === 0) {
                url.searchParams.set('sexes', '__none__');
            } else if (EXPLORE.sexes && EXPLORE.sexes.length && EXPLORE.sexes.length < 2) {
                url.searchParams.set('sexes', EXPLORE.sexes.join(','));
            } else {
                url.searchParams.delete('sexes');
            }
            window.history.replaceState({}, document.title, url.pathname + '?' + url.searchParams.toString());
        } catch (e) {
            /* no-op */
        }
    }

    function hydrateExploreFromURL() {
        const sp = new URLSearchParams(window.location.search);
        const dept = sp.get('dept');
        if (dept) EXPLORE.dept = dept;
        const page = parseInt(sp.get('page') || '1', 10);
        if (!isNaN(page) && page > 0) EXPLORE.page = page;
        const limit = parseInt(sp.get('limit') || '50', 10);
        if (!isNaN(limit) && limit > 0) EXPLORE.limit = limit;
        const q = sp.get('q');
        if (q) EXPLORE.q = q;
        const sort = sp.get('sort');
        if (sort) EXPLORE.sort = sort;
        const dir = sp.get('dir');
        if (dir === 'asc' || dir === 'desc') EXPLORE.dir = dir;
        const results = sp.get('results');
        if (results) {
            if (results === '__none__') EXPLORE.results = [];
            else EXPLORE.results = results.split(',').filter(Boolean);
        }
        const sexes = sp.get('sexes');
        if (sexes) {
            if (sexes === '__none__') EXPLORE.sexes = [];
            else EXPLORE.sexes = sexes.split(',').filter(Boolean);
        }
        // Reflect into controls if present
        const qEl = document.getElementById('explore_q');
        if (qEl && EXPLORE.q) qEl.value = EXPLORE.q;
        const limEl = document.getElementById('explore_limit');
        if (limEl) limEl.value = String(EXPLORE.limit);
        // Activate department button if provided
        if (EXPLORE.dept) {
            document.querySelectorAll('#explore_buttons .explore-btn').forEach(b => {
                if (b.getAttribute('data-dept') === EXPLORE.dept) b.classList.add('active');
                else b.classList.remove('active');
            });
        }
    }

    function loadExplore(dept) {
        if (dept) {
            EXPLORE.dept = dept;
            EXPLORE.page = 1;
        }
        const baseParams = {
            action: 'list_passers',
            dept: EXPLORE.dept,
            page: String(EXPLORE.page),
            limit: String(EXPLORE.limit),
            q: EXPLORE.q,
            sort: EXPLORE.sort,
            dir: EXPLORE.dir
        };
        if (EXPLORE.results) {
            baseParams.results = EXPLORE.results.length ? EXPLORE.results.join(',') : '__none__';
        }
        if (EXPLORE.sexes) {
            baseParams.sexes = EXPLORE.sexes.length ? EXPLORE.sexes.join(',') : '__none__';
        }
        const params = new URLSearchParams(baseParams);
        // Prepare fade-out on existing content
        const tb = document.querySelector('#explore_table tbody');
        const legEl = document.getElementById('explore_legends');
        if (tb) {
            tb.classList.remove('fade-in');
            tb.classList.add('fade-out');
        }
        if (legEl) {
            legEl.classList.remove('fade-in');
            legEl.classList.add('fade-out');
        }
        // Update URL with current Explore state
        writeExploreToURL();
        fetch('explore_public.php?' + params.toString()).then(r => r.json()).then(res => {
            if (!res || !res.success) {
                renderExploreTable([]);
                updateExploreInfo({});
                return;
            }
            EXPLORE.total = (res.meta && res.meta.total) || 0;
            renderExploreTable(res.data || []);
            renderExploreLegends(res.legends || {});
            updateExploreInfo(res.meta || {});
            // Apply fade-in after DOM update
            requestAnimationFrame(() => {
                if (tb) {
                    tb.classList.remove('fade-out');
                    tb.classList.add('fade-in');
                    tb.addEventListener('animationend', function h() {
                        tb.classList.remove('fade-in');
                        tb.removeEventListener('animationend', h);
                    });
                }
                if (legEl) {
                    legEl.classList.remove('fade-out');
                    legEl.classList.add('fade-in');
                    legEl.addEventListener('animationend', function h() {
                        legEl.classList.remove('fade-in');
                        legEl.removeEventListener('animationend', h);
                    });
                }
            });
        }).catch(console.error);
    }

    function renderExploreTable(rows) {
        const tb = document.querySelector('#explore_table tbody');
        if (!tb) return;
        if (!rows || !rows.length) {
            tb.innerHTML = `<tr>
          <td colspan="5" style="color:#64748b;padding:24px 12px;text-align:center;">
            No records found. Try clearing the search, changing rows, or selecting another department.
          </td>
        </tr>`;
            return;
        }
        tb.innerHTML = rows.map((r, i) => `
        <tr style="animation: fadeIn 220ms ease-out both; animation-delay:${i*35}ms;">
          <td>${escapeHtml(r.name||'')}</td>
          <td>${escapeHtml(r.board_exam_type||'')}</td>
          <td>${escapeHtml(r.board_exam_date||'')}</td>
          <td><span class="attempt-badge">${escapeHtml(r.exam_type||'')}</span></td>
          <td>${r.result==='Passed' ? '<span class="status-badge status-pass">Passed</span>' : (r.result ? '<span class="status-badge status-fail">'+escapeHtml(r.result)+'</span>' : '')}</td>
        </tr>`).join('');
    }

    function renderExploreLegends(leg) {
        const el = document.getElementById('explore_legends');
        if (!el) return;
        const res = leg.result || {};
        const sex = leg.sex || leg.gender || {}; // backend may use either
        // Build groups: Results and Sexes
        const resultOrder = ['Passed', 'Failed'];
        const sexOrder = ['Male', 'Female'];
        const mkChip = (type, key, idx) => {
            const isResult = (type === 'result');
            const count = isResult ? (res[key] || 0) : (sex[key] || 0);
            const active = isResult ? (!EXPLORE.results || EXPLORE.results.includes(key)) : (!EXPLORE.sexes ||
                EXPLORE.sexes.includes(key));
            const color = isResult ?
                (key === 'Passed' ? '#0ea5e9' : '#b91c1c') :
                (key === 'Male' ? '#0284c7' : '#ec4899');
            const extraStyle =
                `animation: fadeIn 200ms ease-out both; animation-delay:${idx*60}ms; color:${active?'#ffffff':color}`;
            const dataAttr = isResult ? `data-result=\"${key}\"` : `data-sex=\"${key}\"`;
            const label = `${escapeHtml(key)}${count?`: ${count}`:''}`;
            return `<span class=\"legend-item${active?'':' inactive'}\" role=\"button\" tabindex=\"0\" ${dataAttr} style=\"${extraStyle}\"><span class=\"swatch\"></span><span class=\"legend-text\">${label}</span></span>`;
        };
        const resultChips = resultOrder.map((k, i) => mkChip('result', k, i)).join('');
        const sexChips = sexOrder.map((k, i) => mkChip('sex', k, i + resultOrder.length)).join('');
        const showReset = ((EXPLORE.results && EXPLORE.results.length < resultOrder.length) || (EXPLORE.sexes && EXPLORE
            .sexes.length < sexOrder.length));
        const resetBtn = showReset ?
            `<span class=\"explore-reset\" id=\"explore_reset\" role=\"button\" tabindex=\"0\" style=\"animation: fadeIn 200ms ease-out both; animation-delay:${(resultOrder.length+sexOrder.length)*60}ms;\">Reset filters</span>` :
            '';
        el.innerHTML = resultChips + sexChips + (resetBtn ? ` ${resetBtn}` : '');
        // Bind toggles for result chips
        el.querySelectorAll('.legend-item[data-result]').forEach(chip => {
            const toggle = () => {
                const key = chip.getAttribute('data-result');
                const idx = (EXPLORE.results || []).indexOf(key);
                if (idx >= 0) {
                    EXPLORE.results.splice(idx, 1);
                    chip.classList.add('inactive');
                } else {
                    (EXPLORE.results = EXPLORE.results || []).push(key);
                    chip.classList.remove('inactive');
                }
                EXPLORE.page = 1;
                loadExplore();
            };
            chip.addEventListener('click', toggle);
            chip.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    toggle();
                }
            });
        });
        // Bind toggles for sex chips
        el.querySelectorAll('.legend-item[data-sex]').forEach(chip => {
            const toggle = () => {
                const key = chip.getAttribute('data-sex');
                const idx = (EXPLORE.sexes || []).indexOf(key);
                if (idx >= 0) {
                    EXPLORE.sexes.splice(idx, 1);
                    chip.classList.add('inactive');
                } else {
                    (EXPLORE.sexes = EXPLORE.sexes || []).push(key);
                    chip.classList.remove('inactive');
                }
                EXPLORE.page = 1;
                loadExplore();
            };
            chip.addEventListener('click', toggle);
            chip.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    toggle();
                }
            });
        });
        const rbtn = document.getElementById('explore_reset');
        if (rbtn) {
            const doReset = () => {
                EXPLORE.results = ['Passed', 'Failed'];
                EXPLORE.sexes = ['Male', 'Female'];
                EXPLORE.page = 1;
                loadExplore();
            };
            rbtn.addEventListener('click', doReset);
            rbtn.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    doReset();
                }
            });
        }
    }

    function updateExploreInfo(meta) {
        const info = document.getElementById('explore_info');
        if (!info) return;
        const start = EXPLORE.total ? ((EXPLORE.page - 1) * EXPLORE.limit + 1) : 0;
        const end = Math.min(EXPLORE.page * EXPLORE.limit, EXPLORE.total);
        info.textContent = `Showing ${start}-${end} of ${EXPLORE.total}`;
        document.getElementById('explore_prev').disabled = (EXPLORE.page <= 1);
        const pages = meta.pages || (EXPLORE.limit ? Math.ceil(EXPLORE.total / EXPLORE.limit) : 1);
        document.getElementById('explore_next').disabled = (EXPLORE.page >= pages);
    }

    function bindExploreUI() {
        const btns = document.querySelectorAll('#explore_buttons .explore-btn');
        btns.forEach(b => {
            b.addEventListener('click', () => {
                btns.forEach(x => x.classList.remove('active'));
                b.classList.add('active');
                loadExplore(b.getAttribute('data-dept'));
            });
        });
        const q = document.getElementById('explore_q');
        if (q) q.addEventListener('input', debounce(() => {
            EXPLORE.q = q.value.trim();
            EXPLORE.page = 1;
            loadExplore();
        }, 300));
        const lim = document.getElementById('explore_limit');
        if (lim) lim.addEventListener('change', () => {
            EXPLORE.limit = parseInt(lim.value, 10) || 50;
            EXPLORE.page = 1;
            loadExplore();
        });
        document.getElementById('explore_prev').addEventListener('click', () => {
            if (EXPLORE.page > 1) {
                EXPLORE.page--;
                loadExplore();
            }
        });
        document.getElementById('explore_next').addEventListener('click', () => {
            EXPLORE.page++;
            loadExplore();
        });
        // Sort
        document.querySelectorAll('#explore_table thead th[data-sort]').forEach(th => {
            th.addEventListener('click', () => {
                const s = th.getAttribute('data-sort');
                if (EXPLORE.sort === s) {
                    EXPLORE.dir = (EXPLORE.dir === 'asc' ? 'desc' : 'asc');
                } else {
                    EXPLORE.sort = s;
                    EXPLORE.dir = 'asc';
                }
                loadExplore();
            });
        });
        // CSV
        document.getElementById('explore_csv').addEventListener('click', () => exportExploreCSV());
        const xbtn = document.getElementById('explore_xlsx');
        if (xbtn) xbtn.addEventListener('click', () => exportExploreXLSX());
        // Remove optional date/exam-type filters if they exist in this build
        removeExploreFiltersIfPresent();
        // Default load first department on initial page view
        const first = document.querySelector('#explore_buttons .explore-btn');
        if (first && !EXPLORE.dept) {
            first.classList.add('active');
            loadExplore(first.getAttribute('data-dept'));
        }
    }

    function removeExploreFiltersIfPresent() {
        const toolbar = document.querySelector('.explore-toolbar');
        if (!toolbar) return;
        const ids = ['explore_from', 'explore_to', 'explore_exam_type', 'explore_examtype'];
        ids.forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                const lbl = toolbar.querySelector(`label[for="${id}"]`);
                if (lbl) lbl.remove();
                el.remove();
            }
        });
        // Remove plain text labels like "From:" / "To:" / "Exam Type:" wrapped in spans/labels
        const killTexts = ['From:', 'To:', 'Exam Type:'];
        Array.from(toolbar.querySelectorAll('span,label,div')).forEach(n => {
            const t = (n.textContent || '').trim();
            if (killTexts.includes(t)) n.remove();
        });
    }

    function exportExploreCSV() {
        const base = {
            action: 'list_passers',
            dept: EXPLORE.dept,
            page: '1',
            limit: String(Math.max(1000, EXPLORE.total || 1000)),
            q: EXPLORE.q,
            sort: EXPLORE.sort,
            dir: EXPLORE.dir
        };
        if (EXPLORE.results) {
            base.results = EXPLORE.results.length ? EXPLORE.results.join(',') : '__none__';
        }
        if (EXPLORE.sexes) {
            base.sexes = EXPLORE.sexes.length ? EXPLORE.sexes.join(',') : '__none__';
        }
        const params = new URLSearchParams(base);
        fetch('explore_public.php?' + params.toString()).then(r => r.json()).then(res => {
            if (!res || !res.success) return;
            const rows = res.data || [];
            const head = ['Full Name', 'Board Exam Type', 'Exam Date', 'Attempts', 'Result'];
            const lines = [head].concat(rows.map(r => [r.name || '', r.board_exam_type || '', r
                .board_exam_date || '', r.exam_type || '', r.result || ''
            ]));
            const csv = lines.map(x => x.map(v => '"' + String(v).replace(/"/g, '""') + '"').join(',')).join(
                '\n');
            // Add BOM for better Excel compatibility
            const blob = new Blob(["\uFEFF" + csv], {
                type: 'text/csv;charset=utf-8;'
            });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `${EXPLORE.dept.replace(/[^a-z0-9]+/ig,'_').toLowerCase()}_examinees.csv`;
            a.click();
            URL.revokeObjectURL(url);
        });
    }

    function exportExploreXLSX() {
        const base = {
            action: 'list_passers',
            dept: EXPLORE.dept,
            page: '1',
            limit: String(Math.max(1000, EXPLORE.total || 1000)),
            q: EXPLORE.q,
            sort: EXPLORE.sort,
            dir: EXPLORE.dir
        };
        if (EXPLORE.results) {
            base.results = EXPLORE.results.length ? EXPLORE.results.join(',') : '__none__';
        }
        if (EXPLORE.sexes) {
            base.sexes = EXPLORE.sexes.length ? EXPLORE.sexes.join(',') : '__none__';
        }
        const params = new URLSearchParams(base);
        fetch('explore_public.php?' + params.toString()).then(r => r.json()).then(res => {
            if (!res || !res.success) return;
            const rows = res.data || [];
            const header = ['Full Name', 'Board Exam Type', 'Exam Date', 'Attempts', 'Result'];
            const data = rows.map(r => [
                r.name || '',
                r.board_exam_type || '',
                r.board_exam_date ? String(r.board_exam_date) : '',
                r.exam_type || '',
                r.result || ''
            ]);
            const aoa = [header, ...data];
            const ws = XLSX.utils.aoa_to_sheet(aoa, {
                cellDates: false
            });
            // Set comfortable column widths (chars)
            ws['!cols'] = [{
                wch: 26
            }, {
                wch: 40
            }, {
                wch: 14
            }, {
                wch: 14
            }, {
                wch: 12
            }];
            const wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, (EXPLORE.dept || 'Data').substring(0, 31));
            XLSX.writeFile(wb,
                `${(EXPLORE.dept||'department').replace(/[^a-z0-9]+/ig,'_').toLowerCase()}_examinees.xlsx`, {
                    bookType: 'xlsx'
                });
        });
    }

    function debounce(fn, t) {
        let to = null;
        return function() {
            clearTimeout(to);
            to = setTimeout(() => fn.apply(this, arguments), t || 250);
        }
    }

    // === Public Visualization Boot ===
    (function initPublicViz() {
        // Highlight active nav based on hash
        function updateActiveNav() {
            const hash = window.location.hash || '#home';
            document.querySelectorAll('nav.navigation a').forEach(a => {
                const href = a.getAttribute('href') || '';
                if (href === hash) a.classList.add('active');
                else a.classList.remove('active');
            });
        }
        updateActiveNav();
        window.addEventListener('hashchange', updateActiveNav);

        // Mobile menu toggle
        const headerEl = document.querySelector('header');
        const toggleBtn = document.querySelector('.nav-toggle');

        function closeMenu() {
            if (headerEl.classList.contains('menu-open')) {
                headerEl.classList.remove('menu-open');
                toggleBtn && toggleBtn.setAttribute('aria-expanded', 'false');
            }
        }
        if (toggleBtn) {
            toggleBtn.addEventListener('click', () => {
                const open = headerEl.classList.toggle('menu-open');
                toggleBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
            });
            // Close on escape
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') closeMenu();
            });
            // Close on clicking a link or the Login button
            document.querySelectorAll('nav.navigation a, .btnLogin-popup').forEach(el => {
                el.addEventListener('click', closeMenu);
            });
            // Close when clicking outside panel (for small screens)
            document.addEventListener('click', (e) => {
                if (!headerEl.classList.contains('menu-open')) return;
                const nav = document.getElementById('site_nav');
                if (!nav) return;
                if (!nav.contains(e.target) && !toggleBtn.contains(e.target) && !headerEl.contains(e
                        .target)) closeMenu();
            });
        }

        // Hydrate Explore state from URL first, then bind UI
        hydrateExploreFromURL();
        bindExploreUI();
        // Enable copy buttons in About section
        bindCopyButtons();
        // Enable flip interactions for About cards
        bindFlipCards();
        // If URL already specified a department, trigger initial load
        if (EXPLORE.dept) {
            loadExplore();
        }
        const exploreBtn = document.getElementById('btn_explore');
        if (exploreBtn) {
            exploreBtn.addEventListener('click', () => {
                window.location.href = 'main_dashboard.php';
            });
        }

        // Make service cards jump to their target sections
        document.querySelectorAll('.services-grid .service-card[data-target]').forEach(card => {
            const tgt = card.getAttribute('data-target');
            const go = (e) => {
                e.preventDefault();
                const el = document.querySelector(tgt);
                if (el) {
                    el.scrollIntoView({
                        behavior: 'smooth'
                    });
                }
            };
            card.addEventListener('click', go);
            card.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    go(e);
                }
            });
        });

        // Initialize subtle parallax for hero (tiny vertical offset on scroll)
        initHeroParallax();

        const grid = document.getElementById('deptGrid');
        if (!grid) return;
        const years = [2019, 2020, 2021, 2022, 2023, 2024];
        const deptOrder = [{
                key: 'Engineering',
                theme: 'theme-green',
                label: 'College of Engineering'
            },
            {
                key: 'Arts and Science',
                theme: 'theme-pink',
                label: 'College of Arts and Science'
            },
            {
                key: 'Business Administration and Accountancy',
                theme: 'theme-yellow',
                label: 'College of Business Administration and Accountancy'
            },
            {
                key: 'Criminal Justice Education',
                theme: 'theme-red',
                label: 'College of Criminal Justice Education'
            },
            {
                key: 'Teacher Education',
                theme: 'theme-blue',
                label: 'College of Teacher Education'
            },
        ];

        // Colors for overview (one line per department) - Updated to match CSS variables
        const deptColors = {
            'Engineering': '#16a34a',           // Green
            'Arts and Science': '#BF3853',       // Rose/Pink (CAS)
            'Business Administration and Accountancy': '#d97706', // Golden (CBAA)
            'Criminal Justice Education': '#7f1d1d', // Maroon (CCJE)
            'Teacher Education': '#1d4ed8'       // Blue (CTE)
        };

        // Render placeholders
        deptOrder.forEach(dept => {
            const card = document.createElement('div');
            card.className = `dept-card ${dept.theme}`;
            card.setAttribute('data-dept-key', dept.key);
            card.id = `dept_${cssId(dept.key)}`;
            card.innerHTML = `
          <div class="dept-head">
            <div class="dept-left">
              <div class="dept-badge">${dept.label}</div>
              <div>
                <div class="dept-title">Overall Trends (2019–2024)</div>
                <div class="dept-desc">Passing rate and totals by exam type</div>
              </div>
            </div>
            <div class="dept-right" style="display:flex;align-items:center;gap:8px;">
              <button class="btn-outline-teal" id="btn_dept_pdf_${cssId(dept.key)}" title="Print Department PDF" style="padding:6px 10px;border-radius:10px;line-height:1;">⎙ Print PDF</button>
            </div>
          </div>
          <div class="line-accent"></div>
          <div class="dept-charts">
            <div class="chart-box">
              <h4>Passing Rate by Year</h4>
              <canvas id="line_${cssId(dept.key)}"></canvas>
            </div>
            <div class="chart-box">
              <h4>Total Number of Takes by Year</h4>
              <div class="ctl-row" id="ctl_takes_${cssId(dept.key)}" style="display:flex;align-items:center;gap:8px;justify-content:flex-end;margin:4px 0 6px 0;">
                <span class="kpi-chip" id="kpi_takes_${cssId(dept.key)}" title="All-time total takes for the selected years">Total Takes: —</span>
                <span class="kpi-chip" id="kpi_takes_avg_${cssId(dept.key)}" title="Average takes per year across the selected years">Avg/Year: —</span>
                <span style="flex:1"></span>
                <button class="btn-secondary" title="Download PNG" id="btn_takes_png_${cssId(dept.key)}" style="padding:4px 8px;border-radius:8px;line-height:1;">PNG</button>
                <button class="btn-secondary" title="Download CSV" id="btn_takes_csv_${cssId(dept.key)}" style="padding:4px 8px;border-radius:8px;line-height:1;">CSV</button>
              </div>
              <canvas id="takes_${cssId(dept.key)}"></canvas>
            </div>
            <div class="chart-box">
              <h4>Totals by Exam Type (per Year)</h4>
              <div class="ctl-row" id="ctl_stack_${cssId(dept.key)}" style="display:flex;align-items:center;gap:8px;justify-content:flex-end;margin:4px 0 6px 0;">
                <button class="btn-secondary" data-mode="percent" style="padding:4px 8px;border-radius:8px;line-height:1;">Percent</button>
                <button class="btn-secondary" data-mode="count" style="padding:4px 8px;border-radius:8px;line-height:1;">Counts</button>
                <span style="flex:1"></span>
                <button class="btn-secondary" title="Download PNG" id="btn_png_${cssId(dept.key)}" style="padding:4px 8px;border-radius:8px;line-height:1;">PNG</button>
                <button class="btn-secondary" title="Download CSV" id="btn_csv_${cssId(dept.key)}" style="padding:4px 8px;border-radius:8px;line-height:1;">CSV</button>
              </div>
              <canvas id="stack_${cssId(dept.key)}"></canvas>
              <div id="legend_${cssId(dept.key)}" class="legend"></div>
            </div>
            <div class="chart-box">
              <h4>Pass vs Fail by Year</h4>
              <div class="ctl-row" id="ctl_pf_${cssId(dept.key)}" style="display:flex;align-items:center;gap:8px;justify-content:flex-end;margin:4px 0 6px 0;">
                <button class="btn-secondary" data-mode="percent" style="padding:4px 8px;border-radius:8px;line-height:1;">Percent</button>
                <button class="btn-secondary" data-mode="count" style="padding:4px 8px;border-radius:8px;line-height:1;">Counts</button>
                <span style="flex:1"></span>
                <button class="btn-secondary" title="Download PNG" id="btn_pf_png_${cssId(dept.key)}" style="padding:4px 8px;border-radius:8px;line-height:1;">PNG</button>
                <button class="btn-secondary" title="Download CSV" id="btn_pf_csv_${cssId(dept.key)}" style="padding:4px 8px;border-radius:8px;line-height:1;">CSV</button>
              </div>
              <canvas id="pf_${cssId(dept.key)}"></canvas>
              <div id="pf_legend_${cssId(dept.key)}" class="legend"></div>
            </div>
            <div class="chart-box">
              <h4>Male vs Female by Year</h4>
              <div class="ctl-row" id="ctl_sex_${cssId(dept.key)}" style="display:flex;align-items:center;gap:8px;justify-content:flex-end;margin:4px 0 6px 0;">
                <button class="btn-secondary" data-mode="percent" style="padding:4px 8px;border-radius:8px;line-height:1;">Percent</button>
                <button class="btn-secondary" data-mode="count" style="padding:4px 8px;border-radius:8px;line-height:1;">Counts</button>
                <span style="flex:1"></span>
                <button class="btn-secondary" title="Download PNG" id="btn_sex_png_${cssId(dept.key)}" style="padding:4px 8px;border-radius:8px;line-height:1;">PNG</button>
                <button class="btn-secondary" title="Download CSV" id="btn_sex_csv_${cssId(dept.key)}" style="padding:4px 8px;border-radius:8px;line-height:1;">CSV</button>
              </div>
              <canvas id="sex_${cssId(dept.key)}"></canvas>
              <div id="sex_legend_${cssId(dept.key)}" class="legend"></div>
            </div>
            <div class="chart-box">
              <h4>First Time vs Repeater by Year</h4>
              <div class="ctl-row" id="ctl_att_${cssId(dept.key)}" style="display:flex;align-items:center;gap:8px;justify-content:flex-end;margin:4px 0 6px 0;">
                <button class="btn-secondary" data-mode="percent" style="padding:4px 8px;border-radius:8px;line-height:1;">Percent</button>
                <button class="btn-secondary" data-mode="count" style="padding:4px 8px;border-radius:8px;line-height:1;">Counts</button>
                <span style="flex:1"></span>
                <button class="btn-secondary" title="Download PNG" id="btn_att_png_${cssId(dept.key)}" style="padding:4px 8px;border-radius:8px;line-height:1;">PNG</button>
                <button class="btn-secondary" title="Download CSV" id="btn_att_csv_${cssId(dept.key)}" style="padding:4px 8px;border-radius:8px;line-height:1;">CSV</button>
              </div>
              <canvas id="att_${cssId(dept.key)}"></canvas>
              <div id="att_legend_${cssId(dept.key)}" class="legend"></div>
            </div>
            <div class="chart-box">
              <h4>Top 5 Exam Types (by Examinees)</h4>
              <div class="ctl-row" id="ctl_top_${cssId(dept.key)}" style="display:flex;align-items:center;gap:8px;justify-content:flex-end;margin:4px 0 6px 0;">
                <span style="flex:1"></span>
                <button class="btn-secondary" title="Download PNG" id="btn_top_png_${cssId(dept.key)}" style="padding:4px 8px;border-radius:8px;line-height:1;">PNG</button>
                <button class="btn-secondary" title="Download CSV" id="btn_top_csv_${cssId(dept.key)}" style="padding:4px 8px;border-radius:8px;line-height:1;">CSV</button>
              </div>
              <canvas id="top_${cssId(dept.key)}"></canvas>
              <div id="top_legend_${cssId(dept.key)}" class="legend"></div>
            </div>
          </div>
        `;
            grid.appendChild(card);
        });

        // Cache for overview lines: one per department
        const overviewLines = {};
        let overviewYears = null;
        // Cache stacked payload and mode per department
        const stackedCache = {};
        const stackedMode = {};
        const yearsCache = {};
        // Extra charts (client-side): cache rows and modes per department
        const extraRowsCache = {};
        const pfMode = {};
        const attMode = {};
        const sexMode = {};

        // Fetch and render in parallel per department
        deptOrder.forEach(dept => {
            const params = (o) => Object.entries(o).map(([k, v]) =>
                `${encodeURIComponent(k)}=${encodeURIComponent(v)}`).join('&');
            const base = 'stats_public.php';
            const pRate = fetch(
                `${base}?${params({action:'dept_passing_rate', department: dept.key, yearStart: years[0], yearEnd: years[years.length-1]})}`
            ).then(r => r.json());
            const pStack = fetch(
                `${base}?${params({action:'examtype_totals_by_year', department: dept.key, yearStart: years[0], yearEnd: years[years.length-1]})}`
            ).then(r => r.json());
            Promise.all([pRate, pStack]).then(([rate, stack]) => {
                const rateData = rate && rate.success ? rate.data : null;
                renderLine(dept, years, rateData);
                const stackData = stack && stack.success ? stack.data : null;
                renderStacked(dept, years, stackData, true);
                // cache for toggles/exports
                if (stackData) {
                    stackedCache[dept.key] = stackData;
                }
                if (years && years.length) {
                    yearsCache[dept.key] = years.slice();
                }
                stackedMode[dept.key] = stackedMode[dept.key] || 'count';
                wireStackControls(dept);

                // Store overview series values for this department
                const labels = years.map(y => String(y));
                const valuesByYear = (rateData && rateData.series && rateData.series[0]) ? (rateData
                    .series[0].values_by_year || {}) : {};
                overviewLines[dept.key] = {
                    values_by_year: valuesByYear
                };
                // Once all depts are in, render the combined overview line
                if (Object.keys(overviewLines).length === deptOrder.length) {
                    renderOverviewLine(years, overviewLines);
                }
                // Once totals are available for all departments, render overall takers
                if (Object.keys(stackedCache).length === deptOrder.length) {
                    renderOverallTakers(years);
                }
            }).catch(console.error);

            // Also render extra charts that aggregate raw rows from Explore
            fetchDeptRowsAndRender(dept, years);
        });

        function cssId(s) {
            return String(s).toLowerCase().replace(/[^a-z0-9]+/g, '_');
        }

        function grad(ctx, c1, c2) {
            const g = ctx.createLinearGradient(0, 0, 0, 160);
            g.addColorStop(0, c1);
            g.addColorStop(1, c2);
            return g;
        }

        function commonGrid(color) {
            return {
                responsive: true,
                maintainAspectRatio: true,
                scales: {
                    x: {
                        grid: {
                            color: 'rgba(148,163,184,0.15)'
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(148,163,184,0.15)'
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        enabled: true
                    }
                }
            };
        }

        const charts = {};
        let overallTakersCache = {
            years: [],
            values: []
        };

        function shortLabel(s, n) {
            s = String(s || '');
            return (s.length > (n || 30)) ? (s.slice(0, (n || 30) - 1) + '…') : s;
        }

        function setupLegend(containerId, items, onToggle) {
            const leg = document.getElementById(containerId);
            if (!leg) return;
            leg.innerHTML = items.map((it, idx) =>
                `<span class=\"legend-item\" role=\"button\" tabindex=\"0\" data-idx=\"${idx}\" style=\"color:${it.color}\"><span class=\"swatch\"></span><span class=\"legend-text\">${escapeHtml(it.label||'')}</span></span>`
            ).join('');
            const handler = (el, evt) => {
                const i = parseInt(el.getAttribute('data-idx') || '0');
                const active = onToggle(i, evt);
                if (active) el.classList.remove('inactive');
                else el.classList.add('inactive');
            };
            leg.querySelectorAll('.legend-item').forEach(el => {
                el.addEventListener('click', (e) => handler(el, e));
                el.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        handler(el, e);
                    }
                });
            });
        }

        function renderOverviewLine(years, lines) {
            const el = document.getElementById('dept_overall_line');
            if (!el) return;
            const ctx = el.getContext('2d');
            const labels = years.map(y => String(y));
            const datasets = deptOrder.map(d => ({
                label: d.label,
                data: labels.map(l => (lines[d.key] && lines[d.key].values_by_year ? (lines[d.key]
                    .values_by_year[l] || 0) : 0)),
                borderColor: deptColors[d.key] || '#0ea5e9',
                backgroundColor: (deptColors[d.key] || '#0ea5e9') + '22',
                borderWidth: 2.5,
                tension: 0.3,
                fill: false,
                pointRadius: 2,
                pointHoverRadius: 5,
                pointHitRadius: 14
            }));
            if (charts['dept_overall_line']) {
                charts['dept_overall_line'].destroy();
            }
            const chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels,
                    datasets
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    aspectRatio: 2.0,
                    animation: {
                        duration: 0
                    },
                    transitions: {
                        show: {
                            animations: {
                                x: {
                                    duration: 300
                                },
                                y: {
                                    duration: 300
                                }
                            }
                        },
                        hide: {
                            animations: {
                                x: {
                                    duration: 300
                                },
                                y: {
                                    duration: 300
                                }
                            }
                        },
                        active: {
                            animation: {
                                duration: 250,
                                easing: 'easeOutQuart'
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                color: 'rgba(148,163,184,0.15)'
                            }
                        },
                        y: {
                            beginAtZero: true,
                            suggestedMax: 100,
                            ticks: {
                                callback: (v) => v + '%'
                            },
                            grid: {
                                color: 'rgba(148,163,184,0.15)'
                            }
                        }
                    },
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            enabled: true,
                            animation: {
                                duration: 120
                            },
                            callbacks: {
                                label: (ctx) => `${ctx.dataset.label}: ${ctx.parsed.y}%`
                            }
                        }
                    }
                }
            });
            charts['dept_overall_line'] = chart;
            const leg = document.getElementById('dept_overall_legend');
            if (leg) {
                setupLegend('dept_overall_legend', deptOrder.map((d, i) => ({
                    label: d.label,
                    color: deptColors[d.key],
                    idx: i
                })), (idx) => {
                    const meta = chart.getDatasetMeta(idx);
                    const willHide = meta.hidden !== true; // currently visible -> will hide
                    meta.hidden = willHide ? true : null;
                    chart.update(willHide ? 'hide' : 'show');
                    return meta.hidden !== true;
                });
            }
            // Click on a point -> scroll to the department card
            el.onclick = function(evt) {
                const pts = chart.getElementsAtEventForMode(evt, 'nearest', {
                    intersect: true
                }, true);
                if (!pts || !pts.length) return;
                const ds = pts[0].datasetIndex;
                const d = deptOrder[ds];
                if (!d) return;
                const target = document.getElementById(`dept_${cssId(d.key)}`);
                if (target) target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            };
        }

        // Compute and render Overall Takers (sum of all departments across exam types per year)
        function renderOverallTakers(years) {
            const el = document.getElementById('overall_takers');
            if (!el) return;
            const labels = (years || []).map(y => String(y));
            // Sum per year across all departments using stackedCache
            const overall = {};
            labels.forEach(l => overall[l] = 0);
            Object.values(stackedCache).forEach(stack => {
                const series = (stack && stack.series) ? stack.series : [];
                series.forEach(s => {
                    labels.forEach(l => {
                        overall[l] += (s.totals_by_year && (s.totals_by_year[l] || 0)) || 0;
                    });
                });
            });
            const data = labels.map(l => overall[l] || 0);
            overallTakersCache = {
                years: labels.slice(),
                values: data.slice()
            };
            if (charts['overall_takers']) charts['overall_takers'].destroy();
            charts['overall_takers'] = new Chart(el.getContext('2d'), {
                type: 'bar',
                data: {
                    labels,
                    datasets: [{
                        label: 'Takers',
                        data,
                        backgroundColor: '#0ea5e9AA',
                        borderColor: '#0ea5e9',
                        borderWidth: 1,
                        borderRadius: 8,
                        barPercentage: 0.85,
                        categoryPercentage: 0.7,
                        maxBarThickness: 40
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    aspectRatio: 2.0,
                    scales: {
                        x: {
                            grid: {
                                color: 'rgba(148,163,184,0.15)'
                            }
                        },
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(148,163,184,0.15)'
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: (ctx) => `${ctx.parsed.y} takers`
                            }
                        }
                    },
                    animation: {
                        duration: 450,
                        easing: 'easeOutQuart'
                    }
                }
            });
        }

        function renderTakes(dept, years, rows) {
            const el = document.getElementById(`takes_${cssId(dept.key)}`);
            if (!el) return;
            const labels = years.map(y => String(y));
            const map = {};
            labels.forEach(y => map[y] = 0);
            rows.forEach(r => {
                const y = String(yearOf(r.board_exam_date));
                if (!map.hasOwnProperty(y)) return;
                map[y]++;
            });
            const data = labels.map(l => map[l] || 0);
            // Update KPI chip (sum of years in range)
            const total = data.reduce((a, b) => a + b, 0);
            const kpi = document.getElementById(`kpi_takes_${cssId(dept.key)}`);
            if (kpi) kpi.textContent = `Total Takes: ${Number(total||0).toLocaleString()}`;
            // Update Average per Year chip
            const avg = labels.length ? total / labels.length : 0;
            const kpiAvg = document.getElementById(`kpi_takes_avg_${cssId(dept.key)}`);
            if (kpiAvg) kpiAvg.textContent =
                `Avg/Year: ${avg.toLocaleString(undefined, { minimumFractionDigits: avg%1?1:0, maximumFractionDigits: 1 })}`;
            if (charts[el.id]) charts[el.id].destroy();
            charts[el.id] = new Chart(el.getContext('2d'), {
                type: 'bar',
                data: {
                    labels,
                    datasets: [{
                        label: 'Total Takes',
                        data,
                        backgroundColor: '#0ea5e9AA',
                        borderColor: '#0ea5e9',
                        borderWidth: 1,
                        borderRadius: 6,
                        barPercentage: 0.85,
                        categoryPercentage: 0.7,
                        maxBarThickness: 36
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    aspectRatio: 2.0,
                    scales: {
                        x: {
                            grid: {
                                color: 'rgba(148,163,184,0.15)'
                            }
                        },
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(148,163,184,0.15)'
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: (ctx) => `${ctx.parsed.y} takes`
                            }
                        }
                    },
                    animation: {
                        duration: 400,
                        easing: 'easeOutQuart'
                    }
                }
            });
        }

        function renderLine(dept, years, payload) {
            const el = document.getElementById(`line_${cssId(dept.key)}`);
            if (!el) return;
            const ctx = el.getContext('2d');
            const data = (payload && payload.series && payload.series[0]) ? payload.series[0].values_by_year : {};
            const labels = years.map(y => String(y));
            const values = labels.map(l => data[l] || 0);
            // Use brand teal for primary data color regardless of department accent
            const theme = '#0ea5e9';
            const cfg = {
                type: 'line',
                data: {
                    labels,
                    datasets: [{
                        label: 'Passing Rate (%)',
                        data: values,
                        borderColor: theme,
                        backgroundColor: 'rgba(14,165,233,0.12)',
                        tension: 0.3,
                        fill: true,
                        pointRadius: 3,
                        pointHoverRadius: 6,
                        pointHitRadius: 16
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    aspectRatio: 2.0,
                    animation: {
                        duration: 800,
                        easing: 'easeOutQuart',
                        delay: (ctx) => ctx && ctx.dataIndex !== undefined ? ctx.dataIndex * 60 : 0
                    },
                    transitions: {
                        show: {
                            animations: {
                                x: {
                                    duration: 300
                                },
                                y: {
                                    duration: 300
                                }
                            }
                        },
                        hide: {
                            animations: {
                                x: {
                                    duration: 300
                                },
                                y: {
                                    duration: 300
                                }
                            }
                        },
                        active: {
                            animation: {
                                duration: 250,
                                easing: 'easeOutQuart'
                            }
                        }
                    },
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    scales: {
                        x: {
                            grid: {
                                color: 'rgba(148,163,184,0.15)'
                            }
                        },
                        y: {
                            beginAtZero: true,
                            suggestedMax: 100,
                            ticks: {
                                callback: (v) => v + '%'
                            },
                            grid: {
                                color: 'rgba(148,163,184,0.15)'
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            enabled: true,
                            backgroundColor: '#ffffff',
                            titleColor: '#0f172a',
                            bodyColor: '#0f172a',
                            borderColor: '#0ea5e9',
                            borderWidth: 1,
                            displayColors: false,
                            padding: 10,
                            titleFont: {
                                weight: '700'
                            },
                            bodyFont: {
                                weight: '600'
                            },
                            callbacks: {
                                label: (ctx) => `Passing Rate: ${ctx.parsed.y}%`
                            }
                        }
                    }
                }
            };
            if (charts[el.id]) {
                charts[el.id].destroy();
            }
            charts[el.id] = new Chart(ctx, cfg);
        }

        function renderStacked(dept, years, payload, animate) {
            const el = document.getElementById(`stack_${cssId(dept.key)}`);
            if (!el) return;
            const ctx = el.getContext('2d');
            const labels = years.map(y => String(y));
            const series = (payload && payload.series) ? payload.series : [];
            // Brand palette (teal forward with navy complements)
            const brandPalette = ['#0ea5e9', '#2563eb', '#0891b2', '#38bdf8', '#0c4a6e', '#1d4ed8', '#14b8a6'];
            const main = '#0ea5e9';
            const acc = '#22d3ee';
            const mode = stackedMode[dept.key] || 'count';
            // Compute totals per year for percent mode
            const totalsByYear = {};
            if (mode === 'percent') {
                labels.forEach(l => {
                    totalsByYear[l] = 0;
                });
                series.forEach(s => {
                    labels.forEach(l => {
                        totalsByYear[l] += (s.totals_by_year && (s.totals_by_year[l] || 0)) || 0;
                    });
                });
            }
            // Build colored datasets cycling tints
            const ds = series.map((s, i) => {
                const base = brandPalette[i % brandPalette.length];
                const values = labels.map(l => (s.totals_by_year && (s.totals_by_year[l] || 0)));
                const dataVals = (mode === 'percent') ? values.map((v, idx) => {
                    const l = labels[idx];
                    const t = totalsByYear[l] || 0;
                    return t ? Math.round((v / t) * 100) : 0;
                }) : values;
                return {
                    label: s.label,
                    data: dataVals,
                    backgroundColor: base + 'CC', // with alpha
                    borderColor: base,
                    borderWidth: 1,
                    stack: 'totals',
                    borderRadius: 6,
                    barPercentage: 0.85,
                    categoryPercentage: 0.7,
                    maxBarThickness: 36
                };
            });
            const cfg = {
                type: 'bar',
                data: {
                    labels,
                    datasets: ds
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    aspectRatio: 2.0,
                    scales: {
                        x: {
                            stacked: true,
                            grid: {
                                color: 'rgba(148,163,184,0.15)'
                            }
                        },
                        y: {
                            stacked: true,
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(148,163,184,0.15)'
                            },
                            suggestedMax: (mode === 'percent') ? 100 : undefined,
                            ticks: (mode === 'percent') ? {
                                callback: (v) => v + '%'
                            } : {}
                        }
                    },
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            enabled: true,
                            backgroundColor: '#ffffff',
                            titleColor: '#0f172a',
                            bodyColor: '#0f172a',
                            borderColor: '#0ea5e9',
                            borderWidth: 1,
                            displayColors: true,
                            padding: 10,
                            bodyFont: {
                                size: 12,
                                weight: '600'
                            },
                            titleFont: {
                                size: 12,
                                weight: '700'
                            },
                            callbacks: {
                                title(items) {
                                    return (items && items[0]) ? String(items[0].label) : '';
                                },
                                label(ctx) {
                                    const lbl = ctx && ctx.dataset ? (ctx.dataset.label || '') : '';
                                    const v = (ctx && ctx.parsed && typeof ctx.parsed.y === 'number') ? ctx
                                        .parsed.y : (ctx.raw || 0);
                                    return shortLabel(lbl, 36) + ': ' + v + (mode === 'percent' ? '%' : '');
                                }
                            }
                        }
                    },
                    animation: {
                        duration: animate ? 500 : 0,
                        easing: 'easeOutQuart',
                        delay: (ctx) => (animate && ctx && ctx.dataIndex !== undefined) ? (ctx.dataIndex * 40) :
                            0
                    },
                    transitions: {
                        show: {
                            animations: {
                                x: {
                                    duration: 300
                                },
                                y: {
                                    duration: 300
                                }
                            }
                        },
                        hide: {
                            animations: {
                                x: {
                                    duration: 300
                                },
                                y: {
                                    duration: 300
                                }
                            }
                        },
                        active: {
                            animation: {
                                duration: 250,
                                easing: 'easeOutQuart'
                            }
                        }
                    }
                }
            };
            if (charts[el.id]) {
                charts[el.id].destroy();
            }
            charts[el.id] = new Chart(ctx, cfg);
            // External legend
            const legId = `legend_${cssId(dept.key)}`;
            const leg = document.getElementById(legId);
            if (leg) {
                // Legend click focuses the dataset instead of hiding; Ctrl+click toggles visibility
                setupLegend(legId, ds.map((d, i) => ({
                    label: d.label,
                    color: d.backgroundColor,
                    idx: i
                })), (idx, evt) => {
                    const ch = charts[el.id];
                    if (!ch) return true;
                    if (evt && (evt.ctrlKey || evt.metaKey)) {
                        const meta = ch.getDatasetMeta(idx);
                        const willHide = meta.hidden !== true;
                        meta.hidden = willHide ? true : null;
                        ch.update(willHide ? 'hide' : 'show');
                        return meta.hidden !== true;
                    }
                    ch.data.datasets.forEach((d, i) => {
                        const base = brandPalette[i % brandPalette.length];
                        const active = (i === idx);
                        d.backgroundColor = base + (active ? 'EE' : '66');
                        d.borderWidth = active ? 2 : 1;
                    });
                    ch.update();
                    return true; // keep legend item visually active
                });
            }
            // Click to highlight dataset across bars
            document.getElementById(`stack_${cssId(dept.key)}`).onclick = function(evt) {
                const ch = charts[el.id];
                if (!ch) return;
                const pts = ch.getElementsAtEventForMode(evt, 'nearest', {
                    intersect: true
                }, true);
                if (!pts || !pts.length) return;
                const dsIdx = pts[0].datasetIndex;
                ch.data.datasets.forEach((d, i) => {
                    const base = shade(main, (i * 0.12));
                    const active = (i === dsIdx);
                    d.backgroundColor = base + (active ? 'EE' : '66');
                    d.borderWidth = active ? 2 : 1;
                });
                ch.update();
            };
        }

        // Ensure we remember the years used by the overview for CSV export
        try {
            const _origRenderOverviewLine = renderOverviewLine;
            renderOverviewLine = function(years, lines) {
                overviewYears = (years && years.slice) ? years.slice() : years;
                return _origRenderOverviewLine(years, lines);
            }
        } catch (e) {
            /* noop: keep going if renderOverviewLine is const or missing */
        }

        function wireStackControls(dept) {
            const key = cssId(dept.key);
            const pctBtn = document.querySelector(`#ctl_stack_${key} button[data-mode="percent"]`);
            const cntBtn = document.querySelector(`#ctl_stack_${key} button[data-mode="count"]`);
            const setActive = () => {
                const isPct = (stackedMode[dept.key] === 'percent');
                if (pctBtn) pctBtn.style.background = isPct ? (getComputedStyle(document.getElementById(
                    `dept_${key}`)).getPropertyValue('--main').trim() || '#0ea5e9') : '';
                if (pctBtn) pctBtn.style.color = isPct ? '#fff' : '';
                if (cntBtn) cntBtn.style.background = !isPct ? (getComputedStyle(document.getElementById(
                    `dept_${key}`)).getPropertyValue('--main').trim() || '#0ea5e9') : '';
                if (cntBtn) cntBtn.style.color = !isPct ? '#fff' : '';
            };
            if (pctBtn) pctBtn.addEventListener('click', () => {
                stackedMode[dept.key] = 'percent';
                setActive();
                renderStacked(dept, yearsCache[dept.key] || [], stackedCache[dept.key], true);
            });
            if (cntBtn) cntBtn.addEventListener('click', () => {
                stackedMode[dept.key] = 'count';
                setActive();
                renderStacked(dept, yearsCache[dept.key] || [], stackedCache[dept.key], true);
            });
            setActive();
            // Exports
            const btnPng = document.getElementById(`btn_png_${key}`);
            const btnCsv = document.getElementById(`btn_csv_${key}`);
            if (btnPng) btnPng.addEventListener('click', () => downloadCanvasPNG(`stack_${key}`,
                `${key}_examtype_totals_${stackedMode[dept.key]}.png`));
            if (btnCsv) btnCsv.addEventListener('click', () => downloadStackCSV(dept, stackedCache[dept.key],
                stackedMode[dept.key] || 'count'));
            // Per-department line chart exports (if buttons present)
            const btnLinePng = document.getElementById(`btn_line_png_${key}`);
            const btnLineCsv = document.getElementById(`btn_line_csv_${key}`);
            if (btnLinePng) btnLinePng.addEventListener('click', () => downloadCanvasPNG(`line_${key}`,
                `${key}_passing_rate.png`));
            if (btnLineCsv) btnLineCsv.addEventListener('click', () => downloadLineCSV(dept));

            // Pass vs Fail controls
            const pfPct = document.querySelector(`#ctl_pf_${key} button[data-mode="percent"]`);
            const pfCnt = document.querySelector(`#ctl_pf_${key} button[data-mode="count"]`);
            const setPfActive = () => {
                const isPct = (pfMode[dept.key] === 'percent');
                if (pfPct) pfPct.style.background = isPct ? (getComputedStyle(document.getElementById(
                    `dept_${key}`)).getPropertyValue('--main').trim() || '#0ea5e9') : '';
                if (pfPct) pfPct.style.color = isPct ? '#fff' : '';
                if (pfCnt) pfCnt.style.background = !isPct ? (getComputedStyle(document.getElementById(
                    `dept_${key}`)).getPropertyValue('--main').trim() || '#0ea5e9') : '';
                if (pfCnt) pfCnt.style.color = !isPct ? '#fff' : '';
            };
            if (pfPct) pfPct.addEventListener('click', () => {
                pfMode[dept.key] = 'percent';
                setPfActive();
                renderPassFail(dept, yearsCache[dept.key] || [], extraRowsCache[dept.key] || []);
            });
            if (pfCnt) pfCnt.addEventListener('click', () => {
                pfMode[dept.key] = 'count';
                setPfActive();
                renderPassFail(dept, yearsCache[dept.key] || [], extraRowsCache[dept.key] || []);
            });
            setPfActive();
            const btnPfPng = document.getElementById(`btn_pf_png_${key}`);
            const btnPfCsv = document.getElementById(`btn_pf_csv_${key}`);
            if (btnPfPng) btnPfPng.addEventListener('click', () => downloadCanvasPNG(`pf_${key}`,
                `${key}_pass_fail_${pfMode[dept.key]||'count'}.png`));
            if (btnPfCsv) btnPfCsv.addEventListener('click', () => downloadPassFailCSV(dept));

            // Attempts controls
            const attPct = document.querySelector(`#ctl_att_${key} button[data-mode="percent"]`);
            const attCnt = document.querySelector(`#ctl_att_${key} button[data-mode="count"]`);
            const setAttActive = () => {
                const isPct = (attMode[dept.key] === 'percent');
                if (attPct) attPct.style.background = isPct ? (getComputedStyle(document.getElementById(
                    `dept_${key}`)).getPropertyValue('--main').trim() || '#0ea5e9') : '';
                if (attPct) attPct.style.color = isPct ? '#fff' : '';
                if (attCnt) attCnt.style.background = !isPct ? (getComputedStyle(document.getElementById(
                    `dept_${key}`)).getPropertyValue('--main').trim() || '#0ea5e9') : '';
                if (attCnt) attCnt.style.color = !isPct ? '#fff' : '';
            };
            if (attPct) attPct.addEventListener('click', () => {
                attMode[dept.key] = 'percent';
                setAttActive();
                renderAttempts(dept, yearsCache[dept.key] || [], extraRowsCache[dept.key] || []);
            });
            if (attCnt) attCnt.addEventListener('click', () => {
                attMode[dept.key] = 'count';
                setAttActive();
                renderAttempts(dept, yearsCache[dept.key] || [], extraRowsCache[dept.key] || []);
            });
            setAttActive();
            const btnAttPng = document.getElementById(`btn_att_png_${key}`);
            const btnAttCsv = document.getElementById(`btn_att_csv_${key}`);
            if (btnAttPng) btnAttPng.addEventListener('click', () => downloadCanvasPNG(`att_${key}`,
                `${key}_attempts_${attMode[dept.key]||'count'}.png`));
            if (btnAttCsv) btnAttCsv.addEventListener('click', () => downloadAttemptsCSV(dept));

            // Sex controls
            const sexPct = document.querySelector(`#ctl_sex_${key} button[data-mode="percent"]`);
            const sexCnt = document.querySelector(`#ctl_sex_${key} button[data-mode="count"]`);
            const setSexActive = () => {
                const isPct = (sexMode[dept.key] === 'percent');
                const color = (getComputedStyle(document.getElementById(`dept_${key}`)).getPropertyValue(
                    '--main').trim() || '#0ea5e9');
                if (sexPct) {
                    sexPct.style.background = isPct ? color : '';
                    sexPct.style.color = isPct ? '#fff' : '';
                }
                if (sexCnt) {
                    sexCnt.style.background = !isPct ? color : '';
                    sexCnt.style.color = !isPct ? '#fff' : '';
                }
            };
            if (sexPct) sexPct.addEventListener('click', () => {
                sexMode[dept.key] = 'percent';
                setSexActive();
                renderSex(dept, yearsCache[dept.key] || [], extraRowsCache[dept.key] || []);
            });
            if (sexCnt) sexCnt.addEventListener('click', () => {
                sexMode[dept.key] = 'count';
                setSexActive();
                renderSex(dept, yearsCache[dept.key] || [], extraRowsCache[dept.key] || []);
            });
            setSexActive();
            const btnSexPng = document.getElementById(`btn_sex_png_${key}`);
            const btnSexCsv = document.getElementById(`btn_sex_csv_${key}`);
            if (btnSexPng) btnSexPng.addEventListener('click', () => downloadCanvasPNG(`sex_${key}`,
                `${key}_sex_${sexMode[dept.key]||'count'}.png`));
            if (btnSexCsv) btnSexCsv.addEventListener('click', () => downloadSexCSV(dept));

            // Takes exports
            const btnTakesPng = document.getElementById(`btn_takes_png_${key}`);
            const btnTakesCsv = document.getElementById(`btn_takes_csv_${key}`);
            if (btnTakesPng) btnTakesPng.addEventListener('click', () => downloadCanvasPNG(`takes_${key}`,
                `${key}_total_takes.png`));
            if (btnTakesCsv) btnTakesCsv.addEventListener('click', () => downloadTakesCSV(dept));

            // Department PDF (multi-page, branded)
            const btnDeptPdf = document.getElementById(`btn_dept_pdf_${key}`);
            if (btnDeptPdf) btnDeptPdf.addEventListener('click', () => printDepartmentToPDF(dept));
        }

        function downloadCanvasPNG(canvasId, filename) {
            const el = document.getElementById(canvasId);
            if (!el) return;
            const url = el.toDataURL('image/png');
            const a = document.createElement('a');
            a.href = url;
            a.download = filename || 'chart.png';
            a.click();
        }

        function downloadStackCSV(dept, payload, mode) {
            if (!payload || !payload.series) return;
            const yrs = yearsCache[dept.key] || (payload.series.length ? Object.keys(payload.series[0]
                .totals_by_year || {}) : []);
            const labels = yrs.map(y => String(y));
            const header = ['Year'].concat(payload.series.map(s => s.label));
            // For percent mode compute totals per year
            const totals = {};
            labels.forEach(l => totals[l] = 0);
            if (mode === 'percent') {
                payload.series.forEach(s => labels.forEach(l => {
                    totals[l] += (s.totals_by_year && (s.totals_by_year[l] || 0)) || 0;
                }));
            }
            const rows = [header];
            labels.forEach(l => {
                const row = [l];
                payload.series.forEach(s => {
                    const v = (s.totals_by_year && (s.totals_by_year[l] || 0)) || 0;
                    row.push(mode === 'percent' ? ((totals[l] ? Math.round((v / totals[l]) * 100) :
                        0) + '%') : v);
                });
                rows.push(row);
            });
            const csv = rows.map(r => r.map(x => '"' + String(x).replace(/"/g, '""') + '"').join(',')).join('\n');
            const blob = new Blob([csv], {
                type: 'text/csv;charset=utf-8;'
            });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `${cssId(dept.key)}_examtype_${mode}.csv`;
            a.click();
            URL.revokeObjectURL(url);
        }

        function downloadPassFailCSV(dept) {
            const rows = extraRowsCache[dept.key] || [];
            if (!rows.length) return;
            const years = yearsCache[dept.key] || [];
            const labels = years.map(y => String(y));
            const map = {};
            labels.forEach(y => map[y] = {
                Passed: 0,
                Failed: 0
            });
            rows.forEach(r => {
                const y = String(yearOf(r.board_exam_date));
                if (!map[y]) return;
                const res = (r.result || '').trim();
                if (res === 'Passed') map[y].Passed++;
                else if (res === 'Failed') map[y].Failed++;
            });
            const header = ['Year', 'Passed', 'Failed'];
            const mode = pfMode[dept.key] || 'count';
            const out = [header];
            labels.forEach(l => {
                const tot = map[l].Passed + map[l].Failed;
                const p = (mode === 'percent' && tot) ? Math.round((map[l].Passed / tot) * 100) + '%' : map[
                    l].Passed;
                const f = (mode === 'percent' && tot) ? Math.round((map[l].Failed / tot) * 100) + '%' : map[
                    l].Failed;
                out.push([l, p, f]);
            });
            const csv = out.map(r => r.map(x => '"' + String(x).replace(/"/g, '""') + '"').join(',')).join('\n');
            const blob = new Blob([csv], {
                type: 'text/csv;charset=utf-8;'
            });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `${cssId(dept.key)}_pass_fail_${mode}.csv`;
            a.click();
            URL.revokeObjectURL(url);
        }

        function downloadAttemptsCSV(dept) {
            const rows = extraRowsCache[dept.key] || [];
            if (!rows.length) return;
            const years = yearsCache[dept.key] || [];
            const labels = years.map(y => String(y));
            const map = {};
            labels.forEach(y => map[y] = {
                First: 0,
                Repeat: 0
            });
            rows.forEach(r => {
                const y = String(yearOf(r.board_exam_date));
                if (!map[y]) return;
                const t = (r.exam_type || '').toLowerCase();
                if (t.includes('first')) map[y].First++;
                else if (t.includes('repeat')) map[y].Repeat++;
            });
            const header = ['Year', 'First Time', 'Repeater'];
            const mode = attMode[dept.key] || 'count';
            const out = [header];
            labels.forEach(l => {
                const tot = map[l].First + map[l].Repeat;
                const ft = (mode === 'percent' && tot) ? Math.round((map[l].First / tot) * 100) + '%' : map[
                    l].First;
                const rp = (mode === 'percent' && tot) ? Math.round((map[l].Repeat / tot) * 100) + '%' :
                    map[l].Repeat;
                out.push([l, ft, rp]);
            });
            const csv = out.map(r => r.map(x => '"' + String(x).replace(/"/g, '""') + '"').join(',')).join('\n');
            const blob = new Blob([csv], {
                type: 'text/csv;charset=utf-8;'
            });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `${cssId(dept.key)}_attempts_${mode}.csv`;
            a.click();
            URL.revokeObjectURL(url);
        }

        function downloadSexCSV(dept) {
            const rows = extraRowsCache[dept.key] || [];
            if (!rows.length) return;
            const years = yearsCache[dept.key] || [];
            const labels = years.map(y => String(y));
            const map = {};
            labels.forEach(y => map[y] = {
                Male: 0,
                Female: 0
            });
            rows.forEach(r => {
                const y = String(yearOf(r.board_exam_date));
                if (!map[y]) return;
                const sx = getSexValue(r);
                if (!sx) return;
                map[y][sx]++;
            });
            const header = ['Year', 'Male', 'Female'];
            const mode = sexMode[dept.key] || 'count';
            const out = [header];
            labels.forEach(l => {
                const tot = map[l].Male + map[l].Female;
                const m = (mode === 'percent' && tot) ? Math.round((map[l].Male / tot) * 100) + '%' : map[l]
                    .Male;
                const f = (mode === 'percent' && tot) ? Math.round((map[l].Female / tot) * 100) + '%' : map[
                    l].Female;
                out.push([l, m, f]);
            });
            const csv = out.map(r => r.map(x => '"' + String(x).replace(/"/g, '""') + '"').join(',')).join('\n');
            const blob = new Blob([csv], {
                type: 'text/csv;charset=utf-8;'
            });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `${cssId(dept.key)}_sex_${mode}.csv`;
            a.click();
            URL.revokeObjectURL(url);
        }

        function downloadTopExamCSV(dept) {
            const rows = extraRowsCache[dept.key] || [];
            if (!rows.length) return;
            const counts = {};
            rows.forEach(r => {
                const k = (r.board_exam_type || 'Unknown').trim();
                counts[k] = (counts[k] || 0) + 1;
            });
            const items = Object.entries(counts).sort((a, b) => b[1] - a[1]).slice(0, 5);
            const out = [
                ['Exam Type', 'Examinees']
            ].concat(items.map(([k, v]) => [k, v]));
            const csv = out.map(r => r.map(x => '"' + String(x).replace(/"/g, '""') + '"').join(',')).join('\n');
            const blob = new Blob([csv], {
                type: 'text/csv;charset=utf-8;'
            });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `${cssId(dept.key)}_top_exam_types.csv`;
            a.click();
            URL.revokeObjectURL(url);
        }

        function downloadTakesCSV(dept) {
            const rows = extraRowsCache[dept.key] || [];
            if (!rows.length) return;
            const years = yearsCache[dept.key] || [];
            const labels = years.map(y => String(y));
            const map = {};
            labels.forEach(y => map[y] = 0);
            rows.forEach(r => {
                const y = String(yearOf(r.board_exam_date));
                if (!map.hasOwnProperty(y)) return;
                map[y]++;
            });
            const out = [
                ['Year', 'Total Takes']
            ];
            labels.forEach(l => out.push([l, map[l] || 0]));
            const csv = out.map(r => r.map(x => '"' + String(x).replace(/"/g, '""') + '"').join(',')).join('\n');
            const blob = new Blob([csv], {
                type: 'text/csv;charset=utf-8;'
            });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `${cssId(dept.key)}_total_takes.csv`;
            a.click();
            URL.revokeObjectURL(url);
        }

        // === Extra charts (client-side aggregates from Explore rows) ===
        function yearOf(v) {
            if (v == null) return null;
            const s = String(v);
            const m = s.match(/\d{4}/);
            return m ? parseInt(m[0], 10) : null;
        }

        function fetchDeptRowsAndRender(dept, years) {
            try {
                const base = {
                    action: 'list_passers',
                    dept: dept.key,
                    page: '1',
                    limit: '10000',
                    sort: 'board_exam_date',
                    dir: 'asc'
                };
                const params = new URLSearchParams(base);
                fetch('explore_public.php?' + params.toString()).then(r => r.json()).then(res => {
                    const rows = (res && res.success && Array.isArray(res.data)) ? res.data : [];
                    extraRowsCache[dept.key] = rows;
                    renderTakes(dept, years, rows);
                    renderPassFail(dept, years, rows);
                    renderAttempts(dept, years, rows);
                    renderSex(dept, years, rows);
                    renderTopExamTypes(dept, rows);
                }).catch(() => {
                    renderTakes(dept, years, []);
                    renderPassFail(dept, years, []);
                    renderAttempts(dept, years, []);
                    renderTopExamTypes(dept, []);
                });
            } catch (e) {
                /* ignore */
            }
        }

        function renderPassFail(dept, years, rows) {
            const el = document.getElementById(`pf_${cssId(dept.key)}`);
            if (!el) return;
            const labels = years.map(y => String(y));
            const map = {};
            labels.forEach(y => map[y] = {
                Passed: 0,
                Failed: 0
            });
            rows.forEach(r => {
                const y = String(yearOf(r.board_exam_date));
                if (!map[y]) return;
                const res = (r.result || '').trim();
                if (res === 'Passed') map[y].Passed++;
                else if (res === 'Failed') map[y].Failed++;
            });
            // Toggle percent vs counts
            const mode = pfMode[dept.key] || 'count';
            const totals = labels.reduce((acc, l) => {
                acc[l] = (map[l].Passed + map[l].Failed);
                return acc;
            }, {});
            const toVals = (arrKey) => labels.map(y => (mode === 'percent') ? (totals[y] ? Math.round((map[y][
                arrKey
            ] / totals[y]) * 100) : 0) : map[y][arrKey]);
            const ds = [{
                    label: 'Passed',
                    data: toVals('Passed'),
                    backgroundColor: '#0ea5e966'
                },
                {
                    label: 'Failed',
                    data: toVals('Failed'),
                    backgroundColor: '#b91c1c66'
                }
            ];
            if (charts[el.id]) charts[el.id].destroy();
            charts[el.id] = new Chart(el.getContext('2d'), {
                type: 'bar',
                data: {
                    labels,
                    datasets: ds
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    scales: {
                        x: {
                            stacked: true
                        },
                        y: {
                            stacked: true,
                            beginAtZero: true,
                            suggestedMax: (mode === 'percent') ? 100 : undefined,
                            ticks: (mode === 'percent') ? {
                                callback: (v) => v + '%'
                            } : {}
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: (ctx) =>
                                    `${ctx.dataset.label}: ${ctx.parsed.y}${mode==='percent'?'%':''}`
                            }
                        }
                    }
                }
            });
            // External legend for clickable filter (toggle show/hide per series)
            const legId = `pf_legend_${cssId(dept.key)}`;
            const leg = document.getElementById(legId);
            if (leg) {
                setupLegend(legId, ds.map((d, i) => ({
                    label: d.label,
                    color: d.backgroundColor,
                    idx: i
                })), (idx) => {
                    const ch = charts[el.id];
                    if (!ch) return true;
                    const meta = ch.getDatasetMeta(idx);
                    const willHide = meta.hidden !== true; // if visible -> hide
                    meta.hidden = willHide ? true : null;
                    ch.update(willHide ? 'hide' : 'show');
                    return meta.hidden !== true; // active = visible
                });
            }
        }

        function renderAttempts(dept, years, rows) {
            const el = document.getElementById(`att_${cssId(dept.key)}`);
            if (!el) return;
            const labels = years.map(y => String(y));
            const map = {};
            labels.forEach(y => map[y] = {
                First: 0,
                Repeat: 0
            });
            rows.forEach(r => {
                const y = String(yearOf(r.board_exam_date));
                if (!map[y]) return;
                const t = (r.exam_type || '').toLowerCase();
                if (t.includes('first')) map[y].First++;
                else if (t.includes('repeat')) map[y].Repeat++;
            });
            const mode = attMode[dept.key] || 'count';
            const totals = labels.reduce((acc, l) => {
                acc[l] = (map[l].First + map[l].Repeat);
                return acc;
            }, {});
            const toVals = (k) => labels.map(y => (mode === 'percent') ? (totals[y] ? Math.round((map[y][k] /
                totals[y]) * 100) : 0) : map[y][k]);
            const ds = [{
                    label: 'First Time',
                    data: toVals('First'),
                    backgroundColor: '#14b8a666'
                },
                {
                    label: 'Repeater',
                    data: toVals('Repeat'),
                    backgroundColor: '#0369a166'
                }
            ];
            if (charts[el.id]) charts[el.id].destroy();
            charts[el.id] = new Chart(el.getContext('2d'), {
                type: 'bar',
                data: {
                    labels,
                    datasets: ds
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    scales: {
                        x: {
                            stacked: true
                        },
                        y: {
                            stacked: true,
                            beginAtZero: true,
                            suggestedMax: (mode === 'percent') ? 100 : undefined,
                            ticks: (mode === 'percent') ? {
                                callback: (v) => v + '%'
                            } : {}
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: (ctx) =>
                                    `${ctx.dataset.label}: ${ctx.parsed.y}${mode==='percent'?'%':''}`
                            }
                        }
                    }
                }
            });
            // External legend (centered) with click-to-toggle per series
            const legId = `att_legend_${cssId(dept.key)}`;
            const leg = document.getElementById(legId);
            if (leg) {
                setupLegend(legId, ds.map((d, i) => ({
                    label: d.label,
                    color: d.backgroundColor,
                    idx: i
                })), (idx) => {
                    const ch = charts[el.id];
                    if (!ch) return true;
                    const meta = ch.getDatasetMeta(idx);
                    const willHide = meta.hidden !== true; // if visible -> hide
                    meta.hidden = willHide ? true : null;
                    ch.update(willHide ? 'hide' : 'show');
                    return meta.hidden !== true; // active = visible
                });
            }
        }

        // Read sex from row; handle common keys and values
        function getSexValue(r) {
            const raw = (r && (r.gender ?? r.sex ?? r.Sex ?? r.Gender)) || '';
            const v = String(raw).trim().toLowerCase();
            if (!v) return null;
            if (v.startsWith('m')) return 'Male';
            if (v.startsWith('f')) return 'Female';
            return null;
        }

        function renderSex(dept, years, rows) {
            const el = document.getElementById(`sex_${cssId(dept.key)}`);
            if (!el) return;
            const labels = years.map(y => String(y));
            const map = {};
            labels.forEach(y => map[y] = {
                Male: 0,
                Female: 0
            });
            rows.forEach(r => {
                const y = String(yearOf(r.board_exam_date));
                if (!map[y]) return;
                const sx = getSexValue(r);
                if (!sx) return;
                map[y][sx]++;
            });
            const mode = sexMode[dept.key] || 'count';
            const totals = labels.reduce((acc, l) => {
                acc[l] = (map[l].Male + map[l].Female);
                return acc;
            }, {});
            const vals = (k) => labels.map(y => (mode === 'percent') ? (totals[y] ? Math.round((map[y][k] / totals[
                y]) * 100) : 0) : map[y][k]);
            const ds = [{
                    label: 'Male',
                    data: vals('Male'),
                    backgroundColor: '#0ea5e966'
                },
                {
                    label: 'Female',
                    data: vals('Female'),
                    backgroundColor: '#ec489966'
                }
            ];
            if (charts[el.id]) charts[el.id].destroy();
            charts[el.id] = new Chart(el.getContext('2d'), {
                type: 'bar',
                data: {
                    labels,
                    datasets: ds
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    scales: {
                        x: {
                            stacked: true
                        },
                        y: {
                            stacked: true,
                            beginAtZero: true,
                            suggestedMax: (mode === 'percent') ? 100 : undefined,
                            ticks: (mode === 'percent') ? {
                                callback: (v) => v + '%'
                            } : {}
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: (ctx) =>
                                    `${ctx.dataset.label}: ${ctx.parsed.y}${mode==='percent'?'%':''}`
                            }
                        }
                    }
                }
            });
            const legId = `sex_legend_${cssId(dept.key)}`;
            const leg = document.getElementById(legId);
            if (leg) {
                // Use Chart.js dataset-visibility API to avoid any accidental cross-hiding
                setupLegend(legId, ds.map((d, i) => ({
                    label: d.label,
                    color: d.backgroundColor,
                    idx: i
                })), (idx) => {
                    const ch = charts[el.id];
                    if (!ch) return true;
                    const visible = ch.isDatasetVisible ? ch.isDatasetVisible(idx) : (ch.getDatasetMeta(idx)
                        ?.hidden !== true);
                    if (ch.setDatasetVisibility) {
                        ch.setDatasetVisibility(idx, !visible);
                    } else {
                        // Fallback for older Chart.js: toggle meta.hidden
                        const meta = ch.getDatasetMeta(idx);
                        const willHide = meta.hidden !== true;
                        meta.hidden = willHide ? true : null;
                    }
                    ch.update();
                    return ch.isDatasetVisible ? ch.isDatasetVisible(idx) : (ch.getDatasetMeta(idx)
                        ?.hidden !== true);
                });
            }
        }

        function renderTopExamTypes(dept, rows) {
            const el = document.getElementById(`top_${cssId(dept.key)}`);
            if (!el) return;
            const counts = {};
            rows.forEach(r => {
                const k = (r.board_exam_type || 'Unknown').trim();
                counts[k] = (counts[k] || 0) + 1;
            });
            const items = Object.entries(counts).sort((a, b) => b[1] - a[1]).slice(0, 5);
            const labels = items.map(i => i[0]);
            const data = items.map(i => i[1]);
            const short = labels.map(l => shortLabel(l, 36));
            if (charts[el.id]) charts[el.id].destroy();
            const ctx = el.getContext('2d');
            const topPalette = ['#0ea5e9', '#14b8a6', '#38bdf8', '#0284c7', '#22d3ee'];
            const makeGrad = (chart) => {
                const {
                    chartArea
                } = chart;
                if (!chartArea) return '#0ea5e966';
                const g = ctx.createLinearGradient(chartArea.left, 0, chartArea.right, 0);
                g.addColorStop(0, 'rgba(14,165,233,0.65)');
                g.addColorStop(1, 'rgba(56,189,248,0.65)');
                return g;
            };
            const hoverFill = 'rgba(14,165,233,0.85)';
            const topLabelPlugin = {
                id: 'topBarLabels',
                afterDatasetsDraw(chart) {
                    const {
                        ctx,
                        data,
                        chartArea
                    } = chart;
                    if (!chartArea) return;
                    ctx.save();
                    ctx.font = 'bold 12px Poppins, Arial, sans-serif';
                    const meta = chart.getDatasetMeta(0);
                    meta.data.forEach((el, i) => {
                        const val = data.datasets[0].data[i];
                        const text = `${val}`; // keep simple; "Examinees" implied by title and tooltip
                        const x = el.x + 8;
                        const y = el.y + 4; // slight vertical centering tweak
                        // If bar is long enough, draw inside with white; else outside with dark text
                        const barStart = chart.scales.x.getPixelForValue(0);
                        const width = Math.max(0, el.x - barStart);
                        const tw = ctx.measureText(text).width + 6;
                        if (width > tw + 8) {
                            ctx.fillStyle = '#ffffff';
                            ctx.fillText(text, el.x - tw, el.y + 4);
                        } else {
                            ctx.fillStyle = '#0f172a';
                            ctx.fillText(text, x, y);
                        }
                    });
                    ctx.restore();
                }
            };
            // Use short codes 1..N on axis; show full names in external legend
            const codes = short.map((_, i) => String(i + 1));
            charts[el.id] = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: codes,
                    datasets: [{
                        label: 'Examinees',
                        data,
                        backgroundColor: codes.map((_, i) => (topPalette[i % topPalette.length] +
                            '66')),
                        hoverBackgroundColor: codes.map((_, i) => (topPalette[i % topPalette
                            .length] + 'AA')),
                        borderRadius: 6,
                        barPercentage: 0.8,
                        categoryPercentage: 0.7
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: true,
                    layout: {
                        padding: {
                            left: 8,
                            right: 12,
                            top: 0,
                            bottom: 0
                        }
                    },
                    animation: {
                        duration: 400
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: '#0f172a',
                            titleColor: '#ffffff',
                            bodyColor: '#ffffff',
                            displayColors: false,
                            callbacks: {
                                title(items) {
                                    const i = (items && items[0]) ? items[0].dataIndex : 0;
                                    return `${i+1}. ${labels[i] || ''}`;
                                },
                                label: (ctx) => `${ctx.parsed.x} examinees`
                            }
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(148,163,184,0.15)'
                            }
                        },
                        y: {
                            offset: true,
                            grid: {
                                display: false
                            },
                            ticks: {
                                autoSkip: false,
                                padding: 6,
                                font: {
                                    size: 12
                                }
                            }
                        }
                    }
                },
                plugins: [topLabelPlugin]
            });
            // External legend mapping color -> full exam name; click to highlight a bar
            const legId = `top_legend_${cssId(dept.key)}`;
            const leg = document.getElementById(legId);
            if (leg) {
                const itemsData = labels.map((full, i) => ({
                    label: full,
                    color: topPalette[i % topPalette.length],
                    idx: i
                }));
                setupLegend(legId, itemsData, (idx) => {
                    const ch = charts[el.id];
                    if (!ch) return true;
                    const base = topPalette;
                    const arr = codes.map((_, i) => base[i % base.length] + (i === idx ? 'EE' : '66'));
                    ch.data.datasets[0].backgroundColor = arr;
                    ch.update();
                    return true; // keep legend visual active
                });
            }
            // Wire exports if buttons present
            const key = cssId(dept.key);
            const btnTopPng = document.getElementById(`btn_top_png_${key}`);
            const btnTopCsv = document.getElementById(`btn_top_csv_${key}`);
            if (btnTopPng) btnTopPng.onclick = () => downloadCanvasPNG(`top_${key}`, `${key}_top_exam_types.png`);
            if (btnTopCsv) btnTopCsv.onclick = () => downloadTopExamCSV(dept);
        }

        // Print a branded, multi-section PDF for the Overall Board Performance report
        function printOverallToPDF() {
            try {
                const canvas = document.getElementById('dept_overall_line');
                if (!canvas) return;
                const chartUrl = canvas.toDataURL('image/png');
                const now = new Date();

                // Resolve years (date range) and departments metadata
                const years = (typeof overviewYears !== 'undefined' && overviewYears && overviewYears.length) ?
                    overviewYears :
                    ((charts['dept_overall_line'] && charts['dept_overall_line'].data && charts['dept_overall_line']
                        .data.labels) || []);
                const yFrom = years.length ? years[0] : '';
                const yTo = years.length ? years[years.length - 1] : '';
                const dateRange = (yFrom && yTo) ? `(${yFrom}–${yTo})` : '';

                const departments = (typeof deptOrder !== 'undefined' && Array.isArray(deptOrder) && deptOrder
                    .length) ? deptOrder : [];
                const colors = (typeof deptColors !== 'undefined') ? deptColors : {};

                // Build legend html
                const legendHtml = departments.map(d => {
                    const col = colors[d.key] || '#0ea5e9';
                    return `<span class="lg-item"><span class="sw" style="background:${col}"></span>${d.label}</span>`;
                }).join('');

                // Active department context (from Explore state if any)
                const activeDept = (typeof EXPLORE !== 'undefined' && EXPLORE.dept) ? EXPLORE.dept :
                    'All Departments';

                // Compute simple key findings from overviewLines
                const findings = (function() {
                    try {
                        if (typeof overviewLines === 'undefined' || !overviewLines) return [];
                        const deptKeys = Object.keys(overviewLines);
                        if (!deptKeys.length) return [];
                        // Average per year across departments
                        const avgByYear = (years || []).map(y => {
                            const vals = deptKeys.map(k => (overviewLines[k] && overviewLines[k]
                                .values_by_year) ? (overviewLines[k].values_by_year[String(
                                y)] || 0) : 0);
                            const avg = vals.length ? (vals.reduce((a, b) => a + b, 0) / vals
                                .length) : 0;
                            return {
                                y,
                                avg
                            };
                        });
                        const maxYear = avgByYear.reduce((m, c) => c.avg > m.avg ? c : m, {
                            y: years[0],
                            avg: -1
                        });
                        const minYear = avgByYear.reduce((m, c) => c.avg < m.avg ? c : m, {
                            y: years[0],
                            avg: 999999
                        });
                        // Department with highest average rate across years
                        const deptAverages = deptKeys.map(k => {
                            const vals = (years || []).map(y => (overviewLines[k] && overviewLines[
                                k].values_by_year) ? (overviewLines[k].values_by_year[
                                String(y)] || 0) : 0);
                            const avg = vals.length ? (vals.reduce((a, b) => a + b, 0) / vals
                                .length) : 0;
                            const slope = vals.length > 1 ? (vals[vals.length - 1] - vals[0]) : 0;
                            return {
                                key: k,
                                label: (departments.find(d => d.key === k) || {}).label || k,
                                avg,
                                slope
                            };
                        });
                        const bestDept = deptAverages.reduce((m, c) => c.avg > m.avg ? c : m, deptAverages[
                            0]);
                        const mostImproved = deptAverages.reduce((m, c) => c.slope > m.slope ? c : m,
                            deptAverages[0]);
                        const msgs = [];
                        if (years && years.length) {
                            msgs.push(
                                `Coverage: ${years[0]}–${years[years.length-1]} with ${deptKeys.length} departments.`
                            );
                        }
                        if (avgByYear.length) {
                            msgs.push(
                                `Highest overall average passing rate observed in ${maxYear.y}: ${maxYear.avg.toFixed(1)}%.`
                            );
                        }
                        if (avgByYear.length) {
                            msgs.push(
                                `Lowest overall average passing rate observed in ${minYear.y}: ${minYear.avg.toFixed(1)}%.`
                            );
                        }
                        if (bestDept) {
                            msgs.push(
                                `Top average across the period: ${bestDept.label} (${bestDept.avg.toFixed(1)}%).`
                            );
                        }
                        if (mostImproved && mostImproved.slope > 0) {
                            msgs.push(
                                `Strongest improvement from first to last year: ${mostImproved.label} (+${mostImproved.slope.toFixed(1)} pts).`
                            );
                        }
                        return msgs;
                    } catch (e) {
                        return [];
                    }
                })();

                // Build an optional raw data table (years x departments)
                const tableHtml = (function() {
                    try {
                        if (typeof overviewLines === 'undefined' || !overviewLines || !years || !years
                            .length) return '';
                        const deptKeys = Object.keys(overviewLines);
                        if (!deptKeys.length) return '';
                        const head =
                            `<tr><th>Year</th>${deptKeys.map(k=>`<th>${(departments.find(d=>d.key===k)||{}).label || k}</th>`).join('')}</tr>`;
                        const rows = years.map(y => {
                            const cols = deptKeys.map(k => {
                                const v = (overviewLines[k] && overviewLines[k]
                                    .values_by_year) ? (overviewLines[k].values_by_year[
                                    String(y)] || 0) : 0;
                                return `<td>${v.toFixed(1)}%</td>`;
                            }).join('');
                            return `<tr><td>${y}</td>${cols}</tr>`;
                        }).join('');
                        return `<table class="raw"><thead>${head}</thead><tbody>${rows}</tbody></table>`;
                    } catch (e) {
                        return '';
                    }
                })();

                // Logo (configurable). If missing, we simply hide the img (no fallback badge).
                const logoUrl = (window.PRINT_LOGO_URL || '');

                const w = window.open('', '_blank', 'width=1200,height=900');
                if (!w) return;
                w.document.write(`<!doctype html><html><head><meta charset="utf-8"/>
            <title>Overall Board Performance by Department ${dateRange}</title>
            <style>
              :root{ --teal:#0ea5e9; --teal-dark:#0369a1; --gray-900:#0f172a; --gray-700:#334155; --gray-500:#64748b; --gray-300:#e2e8f0; }
              @page{ size:A4; margin:18mm; }
              html,body{ height:auto; }
              body{ font-family: Poppins, Arial, sans-serif; color:var(--gray-900); -webkit-print-color-adjust: exact; print-color-adjust: exact; padding-bottom:20mm; }
              .print-header{ display:flex; align-items:center; gap:14px; }
              .logo{ height:42px; width:auto; object-fit:contain; }
              .uni{ line-height:1.2; }
              .uni .name{ font-weight:800; font-size:16pt; letter-spacing:.2px; }
              .uni .campus{ color:var(--gray-700); font-size:11pt; }
              .divider{ height:3px; background:linear-gradient(90deg,var(--teal),#14b8a6); border-radius:999px; margin:10px 0 18px; }
              h1.title{ margin:0; font-weight:800; font-size:18pt; color:var(--teal-dark); text-align:center; }
              .subtitle{ text-align:center; color:#475569; margin-top:4px; font-size:11pt; }
              .pill{ display:inline-flex; align-items:center; gap:8px; padding:6px 12px; border:1px solid var(--teal); color:var(--teal-dark); border-radius:999px; background:#f0fdff; font-weight:700; font-size:10pt; }
              .section{ margin-top:16px; }
              .chart{ width:100%; border:1px solid var(--gray-300); border-radius:10px; box-shadow:0 2px 8px rgba(2,6,23,.06); }
              .legend{ display:flex; flex-wrap:wrap; gap:8px 16px; margin-top:8px; justify-content:center; font-size:10pt; }
              .legend .lg-item{ display:inline-flex; align-items:center; gap:6px; }
              .legend .sw{ width:12px; height:12px; border-radius:3px; box-shadow:0 0 0 2px rgba(255,255,255,.7); }
              .key-text{ margin-top:14px; color:var(--gray-700); font-size:11pt; line-height:1.6; }
              .key-text ul{ margin:6px 0 0 18px; }
              table.raw{ width:100%; border-collapse:separate; border-spacing:0; margin-top:16px; font-size:10pt; }
              table.raw thead th{ position:sticky; top:0; background:linear-gradient(180deg,#f0fdff,#ecfeff); color:var(--gray-900); font-weight:800; border-bottom:2px solid var(--teal); padding:8px 10px; text-align:left; }
              table.raw tbody td{ padding:8px 10px; border-bottom:1px solid #eef2f7; }
              table.raw tbody tr:nth-child(even){ background:#f8feff; }
              .page-break{ page-break-after: always; }
              /* Avoid awkward splits */
              .section, .chart, .legend, .key-text, table.raw, .dept-head{ break-inside: avoid; page-break-inside: avoid; }
              .meta{ margin-top:10px; color:var(--gray-500); font-size:9.5pt; }
              .footer{ position:fixed; left:0; right:0; bottom:0; color:var(--gray-500); font-size:10pt; display:flex; align-items:center; justify-content:space-between; }
              .footer .copy{ opacity:.9; }
              .footer .page-number:after{ content: "Page " counter(page); }
            </style>
          </head>
          <body>
            <header class="print-header">
              <img class="logo" src="${logoUrl}" onerror="this.style.display='none'" alt="LSPU logo"/>
              <div class="uni">
                <div class="name">Laguna State Polytechnic University</div>
                <div class="campus">San Pablo City Campus</div>
              </div>
            </header>
            <div class="divider"></div>

            <h1 class="title">Overall Board Performance by Department</h1>
            <div class="subtitle">Passing Rate by Department ${dateRange}</div>

            <div style="margin-top:12px; text-align:center;">
              <span class="pill">Department: ${activeDept}</span>
            </div>

            <section class="section">
              <img class="chart" src="${chartUrl}" alt="Overview line chart"/>
              ${legendHtml ? `<div class="legend">${legendHtml}</div>` : ''}
            </section>

            <section class="section key-text">
              <div><strong>About this report.</strong> This report visualizes the board exam passing rates across departments over the selected period. It highlights trends, relative performance, and improvements.</div>
              ${findings.length ? `<ul>${findings.map(f=>`<li>${f}</li>`).join('')}</ul>` : ''}
            </section>

            ${tableHtml ? `<div class="section">${tableHtml}</div>` : ''}

            <div class="meta">Report Generated: ${now.toLocaleString()}</div>

            <footer class="footer">
              <div class="copy">© ${now.getFullYear()} LSPU • lspu.edu.ph</div>
              <div class="page-number"></div>
            </footer>
          </body></html>`);
                w.document.close();
                w.focus();
                // Let images render before invoking print
                setTimeout(() => {
                    try {
                        w.print();
                    } catch (e) {}
                    setTimeout(() => {
                        try {
                            w.close();
                        } catch (e) {}
                    }, 500);
                }, 600);
            } catch (e) {
                console.error(e);
            }
        }

        // Compute department metrics and small insights from cached data
        function computeDepartmentSummary(dept) {
            const key = dept.key;
            const yrs = yearsCache[key] || [];
            const rows = extraRowsCache[key] || [];
            const line = overviewLines[key] || {
                values_by_year: {}
            };
            const labels = (yrs && yrs.length) ? yrs.map(y => String(y)) : Object.keys(line.values_by_year || {});
            // Totals
            let passed = 0,
                failed = 0;
            rows.forEach(r => {
                const res = (r.result || '').trim();
                if (res === 'Passed') passed++;
                else if (res === 'Failed') failed++;
            });
            const total = passed + failed || rows.length;
            const passRate = total ? Math.round((passed / total) * 1000) / 10 : 0;
            // Line min/max and last delta
            const seriesVals = labels.map(l => (line.values_by_year && (line.values_by_year[l] || 0)) || 0);
            const first = seriesVals.length ? seriesVals[0] : 0;
            const last = seriesVals.length ? seriesVals[seriesVals.length - 1] : 0;
            const best = seriesVals.reduce((m, v) => v > m ? v : m, -1);
            const bestYear = (function() {
                let y = labels[0],
                    v = -1;
                labels.forEach((lab, i) => {
                    const val = seriesVals[i] || 0;
                    if (val > v) {
                        v = val;
                        y = lab;
                    }
                });
                return y;
            })();
            // Attempts split
            let firstTime = 0,
                repeat = 0;
            rows.forEach(r => {
                const t = (r.exam_type || '').toLowerCase();
                if (t.includes('first')) firstTime++;
                else if (t.includes('repeat')) repeat++;
            });
            // Sex split
            let male = 0,
                female = 0;
            rows.forEach(r => {
                const s = getSexValue(r);
                if (s === 'Male') male++;
                if (s === 'Female') female++;
            });
            // Takes by year
            const takesByYear = {};
            labels.forEach(y => takesByYear[y] = 0);
            rows.forEach(r => {
                const y = String(yearOf(r.board_exam_date));
                if (takesByYear.hasOwnProperty(y)) takesByYear[y]++;
            });
            const takesArray = labels.map(l => takesByYear[l] || 0);
            const maxTakes = takesArray.reduce((m, v) => v > m ? v : m, 0);
            const peakTakesYear = (function() {
                let yi = 0;
                takesArray.forEach((v, i) => {
                    if (v > takesArray[yi]) yi = i;
                });
                return labels[yi] || labels[0];
            })();
            // Top exams
            const examCounts = {};
            rows.forEach(r => {
                const k = (r.board_exam_type || 'Unknown').trim();
                examCounts[k] = (examCounts[k] || 0) + 1;
            });
            const topItem = Object.entries(examCounts).sort((a, b) => b[1] - a[1])[0] || null;
            return {
                years: labels,
                passRate,
                totalExaminees: total || rows.length,
                first,
                last,
                change: Math.round((last - first) * 10) / 10,
                best,
                bestYear,
                firstTime,
                repeat,
                male,
                female,
                takesByYear,
                maxTakes,
                peakTakesYear,
                topExam: topItem ? {
                    name: topItem[0],
                    count: topItem[1]
                } : null
            };
        }

        // Print a branded, multi-page PDF for a single department (two pages, with header/footer)
        function printDepartmentToPDF(dept) {
            try {
                const key = cssId(dept.key);
                const years = yearsCache[dept.key] || [];
                const dateRange = years.length ? `(${years[0]}–${years[years.length-1]})` : '';
                const S = computeDepartmentSummary(dept);

                // Collect chart images
                const ids = [`line_${key}`, `stack_${key}`, `pf_${key}`, `sex_${key}`, `att_${key}`, `top_${key}`];
                const dataUrls = {};
                ids.forEach(id => {
                    const c = document.getElementById(id);
                    if (c) dataUrls[id] = c.toDataURL('image/png');
                });

                const now = new Date();
                const logoUrl = (window.PRINT_LOGO_URL || '');

                const introSummary = (function() {
                    const dir = S.change > 0 ? 'upward' : (S.change < 0 ? 'downward' : 'stable');
                    const driver = S.topExam ? `with notable volume in ${S.topExam.name}` :
                        'across administered examinations';
                    return `This report summarizes ${dept.label}'s board performance ${dateRange}. Passing rates show a ${dir} trend from ${S.first.toFixed(1)}% to ${S.last.toFixed(1)}%, peaking in ${S.bestYear} at ${S.best.toFixed(1)}%, ${driver}.`;
                })();

                const p1Notes = {
                    line: `Interpretation: Passing rate moved from ${S.first.toFixed(1)}% to ${S.last.toFixed(1)}% (${S.change>=0?'+':''}${S.change.toFixed(1)} pts) over the covered years; best year ${S.bestYear}.`,
                    stack: S.topExam ?
                        `Interpretation: ${S.topExam.name} contributed the most examinees overall.` :
                        `Interpretation: Examinee totals vary by exam type across years.`,
                    pf: `Interpretation: Overall passing rate is ${S.passRate.toFixed(1)}% out of ${S.totalExaminees} examinees.`
                };
                const p2Notes = {
                    sex: `Interpretation: Distribution is ${S.male + S.female ? Math.round((S.male/(S.male+S.female))*100) : 0}% male and ${S.male + S.female ? Math.round((S.female/(S.male+S.female))*100) : 0}% female overall.`,
                    att: `Interpretation: First-timers ${S.firstTime} vs repeaters ${S.repeat}; rates reflect preparation and review dynamics.`,
                    takes: `Interpretation: Total exam sittings peaked in ${S.peakTakesYear} with ${S.maxTakes} takes across all exam types.`,
                    top: S.topExam ?
                        `Interpretation: ${S.topExam.name} ranks first among exam types by examinee count.` :
                        `Interpretation: No single exam type dominates across the period.`
                };

                const w = window.open('', '_blank', 'width=1200,height=900');
                if (!w) return;
                w.document.write(`<!doctype html><html><head><meta charset="utf-8"/>
            <title>Departmental Performance Analysis – ${dept.label} ${dateRange}</title>
            <style>
              :root{ --teal:#0ea5e9; --teal-dark:#0369a1; --gray-900:#0f172a; --gray-700:#334155; --gray-500:#64748b; --gray-300:#e2e8f0; }
              @page{ size:A4; margin:16mm; }
              html,body{ height:auto; }
              body{ font-family:Poppins, Arial, sans-serif; color:var(--gray-900); -webkit-print-color-adjust:exact; print-color-adjust:exact; counter-reset: page; padding-bottom:18mm; }
              .header{ text-align:center; }
              .hdr-top{ display:flex; align-items:center; justify-content:center; gap:10px; }
              .logo{ height:42px; width:auto; object-fit:contain; }
              .name{ font-weight:800; font-size:16pt; letter-spacing:.2px; text-transform:uppercase; }
              .campus{ color:var(--gray-700); font-size:11pt; }
              .title{ margin:6px 0 0; font-weight:900; font-size:18pt; color:var(--teal-dark); }
              .subtitle{ margin-top:4px; color:#475569; font-size:11pt; }
              .sep{ height:3px; background:linear-gradient(90deg,var(--teal),#14b8a6); border-radius:999px; margin:10px 0 14px; }
              .metrics{ display:flex; gap:12px; flex-wrap:wrap; justify-content:center; margin:8px 0 4px; }
              .metric{ background:#ecfeff; border:1px solid var(--teal); color:var(--teal-dark); border-radius:12px; padding:8px 12px; font-weight:800; }
              .page{ page-break-after:always; break-inside: avoid-page; padding-bottom:14mm; }
              .page:last-of-type{ page-break-after:auto; }
              .row{ display:flex; gap:12px; }
              .half{ flex:1; }
              .block{ margin-top:10px; break-inside: avoid; page-break-inside: avoid; }
              /* Chart sizing helpers for consistent, balanced layout */
              .chart-box{ width:100%; height:210px; border:1px solid var(--gray-300); border-radius:10px; box-shadow:0 2px 8px rgba(2,6,23,.06); overflow:hidden; background:#fff; display:flex; align-items:center; justify-content:center; break-inside: avoid; page-break-inside: avoid; }
              .chart-box.full{ height:260px; }
              .chart-img{ width:100%; height:100%; object-fit:contain; display:block; }
              /* Optional placeholder to preserve balance for single-item rows */
              .half.placeholder{ visibility:hidden; }
              .note{ font-size:10.5pt; color:var(--gray-700); margin-top:6px; }
              .para{ font-size:11pt; line-height:1.6; color:var(--gray-800); }
              .footer{ position:fixed; left:0; right:0; bottom:0; font-size:10pt; color:var(--gray-500); display:flex; justify-content:space-between; }
              .footer .page-number:after{ content: "Page " counter(page); }
            </style>
          </head>
          <body>
            <!-- Page 1 -->
            <div class="page">
              <div class="header">
                <div class="hdr-top"><img class="logo" src="${logoUrl}" onerror="this.style.display='none'" alt="LSPU"/></div>
                <div class="name">Laguna State Polytechnic University</div>
                <div class="campus">San Pablo City Campus</div>
                <div class="title">DEPARTMENTAL PERFORMANCE ANALYSIS</div>
                <div class="subtitle">Overall Board Trends – ${dept.label} ${dateRange}</div>
                <div class="sep"></div>
              </div>

              <div class="metrics">
                <div class="metric">Overall Passing Rate: ${S.passRate.toFixed(1)}%</div>
                <div class="metric">Total Examinees: ${S.totalExaminees}</div>
              </div>

              <div class="block para">${introSummary}</div>

              <div class="block">
                <div class="chart-box full"><img class="chart-img" src="${dataUrls[`line_${key}`]||''}" alt="Passing rate by year"/></div>
                <div class="note">${p1Notes.line}</div>
              </div>
              <div class="row block">
                <div class="half">
                  <div class="chart-box"><img class="chart-img" src="${dataUrls[`stack_${key}`]||''}" alt="Totals by exam type"/></div>
                  <div class="note">${p1Notes.stack}</div>
                </div>
                <div class="half">
                  <div class="chart-box"><img class="chart-img" src="${dataUrls[`pf_${key}`]||''}" alt="Pass vs Fail by year"/></div>
                  <div class="note">${p1Notes.pf}</div>
                </div>
              </div>
            </div>

            <!-- Page 2 -->
            <div class="page">
              <div class="header">
                <div class="hdr-top"><img class="logo" src="${logoUrl}" onerror="this.style.display='none'" alt="LSPU"/></div>
                <div class="name">Laguna State Polytechnic University</div>
                <div class="campus">San Pablo City Campus</div>
                <div class="title">DEPARTMENTAL PERFORMANCE ANALYSIS</div>
                <div class="subtitle">Overall Board Trends – ${dept.label} ${dateRange}</div>
                <div class="sep"></div>
              </div>

              <div class="row block">
                <div class="half">
                  <div class="chart-box"><img class="chart-img" src="${dataUrls[`takes_${key}`]||''}" alt="Total takes by year"/></div>
                  <div class="note">${p2Notes.takes}</div>
                </div>
                <div class="half">
                  <div class="chart-box"><img class="chart-img" src="${dataUrls[`sex_${key}`]||''}" alt="Male vs Female by year"/></div>
                  <div class="note">${p2Notes.sex}</div>
                </div>
              </div>
              <div class="row block">
                <div class="half">
                  <div class="chart-box"><img class="chart-img" src="${dataUrls[`att_${key}`]||''}" alt="First timer vs repeater by year"/></div>
                  <div class="note">${p2Notes.att}</div>
                </div>
                <div class="half placeholder"></div>
              </div>
              <div class="block">
                <div class="chart-box full"><img class="chart-img" src="${dataUrls[`top_${key}`]||''}" alt="Top 5 exam types"/></div>
                <div class="note">${p2Notes.top}</div>
              </div>

              <div class="block para"><strong>Conclusion.</strong> ${dept.label} demonstrates resilient outcomes with ${S.passRate.toFixed(1)}% overall passing and ${S.totalExaminees} examinees. Continued focus on high-volume exams${S.topExam?` like ${S.topExam.name}`:''} and sustaining the improvements seen since ${S.bestYear} is recommended.</div>
              <div class="block" style="margin-top:8px; font-size:10pt; color:#64748b;">Source: LSPU Performance Dashboard | Generated: ${now.toLocaleString()}</div>
            </div>

            <div class="footer">
              <div>© ${now.getFullYear()} LSPU</div>
              <div class="page-number"></div>
            </div>
          </body></html>`);
                w.document.close();
                w.focus();
                setTimeout(() => {
                    try {
                        w.print();
                    } catch (e) {}
                    setTimeout(() => {
                        try {
                            w.close();
                        } catch (e) {}
                    }, 500);
                }, 600);
            } catch (e) {
                console.error(e);
            }
        }

        // Overview and line exports
        const btnOverallPng = document.getElementById('btn_overall_png');
        if (btnOverallPng) btnOverallPng.addEventListener('click', () => downloadCanvasPNG('dept_overall_line',
            'departments_overview.png'));
        const btnOverallCsv = document.getElementById('btn_overall_csv');
        if (btnOverallCsv) btnOverallCsv.addEventListener('click', () => downloadOverviewCSV());
        const btnOverallPdf = document.getElementById('btn_overall_pdf');
        if (btnOverallPdf) btnOverallPdf.addEventListener('click', () => printOverallToPDF());

        // Overall takers exports
        const btnOverallTakersPng = document.getElementById('btn_overall_takers_png');
        if (btnOverallTakersPng) btnOverallTakersPng.addEventListener('click', () => downloadCanvasPNG(
            'overall_takers', 'overall_takers_by_year.png'));
        const btnOverallTakersCsv = document.getElementById('btn_overall_takers_csv');
        if (btnOverallTakersCsv) btnOverallTakersCsv.addEventListener('click', () => downloadOverallTakersCSV());

        function downloadOverviewCSV() {
            const yrs = (overviewYears && overviewYears.length) ? overviewYears : ((charts['dept_overall_line'] &&
                charts['dept_overall_line'].data && charts['dept_overall_line'].data.labels) ? charts[
                'dept_overall_line'].data.labels : []);
            if (!yrs || !yrs.length) return;
            const header = ['Year'].concat(Object.keys(overviewLines));
            const rows = [header];
            yrs.forEach(y => {
                const row = [String(y)];
                Object.keys(overviewLines).forEach(k => {
                    const line = overviewLines[k];
                    const v = line && line.values_by_year ? (line.values_by_year[String(y)] || 0) :
                        0;
                    row.push(v + '%');
                });
                rows.push(row);
            });
            const csv = rows.map(r => r.map(x => '"' + String(x).replace(/"/g, '""') + '"').join(',')).join('\n');
            const blob = new Blob([csv], {
                type: 'text/csv;charset=utf-8;'
            });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'departments_overview.csv';
            a.click();
            URL.revokeObjectURL(url);
        }

        function downloadOverallTakersCSV() {
            const yrs = (overallTakersCache && overallTakersCache.years) ? overallTakersCache.years : [];
            const vals = (overallTakersCache && overallTakersCache.values) ? overallTakersCache.values : [];
            if (!yrs.length || !vals.length) return;
            const rows = [
                ['Year', 'Takers']
            ];
            yrs.forEach((y, i) => rows.push([String(y), vals[i] || 0]));
            const csv = rows.map(r => r.map(x => '"' + String(x).replace(/"/g, '""') + '"').join(',')).join('\n');
            const blob = new Blob([csv], {
                type: 'text/csv;charset=utf-8;'
            });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'overall_takers_by_year.csv';
            a.click();
            URL.revokeObjectURL(url);
        }

        function downloadLineCSV(dept) {
            const key = dept.key;
            const line = overviewLines[key];
            const yrs = yearsCache[key] || (charts[`line_${cssId(key)}`] && charts[`line_${cssId(key)}`].data ?
                charts[`line_${cssId(key)}`].data.labels : (overviewYears || []));
            if (!line || !yrs.length) return;
            const header = ['Year', 'Passing Rate (%)'];
            const rows = [header];
            yrs.forEach(y => {
                const v = line.values_by_year ? (line.values_by_year[String(y)] || 0) : 0;
                rows.push([String(y), v]);
            });
            const csv = rows.map(r => r.map(x => '"' + String(x).replace(/"/g, '""') + '"').join(',')).join('\n');
            const blob = new Blob([csv], {
                type: 'text/csv;charset=utf-8;'
            });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `${cssId(key)}_passing_rate.csv`;
            a.click();
            URL.revokeObjectURL(url);
        }

        function shade(hex, t) {
            // Simple lighten by mixing white
            const m = hex.replace('#', '');
            if (m.length < 6) return hex;
            const r = parseInt(m.substring(0, 2), 16),
                g = parseInt(m.substring(2, 4), 16),
                b = parseInt(m.substring(4, 6), 16);
            const nr = Math.min(255, Math.round(r + (255 - r) * t));
            const ng = Math.min(255, Math.round(g + (255 - g) * t));
            const nb = Math.min(255, Math.round(b + (255 - b) * t));
            return '#' + [nr, ng, nb].map(v => v.toString(16).padStart(2, '0')).join('');
        }

    })();

    // Tiny parallax effect for the hero section
    function initHeroParallax() {
        try {
            const hero = document.querySelector('.hero-section');
            const content = document.querySelector('.hero-content');
            const illo = document.querySelector('.hero-illustration');
            if (!hero || !content || !illo) return;
            const reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            if (reduce) return;
            let raf = null;
            const onScroll = () => {
                if (raf) cancelAnimationFrame(raf);
                raf = requestAnimationFrame(() => {
                    const r = hero.getBoundingClientRect();
                    const vh = window.innerHeight || 1;
                    if (r.bottom < 0 || r.top > vh) return; // out of view
                    const t = Math.max(-1, Math.min(1, (0 - r.top) / vh));
                    // content slower, illustration a bit faster
                    content.style.transform = `translateY(${(t*6).toFixed(2)}px)`;
                    illo.style.transform = `translateY(${(t*14).toFixed(2)}px)`;
                });
            };
            window.addEventListener('scroll', onScroll, {
                passive: true
            });
            onScroll();
        } catch (e) {
            /* no-op */
        }
    }

    // Copy-to-clipboard helper for About section
    function bindCopyButtons() {
        const btns = document.querySelectorAll('[data-copy]');
        btns.forEach(btn => {
            btn.addEventListener('click', () => {
                const text = btn.getAttribute('data-copy') || '';
                if (!text) return;
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(text).then(() => flashCopied(btn)).catch(() =>
                        fallbackCopy(text, btn));
                } else {
                    fallbackCopy(text, btn);
                }
            });
        });
    }

    function fallbackCopy(text, btn) {
        const ta = document.createElement('textarea');
        ta.value = text;
        document.body.appendChild(ta);
        ta.select();
        try {
            document.execCommand('copy');
        } catch (e) {}
        document.body.removeChild(ta);
        flashCopied(btn);
    }

    function flashCopied(btn) {
        const original = btn.textContent;
        btn.textContent = 'Copied!';
        btn.disabled = true;
        setTimeout(() => {
            btn.textContent = original;
            btn.disabled = false;
        }, 1200);
    }

    // Flip-card interactions for About section
    function bindFlipCards() {
        const cards = document.querySelectorAll('.flip-card');
        cards.forEach(card => {
            const toggle = () => {
                const willFlip = !card.classList.contains('flipped');
                // close others if opening this one
                if (willFlip) {
                    document.querySelectorAll('.flip-card.flipped').forEach(c => {
                        if (c !== card) {
                            c.classList.remove('flipped');
                            c.setAttribute('aria-expanded', 'false');
                        }
                    });
                }
                card.classList.toggle('flipped');
                const expanded = willFlip;
                card.setAttribute('aria-expanded', expanded ? 'true' : 'false');
            };
            // click: ripple + flip
            card.addEventListener('click', (e) => {
                makeRipple(card, e);
                toggle();
            });
            card.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    toggle();
                }
            });
            // small 3D tilt on mouse move
            let raf = null;
            const onMove = (e) => {
                if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
                const r = card.getBoundingClientRect();
                const cx = r.left + r.width / 2;
                const cy = r.top + r.height / 2;
                const dx = (e.clientX - cx) / (r.width / 2); // -1..1
                const dy = (e.clientY - cy) / (r.height / 2);
                const max = 6; // degrees
                const rx = (-dy * max).toFixed(2);
                const ry = (dx * max).toFixed(2);
                if (raf) cancelAnimationFrame(raf);
                raf = requestAnimationFrame(() => {
                    card.style.transform = `rotateX(${rx}deg) rotateY(${ry}deg)`;
                });
            };
            const onLeave = () => {
                if (raf) cancelAnimationFrame(raf);
                card.style.transform = 'rotateX(0) rotateY(0)';
            };
            card.addEventListener('mousemove', onMove);
            card.addEventListener('mouseleave', onLeave);
        });
    }

    function makeRipple(card, e) {
        try {
            const face = card.classList.contains('flipped') ? card.querySelector('.flip-back') : card.querySelector(
                '.flip-front');
            if (!face) return;
            const rect = face.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const span = document.createElement('span');
            span.className = 'ripple';
            span.style.width = span.style.height = `${size}px`;
            span.style.left = `${e.clientX - rect.left - size/2}px`;
            span.style.top = `${e.clientY - rect.top - size/2}px`;
            face.appendChild(span);
            span.addEventListener('animationend', () => span.remove());
        } catch (_e) {
            /* no-op */
        }
    }

    // Simple modal for full texts (with animated border)
    function openPolicyModal(title, text) {
        let overlay = document.getElementById('policy-modal');
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.id = 'policy-modal';
            overlay.className = 'modal-overlay';
            overlay.innerHTML = `
          <div class="modal" role="dialog" aria-modal="true" aria-labelledby="policyModalTitle">
            <header>
              <h4 id="policyModalTitle"></h4>
              <button class="close" id="policyModalClose" aria-label="Close dialog" title="Close">&times;</button>
            </header>
            <div class="modal-body" id="policyModalBody"></div>
          </div>`;
            document.body.appendChild(overlay);
            overlay.addEventListener('click', (e) => {
                if (e.target === overlay) closePolicyModal();
            });
            overlay.querySelector('#policyModalClose').addEventListener('click', closePolicyModal);
            // Global ESC handler
            document.addEventListener('keydown', function escHandler(e) {
                const ov = document.getElementById('policy-modal');
                if (!ov || ov.style.display !== 'flex') return;
                if (e.key === 'Escape') {
                    closePolicyModal();
                }
            });
        }
        overlay.querySelector('#policyModalTitle').textContent = title || 'Details';
        overlay.querySelector('#policyModalBody').textContent = text || '';
        overlay.style.display = 'flex';
        document.body.classList.add('no-scroll');
        // Focus the close button for quick keyboard access
        const btn = overlay.querySelector('#policyModalClose');
        if (btn) btn.focus();
    }

    function closePolicyModal() {
        const overlay = document.getElementById('policy-modal');
        if (overlay) {
            overlay.style.display = 'none';
        }
        document.body.classList.remove('no-scroll');
    }

    // Delegate link clicks for "Read the full policy"
    document.addEventListener('click', (e) => {
        const link = e.target.closest('.open-policy');
        if (link) {
            e.preventDefault();
            e.stopPropagation();
            const src = link.getAttribute('data-source');
            const title = link.getAttribute('data-title') || 'Details';
            const node = src ? document.querySelector(src) : null;
            const text = node ? (node.textContent || '') : '';
            openPolicyModal(title, text);
        }
    });
    </script>

</body>

</html>