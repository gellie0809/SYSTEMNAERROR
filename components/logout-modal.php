 <div id="logoutModal" class="modal" style="display: none;">
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