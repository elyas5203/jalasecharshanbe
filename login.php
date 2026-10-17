<?php
require_once __DIR__ . '/auth.php';

// اگر از قبل وارد شده، به مقصد درست هدایت شود
if (isLoggedIn()) {
    header('Location: ' . (isAdmin() ? 'admin.php' : 'index.php'));
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfCheck($_POST['csrf_token'] ?? '')) {
        $error = 'نشست منقضی شده است. دوباره تلاش کنید.';
    } elseif (authAttemptLogin($_POST['username'] ?? '', $_POST['password'] ?? '', $err)) {
        header('Location: ' . (isAdmin() ? 'admin.php' : 'index.php'));
        exit;
    } else {
        $error = $err ?: 'ورود ناموفق بود.';
    }
}
$csrf = csrfToken();
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <title>ورود به سامانه مدیریت هوشمند کلاس</title>
  <link rel="icon" href="assets/favicon.svg" type="image/svg+xml">
  <link rel="preload" href="assets/fonts/Vazirmatn-400.woff2" as="font" type="font/woff2" crossorigin>
  <link rel="preload" href="assets/fonts/Vazirmatn-700.woff2" as="font" type="font/woff2" crossorigin>
  <link rel="stylesheet" href="assets/app.css">
  <style>
    body {
      padding-bottom: 0 !important;
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      margin: 0;
      position: relative;
      overflow-y: auto;
    }
    .login-ambient-1 {
      position: fixed;
      top: 10%;
      right: 15%;
      width: 380px;
      height: 380px;
      background: radial-gradient(circle, rgba(79, 70, 229, 0.28) 0%, rgba(99, 102, 241, 0) 70%);
      border-radius: 50%;
      filter: blur(50px);
      pointer-events: none;
      z-index: 0;
    }
    .login-ambient-2 {
      position: fixed;
      bottom: 10%;
      left: 15%;
      width: 360px;
      height: 360px;
      background: radial-gradient(circle, rgba(168, 85, 247, 0.22) 0%, rgba(217, 70, 239, 0) 70%);
      border-radius: 50%;
      filter: blur(50px);
      pointer-events: none;
      z-index: 0;
    }
    .theme-pill-top {
      position: fixed;
      top: 20px;
      left: 20px;
      z-index: 10;
      background: var(--glass-bg);
      backdrop-filter: blur(16px);
      -webkit-backdrop-filter: blur(16px);
      border: 1px solid var(--glass-border);
      padding: 8px 14px;
      border-radius: var(--radius-pill);
      cursor: pointer;
      font-size: 0.9rem;
      display: flex;
      align-items: center;
      gap: 6px;
      color: var(--text-main);
      box-shadow: var(--glass-shadow);
      transition: all 0.2s ease;
    }
    .theme-pill-top:hover {
      transform: translateY(-2px);
      box-shadow: var(--glass-shadow-lg);
    }
    .login-card {
      width: 100%;
      max-width: 410px;
      background: var(--glass-bg);
      backdrop-filter: blur(28px) saturate(190%);
      -webkit-backdrop-filter: blur(28px) saturate(190%);
      border: 1px solid var(--glass-border);
      border-radius: var(--radius-lg);
      padding: 40px 32px;
      box-shadow: var(--glass-shadow-lg);
      position: relative;
      z-index: 1;
      margin: 20px;
    }
    .brand {
      text-align: center;
      margin-bottom: 28px;
    }
    .brand .logo {
      width: 68px;
      height: 68px;
      margin: 0 auto 16px;
      border-radius: 22px;
      background: linear-gradient(135deg, var(--accent), #7c3aed);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 2.1rem;
      color: #fff;
      box-shadow: 0 12px 28px rgba(79, 70, 229, 0.35);
      border: 1px solid rgba(255, 255, 255, 0.25);
    }
    .brand h1 {
      margin: 0;
      font-size: 1.35rem;
      font-weight: 800;
      color: var(--text-main);
      letter-spacing: -0.02em;
    }
    .brand p {
      margin: 8px 0 0;
      font-size: 0.88rem;
      color: var(--text-muted);
    }
    .field {
      margin-bottom: 18px;
    }
    .field label {
      display: block;
      font-size: 0.86rem;
      color: var(--text-main);
      margin-bottom: 8px;
      font-weight: 700;
    }
    .field input {
      width: 100%;
      padding: 13px 16px;
      border: 1.5px solid var(--glass-border);
      border-radius: var(--radius-md);
      font-size: 0.98rem;
      background: var(--glass-bg-subtle);
      color: var(--text-main);
      transition: border-color 0.2s, box-shadow 0.2s, background-color 0.2s;
    }
    .field input:focus {
      outline: none;
      border-color: var(--accent);
      box-shadow: 0 0 0 4px var(--accent-light);
      background: var(--glass-bg);
    }
    .pw-wrap {
      position: relative;
    }
    .pw-toggle {
      position: absolute;
      left: 12px;
      top: 50%;
      transform: translateY(-50%);
      background: none;
      border: none;
      cursor: pointer;
      color: var(--text-muted);
      font-size: 0.82rem;
      font-weight: 600;
      padding: 4px 8px;
      border-radius: var(--radius-sm);
      transition: color 0.15s;
    }
    .pw-toggle:hover {
      color: var(--accent);
    }
    .btn-login {
      width: 100%;
      padding: 14px;
      border: none;
      border-radius: var(--radius-md);
      cursor: pointer;
      background: linear-gradient(135deg, var(--accent), var(--accent-hover));
      color: #fff;
      font-size: 1.05rem;
      font-weight: 700;
      margin-top: 10px;
      box-shadow: 0 10px 24px rgba(79, 70, 229, 0.35);
      transition: transform 0.15s, box-shadow 0.2s;
    }
    .btn-login:hover {
      transform: translateY(-2px);
      box-shadow: 0 14px 32px rgba(79, 70, 229, 0.45);
    }
    .btn-login:active {
      transform: translateY(0);
    }
    .error {
      background: rgba(239, 68, 68, 0.12);
      color: #ef4444;
      border: 1px solid rgba(239, 68, 68, 0.25);
      border-radius: var(--radius-md);
      padding: 12px 16px;
      font-size: 0.88rem;
      font-weight: 600;
      margin-bottom: 20px;
      text-align: center;
      backdrop-filter: blur(8px);
    }
  </style>
</head>
<body>
  <div class="login-ambient-1"></div>
  <div class="login-ambient-2"></div>

  <button type="button" class="theme-pill-top" onclick="toggleDark()" title="تغییر تم">
    <span id="theme-icon">🌓</span>
    <span id="theme-label" style="font-size:0.8rem;">تم</span>
  </button>

  <form class="login-card" method="POST" autocomplete="off">
    <div class="brand">
      <div class="logo">📚</div>
      <h1>مدیریت هوشمند کلاس</h1>
      <p>برای ورود به سامانه، مشخصات خود را وارد کنید</p>
    </div>

    <?php if ($error): ?>
      <div class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">

    <div class="field">
      <label for="username">نام کاربری</label>
      <input type="text" id="username" name="username" required autofocus dir="ltr" style="text-align:right;" placeholder="مثلاً: elyas">
    </div>

    <div class="field">
      <label for="password">رمز عبور</label>
      <div class="pw-wrap">
        <input type="password" id="password" name="password" required dir="ltr" style="text-align:right;" placeholder="رمز عبور شما">
        <button type="button" class="pw-toggle" onclick="const p=document.getElementById('password'); p.type=p.type==='password'?'text':'password'; this.textContent=p.type==='password'?'نمایش':'مخفی';">نمایش</button>
      </div>
    </div>

    <button type="submit" class="btn-login">ورود به پنل</button>
  </form>

  <script>
    // بازیابی تم ذخیره شده
    (function() {
      const savedTheme = localStorage.getItem('theme_preference');
      const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
      if (savedTheme === 'dark' || (!savedTheme && prefersDark)) {
        document.body.classList.add('dark-mode');
        const icon = document.getElementById('theme-icon');
        if (icon) icon.textContent = '☀️';
      }
    })();

    function toggleDark() {
      const isDark = document.body.classList.toggle('dark-mode');
      localStorage.setItem('theme_preference', isDark ? 'dark' : 'light');
      const icon = document.getElementById('theme-icon');
      if (icon) icon.textContent = isDark ? '☀️' : '🌙';
    }
  </script>
</body>
</html>
