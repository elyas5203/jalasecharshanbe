<!-- NOTES VIEW -->
<div id="view-notes" class="view <?= ($initialPage ?? 'dash') === 'notes' ? 'active' : '' ?>">
  <div class="card">
    <label>تاریخ یادداشت:</label>
    <input type="text" id="note-date" class="to-persian" style="text-align:center;">
    <textarea id="note-text" placeholder="متن یادداشت یا صورتجلسه امروز..." oninput="autoGrow(this)"></textarea>
    <button class="btn btn-blue" onclick="saveNote(this)">💾 ذخیره یادداشت جلسه</button>
  </div>
  <div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
      <h3 style="margin:0;">📒 سوابق یادداشت‌ها</h3>
      <input type="text" placeholder="🔍 جستجو..." style="width:130px; margin:0;" onkeyup="filterNotes(this.value)">
    </div>
    <div id="notes-history"></div>
  </div>
</div>
