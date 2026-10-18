/**
 * سامانه ارزیابی هوش‌های چندگانه (طراحی موبایل‌فرست، کاملاً ریسپانسیو و تک‌کلیکی)
 * منطبق بر پرسشنامه ۶۴ گویه‌ای استاندارد والدین + مصاحبه تکمیلی + خروجی تحلیل هوش مصنوعی (AI Prompt)
 */

(function() {
  const miState = {
    studentName: null,
    profileData: null,
    selectedPeriodId: null,
    viewMode: 'parent', // 'parent' (نمای کلان والد / سرفصل‌ها) | 'child' (نمای ارزیابی فرزند / جزئیات حوزه)
    activeDomainId: null,
    activeSubTabs: {},
    loading: false
  };

  // رنگ و زمینه هویتی هر هوش
  const domainThemes = {
    'هوش-کلامی-': { color: '#8b5cf6', bg: 'rgba(139, 92, 246, 0.12)', label: 'کلامی و زبانی' },
    'هوش-منطقی-ریاضی': { color: '#3b82f6', bg: 'rgba(59, 130, 246, 0.12)', label: 'منطقی و ریاضی' },
    'هوش-تصویری-فضایی': { color: '#ec4899', bg: 'rgba(236, 72, 153, 0.12)', label: 'تصویری و فضایی' },
    'هوش-جنبشی-حرکتی': { color: '#f97316', bg: 'rgba(249, 115, 22, 0.12)', label: 'بدنی و حرکتی' },
    'هوش-موسیقیایی': { color: '#eab308', bg: 'rgba(234, 179, 8, 0.12)', label: 'موسیقایی و ریتم' },
    'هوش-بین-فردی': { color: '#10b981', bg: 'rgba(16, 185, 129, 0.12)', label: 'ارتباط با دیگران' },
    'هوش-درون-فردی': { color: '#06b6d4', bg: 'rgba(6, 182, 212, 0.12)', label: 'خودشناسی و اراده' },
    'هوش-طبیعت-گرا': { color: '#84cc16', bg: 'rgba(132, 204, 22, 0.12)', label: 'طبیعت و جانداران' }
  };

  function getTheme(slug) {
    return domainThemes[slug] || { color: '#6366f1', bg: 'rgba(99,102,241,0.12)', label: '' };
  }

  /**
   * رندر اولیه یا به‌روزرسانی سامانه هوش‌ها
   */
  window.renderMIHub = async function(studentName, periodId = null) {
    if (!studentName) return;
    if (miState.studentName !== studentName) {
      miState.studentName = studentName;
      miState.viewMode = 'parent';
      miState.activeDomainId = null;
    }
    if (periodId) miState.selectedPeriodId = periodId;

    const root = document.getElementById('mi-hub-root');
    if (!root) return;

    root.innerHTML = `
      <div style="text-align:center; padding:35px 10px; color:var(--text-muted, #64748b);">
        <i class="fas fa-spinner fa-spin" style="font-size:1.8rem; margin-bottom:10px; display:block; color:var(--primary, #4f46e5);"></i>
        در حال بارگذاری پرونده هوش‌های ${escapeHtml(studentName)}...
      </div>
    `;

    try {
      miState.loading = true;
      const res = await callApi('getStudentMIProfileNew', [studentName, miState.selectedPeriodId]);
      miState.loading = false;

      if (!res || !res.success) {
        root.innerHTML = `
          <div class="card" style="text-align:center; padding:25px 15px; border:1px solid #fed7aa; background:rgba(254,243,199,0.4); border-radius:16px;">
            <div style="font-size:2rem; margin-bottom:8px;">🔒</div>
            <h4 style="color:#b45309; margin-bottom:6px; font-size:1rem;">دسترسی به بخش هوش‌ها فعال نیست</h4>
            <p style="color:#78350f; font-size:0.86rem; line-height:1.7; margin:0;">
              ${escapeHtml(res ? res.msg : 'دسترسی فعال نیست.')}
            </p>
          </div>
        `;
        return;
      }

      miState.profileData = res;
      if (res.current_period_id) miState.selectedPeriodId = res.current_period_id;

      if (!miState.activeDomainId && res.domains && res.domains.length > 0) {
        miState.activeDomainId = res.domains[0].domain.id;
      }

      renderMIView();
    } catch (e) {
      miState.loading = false;
      root.innerHTML = `
        <div class="card" style="text-align:center; padding:25px 15px; border:1px solid #fecaca; background:rgba(254,226,226,0.5); border-radius:16px;">
          <div style="font-size:1.8rem; margin-bottom:8px;">⚠️</div>
          <p style="color:#b91c1c; font-size:0.88rem; margin-bottom:12px;">خطا در دریافت اطلاعات: ${escapeHtml(e.message)}</p>
          <button class="btn btn-outline" style="width:auto; padding:6px 16px; font-size:0.85rem;" onclick="renderMIHub('${escapeHtml(studentName)}')">تلاش مجدد</button>
        </div>
      `;
    }
  };

  /**
   * استایل‌های یکپارچه و مدرن سامانه هوش‌های چندگانه (موبایل‌فرست، شیشه‌ای مایع و بدون اسکرول سرسام‌آور)
   */
  function getMICommonStyles() {
    return `
      <style>
        #tab-mi, .mi-wrap {
          width: 100% !important;
          max-width: 100% !important;
          box-sizing: border-box !important;
          overflow-x: hidden !important;
          direction: rtl !important;
          font-family: inherit !important;
        }
        .mi-wrap * {
          box-sizing: border-box !important;
        }

        /* نوار بالای صفحه هوش‌ها */
        .mi-top-bar {
          display: flex;
          flex-direction: column;
          gap: 12px;
          margin-bottom: 16px;
          padding-bottom: 14px;
          border-bottom: 1px solid var(--border, rgba(255,255,255,0.25));
        }
        @media (min-width: 768px) {
          .mi-top-bar {
            flex-direction: row;
            justify-content: space-between;
            align-items: center;
          }
        }
        .mi-top-title-row {
          display: flex;
          align-items: center;
          gap: 10px;
        }
        .mi-top-title {
          font-size: 1.12rem;
          font-weight: 800;
          color: var(--text-main, #0f172a);
          margin: 0;
          display: flex;
          align-items: center;
          gap: 6px;
        }
        .mi-top-actions {
          display: flex;
          align-items: center;
          flex-wrap: wrap;
          gap: 8px;
        }
        .mi-select-period {
          padding: 8px 12px;
          border-radius: var(--radius-sm, 12px);
          border: 1px solid var(--border, #cbd5e1);
          background: var(--glass-bg, #ffffff);
          color: var(--text-main, #0f172a);
          font-size: 0.84rem;
          font-weight: 700;
          cursor: pointer;
        }
        .mi-btn-action {
          padding: 8px 14px;
          border-radius: var(--radius-sm, 12px);
          font-size: 0.82rem;
          font-weight: 700;
          white-space: nowrap;
          cursor: pointer;
          display: inline-flex;
          align-items: center;
          gap: 6px;
        }

        /* کارت وضعیت کلی مشاهدات در نمای والد */
        .mi-parent-hero-card {
          background: var(--glass-bg, rgba(255,255,255,0.7));
          backdrop-filter: var(--glass-blur, blur(20px));
          -webkit-backdrop-filter: var(--glass-blur, blur(20px));
          border: 1px solid var(--border, rgba(255,255,255,0.4));
          box-shadow: var(--glass-shadow, 0 6px 16px rgba(0,0,0,0.04));
          border-radius: var(--radius-md, 18px);
          padding: 16px 18px;
          margin-bottom: 18px;
        }
        .mi-parent-hero-header {
          display: flex;
          justify-content: space-between;
          align-items: center;
          flex-wrap: wrap;
          gap: 8px;
          margin-bottom: 14px;
        }
        .mi-parent-hero-title {
          font-size: 0.98rem;
          font-weight: 800;
          color: var(--text-main, #0f172a);
          margin: 0;
          display: flex;
          align-items: center;
          gap: 6px;
        }
        .mi-parent-hero-hint {
          font-size: 0.78rem;
          font-weight: 600;
          color: var(--text-muted, #64748b);
        }
        .mi-stats-pills-row {
          display: grid;
          grid-template-columns: repeat(2, 1fr);
          gap: 8px;
        }
        @media (min-width: 640px) {
          .mi-stats-pills-row {
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
          }
        }
        .mi-stat-box {
          background: var(--glass-bg-subtle, rgba(255,255,255,0.5));
          border: 1px solid var(--border, rgba(203,213,225,0.5));
          border-radius: 12px;
          padding: 10px 12px;
          text-align: center;
        }
        .mi-stat-box small {
          display: block;
          font-size: 0.74rem;
          color: var(--text-muted, #64748b);
          margin-bottom: 2px;
          font-weight: 600;
        }
        .mi-stat-box b {
          font-size: 1.05rem;
          font-weight: 800;
          color: var(--text-main, #0f172a);
        }

        /* شبکه سرفصل‌های ۸ هوش (نمای والد) */
        .mi-parent-grid {
          display: grid;
          grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
          gap: 14px;
          margin-top: 14px;
        }
        @media (min-width: 900px) {
          .mi-parent-grid {
            grid-template-columns: repeat(4, 1fr);
          }
        }
        @media (max-width: 600px) {
          .mi-parent-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
          }
        }
        @media (max-width: 360px) {
          .mi-parent-grid {
            grid-template-columns: 1fr;
          }
        }
        .mi-master-card {
          background: var(--glass-bg, rgba(255,255,255,0.7));
          backdrop-filter: var(--glass-blur, blur(20px));
          -webkit-backdrop-filter: var(--glass-blur, blur(20px));
          border: 1px solid var(--border, rgba(255,255,255,0.45));
          box-shadow: var(--glass-shadow, 0 4px 14px rgba(0,0,0,0.03));
          border-radius: var(--radius-md, 18px);
          padding: 16px;
          display: flex;
          flex-direction: column;
          justify-content: space-between;
          cursor: pointer;
          transition: all 0.22s cubic-bezier(0.4, 0, 0.2, 1);
          position: relative;
          overflow: hidden;
          user-select: none;
        }
        .mi-master-card:hover {
          transform: translateY(-4px);
          border-color: rgba(99, 102, 241, 0.45);
          box-shadow: var(--glass-shadow-lg, 0 14px 28px rgba(0,0,0,0.08));
        }
        .mi-master-card-top {
          display: flex;
          align-items: center;
          gap: 10px;
          margin-bottom: 12px;
        }
        .mi-master-card-icon {
          width: 44px;
          height: 44px;
          min-width: 44px;
          border-radius: 12px;
          display: flex;
          align-items: center;
          justify-content: center;
          font-size: 1.5rem;
          flex-shrink: 0;
        }
        .mi-master-card-info {
          flex: 1;
          min-width: 0;
        }
        .mi-master-card-title {
          font-size: 0.95rem;
          font-weight: 800;
          color: var(--text-main, #0f172a);
          margin: 0;
          line-height: 1.35;
          white-space: nowrap;
          overflow: hidden;
          text-overflow: ellipsis;
        }
        .mi-master-card-sub {
          font-size: 0.72rem;
          color: var(--text-muted, #64748b);
          margin-top: 2px;
          line-height: 1.4;
          white-space: nowrap;
          overflow: hidden;
          text-overflow: ellipsis;
        }
        .mi-master-card-badges {
          display: flex;
          flex-wrap: wrap;
          gap: 4px;
          margin-bottom: 12px;
        }
        .mi-master-card-track {
          width: 100%;
          height: 6px;
          background: rgba(148, 163, 184, 0.2);
          border-radius: 999px;
          overflow: hidden;
          margin-bottom: 12px;
        }
        .mi-master-card-fill {
          height: 100%;
          border-radius: 999px;
          transition: width 0.3s ease;
        }
        .mi-master-card-action {
          width: 100%;
          padding: 8px 10px;
          border-radius: 10px;
          background: rgba(99, 102, 241, 0.08);
          border: 1px solid rgba(99, 102, 241, 0.2);
          color: var(--primary, #4f46e5);
          font-size: 0.8rem;
          font-weight: 800;
          display: flex;
          align-items: center;
          justify-content: center;
          gap: 6px;
          transition: all 0.18s;
        }
        .mi-master-card:hover .mi-master-card-action {
          background: var(--primary, #4f46e5);
          color: #ffffff;
        }

        /* نوار و هدر نمای فرزند (Child View Header & Stepper) */
        .mi-child-nav-bar {
          background: var(--glass-bg, rgba(255,255,255,0.7));
          backdrop-filter: var(--glass-blur, blur(20px));
          -webkit-backdrop-filter: var(--glass-blur, blur(20px));
          border: 1px solid var(--border, rgba(255,255,255,0.45));
          border-radius: var(--radius-md, 18px);
          padding: 12px 16px;
          margin-bottom: 16px;
          display: flex;
          flex-direction: column;
          gap: 10px;
        }
        @media (min-width: 640px) {
          .mi-child-nav-bar {
            flex-direction: row;
            justify-content: space-between;
            align-items: center;
          }
        }
        .mi-child-back-btn {
          display: inline-flex;
          align-items: center;
          gap: 6px;
          background: var(--glass-bg-subtle, rgba(255,255,255,0.5));
          border: 1px solid var(--border, #cbd5e1);
          border-radius: 10px;
          padding: 8px 14px;
          font-size: 0.84rem;
          font-weight: 700;
          color: var(--text-main, #0f172a);
          cursor: pointer;
          transition: all 0.18s;
        }
        .mi-child-back-btn:hover {
          background: var(--primary, #4f46e5);
          color: #ffffff;
          border-color: var(--primary, #4f46e5);
        }
        .mi-stepper-wrap {
          display: flex;
          align-items: center;
          gap: 6px;
        }
        .mi-stepper-btn {
          background: var(--glass-bg-subtle, rgba(255,255,255,0.5));
          border: 1px solid var(--border, #cbd5e1);
          border-radius: 9px;
          padding: 6px 12px;
          font-size: 0.78rem;
          font-weight: 700;
          color: var(--text-muted, #475569);
          cursor: pointer;
          transition: all 0.18s;
        }
        .mi-stepper-btn:hover {
          background: rgba(99, 102, 241, 0.12);
          color: var(--primary, #4f46e5);
          border-color: rgba(99, 102, 241, 0.3);
        }

        /* کارت معرفی هوش در نمای فرزند */
        .mi-child-hero-card {
          background: var(--glass-bg, rgba(255,255,255,0.7));
          backdrop-filter: var(--glass-blur, blur(20px));
          -webkit-backdrop-filter: var(--glass-blur, blur(20px));
          border: 1px solid var(--border, rgba(255,255,255,0.45));
          box-shadow: var(--glass-shadow, 0 6px 18px rgba(0,0,0,0.04));
          border-radius: var(--radius-md, 18px);
          padding: 16px 18px;
          margin-bottom: 16px;
        }
        .mi-child-hero-top {
          display: flex;
          align-items: center;
          gap: 12px;
          margin-bottom: 12px;
        }
        .mi-child-hero-icon {
          width: 52px;
          height: 52px;
          min-width: 52px;
          border-radius: 14px;
          display: flex;
          align-items: center;
          justify-content: center;
          font-size: 1.8rem;
          flex-shrink: 0;
        }
        .mi-child-hero-info {
          flex: 1;
          min-width: 0;
        }
        .mi-child-hero-title {
          font-size: 1.15rem;
          font-weight: 800;
          color: var(--text-main, #0f172a);
          margin: 0 0 3px;
        }
        .mi-child-hero-sub {
          font-size: 0.8rem;
          color: var(--text-muted, #64748b);
          line-height: 1.5;
        }
        .mi-child-hero-badges {
          display: flex;
          flex-wrap: wrap;
          gap: 6px;
        }

        /* کارت بدنه و سربرگ‌ها */
        .mi-card {
          width: 100%;
          background: var(--glass-bg, rgba(255,255,255,0.7));
          backdrop-filter: var(--glass-blur, blur(20px));
          -webkit-backdrop-filter: var(--glass-blur, blur(20px));
          border: 1px solid var(--border, rgba(255,255,255,0.4));
          box-shadow: var(--glass-shadow, 0 4px 12px rgba(0,0,0,0.03));
          border-radius: var(--radius-md, 16px);
          overflow: hidden;
        }
        .mi-card-body {
          padding: 14px 16px 18px;
        }

        /* ۴ سربرگ ۲ در ۲ یا ۴ تایی */
        .mi-subtabs-grid {
          display: grid;
          grid-template-columns: 1fr 1fr;
          gap: 6px;
          margin-bottom: 14px;
        }
        @media (min-width: 640px) {
          .mi-subtabs-grid {
            grid-template-columns: repeat(4, 1fr);
          }
        }
        .mi-subtab-btn {
          width: 100%;
          min-height: 42px;
          padding: 8px 6px;
          border-radius: 10px;
          border: 1px solid transparent;
          background: rgba(148, 163, 184, 0.12);
          color: var(--text-muted, #475569);
          font-size: 0.82rem;
          font-weight: 700;
          cursor: pointer;
          text-align: center;
          white-space: nowrap;
          transition: all 0.15s;
          display: flex;
          align-items: center;
          justify-content: center;
          gap: 4px;
        }
        .mi-subtab-btn.active {
          background: var(--primary, #4f46e5);
          color: #ffffff;
          box-shadow: 0 4px 10px rgba(79, 70, 229, 0.25);
        }

        .mi-pill {
          padding: 3px 8px;
          border-radius: 999px;
          font-size: 0.72rem;
          font-weight: 600;
          background: rgba(148, 163, 184, 0.16);
          color: var(--text-muted, #475569);
          white-space: nowrap;
        }

        /* ردیف‌های رفتار مربی (تک‌کلیکی) */
        .mi-trait-card {
          padding: 10px 0;
          border-bottom: 1px solid rgba(148, 163, 184, 0.15);
        }
        .mi-trait-card:last-child {
          border-bottom: none;
        }
        .mi-trait-title {
          font-size: 0.88rem;
          font-weight: 600;
          color: var(--text-main, #0f172a);
          line-height: 1.5;
          margin-bottom: 8px;
        }
        .mi-trait-actions-grid {
          display: grid;
          grid-template-columns: 1fr 1fr;
          gap: 8px;
          width: 100%;
        }
        .mi-touch-btn {
          width: 100%;
          min-height: 42px;
          padding: 6px 10px;
          border-radius: 10px;
          font-size: 0.8rem;
          font-weight: 700;
          cursor: pointer;
          border: 1px solid transparent;
          transition: all 0.15s;
          display: flex;
          align-items: center;
          justify-content: center;
          gap: 6px;
          user-select: none;
        }
        .mi-touch-btn.pos {
          background: rgba(34, 197, 94, 0.12);
          color: #15803d;
          border-color: rgba(34, 197, 94, 0.3);
        }
        .mi-touch-btn.pos.active {
          background: #16a34a;
          color: #ffffff;
          box-shadow: 0 4px 10px rgba(22, 163, 74, 0.35);
        }
        .mi-touch-btn.mid {
          background: rgba(245, 158, 11, 0.12);
          color: #b45309;
          border-color: rgba(245, 158, 11, 0.3);
        }
        .mi-touch-btn.mid.active {
          background: #d97706;
          color: #ffffff;
          box-shadow: 0 4px 10px rgba(217, 119, 6, 0.35);
        }

        /* سوالات پرسشنامه والدین (نوار ۶ گزینه‌ای پیوسته، فشرده و مدرن) */
        .mi-parent-guide-card {
          background: rgba(99, 102, 241, 0.05);
          border: 1px dashed rgba(99, 102, 241, 0.35);
          border-radius: 12px;
          padding: 8px 10px;
          margin-bottom: 12px;
        }
        .mi-guide-header {
          font-size: 0.78rem;
          font-weight: 700;
          color: var(--primary, #4f46e5);
          margin-bottom: 6px;
        }
        .mi-guide-badges {
          display: flex;
          flex-wrap: wrap;
          gap: 6px;
        }
        .mi-guide-pill {
          display: inline-flex;
          align-items: center;
          gap: 4px;
          font-size: 0.72rem;
          font-weight: 600;
          color: #334155;
          background: rgba(255, 255, 255, 0.8);
          padding: 3px 8px;
          border-radius: 999px;
          border: 1px solid rgba(203, 213, 225, 0.7);
        }
        .mi-guide-dot {
          width: 8px;
          height: 8px;
          border-radius: 50%;
          display: inline-block;
        }

        /* نوار پیشرفت حوزه */
        .mi-domain-progress {
          display: flex;
          align-items: center;
          justify-content: space-between;
          margin-bottom: 10px;
          padding: 6px 4px;
        }
        .mi-domain-progress-text {
          font-size: 0.8rem;
          font-weight: 700;
          color: var(--text-main, #0f172a);
        }
        .mi-domain-progress-track {
          width: 100px;
          height: 6px;
          background: rgba(148, 163, 184, 0.2);
          border-radius: 999px;
          overflow: hidden;
        }
        .mi-domain-progress-fill {
          height: 100%;
          background: #10b981;
          border-radius: 999px;
          transition: width 0.3s ease;
        }

        /* کارت هر سوال */
        .mi-parent-q-card {
          background: var(--glass-bg, rgba(255, 255, 255, 0.8));
          border: 1px solid var(--border, rgba(226, 232, 240, 0.85));
          border-radius: 12px;
          padding: 10px 12px;
          margin-bottom: 8px;
          transition: border-color 0.18s, box-shadow 0.18s;
        }
        .mi-parent-q-card:hover {
          border-color: rgba(99, 102, 241, 0.3);
          box-shadow: 0 4px 10px rgba(99, 102, 241, 0.04);
        }
        .mi-q-top-row {
          display: flex;
          align-items: flex-start;
          gap: 8px;
          margin-bottom: 8px;
        }
        .mi-q-num {
          min-width: 22px;
          height: 22px;
          border-radius: 6px;
          background: rgba(99, 102, 241, 0.12);
          color: var(--primary, #4f46e5);
          font-size: 0.78rem;
          font-weight: 800;
          display: flex;
          align-items: center;
          justify-content: center;
          flex-shrink: 0;
          margin-top: 1px;
        }
        .mi-q-text {
          flex: 1;
          font-size: 0.86rem;
          font-weight: 600;
          color: var(--text-main, #0f172a);
          line-height: 1.55;
        }
        .mi-q-status-dot {
          color: #10b981;
          font-size: 0.85rem;
          flex-shrink: 0;
          padding-top: 2px;
          transition: opacity 0.2s;
        }

        /* ردیف گزینه‌ها: نوار یکپارچه ۶ گزینه‌ای */
        .mi-scale-segmented {
          display: grid;
          grid-template-columns: repeat(6, 1fr);
          gap: 4px;
          background: rgba(241, 245, 249, 0.85);
          padding: 3px;
          border-radius: 10px;
          border: 1px solid rgba(203, 213, 225, 0.65);
          width: 100%;
          box-sizing: border-box;
        }
        .mi-scale-cell {
          background: transparent;
          border: none;
          border-radius: 7px;
          padding: 6px 2px;
          min-height: 42px;
          cursor: pointer;
          display: flex;
          flex-direction: column;
          align-items: center;
          justify-content: center;
          gap: 2px;
          user-select: none;
          -webkit-tap-highlight-color: transparent;
          transition: all 0.16s ease;
          outline: none;
        }
        .mi-scale-cell:hover:not(.active) {
          background: rgba(255, 255, 255, 0.75);
        }
        .mi-scale-cell .s-val {
          font-size: 0.95rem;
          font-weight: 800;
          line-height: 1.1;
          color: #334155;
        }
        .mi-scale-cell .s-txt {
          font-size: 0.62rem;
          font-weight: 600;
          line-height: 1;
          color: #64748b;
          white-space: nowrap;
        }

        /* استایل حالت فعال */
        .mi-scale-cell.cell-4.active {
          background: #10b981 !important;
          box-shadow: 0 3px 8px rgba(16, 185, 129, 0.35);
        }
        .mi-scale-cell.cell-3.active {
          background: #0284c7 !important;
          box-shadow: 0 3px 8px rgba(2, 132, 199, 0.35);
        }
        .mi-scale-cell.cell-2.active {
          background: #d97706 !important;
          box-shadow: 0 3px 8px rgba(217, 119, 6, 0.35);
        }
        .mi-scale-cell.cell-1.active {
          background: #ea580c !important;
          box-shadow: 0 3px 8px rgba(234, 88, 12, 0.35);
        }
        .mi-scale-cell.cell-0.active {
          background: #e11d48 !important;
          box-shadow: 0 3px 8px rgba(225, 29, 72, 0.35);
        }
        .mi-scale-cell.cell-null.active {
          background: #475569 !important;
          box-shadow: 0 3px 8px rgba(71, 85, 105, 0.35);
        }
        .mi-scale-cell.active .s-val,
        .mi-scale-cell.active .s-txt {
          color: #ffffff !important;
        }

        body.dark-mode .mi-scale-segmented {
          background: rgba(30, 41, 65, 0.75);
          border-color: rgba(255, 255, 255, 0.12);
        }
        body.dark-mode .mi-scale-cell:hover:not(.active) {
          background: rgba(255, 255, 255, 0.12);
        }
        body.dark-mode .mi-scale-cell .s-val {
          color: #f8fafc;
        }
        body.dark-mode .mi-scale-cell .s-txt {
          color: #94a3b8;
        }

        /* کادر مثال واقعی والدین */
        .mi-example-box {
          background: rgba(254, 243, 199, 0.45);
          border: 1px solid #fde68a;
          border-radius: 12px;
          padding: 12px;
          margin: 12px 0 10px;
        }
        .mi-example-title {
          font-weight: 700;
          font-size: 0.84rem;
          color: #b45309;
          margin-bottom: 6px;
          display: block;
        }

        /* بازی عملی مدرن */
        .mi-game-box {
          background: rgba(99, 102, 241, 0.05);
          border: 1px solid rgba(99, 102, 241, 0.2);
          border-radius: 12px;
          padding: 14px;
        }
        .mi-game-opts-grid {
          display: flex;
          flex-direction: column;
          gap: 7px;
          margin: 12px 0 14px;
        }
        .mi-game-opt-card {
          display: flex;
          align-items: center;
          gap: 10px;
          padding: 10px 12px;
          border-radius: 10px;
          background: var(--glass-bg, #ffffff);
          border: 1px solid var(--border, #cbd5e1);
          cursor: pointer;
          transition: all 0.16s ease;
          user-select: none;
        }
        .mi-game-opt-card:hover {
          border-color: rgba(99, 102, 241, 0.4);
          background: rgba(248, 250, 252, 0.9);
        }
        .mi-game-opt-card.active {
          border-color: var(--primary, #4f46e5);
          background: rgba(99, 102, 241, 0.1);
          box-shadow: 0 4px 12px rgba(99, 102, 241, 0.15);
        }
        .mi-game-opt-icon {
          font-size: 1.35rem;
          flex-shrink: 0;
        }
        .mi-game-opt-info {
          flex: 1;
          min-width: 0;
        }
        .mi-game-opt-title {
          font-size: 0.86rem;
          font-weight: 700;
          margin-bottom: 2px;
        }
        .mi-game-opt-desc {
          font-size: 0.74rem;
          color: var(--text-muted, #64748b);
          line-height: 1.35;
        }
      </style>
    `;
  }

  /**
   * رندر ساختار کامل هوش‌ها (معماری والد و فرزند / سرفصل‌ها و جزئیات تک‌حوزه)
   */
  function renderMIView() {
    const root = document.getElementById('mi-hub-root');
    if (!root || !miState.profileData) return;

    const p = miState.profileData;
    const periods = p.periods || [];
    const domains = p.domains || [];

    let periodOptions = periods.map(pr => `
      <option value="${pr.id}" ${pr.id == miState.selectedPeriodId ? 'selected' : ''}>
        ${escapeHtml(pr.title)} ${pr.is_active ? '🌟 (جاری)' : ''}
      </option>
    `).join('');

    const legacyCount = (p.legacy_history || []).length;
    const hasInterview = !!p.parent_interview;

    if (miState.viewMode === 'child' && miState.activeDomainId) {
      root.innerHTML = renderChildDetailHTML(domains, periodOptions, legacyCount, hasInterview);
    } else {
      root.innerHTML = renderParentOverviewHTML(domains, periodOptions, legacyCount, hasInterview);
    }
  }

  /**
   * نمودار ستونی آنالیز تطبیقی هوش‌های هشت‌گانه متربی (طراحی مدرن، ریسپانسیو و تعاملی)
   */
  function renderMIAnalysisBarChart(domains) {
    if (!domains || !domains.length) return '';

    const chartItems = domains.map(item => {
      const d = item.domain;
      const theme = getTheme(d.slug);
      const s = item.sources || {};
      const parentAvg = (s.parent && s.parent.avg_score !== null) ? Number(s.parent.avg_score) : null;
      const parentCount = (s.parent && s.parent.count) || 0;
      const teacherCount = (s.teacher && s.teacher.count) || 0;
      const practicalCount = (s.practical && s.practical.count) || 0;
      
      let pct = 0;
      if (parentAvg !== null) {
        pct = Math.max(0, Math.min(100, Math.round((parentAvg / 4) * 100)));
      } else if (parentCount > 0) {
        pct = Math.max(0, Math.min(100, Math.round((parentCount / 8) * 100)));
      } else if (teacherCount > 0 || practicalCount > 0) {
        pct = Math.min(100, (teacherCount + practicalCount) * 15);
      }

      return {
        id: d.id,
        name: d.name_fa.replace('هوش', '').trim(),
        fullName: d.name_fa,
        icon: d.icon,
        color: theme.color,
        bg: theme.bg,
        pct: pct,
        scoreText: parentAvg !== null ? (toPersianNum(parentAvg.toFixed(1)) + ' از ۴') : (parentCount > 0 ? (toPersianNum(parentCount) + ' پاسخ') : 'در انتظار'),
        totalObs: parentCount + teacherCount + practicalCount
      };
    });

    // رتبه‌بندی بر اساس درصد هوش
    const sorted = [...chartItems].sort((a, b) => b.pct - a.pct);

    let barsHtml = sorted.map((c, idx) => {
      return `
        <div class="mi-analysis-bar-row" onclick="window.openDomainDetail(${c.id})" title="مشاهده و ارزیابی ${escapeHtml(c.fullName)}" style="cursor:pointer; display:flex; align-items:center; gap:10px; padding:7px 10px; border-radius:12px; transition:background 0.2s;" onmouseover="this.style.background='rgba(99,102,241,0.06)'" onmouseout="this.style.background='transparent'">
          <div style="width:22px; text-align:center; font-size:0.8rem; font-weight:800; color:${idx < 3 ? 'var(--primary, #4f46e5)' : 'var(--text-muted, #64748b)'};">
            ${toPersianNum(idx + 1)}
          </div>
          <div style="width:130px; display:flex; align-items:center; gap:6px; min-width:0;">
            <span style="font-size:1.1rem;">${c.icon}</span>
            <span style="font-size:0.86rem; font-weight:700; color:var(--text-main, #0f172a); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">${escapeHtml(c.name)}</span>
          </div>
          <div style="flex:1; background:rgba(226,232,240,0.7); height:16px; border-radius:999px; overflow:hidden; position:relative;">
            <div style="width:${Math.max(4, c.pct)}%; background:linear-gradient(90deg, ${c.color}, ${c.color}dd); height:100%; border-radius:999px; transition:width 0.4s ease;"></div>
          </div>
          <div style="width:70px; text-align:left; font-size:0.82rem; font-weight:800; color:var(--text-main, #0f172a);">
            ${c.pct > 0 ? (toPersianNum(c.pct) + '٪') : '<span style="color:#94a3b8; font-weight:500;">۰٪</span>'}
          </div>
        </div>
      `;
    }).join('');

    return `
      <div class="card mi-parent-hero-card" style="margin-bottom:18px; padding:18px 20px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; flex-wrap:wrap; gap:8px;">
          <div style="display:flex; align-items:center; gap:8px;">
            <span style="font-size:1.25rem;">📊</span>
            <h4 class="mi-parent-hero-title" style="margin:0;">
              نمودار ستونی آنالیز تطبیقی هوش‌های هشت‌گانه
            </h4>
          </div>
          <span class="mi-pill" style="color:var(--primary, #4f46e5); background:rgba(99,102,241,0.12); font-size:0.78rem;">رتبه‌بندی گرایش‌های غالب متربی</span>
        </div>
        <div style="display:flex; flex-direction:column; gap:6px;">
          ${barsHtml}
        </div>
      </div>
    `;
  }

  /**
   * رندر نمای والد: سرفصل‌های ۸ هوش چندگانه (کاشی‌های مدرن، شفاف، خلوت و بدون سردرگمی)
   */
  function renderParentOverviewHTML(domains, periodOptions, legacyCount, hasInterview) {
    // محاسبه آمارهای کلان
    let totalParentAnswers = 0;
    let totalTeacherLogs = 0;
    let totalPracticalLogs = 0;

    domains.forEach(d => {
      const s = d.sources || {};
      const pCount = (s.parent && s.parent.count) || 0;
      const tCount = (s.teacher && s.teacher.count) || 0;
      const prCount = (s.practical && s.practical.count) || 0;
      totalParentAnswers += pCount;
      totalTeacherLogs += tCount;
      totalPracticalLogs += prCount;
    });

    const totalObs = totalParentAnswers + totalTeacherLogs + totalPracticalLogs;

    // رندر کارت‌های سرفصل ۸ هوش
    const domainCardsHTML = domains.map(item => {
      const d = item.domain;
      const theme = getTheme(d.slug);
      const s = item.sources || {};
      const parentCount = (s.parent && s.parent.count) || 0;
      const teacherCount = (s.teacher && s.teacher.count) || 0;
      const practicalCount = (s.practical && s.practical.count) || 0;
      const pct = Math.max(0, Math.min(100, Math.round((parentCount / 8) * 100)));

      let badges = [];
      if (parentCount > 0) badges.push(`<span class="mi-pill" style="color:#2563eb; background:rgba(37,99,235,0.12);">🏡 ${toPersianNum(parentCount)} پاسخ والد</span>`);
      if (teacherCount > 0) badges.push(`<span class="mi-pill" style="color:#15803d; background:rgba(34,197,94,0.12);">🏫 ${toPersianNum(teacherCount)} کلاس</span>`);
      if (practicalCount > 0) badges.push(`<span class="mi-pill" style="color:#b45309; background:rgba(245,158,11,0.12);">🎯 بازی</span>`);
      if (!badges.length) badges.push(`<span class="mi-pill" style="color:#64748b; background:rgba(148,163,184,0.14);">در انتظار ارزیابی</span>`);

      return `
        <div class="mi-master-card" onclick="window.openDomainDetail(${d.id})" title="ورود به ارزیابی ${escapeHtml(d.name_fa)}">
          <div>
            <div class="mi-master-card-top">
              <div class="mi-master-card-icon" style="background:${theme.bg}; color:${theme.color};">
                ${d.icon}
              </div>
              <div class="mi-master-card-info">
                <h4 class="mi-master-card-title">${escapeHtml(d.name_fa)}</h4>
                <div class="mi-master-card-sub">${escapeHtml(d.scientific_desc || '۸ گویه ارزیابی')}</div>
              </div>
            </div>

            <div class="mi-master-card-badges">
              ${badges.join('')}
            </div>
          </div>

          <div>
            <div style="display:flex; justify-content:space-between; align-items:center; font-size:0.75rem; font-weight:700; margin-bottom:5px; color:var(--text-muted, #64748b);">
              <span>پیشرفت فرم والدین</span>
              <span>${toPersianNum(pct)}٪</span>
            </div>
            <div class="mi-master-card-track">
              <div class="mi-master-card-fill" style="width:${pct}%; background:${theme.color};"></div>
            </div>
            <button type="button" class="mi-master-card-action" onclick="event.stopPropagation(); window.openDomainDetail(${d.id})">
              ورود و ثبت ارزیابی <i class="fas fa-arrow-left"></i>
            </button>
          </div>
        </div>
      `;
    }).join('');

    return `
      ${getMICommonStyles()}
      <div class="mi-wrap">
        <!-- نوار بالایی هدر -->
        <div class="mi-top-bar">
          <div class="mi-top-title-row">
            <h3 class="mi-top-title">🧠 پرونده هوش‌های چندگانه</h3>
            <span class="score-badge" style="font-size:0.85rem;">${escapeHtml(miState.studentName)}</span>
          </div>

          <div class="mi-top-actions">
            <select class="mi-select-period" onchange="window.changeMIPeriod(this.value)" title="انتخاب دوره تحصیلی">
              ${periodOptions}
            </select>
            <button type="button" class="btn btn-blue mi-btn-action" onclick="window.openAIPromptModal()" title="خروجی پرامپت هوشمند برای ChatGPT / Claude">
              🤖 گزارش هوش مصنوعی
            </button>
            <button type="button" class="btn btn-outline mi-btn-action" onclick="window.openParentInterviewModal()" title="پاسخ‌های تشریحی صفحات ۸ و ۹ پرسشنامه">
              📝 ${hasInterview ? 'ویرایش مصاحبه والد' : 'مصاحبه والدین'}
            </button>
            <button type="button" class="btn btn-outline mi-btn-action" onclick="window.openLegacyModal()" title="سوابق سیستم قدیمی">
              📜 سوابق (${toPersianNum(legacyCount)})
            </button>
          </div>
        </div>

        <!-- کارت شاخص‌های کلان تربیتی (والد) -->
        <div class="mi-parent-hero-card">
          <div class="mi-parent-hero-header">
            <h4 class="mi-parent-hero-title">
              <span>📊 سرفصل‌های هشت‌گانه هوش و استعداد</span>
            </h4>
            <span class="mi-parent-hero-hint">برای ورود و ثبت ارزیابی، روی کارت حوزه مورد نظر کلیک کنید 👇</span>
          </div>

          <div class="mi-stats-pills-row">
            <div class="mi-stat-box">
              <small>کل شواهد ثبت‌شده</small>
              <b>${toPersianNum(totalObs)} مشاهده</b>
            </div>
            <div class="mi-stat-box">
              <small>پاسخ‌های والدین</small>
              <b>${toPersianNum(totalParentAnswers)} از ۶۴ گویه</b>
            </div>
            <div class="mi-stat-box">
              <small>مشاهدات کلاسی</small>
              <b>${toPersianNum(totalTeacherLogs)} ثبت</b>
            </div>
            <div class="mi-stat-box">
              <small>چالش‌های عملی</small>
              <b>${toPersianNum(totalPracticalLogs)} آزمون</b>
            </div>
          </div>
        </div>

        <!-- کارت نمودار ستونی آنالیز هوش‌های چندگانه متربی -->
        ${renderMIAnalysisBarChart(domains)}

        <!-- شبکه ۸ کاشی سرفصل هوش‌ها -->
        <div class="mi-parent-grid">
          ${domainCardsHTML}
        </div>
      </div>
    `;
  }

  /**
   * رندر نمای فرزند: صفحه متمرکز ارزیابی یک حوزه انتخابی با ناوبری گام‌به‌گام
   */
  function renderChildDetailHTML(domains, periodOptions, legacyCount, hasInterview) {
    const currentDomainItem = domains.find(x => x.domain.id == miState.activeDomainId) || domains[0];
    const d = currentDomainItem.domain;
    const theme = getTheme(d.slug);
    const s = currentDomainItem.sources || {};
    const parentCount = (s.parent && s.parent.count) || 0;
    const teacherCount = (s.teacher && s.teacher.count) || 0;
    const practicalCount = (s.practical && s.practical.count) || 0;

    const curIdx = domains.findIndex(x => x.domain.id == d.id);
    const prevIdx = (curIdx - 1 + domains.length) % domains.length;
    const nextIdx = (curIdx + 1) % domains.length;
    const prevDomain = domains[prevIdx];
    const nextDomain = domains[nextIdx];

    const activeTab = miState.activeSubTabs[d.id] || 'parent';

    let badges = [];
    badges.push(`<span class="mi-pill" style="color:#2563eb; background:rgba(37,99,235,0.12);">🏡 ${toPersianNum(parentCount)} پاسخ والد</span>`);
    badges.push(`<span class="mi-pill" style="color:#15803d; background:rgba(34,197,94,0.12);">🏫 ${toPersianNum(teacherCount)} کلاس</span>`);
    badges.push(`<span class="mi-pill" style="color:#b45309; background:rgba(245,158,11,0.12);">🎯 ${toPersianNum(practicalCount)} بازی</span>`);

    return `
      ${getMICommonStyles()}
      <div class="mi-wrap">
        <!-- نوار ناوبری والد و فرزند و دکمه بازگشت -->
        <div class="mi-child-nav-bar">
          <button type="button" class="mi-child-back-btn" onclick="window.backToParentOverview()">
            <i class="fas fa-arrow-right"></i> بازگشت به سرفصل‌های هوش (نمای والد)
          </button>

          <div class="mi-stepper-wrap">
            <button type="button" class="mi-stepper-btn" onclick="window.openDomainDetail(${prevDomain.domain.id})" title="رفتن به ${escapeHtml(prevDomain.domain.name_fa)}">
              ◄ ${escapeHtml(prevDomain.domain.name_fa.replace('هوش','').trim())}
            </button>
            <span style="font-size:0.82rem; font-weight:800; color:var(--primary, #4f46e5); padding:0 6px;">
              ${toPersianNum(curIdx + 1)} از ۸
            </span>
            <button type="button" class="mi-stepper-btn" onclick="window.openDomainDetail(${nextDomain.domain.id})" title="رفتن به ${escapeHtml(nextDomain.domain.name_fa)}">
              ${escapeHtml(nextDomain.domain.name_fa.replace('هوش','').trim())} ►
            </button>
          </div>
        </div>

        <!-- کارت معرفی حوزه انتخابی -->
        <div class="mi-child-hero-card">
          <div class="mi-child-hero-top">
            <div class="mi-child-hero-icon" style="background:${theme.bg}; color:${theme.color};">
              ${d.icon}
            </div>
            <div class="mi-child-hero-info">
              <h3 class="mi-child-hero-title">${escapeHtml(d.name_fa)}</h3>
              <div class="mi-child-hero-sub">${escapeHtml(d.scientific_desc || '۸ گویه ارزیابی چندمنبعه')}</div>
            </div>
          </div>

          <div class="mi-child-hero-badges">
            ${badges.join('')}
          </div>
        </div>

        <!-- کارت محتوای ارزیابی حوزه با ۴ سربرگ -->
        <div class="mi-card open" style="margin-bottom:16px;">
          <div class="mi-card-body" style="display:block;">
            <!-- ۴ سربرگ -->
            <div class="mi-subtabs-grid">
              <button type="button" class="mi-subtab-btn ${activeTab === 'parent' ? 'active' : ''}" data-tab="parent" onclick="window.setCardSubTab(${d.id}, 'parent')">
                🏡 پرسشنامه والدین (۸ گویه)
              </button>
              <button type="button" class="mi-subtab-btn ${activeTab === 'teacher' ? 'active' : ''}" data-tab="teacher" onclick="window.setCardSubTab(${d.id}, 'teacher')">
                🏫 مشاهده در کلاس (مربی)
              </button>
              <button type="button" class="mi-subtab-btn ${activeTab === 'practical' ? 'active' : ''}" data-tab="practical" onclick="window.setCardSubTab(${d.id}, 'practical')">
                🎯 بازی عملی (اسپکتروم)
              </button>
              <button type="button" class="mi-subtab-btn ${activeTab === 'tips' ? 'active' : ''}" data-tab="tips" onclick="window.setCardSubTab(${d.id}, 'tips')">
                💡 راهنما و تحلیل
              </button>
            </div>

            <!-- بدنه محتوای سربرگ فعال -->
            <div id="mi-tab-content-${d.id}">
              ${renderTabContentHTML(currentDomainItem, activeTab)}
            </div>
          </div>
        </div>

        <!-- نوار پایان صفحه برای پیمایش آسان -->
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; margin-top:16px; padding:12px; background:var(--glass-bg-subtle, rgba(255,255,255,0.4)); border-radius:14px; border:1px solid var(--border, #cbd5e1);">
          <button type="button" class="btn btn-outline" style="width:auto; padding:8px 18px; font-size:0.85rem;" onclick="window.backToParentOverview()">
            ← بازگشت به نمای سرفصل‌ها
          </button>
          <button type="button" class="btn btn-blue" style="width:auto; padding:8px 18px; font-size:0.85rem; font-weight:700;" onclick="window.openDomainDetail(${nextDomain.domain.id})">
            ورود به حوزه بعدی (${escapeHtml(nextDomain.domain.name_fa.replace('هوش','').trim())}) ◄
          </button>
        </div>
      </div>
    `;
  }

  /**
   * باز کردن کار برگ اختصاصی یک هوش (نمای فرزند)
   */
  window.openDomainDetail = function(domainId) {
    miState.viewMode = 'child';
    miState.activeDomainId = domainId;
    renderMIView();
    const root = document.getElementById('mi-hub-root');
    if (root) root.scrollIntoView({ behavior: 'smooth', block: 'start' });
  };

  /**
   * بازگشت به نمای والد (سرفصل‌ها)
   */
  window.backToParentOverview = function() {
    miState.viewMode = 'parent';
    renderMIView();
    const root = document.getElementById('mi-hub-root');
    if (root) root.scrollIntoView({ behavior: 'smooth', block: 'start' });
  };

  /**
   * پشتیبانی رو به عقب برای فراخوانی کارت
   */
  window.toggleDomainCard = function(domainId) {
    window.openDomainDetail(domainId);
  };

  /**
   * سوئیچ سربرگ داخل کارت
   */
  window.setCardSubTab = function(domainId, tabName) {
    miState.activeSubTabs[domainId] = tabName;
    const container = document.getElementById(`mi-tab-content-${domainId}`);
    const domainItem = miState.profileData.domains.find(x => x.domain.id == domainId);
    if (container && domainItem) {
      document.querySelectorAll('.mi-subtab-btn').forEach(b => b.classList.remove('active'));
      const targetBtn = document.querySelector(`.mi-subtab-btn[data-tab="${tabName}"]`);
      if (targetBtn) targetBtn.classList.add('active');
      container.innerHTML = renderTabContentHTML(domainItem, tabName);
    }
  };

  /**
   * تغییر دوره
   */
  window.changeMIPeriod = function(periodId) {
    window.renderMIHub(miState.studentName, parseInt(periodId, 10));
  };

  /**
   * رندر محتوای سربرگ فعال
   */
  function renderTabContentHTML(item, tabName) {
    const d = item.domain;
    const s = item.sources;

    // ۱. سربرگ پرسشنامه والدین (۸ گویه با ۶ گزینه دقیقاً مطابق PDF)
    // ۱. سربرگ پرسشنامه والدین (۸ گویه با نوار مدرن ۶ گزینه‌ای)
    if (tabName === 'parent') {
      const indicators = d.indicators || [];
      const parentStates = (s.parent && s.parent.states) || {};

      // یافتن مثال قبلی ثبت‌شده برای این حوزه
      let prevExample = '';
      if (s.parent.evidences && s.parent.evidences.length > 0) {
        prevExample = s.parent.evidences[0].text || '';
      }

      // محاسبه تعداد گویه‌های پاسخ‌داده‌شده در این بخش
      let answeredCount = 0;
      indicators.forEach(ind => {
        if (parentStates[ind.id] && (parentStates[ind.id].score !== null || parentStates[ind.id].no_opportunity)) {
          answeredCount++;
        }
      });

      const items = indicators.map((ind) => {
        const state = parentStates[ind.id] || null;
        let curScore = '';
        if (state) {
          curScore = state.no_opportunity ? 'null' : (state.score !== null ? String(state.score) : '');
        }
        const hasAnswer = curScore !== '';

        return `
          <div class="mi-parent-q-card" id="mi-parent-q-${d.id}-${ind.id}">
            <div class="mi-q-top-row">
              <span class="mi-q-num">${toPersianNum(ind.id)}</span>
              <div class="mi-q-text">${escapeHtml(ind.indicator_text)}</div>
              <span class="mi-q-status-dot" id="mi-q-status-${d.id}-${ind.id}" style="${hasAnswer ? 'opacity:1' : 'opacity:0'}">
                <i class="fas fa-check-circle"></i>
              </span>
            </div>

            <div class="mi-scale-segmented" role="radiogroup">
              <button type="button" class="mi-scale-cell cell-4 ${curScore === '4' ? 'active' : ''}"
                      onclick="window.selectParentScore(${d.id}, ${ind.id}, '4')"
                      data-score="4" title="۴ ـ تقریباً همیشه">
                <span class="s-val">۴</span>
                <span class="s-txt">همیشه</span>
              </button>
              <button type="button" class="mi-scale-cell cell-3 ${curScore === '3' ? 'active' : ''}"
                      onclick="window.selectParentScore(${d.id}, ${ind.id}, '3')"
                      data-score="3" title="۳ ـ اغلب">
                <span class="s-val">۳</span>
                <span class="s-txt">اغلب</span>
              </button>
              <button type="button" class="mi-scale-cell cell-2 ${curScore === '2' ? 'active' : ''}"
                      onclick="window.selectParentScore(${d.id}, ${ind.id}, '2')"
                      data-score="2" title="۲ ـ گاهی">
                <span class="s-val">۲</span>
                <span class="s-txt">گاهی</span>
              </button>
              <button type="button" class="mi-scale-cell cell-1 ${curScore === '1' ? 'active' : ''}"
                      onclick="window.selectParentScore(${d.id}, ${ind.id}, '1')"
                      data-score="1" title="۱ ـ به‌ندرت">
                <span class="s-val">۱</span>
                <span class="s-txt">به‌ندرت</span>
              </button>
              <button type="button" class="mi-scale-cell cell-0 ${curScore === '0' ? 'active' : ''}"
                      onclick="window.selectParentScore(${d.id}, ${ind.id}, '0')"
                      data-score="0" title="۰ ـ مشاهده نکرده‌ام">
                <span class="s-val">۰</span>
                <span class="s-txt">ندیدم</span>
              </button>
              <button type="button" class="mi-scale-cell cell-null ${curScore === 'null' ? 'active' : ''}"
                      onclick="window.selectParentScore(${d.id}, ${ind.id}, 'null')"
                      data-score="null" title="؟ ـ فرصت مشاهده نداشته‌ام (بدون نمره منفی)">
                <span class="s-val">؟</span>
                <span class="s-txt">بی‌فرصت</span>
              </button>
            </div>
            <input type="hidden" id="p_input_${d.id}_${ind.id}" value="${curScore}">
          </div>
        `;
      }).join('');

      return `
        <div>
          <!-- راهنمای مقیاس -->
          <div class="mi-parent-guide-card">
            <div class="mi-guide-header">💡 راهنمای نمره‌دهی گزینه‌ها:</div>
            <div class="mi-guide-badges">
              <span class="mi-guide-pill"><span class="mi-guide-dot" style="background:#10b981;"></span> <b>۴</b> تقریباً همیشه</span>
              <span class="mi-guide-pill"><span class="mi-guide-dot" style="background:#0284c7;"></span> <b>۳</b> اغلب</span>
              <span class="mi-guide-pill"><span class="mi-guide-dot" style="background:#d97706;"></span> <b>۲</b> گاهی</span>
              <span class="mi-guide-pill"><span class="mi-guide-dot" style="background:#ea580c;"></span> <b>۱</b> به‌ندرت</span>
              <span class="mi-guide-pill"><span class="mi-guide-dot" style="background:#e11d48;"></span> <b>۰</b> مشاهده نشده</span>
              <span class="mi-guide-pill" title="عدم وجود موقعیت تجربه؛ امتیاز را کسر نمی‌کند"><span class="mi-guide-dot" style="background:#475569;"></span> <b>؟</b> فرصت نبوده</span>
            </div>
          </div>

          <!-- شمارنده پیشرفت -->
          <div class="mi-domain-progress">
            <span class="mi-domain-progress-text">
              پاسخ‌های ثبت‌شده: <b id="mi-prog-num-${d.id}">${toPersianNum(answeredCount)}</b> از ${toPersianNum(indicators.length || 8)} گویه
            </span>
            <div class="mi-domain-progress-track">
              <div class="mi-domain-progress-fill" id="mi-prog-fill-${d.id}" style="width:${Math.round((answeredCount / (indicators.length || 8)) * 100)}%;"></div>
            </div>
          </div>

          <!-- فهرست گویه‌ها -->
          <div id="mi-parent-list-${d.id}">
            ${items}
          </div>

          <!-- فیلد ثبت مثال واقعی در منزل -->
          <div class="mi-example-box">
            <label class="mi-example-title">
              ✍️ یک مثال واقعی از این توانمندی در فرزندم (مطابق برگه چاپی):
            </label>
            <textarea id="p_example_${d.id}" class="form-control" rows="2"
                      placeholder="مثال: دیشب وقتی از پارک برگشتیم، تمام مسیر و بازی‌ها را با جزئیات کامل برای پدرش تعریف کرد..."
                      style="width:100%; border-radius:10px; font-size:0.84rem; line-height:1.7; background:#ffffff;">${escapeHtml(prevExample)}</textarea>
          </div>

          <div style="margin-top:12px;">
            <button type="button" onclick="window.saveParentSurveyDomain(${d.id})" class="btn btn-green"
                    style="width:100%; min-height:44px; font-size:0.88rem; font-weight:700; border-radius:12px; display:flex; align-items:center; justify-content:center; gap:8px;">
              💾 ذخیره کامل پاسخ‌ها و مثال این حوزه
            </button>
          </div>
        </div>
      `;
    }

    // ۲. کلاس (مربی) - تک کلیکی
    if (tabName === 'teacher') {
      const indicators = d.indicators || [];
      const teacherStates = (s.teacher && s.teacher.states) || {};

      if (!indicators.length) {
        return '<div style="color:#64748b; font-size:0.82rem; padding:12px; text-align:center;">رفتاری تعریف نشده است.</div>';
      }

      const rows = indicators.map(ind => {
        const state = teacherStates[ind.id] || null;
        const isIndep = state && state.assistance === 'مستقل';
        const isWithHelp = state && (state.assistance === 'با کمک مستقیم' || state.assistance === 'با یادآوری');

        return `
          <div class="mi-trait-card">
            <div class="mi-trait-title">• ${escapeHtml(ind.indicator_text)}</div>
            <div class="mi-trait-actions-grid">
              <button type="button" class="mi-touch-btn pos ${isIndep ? 'active' : ''}"
                      onclick="window.fastLogTeacher(${d.id}, ${ind.id}, 'مستقل', ${isIndep ? 'true' : 'false'})">
                <i class="fas fa-check"></i> مستقل
              </button>
              <button type="button" class="mi-touch-btn mid ${isWithHelp ? 'active' : ''}"
                      onclick="window.fastLogTeacher(${d.id}, ${ind.id}, 'با کمک', ${isWithHelp ? 'true' : 'false'})">
                <i class="fas fa-hands-helping"></i> با کمک
              </button>
            </div>
          </div>
        `;
      }).join('');

      return `
        <div>
          <div style="font-size:0.78rem; color:var(--text-muted, #64748b); margin-bottom:10px;">
            مشاهدات کلاسی مربی (لمس دکمه = ثبت آنی | لمس دوباره = خنثی):
          </div>
          ${rows}
        </div>
      `;
    }

    // ۳. بازی عملی (کارت‌های مدرن انتخابی)
    if (tabName === 'practical') {
      const activities = d.activities || [];
      if (!activities.length) {
        return '<div style="color:#64748b; font-size:0.82rem; padding:12px; text-align:center;">بازی تعریف نشده است.</div>';
      }

      const act = activities[0];
      const practicalStates = (s.practical && s.practical.states) || {};
      const curState = practicalStates[act.id] || null;
      const curScore = curState ? curState.score : 3;

      return `
        <div class="mi-game-box">
          <h4 style="margin:0 0 6px; font-size:0.95rem; font-weight:700; color:var(--text-main, #0f172a);">
            🎮 ${escapeHtml(act.title)}
          </h4>
          <p style="font-size:0.82rem; line-height:1.7; color:var(--text-muted, #475569); margin:0 0 10px;">
            ${escapeHtml(act.instructions)}
          </p>

          <input type="hidden" id="mi_game_val_${act.id}" value="${curScore}">

          <div class="mi-game-opts-grid">
            <div class="mi-game-opt-card ${curScore == 4 ? 'active' : ''}" onclick="window.selectGameScore(${d.id}, ${act.id}, 4)">
              <div class="mi-game-opt-icon">🌟</div>
              <div class="mi-game-opt-info">
                <div class="mi-game-opt-title" style="color:#15803d;">مهارت عالی و خلاق (۴)</div>
                <div class="mi-game-opt-desc">کودک به صورت کاملاً مستقل و با خلاقیت بازی را اجرا کرد</div>
              </div>
            </div>

            <div class="mi-game-opt-card ${curScore == 3 ? 'active' : ''}" onclick="window.selectGameScore(${d.id}, ${act.id}, 3)">
              <div class="mi-game-opt-icon">✨</div>
              <div class="mi-game-opt-info">
                <div class="mi-game-opt-title" style="color:#0284c7;">تسلط خوب و مستقل (۳)</div>
                <div class="mi-game-opt-desc">بدون نیاز به کمک مستقیم هدف فعالیت را با موفقیت انجام داد</div>
              </div>
            </div>

            <div class="mi-game-opt-card ${curScore == 2 ? 'active' : ''}" onclick="window.selectGameScore(${d.id}, ${act.id}, 2)">
              <div class="mi-game-opt-icon">⚡</div>
              <div class="mi-game-opt-info">
                <div class="mi-game-opt-title" style="color:#d97706;">با راهنمایی و کمک (۲)</div>
                <div class="mi-game-opt-desc">با سرنخ اولیه یا یادآوری مربی به هدف رسید</div>
              </div>
            </div>

            <div class="mi-game-opt-card ${curScore == 1 ? 'active' : ''}" onclick="window.selectGameScore(${d.id}, ${act.id}, 1)">
              <div class="mi-game-opt-icon">🌱</div>
              <div class="mi-game-opt-info">
                <div class="mi-game-opt-title" style="color:#ea580c;">نیاز به تمرین و تجربه (۱)</div>
                <div class="mi-game-opt-desc">چالش برای کودک تازه بود و نیاز به تکرار و فرصت بیشتری دارد</div>
              </div>
            </div>
          </div>

          <button type="button" onclick="window.savePracticalGameAction(${d.id}, ${act.id})" class="btn btn-blue"
                  style="width:100%; min-height:42px; font-size:0.88rem; font-weight:700; border-radius:12px;">
            💾 ثبت ارزیابی بازی عملی
          </button>
        </div>
      `;
    }

    // ۴. راهنما و تقویت
    if (tabName === 'tips') {
      const recs = item.recommendations || {};
      const insight = item.cross_context_insight || '';

      return `
        <div style="display:flex; flex-direction:column; gap:8px;">
          ${insight ? `
            <div style="background:rgba(99,102,241,0.08); border-right:3px solid var(--primary, #4f46e5); padding:10px 12px; border-radius:8px; font-size:0.82rem; line-height:1.7; color:var(--text-main, #1e293b);">
              <b>وضعیت شواهد:</b> ${escapeHtml(insight)}
            </div>
          ` : ''}

          <div style="background:rgba(34,197,94,0.08); border-right:3px solid #16a34a; padding:10px 12px; border-radius:8px; font-size:0.82rem; line-height:1.7; color:var(--text-main, #1e293b);">
            <b>🏫 در کلاس:</b> ${escapeHtml(recs.school || 'فرصت‌های تکرار و کار گروهی.')}
          </div>

          <div style="background:rgba(245,158,11,0.08); border-right:3px solid #d97706; padding:10px 12px; border-radius:8px; font-size:0.82rem; line-height:1.7; color:var(--text-main, #1e293b);">
            <b>🏡 در خانه:</b> ${escapeHtml(recs.home || 'تشویق و توجه به علایق کودک در منزل.')}
          </div>
        </div>
      `;
    }

    return '';
  }

  /**
   * ثبت تک‌کلیکی مربی
   */
  window.fastLogTeacher = async function(domainId, indicatorId, assistanceLevel, currentlyActive) {
    const todayStr = getTodayPersian();
    const isReset = currentlyActive;
    const scoreVal = isReset ? 0 : (assistanceLevel === 'مستقل' ? 4 : 2);
    const assistanceVal = isReset ? 'عادی' : (assistanceLevel === 'مستقل' ? 'مستقل' : 'با کمک مستقیم');

    try {
      const p = miState.profileData;
      const domainItem = p.domains.find(x => x.domain.id == domainId);
      if (domainItem) {
        if (!domainItem.sources.teacher.states) domainItem.sources.teacher.states = {};
        if (isReset) {
          delete domainItem.sources.teacher.states[indicatorId];
          if (domainItem.sources.teacher.count > 0) domainItem.sources.teacher.count--;
          if (domainItem.total_observations > 0) domainItem.total_observations--;
        } else {
          domainItem.sources.teacher.states[indicatorId] = {
            assistance: assistanceVal,
            score: scoreVal,
            date: todayStr
          };
          domainItem.sources.teacher.count++;
          domainItem.total_observations++;
        }
      }

      renderMIView();

      const payload = {
        student_name: miState.studentName,
        period_id: miState.selectedPeriodId,
        source_slug: 'teacher',
        domain_id: domainId,
        indicator_id: indicatorId,
        score: scoreVal,
        assistance_level: assistanceVal,
        observed_at: todayStr
      };

      const res = await callApi('submitMIObservation', [payload]);
      if (res && res.success) {
        if (window.showToast) {
          showToast(isReset ? 'پاک شد' : '✅ ثبت شد', 'success');
        }
      } else {
        if (window.showAlert) showAlert(res ? res.msg : 'خطا در ثبت', 'error');
      }
    } catch (err) {
      if (window.showAlert) showAlert('خطا در ارتباط: ' + err.message, 'error');
    }
  };

  /**
   * انتخاب نمره والد برای یک گویه (تک‌لمسی، فیدبک آنی و ذخیره خودکار در پس‌زمینه)
   */
  window.selectParentScore = async function(domainId, indicatorId, scoreVal) {
    const qCard = document.getElementById(`mi-parent-q-${domainId}-${indicatorId}`);
    const input = document.getElementById(`p_input_${domainId}_${indicatorId}`);
    if (!qCard || !input) return;

    const currentVal = input.value;
    const isToggleOff = (currentVal === String(scoreVal));
    const newVal = isToggleOff ? '' : String(scoreVal);
    input.value = newVal;

    // به‌روزرسانی استایل دکمه‌ها
    qCard.querySelectorAll('.mi-scale-cell').forEach(c => c.classList.remove('active'));
    if (!isToggleOff) {
      const targetCell = qCard.querySelector(`.mi-scale-cell[data-score="${scoreVal}"]`);
      if (targetCell) targetCell.classList.add('active');
    }

    // تیک وضعیت پاسخ
    const statusDot = document.getElementById(`mi-q-status-${domainId}-${indicatorId}`);
    if (statusDot) {
      statusDot.style.opacity = isToggleOff ? '0' : '1';
    }

    // به‌روزرسانی شمارنده و نوار پیشرفت این حوزه
    const p = miState.profileData;
    const domainItem = p.domains.find(x => x.domain.id == domainId);
    if (domainItem) {
      if (!domainItem.sources.parent.states) domainItem.sources.parent.states = {};
      if (isToggleOff) {
        delete domainItem.sources.parent.states[indicatorId];
      } else {
        domainItem.sources.parent.states[indicatorId] = {
          score: scoreVal === 'null' ? null : parseInt(scoreVal, 10),
          no_opportunity: scoreVal === 'null'
        };
      }
      const answeredCount = Object.keys(domainItem.sources.parent.states).length;
      domainItem.sources.parent.count = answeredCount;
      const numEl = document.getElementById(`mi-prog-num-${domainId}`);
      if (numEl) numEl.textContent = toPersianNum(answeredCount);
      const fillEl = document.getElementById(`mi-prog-fill-${domainId}`);
      if (fillEl) fillEl.style.width = `${Math.round((answeredCount / 8) * 100)}%`;
    }

    // ذخیره خودکار در پس‌زمینه
    const todayStr = getTodayPersian();
    const isNull = (scoreVal === 'null');
    const numericScore = isNull ? null : parseInt(scoreVal, 10);

    try {
      await callApi('submitMIObservation', [{
        student_name: miState.studentName,
        period_id: miState.selectedPeriodId,
        source_slug: 'parent',
        domain_id: domainId,
        indicator_id: indicatorId,
        score: numericScore,
        no_opportunity: isNull ? 1 : 0,
        is_clear: isToggleOff ? 1 : 0,
        respondent_relation: 'والد',
        observed_at: todayStr
      }]);
    } catch (err) {
      console.warn('Auto-save failed:', err);
    }
  };

  /**
   * ذخیره کلیه پاسخ‌های والدین و متن مثال عینی در یک درخواست یکپارچه (Batch)
   */
  window.saveParentSurveyDomain = async function(domainId) {
    const p = miState.profileData;
    const domainItem = p.domains.find(x => x.domain.id == domainId);
    const indicators = (domainItem && domainItem.domain.indicators) || [];
    const todayStr = getTodayPersian();
    const exampleText = (document.getElementById(`p_example_${domainId}`)?.value || '').trim();

    const answers = [];
    indicators.forEach(ind => {
      const input = document.getElementById(`p_input_${domainId}_${ind.id}`);
      if (input && input.value !== '') {
        const val = input.value;
        const isNull = (val === 'null');
        answers.push({
          indicator_id: ind.id,
          score: isNull ? null : parseInt(val, 10),
          no_opportunity: isNull
        });
      }
    });

    try {
      showLoading();
      const res = await callApi('saveParentDomainBatch', [{
        student_name: miState.studentName,
        period_id: miState.selectedPeriodId,
        domain_id: domainId,
        answers: answers,
        evidence: exampleText,
        observed_at: todayStr
      }]);
      hideLoading();

      if (res && res.success) {
        if (window.showToast) showToast('✅ پاسخ‌ها و مثال والدین ذخیره شد', 'success');
        window.renderMIHub(miState.studentName, miState.selectedPeriodId);
      } else {
        if (window.showAlert) showAlert(res ? res.msg : 'خطا در ثبت', 'error');
      }
    } catch (err) {
      hideLoading();
      if (window.showAlert) showAlert('خطا در ذخیره پاسخ‌ها: ' + err.message, 'error');
    }
  };

  /**
   * انتخاب نمره بازی عملی
   */
  window.selectGameScore = function(domainId, actId, score) {
    const input = document.getElementById(`mi_game_val_${actId}`);
    if (input) input.value = score;
    const box = document.querySelector(`#mi-tab-content-${domainId} .mi-game-box`);
    if (box) {
      box.querySelectorAll('.mi-game-opt-card').forEach(c => c.classList.remove('active'));
      const activeCard = box.querySelector(`.mi-game-opt-card[onclick*=", ${score})"]`);
      if (activeCard) activeCard.classList.add('active');
    }
  };

  /**
   * ثبت ارزیابی بازی عملی
   */
  window.savePracticalGameAction = async function(domainId, actId) {
    const input = document.getElementById(`mi_game_val_${actId}`);
    const scoreVal = parseInt(input ? input.value : '3', 10);
    const todayStr = getTodayPersian();

    try {
      showLoading();
      const payload = {
        student_name: miState.studentName,
        period_id: miState.selectedPeriodId,
        source_slug: 'practical',
        domain_id: domainId,
        activity_id: actId,
        score: scoreVal,
        assistance_level: scoreVal === 4 ? 'مستقل و خلاق' : (scoreVal === 3 ? 'مستقل' : (scoreVal === 2 ? 'با یادآوری' : 'با کمک مستقیم')),
        observed_at: todayStr
      };

      const res = await callApi('submitMIObservation', [payload]);
      hideLoading();

      if (res && res.success) {
        if (window.showToast) showToast('✅ نتیجه بازی ثبت شد', 'success');
        window.renderMIHub(miState.studentName, miState.selectedPeriodId);
      } else {
        if (window.showAlert) showAlert(res ? res.msg : 'خطا در ثبت بازی', 'error');
      }
    } catch (err) {
      hideLoading();
      if (window.showAlert) showAlert('خطا در ارتباط با سرور: ' + err.message, 'error');
    }
  };

  /**
   * مودال مصاحبه و سوالات تکمیلی والدین (صفحات ۸ و ۹ PDF)
   */
  window.openParentInterviewModal = function() {
    const existing = document.getElementById('mi-interview-modal');
    if (existing) existing.remove();

    const p = miState.profileData;
    const intv = (p && p.parent_interview) || {};

    const m = document.createElement('div');
    m.id = 'mi-interview-modal';
    m.style.cssText = 'position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; display:flex; align-items:center; justify-content:center; padding:12px; box-sizing:border-box;';
    m.innerHTML = `
      <div style="max-width:620px; width:100%; max-height:90vh; display:flex; flex-direction:column; background:var(--glass-bg, #ffffff); backdrop-filter:blur(25px); -webkit-backdrop-filter:blur(25px); border-radius:18px; padding:18px; box-shadow:0 12px 35px rgba(0,0,0,0.25); border:1px solid var(--border, #cbd5e1); direction:rtl;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; border-bottom:1px solid var(--border, #e2e8f0); padding-bottom:10px;">
          <div>
            <h4 style="margin:0 0 4px; font-size:1.05rem; font-weight:700; color:var(--text-main, #0f172a);">
              📝 سوالات تکمیلی و نظر کلی والدین (صفحات ۸ و ۹ پرسشنامه)
            </h4>
            <small style="color:#64748b;">این پاسخ‌ها به مربی کمک می‌کند تصویر عمیق‌تری از علایق و جهان کودک داشته باشد.</small>
          </div>
          <button type="button" style="background:transparent; border:none; font-size:1.3rem; cursor:pointer; color:#64748b;" onclick="document.getElementById('mi-interview-modal').remove()">✕</button>
        </div>

        <form id="mi-interview-form" onsubmit="window.saveParentInterviewSubmit(event)" style="flex:1; overflow-y:auto; padding-left:6px; display:flex; flex-direction:column; gap:12px;">
          <div>
            <label style="font-size:0.84rem; font-weight:700; display:block; margin-bottom:4px;">۱. فرزند شما بیشتر اوقات آزاد خود را چگونه می‌گذراند؟</label>
            <textarea id="intv_free_time" class="form-control" rows="2" style="width:100%; border-radius:10px; font-size:0.84rem; line-height:1.6;">${escapeHtml(intv.free_time || '')}</textarea>
          </div>

          <div>
            <label style="font-size:0.84rem; font-weight:700; display:block; margin-bottom:4px;">۲. در حال حاضر در چه کلاس‌ها یا فعالیت‌های فوق‌برنامه‌ای شرکت می‌کند؟</label>
            <textarea id="intv_extracurricular" class="form-control" rows="2" style="width:100%; border-radius:10px; font-size:0.84rem; line-height:1.6;">${escapeHtml(intv.extracurricular || '')}</textarea>
          </div>

          <div>
            <label style="font-size:0.84rem; font-weight:700; display:block; margin-bottom:4px;">۳. به نظر شما در انجام چه کاری بیشتر از سایر کارها احساس توانمندی می‌کند؟</label>
            <textarea id="intv_feels_capable" class="form-control" rows="2" style="width:100%; border-radius:10px; font-size:0.84rem; line-height:1.6;">${escapeHtml(intv.feels_capable || '')}</textarea>
          </div>

          <div>
            <label style="font-size:0.84rem; font-weight:700; display:block; margin-bottom:4px;">۴. کاری هست که بدون اجبار و یادآوری، خودش با علاقه دنبال کند؟</label>
            <textarea id="intv_spontaneous" class="form-control" rows="2" style="width:100%; border-radius:10px; font-size:0.84rem; line-height:1.6;">${escapeHtml(intv.spontaneous_interest || '')}</textarea>
          </div>

          <div>
            <label style="font-size:0.84rem; font-weight:700; display:block; margin-bottom:4px;">۵. اگر بخواهید فقط یک توانمندی برجسته را مثال بزنید، چه چیزی است؟ (با مثال واقعی)</label>
            <textarea id="intv_top_talent" class="form-control" rows="2" style="width:100%; border-radius:10px; font-size:0.84rem; line-height:1.6;">${escapeHtml(intv.top_talent_example || '')}</textarea>
          </div>

          <div>
            <label style="font-size:0.84rem; font-weight:700; display:block; margin-bottom:4px;">۶. آیا حوزه‌ای هست که در آن توانایی دارد اما در مدرسه یا جمع کمتر فرصت نشان دادن پیدا می‌کند؟</label>
            <textarea id="intv_hidden_talent" class="form-control" rows="2" style="width:100%; border-radius:10px; font-size:0.84rem; line-height:1.6;">${escapeHtml(intv.hidden_talent_school || '')}</textarea>
          </div>

          <div>
            <label style="font-size:0.84rem; font-weight:700; display:block; margin-bottom:4px;">۷. اگر بخواهید فرزندتان را با سه کلمه توصیف کنید، چه می‌گویید؟</label>
            <input type="text" id="intv_three_words" class="form-control" placeholder="مثال: کنجکاو، مهربان، پرانرژی" value="${escapeHtml(intv.three_words || '')}" style="width:100%; border-radius:10px; font-size:0.84rem;">
          </div>

          <div>
            <label style="font-size:0.84rem; font-weight:700; display:block; margin-bottom:4px;">۸. مهم‌ترین چیزی که دوست دارید مربی درباره فرزندتان بداند:</label>
            <textarea id="intv_note_coach" class="form-control" rows="2" style="width:100%; border-radius:10px; font-size:0.84rem; line-height:1.6;">${escapeHtml(intv.note_for_coach || '')}</textarea>
          </div>

          <div>
            <label style="font-size:0.84rem; font-weight:700; display:block; margin-bottom:4px;">۹. نکته یا مثال دیگری درباره توانمندی‌های فرزندتان:</label>
            <textarea id="intv_additional" class="form-control" rows="2" style="width:100%; border-radius:10px; font-size:0.84rem; line-height:1.6;">${escapeHtml(intv.additional_notes || '')}</textarea>
          </div>

          <div style="display:flex; justify-content:flex-end; gap:8px; margin-top:14px; border-top:1px solid var(--border, #e2e8f0); padding-top:12px;">
            <button type="button" class="btn btn-outline" style="width:auto; padding:8px 16px;" onclick="document.getElementById('mi-interview-modal').remove()">انصراف</button>
            <button type="submit" class="btn btn-green" style="width:auto; padding:8px 22px; font-weight:700;">💾 ذخیره پاسخ‌ها</button>
          </div>
        </form>
      </div>
    `;
    document.body.appendChild(m);
  };

  window.saveParentInterviewSubmit = async function(e) {
    e.preventDefault();
    const payload = {
      student_name: miState.studentName,
      period_id: miState.selectedPeriodId,
      free_time: document.getElementById('intv_free_time').value.trim(),
      extracurricular: document.getElementById('intv_extracurricular').value.trim(),
      feels_capable: document.getElementById('intv_feels_capable').value.trim(),
      spontaneous_interest: document.getElementById('intv_spontaneous').value.trim(),
      top_talent_example: document.getElementById('intv_top_talent').value.trim(),
      hidden_talent_school: document.getElementById('intv_hidden_talent').value.trim(),
      three_words: document.getElementById('intv_three_words').value.trim(),
      note_for_coach: document.getElementById('intv_note_coach').value.trim(),
      additional_notes: document.getElementById('intv_additional').value.trim()
    };

    try {
      showLoading();
      const res = await callApi('saveParentInterview', [payload]);
      hideLoading();

      const m = document.getElementById('mi-interview-modal');
      if (m) m.remove();

      if (res && res.success) {
        if (window.showToast) showToast('✅ پاسخ‌های تکمیلی والدین ذخیره شد', 'success');
        window.renderMIHub(miState.studentName, miState.selectedPeriodId);
      } else {
        if (window.showAlert) showAlert(res ? res.msg : 'خطا در ثبت', 'error');
      }
    } catch (err) {
      hideLoading();
      if (window.showAlert) showAlert('خطا در ذخیره اطلاعات: ' + err.message, 'error');
    }
  };

  /**
   * مودال تولید پرونده و پرامپت استعدادیابی برای هوش مصنوعی (ChatGPT / Claude / Gemini)
   */
  window.openAIPromptModal = async function() {
    const existing = document.getElementById('mi-ai-modal');
    if (existing) existing.remove();

    showLoading();
    let promptData = null;
    try {
      promptData = await callApi('generateMIAIPrompt', [miState.studentName, miState.selectedPeriodId]);
    } catch (err) {
      hideLoading();
      if (window.showAlert) showAlert('خطا در تولید پرامپت هوش مصنوعی: ' + err.message, 'error');
      return;
    }
    hideLoading();

    if (!promptData || !promptData.success) {
      if (window.showAlert) showAlert(promptData ? promptData.msg : 'داده‌ای یافت نشد', 'error');
      return;
    }

    const text = promptData.prompt;
    const filename = promptData.filename || 'dossier.md';

    const m = document.createElement('div');
    m.id = 'mi-ai-modal';
    m.style.cssText = 'position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.55); z-index:9999; display:flex; align-items:center; justify-content:center; padding:12px; box-sizing:border-box;';
    m.innerHTML = `
      <div style="max-width:680px; width:100%; max-height:90vh; display:flex; flex-direction:column; background:var(--glass-bg, #ffffff); backdrop-filter:blur(25px); -webkit-backdrop-filter:blur(25px); border-radius:18px; padding:18px; box-shadow:0 14px 40px rgba(0,0,0,0.3); border:1px solid var(--border, #cbd5e1); direction:rtl;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; border-bottom:1px solid var(--border, #e2e8f0); padding-bottom:10px;">
          <div>
            <h4 style="margin:0 0 4px; font-size:1.1rem; font-weight:800; color:var(--text-main, #0f172a); display:flex; align-items:center; gap:8px;">
              🤖 خروجی هوشمند برای هوش مصنوعی (AI Prompt)
            </h4>
            <small style="color:#64748b;">
              این متن حاوی تمام نمرات، مثال‌های واقعی، و دستورالعمل تخصصی تحلیل هوش‌های گاردنر برای ارسال به ChatGPT یا Claude است.
            </small>
          </div>
          <button type="button" style="background:transparent; border:none; font-size:1.3rem; cursor:pointer; color:#64748b;" onclick="document.getElementById('mi-ai-modal').remove()">✕</button>
        </div>

        <div style="background:#f8fafc; border:1px solid #cbd5e1; border-radius:12px; padding:12px; margin-bottom:12px; font-size:0.8rem; color:#334155; line-height:1.7;">
          💡 <b>نحوه استفاده:</b> روی دکمه <b>«کپی متن پرامپت»</b> کلیک کنید، سپس وارد <b>ChatGPT</b> یا <b>Claude</b> یا <b>Gemini</b> شوید و آن را Paste کنید تا یک تحلیل کامل روان‌شناختی، برنامه کلاسی و راهنمای والدینی دریافت کنید.
        </div>

        <textarea id="mi-ai-textarea" readonly style="flex:1; width:100%; border:1px solid var(--border, #cbd5e1); border-radius:10px; padding:12px; font-family:monospace; font-size:0.82rem; line-height:1.7; background:rgba(255,255,255,0.7); resize:none; direction:rtl; min-height:260px;">${escapeHtml(text)}</textarea>

        <div style="display:flex; flex-wrap:wrap; justify-content:space-between; align-items:center; gap:8px; margin-top:14px; border-top:1px solid var(--border, #e2e8f0); padding-top:12px;">
          <div style="display:flex; gap:8px;">
            <button type="button" class="btn btn-blue" style="padding:10px 18px; font-size:0.88rem; font-weight:700; border-radius:12px;" onclick="window.copyAIPrompt()">
              📋 کپی متن پرامپت
            </button>
            <button type="button" class="btn btn-outline" style="padding:10px 16px; font-size:0.88rem; font-weight:600; border-radius:12px;" onclick="window.downloadAIPrompt('${escapeHtml(filename)}')">
              📥 دانلود فایل (.md)
            </button>
          </div>
          <button type="button" class="btn btn-outline" style="padding:8px 16px; font-size:0.85rem;" onclick="document.getElementById('mi-ai-modal').remove()">بستن</button>
        </div>
      </div>
    `;
    document.body.appendChild(m);
  };

  window.copyAIPrompt = function() {
    const ta = document.getElementById('mi-ai-textarea');
    if (!ta) return;
    navigator.clipboard.writeText(ta.value).then(() => {
      if (window.showToast) showToast('✅ متن پرامپت با موفقیت در کلیپ‌بورد کپی شد', 'success');
    }).catch(() => {
      ta.select();
      document.execCommand('copy');
      if (window.showToast) showToast('✅ کپی شد', 'success');
    });
  };

  window.downloadAIPrompt = function(filename) {
    const ta = document.getElementById('mi-ai-textarea');
    if (!ta) return;
    const blob = new Blob([ta.value], { type: 'text/markdown;charset=utf-8' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename || 'MI_Dossier.md';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
    if (window.showToast) showToast('📥 فایل با موفقیت دانلود شد', 'success');
  };

  /**
   * مودال نمایش سوابق تاریخی قبلی
   */
  window.openLegacyModal = function() {
    const p = miState.profileData;
    const legacy = (p && p.legacy_history) || [];

    const existing = document.getElementById('mi-legacy-modal');
    if (existing) existing.remove();

    let rowsHtml = '';
    if (!legacy.length) {
      rowsHtml = '<div style="text-align:center; padding:25px; color:#64748b;">سابقه‌ای ثبت نشده است.</div>';
    } else {
      rowsHtml = legacy.map(x => {
        const isPos = x.score === 1;
        const isNeg = x.score === -1;
        const badge = isPos
          ? '<span style="color:#15803d; font-weight:700;">✅ مثبت</span>'
          : (isNeg ? '<span style="color:#dc2626; font-weight:700;">❌ نیازمند هدایت</span>' : '<span style="color:#64748b;">⚪ خنثی</span>');

        return `
          <div style="padding:10px 12px; border-bottom:1px solid var(--border, #e2e8f0); display:flex; justify-content:space-between; align-items:center; gap:8px; font-size:0.84rem;">
            <div style="min-width:0; flex:1;">
              <span style="color:#64748b; font-size:0.75rem; margin-left:6px;">${toPersianNum(x.date)}</span>
              <b>${escapeHtml(x.type)}</b>: ${escapeHtml(x.behavior)}
            </div>
            <div>${badge}</div>
          </div>
        `;
      }).join('');
    }

    const m = document.createElement('div');
    m.id = 'mi-legacy-modal';
    m.style.cssText = 'position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; display:flex; align-items:center; justify-content:center; padding:12px; box-sizing:border-box;';
    m.innerHTML = `
      <div style="max-width:480px; width:100%; max-height:85vh; display:flex; flex-direction:column; background:var(--glass-bg, #ffffff); backdrop-filter:blur(20px); border-radius:16px; padding:16px; box-shadow:0 10px 30px rgba(0,0,0,0.25); border:1px solid var(--border, #cbd5e1); direction:rtl;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
          <h4 style="margin:0; font-size:1rem; font-weight:700; color:var(--text-main, #0f172a);">
            📜 سوابق سیستم پیشین
          </h4>
          <button type="button" style="background:transparent; border:none; font-size:1.2rem; cursor:pointer; color:#64748b;" onclick="document.getElementById('mi-legacy-modal').remove()">✕</button>
        </div>
        <p style="font-size:0.78rem; color:#64748b; margin:0 0 10px; line-height:1.6;">
          این اطلاعات برای حفظ تاریخچه پرونده دانش‌آموز نگهداری شده‌اند.
        </p>
        <div style="flex:1; overflow-y:auto; border:1px solid var(--border, #e2e8f0); border-radius:10px; background:rgba(255,255,255,0.4); max-height:340px;">
          ${rowsHtml}
        </div>
        <div style="margin-top:12px; text-align:left;">
          <button class="btn btn-outline" style="width:auto; padding:6px 16px; font-size:0.84rem;" onclick="document.getElementById('mi-legacy-modal').remove()">بستن</button>
        </div>
      </div>
    `;
    document.body.appendChild(m);
  };

  /**
   * محاسبه تاریخ شمسی امروز
   */
  function getTodayPersian() {
    const d = new Date();
    if (typeof gregorianToJalali === 'function') {
      const j = gregorianToJalali(d.getFullYear(), d.getMonth() + 1, d.getDate());
      return j[0] + '/' + String(j[1]).padStart(2, '0') + '/' + String(j[2]).padStart(2, '0');
    }
    return d.toLocaleDateString('fa-IR');
  }

  function escapeHtml(str) {
    if (typeof window !== 'undefined' && typeof window.escapeHtml === 'function') {
      return window.escapeHtml(str);
    }
    if (str === null || str === undefined) return '';
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }
})();
