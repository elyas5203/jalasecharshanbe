<?php
require_once __DIR__ . '/auth.php';
$__admin = requireAdmin();

/* ---------------------------------------------------------------------------
 * ورود/خروج به «حالت مشاهده» یک جلسه — تغییر وضعیت نشست (نه JSON API).
 * با POST + CSRF انجام می‌شود و سپس به index.php هدایت می‌کند.
 * ------------------------------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['do'] ?? '') === 'view') {
    if (!csrfCheck($_POST['csrf_token'] ?? '')) { http_response_code(403); exit('bad token'); }
    $target = (int)($_POST['owner'] ?? 0);
    // فقط کاربران عادیِ موجود قابل مشاهده‌اند
    $st = db()->prepare("SELECT id FROM users WHERE id=? AND role='user'");
    $st->execute([$target]);
    if ($st->fetchColumn()) {
        $_SESSION['admin_view_owner'] = $target;
        header('Location: index.php');
        exit;
    }
    header('Location: admin.php');
    exit;
}
if (($_GET['exitview'] ?? '') === '1') {
    unset($_SESSION['admin_view_owner']);
    header('Location: admin.php');
    exit;
}

$csrf = csrfToken();
$adminName = htmlspecialchars($__admin['display_name'] ?: $__admin['username'], ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <title>پنل مدیریت</title>
  <link rel="icon" href="assets/favicon.svg" type="image/svg+xml">
  <link rel="preload" href="assets/fonts/Vazirmatn-400.woff2" as="font" type="font/woff2" crossorigin>
  <link rel="preload" href="assets/fonts/Vazirmatn-700.woff2" as="font" type="font/woff2" crossorigin>
  <link rel="stylesheet" href="assets/app.css">
  <script>window.ADMIN = { csrf: <?= json_encode($csrf) ?> };</script>
  <style>
    :root {
      --primary: #4f46e5;
      --primary-d: #4338ca;
      --ok: #059669;
      --warn: #d97706;
      --danger: #dc2626;
      --ink: var(--text-main);
      --muted: var(--text-muted);
      --line: var(--glass-border);
    }
    *, *::before, *::after {
      box-sizing: border-box;
      font-family: var(--font-main) !important;
      letter-spacing: normal !important;
    }
    body {
      margin: 0;
      background: var(--bg-gradient);
      background-color: var(--bg-body);
      background-attachment: fixed;
      color: var(--text-main);
      padding-bottom: 40px !important;
    }
    .topbar {
      background: var(--glass-bg);
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      border-bottom: 1px solid var(--glass-border);
      color: var(--text-main);
      padding: 16px 24px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      box-shadow: var(--glass-shadow);
      position: sticky;
      top: 0;
      z-index: 100;
    }
    .topbar h1 { margin:0; font-size:1.2rem; display:flex; align-items:center; gap:10px; color: var(--text-main); }
    .topbar .who { font-size:.85rem; color: var(--text-muted); }
    .topbar a.logout {
      color: var(--text-main);
      text-decoration: none;
      font-size: .85rem;
      margin-right: 14px;
      border: 1px solid var(--glass-border);
      padding: 6px 14px;
      border-radius: var(--radius-pill);
      background: var(--glass-bg-subtle);
      transition: all 0.2s ease;
    }
    .topbar a.logout:hover { background: var(--accent-light); color: var(--accent); }
    .wrap { max-width:1080px; margin:24px auto; padding:0 18px; }
    .card {
      background: var(--glass-bg);
      backdrop-filter: blur(24px) saturate(180%);
      -webkit-backdrop-filter: blur(24px) saturate(180%);
      border: 1px solid var(--glass-border);
      border-radius: var(--radius-lg);
      padding: 24px;
      box-shadow: var(--glass-shadow);
      margin-bottom: 24px;
    }
    .card h2 { margin:0 0 16px; font-size:1.05rem; display:flex; align-items:center; gap:8px; color: var(--text-main); }
    .grid-cards { display:grid; grid-template-columns:repeat(auto-fill,minmax(300px,1fr)); gap:16px; }
    .user-card {
      background: var(--glass-bg-subtle);
      border: 1.5px solid var(--glass-border);
      border-radius: var(--radius-md);
      padding: 18px;
      transition: border-color .15s, box-shadow .15s, transform .15s;
    }
    .user-card:hover {
      border-color: var(--primary);
      transform: translateY(-2px);
      box-shadow: var(--glass-shadow);
    }
    .user-card.inactive { opacity:.62; background: var(--glass-bg-subtle); }
    .user-head { display:flex; align-items:center; justify-content:space-between; margin-bottom:10px; }
    .user-head .name { font-weight:bold; font-size:1.02rem; }
    .user-head .uname { font-size:.78rem; color:var(--muted); direction:ltr; }
    .badge { font-size:.68rem; padding:3px 9px; border-radius:20px; font-weight:bold; }
    .badge.admin { background:#ede9fe; color:#6d28d9; }
    .badge.user { background:#dbeafe; color:#1d4ed8; }
    .badge.off { background:#fee2e2; color:#b91c1c; }
    .counts { display:flex; flex-wrap:wrap; gap:6px; margin:10px 0 14px; }
    .chip { background:#f1f5f9; border-radius:9px; padding:5px 9px; font-size:.72rem; color:#475569; }
    .chip b { color:var(--ink); }
    .actions { display:flex; flex-wrap:wrap; gap:7px; }
    .btn { border:none; border-radius:10px; padding:8px 12px; font-family:inherit; font-size:.8rem; cursor:pointer; font-weight:bold; transition:transform .1s, opacity .15s; text-decoration:none; display:inline-block; }
    .btn:active { transform:scale(.97); }
    .btn-view { background:linear-gradient(135deg,#0284c7,#0369a1); color:#fff; }
    .btn-pass { background:#fef3c7; color:#92400e; }
    .btn-edit { background:#e0e7ff; color:#3730a3; }
    .btn-toggle { background:#f1f5f9; color:#475569; }
    .btn-mi { background:#ede9fe; color:#6d28d9; }
    .btn-primary { background:linear-gradient(135deg,var(--primary),var(--primary-d)); color:#fff; }
    .btn-primary:hover { box-shadow:0 8px 20px rgba(79,70,229,.35); }
    .field { margin-bottom:13px; }
    .field label { display:block; font-size:.82rem; margin-bottom:6px; color:#334155; font-weight:bold; }
    .field input { width:100%; padding:11px 13px; border:1.5px solid var(--line); border-radius:11px; font-family:inherit; font-size:.95rem; background:#f8fafc; }
    .field input:focus { outline:none; border-color:var(--primary); background:#fff; box-shadow:0 0 0 3px rgba(79,70,229,.12); }
    .row2 { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
    .toast { position:fixed; bottom:24px; left:50%; transform:translateX(-50%); background:var(--ink); color:#fff; padding:12px 22px; border-radius:12px; font-size:.9rem; opacity:0; pointer-events:none; transition:opacity .2s, transform .2s; z-index:9000; }
    .toast.show { opacity:1; transform:translateX(-50%) translateY(-4px); }
    .toast.err { background:var(--danger); }
    .toast.ok { background:var(--ok); }
    .empty { text-align:center; color:var(--muted); padding:30px; }
    /* Modal */
    .modal { position:fixed; inset:0; background:rgba(15,23,42,.55); display:none; align-items:center; justify-content:center; z-index:8000; padding:18px; }
    .modal.show { display:flex; }
    .modal-box { background:#fff; border-radius:18px; padding:24px; width:100%; max-width:420px; box-shadow:0 30px 70px rgba(2,6,23,.5); }
    .modal-box h3 { margin:0 0 16px; font-size:1.05rem; }
    .modal-actions { display:flex; gap:10px; margin-top:8px; }
    .modal-actions .btn { flex:1; padding:12px; }
    .btn-cancel { background:#e2e8f0; color:#475569; }
    @media (max-width:560px){ .row2{grid-template-columns:1fr;} }
  </style>
</head>
<body>
  <div class="topbar">
    <h1>🛡️ پنل مدیریت</h1>
    <div>
      <span class="who">مدیر: <?= $adminName ?></span>
      <a class="logout" href="logout.php">خروج</a>
    </div>
  </div>

  <div class="wrap">

    <!-- ساخت کاربر جدید -->
    <div class="card">
      <h2>➕ ساخت کاربر (جلسه) جدید</h2>
      <div class="row2">
        <div class="field">
          <label for="nu-username">نام کاربری</label>
          <input type="text" id="nu-username" dir="ltr" style="text-align:right;" placeholder="مثلاً session2">
        </div>
        <div class="field">
          <label for="nu-display">نام نمایشی (نام جلسه)</label>
          <input type="text" id="nu-display" placeholder="مثلاً جلسه‌ی سه‌شنبه">
        </div>
      </div>
      <div class="row2">
        <div class="field">
          <label for="nu-password">رمز عبور</label>
          <input type="text" id="nu-password" dir="ltr" style="text-align:right;" placeholder="حداقل ۶ نویسه">
        </div>
        <div class="field" style="display:flex; align-items:flex-end;">
          <button class="btn btn-primary" style="width:100%; padding:11px;" onclick="createUser()">ساخت کاربر</button>
        </div>
      </div>
    </div>

    <!-- فهرست کاربران -->
    <div class="card">
      <h2>👥 کاربران و جلسات</h2>
      <div id="users-grid" class="grid-cards"><div class="empty">در حال بارگذاری…</div></div>
    </div>

    <!-- تنظیمات حساب ادمین -->
    <div class="card">
      <h2>⚙️ تنظیمات حساب من</h2>
      <p style="font-size:.83rem; color:var(--muted); margin-top:0;">برای اعمال تغییرات، رمز عبور فعلی الزامی است.</p>
      <div class="field">
        <label for="me-current">رمز عبور فعلی</label>
        <input type="password" id="me-current" dir="ltr" style="text-align:right;">
      </div>
      <div class="row2">
        <div class="field">
          <label for="me-username">نام کاربری جدید (اختیاری)</label>
          <input type="text" id="me-username" dir="ltr" style="text-align:right;" placeholder="<?= htmlspecialchars($__admin['username'], ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="field">
          <label for="me-password">رمز عبور جدید (اختیاری)</label>
          <input type="text" id="me-password" dir="ltr" style="text-align:right;" placeholder="حداقل ۸ نویسه">
        </div>
      </div>
      <button class="btn btn-primary" onclick="updateSelf()">ذخیره تغییرات حساب</button>
    </div>

  </div>

  <!-- فرم مخفی برای ورود به حالت مشاهده (POST + CSRF) -->
  <form id="view-form" method="POST" style="display:none;">
    <input type="hidden" name="do" value="view">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" name="owner" id="view-owner">
  </form>

  <!-- Modal رمز/ویرایش -->
  <div class="modal" id="modal">
    <div class="modal-box">
      <h3 id="modal-title"></h3>
      <div id="modal-body"></div>
      <div class="modal-actions">
        <button class="btn btn-primary" id="modal-ok">تایید</button>
        <button class="btn btn-cancel" onclick="closeModal()">انصراف</button>
      </div>
    </div>
  </div>

  <div class="toast" id="toast"></div>

  <script>
    const CSRF = window.ADMIN.csrf;

    function toast(msg, kind) {
      const t = document.getElementById('toast');
      t.textContent = msg; t.className = 'toast show ' + (kind || '');
      setTimeout(() => t.className = 'toast', 2600);
    }

    async function api(action, args) {
      const res = await fetch('api.php?action=' + encodeURIComponent(action), {
        method:'POST', headers:{'Content-Type':'application/json','X-CSRF-Token':CSRF},
        body: JSON.stringify({args: args || []})
      });
      if (res.status === 401) { window.location.href = 'login.php'; throw new Error('نشست منقضی'); }
      const data = await res.json();
      if (!res.ok || data.success === false) throw new Error(data.msg || data.message || 'خطای سرور');
      return data;
    }

    function esc(s){ return String(s==null?'':s).replace(/[&<>"']/g, c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }

    async function loadUsers() {
      const grid = document.getElementById('users-grid');
      try {
        const { users } = await api('adminListUsers');
        if (!users.length) { grid.innerHTML = '<div class="empty">کاربری وجود ندارد.</div>'; return; }
        grid.innerHTML = users.map(u => renderUser(u)).join('');
      } catch (e) { grid.innerHTML = '<div class="empty">خطا در بارگذاری: ' + esc(e.message) + '</div>'; }
    }

    function renderUser(u) {
      const c = u.counts;
      const roleBadge = u.role === 'admin' ? '<span class="badge admin">مدیر</span>' : '<span class="badge user">کاربر</span>';
      const offBadge = u.isActive ? '' : '<span class="badge off">غیرفعال</span>';
      const counts = u.role === 'admin' ? '' : `
        <div class="counts">
          <span class="chip">متربی <b>${c.students}</b></span>
          <span class="chip">جلسه <b>${c.sessions}</b></span>
          <span class="chip">طرح درس <b>${c.plans}</b></span>
          <span class="chip">یادداشت <b>${c.notes}</b></span>
          <span class="chip">آرشیو <b>${c.archive}</b></span>
        </div>`;
      let actions = '';
      if (u.role === 'user') {
        actions = `
          <button class="btn btn-view" onclick="viewSession(${u.id})">👁️ مشاهده جلسه</button>
          <button class="btn btn-pass" onclick="askPassword(${u.id}, '${esc(u.username)}')">🔑 رمز</button>
          <button class="btn btn-edit" onclick="askEdit(${u.id}, '${esc(u.username)}', '${esc(u.displayName)}')">✏️ ویرایش</button>
          <button class="btn btn-mi" onclick="toggleMi(${u.id})" title="دسترسی به بخش هوش‌های چندگانه">${u.canUseMi ? '🧠 هوش: باز' : '🔒 هوش: بسته'}</button>
          <button class="btn btn-toggle" onclick="toggleActive(${u.id})">${u.isActive ? '⛔ غیرفعال' : '✅ فعال'}</button>`;
      }
      return `
        <div class="user-card ${u.isActive ? '' : 'inactive'}">
          <div class="user-head">
            <div>
              <div class="name">${esc(u.displayName || u.username)} ${offBadge}</div>
              <div class="uname">${esc(u.username)}</div>
            </div>
            ${roleBadge}
          </div>
          ${counts}
          <div class="actions">${actions}</div>
        </div>`;
    }

    function viewSession(id) {
      document.getElementById('view-owner').value = id;
      document.getElementById('view-form').submit();
    }

    async function createUser() {
      const username = document.getElementById('nu-username').value.trim();
      const displayName = document.getElementById('nu-display').value.trim();
      const password = document.getElementById('nu-password').value;
      if (!username || !password) { toast('نام کاربری و رمز را وارد کنید', 'err'); return; }
      try {
        await api('adminCreateUser', [{ username, displayName, password }]);
        toast('کاربر ساخته شد', 'ok');
        document.getElementById('nu-username').value = '';
        document.getElementById('nu-display').value = '';
        document.getElementById('nu-password').value = '';
        loadUsers();
      } catch (e) { toast(e.message, 'err'); }
    }

    async function toggleActive(id) {
      try { const r = await api('adminToggleActive', [id]); toast(r.msg, 'ok'); loadUsers(); }
      catch (e) { toast(e.message, 'err'); }
    }

    async function toggleMi(id) {
      try { const r = await api('adminToggleMi', [id]); toast(r.msg, 'ok'); loadUsers(); }
      catch (e) { toast(e.message, 'err'); }
    }

    // ---- Modal helpers ----
    let modalHandler = null;
    function openModal(title, bodyHtml, onOk) {
      document.getElementById('modal-title').textContent = title;
      document.getElementById('modal-body').innerHTML = bodyHtml;
      modalHandler = onOk;
      document.getElementById('modal').classList.add('show');
    }
    function closeModal() { document.getElementById('modal').classList.remove('show'); modalHandler = null; }
    document.getElementById('modal-ok').onclick = () => { if (modalHandler) modalHandler(); };

    function askPassword(id, uname) {
      openModal('تغییر رمز «' + uname + '»',
        '<div class="field"><label>رمز عبور جدید</label><input type="text" id="m-pass" dir="ltr" style="text-align:right;" placeholder="حداقل ۶ نویسه"></div>',
        async () => {
          const p = document.getElementById('m-pass').value;
          try { await api('adminSetPassword', [id, p]); toast('رمز تغییر کرد', 'ok'); closeModal(); }
          catch (e) { toast(e.message, 'err'); }
        });
    }

    function askEdit(id, uname, dname) {
      openModal('ویرایش کاربر',
        '<div class="field"><label>نام کاربری</label><input type="text" id="m-uname" dir="ltr" style="text-align:right;" value="' + esc(uname) + '"></div>' +
        '<div class="field"><label>نام نمایشی</label><input type="text" id="m-dname" value="' + esc(dname) + '"></div>',
        async () => {
          const username = document.getElementById('m-uname').value.trim();
          const displayName = document.getElementById('m-dname').value.trim();
          try { await api('adminUpdateUser', [{ id, username, displayName }]); toast('بروزرسانی شد', 'ok'); closeModal(); loadUsers(); }
          catch (e) { toast(e.message, 'err'); }
        });
    }

    async function updateSelf() {
      const currentPassword = document.getElementById('me-current').value;
      const username = document.getElementById('me-username').value.trim();
      const newPassword = document.getElementById('me-password').value;
      if (!currentPassword) { toast('رمز عبور فعلی را وارد کنید', 'err'); return; }
      if (!username && !newPassword) { toast('نام کاربری یا رمز جدید را وارد کنید', 'err'); return; }
      try {
        await api('adminUpdateSelf', [{ currentPassword, username, newPassword }]);
        toast('حساب بروزرسانی شد — در صورت تغییر رمز، دوباره وارد شوید', 'ok');
        document.getElementById('me-current').value = '';
        document.getElementById('me-username').value = '';
        document.getElementById('me-password').value = '';
      } catch (e) { toast(e.message, 'err'); }
    }

    loadUsers();
  </script>
</body>
</html>
