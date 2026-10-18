<?php
require_once __DIR__ . '/auth.php';
$__me = requireLogin();
// ادمین دیتای خودش را ندارد؛ اگر بدون حالت تماشا وارد شد به پنل مدیریت هدایت شود
if ($__me['role'] === 'admin' && empty($_SESSION['admin_view_owner'])) { header('Location: admin.php'); exit; }
$__readOnly = isReadOnlyContext();
$__miEnabled = effectiveUserCanUseMi();
$__csrf = csrfToken();

// تشخیص صفحه اولیه درخواستی
$allowedPages = ['dash', 'tools', 'profiles', 'att', 'plans', 'archive', 'calendar', 'search', 'notes', 'settings', 'score'];
$initialPage = isset($initialPage) && in_array($initialPage, $allowedPages)
  ? $initialPage
  : (isset($_GET['page']) && in_array($_GET['page'], $allowedPages) ? $_GET['page'] : 'dash');
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <title>مدیریت هوشمند کلاس</title>
  <link rel="icon" href="assets/favicon.svg" type="image/svg+xml">
  <link rel="preload" href="assets/fonts/Vazirmatn-400.woff2" as="font" type="font/woff2" crossorigin>
  <link rel="preload" href="assets/fonts/Vazirmatn-700.woff2" as="font" type="font/woff2" crossorigin>
  <script>
    window.CHSH = {
      csrf: <?= json_encode($__csrf) ?>,
      readOnly: <?= $__readOnly ? 'true' : 'false' ?>,
      miEnabled: <?= $__miEnabled ? 'true' : 'false' ?>,
      initialPage: <?= json_encode($initialPage) ?>
    };
  </script>

<script>
<?php include __DIR__ . '/parts/chart.js'; ?>
</script>

  <link rel="stylesheet" href="assets/app.css">
</head>
<body class="<?= $__miEnabled ? '' : 'mi-locked' ?>">
<?php if ($__readOnly): ?>
<style>
  /* حالت فقط‌خواندنی: کنترل‌های نوشتنی غیرفعال و کم‌رنگ می‌شوند */
  body.chsh-readonly .btn-green, body.chsh-readonly .btn-red, body.chsh-readonly .btn-blue,
  body.chsh-readonly button[type="submit"], body.chsh-readonly .fab,
  body.chsh-readonly .plan-save, body.chsh-readonly .add-btn {
    opacity:.45 !important; pointer-events:none !important; cursor:not-allowed !important;
  }
  #chsh-ro-banner {
    position:sticky; top:0; z-index:6500; background:linear-gradient(135deg,#0369a1,#0284c7);
    color:#fff; text-align:center; padding:9px 14px; font-family:Tahoma,sans-serif;
    font-size:.86rem; font-weight:bold; box-shadow:0 4px 14px rgba(2,132,199,.35);
  }
  #chsh-ro-banner a { color:#e0f2fe; text-decoration:underline; margin-right:8px; }
</style>
<div id="chsh-ro-banner">👁️ حالت مشاهده — این اطلاعات فقط‌خواندنی است <a href="admin.php">بازگشت به پنل مدیریت</a></div>
<script>document.body.classList.add('chsh-readonly');</script>
<?php endif; ?>

  <?php include __DIR__ . '/views/layout/ambient_mesh.php'; ?>
  <?php include __DIR__ . '/views/layout/overlays.php'; ?>
  <?php include __DIR__ . '/views/layout/header.php'; ?>
  <?php include __DIR__ . '/views/layout/sidebar.php'; ?>

  <div class="container">
    <?php include __DIR__ . '/views/pages/dash.php'; ?>
    <?php include __DIR__ . '/views/pages/tools.php'; ?>
    <?php include __DIR__ . '/views/pages/profiles.php'; ?>
    <?php include __DIR__ . '/views/pages/single_profile.php'; ?>
    <?php include __DIR__ . '/views/pages/attendance.php'; ?>
    <?php include __DIR__ . '/views/pages/plans.php'; ?>
    <?php include __DIR__ . '/views/pages/archive.php'; ?>
    <?php include __DIR__ . '/views/pages/calendar.php'; ?>
    <?php include __DIR__ . '/views/pages/search.php'; ?>
    <?php include __DIR__ . '/views/pages/notes.php'; ?>
    <?php include __DIR__ . '/views/pages/settings.php'; ?>
    <?php include __DIR__ . '/views/pages/scores.php'; ?>
  </div>

  <?php include __DIR__ . '/views/layout/bottom_nav.php'; ?>

  <?php include __DIR__ . '/views/modals/plan_modal.php'; ?>
  <?php include __DIR__ . '/views/modals/timer_modal.php'; ?>
  <?php include __DIR__ . '/views/modals/grouping_modal.php'; ?>
  <?php include __DIR__ . '/views/modals/free_activity_modal.php'; ?>
  <?php include __DIR__ . '/views/modals/report_modal.php'; ?>

<script>
<?php include __DIR__ . '/parts/api-client.js'; ?>
</script>

<script>
<?php include __DIR__ . '/parts/app.js'; ?>
</script>

<script>
<?php include __DIR__ . '/parts/spotlight.js'; ?>
</script>

</body>
</html>