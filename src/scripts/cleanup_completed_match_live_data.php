<?php
/**
 * 完了済み試合のプレイ中専用データを削除し、孤立行を掃除する。
 * php scripts/cleanup_completed_match_live_data.php
 */
require_once __DIR__ . '/../libs/core/Config.php';
require_once __DIR__ . '/../libs/core/Database.php';
require_once __DIR__ . '/../libs/core/Model.php';
require_once __DIR__ . '/../libs/models/match_model.php';

$pdo = new PDO(
    DB_TYPE . ':host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
    DB_USER,
    DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$orphanTables = [
    't_match_round_scores',
    't_match_ability_usage',
    't_match_ability_target_units',
    't_match_unit_status',
    't_match_unit_phase_flags',
    't_match_battle_tactic_progress',
];

foreach ($orphanTables as $table) {
    $deleted = $pdo->exec(
        "DELETE c FROM {$table} c
           LEFT JOIN t_matches m ON c.match_id = m.id
          WHERE m.id IS NULL"
    );
    echo "Orphan {$table}: {$deleted} rows deleted\n";
}

$model = new Match_Model();
$completed = $pdo->query(
    "SELECT id FROM t_matches WHERE status = 'completed' ORDER BY id ASC"
)->fetchAll(PDO::FETCH_COLUMN);

foreach ($completed as $matchId) {
    $matchId = (int)$matchId;
    $model->finalizeCompletedMatchLiveData($matchId);
    echo "Finalized completed match #{$matchId}\n";
}

echo 'Cleanup complete. Completed matches processed: ' . count($completed) . "\n";
