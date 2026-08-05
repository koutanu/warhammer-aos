<?php
/**
 * 試合ロスタースナップショット列を追加するマイグレーション
 * php scripts/migrate_match_roster_snapshot.php
 *
 * 冪等: 既に列がある場合はスキップする。
 */
require_once __DIR__ . '/../libs/core/Config.php';

$pdo = new PDO(
    DB_TYPE . ':host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
    DB_USER,
    DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$alterColumns = [
    'player_a_roster_snapshot' => 'MEDIUMTEXT DEFAULT NULL COMMENT \'Player A ロスター表示用スナップショット(JSON)\'',
    'player_b_roster_snapshot' => 'MEDIUMTEXT DEFAULT NULL COMMENT \'Player B ロスター表示用スナップショット(JSON)\'',
];

$existing = $pdo->query('DESCRIBE t_matches')->fetchAll(PDO::FETCH_COLUMN);
foreach ($alterColumns as $col => $def) {
    if (in_array($col, $existing, true)) {
        echo "SKIP: {$col} already exists on t_matches\n";
        continue;
    }
    $pdo->exec("ALTER TABLE t_matches ADD COLUMN `{$col}` {$def}");
    echo "ALTER: added {$col} to t_matches\n";
}

echo "Match roster snapshot migration complete.\n";
