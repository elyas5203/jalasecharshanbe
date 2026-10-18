<!-- CALENDAR VIEW -->
<div id="view-calendar" class="view <?= ($initialPage ?? 'dash') === 'calendar' ? 'active' : '' ?>">
  <div class="card page-hero-lite">
    <h3 class="section-title">📅 تقویم جلسات</h3>
    <p class="page-hint">نمای سریع جلسه‌ها و برنامه‌های ثبت‌شده</p>
    <div id="calendar-list"></div>
  </div>
</div>
