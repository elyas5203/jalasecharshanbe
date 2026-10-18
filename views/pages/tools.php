<!-- TOOLS VIEW -->
<div id="view-tools" class="view <?= ($initialPage ?? 'dash') === 'tools' ? 'active' : '' ?>">
  <div class="card">
    <h3 class="section-title">🧰 ابزارها</h3>
    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;">
      <div class="tool-btn" onclick="openTimer()">
        <i class="fas fa-stopwatch"></i>
        <span>تایمر / کرنومتر</span>
      </div>
      <div class="tool-btn" onclick="openManualGrouping()">
        <i class="fas fa-users-cog"></i>
        <span>ثبت گروه‌بندی دستی</span>
      </div>
      <div class="tool-btn" onclick="openFreeActivity()">
        <i class="fas fa-pen-fancy"></i>
        <span>ثبت فعالیت آزاد</span>
      </div>
    </div>
  </div>
</div>
