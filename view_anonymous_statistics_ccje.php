<?php
session_start();

// Only allow College of Criminal Justice Education admin
if (!isset($_SESSION["users"]) || $_SESSION["users"] !== 'ccje_admin@lspu.edu.ph') {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <title>Anonymous Data Statistics - Criminal Justice Education</title>
    <link rel="stylesheet" href="style.css"/>
    <link rel="stylesheet" href="css/sidebar.css?v=<?php echo time(); ?>"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <style>
        :root {
            --primary: #D32F2F;
            --primary-dark: #C62828;
            --success: #D32F2F;
            --danger: #64748b;
            --warning: #FAD6A5;
            --purple: #8b5cf6;
            --teal: #14b8a6;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #FDF3E7 0%, #FAD6A5 50%, #FDF3E7 100%);
            color: #0f1724;
            min-height: 100vh;
            position: relative;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 50%, rgba(211, 47, 47, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(198, 40, 40, 0.08) 0%, transparent 50%);
            pointer-events: none;
            z-index: 0;
        }

        .topbar {
            position: fixed;
            top: 0;
            left: 260px;
            right: 0;
            background: linear-gradient(135deg, #D32F2F 0%, #C62828 50%, #800020 100%);
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 40px;
            box-shadow: 0 4px 30px rgba(211, 47, 47, 0.3), 0 2px 10px rgba(0, 0, 0, 0.1);
            z-index: 50;
            backdrop-filter: blur(10px);
        }

        .dashboard-title {
            font-size: 1.4rem;
            color: #fff;
            font-weight: 700;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .logout-btn {
            background: rgba(255, 255, 255, 0.15);
            color: #fff;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 12px;
            padding: 12px 24px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .logout-btn:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .main {
            margin-left: 260px;
            margin-top: 70px;
            padding: 32px;
            position: relative;
            z-index: 1;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
            padding: 24px;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(253, 243, 231, 0.85) 100%);
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(211, 47, 47, 0.15);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(211, 47, 47, 0.2);
        }

        .page-header h2 {
            font-size: 1.8rem;
            font-weight: 800;
            color: #0f1724;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .page-header h2 i {
            background: linear-gradient(135deg, #D32F2F 0%, #C62828 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .btn-link {
            background: linear-gradient(135deg, #D32F2F 0%, #C62828 50%, #800020 100%);
            color: #fff;
            border: none;
            border-radius: 12px;
            padding: 12px 24px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 15px rgba(145, 179, 142, 0.3);
            position: relative;
            overflow: hidden;
        }

        .btn-link::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.5s;
        }

        .btn-link:hover::before {
            left: 100%;
        }

        .btn-link:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(211, 47, 47, 0.4);
        }

        .charts-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 24px;
            margin-bottom: 24px;
        }

        .chart-card {
            background: linear-gradient(145deg, rgba(255, 255, 255, 0.98) 0%, rgba(253, 243, 231, 0.95) 100%);
            border-radius: 24px;
            padding: 32px;
            box-shadow: 
                0 10px 40px rgba(211, 47, 47, 0.12),
                0 2px 8px rgba(0, 0, 0, 0.05);
            border: 2px solid rgba(211, 47, 47, 0.15);
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
            height: 4px;
            background: linear-gradient(90deg, #D32F2F, #C62828, #D32F2F);
            background-size: 200% 100%;
            animation: shimmer 3s linear infinite;
        }

        @keyframes shimmer {
            0% { background-position: -200% 0; }
            100% { background-position: 200% 0; }
        }

        .chart-card:hover {
            transform: translateY(-4px);
            box-shadow: 
                0 15px 50px rgba(211, 47, 47, 0.2),
                0 5px 15px rgba(0, 0, 0, 0.1);
            border-color: rgba(211, 47, 47, 0.3);
        }

        .chart-card.full-width {
            grid-column: 1 / -1;
        }

        .chart-card h3 {
            font-size: 1.3rem;
            font-weight: 700;
            color: #0f1724;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            padding-bottom: 16px;
            border-bottom: 2px solid rgba(145, 179, 142, 0.15);
        }

        .chart-card h3 i {
            background: linear-gradient(135deg, #D32F2F 0%, #C62828 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 1.4rem;
        }

        .info-btn {
            margin-left: auto;
            background: linear-gradient(135deg, #D32F2F 0%, #C62828 100%);
            color: white;
            border: none;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(145, 179, 142, 0.3);
        }

        .info-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 12px rgba(211, 47, 47, 0.5);
        }

        .info-btn i {
            font-size: 16px;
            background: none;
            -webkit-text-fill-color: white;
        }

        .filter-section {
            background: white;
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 32px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(211, 47, 47, 0.2);
        }

        .filter-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 2px solid rgba(211, 47, 47, 0.15);
        }

        .filter-header h3 {
            margin: 0;
            font-size: 1.2rem;
            font-weight: 700;
            color: #0f1724;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .filter-header h3 i {
            color: #D32F2F;
            font-size: 1.3rem;
        }

        .filter-controls {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .filter-group label {
            font-weight: 600;
            color: #334155;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .filter-group label i {
            color: #D32F2F;
            font-size: 1rem;
        }

        .filter-select {
            position: relative;
        }

        .filter-select select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 0.95rem;
            color: #334155;
            background: white;
            cursor: pointer;
            transition: all 0.3s;
            font-family: 'Inter', sans-serif;
        }

        .filter-select select:focus {
            outline: none;
            border-color: #D32F2F;
            box-shadow: 0 0 0 3px rgba(211, 47, 47, 0.1);
        }

        .multi-select-container {
            position: relative;
        }

        .multi-select-display {
            width: 100%;
            min-height: 45px;
            padding: 10px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            background: white;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 6px;
        }

        .multi-select-display:hover {
            border-color: #D32F2F;
        }

        .multi-select-display.active {
            border-color: #D32F2F;
            box-shadow: 0 0 0 3px rgba(211, 47, 47, 0.1);
        }

        .multi-select-placeholder {
            color: #94a3b8;
            font-size: 0.95rem;
        }

        .selected-tag {
            background: linear-gradient(135deg, #D32F2F 0%, #C62828 100%);
            color: white;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .selected-tag i {
            cursor: pointer;
            font-size: 0.75rem;
        }

        .selected-tag i:hover {
            transform: scale(1.2);
        }

        .multi-select-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            margin-top: 4px;
            background: white;
            border: 2px solid #D32F2F;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
            max-height: 300px;
            overflow-y: auto;
            z-index: 100;
            display: none;
        }

        .multi-select-dropdown.show {
            display: block;
        }

        .multi-select-option {
            padding: 12px 16px;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 1px solid #f1f5f9;
        }

        .multi-select-option:last-child {
            border-bottom: none;
        }

        .multi-select-option:hover {
            background: #FDF3E7;
        }

        .multi-select-option.selected {
            background: linear-gradient(135deg, #FDF3E7 0%, #FAD6A5 100%);
            font-weight: 600;
        }

        .multi-select-option input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: #D32F2F;
        }

        .filter-actions {
            display: flex;
            gap: 12px;
            margin-top: 20px;
        }

        .filter-btn {
            padding: 12px 24px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
            font-family: 'Inter', sans-serif;
        }

        .filter-btn.primary {
            background: linear-gradient(135deg, #D32F2F 0%, #C62828 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(211, 47, 47, 0.3);
        }

        .filter-btn.primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(211, 47, 47, 0.4);
        }

        .filter-btn.secondary {
            background: white;
            color: #64748b;
            border: 2px solid #e2e8f0;
        }

        .filter-btn.secondary:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
        }

        .active-filters {
            margin-top: 16px;
            padding: 12px;
            background: #FDF3E7;
            border-radius: 8px;
            display: none;
        }

        .active-filters.show {
            display: block;
        }

        .active-filters-text {
            font-size: 0.9rem;
            color: #334155;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .active-filters-list {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .chart-container {
            position: relative;
            height: 350px;
            width: 100%;
        }

        .chart-container.small {
            height: 250px;
            width: 100%;
        }

        canvas {
            display: block;
            box-sizing: border-box;
        }

        .empty-state {
            text-align: center;
            padding: 80px 20px;
            color: #64748b;
        }

        .empty-state i {
            font-size: 5rem;
            color: #cbd5e1;
            margin-bottom: 24px;
            opacity: 0.5;
        }

        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 16px;
            color: #475569;
            font-weight: 700;
        }

        @media (max-width: 1200px) {
            .charts-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 900px) {
            .main {
                margin-left: 80px;
            }
            .topbar {
                left: 80px;
            }
        }

        @media (max-width: 600px) {
            .main {
                margin-left: 0;
                padding: 16px;
                margin-top: 80px;
            }
            .topbar {
                left: 0;
                padding: 0 20px;
                height: 80px;
                flex-direction: column;
                justify-content: center;
                gap: 8px;
            }
            .dashboard-title {
                font-size: 1.1rem;
            }
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 16px;
                padding: 20px;
            }
            .chart-card {
                padding: 24px;
            }
        }

    /* Logout Modal Styles - Beautiful Green Theme Design */
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
            0 32px 64px -12px rgba(90, 133, 95, 0.25),
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
        background: linear-gradient(135deg, #800020 0%, #D32F2F 25%, #FAD6A5 50%, #D32F2F 75%, #800020 100%) !important;
        border-radius: 30px !important;
        z-index: -1 !important;
        opacity: 0.8 !important;
        animation: borderGradientRotate 4s linear infinite !important;
    }

    @keyframes borderGradientRotate {
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

    #logoutModal .modal-header {
        margin-bottom: 32px !important;
        background: linear-gradient(135deg, #FDF3E7 0%, #FAD6A5 100%) !important;
        padding: 32px 28px !important;
        border-radius: 20px !important;
        border: 2px solid #E8C9A3 !important;
        position: relative !important;
        overflow: hidden !important;
        box-shadow: 0 8px 25px rgba(211, 47, 47, 0.15) !important;
    }

    #logoutModal .modal-header::before {
        content: '' !important;
        position: absolute !important;
        top: 0 !important;
        left: 0 !important;
        right: 0 !important;
        height: 4px !important;
        background: linear-gradient(90deg, #800020 0%, #D32F2F 50%, #FAD6A5 100%) !important;
        border-radius: 20px 20px 0 0 !important;
    }

    #logoutModal .modal-header::after {
        content: '' !important;
        position: absolute !important;
        top: -50px !important;
        right: -50px !important;
        width: 120px !important;
        height: 120px !important;
        background: linear-gradient(135deg, rgba(211, 47, 47, 0.1) 0%, rgba(250, 214, 165, 0.05) 100%) !important;
        border-radius: 50% !important;
        z-index: 0 !important;
    }

    #logoutModal .modal-icon {
        width: 88px !important;
        height: 88px !important;
        background: linear-gradient(135deg, #800020 0%, #D32F2F 50%, #C62828 100%) !important;
        border-radius: 50% !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        margin: 0 auto 24px !important;
        color: white !important;
        font-size: 2.2rem !important;
        box-shadow:
            0 20px 40px rgba(211, 47, 47, 0.4),
            0 0 0 4px rgba(255, 255, 255, 0.8),
            0 0 0 6px rgba(211, 47, 47, 0.2) !important;
        position: relative !important;
        z-index: 1 !important;
        animation: iconPulse 3s ease-in-out infinite !important;
    }

    @keyframes iconPulse {
        0%, 100% {
            box-shadow:
                0 20px 40px rgba(211, 47, 47, 0.4),
                0 0 0 4px rgba(255, 255, 255, 0.8),
                0 0 0 6px rgba(211, 47, 47, 0.2);
            transform: scale(1);
        }
        50% {
            box-shadow:
                0 25px 50px rgba(211, 47, 47, 0.6),
                0 0 0 6px rgba(255, 255, 255, 0.9),
                0 0 0 8px rgba(211, 47, 47, 0.3);
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
        background: linear-gradient(135deg, #FAD6A5, #D32F2F, #800020, #5D0016) !important;
        border-radius: 50% !important;
        z-index: -1 !important;
        opacity: 0.6 !important;
        animation: rotateGradient 6s linear infinite !important;
    }

    @keyframes rotateGradient {
        0% {
            transform: rotate(0deg);
        }
        100% {
            transform: rotate(360deg);
        }
    }

    #logoutModal .modal-title {
        font-size: 1.75rem !important;
        font-weight: 800 !important;
        background: linear-gradient(135deg, #800020 0%, #5D0016 100%) !important;
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
        color: #5D0016 !important;
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
        position: relative !important;
        overflow: hidden !important;
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

    #logoutModal .modal-btn>* {
        position: relative !important;
        z-index: 1 !important;
    }

    #logoutModal .modal-btn:hover>i {
        transform: scale(1.15) rotate(5deg) !important;
    }

    #logoutModal .modal-btn .btn-text {
        transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1) !important;
    }

    #logoutModal .modal-btn .btn-spinner {
        position: absolute !important;
        left: 50% !important;
        top: 50% !important;
        transform: translate(-50%, -50%) translateX(20px) !important;
        opacity: 0 !important;
        transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1) !important;
        animation: spin 1s linear infinite !important;
    }

    #logoutModal .modal-btn .btn-check {
        position: absolute !important;
        left: 50% !important;
        top: 50% !important;
        transform: translate(-50%, -50%) translateX(20px) scale(0.8) !important;
        opacity: 0 !important;
        transition: all 0.5s cubic-bezier(0.34, 1.56, 0.64, 1) !important;
        color: white !important;
    }

    @keyframes spin {
        0% { transform: translate(-50%, -50%) rotate(0deg); }
        100% { transform: translate(-50%, -50%) rotate(360deg); }
    }

    #logoutModal .modal-btn.logout-confirm {
        background: linear-gradient(135deg, #800020 0%, #D32F2F 50%, #5D0016 100%) !important;
        color: #ffffff !important;
    }

    #logoutModal .modal-btn.logout-confirm:hover {
        background: linear-gradient(135deg, #5D0016 0%, #800020 50%, #D32F2F 100%) !important;
        transform: translateY(-3px) scale(1.05) !important;
    }

    #logoutModal .modal-btn.logout-confirm.loading {
        background: linear-gradient(135deg, #64748b 0%, #475569 50%, #374151 100%) !important;
        cursor: not-allowed !important;
        pointer-events: none !important;
    }

    #logoutModal .modal-btn.logout-confirm.loading .btn-text {
        opacity: 0 !important;
        transform: translateX(-20px) !important;
    }

    #logoutModal .modal-btn.logout-confirm.loading .btn-spinner {
        opacity: 1 !important;
        transform: translateX(0) !important;
    }

    #logoutModal .modal-btn.logout-confirm.success {
        background: linear-gradient(135deg, #D32F2F 0%, #800020 50%, #C62828 100%) !important;
        transform: translateY(-2px) scale(1.05) !important;
        box-shadow: 0 12px 30px rgba(211, 47, 47, 0.4) !important;
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
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08) !important;
    }

    #logoutModal .modal-btn.logout-cancel:hover {
        background: linear-gradient(135deg, #e2e8f0 0%, #cbd5e0 100%) !important;
        color: #475569 !important;
        transform: translateY(-2px) scale(1.05) !important;
    }

    @media (max-width: 640px) {
        #logoutModal .modal-content {
            width: 95% !important;
            padding: 36px 32px !important;
        }
        #logoutModal .modal-buttons {
            flex-direction: column !important;
            gap: 16px !important;
        }
        #logoutModal .modal-btn {
            width: 100% !important;
        }
    }
    </style>
</head>
<body>
    <?php include __DIR__ . '/includes/ccje_nav.php'; ?>
    
    <div class="topbar">
        <div class="dashboard-title">Anonymous Data Statistics - Criminal Justice Education</div>
        <div><a class="logout-btn" href="#" onclick="confirmLogout(event)">Logout</a></div>
    </div>

    <div class="main">
        <div class="page-header">
            <h2><i class="fas fa-chart-area" style="margin-right: 12px;"></i>Anonymous Data Statistics</h2>
            <div style="display: flex; gap: 12px;">
                <button onclick="exportToPDF()" class="btn-link" style="border: none; cursor: pointer;">
                    <i class="fas fa-file-pdf"></i> Export to PDF
                </button>
                <a href="testing_anonymous_data_ccje.php" class="btn-link">
                    <i class="fas fa-plus-circle"></i> Add Data
                </a>
                <a href="anonymous_dashboard_ccje.php" class="btn-link">
                    <i class="fas fa-table"></i> View Dashboard
                </a>
            </div>
        </div>

        <div id="contentArea">
            <!-- Filter Section -->
            <div class="filter-section">
                <div class="filter-header">
                    <h3>
                        <i class="fas fa-filter"></i>
                        Filter Statistics
                    </h3>
                    <button class="filter-btn secondary" onclick="toggleFilterSection()" id="toggleFilterBtn">
                        <i class="fas fa-chevron-up"></i>
                        <span>Collapse</span>
                    </button>
                </div>
                
                <div id="filterContent">
                    <div class="filter-controls">
                        <!-- Board Exam Type Filter -->
                        <div class="filter-group">
                            <label>
                                <i class="fas fa-graduation-cap"></i>
                                Board Exam Types
                            </label>
                            <div class="multi-select-container">
                                <div class="multi-select-display" onclick="toggleDropdown('examTypesDropdown')" id="examTypesDisplay">
                                    <span class="multi-select-placeholder">Select exam types...</span>
                                </div>
                                <div class="multi-select-dropdown" id="examTypesDropdown">
                                    <!-- Options will be populated dynamically -->
                                </div>
                            </div>
                        </div>

                        <!-- Exam Date Filter -->
                        <div class="filter-group">
                            <label>
                                <i class="fas fa-calendar-alt"></i>
                                Exam Dates
                            </label>
                            <div class="multi-select-container">
                                <div class="multi-select-display" onclick="toggleDropdown('examDatesDropdown')" id="examDatesDisplay">
                                    <span class="multi-select-placeholder">Select exam dates...</span>
                                </div>
                                <div class="multi-select-dropdown" id="examDatesDropdown">
                                    <!-- Options will be populated dynamically -->
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="filter-actions">
                        <button class="filter-btn primary" onclick="applyFilters()">
                            <i class="fas fa-check"></i>
                            Apply Filters
                        </button>
                        <button class="filter-btn secondary" onclick="clearFilters()">
                            <i class="fas fa-times"></i>
                            Clear All
                        </button>
                    </div>

                    <div class="active-filters" id="activeFilters">
                        <div class="active-filters-text">
                            <i class="fas fa-info-circle"></i> Active Filters:
                        </div>
                        <div class="active-filters-list" id="activeFiltersList"></div>
                    </div>
                </div>
            </div>

            <div class="charts-grid">
                <!-- Results Distribution Pie Chart -->
                <div class="chart-card">
                    <h3>
                        <i class="fas fa-chart-pie"></i> Results Distribution
                        <button class="info-btn" onclick="showChartInfo('resultsChart')" title="Learn more about this chart">
                            <i class="fas fa-info-circle"></i>
                        </button>
                    </h3>
                    <div class="chart-container small">
                        <canvas id="resultsChart"></canvas>
                    </div>
                </div>

                <!-- Exam Type Distribution -->
                <div class="chart-card">
                    <h3>
                        <i class="fas fa-user-graduate"></i> Take Attempt
                        <button class="info-btn" onclick="showChartInfo('examTypeChart')" title="Learn more about this chart">
                            <i class="fas fa-info-circle"></i>
                        </button>
                    </h3>
                    <div class="chart-container small">
                        <canvas id="examTypeChart"></canvas>
                    </div>
                </div>

                <!-- Passing Rate by Board Exam Type -->
                <div class="chart-card full-width">
                    <h3>
                        <i class="fas fa-graduation-cap"></i> Overall Passing Rate by Board Exam Type 2021-2024
                        <button class="info-btn" onclick="showChartInfo('passingRateChart')" title="Learn more about this chart">
                            <i class="fas fa-info-circle"></i>
                        </button>
                    </h3>
                    <div class="chart-container">
                        <canvas id="passingRateChart"></canvas>
                    </div>
                </div>

                <!-- Trend Over Time -->
                <div class="chart-card full-width">
                    <h3>
                        <i class="fas fa-chart-line"></i> Exam Results Trend Over Time
                        <button class="info-btn" onclick="showChartInfo('trendChart')" title="Learn more about this chart">
                            <i class="fas fa-info-circle"></i>
                        </button>
                    </h3>
                    <div class="chart-container">
                        <canvas id="trendChart"></canvas>
                    </div>
                </div>

                <!-- Comparison: First Timers vs Repeaters -->
                <div class="chart-card">
                    <h3>
                        <i class="fas fa-balance-scale"></i> First Timers vs Repeaters Performance
                        <button class="info-btn" onclick="showChartInfo('comparisonChart')" title="Learn more about this chart">
                            <i class="fas fa-info-circle"></i>
                        </button>
                    </h3>
                    <div class="chart-container">
                        <canvas id="comparisonChart"></canvas>
                    </div>
                </div>

                <!-- Results by Exam Date -->
                <div class="chart-card">
                    <h3>
                        <i class="fas fa-calendar-alt"></i> Results by Exam Date
                        <button class="info-btn" onclick="showChartInfo('examDateChart')" title="Learn more about this chart">
                            <i class="fas fa-info-circle"></i>
                        </button>
                    </h3>
                    <div class="chart-container">
                        <canvas id="examDateChart"></canvas>
                    </div>
                </div>

                <!-- Performance Trends by Year -->
                <div class="chart-card full-width">
                    <h3>
                        <i class="fas fa-calendar-year"></i> Performance Trends by Year
                        <button class="info-btn" onclick="showChartInfo('yearlyTrendChart')" title="Learn more about this chart">
                            <i class="fas fa-info-circle"></i>
                        </button>
                    </h3>
                    <div class="chart-container">
                        <canvas id="yearlyTrendChart"></canvas>
                    </div>
                </div>

                <!-- Radar Chart for Exam Type Performance -->
                <div class="chart-card full-width">
                    <h3>
                        <i class="fas fa-radar"></i> Multi-Dimensional Exam Type Analysis
                        <button class="info-btn" onclick="showChartInfo('radarChart')" title="Learn more about this chart">
                            <i class="fas fa-info-circle"></i>
                        </button>
                    </h3>
                    <div class="chart-container">
                        <canvas id="radarChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function confirmLogout(event) {
            event.preventDefault();
            const modal = document.getElementById('logoutModal');
            if (modal) {
                modal.style.display = 'flex';
                modal.classList.add('show');
                
                const yesBtn = document.getElementById('logoutConfirmYes');
                const noBtn = document.getElementById('logoutConfirmNo');
                
                if (yesBtn) {
                    yesBtn.onclick = function(e) {
                        e.preventDefault();
                        handleInteractiveLogout(this);
                    };
                }
                
                if (noBtn) {
                    noBtn.onclick = function() {
                        modal.style.display = 'none';
                    };
                }
            }
            return false;
        }

        function handleInteractiveLogout(button) {
            if (button.classList.contains('loading')) return;
            button.classList.add('loading');

            const cancelBtn = document.getElementById('logoutConfirmNo');
            if (cancelBtn) {
                cancelBtn.style.opacity = '0.5';
                cancelBtn.style.pointerEvents = 'none';
            }

            setTimeout(() => {
                button.classList.remove('loading');
                button.classList.add('success');
                showLogoutSuccessMessage();
                setTimeout(() => {
                    window.location.href = 'logout.php';
                }, 1500);
            }, 2000);
        }

        function showLogoutSuccessMessage() {
            const messageDiv = document.createElement('div');
            messageDiv.innerHTML = `
            <div style="
              position: fixed;
              top: 50%;
              left: 50%;
              transform: translate(-50%, -50%);
              background: linear-gradient(135deg, #D32F2F 0%, #C62828 100%);
              color: white;
              padding: 20px 32px;
              border-radius: 16px;
              box-shadow: 0 16px 40px rgba(211, 47, 47, 0.4);
              z-index: 10002;
              font-family: 'Inter', sans-serif;
              font-weight: 700;
              text-align: center;
              min-width: 300px;
              animation: successSlideIn 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
            ">
              <div style="display: flex; align-items: center; justify-content: center; gap: 12px; font-size: 1.1rem;">
                <i class="fas fa-check-circle" style="font-size: 1.3rem; animation: successCheckBounce 0.8s cubic-bezier(0.68, -0.55, 0.265, 1.55) 0.3s both;"></i>
                Logout Successful!
              </div>
              <div style="font-size: 0.9rem; font-weight: 500; margin-top: 8px; opacity: 0.9;">
                Redirecting to login page...
              </div>
            </div>
            <style>
              @keyframes successSlideIn {
                0% { opacity: 0; transform: translate(-50%, -50%) scale(0.8) translateY(20px); }
                100% { opacity: 1; transform: translate(-50%, -50%) scale(1) translateY(0); }
              }
              @keyframes successCheckBounce {
                0% { transform: scale(0) rotate(-180deg); }
                70% { transform: scale(1.2) rotate(10deg); }
                100% { transform: scale(1) rotate(0deg); }
              }
            </style>
          `;
            document.body.appendChild(messageDiv);
            setTimeout(() => {
                document.body.removeChild(messageDiv);
            }, 3000);
        }

        // Check if Chart.js is loaded
        if (typeof Chart === 'undefined') {
            console.error('Chart.js is not loaded!');
            document.getElementById('contentArea').innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-exclamation-triangle"></i>
                    <h3>Error Loading Chart Library</h3>
                    <p>Please refresh the page or check your internet connection.</p>
                </div>
            `;
        }

        // Global filter state
        let selectedExamTypes = [];
        let selectedExamDates = [];
        let allData = null;
        let availableExamTypes = [];
        let availableExamDates = [];

        // Format date helper function
        function formatDate(dateStr) {
            const [year, month, day] = dateStr.split('-');
            const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
                              'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            return `${monthNames[parseInt(month) - 1]} ${year}`;
        }

        // Toggle filter section
        function toggleFilterSection() {
            const content = document.getElementById('filterContent');
            const btn = document.getElementById('toggleFilterBtn');
            const icon = btn.querySelector('i');
            const text = btn.querySelector('span');
            
            if (content.style.display === 'none') {
                content.style.display = 'block';
                icon.className = 'fas fa-chevron-up';
                text.textContent = 'Collapse';
            } else {
                content.style.display = 'none';
                icon.className = 'fas fa-chevron-down';
                text.textContent = 'Expand';
            }
        }

        // Toggle dropdown
        function toggleDropdown(dropdownId) {
            const dropdown = document.getElementById(dropdownId);
            const allDropdowns = document.querySelectorAll('.multi-select-dropdown');
            
            allDropdowns.forEach(d => {
                if (d.id !== dropdownId) {
                    d.classList.remove('show');
                }
            });
            
            dropdown.classList.toggle('show');
            
            // Add active state to display
            const display = dropdown.previousElementSibling;
            if (dropdown.classList.contains('show')) {
                display.classList.add('active');
            } else {
                display.classList.remove('active');
            }
        }

        // Close dropdowns when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.multi-select-container')) {
                document.querySelectorAll('.multi-select-dropdown').forEach(d => {
                    d.classList.remove('show');
                });
                document.querySelectorAll('.multi-select-display').forEach(d => {
                    d.classList.remove('active');
                });
            }
        });

        // Populate filter options
        function populateFilterOptions(data) {
            // Get unique exam types
            if (data.by_exam_type) {
                availableExamTypes = Object.keys(data.by_exam_type).sort();
            }
            
            // Get unique exam dates
            if (data.by_date) {
                availableExamDates = Object.keys(data.by_date).sort((a, b) => new Date(b) - new Date(a));
            }

            // Populate exam types dropdown
            const examTypesDropdown = document.getElementById('examTypesDropdown');
            examTypesDropdown.innerHTML = availableExamTypes.map(type => `
                <div class="multi-select-option" onclick="toggleExamType('${type}', event)">
                    <input type="checkbox" id="exam_${type.replace(/\s+/g, '_')}" onclick="event.stopPropagation()">
                    <label for="exam_${type.replace(/\s+/g, '_')}" style="cursor: pointer; flex: 1; margin: 0;">${type}</label>
                </div>
            `).join('');

            // Populate exam dates dropdown
            const examDatesDropdown = document.getElementById('examDatesDropdown');
            examDatesDropdown.innerHTML = availableExamDates.map(date => `
                <div class="multi-select-option" onclick="toggleExamDate('${date}', event)">
                    <input type="checkbox" id="date_${date.replace(/\s+/g, '_')}" onclick="event.stopPropagation()">
                    <label for="date_${date.replace(/\s+/g, '_')}" style="cursor: pointer; flex: 1; margin: 0;">${formatDate(date)}</label>
                </div>
            `).join('');
        }

        // Toggle exam type selection
        function toggleExamType(type, event) {
            event.stopPropagation();
            const checkbox = document.getElementById(`exam_${type.replace(/\s+/g, '_')}`);
            checkbox.checked = !checkbox.checked;
            
            const index = selectedExamTypes.indexOf(type);
            if (checkbox.checked && index === -1) {
                selectedExamTypes.push(type);
            } else if (!checkbox.checked && index > -1) {
                selectedExamTypes.splice(index, 1);
            }
            
            updateExamTypesDisplay();
        }

        // Toggle exam date selection
        function toggleExamDate(date, event) {
            event.stopPropagation();
            const checkbox = document.getElementById(`date_${date.replace(/\s+/g, '_')}`);
            checkbox.checked = !checkbox.checked;
            
            const index = selectedExamDates.indexOf(date);
            if (checkbox.checked && index === -1) {
                selectedExamDates.push(date);
            } else if (!checkbox.checked && index > -1) {
                selectedExamDates.splice(index, 1);
            }
            
            updateExamDatesDisplay();
        }

        // Update exam types display
        function updateExamTypesDisplay() {
            const display = document.getElementById('examTypesDisplay');
            
            if (selectedExamTypes.length === 0) {
                display.innerHTML = '<span class="multi-select-placeholder">Select exam types...</span>';
            } else {
                display.innerHTML = selectedExamTypes.map(type => `
                    <span class="selected-tag">
                        ${type}
                        <i class="fas fa-times" onclick="removeExamType('${type}', event)"></i>
                    </span>
                `).join('');
            }
        }

        // Update exam dates display
        function updateExamDatesDisplay() {
            const display = document.getElementById('examDatesDisplay');
            
            if (selectedExamDates.length === 0) {
                display.innerHTML = '<span class="multi-select-placeholder">Select exam dates...</span>';
            } else {
                display.innerHTML = selectedExamDates.map(date => `
                    <span class="selected-tag">
                        ${formatDate(date)}
                        <i class="fas fa-times" onclick="removeExamDate('${date}', event)"></i>
                    </span>
                `).join('');
            }
        }

        // Remove exam type
        function removeExamType(type, event) {
            event.stopPropagation();
            const index = selectedExamTypes.indexOf(type);
            if (index > -1) {
                selectedExamTypes.splice(index, 1);
                const checkbox = document.getElementById(`exam_${type.replace(/\s+/g, '_')}`);
                if (checkbox) checkbox.checked = false;
                updateExamTypesDisplay();
            }
        }

        // Remove exam date
        function removeExamDate(date, event) {
            event.stopPropagation();
            const index = selectedExamDates.indexOf(date);
            if (index > -1) {
                selectedExamDates.splice(index, 1);
                const checkbox = document.getElementById(`date_${date.replace(/\s+/g, '_')}`);
                if (checkbox) checkbox.checked = false;
                updateExamDatesDisplay();
            }
        }

        // Apply filters
        function applyFilters() {
            if (!allData) {
                alert('No data available to filter');
                return;
            }

            // Check if at least one filter is selected
            if (selectedExamTypes.length === 0 && selectedExamDates.length === 0) {
                alert('Please select at least one filter option');
                return;
            }

            // Show active filters
            updateActiveFilters();

            // Filter the data
            const filteredData = filterData(allData);

            // Check if filtered data has results
            if (filteredData.total === 0) {
                alert('No data matches the selected filters. Please try different filter options.');
                return;
            }

            // Re-render charts with filtered data
            renderAllCharts(filteredData);

            // Show success message
            showSuccessMessage('Filters applied successfully!');
        }

        // Clear all filters
        function clearFilters() {
            selectedExamTypes = [];
            selectedExamDates = [];
            
            // Uncheck all checkboxes
            document.querySelectorAll('.multi-select-option input[type="checkbox"]').forEach(cb => {
                cb.checked = false;
            });
            
            updateExamTypesDisplay();
            updateExamDatesDisplay();
            
            // Hide active filters
            document.getElementById('activeFilters').classList.remove('show');
            
            // Reload original data
            if (allData) {
                renderAllCharts(allData);
            }
            
            showSuccessMessage('Filters cleared!');
        }

        // Update active filters display
        function updateActiveFilters() {
            const activeFilters = document.getElementById('activeFilters');
            const activeFiltersList = document.getElementById('activeFiltersList');
            
            if (selectedExamTypes.length === 0 && selectedExamDates.length === 0) {
                activeFilters.classList.remove('show');
                return;
            }
            
            let filtersHTML = '';
            
            if (selectedExamTypes.length > 0) {
                filtersHTML += selectedExamTypes.map(type => `
                    <span class="selected-tag">${type}</span>
                `).join('');
            }
            
            if (selectedExamDates.length > 0) {
                filtersHTML += selectedExamDates.map(date => `
                    <span class="selected-tag">${formatDate(date)}</span>
                `).join('');
            }
            
            activeFiltersList.innerHTML = filtersHTML;
            activeFilters.classList.add('show');
        }

        // Filter data based on selections
        function filterData(data) {
            let filtered = JSON.parse(JSON.stringify(data)); // Deep copy
            
            // Filter by exam types
            if (selectedExamTypes.length > 0) {
                if (filtered.by_exam_type) {
                    const newByExamType = {};
                    selectedExamTypes.forEach(type => {
                        if (filtered.by_exam_type[type]) {
                            newByExamType[type] = filtered.by_exam_type[type];
                        }
                    });
                    filtered.by_exam_type = newByExamType;
                }
            }
            
            // Filter by exam dates
            if (selectedExamDates.length > 0) {
                if (filtered.by_date) {
                    const newByDate = {};
                    selectedExamDates.forEach(date => {
                        if (filtered.by_date[date]) {
                            newByDate[date] = filtered.by_date[date];
                        }
                    });
                    filtered.by_date = newByDate;
                }
                
                if (filtered.by_year) {
                    // Recalculate by_year based on filtered dates
                    const newByYear = {};
                    selectedExamDates.forEach(date => {
                        const year = date.split('-')[0];
                        const dateData = data.by_date[date];
                        if (!newByYear[year]) {
                            newByYear[year] = { passed: 0, failed: 0, conditional: 0, total: 0 };
                        }
                        newByYear[year].passed += dateData.passed || 0;
                        newByYear[year].failed += dateData.failed || 0;
                        newByYear[year].conditional += dateData.conditional || 0;
                        newByYear[year].total += dateData.total || 0;
                    });
                    filtered.by_year = newByYear;
                }
            }
            
            // Recalculate totals
            filtered.passed = 0;
            filtered.failed = 0;
            filtered.conditional = 0;
            filtered.total = 0;
            
            if (filtered.by_date) {
                Object.values(filtered.by_date).forEach(dateData => {
                    filtered.passed += dateData.passed || 0;
                    filtered.failed += dateData.failed || 0;
                    filtered.conditional += dateData.conditional || 0;
                    filtered.total += dateData.total || 0;
                });
            }
            
            return filtered;
        }

        // Render all charts
        function renderAllCharts(data) {
            // Destroy existing charts
            Chart.helpers.each(Chart.instances, function(instance) {
                instance.destroy();
            });
            
            try {
                renderResultsChart(data);
                renderExamTypeChart(data);
                renderPassingRateChart(data);
                renderTrendChart(data);
                renderComparisonChart(data);
                renderExamDateChart(data);
                renderYearlyTrendChart(data);
                renderRadarChart(data);
            } catch (e) {
                console.error('Error rendering charts:', e);
            }
        }

        // Fetch data and render charts
        async function loadStatistics() {
            try {
                console.log('Starting to load statistics...');
                const response = await fetch('stats_anonymous_ccje.php');
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const text = await response.text();
                console.log('Raw response:', text);
                
                let data;
                try {
                    data = JSON.parse(text);
                } catch (parseError) {
                    console.error('JSON parse error:', parseError);
                    throw new Error('Invalid JSON response: ' + text.substring(0, 100));
                }
                
                console.log('Statistics data:', data); // Debug log

                if (data.error) {
                    console.error('API Error:', data.error);
                    throw new Error(data.error);
                }

                // Always render charts, even with zero data - they will show empty state gracefully
                // Initialize data structure if it doesn't exist
                if (!data.total || data.total === 0) {
                    data = {
                        total: 0,
                        passed: 0,
                        failed: 0,
                        conditional: 0,
                        first_timer: 0,
                        repeater: 0,
                        first_timer_passed: 0,
                        repeater_passed: 0,
                        by_exam_type: {},
                        by_date: {},
                        by_year: {}
                    };
                }

                try {
                    renderResultsChart(data);
                    console.log('Results chart rendered');
                } catch (e) {
                    console.error('Error rendering results chart:', e);
                }
                
                try {
                    renderExamTypeChart(data);
                    console.log('Exam type chart rendered');
                } catch (e) {
                    console.error('Error rendering exam type chart:', e);
                }
                
                try {
                    renderPassingRateChart(data);
                    console.log('Passing rate chart rendered');
                } catch (e) {
                    console.error('Error rendering passing rate chart:', e);
                }
                
                try {
                    renderTrendChart(data);
                    console.log('Trend chart rendered');
                } catch (e) {
                    console.error('Error rendering trend chart:', e);
                }
                
                try {
                    renderComparisonChart(data);
                    console.log('Comparison chart rendered');
                } catch (e) {
                    console.error('Error rendering comparison chart:', e);
                }
                
                try {
                    renderExamDateChart(data);
                    console.log('Exam date chart rendered');
                } catch (e) {
                    console.error('Error rendering exam date chart:', e);
                }
                
                try {
                    renderYearlyTrendChart(data);
                    console.log('Yearly trend chart rendered');
                } catch (e) {
                    console.error('Error rendering yearly trend chart:', e);
                }
                
                try {
                    renderRadarChart(data);
                    console.log('Radar chart rendered');
                } catch (e) {
                    console.error('Error rendering radar chart:', e);
                }
                
                // Store data globally for filtering
                allData = data;
                
                // Populate filter options
                populateFilterOptions(data);
                
                console.log('All charts rendered successfully');
            } catch (error) {
                console.error('Error loading statistics:', error);
                document.getElementById('contentArea').innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-exclamation-triangle"></i>
                        <h3>Error Loading Statistics</h3>
                        <p>${error.message}</p>
                        <p>Please check the browser console for more details.</p>
                    </div>
                `;
            }
        }

        function renderResultsChart(data) {
            const ctx = document.getElementById('resultsChart').getContext('2d');
            const hasData = data.total > 0;
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: hasData ? ['Passed', 'Failed', 'Conditional'] : ['No Data Available'],
                    datasets: [{
                        data: hasData ? [data.passed, data.failed, data.conditional] : [1],
                        backgroundColor: hasData ? ['#D32F2F', '#64748b', '#FAD6A5'] : ['#e2e8f0'],
                        borderWidth: 2,
                        borderColor: '#fff',
                        hoverOffset: 15,
                        hoverBorderWidth: 3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 15,
                                font: { size: 12, weight: 'bold' },
                                usePointStyle: true
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.parsed || 0;
                                    const total = data.total;
                                    const percentage = ((value / total) * 100).toFixed(2);
                                    return `${label}: ${value} (${percentage}%)`;
                                }
                            },
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            padding: 12,
                            titleFont: { size: 14, weight: 'bold' },
                            bodyFont: { size: 13 }
                        }
                    },
                    animation: {
                        animateRotate: true,
                        animateScale: true,
                        duration: 1500,
                        easing: 'easeInOutQuart'
                    }
                }
            });
        }

        function renderExamTypeChart(data) {
            const ctx = document.getElementById('examTypeChart').getContext('2d');
            const hasData = data.total > 0;
            new Chart(ctx, {
                type: 'polarArea',
                data: {
                    labels: hasData ? ['First Timer', 'Repeater'] : ['No Data Available'],
                    datasets: [{
                        data: hasData ? [data.first_timer, data.repeater] : [1],
                        backgroundColor: hasData ? [
                            'rgba(211, 47, 47, 0.75)',
                            'rgba(128, 0, 32, 0.75)'
                        ] : ['rgba(226, 232, 240, 0.75)'],
                        borderWidth: 2,
                        borderColor: '#fff',
                        hoverBorderWidth: 3,
                        hoverBorderColor: '#D32F2F'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 15,
                                font: { size: 12, weight: 'bold' },
                                usePointStyle: true,
                                pointStyle: 'circle'
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.parsed.r || 0;
                                    const total = data.total;
                                    const percentage = ((value / total) * 100).toFixed(2);
                                    return `${label}: ${value} examinees (${percentage}%)`;
                                }
                            },
                            backgroundColor: 'rgba(0, 0, 0, 0.85)',
                            padding: 14,
                            titleFont: { size: 14, weight: 'bold' },
                            bodyFont: { size: 13 },
                            borderColor: '#D32F2F',
                            borderWidth: 2
                        }
                    },
                    scales: {
                        r: {
                            beginAtZero: true,
                            ticks: {
                                backdropColor: 'transparent',
                                font: { size: 10 }
                            },
                            grid: {
                                color: 'rgba(211, 47, 47, 0.1)',
                                drawBorder: false
                            }
                        }
                    },
                    animation: {
                        animateRotate: true,
                        animateScale: true,
                        duration: 1800,
                        easing: 'easeInOutQuart'
                    }
                }
            });
        }

        function renderPassingRateChart(data) {
            const ctx = document.getElementById('passingRateChart').getContext('2d');
            const hasData = data.by_exam_type && Object.keys(data.by_exam_type).length > 0;
            const examTypes = hasData ? Object.keys(data.by_exam_type) : ['No Data Available'];
            const passingRates = hasData ? examTypes.map(type => {
                const typeData = data.by_exam_type[type];
                return typeData.total > 0 ? ((typeData.passed / typeData.total) * 100).toFixed(2) : 0;
            }) : [0];

            // Color code based on passing rate
            const gradientColors = hasData ? passingRates.map((rate) => {
                if (rate >= 75) return 'rgba(211, 47, 47, 0.85)';  // CCJE red for high
                if (rate >= 50) return 'rgba(250, 214, 165, 0.85)';  // Peach for medium
                return 'rgba(100, 116, 139, 0.85)';  // Slate gray for low
            }) : ['rgba(226, 232, 240, 0.85)'];

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: examTypes,
                    datasets: [{
                        label: 'Passing Rate (%)',
                        data: passingRates,
                        backgroundColor: gradientColors,
                        borderColor: gradientColors.map(color => color.replace('0.85', '1')),
                        borderWidth: 2,
                        borderRadius: 8,
                        hoverBackgroundColor: gradientColors.map(color => color.replace('0.85', '1')),
                        hoverBorderColor: '#fff',
                        hoverBorderWidth: 3
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    onClick: (e, activeElements) => {
                        if (activeElements.length > 0) {
                            const index = activeElements[0].index;
                            const examType = examTypes[index];
                            const typeData = data.by_exam_type[examType];
                            showExamTypeDetails(examType, typeData);
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            max: 100,
                            ticks: {
                                callback: function(value) {
                                    return value + '%';
                                },
                                font: { size: 11, weight: '600' }
                            },
                            grid: {
                                color: 'rgba(211, 47, 47, 0.15)',
                                drawBorder: false
                            }
                        },
                        y: {
                            ticks: {
                                font: { size: 11, weight: '600' },
                                color: '#334155'
                            },
                            grid: {
                                display: false
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const examType = context.label;
                                    const typeData = data.by_exam_type[examType];
                                    return [
                                        `Passing Rate: ${context.parsed.x}%`,
                                        `Passed: ${typeData.passed}`,
                                        `Failed: ${typeData.failed}`,
                                        `Total: ${typeData.total}`
                                    ];
                                }
                            },
                            backgroundColor: 'rgba(0, 0, 0, 0.85)',
                            padding: 14,
                            titleFont: { size: 14, weight: 'bold' },
                            bodyFont: { size: 12 },
                            borderColor: '#D32F2F',
                            borderWidth: 2
                        }
                    },
                    animation: {
                        duration: 1800,
                        easing: 'easeInOutQuart'
                    }
                }
            });
        }

        function renderTrendChart(data) {
            const ctx = document.getElementById('trendChart').getContext('2d');
            
            // Group data by year
            const yearlyTrend = {};
            const examDatesByYear = {};
            
            const hasDateData = data.by_date && Object.keys(data.by_date).length > 0;
            if (hasDateData) {
                Object.keys(data.by_date).forEach(date => {
                const year = date.split('-')[0];
                const dateData = data.by_date[date];
                
                if (!yearlyTrend[year]) {
                    yearlyTrend[year] = {
                        passed: 0,
                        failed: 0,
                        conditional: 0,
                        total: 0
                    };
                    examDatesByYear[year] = [];
                }
                
                yearlyTrend[year].passed += dateData.passed || 0;
                yearlyTrend[year].failed += dateData.failed || 0;
                yearlyTrend[year].conditional += dateData.conditional || 0;
                yearlyTrend[year].total += dateData.total || 0;
                examDatesByYear[year].push(date);
            });
            }
            
            const years = Object.keys(yearlyTrend).length > 0 ? Object.keys(yearlyTrend).sort() : ['No Data'];
            const passedData = years[0] !== 'No Data' ? years.map(year => yearlyTrend[year].passed) : [0];
            const failedData = years[0] !== 'No Data' ? years.map(year => yearlyTrend[year].failed) : [0];
            const conditionalData = years[0] !== 'No Data' ? years.map(year => yearlyTrend[year].conditional) : [0];

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: years,
                    datasets: [
                        {
                            label: 'Passed',
                            data: passedData,
                            borderColor: '#D32F2F',
                            backgroundColor: 'rgba(211, 47, 47, 0.2)',
                            tension: 0.4,
                            fill: true,
                            borderWidth: 3,
                            pointRadius: 6,
                            pointHoverRadius: 9,
                            pointBackgroundColor: '#D32F2F',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            pointHoverBorderWidth: 3
                        },
                        {
                            label: 'Failed',
                            data: failedData,
                            borderColor: '#64748b',
                            backgroundColor: 'rgba(100, 116, 139, 0.2)',
                            tension: 0.4,
                            fill: true,
                            borderWidth: 3,
                            pointRadius: 6,
                            pointHoverRadius: 9,
                            pointBackgroundColor: '#64748b',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            pointHoverBorderWidth: 3
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    onClick: (e, activeElements) => {
                        if (activeElements.length > 0) {
                            const index = activeElements[0].index;
                            const year = years[index];
                            showYearTrendDetails(year, yearlyTrend[year], examDatesByYear[year]);
                        }
                    },
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 15,
                                font: { size: 12, weight: 'bold' },
                                usePointStyle: true,
                                generateLabels: function(chart) {
                                    const datasets = chart.data.datasets;
                                    return datasets.map((dataset, i) => ({
                                        text: dataset.label,
                                        fillStyle: dataset.borderColor,
                                        strokeStyle: dataset.borderColor,
                                        lineWidth: 2,
                                        hidden: !chart.isDatasetVisible(i),
                                        index: i,
                                        pointStyle: 'circle'
                                    }));
                                }
                            },
                            onClick: (e, legendItem, legend) => {
                                const index = legendItem.index;
                                const chart = legend.chart;
                                const meta = chart.getDatasetMeta(index);
                                meta.hidden = !meta.hidden;
                                chart.update();
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.9)',
                            padding: 15,
                            titleFont: { size: 15, weight: 'bold' },
                            bodyFont: { size: 13 },
                            footerFont: { size: 12, weight: '600' },
                            callbacks: {
                                title: function(context) {
                                    return `Year ${context[0].label}`;
                                },
                                afterBody: function(context) {
                                    const year = context[0].label;
                                    const yearData = yearlyTrend[year];
                                    const examDates = examDatesByYear[year];
                                    const passingRate = ((yearData.passed / yearData.total) * 100).toFixed(2);
                                    
                                    return `\nTotal Examinees: ${yearData.total}\nPassing Rate: ${passingRate}%\nConditional: ${yearData.conditional}`;
                                },
                                footer: function(context) {
                                    const year = context[0].label;
                                    const examDates = examDatesByYear[year].sort();
                                    
                                    // Format dates to Month Year
                                    const formatDate = (dateStr) => {
                                        const [year, month, day] = dateStr.split('-');
                                        const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
                                                          'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                                        return `${monthNames[parseInt(month) - 1]} ${year}`;
                                    };
                                    
                                    const formattedDates = examDates.map(date => formatDate(date));
                                    return `\nExam Dates (${examDates.length}):\n${formattedDates.join('\n')}`;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                font: { size: 11 }
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            }
                        },
                        x: {
                            ticks: {
                                font: { size: 12, weight: '600' }
                            },
                            grid: {
                                display: false
                            }
                        }
                    },
                    animation: {
                        duration: 1500,
                        easing: 'easeInOutQuart'
                    }
                }
            });
        }
        
        function showYearTrendDetails(year, yearData, examDates) {
            const passingRate = ((yearData.passed / yearData.total) * 100).toFixed(2);
            const failRate = ((yearData.failed / yearData.total) * 100).toFixed(2);
            const conditionalRate = ((yearData.conditional / yearData.total) * 100).toFixed(2);
            
            // Format dates to Month Year
            const formatDate = (dateStr) => {
                const [year, month, day] = dateStr.split('-');
                const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
                                  'July', 'August', 'September', 'October', 'November', 'December'];
                return `${monthNames[parseInt(month) - 1]} ${year}`;
            };
            
            const modal = document.createElement('div');
            modal.style.cssText = `
                position: fixed; top: 0; left: 0; right: 0; bottom: 0;
                background: rgba(0,0,0,0.6); z-index: 10000;
                display: flex; align-items: center; justify-content: center;
                padding: 20px;
            `;
            modal.innerHTML = `
                <div style="background: white; border-radius: 20px; padding: 40px; max-width: 700px; max-height: 90vh; overflow-y: auto; box-shadow: 0 25px 80px rgba(0,0,0,0.4);">
                    <h2 style="margin: 0 0 24px 0; font-size: 1.8rem; color: #0f1724; display: flex; align-items: center; gap: 12px;">
                        <i class="fas fa-chart-line" style="color: #91b38e;"></i> Year ${year} Exam Trend
                    </h2>
                    
                    <div style="margin-bottom: 24px; padding: 20px; background: linear-gradient(135deg, #f0f9f0 0%, #e8f5e8 100%); border-radius: 12px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 12px; padding-bottom: 12px; border-bottom: 2px solid rgba(145, 179, 142, 0.3);">
                            <span style="font-weight: 700; font-size: 1.2rem;">Total Examinees:</span>
                            <span style="font-weight: 800; font-size: 1.3rem; color: #91b38e;">${yearData.total}</span>
                        </div>
                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-top: 16px;">
                            <div style="background: white; padding: 16px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
                                <div style="color: #64748b; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px;">PASSED</div>
                                <div style="font-weight: 800; font-size: 1.5rem; color: #91b38e;">${yearData.passed}</div>
                                <div style="font-size: 0.9rem; color: #91b38e; font-weight: 600;">${passingRate}%</div>
                            </div>
                            <div style="background: white; padding: 16px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
                                <div style="color: #64748b; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px;">FAILED</div>
                                <div style="font-weight: 800; font-size: 1.5rem; color: #64748b;">${yearData.failed}</div>
                                <div style="font-size: 0.9rem; color: #64748b; font-weight: 600;">${failRate}%</div>
                            </div>
                            <div style="background: white; padding: 16px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
                                <div style="color: #64748b; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px;">CONDITIONAL</div>
                                <div style="font-weight: 800; font-size: 1.5rem; color: #a8c5a5;">${yearData.conditional}</div>
                                <div style="font-size: 0.9rem; color: #a8c5a5; font-weight: 600;">${conditionalRate}%</div>
                            </div>
                        </div>
                    </div>
                    
                    <div style="margin-bottom: 24px;">
                        <h3 style="margin: 0 0 16px 0; font-size: 1.1rem; color: #0f1724; display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-calendar-alt" style="color: #91b38e;"></i> Exam Dates (${examDates.length})
                        </h3>
                        <div style="display: grid; gap: 8px; max-height: 200px; overflow-y: auto; padding: 12px; background: #f8fafc; border-radius: 10px;">
                            ${examDates.sort().map(date => `
                                <div style="background: white; padding: 10px 14px; border-radius: 8px; border-left: 3px solid #91b38e; font-size: 0.95rem; font-weight: 600; color: #0f1724;">
                                    ${formatDate(date)}
                                </div>
                            `).join('')}
                        </div>
                    </div>
                    
                    <div style="padding: 20px; background: linear-gradient(135deg, #91b38e 0%, #5a855f 100%); border-radius: 12px; color: white; margin-bottom: 20px;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-weight: 700; font-size: 1.2rem;">Overall Passing Rate</span>
                            <span style="font-weight: 900; font-size: 2rem;">${passingRate}%</span>
                        </div>
                    </div>
                    
                    <button onclick="this.closest('div[style*=fixed]').remove()" 
                        style="width: 100%; padding: 14px; background: #91b38e; color: white; border: none; border-radius: 12px; font-weight: 700; cursor: pointer; font-size: 1.05rem; transition: all 0.3s;"
                        onmouseover="this.style.background='#5a855f'; this.style.transform='translateY(-2px)'"
                        onmouseout="this.style.background='#91b38e'; this.style.transform='translateY(0)'">
                        <i class="fas fa-times-circle"></i> Close
                    </button>
                </div>
            `;
            document.body.appendChild(modal);
            modal.onclick = (e) => { if (e.target === modal) modal.remove(); };
        }

        function renderComparisonChart(data) {
            const ctx = document.getElementById('comparisonChart').getContext('2d');
            const hasData = data.total > 0;
            const firstTimerRate = data.first_timer > 0 ? ((data.first_timer_passed / data.first_timer) * 100).toFixed(2) : 0;
            const repeaterRate = data.repeater > 0 ? ((data.repeater_passed / data.repeater) * 100).toFixed(2) : 0;

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: hasData ? ['First Timers', 'Repeaters'] : ['No Data'],
                    datasets: [
                        {
                            label: 'Passed',
                            data: hasData ? [data.first_timer_passed, data.repeater_passed] : [0],
                            backgroundColor: ['rgba(211, 47, 47, 0.85)', 'rgba(128, 0, 32, 0.85)'],
                            borderColor: ['#D32F2F', '#800020'],
                            borderWidth: 2,
                            borderRadius: 8,
                            hoverBackgroundColor: ['#D32F2F', '#800020'],
                            hoverBorderWidth: 3,
                            hoverBorderColor: '#fff'
                        },
                        {
                            label: 'Failed',
                            data: hasData ? [data.first_timer - data.first_timer_passed, data.repeater - data.repeater_passed] : [0],
                            backgroundColor: ['rgba(100, 116, 139, 0.85)', 'rgba(71, 85, 105, 0.85)'],
                            borderColor: ['#64748b', '#475569'],
                            borderWidth: 2,
                            borderRadius: 8,
                            hoverBackgroundColor: ['#64748b', '#475569'],
                            hoverBorderWidth: 3,
                            hoverBorderColor: '#fff'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 15,
                                font: { size: 13, weight: 'bold' },
                                usePointStyle: true,
                                pointStyle: 'circle'
                            }
                        },
                        tooltip: {
                            callbacks: {
                                footer: function(context) {
                                    const index = context[0].dataIndex;
                                    const rate = index === 0 ? firstTimerRate : repeaterRate;
                                    const total = index === 0 ? data.first_timer : data.repeater;
                                    return `\nTotal: ${total}\nPassing Rate: ${rate}%`;
                                }
                            },
                            backgroundColor: 'rgba(0, 0, 0, 0.85)',
                            padding: 14,
                            titleFont: { size: 14, weight: 'bold' },
                            bodyFont: { size: 13 },
                            footerFont: { size: 12 },
                            borderColor: '#91b38e',
                            borderWidth: 2
                        }
                    },
                    scales: {
                        x: {
                            stacked: true,
                            ticks: {
                                font: { size: 11, weight: '600' }
                            },
                            grid: {
                                display: false
                            }
                        },
                        y: {
                            stacked: true,
                            beginAtZero: true,
                            ticks: {
                                font: { size: 11 }
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            }
                        }
                    },
                    animation: {
                        duration: 1500,
                        easing: 'easeInOutQuart'
                    }
                }
            });
        }

        function renderExamDateChart(data) {
            const ctx = document.getElementById('examDateChart').getContext('2d');
            const hasData = data.by_date && Object.keys(data.by_date).length > 0;
            const dates = hasData ? Object.keys(data.by_date).sort() : ['No Data'];
            const totals = hasData ? dates.map(date => data.by_date[date].total || 0) : [0];
            const passingRates = hasData ? dates.map(date => {
                const dateData = data.by_date[date];
                return dateData.total > 0 ? ((dateData.passed / dateData.total) * 100).toFixed(2) : 0;
            }) : [0];

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: dates,
                    datasets: [{
                        label: 'Total Examinees',
                        data: totals,
                        backgroundColor: totals.map((val, i) => {
                            const rate = parseFloat(passingRates[i]);
                            if (rate >= 70) return '#D32F2F';  // CCJE red for high passing rate
                            if (rate >= 50) return '#FAD6A5';  // Peach for medium passing rate
                            return '#64748b';  // Slate gray for low passing rate
                        }),
                        borderColor: '#5a855f',
                        borderWidth: 2,
                        borderRadius: 8,
                        hoverBorderWidth: 3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    onClick: (e, activeElements) => {
                        if (activeElements.length > 0) {
                            const index = activeElements[0].index;
                            const date = dates[index];
                            const dateData = data.by_date[date];
                            showDateDetails(date, dateData);
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const date = context.label;
                                    const dateData = data.by_date[date];
                                    const passingRate = ((dateData.passed / dateData.total) * 100).toFixed(2);
                                    return [
                                        `Total: ${context.parsed.y}`,
                                        `Passed: ${dateData.passed}`,
                                        `Failed: ${dateData.failed}`,
                                        `Passing Rate: ${passingRate}%`
                                    ];
                                }
                            },
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            padding: 12,
                            titleFont: { size: 14, weight: 'bold' },
                            bodyFont: { size: 13 }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                font: { size: 11 }
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            }
                        },
                        x: {
                            ticks: {
                                font: { size: 11 }
                            },
                            grid: {
                                display: false
                            }
                        }
                    },
                    animation: {
                        duration: 1500,
                        easing: 'easeInOutQuart'
                    }
                }
            });
        }

        function showExamTypeDetails(examType, typeData) {
            const passingRate = ((typeData.passed / typeData.total) * 100).toFixed(2);
            const modal = document.createElement('div');
            modal.style.cssText = `
                position: fixed; top: 0; left: 0; right: 0; bottom: 0;
                background: rgba(0,0,0,0.5); z-index: 10000;
                display: flex; align-items: center; justify-content: center;
            `;
            modal.innerHTML = `
                <div style="background: white; border-radius: 16px; padding: 32px; max-width: 500px; box-shadow: 0 20px 60px rgba(0,0,0,0.3);">
                    <h3 style="margin: 0 0 20px 0; font-size: 1.5rem; color: #0f1724;">${examType}</h3>
                    <div style="margin-bottom: 16px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                            <span style="font-weight: 600;">Total Examinees:</span>
                            <span style="font-weight: 700; color: #91b38e;">${typeData.total}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                            <span style="font-weight: 600;">Passed:</span>
                            <span style="font-weight: 700; color: #D32F2F;">${typeData.passed}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                            <span style="font-weight: 600;">Failed:</span>
                            <span style="font-weight: 700; color: #ef4444;">${typeData.failed}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                            <span style="font-weight: 600;">Conditional:</span>
                            <span style="font-weight: 700; color: #f59e0b;">${typeData.conditional}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-top: 16px; padding-top: 16px; border-top: 2px solid #e5e7eb;">
                            <span style="font-weight: 700; font-size: 1.1rem;">Passing Rate:</span>
                            <span style="font-weight: 800; font-size: 1.2rem; color: #91b38e;">${passingRate}%</span>
                        </div>
                    </div>
                    <button onclick="this.closest('div[style*=fixed]').remove()" 
                        style="width: 100%; padding: 12px; background: #91b38e; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 1rem;">
                        Close
                    </button>
                </div>
            `;
            document.body.appendChild(modal);
            modal.onclick = (e) => { if (e.target === modal) modal.remove(); };
        }

        function showDateDetails(date, dateData) {
            const passingRate = ((dateData.passed / dateData.total) * 100).toFixed(2);
            const modal = document.createElement('div');
            modal.style.cssText = `
                position: fixed; top: 0; left: 0; right: 0; bottom: 0;
                background: rgba(0,0,0,0.5); z-index: 10000;
                display: flex; align-items: center; justify-content: center;
            `;
            modal.innerHTML = `
                <div style="background: white; border-radius: 16px; padding: 32px; max-width: 500px; box-shadow: 0 20px 60px rgba(0,0,0,0.3);">
                    <h3 style="margin: 0 0 20px 0; font-size: 1.5rem; color: #0f1724;">Exam Date: ${date}</h3>
                    <div style="margin-bottom: 16px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                            <span style="font-weight: 600;">Total Examinees:</span>
                            <span style="font-weight: 700; color: #91b38e;">${dateData.total}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                            <span style="font-weight: 600;">Passed:</span>
                            <span style="font-weight: 700; color: #91b38e;">${dateData.passed}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                            <span style="font-weight: 600;">Failed:</span>
                            <span style="font-weight: 700; color: #64748b;">${dateData.failed}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                            <span style="font-weight: 600;">Conditional:</span>
                            <span style="font-weight: 700; color: #a8c5a5;">${dateData.conditional}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-top: 16px; padding-top: 16px; border-top: 2px solid #e5e7eb;">
                            <span style="font-weight: 700; font-size: 1.1rem;">Passing Rate:</span>
                            <span style="font-weight: 800; font-size: 1.2rem; color: #91b38e;">${passingRate}%</span>
                        </div>
                    </div>
                    <button onclick="this.closest('div[style*=fixed]').remove()" 
                        style="width: 100%; padding: 12px; background: #91b38e; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 1rem;">
                        Close
                    </button>
                </div>
            `;
            document.body.appendChild(modal);
            modal.onclick = (e) => { if (e.target === modal) modal.remove(); };
        }

        function renderYearlyTrendChart(data) {
            const ctx = document.getElementById('yearlyTrendChart').getContext('2d');
            
            // Use the by_year data from the API
            const yearlyData = data.by_year || {};
            const hasData = Object.keys(yearlyData).length > 0;
            const years = hasData ? Object.keys(yearlyData).sort() : ['No Data'];
            const totalData = hasData ? years.map(year => yearlyData[year].total) : [0];
            const passedData = hasData ? years.map(year => yearlyData[year].passed) : [0];
            const failedData = hasData ? years.map(year => yearlyData[year].failed) : [0];
            const conditionalData = hasData ? years.map(year => yearlyData[year].conditional) : [0];
            const passingRates = hasData ? years.map(year => {
                const total = yearlyData[year].total;
                return total > 0 ? ((yearlyData[year].passed / total) * 100).toFixed(2) : 0;
            }) : [0];

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: years,
                    datasets: [
                        {
                            label: 'Passed',
                            data: passedData,
                            backgroundColor: '#D32F2F',
                            borderRadius: 6,
                            yAxisID: 'y',
                            order: 2
                        },
                        {
                            label: 'Failed',
                            data: failedData,
                            backgroundColor: '#64748b',
                            borderRadius: 6,
                            yAxisID: 'y',
                            order: 2
                        },
                        {
                            label: 'Conditional',
                            data: conditionalData,
                            backgroundColor: '#FAD6A5',
                            borderRadius: 6,
                            yAxisID: 'y',
                            order: 2
                        },
                        {
                            label: 'Passing Rate (%)',
                            data: passingRates,
                            type: 'line',
                            borderColor: '#800020',
                            backgroundColor: 'rgba(128, 0, 32, 0.1)',
                            borderWidth: 3,
                            pointRadius: 6,
                            pointHoverRadius: 9,
                            pointBackgroundColor: '#800020',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            yAxisID: 'y1',
                            order: 1,
                            tension: 0.4
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    onClick: (e, activeElements) => {
                        if (activeElements.length > 0) {
                            const index = activeElements[0].index;
                            const year = years[index];
                            showYearDetails(year, yearlyData[year]);
                        }
                    },
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 15,
                                font: { size: 12, weight: 'bold' },
                                usePointStyle: true,
                                boxWidth: 12,
                                boxHeight: 12
                            }
                        },
                        tooltip: {
                            callbacks: {
                                title: function(context) {
                                    return 'Year ' + context[0].label;
                                },
                                footer: function(context) {
                                    const year = context[0].label;
                                    const total = yearlyData[year].total;
                                    const passingRate = ((yearlyData[year].passed / total) * 100).toFixed(2);
                                    return `\nTotal Examinees: ${total}\nOverall Passing Rate: ${passingRate}%`;
                                }
                            },
                            backgroundColor: 'rgba(0, 0, 0, 0.85)',
                            padding: 15,
                            titleFont: { size: 15, weight: 'bold' },
                            bodyFont: { size: 13 },
                            footerFont: { size: 12, weight: '600' }
                        }
                    },
                    scales: {
                        x: {
                            ticks: {
                                font: { size: 12, weight: '600' }
                            },
                            grid: {
                                display: false
                            }
                        },
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Number of Examinees',
                                font: { size: 12, weight: 'bold' }
                            },
                            ticks: {
                                font: { size: 11 }
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            beginAtZero: true,
                            max: 100,
                            title: {
                                display: true,
                                text: 'Passing Rate (%)',
                                font: { size: 12, weight: 'bold' }
                            },
                            ticks: {
                                callback: function(value) {
                                    return value + '%';
                                },
                                font: { size: 11 }
                            },
                            grid: {
                                drawOnChartArea: false
                            }
                        }
                    },
                    animation: {
                        duration: 1800,
                        easing: 'easeInOutQuart'
                    }
                }
            });
        }

        function renderRadarChart(data) {
            const ctx = document.getElementById('radarChart').getContext('2d');
            const hasData = data.by_exam_type && Object.keys(data.by_exam_type).length > 0;
            const examTypes = hasData ? Object.keys(data.by_exam_type) : ['No Data'];
            
            // Calculate metrics for each exam type
            const passingRates = hasData ? examTypes.map(type => {
                const typeData = data.by_exam_type[type];
                return typeData.total > 0 ? ((typeData.passed / typeData.total) * 100).toFixed(2) : 0;
            }) : [0];
            
            const examineeCounts = hasData ? examTypes.map(type => {
                return data.by_exam_type[type].total;
            }) : [0];
            
            // Normalize examinee counts to 0-100 scale
            const maxExaminees = Math.max(...examineeCounts);
            const normalizedCounts = maxExaminees > 0 ? examineeCounts.map(count => 
                ((count / maxExaminees) * 100).toFixed(2)
            ) : [0];

            new Chart(ctx, {
                type: 'radar',
                data: {
                    labels: examTypes,
                    datasets: [
                        {
                            label: 'Passing Rate (%)',
                            data: passingRates,
                            borderColor: 'rgba(211, 47, 47, 1)',
                            backgroundColor: 'rgba(211, 47, 47, 0.25)',
                            borderWidth: 3,
                            pointRadius: 5,
                            pointHoverRadius: 8,
                            pointBackgroundColor: '#D32F2F',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            pointHoverBackgroundColor: '#fff',
                            pointHoverBorderColor: '#D32F2F',
                            pointHoverBorderWidth: 3
                        },
                        {
                            label: 'Popularity (Normalized)',
                            data: normalizedCounts,
                            borderColor: 'rgba(128, 0, 32, 1)',
                            backgroundColor: 'rgba(128, 0, 32, 0.25)',
                            borderWidth: 3,
                            pointRadius: 5,
                            pointHoverRadius: 8,
                            pointBackgroundColor: '#800020',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            pointHoverBackgroundColor: '#fff',
                            pointHoverBorderColor: '#800020',
                            pointHoverBorderWidth: 3
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 15,
                                font: { size: 13, weight: 'bold' },
                                usePointStyle: true,
                                pointStyle: 'circle'
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const examType = context.label;
                                    const typeData = data.by_exam_type[examType];
                                    const datasetLabel = context.dataset.label;
                                    
                                    if (datasetLabel === 'Passing Rate (%)') {
                                        return [
                                            `Passing Rate: ${context.parsed.r}%`,
                                            `Passed: ${typeData.passed}/${typeData.total}`,
                                            `Failed: ${typeData.failed}`,
                                            `Conditional: ${typeData.conditional}`
                                        ];
                                    } else {
                                        const actualCount = typeData.total;
                                        return [
                                            `Normalized: ${context.parsed.r}%`,
                                            `Actual Examinees: ${actualCount}`
                                        ];
                                    }
                                }
                            },
                            backgroundColor: 'rgba(0, 0, 0, 0.85)',
                            padding: 14,
                            titleFont: { size: 14, weight: 'bold' },
                            bodyFont: { size: 12 },
                            borderColor: '#D32F2F',
                            borderWidth: 2
                        }
                    },
                    scales: {
                        r: {
                            beginAtZero: true,
                            max: 100,
                            ticks: {
                                stepSize: 20,
                                backdropColor: 'transparent',
                                font: { size: 11, weight: '600' },
                                callback: function(value) {
                                    return value + '%';
                                }
                            },
                            grid: {
                                color: 'rgba(145, 179, 142, 0.2)',
                                circular: true
                            },
                            angleLines: {
                                color: 'rgba(145, 179, 142, 0.3)'
                            },
                            pointLabels: {
                                font: { size: 11, weight: '700' },
                                color: '#334155'
                            }
                        }
                    },
                    animation: {
                        duration: 2000,
                        easing: 'easeInOutQuart'
                    }
                }
            });
        }

        function showChartInfo(chartId) {
            const chartInfo = {
                'resultsChart': {
                    title: 'Results Distribution',
                    icon: 'fa-chart-pie',
                    type: 'Doughnut Chart',
                    purpose: 'Visual Overview of Exam Outcomes',
                    description: 'This doughnut chart provides a comprehensive snapshot of all exam results, showing the proportion of students who Passed, Failed, or received Conditional results.',
                    features: [
                        'Quick visual assessment of overall performance',
                        'Color-coded segments for easy identification',
                        'Percentage breakdowns with hover tooltips',
                        'Modern doughnut design with center cutout'
                    ],
                    insights: [
                        'See at a glance how many students passed vs failed',
                        'Identify if conditional results are significant',
                        'Compare proportions between different outcomes',
                        'Monitor overall success rate trends'
                    ],
                    useCase: 'Use this chart to get a quick overview of the overall exam performance and to identify the distribution of results across all examinees.'
                },
                'examTypeChart': {
                    title: 'Take Attempt Distribution',
                    icon: 'fa-user-graduate',
                    type: 'Polar Area Chart',
                    purpose: 'First-Time vs Repeat Examinees',
                    description: 'This polar area chart visualizes the distribution between first-time test takers and those retaking the exam, with area size emphasizing the magnitude of each group.',
                    features: [
                        'Radial segments showing category sizes',
                        'Area proportional to actual values',
                        'Better magnitude visualization than pie charts',
                        'Red color scheme for consistency'
                    ],
                    insights: [
                        'Understand the ratio of first-timers to repeaters',
                        'Assess exam difficulty (more repeaters may indicate harder exams)',
                        'Track trends in retake rates over time',
                        'Identify support needs for different groups'
                    ],
                    useCase: 'Use this to understand your examinee population composition and to assess whether additional support programs are needed for repeat test-takers.'
                },
                'passingRateChart': {
                    title: 'Overall Passing Rate by Board Exam Type',
                    icon: 'fa-graduation-cap',
                    type: 'Horizontal Bar Chart',
                    purpose: 'Compare Performance Across Different Exam Types',
                    description: 'This horizontal bar chart displays passing rates for each board exam type, with color-coding to quickly identify high, medium, and low-performing exam categories.',
                    features: [
                        'Horizontal layout for better readability of exam names',
                        'Color-coded by performance (Red ≥75%, Peach ≥50%, Gray <50%)',
                        'Click bars to see detailed breakdowns',
                        'Percentage scale from 0-100%'
                    ],
                    insights: [
                        'Identify which exam types have highest/lowest success rates',
                        'Compare difficulty levels across different board exams',
                        'Spot areas needing curriculum improvement',
                        'Benchmark performance against targets'
                    ],
                    useCase: 'Use this chart to identify which exam types need additional focus or curriculum improvements, and to celebrate high-performing programs.'
                },
                'trendChart': {
                    title: 'Exam Results Trend Over Time',
                    icon: 'fa-chart-line',
                    type: 'Area Chart (Filled Line)',
                    purpose: 'Track Performance Changes Over Exam Dates',
                    description: 'This area chart tracks how exam results have evolved over time, showing trends in passed, failed, and conditional outcomes across different exam dates.',
                    features: [
                        'Three overlapping filled areas (20% opacity)',
                        'Smooth curves showing trend patterns',
                        'Enhanced data points for precise values',
                        'Visual emphasis on volume under curves'
                    ],
                    insights: [
                        'Identify improving or declining performance trends',
                        'Spot seasonal patterns or anomalies',
                        'Track the impact of curriculum changes',
                        'Predict future performance based on trends'
                    ],
                    useCase: 'Use this to monitor performance trends over time and to evaluate the effectiveness of interventions or program changes.'
                },
                'comparisonChart': {
                    title: 'First Timers vs Repeaters Performance',
                    icon: 'fa-balance-scale',
                    type: 'Stacked Bar Chart',
                    purpose: 'Compare Success Rates Between Groups',
                    description: 'This stacked bar chart compares the performance of first-time examinees versus those retaking the exam, showing passed and failed counts for each group.',
                    features: [
                        'Side-by-side comparison of two groups',
                        'Gradient colors for visual appeal',
                        'Stacked format shows composition',
                        'Tooltips include passing rates and totals'
                    ],
                    insights: [
                        'Compare first-timer vs repeater success rates',
                        'Assess if preparation programs are effective',
                        'Identify if repeaters need different support',
                        'Understand performance gaps between groups'
                    ],
                    useCase: 'Use this to evaluate whether your support programs for repeat test-takers are effective and to identify performance gaps.'
                },
                'examDateChart': {
                    title: 'Results by Exam Date',
                    icon: 'fa-calendar-alt',
                    type: 'Color-Coded Bar Chart',
                    purpose: 'Analyze Performance Per Exam Administration',
                    description: 'This bar chart shows the total number of examinees for each exam date, with bars color-coded based on the passing rate to quickly identify successful vs challenging exam administrations.',
                    features: [
                        'Dynamic color coding by passing rate',
                        'Red (≥70%), Peach (≥50%), Gray (<50%)',
                        'Click dates for detailed breakdowns',
                        'Rounded corners for modern appearance'
                    ],
                    insights: [
                        'Identify which exam dates had best/worst results',
                        'Spot patterns in exam difficulty over time',
                        'Assess consistency across exam administrations',
                        'Investigate anomalies in specific exam dates'
                    ],
                    useCase: 'Use this to identify specific exam administrations that may have had issues or to celebrate particularly successful exam dates.'
                },
                'yearlyTrendChart': {
                    title: 'Performance Trends by Year',
                    icon: 'fa-calendar-year',
                    type: 'Mixed/Combo Chart (Bar + Line)',
                    purpose: 'Yearly Performance with Passing Rate Overlay',
                    description: 'This combo chart combines bar graphs showing absolute numbers (passed, failed, conditional) with a line overlay showing the passing rate percentage, providing both volume and performance metrics.',
                    features: [
                        'Bars for absolute counts of results',
                        'Line overlay for passing rate trends',
                        'Dual Y-axes (count on left, percentage on right)',
                        'Click years for detailed information'
                    ],
                    insights: [
                        'Track year-over-year performance changes',
                        'See both volume and success rate simultaneously',
                        'Identify long-term improvement or decline',
                        'Correlate exam volume with passing rates'
                    ],
                    useCase: 'Use this chart to understand both the scale of your program (how many examinees) and its effectiveness (passing rate) over multiple years.'
                },
                'radarChart': {
                    title: 'Multi-Dimensional Exam Type Analysis',
                    icon: 'fa-radar',
                    type: 'Radar Chart (Spider/Web)',
                    purpose: 'Compare Multiple Metrics Simultaneously',
                    description: 'This radar chart provides a multi-dimensional view of exam types, comparing both passing rates and popularity (normalized examinee counts) in a single visualization.',
                    features: [
                        'Two overlapping datasets (Passing Rate & Popularity)',
                        'Circular grid with percentage scale',
                        'Enhanced point markers with hover effects',
                        'Shows both performance and volume'
                    ],
                    insights: [
                        'Identify exam types that are both popular and successful',
                        'Spot high-volume but low-performing programs',
                        'Find underutilized but high-success programs',
                        'Compare multiple dimensions at once'
                    ],
                    useCase: 'Use this to get a holistic view of each exam type, understanding both its popularity and success rate to make strategic decisions about resource allocation.'
                }
            };

            const info = chartInfo[chartId];
            if (!info) return;

            const modal = document.createElement('div');
            modal.style.cssText = `
                position: fixed; top: 0; left: 0; right: 0; bottom: 0;
                background: rgba(0,0,0,0.7); z-index: 10000;
                display: flex; align-items: center; justify-content: center;
                padding: 20px;
                animation: fadeIn 0.3s ease;
            `;
            
            modal.innerHTML = `
                <div style="background: white; border-radius: 20px; padding: 40px; max-width: 700px; max-height: 85vh; overflow-y: auto; box-shadow: 0 25px 80px rgba(0,0,0,0.4); animation: slideUp 0.3s ease;">
                    <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 24px; padding-bottom: 20px; border-bottom: 3px solid #91b38e;">
                        <div style="width: 60px; height: 60px; background: linear-gradient(135deg, #91b38e 0%, #5a855f 100%); border-radius: 15px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(145, 179, 142, 0.4);">
                            <i class="fas ${info.icon}" style="font-size: 28px; color: white;"></i>
                        </div>
                        <div style="flex: 1;">
                            <h2 style="margin: 0; font-size: 1.8rem; color: #0f1724; font-weight: 800;">${info.title}</h2>
                            <p style="margin: 4px 0 0 0; color: #64748b; font-weight: 600; font-size: 0.95rem;">${info.type}</p>
                        </div>
                        <button onclick="this.closest('div[style*=fixed]').remove()" 
                            style="width: 40px; height: 40px; border: none; background: #f1f5f9; border-radius: 10px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.3s;"
                            onmouseover="this.style.background='#e2e8f0'; this.style.transform='rotate(90deg)'"
                            onmouseout="this.style.background='#f1f5f9'; this.style.transform='rotate(0deg)'">
                            <i class="fas fa-times" style="font-size: 18px; color: #64748b;"></i>
                        </button>
                    </div>
                    
                    <div style="margin-bottom: 24px; padding: 20px; background: linear-gradient(135deg, #f0f9f0 0%, #e8f5e8 100%); border-radius: 12px; border-left: 4px solid #91b38e;">
                        <h3 style="margin: 0 0 12px 0; color: #0f1724; font-size: 1.2rem; font-weight: 700; display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-bullseye" style="color: #91b38e;"></i> Purpose
                        </h3>
                        <p style="margin: 0; color: #334155; font-size: 1.05rem; font-weight: 600; line-height: 1.6;">${info.purpose}</p>
                    </div>

                    <div style="margin-bottom: 24px;">
                        <h3 style="margin: 0 0 12px 0; color: #0f1724; font-size: 1.1rem; font-weight: 700; display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-align-left" style="color: #91b38e;"></i> Description
                        </h3>
                        <p style="margin: 0; color: #475569; line-height: 1.8; font-size: 0.95rem;">${info.description}</p>
                    </div>

                    <div style="margin-bottom: 24px;">
                        <h3 style="margin: 0 0 16px 0; color: #0f1724; font-size: 1.1rem; font-weight: 700; display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-star" style="color: #91b38e;"></i> Key Features
                        </h3>
                        <ul style="margin: 0; padding-left: 0; list-style: none;">
                            ${info.features.map(feature => `
                                <li style="margin-bottom: 10px; padding: 12px 16px; background: #f8fafc; border-radius: 8px; border-left: 3px solid #91b38e; color: #334155; font-size: 0.95rem;">
                                    <i class="fas fa-check-circle" style="color: #D32F2F; margin-right: 8px;"></i>${feature}
                                </li>
                            `).join('')}
                        </ul>
                    </div>

                    <div style="margin-bottom: 24px;">
                        <h3 style="margin: 0 0 16px 0; color: #0f1724; font-size: 1.1rem; font-weight: 700; display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-lightbulb" style="color: #f59e0b;"></i> Insights You Can Gain
                        </h3>
                        <ul style="margin: 0; padding-left: 0; list-style: none;">
                            ${info.insights.map(insight => `
                                <li style="margin-bottom: 10px; padding: 12px 16px; background: #fffbeb; border-radius: 8px; border-left: 3px solid #f59e0b; color: #334155; font-size: 0.95rem;">
                                    <i class="fas fa-arrow-right" style="color: #f59e0b; margin-right: 8px;"></i>${insight}
                                </li>
                            `).join('')}
                        </ul>
                    </div>

                    <div style="padding: 20px; background: linear-gradient(135deg, #91b38e 0%, #5a855f 100%); border-radius: 12px; color: white;">
                        <h3 style="margin: 0 0 12px 0; font-size: 1.1rem; font-weight: 700; display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-chart-line"></i> How to Use This Chart
                        </h3>
                        <p style="margin: 0; line-height: 1.8; font-size: 0.95rem; opacity: 0.95;">${info.useCase}</p>
                    </div>

                    <button onclick="this.closest('div[style*=fixed]').remove()" 
                        style="width: 100%; margin-top: 24px; padding: 14px; background: #91b38e; color: white; border: none; border-radius: 12px; font-weight: 700; cursor: pointer; font-size: 1.05rem; transition: all 0.3s;"
                        onmouseover="this.style.background='#5a855f'; this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(145,179,142,0.4)'"
                        onmouseout="this.style.background='#91b38e'; this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                        <i class="fas fa-times-circle"></i> Close
                    </button>
                </div>
            `;
            
            // Add animation styles
            const style = document.createElement('style');
            style.textContent = `
                @keyframes fadeIn {
                    from { opacity: 0; }
                    to { opacity: 1; }
                }
                @keyframes slideUp {
                    from { transform: translateY(50px); opacity: 0; }
                    to { transform: translateY(0); opacity: 1; }
                }
            `;
            document.head.appendChild(style);
            
            document.body.appendChild(modal);
            modal.onclick = (e) => { 
                if (e.target === modal) modal.remove(); 
            };
        }

        function showYearDetails(year, yearData) {
            const passingRate = ((yearData.passed / yearData.total) * 100).toFixed(2);
            const failRate = ((yearData.failed / yearData.total) * 100).toFixed(2);
            const conditionalRate = ((yearData.conditional / yearData.total) * 100).toFixed(2);
            
            const modal = document.createElement('div');
            modal.style.cssText = `
                position: fixed; top: 0; left: 0; right: 0; bottom: 0;
                background: rgba(0,0,0,0.6); z-index: 10000;
                display: flex; align-items: center; justify-content: center;
            `;
            modal.innerHTML = `
                <div style="background: white; border-radius: 20px; padding: 40px; max-width: 600px; box-shadow: 0 25px 80px rgba(0,0,0,0.4);">
                    <h2 style="margin: 0 0 24px 0; font-size: 1.8rem; color: #0f1724; display: flex; align-items: center; gap: 12px;">
                        <i class="fas fa-calendar-year" style="color: #91b38e;"></i> Year ${year} Performance Summary
                    </h2>
                    <div style="margin-bottom: 20px; padding: 20px; background: linear-gradient(135deg, #f0f9f0 0%, #e8f5e8 100%); border-radius: 12px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 12px; padding-bottom: 12px; border-bottom: 2px solid rgba(145, 179, 142, 0.3);">
                            <span style="font-weight: 700; font-size: 1.2rem;">Total Examinees:</span>
                            <span style="font-weight: 800; font-size: 1.3rem; color: #91b38e;">${yearData.total}</span>
                        </div>
                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; margin-top: 16px;">
                            <div style="background: white; padding: 16px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
                                <div style="color: #64748b; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px;">PASSED</div>
                                <div style="font-weight: 800; font-size: 1.5rem; color: #91b38e;">${yearData.passed}</div>
                                <div style="font-size: 0.9rem; color: #91b38e; font-weight: 600;">${passingRate}%</div>
                            </div>
                            <div style="background: white; padding: 16px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
                                <div style="color: #64748b; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px;">FAILED</div>
                                <div style="font-weight: 800; font-size: 1.5rem; color: #64748b;">${yearData.failed}</div>
                                <div style="font-size: 0.9rem; color: #64748b; font-weight: 600;">${failRate}%</div>
                            </div>
                        </div>
                        ${yearData.conditional > 0 ? `
                        <div style="background: white; padding: 16px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-top: 16px;">
                            <div style="color: #64748b; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px;">CONDITIONAL</div>
                            <div style="font-weight: 800; font-size: 1.5rem; color: #a8c5a5;">${yearData.conditional}</div>
                            <div style="font-size: 0.9rem; color: #a8c5a5; font-weight: 600;">${conditionalRate}%</div>
                        </div>
                        ` : ''}
                    </div>
                    
                    <div style="margin-top: 24px; padding: 20px; background: linear-gradient(135deg, #e0f2f1 0%, #b2dfdb 100%); border-radius: 12px;">
                        <h3 style="margin: 0 0 16px 0; font-size: 1.1rem; color: #0f1724; display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-user-graduate" style="color: #00897b;"></i> Take Attempts
                        </h3>
                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px;">
                            <div style="background: white; padding: 16px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
                                <div style="color: #64748b; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px;">FIRST TIMER</div>
                                <div style="font-weight: 800; font-size: 1.5rem; color: #91b38e;">${yearData.first_timer || 0}</div>
                                <div style="font-size: 0.9rem; color: #64748b; font-weight: 600;">${yearData.total > 0 ? ((yearData.first_timer / yearData.total) * 100).toFixed(1) : 0}%</div>
                            </div>
                            <div style="background: white; padding: 16px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
                                <div style="color: #64748b; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px;">REPEATER</div>
                                <div style="font-weight: 800; font-size: 1.5rem; color: #5a855f;">${yearData.repeater || 0}</div>
                                <div style="font-size: 0.9rem; color: #64748b; font-weight: 600;">${yearData.total > 0 ? ((yearData.repeater / yearData.total) * 100).toFixed(1) : 0}%</div>
                            </div>
                        </div>
                    </div>
                    
                    ${yearData.board_exams && Object.keys(yearData.board_exams).length > 0 ? `
                    <div style="margin-top: 24px;">
                        <h3 style="margin: 0 0 16px 0; font-size: 1.1rem; color: #0f1724; display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-graduation-cap" style="color: #91b38e;"></i> Board Exams Taken
                        </h3>
                        <div style="display: grid; gap: 12px;">
                            ${Object.keys(yearData.board_exams).map(examType => {
                                const examData = yearData.board_exams[examType];
                                if (examData.total === 0) return '';
                                const examPassingRate = ((examData.passed / examData.total) * 100).toFixed(1);
                                return `
                                <div style="background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); padding: 14px; border-radius: 10px; border-left: 4px solid #91b38e;">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                        <span style="font-weight: 700; color: #0f1724; font-size: 0.95rem;">${examType}</span>
                                        <span style="font-weight: 800; color: #91b38e; font-size: 1rem;">${examPassingRate}%</span>
                                    </div>
                                    <div style="display: flex; gap: 12px; font-size: 0.85rem;">
                                        <span style="color: #64748b;">Total: <strong>${examData.total}</strong></span>
                                        <span style="color: #91b38e;">Passed: <strong>${examData.passed}</strong></span>
                                        <span style="color: #64748b;">Failed: <strong>${examData.failed}</strong></span>
                                        ${examData.conditional > 0 ? `<span style="color: #a8c5a5;">Conditional: <strong>${examData.conditional}</strong></span>` : ''}
                                    </div>
                                </div>
                                `;
                            }).join('')}
                        </div>
                    </div>
                    ` : ''}
                    
                    <div style="margin-top: 24px; padding: 20px; background: linear-gradient(135deg, #91b38e 0%, #5a855f 100%); border-radius: 12px; color: white;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-weight: 700; font-size: 1.2rem;">Overall Passing Rate</span>
                            <span style="font-weight: 900; font-size: 2rem;">${passingRate}%</span>
                        </div>
                    </div>
                    <button onclick="this.closest('div[style*=fixed]').remove()" 
                        style="width: 100%; padding: 14px; background: #91b38e; color: white; border: none; border-radius: 12px; font-weight: 700; cursor: pointer; font-size: 1.05rem; margin-top: 24px; transition: all 0.3s;"
                        onmouseover="this.style.background='#5a855f'; this.style.transform='translateY(-2px)'"
                        onmouseout="this.style.background='#91b38e'; this.style.transform='translateY(0)'">
                        <i class="fas fa-times-circle"></i> Close
                    </button>
                </div>
            `;
            document.body.appendChild(modal);
            modal.onclick = (e) => { if (e.target === modal) modal.remove(); };
        }

        async function exportToPDF() {
            // Show loading overlay
            const loadingOverlay = document.createElement('div');
            loadingOverlay.style.cssText = `
                position: fixed; top: 0; left: 0; right: 0; bottom: 0;
                background: rgba(0,0,0,0.8); z-index: 10001;
                display: flex; align-items: center; justify-content: center;
                flex-direction: column; gap: 20px;
            `;
            loadingOverlay.innerHTML = `
                <div style="width: 80px; height: 80px; border: 5px solid rgba(145, 179, 142, 0.3); border-top-color: #91b38e; border-radius: 50%; animation: spin 1s linear infinite;"></div>
                <div style="color: white; font-size: 1.2rem; font-weight: 600;">Generating PDF Report...</div>
                <div style="color: rgba(255,255,255,0.7); font-size: 0.95rem;">This may take a few moments</div>
                <style>
                    @keyframes spin {
                        to { transform: rotate(360deg); }
                    }
                </style>
            `;
            document.body.appendChild(loadingOverlay);

            try {
                const { jsPDF } = window.jspdf;
                const pdf = new jsPDF('p', 'mm', 'a4');
                const pageWidth = pdf.internal.pageSize.getWidth();
                const pageHeight = pdf.internal.pageSize.getHeight();
                const margin = 15;
                const contentWidth = pageWidth - (2 * margin);
                let currentY = margin;

                // Header with Logo and Title
                pdf.setFillColor(145, 179, 142);
                pdf.rect(0, 0, pageWidth, 40, 'F');
                
                pdf.setTextColor(255, 255, 255);
                pdf.setFontSize(22);
                pdf.setFont(undefined, 'bold');
                pdf.text('LAGUNA STATE POLYTECHNIC UNIVERSITY', pageWidth / 2, 15, { align: 'center' });
                
                pdf.setFontSize(14);
                pdf.setFont(undefined, 'normal');
                pdf.text('College of Criminal Justice Education', pageWidth / 2, 23, { align: 'center' });
                
                pdf.setFontSize(11);
                pdf.text('San Pablo City Campus', pageWidth / 2, 30, { align: 'center' });
                
                currentY = 50;

                // Report Title
                pdf.setTextColor(15, 23, 36);
                pdf.setFontSize(18);
                pdf.setFont(undefined, 'bold');
                pdf.text('Anonymous Board Exam Statistics Report', pageWidth / 2, currentY, { align: 'center' });
                
                currentY += 10;
                pdf.setFontSize(10);
                pdf.setFont(undefined, 'normal');
                pdf.setTextColor(100, 116, 139);
                const reportDate = new Date().toLocaleDateString('en-US', { 
                    year: 'numeric', 
                    month: 'long', 
                    day: 'numeric' 
                });
                pdf.text(`Generated on: ${reportDate}`, pageWidth / 2, currentY, { align: 'center' });
                
                currentY += 15;

                // Get all chart canvases
                const charts = [
                    { id: 'resultsChart', title: 'Results Distribution' },
                    { id: 'examTypeChart', title: 'Take Attempt Distribution' },
                    { id: 'passingRateChart', title: 'Overall Passing Rate by Board Exam Type' },
                    { id: 'trendChart', title: 'Exam Results Trend Over Time' },
                    { id: 'comparisonChart', title: 'First Timers vs Repeaters Performance' },
                    { id: 'examDateChart', title: 'Results by Exam Date' },
                    { id: 'yearlyTrendChart', title: 'Performance Trends by Year' },
                    { id: 'radarChart', title: 'Multi-Dimensional Exam Type Analysis' }
                ];

                for (let i = 0; i < charts.length; i++) {
                    const chart = charts[i];
                    const canvas = document.getElementById(chart.id);
                    
                    if (!canvas) continue;

                    // Check if we need a new page
                    if (currentY > pageHeight - 100) {
                        pdf.addPage();
                        currentY = margin;
                    }

                    // Chart title
                    pdf.setFontSize(13);
                    pdf.setFont(undefined, 'bold');
                    pdf.setTextColor(15, 23, 36);
                    pdf.text(chart.title, margin, currentY);
                    currentY += 8;

                    // Convert canvas to image
                    const imgData = canvas.toDataURL('image/png', 1.0);
                    const imgWidth = contentWidth;
                    const imgHeight = (canvas.height / canvas.width) * imgWidth;

                    // Add chart image
                    pdf.addImage(imgData, 'PNG', margin, currentY, imgWidth, imgHeight);
                    currentY += imgHeight + 10;

                    // Add divider line
                    if (i < charts.length - 1) {
                        pdf.setDrawColor(203, 213, 225);
                        pdf.setLineWidth(0.5);
                        pdf.line(margin, currentY, pageWidth - margin, currentY);
                        currentY += 10;
                    }
                }

                // Footer on last page
                const pageCount = pdf.internal.getNumberOfPages();
                for (let i = 1; i <= pageCount; i++) {
                    pdf.setPage(i);
                    pdf.setFontSize(8);
                    pdf.setTextColor(148, 163, 184);
                    pdf.text(
                        `Page ${i} of ${pageCount} | LSPU College of Criminal Justice Education - Anonymous Statistics`,
                        pageWidth / 2,
                        pageHeight - 10,
                        { align: 'center' }
                    );
                }

                // Save PDF
                const fileName = `LSPU_Criminal Justice Education_Statistics_${new Date().toISOString().split('T')[0]}.pdf`;
                pdf.save(fileName);

                // Remove loading overlay
                loadingOverlay.remove();

                // Show success message
                showSuccessMessage('PDF exported successfully!');

            } catch (error) {
                console.error('Error generating PDF:', error);
                loadingOverlay.remove();
                alert('Error generating PDF. Please try again.');
            }
        }

        function showSuccessMessage(message) {
            const toast = document.createElement('div');
            toast.style.cssText = `
                position: fixed; top: 20px; right: 20px; z-index: 10002;
                background: linear-gradient(135deg, #D32F2F 0%, #C62828 100%);
                color: white; padding: 16px 24px; border-radius: 12px;
                box-shadow: 0 10px 25px rgba(211, 47, 47, 0.3);
                display: flex; align-items: center; gap: 12px;
                font-weight: 600; animation: slideInRight 0.3s ease;
            `;
            toast.innerHTML = `
                <i class="fas fa-check-circle" style="font-size: 20px;"></i>
                <span>${message}</span>
            `;
            
            const style = document.createElement('style');
            style.textContent = `
                @keyframes slideInRight {
                    from { transform: translateX(400px); opacity: 0; }
                    to { transform: translateX(0); opacity: 1; }
                }
            `;
            document.head.appendChild(style);
            
            document.body.appendChild(toast);
            setTimeout(() => {
                toast.style.animation = 'slideInRight 0.3s ease reverse';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }

        // Load statistics on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Setup logout modal click handler
            const logoutModal = document.getElementById('logoutModal');
            if (logoutModal) {
                logoutModal.onclick = function(e) {
                    if (e.target === this) {
                        this.style.display = 'none';
                    }
                };
            }
            
            // Load statistics
            loadStatistics();
        });
    </script>

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
</body>
</html>


