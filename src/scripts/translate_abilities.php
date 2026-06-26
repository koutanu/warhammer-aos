<?php
/**
 * m_ability_master (name, effect) と m_unit_weapons (name) を日本語化する。
 * - 定型キーワード(abilities, trigger_phase, ability_type, type)は英語のまま。
 * - 未訳(日本語を含まない)行のみ処理＝冪等。
 * - 元の英語は *_en 列へ退避。
 * - 翻訳マップは「英語原文 => 日本語」（重複自動対応）。
 *   名称は「日本語 (English)」、effect は純日本語に置換。
 *
 * 実行: php scripts/translate_abilities.php
 * 復元:
 *   UPDATE m_ability_master SET name=name_en WHERE name_en IS NOT NULL;
 *   UPDATE m_ability_master SET effect=effect_en WHERE effect_en IS NOT NULL;
 *   UPDATE m_unit_weapons SET name=name_en WHERE name_en IS NOT NULL;
 */
$abilityNameMap   = require __DIR__ . '/../libs/maps/ability_name_map.php';
$abilityEffectMap = require __DIR__ . '/../libs/maps/ability_effect_map.php';
$weaponNameMap    = require __DIR__ . '/../libs/maps/weapon_name_map.php';
require_once __DIR__ . '/../libs/core/Config.php';

$pdo = new PDO(
    DB_TYPE . ':host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
    DB_USER,
    DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$JP = '/[\x{3040}-\x{30ff}\x{4e00}-\x{9fff}]/u';

/* ---------- 1. _en 列の追加（冪等） ---------- */
function ensureColumn(PDO $pdo, string $table, string $col, string $def): void
{
    $cols = $pdo->query("DESCRIBE `$table`")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array($col, $cols, true)) {
        $pdo->exec("ALTER TABLE `$table` ADD COLUMN `$col` $def");
        echo "ALTER: $table.$col added\n";
    } else {
        echo "SKIP: $table.$col exists\n";
    }
}
ensureColumn($pdo, 'm_ability_master', 'name_en', 'VARCHAR(255) NULL AFTER name');
ensureColumn($pdo, 'm_ability_master', 'effect_en', 'TEXT NULL AFTER effect');
ensureColumn($pdo, 'm_unit_weapons', 'name_en', 'VARCHAR(255) NULL AFTER name');

/* ---------- 2. m_ability_master name / effect ---------- */
$abilityRows = $pdo->query('SELECT id, name, effect FROM m_ability_master')->fetchAll(PDO::FETCH_ASSOC);
$updAbName = $pdo->prepare('UPDATE m_ability_master SET name_en = :name_en, name = :name WHERE id = :id');
$updAbEff  = $pdo->prepare('UPDATE m_ability_master SET effect_en = :effect_en, effect = :effect WHERE id = :id');

$nameDone = 0; $nameMiss = [];
$effDone = 0; $effMiss = [];
foreach ($abilityRows as $r) {
    $id = (int)$r['id'];

    // name
    if (!preg_match($JP, (string)$r['name'])) {
        $en = $r['name'];
        if (isset($abilityNameMap[$en])) {
            $updAbName->execute([
                'name_en' => $en,
                'name'    => $abilityNameMap[$en] . ' (' . $en . ')',
                'id'      => $id,
            ]);
            $nameDone += $updAbName->rowCount();
        } else {
            $nameMiss[$en] = 1;
        }
    }

    // effect（id キー：改行・空白の完全一致を避けるため）
    if ($r['effect'] !== null && $r['effect'] !== '' && !preg_match($JP, (string)$r['effect'])) {
        if (isset($abilityEffectMap[$id])) {
            $updAbEff->execute([
                'effect_en' => $r['effect'],
                'effect'    => $abilityEffectMap[$id],
                'id'        => $id,
            ]);
            $effDone += $updAbEff->rowCount();
        } else {
            $effMiss[$id] = 1;
        }
    }
}
echo "ability name updated: $nameDone (missing distinct: " . count($nameMiss) . ")\n";
echo "ability effect updated: $effDone (missing distinct: " . count($effMiss) . ")\n";

/* ---------- 3. m_unit_weapons name ---------- */
$weaponRows = $pdo->query('SELECT id, name FROM m_unit_weapons')->fetchAll(PDO::FETCH_ASSOC);
$updW = $pdo->prepare('UPDATE m_unit_weapons SET name_en = :name_en, name = :name WHERE id = :id');
$wDone = 0; $wMiss = [];
foreach ($weaponRows as $r) {
    $id = (int)$r['id'];
    if (preg_match($JP, (string)$r['name'])) continue;
    $en = $r['name'];
    if (isset($weaponNameMap[$en])) {
        $updW->execute([
            'name_en' => $en,
            'name'    => $weaponNameMap[$en] . ' (' . $en . ')',
            'id'      => $id,
        ]);
        $wDone += $updW->rowCount();
    } else {
        $wMiss[$en] = 1;
    }
}
echo "weapon name updated: $wDone (missing distinct: " . count($wMiss) . ")\n";

/* ---------- 4. 未訳(マップ欠落)の一覧出力 ---------- */
if ($nameMiss) { echo "\n[MISSING ability names]\n"; foreach (array_keys($nameMiss) as $s) echo "  $s\n"; }
if ($effMiss)  { echo "\n[MISSING ability effect ids]\n  " . implode(',', array_keys($effMiss)) . "\n"; }
if ($wMiss)    { echo "\n[MISSING weapon names]\n"; foreach (array_keys($wMiss) as $s) echo "  $s\n"; }

echo "\nDone.\n";
