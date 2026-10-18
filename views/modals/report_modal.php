<!-- MODAL: REPORT CARD -->
<div class="modal" id="report-modal">
  <div class="modal-box report-card-modal">
    <div class="report-header" style="position:relative;">
      <button type="button" class="report-close-x" onclick="document.getElementById('report-modal').style.display='none'" title="بستن" aria-label="بستن">✕</button>
      <h2 style="margin:0; font-size:1.35rem; font-weight:800;">کارنامه جامع عملکرد و تربیت</h2>
      <div id="report-date" style="font-size:0.88rem; opacity:0.95; margin-top:5px; font-weight:600;"></div>
    </div>
    <div class="report-body" id="report-content"></div>
    <div class="report-actions">
      <button type="button" class="btn btn-blue" onclick="window.print()">🖨️ پرینت / ذخیره PDF</button>
      <button type="button" class="btn btn-red" onclick="document.getElementById('report-modal').style.display='none'">بستن</button>
    </div>
  </div>
</div>
