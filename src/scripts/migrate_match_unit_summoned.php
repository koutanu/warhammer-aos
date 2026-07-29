<?php
/**
 * t_match_unit_status に is_summoned 列を冪等に追加する。
 * php scripts/migrate_match_unit_summoned.php
 */
require_once __DIR__ . '/../libs/core/Config.php';

$pdo = new PDO(
    DB_TYPE . ':host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
    DB_USER,
    DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$cols = $pdo->query('DESCRIBE t_match_unit_status')->fetchAll(PDO::FETCH_COLUMN);

if (!in_array('is_summoned', $cols, true)) {
    $pdo->exec(
        'ALTER TABLE t_match_unit_status
         ADD COLUMN is_summoned TINYINT(1) NOT NULL DEFAULT 0 AFTER is_destroyed'
    );
    echo "ALTER: added is_summoned to t_match_unit_status\n";
} else {
    echo "SKIP: is_summoned already exists\n";
}

echo "Match unit summoned migration complete.\n";
