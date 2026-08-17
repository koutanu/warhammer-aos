<?php
/**
 * t_match_unit_phase_flags を冪等に作成する。
 *
 * Docker 内:
 *   php scripts/migrate_match_unit_phase_flags.php
 * ホストから:
 *   docker compose exec web php scripts/migrate_match_unit_phase_flags.php
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

$exists = $pdo->query("SHOW TABLES LIKE 't_match_unit_phase_flags'")->fetch(PDO::FETCH_NUM);
if ($exists) {
    echo "SKIP: t_match_unit_phase_flags already exists\n";
    echo "Match unit phase flags migration complete.\n";
    exit(0);
}

$sql = file_get_contents(__DIR__ . '/../sql/match_unit_phase_flags_migration.sql');
if ($sql === false || trim($sql) === '') {
    throw new RuntimeException('Could not read match_unit_phase_flags_migration.sql');
}
$pdo->exec($sql);
echo "CREATE: t_match_unit_phase_flags\n";
echo "Match unit phase flags migration complete.\n";
