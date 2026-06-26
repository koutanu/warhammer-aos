<?php
require_once __DIR__ . '/../libs/core/Config.php';

try {
    $pdo = new PDO(
        DB_TYPE . ':host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "=== TABLES ===\n";
    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $t) {
        echo $t . "\n";
    }

    $matchTables = ['t_matches', 't_match_players', 't_match_round_scores', 'm_battleplans', 'm_battle_tactics', 't_rosters', 't_roster_units'];
    foreach ($matchTables as $t) {
        if (in_array($t, $tables, true)) {
            echo "\n=== DESCRIBE $t ===\n";
            $cols = $pdo->query("DESCRIBE `$t`")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($cols as $c) {
                echo $c['Field'] . ' ' . $c['Type'] . "\n";
            }
        }
    }
} catch (Exception $e) {
    echo 'ERROR: ' . $e->getMessage() . "\n";
}
