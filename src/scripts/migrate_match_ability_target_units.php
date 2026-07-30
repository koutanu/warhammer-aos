<?php
/**
 * t_match_ability_usage.target_unit_key と t_match_ability_target_units を冪等に追加する。
 * php scripts/migrate_match_ability_target_units.php
 */
require_once __DIR__ . '/../libs/core/Config.php';

$pdo = new PDO(
    DB_TYPE . ':host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
    DB_USER,
    DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$cols = $pdo->query('DESCRIBE t_match_ability_usage')->fetchAll(PDO::FETCH_COLUMN);

if (!in_array('target_unit_key', $cols, true)) {
    $pdo->exec(
        'ALTER TABLE t_match_ability_usage
         ADD COLUMN target_unit_key VARCHAR(64) DEFAULT NULL AFTER used_at_phase'
    );
    echo "ALTER: added target_unit_key to t_match_ability_usage\n";
} else {
    echo "SKIP: target_unit_key already exists\n";
}

$tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
if (!in_array('t_match_ability_target_units', $tables, true)) {
    $pdo->exec(
        'CREATE TABLE `t_match_ability_target_units` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `match_id` int(11) NOT NULL,
          `player_slot` tinyint(4) NOT NULL COMMENT \'1=player_a, 2=player_b\',
          `ability_key` varchar(128) NOT NULL,
          `unit_key` varchar(64) NOT NULL COMMENT \'instanceKey (hero:/unit:)\',
          `used_in_turn` int(11) NOT NULL DEFAULT 1,
          `updated_at` datetime DEFAULT NULL,
          PRIMARY KEY (`id`),
          UNIQUE KEY `uk_match_player_ability_unit` (`match_id`,`player_slot`,`ability_key`,`unit_key`),
          KEY `idx_match_id` (`match_id`),
          CONSTRAINT `fk_ability_target_units_match`
            FOREIGN KEY (`match_id`) REFERENCES `t_matches` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );
    echo "CREATE: t_match_ability_target_units\n";
} else {
    echo "SKIP: t_match_ability_target_units already exists\n";
}

echo "Match ability target units migration complete.\n";
