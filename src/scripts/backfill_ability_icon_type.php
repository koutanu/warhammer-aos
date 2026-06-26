<?php

/**
 * 既存の m_ability_master にアイコン分類(icon_type)を補完するワンオフスクリプト。
 *
 * Wahapedia の Warscrolls_abilities.csv にある ability_type カテゴリ
 * (Offensive / Defensive / Movement / Shooting / Damage / Control / Rallying / Special)
 * を、名前一致で m_ability_master.icon_type に流し込む。
 *
 * - icon_type 列が無ければ追加する（マイグレーション兼用）。
 * - 既に icon_type が入っている行は上書きしない。
 *
 * Usage:
 *   php scripts/backfill_ability_icon_type.php
 */
require_once __DIR__ . '/../libs/core/Config.php';

const CSV_PATH = __DIR__ . '/../data/wahapedia/Warscrolls_abilities.csv';

$pdo = new PDO(
	DB_TYPE . ':host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
	DB_USER,
	DB_PASS,
	[PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

// 1) 列の存在を保証
$cols = $pdo->query('DESCRIBE m_ability_master')->fetchAll(PDO::FETCH_COLUMN);
if (!in_array('icon_type', $cols, true)) {
	$pdo->exec('ALTER TABLE m_ability_master ADD COLUMN icon_type VARCHAR(20) NULL DEFAULT NULL AFTER ability_type');
	echo "ALTER: added icon_type to m_ability_master\n";
}

// 2) CSV から 能力名 → カテゴリ の対応を作る
if (!is_file(CSV_PATH)) {
	fwrite(STDERR, "CSV not found: " . CSV_PATH . "\n");
	exit(1);
}

$nameToIcon = [];
$fh = fopen(CSV_PATH, 'r');
$header = null;
while (($line = fgets($fh)) !== false) {
	$line = preg_replace('/^\xEF\xBB\xBF/', '', rtrim($line, "\r\n"));
	if ($line === '') {
		continue;
	}
	$cells = str_getcsv($line, '|', '"', '\\');
	if ($header === null) {
		$header = $cells;
		continue;
	}
	$row = [];
	foreach ($header as $i => $key) {
		$row[$key] = $cells[$i] ?? '';
	}
	$name = trim($row['name'] ?? '');
	$type = trim($row['ability_type'] ?? '');
	if ($name === '') {
		continue;
	}
	$icon = $type !== '' ? $type : 'Special';
	// 同名で複数カテゴリがある場合は最初のものを優先（基本は一致）
	if (!isset($nameToIcon[$name])) {
		$nameToIcon[$name] = $icon;
	}
}
fclose($fh);

echo 'CSV abilities: ' . count($nameToIcon) . "\n";

// 3) DB 側の英語名で突合する。
//    名称は translate_abilities.php で「日本語 (English)」に置換され、原文は name_en に退避済み。
//    また import 時の重複回避で "name — UnitName" のサフィックスが付く場合がある。
$dbCols = $pdo->query('DESCRIBE m_ability_master')->fetchAll(PDO::FETCH_COLUMN);
$hasNameEn = in_array('name_en', $dbCols, true);

$selectSql = $hasNameEn
	? 'SELECT id, name, name_en, icon_type FROM m_ability_master'
	: 'SELECT id, name, NULL AS name_en, icon_type FROM m_ability_master';
$rows = $pdo->query($selectSql)->fetchAll(PDO::FETCH_ASSOC);

$update = $pdo->prepare('UPDATE m_ability_master SET icon_type = :icon WHERE id = :id');

$stripSuffix = static function (string $name): string {
	// " — UnitName"（em dash）以降を除去
	$pos = mb_strpos($name, ' — ');
	return $pos === false ? $name : mb_substr($name, 0, $pos);
};

$updated = 0;
$unmatched = [];
foreach ($rows as $r) {
	if (!empty($r['icon_type'])) {
		continue;
	}
	$english = trim((string)($r['name_en'] ?? '')) !== ''
		? (string)$r['name_en']
		: (string)$r['name'];
	$key = $stripSuffix(trim($english));

	$icon = $nameToIcon[$key] ?? null;
	if ($icon === null) {
		$unmatched[$key] = 1;
		continue;
	}
	$update->execute([
		'icon' => mb_substr($icon, 0, 20),
		'id'   => (int)$r['id'],
	]);
	$updated += $update->rowCount();
}

echo "Updated rows: {$updated}\n";
if ($unmatched) {
	echo 'Unmatched names: ' . count($unmatched) . "\n";
}

$remaining = (int)$pdo->query("SELECT COUNT(*) FROM m_ability_master WHERE icon_type IS NULL OR icon_type = ''")
	->fetchColumn();
echo "Rows still without icon_type: {$remaining}\n";
echo "Done.\n";
