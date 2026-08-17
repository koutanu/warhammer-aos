<?php
/**
 * t_matches に CP / 憤激レベル / 憤激ダイス列を冪等に追加する。
 *
 * Docker 内:
 *   php scripts/migrate_match_resources.php
 * ホストから:
 *   docker compose exec web php scripts/migrate_match_resources.php
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

$alterColumns = [
    'player_a_cp'          => "INT NOT NULL DEFAULT 0 COMMENT 'Player A コマンドポイント'",
    'player_b_cp'          => "INT NOT NULL DEFAULT 0 COMMENT 'Player B コマンドポイント'",
    'player_a_rage_level'  => "INT NOT NULL DEFAULT 0 COMMENT 'Player A 憤激レベル'",
    'player_b_rage_level'  => "INT NOT NULL DEFAULT 0 COMMENT 'Player B 憤激レベル'",
    'player_a_rage_dice'   => "INT NOT NULL DEFAULT 0 COMMENT 'Player A 憤激ダイス'",
    'player_b_rage_dice'   => "INT NOT NULL DEFAULT 0 COMMENT 'Player B 憤激ダイス'",
];

$existing = $pdo->query('DESCRIBE t_matches')->fetchAll(PDO::FETCH_COLUMN);
foreach ($alterColumns as $col => $def) {
    if (in_array($col, $existing, true)) {
        echo "SKIP: {$col} already exists on t_matches\n";
        continue;
    }
    $pdo->exec("ALTER TABLE t_matches ADD COLUMN `{$col}` {$def}");
    echo "ALTER: added {$col} to t_matches\n";
}

echo "Match resources migration complete.\n";
