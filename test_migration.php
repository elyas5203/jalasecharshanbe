<?php
require_once __DIR__ . '/bootstrap.php';

echo "Testing MI migration...\n";
try {
    $pdo = db();
    echo "✅ Migration completed successfully\n";

    // Verify tables
    $tables = $pdo->query("SHOW TABLES LIKE '%mi%'")->fetchAll(PDO::FETCH_COLUMN);
    echo "\nTables created:\n";
    foreach ($tables as $table) {
        $count = $pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
        echo "  - $table: $count rows\n";
    }

} catch (Exception $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
