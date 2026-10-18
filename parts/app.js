  let DB={}, TEMP={}, chartInst=null, miChart=null, growthChart=null, classMIChart=null, currentStudent=null;
  const lazyLoads = {};
  const attendanceHistoryState = { offset: 0, limit: 80, done: false, loading: false };
  const PAGE_SIZE = { plans: 150, notes: 120 };
  const lazyOffsets = { plans: 0, notes: 0 };
  const lazyDone = { plans: false, notes: false };
  let timerInterval=null, timerSec=0;
  let confirmCallback = null;
  let promptCallback = null;
  let tempGroups = [];

  const verticalHoverLine = {
    id: 'verticalHoverLine',
    afterDraw(chart) {
      if (!chart.tooltip || !chart.tooltip.getActiveElements().length) return;
      const ctx = chart.ctx;
      const active = chart.tooltip.getActiveElements()[0];
      const x = active.element.x;
      const topY = chart.chartArea.top;
      const bottomY = chart.chartArea.bottom;
      ctx.save();
      ctx.beginPath();
      ctx.moveTo(x, topY);
      ctx.lineTo(x, bottomY);
      ctx.lineWidth = 1.5;
      ctx.strokeStyle = 'rgba(79,70,229,0.45)';
      ctx.setLineDash([5, 5]);
      ctx.stroke();
      ctx.restore();
    }
  };
  if (typeof Chart !== 'undefined') Chart.register(verticalHoverLine);


  // --- سیستم هوشمند اعلان و پیام ---
  function showToast(msg, type = 'success') {
      const container = document.getElementById('toast-container');
      const toast = document.createElement('div');
      toast.className = `toast ${type}`;
      const icon = type === 'success' ? 'fa-check-circle' : (type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle');
      toast.innerHTML = `<i class="fas ${icon}"></i> <span>${msg}</span>`;
      container.appendChild(toast);
      setTimeout(() => toast.remove(), 3000);
  }

  function showAlert(msg, type = "info") {
      const iconMap = { 'info': 'fa-info-circle', 'error': 'fa-exclamation-triangle', 'success': 'fa-check-circle' };
      document.getElementById('confirm-icon').innerHTML = `<i class="fas ${iconMap[type] || 'fa-info-circle'}"></i>`;
      document.getElementById('confirm-msg').innerText = msg;
      document.getElementById('confirm-no').style.display = 'none';
      document.getElementById('confirm-yes').innerText = 'متوجه شدم';
      document.getElementById('confirm-yes').className = 'btn btn-blue';
      const modal = document.getElementById('confirm-modal');
      modal.style.display = 'flex';
      confirmCallback = () => {
          document.getElementById('confirm-no').style.display = 'block';
          document.getElementById('confirm-yes').innerText = 'بله، تایید';
          document.getElementById('confirm-yes').className = 'btn btn-green';
      };
  }

  // --- سیستم مودال تایید ---
  function showConfirm(msg, callback) {
      document.getElementById('confirm-icon').innerHTML = `<i class="fas fa-question-circle"></i>`;
      document.getElementById('confirm-msg').innerText = msg;
      document.getElementById('confirm-no').style.display = 'block';
      document.getElementById('confirm-yes').innerText = 'بله، تایید';
      document.getElementById('confirm-yes').className = 'btn btn-green';
      const modal = document.getElementById('confirm-modal');
      modal.style.display = 'flex';
      confirmCallback = callback;
  }

  document.getElementById('confirm-yes').onclick = function() {
      document.getElementById('confirm-modal').style.display = 'none';
      if(confirmCallback) confirmCallback();
  };

  function closeConfirm() {
      document.getElementById('confirm-modal').style.display = 'none';
  }

  function showPrompt(msg, defaultValue, callback) {
      document.getElementById('prompt-msg').innerText = msg;
      const inp = document.getElementById('prompt-input');
      inp.value = toPersianNum(defaultValue || "");
      const modal = document.getElementById('prompt-modal');
      modal.style.display = 'flex';
      inp.focus();
      promptCallback = callback;
  }

  document.getElementById('prompt-yes').onclick = function() {
      const val = document.getElementById('prompt-input').value;
      document.getElementById('prompt-modal').style.display = 'none';
      if(promptCallback) promptCallback(val);
  };

  document.getElementById('prompt-no').onclick = function() {
      document.getElementById('prompt-modal').style.display = 'none';
      if(promptCallback) promptCallback(null);
  };

  // --- توابع کمکی برای دکمه‌ها ---
  function askDeletePlan(id, event) {
      if(event) event.stopPropagation();
      showConfirm('آیا مطمئن هستید که می‌خواهید این طرح درس را حذف کنید؟', function() {
          delPlan(id);
      });
  }

  function editPlanClick(id, event) {
      if(event) event.stopPropagation();
      editPlan(id);
  }

  function showLoading(){ document.getElementById('skeleton-screen').style.display='block'; }
  function hideLoading(){ document.getElementById('skeleton-screen').style.display='none'; }
  function toggleSidebar(){ document.querySelector('.sidebar').classList.toggle('open'); document.querySelector('.sidebar-overlay').classList.toggle('open'); }

  function nav(v, skipPushState = false){
    document.querySelectorAll('.view').forEach(x=>x.classList.remove('active'));
    const targetEl = document.getElementById('view-'+v);
    if(targetEl) targetEl.classList.add('active');

    document.querySelectorAll('.nav-item').forEach(x=>x.classList.remove('active'));
    const btn = document.getElementById('bn-'+v);
    if(btn) btn.classList.add('active');

    document.querySelectorAll('.sidebar .menu-item').forEach(x => {
      const oc = x.getAttribute('onclick') || '';
      if (oc.includes("'" + v + "'")) {
        x.classList.add('active');
      } else if (oc.includes('nav(')) {
        x.classList.remove('active');
      }
    });

    if(document.querySelector('.sidebar.open')) toggleSidebar();

    const titles={'dash':'داشبورد','tools':'جعبه ابزار','profiles':'پروفایل‌ها','plans':'طرح درس','att':'حضور و غیاب','score':'امتیازدهی','notes':'یادداشت','settings':'تنظیمات', 'archive': 'آرشیو فعالیت‌ها', 'calendar':'تقویم جلسات', 'search':'جستجوی سراسری', 'single-profile': 'پروفایل متربی'};
    document.getElementById('page-title').innerText=titles[v] || 'مدیریت کلاس';
    renderView(v);
    if(v==='calendar') renderCalendar();
    window.scrollTo(0,0);

    if(!skipPushState) {
      try {
        const pageRoutes = {
          'dash': 'index.php',
          'tools': 'tools.php',
          'profiles': 'profiles.php',
          'plans': 'plans.php',
          'att': 'attendance.php',
          'archive': 'archive.php',
          'calendar': 'calendar.php',
          'search': 'search.php',
          'notes': 'notes.php',
          'settings': 'settings.php',
          'score': 'scores.php'
        };
        const targetRoute = pageRoutes[v] || ('index.php?page=' + encodeURIComponent(v));
        if (!window.location.pathname.endsWith(targetRoute) && !window.location.search.includes('page=' + v)) {
          history.pushState({ view: v }, '', targetRoute);
        }
      } catch(e) {}
    }
  }

  window.addEventListener('popstate', function(e) {
    if (e.state && e.state.view) {
      nav(e.state.view, true);
    }
  });

  function switchTab(t){
    document.querySelectorAll('.tab').forEach(x=>x.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(x=>x.classList.remove('active'));
    if(event && event.target) event.target.classList.add('active');
    document.getElementById('tab-'+t).classList.add('active');
  }

  function toPersianNum(n) {
      const f = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
      return String(n).replace(/[0-9]/g, w => f[+w]);
  }
  window.toPersianNum = toPersianNum;
  function toEnglishNum(n) {
      return String(n || '').replace(/[۰-۹]/g, c => '0123456789'['۰۱۲۳۴۵۶۷۸۹'.indexOf(c)]).replace(/[٠-٩]/g, c => '0123456789'['٠١٢٣٤٥٦٧٨٩'.indexOf(c)]);
  }
  function persianizeInputValue(el) {
      if(!el || ['file','hidden','checkbox','radio','password','email','url'].includes(el.type)) return;
      // فیلدهایی که باید ارقامشان انگلیسی/لاتین بماند (توکن ربات، آیدی عددی، پروکسی و هر فیلد no-persian)
      if(el.classList.contains('no-persian') || el.dataset.noPersian !== undefined) return;
      const old = el.value;
      const val = toPersianNum(old);
      if(old !== val) {
        const pos = el.selectionStart;
        el.value = val;
        try { el.setSelectionRange(pos, pos); } catch(e) {}
      }
  }
  var jalaliWheelState = { input: null, year: 1405, month: 1, day: 1, modal: null };
  function initPersianInputs(){
    document.querySelectorAll('input, textarea').forEach(el => { persianizeInputValue(el); });
    if (!window.__persianInputBound) {
      document.addEventListener('input', e => { if(e.target && (e.target.matches('input') || e.target.matches('textarea'))) persianizeInputValue(e.target); });
      window.__persianInputBound = true;
    }
  }
  function pad2(num){ return String(num).padStart(2, '0'); }
  function isJalaliLeap(year){ return [1,5,9,13,17,22,26,30].indexOf(year % 33) !== -1; }
  function daysInJalaliMonth(year, month){ if(month <= 6) return 31; if(month <= 11) return 30; return isJalaliLeap(year) ? 30 : 29; }
  function gregorianToJalali(gy, gm, gd){
    var gdm=[31,28,31,30,31,30,31,31,30,31,30,31], jdm=[31,31,31,31,31,31,30,30,30,30,30,29];
    var gy2=gy-1600, gm2=gm-1, gd2=gd-1;
    var gdn=365*gy2+Math.floor((gy2+3)/4)-Math.floor((gy2+99)/100)+Math.floor((gy2+399)/400);
    for(var i=0;i<gm2;++i) gdn+=gdm[i];
    if(gm2>1 && ((gy%4===0 && gy%100!==0)||(gy%400===0))) gdn++;
    gdn+=gd2; var jdn=gdn-79, jnp=Math.floor(jdn/12053); jdn%=12053;
    var jy=979+33*jnp+4*Math.floor(jdn/1461); jdn%=1461;
    if(jdn>=366){ jy+=Math.floor((jdn-1)/365); jdn=(jdn-1)%365; }
    var jm; for(jm=0; jm<11 && jdn>=jdm[jm]; ++jm) jdn-=jdm[jm];
    return [jy, jm+1, jdn+1];
  }
  function readInputDate(input){
    var value = toEnglishNum(input.value || '').replace(/-/g, '/');
    var match = value.match(/(\d{4})\/(\d{1,2})\/(\d{1,2})/);
    if(match) return [parseInt(match[1],10), parseInt(match[2],10), parseInt(match[3],10)];
    var today = new Date(); return gregorianToJalali(today.getFullYear(), today.getMonth()+1, today.getDate());
  }
  function ensureJalaliWheel(){
    if(jalaliWheelState.modal) return jalaliWheelState.modal;
    var modal = document.createElement('div'); modal.className='jalali-wheel-modal'; modal.hidden=true;
    modal.innerHTML = '<div class="jalali-wheel-backdrop" data-jalali-close></div><div class="jalali-wheel-sheet" role="dialog" aria-modal="true" aria-label="انتخاب تاریخ شمسی"><button type="button" class="jalali-wheel-close" data-jalali-close aria-label="بستن">×</button><span class="jalali-wheel-kicker">تاریخ شمسی</span><strong class="jalali-wheel-preview" data-jalali-preview></strong><div class="jalali-wheel-grid"><div class="jalali-wheel-col"><button type="button" data-jalali-part="year" data-jalali-delta="1">+</button><div data-jalali-value="year"></div><button type="button" data-jalali-part="year" data-jalali-delta="-1">-</button><span>سال</span></div><div class="jalali-wheel-col"><button type="button" data-jalali-part="month" data-jalali-delta="1">+</button><div data-jalali-value="month"></div><button type="button" data-jalali-part="month" data-jalali-delta="-1">-</button><span>ماه</span></div><div class="jalali-wheel-col"><button type="button" data-jalali-part="day" data-jalali-delta="1">+</button><div data-jalali-value="day"></div><button type="button" data-jalali-part="day" data-jalali-delta="-1">-</button><span>روز</span></div></div><button type="button" class="btn btn-blue jalali-wheel-apply" data-jalali-apply>تایید تاریخ</button></div>';
    modal.addEventListener('click', function(event){
      var closeButton = event.target.closest('[data-jalali-close]');
      var applyButton = event.target.closest('[data-jalali-apply]');
      var spinButton = event.target.closest('[data-jalali-part]');
      if(closeButton){ closeJalaliWheel(); return; }
      if(applyButton && jalaliWheelState.input){
        jalaliWheelState.input.value = toPersianNum(jalaliWheelState.year + '/' + pad2(jalaliWheelState.month) + '/' + pad2(jalaliWheelState.day));
        jalaliWheelState.input.dispatchEvent(new Event('input', {bubbles:true}));
        jalaliWheelState.input.dispatchEvent(new Event('change', {bubbles:true}));
        closeJalaliWheel(); return;
      }
      if(spinButton){
        var part=spinButton.getAttribute('data-jalali-part'); var delta=parseInt(spinButton.getAttribute('data-jalali-delta')||'0',10);
        if(part==='year') jalaliWheelState.year=Math.max(1300,Math.min(1500,jalaliWheelState.year+delta));
        else if(part==='month'){ jalaliWheelState.month+=delta; if(jalaliWheelState.month>12){jalaliWheelState.month=1; jalaliWheelState.year=Math.min(1500,jalaliWheelState.year+1);} else if(jalaliWheelState.month<1){jalaliWheelState.month=12; jalaliWheelState.year=Math.max(1300,jalaliWheelState.year-1);} }
        else if(part==='day'){ jalaliWheelState.day+=delta; if(jalaliWheelState.day>daysInJalaliMonth(jalaliWheelState.year,jalaliWheelState.month)) jalaliWheelState.day=1; else if(jalaliWheelState.day<1) jalaliWheelState.day=daysInJalaliMonth(jalaliWheelState.year,jalaliWheelState.month); }
        renderJalaliWheel();
      }
    });
    document.body.appendChild(modal); jalaliWheelState.modal=modal; return modal;
  }
  function renderJalaliWheel(){
    var modal=ensureJalaliWheel(); jalaliWheelState.day=Math.min(jalaliWheelState.day, daysInJalaliMonth(jalaliWheelState.year, jalaliWheelState.month));
    modal.querySelector('[data-jalali-value="year"]').textContent=toPersianNum(jalaliWheelState.year);
    modal.querySelector('[data-jalali-value="month"]').textContent=toPersianNum(pad2(jalaliWheelState.month));
    modal.querySelector('[data-jalali-value="day"]').textContent=toPersianNum(pad2(jalaliWheelState.day));
    modal.querySelector('[data-jalali-preview]').textContent=toPersianNum(jalaliWheelState.year + '/' + pad2(jalaliWheelState.month) + '/' + pad2(jalaliWheelState.day));
  }
  function openDatePickerFor(input){
    var parts=readInputDate(input); jalaliWheelState.input=input; jalaliWheelState.year=Math.max(1300,Math.min(1500,parts[0])); jalaliWheelState.month=Math.max(1,Math.min(12,parts[1])); jalaliWheelState.day=Math.max(1,Math.min(31,parts[2]));
    var modal=ensureJalaliWheel(); renderJalaliWheel(); modal.hidden=false; requestAnimationFrame(function(){ modal.classList.add('is-visible'); });
  }
  function closeJalaliWheel(){ var modal=ensureJalaliWheel(); modal.classList.remove('is-visible'); setTimeout(function(){ modal.hidden=true; }, 220); }
  function attachDatePickers(){
    document.querySelectorAll('.to-persian').forEach(input => {
      input.setAttribute('readonly','readonly'); input.setAttribute('autocomplete','off'); input.style.cursor='pointer';
      input.onclick = () => openDatePickerFor(input);
      input.onkeydown = (event) => { if(event.key === 'Enter' || event.key === ' ') { event.preventDefault(); openDatePickerFor(input); } };
    });
  }

  function openLightbox(src){
    if(!src || src.includes('default-avatar.svg')) return;
    document.getElementById('lightbox-img').src = src.replace("=s400", "=s2000");
    document.getElementById('lightbox').style.display = 'flex';
  }

  function autoGrow(el) { el.style.height='auto'; el.style.height=(el.scrollHeight)+'px'; }

  function toggleDark(){
    document.body.classList.toggle('dark-mode');
    const isDark = document.body.classList.contains('dark-mode');
    localStorage.setItem('chsh_dark_theme', isDark ? '1' : '0');
  }
  window.toggleDark = toggleDark;

  window.addEventListener('load', () => {
    try {
      if (localStorage.getItem('chsh_dark_theme') === '1') {
        document.body.classList.add('dark-mode');
      }
      const t=new Date().toLocaleDateString('fa-IR');
      document.querySelectorAll('.to-persian').forEach(i=>i.value=toPersianNum(t));
      initPersianInputs();
      attachDatePickers();

      // Keyboard accessibility for non-native buttons
      document.querySelectorAll('.menu-item, .nav-item, .tool-btn, .menu-btn, .tab').forEach(el => {
        if (!el.getAttribute('role')) el.setAttribute('role', 'button');
        if (el.tabIndex < 0) el.tabIndex = 0;
      });

      if (!window.__keyboardBound) {
        document.addEventListener('keydown', e => {
          if ((e.key === 'Enter' || e.key === ' ') && e.target.getAttribute('role') === 'button') {
            if (e.target.tagName !== 'BUTTON' && e.target.tagName !== 'A') {
              e.preventDefault();
              e.target.click();
            }
          }
        });
        window.__keyboardBound = true;
      }
      if(document.getElementById('header-date')) document.getElementById('header-date').innerText = t;
      try { localStorage.removeItem('charshanbeAppData'); } catch(e) {}
      if (window.CHSH && window.CHSH.initialPage && window.CHSH.initialPage !== 'dash') {
        nav(window.CHSH.initialPage, true);
      }
      fetchData();
    } catch(e) { console.error(e); }
  });

  function activeViewName(){
    const active = document.querySelector('.view.active');
    return active && active.id ? active.id.replace('view-','') : 'dash';
  }
  function loadLazyData(key, action, apply, done){
    if (DB[key]) { if(done) done(); return; }
    if (lazyLoads[key]) return;
    lazyLoads[key] = true;
    const target = key === 'plans' ? 'plans-list' : (key === 'notes' ? 'notes-history' : 'archive-list');
    const box = document.getElementById(target);
    if (box) box.innerHTML = '<p style="opacity:.65;text-align:center;padding:18px;">در حال دریافت اطلاعات...</p>';
    app.run.withSuccessHandler(r=>{
      lazyLoads[key] = false;
      apply(r || {});
      if(done) done();
    }).withFailureHandler(e=>{
      lazyLoads[key] = false;
      if (box) box.innerHTML = '<p style="opacity:.65;text-align:center;padding:18px;">دریافت اطلاعات انجام نشد.</p>';
      showAlert('خطا در دریافت اطلاعات: ' + e.message, 'error');
    })[action]();
  }
  function ensurePlansData(done){
    if (DB.plans) { if(done) done(); return; }
    loadPlansPage(false, done);
  }
  function ensureNotesData(done){
    if (DB.notes) { if(done) done(); return; }
    loadNotesPage(false, done);
  }
  function loadPlansPage(append = true, done){
    if (lazyLoads.plans || (append && lazyDone.plans)) return;
    lazyLoads.plans = true;
    const offset = append ? lazyOffsets.plans : 0;
    const box = document.getElementById('plans-list');
    if (!append && box) box.innerHTML = '<p style="opacity:.65;text-align:center;padding:18px;">در حال دریافت طرح درس‌ها...</p>';
    app.run.withSuccessHandler(r=>{
      lazyLoads.plans = false;
      const rows = r.plans || [];
      DB.plans = append && DB.plans ? DB.plans.concat(rows) : rows;
      lazyOffsets.plans = offset + rows.length;
      lazyDone.plans = rows.length < PAGE_SIZE.plans;
      renderPlans();
      if(done) done();
    }).withFailureHandler(e=>{ lazyLoads.plans = false; showAlert('خطا در دریافت طرح درس‌ها: ' + e.message, 'error'); }).getPlansData(PAGE_SIZE.plans, offset);
  }
  function loadNotesPage(append = true, done){
    if (lazyLoads.notes || (append && lazyDone.notes)) return;
    lazyLoads.notes = true;
    const offset = append ? lazyOffsets.notes : 0;
    const box = document.getElementById('notes-history');
    if (!append && box) box.innerHTML = '<p style="opacity:.65;text-align:center;padding:18px;">در حال دریافت یادداشت‌ها...</p>';
    app.run.withSuccessHandler(r=>{
      lazyLoads.notes = false;
      const rows = r.notes || [];
      DB.notes = append && DB.notes ? DB.notes.concat(rows) : rows;
      lazyOffsets.notes = offset + rows.length;
      lazyDone.notes = rows.length < PAGE_SIZE.notes;
      renderNotes();
      if(done) done();
    }).withFailureHandler(e=>{ lazyLoads.notes = false; showAlert('خطا در دریافت یادداشت‌ها: ' + e.message, 'error'); }).getNotesData(PAGE_SIZE.notes, offset);
  }
  function ensureArchiveData(done){ loadArchiveData(currentArchiveType || 'grouping', '', done); }
  function loadArchiveData(type, query, done){
    currentArchiveType = type || currentArchiveType || 'grouping';
    const list = document.getElementById('archive-list');
    if (list) list.innerHTML = '<p style="opacity:.65;text-align:center;padding:18px;">در حال دریافت آرشیو...</p>';
    app.run.withSuccessHandler(r=>{
      DB.moduleHistory = r.moduleHistory || [];
      if(done) done(); else renderArchive(currentArchiveType);
    }).withFailureHandler(e=>{
      if (list) list.innerHTML = '<p style="opacity:.65;text-align:center;padding:18px;">دریافت آرشیو انجام نشد.</p>';
      showAlert('خطا در دریافت آرشیو: ' + e.message, 'error');
    }).getArchiveData(currentArchiveType, query || '', 180);
  }

  function renderView(v){
      if(!DB || !DB.students) return;
      // Re-apply accessibility traits for dynamically rendered views
      document.querySelectorAll('.menu-item, .nav-item, .tool-btn, .menu-btn, .tab').forEach(el => {
        if (!el.getAttribute('role')) el.setAttribute('role', 'button');
        if (el.tabIndex < 0) el.tabIndex = 0;
      });
      try {
        if(v === 'dash') { renderDash(); renderAlerts(); }
        else if(v === 'profiles') renderProfiles();
        else if(v === 'att') { renderAtt(); loadAttendanceHistory(false); }
        else if(v === 'plans') ensurePlansData(renderPlans);
        else if(v === 'notes') ensureNotesData(renderNotes);
        else if(v === 'settings') renderSettings();
        else if(v === 'score') renderScoreList();
        else if(v === 'archive') ensureArchiveData(() => renderArchive(currentArchiveType || 'grouping'));
      } catch(e) { console.error(e); }
  }

  function renderAll(){
      ['dash','profiles','att','settings','score'].forEach(renderView);
  }

  function fetchData(){
    showLoading();
    app.run.withSuccessHandler(d=>{
      DB=d;
      renderView(activeViewName());
      if(activeViewName() !== 'dash') renderView('dash');
      hideLoading();
      const later = window.requestIdleCallback || (fn => setTimeout(fn, 120));
      later(() => {
        if (activeViewName() === 'dash') app.run.withSuccessHandler(mi=>{ renderClassMI(mi); }).getClassMIData();
      });
    }).withFailureHandler(e => {
        hideLoading();
        showAlert('خطا در دریافت اطلاعات از سرور محلی.\n' + e.message, 'error');
    }).getInitialData();
  }

  function renderDash(){
    if(!DB.students) return;
    document.getElementById('top-student').innerText = DB.topStudent;
    const dashCount = document.getElementById('dash-count'); if (dashCount) dashCount.innerText = toPersianNum((DB.students || []).length);
    const dashSessionCount = document.getElementById('dash-session-count'); if (dashSessionCount) dashSessionCount.innerText = toPersianNum(DB.sessionCount ?? (DB.trend.labels || []).length);
    const dashAlertCount = document.getElementById('dash-alert-count'); if (dashAlertCount) dashAlertCount.innerText = toPersianNum(getVisibleAlerts().length);
    const mainChartBox = document.getElementById('mainChartBox');
    if (mainChartBox) { const chartWidth = Math.max(320, (DB.trend.labels || []).length * 56); mainChartBox.style.width = chartWidth + 'px'; mainChartBox.style.minWidth = '100%'; }
    if(typeof Chart !== 'undefined') {
        if(chartInst) chartInst.destroy();
        chartInst = new Chart(document.getElementById('mainChart'), {
          type:'line',
          data:{labels:DB.trend.labels, datasets:[{label:'شاخص حضور هوشمند %', data:DB.trend.data, borderColor:'#0f766e', tension:0.35, fill:true, backgroundColor:'rgba(15,118,110,0.10)'}]},
          options:{
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins:{
              legend:{display:false},
              verticalHoverLine: {},
              tooltip: {
                callbacks: {
                  label: (ctx) => {
                    let d = (DB.trend.details && DB.trend.details[ctx.dataIndex]) || {};
                    let b = d.lateBuckets || {};
                    let lateBreakdown = `تاخیرها: ۰-۵د ${b['0_5'] || 0} | ۶-۱۰د ${b['6_10'] || 0} | ۱۱-۱۵د ${b['11_15'] || 0} | ۱۶+د ${(b['16_20'] || 0) + (b['21_30'] || 0) + (b['31_plus'] || 0)}`;
                    return [`شاخص هوشمند: ${ctx.raw}%`, `حاضر کامل: ${d.p || 0} نفر`, `تاخیر: ${d.l || 0} نفر / ${d.lateMinutes || 0} دقیقه`, lateBreakdown, `غیبت: ${d.a || 0} | موجه: ${d.e || 0}`, `درصد خام حضور: ${d.rawPresentPercent ?? ctx.raw}%`];
                  }
                }
              }
            },
            scales:{y:{beginAtZero:true, max:100}}
          }
        });
    }
    let h = "";
    DB.students.forEach(s=>{
      h += `<div class="dash-item" onclick="openProfile('${s.name}')">
        <div style="display:flex; align-items:center; gap:12px;"><img src="${s.image}" class="avatar-sm" onerror="this.src='assets/default-avatar.svg'"><b>${s.name} ${s.fire?'<span class="fire-mark" title="۸ جلسه آخر بدون غیبت موجه یا غیرموجه" aria-label="۸ جلسه آخر بدون غیبت موجه یا غیرموجه">🔥</span>':''}</b></div>
        <span class="score-badge">${toPersianNum(s.score)}</span>
      </div>`;
    });
    document.getElementById('dash-list').innerHTML = h;
  }

  function renderProfiles(){
    let h="";
    if (DB.students && DB.students.length > 0) {
      DB.students.forEach(s=> h+=`<div class="dash-item profile-list-item" onclick="openProfile('${s.name}')"><div style="display:flex; align-items:center; gap:12px; min-width:0;"><img src="${s.image}" class="avatar-sm" onerror="this.src='assets/default-avatar.svg'"><span style="font-weight:950; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">${s.name}</span></div><span class="score-badge">${toPersianNum(s.score || 0)}</span></div>`);
    } else {
      h = emptyState('👥','هنوز متربی‌ای اضافه نکرده‌ای','نام متربی را در کادر بالا بنویس و دکمه‌ی + را بزن.', "document.getElementById('new-name').focus()", '✍️ افزودن اولین متربی');
    }
    document.getElementById('profiles-list').innerHTML=h;
  }

  // حالت خالیِ استاندارد و دعوت‌کننده برای صفحه‌های بدون داده
  function emptyState(icon, title, sub, action, btnLabel){
    return `<div class="empty-state"><div class="empty-ico">${icon}</div><b>${title}</b>${sub?`<small>${sub}</small>`:''}${action?`<button class="btn btn-blue empty-cta" onclick="${action}">${btnLabel}</button>`:''}</div>`;
  }
  function attAttr(s){ return String(s==null?'':s).replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;'); }

  // ردیف حضور و غیاب: چهار دکمه‌ی وضعیت لمسی به‌جای dropdown — مناسب موبایل
  const ATT_SEG = [['Present','حاضر'],['Late','تاخیر'],['Excused','موجه'],['Absent','غیبت']];
  function attRowHtml(s){
    if(!TEMP[s.name]) TEMP[s.name]={s:'Present', m:0, r:''};
    const t = TEMP[s.name];
    const st = t.s, m = t.m, r = t.r || '';
    const seg = ATT_SEG.map(([v,label])=>`<button type="button" class="att-seg-btn ${v.toLowerCase()} ${st===v?'active':''}" data-v="${v}">${label}</button>`).join('');
    const lateBadge = (st==='Late'&&m)?`<span class="att-late-badge" onclick="editAttLate('${attAttr(s.name)}', event)" title="ویرایش دقایق تاخیر">${toPersianNum(m)} دقیقه</span>`:'';
    let reasonBadge = '';
    if(st==='Absent' || st==='Excused'){
      if(r){
        reasonBadge = `<span class="att-reason-badge ${st.toLowerCase()}" onclick="editAttReason('${attAttr(s.name)}', event)" title="علت: ${attAttr(r)}">علت: ${attAttr(r)} ✏️</span>`;
      } else {
        reasonBadge = `<span class="att-reason-badge empty ${st.toLowerCase()}" onclick="editAttReason('${attAttr(s.name)}', event)" title="ثبت علت عدم حضور">+ ثبت علت</span>`;
      }
    }
    return `<div class="att-row" data-name="${attAttr(s.name)}">
      <div class="att-row-head"><img src="${s.image}" class="avatar-sm" onerror="this.src='assets/default-avatar.svg'"><b>${s.name}</b>${lateBadge}${reasonBadge}</div>
      <div class="att-seg">${seg}</div>
    </div>`;
  }
  function renderAtt(){
    const list = document.getElementById('att-list');
    if(!DB.students || !DB.students.length){
      list.innerHTML = emptyState('👥','هنوز متربی‌ای اضافه نکرده‌ای','برای ثبت حضور، اول از بخش «پروفایل‌ها» متربی اضافه کن.', "nav('profiles')", '➕ افزودن متربی');
      return;
    }
    list.innerHTML = DB.students.map(attRowHtml).join('');
    bindAttOnce();
  }
  function bindAttOnce(){
    if(window.__attBound) return; window.__attBound = true;
    document.getElementById('att-list').addEventListener('click', e=>{
      const btn = e.target.closest('.att-seg-btn'); if(!btn) return;
      const row = btn.closest('.att-row'); if(!row) return;
      setAtt(row.getAttribute('data-name'), btn.dataset.v);
    });
  }
  function refreshAttRow(name){
    document.querySelectorAll('#att-list .att-row').forEach(row=>{
      if(row.getAttribute('data-name')!==name) return;
      const t = TEMP[name] || {s:'Present', m:0, r:''};
      row.querySelectorAll('.att-seg-btn').forEach(b=> b.classList.toggle('active', b.dataset.v===t.s));
      const head = row.querySelector('.att-row-head');
      let lateBadge = head.querySelector('.att-late-badge');
      let reasonBadge = head.querySelector('.att-reason-badge');

      if(t.s==='Late' && t.m){
        if(!lateBadge){
          lateBadge=document.createElement('span');
          lateBadge.className='att-late-badge';
          lateBadge.onclick=(e)=>editAttLate(name, e);
          head.appendChild(lateBadge);
        }
        lateBadge.title = 'ویرایش دقایق تاخیر';
        lateBadge.textContent = toPersianNum(t.m)+' دقیقه';
      } else if(lateBadge){
        lateBadge.remove();
      }

      if(t.s==='Absent' || t.s==='Excused'){
        if(!reasonBadge){
          reasonBadge=document.createElement('span');
          reasonBadge.onclick=(e)=>editAttReason(name, e);
          head.appendChild(reasonBadge);
        }
        reasonBadge.className = 'att-reason-badge ' + (t.s==='Excused'?'excused':'') + (t.r?'':' empty');
        reasonBadge.textContent = t.r ? ('علت: ' + t.r + ' ✏️') : '+ ثبت علت';
        reasonBadge.title = t.r ? ('علت: ' + t.r) : 'ثبت علت عدم حضور';
      } else if(reasonBadge){
        reasonBadge.remove();
      }
    });
  }

  function renderPlans(){
    let h = "";
    if (DB.plans && DB.plans.length > 0) {
      DB.plans.forEach(p=>{
        let mods = []; try { mods = JSON.parse(p.modules||"[]"); } catch(e) {}
        let mHtml = p.sin?`<div style="white-space:pre-wrap; background:#f1f5f9; padding:12px; border-radius:12px; font-size:0.9rem; margin-bottom:15px; line-height:1.6; border-right:5px solid #cbd5e1;">${p.sin}</div>`:"";
        let mainType = "other";
        mods.forEach(m=>{
          let its = ""; (m.items||[]).forEach(it=>{ its+=`<span class="mat-chip ${it.type}">${it.type=='buy'?'🛒':'✅'} ${it.name}</span>`; });
          if (m.type === 'game') mainType = 'game';
          else if (m.type === 'story') mainType = 'story';
          else if (m.type === 'yade_hazrat') mainType = 'hazrat';
          let typeName = m.type === 'yade_hazrat' ? 'یاد حضرت' : (m.type === 'game' ? 'بازی' : (m.type === 'story' ? 'داستان' : (m.type === 'trip' ? 'اردو' : 'سایر')));
          mHtml += `<div style="margin-top:12px; border-right:4px solid var(--primary); padding-right:12px;"><b>${typeName}:</b> ${m.name}<br><div style="display:flex; flex-wrap:wrap; margin-top:5px;">${its}</div></div>`;
        });
        const files = p.files || [];
        const filesHtml = `<div style="margin-top:16px; padding-top:12px; border-top:1px dashed var(--border);"><b>📎 فایل‌های طرح درس:</b><div style="margin-top:8px;">${files.length ? files.map(f=>`<a class="file-chip" href="${f.path}" target="_blank">📄 ${f.name}</a><button onclick="deletePlanFile(${f.id}, event)" style="border:0;background:transparent;color:#d64045;cursor:pointer;">×</button>`).join('') : '<small style="opacity:.65;">فایلی ثبت نشده است.</small>'}</div><div style="display:flex; gap:8px; margin-top:10px;"><input type="file" id="plan-file-${p.id}" multiple style="margin:0;"><button class="btn btn-outline" style="width:auto; padding:10px 14px;" onclick="uploadPlanFile('${p.id}', event)">آپلود فایل‌ها</button></div></div>`;
        mHtml += filesHtml;
        h += `<div class="plan-card ${mainType}" id="plan-${p.id}"><div class="plan-header" onclick="this.parentElement.classList.toggle('open')"><div><b>${p.title}</b><br><small style="opacity:0.7;">📅 جلسه: ${toPersianNum(p.date)}</small></div><span style="font-size:.85rem; opacity:.75;">باز کردن</span></div><div class="plan-body">${mHtml}<div style="margin-top:20px; display:flex; gap:15px; border-top:1px solid var(--border); padding-top:12px;"><button onclick="editPlanClick('${p.id}', event)" style="color:var(--primary); background:none; border:none; cursor:pointer; font-weight:bold; font-size:16px; padding:10px;"><i class="fas fa-edit"></i> ویرایش</button><button onclick="askDeletePlan('${p.id}', event)" style="color:#ef4444; background:none; border:none; cursor:pointer; font-weight:bold; font-size:16px; padding:10px;"><i class="fas fa-trash"></i> حذف</button></div></div></div>`;
      });
    } else {
        h = "<p style='text-align:center; color:#888; padding:20px;'>هنوز طرح درسی ثبت نشده است.</p>";
    }
    if (DB.plans && DB.plans.length > 0 && !lazyDone.plans) h += `<button class="btn btn-outline" style="margin:8px auto 18px; max-width:260px;" onclick="loadPlansPage(true)">نمایش بیشتر طرح درس‌ها</button>`;
    document.getElementById('plans-list').innerHTML = h;
  }

  function renderNotes(){
    let h="";
    if(DB.notes) DB.notes.forEach(n=> h+=`<div class="note-item"><div class="note-head" onclick="this.parentElement.classList.toggle('open')"><span>📅 ${toPersianNum(n.date)}</span><span>🔽</span></div><div class="note-body">${n.text}</div></div>`);
    if(DB.notes && DB.notes.length > 0 && !lazyDone.notes) h += `<button class="btn btn-outline" style="margin:8px auto 18px; max-width:260px;" onclick="loadNotesPage(true)">نمایش بیشتر یادداشت‌ها</button>`;
    if(!h) h = emptyState('📒','هنوز یادداشتی ثبت نشده','یادداشت جلسه یا صورتجلسه را در کادر بالا بنویس و ذخیره کن.', '', '');
    document.getElementById('notes-history').innerHTML=h;
  }
  function renderScoreList(){ let h=""; if(DB.students) DB.students.forEach(s=> h+=`<div class="dash-item" onclick="manualScore('${s.name}')"><span>${s.name}</span><span class="score-badge">دستی: ${toPersianNum(s.manualScore)}</span></div>`); if(!h) h = emptyState('⭐','هنوز متربی‌ای برای امتیازدهی نیست','اول از بخش «پروفایل‌ها» متربی اضافه کن.', "nav('profiles')", '➕ افزودن متربی'); document.getElementById('score-list').innerHTML=h; }
  function renderSettings(){ if(!DB.settings) return; const s=DB.settings, g=id=>document.getElementById(id), setV=(id,v)=>{const e=g(id); if(e) e.value=v||'';}, setC=(id,v)=>{const e=g(id); if(e) e.checked=v;};
    setV('st-token', s.BOT_TOKEN); setV('st-admin', s.ADMIN_CHAT_IDS); setV('st-proxy', s.TG_PROXY); setV('st-site-url', s.SITE_URL);
    setC('st-tg-score', (s.TG_NOTIFY_SCORE||'1')==='1'); setC('st-tg-att', (s.TG_NOTIFY_ATTENDANCE||'1')==='1'); setC('st-tg-plan', (s.TG_NOTIFY_PLAN||'1')==='1'); setC('st-tg-note', (s.TG_NOTIFY_NOTE||'0')==='1'); setC('st-tg-alerts', (s.TG_NOTIFY_ALERTS||'1')==='1');
  }

  function renderClassMI(mi) {
    if(!mi.labels || mi.labels.length === 0) return;
    document.getElementById('class-mi-section').style.display = 'block';
    if(classMIChart) classMIChart.destroy();
    classMIChart = new Chart(document.getElementById('classMIChart'), {
      type: 'bar',
      data: { labels: mi.labels, datasets: [{ label: 'میانگین کلاس', data: mi.averages, backgroundColor: 'rgba(129, 140, 248, 0.8)', borderRadius: 8, borderWidth: 1, borderColor: '#4f46e5' }] },
      options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true, grid: { display: false } }, y: { ticks: { font: { size: 10 } } } } }
    });
  }

  function openProfile(n){
    showLoading(); currentStudent = n;
    app.run.withSuccessHandler(d=>{
      hideLoading();
      if(!d.success) return showAlert(d.msg, 'error');
      const i = d.info;
      document.getElementById('sp-img').src=i.displayImage || 'assets/default-avatar.svg';
      document.getElementById('sp-name').innerText = i.name || "";
      document.getElementById('sp-score').innerText=toPersianNum(i.score)+" امتیاز";
      document.getElementById('sp-input-img').value=i.image;
      document.getElementById('sp-bio').value=i.bio || "";
      document.getElementById('sp-parent-note').value=i.parentNote || "";
      const cont = document.getElementById('phones-container'); cont.innerHTML = "";
      let ps = []; try { ps = JSON.parse(i.phone || "[]"); } catch(e) { if(i.phone) ps.push({label:"تلفن", number:i.phone}); }
      if(ps.length === 0) addPhoneField(); else ps.forEach(p => addPhoneField(p.label, p.number));
      document.getElementById('sp-dob').value=i.dob || "";
      document.getElementById('sp-school').value=i.school || "";
      document.getElementById('sp-medical').value=i.medical || "";
      document.querySelectorAll('#view-single-profile input, #view-single-profile textarea').forEach(persianizeInputValue);
      renderMIChart(d.mi.chart); renderMIAccordion(d.mi.config, n, d.mi.traitStates); renderFullHistory(d.fullHistory); renderGrowthChart(d.stats.growth); renderSummaryStats(d.stats.stats);
      nav('single-profile');
    }).withFailureHandler(e => { hideLoading(); showAlert("خطا در بارگذاری پروفایل: " + e.message, 'error'); }).getFullStudentProfile(n);
  }

  function addPhoneField(l="", n="") {
    const div = document.createElement('div'); div.className="phone-item"; div.style.display="flex"; div.style.gap="10px"; div.style.marginBottom="10px";
    div.innerHTML = `<input type="text" placeholder="عنوان" value="${l}" style="flex:1; margin:0;"><input type="text" placeholder="شماره" value="${n}" style="flex:1.5; margin:0;"><button class="btn btn-red" style="width:40px; padding:0; border-radius:12px;" onclick="this.parentElement.remove()">✕</button>`;
    document.getElementById('phones-container').appendChild(div); div.querySelectorAll('input').forEach(persianizeInputValue);
  }

  function setAtt(name, v){
    if(v==='Late'){
      showPrompt("چند دقیقه تاخیر؟", "15", (m) => {
        const min = parseInt(toEnglishNum(String(m||'')).replace(/[^0-9]/g,''), 10);
        if(!m || isNaN(min)) TEMP[name] = {s:'Present', m:0, r:''}; // انصراف از دقیقه = حاضر
        else TEMP[name] = {s:'Late', m:min, r:''};
        refreshAttRow(name);
      });
    } else if(v==='Absent' || v==='Excused'){
      const curReason = TEMP[name]?.r || '';
      const promptTitle = v==='Absent' ? `علت غیبت ${name} (اختیاری):` : `علت موجه بودن ${name} (اختیاری):`;
      showPrompt(promptTitle, curReason, (r) => {
        const reason = (r !== null) ? String(r).trim() : curReason;
        TEMP[name] = {s:v, m:0, r:reason};
        refreshAttRow(name);
      });
    } else {
      TEMP[name] = {s:'Present', m:0, r:''};
      refreshAttRow(name);
    }
  }

  window.editAttReason = function(name, event){
    if(event) event.stopPropagation();
    const cur = TEMP[name]?.r || '';
    const st = TEMP[name]?.s || 'Absent';
    const title = st==='Excused' ? `علت موجه بودن ${name} (اختیاری):` : `علت غیبت ${name} (اختیاری):`;
    showPrompt(title, cur, (r) => {
      if(r !== null){
        if(!TEMP[name]) TEMP[name] = {s:st, m:0, r:''};
        TEMP[name].r = String(r).trim();
        refreshAttRow(name);
      }
    });
  };

  window.editAttLate = function(name, event){
    if(event) event.stopPropagation();
    const curM = TEMP[name]?.m || 15;
    showPrompt("چند دقیقه تاخیر؟", String(curM), (m) => {
      const min = parseInt(toEnglishNum(String(m||'')).replace(/[^0-9]/g,''), 10);
      if(m && !isNaN(min)) {
        if(!TEMP[name]) TEMP[name] = {s:'Late', m:min, r:''};
        TEMP[name].m = min;
        refreshAttRow(name);
      }
    });
  };

  function manualScore(n){
    showPrompt(`امتیاز برای ${n}:`, "1", (p) => {
      if(p) app.run.withSuccessHandler(fetchData).addManualScore(n,p);
    });
  }

  function markAllPresent(){ (DB.students||[]).forEach(s=>TEMP[s.name]={s:'Present',m:0,r:''}); (DB.students||[]).forEach(s=>refreshAttRow(s.name)); }
  function submitData(btn){
    if(!DB.students || DB.students.length===0) return showAlert("دانش‌آموزی ثبت نشده است.");
    const done=btnBusy(btn,'در حال ثبت...');
    const recs=DB.students.map(s=>{
      const t = TEMP[s.name] || {s:'Present', m:0, r:''};
      return {
        name: s.name,
        status: t.s,
        min: t.m,
        reason: t.r || ''
      };
    });
    app.run.withSuccessHandler(r=>{
      attendanceHistoryState.offset=0;
      attendanceHistoryState.done=false;
      done();
      showToast(r.msg);
      fetchData();
    }).withFailureHandler(e=>{
      done();
      showAlert(cleanServerError(e.message),'error');
    }).submitAttendance({date:document.getElementById('att-date').value, records:recs});
  }
  function saveNote(btn){ const d=document.getElementById('note-date').value; const t=document.getElementById('note-text').value; if(!t) return showToast('متن یادداشت خالی است','error'); const done=btnBusy(btn,'در حال ذخیره...'); app.run.withSuccessHandler(r=>{DB.notes=null; lazyOffsets.notes=0; lazyDone.notes=false; done(); showToast(r.msg); document.getElementById('note-text').value=""; fetchData();}).withFailureHandler(e=>{ done(); showAlert(e.message,'error'); }).saveNote(d,t); }
  function filterNotes(t){ document.querySelectorAll('.note-item').forEach(i=> i.style.display=i.innerText.includes(t)?'block':'none'); }
  function filterAtt(t){ document.querySelectorAll('#att-list .att-row').forEach(i=> i.style.display=i.innerText.includes(t)?'':'none'); }
  function saveSettings(){ const g=id=>document.getElementById(id); const d={ BOT_TOKEN: g('st-token').value.trim(), ADMIN_CHAT_IDS: g('st-admin').value.trim(), TG_PROXY: (g('st-proxy')||{}).value?.trim()||'', SITE_URL: (g('st-site-url')||{}).value?.trim()||'', TG_NOTIFY_SCORE: g('st-tg-score').checked?'1':'0', TG_NOTIFY_ATTENDANCE: g('st-tg-att').checked?'1':'0', TG_NOTIFY_PLAN: g('st-tg-plan').checked?'1':'0', TG_NOTIFY_NOTE: g('st-tg-note').checked?'1':'0', TG_NOTIFY_ALERTS: g('st-tg-alerts').checked?'1':'0' }; showLoading(); app.run.withSuccessHandler(r=>{hideLoading(); showToast(r.msg); fetchData();}).saveSystemSettings(d); }
  // حالت «مشغول» روی خود دکمه، به‌جای اسکلتون تمام‌صفحه که برای یک اکشن کوچک آزاردهنده بود.
  function btnBusy(btn, busyText){
    if(!btn) return function(){};
    const oldHtml = btn.innerHTML, oldDis = btn.disabled;
    btn.disabled = true; btn.classList.add('is-busy');
    btn.innerHTML = '<span class="btn-spinner"></span> ' + (busyText || 'در حال انجام...');
    return function(){ btn.disabled = oldDis; btn.classList.remove('is-busy'); btn.innerHTML = oldHtml; };
  }
  function testTelegram(btn){ const done=btnBusy(btn,'در حال بررسی...'); app.run.withSuccessHandler(r=>{ done(); showAlert(r.msg, r.success?'success':'error'); }).withFailureHandler(e=>{ done(); showAlert('خطا در اتصال: '+e.message,'error'); }).testTelegramConnection(); }
  function setupWebhook(btn){ const done=btnBusy(btn,'در حال تنظیم...'); app.run.withSuccessHandler(r=>{ done(); showAlert(r,'info'); }).withFailureHandler(e=>{ done(); showAlert('خطا: '+e.message,'error'); }).setupTelegramWebhook(); }
  function addItemToMod(btn, id, ty){ let inp = btn.parentElement.querySelector('input'); let val = inp.value.trim(); if(!val) return; let ic = ty=='buy'?'🛒':'✅'; document.getElementById('tags-'+id).insertAdjacentHTML('beforeend', `<span class="mat-chip ${ty}" data-type="${ty}"><span>${ic} ${val}</span><span style="cursor:pointer; margin-right:6px;" onclick="this.parentElement.remove()">×</span></span>`); inp.value=""; }
  function delPlan(id){ app.run.withSuccessHandler(()=>{ DB.plans=null; lazyOffsets.plans=0; lazyDone.plans=false; DB.moduleHistory=null; fetchData(); }).deletePlan(id); }
  function uploadProfileImage(){
    const fileInput = document.getElementById('sp-image-file');
    if(!currentStudent) return showAlert('اول یک متربی را باز کنید.', 'error');
    if(!fileInput.files || !fileInput.files[0]) return showAlert('لطفا یک عکس انتخاب کنید.');
    const form = new FormData();
    form.append('studentName', currentStudent);
    form.append('image', fileInput.files[0]);
    showLoading();
    fetch('api.php?action=uploadStudentImage', {method:'POST', body: form, headers: (window.CHSH && window.CHSH.csrf) ? {'X-CSRF-Token': window.CHSH.csrf} : {}})
      .then(async res => {
        const text = await res.text();
        let data; try { data = JSON.parse(text); } catch(e) { throw new Error(text || 'پاسخ نامعتبر از سرور'); }
        if(!res.ok || !data.success) throw new Error(data.msg || data.message || 'آپلود انجام نشد');
        return data;
      })
      .then(data => {
        hideLoading();
        document.getElementById('sp-input-img').value = data.path;
        document.getElementById('sp-img').src = data.displayImage || data.path;
        fileInput.value = '';
        showToast(data.msg || 'عکس آپلود شد');
        fetchData();
      })
      .catch(err => { hideLoading(); showAlert(err.message, 'error'); });
  }

  function askDeleteStudent() {
    if(!currentStudent) return;
    showConfirm(`آیا از حذف کامل "${currentStudent}" اطمینان دارید؟\nاین عمل غیرقابل بازگشت است و تمامی سوابق حضور، امتیازات و یادداشت‌های مربوطه برای همیشه پاک خواهد شد.`, () => {
      showLoading();
      app.run.withSuccessHandler(r => {
        hideLoading();
        showToast(r.msg);
        nav('profiles');
        fetchData();
      }).withFailureHandler(e => {
        hideLoading();
        showAlert(e.message, 'error');
      }).deleteStudent(currentStudent);
    });
  }

  function saveProfile(){
    let ps = []; document.querySelectorAll('.phone-item').forEach(p => { let inps = p.querySelectorAll('input'); if(inps[1].value) ps.push({label: inps[0].value, number: inps[1].value}); });
    showLoading(); const d={originalName:currentStudent, image:document.getElementById('sp-input-img').value, bio:document.getElementById('sp-bio').value, parentNote:document.getElementById('sp-parent-note').value, phone:JSON.stringify(ps), dob:document.getElementById('sp-dob').value, school:document.getElementById('sp-school').value, medical:document.getElementById('sp-medical').value}; app.run.withSuccessHandler(r=>{ hideLoading(); showToast(r.msg); fetchData(); }).updateStudentProfile(d);
  }
  function addStudent(){ let n=document.getElementById('new-name').value; if(n) app.run.withSuccessHandler(fetchData).addStudent(n); }
  function openPlanModal(){ document.getElementById('p-id').value=""; document.getElementById('modules-container').innerHTML=""; document.getElementById('plan-modal').style.display='flex'; }

  function editPlan(id){
    const p = DB.plans.find(x => x.id == id);
    if(!p) return showAlert("خطا: طرح درس پیدا نشد.", 'error');
    document.getElementById('p-id').value=p.id;
    document.getElementById('p-date').value=toPersianNum(p.date);
    document.getElementById('p-title').value=p.title;
    document.getElementById('p-sin').value=p.sin||"";
    document.getElementById('modules-container').innerHTML="";
    let ms = []; try { ms = JSON.parse(p.modules||"[]"); } catch(e) {}
    ms.forEach(m=> {
      let type = m.type;
      if(type === 'بازی') type = 'game'; else if(type === 'داستان') type = 'story'; else if(type === 'اردو') type = 'trip'; else if(type === 'یاد حضرت') type = 'yade_hazrat'; else type = 'other';
      addModule(type, m.name, m.items||[], m.desc);
    });
    document.getElementById('plan-modal').style.display='flex';
  }

  function addModule(type, name="", items=[], desc=""){
    const c = document.getElementById('modules-container');
    const d = document.createElement('div'); d.className="mod-box-full"; d.style.border="1px solid var(--border)"; d.style.padding="15px"; d.style.borderRadius="15px"; d.style.margin="10px 0"; d.style.background="var(--bg)";
    let faMap = {'game':'بازی','story':'داستان','trip':'اردو','other':'سایر', 'yade_hazrat': 'یاد حضرت'};
    let label = faMap[type] || 'سایر'; let id = Date.now()+Math.random();
    d.innerHTML = `<div style="display:flex; justify-content:space-between; margin-bottom:10px;"><small style="color:var(--primary); font-weight:bold;">بخش ${label}</small><span style="cursor:pointer; color:#ef4444; font-size:1.2rem;" onclick="this.parentElement.parentElement.remove()">✕</span></div>
      <input type="hidden" class="m-type" value="${type}">
      <input class="m-name" placeholder="عنوان این ماژول" value="${name}">
      <div style="display:flex; gap:8px;"><input class="m-new-mat" placeholder="نام وسیله لازم..." style="margin:0;"><button class="btn btn-green" style="width:45px; padding:0;" onclick="addItemToMod(this,'${id}','have')">✅</button><button class="btn btn-red" style="width:45px; padding:0;" onclick="addItemToMod(this,'${id}','buy')">🛒</button></div>
      <div class="m-tags-container" id="tags-${id}" style="display:flex; flex-wrap:wrap; margin-top:8px;">${items.map(it=>`<span class="mat-chip ${it.type}" data-type="${it.type}"><span>${it.type=='buy'?'🛒':'✅'} ${it.name}</span><span style="cursor:pointer; margin-right:6px;" onclick="this.parentElement.remove()">×</span></span>`).join('')}</div>
      <textarea class="m-desc" placeholder="توضیحات و نکات تکمیلی این بخش..." oninput="autoGrow(this)" style="margin-top:10px;">${desc}</textarea>`;
    c.appendChild(d);
  }

  function savePlan() {
    const idVal=document.getElementById('p-id').value; const dVal=document.getElementById('p-date').value; const tVal=document.getElementById('p-title').value; const sVal=document.getElementById('p-sin').value;
    if(!tVal) return showAlert("عنوان طرح درس اجباری است.");
    let mods = [];
    document.querySelectorAll('#modules-container > .mod-box-full').forEach(el=>{
      let its = []; el.querySelectorAll('.mat-chip').forEach(c=> its.push({name: c.querySelector('span').innerText.substring(2).trim(), type: c.classList.contains('buy') ? 'buy' : 'have'}));
      mods.push({type: el.querySelector('.m-type').value, name: el.querySelector('.m-name').value, desc: el.querySelector('.m-desc').value, items: its});
    });
    showLoading(); app.run.withSuccessHandler(r=>{ DB.plans=null; lazyOffsets.plans=0; lazyDone.plans=false; DB.moduleHistory=null; showToast(r.msg); document.getElementById('plan-modal').style.display='none'; fetchData(); }).savePlan(idVal,dVal,tVal,sVal,JSON.stringify(mods));
  }

  function renderMIChart(d){
    if(typeof Chart === 'undefined') return;
    const ctx = document.getElementById('sp-mi-chart');
    if(miChart) miChart.destroy();
    miChart = new Chart(ctx, {
      type:'bar',
      data:{labels:d.labels, datasets:[{label:'امتیاز هوش', data:d.data, backgroundColor:'#4f46e5', borderRadius: 5}]},
      options:{ indexAxis: 'y', maintainAspectRatio: false, responsive: true, plugins:{legend:{display:false}}, scales:{ x:{beginAtZero:true, grid:{display:false}}, y:{ticks:{autoSkip:false, font:{size:11}}} } }
    });
  }

  function renderMIAccordion(conf, name, traitStates){
    let h="";
    conf.forEach(c=>{
      let traitHtml = "";
      const allTraits = (c.pos || []).concat(c.neg || []);
      allTraits.forEach(t => {
        if(!t) return;
        let currentState = traitStates[c.type + "_" + t] || 0;
        let traitTitle = t; let traitDesc = "";
        if(t.includes(" (")) { const parts = t.split(" ("); traitTitle = parts[0]; traitDesc = parts[1].replace(")", ""); }
        traitHtml += `<div style="display:flex; align-items:center; justify-content:space-between; padding:12px 0; border-bottom:1px solid var(--border);"><div style="flex:1; padding-left:10px;"><div style="font-size:0.95rem; font-weight:600;">${traitTitle}</div>${traitDesc ? `<div style="font-size:0.75rem; color:#64748b; margin-top:2px;">${traitDesc}</div>` : ""}</div><div style="display:flex; gap:10px;"><button class="btn ${currentState === 1 ? 'btn-green' : 'btn-outline'}" style="width:42px; height:42px; padding:0; border-radius:12px; border-color:#10b981; color:${currentState === 1 ? 'white' : '#10b981'}; background:${currentState === 1 ? '#10b981' : 'transparent'}" onclick="logMI('${name}','${c.type}','${t}', ${currentState === 1 ? 0 : 1})"><i class="fas fa-check"></i></button><button class="btn ${currentState === -1 ? 'btn-red' : 'btn-outline'}" style="width:42px; height:42px; padding:0; border-radius:12px; border-color:#ef4444; color:${currentState === -1 ? 'white' : '#ef4444'}; background:${currentState === -1 ? '#ef4444' : 'transparent'}" onclick="logMI('${name}','${c.type}','${t}', ${currentState === -1 ? 0 : -1})"><i class="fas fa-times"></i></button></div></div>`;
      });
      h += `<div class="card" style="padding:15px; margin-bottom:12px; border:1px solid var(--border);"><div style="font-weight:bold; cursor:pointer; display:flex; justify-content:space-between; align-items:center; font-size:1.1rem;" onclick="this.nextElementSibling.style.display=this.nextElementSibling.style.display=='block'?'none':'block'"><span>${c.type}</span> <i class="fas fa-chevron-down" style="font-size:0.9rem; opacity:0.5;"></i></div><div style="display:none; padding-top:15px;"><div style="background:#f1f5f9; padding:12px; border-radius:12px; font-size:0.85rem; color:#475569; margin-bottom:15px; line-height:1.6; border-right:4px solid var(--primary);">${c.desc}</div>${traitHtml}</div></div>`;
    });
    document.getElementById('sp-mi-accordion').innerHTML = h;
  }

  function logMI(n,t,b,s){ showLoading(); app.run.withSuccessHandler(()=>{ hideLoading(); showToast("بروزرسانی شد"); openProfile(n); }).submitMILog(n,t,b,s); }

  function renderFullHistory(h){
    if(!h || h.length === 0) { document.getElementById('sp-mi-history').innerHTML = `<div style="text-align:center; padding:30px; color:#94a3b8;"><i class="fas fa-history" style="font-size:2rem; display:block; margin-bottom:10px; opacity:0.3;"></i>هنوز فعالیتی ثبت نشده است.</div>`; return; }
    document.getElementById('sp-mi-history').innerHTML = h.map(x=>{
      let badge = x.score ? `<b style="color:${x.score>0?'#10b981':'#ef4444'};">${x.score>0?'+':''}${toPersianNum(x.score)}</b>` : "";
      return `<div style="font-size:0.88rem; border-bottom:1px solid var(--border); padding:12px; display:flex; justify-content:space-between; align-items:center;">
        <div style="display:flex; align-items:center; gap:12px;"><span style="font-size:1.2rem;">${x.icon}</span><div><small style="opacity:0.6;">${toPersianNum(x.date)} - ${x.title}</small><br><b style="color:var(--text);">${x.desc}</b></div></div>${badge}</div>`;
    }).join('');
  }

  function renderSummaryStats(s) {
    const h = `
      <div style="text-align:center; padding:10px; background:#f0fdf4; border-radius:12px; border:1px solid #bbf7d0;">
        <small style="color:#15803d; display:block;">حضور به‌موقع</small>
        <b style="font-size:1.2rem;">${toPersianNum(s.p)}</b>
      </div>
      <div style="text-align:center; padding:10px; background:#fffbeb; border-radius:12px; border:1px solid #fef3c7;">
        <small style="color:#b45309; display:block;">حضور با تاخیر</small>
        <b style="font-size:1.2rem;">${toPersianNum(s.l)} <small>(${toPersianNum(s.lm)} د)</small></b>
      </div>
      <div style="text-align:center; padding:10px; background:#fef2f2; border-radius:12px; border:1px solid #fecaca;">
        <small style="color:#b91c1c; display:block;">غیبت</small>
        <b style="font-size:1.2rem;">${toPersianNum(s.a)}</b>
      </div>
      <div style="text-align:center; padding:10px; background:#f0f9ff; border-radius:12px; border:1px solid #bae6fd;">
        <small style="color:#0369a1; display:block;">غیبت موجه</small>
        <b style="font-size:1.2rem;">${toPersianNum(s.e)}</b>
      </div>
    `;
    document.getElementById('sp-summary-stats').innerHTML = h;
  }
  function renderGrowthChart(g){
    const empty = document.getElementById('sp-growth-empty');
    const box = document.getElementById('spGrowthChartBox');
    const labels = (g && g.labels) || [];
    if(empty) empty.style.display = labels.length ? 'none' : 'block';
    if(box) box.style.display = labels.length ? 'block' : 'none';
    if(!labels.length || typeof Chart === 'undefined') return;
    if(box) box.style.width = Math.max(320, labels.length * 58) + 'px';
    if(growthChart) growthChart.destroy();
    growthChart = new Chart(document.getElementById('sp-growth-chart'), {
      type:'line',
      data:{labels:g.labels, datasets:[{label:'امتیاز', data:g.data, borderColor:'#10b981', tension:0.4, fill:true, backgroundColor:'rgba(16,185,129,0.1)'}]},
      options:{responsive: true, maintainAspectRatio:false, plugins:{legend:{display:false}, tooltip:{callbacks:{label:(ctx)=>`امتیاز: ${ctx.raw}`}}}}
    });
  }

  function openReportCard() {
    if(!currentStudent) return;
    showLoading();
    app.run.withSuccessHandler(d=>{
      hideLoading();
      if(!d.success) return showAlert("خطا در دریافت اطلاعات", 'error');
      const info = d.info; const stats = d.stats; const today = new Date().toLocaleDateString('fa-IR');
      const parentNote = String(info.parentNote || info.parent_note || '').trim() || 'توضیحات خاصی ثبت نشده است.';
      document.getElementById('report-date').innerText = `تاریخ صدور: ${toPersianNum(today)}`;
      let h = `<div class="report-compact-head"><img src="${info.displayImage || 'assets/default-avatar.svg'}" onerror="this.src='assets/default-avatar.svg'"><div><small>کارنامه عملکرد</small><h3>${info.name}</h3><span class="score-badge">امتیاز کل: ${toPersianNum(stats.scores.total)}</span></div></div><div class="report-section report-compact-stats"><h4>📊 وضعیت حضور و غیاب</h4><div class="report-stat-row"><span>✅ حضور به‌موقع</span><b>${toPersianNum(stats.stats.p)}</b></div><div class="report-stat-row"><span>⏰ تاخیر</span><b>${toPersianNum(stats.stats.l)} / ${toPersianNum(stats.stats.lm)}د</b></div><div class="report-stat-row"><span>❌ غیبت</span><b>${toPersianNum(stats.stats.a)}</b></div><div class="report-stat-row"><span>🏳️ موجه</span><b>${toPersianNum(stats.stats.e)}</b></div></div><div class="report-section report-compact-mi"><h4>🧠 پروفایل هوش برتر</h4>${generateSimpleBarChart(d.mi.chart)}</div><div class="report-section report-parent-note"><h4>📝 یادداشت مربی برای والدین</h4><div>${parentNote}</div></div>`;
      document.getElementById('report-content').innerHTML = h;
      document.getElementById('report-modal').style.display = 'block';
    }).getFullStudentProfile(currentStudent);
  }

  function generateSimpleBarChart(chartData) {
    if(!chartData || !chartData.labels) return "";
    let html = '<div style="display:flex; flex-direction:column; gap:8px; margin-top:10px;">';
    let dataMap = chartData.labels.map((l, i) => ({label: l, value: chartData.data[i]}));
    dataMap.sort((a,b) => b.value - a.value);
    dataMap.forEach(item => {
      let pct = Math.min(100, (item.value / 10) * 100);
      html += `<div style="display:flex; align-items:center; font-size:0.85rem;"><span style="width:100px;">${item.label}</span><div style="flex:1; background:#eee; height:10px; border-radius:5px; overflow:hidden;"><div style="width:${pct}%; background:var(--primary); height:100%;"></div></div><span style="width:30px; text-align:left; margin-right:5px;">${toPersianNum(item.value)}</span></div>`;
    });
    html += '</div>'; return html;
  }

  function openTimer() { document.getElementById('timer-modal').style.display='flex'; }
  function setCustomTimer() { const m = parseInt(toEnglishNum(document.getElementById('t-min').value)) || 0; const s = parseInt(toEnglishNum(document.getElementById('t-sec').value)) || 0; if(m===0 && s===0) return showAlert("لطفا زمان را وارد کنید"); timerSec = (m*60) + s; updateTimerDisplay(); }
  function startTimer() { if(timerInterval) clearInterval(timerInterval); timerInterval = setInterval(()=>{ if(timerSec>0) { timerSec--; updateTimerDisplay(); } else { stopTimer(); showAlert("⏰ زمان تمام شد!"); } }, 1000); }
  function stopTimer() { clearInterval(timerInterval); }
  function resetTimer() { stopTimer(); timerSec=0; updateTimerDisplay(); }
  function updateTimerDisplay() { const m = Math.floor(timerSec/60); const s = timerSec%60; document.getElementById('timer-display').innerText = `${toPersianNum(m.toString().padStart(2,'0'))}:${toPersianNum(s.toString().padStart(2,'0'))}`; }

  function openManualGrouping() { if(!DB.students || DB.students.length===0) return showAlert("هنوز دانش‌آموزی ندارید!", 'error'); document.getElementById('g-date').value = toPersianNum(new Date().toLocaleDateString('fa-IR')); document.getElementById('g-model').value = ""; document.getElementById('g-current-name').value = ""; tempGroups = []; renderGroupStudentGrid(); renderAddedGroups(); document.getElementById('manual-grouping-modal').style.display='flex'; }
  function renderGroupStudentGrid() { let h = ""; DB.students.forEach(s => { h += `<div class="student-select-card" onclick="toggleStudentSelection(this, '${s.name}')"><img src="${s.image}" onerror="this.src='assets/default-avatar.svg'"><span>${s.name}</span><i class="fas fa-check-circle"></i></div>`; }); document.getElementById('g-students-list').innerHTML = h; }
  function toggleStudentSelection(el, name) { el.classList.toggle('selected'); }
  function addGroupToList() { const gName = document.getElementById('g-current-name').value; let members = []; document.querySelectorAll('.student-select-card.selected').forEach(el => { members.push(el.querySelector('span').innerText); }); if(!gName) return showAlert("لطفا نام گروه را وارد کنید"); if(members.length === 0) return showAlert("حداقل یک نفر را انتخاب کنید"); tempGroups.push({name: gName, members: members}); document.getElementById('g-current-name').value = ""; document.querySelectorAll('.student-select-card.selected').forEach(el => el.classList.remove('selected')); renderAddedGroups(); showToast("گروه اضافه شد"); }
  function renderAddedGroups() { let h = ""; tempGroups.forEach((g, index) => { h += `<div class="added-group-card"><div><b>${g.name}:</b> <span style="font-size:0.9rem; color:#666;">${g.members.join('، ')}</span></div><span style="color:red; cursor:pointer;" onclick="removeTempGroup(${index})"><i class="fas fa-trash"></i></span></div>`; }); document.getElementById('added-groups-container').innerHTML = h || '<small style="color:#999;">هنوز گروهی اضافه نشده است.</small>'; }
  function removeTempGroup(index) { tempGroups.splice(index, 1); renderAddedGroups(); }
  function saveFinalGrouping() { const d = document.getElementById('g-date').value; const model = document.getElementById('g-model').value; if(!model) return showAlert("لطفا عنوان مدل گروه‌بندی را وارد کنید"); if(tempGroups.length === 0) return showAlert("هیچ گروهی ایجاد نشده است!"); const details = JSON.stringify({ isMultiGroup: true, groups: tempGroups }); showLoading(); app.run.withSuccessHandler(r=>{ DB.moduleHistory=null; hideLoading(); showToast(r.msg); document.getElementById('manual-grouping-modal').style.display='none'; fetchData(); }).saveManualLog({date: d, type: 'grouping', title: model, details: details}); }
  function openFreeActivity() { document.getElementById('fa-date').value = toPersianNum(new Date().toLocaleDateString('fa-IR')); document.getElementById('free-activity-modal').style.display='flex'; }
  function saveFreeActivityLog() { const d = document.getElementById('fa-date').value; const t = document.getElementById('fa-title').value; const desc = document.getElementById('fa-desc').value; if(!t) return showAlert("عنوان الزامی است"); showLoading(); app.run.withSuccessHandler(r=>{ DB.moduleHistory=null; hideLoading(); showToast(r.msg); document.getElementById('free-activity-modal').style.display='none'; document.getElementById('fa-title').value = ""; document.getElementById('fa-desc').value = ""; fetchData(); }).saveManualLog({date: d, type: 'free_activity', title: t, details: desc}); }
  function statusLabel(status, lateMinutes, reason){
    const rTxt = reason ? ' (علت: ' + attAttr(reason) + ')' : '';
    if(status === 'Absent') return 'غیبت' + rTxt;
    if(status === 'Late') return 'تاخیر' + (lateMinutes ? ' (' + toPersianNum(lateMinutes) + 'د)' : '') + rTxt;
    if(status === 'Excused') return 'موجه' + rTxt;
    return 'حاضر';
  }
  function statusClass(status){
    if(status === 'Absent') return 'danger';
    if(status === 'Late') return 'warn';
    if(status === 'Excused') return 'info';
    return 'ok';
  }
  function loadAttendanceHistory(append = false){
    const box = document.getElementById('attendance-history-list');
    if(!box || attendanceHistoryState.loading) return;
    if(!append){ attendanceHistoryState.offset = 0; attendanceHistoryState.done = false; box.innerHTML = '<p style="opacity:.65;text-align:center;padding:18px;">در حال دریافت سوابق...</p>'; }
    if(append && attendanceHistoryState.done) return;
    attendanceHistoryState.loading = true;
    app.run.withSuccessHandler(r=>{
      attendanceHistoryState.loading = false;
      const rows = r.sessions || [];
      attendanceHistoryState.offset += rows.length;
      attendanceHistoryState.done = rows.length < attendanceHistoryState.limit;
      renderAttendanceHistory(rows, append);
    }).withFailureHandler(e=>{
      attendanceHistoryState.loading = false;
      box.innerHTML = '<p style="opacity:.65;text-align:center;padding:18px;">دریافت سوابق انجام نشد.</p>';
      showAlert('خطا در دریافت سوابق حضور و غیاب: ' + e.message, 'error');
    }).getAttendanceSessions(attendanceHistoryState.limit, attendanceHistoryState.offset);
  }
  function renderAttendanceHistory(rows, append){
    const box = document.getElementById('attendance-history-list'); if(!box) return;
    let current = append ? box.querySelectorAll('.attendance-session-card').length : 0;
    let h = append ? box.innerHTML.replace(/<button[\s\S]*?loadAttendanceHistory\(true\)[\s\S]*?<\/button>/, '') : '';
    if(!rows.length && !current){ box.innerHTML = '<p style="opacity:.65;text-align:center;padding:18px;">هنوز حضور و غیابی ثبت نشده است.</p>'; return; }
    rows.forEach(s=>{
      h += `<div class="attendance-session-card" id="att-session-${s.id}">
        <div class="attendance-session-head" onclick="toggleAttendanceSession(${s.id})">
          <div><b>جلسه ${toPersianNum(s.date)}</b><small>${toPersianNum(s.total)} نفر ثبت شده</small></div>
          <div class="attendance-mini-stats"><span class="ok">${toPersianNum(s.present)}</span><span class="warn">${toPersianNum(s.late)}</span><span class="danger">${toPersianNum(s.absent)}</span><span class="info">${toPersianNum(s.excused)}</span></div>
        </div>
        <div class="attendance-session-body" id="att-session-body-${s.id}"></div>
      </div>`;
    });
    if(!attendanceHistoryState.done) h += `<button class="btn btn-outline" style="margin:10px auto 0; max-width:260px;" onclick="loadAttendanceHistory(true)">نمایش بیشتر سوابق</button>`;
    box.innerHTML = h;
  }
  function toggleAttendanceSession(id){
    const card = document.getElementById('att-session-' + id);
    const body = document.getElementById('att-session-body-' + id);
    if(!card || !body) return;
    if(card.classList.contains('open')) { card.classList.remove('open'); return; }
    card.classList.add('open');
    if(body.dataset.loaded === '1') return;
    body.innerHTML = '<p style="opacity:.65;text-align:center;">در حال دریافت ریز حضور...</p>';
    app.run.withSuccessHandler(r=>{
      if(!r.success) { body.innerHTML = `<p style="opacity:.65;text-align:center;">${r.msg || 'جلسه پیدا نشد'}</p>`; return; }
      body.dataset.loaded = '1';
      let h = `<div class="attendance-detail-actions"><button class="btn btn-red" onclick="askDeleteAttendanceSession(${id}, event)">حذف این تاریخ</button></div>`;
      h += (r.records || []).map(x=>`<div class="attendance-detail-row ${statusClass(x.status)}"><div><img src="${x.image}" class="avatar-sm" onerror="this.src='assets/default-avatar.svg'"><b>${x.name}</b></div><span>${statusLabel(x.status, x.lateMinutes, x.reason || x.absence_reason)}</span></div>`).join('') || '<p style="opacity:.65;text-align:center;">رکوردی برای این جلسه ثبت نشده است.</p>';
      body.innerHTML = h;
    }).withFailureHandler(e=>{ body.innerHTML = '<p style="opacity:.65;text-align:center;">خطا در دریافت ریز حضور.</p>'; showAlert(e.message, 'error'); }).getAttendanceSessionDetails(id);
  }
  function askDeleteAttendanceSession(id, event){
    if(event) event.stopPropagation();
    showConfirm('این تاریخ حضور و غیاب کامل حذف شود؟ این کار رکورد همه متربی‌ها در این جلسه را پاک می‌کند.', ()=>{
      showLoading();
      app.run.withSuccessHandler(r=>{ hideLoading(); attendanceHistoryState.offset=0; attendanceHistoryState.done=false; showToast(r.msg || 'حذف شد'); fetchData(); if(activeViewName()==='att') loadAttendanceHistory(false); }).withFailureHandler(e=>{ hideLoading(); showAlert(e.message, 'error'); }).deleteAttendanceSession(id);
    });
  }

  let currentArchiveType = 'grouping';
  let archiveSearchTimer = null;
  function filterArchive(query) {
    clearTimeout(archiveSearchTimer);
    archiveSearchTimer = setTimeout(() => loadArchiveData(currentArchiveType || 'grouping', query || ''), 250);
  }
  function renderArchive(type, tabElement) {
    if(tabElement) { document.querySelectorAll('#view-archive .tab').forEach(t => t.classList.remove('active')); tabElement.classList.add('active'); }
    if (type && type !== currentArchiveType) { currentArchiveType = type; document.getElementById('archive-search').value = ""; return loadArchiveData(type, ''); }
    currentArchiveType = type || currentArchiveType || 'grouping';
    const list = document.getElementById('archive-list'); let h = "";
    const filtered = DB.moduleHistory || [];
    if (filtered.length === 0) { h = `<div style="text-align:center; padding:20px; color:#94a3b8;">هنوز موردی در این دسته ثبت نشده است.</div>`; }
    else { filtered.forEach(item => h += buildArchiveItemHTML(item)); }
    list.innerHTML = h;
  }
  function buildArchiveItemHTML(item) { let headerText = item.title; let bodyContent = ""; let extraBtn = ""; if (item.source === 'plan' && item.planId) { extraBtn = `<br><button class="btn btn-blue" style="font-size:0.8rem; padding:5px 10px; margin-top:10px; width:auto; display:inline-block;" onclick="goToPlan('${item.planId}')">📜 مشاهده طرح درس</button>`; } if (item.type === 'grouping') { let detailsObj = {}; try { detailsObj = JSON.parse(item.details); } catch(e){} if(detailsObj.isMultiGroup) { let groupsHtml = ""; (detailsObj.groups || []).forEach(g => { groupsHtml += `<div style="margin-bottom:8px; border-bottom:1px dashed #eee; padding-bottom:5px;"><span style="font-weight:bold; color:var(--primary);">${g.name}:</span> ${g.members.join('، ')}</div>`; }); bodyContent = groupsHtml; } else { const membersStr = (detailsObj.members || []).join(' - '); const grpName = detailsObj.groupName || ""; if(grpName) headerText += ` <span style="opacity:0.7">(${grpName})</span>`; bodyContent = `<b>اعضا:</b> ${membersStr}`; } } else { const sourceBadge = item.source==='plan' ? '<span style="color:var(--primary);">📚 طرح درس</span>' : '<span style="color:#10b981;">📝 آزاد</span>'; bodyContent = `${item.details || 'بدون توضیحات'}<br><br><small>${sourceBadge}</small>`; } return `<div class="acc-card"><div class="acc-header" onclick="this.parentElement.classList.toggle('open')"><div>${headerText}</div><div style="font-size:0.8rem; white-space:nowrap;">${toPersianNum(item.date)} 🔽</div></div><div class="acc-body">${bodyContent}${extraBtn}</div></div>`; }
  function hiddenAlertIds(){
    try { return JSON.parse(localStorage.getItem('charshanbeHiddenAlerts') || '[]'); } catch(e) { return []; }
  }
  function getVisibleAlerts(){
    const hidden = new Set(hiddenAlertIds());
    return (DB.alerts || []).filter(a => !hidden.has(a.id || `${a.title}:${a.text}`));
  }
  function dismissAlert(id){
    const hidden = new Set(hiddenAlertIds());
    hidden.add(id);
    localStorage.setItem('charshanbeHiddenAlerts', JSON.stringify([...hidden]));
    renderAlerts();
    const dashAlertCount = document.getElementById('dash-alert-count'); if (dashAlertCount) dashAlertCount.innerText = toPersianNum(getVisibleAlerts().length);
  }
  function renderAlerts(){
    const box=document.getElementById('alerts-list'); if(!box) return;
    const alerts=getVisibleAlerts();
    box.innerHTML = alerts.length ? alerts.map(a=>`<div class="alert-card ${a.level}" style="position:relative; padding-left:46px;"><button onclick="dismissAlert('${a.id || `${a.title}:${a.text}`}')" title="دیگر نمایش نده" aria-label="بستن هشدار" style="position:absolute; left:12px; top:12px; width:28px; height:28px; border:0; border-radius:50%; background:#f1f5f9; color:#64748b; cursor:pointer; font-size:1.1rem;">×</button><b>${a.title}</b><br><span>${a.text}</span></div>`).join('') : '<p style="opacity:.65;text-align:center;">فعلاً هشدار مهمی ثبت نشده است.</p>';
  }
  function runGlobalSearch(q){
    const out=document.getElementById('global-search-results'); if(!out) return;
    if(!q || q.trim().length<2){ out.innerHTML=''; return; }
    app.run.withSuccessHandler(r=>{ out.innerHTML = (r.items||[]).length ? r.items.map(i=>`<div class="search-result"><b>${i.type}: ${i.title}</b><br><small>${i.text||''}</small></div>`).join('') : '<p style="opacity:.65;">چیزی پیدا نشد.</p>'; }).globalSearch(q);
  }
  function quickSearch(q){
    const out=document.getElementById('quick-search-results'); if(!out) return;
    if(!q || q.trim().length<2){ out.innerHTML=''; return; }
    app.run.withSuccessHandler(r=>{ out.innerHTML=(r.items||[]).slice(0,5).map(i=>`<div class="search-result"><b>${i.type}: ${i.title}</b><br><small>${i.text||''}</small></div>`).join('') || '<p style="opacity:.65;">چیزی پیدا نشد.</p>'; }).globalSearch(q);
  }
  function renderCalendar(){
    const out=document.getElementById('calendar-list'); if(!out) return;
    out.innerHTML='<p style="opacity:.65;">در حال دریافت...</p>';
    app.run.withSuccessHandler(r=>{ out.innerHTML=(r.events||[]).length ? r.events.map(e=>`<div class="calendar-item clickable-calendar-item" onclick="openCalendarEvent('${e.type}', '${e.id || ''}')"><div><b>${e.type==='plan'?'📚 طرح درس':'✅ حضور و غیاب'}</b><br>${e.title}</div><strong>${toPersianNum(e.date)}</strong></div>`).join('') : '<p style="opacity:.65;">هنوز جلسه‌ای ثبت نشده است.</p>'; }).getCalendarData();
  }
  async function uploadPlanFile(planId, event){
    if(event) event.stopPropagation();
    const input=document.getElementById('plan-file-'+planId); if(!input || !input.files || !input.files.length) return showAlert('اول فایل‌ها را انتخاب کنید.');
    const selectedFiles = Array.from(input.files);
    const chunkSize = 20;
    const uploaded = [];
    showLoading();
    try {
      for(let i=0; i<selectedFiles.length; i+=chunkSize){
        const form=new FormData();
        form.append('planId', planId);
        selectedFiles.slice(i, i + chunkSize).forEach(file => form.append('files[]', file));
        const res = await fetch('api.php?action=uploadPlanFile',{method:'POST',body:form,headers: (window.CHSH && window.CHSH.csrf) ? {'X-CSRF-Token': window.CHSH.csrf} : {}});
        const d = await parseApiJsonResponse(res);
        if(!d.success) throw new Error(d.msg||'آپلود نشد');
        uploaded.push(...(d.files || (d.file ? [d.file] : [])));
      }
      appendPlanFiles(planId, uploaded);
      input.value = '';
      hideLoading();
      showToast(`✅ ${toPersianNum(uploaded.length)} فایل طرح درس آپلود شد`);
    } catch(e) { hideLoading(); showAlert(cleanServerError(e.message),'error'); }
  }
  async function parseApiJsonResponse(res){
    const text = await res.text();
    const jsonStart = text.indexOf('{');
    const jsonText = jsonStart >= 0 ? text.slice(jsonStart) : text;
    try { return JSON.parse(jsonText); } catch(e) { throw new Error(text); }
  }
  function cleanServerError(msg){
    return String(msg || '').replace(/<br\s*\/?>/gi, '\n').replace(/<[^>]*>/g, '').trim() || 'خطای نامشخص در سرور';
  }
  function appendPlanFiles(planId, files){
    const plan = (DB.plans || []).find(p => String(p.id) === String(planId));
    if(plan && files.length){
      plan.files = [...files, ...(plan.files || [])];
      renderPlans();
    }
  }
  function deletePlanFile(id, event){ if(event) event.stopPropagation(); showConfirm('این فایل حذف شود؟',()=> app.run.withSuccessHandler(r=>{DB.plans=null; lazyOffsets.plans=0; lazyDone.plans=false; showToast(r.msg); fetchData();}).deletePlanFile(id)); }
  function flushTelegram(btn){ const done=btnBusy(btn,'در حال ارسال...'); app.run.withSuccessHandler(r=>{ done(); showAlert(r.msg, r.success?'success':'error'); }).withFailureHandler(e=>{ done(); showAlert('خطا: '+e.message,'error'); }).flushTelegramQueue(); }
  function openMonthlyReport(){
    if(!currentStudent) return;
    showPrompt('ماه گزارش را وارد کن (مثلاً 1405/01):', new Date().toLocaleDateString('fa-IR').slice(0,7), (m)=>{ if(!m) return; showLoading(); app.run.withSuccessHandler(d=>{ hideLoading(); if(!d.success) return showAlert('گزارش پیدا نشد','error'); renderReportModal(d, 'گزارش ماهانه ' + toPersianNum(d.month)); }).getMonthlyReport(currentStudent,m); });
  }
  function renderReportModal(d, title){
    const info=d.info, stats=d.stats; document.getElementById('report-date').innerText=title;
    const parentNote = String(info.parentNote || info.parent_note || '').trim() || 'توضیحات خاصی ثبت نشده است.';
    let h=`<div class="report-compact-head"><img src="${info.displayImage || 'assets/default-avatar.svg'}" onerror="this.src='assets/default-avatar.svg'"><div><small>${title}</small><h3>${info.name}</h3><span class="score-badge">امتیاز کل: ${toPersianNum(stats.scores.total)}</span></div></div><div class="report-section report-compact-stats"><h4>📊 وضعیت حضور</h4><div class="report-stat-row"><span>حاضر</span><b>${toPersianNum(stats.stats.p)}</b></div><div class="report-stat-row"><span>تاخیر</span><b>${toPersianNum(stats.stats.l)}</b></div><div class="report-stat-row"><span>غیبت</span><b>${toPersianNum(stats.stats.a)}</b></div><div class="report-stat-row"><span>موجه</span><b>${toPersianNum(stats.stats.e)}</b></div></div><div class="report-section report-compact-mi"><h4>🧠 هوش‌ها</h4>${generateSimpleBarChart(d.mi.chart)}</div><div class="report-section report-parent-note"><h4>📝 یادداشت مربی برای والدین</h4><div>${parentNote}</div></div><div class="report-section report-events"><h4>⏳ رویدادهای این بازه</h4>${(d.fullHistory||[]).slice(0,6).map(x=>`<div style="border-bottom:1px solid #eee;padding:6px 0"><b>${toPersianNum(x.date)} - ${x.title}</b><br>${x.desc}</div>`).join('') || 'موردی ثبت نشده است.'}</div>`;
    document.getElementById('report-content').innerHTML=h; document.getElementById('report-modal').style.display='block';
  }

  function openCalendarEvent(type, id) {
    if(type === 'plan') return goToPlan(id);
    if(type === 'attendance') return goToAttendanceSession(id);
  }
  function goToAttendanceSession(sessionId) {
    nav('att');
    setTimeout(() => {
      loadAttendanceHistory(false);
      setTimeout(() => openAttendanceSessionFromCalendar(sessionId), 650);
    }, 120);
  }
  function openAttendanceSessionFromCalendar(sessionId) {
    const el = document.getElementById('att-session-' + sessionId);
    if(el) {
      el.scrollIntoView({behavior:'smooth', block:'center'});
      if(!el.classList.contains('open')) toggleAttendanceSession(sessionId);
      el.classList.add('highlight');
      setTimeout(() => el.classList.remove('highlight'), 2200);
      return;
    }
    if(!attendanceHistoryState.done) {
      loadAttendanceHistory(true);
      setTimeout(() => openAttendanceSessionFromCalendar(sessionId), 650);
    } else {
      showAlert('این حضور و غیاب در لیست فعلی پیدا نشد.', 'error');
    }
  }

  function goToPlan(planId) { nav('plans'); setTimeout(() => { const el = document.getElementById('plan-' + planId); if(el) { el.scrollIntoView({behavior: 'smooth', block: 'center'}); el.classList.add('open'); el.classList.add('highlight'); setTimeout(() => el.classList.remove('highlight'), 2000); } else { showAlert("طرح درس مورد نظر یافت نشد.", 'error'); } }, 600); }
if ('serviceWorker' in navigator) { window.addEventListener('load', ()=>navigator.serviceWorker.register('sw.js').catch(()=>{})); }
