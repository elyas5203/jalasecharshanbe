<?php
/**
 * auth.php — لایه احراز هویت و کنترل نشست
 *
 * این فایل کاملاً جدا از منطق برنامه است و فقط مسئول ورود/خروج، نشست امن،
 * توکن CSRF و تشخیص نقش (کاربر عادی / ادمین) است. هیچ بخشی از مکانیزم
 * فعلی سایت را تغییر نمی‌دهد؛ فقط جلوی آن یک دروازه می‌گذارد.
 */

require_once __DIR__ . '/bootstrap.php';

/* ---------------------------------------------------------------------------
 * پیکربندی نشست امن (طبق best practiceها: HttpOnly, SameSite, strict mode)
 * ------------------------------------------------------------------------- */
function authBootSession(): void {
    if (session_status() === PHP_SESSION_ACTIVE) return;

    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['SERVER_PORT'] ?? '') == 443);

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    if ($https) ini_set('session.cookie_secure', '1');

    session_name('CHSHSESSID');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'secure'   => $https,
        'samesite' => 'Lax',
    ]);
    session_start();

    // چرخش دوره‌ای شناسه نشست برای جلوگیری از session fixation
    $now = time();
    if (!isset($_SESSION['_born'])) {
        $_SESSION['_born'] = $now;
    } elseif ($now - (int)$_SESSION['_born'] > 1800) { // هر ۳۰ دقیقه
        session_regenerate_id(true);
        $_SESSION['_born'] = $now;
    }
}

/* ---------------------------------------------------------------------------
 * توکن CSRF
 * ------------------------------------------------------------------------- */
function csrfToken(): string {
    authBootSession();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfCheck(?string $token): bool {
    authBootSession();
    return is_string($token)
        && !empty($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

/* ---------------------------------------------------------------------------
 * وضعیت ورود و نقش
 * ------------------------------------------------------------------------- */
function currentUser(): ?array {
    authBootSession();
    if (empty($_SESSION['uid'])) return null;
    static $cache = null;
    if ($cache && (int)$cache['id'] === (int)$_SESSION['uid']) return $cache;
    $st = db()->prepare('SELECT id, username, display_name, role, must_change_password, can_use_mi FROM users WHERE id=? AND is_active=1');
    $st->execute([(int)$_SESSION['uid']]);
    $u = $st->fetch();
    if (!$u) { authLogout(); return null; }
    $cache = $u;
    return $u;
}

function isLoggedIn(): bool { return currentUser() !== null; }
function isAdmin(): bool { $u = currentUser(); return $u && $u['role'] === 'admin'; }

/**
 * owner_id مؤثر برای اسکوپ داده‌ها.
 * - کاربر عادی: همیشه شناسه خودش.
 * - ادمین در «حالت تماشا»: شناسه کاربری که انتخاب کرده (فقط‌خواندنی).
 * - ادمین بدون حالت تماشا: null (ادمین جلسه‌ی دیتای خودش را ندارد).
 */
function effectiveOwnerId(): ?int {
    $u = currentUser();
    if (!$u) return null;
    if ($u['role'] === 'admin') {
        $view = $_SESSION['admin_view_owner'] ?? null;
        return $view ? (int)$view : null;
    }
    return (int)$u['id'];
}

/**
 * آیا کاربرِ مالکِ مؤثر به بخش «هوش‌های چندگانه» دسترسی دارد؟
 * - کاربر عادی: طبق فلگ خودش.
 * - ادمین در حالت تماشا: طبق فلگ کاربری که تماشا می‌کند.
 */
function effectiveUserCanUseMi(): bool {
    $oid = effectiveOwnerId();
    if (!$oid) return false;
    $u = currentUser();
    if ($u && (int)$u['id'] === (int)$oid) return (int)($u['can_use_mi'] ?? 0) === 1;
    $st = db()->prepare('SELECT can_use_mi FROM users WHERE id=?');
    $st->execute([(int)$oid]);
    return (int)$st->fetchColumn() === 1;
}

/**
 * آیا درخواست فعلی فقط‌خواندنی است؟ (ادمین در حال تماشای جلسه‌ی کاربر)
 * هر عملیات نوشتنی در این حالت باید بلاک شود.
 */
function isReadOnlyContext(): bool {
    $u = currentUser();
    return $u && $u['role'] === 'admin' && !empty($_SESSION['admin_view_owner']);
}

/* ---------------------------------------------------------------------------
 * ورود / خروج
 * ------------------------------------------------------------------------- */
function authAttemptLogin(string $username, string $password, ?string &$error = null): bool {
    authBootSession();
    $username = trim($username);

    // محدودیت نرخ تلاش (anti brute-force) در سطح نشست + تأخیر ثابت
    $fails = (int)($_SESSION['login_fails'] ?? 0);
    $lockUntil = (int)($_SESSION['login_lock_until'] ?? 0);
    if ($lockUntil > time()) {
        $error = 'به دلیل تلاش‌های ناموفق، ورود موقتاً قفل شده است. کمی بعد دوباره تلاش کنید.';
        return false;
    }

    if ($username === '' || $password === '') {
        $error = 'نام کاربری و رمز عبور را وارد کنید.';
        return false;
    }

    $st = db()->prepare('SELECT id, username, password_hash, role, is_active FROM users WHERE username=? LIMIT 1');
    $st->execute([$username]);
    $u = $st->fetch();

    // پیام خطای یکسان برای جلوگیری از user enumeration
    $genericFail = 'نام کاربری یا رمز عبور اشتباه است.';

    if (!$u || !(int)$u['is_active'] || !password_verify($password, $u['password_hash'])) {
        $_SESSION['login_fails'] = $fails + 1;
        if ($_SESSION['login_fails'] >= 5) {
            $_SESSION['login_lock_until'] = time() + 300; // ۵ دقیقه قفل
            $_SESSION['login_fails'] = 0;
        }
        usleep(300000); // تأخیر ثابت ۰.۳ ثانیه
        $error = $genericFail;
        return false;
    }

    // موفق: ری‌هش در صورت نیاز
    if (password_needs_rehash($u['password_hash'], PASSWORD_BCRYPT)) {
        $nh = password_hash($password, PASSWORD_BCRYPT);
        db()->prepare('UPDATE users SET password_hash=? WHERE id=?')->execute([$nh, $u['id']]);
    }

    // بازسازی نشست (جلوگیری از fixation)
    session_regenerate_id(true);
    $_SESSION['uid'] = (int)$u['id'];
    $_SESSION['_born'] = time();
    unset($_SESSION['login_fails'], $_SESSION['login_lock_until'], $_SESSION['admin_view_owner']);
    return true;
}

function authLogout(): void {
    authBootSession();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

/* ---------------------------------------------------------------------------
 * گاردها — بالای هر صفحه صدا زده می‌شوند
 * ------------------------------------------------------------------------- */
function requireLogin(): array {
    $u = currentUser();
    if (!$u) { header('Location: login.php'); exit; }
    return $u;
}

function requireAdmin(): array {
    $u = currentUser();
    if (!$u) { header('Location: login.php'); exit; }
    if ($u['role'] !== 'admin') { header('Location: index.php'); exit; }
    return $u;
}
