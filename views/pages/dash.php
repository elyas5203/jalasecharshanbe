<!-- DASHBOARD VIEW -->
<div id="view-dash" class="view <?= ($initialPage ?? 'dash') === 'dash' ? 'active' : '' ?>">
  <div class="card hero-card">
    <div class="hero-grid">
      <div>
        <small>امروز</small>
        <h2 style="margin:6px 0 12px; font-size:1.5rem;">کنترل سریع جلسه</h2>
        <div class="quick-actions">
          <button type="button" class="action-tile" onclick="nav('att')">
            <span class="tile-icon-badge bg-green">✅</span>
            <span class="tile-text">ثبت حضور</span>
          </button>
          <button type="button" class="action-tile" onclick="openPlanModal()">
            <span class="tile-icon-badge bg-purple">📚</span>
            <span class="tile-text">طرح درس</span>
          </button>
          <button type="button" class="action-tile" onclick="nav('profiles')">
            <span class="tile-icon-badge bg-blue">👥</span>
            <span class="tile-text">متربیان</span>
          </button>
          <button type="button" class="action-tile" onclick="nav('notes')">
            <span class="tile-icon-badge bg-amber">✍</span>
            <span class="tile-text">یادداشت‌ها</span>
          </button>
        </div>
      </div>
      <div>
        <div class="hero-stat"><small>متربی برتر</small><b id="top-student">...</b></div>
        <div class="metric-strip">
          <div class="metric-pill"><small>متربی</small><b id="dash-count">۰</b></div>
          <div class="metric-pill"><small>جلسه</small><b id="dash-session-count">۰</b></div>
          <div class="metric-pill"><small>هشدار</small><b id="dash-alert-count">۰</b></div>
        </div>
      </div>
    </div>
  </div>

  <div class="card"><h3 class="section-title">🚨 هشدارها</h3><div id="alerts-list"></div></div>

  <div class="card">
    <h3 class="section-title">📈 روند حضور</h3>
    <div style="overflow-x:auto; padding-bottom:6px;">
      <div id="mainChartBox" style="position: relative; height: 240px; width: 100%; min-width: 100%;">
        <canvas id="mainChart"></canvas>
      </div>
    </div>
  </div>

  <div id="class-mi-section" class="card" style="display:none;">
    <div class="mi-lock-overlay">
      <div class="mi-lock-box">
        <div style="font-size:2.4rem;">🔒</div>
        <b>بخش هوش‌های چندگانه هنوز در دسترس نیست -</b>
        <small> این قابلیت در به‌روزرسانی‌های بعدی فعال می‌شود.</small>
      </div>
    </div>
    <h3 class="section-title">🧠 میانگین هوش کلاس</h3>
    <div style="position: relative; height: 320px; width: 100%;">
      <canvas id="classMIChart"></canvas>
    </div>
  </div>

  <div class="card"><h3 class="section-title">👥 متربی‌ها</h3><div id="dash-list"></div></div>
</div>
