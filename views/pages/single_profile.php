<!-- SINGLE PROFILE DETAIL VIEW -->
<div id="view-single-profile" class="view">
  <button onclick="nav('profiles')" class="btn btn-outline" style="margin-bottom:15px; width:auto; padding: 10px 20px;">
    <i class="fas fa-arrow-right" style="margin-left:8px;"></i> بازگشت به لیست
  </button>

  <div class="card" style="text-align:center;">
    <img src="" id="sp-img" class="profile-avatar" onclick="openLightbox(this.src)" title="کلیک برای بزرگ‌نمایی">
    <h2 id="sp-name" style="margin:12px 0 6px;"></h2>
    <div id="sp-score" class="score-badge">0 امتیاز</div>
    <div style="display:flex; gap:10px; justify-content:center; flex-wrap:wrap; margin-top:15px;">
      <button class="btn btn-blue" style="width:auto; font-size:0.9rem;" onclick="openReportCard()">📄 صدور کارنامه عملکرد</button>
      <button class="btn btn-outline" style="width:auto; font-size:0.9rem;" onclick="openMonthlyReport()">🗓 گزارش ماهانه</button>
    </div>
  </div>

  <div class="tabs">
    <div class="tab active" onclick="switchTab('info')">ℹ️ مشخصات</div>
    <div class="tab" onclick="switchTab('mi')">🧠 هوش</div>
    <div class="tab" onclick="switchTab('stats')">📊 آمار</div>
  </div>

  <div id="tab-info" class="tab-content active">
    <div class="card">
      <label>عکس پروفایل:</label>
      <div style="display:flex; gap:10px; align-items:center; margin-bottom:12px;">
        <input type="file" id="sp-image-file" accept="image/jpeg,image/png,image/webp,image/gif" style="margin:0; flex:1;">
        <button class="btn btn-blue" style="width:auto; white-space:nowrap; padding:12px 16px;" onclick="uploadProfileImage()">آپلود</button>
      </div>
      <input type="hidden" id="sp-input-img">
      <label>بیوگرافی:</label><textarea id="sp-bio" oninput="autoGrow(this)"></textarea>
      <label>یادداشت والدین:</label><textarea id="sp-parent-note" oninput="autoGrow(this)" placeholder="یادداشت کارنامه..."></textarea>
      <label>شماره‌های تماس:</label>
      <div id="phones-container"></div>
      <button class="btn btn-outline" style="padding:8px; font-size:0.85rem; margin-bottom:15px; width:auto;" onclick="addPhoneField()">➕ افزودن شماره تماس</button>
      <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
        <input type="text" id="sp-dob" placeholder="تاریخ تولد">
        <input type="text" id="sp-school" placeholder="مدرسه">
      </div>
      <label>ملاحظات پزشکی / خاص:</label><input type="text" id="sp-medical">
      <button class="btn btn-green" onclick="saveProfile()" style="margin-top:15px;">💾 ذخیره پروفایل</button>
      <hr style="opacity:0.1; margin:20px 0;">
      <button class="btn btn-red" onclick="askDeleteStudent()" style="background:linear-gradient(135deg,#861f35,#c93445) !important; opacity:0.9;">🗑️ حذف کامل این متربی</button>
    </div>
  </div>

  <div id="tab-mi" class="tab-content">
    <div class="mi-lock-overlay">
      <div class="mi-lock-box">
        <div style="font-size:2.4rem;">🔒</div>
        <b>بخش هوش‌های چندگانه هنوز در دسترس نیست -</b>
        <small> این قابلیت در به‌روزرسانی‌های بعدی فعال می‌شود.</small>
      </div>
    </div>
    <div class="card">
      <div style="position: relative; height: 400px; width: 100%;">
        <canvas id="sp-mi-chart"></canvas>
      </div>
    </div>
    <div id="sp-mi-accordion"></div>
  </div>

  <div id="tab-stats" class="tab-content">
    <div class="card" id="sp-summary-stats" style="display:grid; grid-template-columns:1fr 1fr; gap:10px;"></div>
    <div class="card"><h3>⏳ تاریخچه فعالیت‌ها</h3><div id="sp-mi-history" style="max-height:350px; overflow-y:auto;"></div></div>
    <div class="card">
      <h3 class="section-title">📈 روند رشد</h3>
      <div id="sp-growth-empty" class="empty-chart" style="display:none;">داده کافی برای نمودار رشد نیست.</div>
      <div style="overflow-x:auto; padding-bottom:6px;">
        <div id="spGrowthChartBox" style="position: relative; height: 260px; min-width: 100%;">
          <canvas id="sp-growth-chart"></canvas>
        </div>
      </div>
    </div>
  </div>
</div>
