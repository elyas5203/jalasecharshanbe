<!-- ARCHIVE VIEW -->
<div id="view-archive" class="view <?= ($initialPage ?? 'dash') === 'archive' ? 'active' : '' ?>">
  <div class="card">
    <h3>📂 آرشیو فعالیت‌های انجام شده</h3>
    <!-- Search Bar -->
    <input type="text" id="archive-search" placeholder="🔍 جستجوی هوشمند در آرشیو..." onkeyup="filterArchive(this.value)" style="margin-bottom:15px; border-color:var(--primary);">

    <div class="tabs" style="margin-top:10px; flex-wrap:wrap;">
      <div class="tab active" onclick="renderArchive('grouping', this)">👥 گروه‌بندی</div>
      <div class="tab" onclick="renderArchive('game', this)">🎲 بازی</div>
      <div class="tab" onclick="renderArchive('story', this)">📖 داستان</div>
      <div class="tab" onclick="renderArchive('yade_hazrat', this)">🕌 یاد حضرت</div>
      <div class="tab" onclick="renderArchive('free_activity', this)">📝 آزاد</div>
      <div class="tab" onclick="renderArchive('other', this)">🔹 سایر</div>
    </div>
    <div id="archive-list" style="max-height:60vh; overflow-y:auto;"></div>
  </div>
</div>
