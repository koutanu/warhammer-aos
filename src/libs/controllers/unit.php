<?php

class Unit extends Controller
{
	private $class_name = 'unit';

	public function __construct()
	{
		parent::__construct();
		Auth::handleLogin();
	}

	/**
	 * 図鑑トップ: グランドアライアンス別のファクション一覧
	 */
	function index()
	{
		$data = [
			'token'    => Session::setToken($this->class_name . '/index'),
			'js'       => [$this->class_name . '/index.js'],
			'factions' => $this->model->getFactionsGrouped(),
			'is_admin' => Auth::isAdmin(),
		];
		$this->view->render($this->class_name, 'index', 'ユニット図鑑', $data);
	}

	/**
	 * 指定ファクションのユニット一覧（閲覧 + 管理者向け操作）
	 */
	function faction($factionId = '')
	{
		$factionId = (int)$factionId;
		$faction = $this->model->getFactionById($factionId);
		if (!$faction) {
			header('Location: ' . URL . 'unit/index');
			exit;
		}

		$data = [
			'token'    => Session::setToken($this->class_name . '/index'),
			'js'       => [
				'match/phases.js',
				'roster/unit_detail.js',
				$this->class_name . '/index.js',
			],
			'faction'  => $faction,
			'units'    => $this->model->getUnitsByFaction($factionId),
			'faction_terrain'     => $this->model->getFactionTerrain($factionId),
			'manifestation_details' => $this->model->getManifestationLores($factionId),
			'battle_formations'   => $this->model->getBattleFormations($factionId),
			'spell_lores'         => $this->formatLoreData($this->model->getSpellLores($factionId), 'spell'),
			'prayer_lores'        => $this->formatLoreData($this->model->getPrayerLores($factionId), 'prayer'),
			'heroic_traits'       => $this->model->getHeroicTraitsForFaction($factionId),
			'artefacts'           => $this->model->getArtefactsForFaction($factionId),
			'is_admin' => Auth::isAdmin(),
			'unit_error'   => $this->pullFlash('unit_error'),
			'unit_success' => $this->pullFlash('unit_success'),
		];
		$this->view->render($this->class_name, 'faction', 'ユニット一覧: ' . $faction['name'], $data);
	}

	/**
	 * 新規ユニット作成フォーム（管理者のみ）
	 */
	function create()
	{
		Auth::requireAdmin();

		$factionId = (int)($_GET['faction_id'] ?? 0);
		$faction = $factionId > 0 ? $this->model->getFactionById($factionId) : null;

		$data = [
			'token'        => Session::setToken($this->class_name . '/index'),
			'js'           => [$this->class_name . '/edit.js'],
			'is_new'       => true,
			'unit'         => null,
			'faction'      => $faction,
			'factions'     => $this->model->getFactionsGrouped(),
			'weapons'      => [],
			'abilities'    => [],
			'all_abilities' => $this->model->getAllAbilities(),
			'regiment_options'        => $factionId > 0 ? $this->model->getFactionRegimentOptions($factionId) : [],
			'unit_eligibility_ids'    => [],
			'hero_regiment_option_map' => [],
			'unit_error'   => $this->pullFlash('unit_error'),
		];
		$this->view->render($this->class_name, 'edit', 'ユニット新規作成', $data);
	}

	/**
	 * 既存ユニット編集フォーム（管理者のみ）
	 */
	function edit($unitId = '')
	{
		Auth::requireAdmin();

		$unitId = (int)$unitId;
		$unit = $this->model->getUnitById($unitId);
		if (!$unit) {
			header('Location: ' . URL . 'unit/index');
			exit;
		}

		$factionId = (int)$unit['faction_id'];
		$data = [
			'token'        => Session::setToken($this->class_name . '/index'),
			'js'           => [$this->class_name . '/edit.js'],
			'is_new'       => false,
			'unit'         => $unit,
			'faction'      => $this->model->getFactionById($factionId),
			'factions'     => $this->model->getFactionsGrouped(),
			'weapons'      => $this->model->getUnitWeapons($unitId),
			'abilities'    => $this->model->getUnitAbilities($unitId),
			'all_abilities' => $this->model->getAllAbilities(),
			'regiment_options'        => $this->model->getFactionRegimentOptions($factionId),
			'unit_eligibility_ids'    => $this->model->getUnitEligibilityOptionIds($unitId),
			'hero_regiment_option_map' => $this->model->getHeroRegimentOptionMap($unitId),
			'unit_error'   => $this->pullFlash('unit_error'),
		];
		$this->view->render($this->class_name, 'edit', 'ユニット編集: ' . $unit['name'], $data);
	}

	/**
	 * 保存（新規/更新 共通・管理者のみ・POST + CSRF）
	 */
	function save()
	{
		Auth::requireAdmin();

		if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
			header('Location: ' . URL . 'unit/index');
			exit;
		}

		$token = $_POST['token'] ?? '';
		if (!Session::checkToken($this->class_name . '/index', $token)) {
			Session::set('unit_error', 'セッションが無効です。再度お試しください。');
			header('Location: ' . URL . 'unit/index');
			exit;
		}

		$unitId = isset($_POST['unit_id']) ? (int)$_POST['unit_id'] : 0;
		$unitId = $unitId > 0 ? $unitId : null;

		$data = [
			'faction_id' => (int)($_POST['faction_id'] ?? 0),
			'name'       => trim($_POST['name'] ?? ''),
			'movement'   => $_POST['movement'] ?? null,
			'wounds'     => $_POST['wounds'] ?? null,
			'save'       => $_POST['save'] ?? null,
			'control'    => $_POST['control'] ?? null,
			'points'     => $_POST['points'] ?? null,
			'unit_size'  => $_POST['unit_size'] ?? null,
			'base_size'  => $_POST['base_size'] ?? null,
			'unit_keywords'    => $_POST['unit_keywords'] ?? null,
			'faction_keywords' => $_POST['faction_keywords'] ?? null,
			'flavor_text' => $_POST['flavor_text'] ?? null,
			'image'      => $_POST['image'] ?? null,
			'is_hidden'  => !empty($_POST['is_hidden']) ? 1 : 0,
			'is_hero'       => !empty($_POST['is_hero']) ? 1 : 0,
			'can_reinforce' => !empty($_POST['can_reinforce']) ? 1 : 0,
			'is_general'    => !empty($_POST['is_general']) ? 1 : 0,
			'is_unique'     => !empty($_POST['is_unique']) ? 1 : 0,
			'is_terrain'    => !empty($_POST['is_terrain']) ? 1 : 0,
			'is_manifestation' => !empty($_POST['is_manifestation']) ? 1 : 0,
			'weapons'    => $this->normalizePostArray($_POST['weapons'] ?? []),
			'abilities'  => $this->normalizePostArray($_POST['abilities'] ?? []),
			'regiment_eligibility'  => is_array($_POST['regiment_eligibility'] ?? null) ? $_POST['regiment_eligibility'] : [],
			'hero_regiment_options' => $this->normalizeHeroRegimentOptions($_POST['hero_regiment_options'] ?? []),
		];

		[$ok, $result] = $this->model->saveUnit($data, $unitId);

		if (!$ok) {
			Session::set('unit_error', $result);
			if ($unitId) {
				header('Location: ' . URL . 'unit/edit/' . $unitId);
			} else {
				header('Location: ' . URL . 'unit/create?faction_id=' . (int)$data['faction_id']);
			}
			exit;
		}

		// 画像アップロード（任意）。本体保存後に処理し image カラムを更新する。
		$savedUnitId = (int)$result;
		if (!empty($_FILES['image_file']) && (int)($_FILES['image_file']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
			$faction = $this->model->getFactionById((int)$data['faction_id']);
			[$imgOk, $imgResult] = $this->handleImageUpload($_FILES['image_file'], $faction, $savedUnitId);
			if ($imgOk) {
				$this->model->updateImage($savedUnitId, $imgResult);
			} else {
				Session::set('unit_error', $imgResult);
				header('Location: ' . URL . 'unit/edit/' . $savedUnitId);
				exit;
			}
		}

		Session::set('unit_success', 'ユニットを保存しました。');
		header('Location: ' . URL . 'unit/faction/' . (int)$data['faction_id']);
		exit;
	}

	/**
	 * ユニット画像を www/assets/images/{大同盟}/{ファクション}/ に保存する。
	 *
	 * @return array [bool $ok, string $relativePathOrMessage]
	 */
	private function handleImageUpload(array $file, ?array $faction, int $unitId): array
	{
		if (!$faction) {
			return [false, '画像保存先のファクションが特定できませんでした。'];
		}
		if (!is_uploaded_file($file['tmp_name'] ?? '')) {
			return [false, '不正なアップロードです。'];
		}

		$maxBytes = 5 * 1024 * 1024; // 5MB
		if (($file['size'] ?? 0) > $maxBytes) {
			return [false, '画像サイズが大きすぎます（最大5MB）。'];
		}

		// MIME と拡張子の検証
		$allowed = [
			'image/jpeg' => 'jpg',
			'image/png'  => 'png',
			'image/gif'  => 'gif',
			'image/webp' => 'webp',
		];
		$info = @getimagesize($file['tmp_name']);
		$mime = $info['mime'] ?? '';
		if (!isset($allowed[$mime])) {
			return [false, '対応していない画像形式です（jpg/png/gif/webp のみ）。'];
		}
		$ext = $allowed[$mime];

		// 保存先ディレクトリ: assets/images/{alliance}/{faction}/
		$allianceSlug = $this->slugify($faction['grand_alliance'] ?? 'other');
		$factionSlug  = $this->slugify($faction['name_en'] ?? ($faction['name'] ?? ('faction_' . $faction['id'])));
		if ($factionSlug === '') {
			$factionSlug = 'faction_' . (int)$faction['id'];
		}

		$relativeDir = 'assets/images/' . $allianceSlug . '/' . $factionSlug;
		$absoluteDir = rtrim(DOC_ROOT, '/\\') . '/' . $relativeDir;

		if (!is_dir($absoluteDir) && !mkdir($absoluteDir, 0775, true) && !is_dir($absoluteDir)) {
			return [false, '画像保存ディレクトリの作成に失敗しました。'];
		}

		// 同一ユニットの既存画像（拡張子違い含む）を削除してから保存
		foreach (glob($absoluteDir . '/unit_' . $unitId . '.*') ?: [] as $old) {
			@unlink($old);
		}

		$fileName = 'unit_' . $unitId . '.' . $ext;
		$absolutePath = $absoluteDir . '/' . $fileName;

		if (!move_uploaded_file($file['tmp_name'], $absolutePath)) {
			return [false, '画像の保存に失敗しました。'];
		}

		return [true, $relativeDir . '/' . $fileName];
	}

	/**
	 * 文字列をスラッグ化（小文字・英数字以外はアンダースコアに）
	 */
	private function slugify(string $value): string
	{
		$value = strtolower(trim($value));
		$value = preg_replace('/[^a-z0-9]+/', '_', $value);
		return trim($value, '_');
	}

	/**
	 * ロスター作成画面での表示/非表示の切替（管理者のみ・POST + CSRF・JSON）
	 */
	function toggleVisibility()
	{
		header('Content-Type: application/json; charset=utf-8');

		if (!Auth::isAdmin()) {
			http_response_code(403);
			echo json_encode(['ok' => false, 'message' => '権限がありません。'], JSON_UNESCAPED_UNICODE);
			exit;
		}

		if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
			http_response_code(405);
			echo json_encode(['ok' => false, 'message' => 'メソッドが不正です。'], JSON_UNESCAPED_UNICODE);
			exit;
		}

		$token = $_POST['token'] ?? '';
		if (!Session::checkToken($this->class_name . '/index', $token)) {
			http_response_code(400);
			echo json_encode(['ok' => false, 'message' => 'セッションが無効です。'], JSON_UNESCAPED_UNICODE);
			exit;
		}

		$unitId = (int)($_POST['unit_id'] ?? 0);
		$isHidden = !empty($_POST['is_hidden']) ? 1 : 0;
		if ($unitId <= 0) {
			http_response_code(400);
			echo json_encode(['ok' => false, 'message' => '無効なユニットIDです。'], JSON_UNESCAPED_UNICODE);
			exit;
		}

		[$ok, $result] = $this->model->toggleVisibility($unitId, $isHidden);
		if (!$ok) {
			http_response_code(500);
			echo json_encode(['ok' => false, 'message' => $result], JSON_UNESCAPED_UNICODE);
			exit;
		}

		echo json_encode(['ok' => true, 'is_hidden' => (int)$result], JSON_UNESCAPED_UNICODE);
		exit;
	}

	/**
	 * 伝承データ（呪文/祈祷/顕現）を伝承名ごとにまとめ、
	 * 表示用の combined_effect（整形済みHTML文字列）を生成する。
	 * roster コントローラーの同名処理と同等。
	 */
	private function formatLoreData(array $raw_data, string $type): array
	{
		$lores = [];

		$name_key = $type . '_name';
		$val_key  = ($type === 'prayer') ? 'chanting_value' : 'casting_value';
		$label    = ($type === 'prayer') ? 'Chanting' : 'Casting';

		foreach ($raw_data as $row) {
			$lore_name = $row['lore_name'];

			if (!isset($lores[$lore_name])) {
				$lores[$lore_name] = [
					'id'            => $row['id'],
					'lore_name'     => $lore_name,
					'trigger_phase' => 'YOUR HERO PHASE',
					'texts'         => [],
					'points'        => $row['points'],
				];
			}

			$ability_name = htmlspecialchars($row[$name_key] ?? '', ENT_QUOTES, 'UTF-8');
			$value        = htmlspecialchars((string)($row[$val_key] ?? ''), ENT_QUOTES, 'UTF-8');
			$effect       = htmlspecialchars($row['effect'] ?? '', ENT_QUOTES, 'UTF-8');
			$keywords     = htmlspecialchars($row['keywords'] ?? '', ENT_QUOTES, 'UTF-8');

			$text = "<strong>【{$ability_name}】</strong> ({$label}: {$value})\n"
				. "{$effect}\n"
				. "<span class='keywords-label'>Keywords:</span> {$keywords}";

			if (!empty($row['flavor_text'])) {
				$flavor = htmlspecialchars($row['flavor_text'], ENT_QUOTES, 'UTF-8');
				$text .= "\n<small class='text-muted lore-flavor'>{$flavor}</small>";
			}

			$lores[$lore_name]['texts'][] = $text;
		}

		foreach ($lores as $name => $lore) {
			$lores[$name]['combined_effect'] = implode("\n<hr class='lore-divider'>\n", $lore['texts']);
			unset($lores[$name]['texts']);
		}

		return array_values($lores);
	}

	/**
	 * name="weapons[0][name]" 形式の POST 配列を 0 始まりの連番配列に正規化する
	 */
	private function normalizePostArray($arr): array
	{
		if (!is_array($arr)) {
			return [];
		}
		return array_values($arr);
	}

	/**
	 * HERO 連隊枠の POST を [option_id => max_limit] に正規化する。
	 * 入力形式: hero_regiment_options[<option_id>][enabled]=1, [max_limit]=N
	 * enabled が立っているものだけを返す（max_limit 未入力は 0 = 無制限扱い）。
	 */
	private function normalizeHeroRegimentOptions($arr): array
	{
		if (!is_array($arr)) {
			return [];
		}
		$map = [];
		foreach ($arr as $optionId => $row) {
			$optionId = (int)$optionId;
			if ($optionId <= 0 || !is_array($row) || empty($row['enabled'])) {
				continue;
			}
			$map[$optionId] = max(0, (int)($row['max_limit'] ?? 0));
		}
		return $map;
	}

	private function pullFlash(string $key): ?string
	{
		$value = Session::get($key);
		if ($value) {
			Session::set($key, null);
		}
		return $value ?: null;
	}
}
