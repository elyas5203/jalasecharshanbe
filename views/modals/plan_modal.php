<!-- MODAL: PLAN -->
<div class="modal" id="plan-modal">
  <div class="modal-box">
    <h3 style="margin-top:0;">📝 مدیریت طرح درس</h3>
    <input type="hidden" id="p-id">
    <label>تاریخ اجرای طرح:</label><input type="text" id="p-date" class="to-persian" style="text-align:center;">
    <label>عنوان اصلی طرح:</label><input type="text" id="p-title" placeholder="عنوان جلسه">
    <label>⏰ سین و زمان‌بندی برنامه:</label>
    <textarea id="p-sin" placeholder="۱۸:۰۰ شروع برنامه..." oninput="autoGrow(this)"></textarea>

    <div style="margin:15px 0;">
      <label>افزودن بخش جدید:</label><br>
      <span class="chip-ui" onclick="addModule('game')">🎲 بازی</span>
      <span class="chip-ui" onclick="addModule('story')">📖 داستان</span>
      <span class="chip-ui" onclick="addModule('yade_hazrat')">🕌 یاد حضرت</span>
      <span class="chip-ui" onclick="addModule('trip')">🚌 اردو</span>
      <span class="chip-ui" onclick="addModule('other')">📝 سایر</span>
    </div>

    <div id="modules-container"></div>

    <div style="display:flex; gap:12px; margin-top:25px;">
      <button class="btn btn-blue" onclick="savePlan()">💾 ذخیره نهایی</button>
      <button class="btn btn-red" onclick="document.getElementById('plan-modal').style.display='none'">❌ انصراف</button>
    </div>
  </div>
</div>
