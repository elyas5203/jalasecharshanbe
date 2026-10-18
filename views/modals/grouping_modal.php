<!-- MODAL: MANUAL GROUPING -->
<div class="modal" id="manual-grouping-modal">
  <div class="modal-box">
    <h3 style="margin-top:0;">👥 ثبت گروه‌بندی</h3>
    <label>مدل گروه‌بندی (عنوان کلی):</label>
    <input type="text" id="g-model" placeholder="مثلاً: مسابقه علمی">
    <label>تاریخ:</label><input type="text" id="g-date" class="to-persian" style="text-align:center;">

    <hr style="opacity:0.2; margin:15px 0;">

    <label>نام گروه جدید:</label>
    <div style="display:flex; gap:8px; margin-bottom:10px;">
      <input type="text" id="g-current-name" placeholder="مثلاً: گروه شهید همت" style="margin:0; flex:1;">
      <button class="btn btn-green" style="width:auto; white-space:nowrap; padding:10px 16px;" onclick="addGroupToList()">ثبت این گروه</button>
    </div>

    <p style="font-size:0.85rem; color:var(--text-muted); margin-bottom:8px;">اعضای گروه را انتخاب کنید:</p>
    <div class="student-select-grid" id="g-students-list"></div>

    <div id="added-groups-container" style="margin-top:15px; border-top:1px solid var(--glass-border); padding-top:10px;">
      <small style="color:var(--text-muted); display:block; margin-bottom:5px; font-weight:700;">گروه‌های ساخته شده:</small>
    </div>

    <div style="display:flex; gap:10px; margin-top:15px;">
      <button class="btn btn-blue" onclick="saveFinalGrouping()">💾 ذخیره نهایی همه گروه‌ها</button>
      <button class="btn btn-red" onclick="document.getElementById('manual-grouping-modal').style.display='none'">بستن</button>
    </div>
  </div>
</div>
