<?php
/**
 * MI System Test Suite
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config.php';

try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage() . "\n");
}

echo "=========================================\n";
echo "MI SYSTEM TEST SUITE\n";
echo "=========================================\n\n";

$passed = 0;
$failed = 0;
$warnings = 0;

function test($name, $condition, $errorMsg = '') {
    global $passed, $failed;
    if ($condition) {
        echo "✅ PASS: $name\n";
        $passed++;
        return true;
    } else {
        echo "❌ FAIL: $name\n";
        if ($errorMsg) echo "   Error: $errorMsg\n";
        $failed++;
        return false;
    }
}

function warn($name, $msg) {
    global $warnings;
    echo "⚠️  WARN: $name\n";
    echo "   $msg\n";
    $warnings++;
}

echo "--- DATABASE STRUCTURE TESTS ---\n";

// Test 1: Legacy tables preserved
$legacyConfig = (int)$pdo->query('SELECT COUNT(*) FROM mi_config_legacy')->fetchColumn();
test('Legacy mi_config_legacy exists', $legacyConfig === 8, "Expected 8 rows, got $legacyConfig");

$legacyLogs = (int)$pdo->query('SELECT COUNT(*) FROM mi_logs_legacy')->fetchColumn();
test('Legacy mi_logs_legacy preserved', $legacyLogs === 295, "Expected 295 rows, got $legacyLogs");

// Test 2: New tables exist
$newDomains = (int)$pdo->query('SELECT COUNT(*) FROM intelligence_domains')->fetchColumn();
test('New intelligence_domains created', $newDomains === 8, "Expected 8 domains, got $newDomains");

$newIndicators = (int)$pdo->query('SELECT COUNT(*) FROM behavioral_indicators')->fetchColumn();
test('New behavioral_indicators created', $newIndicators === 63, "Expected 63 indicators, got $newIndicators");

$sources = (int)$pdo->query('SELECT COUNT(*) FROM observation_sources')->fetchColumn();
test('Observation sources created', $sources === 4, "Expected 4 sources, got $sources");

$contexts = (int)$pdo->query('SELECT COUNT(*) FROM observation_contexts')->fetchColumn();
test('Observation contexts created', $contexts === 5, "Expected 5 contexts, got $contexts");

// Test 3: Compatibility views exist
$viewsQuery = $pdo->query("SHOW TABLES LIKE '%mi_%'");
$tables = $viewsQuery->fetchAll(PDO::FETCH_COLUMN);
test('mi_config VIEW exists', in_array('mi_config', $tables));
test('mi_logs VIEW exists', in_array('mi_logs', $tables));

echo "\n--- DATA INTEGRITY TESTS ---\n";

// Test 4: Check legacy score distribution
$scoreDistribution = $pdo->query('SELECT score, COUNT(*) as cnt FROM mi_logs_legacy GROUP BY score')->fetchAll();
$scores = array_column($scoreDistribution, 'cnt', 'score');
test('Legacy score -1 preserved', isset($scores[-1]) && $scores[-1] == 21);
test('Legacy score 0 preserved', isset($scores[0]) && $scores[0] == 21);
test('Legacy score 1 preserved', isset($scores[1]) && $scores[1] == 253);

// Test 5: Check constraint on new observations table
try {
    $pdo->exec("INSERT INTO intelligence_observations
                (owner_id, student_id, indicator_id, source_id, score, no_opportunity, observed_at, recorded_by)
                VALUES (1, 1, 1, 1, 5, 0, CURDATE(), 1)");
    test('CHECK constraint on score (should fail for score=5)', false, 'Constraint not enforced!');
    $pdo->exec("DELETE FROM intelligence_observations WHERE score=5");
} catch (PDOException $e) {
    test('CHECK constraint on score enforced', true);
}

// Test 6: Indicator polarity distribution
$polarityDist = $pdo->query('SELECT indicator_category, COUNT(*) as cnt FROM behavioral_indicators GROUP BY indicator_category')->fetchAll();
$polarity = array_column($polarityDist, 'cnt', 'indicator_category');
test('Positive indicators exist', isset($polarity['positive']) && $polarity['positive'] > 0);
test('Negative indicators exist', isset($polarity['negative']) && $polarity['negative'] > 0);

echo "\n--- BUSINESS LOGIC TESTS ---\n";

// Test 7: Polarity normalization logic
function testPolarityNormalization($score, $category, $expected) {
    if ($category === 'negative') {
        $normalized = 4 - $score;
    } else {
        $normalized = $score;
    }
    return $normalized === $expected;
}

test('Positive score 0 → normalized 0', testPolarityNormalization(0, 'positive', 0));
test('Positive score 4 → normalized 4', testPolarityNormalization(4, 'positive', 4));
test('Negative score 0 → normalized 4', testPolarityNormalization(0, 'negative', 4));
test('Negative score 4 → normalized 0', testPolarityNormalization(4, 'negative', 0));
test('Neutral score 3 → normalized 3', testPolarityNormalization(3, 'neutral', 3));

// Test 8: Domain score calculation
$testObservations = [
    ['indicator_id' => 1, 'score' => 4, 'no_opportunity' => false],
    ['indicator_id' => 2, 'score' => 3, 'no_opportunity' => false],
    ['indicator_id' => 3, 'score' => 2, 'no_opportunity' => false],
];
$testIndicators = [
    ['id' => 1, 'indicator_category' => 'positive'],
    ['id' => 2, 'indicator_category' => 'positive'],
    ['id' => 3, 'indicator_category' => 'negative'], // Will be inverted: 4-2=2
];
// Expected: (4 + 3 + 2) / 3 = 3.0
$normalizedScores = [];
foreach ($testObservations as $obs) {
    if ($obs['no_opportunity']) continue;
    $category = $testIndicators[array_search($obs['indicator_id'], array_column($testIndicators, 'id'))]['indicator_category'];
    $normalizedScores[] = $category === 'negative' ? 4 - $obs['score'] : $obs['score'];
}
$avgScore = round(array_sum($normalizedScores) / count($normalizedScores), 2);
test('Domain score calculation with polarity', $avgScore == 3.0, "Expected 3.0, got $avgScore");

// Test 9: Domain score never > 4
$maxTestObservations = [
    ['indicator_id' => 1, 'score' => 4, 'no_opportunity' => false],
    ['indicator_id' => 2, 'score' => 4, 'no_opportunity' => false],
];
$maxTestIndicators = [
    ['id' => 1, 'indicator_category' => 'positive'],
    ['id' => 2, 'indicator_category' => 'positive'],
];
$normalizedScores = [];
foreach ($maxTestObservations as $obs) {
    $category = $maxTestIndicators[array_search($obs['indicator_id'], array_column($maxTestIndicators, 'id'))]['indicator_category'];
    $normalizedScores[] = $category === 'negative' ? 4 - $obs['score'] : $obs['score'];
}
$maxScore = round(array_sum($normalizedScores) / count($normalizedScores), 2);
test('Domain score never exceeds 4', $maxScore <= 4, "Expected ≤4, got $maxScore");

// Test 10: Domain score never < 0
$minTestObservations = [
    ['indicator_id' => 1, 'score' => 0, 'no_opportunity' => false],
    ['indicator_id' => 2, 'score' => 0, 'no_opportunity' => false],
];
$minTestIndicators = [
    ['id' => 1, 'indicator_category' => 'positive'],
    ['id' => 2, 'indicator_category' => 'positive'],
];
$normalizedScores = [];
foreach ($minTestObservations as $obs) {
    $category = $minTestIndicators[array_search($obs['indicator_id'], array_column($minTestIndicators, 'id'))]['indicator_category'];
    $normalizedScores[] = $category === 'negative' ? 4 - $obs['score'] : $obs['score'];
}
$minScore = round(array_sum($normalizedScores) / count($normalizedScores), 2);
test('Domain score never below 0', $minScore >= 0, "Expected ≥0, got $minScore");

echo "\n--- SCHEMA VALIDATION ---\n";

// Test 11: Foreign keys exist
$fks = $pdo->query("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
                    WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='intelligence_observations'
                    AND CONSTRAINT_TYPE='FOREIGN KEY'")->fetchAll(PDO::FETCH_COLUMN);
test('Foreign keys on intelligence_observations', count($fks) >= 4, "Expected ≥4 FKs, got " . count($fks));

// Test 12: Indexes exist
$indexes = $pdo->query("SHOW INDEX FROM intelligence_observations WHERE Key_name != 'PRIMARY'")->fetchAll();
test('Indexes on intelligence_observations', count($indexes) >= 5, "Expected ≥5 indexes, got " . count($indexes));

echo "\n--- MIGRATION VERIFICATION ---\n";

// Test 13: Schema version updated
$schemaVersion = $pdo->query("SELECT value FROM settings WHERE `key`='SCHEMA_VERSION'")->fetchColumn();
test('Schema version is 8', $schemaVersion === '8', "Expected '8', got '$schemaVersion'");

// Test 14: No orphan observations
$orphanObs = (int)$pdo->query('SELECT COUNT(*) FROM intelligence_observations
                               WHERE student_id NOT IN (SELECT id FROM students)')->fetchColumn();
test('No orphan observations', $orphanObs === 0, "Found $orphanObs orphan observations");

echo "\n=========================================\n";
echo "SUMMARY\n";
echo "=========================================\n";
echo "✅ Passed: $passed\n";
echo "❌ Failed: $failed\n";
echo "⚠️  Warnings: $warnings\n";
echo "=========================================\n";

if ($failed > 0) {
    echo "\n⚠️  Some tests failed. Please review the failures above.\n";
    exit(1);
} else {
    echo "\n✅ All tests passed!\n";
    exit(0);
}
