<!-- SINGLE PROFILE DETAIL VIEW -->
<div id="view-single-profile" class="view">
  <div class="sp-desktop-bar">
    <button onclick="nav('profiles')" class="btn btn-outline sp-back-btn">
      <i class="fas fa-arrow-right" style="margin-left:8px;"></i> بازگشت به لیست متربیان
    </button>
  </div>

  <div class="card sp-profile-hero">
    <div class="sp-hero-avatar-wrap">
      <img src="assets/default-avatar.svg" id="sp-img" class="profile-avatar" onclick="openLightbox(this.src)" title="کلیک برای بزرگ‌نمایی" onerror="this.src='assets/default-avatar.svg'" alt="تصویر متربی">
    </div>
    <h2 id="sp-name" class="sp-hero-name">متربی</h2>
    <div id="sp-score" class="score-badge">۰ امتیاز</div>
    <div class="sp-hero-actions">
      <button type="button" class="btn btn-blue" onclick="openReportCard()">📄 صدور کارنامه عملکرد</button>
      <button type="button" class="btn btn-outline" onclick="openMonthlyReport()">🗓 گزارش ماهانه</button>
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
    <div id="mi-hub-root">
      <div style="text-align:center; padding:40px 20px; color:#64748b;">
        <i class="fas fa-spinner fa-spin" style="font-size:2rem; margin-bottom:12px; display:block; color:var(--primary);"></i>
        در حال بارگذاری سامانه هوش‌های چندگانه...
      </div>
    </div>
  </div>

  <div id="tab-stats" class="tab-content">
    <div class="card" id="sp-summary-stats" style="display:grid; grid-template-columns:1fr 1fr; gap:10px;"></div>

    <!-- نمودار ستونی آنالیز هوش‌های چندگانه متربی -->
    <div class="card">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; flex-wrap:wrap; gap:8px;">
        <h3 class="section-title" style="margin:0;">📊 نمودار ستونی آنالیز هوش‌های چندگانه</h3>
        <span class="score-badge" style="font-size:0.8rem;">تحلیل مقایسه‌ای ۸ حوزه هوش</span>
      </div>
      <div id="sp-mi-empty" class="empty-chart" style="display:none; text-align:center; padding:18px; color:#64748b;">داده‌ای برای آنالیز هوش‌های چندگانه ثبت نشده است.</div>
      <div style="overflow-x:auto; padding-bottom:6px;">
        <div id="spMIChartBox" style="position: relative; height: 320px; width: 100%; min-width: 100%;">
          <canvas id="sp-mi-chart"></canvas>
        </div>
      </div>
    </div>

    <div class="card">
      <h3 class="section-title">📈 روند رشد</h3>
      <div id="sp-growth-empty" class="empty-chart" style="display:none;">داده کافی برای نمودار رشد نیست.</div>
      <div style="overflow-x:auto; padding-bottom:6px;">
        <div id="spGrowthChartBox" style="position: relative; height: 260px; min-width: 100%;">
          <canvas id="sp-growth-chart"></canvas>
        </div>
      </div>
    </div>

    <div class="card"><h3>⏳ تاریخچه فعالیت‌ها</h3><div id="sp-mi-history" style="max-height:350px; overflow-y:auto;"></div></div>
  </div>
</div>
