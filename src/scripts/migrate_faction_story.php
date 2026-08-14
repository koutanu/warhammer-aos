<?php
/**
 * m_factions にストーリー用列を冪等に追加する。
 * php scripts/migrate_faction_story.php
 */
require_once __DIR__ . '/../libs/core/Config.php';

$pdo = new PDO(
    DB_TYPE . ':host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
    DB_USER,
    DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$cols = $pdo->query('DESCRIBE m_factions')->fetchAll(PDO::FETCH_COLUMN);

if (!in_array('story_text', $cols, true)) {
    $pdo->exec('ALTER TABLE m_factions ADD COLUMN story_text TEXT NULL AFTER is_hidden');
    echo "ALTER: added story_text to m_factions\n";
} else {
    echo "SKIP: story_text already exists on m_factions\n";
}

$cols = $pdo->query('DESCRIBE m_factions')->fetchAll(PDO::FETCH_COLUMN);
if (!in_array('story_source_url', $cols, true)) {
    $after = in_array('story_text', $cols, true) ? 'story_text' : 'is_hidden';
    $pdo->exec("ALTER TABLE m_factions ADD COLUMN story_source_url VARCHAR(512) NULL AFTER {$after}");
    echo "ALTER: added story_source_url to m_factions\n";
} else {
    echo "SKIP: story_source_url already exists on m_factions\n";
}

echo "Faction story migration complete.\n";
