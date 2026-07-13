<?php
/**
 * GHB 2026-27 Battle Tactics マイグレーション
 * php scripts/migrate_battle_tactics_2026.php
 */
require_once __DIR__ . '/../libs/core/Config.php';
require_once __DIR__ . '/../libs/types/battle_tactics.php';

$pdo = new PDO(
    DB_TYPE . ':host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
    DB_USER,
    DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$sqlFiles = [
    __DIR__ . '/../sql/battle_tactics_2026_migration.sql',
    __DIR__ . '/../sql/battle_tactics_match_progress_migration.sql',
];
foreach ($sqlFiles as $sqlFile) {
    $rawSql = preg_replace('/^--.*$/m', '', file_get_contents($sqlFile));
    $statements = array_filter(array_map('trim', explode(';', $rawSql)));
    foreach ($statements as $sql) {
        if ($sql !== '') {
            $pdo->exec($sql);
            echo 'OK: ' . substr(str_replace("\n", ' ', $sql), 0, 70) . "...\n";
        }
    }
}

$stageCols = $pdo->query('DESCRIBE m_battle_tactic_stages')->fetchAll(PDO::FETCH_COLUMN);
if (!in_array('victory_points', $stageCols, true)) {
    $pdo->exec(
        "ALTER TABLE m_battle_tactic_stages
         ADD COLUMN `victory_points` TINYINT(4) NOT NULL DEFAULT 2
         AFTER `effect`"
    );
    echo "ALTER: added victory_points to m_battle_tactic_stages\n";
} else {
    $pdo->exec(
        "ALTER TABLE m_battle_tactic_stages
         MODIFY COLUMN `victory_points` TINYINT(4) NOT NULL DEFAULT 2"
    );
    echo "ALTER: normalized victory_points on m_battle_tactic_stages\n";
}
$pdo->exec(
    "UPDATE m_battle_tactic_stages
     SET victory_points = 2
     WHERE victory_points IS NULL OR victory_points < 1"
);
echo "MIGRATE: ensured victory_points defaults on stages\n";

$existing = $pdo->query('DESCRIBE m_battle_tactics')->fetchAll(PDO::FETCH_COLUMN);
if (!in_array('season', $existing, true)) {
    $pdo->exec(
        "ALTER TABLE m_battle_tactics
         ADD COLUMN `season` VARCHAR(16) NOT NULL DEFAULT '2024-25'
         AFTER `grand_alliance`"
    );
    echo "ALTER: added season to m_battle_tactics\n";
}

$pdo->exec(
    "UPDATE m_battle_tactics SET season = '2024-25'
     WHERE season IS NULL OR season = ''"
);
echo "MIGRATE: backfilled season=2024-25 on existing tactics\n";

$seedCards = [
    [
        'name' => 'GHB 2026-27: Placeholder Card A',
        'sort_order' => 100,
        'stages' => [
            ['affray', 1, 'Affray A', 'Placeholder Affray objective for Card A.'],
            ['strike', 2, 'Strike A', 'Placeholder Strike objective for Card A.'],
            ['domination', 3, 'Domination A', 'Placeholder Domination objective for Card A.'],
        ],
    ],
    [
        'name' => 'GHB 2026-27: Placeholder Card B',
        'sort_order' => 101,
        'stages' => [
            ['affray', 1, 'Affray B', 'Placeholder Affray objective for Card B.'],
            ['strike', 2, 'Strike B', 'Placeholder Strike objective for Card B.'],
            ['domination', 3, 'Domination B', 'Placeholder Domination objective for Card B.'],
        ],
    ],
];

$findCard = $pdo->prepare(
    'SELECT id FROM m_battle_tactics WHERE name = ? AND season = ? LIMIT 1'
);
$insertCard = $pdo->prepare(
    'INSERT INTO m_battle_tactics (name, grand_alliance, season, sort_order)
     VALUES (?, NULL, ?, ?)'
);
$stageCount = $pdo->prepare(
    'SELECT COUNT(*) FROM m_battle_tactic_stages WHERE battle_tactic_id = ?'
);
$insertStage = $pdo->prepare(
    'INSERT INTO m_battle_tactic_stages
        (battle_tactic_id, stage, stage_order, name, effect, victory_points)
     VALUES (?, ?, ?, ?, ?, ?)'
);

foreach ($seedCards as $card) {
    $findCard->execute([$card['name'], BattleTactics::SEASON_2026_27]);
    $row = $findCard->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $cardId = (int)$row['id'];
    } else {
        $insertCard->execute([
            $card['name'],
            BattleTactics::SEASON_2026_27,
            $card['sort_order'],
        ]);
        $cardId = (int)$pdo->lastInsertId();
        echo "SEED: card {$card['name']} (id={$cardId})\n";
    }

    $stageCount->execute([$cardId]);
    if ((int)$stageCount->fetchColumn() === 0) {
        foreach ($card['stages'] as $stage) {
            $insertStage->execute([
                $cardId,
                $stage[0],
                $stage[1],
                $stage[2],
                $stage[3],
                2,
            ]);
        }
        echo "SEED: stages for card id={$cardId}\n";
    }
}

echo "Battle tactics 2026-27 migration complete.\n";
