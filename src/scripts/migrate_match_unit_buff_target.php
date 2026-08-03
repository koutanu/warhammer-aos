<?php
/**
 * t_match_unit_status に buff_target_unit_key 列を冪等に追加する。
 * 顕現召喚時に効果対象ユニット (hero:/unit:) を保持する。
 * php scripts/migrate_match_unit_buff_target.php
 */
require_once __DIR__ . '/../libs/core/Config.php';

$pdo = new PDO(
    DB_TYPE . ':host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
    DB_USER,
    DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$cols = $pdo->query('DESCRIBE t_match_unit_status')->fetchAll(PDO::FETCH_COLUMN);

if (!in_array('buff_target_unit_key', $cols, true)) {
    $pdo->exec(
        "ALTER TABLE t_match_unit_status
         ADD COLUMN buff_target_unit_key varchar(64) DEFAULT NULL
         COMMENT '顕現の効果対象 instanceKey (hero:/unit:)'
         AFTER is_summoned"
    );
    echo "ALTER: added buff_target_unit_key to t_match_unit_status\n";
} else {
    echo "SKIP: buff_target_unit_key already exists\n";
}

echo "Match unit buff target migration complete.\n";
