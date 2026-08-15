<?php
/**
 * m_ability_master に usable_when_destroyed 列を冪等に追加し、効果文から backfill する。
 *
 * Docker 内:
 *   php scripts/migrate_ability_usable_when_destroyed.php
 * ホストから:
 *   php scripts/migrate_ability_usable_when_destroyed.php
 *   （または）docker compose exec web php scripts/migrate_ability_usable_when_destroyed.php
 */
if (php_sapi_name() === 'cli' && empty($_SERVER['HTTP_HOST'])) {
    $_SERVER['HTTP_HOST'] = 'localhost';
}

require_once __DIR__ . '/../libs/core/Config.php';

/**
 * Docker 内は host=db、ホスト CLI は 127.0.0.1:3307 にフォールバックする。
 */
function connectMigrationPdo(): PDO
{
    $inDocker = file_exists('/.dockerenv');
    $candidates = $inDocker
        ? [['host' => DB_HOST, 'port' => 3306]]
        : [
            ['host' => '127.0.0.1', 'port' => 3307],
            ['host' => DB_HOST, 'port' => 3306],
            ['host' => '127.0.0.1', 'port' => 3306],
        ];

    $lastError = null;
    foreach ($candidates as $c) {
        try {
            $dsn = DB_TYPE
                . ':host=' . $c['host']
                . ';port=' . $c['port']
                . ';dbname=' . DB_NAME
                . ';charset=utf8mb4';
            return new PDO(
                $dsn,
                DB_USER,
                DB_PASS,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
        } catch (PDOException $e) {
            $lastError = $e;
        }
    }

    throw $lastError ?? new RuntimeException('DB connection failed.');
}

$pdo = connectMigrationPdo();

$cols = $pdo->query('DESCRIBE m_ability_master')->fetchAll(PDO::FETCH_COLUMN);

if (!in_array('usable_when_destroyed', $cols, true)) {
    $after = in_array('usage_per', $cols, true) ? ' AFTER usage_per' : '';
    $pdo->exec(
        'ALTER TABLE m_ability_master
         ADD COLUMN usable_when_destroyed TINYINT(1) NOT NULL DEFAULT 0' . $after
    );
    echo "ALTER: added usable_when_destroyed to m_ability_master\n";
} else {
    echo "SKIP: usable_when_destroyed already exists\n";
}

$cols = $pdo->query('DESCRIBE m_ability_master')->fetchAll(PDO::FETCH_COLUMN);
$clauses = [
    "effect LIKE '%このユニットが破壊されていても使用できる%'",
    "effect LIKE '%even if this unit has been destroyed%'",
    "effect LIKE '%even if this unit is destroyed%'",
];
if (in_array('trigger_condition_en', $cols, true)) {
    $clauses[] = "IFNULL(trigger_condition_en, '') LIKE '%even if this unit has been destroyed%'";
    $clauses[] = "IFNULL(trigger_condition_en, '') LIKE '%even if this unit is destroyed%'";
}
if (in_array('trigger_condition_ja', $cols, true)) {
    $clauses[] = "IFNULL(trigger_condition_ja, '') LIKE '%このユニットが破壊されていても使用できる%'";
}

$updated = $pdo->exec(
    'UPDATE m_ability_master
     SET usable_when_destroyed = 1
     WHERE usable_when_destroyed = 0
       AND (' . implode("\n         OR ", $clauses) . ')'
);
echo 'BACKFILL: marked ' . (int)$updated . " abilities usable_when_destroyed\n";
echo "Ability usable_when_destroyed migration complete.\n";
