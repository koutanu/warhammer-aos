<?php
/**
 * ロスター用DBマイグレーション・シード実行
 * php scripts/migrate_roster.php
 */
require_once __DIR__ . '/../libs/core/Config.php';

$pdo = new PDO(
    DB_TYPE . ':host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
    DB_USER,
    DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$migrationFile = __DIR__ . '/../sql/roster_migration.sql';
$statements = array_filter(array_map('trim', explode(';', file_get_contents($migrationFile))));
foreach ($statements as $sql) {
    if ($sql !== '') {
        $pdo->exec($sql);
        echo "OK: " . substr(str_replace("\n", ' ', $sql), 0, 70) . "...\n";
    }
}

$alterColumns = [
    'battle_formation_id'   => 'INT(11) DEFAULT NULL',
    'spell_lore_id'         => 'INT(11) DEFAULT NULL',
    'prayer_lore_id'        => 'INT(11) DEFAULT NULL',
    'manifestation_lore_id' => 'INT(11) DEFAULT NULL',
    'grand_alliance'        => 'VARCHAR(50) DEFAULT NULL',
    'point_limit'           => 'INT(11) DEFAULT NULL',
    'updated_at'            => 'DATETIME DEFAULT NULL',
    'heroic_trait_id'       => 'INT(11) DEFAULT NULL',
    'trait_regiment_index'  => 'TINYINT DEFAULT NULL',
    'artefact_id'           => 'INT(11) DEFAULT NULL',
    'artefact_regiment_index' => 'TINYINT DEFAULT NULL',
    'trait_target_unit_id'  => 'INT(11) DEFAULT NULL',
    'artefact_target_unit_id' => 'INT(11) DEFAULT NULL',
	'trait_unit_slot'       => 'VARCHAR(16) DEFAULT NULL',
	'artefact_unit_slot'    => 'VARCHAR(16) DEFAULT NULL',
	'terrain_id'            => 'INT(11) DEFAULT NULL',
];

$existing = $pdo->query('DESCRIBE t_rosters')->fetchAll(PDO::FETCH_COLUMN);
foreach ($alterColumns as $col => $def) {
    if (!in_array($col, $existing, true)) {
        $pdo->exec("ALTER TABLE t_rosters ADD COLUMN `$col` $def");
        echo "ALTER: added $col to t_rosters\n";
    }
}

// 旧 trait_regiment_index から trait_target_unit_id へ移行
if (in_array('trait_target_unit_id', $pdo->query('DESCRIBE t_rosters')->fetchAll(PDO::FETCH_COLUMN), true)) {
    $pdo->exec("UPDATE t_rosters r
        JOIN t_roster_regiments rr ON rr.roster_id = r.id AND rr.sort_order = r.trait_regiment_index
        SET r.trait_target_unit_id = rr.hero_unit_id
        WHERE r.trait_regiment_index IS NOT NULL AND r.trait_target_unit_id IS NULL");
    $pdo->exec("UPDATE t_rosters r
        JOIN t_roster_regiments rr ON rr.roster_id = r.id AND rr.sort_order = r.artefact_regiment_index
        SET r.artefact_target_unit_id = rr.hero_unit_id
        WHERE r.artefact_regiment_index IS NOT NULL AND r.artefact_target_unit_id IS NULL");
    echo "MIGRATE: trait/artefact target unit ids from regiment index\n";
    $pdo->exec("UPDATE t_rosters SET trait_unit_slot = 'leader'
        WHERE trait_regiment_index IS NOT NULL AND (trait_unit_slot IS NULL OR trait_unit_slot = '')");
    $pdo->exec("UPDATE t_rosters SET artefact_unit_slot = 'leader'
        WHERE artefact_regiment_index IS NOT NULL AND (artefact_unit_slot IS NULL OR artefact_unit_slot = '')");
    echo "MIGRATE: default trait/artefact unit_slot to leader\n";
}

$seedFile = __DIR__ . '/../sql/roster_seed.sql';
$seedStatements = array_filter(array_map('trim', explode(';', file_get_contents($seedFile))));
foreach ($seedStatements as $sql) {
    if ($sql !== '') {
        $pdo->exec($sql);
        echo "SEED: " . substr(str_replace("\n", ' ', $sql), 0, 60) . "...\n";
    }
}

echo "Roster migration complete.\n";
