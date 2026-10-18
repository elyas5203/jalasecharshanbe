<!-- ATTENDANCE VIEW -->
<div id="view-att" class="view <?= ($initialPage ?? 'dash') === 'att' ? 'active' : '' ?>">
  <div class="card">
    <h3 class="section-title">
      ✅ حضور و غیاب 
      <button class="btn btn-green" onclick="submitData(this)" style="width:auto; padding:10px 16px; border-radius:16px;">ثبت</button>
    </h3>
    <div class="compact-row">
      <input type="text" id="att-date" class="to-persian" style="text-align:center; font-weight:bold; font-size:1.15rem;">
      <button class="btn btn-outline" onclick="markAllPresent()" style="width:auto; padding:10px 14px; white-space:nowrap;">همه حاضر</button>
    </div>
  </div>

  <div class="card" id="att-list"></div>

  <div class="card attendance-history-card">
    <h3 class="section-title">
      🗂 سوابق حضور و غیاب 
      <button class="btn btn-outline" onclick="loadAttendanceHistory(false)" style="width:auto; padding:9px 13px; border-radius:14px;">بروزرسانی</button>
    </h3>
    <div id="attendance-history-list">
      <p style="opacity:.65;text-align:center;">برای دیدن سوابق، صفحه حضور و غیاب را باز کن.</p>
    </div>
  </div>
</div>
