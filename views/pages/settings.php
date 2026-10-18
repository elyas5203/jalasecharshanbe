<!-- SETTINGS VIEW -->
<div id="view-settings" class="view <?= ($initialPage ?? 'dash') === 'settings' ? 'active' : '' ?>">
  <div class="card">
    <h3 class="section-title">⚙️ تنظیمات</h3>
    <label>توکن ربات تلگرام:</label>
    <input type="text" id="st-token" class="no-persian" dir="ltr" placeholder="مثلاً: 123456789:AAE...">
    
    <label>آیدی عددی مدیران (با کاما):</label>
    <input type="text" id="st-admin" class="no-persian" dir="ltr" placeholder="مثلاً: 12345,67890">
    
    <label>پروکسی تلگرام (اختیاری، اگر تلگرام فیلتر است):</label>
    <input type="text" id="st-proxy" class="no-persian" dir="ltr" placeholder="مثلاً: socks5h://127.0.0.1:1080 — یا خالی بگذار">
    
    <div style="display:grid; grid-template-columns:1fr; gap:8px; margin:10px 0;">
      <small style="opacity:.7; font-weight:800;">کدام رویدادها در تلگرام اعلان شوند؟</small>
      <label style="display:flex; gap:10px; align-items:center;"><input type="checkbox" id="st-tg-score" style="width:auto; margin:0;"> ⭐ امتیازها</label>
      <label style="display:flex; gap:10px; align-items:center;"><input type="checkbox" id="st-tg-att" style="width:auto; margin:0;"> 📋 حضور و غیاب (با اسم غایب‌ها)</label>
      <label style="display:flex; gap:10px; align-items:center;"><input type="checkbox" id="st-tg-plan" style="width:auto; margin:0;"> 📚 طرح درس جدید (با سین اجرایی)</label>
      <label style="display:flex; gap:10px; align-items:center;"><input type="checkbox" id="st-tg-note" style="width:auto; margin:0;"> 📝 یادداشت جدید (بی‌صدا)</label>
      <label style="display:flex; gap:10px; align-items:center;"><input type="checkbox" id="st-tg-alerts" style="width:auto; margin:0;"> 🚨 هشدارهای خودکار (غیبت/تاخیر/امتیاز منفی)</label>
    </div>
    
    <label>آدرس اینترنتی سایت (برای دکمه‌ی «مشاهده پروفایل» در پیام‌ها):</label>
    <input type="text" id="st-site-url" class="no-persian" dir="ltr" placeholder="مثلاً: https://jalase.kerayechi110.ir">
    
    <button class="btn btn-blue" onclick="saveSettings()" style="margin-top:15px;">💾 ذخیره تنظیمات</button>
    
    <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-top:10px;">
      <button class="btn btn-outline" onclick="testTelegram(this)" style="border-color:#8b5cf6; color:#8b5cf6;">🔔 تست بات</button>
      <button class="btn btn-outline" onclick="setupWebhook(this)" style="border-color:#10b981; color:#10b981;">🔗 فعالسازی دکمه‌ها</button>
      <button class="btn btn-outline" onclick="flushTelegram(this)" style="border-color:#d0a94f; color:#8f6b24; grid-column:1 / -1;">📨 ارسال صف تلگرام</button>
    </div>
  </div>
</div>
