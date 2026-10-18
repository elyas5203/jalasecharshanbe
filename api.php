<?php
require_once __DIR__ . '/bootstrap.php';

/* ---------------------------------------------------------------------------
 * لایه‌ی اسکوپ چندمستأجری: مالک مؤثر برای همه‌ی کوئری‌ها.
 * روتر آن را از نشست احراز هویت‌شده تنظیم می‌کند؛ اسکریپت‌های CLI/import
 * می‌توانند با apiSetOwner() صریحاً مقداردهی کنند.
 * ------------------------------------------------------------------------- */
function apiOwner(): int { return (int)($GLOBALS['__api_owner'] ?? 0); }
function apiSetOwner(int $id): void { $GLOBALS['__api_owner'] = $id; }

// گاردهای عملیات ادمین در سطح API
function requireAdminApi(): void {
    if (!function_exists('isAdmin') || !isAdmin()) { http_response_code(403); jsonResponse(['success'=>false, 'msg'=>'دسترسی مجاز نیست']); }
}
function requireCsrfApi(): void {
    $tok = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? '');
    if (!csrfCheck($tok)) { http_response_code(403); jsonResponse(['success'=>false, 'msg'=>'نشست منقضی شده است. صفحه را تازه کنید.']); }
}

// عملیات‌هایی که داده را تغییر می‌دهند (برای بلاک در حالت فقط‌خواندنی و بررسی CSRF)
const CHARSHANBE_WRITE_ACTIONS = ['addStudent','deleteStudent','addManualScore','submitAttendance','deleteAttendanceSession','saveNote','saveSystemSettings','updateStudentProfile','uploadStudentImage','savePlan','deletePlan','submitMILog','submitMIObservation','saveManualLog','testTelegramConnection','setupTelegramWebhook','flushTelegramQueue','uploadPlanFile','deletePlanFile'];

if (!defined('CHARSHANBE_API_NO_ROUTER')) {
require_once __DIR__ . '/auth.php';
try {
    $action = $_GET['action'] ?? '';

    // ۱) باید وارد شده باشد
    $me = currentUser();
    if (!$me) { http_response_code(401); jsonResponse(['success'=>false, 'msg'=>'برای ادامه باید وارد شوید', 'auth'=>false]); }

    // ۲) اسکوپ مالک مؤثر (کاربر عادی=خودش، ادمین در حالت تماشا=کاربر انتخاب‌شده)
    $ownerId = effectiveOwnerId();

    $isWrite = in_array($action, CHARSHANBE_WRITE_ACTIONS, true);

    // ۳) در حالت فقط‌خواندنی (ادمین در حال تماشای جلسه‌ی کاربر) هیچ نوشتنی مجاز نیست
    if ($isWrite && isReadOnlyContext()) {
        jsonResponse(['success'=>false, 'msg'=>'در حالت مشاهده امکان ثبت یا تغییر وجود ندارد.']);
    }

    // ۴) بررسی توکن CSRF برای عملیات‌های نوشتنی
    if ($isWrite) {
        $tok = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? '');
        if (!csrfCheck($tok)) { http_response_code(403); jsonResponse(['success'=>false, 'msg'=>'نشست منقضی شده است. صفحه را تازه کنید.']); }
    }

    // ۵) عملیات نوشتنی به مالک معتبر نیاز دارد (ادمینِ بدون حالت تماشا نمی‌تواند بنویسد)
    if ($isWrite && !$ownerId) {
        jsonResponse(['success'=>false, 'msg'=>'جلسه‌ای برای ثبت اطلاعات انتخاب نشده است.']);
    }

    apiSetOwner((int)$ownerId);
    $args = bodyArgs();
    $pdo = db();
    switch ($action) {
        case 'getAppData': jsonResponse(getAppData($pdo));
        case 'getInitialData': jsonResponse(getInitialData($pdo));
        case 'getPlansData': jsonResponse(['plans'=>getPlans($pdo, (int)($args[0] ?? 150), (int)($args[1] ?? 0)), 'limit'=>(int)($args[0] ?? 150), 'offset'=>(int)($args[1] ?? 0)]);
        case 'getNotesData': jsonResponse(['notes'=>getNotesList($pdo, (int)($args[0] ?? 120), (int)($args[1] ?? 0)), 'limit'=>(int)($args[0] ?? 120), 'offset'=>(int)($args[1] ?? 0)]);
        case 'getArchiveData': jsonResponse(getArchiveData($pdo, $args[0] ?? 'grouping', $args[1] ?? '', (int)($args[2] ?? 180))); 
        case 'getClassMIData': jsonResponse(getClassMIData($pdo));
        case 'getFullStudentProfile': jsonResponse(getFullStudentProfile($pdo, $args[0] ?? ''));
        case 'addStudent': jsonResponse(addStudent($pdo, $args[0] ?? ''));
        case 'deleteStudent': jsonResponse(deleteStudent($pdo, $args[0] ?? ''));
        case 'addManualScore': jsonResponse(addManualScore($pdo, $args[0] ?? '', $args[1] ?? 0));
        case 'submitAttendance': jsonResponse(submitAttendance($pdo, $args[0] ?? []));
        case 'getAttendanceSessions': jsonResponse(getAttendanceSessions($pdo, (int)($args[0] ?? 80), (int)($args[1] ?? 0)));
        case 'getAttendanceSessionDetails': jsonResponse(getAttendanceSessionDetails($pdo, (int)($args[0] ?? 0)));
        case 'deleteAttendanceSession': jsonResponse(deleteAttendanceSession($pdo, (int)($args[0] ?? 0)));
        case 'saveNote': jsonResponse(saveNote($pdo, $args[0] ?? '', $args[1] ?? ''));
        case 'saveSystemSettings': jsonResponse(saveSystemSettings($pdo, $args[0] ?? []));
        case 'updateStudentProfile': jsonResponse(updateStudentProfile($pdo, $args[0] ?? []));
        case 'uploadStudentImage': jsonResponse(uploadStudentImage($pdo));
        case 'savePlan': jsonResponse(savePlan($pdo, $args[0] ?? '', $args[1] ?? '', $args[2] ?? '', $args[3] ?? '', $args[4] ?? '[]'));
        case 'deletePlan': jsonResponse(deletePlan($pdo, $args[0] ?? ''));
        case 'submitMILog': jsonResponse(submitMILog($pdo, $args[0] ?? '', $args[1] ?? '', $args[2] ?? '', $args[3] ?? 0));
        case 'saveManualLog': jsonResponse(saveManualLog($pdo, $args[0] ?? []));
        case 'testTelegramConnection': jsonResponse(testTelegramConnection($pdo));
        case 'setupTelegramWebhook': jsonResponse(setupTelegramWebhook($pdo));
        case 'flushTelegramQueue': jsonResponse(flushTelegramQueue($pdo));
        case 'uploadPlanFile': jsonResponse(uploadPlanFile($pdo));
        case 'deletePlanFile': jsonResponse(deletePlanFile($pdo, $args[0] ?? 0));
        case 'globalSearch': jsonResponse(globalSearch($pdo, $args[0] ?? ''));
        case 'getCalendarData': jsonResponse(getCalendarData($pdo));
        case 'getDashboardAlerts': jsonResponse(getDashboardAlerts($pdo));
        case 'getMonthlyReport': jsonResponse(getMonthlyReport($pdo, $args[0] ?? '', $args[1] ?? ''));

        // --- NEW MI System (0-4 scale) ---
        case 'getIntelligenceDomains': jsonResponse(getIntelligenceDomains($pdo));
        case 'getObservationSources': jsonResponse(getObservationSources($pdo));
        case 'getObservationContexts': jsonResponse(getObservationContexts($pdo));
        case 'submitMIObservation': jsonResponse(submitMIObservation($pdo, $args[0] ?? []));
        case 'getStudentMIProfileNew': jsonResponse(getStudentMIProfileNew($pdo, $args[0] ?? ''));

        // --- عملیات مخصوص ادمین (فقط نقش admin) ---
        case 'adminListUsers': requireAdminApi(); jsonResponse(adminListUsers($pdo));
        case 'adminCreateUser': requireAdminApi(); requireCsrfApi(); jsonResponse(adminCreateUser($pdo, $args[0] ?? []));
        case 'adminUpdateUser': requireAdminApi(); requireCsrfApi(); jsonResponse(adminUpdateUser($pdo, $args[0] ?? []));
        case 'adminSetPassword': requireAdminApi(); requireCsrfApi(); jsonResponse(adminSetPassword($pdo, (int)($args[0] ?? 0), $args[1] ?? ''));
        case 'adminToggleActive': requireAdminApi(); requireCsrfApi(); jsonResponse(adminToggleActive($pdo, (int)($args[0] ?? 0)));
        case 'adminToggleMi': requireAdminApi(); requireCsrfApi(); jsonResponse(adminToggleMi($pdo, (int)($args[0] ?? 0)));
        case 'adminUpdateSelf': requireAdminApi(); requireCsrfApi(); jsonResponse(adminUpdateSelf($pdo, $args[0] ?? []));

        default: jsonResponse(['success'=>false, 'msg'=>'Action not found']);
    }
} catch (Throwable $e) {
    http_response_code(500);
    jsonResponse(['success'=>false, 'message'=>$e->getMessage(), 'msg'=>'خطای سرور: ' . $e->getMessage()]);
}
}

function getAppData(PDO $pdo): array {
    $students = [];
    $fireCache = recentNoAbsenceMap($pdo, 8);
    foreach ($pdo->query('SELECT * FROM students WHERE owner_id='.apiOwner().' ORDER BY score DESC, name ASC') as $r) {
        $sid = (int)$r['id'];
        $students[] = ['name'=>$r['name'], 'score'=>(float)$r['score'], 'manualScore'=>(float)$r['manual_score'], 'image'=>fixUrl($r['image'] ?? ''), 'fire'=>($fireCache[$sid] ?? false)];
    }
    $max = $students ? $students[0]['score'] : -1;
    $top = $max > 0 ? implode(' - ', array_map(fn($s) => $s['name'], array_filter($students, fn($s) => $s['score'] == $max))) : '---';
    return ['students'=>$students, 'trend'=>getTrendData($pdo), 'sessionCount'=>getSessionCount($pdo), 'topStudent'=>$top ?: '---', 'notes'=>getNotesList($pdo), 'plans'=>getPlans($pdo), 'settings'=>clientSettings($pdo), 'moduleHistory'=>getCombinedHistory($pdo), 'alerts'=>getDashboardAlerts($pdo, false)];
}

function getInitialData(PDO $pdo): array {
    $students = [];
    $fireCache = recentNoAbsenceMap($pdo, 8);
    foreach ($pdo->query('SELECT * FROM students WHERE owner_id='.apiOwner().' ORDER BY score DESC, name ASC') as $r) {
        $sid = (int)$r['id'];
        $students[] = ['name'=>$r['name'], 'score'=>(float)$r['score'], 'manualScore'=>(float)$r['manual_score'], 'image'=>fixUrl($r['image'] ?? ''), 'fire'=>($fireCache[$sid] ?? false)];
    }
    $max = $students ? $students[0]['score'] : -1;
    $top = $max > 0 ? implode(' - ', array_map(fn($s) => $s['name'], array_filter($students, fn($s) => $s['score'] == $max))) : '---';
    return [
        'students'=>$students,
        'trend'=>getTrendData($pdo),
        'sessionCount'=>getSessionCount($pdo),
        'topStudent'=>$top ?: '---',
        'settings'=>clientSettings($pdo),
        'alerts'=>getDashboardAlerts($pdo, false),
        'notes'=>null,
        'plans'=>null,
        'moduleHistory'=>null,
        'lazy'=>true
    ];
}

function getSessionCount(PDO $pdo): int {
    return (int)$pdo->query('SELECT COUNT(*) FROM attendance_sessions WHERE owner_id='.apiOwner())->fetchColumn();
}

/** تنظیماتی که به فرانت‌اند فرستاده می‌شود؛ کلیدهای حساس سمت سرور (مثل کلید cron) حذف می‌شوند. */
function clientSettings(PDO $pdo): array {
    $s = settings($pdo);
    unset($s['CRON_KEY']); // راز cron نباید به مرورگر کاربر برود
    return $s;
}
function getStudentByName(PDO $pdo, string $name): ?array {
    $st = $pdo->prepare('SELECT * FROM students WHERE name=? AND owner_id=?');
    $st->execute([$name, apiOwner()]);
    $r = $st->fetch();
    return $r ?: null;
}

function getFullStudentProfile(PDO $pdo, string $studentName): array {
    $r = getStudentByName($pdo, $studentName);
    if (!$r) return ['success'=>false, 'msg'=>'دانش‌آموز یافت نشد'];
    $phones = $r['phones'] ?: '';
    if ($phones === '' && !empty($r['parent_phone'])) $phones = json_encode([['label'=>'والدین', 'number'=>$r['parent_phone']]], JSON_UNESCAPED_UNICODE);
    $info = ['name'=>$r['name'], 'score'=>(float)$r['score'], 'image'=>$r['image'] ?? '', 'displayImage'=>fixUrl($r['image'] ?? ''), 'bio'=>$r['bio'] ?? '', 'phone'=>$phones ?: '[]', 'parentPhone'=>$r['parent_phone'] ?? '', 'dob'=>$r['dob'] ?? '', 'school'=>$r['school'] ?? '', 'medical'=>$r['medical'] ?? '', 'parentNote'=>$r['parent_note'] ?? ''];
    return ['success'=>true, 'info'=>$info, 'mi'=>getStudentMIProfile($pdo, $studentName), 'stats'=>getStudentDetails($pdo, $studentName), 'fullHistory'=>getComprehensiveStudentHistory($pdo, $studentName)];
}

function getTrendData(PDO $pdo, int $limit = 0): array {
    $owner = apiOwner();
    $sessionSource = '(SELECT * FROM attendance_sessions WHERE owner_id=' . $owner . ')';
    if ($limit > 0) {
        $sessionSource = '(SELECT * FROM attendance_sessions WHERE owner_id=' . $owner . ' ORDER BY session_date DESC, id DESC LIMIT ' . max(1, $limit) . ')';
    }
    $sql = "SELECT s.id, s.session_date,
                   SUM(r.status='Present') p,
                   SUM(r.status='Absent') a,
                   SUM(r.status='Late') l,
                   SUM(r.status='Excused') e,
                   SUM(CASE WHEN r.status='Late' THEN r.late_minutes ELSE 0 END) late_minutes,
                   SUM(CASE WHEN r.status='Late' AND r.late_minutes BETWEEN 0 AND 5 THEN 1 ELSE 0 END) late_0_5,
                   SUM(CASE WHEN r.status='Late' AND r.late_minutes BETWEEN 6 AND 10 THEN 1 ELSE 0 END) late_6_10,
                   SUM(CASE WHEN r.status='Late' AND r.late_minutes BETWEEN 11 AND 15 THEN 1 ELSE 0 END) late_11_15,
                   SUM(CASE WHEN r.status='Late' AND r.late_minutes BETWEEN 16 AND 20 THEN 1 ELSE 0 END) late_16_20,
                   SUM(CASE WHEN r.status='Late' AND r.late_minutes BETWEEN 21 AND 30 THEN 1 ELSE 0 END) late_21_30,
                   SUM(CASE WHEN r.status='Late' AND r.late_minutes > 30 THEN 1 ELSE 0 END) late_31_plus,
                   SUM(CASE
                       WHEN r.status='Present' THEN 1
                       WHEN r.status='Late' THEN
                         CASE
                           WHEN r.late_minutes <= 5 THEN 0.95
                           WHEN r.late_minutes <= 10 THEN 0.85
                           WHEN r.late_minutes <= 15 THEN 0.70
                           WHEN r.late_minutes <= 20 THEN 0.55
                           WHEN r.late_minutes <= 30 THEN 0.40
                           ELSE 0.25
                         END
                       WHEN r.status='Excused' THEN 0.50
                       ELSE 0
                   END) weighted_score,
                   COUNT(r.id) total
            FROM {$sessionSource} s
            LEFT JOIN attendance_records r ON r.session_id=s.id
            GROUP BY s.id, s.session_date
            ORDER BY s.session_date ASC, s.id ASC";
    $labels = []; $data = []; $details = [];
    foreach ($pdo->query($sql) as $r) {
        $p = (int)$r['p']; $a = (int)$r['a']; $l = (int)$r['l']; $e = (int)$r['e']; $total = (int)$r['total'];
        $weighted = (float)($r['weighted_score'] ?? 0);
        $lateMinutes = (int)($r['late_minutes'] ?? 0);
        $labels[] = normalizeDateStr($r['session_date']);
        $data[] = $total ? (int)round(($weighted / $total) * 100) : 0;
        $details[] = [
            'p'=>$p, 'a'=>$a, 'l'=>$l, 'e'=>$e, 'total'=>$total,
            'lateMinutes'=>$lateMinutes,
            'rawPresentPercent'=>$total ? (int)round(($p / $total) * 100) : 0,
            'smartPercent'=>$total ? (int)round(($weighted / $total) * 100) : 0,
            'lateBuckets'=>[
                '0_5'=>(int)$r['late_0_5'], '6_10'=>(int)$r['late_6_10'], '11_15'=>(int)$r['late_11_15'],
                '16_20'=>(int)$r['late_16_20'], '21_30'=>(int)$r['late_21_30'], '31_plus'=>(int)$r['late_31_plus']
            ]
        ];
    }
    return ['labels'=>$labels, 'data'=>$data, 'details'=>$details, 'formula'=>'Present=100%, Late: 0-5=95%, 6-10=85%, 11-15=70%, 16-20=55%, 21-30=40%, 31+=25%, Excused=50%, Absent=0%'];
}

function recentNoAbsenceMap(PDO $pdo, int $sessionLimit = 8): array {
    $out = [];
    $students = array_map('intval', $pdo->query('SELECT id FROM students WHERE owner_id='.apiOwner())->fetchAll(PDO::FETCH_COLUMN));
    foreach ($students as $sid) $out[$sid] = false;
    if (!$students) return $out;

    $sessionLimit = max(1, $sessionLimit);
    $sessionIds = $pdo->query('SELECT id FROM attendance_sessions WHERE owner_id='.apiOwner().' ORDER BY session_date DESC, id DESC LIMIT ' . $sessionLimit)->fetchAll(PDO::FETCH_COLUMN);
    $sessionIds = array_map('intval', $sessionIds);
    if (count($sessionIds) < $sessionLimit) return $out;

    $placeholders = implode(',', array_fill(0, count($sessionIds), '?'));
    $st = $pdo->prepare("SELECT student_id, status FROM attendance_records WHERE session_id IN ($placeholders)");
    $st->execute($sessionIds);
    $seen = [];
    foreach ($st as $row) {
        $sid = (int)$row['student_id'];
        if (!isset($seen[$sid])) $seen[$sid] = ['count'=>0, 'bad'=>false];
        $seen[$sid]['count']++;
        if (in_array($row['status'], ['Absent', 'Excused'], true)) $seen[$sid]['bad'] = true;
    }
    foreach ($students as $sid) {
        $out[$sid] = isset($seen[$sid]) && $seen[$sid]['count'] >= $sessionLimit && !$seen[$sid]['bad'];
    }
    return $out;
}

function getNotesList(PDO $pdo, int $limit = 0, int $offset = 0): array {
    $sql = 'SELECT note_date date, note_text text FROM notes WHERE owner_id='.apiOwner().' ORDER BY id DESC';
    if ($limit > 0) $sql .= ' LIMIT ' . max(1, min(500, $limit)) . ' OFFSET ' . max(0, $offset);
    return $pdo->query($sql)->fetchAll();
}

function getPlans(PDO $pdo, int $limit = 0, int $offset = 0): array {
    $sql = 'SELECT plan_date date, title, priority, modules, sin, id, status FROM plans WHERE owner_id='.apiOwner().' ORDER BY plan_date DESC, updated_at DESC';
    if ($limit > 0) $sql .= ' LIMIT ' . max(1, min(500, $limit)) . ' OFFSET ' . max(0, $offset);
    $rows = $pdo->query($sql)->fetchAll();
    $filesByPlan = [];
    foreach ($pdo->query('SELECT pf.plan_id, pf.id, pf.original_name name, pf.stored_path path, pf.mime_type mime, pf.file_size size, pf.created_at FROM plan_files pf JOIN plans p ON p.id=pf.plan_id WHERE p.owner_id='.apiOwner().' ORDER BY pf.plan_id, pf.id DESC') as $file) {
        $filesByPlan[$file['plan_id']][] = ['id'=>$file['id'], 'name'=>$file['name'], 'path'=>$file['path'], 'mime'=>$file['mime'], 'size'=>$file['size'], 'created_at'=>$file['created_at']];
    }
    foreach ($rows as &$r) { $r['date'] = normalizeDateStr($r['date']); $r['files'] = $filesByPlan[$r['id']] ?? []; }
    return $rows;
}
function getPlanFiles(PDO $pdo, string $planId): array {
    $st = $pdo->prepare('SELECT id, original_name name, stored_path path, mime_type mime, file_size size, created_at FROM plan_files WHERE plan_id=? ORDER BY id DESC');
    $st->execute([$planId]);
    return $st->fetchAll();
}

function getCombinedHistory(PDO $pdo): array {
    $history = [];
    foreach ($pdo->query('SELECT * FROM plans WHERE owner_id='.apiOwner()) as $p) {
        $mods = json_decode($p['modules'] ?: '[]', true) ?: [];
        foreach ($mods as $m) if (!empty($m['name'])) $history[] = ['date'=>normalizeDateStr($p['plan_date']), 'source'=>'plan', 'planTitle'=>$p['title'], 'planId'=>$p['id'], 'type'=>$m['type'] ?? 'other', 'title'=>$m['name'], 'details'=>$m['desc'] ?? ''];
    }
    foreach ($pdo->query('SELECT * FROM archive_logs WHERE owner_id='.apiOwner()) as $a) $history[] = ['date'=>normalizeDateStr($a['log_date']), 'source'=>'manual', 'planTitle'=>'فعالیت ثبت شده دستی', 'type'=>$a['log_type'], 'title'=>$a['title'], 'details'=>$a['details_json']];
    usort($history, fn($a,$b) => strcmp($b['date'], $a['date']));
    return $history;
}

function saveManualLog(PDO $pdo, array $data): array {
    $st = $pdo->prepare('INSERT INTO archive_logs (owner_id, log_date, log_type, title, details_json) VALUES (?, ?, ?, ?, ?)');
    $st->execute([apiOwner(), $data['date'] ?? '', $data['type'] ?? 'other', $data['title'] ?? '', $data['details'] ?? '']);
    return ['success'=>true, 'msg'=>'✅ ثبت شد.'];
}

function saveSystemSettings(PDO $pdo, array $f): array {
    // محکم‌کاری: توکن، آیدی عددی و پروکسی باید ارقام انگلیسی/لاتین باشند؛ اگر ارقام فارسی
    // وارد شده باشد اینجا اصلاح می‌شود تا هرگز به‌صورت فارسی در دیتابیس ذخیره نشود.
    foreach (['BOT_TOKEN', 'ADMIN_CHAT_IDS', 'TG_PROXY'] as $k) {
        if (isset($f[$k])) $f[$k] = trim(toEnglishDigits((string)$f[$k]));
    }
    $st = $pdo->prepare('INSERT INTO settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)');
    foreach ($f as $k => $v) $st->execute([$k, $v]);
    return ['success'=>true, 'msg'=>'✅ تنظیمات ذخیره شد.'];
}

function deleteStudent(PDO $pdo, string $name): array {
    $r = getStudentByName($pdo, $name);
    if (!$r) return ['success'=>false, 'msg'=>'متربی یافت نشد'];

    // Delete profile image if exists
    $img = (string)($r['image'] ?? '');
    if ($img !== '' && preg_match('~^uploads/students/~', $img)) {
        $path = __DIR__ . '/' . $img;
        if (file_exists($path)) @unlink($path);
    }

    // Cascade delete handles attendance_records and mi_logs due to foreign keys in schema
    $pdo->prepare('DELETE FROM students WHERE id=?')->execute([$r['id']]);

    return ['success'=>true, 'msg'=>'متربی و تمامی اطلاعات مربوطه حذف شدند.'];
}

function addStudent(PDO $pdo, string $name): array {
    $name = trim($name);
    if ($name === '') return ['success'=>false, 'msg'=>'نام را وارد کنید'];
    $st = $pdo->prepare('INSERT IGNORE INTO students (owner_id, name) VALUES (?, ?)');
    $st->execute([apiOwner(), $name]);
    return ['success'=>true];
}

function addManualScore(PDO $pdo, string $name, $points): array {
    $r = getStudentByName($pdo, $name); if (!$r) return ['success'=>false];
    $points = (float)toEnglishDigits($points);
    $pdo->prepare('UPDATE students SET manual_score=manual_score+? WHERE id=?')->execute([$points, $r['id']]);
    updateCalculations($pdo);
    if (settingEnabled($pdo, 'TG_NOTIFY_SCORE', true)) {
        $emoji = $points > 0 ? '⭐' : '⚠️';
        notifySimple('ثبت امتیاز دستی', $emoji, ['👤 متربی: ' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8'), '📊 تغییر امتیاز: ' . ($points > 0 ? '+' : '') . $points], studentProfileButton($pdo, $name));
    }
    pushAlertsToTelegram($pdo); // اگر این تغییر باعث منفی‌شدن امتیاز شد، هشدارش برود
    return ['success'=>true, 'msg'=>'✅ امتیاز ثبت شد'];
}

function submitAttendance(PDO $pdo, array $data): array {
    $date = normalizeDateStr($data['date'] ?? '');
    $lists = ['p'=>[], 'a'=>[], 'l'=>[], 'e'=>[]];
    $pdo->beginTransaction();
    try {
        $pdo->prepare('INSERT INTO attendance_sessions (owner_id, session_date) VALUES (?, ?) ON DUPLICATE KEY UPDATE session_date=VALUES(session_date)')->execute([apiOwner(), $date]);
        $st = $pdo->prepare('SELECT id FROM attendance_sessions WHERE session_date=? AND owner_id=?');
        $st->execute([$date, apiOwner()]);
        $sid = (int)$st->fetchColumn();
        $up = $pdo->prepare('INSERT INTO attendance_records (session_id, student_id, status, late_minutes, absence_reason, status_text) VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE status=VALUES(status), late_minutes=VALUES(late_minutes), absence_reason=VALUES(absence_reason), status_text=VALUES(status_text)');
        foreach (($data['records'] ?? []) as $rec) {
            $student = getStudentByName($pdo, $rec['name'] ?? ''); if (!$student) continue;
            $status = $rec['status'] ?? 'Present';
            $mins = (int)preg_replace('/[^0-9]/', '', toEnglishDigits($rec['min'] ?? '0'));
            $reason = trim((string)($rec['reason'] ?? $rec['absence_reason'] ?? ''));
            $reasonVal = ($reason !== '') ? $reason : null;

            if ($status === 'Absent') {
                $txt = 'غیبت' . ($reason !== '' ? " ($reason)" : '');
                $lists['a'][] = $student['name'] . ($reason !== '' ? " (علت: $reason)" : '');
            } elseif ($status === 'Late') {
                $txt = 'تاخیر (' . $mins . ' دقیقه)' . ($reason !== '' ? " ($reason)" : '');
                $lists['l'][] = $student['name'] . ' (' . $mins . 'د)' . ($reason !== '' ? " (علت: $reason)" : '');
            } elseif ($status === 'Excused') {
                $txt = 'موجه' . ($reason !== '' ? " ($reason)" : '');
                $lists['e'][] = $student['name'] . ($reason !== '' ? " (علت: $reason)" : '');
            } else {
                $status = 'Present';
                $mins = 0;
                $reasonVal = null;
                $txt = 'حاضر';
                $lists['p'][] = $student['name'];
            }
            $up->execute([$sid, $student['id'], $status, $mins, $reasonVal, $txt]);
        }
        updateCalculations($pdo);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
    // اعلان به مدیر با ذکر «نام» غایب‌ها و تاخیری‌ها (نه فقط تعداد)
    if (settingEnabled($pdo, 'TG_NOTIFY_ATTENDANCE', true)) {
        $esc = fn($arr) => array_map(fn($n) => htmlspecialchars($n, ENT_QUOTES, 'UTF-8'), $arr);
        $lines = ['📅 تاریخ: ' . htmlspecialchars($date, ENT_QUOTES, 'UTF-8'), ''];
        $lines[] = '✅ حاضر: ' . count($lists['p']) . ' نفر';
        $lines[] = '❌ غایب: ' . (count($lists['a']) ? implode('، ', $esc($lists['a'])) : '۰');
        $lines[] = '🕐 تاخیر: ' . (count($lists['l']) ? implode('، ', $esc($lists['l'])) : '۰');
        $lines[] = 'ℹ️ موجه: ' . (count($lists['e']) ? implode('، ', $esc($lists['e'])) : '۰');
        notifySimple('حضور و غیاب ثبت شد', '📋', $lines);
    }
    pushAlertsToTelegram($pdo); // هشدارهای جدید (غیبت/تاخیر پشت‌سرهم و…) خودکار ارسال شوند
    return ['success'=>true, 'msg'=>'✅ حضور و غیاب ثبت شد.'];
}


function getAttendanceSessions(PDO $pdo, int $limit = 80, int $offset = 0): array {
    $limit = max(20, min(300, $limit));
    $offset = max(0, $offset);
    $sql = "SELECT s.id, s.session_date,
                   COUNT(r.id) total,
                   SUM(r.status='Present') present_count,
                   SUM(r.status='Late') late_count,
                   SUM(r.status='Absent') absent_count,
                   SUM(r.status='Excused') excused_count
            FROM attendance_sessions s
            LEFT JOIN attendance_records r ON r.session_id=s.id
            WHERE s.owner_id=" . apiOwner() . "
            GROUP BY s.id, s.session_date
            ORDER BY s.session_date DESC, s.id DESC
            LIMIT $limit OFFSET $offset";
    $rows = [];
    foreach ($pdo->query($sql) as $r) {
        $rows[] = [
            'id'=>(int)$r['id'],
            'date'=>normalizeDateStr($r['session_date']),
            'total'=>(int)$r['total'],
            'present'=>(int)$r['present_count'],
            'late'=>(int)$r['late_count'],
            'absent'=>(int)$r['absent_count'],
            'excused'=>(int)$r['excused_count'],
        ];
    }
    return ['sessions'=>$rows, 'limit'=>$limit, 'offset'=>$offset];
}

function getAttendanceSessionDetails(PDO $pdo, int $sessionId): array {
    if ($sessionId <= 0) return ['success'=>false, 'msg'=>'شناسه جلسه نامعتبر است'];
    $st = $pdo->prepare('SELECT id, session_date FROM attendance_sessions WHERE id=? AND owner_id=?');
    $st->execute([$sessionId, apiOwner()]);
    $session = $st->fetch();
    if (!$session) return ['success'=>false, 'msg'=>'جلسه پیدا نشد'];
    $st = $pdo->prepare('SELECT st.name, st.image, r.status, r.late_minutes, r.absence_reason, r.status_text
        FROM attendance_records r
        JOIN students st ON st.id=r.student_id
        WHERE r.session_id=?
        ORDER BY st.name ASC');
    $st->execute([$sessionId]);
    $records = [];
    foreach ($st as $r) {
        $records[] = [
            'name'=>$r['name'],
            'image'=>fixUrl($r['image'] ?? ''),
            'status'=>$r['status'],
            'lateMinutes'=>(int)$r['late_minutes'],
            'reason'=>$r['absence_reason'] ?? '',
            'text'=>$r['status_text'],
        ];
    }
    return ['success'=>true, 'session'=>['id'=>(int)$session['id'], 'date'=>normalizeDateStr($session['session_date'])], 'records'=>$records];
}

function deleteAttendanceSession(PDO $pdo, int $sessionId): array {
    if ($sessionId <= 0) return ['success'=>false, 'msg'=>'شناسه جلسه نامعتبر است'];
    $st = $pdo->prepare('SELECT session_date FROM attendance_sessions WHERE id=? AND owner_id=?');
    $st->execute([$sessionId, apiOwner()]);
    $date = $st->fetchColumn();
    if (!$date) return ['success'=>false, 'msg'=>'جلسه پیدا نشد'];
    $pdo->beginTransaction();
    try {
        $pdo->prepare('DELETE FROM attendance_records WHERE session_id=?')->execute([$sessionId]);
        $pdo->prepare('DELETE FROM attendance_sessions WHERE id=?')->execute([$sessionId]);
        updateCalculations($pdo);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
    return ['success'=>true, 'msg'=>'جلسه حضور و غیاب ' . normalizeDateStr($date) . ' حذف شد'];
}

function saveNote(PDO $pdo, string $d, string $t): array {
    $pdo->prepare('INSERT INTO notes (owner_id, note_date, note_text) VALUES (?, ?, ?)')->execute([apiOwner(), $d, $t]);
    // اعلان یادداشت به‌صورت «بی‌صدا» فرستاده می‌شود (کم‌اهمیت‌تر است، مزاحم نشود)
    if (settingEnabled($pdo, 'TG_NOTIFY_NOTE', false)) {
        $preview = function_exists('mb_substr') ? mb_substr($t, 0, 300) : substr($t, 0, 300);
        notifySimple('یادداشت جلسه ثبت شد', '📝', ['📅 تاریخ: ' . htmlspecialchars($d, ENT_QUOTES, 'UTF-8'), '', htmlspecialchars($preview, ENT_QUOTES, 'UTF-8')], null, true);
    }
    return ['success'=>true, 'msg'=>'یادداشت ذخیره شد'];
}

function updateStudentProfile(PDO $pdo, array $data): array {
    $r = getStudentByName($pdo, $data['originalName'] ?? ''); if (!$r) return ['success'=>false];
    $phones = $data['phone'] ?? '[]';
    json_decode($phones); if (json_last_error() !== JSON_ERROR_NONE) $phones = '[]';
    $pdo->prepare('UPDATE students SET image=?, bio=?, phones=?, dob=?, school=?, medical=?, parent_note=? WHERE id=?')->execute([$data['image'] ?? '', $data['bio'] ?? '', $phones, $data['dob'] ?? '', $data['school'] ?? '', $data['medical'] ?? '', $data['parentNote'] ?? '', $r['id']]);
    return ['success'=>true, 'msg'=>'✅ بروزرسانی شد'];
}

function uploadStudentImage(PDO $pdo): array {
    $studentName = trim((string)($_POST['studentName'] ?? ''));
    $student = getStudentByName($pdo, $studentName);
    if (!$student) return ['success'=>false, 'msg'=>'متربی یافت نشد'];
    if (empty($_FILES['image']) || !is_uploaded_file($_FILES['image']['tmp_name'])) return ['success'=>false, 'msg'=>'فایلی انتخاب نشده است'];
    if ((int)$_FILES['image']['size'] > 5 * 1024 * 1024) return ['success'=>false, 'msg'=>'حجم عکس نباید بیشتر از ۵ مگابایت باشد'];

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($_FILES['image']['tmp_name']);
    $extMap = ['image/jpeg'=>'jpg', 'image/png'=>'png', 'image/webp'=>'webp', 'image/gif'=>'gif'];
    if (!isset($extMap[$mime])) return ['success'=>false, 'msg'=>'فقط فایل‌های jpg، png، webp یا gif مجاز هستند'];

    $dir = __DIR__ . '/uploads/students';
    if (!is_dir($dir) && !mkdir($dir, 0775, true)) return ['success'=>false, 'msg'=>'پوشه آپلود ساخته نشد'];
    $filename = 'student_' . (int)$student['id'] . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extMap[$mime];
    $dest = $dir . '/' . $filename;
    if (!move_uploaded_file($_FILES['image']['tmp_name'], $dest)) return ['success'=>false, 'msg'=>'ذخیره عکس انجام نشد'];

    $path = 'uploads/students/' . $filename;
    $old = (string)($student['image'] ?? '');
    $pdo->prepare('UPDATE students SET image=? WHERE id=?')->execute([$path, $student['id']]);
    if (preg_match('~^uploads/students/~', $old)) {
        $oldPath = __DIR__ . '/' . $old;
        if (is_file($oldPath)) @unlink($oldPath);
    }
    return ['success'=>true, 'msg'=>'✅ عکس پروفایل آپلود شد', 'path'=>$path, 'displayImage'=>$path];
}


function deleteStoredUpload(string $storedPath): void {
    $storedPath = str_replace('\\', '/', trim($storedPath));
    if ($storedPath === '' || str_contains($storedPath, '..') || !preg_match('~^uploads/~', $storedPath)) return;
    $fullPath = __DIR__ . '/' . $storedPath;
    if (is_file($fullPath)) @unlink($fullPath);
}

function savePlan(PDO $pdo, string $id, string $date, string $title, string $sin, string $modules): array {
    $date = normalizeDateStr($date);
    $id = (!$id || $id === 'undefined') ? (string)round(microtime(true) * 1000) : $id;
    json_decode($modules); if (json_last_error() !== JSON_ERROR_NONE) $modules = '[]';
    // اگر طرح از قبل وجود دارد، فقط مالکش می‌تواند ویرایشش کند (جلوگیری از دستکاری بین‌کاربری)
    $own = $pdo->prepare('SELECT owner_id FROM plans WHERE id=?');
    $own->execute([$id]);
    $existingOwner = $own->fetchColumn();
    if ($existingOwner !== false && (int)$existingOwner !== apiOwner()) {
        return ['success'=>false, 'msg'=>'شناسه طرح درس نامعتبر است'];
    }
    $pdo->beginTransaction();
    try {
        $pdo->prepare('INSERT INTO plans (id, owner_id, plan_date, title, priority, modules, sin, status) VALUES (?, ?, ?, ?, "low", ?, ?, "Active") ON DUPLICATE KEY UPDATE plan_date=VALUES(plan_date), title=VALUES(title), modules=VALUES(modules), sin=VALUES(sin)')->execute([$id, apiOwner(), $date, $title, $modules, $sin]);
        $pdo->prepare('DELETE FROM essentials WHERE plan_id=?')->execute([$id]);
        $ins = $pdo->prepare('INSERT INTO essentials (plan_id, item_date, title, priority, item_name, uid, done, item_type) VALUES (?, ?, ?, "low", ?, ?, 0, ?)');
        foreach ((json_decode($modules, true) ?: []) as $m) {
            foreach (($m['items'] ?? []) as $it) {
                $ins->execute([$id, $date, $title, $it['name'] ?? '', (string)random_int(10000, 9999999), $it['type'] ?? 'have']);
            }
        }
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
    // اعلان طرح درس جدید در تلگرام — شامل عنوان، تاریخ، سین اجرایی و فهرست بخش‌ها
    if (settingEnabled($pdo, 'TG_NOTIFY_PLAN', true)) {
        $esc = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
        $lines = ['📌 عنوان: <b>' . $esc($title) . '</b>', '📅 تاریخ اجرا: ' . $esc($date)];
        $sinTxt = trim($sin);
        if ($sinTxt !== '') {
            // سین اجرایی چندخطی است؛ کامل می‌آید تا زمان‌بندی مشخص باشد
            $lines[] = '';
            $lines[] = '⏰ <b>سین اجرایی:</b>';
            $lines[] = $esc($sinTxt);
        }
        $typeLabels = ['game'=>'🎲 بازی', 'story'=>'📖 داستان', 'yade_hazrat'=>'🕌 یاد حضرت', 'trip'=>'🚌 اردو', 'other'=>'📝 سایر'];
        $modParts = [];
        foreach ((json_decode($modules, true) ?: []) as $m) {
            $label = $typeLabels[$m['type'] ?? 'other'] ?? '📝';
            $mName = trim((string)($m['name'] ?? ''));
            $modParts[] = '• ' . $label . ($mName !== '' ? ' — ' . $esc($mName) : '');
        }
        if ($modParts) { $lines[] = ''; $lines[] = '🧩 <b>بخش‌های برنامه:</b>'; $lines = array_merge($lines, $modParts); }
        notifySimple('طرح درس جدید ثبت شد', '📚', $lines);
    }
    return ['success'=>true, 'msg'=>'✅ طرح درس با موفقیت ذخیره شد.'];
}

function deletePlan(PDO $pdo, string $id): array {
    // فقط مالک طرح می‌تواند حذفش کند
    $own = $pdo->prepare('SELECT owner_id FROM plans WHERE id=?');
    $own->execute([$id]);
    $planOwner = $own->fetchColumn();
    if ($planOwner === false || (int)$planOwner !== apiOwner()) return ['success'=>false, 'msg'=>'طرح درس پیدا نشد'];
    $files = [];
    $st = $pdo->prepare('SELECT stored_path FROM plan_files WHERE plan_id=?');
    $st->execute([$id]);
    foreach ($st as $row) $files[] = (string)$row['stored_path'];
    $pdo->beginTransaction();
    try {
        $pdo->prepare('DELETE FROM essentials WHERE plan_id=?')->execute([$id]);
        $pdo->prepare('DELETE FROM plan_files WHERE plan_id=?')->execute([$id]);
        $pdo->prepare('DELETE FROM plans WHERE id=?')->execute([$id]);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
    foreach ($files as $path) deleteStoredUpload($path);
    $dir = __DIR__ . '/uploads/plans/' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $id);
    if (is_dir($dir)) @rmdir($dir);
    return ['success'=>true, 'msg'=>'🗑 طرح درس حذف شد'];
}

function getStudentDetails(PDO $pdo, string $name): array {
    $srow = getStudentByName($pdo, $name);
    if (!$srow) return ['history'=>[], 'stats'=>['p'=>0,'a'=>0,'l'=>0,'e'=>0,'lm'=>0], 'scores'=>['system'=>0,'manual'=>0,'total'=>0], 'growth'=>['labels'=>[], 'data'=>[]]];
    $rows = $pdo->prepare('SELECT s.session_date, r.* FROM attendance_records r JOIN attendance_sessions s ON s.id=r.session_id WHERE r.student_id=? ORDER BY s.session_date ASC');
    $rows->execute([$srow['id']]);
    $histAsc = $rows->fetchAll();
    $stats = ['p'=>0,'a'=>0,'l'=>0,'e'=>0,'lm'=>0]; $gl=[]; $gd=[]; $sys=0.0;
    foreach ($histAsc as $r) {
        if ($r['status'] === 'Present') { $sys += 1.0; $stats['p']++; }
        elseif ($r['status'] === 'Absent') { $sys -= 1.0; $stats['a']++; }
        elseif ($r['status'] === 'Late') {
            $mins = (int)($r['late_minutes'] ?? 0);
            $sys += lateScore($mins);
            $stats['l']++;
            $stats['lm'] += $mins;
        }
        elseif ($r['status'] === 'Excused') { $sys += 0.5; $stats['e']++; }
        $gl[] = normalizeDateStr($r['session_date']); $gd[] = round($sys + (float)$srow['manual_score'], 2);
    }
    $history = array_reverse(array_map(fn($r) => [
        'date'=>normalizeDateStr($r['session_date']),
        'status'=>$r['status_text'],
        'reason'=>$r['absence_reason'] ?? '',
        'statusCode'=>$r['status']
    ], $histAsc));
    $roundedSys = round($sys, 2);
    $manualScore = (float)$srow['manual_score'];
    return ['history'=>$history, 'stats'=>$stats, 'scores'=>['system'=>$roundedSys, 'manual'=>$manualScore, 'total'=>round($roundedSys + $manualScore, 2)], 'growth'=>['labels'=>$gl, 'data'=>$gd]];
}

function miConfig(PDO $pdo): array {
    $out = [];
    foreach ($pdo->query('SELECT * FROM mi_config ORDER BY id') as $r) $out[] = ['type'=>$r['type'], 'desc'=>$r['scientific_desc'], 'pos'=>$r['positive_traits'] ? array_map('trim', explode(',', $r['positive_traits'])) : [], 'neg'=>$r['negative_traits'] ? array_map('trim', explode(',', $r['negative_traits'])) : []];
    return $out;
}

function getStudentMIProfile(PDO $pdo, string $name): array {
    $s = getStudentByName($pdo, $name); if (!$s) return ['config'=>[], 'chart'=>['labels'=>[], 'data'=>[]], 'history'=>[], 'traitStates'=>[]];
    $conf = miConfig($pdo); $traitStates = []; $history = [];
    $st = $pdo->prepare('SELECT * FROM mi_logs WHERE student_id=? ORDER BY log_date ASC, id ASC'); $st->execute([$s['id']]);
    foreach ($st as $r) { $traitStates[$r['mi_type'] . '_' . $r['behavior']] = (int)$r['score']; $history[] = ['date'=>date('Y/m/d', strtotime($r['log_date'])), 'type'=>$r['mi_type'], 'behavior'=>$r['behavior'], 'score'=>(int)$r['score']]; }
    $labels=[]; $data=[];
    foreach ($conf as $c) { $score=0; foreach (array_merge($c['pos'], $c['neg']) as $t) $score += $traitStates[$c['type'].'_'.$t] ?? 0; $labels[]=$c['type']; $data[]=max(0, $score); }
    return ['config'=>$conf, 'chart'=>['labels'=>$labels, 'data'=>$data], 'history'=>array_slice(array_reverse($history), 0, 20), 'traitStates'=>$traitStates];
}

function submitMILog(PDO $pdo, string $sn, string $it, string $bh, $sc): array {
    // بخش هوش‌های چندگانه باید توسط مدیر برای این کاربر فعال شده باشد
    if (function_exists('effectiveUserCanUseMi') && !effectiveUserCanUseMi()) return ['success'=>false, 'msg'=>'بخش هوش‌های چندگانه برای شما فعال نیست.'];
    $s = getStudentByName($pdo, $sn); if (!$s) return ['success'=>false];
    $pdo->prepare('INSERT INTO mi_logs (student_id, mi_type, behavior, score) VALUES (?, ?, ?, ?)')->execute([$s['id'], $it, $bh, (int)$sc]);
    return ['success'=>true, 'msg'=>'✅ ثبت شد'];
}

function getComprehensiveStudentHistory(PDO $pdo, string $studentName): array {
    $combined = [];
    foreach (getStudentMIProfile($pdo, $studentName)['history'] as $h) $combined[] = ['date'=>normalizeDateStr($h['date']), 'type'=>'mi', 'title'=>$h['type'], 'desc'=>$h['behavior'] . ' (' . ($h['score'] === 1 ? '✅ مثبت' : ($h['score'] === -1 ? '❌ منفی' : '⚪ خنثی')) . ')', 'score'=>$h['score'], 'icon'=>'🧠'];
    $details = getStudentDetails($pdo, $studentName);
    foreach ($details['history'] as $h) {
        $icon = (strpos($h['status'], 'حاضر') !== false) ? '✅' : ((strpos($h['status'], 'غیبت') !== false) ? '❌' : ((strpos($h['status'], 'تاخیر') !== false) ? '⏰' : '📅'));
        $desc = $h['status'];
        if (!empty($h['reason']) && strpos($desc, $h['reason']) === false) {
            $desc .= ' (علت: ' . $h['reason'] . ')';
        }
        $combined[] = ['date'=>normalizeDateStr($h['date']), 'type'=>'att', 'title'=>'حضور و غیاب', 'desc'=>$desc, 'icon'=>$icon];
    }
    $attDates = array_map(fn($h)=>normalizeDateStr($h['date']), $details['history']);
    foreach (getCombinedHistory($pdo) as $act) if (in_array(normalizeDateStr($act['date']), $attDates, true)) $combined[] = ['date'=>normalizeDateStr($act['date']), 'type'=>'activity', 'title'=>$act['title'], 'desc'=>$act['type']==='grouping' ? 'شرکت در گروه‌بندی' : ($act['details'] ?: 'شرکت در فعالیت کلاسی'), 'icon'=>'🎯'];
    usort($combined, fn($a,$b)=>strcmp($b['date'], $a['date']));
    return $combined;
}

function getClassMIData(PDO $pdo): array {
    $students = $pdo->query('SELECT name FROM students WHERE owner_id='.apiOwner())->fetchAll(PDO::FETCH_COLUMN);
    $count = max(1, count($students)); $conf = miConfig($pdo); $totals = [];
    foreach ($conf as $c) $totals[$c['type']] = 0;
    foreach ($students as $name) { $profile = getStudentMIProfile($pdo, $name); foreach ($profile['chart']['labels'] as $i=>$label) $totals[$label] += $profile['chart']['data'][$i] ?? 0; }
    return ['labels'=>array_keys($totals), 'averages'=>array_map(fn($v)=>round($v/$count, 2), array_values($totals))];
}

// ============================================================
// NEW MI SYSTEM — Multi-source 0-4 Scale
// ============================================================

/**
 * Get all active intelligence domains with indicators
 */
function getIntelligenceDomains(PDO $pdo): array {
    $domains = $pdo->query('SELECT * FROM intelligence_domains WHERE is_active=1 ORDER BY display_order, id')->fetchAll();
    $result = [];
    foreach ($domains as $domain) {
        $indicators = $pdo->prepare('SELECT * FROM behavioral_indicators WHERE domain_id=? AND is_active=1 ORDER BY display_order, id');
        $indicators->execute([$domain['id']]);
        $result[] = [
            'id' => (int)$domain['id'],
            'slug' => $domain['slug'],
            'name_fa' => $domain['name_fa'],
            'icon' => $domain['icon'],
            'scientific_desc' => $domain['scientific_desc'],
            'indicators' => $indicators->fetchAll()
        ];
    }
    return $result;
}

/**
 * Get observation sources
 */
function getObservationSources(PDO $pdo): array {
    return $pdo->query('SELECT * FROM observation_sources WHERE is_active=1 ORDER BY display_order')->fetchAll();
}

/**
 * Get observation contexts
 */
function getObservationContexts(PDO $pdo): array {
    return $pdo->query('SELECT * FROM observation_contexts WHERE is_active=1 ORDER BY display_order')->fetchAll();
}

/**
 * Submit new MI observation (0-4 scale)
 */
function submitMIObservation(PDO $pdo, array $data): array {
    // Permission check
    if (function_exists('effectiveUserCanUseMi') && !effectiveUserCanUseMi()) {
        return ['success'=>false, 'msg'=>'بخش هوش‌های چندگانه برای شما فعال نیست.'];
    }

    // Validation
    $studentName = trim($data['student_name'] ?? '');
    $indicatorId = (int)($data['indicator_id'] ?? 0);
    $sourceId = (int)($data['source_id'] ?? 0);
    $contextId = !empty($data['context_id']) ? (int)$data['context_id'] : null;
    $noOpportunity = !empty($data['no_opportunity']);
    $score = $noOpportunity ? null : (isset($data['score']) ? (int)$data['score'] : null);
    $evidence = trim($data['evidence'] ?? '');
    $observedAt = $data['observed_at'] ?? date('Y-m-d');
    $sessionId = !empty($data['session_id']) ? (int)$data['session_id'] : null;

    // Get student
    $s = getStudentByName($pdo, $studentName);
    if (!$s) return ['success'=>false, 'msg'=>'دانش‌آموز پیدا نشد'];

    // Validate score
    if (!$noOpportunity && ($score === null || $score < 0 || $score > 4)) {
        return ['success'=>false, 'msg'=>'امتیاز باید بین ۰ تا ۴ باشد'];
    }

    if ($noOpportunity && $score !== null) {
        return ['success'=>false, 'msg'=>'نمی‌توان هم‌زمان امتیاز و «فرصت مشاهده نداشتم» را انتخاب کرد'];
    }

    // Validate indicator exists
    $indicator = $pdo->prepare('SELECT id FROM behavioral_indicators WHERE id=? AND is_active=1');
    $indicator->execute([$indicatorId]);
    if (!$indicator->fetch()) {
        return ['success'=>false, 'msg'=>'شاخص رفتاری نامعتبر است'];
    }

    // Validate source exists
    $source = $pdo->prepare('SELECT id FROM observation_sources WHERE id=? AND is_active=1');
    $source->execute([$sourceId]);
    if (!$source->fetch()) {
        return ['success'=>false, 'msg'=>'منبع مشاهده نامعتبر است'];
    }

    // Get current user as recorded_by
    $recordedBy = null;
    if (function_exists('currentUser')) {
        $user = currentUser();
        $recordedBy = $user ? (int)$user['id'] : null;
    }

    // Get owner_id from student
    $ownerId = (int)$s['owner_id'];

    // Insert observation
    $sql = 'INSERT INTO intelligence_observations
            (owner_id, student_id, indicator_id, source_id, context_id, score, no_opportunity, evidence_text, session_id, observed_at, recorded_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';

    $pdo->prepare($sql)->execute([
        $ownerId,
        $s['id'],
        $indicatorId,
        $sourceId,
        $contextId,
        $score,
        $noOpportunity ? 1 : 0,
        $evidence ?: null,
        $sessionId,
        $observedAt,
        $recordedBy
    ]);

    return ['success'=>true, 'msg'=>'✅ مشاهده ثبت شد', 'id'=>(int)$pdo->lastInsertId()];
}

/**
 * Calculate domain score with polarity normalization
 */
function calculateDomainScore(array $observations, array $indicators): ?float {
    if (empty($observations)) return null;

    // Map indicator_id => category
    $indicatorMap = [];
    foreach ($indicators as $ind) {
        $indicatorMap[(int)$ind['id']] = $ind['indicator_category'];
    }

    $normalizedScores = [];
    foreach ($observations as $obs) {
        if ($obs['no_opportunity']) continue; // Skip no-opportunity
        if ($obs['score'] === null) continue; // Skip null scores

        $score = (int)$obs['score'];
        $category = $indicatorMap[(int)$obs['indicator_id']] ?? 'neutral';

        // Polarity normalization
        if ($category === 'negative') {
            $normalized = 4 - $score; // Invert negative indicators
        } else {
            $normalized = $score;
        }

        $normalizedScores[] = $normalized;
    }

    if (empty($normalizedScores)) return null;

    return round(array_sum($normalizedScores) / count($normalizedScores), 2);
}

/**
 * Get new MI profile for student (0-4 system)
 */
function getStudentMIProfileNew(PDO $pdo, string $studentName): array {
    // Permission check
    if (function_exists('effectiveUserCanUseMi') && !effectiveUserCanUseMi()) {
        return ['success'=>false, 'msg'=>'دسترسی به هوش‌های چندگانه ندارید'];
    }

    $student = getStudentByName($pdo, $studentName);
    if (!$student) return ['domains'=>[], 'has_data'=>false, 'legacy_history'=>[]];

    $studentId = (int)$student['id'];
    $ownerId = (int)$student['owner_id'];

    // Get all domains with indicators
    $domains = getIntelligenceDomains($pdo);

    // Get all observations for this student
    $obs = $pdo->prepare('SELECT * FROM intelligence_observations
                          WHERE owner_id=? AND student_id=?
                          ORDER BY observed_at DESC, id DESC');
    $obs->execute([$ownerId, $studentId]);
    $allObservations = $obs->fetchAll();

    $result = [];
    $hasAnyData = false;

    foreach ($domains as $domain) {
        $domainId = (int)$domain['id'];

        // Filter observations for this domain
        $indicatorIds = array_map(fn($i) => (int)$i['id'], $domain['indicators']);
        $domainObservations = array_filter($allObservations, fn($o) => in_array((int)$o['indicator_id'], $indicatorIds));

        // Get latest observation per indicator per source
        $latestByIndicatorSource = [];
        foreach ($domainObservations as $observation) {
            $key = $observation['indicator_id'] . '_' . $observation['source_id'];
            if (!isset($latestByIndicatorSource[$key])) {
                $latestByIndicatorSource[$key] = $observation;
            }
        }

        // Calculate domain score
        $score = calculateDomainScore(array_values($latestByIndicatorSource), $domain['indicators']);

        // Calculate coverage
        $validObservations = array_filter($latestByIndicatorSource, fn($o) => !$o['no_opportunity'] && $o['score'] !== null);
        $coverage = count($domain['indicators']) > 0
            ? round(count($validObservations) / count($domain['indicators']) * 100, 1)
            : 0;

        if ($score !== null) $hasAnyData = true;

        $result[] = [
            'domain' => [
                'id' => $domainId,
                'slug' => $domain['slug'],
                'name_fa' => $domain['name_fa'],
                'icon' => $domain['icon']
            ],
            'score' => $score,
            'max_score' => 4,
            'coverage' => $coverage,
            'has_data' => $score !== null,
            'observation_count' => count($latestByIndicatorSource)
        ];
    }

    // Get legacy history
    $legacyHistory = [];
    $legacyQuery = $pdo->prepare('SELECT * FROM mi_logs_legacy WHERE student_id=? ORDER BY log_date DESC LIMIT 20');
    $legacyQuery->execute([$studentId]);
    foreach ($legacyQuery as $row) {
        $legacyHistory[] = [
            'legacy' => true,
            'date' => date('Y/m/d', strtotime($row['log_date'])),
            'type' => $row['mi_type'],
            'behavior' => $row['behavior'],
            'score' => (int)$row['score']
        ];
    }

    return [
        'domains' => $result,
        'has_data' => $hasAnyData,
        'legacy_history' => $legacyHistory
    ];
}


function uploadPlanFile(PDO $pdo): array {
    $planId = trim((string)($_POST['planId'] ?? ''));
    if ($planId === '') return ['success'=>false, 'msg'=>'شناسه طرح درس نامعتبر است'];
    $st = $pdo->prepare('SELECT id FROM plans WHERE id=? AND owner_id=?'); $st->execute([$planId, apiOwner()]);
    if (!$st->fetch()) return ['success'=>false, 'msg'=>'طرح درس پیدا نشد'];

    $uploads = normalizeUploadedFiles($_FILES['files'] ?? ($_FILES['file'] ?? null));
    if (!$uploads) return ['success'=>false, 'msg'=>'فایلی انتخاب نشده است'];

    $allowed = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif','application/pdf'=>'pdf','audio/mpeg'=>'mp3','audio/wav'=>'wav','audio/ogg'=>'ogg','video/mp4'=>'mp4'];
    $dir = __DIR__ . '/uploads/plans/' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $planId);
    if (!is_dir($dir) && !mkdir($dir, 0775, true)) return ['success'=>false, 'msg'=>'پوشه فایل ساخته نشد'];

    $saved = [];
    foreach ($uploads as $file) {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) continue;
        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) return ['success'=>false, 'msg'=>'آپلود یکی از فایل‌ها کامل انجام نشد'];
        if ((int)$file['size'] > 25 * 1024 * 1024) return ['success'=>false, 'msg'=>'حجم هر فایل نباید بیشتر از ۲۵ مگابایت باشد'];

        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']) ?: 'application/octet-stream';
        if (!isset($allowed[$mime])) return ['success'=>false, 'msg'=>'نوع فایل مجاز نیست. فقط تصویر، PDF، صوت و ویدئوی MP4 قابل آپلود است'];
        $original = basename((string)$file['name']);
        $safeExt = $allowed[$mime];
        $filename = time() . '_' . bin2hex(random_bytes(4)) . '.' . $safeExt;
        $dest = $dir . '/' . $filename;
        if (!move_uploaded_file($file['tmp_name'], $dest)) return ['success'=>false, 'msg'=>'ذخیره یکی از فایل‌ها انجام نشد'];
        $path = 'uploads/plans/' . basename($dir) . '/' . $filename;
        $pdo->prepare('INSERT INTO plan_files (plan_id, original_name, stored_path, mime_type, file_size) VALUES (?, ?, ?, ?, ?)')->execute([$planId, $original, $path, $mime, (int)$file['size']]);
        $saved[] = ['id'=>(int)$pdo->lastInsertId(), 'name'=>$original, 'path'=>$path, 'mime'=>$mime, 'size'=>(int)$file['size']];
    }

    if (!$saved) return ['success'=>false, 'msg'=>'فایلی انتخاب نشده است'];
    $count = count($saved);
    return ['success'=>true, 'msg'=>'✅ ' . $count . ' فایل طرح درس آپلود شد', 'files'=>$saved, 'file'=>$saved[0]];
}
function normalizeUploadedFiles($files): array {
    if (!$files) return [];
    if (is_array($files['name'] ?? null)) {
        $items = [];
        foreach ($files['name'] as $i => $name) {
            $items[] = [
                'name' => $name,
                'type' => $files['type'][$i] ?? '',
                'tmp_name' => $files['tmp_name'][$i] ?? '',
                'error' => $files['error'][$i] ?? UPLOAD_ERR_NO_FILE,
                'size' => $files['size'][$i] ?? 0,
            ];
        }
        return $items;
    }
    return [$files];
}
function deletePlanFile(PDO $pdo, $id): array {
    $st = $pdo->prepare('SELECT pf.* FROM plan_files pf JOIN plans p ON p.id=pf.plan_id WHERE pf.id=? AND p.owner_id=?'); $st->execute([(int)$id, apiOwner()]); $f = $st->fetch();
    if (!$f) return ['success'=>false, 'msg'=>'فایل یافت نشد'];
    $path = __DIR__ . '/' . $f['stored_path']; if (is_file($path)) @unlink($path);
    $pdo->prepare('DELETE FROM plan_files WHERE id=?')->execute([(int)$id]);
    return ['success'=>true, 'msg'=>'فایل حذف شد'];
}
function getDashboardAlerts(PDO $pdo, bool $refresh = true): array {
    if ($refresh) updateCalculations($pdo);
    $alerts = [];
    $owner = apiOwner();
    $latestMonth = (string)$pdo->query('SELECT SUBSTRING(session_date, 1, 7) FROM attendance_sessions WHERE owner_id='.$owner.' ORDER BY session_date DESC, id DESC LIMIT 1')->fetchColumn();

    $monthlyAbsences = [];
    if ($latestMonth !== '') {
        $st = $pdo->prepare('SELECT r.student_id, COUNT(*) c FROM attendance_records r JOIN attendance_sessions s ON s.id=r.session_id WHERE s.owner_id=? AND r.status="Absent" AND s.session_date LIKE ? GROUP BY r.student_id');
        $st->execute([$owner, $latestMonth . '/%']);
        foreach ($st as $r) $monthlyAbsences[(int)$r['student_id']] = (int)$r['c'];
    }

    $recent = [];
    $sessionIds = array_map('intval', $pdo->query('SELECT id FROM attendance_sessions WHERE owner_id='.$owner.' ORDER BY session_date DESC, id DESC LIMIT 2')->fetchAll(PDO::FETCH_COLUMN));
    if ($sessionIds) {
        $placeholders = implode(',', array_fill(0, count($sessionIds), '?'));
        $st = $pdo->prepare("SELECT student_id, status FROM attendance_records WHERE session_id IN ($placeholders) ORDER BY student_id ASC, session_id DESC");
        $st->execute($sessionIds);
        foreach ($st as $r) $recent[(int)$r['student_id']][] = $r['status'];
    }

    foreach ($pdo->query('SELECT id, name, score FROM students WHERE owner_id='.$owner.' ORDER BY name') as $student) {
        $id = (int)$student['id'];
        $lastTwo = $recent[$id] ?? [];
        if (count($lastTwo) >= 2 && $lastTwo[0] === 'Late' && $lastTwo[1] === 'Late') {
            $alerts[] = ['id'=>'late_consecutive:' . $id . ':' . $latestMonth, 'level'=>'warn', 'title'=>'تاخیر پشت سر هم', 'text'=>$student['name'] . ' در دو جلسه آخر پشت سر هم تاخیر داشته است.'];
        }
        if (count($lastTwo) >= 2 && $lastTwo[0] === 'Absent' && $lastTwo[1] === 'Absent') {
            $alerts[] = ['id'=>'absent_consecutive:' . $id . ':' . $latestMonth, 'level'=>'danger', 'title'=>'غیبت پشت سر هم', 'text'=>$student['name'] . ' در دو جلسه آخر پشت سر هم غیبت داشته است.'];
        }
        $monthAbs = $monthlyAbsences[$id] ?? 0;
        if ($monthAbs >= 2) {
            $alerts[] = ['id'=>'absent_month:' . $id . ':' . $latestMonth, 'level'=>'danger', 'title'=>'غیبت ماهانه', 'text'=>$student['name'] . ' در ماه ' . $latestMonth . ' تعداد ' . $monthAbs . ' غیبت دارد.'];
        }
        if ((float)$student['score'] < 0) {
            $alerts[] = ['id'=>'negative_score:' . $id, 'level'=>'danger', 'title'=>'امتیاز منفی', 'text'=>$student['name'] . ' امتیاز کل منفی دارد.'];
        }
    }
    return $alerts;
}

/**
 * هشدارهای «جدید» داشبورد را خودکار به تلگرام می‌فرستد و در جدول ضدتکرار ثبت می‌کند
 * تا هر هشدار فقط یک‌بار ارسال شود (نه با هر بار ثبت حضور).
 */
function pushAlertsToTelegram(PDO $pdo): void {
    if (!settingEnabled($pdo, 'TG_NOTIFY_ALERTS', true)) return;
    // اگر ربات هنوز تنظیم نشده، هیچ هشداری را «ارسال‌شده» علامت نزن تا بعداً از دست نرود
    $s = settings($pdo);
    if (trim($s['BOT_TOKEN'] ?? '') === '' || trim($s['ADMIN_CHAT_IDS'] ?? '') === '') return;
    $owner = apiOwner();
    try { $alerts = getDashboardAlerts($pdo, false); } catch (Throwable $e) { return; }
    if (!$alerts) return;

    // آیدی‌هایی که قبلاً فرستاده شده‌اند
    $seen = [];
    $st = $pdo->prepare('SELECT alert_id FROM telegram_sent_alerts WHERE owner_id=?');
    $st->execute([$owner]);
    foreach ($st as $row) $seen[$row['alert_id']] = true;

    $icons = ['warn'=>'⚠️', 'danger'=>'🚨', 'info'=>'ℹ️'];
    $mark = $pdo->prepare('INSERT IGNORE INTO telegram_sent_alerts (alert_id, owner_id) VALUES (?, ?)');
    foreach ($alerts as $a) {
        $aid = (string)($a['id'] ?? '');
        if ($aid === '' || isset($seen[$aid])) continue;
        $icon = $icons[$a['level'] ?? 'warn'] ?? '⚠️';
        notifySimple('هشدار: ' . ($a['title'] ?? ''), $icon, [htmlspecialchars($a['text'] ?? '', ENT_QUOTES, 'UTF-8')]);
        $mark->execute([$aid, $owner]);
    }
}

/** گزارش دوره‌ای (روزانه/هفتگی) برای ارسال خودکار توسط cron هاست. */
function sendPeriodicSummary(PDO $pdo, string $type = 'daily'): void {
    $owner = apiOwner();
    $studentCount = (int)$pdo->query('SELECT COUNT(*) FROM students WHERE owner_id=' . $owner)->fetchColumn();
    if ($studentCount === 0) return;

    $lines = ['👥 تعداد متربیان: ' . $studentCount];
    $sess = $pdo->query('SELECT id, session_date FROM attendance_sessions WHERE owner_id=' . $owner . ' ORDER BY session_date DESC, id DESC LIMIT 1')->fetch();
    if ($sess) {
        $counts = ['Present'=>0, 'Late'=>0, 'Absent'=>0, 'Excused'=>0];
        $st = $pdo->prepare('SELECT status, COUNT(*) c FROM attendance_records WHERE session_id=? GROUP BY status');
        $st->execute([(int)$sess['id']]);
        foreach ($st as $r) { if (isset($counts[$r['status']])) $counts[$r['status']] = (int)$r['c']; }
        $total = array_sum($counts);
        $pct = $total ? round(($counts['Present'] + $counts['Late']) / $total * 100) : 0;
        $lines[] = '📅 آخرین جلسه: ' . htmlspecialchars(normalizeDateStr($sess['session_date']), ENT_QUOTES, 'UTF-8');
        $lines[] = '📊 درصد حضور: ' . $pct . '٪';
        $lines[] = '✅ ' . $counts['Present'] . ' | 🕐 ' . $counts['Late'] . ' | ❌ ' . $counts['Absent'] . ' | ℹ️ ' . $counts['Excused'];
    }
    $top = $pdo->query('SELECT name FROM students WHERE owner_id=' . $owner . ' ORDER BY score DESC, name ASC LIMIT 1')->fetchColumn();
    if ($top) $lines[] = '🏆 متربی برتر: ' . htmlspecialchars((string)$top, ENT_QUOTES, 'UTF-8');

    try { $alerts = getDashboardAlerts($pdo, false); } catch (Throwable $e) { $alerts = []; }
    $lines[] = '🚨 هشدارهای فعال: ' . count($alerts);
    foreach (array_slice($alerts, 0, 6) as $a) $lines[] = '• ' . htmlspecialchars($a['text'] ?? '', ENT_QUOTES, 'UTF-8');

    notifySimple($type === 'weekly' ? 'گزارش هفتگی کلاس' : 'گزارش روزانه کلاس', '📈', $lines);
}

function getArchiveData(PDO $pdo, string $type = 'grouping', string $query = '', int $limit = 180): array {
    $limit = max(20, min(500, $limit));
    $query = trim($query);
    $items = [];
    $manualSql = 'SELECT * FROM archive_logs';
    $where = ['owner_id=?'];
    $params = [apiOwner()];
    if ($type !== '' && $type !== 'all') { $where[] = 'log_type=?'; $params[] = $type; }
    if ($query !== '') { $where[] = '(title LIKE ? OR details_json LIKE ? OR log_date LIKE ?)'; $like = '%' . $query . '%'; array_push($params, $like, $like, $like); }
    if ($where) $manualSql .= ' WHERE ' . implode(' AND ', $where);
    $manualSql .= ' ORDER BY log_date DESC, id DESC LIMIT ' . $limit;
    $st = $pdo->prepare($manualSql); $st->execute($params);
    foreach ($st as $a) $items[] = ['date'=>normalizeDateStr($a['log_date']), 'source'=>'manual', 'planTitle'=>'فعالیت ثبت شده دستی', 'type'=>$a['log_type'], 'title'=>$a['title'], 'details'=>$a['details_json'] ?: ''];

    if ($query === '' || count($items) < $limit) {
        $planLimit = min(500, $limit * 3);
        $plans = $pdo->query('SELECT id, plan_date, title, modules FROM plans WHERE owner_id='.apiOwner().' ORDER BY plan_date DESC, updated_at DESC LIMIT ' . $planLimit)->fetchAll();
        foreach ($plans as $p) {
            $mods = json_decode($p['modules'] ?: '[]', true) ?: [];
            foreach ($mods as $m) {
                $mType = $m['type'] ?? 'other';
                $mTitle = $m['name'] ?? '';
                $mDesc = $m['desc'] ?? '';
                if ($type !== '' && $type !== 'all' && $mType !== $type) continue;
                if ($query !== '' && mb_stripos(($mTitle . ' ' . $mDesc . ' ' . $p['title'] . ' ' . $p['plan_date']), $query) === false) continue;
                if ($mTitle !== '') $items[] = ['date'=>normalizeDateStr($p['plan_date']), 'source'=>'plan', 'planTitle'=>$p['title'], 'planId'=>$p['id'], 'type'=>$mType, 'title'=>$mTitle, 'details'=>$mDesc];
                if (count($items) >= $limit) break 2;
            }
        }
    }
    usort($items, fn($a,$b) => strcmp($b['date'], $a['date']));
    return ['moduleHistory'=>array_slice($items, 0, $limit), 'limited'=>true];
}

function getCalendarData(PDO $pdo): array {
    $events = [];
    foreach ($pdo->query('SELECT id, plan_date, title FROM plans WHERE owner_id='.apiOwner().' ORDER BY plan_date DESC, id DESC') as $p) $events[] = ['date'=>normalizeDateStr($p['plan_date']), 'type'=>'plan', 'title'=>$p['title'], 'id'=>$p['id']];
    foreach ($pdo->query('SELECT id, session_date FROM attendance_sessions WHERE owner_id='.apiOwner().' ORDER BY session_date DESC, id DESC') as $a) $events[] = ['date'=>normalizeDateStr($a['session_date']), 'type'=>'attendance', 'title'=>'حضور و غیاب', 'id'=>(int)$a['id']];
    usort($events, fn($a,$b)=>strcmp($b['date'],$a['date']));
    return ['events'=>$events];
}
function globalSearch(PDO $pdo, string $q): array {
    $q = trim($q); if ($q === '') return ['items'=>[]];
    $like = '%' . $q . '%'; $items = []; $owner = apiOwner();
    $st=$pdo->prepare('SELECT name, bio, school FROM students WHERE owner_id=? AND (name LIKE ? OR bio LIKE ? OR school LIKE ?) LIMIT 25'); $st->execute([$owner,$like,$like,$like]);
    foreach($st as $r) $items[]=['type'=>'متربی','title'=>$r['name'],'text'=>trim(($r['school'] ?? '') . ' ' . ($r['bio'] ?? ''))];
    $st=$pdo->prepare('SELECT title, plan_date, sin FROM plans WHERE owner_id=? AND (title LIKE ? OR sin LIKE ? OR modules LIKE ?) LIMIT 25'); $st->execute([$owner,$like,$like,$like]);
    foreach($st as $r) $items[]=['type'=>'طرح درس','title'=>$r['title'],'text'=>normalizeDateStr($r['plan_date'])];
    $st=$pdo->prepare('SELECT note_date, note_text FROM notes WHERE owner_id=? AND note_text LIKE ? LIMIT 25'); $st->execute([$owner,$like]);
    foreach($st as $r) $items[]=['type'=>'یادداشت','title'=>normalizeDateStr($r['note_date']),'text'=>(function_exists('mb_substr') ? mb_substr($r['note_text'],0,160) : substr($r['note_text'],0,160))];
    $st=$pdo->prepare('SELECT log_date, log_type, title, details_json FROM archive_logs WHERE owner_id=? AND (title LIKE ? OR details_json LIKE ?) LIMIT 25'); $st->execute([$owner,$like,$like]);
    foreach($st as $r) $items[]=['type'=>'آرشیو','title'=>$r['title'],'text'=>normalizeDateStr($r['log_date']) . ' - ' . $r['log_type']];
    return ['items'=>$items];
}
function getMonthlyReport(PDO $pdo, string $name, string $month): array {
    $full = getFullStudentProfile($pdo, $name); if (empty($full['success'])) return $full;
    $prefix = normalizeDateStr($month); if (strlen($prefix) >= 7) $prefix = substr($prefix, 0, 7);
    $full['stats']['history'] = array_values(array_filter($full['stats']['history'], fn($h)=>str_starts_with(normalizeDateStr($h['date']), $prefix)));
    $full['fullHistory'] = array_values(array_filter($full['fullHistory'], fn($h)=>str_starts_with(normalizeDateStr($h['date']), $prefix)));
    $full['month'] = $prefix;
    return $full;
}
/**
 * تست تشخیصی واقعی: به‌جای «همیشه موفق»، هر مرحله را جدا بررسی و علت دقیق را گزارش می‌کند.
 * ۱) توکن وارد شده؟ قالبش درست است؟  ۲) توکن معتبر است؟ (getMe)  ۳) به هر مدیر پیام می‌رسد؟
 */
function testTelegramConnection(PDO $pdo): array {
    $s = settings($pdo);
    $token = trim($s['BOT_TOKEN'] ?? '');
    $proxy = trim($s['TG_PROXY'] ?? '') ?: null;
    $chats = array_filter(array_map('trim', explode(',', $s['ADMIN_CHAT_IDS'] ?? '')));

    if ($token === '') return ['success'=>false, 'msg'=>"❌ توکن ربات خالی است.\nاز @BotFather یک ربات بساز، توکن را در فیلد «توکن ربات تلگرام» بذار و «ذخیره تنظیمات» را بزن."];
    if (!preg_match('/^\d{6,}:[A-Za-z0-9_-]{30,}$/', $token)) return ['success'=>false, 'msg'=>"❌ قالب توکن نادرست است.\nتوکن باید شبیه این باشد: 123456789:AAExxxxxxxxxxxxxxxxxxxxx"];

    // ۱) اعتبارسنجی توکن
    $me = telegramApi($token, 'getMe', [], $proxy);
    if (!$me['ok']) {
        $d = $me['description'] ?: 'نامشخص';
        if (stripos($d, 'Unauthorized') !== false) return ['success'=>false, 'msg'=>"❌ توکن نامعتبر است (Unauthorized).\nاز @BotFather → /mybots → API Token توکن تازه بگیر."];
        return ['success'=>false, 'msg'=>"❌ اتصال به تلگرام ناموفق بود:\n" . $d . (($proxy===null && stripos($d,'timed out')!==false) ? "\n(اگر تلگرام فیلتر است، در فیلد «پروکسی تلگرام» یک پروکسی بذار.)" : '')];
    }
    $botName = $me['result']['username'] ?? 'bot';

    // ۲) آیا مدیری تنظیم شده؟
    if (!$chats) return ['success'=>false, 'msg'=>"⚠️ توکن معتبر است (ربات: @{$botName})،\nاما «آیدی عددی مدیران» خالی است.\nآیدی عددی خودت را از @userinfobot بگیر و در آن فیلد وارد کن."];

    // ۳) ارسال پیام تست به هر مدیر و گزارش دقیق هر کدام
    $ok = []; $fail = [];
    foreach ($chats as $chat) {
        $r = telegramApi($token, 'sendMessage', ['chat_id'=>$chat, 'text'=>"✅ <b>تست اتصال موفق بود</b>\nربات: @{$botName}", 'parse_mode'=>'HTML'], $proxy);
        if ($r['ok']) { $ok[] = $chat; continue; }
        $d = $r['description'] ?: 'خطای نامشخص';
        if (stripos($d, 'chat not found') !== false)          $d = 'چت پیدا نشد — این مدیر باید اول در تلگرام به ربات پیام /start بدهد';
        elseif (stripos($d, 'bot was blocked') !== false)      $d = 'کاربر ربات را بلاک کرده است';
        elseif (stripos($d, 'user is deactivated') !== false)  $d = 'حساب کاربر غیرفعال است';
        $fail[] = "• {$chat}: {$d}";
    }

    if (!$fail) return ['success'=>true, 'msg'=>"✅ همه چیز درست است!\nربات: @{$botName}\nپیام تست برای " . count($ok) . " مدیر ارسال شد."];
    $msg = "ربات: @{$botName}\n";
    if ($ok) $msg .= "✅ ارسال موفق به: " . implode('، ', $ok) . "\n";
    $msg .= "❌ ناموفق:\n" . implode("\n", $fail);
    return ['success'=>count($ok) > 0, 'msg'=>$msg];
}

/**
 * تنظیم وبهوک. نکته مهم: تلگرام وبهوک را فقط روی دامنه اینترنتی با HTTPS می‌پذیرد؛
 * روی localhost/127.0.0.1 غیرممکن است (و لازم هم نیست — ارسال پیام بدون وبهوک کار می‌کند).
 */
function setupTelegramWebhook(PDO $pdo): string {
    $s = settings($pdo); $token = trim($s['BOT_TOKEN'] ?? ''); $proxy = trim($s['TG_PROXY'] ?? '') ?: null;
    if ($token === '') return '❌ اول توکن ربات را وارد و «ذخیره تنظیمات» را بزن.';
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $url = $scheme . '://' . $host . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') . '/webhook.php';

    if ($scheme !== 'https' || preg_match('/^(localhost|127\.|192\.168\.|10\.|0\.)/', $host)) {
        return "⚠️ وبهوک روی localhost کار نمی‌کند.\nتلگرام فقط آدرس اینترنتی با HTTPS را می‌پذیرد.\nآدرس فعلی: {$url}\n\nنکته: بدون وبهوک هم ارسال پیام، تست بات و اعلان‌ها کاملاً کار می‌کنند. وبهوک فقط برای دکمه‌های تعاملی روی سرور اینترنتی لازم است.";
    }
    $r = telegramApi($token, 'setWebhook', ['url'=>$url], $proxy);
    if ($r['ok']) return "✅ وبهوک با موفقیت تنظیم شد روی:\n{$url}";
    return "❌ خطا در تنظیم وبهوک:\n" . ($r['description'] ?: ($r['transport'] ?: 'نامشخص'));
}

/* ===========================================================================
 * عملیات مدیریت کاربران (فقط ادمین) — جدا از منطق برنامه‌ی کاربر
 * ========================================================================= */

/** فهرست کاربران به‌همراه شمار داده‌ی هر جلسه (برای پنل ادمین). */
function adminListUsers(PDO $pdo): array {
    // شمار داده‌ی هر مالک در یک پرس‌وجوی سبک
    $counts = [];
    foreach (['students','attendance_sessions','notes','plans','archive_logs'] as $t) {
        foreach ($pdo->query("SELECT owner_id, COUNT(*) c FROM `$t` GROUP BY owner_id") as $r) {
            $counts[(int)$r['owner_id']][$t] = (int)$r['c'];
        }
    }
    $users = [];
    foreach ($pdo->query('SELECT id, username, display_name, role, is_active, can_use_mi, created_at FROM users ORDER BY role DESC, id ASC') as $u) {
        $id = (int)$u['id'];
        $c = $counts[$id] ?? [];
        $users[] = [
            'id'=>$id,
            'username'=>$u['username'],
            'displayName'=>$u['display_name'] ?: '',
            'role'=>$u['role'],
            'isActive'=>(int)$u['is_active'] === 1,
            'canUseMi'=>(int)$u['can_use_mi'] === 1,
            'createdAt'=>$u['created_at'],
            'counts'=>[
                'students'=>$c['students'] ?? 0,
                'sessions'=>$c['attendance_sessions'] ?? 0,
                'notes'=>$c['notes'] ?? 0,
                'plans'=>$c['plans'] ?? 0,
                'archive'=>$c['archive_logs'] ?? 0,
            ],
        ];
    }
    return ['success'=>true, 'users'=>$users];
}

function adminValidateUsername(string $u): ?string {
    $u = trim($u);
    if ($u === '') return null;
    if (!preg_match('/^[A-Za-z0-9_.\-]{3,100}$/', $u)) return null;
    return $u;
}

/** ساخت کاربر جدید (هر کاربر = یک جلسه‌ی مستقل). */
function adminCreateUser(PDO $pdo, array $data): array {
    $username = adminValidateUsername($data['username'] ?? '');
    if ($username === null) return ['success'=>false, 'msg'=>'نام کاربری نامعتبر است (حداقل ۳ نویسه، فقط حروف/عدد/._-).'];
    $password = (string)($data['password'] ?? '');
    if (strlen($password) < 6) return ['success'=>false, 'msg'=>'رمز عبور باید حداقل ۶ نویسه باشد.'];
    $display = trim((string)($data['displayName'] ?? '')) ?: $username;

    $exists = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username=?');
    $exists->execute([$username]);
    if ((int)$exists->fetchColumn() > 0) return ['success'=>false, 'msg'=>'این نام کاربری قبلاً ثبت شده است.'];

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $pdo->prepare("INSERT INTO users (username, password_hash, display_name, role) VALUES (?, ?, ?, 'user')")
        ->execute([$username, $hash, $display]);
    return ['success'=>true, 'msg'=>'✅ کاربر جدید ساخته شد.', 'id'=>(int)$pdo->lastInsertId()];
}

/** ویرایش نام نمایشی/نام کاربری یک کاربر عادی. */
function adminUpdateUser(PDO $pdo, array $data): array {
    $id = (int)($data['id'] ?? 0);
    if ($id <= 0) return ['success'=>false, 'msg'=>'کاربر نامعتبر است'];
    $target = adminGetUser($pdo, $id);
    if (!$target) return ['success'=>false, 'msg'=>'کاربر یافت نشد'];

    $fields = []; $params = [];
    if (isset($data['username'])) {
        $username = adminValidateUsername($data['username']);
        if ($username === null) return ['success'=>false, 'msg'=>'نام کاربری نامعتبر است.'];
        $dup = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username=? AND id<>?');
        $dup->execute([$username, $id]);
        if ((int)$dup->fetchColumn() > 0) return ['success'=>false, 'msg'=>'این نام کاربری قبلاً ثبت شده است.'];
        $fields[] = 'username=?'; $params[] = $username;
    }
    if (isset($data['displayName'])) { $fields[] = 'display_name=?'; $params[] = trim((string)$data['displayName']); }
    if (!$fields) return ['success'=>false, 'msg'=>'تغییری اعمال نشد'];
    $params[] = $id;
    $pdo->prepare('UPDATE users SET ' . implode(', ', $fields) . ' WHERE id=?')->execute($params);
    return ['success'=>true, 'msg'=>'✅ اطلاعات کاربر بروزرسانی شد.'];
}

/** تنظیم رمز عبور یک کاربر توسط ادمین (کاربران خودشان نمی‌توانند). */
function adminSetPassword(PDO $pdo, int $id, string $password): array {
    if ($id <= 0) return ['success'=>false, 'msg'=>'کاربر نامعتبر است'];
    if (strlen($password) < 6) return ['success'=>false, 'msg'=>'رمز عبور باید حداقل ۶ نویسه باشد.'];
    $target = adminGetUser($pdo, $id);
    if (!$target) return ['success'=>false, 'msg'=>'کاربر یافت نشد'];
    $hash = password_hash($password, PASSWORD_BCRYPT);
    $pdo->prepare('UPDATE users SET password_hash=? WHERE id=?')->execute([$hash, $id]);
    return ['success'=>true, 'msg'=>'✅ رمز عبور کاربر تغییر کرد.'];
}

/** فعال/غیرفعال کردن کاربر (بدون حذف داده). */
function adminToggleActive(PDO $pdo, int $id): array {
    if ($id <= 0) return ['success'=>false, 'msg'=>'کاربر نامعتبر است'];
    $target = adminGetUser($pdo, $id);
    if (!$target) return ['success'=>false, 'msg'=>'کاربر یافت نشد'];
    // جلوگیری از غیرفعال کردن تنها ادمین فعال
    if ($target['role'] === 'admin') {
        $activeAdmins = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='admin' AND is_active=1")->fetchColumn();
        if ((int)$target['is_active'] === 1 && $activeAdmins <= 1) return ['success'=>false, 'msg'=>'حداقل یک ادمین فعال باید باقی بماند.'];
    }
    $new = (int)$target['is_active'] === 1 ? 0 : 1;
    $pdo->prepare('UPDATE users SET is_active=? WHERE id=?')->execute([$new, $id]);
    return ['success'=>true, 'msg'=>$new ? '✅ کاربر فعال شد.' : '⛔ کاربر غیرفعال شد.', 'isActive'=>(bool)$new];
}

/** باز/بسته کردن دسترسی کاربر به بخش «هوش‌های چندگانه گاردنر». */
function adminToggleMi(PDO $pdo, int $id): array {
    if ($id <= 0) return ['success'=>false, 'msg'=>'کاربر نامعتبر است'];
    $target = adminGetUser($pdo, $id);
    if (!$target) return ['success'=>false, 'msg'=>'کاربر یافت نشد'];
    $new = (int)($target['can_use_mi'] ?? 0) === 1 ? 0 : 1;
    $pdo->prepare('UPDATE users SET can_use_mi=? WHERE id=?')->execute([$new, $id]);
    return ['success'=>true, 'msg'=>$new ? '🧠 دسترسی هوش‌ها باز شد.' : '🔒 دسترسی هوش‌ها بسته شد.', 'canUseMi'=>(bool)$new];
}

/** تغییر نام کاربری/رمز خودِ ادمین (طبق درخواست: ادمین بعداً می‌تواند عوض کند). */
function adminUpdateSelf(PDO $pdo, array $data): array {
    $me = currentUser();
    if (!$me) return ['success'=>false, 'msg'=>'دسترسی مجاز نیست'];
    $id = (int)$me['id'];

    // برای تغییرات حساس، رمز فعلی لازم است
    $current = (string)($data['currentPassword'] ?? '');
    $row = adminGetUser($pdo, $id);
    if (!$row || !password_verify($current, $row['password_hash'])) {
        return ['success'=>false, 'msg'=>'رمز عبور فعلی نادرست است.'];
    }

    $fields = []; $params = [];
    if (!empty($data['username'])) {
        $username = adminValidateUsername($data['username']);
        if ($username === null) return ['success'=>false, 'msg'=>'نام کاربری نامعتبر است.'];
        $dup = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username=? AND id<>?');
        $dup->execute([$username, $id]);
        if ((int)$dup->fetchColumn() > 0) return ['success'=>false, 'msg'=>'این نام کاربری قبلاً ثبت شده است.'];
        $fields[] = 'username=?'; $params[] = $username;
    }
    if (!empty($data['newPassword'])) {
        if (strlen((string)$data['newPassword']) < 8) return ['success'=>false, 'msg'=>'رمز جدید ادمین باید حداقل ۸ نویسه باشد.'];
        $fields[] = 'password_hash=?'; $params[] = password_hash((string)$data['newPassword'], PASSWORD_BCRYPT);
    }
    if (!$fields) return ['success'=>false, 'msg'=>'تغییری وارد نشد'];
    $params[] = $id;
    $pdo->prepare('UPDATE users SET ' . implode(', ', $fields) . ' WHERE id=?')->execute($params);
    return ['success'=>true, 'msg'=>'✅ اطلاعات حساب شما بروزرسانی شد.'];
}

function adminGetUser(PDO $pdo, int $id): ?array {
    $st = $pdo->prepare('SELECT id, username, password_hash, display_name, role, is_active, can_use_mi FROM users WHERE id=?');
    $st->execute([$id]);
    $u = $st->fetch();
    return $u ?: null;
}
