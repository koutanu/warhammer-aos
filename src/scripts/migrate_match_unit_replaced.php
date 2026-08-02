<?php
/**
 * t_match_unit_status に is_replaced 列を冪等に追加する。
 *
 * Docker 内:
 *   php scripts/migrate_match_unit_replaced.php
 * ホストから:
 *   php scripts/migrate_match_unit_replaced.php
 *   （または）docker compose exec web php scripts/migrate_match_unit_replaced.php
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

$cols = $pdo->query('DESCRIBE t_match_unit_status')->fetchAll(PDO::FETCH_COLUMN);

if (in_array('is_replaced', $cols, true)) {
    echo "SKIP: is_replaced already exists\n";
    echo "Match unit replaced migration complete.\n";
    exit(0);
}

$after = in_array('is_summoned', $cols, true) ? ' AFTER is_summoned' : '';
$pdo->exec(
    'ALTER TABLE t_match_unit_status
     ADD COLUMN is_replaced TINYINT(1) NOT NULL DEFAULT 0' . $after
);
echo "ALTER: added is_replaced to t_match_unit_status\n";
echo "Match unit replaced migration complete.\n";
