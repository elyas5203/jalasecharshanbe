<?php
/**
 * Standalone migration script that uses the application's database connection
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Suppress header warnings during CLI execution
define('CLI_MODE', true);

require_once __DIR__ . '/config.php';

// Simple PDO connection
try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage() . "\n");
}

echo "Starting domain and indicator migration...\n\n";

// Get legacy domains
$legacyDomains = $pdo->query('SELECT * FROM mi_config_legacy ORDER BY id')->fetchAll();

$domainMap = [];
$totalIndicators = 0;

foreach ($legacyDomains as $idx => $domain) {
    $type = $domain['type'];

    // Extract slug (remove emoji and extra spaces)
    $slug = preg_replace('/[\x{1F300}-\x{1F9FF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}]/u', '', $type);
    $slug = trim($slug);
    $slug = str_replace([' ', '‌', '/'], '-', $slug);
    $slug = mb_strtolower($slug, 'UTF-8');
    $slug = preg_replace('/[^a-z0-9\-\_آ-ی]/u', '', $slug);
    if (empty($slug)) $slug = 'domain-' . ($idx + 1);

    // Extract emoji
    preg_match('/[\x{1F300}-\x{1F9FF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}]/u', $type, $matches);
    $icon = $matches[0] ?? '';

    echo "Migrating domain: $type\n";
    echo "  Slug: $slug\n";
    echo "  Icon: $icon\n";

    // Insert domain
    $ins = $pdo->prepare("INSERT INTO intelligence_domains (slug, name_fa, icon, scientific_desc, display_order, is_active)
                          VALUES (?, ?, ?, ?, ?, 1)");
    $ins->execute([$slug, $type, $icon, $domain['scientific_desc'], $idx]);
    $newDomainId = (int)$pdo->lastInsertId();
    $domainMap[$type] = $newDomainId;

    // Migrate positive indicators
    $positiveTraits = $domain['positive_traits']
        ? array_filter(array_map('trim', explode(',', $domain['positive_traits'])))
        : [];

    // Migrate negative indicators
    $negativeTraits = $domain['negative_traits']
        ? array_filter(array_map('trim', explode(',', $domain['negative_traits'])))
        : [];

    $order = 0;
    $indicatorCount = 0;

    foreach ($positiveTraits as $trait) {
        if (empty($trait)) continue;

        // Split text and explanation
        $text = $trait;
        $explanation = null;
        if (strpos($trait, '(') !== false && strpos($trait, ')') !== false) {
            preg_match('/^(.+?)\s*\((.+?)\)\s*$/', $trait, $parts);
            if ($parts) {
                $text = trim($parts[1]);
                $explanation = trim($parts[2]);
            }
        }

        $pdo->prepare("INSERT INTO behavioral_indicators (domain_id, indicator_text, indicator_category, explanation, display_order, is_active)
                       VALUES (?, ?, 'positive', ?, ?, 1)")
            ->execute([$newDomainId, $text, $explanation, $order++]);
        $indicatorCount++;
    }

    foreach ($negativeTraits as $trait) {
        if (empty($trait)) continue;

        $text = $trait;
        $explanation = null;
        if (strpos($trait, '(') !== false && strpos($trait, ')') !== false) {
            preg_match('/^(.+?)\s*\((.+?)\)\s*$/', $trait, $parts);
            if ($parts) {
                $text = trim($parts[1]);
                $explanation = trim($parts[2]);
            }
        }

        $pdo->prepare("INSERT INTO behavioral_indicators (domain_id, indicator_text, indicator_category, explanation, display_order, is_active)
                       VALUES (?, ?, 'negative', ?, ?, 1)")
            ->execute([$newDomainId, $text, $explanation, $order++]);
        $indicatorCount++;
    }

    echo "  Created $indicatorCount indicators\n\n";
    $totalIndicators += $indicatorCount;
}

// Verification
$newDomainsCount = (int)$pdo->query('SELECT COUNT(*) FROM intelligence_domains')->fetchColumn();
$newIndicatorsCount = (int)$pdo->query('SELECT COUNT(*) FROM behavioral_indicators')->fetchColumn();
$legacyConfigPreserved = (int)$pdo->query('SELECT COUNT(*) FROM mi_config_legacy')->fetchColumn();
$legacyLogsPreserved = (int)$pdo->query('SELECT COUNT(*) FROM mi_logs_legacy')->fetchColumn();

echo "\n========================================\n";
echo "MIGRATION COMPLETE\n";
echo "========================================\n";
echo "Legacy mi_config preserved: $legacyConfigPreserved rows\n";
echo "Legacy mi_logs preserved: $legacyLogsPreserved rows\n";
echo "New domains created: $newDomainsCount\n";
echo "New indicators created: $newIndicatorsCount\n";
echo "========================================\n";

if ($legacyConfigPreserved !== count($legacyDomains)) {
    die("\n❌ ERROR: Legacy config count mismatch!\n");
}

echo "\n✅ All data migrated and verified successfully!\n";
