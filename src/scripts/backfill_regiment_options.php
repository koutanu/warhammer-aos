<?php
/**
 * Wahapedia Warscrolls.csv の regiment_options 原文を整形し、
 * m_units.regiment_options（整形済みの可読テキスト）へバックフィルする。
 *
 * Usage:
 *   php scripts/backfill_regiment_options.php
 *
 * - m_units.wahapedia_id = CSV の id で突合する。
 * - regiment_options に "%<id><名前>%" 記法（=連隊編成ルール）を含む行のみ対象。
 *   非HERO ユニット（分類語のみで % 記法を含まない）は NULL のまま据え置く。
 *
 * Data source: https://wahapedia.ru/aos4/the-rules/data-export/ (powered by Wahapedia)
 */
require_once __DIR__ . '/../libs/core/Config.php';

const WARSCROLLS_CSV = __DIR__ . '/../data/wahapedia/Warscrolls.csv';

$pdo = new PDO(
    DB_TYPE . ':host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
    DB_USER,
    DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

ensureRegimentOptionsColumn($pdo);

$rows = loadCsv(WARSCROLLS_CSV);

$update = $pdo->prepare('UPDATE m_units SET regiment_options = :opts WHERE wahapedia_id = :wid');

$updated = 0;
$skipped = 0;
foreach ($rows as $row) {
    $wahapediaId = trim($row['id'] ?? '');
    if ($wahapediaId === '') {
        continue;
    }

    $formatted = formatRegimentOptions($row['regiment_options'] ?? '');
    if ($formatted === null) {
        $skipped++;
        continue;
    }

    $update->execute(['opts' => $formatted, 'wid' => $wahapediaId]);
    $updated += $update->rowCount();
}

echo "Backfill complete. updated={$updated}, skipped(no regiment rule)={$skipped}\n";

// -----------------------------------------------------------------------------

/**
 * Wahapedia の regiment_options 原文を可読テキスト（改行区切り）へ整形する。
 *
 * 入力例: "0-1 %000012010<b>Gryph-hounds</b>%, Any %000012110Warrior Chamber%"
 * 出力例: "0-1 Gryph-hounds\nAny Warrior Chamber"
 *
 * % 記法を含まない場合（=連隊編成ルールではない分類語）は null を返す。
 */
function formatRegimentOptions(string $raw): ?string
{
    $raw = trim($raw);
    if ($raw === '' || strpos($raw, '%') === false) {
        return null;
    }

    // 各エントリ: "<体数> %<id><名前>%"
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

function ensureRegimentOptionsColumn(PDO $pdo): void
{
    $cols = $pdo->query('DESCRIBE m_units')->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('regiment_options', $cols, true)) {
        $pdo->exec('ALTER TABLE m_units ADD COLUMN regiment_options TEXT NULL AFTER faction_keywords');
        echo "ALTER: added regiment_options to m_units\n";
    }
}

/** Wahapedia の "|" 区切り CSV を連想配列の配列として読み込む。 */
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
