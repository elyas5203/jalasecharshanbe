<!-- MODAL: REPORT CARD -->
<div class="modal" id="report-modal">
  <div class="modal-box report-card-modal">
    <div class="report-header">
      <h2 style="margin:0;">کارنامه عملکرد</h2>
      <div id="report-date" style="font-size:0.9rem; opacity:0.9; margin-top:5px;"></div>
    </div>
    <div class="report-body" id="report-content"></div>
    <div class="report-actions">
      <button class="btn btn-blue" onclick="window.print()">🖨️ پرینت / ذخیره PDF</button>
      <button class="btn btn-red" onclick="document.getElementById('report-modal').style.display='none'">بستن</button>
    </div>
  </div>
</div>
