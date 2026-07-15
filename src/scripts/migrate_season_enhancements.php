<?php
/**
 * シーズン追加能力マイグレーション + アキュシーの禍事シード
 * php scripts/migrate_season_enhancements.php
 * （Docker: docker compose exec web php scripts/migrate_season_enhancements.php）
 */
require_once __DIR__ . '/../libs/core/Config.php';
require_once __DIR__ . '/../libs/types/season_enhancements.php';

$pdo = new PDO(
    DB_TYPE . ':host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
    DB_USER,
    DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$sqlFile = __DIR__ . '/../sql/season_enhancements_migration.sql';
$rawSql = preg_replace('/^--.*$/m', '', file_get_contents($sqlFile));
$statements = array_filter(array_map('trim', explode(';', $rawSql)));
foreach ($statements as $sql) {
    if ($sql !== '') {
        $pdo->exec($sql);
        echo 'OK: ' . substr(str_replace("\n", ' ', $sql), 0, 70) . "...\n";
    }
}

$alterColumns = [
    'season_enhancement_id'             => 'INT(11) DEFAULT NULL',
    'season_enhancement_target_unit_id' => 'INT(11) DEFAULT NULL',
    'season_enhancement_regiment_index' => 'TINYINT DEFAULT NULL',
    'season_enhancement_unit_slot'      => 'VARCHAR(16) DEFAULT NULL',
];

$existing = $pdo->query('DESCRIBE t_rosters')->fetchAll(PDO::FETCH_COLUMN);
foreach ($alterColumns as $col => $def) {
    if (!in_array($col, $existing, true)) {
        $pdo->exec("ALTER TABLE t_rosters ADD COLUMN `$col` $def");
        echo "ALTER: added $col to t_rosters\n";
    }
}

$kwCols = $pdo->query('DESCRIBE m_season_enhancement_keywords')->fetchAll(PDO::FETCH_COLUMN);
if (!in_array('requirement', $kwCols, true)) {
    $pdo->exec(
        "ALTER TABLE m_season_enhancement_keywords
         ADD COLUMN `requirement` ENUM('require','exclude') NOT NULL DEFAULT 'require'
         AFTER `keyword_id`"
    );
    echo "ALTER: added requirement to m_season_enhancement_keywords\n";
}

$season = SeasonEnhancements::SEASON_2026_27;

$findKw = $pdo->prepare(
    "SELECT id FROM m_keywords_master WHERE name = ? AND keyword_type = 'unit' LIMIT 1"
);
$insertKw = $pdo->prepare(
    "INSERT INTO m_keywords_master (name, keyword_type, sort_order) VALUES (?, 'unit', 0)"
);

function resolveUnitKeyword(PDO $pdo, PDOStatement $findKw, PDOStatement $insertKw, array $names): int
{
    foreach ($names as $name) {
        $findKw->execute([$name]);
        $row = $findKw->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            return (int)$row['id'];
        }
    }
    $primary = $names[0];
    $insertKw->execute([$primary]);
    $id = (int)$pdo->lastInsertId();
    echo "SEED: created keyword {$primary} (id={$id})\n";
    return $id;
}

/**
 * @param array{
 *   faction_name_en: string,
 *   label_ja: string,
 *   label_en: string,
 *   exclude_keywords: list<list<string>>,
 *   abilities: list<array{
 *     name: string,
 *     sort_order: int,
 *     activation: string,
 *     trigger_phase: string|null,
 *     trigger_turn: string,
 *     trigger_condition_ja: string,
 *     effect: string
 *   }>
 * } $pack
 */
function seedFactionSeasonEnhancements(PDO $pdo, string $season, array $pack, PDOStatement $findKw, PDOStatement $insertKw): void
{
    $factionStmt = $pdo->prepare(
        "SELECT id FROM m_factions WHERE UPPER(name_en) = UPPER(?) LIMIT 1"
    );
    $factionStmt->execute([$pack['faction_name_en']]);
    $factionRow = $factionStmt->fetch(PDO::FETCH_ASSOC);
    if (!$factionRow) {
        echo "SEED SKIP: faction not found (name_en={$pack['faction_name_en']})\n";
        return;
    }
    $factionId = (int)$factionRow['id'];
    echo "SEED: {$pack['faction_name_en']} faction_id={$factionId}\n";

    $excludeIds = [];
    foreach ($pack['exclude_keywords'] as $names) {
        $excludeIds[] = resolveUnitKeyword($pdo, $findKw, $insertKw, $names);
    }
    echo 'SEED: exclude keyword_ids=' . implode(',', $excludeIds) . "\n";

    $pdo->prepare(
        "INSERT INTO m_faction_season_enhancement_labels (faction_id, season, label_ja, label_en)
         VALUES (?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE label_ja = VALUES(label_ja), label_en = VALUES(label_en)"
    )->execute([$factionId, $season, $pack['label_ja'], $pack['label_en']]);
    echo "SEED: label {$pack['label_ja']} / {$pack['label_en']}\n";

    $findEnh = $pdo->prepare(
        "SELECT id FROM m_season_enhancements
         WHERE faction_id = ? AND season = ? AND name = ?
         LIMIT 1"
    );
    $insertEnh = $pdo->prepare(
        "INSERT INTO m_season_enhancements
            (faction_id, season, name, effect, points, sort_order, is_hidden,
             activation, usage_scope, usage_per, trigger_phase, trigger_turn, trigger_condition_ja)
         VALUES (?, ?, ?, ?, 0, ?, 0, ?, 'unlimited', 'unit', ?, ?, ?)"
    );
    $updateEnh = $pdo->prepare(
        "UPDATE m_season_enhancements
         SET effect = ?, sort_order = ?, activation = ?, trigger_phase = ?, trigger_turn = ?,
             trigger_condition_ja = ?
         WHERE id = ?"
    );
    $findLink = $pdo->prepare(
        "SELECT 1 FROM m_season_enhancement_keywords
         WHERE enhancement_id = ? AND keyword_id = ?
         LIMIT 1"
    );
    $insertLink = $pdo->prepare(
        "INSERT INTO m_season_enhancement_keywords (enhancement_id, keyword_id, requirement)
         VALUES (?, ?, 'exclude')"
    );
    $cleanupOrphan = $pdo->prepare(
        "DELETE sek FROM m_season_enhancement_keywords sek
         JOIN m_keywords_master km ON km.id = sek.keyword_id
         WHERE sek.enhancement_id = ?
           AND km.name IN ('HERO', 'WAR MACHINE', 'MONSTER', 'BEAST')
           AND km.keyword_type = 'unit'"
    );

    foreach ($pack['abilities'] as $ability) {
        $conditionJa = $ability['trigger_condition_ja'] ?? null;
        $findEnh->execute([$factionId, $season, $ability['name']]);
        $existingEnh = $findEnh->fetch(PDO::FETCH_ASSOC);
        if ($existingEnh) {
            $enhId = (int)$existingEnh['id'];
            $updateEnh->execute([
                $ability['effect'],
                $ability['sort_order'],
                $ability['activation'],
                $ability['trigger_phase'],
                $ability['trigger_turn'],
                $conditionJa,
                $enhId,
            ]);
            echo "SEED: updated {$ability['name']} (id={$enhId})\n";
        } else {
            $insertEnh->execute([
                $factionId,
                $season,
                $ability['name'],
                $ability['effect'],
                $ability['sort_order'],
                $ability['activation'],
                $ability['trigger_phase'],
                $ability['trigger_turn'],
                $conditionJa,
            ]);
            $enhId = (int)$pdo->lastInsertId();
            echo "SEED: inserted {$ability['name']} (id={$enhId})\n";
        }

        $cleanupOrphan->execute([$enhId]);

        foreach ($excludeIds as $kwId) {
            $findLink->execute([$enhId, $kwId]);
            if (!$findLink->fetchColumn()) {
                $insertLink->execute([$enhId, $kwId]);
                echo "SEED: linked exclude keyword_id={$kwId} to enh={$enhId}\n";
            }
        }
    }
}

// --- Skaven: モウルダー変異 ---
seedFactionSeasonEnhancements($pdo, $season, [
    'faction_name_en' => 'SKAVEN',
    'label_ja' => 'モウルダー変異',
    'label_en' => 'Moulder Mutations',
    'exclude_keywords' => [
        ['英雄', 'HERO'],
        ['戦闘兵器', 'WAR MACHINE'],
    ],
    'abilities' => [
        [
            'name' => '移植された脳',
            'sort_order' => 1,
            'activation' => 'active',
            'trigger_phase' => 'hero',
            'trigger_turn' => 'any',
            'trigger_condition_ja' => '任意のヒーローフェイズ',
            'effect' =>
                "モールダーの「ボランティア」たちの脳組織が変異体に移植されており、一瞬で人格を入れ替えることが可能だ。\n"
                . "以下の脳から1つを選ぶ。その効果はこのターンの残りの間、適用される：\n"
                . "・歪み石中毒者の脳：このユニットの白兵攻撃の命中ロールに+1する。このユニットの統制値の最大は1となる。\n"
                . "・ウォーロックの幼鼠脳：このユニットの統制値に+10する。",
        ],
        [
            'name' => '同化促進剤',
            'sort_order' => 2,
            'activation' => 'active',
            'trigger_phase' => 'charge',
            'trigger_turn' => 'your',
            'trigger_condition_ja' => '自軍側突撃フェイズ',
            'effect' =>
                "四肢の筋肉に急速成長を促す血清が注入されており、それにより恐るべき速度で移動できる。\n"
                . "このターン中にこのユニットが配置された場合、このターンの間、このユニットの突撃ロールのダイス数に+1する"
                . "（最大3）。その後、出目から1個を選び除去し、残りのダイスを突撃ロールとして用いる。",
        ],
        [
            'name' => '鋸骨突起',
            'sort_order' => 3,
            'activation' => 'passive',
            'trigger_phase' => 'combat',
            'trigger_turn' => 'any',
            'trigger_condition_ja' => 'パッシブ',
            'effect' =>
                "鋭い骨片が皮膚を突き破り、このユニットを攻撃する者の肉を深く抉る。\n"
                . "このユニットを対象とする敵ユニットの白兵攻撃の未修正の命中ロールが1だった場合、"
                . "その「白兵攻撃」アビリティの解決直後に、攻撃側ユニットに致命的ダメージを1点与える。",
        ],
    ],
], $findKw, $insertKw);

// --- Stormcast Eternals: 激闘の傷跡 ---
// 「獣兵」は既存マスタの大型獣（MONSTER）に対応
seedFactionSeasonEnhancements($pdo, $season, [
    'faction_name_en' => 'Stormcast Eternals',
    'label_ja' => '激闘の傷跡',
    'label_en' => 'Battle Scars',
    'exclude_keywords' => [
        ['英雄', 'HERO'],
        ['大型獣', 'MONSTER', '獣兵'],
    ],
    'abilities' => [
        [
            'name' => '束縛なき稲妻',
            'sort_order' => 1,
            'activation' => 'passive',
            'trigger_phase' => 'any',
            'trigger_turn' => 'any',
            'trigger_condition_ja' => 'パッシブ',
            'effect' =>
                "これらの戦士たちが帯びる神秘の稲妻は不安定と化し、その憤怒から逃れようとする者に己の意思とは無関係に襲いかかる。\n"
                . "このユニットと近接戦闘中である敵ユニットが移動するか、あるいは戦場から取り除かれる際、"
                . "そのアビリティが解決された直後に、その敵ユニットが戦場に配置されており、"
                . "かつこのユニットと近接戦闘中ではない場合、その敵ユニットにD3ポイントの致命的ダメージを与える。",
        ],
        [
            'name' => 'アンバーストーン歩哨の古参兵',
            'sort_order' => 2,
            'activation' => 'passive',
            'trigger_phase' => 'combat',
            'trigger_turn' => 'any',
            'trigger_condition_ja' => 'パッシブ',
            'effect' =>
                "狡猾な敵対者と戦い慣れている戦士たちは、有利に戦いを運ぶ。\n"
                . "敵ユニットがこのユニットと近接戦闘中である間、その敵ユニットは『先手効果』キーワードを持っていないものとして扱われる。\n"
                . "デザイナーズ・ノート：『後手効果』を有する敵ユニットは『後手効果』を持ったままになる。"
                . "『先手効果』も『後手効果』も両方持っていないものとしてはみなされない。",
        ],
        [
            'name' => '炎の魂',
            'sort_order' => 3,
            'activation' => 'passive',
            'trigger_phase' => 'any',
            'trigger_turn' => 'any',
            'trigger_condition_ja' => 'パッシブ',
            'effect' =>
                "これらの戦士たちが物質の体を失うとき、華々しい爆発で散ってゆく。\n"
                . "このユニットが全滅したとき、自軍は3憤激ダイスを獲得する。",
        ],
    ],
], $findKw, $insertKw);

echo "Season enhancements migration complete.\n";
