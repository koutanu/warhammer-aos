<?php
/**
 * Age of Sigmar Fandom Wiki から英語ストーリーを取得し DB に保存する。
 *
 * 実行: php scripts/import_fandom_story.php
 * 上書き: php scripts/import_fandom_story.php --force
 * ユニット1件: php scripts/import_fandom_story.php --id=22
 * 陣営のみ: php scripts/import_fandom_story.php --factions-only
 * ユニットのみ: php scripts/import_fandom_story.php --units-only
 * 陣営1件: php scripts/import_fandom_story.php --faction-id=1
 * CSV再取得: php scripts/import_fandom_story.php --download
 *
 * ユニット英語ページ名の解決順:
 *   1. m_units.name_en
 *   2. Wahapedia Warscrolls.csv（wahapedia_id 照合）
 *
 * 陣営は m_factions.name_en（＋別名マップ）で解決する。
 *
 * ライセンス: CC-BY-SA（出典 URL を story_source_url に保存）
 */
require_once __DIR__ . '/../libs/core/Config.php';

const FANDOM_API = 'https://ageofsigmar.fandom.com/api.php';
const FANDOM_WIKI = 'https://ageofsigmar.fandom.com/wiki/';
const WAHAPEDIA_WARSCROLLS = 'https://wahapedia.ru/aos4/Warscrolls.csv';
const DATA_DIR = __DIR__ . '/../data/wahapedia';
const USER_AGENT = 'WarhammerAoSApp/1.0 (unit story import; local; contact: local-dev)';
const REQUEST_SLEEP_US = 400000; // 0.4s

/** name_en → 追加で試す Wiki タイトル（軍勢ページに限る。神ページ等は使わない） */
const FACTION_TITLE_ALIASES = [
    'Ogor Mautribes' => ['Ogor Mawtribes', 'Ogors'],
    'Orruk Warclans' => ['Orruks'],
    'Cities of Sigmar' => ['Free Peoples'],
    'Lumineth Realm-lords' => ['Lumineth'],
];

$force = in_array('--force', $argv, true);
$download = in_array('--download', $argv, true);
$unitsOnly = in_array('--units-only', $argv, true);
$factionsOnly = in_array('--factions-only', $argv, true);
$idFilter = null;
$factionIdFilter = null;
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--id=')) {
        $idFilter = (int)substr($arg, 5);
    }
    if (str_starts_with($arg, '--faction-id=')) {
        $factionIdFilter = (int)substr($arg, 13);
    }
}

$pdo = new PDO(
    DB_TYPE . ':host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
    DB_USER,
    DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

ensureUnitStoryColumns($pdo);
ensureNameEnColumn($pdo);
ensureFactionStoryColumns($pdo);

$doUnits = true;
$doFactions = true;
if ($unitsOnly) {
    $doFactions = false;
}
if ($factionsOnly) {
    $doUnits = false;
}
if ($idFilter !== null) {
    $doUnits = true;
    $doFactions = false;
}
if ($factionIdFilter !== null) {
    $doUnits = false;
    $doFactions = true;
}

if ($doUnits) {
    $wahapediaNames = loadWahapediaNameMap($download);
    importUnitStories($pdo, $wahapediaNames, $force, $idFilter);
} elseif ($download) {
    loadWahapediaNameMap(true);
}

if ($doFactions) {
    importFactionStories($pdo, $force, $factionIdFilter);
}

// -----------------------------------------------------------------------------

function importUnitStories(PDO $pdo, array $wahapediaNames, bool $force, ?int $idFilter): void
{
    $sql = 'SELECT id, name, name_en, wahapedia_id, story_text FROM m_units WHERE 1=1';
    $params = [];
    if ($idFilter !== null && $idFilter > 0) {
        $sql .= ' AND id = :id';
        $params[':id'] = $idFilter;
    }
    $sql .= ' ORDER BY id ASC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $units = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $update = $pdo->prepare(
        'UPDATE m_units SET story_text = :story_text, story_source_url = :story_source_url WHERE id = :id'
    );
    $updateNameEn = $pdo->prepare(
        'UPDATE m_units SET name_en = :name_en WHERE id = :id AND (name_en IS NULL OR name_en = \'\')'
    );

    $ok = 0;
    $skip = 0;
    $miss = 0;
    $fail = 0;

    echo 'Importing Fandom stories for ' . count($units) . ' unit(s)' . ($force ? ' (force)' : '') . "...\n";

    foreach ($units as $unit) {
        $id = (int)$unit['id'];
        $pageTitle = resolvePageTitle($unit, $wahapediaNames);

        if ($pageTitle === null || $pageTitle === '') {
            echo "MISS unit #{$id} {$unit['name']}: no English title (name_en / wahapedia)\n";
            $miss++;
            continue;
        }

        if (trim((string)($unit['name_en'] ?? '')) === '') {
            $updateNameEn->execute([':name_en' => $pageTitle, ':id' => $id]);
        }

        if (!$force && trim((string)($unit['story_text'] ?? '')) !== '') {
            echo "SKIP unit #{$id} {$pageTitle}: story already set\n";
            $skip++;
            continue;
        }

        $result = fetchAndExtractStory(buildWikiTitleCandidates($pageTitle), false);
        if ($result['error'] !== null) {
            echo "MISS unit #{$id} {$pageTitle}: {$result['error']}\n";
            $miss++;
            continue;
        }

        try {
            $update->execute([
                ':story_text'       => $result['text'],
                ':story_source_url' => $result['url'],
                ':id'               => $id,
            ]);
            echo "OK   unit #{$id} {$result['tried']} (" . mb_strlen($result['text']) . " chars) <- {$result['title']}\n";
            $ok++;
        } catch (Throwable $e) {
            echo "FAIL unit #{$id} {$pageTitle}: " . $e->getMessage() . "\n";
            $fail++;
        }
    }

    echo "Units done. ok={$ok} skip={$skip} miss={$miss} fail={$fail}\n\n";
}

function importFactionStories(PDO $pdo, bool $force, ?int $factionIdFilter): void
{
    $sql = 'SELECT id, name, name_en, story_text FROM m_factions WHERE 1=1';
    $params = [];
    if ($factionIdFilter !== null && $factionIdFilter > 0) {
        $sql .= ' AND id = :id';
        $params[':id'] = $factionIdFilter;
    }
    $sql .= ' ORDER BY id ASC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $factions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $update = $pdo->prepare(
        'UPDATE m_factions SET story_text = :story_text, story_source_url = :story_source_url WHERE id = :id'
    );

    $ok = 0;
    $skip = 0;
    $miss = 0;
    $fail = 0;

    echo 'Importing Fandom stories for ' . count($factions) . ' faction(s)' . ($force ? ' (force)' : '') . "...\n";

    foreach ($factions as $faction) {
        $id = (int)$faction['id'];
        $nameEn = trim((string)($faction['name_en'] ?? ''));
        if ($nameEn === '') {
            echo "MISS faction #{$id} {$faction['name']}: no name_en\n";
            $miss++;
            continue;
        }

        if (!$force && trim((string)($faction['story_text'] ?? '')) !== '') {
            echo "SKIP faction #{$id} {$nameEn}: story already set\n";
            $skip++;
            continue;
        }

        $titlesToTry = buildFactionWikiTitleCandidates($nameEn);
        $result = fetchAndExtractStory($titlesToTry, true);
        if ($result['error'] !== null) {
            echo "MISS faction #{$id} {$nameEn}: {$result['error']}\n";
            $miss++;
            continue;
        }

        try {
            $update->execute([
                ':story_text'       => $result['text'],
                ':story_source_url' => $result['url'],
                ':id'               => $id,
            ]);
            echo "OK   faction #{$id} {$result['tried']} (" . mb_strlen($result['text']) . " chars) <- {$result['title']}\n";
            $ok++;
        } catch (Throwable $e) {
            echo "FAIL faction #{$id} {$nameEn}: " . $e->getMessage() . "\n";
            $fail++;
        }
    }

    echo "Factions done. ok={$ok} skip={$skip} miss={$miss} fail={$fail}\n";
}

/**
 * @param list<string> $titlesToTry
 * @return array{text:?string,url:?string,title:?string,tried:?string,error:?string}
 */
function fetchAndExtractStory(array $titlesToTry, bool $forFaction): array
{
    $wikitext = null;
    $resolvedTitle = null;
    $error = 'missing title';
    $tried = $titlesToTry[0] ?? null;

    foreach ($titlesToTry as $tryTitle) {
        usleep(REQUEST_SLEEP_US);
        [$wikitext, $resolvedTitle, $error] = fetchWikitext($tryTitle);
        if ($error === null) {
            $tried = $tryTitle;
            break;
        }
    }

    if ($error !== null) {
        return ['text' => null, 'url' => null, 'title' => null, 'tried' => $tried, 'error' => $error];
    }

    $plain = extractStoryPlain($wikitext, $forFaction);
    if ($plain === '') {
        return ['text' => null, 'url' => null, 'title' => $resolvedTitle, 'tried' => $tried, 'error' => 'empty story after extract'];
    }

    $sourceUrl = FANDOM_WIKI . rawurlencode(str_replace(' ', '_', $resolvedTitle));
    return [
        'text'  => $plain,
        'url'   => $sourceUrl,
        'title' => $resolvedTitle,
        'tried' => $tried,
        'error' => null,
    ];
}

function ensureUnitStoryColumns(PDO $pdo): void
{
    $cols = $pdo->query('DESCRIBE m_units')->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('story_text', $cols, true)) {
        $pdo->exec('ALTER TABLE m_units ADD COLUMN story_text TEXT NULL AFTER flavor_text');
        echo "ALTER: added story_text to m_units\n";
    }
    $cols = $pdo->query('DESCRIBE m_units')->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('story_source_url', $cols, true)) {
        $after = in_array('story_text', $cols, true) ? 'story_text' : 'flavor_text';
        $pdo->exec("ALTER TABLE m_units ADD COLUMN story_source_url VARCHAR(512) NULL AFTER {$after}");
        echo "ALTER: added story_source_url to m_units\n";
    }
}

function ensureFactionStoryColumns(PDO $pdo): void
{
    $cols = $pdo->query('DESCRIBE m_factions')->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('story_text', $cols, true)) {
        $pdo->exec('ALTER TABLE m_factions ADD COLUMN story_text TEXT NULL AFTER is_hidden');
        echo "ALTER: added story_text to m_factions\n";
    }
    $cols = $pdo->query('DESCRIBE m_factions')->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('story_source_url', $cols, true)) {
        $after = in_array('story_text', $cols, true) ? 'story_text' : 'is_hidden';
        $pdo->exec("ALTER TABLE m_factions ADD COLUMN story_source_url VARCHAR(512) NULL AFTER {$after}");
        echo "ALTER: added story_source_url to m_factions\n";
    }
}

function ensureNameEnColumn(PDO $pdo): void
{
    $cols = $pdo->query('DESCRIBE m_units')->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('name_en', $cols, true)) {
        $pdo->exec('ALTER TABLE m_units ADD COLUMN name_en VARCHAR(255) NULL AFTER name');
        echo "ALTER: added name_en\n";
    }
}

/**
 * @return array<string,string> wahapedia_id => English name
 */
function loadWahapediaNameMap(bool $forceDownload): array
{
    if (!is_dir(DATA_DIR)) {
        mkdir(DATA_DIR, 0775, true);
    }
    $path = DATA_DIR . '/Warscrolls.csv';
    if ($forceDownload || !is_file($path)) {
        echo "Downloading Warscrolls.csv from Wahapedia...\n";
        $ch = curl_init(WAHAPEDIA_WARSCROLLS);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT        => 120,
            CURLOPT_USERAGENT      => USER_AGENT,
        ]);
        $body = curl_exec($ch);
        $errno = curl_errno($ch);
        $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($errno !== 0 || $body === false || $http >= 400) {
            fwrite(STDERR, "WARN: failed to download Warscrolls.csv (http={$http}, errno={$errno})\n");
            return [];
        }
        file_put_contents($path, $body);
    }

    $fh = fopen($path, 'r');
    if ($fh === false) {
        return [];
    }
    $header = fgetcsv($fh, 0, '|');
    if ($header === false) {
        fclose($fh);
        return [];
    }
    $header = array_map(static function ($col) {
        $col = trim((string)$col);
        if (str_starts_with($col, "\xEF\xBB\xBF")) {
            $col = substr($col, 3);
        }
        return $col;
    }, $header);
    $idIdx = array_search('id', $header, true);
    $nameIdx = array_search('name', $header, true);
    if ($idIdx === false || $nameIdx === false) {
        fclose($fh);
        return [];
    }

    $map = [];
    while (($row = fgetcsv($fh, 0, '|')) !== false) {
        $wid = trim((string)($row[$idIdx] ?? ''));
        $name = trim((string)($row[$nameIdx] ?? ''));
        if ($wid !== '' && $name !== '') {
            $map[$wid] = $name;
        }
    }
    fclose($fh);
    echo 'Wahapedia names loaded: ' . count($map) . "\n";
    return $map;
}

/**
 * @param array<string,mixed> $unit
 * @param array<string,string> $wahapediaNames
 */
function resolvePageTitle(array $unit, array $wahapediaNames): ?string
{
    $nameEn = trim((string)($unit['name_en'] ?? ''));
    if ($nameEn !== '') {
        return $nameEn;
    }
    $wid = trim((string)($unit['wahapedia_id'] ?? ''));
    if ($wid !== '' && isset($wahapediaNames[$wid])) {
        return $wahapediaNames[$wid];
    }
    return null;
}

/**
 * @return list<string>
 */
function buildWikiTitleCandidates(string $nameEn): array
{
    $name = trim($nameEn);
    $name = preg_replace('/（[^）]*）/u', '', $name) ?? $name;
    $name = trim($name);

    $candidates = [$name];

    if (str_contains($name, ',')) {
        $candidates[] = trim(explode(',', $name, 2)[0]);
    }

    if (preg_match('/^(.+?)\s+the\s+.+$/i', $name, $m)) {
        $candidates[] = trim($m[1]);
    }

    if (preg_match('/^(.+?)\s*\([^)]+\)\s*$/', $name, $m)) {
        $candidates[] = trim($m[1]);
    }

    if (str_contains($name, '-')) {
        $candidates[] = str_replace('-', ' ', $name);
    }

    if (preg_match('/^(Knight|Lord)\s+([A-Za-z].+)$/', $name, $m)) {
        $candidates[] = $m[1] . '-' . $m[2];
    }

    return uniqueNonEmpty($candidates);
}

/**
 * @return list<string>
 */
function buildFactionWikiTitleCandidates(string $nameEn): array
{
    $candidates = [$nameEn];
    foreach (FACTION_TITLE_ALIASES[$nameEn] ?? [] as $alias) {
        $candidates[] = $alias;
    }
    if (str_contains($nameEn, '-')) {
        $candidates[] = str_replace('-', ' ', $nameEn);
    }
    return uniqueNonEmpty($candidates);
}

/**
 * @param list<string> $candidates
 * @return list<string>
 */
function uniqueNonEmpty(array $candidates): array
{
    $out = [];
    foreach ($candidates as $c) {
        $c = trim((string)$c);
        if ($c !== '' && !in_array($c, $out, true)) {
            $out[] = $c;
        }
    }
    return $out;
}

/**
 * @return array{0:?string,1:?string,2:?string} [wikitext, resolvedTitle, error]
 */
function fetchWikitext(string $pageTitle): array
{
    $query = http_build_query([
        'action'        => 'parse',
        'page'          => $pageTitle,
        'prop'          => 'wikitext',
        'redirects'     => '1',
        'format'        => 'json',
        'formatversion' => '2',
    ]);
    $url = FANDOM_API . '?' . $query;

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_USERAGENT      => USER_AGENT,
        CURLOPT_HTTPHEADER     => ['Accept: application/json'],
    ]);
    $body = curl_exec($ch);
    $errno = curl_errno($ch);
    $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($errno !== 0 || $body === false) {
        return [null, null, 'curl error ' . $errno];
    }
    if ($http >= 400) {
        return [null, null, "HTTP {$http}"];
    }

    $json = json_decode($body, true);
    if (!is_array($json)) {
        return [null, null, 'invalid JSON'];
    }
    if (!empty($json['error'])) {
        $code = $json['error']['code'] ?? 'error';
        return [null, null, (string)$code];
    }

    $wikitext = $json['parse']['wikitext'] ?? null;
    if (is_array($wikitext) && isset($wikitext['*'])) {
        $wikitext = $wikitext['*'];
    }
    if (!is_string($wikitext) || trim($wikitext) === '') {
        return [null, null, 'no wikitext'];
    }

    $title = (string)($json['parse']['title'] ?? $pageTitle);
    return [$wikitext, $title, null];
}

function extractStoryPlain(string $wikitext, bool $forFaction = false): string
{
    $text = $wikitext;

    $text = preg_replace('/\[\[[a-z-]{2,12}:[^\]]*\]\]/u', '', $text) ?? $text;
    $text = preg_replace('/\[\[Category:[^\]]*\]\]/iu', '', $text) ?? $text;

    $cutSections = [
        'Sources', 'Source', 'Wargear', 'Miniatures', 'Gallery', 'Trivia',
        'See also', 'See Also', 'Notes', 'References', 'External links', 'Videos',
        'Military', 'Notable', 'Members', 'Units', 'Organisation', 'Organization',
    ];
    if (preg_match('/^==+\s*Known\b.*$/mi', $text, $m, PREG_OFFSET_CAPTURE)) {
        $text = substr($text, 0, $m[0][1]);
    }
    foreach ($cutSections as $sec) {
        if (preg_match('/^==+\s*' . preg_quote($sec, '/') . '\s*==+/mi', $text, $m, PREG_OFFSET_CAPTURE)) {
            $text = substr($text, 0, $m[0][1]);
        }
    }

    if (preg_match('/^(.*?)(^==+\s*Overview\s*==+\s*\n)(.*)$/msi', $text, $m)) {
        $lead = $m[1];
        $body = $m[3];
        if (preg_match('/^(.*?)(?=^==+\s)/ms', $body, $om)) {
            $body = $om[1];
        }
        $text = $lead . "\n" . trim($body);
    } elseif ($forFaction && preg_match('/^(.*?)(^==+\s*History\s*==+\s*\n)(.*)$/msi', $text, $m)) {
        $lead = $m[1];
        $body = $m[3];
        if (preg_match('/^(.*?)(?=^===\s)/ms', $body, $om)) {
            $body = $om[1];
        } elseif (preg_match('/^(.*?)(?=^==+\s)/ms', $body, $om)) {
            $body = $om[1];
        }
        $body = trim($body);
        if (mb_strlen($body) > 2500) {
            $body = mb_substr($body, 0, 2500);
            $body = preg_replace('/\s+\S*$/u', '', $body) ?? $body;
            $body .= '…';
        }
        $text = $lead . "\n" . $body;
    } else {
        if (preg_match('/^(.*?)(?=^==+\s)/ms', $text, $m)) {
            $text = $m[1];
        }
    }

    $text = stripWikitextMarkup($text);
    $text = preg_replace("/[ \t]+\n/", "\n", $text) ?? $text;
    $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;
    return trim($text);
}

function stripWikitextMarkup(string $text): string
{
    $text = preg_replace('/<!--.*?-->/s', '', $text) ?? $text;
    $text = preg_replace('/\{\{\s*Fn\s*\|[^}]*\}\}/iu', '', $text) ?? $text;
    $text = preg_replace('/\{\{\s*Endn\s*\|[^}]*\}\}/iu', '', $text) ?? $text;

    for ($i = 0; $i < 8; $i++) {
        $next = preg_replace('/\{\{[^{}]*\}\}/s', '', $text);
        if ($next === null || $next === $text) {
            break;
        }
        $text = $next;
    }

    $text = preg_replace('/\[\[(?:File|Image):[^\]]*\]\]/iu', '', $text) ?? $text;
    $text = preg_replace('/\[\[([^|\]]+)\|([^\]]+)\]\]/', '$2', $text) ?? $text;
    $text = preg_replace('/\[\[([^\]]+)\]\]/', '$1', $text) ?? $text;
    $text = preg_replace('/\[https?:\/\/[^\s\]]+\s+([^\]]+)\]/', '$1', $text) ?? $text;
    $text = preg_replace('/\[https?:\/\/[^\]]+\]/', '', $text) ?? $text;
    $text = str_replace(["'''''", "'''", "''"], '', $text);
    $text = preg_replace('/^==+\s*(.*?)\s*==+\s*$/m', '$1', $text) ?? $text;
    $text = preg_replace('/<ref\b[^>]*>.*?<\/ref>/is', '', $text) ?? $text;
    $text = preg_replace('/<br\s*\/?>/i', "\n", $text) ?? $text;
    $text = strip_tags($text);
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

    return $text;
}
