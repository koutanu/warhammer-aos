<?php
/**
 * キーワードパラメータ対応マイグレーション
 * php scripts/migrate_keyword_params.php
 */
require_once __DIR__ . '/../libs/core/Config.php';
require_once __DIR__ . '/../libs/common/KeywordDisplay.php';

$pdo = new PDO(
    DB_TYPE . ':host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
    DB_USER,
    DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

function columnExists(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.columns
         WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?'
    );
    $stmt->execute([$table, $column]);
    return (int)$stmt->fetchColumn() > 0;
}

function findOrCreateBaseMaster(PDO $pdo, string $baseName, string $type, bool $acceptsParam): int
{
    $type = ($type === 'faction') ? 'faction' : 'unit';
    $find = $pdo->prepare(
        'SELECT id, accepts_param FROM m_keywords_master WHERE name = ? AND keyword_type = ? LIMIT 1'
    );
    $find->execute([$baseName, $type]);
    $row = $find->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $id = (int)$row['id'];
        if ($acceptsParam && !(int)$row['accepts_param']) {
            $pdo->prepare('UPDATE m_keywords_master SET accepts_param = 1 WHERE id = ?')->execute([$id]);
        }
        return $id;
    }

    $insert = $pdo->prepare(
        'INSERT INTO m_keywords_master (name, keyword_type, effect, sort_order, accepts_param)
         VALUES (?, ?, NULL, 0, ?)'
    );
    $insert->execute([$baseName, $type, $acceptsParam ? 1 : 0]);
    return (int)$pdo->lastInsertId();
}

function masterLinkCount(PDO $pdo, int $keywordId): int
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM m_unit_keywords WHERE keyword_id = ?');
    $stmt->execute([$keywordId]);
    return (int)$stmt->fetchColumn();
}

// --- Schema ---
$sqlFile = __DIR__ . '/../sql/keyword_param_migration.sql';
if (is_readable($sqlFile)) {
    foreach (array_filter(array_map('trim', explode(';', file_get_contents($sqlFile)))) as $sql) {
        if ($sql === '') {
            continue;
        }
        if (!columnExists($pdo, 'm_keywords_master', 'accepts_param')
            && str_contains($sql, 'm_keywords_master')
        ) {
            $pdo->exec($sql);
            echo 'OK: ' . substr($sql, 0, 70) . "...\n";
            continue;
        }
        if (!columnExists($pdo, 'm_unit_keywords', 'param_value')
            && str_contains($sql, 'm_unit_keywords')
        ) {
            $pdo->exec($sql);
            echo 'OK: ' . substr($sql, 0, 70) . "...\n";
        }
    }
}

if (!columnExists($pdo, 'm_keywords_master', 'accepts_param')) {
    $pdo->exec('ALTER TABLE m_keywords_master ADD COLUMN accepts_param TINYINT(1) NOT NULL DEFAULT 0 AFTER sort_order');
    echo "OK: added accepts_param to m_keywords_master\n";
}
if (!columnExists($pdo, 'm_unit_keywords', 'param_value')) {
    $pdo->exec('ALTER TABLE m_unit_keywords ADD COLUMN param_value VARCHAR(20) NULL DEFAULT NULL AFTER keyword_id');
    echo "OK: added param_value to m_unit_keywords\n";
}

// --- accepts_param on known base names ---
$markParam = $pdo->prepare(
    'UPDATE m_keywords_master SET accepts_param = 1
     WHERE keyword_type = \'unit\' AND name = ?'
);
foreach (KeywordDisplay::PARAM_BASE_NAMES as $baseName) {
    $markParam->execute([$baseName]);
    echo "Marked accepts_param: {$baseName}\n";
}

// --- Split master rows whose name contains (...) ---
$rows = $pdo->query(
    "SELECT id, name, keyword_type FROM m_keywords_master WHERE name LIKE '%(%'"
)->fetchAll(PDO::FETCH_ASSOC);

$migrated = 0;
$deleted = 0;
foreach ($rows as $row) {
    $oldId = (int)$row['id'];
    [$baseName, $param] = KeywordDisplay::parseToken($row['name']);
    if ($baseName === '' || $param === null) {
        continue;
    }

    $accepts = KeywordDisplay::isParamCapableName($baseName);
    $newId = findOrCreateBaseMaster($pdo, $baseName, $row['keyword_type'], $accepts);

    $links = $pdo->prepare('SELECT unit_id FROM m_unit_keywords WHERE keyword_id = ?');
    $links->execute([$oldId]);
    $unitIds = $links->fetchAll(PDO::FETCH_COLUMN);

    $upd = $pdo->prepare(
        'UPDATE m_unit_keywords SET keyword_id = ?, param_value = ?
         WHERE unit_id = ? AND keyword_id = ?'
    );
    $ins = $pdo->prepare(
        'INSERT IGNORE INTO m_unit_keywords (unit_id, keyword_id, param_value) VALUES (?, ?, ?)'
    );

    foreach ($unitIds as $unitId) {
        $unitId = (int)$unitId;
        $upd->execute([$newId, $param, $unitId, $oldId]);
        if ($upd->rowCount() === 0) {
            $ins->execute([$unitId, $newId, $param]);
        }
        $migrated++;
    }

    if ($oldId !== $newId && masterLinkCount($pdo, $oldId) === 0) {
        $pdo->prepare('DELETE FROM m_keywords_master WHERE id = ?')->execute([$oldId]);
        $deleted++;
        echo "Removed old master id={$oldId} name={$row['name']}\n";
    }
}

echo "Split masters: {$migrated} links updated, {$deleted} obsolete masters removed\n";
echo "Migration complete.\n";
