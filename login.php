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
  <title>ورود به سامانه</title>
  <link rel="icon" href="assets/favicon.svg" type="image/svg+xml">
  <style>
    :root { --primary:#4f46e5; --primary-d:#4338ca; --bg:#0f172a; --card:#ffffff; }
    * { box-sizing: border-box; }
    body {
      margin:0; min-height:100vh; font-family: Tahoma, Vazirmatn, sans-serif;
      display:flex; align-items:center; justify-content:center; padding:20px;
      background: linear-gradient(135deg, #1e293b 0%, #0f172a 60%, #312e81 100%);
    }
    .login-card {
      width:100%; max-width:400px; background:var(--card); border-radius:22px;
      padding:38px 30px; box-shadow:0 30px 70px rgba(2,6,23,.55);
    }
    .brand { text-align:center; margin-bottom:26px; }
    .brand .logo {
      width:66px; height:66px; margin:0 auto 14px; border-radius:20px;
      background:linear-gradient(135deg,var(--primary),#7c3aed); display:flex;
      align-items:center; justify-content:center; font-size:2rem; color:#fff;
      box-shadow:0 12px 26px rgba(79,70,229,.4);
    }
    .brand h1 { margin:0; font-size:1.32rem; color:#1e293b; }
    .brand p { margin:6px 0 0; font-size:.85rem; color:#64748b; }
    .field { margin-bottom:16px; }
    .field label { display:block; font-size:.85rem; color:#334155; margin-bottom:7px; font-weight:bold; }
    .field input {
      width:100%; padding:13px 15px; border:1.5px solid #e2e8f0; border-radius:13px;
      font-size:1rem; font-family:inherit; transition:border-color .15s, box-shadow .15s; background:#f8fafc;
    }
    .field input:focus { outline:none; border-color:var(--primary); box-shadow:0 0 0 4px rgba(79,70,229,.13); background:#fff; }
    .btn-login {
      width:100%; padding:14px; border:none; border-radius:13px; cursor:pointer;
      background:linear-gradient(135deg,var(--primary),var(--primary-d)); color:#fff;
      font-size:1.03rem; font-weight:bold; font-family:inherit; margin-top:6px;
      transition:transform .1s, box-shadow .15s; box-shadow:0 10px 24px rgba(79,70,229,.35);
    }
    .btn-login:hover { transform:translateY(-1px); box-shadow:0 14px 30px rgba(79,70,229,.45); }
    .btn-login:active { transform:translateY(0); }
    .error {
      background:#fef2f2; color:#b91c1c; border:1px solid #fecaca; border-radius:12px;
      padding:11px 14px; font-size:.88rem; margin-bottom:18px; text-align:center;
    }
    .pw-wrap { position:relative; }
    .pw-toggle {
      position:absolute; left:12px; top:50%; transform:translateY(-50%);
      background:none; border:none; cursor:pointer; color:#94a3b8; font-size:.8rem; font-family:inherit;
    }
  </style>
</head>
<body>
  <form class="login-card" method="POST" autocomplete="off">
    <div class="brand">
      <div class="logo">📚</div>
      <h1>مدیریت هوشمند کلاس</h1>
      <p>برای ادامه، وارد حساب خود شوید</p>
    </div>

    <?php if ($error): ?>
      <div class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">

    <div class="field">
      <label for="username">نام کاربری</label>
      <input type="text" id="username" name="username" required autofocus dir="ltr" style="text-align:right;">
    </div>

    <div class="field">
      <label for="password">رمز عبور</label>
      <div class="pw-wrap">
        <input type="password" id="password" name="password" required dir="ltr" style="text-align:right;">
        <button type="button" class="pw-toggle" onclick="const p=document.getElementById('password'); p.type=p.type==='password'?'text':'password'; this.textContent=p.type==='password'?'نمایش':'مخفی';">نمایش</button>
      </div>
    </div>

    <button type="submit" class="btn-login">ورود</button>
  </form>
</body>
</html>
