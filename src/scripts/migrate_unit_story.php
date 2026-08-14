<?php
/**
 * m_units にストーリー用列を冪等に追加する。
 * php scripts/migrate_unit_story.php
 */
require_once __DIR__ . '/../libs/core/Config.php';

$pdo = new PDO(
    DB_TYPE . ':host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
    DB_USER,
    DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$cols = $pdo->query('DESCRIBE m_units')->fetchAll(PDO::FETCH_COLUMN);

if (!in_array('story_text', $cols, true)) {
    $pdo->exec('ALTER TABLE m_units ADD COLUMN story_text TEXT NULL AFTER flavor_text');
    echo "ALTER: added story_text to m_units\n";
} else {
    echo "SKIP: story_text already exists\n";
}

$cols = $pdo->query('DESCRIBE m_units')->fetchAll(PDO::FETCH_COLUMN);
if (!in_array('story_source_url', $cols, true)) {
    $after = in_array('story_text', $cols, true) ? 'story_text' : 'flavor_text';
    $pdo->exec("ALTER TABLE m_units ADD COLUMN story_source_url VARCHAR(512) NULL AFTER {$after}");
    echo "ALTER: added story_source_url to m_units\n";
} else {
    echo "SKIP: story_source_url already exists\n";
}

echo "Unit story migration complete.\n";
