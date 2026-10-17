<?php
/* ===========================================================================
 * نقطه‌ی اجرای زمان‌بندی‌شده (Cron) — گزارش دوره‌ای + خالی‌کردن صف تلگرام.
 *
 * روی cPanel یک Cron Job بساز که این آدرس را صدا بزند، مثلاً هر روز ساعت ۲۱:
 *   curl -s "https://YOURSITE/cron.php?key=CRON_KEY&type=daily"
 * یا برای گزارش هفتگی (مثلاً پنجشنبه‌ها):
 *   curl -s "https://YOURSITE/cron.php?key=CRON_KEY&type=weekly"
 *
 * مقدار CRON_KEY را از صفحه‌ی تنظیمات سامانه بردار.
 * ------------------------------------------------------------------------- */

define('CHARSHANBE_API_NO_ROUTER', true); // فقط توابع api.php را می‌خواهیم، نه روترش
require_once __DIR__ . '/api.php';

$pdo = db();
$s = settings($pdo);

// امنیت: بدون کلید درست، هیچ کاری انجام نمی‌شود
$key   = trim((string)($s['CRON_KEY'] ?? ''));
$given = trim((string)($_GET['key'] ?? ($argv[1] ?? '')));
if ($key === '' || $given === '' || !hash_equals($key, $given)) {
    http_response_code(403);
    exit('forbidden');
}

// مالک اصلی داده‌ها = اولین کاربر عادی (همان جلسه‌ای که داده‌ها به آن منتقل شده‌اند)
$owner = (int)($pdo->query("SELECT id FROM users WHERE role='user' ORDER BY id ASC LIMIT 1")->fetchColumn() ?: 0);
apiSetOwner($owner);

$type = ($_GET['type'] ?? ($argv[2] ?? 'daily')) === 'weekly' ? 'weekly' : 'daily';

// ۱) گزارش دوره‌ای
sendPeriodicSummary($pdo, $type);
// ۲) هشدارهای جدیدی که شاید هنوز نرفته‌اند
pushAlertsToTelegram($pdo);
// ۳) خالی‌کردن صف پیام‌های معطل‌مانده
flushTelegramQueue($pdo);

echo 'ok';
