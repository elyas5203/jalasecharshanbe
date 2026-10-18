<!-- Confirmation Modal -->
<div id="confirm-modal" class="modal" style="z-index: 7000;">
  <div class="confirm-box">
    <button class="confirm-close" onclick="closeConfirm()" aria-label="بستن">×</button>
    <div id="confirm-icon" style="font-size: 3rem; margin-bottom: 15px; color: var(--primary);"><i class="fas fa-question-circle"></i></div>
    <h3 id="confirm-msg" style="margin: 0; font-size: 1.2rem;"></h3>
    <div style="display:flex; gap:10px; margin-top:25px;" id="confirm-buttons">
      <button class="btn btn-green" id="confirm-yes" style="border-radius: 12px; padding: 12px;">بله، تایید</button>
      <button class="btn btn-red" id="confirm-no" onclick="closeConfirm()" style="border-radius: 12px; padding: 12px; background: #94a3b8;">خیر، بازگشت</button>
    </div>
  </div>
</div>

<!-- Prompt Modal -->
<div id="prompt-modal" class="modal" style="z-index: 8000;">
  <div class="confirm-box">
    <div style="font-size: 3rem; margin-bottom: 15px; color: var(--primary);"><i class="fas fa-edit"></i></div>
    <h3 id="prompt-msg" style="margin: 0; font-size: 1.2rem;"></h3>
    <input type="text" id="prompt-input" style="margin-top: 20px; text-align: center; font-size: 1.2rem; font-weight: bold; border-color: var(--primary);">
    <div style="display:flex; gap:10px; margin-top:15px;">
      <button class="btn btn-green" id="prompt-yes" style="border-radius: 12px; padding: 12px;">تایید</button>
      <button class="btn btn-red" id="prompt-no" style="border-radius: 12px; padding: 12px; background: #94a3b8;">انصراف</button>
    </div>
  </div>
</div>

<!-- Toast Container -->
<div id="toast-container" class="toast-container"></div>

<!-- Lightbox Image Preview -->
<div id="lightbox" onclick="this.style.display='none'"><span class="close-lb">✕</span><img id="lightbox-img" src=""></div>

<!-- Loading Skeleton Screen -->
<div id="skeleton-screen">
    <div class="sk-card"></div>
    <div class="sk-card"></div>
    <div class="sk-card"></div>
    <div class="sk-card"></div>
</div>
