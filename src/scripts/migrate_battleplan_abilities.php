<?php
/**
 * バトルプラン固有アビリティ用DBマイグレーション
 * php scripts/migrate_battleplan_abilities.php
 *
 * - m_battleplan_abilities テーブルを作成
 * - シードは行わない（データは手動登録）
 */
require_once __DIR__ . '/../libs/core/Config.php';

$pdo = new PDO(
    DB_TYPE . ':host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
    DB_USER,
    DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$sqlFile = __DIR__ . '/../sql/battleplan_abilities_migration.sql';
$statements = array_filter(array_map('trim', explode(';', file_get_contents($sqlFile))));
foreach ($statements as $sql) {
    if ($sql !== '') {
        $pdo->exec($sql);
        echo "OK: " . substr($sql, 0, 60) . "...\n";
    }
}

echo "Migration complete (no seed).\n";
