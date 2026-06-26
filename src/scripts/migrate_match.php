<?php
/**
 * マッチプレイ用DBマイグレーション実行スクリプト
 * php scripts/migrate_match.php
 */
require_once __DIR__ . '/../libs/core/Config.php';

$pdo = new PDO(
    DB_TYPE . ':host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
    DB_USER,
    DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$sqlFile = __DIR__ . '/../sql/match_migration.sql';
$statements = array_filter(array_map('trim', explode(';', file_get_contents($sqlFile))));
foreach ($statements as $sql) {
    if ($sql !== '') {
        $pdo->exec($sql);
        echo "OK: " . substr($sql, 0, 60) . "...\n";
    }
}

$alterColumns = [
    'battleplan_id'      => 'INT(11) DEFAULT NULL',
    'status'             => "VARCHAR(20) NOT NULL DEFAULT 'active'",
    'player_a_name'      => 'VARCHAR(255) DEFAULT NULL',
    'player_b_name'      => 'VARCHAR(255) DEFAULT NULL',
    'player_a_faction_id'=> 'INT(11) DEFAULT NULL',
    'player_b_faction_id'=> 'INT(11) DEFAULT NULL',
    'user_id'            => 'INT(11) DEFAULT NULL COMMENT "試合作成者"',
    'completed_at'       => 'DATETIME DEFAULT NULL',
    'updated_at'         => 'DATETIME DEFAULT NULL',
    'game_battle_round'  => 'TINYINT(4) NOT NULL DEFAULT 1',
    'active_player_slot' => 'TINYINT(4) NOT NULL DEFAULT 1',
    'game_phase'         => "VARCHAR(32) NOT NULL DEFAULT 'hero'",
    'game_turn_counter'  => 'INT(11) NOT NULL DEFAULT 1',
];

$existing = $pdo->query('DESCRIBE t_matches')->fetchAll(PDO::FETCH_COLUMN);
foreach ($alterColumns as $col => $def) {
    if (!in_array($col, $existing, true)) {
        $pdo->exec("ALTER TABLE t_matches ADD COLUMN `$col` $def");
        echo "ALTER: added $col to t_matches\n";
    }
}

$battleplans = [
    ['GHB 2024-25: Battleplan 1', 1],
    ['GHB 2024-25: Battleplan 2', 2],
    ['GHB 2024-25: Battleplan 3', 3],
    ['First Blood: Scenario 1', 4],
    ['First Blood: Scenario 2', 5],
    ['First Blood: Scenario 3', 6],
];
$count = (int)$pdo->query('SELECT COUNT(*) FROM m_battleplans')->fetchColumn();
if ($count === 0) {
    $stmt = $pdo->prepare('INSERT INTO m_battleplans (name, sort_order) VALUES (?, ?)');
    foreach ($battleplans as $bp) {
        $stmt->execute($bp);
    }
    echo "Seeded m_battleplans\n";
}

$tactics = [
    ['Seize the Centre', null, 1],
    ['Covering Fire', null, 2],
    ['Counter-Charge', null, 3],
    ['Restless Energy', null, 4],
    ['Master the Paths', null, 5],
    ['Mark Your Territory', null, 6],
    ['Order: Hold the Line', 'Order', 7],
    ['Order: Strike Fast', 'Order', 8],
    ['Chaos: Desecrate', 'Chaos', 9],
    ['Chaos: Overwhelm', 'Chaos', 10],
    ['Death: Claim Souls', 'Death', 11],
    ['Death: Relentless March', 'Death', 12],
    ['Destruction: Smash Through', 'Destruction', 13],
    ['Destruction: Rampage', 'Destruction', 14],
];
$count = (int)$pdo->query('SELECT COUNT(*) FROM m_battle_tactics')->fetchColumn();
if ($count === 0) {
    $stmt = $pdo->prepare('INSERT INTO m_battle_tactics (name, grand_alliance, sort_order) VALUES (?, ?, ?)');
    foreach ($tactics as $t) {
        $stmt->execute($t);
    }
    echo "Seeded m_battle_tactics\n";
}

echo "Migration complete.\n";
