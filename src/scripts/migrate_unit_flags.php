<?php
/**
 * m_units の地形/顕現フラグ列を冪等に追加する。
 * php scripts/migrate_unit_flags.php
 */
require_once __DIR__ . '/../libs/core/Config.php';

$pdo = new PDO(
    DB_TYPE . ':host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
    DB_USER,
    DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$cols = $pdo->query('DESCRIBE m_units')->fetchAll(PDO::FETCH_COLUMN);

if (!in_array('is_terrain', $cols, true)) {
    $pdo->exec('ALTER TABLE m_units ADD COLUMN is_terrain TINYINT(1) NOT NULL DEFAULT 0 AFTER is_unique');
    echo "ALTER: added is_terrain to m_units\n";
} else {
    echo "SKIP: is_terrain already exists\n";
}

$cols = $pdo->query('DESCRIBE m_units')->fetchAll(PDO::FETCH_COLUMN);
if (!in_array('is_manifestation', $cols, true)) {
    $pdo->exec('ALTER TABLE m_units ADD COLUMN is_manifestation TINYINT(1) NOT NULL DEFAULT 0 AFTER is_terrain');
    echo "ALTER: added is_manifestation to m_units\n";
} else {
    echo "SKIP: is_manifestation already exists\n";
}

echo "Unit flags migration complete.\n";
