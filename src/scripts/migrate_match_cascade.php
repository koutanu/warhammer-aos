<?php
/**
 * t_matches 削除時に子テーブルを自動削除する外部キー(ON DELETE CASCADE)を追加するマイグレーション
 * php scripts/migrate_match_cascade.php
 *
 * 冪等: 既に同名の外部キー制約が存在する場合は追加をスキップする。
 */
require_once __DIR__ . '/../libs/core/Config.php';

$pdo = new PDO(
    DB_TYPE . ':host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
    DB_USER,
    DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

// 孤立行のクリーンアップ（外部キー追加が失敗しないよう先に実行）
$deletedScores = $pdo->exec(
    'DELETE c FROM t_match_round_scores c
       LEFT JOIN t_matches m ON c.match_id = m.id
      WHERE m.id IS NULL'
);
echo "Cleaned orphan t_match_round_scores rows: {$deletedScores}\n";

$deletedAbility = $pdo->exec(
    'DELETE c FROM t_match_ability_usage c
       LEFT JOIN t_matches m ON c.match_id = m.id
      WHERE m.id IS NULL'
);
echo "Cleaned orphan t_match_ability_usage rows: {$deletedAbility}\n";

/**
 * 指定テーブルに同名の外部キー制約が存在するか判定する
 */
$constraintExists = function (PDO $pdo, string $table, string $constraint): bool {
    $stmt = $pdo->prepare(
        'SELECT COUNT(*)
           FROM information_schema.TABLE_CONSTRAINTS
          WHERE CONSTRAINT_SCHEMA = DATABASE()
            AND TABLE_NAME = :table
            AND CONSTRAINT_NAME = :constraint
            AND CONSTRAINT_TYPE = "FOREIGN KEY"'
    );
    $stmt->execute([':table' => $table, ':constraint' => $constraint]);
    return (int)$stmt->fetchColumn() > 0;
};

$foreignKeys = [
    [
        'table'      => 't_match_round_scores',
        'constraint' => 'fk_round_scores_match',
        'sql'        => 'ALTER TABLE t_match_round_scores
            ADD CONSTRAINT fk_round_scores_match
            FOREIGN KEY (match_id) REFERENCES t_matches (id) ON DELETE CASCADE',
    ],
    [
        'table'      => 't_match_ability_usage',
        'constraint' => 'fk_ability_usage_match',
        'sql'        => 'ALTER TABLE t_match_ability_usage
            ADD CONSTRAINT fk_ability_usage_match
            FOREIGN KEY (match_id) REFERENCES t_matches (id) ON DELETE CASCADE',
    ],
];

foreach ($foreignKeys as $fk) {
    if ($constraintExists($pdo, $fk['table'], $fk['constraint'])) {
        echo "SKIP: {$fk['constraint']} already exists on {$fk['table']}\n";
        continue;
    }
    $pdo->exec($fk['sql']);
    echo "ADDED: {$fk['constraint']} (ON DELETE CASCADE) on {$fk['table']}\n";
}

echo "Cascade migration complete.\n";
