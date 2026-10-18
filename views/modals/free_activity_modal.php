<!-- MODAL: FREE ACTIVITY -->
<div class="modal" id="free-activity-modal">
  <div class="modal-box">
    <h3 style="margin-top:0;">📝 ثبت فعالیت آزاد</h3>
    <p style="font-size:0.9rem; color:var(--text-muted);">برای ثبت کارهایی که در طرح درس نبودند.</p>
    <label>تاریخ:</label><input type="text" id="fa-date" class="to-persian" style="text-align:center;">
    <label>عنوان فعالیت:</label><input type="text" id="fa-title" placeholder="مثلاً: فوتبال در حیاط">
    <label>توضیحات:</label><textarea id="fa-desc" placeholder="توضیحات مختصر..."></textarea>
    <button class="btn btn-green" onclick="saveFreeActivityLog()" style="margin-top:15px;">💾 ذخیره در آرشیو</button>
    <button class="btn btn-red" onclick="document.getElementById('free-activity-modal').style.display='none'" style="margin-top:10px;">بستن</button>
  </div>
</div>
