<?php
/**
 * キーワードマスタ faction_id 対応マイグレーション
 * php scripts/migrate_keyword_faction.php
 */
require_once __DIR__ . '/../libs/core/Config.php';

$pdo = new PDO(
    DB_TYPE . ':host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
    DB_USER,
    DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

function columnExists(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.columns
         WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?'
    );
    $stmt->execute([$table, $column]);
    return (int)$stmt->fetchColumn() > 0;
}

function indexExists(PDO $pdo, string $table, string $index): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.statistics
         WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?'
    );
    $stmt->execute([$table, $index]);
    return (int)$stmt->fetchColumn() > 0;
}

if (!columnExists($pdo, 'm_keywords_master', 'faction_id')) {
    $pdo->exec(
        'ALTER TABLE m_keywords_master
         ADD COLUMN faction_id INT NULL DEFAULT NULL AFTER accepts_param'
    );
    echo "OK: added faction_id to m_keywords_master\n";
} else {
    echo "Skip: faction_id already exists on m_keywords_master\n";
}

if (!indexExists($pdo, 'm_keywords_master', 'idx_keywords_master_faction')) {
    $pdo->exec(
        'ALTER TABLE m_keywords_master
         ADD INDEX idx_keywords_master_faction (faction_id)'
    );
    echo "OK: added index idx_keywords_master_faction\n";
} else {
    echo "Skip: idx_keywords_master_faction already exists\n";
}

echo "Done.\n";
