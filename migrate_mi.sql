-- MI System Migration SQL
-- This script migrates from simple -1/0/1 system to multi-source 0-4 system
-- All legacy data is preserved

USE charshanbe_jalase;

-- Check if migration already done
SET @legacy_exists = (SELECT COUNT(*) FROM information_schema.TABLES
                      WHERE TABLE_SCHEMA='charshanbe_jalase' AND TABLE_NAME='mi_logs_legacy');

-- Only proceed if not migrated
-- Note: This will be wrapped in application logic, but for manual execution we proceed

-- STEP 1: Rename legacy tables
RENAME TABLE mi_config TO mi_config_legacy;
RENAME TABLE mi_logs TO mi_logs_legacy;

-- STEP 2: Create new tables

-- intelligence_domains
CREATE TABLE intelligence_domains (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- behavioral_indicators
CREATE TABLE behavioral_indicators (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- observation_sources
CREATE TABLE observation_sources (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(50) NOT NULL UNIQUE,
    name_fa VARCHAR(100) NOT NULL,
    description TEXT NULL,
    display_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- observation_contexts
CREATE TABLE observation_contexts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(50) NOT NULL UNIQUE,
    name_fa VARCHAR(100) NOT NULL,
    description TEXT NULL,
    display_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- intelligence_observations
CREATE TABLE intelligence_observations (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- STEP 3: Seed sources
INSERT INTO observation_sources (slug, name_fa, display_order, is_active) VALUES
    ('parent', 'والدین', 1, 1),
    ('teacher', 'مربی', 2, 1),
    ('student', 'خود دانش‌آموز', 3, 1),
    ('legacy', 'مشاهدات قبلی (بدون تفکیک منبع)', 999, 0);

-- STEP 4: Seed contexts
INSERT INTO observation_contexts (slug, name_fa, display_order) VALUES
    ('home', 'خانه', 1),
    ('class', 'کلاس', 2),
    ('group', 'فعالیت گروهی', 3),
    ('individual', 'فعالیت انفرادی', 4),
    ('other', 'سایر', 99);

-- STEP 5: Create compatibility views
CREATE VIEW mi_config AS
    SELECT id, type, scientific_desc, positive_traits, negative_traits
    FROM mi_config_legacy;

CREATE VIEW mi_logs AS
    SELECT id, log_date, student_id, mi_type, behavior, score
    FROM mi_logs_legacy;

-- STEP 6: Migrate domains and indicators (done via PHP application)
-- This requires parsing CSV and is better handled in PHP

SELECT 'Migration schema created successfully' AS status;
