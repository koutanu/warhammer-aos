<?php
/**
 * キーワード正規化マイグレーション
 * php scripts/migrate_keywords_master.php
 *
 * - 旧 m_core_abilities があれば m_keywords_master へリネーム、両方あれば旧表を削除
 * - m_unit_keywords 中間テーブル作成
 * - m_units.unit_keywords / faction_keywords からバックフィル
 */
require_once __DIR__ . '/../libs/core/Config.php';

$pdo = new PDO(
    DB_TYPE . ':host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
    DB_USER,
    DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

function tableExists(PDO $pdo, string $table): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.tables
         WHERE table_schema = DATABASE() AND table_name = ?'
    );
    $stmt->execute([$table]);
    return (int)$stmt->fetchColumn() > 0;
}

function columnExists(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.columns
         WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?'
    );
    $stmt->execute([$table, $column]);
    return (int)$stmt->fetchColumn() > 0;
}

function indexExists(PDO $pdo, string $table, string $indexName): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.statistics
         WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?'
    );
    $stmt->execute([$table, $indexName]);
    return (int)$stmt->fetchColumn() > 0;
}

function parseKeywordTokens(?string $raw): array
{
    if ($raw === null || trim($raw) === '') {
        return [];
    }
    $parts = preg_split('/,\s*/', $raw) ?: [];
    $out = [];
    foreach ($parts as $part) {
        $token = trim($part);
        if ($token === '') {
            continue;
        }
        $out[] = strtoupper($token);
    }
    return array_values(array_unique($out));
}

function findOrCreateKeyword(PDO $pdo, string $name, string $type): int
{
    $find = $pdo->prepare(
        'SELECT id FROM m_keywords_master WHERE name = ? AND keyword_type = ? LIMIT 1'
    );
    $find->execute([$name, $type]);
    $id = $find->fetchColumn();
    if ($id !== false) {
        return (int)$id;
    }

    $insert = $pdo->prepare(
        'INSERT INTO m_keywords_master (name, keyword_type, effect, sort_order) VALUES (?, ?, NULL, 0)'
    );
    $insert->execute([$name, $type]);
    return (int)$pdo->lastInsertId();
}

// --- 1. 旧 m_core_abilities の片付け ---
if (tableExists($pdo, 'm_core_abilities') && !tableExists($pdo, 'm_keywords_master')) {
    $pdo->exec('RENAME TABLE m_core_abilities TO m_keywords_master');
    echo "OK: RENAME TABLE m_core_abilities TO m_keywords_master\n";
} elseif (tableExists($pdo, 'm_core_abilities') && tableExists($pdo, 'm_keywords_master')) {
    $pdo->exec('DROP TABLE m_core_abilities');
    echo "OK: DROP TABLE m_core_abilities (m_keywords_master already exists)\n";
} elseif (tableExists($pdo, 'm_keywords_master')) {
    echo "Skip: m_keywords_master already exists\n";
} else {
    echo "Skip: m_core_abilities not found (will use CREATE TABLE IF NOT EXISTS)\n";
}

// --- 2. SQL ファイル（CREATE TABLE IF NOT EXISTS）---
$sqlFile = __DIR__ . '/../sql/keywords_master_migration.sql';
$statements = array_filter(array_map('trim', explode(';', file_get_contents($sqlFile))));
foreach ($statements as $sql) {
    if ($sql !== '') {
        $pdo->exec($sql);
        echo 'OK: ' . substr($sql, 0, 70) . "...\n";
    }
}

// --- 3. 既存 m_keywords_master のカラム正規化 ---
if (tableExists($pdo, 'm_keywords_master')) {
    if (columnExists($pdo, 'm_keywords_master', 'keyword') && !columnExists($pdo, 'm_keywords_master', 'name')) {
        $pdo->exec('ALTER TABLE m_keywords_master CHANGE COLUMN keyword name VARCHAR(255) NOT NULL');
        echo "OK: renamed keyword -> name on m_keywords_master\n";
    }
    if (!columnExists($pdo, 'm_keywords_master', 'keyword_type')) {
        $pdo->exec(
            "ALTER TABLE m_keywords_master
             ADD COLUMN keyword_type ENUM('unit','faction') NOT NULL DEFAULT 'unit' AFTER name"
        );
        echo "OK: added keyword_type to m_keywords_master\n";
    }
    if (!columnExists($pdo, 'm_keywords_master', 'sort_order')) {
        $pdo->exec(
            'ALTER TABLE m_keywords_master ADD COLUMN sort_order INT NOT NULL DEFAULT 0'
        );
        echo "OK: added sort_order to m_keywords_master\n";
    } elseif (columnExists($pdo, 'm_keywords_master', 'sort_order')) {
        $pdo->exec('UPDATE m_keywords_master SET sort_order = 0 WHERE sort_order IS NULL');
        $pdo->exec('ALTER TABLE m_keywords_master MODIFY COLUMN sort_order INT NOT NULL DEFAULT 0');
    }
    // 旧 UNIQUE(keyword) を除去してから複合 UNIQUE を付与
    if (indexExists($pdo, 'm_keywords_master', 'keyword')) {
        $pdo->exec('ALTER TABLE m_keywords_master DROP INDEX keyword');
        echo "OK: dropped legacy unique index keyword\n";
    }
    if (!indexExists($pdo, 'm_keywords_master', 'uq_m_keywords_master_name_type')) {
        $pdo->exec(
            'ALTER TABLE m_keywords_master ADD UNIQUE KEY uq_m_keywords_master_name_type (name, keyword_type)'
        );
        echo "OK: added unique index on m_keywords_master (name, keyword_type)\n";
    }
}

if (!indexExists($pdo, 'm_unit_keywords', 'uq_m_unit_keywords')) {
  try {
      $pdo->exec('CREATE UNIQUE INDEX uq_m_unit_keywords ON m_unit_keywords (unit_id, keyword_id)');
      echo "OK: CREATE UNIQUE INDEX uq_m_unit_keywords\n";
  } catch (Throwable $e) {
      echo "Skip unique index (may already exist via PRIMARY KEY): {$e->getMessage()}\n";
  }
}

// --- 4. バックフィル（m_unit_keywords が空のときのみ）---
$linkCount = (int)$pdo->query('SELECT COUNT(*) FROM m_unit_keywords')->fetchColumn();
if ($linkCount > 0) {
    echo "Skip backfill: m_unit_keywords already has {$linkCount} rows\n";
} elseif (!tableExists($pdo, 'm_units')) {
    echo "Skip backfill: m_units not found\n";
} else {
    $units = $pdo->query('SELECT id, unit_keywords, faction_keywords FROM m_units')->fetchAll(PDO::FETCH_ASSOC);
    $attach = $pdo->prepare(
        'INSERT IGNORE INTO m_unit_keywords (unit_id, keyword_id) VALUES (?, ?)'
    );
    $linked = 0;
    $created = 0;

    foreach ($units as $unit) {
        $unitId = (int)$unit['id'];
        $pairs = [
            ['unit', parseKeywordTokens($unit['unit_keywords'] ?? '')],
            ['faction', parseKeywordTokens($unit['faction_keywords'] ?? '')],
        ];
        foreach ($pairs as [$type, $tokens]) {
            foreach ($tokens as $name) {
                $before = (int)$pdo->query('SELECT COUNT(*) FROM m_keywords_master')->fetchColumn();
                $keywordId = findOrCreateKeyword($pdo, $name, $type);
                $after = (int)$pdo->query('SELECT COUNT(*) FROM m_keywords_master')->fetchColumn();
                if ($after > $before) {
                    $created++;
                }
                $attach->execute([$unitId, $keywordId]);
                if ($attach->rowCount() > 0) {
                    $linked++;
                }
            }
        }
    }
    echo "Backfill complete: {$linked} links created, {$created} new master rows\n";
}

echo "Migration complete.\n";
