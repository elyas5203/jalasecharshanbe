<?php
require_once __DIR__ . '/config.php';

const SCHEMA_VERSION = '9';

// شناسه رزرو‌شده برای تنظیمات سراسری (غیر وابسته به کاربر) مثل نسخه‌ی ساختار
const GLOBAL_OWNER = 0;

function db(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_PERSISTENT => true,
    ];
    try {
        $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        // فالبک: ساخت خودکار دیتابیس فقط روی محیط‌هایی که کاربر مجوز CREATE DATABASE دارد (مثل XAMPP لوکال).
        // روی هاست‌های اشتراکی cPanel این مجوز وجود ندارد؛ آنجا باید دیتابیس را دستی بسازی و config.php را درست کنی.
        try {
            $root = new PDO('mysql:host=' . DB_HOST . ';charset=utf8mb4', DB_USER, DB_PASS, $options);
            $root->exec('CREATE DATABASE IF NOT EXISTS `' . DB_NAME . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
            $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, $options);
        } catch (PDOException $e2) {
            http_response_code(500);
            header('Content-Type: text/html; charset=utf-8');
            die('<div style="max-width:640px;margin:60px auto;font-family:Tahoma,sans-serif;direction:rtl;background:#fff3f3;border:1px solid #f5b5b5;border-radius:14px;padding:24px;color:#7a1f2b;line-height:2">'
              . '<h2 style="margin:0 0 10px">❌ خطای اتصال به پایگاه‌داده</h2>'
              . 'اتصال به دیتابیس برقرار نشد. مقادیر فایل <b>config.php</b> باید با اطلاعات دیتابیس هاست هماهنگ باشد:'
              . '<ul><li>در cPanel یک MySQL Database بساز</li><li>یک MySQL User با رمز بساز و با «All Privileges» به آن دیتابیس اضافه کن</li>'
              . '<li>سپس در <b>config.php</b> مقادیر <code>DB_NAME</code>، <code>DB_USER</code>، <code>DB_PASS</code> را با نام کامل (شامل پیشوند اکانت مثل <code>kerayec_</code>) بگذار</li></ul>'
              . '</div>');
        }
    }
    if (!schemaIsCurrent($pdo)) initSchema($pdo);
    return $pdo;
}

function schemaIsCurrent(PDO $pdo): bool {
    try {
        $st = $pdo->prepare('SELECT `value` FROM settings WHERE `key`=? LIMIT 1');
        $st->execute(['SCHEMA_VERSION']);
        return (string)$st->fetchColumn() === SCHEMA_VERSION;
    } catch (Throwable $e) {
        return false;
    }
}

function ensureIndex(PDO $pdo, string $table, string $index, string $sql): void {
    try {
        $st = $pdo->prepare("SELECT TABLE_TYPE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?");
        $st->execute([$table]);
        $type = $st->fetchColumn();
        if ($type !== 'BASE TABLE') return;
        $st = $pdo->prepare('SHOW INDEX FROM `' . $table . '` WHERE Key_name=?');
        $st->execute([$index]);
        if (!$st->fetch()) $pdo->exec($sql);
    } catch (Throwable $e) {
        // نادیده گرفتن در صورت تکرار یا ساختارهای ناسازگار
    }
}

function initSchema(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS students (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(190) NOT NULL UNIQUE, absences INT NOT NULL DEFAULT 0, late_count INT NOT NULL DEFAULT 0, score DECIMAL(10,2) NOT NULL DEFAULT 0, status VARCHAR(100) NOT NULL DEFAULT 'ثبت نام', image TEXT NULL, manual_score DECIMAL(10,2) NOT NULL DEFAULT 0, bio TEXT NULL, phones JSON NULL, parent_phone VARCHAR(100) NULL, dob VARCHAR(50) NULL, school VARCHAR(190) NULL, medical TEXT NULL, parent_note TEXT NULL, late_minutes INT NOT NULL DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("CREATE TABLE IF NOT EXISTS attendance_sessions (id INT AUTO_INCREMENT PRIMARY KEY, session_date VARCHAR(20) NOT NULL UNIQUE, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("CREATE TABLE IF NOT EXISTS attendance_records (id INT AUTO_INCREMENT PRIMARY KEY, session_id INT NOT NULL, student_id INT NOT NULL, status VARCHAR(20) NOT NULL, late_minutes INT NOT NULL DEFAULT 0, absence_reason VARCHAR(255) NULL, status_text VARCHAR(100) NOT NULL, UNIQUE KEY uniq_att (session_id, student_id), CONSTRAINT fk_att_session FOREIGN KEY (session_id) REFERENCES attendance_sessions(id) ON DELETE CASCADE, CONSTRAINT fk_att_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("CREATE TABLE IF NOT EXISTS settings (`key` VARCHAR(100) PRIMARY KEY, `value` TEXT NULL, description TEXT NULL) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("CREATE TABLE IF NOT EXISTS notes (id INT AUTO_INCREMENT PRIMARY KEY, note_date VARCHAR(20) NOT NULL, note_text TEXT NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("CREATE TABLE IF NOT EXISTS plans (id VARCHAR(40) PRIMARY KEY, plan_date VARCHAR(20) NOT NULL, title VARCHAR(255) NOT NULL, priority VARCHAR(20) NOT NULL DEFAULT 'low', modules JSON NULL, sin TEXT NULL, status VARCHAR(50) NOT NULL DEFAULT 'Active', updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("CREATE TABLE IF NOT EXISTS essentials (id INT AUTO_INCREMENT PRIMARY KEY, plan_id VARCHAR(40) NOT NULL, item_date VARCHAR(20), title VARCHAR(255), priority VARCHAR(20), item_name VARCHAR(255), uid VARCHAR(50) NOT NULL, done TINYINT(1) NOT NULL DEFAULT 0, item_type VARCHAR(20), INDEX(plan_id), UNIQUE KEY uniq_uid (uid)) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("CREATE TABLE IF NOT EXISTS mi_config (id INT AUTO_INCREMENT PRIMARY KEY, type VARCHAR(190) NOT NULL UNIQUE, scientific_desc TEXT, positive_traits TEXT, negative_traits TEXT) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("CREATE TABLE IF NOT EXISTS mi_logs (id INT AUTO_INCREMENT PRIMARY KEY, log_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, student_id INT NOT NULL, mi_type VARCHAR(190) NOT NULL, behavior TEXT NOT NULL, score INT NOT NULL, INDEX(student_id), CONSTRAINT fk_mi_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("CREATE TABLE IF NOT EXISTS archive_logs (id INT AUTO_INCREMENT PRIMARY KEY, log_date VARCHAR(20) NOT NULL, log_type VARCHAR(50) NOT NULL, title VARCHAR(255) NOT NULL, details_json TEXT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("CREATE TABLE IF NOT EXISTS plan_files (id INT AUTO_INCREMENT PRIMARY KEY, plan_id VARCHAR(40) NOT NULL, original_name VARCHAR(255) NOT NULL, stored_path TEXT NOT NULL, mime_type VARCHAR(120) NULL, file_size INT NOT NULL DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX(plan_id)) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("CREATE TABLE IF NOT EXISTS telegram_queue (id INT AUTO_INCREMENT PRIMARY KEY, payload JSON NOT NULL, status VARCHAR(20) NOT NULL DEFAULT 'pending', attempts INT NOT NULL DEFAULT 0, last_error TEXT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, sent_at DATETIME NULL) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    // ردگیری هشدارهایی که به تلگرام فرستاده شده‌اند تا هر هشدار فقط یک‌بار ارسال شود (ضدتکرار)
    $pdo->exec("CREATE TABLE IF NOT EXISTS telegram_sent_alerts (alert_id VARCHAR(190) NOT NULL, owner_id INT NOT NULL DEFAULT 0, sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (owner_id, alert_id)) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    // جدول کاربران و لایه‌ی چندمستأجری (احراز هویت + جداسازی داده‌ی جلسات)
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (id INT AUTO_INCREMENT PRIMARY KEY, username VARCHAR(100) NOT NULL UNIQUE, password_hash VARCHAR(255) NOT NULL, display_name VARCHAR(190) NULL, role VARCHAR(20) NOT NULL DEFAULT 'user', is_active TINYINT(1) NOT NULL DEFAULT 1, must_change_password TINYINT(1) NOT NULL DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    migrateTenancy($pdo);
    ensureColumn($pdo, 'attendance_records', 'absence_reason', 'VARCHAR(255) NULL');
    ensureIndex($pdo, 'attendance_records', 'idx_att_student_status', 'CREATE INDEX idx_att_student_status ON attendance_records (student_id, status)');
    ensureIndex($pdo, 'attendance_records', 'idx_att_session_status', 'CREATE INDEX idx_att_session_status ON attendance_records (session_id, status)');
    ensureIndex($pdo, 'attendance_sessions', 'idx_att_session_date_id', 'CREATE INDEX idx_att_session_date_id ON attendance_sessions (session_date, id)');
    ensureIndex($pdo, 'plans', 'idx_plans_date_updated', 'CREATE INDEX idx_plans_date_updated ON plans (plan_date, updated_at)');
    ensureIndex($pdo, 'archive_logs', 'idx_archive_date_type', 'CREATE INDEX idx_archive_date_type ON archive_logs (log_date, log_type)');
    ensureIndex($pdo, 'mi_logs', 'idx_mi_student_date', 'CREATE INDEX idx_mi_student_date ON mi_logs (student_id, log_date)');
    ensureIndex($pdo, 'plan_files', 'idx_plan_files_plan_id_id', 'CREATE INDEX idx_plan_files_plan_id_id ON plan_files (plan_id, id)');
    seedDefaults($pdo);
    migrateMISystem($pdo);
    $ver = $pdo->prepare('INSERT INTO settings (`key`, `value`, description) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)');
    $ver->execute(['SCHEMA_VERSION', SCHEMA_VERSION, 'نسخه ساختار دیتابیس']);
}

/**
 * افزودن owner_id به جدول در صورت نبود، به‌صورت idempotent.
 */
function ensureColumn(PDO $pdo, string $table, string $column, string $definition): void {
    $st = $pdo->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?');
    $st->execute([$table, $column]);
    if (!(int)$st->fetchColumn()) {
        $pdo->exec("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
    }
}

/** حذف یک ایندکس/کلید در صورت وجود (برای بازچینش UNIQUEها). */
function dropIndexIfExists(PDO $pdo, string $table, string $index): void {
    $st = $pdo->prepare('SHOW INDEX FROM `' . $table . '` WHERE Key_name=?');
    $st->execute([$index]);
    if ($st->fetch()) $pdo->exec("ALTER TABLE `$table` DROP INDEX `$index`");
}

/**
 * مایگریشن چندمستأجری — کاملاً افزایشی و idempotent.
 * ۱) به جدول‌های ریشه‌ای owner_id اضافه می‌کند.
 * ۲) UNIQUEهای سراسری را به UNIQUE ترکیبی با owner_id تبدیل می‌کند.
 * ۳) کاربر اول (superadmin) را می‌سازد و داده‌ی موجود را به یک «کاربر مالک اول» منتقل می‌کند.
 * هیچ داده‌ای حذف یا بازنویسی نمی‌شود.
 */
function migrateTenancy(PDO $pdo): void {
    // ۱) ستون owner_id روی جدول‌های ریشه‌ای (فرزندان از طریق والد اسکوپ می‌شوند)
    ensureColumn($pdo, 'students',            'owner_id', 'INT NOT NULL DEFAULT 0');
    ensureColumn($pdo, 'attendance_sessions', 'owner_id', 'INT NOT NULL DEFAULT 0');
    ensureColumn($pdo, 'notes',               'owner_id', 'INT NOT NULL DEFAULT 0');
    ensureColumn($pdo, 'plans',               'owner_id', 'INT NOT NULL DEFAULT 0');
    ensureColumn($pdo, 'archive_logs',        'owner_id', 'INT NOT NULL DEFAULT 0');
    // آیدی عددی تلگرام والدین برای اعلان غیبت (اختیاری، هر متربی می‌تواند خالی باشد)
    ensureColumn($pdo, 'students',            'parent_chat_id', 'VARCHAR(40) NULL');
    // دسترسی به بخش «هوش‌های چندگانه گاردنر» — پیش‌فرض بسته؛ مدیر برای هر کاربر باز می‌کند
    ensureColumn($pdo, 'users',               'can_use_mi', 'TINYINT(1) NOT NULL DEFAULT 0');

    // ۲) بازچینش constraintهای UNIQUE سراسری → ترکیبی با owner_id
    dropIndexIfExists($pdo, 'students', 'name');            // UNIQUE(name)
    ensureIndex($pdo, 'students', 'uniq_owner_name', 'CREATE UNIQUE INDEX uniq_owner_name ON students (owner_id, name)');
    dropIndexIfExists($pdo, 'attendance_sessions', 'session_date'); // UNIQUE(session_date)
    ensureIndex($pdo, 'attendance_sessions', 'uniq_owner_date', 'CREATE UNIQUE INDEX uniq_owner_date ON attendance_sessions (owner_id, session_date)');
    ensureIndex($pdo, 'students', 'idx_students_owner', 'CREATE INDEX idx_students_owner ON students (owner_id)');
    ensureIndex($pdo, 'attendance_sessions', 'idx_sessions_owner', 'CREATE INDEX idx_sessions_owner ON attendance_sessions (owner_id)');
    ensureIndex($pdo, 'notes', 'idx_notes_owner', 'CREATE INDEX idx_notes_owner ON notes (owner_id)');
    ensureIndex($pdo, 'plans', 'idx_plans_owner', 'CREATE INDEX idx_plans_owner ON plans (owner_id)');
    ensureIndex($pdo, 'archive_logs', 'idx_archive_owner', 'CREATE INDEX idx_archive_owner ON archive_logs (owner_id)');

    // ۳) ادمین ثابت superadmin (طبق درخواست کاربر). رمز اولیه، قابل تغییر بعدی.
    $hasAdmin = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='admin'")->fetchColumn();
    if (!$hasAdmin) {
        $hash = password_hash('Admin_110_59', PASSWORD_BCRYPT);
        $pdo->prepare("INSERT INTO users (username, password_hash, display_name, role) VALUES (?, ?, ?, 'admin')")
            ->execute(['superadmin', $hash, 'مدیر سیستم']);
    }

    // ۴) انتقال داده‌ی موجود (owner_id=0) به «کاربر مالک اول».
    $orphan = (int)$pdo->query('SELECT COUNT(*) FROM students WHERE owner_id=0')->fetchColumn();
    if ($orphan > 0) {
        // کاربر عادی اولی که داده‌ی فعلی به او تعلق می‌گیرد
        $firstUser = $pdo->query("SELECT id FROM users WHERE role='user' ORDER BY id ASC LIMIT 1")->fetchColumn();
        if (!$firstUser) {
            // رمز اولیه‌ی معلوم تا مالک بتواند فوراً وارد شود؛ ادمین بعداً تغییرش می‌دهد.
            $hash = password_hash('User_110_59', PASSWORD_BCRYPT);
            $pdo->prepare("INSERT INTO users (username, password_hash, display_name, role) VALUES (?, ?, ?, 'user')")
                ->execute(['user1', $hash, 'جلسه‌ی اول']);
            $firstUser = (int)$pdo->lastInsertId();
        }
        $firstUser = (int)$firstUser;
        foreach (['students','attendance_sessions','notes','plans','archive_logs'] as $t) {
            $pdo->prepare("UPDATE `$t` SET owner_id=? WHERE owner_id=0")->execute([$firstUser]);
        }
    }
}

/**
 * مایگریشن سیستم هوش‌های چندگانه — از ساختار ساده -1/0/1 به ساختار چندمنبعی 0-4
 * این مایگریشن کاملاً data-preserving است و هیچ داده‌ای را حذف نمی‌کند.
 */
function migrateMISystem(PDO $pdo): void {
    // بررسی: آیا migration قبلاً انجام شده؟
    $st = $pdo->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?');
    $st->execute(['mi_logs_legacy']);
    if ((int)$st->fetchColumn() > 0) {
        // Migration قبلاً انجام شده
        return;
    }

    // ثبت تعداد ردیف‌های قبل از migration
    $legacyConfigCount = (int)$pdo->query('SELECT COUNT(*) FROM mi_config')->fetchColumn();
    $legacyLogsCount = (int)$pdo->query('SELECT COUNT(*) FROM mi_logs')->fetchColumn();

    error_log("MI Migration: Starting with mi_config={$legacyConfigCount} rows, mi_logs={$legacyLogsCount} rows");

    // STEP 1: Rename legacy tables
    $pdo->exec('RENAME TABLE mi_config TO mi_config_legacy');
    $pdo->exec('RENAME TABLE mi_logs TO mi_logs_legacy');

    // STEP 2: Create new schema

    // intelligence_domains — حوزه‌های هوش
    $pdo->exec("CREATE TABLE intelligence_domains (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        slug VARCHAR(50) NOT NULL UNIQUE,
        name_fa VARCHAR(190) NOT NULL,
        icon VARCHAR(10) DEFAULT '',
        scientific_desc TEXT NULL,
        display_order INT NOT NULL DEFAULT 0,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_active_order (is_active, display_order)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // behavioral_indicators — شاخص‌های رفتاری
    $pdo->exec("CREATE TABLE behavioral_indicators (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        domain_id INT UNSIGNED NOT NULL,
        indicator_text TEXT NOT NULL,
        indicator_category ENUM('positive', 'negative', 'neutral') DEFAULT 'positive',
        explanation TEXT NULL,
        display_order INT NOT NULL DEFAULT 0,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (domain_id) REFERENCES intelligence_domains(id) ON DELETE CASCADE,
        INDEX idx_domain_active (domain_id, is_active),
        INDEX idx_domain_order (domain_id, display_order)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // observation_sources — منابع مشاهده
    $pdo->exec("CREATE TABLE observation_sources (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        slug VARCHAR(50) NOT NULL UNIQUE,
        name_fa VARCHAR(100) NOT NULL,
        description TEXT NULL,
        display_order INT NOT NULL DEFAULT 0,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // observation_contexts — محیط‌های مشاهده
    $pdo->exec("CREATE TABLE observation_contexts (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        slug VARCHAR(50) NOT NULL UNIQUE,
        name_fa VARCHAR(100) NOT NULL,
        description TEXT NULL,
        display_order INT NOT NULL DEFAULT 0,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // intelligence_observations — مشاهدات جدید
    $pdo->exec("CREATE TABLE intelligence_observations (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        owner_id INT NOT NULL DEFAULT 0,
        student_id INT NOT NULL,
        indicator_id INT UNSIGNED NOT NULL,
        source_id INT UNSIGNED NOT NULL,
        context_id INT UNSIGNED NULL,
        score TINYINT UNSIGNED NULL,
        no_opportunity TINYINT(1) NOT NULL DEFAULT 0,
        evidence_text TEXT NULL,
        session_id INT NULL,
        observed_at DATE NOT NULL,
        recorded_by INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
        FOREIGN KEY (indicator_id) REFERENCES behavioral_indicators(id) ON DELETE RESTRICT,
        FOREIGN KEY (source_id) REFERENCES observation_sources(id) ON DELETE RESTRICT,
        FOREIGN KEY (context_id) REFERENCES observation_contexts(id) ON DELETE SET NULL,
        FOREIGN KEY (session_id) REFERENCES attendance_sessions(id) ON DELETE SET NULL,
        FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE SET NULL,
        INDEX idx_owner_student (owner_id, student_id),
        INDEX idx_student_indicator (student_id, indicator_id),
        INDEX idx_student_source (student_id, source_id),
        INDEX idx_observed_at (observed_at),
        INDEX idx_source_date (source_id, observed_at),
        CONSTRAINT chk_score_or_no_opportunity CHECK (
            (score IS NULL AND no_opportunity = 1) OR
            (score IS NOT NULL AND no_opportunity = 0 AND score BETWEEN 0 AND 4)
        )
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // STEP 3: Seed new tables

    // Seed sources
    $pdo->exec("INSERT INTO observation_sources (slug, name_fa, display_order, is_active) VALUES
        ('parent', 'والدین', 1, 1),
        ('teacher', 'مربی', 2, 1),
        ('student', 'خود دانش‌آموز', 3, 1),
        ('legacy', 'مشاهدات قبلی (بدون تفکیک منبع)', 999, 0)");

    // Seed contexts
    $pdo->exec("INSERT INTO observation_contexts (slug, name_fa, display_order) VALUES
        ('home', 'خانه', 1),
        ('class', 'کلاس', 2),
        ('group', 'فعالیت گروهی', 3),
        ('individual', 'فعالیت انفرادی', 4),
        ('other', 'سایر', 99)");

    // STEP 4: Migrate mi_config_legacy → intelligence_domains
    $legacyDomains = $pdo->query('SELECT * FROM mi_config_legacy ORDER BY id')->fetchAll();
    $domainMap = []; // legacy type => new domain_id

    foreach ($legacyDomains as $idx => $domain) {
        // استخراج slug از type (حذف emoji و فاصله‌های اضافی)
        $type = $domain['type'];
        $slug = preg_replace('/[\x{1F300}-\x{1F9FF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}]/u', '', $type);
        $slug = trim($slug);
        $slug = str_replace([' ', '‌'], '-', $slug); // فاصله و نیم‌فاصله به خط تیره
        $slug = strtolower($slug);
        $slug = preg_replace('/[^a-z0-9\-\_آ-ی]/u', '', $slug); // حذف کاراکترهای نامعتبر
        if (empty($slug)) $slug = 'domain-' . ($idx + 1);

        // استخراج emoji
        preg_match('/[\x{1F300}-\x{1F9FF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}]/u', $type, $matches);
        $icon = $matches[0] ?? '';

        $ins = $pdo->prepare("INSERT INTO intelligence_domains (slug, name_fa, icon, scientific_desc, display_order, is_active)
                              VALUES (?, ?, ?, ?, ?, 1)");
        $ins->execute([$slug, $type, $icon, $domain['scientific_desc'], $idx]);
        $newDomainId = (int)$pdo->lastInsertId();
        $domainMap[$type] = $newDomainId;

        // Migrate indicators
        $positiveTraits = $domain['positive_traits'] ? array_filter(array_map('trim', explode(',', $domain['positive_traits']))) : [];
        $negativeTraits = $domain['negative_traits'] ? array_filter(array_map('trim', explode(',', $domain['negative_traits']))) : [];

        $order = 0;
        foreach ($positiveTraits as $trait) {
            if (empty($trait)) continue;
            // تفکیک عنوان و توضیح
            $text = $trait;
            $explanation = null;
            if (strpos($trait, '(') !== false) {
                preg_match('/^(.+?)\s*\((.+?)\)\s*$/', $trait, $parts);
                if ($parts) {
                    $text = trim($parts[1]);
                    $explanation = trim($parts[2]);
                }
            }
            $pdo->prepare("INSERT INTO behavioral_indicators (domain_id, indicator_text, indicator_category, explanation, display_order, is_active)
                           VALUES (?, ?, 'positive', ?, ?, 1)")
                ->execute([$newDomainId, $text, $explanation, $order++]);
        }

        foreach ($negativeTraits as $trait) {
            if (empty($trait)) continue;
            $text = $trait;
            $explanation = null;
            if (strpos($trait, '(') !== false) {
                preg_match('/^(.+?)\s*\((.+?)\)\s*$/', $trait, $parts);
                if ($parts) {
                    $text = trim($parts[1]);
                    $explanation = trim($parts[2]);
                }
            }
            $pdo->prepare("INSERT INTO behavioral_indicators (domain_id, indicator_text, indicator_category, explanation, display_order, is_active)
                           VALUES (?, ?, 'negative', ?, ?, 1)")
                ->execute([$newDomainId, $text, $explanation, $order++]);
        }
    }

    // STEP 5: Create compatibility VIEWs (read-only guidance)
    $pdo->exec("CREATE VIEW mi_config AS
                SELECT id, type, scientific_desc, positive_traits, negative_traits
                FROM mi_config_legacy");

    $pdo->exec("CREATE VIEW mi_logs AS
                SELECT id, log_date, student_id, mi_type, behavior, score
                FROM mi_logs_legacy");

    // STEP 6: Verification
    $newDomainsCount = (int)$pdo->query('SELECT COUNT(*) FROM intelligence_domains')->fetchColumn();
    $newIndicatorsCount = (int)$pdo->query('SELECT COUNT(*) FROM behavioral_indicators')->fetchColumn();
    $legacyConfigPreserved = (int)$pdo->query('SELECT COUNT(*) FROM mi_config_legacy')->fetchColumn();
    $legacyLogsPreserved = (int)$pdo->query('SELECT COUNT(*) FROM mi_logs_legacy')->fetchColumn();

    error_log("MI Migration: COMPLETE");
    error_log("  - Legacy mi_config preserved: {$legacyConfigPreserved} rows (expected {$legacyConfigCount})");
    error_log("  - Legacy mi_logs preserved: {$legacyLogsPreserved} rows (expected {$legacyLogsCount})");
    error_log("  - New domains created: {$newDomainsCount}");
    error_log("  - New indicators created: {$newIndicatorsCount}");

    if ($legacyConfigPreserved !== $legacyConfigCount) {
        throw new Exception("MI Migration FAILED: mi_config_legacy count mismatch");
    }
    if ($legacyLogsPreserved !== $legacyLogsCount) {
        throw new Exception("MI Migration FAILED: mi_logs_legacy count mismatch");
    }
}

function seedDefaults(PDO $pdo): void {
    $stmt = $pdo->prepare('INSERT IGNORE INTO settings (`key`, `value`, description) VALUES (?, ?, ?)');
    $stmt->execute(['BOT_TOKEN', '', 'توکن ربات تلگرام']);
    $stmt->execute(['ADMIN_CHAT_IDS', '', 'آیدی مدیران تلگرام']);
    $stmt->execute(['TG_NOTIFY_SCORE', '1', 'ارسال اعلان امتیاز']);
    $stmt->execute(['TG_NOTIFY_ATTENDANCE', '1', 'ارسال اعلان حضور و غیاب']);
    $stmt->execute(['TG_NOTIFY_PLAN', '1', 'ارسال اعلان طرح درس جدید']);
    $stmt->execute(['TG_NOTIFY_NOTE', '0', 'ارسال اعلان یادداشت جدید']);
    $stmt->execute(['TG_NOTIFY_ALERTS', '1', 'ارسال خودکار هشدارها به تلگرام']);
    $stmt->execute(['TG_NOTIFY_PARENTS', '0', 'اعلان غیبت به والدین']);
    $stmt->execute(['SITE_URL', '', 'آدرس اینترنتی سایت برای دکمه‌های تلگرام']);
    $stmt->execute(['CRON_KEY', bin2hex(random_bytes(12)), 'کلید امنیتی اجرای cron گزارش دوره‌ای']);
    if ((int)$pdo->query('SELECT COUNT(*) FROM mi_config')->fetchColumn() === 0) {
        $rows = [
            ['زبانی-کلامی 🗣️','توانایی درک و تولید زبان.','استفاده از واژگان غنی,بیان شیوا','قطع کردن حرف دیگران'],
            ['منطقی-ریاضی 🔢','تحلیل مسائل و تفکر علمی.','حل معما,استدلال قوی','بی‌نظمی در استدلال'],
            ['تصویری-فضایی 🎨','تجسم فضایی.','نقاشی خوب,تصویرسازی','گم کردن مسیرها'],
            ['بدنی-جنبشی ⚽','استفاده از بدن.','مهارت ورزش,زبان بدن','بی‌قراری'],
            ['موسیقایی 🎵','حساسیت به ریتم.','تشخیص ریتم,زمزمه','بی‌توجهی به صداها'],
            ['میان‌فردی 🤝','درک دیگران.','رهبری گروه,همدلی','پرخاشگری,انزوا'],
            ['درون‌فردی 🧘','شناخت خود.','داشتن هدف,کنترل خشم','عدم شناخت احساس'],
            ['طبیعت‌گرا 🌿','درک طبیعت.','علاقه به حیوانات,مشاهده محیط','آسیب به طبیعت'],
        ];
        $ins = $pdo->prepare('INSERT INTO mi_config (type, scientific_desc, positive_traits, negative_traits) VALUES (?, ?, ?, ?)');
        foreach ($rows as $r) $ins->execute($r);
    }
}

function jsonResponse($data): never { header('Content-Type: application/json; charset=utf-8'); echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); exit; }
function bodyArgs(): array { $raw = file_get_contents('php://input'); $payload = $raw ? json_decode($raw, true) : null; return is_array($payload) ? ($payload['args'] ?? []) : []; }
function toEnglishDigits($s): string { return strtr((string)$s, ['۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9','٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9']); }
function normalizeDateStr($d): string { if (!$d) return ''; $s = toEnglishDigits((string)$d); $p = explode('/', $s); if (count($p) !== 3) return $s; return $p[0] . '/' . str_pad($p[1], 2, '0', STR_PAD_LEFT) . '/' . str_pad($p[2], 2, '0', STR_PAD_LEFT); }
function fixUrl($u): string {
    $u = trim((string)$u);
    if ($u === '') return DEFAULT_IMG;
    if (preg_match('~^(data:image/|assets/|uploads/|/|\./|\../)~', $u)) return $u;
    // The app must work without Google/CDN access; external images are kept in DB but not loaded in the UI.
    if (preg_match('~^https?://~i', $u)) return DEFAULT_IMG;
    return $u;
}
function lateScore(int $minutes): float {
    if ($minutes <= 5) return 0.95;
    if ($minutes <= 10) return 0.85;
    if ($minutes <= 15) return 0.70;
    if ($minutes <= 20) return 0.55;
    if ($minutes <= 30) return 0.40;
    return 0.25;
}
function updateCalculations(PDO $pdo): void {
    $pdo->exec("UPDATE students s
        LEFT JOIN (
            SELECT student_id,
                   SUM(CASE WHEN status='Absent' THEN 1 ELSE 0 END) absences,
                   SUM(CASE WHEN status='Late' THEN 1 ELSE 0 END) late_count,
                   SUM(CASE WHEN status='Late' THEN late_minutes ELSE 0 END) late_minutes,
                   SUM(CASE
                       WHEN status='Absent' THEN -1
                       WHEN status='Present' THEN 1
                       WHEN status='Excused' THEN 0.5
                       WHEN status='Late' THEN
                           CASE
                               WHEN late_minutes <= 5 THEN 0.95
                               WHEN late_minutes <= 10 THEN 0.85
                               WHEN late_minutes <= 15 THEN 0.70
                               WHEN late_minutes <= 20 THEN 0.55
                               WHEN late_minutes <= 30 THEN 0.40
                               ELSE 0.25
                           END
                       ELSE 0
                   END) activity_score
            FROM attendance_records
            GROUP BY student_id
        ) a ON a.student_id=s.id
        SET s.absences=COALESCE(a.absences,0),
            s.late_count=COALESCE(a.late_count,0),
            s.late_minutes=COALESCE(a.late_minutes,0),
            s.score=s.manual_score+COALESCE(a.activity_score,0)");
}
function settings(PDO $pdo): array { $out = []; foreach ($pdo->query('SELECT `key`, `value` FROM settings') as $r) $out[$r['key']] = $r['value']; return $out; }
/**
 * تماس سطح‌پایین با Bot API تلگرام از طریق cURL با تایم‌اوت محدود.
 * چرا cURL؟ برخلاف file_get_contents، هم وقتی allow_url_fopen خاموش باشد کار می‌کند،
 * هم connect-timeout جدا دارد تا صفحه هرگز روی هاست کند/غیرقابل‌دسترس تا ۶۰ ثانیه هنگ نکند.
 * خروجی: ['ok'=>bool, 'result'=>mixed, 'description'=>string, 'error_code'=>int, 'http'=>int, 'transport'=>string]
 */
function telegramApi(string $token, string $method, array $params = [], ?string $proxy = null): array {
    $url  = 'https://api.telegram.org/bot' . $token . '/' . $method;
    $body = json_encode($params, JSON_UNESCAPED_UNICODE);

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_CONNECTTIMEOUT => 5,   // اگر ظرف ۵ ثانیه وصل نشد، سریع شکست بخور
            CURLOPT_TIMEOUT        => 12,  // سقف کل زمان درخواست
        ]);
        if ($proxy) curl_setopt($ch, CURLOPT_PROXY, $proxy);
        $res  = curl_exec($ch);
        if ($res === false) {
            $err = 'cURL ' . curl_errno($ch) . ': ' . curl_error($ch);
            curl_close($ch);
            return ['ok'=>false, 'result'=>null, 'description'=>'ارتباط با تلگرام برقرار نشد ('.$err.')', 'error_code'=>0, 'http'=>0, 'transport'=>$err];
        }
        $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
    } else {
        // فالبک: file_get_contents با تایم‌اوت محدود و خواندن بدنه خطا
        $ctx = stream_context_create(['http'=>['method'=>'POST','header'=>'Content-Type: application/json','content'=>$body,'timeout'=>12,'ignore_errors'=>true]]);
        $res = @file_get_contents($url, false, $ctx);
        $http = 0;
        if ($res === false) return ['ok'=>false, 'result'=>null, 'description'=>'ارتباط با تلگرام برقرار نشد (allow_url_fopen)', 'error_code'=>0, 'http'=>0, 'transport'=>'file_get_contents failed'];
    }

    $json = json_decode($res, true);
    if (!is_array($json)) return ['ok'=>false, 'result'=>null, 'description'=>'پاسخ نامعتبر از تلگرام', 'error_code'=>0, 'http'=>$http, 'transport'=>'invalid json'];
    return [
        'ok'          => !empty($json['ok']),
        'result'      => $json['result'] ?? null,
        'description' => (string)($json['description'] ?? ''),
        'error_code'  => (int)($json['error_code'] ?? $http),
        'http'        => $http,
        'transport'   => '',
    ];
}
function sendTelegramMessage(string $text, ?array $markup = null, bool $silent = false): void {
    $pdo = db();
    $s = settings($pdo);
    $token = trim($s['BOT_TOKEN'] ?? '');
    $proxy = trim($s['TG_PROXY'] ?? '') ?: null;
    $chats = array_filter(array_map('trim', explode(',', $s['ADMIN_CHAT_IDS'] ?? '')));
    if ($token === '' || !$chats) return;
    $anyOk = false;
    foreach ($chats as $chat) {
        $payload = ['chat_id'=>$chat, 'text'=>$text, 'parse_mode'=>'HTML', 'disable_web_page_preview'=>true];
        if ($silent) $payload['disable_notification'] = true;
        if ($markup) $payload['reply_markup'] = $markup;
        $ok = sendTelegramPayload($token, $payload, $err, $proxy);
        if ($ok) $anyOk = true;
        else queueTelegramPayload($pdo, $payload, $err); // ناموفق‌ها در صف می‌مانند تا بعداً ارسال شوند
    }
    // شبکه همین الان سالم بوده؛ فرصت را غنیمت بشمار و چند پیام معطلِ صف را هم خالی کن
    if ($anyOk) autoFlushQueue($pdo, $token, $proxy);
}
/** ارسال پیام به یک چت مشخص (مثلاً والدین یک متربی) — ناموفق‌ها به صف می‌روند. */
function sendTelegramTo(string $chatId, string $text, bool $silent = false): void {
    $pdo = db();
    $s = settings($pdo);
    $token = trim($s['BOT_TOKEN'] ?? '');
    $proxy = trim($s['TG_PROXY'] ?? '') ?: null;
    $chatId = trim($chatId);
    if ($token === '' || $chatId === '') return;
    $payload = ['chat_id'=>$chatId, 'text'=>$text, 'parse_mode'=>'HTML', 'disable_web_page_preview'=>true];
    if ($silent) $payload['disable_notification'] = true;
    if (!sendTelegramPayload($token, $payload, $err, $proxy)) queueTelegramPayload($pdo, $payload, $err);
}
/** خالی‌کردنِ خودکار و بی‌سروصدای چند پیام معطلِ صف وقتی می‌دانیم شبکه سالم است. */
function autoFlushQueue(PDO $pdo, string $token, ?string $proxy = null, int $limit = 5): void {
    if ($token === '') return;
    $rows = $pdo->query("SELECT * FROM telegram_queue WHERE status='pending' AND attempts < 8 ORDER BY id ASC LIMIT " . (int)$limit)->fetchAll();
    foreach ($rows as $row) {
        $payload = json_decode($row['payload'], true) ?: [];
        if (sendTelegramPayload($token, $payload, $err, $proxy)) {
            $pdo->prepare("UPDATE telegram_queue SET status='sent', sent_at=NOW(), attempts=attempts+1 WHERE id=?")->execute([$row['id']]);
        } else {
            // اگر یکی شکست خورد یعنی شبکه دوباره مشکل دارد؛ بقیه را رها کن تا دفعه بعد
            $pdo->prepare('UPDATE telegram_queue SET attempts=attempts+1, last_error=? WHERE id=?')->execute([$err, $row['id']]);
            break;
        }
    }
}
function sendTelegramPayload(string $token, array $payload, ?string &$error = null, ?string $proxy = null): bool {
    $r = telegramApi($token, 'sendMessage', $payload, $proxy);
    if (!$r['ok']) { $error = $r['description'] ?: ($r['transport'] ?: 'ارسال ناموفق'); return false; }
    return true;
}
function queueTelegramPayload(PDO $pdo, array $payload, string $error = ''): void {
    $st = $pdo->prepare('INSERT INTO telegram_queue (payload, last_error) VALUES (?, ?)');
    $st->execute([json_encode($payload, JSON_UNESCAPED_UNICODE), $error]);
}
function flushTelegramQueue(PDO $pdo): array {
    $s = settings($pdo); $token = trim($s['BOT_TOKEN'] ?? ''); $proxy = trim($s['TG_PROXY'] ?? '') ?: null;
    if ($token === '') return ['success'=>false, 'msg'=>'❌ توکن تلگرام تنظیم نشده است'];
    $rows = $pdo->query("SELECT * FROM telegram_queue WHERE status='pending' AND attempts < 8 ORDER BY id ASC LIMIT 50")->fetchAll();
    if (!$rows) return ['success'=>true, 'msg'=>'✅ صف خالی است؛ پیام معطلی برای ارسال نیست.'];
    $sent = 0; $failed = 0; $lastErr = '';
    foreach ($rows as $row) {
        $payload = json_decode($row['payload'], true) ?: [];
        $ok = sendTelegramPayload($token, $payload, $err, $proxy);
        if ($ok) { $sent++; $pdo->prepare("UPDATE telegram_queue SET status='sent', sent_at=NOW(), attempts=attempts+1 WHERE id=?")->execute([$row['id']]); }
        else { $failed++; $lastErr = $err; $pdo->prepare('UPDATE telegram_queue SET attempts=attempts+1, last_error=? WHERE id=?')->execute([$err, $row['id']]); }
    }
    $msg = '📨 صف تلگرام: ' . $sent . ' ارسال شد' . ($failed ? '، ' . $failed . ' ناموفق ماند' : '') . '.';
    if ($failed && $lastErr) $msg .= "\nعلت آخرین خطا: " . $lastErr;
    return ['success'=>$failed===0, 'msg'=>$msg];
}
function notifySimple(string $title, string $icon, array $lines, ?array $markup = null, bool $silent = false): void { $text = '<b>' . htmlspecialchars($icon . ' ' . $title, ENT_QUOTES, 'UTF-8') . '</b>' . "\n<b>────────────────</b>\n" . implode("\n", $lines); sendTelegramMessage($text, $markup, $silent); }
/** دکمه‌ی لینک به پروفایل متربی در سایت — فقط اگر SITE_URL تنظیم شده باشد. */
function studentProfileButton(PDO $pdo, string $name): ?array {
    $base = rtrim(trim(settings($pdo)['SITE_URL'] ?? ''), '/');
    if ($base === '') return null;
    return ['inline_keyboard'=>[[['text'=>'👤 مشاهده پروفایل', 'url'=>$base . '/index.php#profile=' . rawurlencode($name)]]]];
}
function settingEnabled(PDO $pdo, string $key, bool $default = true): bool {
    $s = settings($pdo);
    return isset($s[$key]) ? in_array((string)$s[$key], ['1','true','on','yes'], true) : $default;
}
