<?php
/**
 * Wahapedia AoS4 CSV からユニット・武器・能力をインポート
 *
 * Usage:
 *   php scripts/import_wahapedia.php [--download] [--faction=SE,ST]
 *
 * Data source: https://wahapedia.ru/aos4/the-rules/data-export/
 * (powered by Wahapedia)
 */
require_once __DIR__ . '/../libs/core/Config.php';

const WAHAPEDIA_BASE = 'https://wahapedia.ru/aos4/';
const DATA_DIR = __DIR__ . '/../data/wahapedia';

const FACTION_MAP = [
    'SE' => 1,  // Stormcast Eternals
    'ST' => 10, // Skaven
];

const FACTION_KEYWORDS = [
    'SE' => 'STORMCAST ETERNALS',
    'ST' => 'SKAVEN',
];

/** 手動シード名 → Wahapedia 英名（ロスター参照の統合用） */
const LEGACY_UNIT_ALIASES = [
    'リベレイター' => 'Liberators',
    'プロセキューター' => 'Prosecutors',
    'ヴァンキッシャー' => 'Vanquishers',
    'アナイアレイター' => 'Annihilators',
];

const EXCLUDE_ROLES = [
];

/** ファクションテレイン(陣営地形)の role 名。is_terrain フラグの判定に使う。 */
const TERRAIN_ROLE = 'Faction Terrain';

/** 顕現(マニフェステーション/エンドレススペル)の role 名。is_manifestation 判定に使う。 */
const MANIFESTATION_ROLES = [
    'Manifestation',
    'Endless Spell',
];

const CSV_FILES = [
    'Factions.csv',
    'Warscrolls.csv',
    'Warscrolls_abilities.csv',
    'Warscrolls_weapons.csv',
];

$opts = getopt('', ['download', 'faction::']);
$download = isset($opts['download']);
$factionFilter = isset($opts['faction'])
    ? array_map('trim', explode(',', strtoupper($opts['faction'])))
    : array_keys(FACTION_MAP);

$pdo = new PDO(
    DB_TYPE . ':host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
    DB_USER,
    DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

ensureSchema($pdo);
if ($download || !csvFilesPresent()) {
    downloadCsvFiles();
}

$warscrolls = loadCsv(DATA_DIR . '/Warscrolls.csv');
$abilitiesByScroll = groupBy(loadCsv(DATA_DIR . '/Warscrolls_abilities.csv'), 'warscroll_id');
$weaponsByScroll = groupBy(loadCsv(DATA_DIR . '/Warscrolls_weapons.csv'), 'warscroll_id');

$regimentOptions = loadRegimentOptionMap($pdo);

foreach ($factionFilter as $wahapediaFactionId) {
    if (!isset(FACTION_MAP[$wahapediaFactionId])) {
        fwrite(STDERR, "Unknown faction code: {$wahapediaFactionId}\n");
        continue;
    }
    importFaction(
        $pdo,
        $wahapediaFactionId,
        FACTION_MAP[$wahapediaFactionId],
        $warscrolls,
        $abilitiesByScroll,
        $weaponsByScroll,
        $regimentOptions
    );
}

echo "Import complete.\n";

// -----------------------------------------------------------------------------

function ensureSchema(PDO $pdo): void
{
    $cols = $pdo->query('DESCRIBE m_units')->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('wahapedia_id', $cols, true)) {
        $pdo->exec('ALTER TABLE m_units ADD COLUMN wahapedia_id VARCHAR(16) NULL DEFAULT NULL AFTER faction_id');
        echo "ALTER: added wahapedia_id to m_units\n";
    }
    // keywords を unit_keywords / faction_keywords に分割（移行）
    if (in_array('keywords', $cols, true) && !in_array('unit_keywords', $cols, true)) {
        $pdo->exec('ALTER TABLE m_units CHANGE COLUMN keywords unit_keywords VARCHAR(555) NULL DEFAULT NULL');
        echo "ALTER: renamed keywords -> unit_keywords\n";
        $cols[] = 'unit_keywords';
    }
    if (!in_array('faction_keywords', $cols, true)) {
        if (in_array('keywords2', $cols, true)) {
            $pdo->exec('ALTER TABLE m_units CHANGE COLUMN keywords2 faction_keywords VARCHAR(555) NULL DEFAULT NULL');
            echo "ALTER: renamed keywords2 -> faction_keywords\n";
        } else {
            $pdo->exec('ALTER TABLE m_units ADD COLUMN faction_keywords VARCHAR(555) NULL DEFAULT NULL AFTER unit_keywords');
            echo "ALTER: added faction_keywords to m_units\n";
        }
        $cols[] = 'faction_keywords';
    }
    if (!in_array('is_hero', $cols, true)) {
        $pdo->exec('ALTER TABLE m_units ADD COLUMN is_hero TINYINT(1) NOT NULL DEFAULT 0');
        echo "ALTER: added is_hero to m_units\n";
    }
    if (!in_array('can_reinforce', $cols, true)) {
        $pdo->exec('ALTER TABLE m_units ADD COLUMN can_reinforce TINYINT(1) NOT NULL DEFAULT 0');
        echo "ALTER: added can_reinforce to m_units\n";
    }
    if (!in_array('is_terrain', $cols, true)) {
        $pdo->exec('ALTER TABLE m_units ADD COLUMN is_terrain TINYINT(1) NOT NULL DEFAULT 0');
        echo "ALTER: added is_terrain to m_units\n";
    }
    if (!in_array('is_manifestation', $cols, true)) {
        $pdo->exec('ALTER TABLE m_units ADD COLUMN is_manifestation TINYINT(1) NOT NULL DEFAULT 0');
        echo "ALTER: added is_manifestation to m_units\n";
    }
    $indexes = $pdo->query("SHOW INDEX FROM m_units WHERE Key_name = 'uq_m_units_wahapedia_id'")->fetchAll();
    if (empty($indexes)) {
        $pdo->exec('CREATE UNIQUE INDEX uq_m_units_wahapedia_id ON m_units (wahapedia_id)');
        echo "INDEX: uq_m_units_wahapedia_id\n";
    }

    // アビリティのアイコン分類(icon_type)。発動タイミング種別(ability_type)とは別軸。
    $abilityCols = $pdo->query('DESCRIBE m_ability_master')->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('icon_type', $abilityCols, true)) {
        $pdo->exec('ALTER TABLE m_ability_master ADD COLUMN icon_type VARCHAR(20) NULL DEFAULT NULL AFTER ability_type');
        echo "ALTER: added icon_type to m_ability_master\n";
    }

    // 発動条件: CSV condition の英語原文(en)と手動翻訳(ja)を保持する。
    if (!in_array('trigger_condition_en', $abilityCols, true)) {
        $pdo->exec('ALTER TABLE m_ability_master ADD COLUMN trigger_condition_en TEXT NULL DEFAULT NULL AFTER ability_type');
        echo "ALTER: added trigger_condition_en to m_ability_master\n";
    }
    if (!in_array('trigger_condition_ja', $abilityCols, true)) {
        $pdo->exec('ALTER TABLE m_ability_master ADD COLUMN trigger_condition_ja TEXT NULL DEFAULT NULL AFTER trigger_condition_en');
        echo "ALTER: added trigger_condition_ja to m_ability_master\n";
    }
}

function csvFilesPresent(): bool
{
    foreach (CSV_FILES as $file) {
        if (!is_file(DATA_DIR . '/' . $file)) {
            return false;
        }
    }
    return true;
}

function downloadCsvFiles(): void
{
    if (!is_dir(DATA_DIR)) {
        mkdir(DATA_DIR, 0755, true);
    }
    foreach (CSV_FILES as $file) {
        $url = WAHAPEDIA_BASE . $file;
        $dest = DATA_DIR . '/' . $file;
        echo "Downloading {$url} ...\n";
        $ctx = stream_context_create(['http' => ['timeout' => 120]]);
        $data = @file_get_contents($url, false, $ctx);
        if ($data === false) {
            throw new RuntimeException("Failed to download {$url}");
        }
        file_put_contents($dest, $data);
        echo "  -> " . strlen($data) . " bytes\n";
    }
}

function loadCsv(string $path): array
{
    if (!is_file($path)) {
        throw new RuntimeException("CSV not found: {$path}");
    }
    $rows = [];
    $fh = fopen($path, 'r');
    $header = null;
    while (($line = fgets($fh)) !== false) {
        $line = preg_replace('/^\xEF\xBB\xBF/', '', trim($line));
        if ($line === '') {
            continue;
        }
        $cols = str_getcsv($line, '|', '"', '\\');
        if ($header === null) {
            $header = $cols;
            continue;
        }
        $row = [];
        foreach ($header as $i => $key) {
            $row[$key] = $cols[$i] ?? '';
        }
        $rows[] = $row;
    }
    fclose($fh);
    return $rows;
}

function groupBy(array $rows, string $key): array
{
    $grouped = [];
    foreach ($rows as $row) {
        $id = $row[$key] ?? '';
        if ($id === '') {
            continue;
        }
        $grouped[$id][] = $row;
    }
    return $grouped;
}

function loadRegimentOptionMap(PDO $pdo): array
{
    // option_name は表示専用（日本語可）なので、シードの照合には英語の安定キー option_code を使う。
    $cols = $pdo->query('DESCRIBE m_regiment_options')->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('option_code', $cols, true)) {
        $pdo->exec('ALTER TABLE m_regiment_options ADD COLUMN option_code VARCHAR(255) NULL AFTER option_name');
        $pdo->exec("UPDATE m_regiment_options SET option_code = UPPER(option_name) WHERE option_code IS NULL OR option_code = ''");
        echo "ALTER: added option_code to m_regiment_options\n";
    }

    $map = [];
    foreach ($pdo->query('SELECT id, option_name, option_code FROM m_regiment_options')->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $key = $row['option_code'] !== null && $row['option_code'] !== '' ? $row['option_code'] : $row['option_name'];
        $map[strtoupper((string)$key)] = (int)$row['id'];
    }
    return $map;
}

function importFaction(
    PDO $pdo,
    string $wahapediaFactionId,
    int $dbFactionId,
    array $warscrolls,
    array $abilitiesByScroll,
    array $weaponsByScroll,
    array $regimentOptions
): void {
    $faction = $pdo->prepare('SELECT grand_alliance FROM m_factions WHERE id = ?');
    $faction->execute([$dbFactionId]);
    $grandAlliance = strtoupper((string)$faction->fetchColumn());

    $targets = array_values(array_filter($warscrolls, function (array $row) use ($wahapediaFactionId) {
        if (($row['faction_id'] ?? '') !== $wahapediaFactionId) {
            return false;
        }
        if (($row['virtual'] ?? '') === 'true') {
            return false;
        }
        $role = $row['role'] ?? '';
        return !in_array($role, EXCLUDE_ROLES, true);
    }));

    echo "\n=== {$wahapediaFactionId} (faction_id={$dbFactionId}): " . count($targets) . " warscrolls ===\n";

    $pdo->beginTransaction();
    try {
        $existingIds = $pdo->prepare('SELECT id, wahapedia_id FROM m_units WHERE faction_id = ?');
        $existingIds->execute([$dbFactionId]);
        $byWahapediaId = [];
        foreach ($existingIds->fetchAll(PDO::FETCH_ASSOC) as $row) {
            if (!empty($row['wahapedia_id'])) {
                $byWahapediaId[$row['wahapedia_id']] = (int)$row['id'];
            }
        }

        // 既存の手動データを英名で紐付け（ロスター参照を維持）
        linkLegacyUnits($pdo, $dbFactionId, $targets, $byWahapediaId);

        $importedWahapediaIds = [];
        $inserted = 0;
        $updated = 0;

        foreach ($targets as $ws) {
            $wahapediaId = $ws['id'];
            $importedWahapediaIds[] = $wahapediaId;

            $unitKeywords = buildUnitKeywords($ws);
            $factionKeywords = buildFactionKeywords($ws, $wahapediaFactionId, $grandAlliance);
            // 地形・顕現は role 由来で安定判定できるため INSERT/UPDATE 両方で設定する。
            // ロスター編成の選択肢には出さないよう is_hidden=1 を併用する。
            $isTerrain = ($ws['role'] ?? '') === TERRAIN_ROLE ? 1 : 0;
            $isManifestation = in_array($ws['role'] ?? '', MANIFESTATION_ROLES, true) ? 1 : 0;
            $isSpecial = $isTerrain || $isManifestation;
            $unitData = [
                'faction_id'   => $dbFactionId,
                'wahapedia_id' => $wahapediaId,
                'name'         => $ws['name'],
                'movement'     => parseStatInt($ws['Move'] ?? ''),
                'wounds'       => parseStatInt($ws['Health'] ?? ''),
                'save'         => parseSave($ws['Save'] ?? ''),
                'control'      => parseStatInt($ws['Control'] ?? ''),
                'points'       => parseStatInt($ws['Cost'] ?? '') ?? 0,
                'unit_size'    => parseStatInt($ws['UnitSize'] ?? ''),
                'base_size'    => null,
                'unit_keywords'    => $unitKeywords,
                'faction_keywords' => $factionKeywords,
                'image'        => null,
                'is_terrain'   => $isTerrain,
                'is_manifestation' => $isManifestation,
            ];

            if (isset($byWahapediaId[$wahapediaId])) {
                $unitId = $byWahapediaId[$wahapediaId];
                $sql = 'UPDATE m_units SET faction_id=:faction_id, name=:name, movement=:movement, wounds=:wounds,
                        save=:save, control=:control, points=:points, unit_size=:unit_size, base_size=:base_size,
                        unit_keywords=:unit_keywords, faction_keywords=:faction_keywords, image=:image, is_terrain=:is_terrain, is_manifestation=:is_manifestation, wahapedia_id=:wahapedia_id WHERE id=:id';
                $stmt = $pdo->prepare($sql);
                $stmt->execute($unitData + ['id' => $unitId]);
                $updated++;
            } else {
                // 新規ユニットのみ HERO 判定と増強可否の初期値を付与する
                // （既存ユニットは管理画面での手動調整を尊重して UPDATE では触らない）
                $isHero = str_contains(strtoupper($unitKeywords), 'HERO') ? 1 : 0;
                $insertData = $unitData + [
                    'is_hero'       => $isSpecial ? 0 : $isHero,
                    'can_reinforce' => (!$isSpecial && !$isHero && (int)($unitData['unit_size'] ?? 1) > 1) ? 1 : 0,
                    'is_hidden'     => $isSpecial ? 1 : 0,
                ];
                $sql = 'INSERT INTO m_units (faction_id, wahapedia_id, name, movement, wounds, save, control, points, unit_size, base_size, unit_keywords, faction_keywords, image, is_terrain, is_manifestation, is_hero, can_reinforce, is_hidden)
                        VALUES (:faction_id, :wahapedia_id, :name, :movement, :wounds, :save, :control, :points, :unit_size, :base_size, :unit_keywords, :faction_keywords, :image, :is_terrain, :is_manifestation, :is_hero, :can_reinforce, :is_hidden)';
                $stmt = $pdo->prepare($sql);
                $stmt->execute($insertData);
                $unitId = (int)$pdo->lastInsertId();
                $byWahapediaId[$wahapediaId] = $unitId;
                $inserted++;
            }

            replaceUnitChildren(
                $pdo,
                $unitId,
                $ws['name'],
                $abilitiesByScroll[$wahapediaId] ?? [],
                $weaponsByScroll[$wahapediaId] ?? []
            );
        }

        purgeStaleFactionUnits($pdo, $dbFactionId, $importedWahapediaIds);
        remapLegacyRosterUnits($pdo, $dbFactionId, $byWahapediaId);
        seedRegimentEligibility($pdo, $dbFactionId, $wahapediaFactionId, $regimentOptions);

        $pdo->commit();
        echo "  inserted={$inserted}, updated={$updated}\n";
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function linkLegacyUnits(PDO $pdo, int $dbFactionId, array $targets, array &$byWahapediaId): void
{
    $nameToWahapedia = [];
    foreach ($targets as $ws) {
        $nameToWahapedia[strtolower($ws['name'])] = $ws['id'];
    }

    $legacy = $pdo->prepare('SELECT id, name, wahapedia_id FROM m_units WHERE faction_id = ? AND (wahapedia_id IS NULL OR wahapedia_id = "")');
    $legacy->execute([$dbFactionId]);
    foreach ($legacy->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $normalized = strtolower(preg_replace('/\s*\([^)]*\)\s*/', '', $row['name']));
        $wahapediaId = $nameToWahapedia[$normalized] ?? $nameToWahapedia[strtolower($row['name'])] ?? null;
        if ($wahapediaId) {
            $pdo->prepare('UPDATE m_units SET wahapedia_id = ? WHERE id = ?')->execute([$wahapediaId, $row['id']]);
            $byWahapediaId[$wahapediaId] = (int)$row['id'];
        }
    }
}

function replaceUnitChildren(PDO $pdo, int $unitId, string $unitName, array $abilities, array $weapons): void
{
    $pdo->prepare('DELETE FROM m_unit_weapons WHERE unit_id = ?')->execute([$unitId]);
    $pdo->prepare('DELETE FROM m_unit_abilities WHERE unit_id = ?')->execute([$unitId]);

    foreach ($abilities as $ability) {
        [$abilityType, $triggerPhase, $triggerTurn, $iconType] = mapAbilityFields($ability);
        $name = trim($ability['name'] ?? '');
        if ($name === '') {
            continue;
        }
        $effect = htmlToText($ability['description'] ?? '');
        $flavor = trim($ability['legend'] ?? '');
        $condition = trim($ability['condition'] ?? '');

        $abilityId = getOrCreateAbilityMaster(
            $pdo,
            $name,
            $effect,
            $triggerPhase,
            $triggerTurn,
            $abilityType,
            $flavor,
            $unitName,
            $iconType,
            $condition
        );
        $pdo->prepare('INSERT IGNORE INTO m_unit_abilities (unit_id, ability_id) VALUES (?, ?)')
            ->execute([$unitId, $abilityId]);
    }

    foreach ($weapons as $weapon) {
        $type = strtolower($weapon['type'] ?? 'melee');
        if ($type === 'ranged') {
            $type = 'ranged';
        } elseif ($type === 'melee') {
            $type = 'melee';
        }
        $stmt = $pdo->prepare(
            'INSERT INTO m_unit_weapons (unit_id, name, type, rng, atk, hit, wnd, rnd, dmg, abilities, memo)
             VALUES (:unit_id, :name, :type, :rng, :atk, :hit, :wnd, :rnd, :dmg, :abilities, :memo)'
        );
        $stmt->execute([
            'unit_id'   => $unitId,
            'name'      => mb_substr($weapon['name'] ?? '', 0, 255),
            'type'      => $type,
            'rng'       => parseRangeInches($weapon['Rng'] ?? ''),
            'atk'       => parseStatInt($weapon['Atk'] ?? ''),
            'hit'       => parseDice($weapon['Hit'] ?? ''),
            'wnd'       => parseDice($weapon['Wnd'] ?? ''),
            'rnd'       => parseDice($weapon['Rnd'] ?? ''),
            'dmg'       => mb_substr($weapon['Dmg'] ?? '', 0, 50) ?: null,
            'abilities' => mb_substr($weapon['abilities'] ?? '', 0, 255) ?: null,
            'memo'      => null,
        ]);
    }
}

function remapLegacyRosterUnits(PDO $pdo, int $dbFactionId, array $byWahapediaId): void
{
    $nameToId = [];
    $stmt = $pdo->prepare('SELECT id, name, wahapedia_id FROM m_units WHERE faction_id = ?');
    $stmt->execute([$dbFactionId]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $nameToId[$row['name']] = (int)$row['id'];
    }

    $legacy = $pdo->prepare(
        'SELECT id, name FROM m_units WHERE faction_id = ? AND (wahapedia_id IS NULL OR wahapedia_id = "")'
    );
    $legacy->execute([$dbFactionId]);

    foreach ($legacy->fetchAll(PDO::FETCH_ASSOC) as $old) {
        $englishName = LEGACY_UNIT_ALIASES[$old['name']] ?? null;
        if ($englishName === null) {
            $stripped = preg_replace('/\s*\([^)]*\)\s*/', '', $old['name']);
            $englishName = LEGACY_UNIT_ALIASES[$stripped] ?? null;
        }
        if ($englishName === null || !isset($nameToId[$englishName])) {
            continue;
        }

        $oldId = (int)$old['id'];
        $newId = $nameToId[$englishName];
        if ($oldId === $newId) {
            continue;
        }

        $pdo->prepare('UPDATE t_roster_regiment_units SET unit_id = ? WHERE unit_id = ?')
            ->execute([$newId, $oldId]);
        $pdo->prepare('UPDATE t_roster_regiments SET hero_unit_id = ? WHERE hero_unit_id = ?')
            ->execute([$newId, $oldId]);

        $pdo->prepare('DELETE FROM m_unit_weapons WHERE unit_id = ?')->execute([$oldId]);
        $pdo->prepare('DELETE FROM m_unit_abilities WHERE unit_id = ?')->execute([$oldId]);
        $pdo->prepare('DELETE FROM t_unit_regiment_eligibility WHERE unit_id = ?')->execute([$oldId]);
        $pdo->prepare('DELETE FROM t_hero_regiment_options WHERE hero_unit_id = ?')->execute([$oldId]);
        $pdo->prepare('DELETE FROM m_units WHERE id = ?')->execute([$oldId]);
        echo "  remapped legacy roster unit {$old['name']} (id={$oldId}) -> {$englishName} (id={$newId})\n";
    }
}

function purgeStaleFactionUnits(PDO $pdo, int $dbFactionId, array $keepWahapediaIds): void
{
    $inUse = [];
    foreach (['t_roster_regiments' => 'hero_unit_id', 't_roster_regiment_units' => 'unit_id'] as $table => $col) {
        foreach ($pdo->query("SELECT DISTINCT {$col} AS uid FROM {$table}")->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $inUse[(int)$row['uid']] = true;
        }
    }

    $stmt = $pdo->prepare('SELECT id, wahapedia_id, name FROM m_units WHERE faction_id = ?');
    $stmt->execute([$dbFactionId]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $wid = $row['wahapedia_id'] ?? '';
        if ($wid !== '' && in_array($wid, $keepWahapediaIds, true)) {
            continue;
        }
        if (isset($inUse[(int)$row['id']])) {
            echo "  keep legacy unit in use: {$row['name']} (id={$row['id']})\n";
            continue;
        }
        $unitId = (int)$row['id'];
        $pdo->prepare('DELETE FROM m_unit_weapons WHERE unit_id = ?')->execute([$unitId]);
        $pdo->prepare('DELETE FROM m_unit_abilities WHERE unit_id = ?')->execute([$unitId]);
        $pdo->prepare('DELETE FROM t_unit_regiment_eligibility WHERE unit_id = ?')->execute([$unitId]);
        $pdo->prepare('DELETE FROM t_hero_regiment_options WHERE hero_unit_id = ?')->execute([$unitId]);
        $pdo->prepare('DELETE FROM m_units WHERE id = ?')->execute([$unitId]);
        echo "  removed stale unit: {$row['name']} (id={$unitId})\n";
    }
}

function seedRegimentEligibility(PDO $pdo, int $dbFactionId, string $wahapediaFactionId, array $regimentOptions): void
{
    $generalId = $regimentOptions['GENERAL REGIMENT'] ?? null;
    if (!$generalId) {
        return;
    }

    $units = $pdo->prepare('SELECT id, unit_keywords, faction_keywords, is_hero, is_terrain, is_manifestation FROM m_units WHERE faction_id = ?');
    $units->execute([$dbFactionId]);
    $allUnits = $units->fetchAll(PDO::FETCH_ASSOC);

    $pdo->prepare('DELETE FROM t_unit_regiment_eligibility WHERE unit_id IN (SELECT id FROM m_units WHERE faction_id = ?)')
        ->execute([$dbFactionId]);
    $pdo->prepare('DELETE FROM t_hero_regiment_options WHERE hero_unit_id IN (SELECT id FROM m_units WHERE faction_id = ?)')
        ->execute([$dbFactionId]);

    $insertElig = $pdo->prepare('INSERT IGNORE INTO t_unit_regiment_eligibility (unit_id, option_id) VALUES (?, ?)');
    $insertHero = $pdo->prepare('INSERT IGNORE INTO t_hero_regiment_options (hero_unit_id, option_id, max_limit) VALUES (?, ?, ?)');

    foreach ($allUnits as $unit) {
        // 地形(陣営地形)・顕現は連隊に編成されないため、適格/HERO枠を作らない。
        if (!empty($unit['is_terrain']) || !empty($unit['is_manifestation'])) {
            continue;
        }
        $unitKeywords = strtoupper($unit['unit_keywords'] ?? '');
        $factionKeywords = strtoupper($unit['faction_keywords'] ?? '');
        $isHero = isset($unit['is_hero'])
            ? (int)$unit['is_hero'] === 1
            : str_contains($unitKeywords, 'HERO');
        $unitId = (int)$unit['id'];

        if ($isHero) {
            $insertHero->execute([$unitId, $generalId, 0]);
        } else {
            $insertElig->execute([$unitId, $generalId]);
        }

        if ($wahapediaFactionId !== 'SE') {
            continue;
        }

        $stormcastOptions = [
            'STORMCAST EXEMPLAR' => 1,
            'WARRIOR CHAMBER'    => 4,
            'RUINATION CHAMBER'  => 3,
            'GRYPH-HOUNDS'       => 2,
        ];
        foreach ($stormcastOptions as $keyword => $limit) {
            if (!str_contains($factionKeywords, $keyword)) {
                continue;
            }
            $optionId = $regimentOptions[$keyword] ?? null;
            if (!$optionId) {
                continue;
            }
            if ($isHero) {
                $insertHero->execute([$unitId, $optionId, $limit]);
            } else {
                $insertElig->execute([$unitId, $optionId]);
            }
        }
    }
}

/**
 * ユニット自身のルール系キーワード（role 由来: HERO / INFANTRY など）を組み立てる。
 */
function buildUnitKeywords(array $ws): string
{
    $parts = [];
    $role = $ws['role'] ?? '';
    foreach (preg_split('/\s+/', $role) as $token) {
        $token = trim($token);
        if ($token !== '') {
            $parts[] = strtoupper($token);
        }
    }

    $parts = array_values(array_unique(array_filter($parts)));
    return implode(',', $parts);
}

/**
 * 所属（ファクション）キーワードを組み立てる。
 * 連隊のチェンバー/クラン等（regiment_options 由来）の判別キーワードのみを返す。
 * 大同盟・軍勢キーワードは取得時に m_factions（grand_alliance / name_en）から
 * 動的に結合するため、ここでは保持しない。
 */
function buildFactionKeywords(array $ws, string $wahapediaFactionId, string $grandAlliance): string
{
    $parts = [];

    $regiment = $ws['regiment_options'] ?? '';
    foreach (preg_split('/,\s*/', $regiment) as $chunk) {
        $chunk = trim($chunk);
        if ($chunk === '') {
            continue;
        }
        if (preg_match_all('/%([^%]+)%/', $chunk, $matches)) {
            foreach ($matches[1] as $m) {
                $m = preg_replace('/<[^>]+>/', '', $m);
                $parts[] = strtoupper(trim($m));
            }
        } else {
            $chunk = preg_replace('/\d+-1\s+/', '', $chunk);
            $chunk = preg_replace('/\bAny\b/i', '', $chunk);
            $parts[] = strtoupper(trim($chunk));
        }
    }

    $parts = array_values(array_unique(array_filter($parts)));
    return implode(',', $parts);
}

/**
 * Wahapedia の regiment_options 原文を可読テキスト（改行区切り）へ整形する。
 *
 * 入力例: "0-1 %000012010<b>Gryph-hounds</b>%, Any %000012110Warrior Chamber%"
 * 出力例: "0-1 Gryph-hounds\nAny Warrior Chamber"
 *
 * % 記法（連隊編成ルール）を含まない場合（=分類語のみの非HERO）は null を返す。
 */
function formatRegimentOptions(string $raw): ?string
{
    $raw = trim($raw);
    if ($raw === '' || strpos($raw, '%') === false) {
        return null;
    }

    if (!preg_match_all('/([^,%]*)%\d+(.*?)%/', $raw, $matches, PREG_SET_ORDER)) {
        return null;
    }

    $lines = [];
    foreach ($matches as $m) {
        $qty = trim($m[1]);
        $name = trim(strip_tags($m[2]));
        $name = html_entity_decode($name, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if ($name === '') {
            continue;
        }
        $lines[] = $qty !== '' ? ($qty . ' ' . $name) : $name;
    }

    return $lines ? implode("\n", $lines) : null;
}

function getOrCreateAbilityMaster(
    PDO $pdo,
    string $name,
    string $effect,
    string $triggerPhase,
    string $triggerTurn,
    string $abilityType,
    string $flavor,
    string $unitName,
    string $iconType = '',
    string $condition = ''
): int {
    $find = $pdo->prepare('SELECT id, effect, icon_type, trigger_condition_en FROM m_ability_master WHERE name = ? LIMIT 1');
    $find->execute([$name]);
    $existing = $find->fetch(PDO::FETCH_ASSOC);
    if ($existing && normalizeText($existing['effect']) === normalizeText($effect)) {
        backfillAbilityIconType($pdo, (int)$existing['id'], $existing['icon_type'] ?? null, $iconType);
        backfillAbilityCondition($pdo, (int)$existing['id'], $existing['trigger_condition_en'] ?? null, $condition);
        return (int)$existing['id'];
    }

    $storedName = $name;
    if ($existing) {
        $suffix = ' — ' . $unitName;
        $storedName = mb_substr($name, 0, max(1, 255 - mb_strlen($suffix))) . $suffix;
        $find->execute([$storedName]);
        $existing = $find->fetch(PDO::FETCH_ASSOC);
        if ($existing && normalizeText($existing['effect']) === normalizeText($effect)) {
            backfillAbilityIconType($pdo, (int)$existing['id'], $existing['icon_type'] ?? null, $iconType);
            backfillAbilityCondition($pdo, (int)$existing['id'], $existing['trigger_condition_en'] ?? null, $condition);
            return (int)$existing['id'];
        }
    }

    $stmt = $pdo->prepare(
        'INSERT INTO m_ability_master (name, trigger_phase, trigger_turn, ability_type, trigger_condition_en, icon_type, effect, flavor_text)
         VALUES (:name, :trigger_phase, :trigger_turn, :ability_type, :trigger_condition_en, :icon_type, :effect, :flavor_text)'
    );
    $stmt->execute([
        'name'                 => mb_substr($storedName, 0, 255),
        'trigger_phase'        => mb_substr($triggerPhase, 0, 100),
        'trigger_turn'         => mb_substr($triggerTurn, 0, 100),
        'ability_type'         => mb_substr($abilityType, 0, 100),
        'trigger_condition_en' => $condition !== '' ? $condition : null,
        'icon_type'            => $iconType !== '' ? mb_substr($iconType, 0, 20) : null,
        'effect'               => $effect,
        'flavor_text'          => $flavor !== '' ? $flavor : null,
    ]);
    return (int)$pdo->lastInsertId();
}

/** 既存アビリティ行の icon_type が未設定なら CSV 由来カテゴリで補完する */
function backfillAbilityIconType(PDO $pdo, int $abilityId, ?string $current, string $iconType): void
{
    if ($iconType === '' || ($current !== null && $current !== '')) {
        return;
    }
    $pdo->prepare('UPDATE m_ability_master SET icon_type = ? WHERE id = ?')
        ->execute([mb_substr($iconType, 0, 20), $abilityId]);
}

/** 既存アビリティ行の trigger_condition_en が未設定なら CSV 原文で補完する（ja は触らない） */
function backfillAbilityCondition(PDO $pdo, int $abilityId, ?string $current, string $condition): void
{
    if ($condition === '' || ($current !== null && $current !== '')) {
        return;
    }
    $pdo->prepare('UPDATE m_ability_master SET trigger_condition_en = ? WHERE id = ?')
        ->execute([$condition, $abilityId]);
}

function normalizeText(string $text): string
{
    return preg_replace('/\s+/', ' ', trim($text));
}

function mapAbilityFields(array $row): array
{
    $condition = trim($row['condition'] ?? '');
    $phase = trim($row['ability_phase'] ?? '');
    $type = trim($row['ability_type'] ?? '');

    $abilityType = $type !== '' ? $type : 'Special';
    // アイコン分類は CSV 由来のカテゴリ(Offensive/Damage/...)を保持する。
    // ability_type は下で Passive / Once Per に上書きされ得るため別変数に退避。
    $iconType = $abilityType;
    $triggerPhase = $phase !== '' ? $phase : 'Any Phase';
    $triggerTurn = 'Any Turn';

    if (stripos($condition, 'Passive') !== false) {
        $abilityType = 'Passive';
    } elseif (preg_match('/Once Per [^,]+(?:\([^)]+\))?[^,]*/', $condition, $m)) {
        $abilityType = trim($m[0]);
    }

    if (preg_match('/\b(Your|End of Your)\b/i', $condition)) {
        $triggerTurn = 'Your Turn';
    } elseif (preg_match('/\b(Enemy|Opponent)\b/i', $condition)) {
        $triggerTurn = 'Opponent Turn';
    }

    $phaseCandidates = [
        'Deployment Phase', 'Start of Turn', 'Hero Phase', 'Movement Phase',
        'Shooting Phase', 'Charge Phase', 'Combat Phase', 'End of Turn',
        'Any Combat Phase', 'Any Charge Phase', 'Any Hero Phase',
    ];
    foreach ($phaseCandidates as $candidate) {
        if (stripos($condition, $candidate) !== false) {
            $triggerPhase = $candidate;
            break;
        }
    }
    if ($phase !== '') {
        $triggerPhase = $phase;
    }

    return [$abilityType, $triggerPhase, $triggerTurn, $iconType];
}

function htmlToText(string $html): string
{
    $html = preg_replace('/<br\s*\/?>/i', "\n", $html);
    $html = preg_replace('/<\/p>/i', "\n", $html);
    $html = preg_replace('/<\/li>/i', "\n", $html);
    $text = strip_tags($html);
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = preg_replace("/\n{3,}/", "\n\n", $text);
    return trim($text);
}

function parseStatInt(?string $value): ?int
{
    $value = trim((string)$value);
    if ($value === '' || $value === '-') {
        return null;
    }
    if (preg_match('/(\d+)/', $value, $m)) {
        return (int)$m[1];
    }
    return null;
}

function parseSave(?string $value): ?int
{
    $value = trim((string)$value);
    if ($value === '') {
        return null;
    }
    if (preg_match('/(\d+)\+/', $value, $m)) {
        return (int)$m[1];
    }
    return parseStatInt($value);
}

function parseDice(?string $value): ?int
{
    $value = trim((string)$value);
    if ($value === '' || $value === '-') {
        return null;
    }
    if (preg_match('/(\d+)\+/', $value, $m)) {
        return (int)$m[1];
    }
    return null;
}

function parseRangeInches(?string $value): ?int
{
    $value = trim((string)$value);
    if ($value === '' || $value === '-') {
        return null;
    }
    if (preg_match('/(\d+)/', $value, $m)) {
        return (int)$m[1];
    }
    return null;
}
