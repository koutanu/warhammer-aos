<?php
/**
 * 共通アビリティ(コアアビリティ/ユニバーサルコマンド)用DBマイグレーション
 * php scripts/migrate_common_abilities.php
 *
 * - m_common_abilities テーブルを作成
 * - 初期データ(command_cost付き)を冪等にシード
 *
 * ※ 効果文・CP費用はゲームの最新ルールに合わせて登録後に調整してよい。
 */
require_once __DIR__ . '/../libs/core/Config.php';

$pdo = new PDO(
    DB_TYPE . ':host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
    DB_USER,
    DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$sqlFile = __DIR__ . '/../sql/common_abilities_migration.sql';
$statements = array_filter(array_map('trim', explode(';', file_get_contents($sqlFile))));
foreach ($statements as $sql) {
    if ($sql !== '') {
        $pdo->exec($sql);
        echo "OK: " . substr($sql, 0, 60) . "...\n";
    }
}

// [name, command_cost, trigger_phase, trigger_turn, ability_type, icon_type, effect, flavor_text, sort_order]
$commonAbilities = [
    [
        'Deploy Unit', null, 'Deployment Phase', 'Your Turn', 'Special', 'Special',
        "【効果】：戦域(Reinforcement)に置いた予備のユニットを、戦場に展開する。自軍テリトリーの端から6インチ以内、かつ全ての敵ユニットから9インチを超えて離れた位置に配置する。",
        null, 10,
    ],
    [
        'Banish Manifestation', null, 'Any Phase', 'Any Turn', 'Offensive', 'Offensive',
        "【宣言】：視認可能な敵MANIFESTATION1個を対象に選ぶ。\n【効果】：そのMANIFESTATIONを退け(Banish)、その効果を解決する。",
        null, 20,
    ],
    [
        'All-out Attack', 1, 'Combat Phase', 'Any Turn', 'Once Per Turn', 'Offensive',
        "【宣言】：味方ユニット1個を対象に選ぶ。\n【効果】：このフェイズの残りの間、対象の攻撃の命中ロールに+1する。",
        null, 100,
    ],
    [
        'All-out Defence', 1, 'Any Phase', 'Any Turn', 'Once Per Turn', 'Defensive',
        "【宣言】：味方ユニット1個を対象に選ぶ。\n【効果】：このフェイズの残りの間、対象はウォード(6+)を得る。",
        null, 110,
    ],
    [
        'Rally', 1, 'Hero Phase', 'Your Turn', 'Once Per Turn', 'Rallying',
        "【宣言】：交戦中でない味方ユニット1個を対象に選ぶ。\n【効果】：ラリーロールとしてダイスを数個振る。4+が出るたびに1点回復するか、撃破されたモデルを1体復帰させる。",
        null, 120,
    ],
    [
        'Redeploy', 1, 'Any Phase', 'Opponent Turn', 'Once Per Turn', 'Movement',
        "【宣言】：交戦中でない味方ユニット1個を対象に選ぶ。\n【効果】：対象は最大D6インチの通常移動を行う。ただしこのターン、対象は走行(Run)・突撃(Charge)できない。",
        null, 130,
    ],
    [
        'At the Double', 1, 'Movement Phase', 'Your Turn', 'Once Per Turn', 'Movement',
        "【宣言】：味方ユニット1個を対象に選ぶ。\n【効果】：対象は走行(Run)を行う際、ロールせずに移動特性に6インチを加える。",
        null, 140,
    ],
    [
        'Forward to Victory', 1, 'Charge Phase', 'Your Turn', 'Once Per Turn', 'Movement',
        "【宣言】：突撃ロールを行った味方ユニット1個を対象に選ぶ。\n【効果】：対象の突撃ロールを振り直す。",
        null, 150,
    ],
    [
        'Power Through', 1, 'Charge Phase', 'Your Turn', 'Once Per Turn', 'Damage',
        "【宣言】：このフェイズに突撃した味方ユニット1個を対象に選ぶ。\n【効果】：対象と交戦中の敵ユニット1個にD3点の致命的ダメージを与える。",
        null, 160,
    ],
    [
        'Covering Fire', 1, 'Charge Phase', 'Opponent Turn', 'Once Per Turn', 'Shooting',
        "【宣言】：突撃した敵ユニットから交戦範囲外にいる、射撃武器を持つ味方ユニット1個を対象に選ぶ。\n【効果】：対象はその敵ユニットを対象にSHOOTアビリティを使用できる。",
        null, 170,
    ],
    [
        'Magnificent Display', 1, 'Hero Phase', 'Your Turn', 'Once Per Turn', 'Control',
        "【宣言】：目標を確保している味方ユニット1個を対象に選ぶ。\n【効果】：このターンの残りの間、対象のコントロール値に+1する。",
        null, 180,
    ],
];

$count = (int)$pdo->query('SELECT COUNT(*) FROM m_common_abilities')->fetchColumn();
if ($count === 0) {
    $stmt = $pdo->prepare(
        'INSERT INTO m_common_abilities
            (name, command_cost, trigger_phase, trigger_turn, ability_type, icon_type, effect, flavor_text, sort_order)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    foreach ($commonAbilities as $ability) {
        $stmt->execute($ability);
    }
    echo "Seeded m_common_abilities (" . count($commonAbilities) . " rows)\n";
} else {
    echo "Skip seed: m_common_abilities already has {$count} rows\n";
}

echo "Migration complete.\n";
