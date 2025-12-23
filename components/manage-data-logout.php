<style>
    .confirm-modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(15, 23, 42, 0.8);
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 99999;
        animation: fadeInBackdrop 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
    }

    @keyframes fadeInBackdrop {
        from {
            opacity: 0;
            backdrop-filter: blur(0px);
        }
        to {
            opacity: 1;
            backdrop-filter: blur(16px);
        }
    }

    .confirm-modal-content {
        background: rgba(255, 255, 255, 0.98);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        padding: 48px 44px;
        border-radius: 28px;
        box-shadow: 0 32px 64px -12px rgba(217, 119, 6, 0.25), inset 0 1px 0 rgba(255, 255, 255, 0.9);
        max-width: 480px;
        width: 92%;
        text-align: center;
        animation: slideInModal 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
        position: relative;
        overflow: visible;
    }

    @keyframes slideInModal {
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

    .confirm-modal-content::before {
        content: '';
        position: absolute;
        top: -2px;
        left: -2px;
        right: -2px;
        bottom: -2px;
        background: linear-gradient(135deg, #D97706 0%, #F59E0B 25%, #FBBF24 50%, #F59E0B 75%, #D97706 100%);
        border-radius: 30px;
        z-index: -1;
        opacity: 0.8;
        animation: rotateGradient 3s linear infinite;
        background-size: 200% 200%;
    }

    @keyframes rotateGradient {
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

    .confirm-icon {
        width: 88px;
        height: 88px;
        background: linear-gradient(135deg, #D97706 0%, #F59E0B 50%, #FBBF24 100%);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 24px;
        color: #78350F;
        font-size: 2.2rem;
        box-shadow: 0 20px 40px rgba(217, 119, 6, 0.4), 0 0 0 4px rgba(255, 255, 255, 0.8), 0 0 0 6px rgba(217, 119, 6, 0.2);
        position: relative;
        animation: iconPulse 2.5s ease-in-out infinite;
    }

    .confirm-icon::before {
        content: '';
        position: absolute;
        inset: -12px;
        border-radius: 50%;
        background: linear-gradient(135deg, #D97706 0%, #FBBF24 100%);
        opacity: 0.2;
        animation: ringPulse 2.5s ease-in-out infinite;
        z-index: -1;
    }

    @keyframes iconPulse {
        0%, 100% {
            transform: scale(1);
        }
        50% {
            transform: scale(1.08);
        }
    }

    @keyframes ringPulse {
        0%, 100% {
            transform: scale(1);
            opacity: 0.2;
        }
        50% {
            transform: scale(1.15);
            opacity: 0.1;
        }
    }

    .confirm-title {
        font-size: 1.75rem;
        font-weight: 800;
        background: linear-gradient(135deg, #D97706 0%, #78350F 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        margin: 0 0 16px 0;
        letter-spacing: -0.5px;
    }

    .confirm-message {
        color: #475569;
        font-size: 1.1rem;
        margin: 0 0 36px 0;
        line-height: 1.7;
        font-weight: 500;
        padding: 24px;
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        border-radius: 16px;
        border: 1px solid #e2e8f0;
        position: relative;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    }

    .confirm-buttons {
        display: flex;
        gap: 16px;
        justify-content: center;
        align-items: center;
    }

    .confirm-ok-btn {
        padding: 16px 32px;
        border: none;
        border-radius: 16px;
        font-size: 1rem;
        font-weight: 700;
        font-family: 'Inter', sans-serif;
        cursor: pointer;
        transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
        display: flex;
        align-items: center;
        gap: 10px;
        min-width: 150px;
        justify-content: center;
        background: linear-gradient(135deg, #D97706 0%, #F59E0B 50%, #FBBF24 100%);
        color: #78350F;
        position: relative;
        overflow: hidden;
        box-shadow: 0 6px 20px rgba(199, 91, 155, 0.35);
    }

    .confirm-ok-btn::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
        transition: left 0.5s;
    }

    .confirm-ok-btn:hover::before {
        left: 100%;
    }

    .confirm-ok-btn:hover {
        transform: translateY(-3px) scale(1.05);
        box-shadow: 0 12px 30px rgba(199, 91, 155, 0.5);
        background: linear-gradient(135deg, #FBBF24 0%, #F59E0B 50%, #D97706 100%);
    }

    .confirm-ok-btn:active {
        transform: translateY(-1px) scale(1.02);
    }

    .confirm-cancel-btn {
        padding: 16px 32px;
        border: none;
        border-radius: 16px;
        font-size: 1rem;
        font-weight: 700;
        font-family: 'Inter', sans-serif;
        cursor: pointer;
        transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
        display: flex;
        align-items: center;
        gap: 10px;
        min-width: 150px;
        justify-content: center;
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        color: #64748b;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        border: 2px solid #e2e8f0;
    }

    .confirm-cancel-btn:hover {
        transform: translateY(-2px) scale(1.05);
        background: linear-gradient(135deg, #e2e8f0 0%, #cbd5e0 100%);
        color: #475569;
        border-color: #cbd5e1;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
    }

    .confirm-cancel-btn:active {
        transform: translateY(0) scale(1.02);
    }
</style>

<div id="customConfirmModal" class="confirm-modal-overlay" style="display: none;">
    <div class="confirm-modal-content">
        <div class="confirm-icon">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <div class="confirm-title">Confirm Action</div>
        <div class="confirm-message" id="confirmMessage">Are you sure you want to proceed?</div>
        <div class="confirm-buttons">
            <button class="confirm-ok-btn" id="confirmOkBtn">
                <i class="fas fa-check"></i> Confirm
            </button>
            <button class="confirm-cancel-btn" id="confirmCancelBtn">
                <i class="fas fa-times"></i> Cancel
            </button>
        </div>
    </div>
</div> 