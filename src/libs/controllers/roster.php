<?php

class Roster extends Controller
{
	private $class_name = 'roster';

	public function __construct()
	{
		parent::__construct();
		Auth::handleLogin();
	}

	public function index()
	{
		$token = Session::setToken($this->class_name . '/index');
		$data = [
			'token' => $token,
			'js'    => [$this->class_name . '/index.js'],
			'roster_data'  => $this->model->getFactions(),
			'roster_error' => $this->pullFlash('roster_error'),
		];
		$this->view->render($this->class_name, 'index', 'ロスター作成', $data);
	}

	public function list()
	{
		$userId = Session::getUserInfo('user_id');
		$rosterSuccess = Session::get('roster_success');
		if ($rosterSuccess) {
			Session::set('roster_success', null);
		}
		$data = [
			'rosters'        => $this->model->getRostersByUser((int)$userId),
			'roster_success' => $rosterSuccess,
			'roster_error'   => $this->pullFlash('roster_error'),
			'delete_token'   => Session::setToken($this->class_name . '/delete'),
		];
		$this->view->render($this->class_name, 'list', 'ロスター一覧', $data);
	}

	public function delete()
	{
		if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
			header('Location: ' . URL . 'roster/list');
			exit;
		}

		$token = $_POST['token'] ?? '';
		if (!Session::checkToken($this->class_name . '/delete', $token)) {
			Session::set('roster_error', 'セッションが無効です。再度お試しください。');
			header('Location: ' . URL . 'roster/list');
			exit;
		}

		$userId = (int)Session::getUserInfo('user_id');
		$rosterId = isset($_POST['roster_id']) ? (int)$_POST['roster_id'] : 0;

		if ($rosterId <= 0) {
			Session::set('roster_error', '削除対象のロスターが不正です。');
			header('Location: ' . URL . 'roster/list');
			exit;
		}

		if ($this->model->deleteRoster($rosterId, $userId)) {
			Session::set('roster_success', 'ロスターを削除しました。');
		} else {
			Session::set('roster_error', 'ロスターの削除に失敗しました。');
		}

		header('Location: ' . URL . 'roster/list');
		exit;
	}

	public function edit($rosterId = '')
	{
		$userId = (int)Session::getUserInfo('user_id');
		$rosterId = (int)$rosterId;
		$rosterData = $this->model->getRosterWithDetails($rosterId, $userId);

		if (!$rosterData) {
			header('Location: ' . URL . 'roster/list');
			exit;
		}

		$roster = $rosterData['roster'];
		$faction_id = $roster['faction_id'];

		$raw_spells         = $this->model->getSpellLores($faction_id);
		$raw_prayers        = $this->model->getPrayerLores($faction_id);
		$raw_manifestations = $this->model->getManifestationLores($faction_id);

		$data = [
			'token' => Session::setToken($this->class_name . '/index'),
			'js' => [
				'match/phases.js',
				$this->class_name . '/create_units.js',
				$this->class_name . '/unit_detail.js',
				$this->class_name . '/get_units.js',
				$this->class_name . '/create_roster.js',
				$this->class_name . '/enhancements.js'
			],
			'roster_meta' => [
				'roster_name'    => $roster['name'],
				'grand_alliance' => $roster['grand_alliance'] ?? '',
				'faction_id'     => $faction_id,
				'faction_name'   => $roster['faction_name'] ?? '',
				'roster_points'  => $roster['point_limit'] ?? $roster['total_points'],
			],
			'edit_roster_id'      => $rosterId,
			'edit_roster_json'    => json_encode($rosterData, JSON_UNESCAPED_UNICODE),
			'battle_formations'   => $this->model->getBattleFormations($faction_id),
			'hero_units'          => $this->model->getHeroUnits($faction_id),
			'regiment_units'      => $this->model->getRegimentUnits($faction_id),
			'faction_terrain'     => $this->model->getFactionTerrain($faction_id),
			'spell_lores'         => $this->formatLoreData($raw_spells, 'spell'),
			'prayer_lores'        => $this->formatLoreData($raw_prayers, 'prayer'),
			'manifestation_lores' => $this->formatLoreData($raw_manifestations, 'manifestation'),
			'saved_options' => [
				'battle_formation'   => $roster['battle_formation_id'],
				'spell_lore'         => $roster['spell_lore_id'],
				'prayer_lore'        => $roster['prayer_lore_id'],
				'manifestation_lore' => $roster['manifestation_lore_id'],
				'faction_terrain'    => $roster['terrain_id'] ?? null,
			],
			'roster_error' => $this->pullFlash('roster_error'),
		];

		$this->view->render($this->class_name, 'create_roster', 'ロスター編集', $data);
	}

	public function create_roster()
	{
		// 1. $_GET からデータを受け取り
		$roster_name    = isset($_GET['roster_name']) ? trim($_GET['roster_name']) : '';
		$grand_alliance = isset($_GET['grand_alliance']) ? trim($_GET['grand_alliance']) : '';
		$faction_id     = isset($_GET['faction_id']) ? trim($_GET['faction_id']) : '';
		$faction_name   = $this->model->getFactionName($faction_id);
		$roster_points  = isset($_GET['roster_points']) ? trim($_GET['roster_points']) : '';

		// 2. Modelからシンプルな生データを取得
		$raw_spells         = $this->model->getSpellLores($faction_id);
		$raw_prayers        = $this->model->getPrayerLores($faction_id);
		$raw_manifestations = $this->model->getManifestationLores($faction_id);

		$token = Session::setToken($this->class_name . '/index');

		$hero_units     = $this->model->getHeroUnits($faction_id);
		$regiment_units = $this->model->getRegimentUnits($faction_id);

		// 3. 画面に渡すデータをまとめる
		$data = [
			'token' => $token,
			'js' => [
				'match/phases.js',
				$this->class_name . '/create_units.js',
				$this->class_name . '/unit_detail.js',
				$this->class_name . '/get_units.js',
				$this->class_name . '/create_roster.js',
				$this->class_name . '/enhancements.js'
			],
			'roster_meta' => [
				'roster_name'    => $roster_name,
				'grand_alliance' => $grand_alliance,
				'faction_id'     => $faction_id,
				'faction_name'   => $faction_name,
				'roster_points'  => $roster_points
			],
			'battle_formations'   => $this->model->getBattleFormations($faction_id),
			'hero_units'          => $hero_units,
			'regiment_units'      => $regiment_units,
			'faction_terrain'     => $this->model->getFactionTerrain((int)$faction_id),

			'spell_lores'         => $this->formatLoreData($raw_spells, 'spell'),
			'prayer_lores'        => $this->formatLoreData($raw_prayers, 'prayer'),
			'manifestation_lores' => $this->formatLoreData($raw_manifestations, 'manifestation'),
			'roster_error'        => $this->pullFlash('roster_error'),
		];

		$this->view->render($this->class_name, 'create_roster', 'ロスター作成', $data);
	}

	public function save()
	{
		if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
			header('Location: ' . URL . 'roster/index');
			exit;
		}

		$token = $_POST['token'] ?? '';
		if (!Session::checkToken($this->class_name . '/index', $token)) {
			Session::set('roster_error', 'セッションが無効です。再度お試しください。');
			header('Location: ' . URL . 'roster/index');
			exit;
		}

		$userId = (int)Session::getUserInfo('user_id');
		$rosterId = isset($_POST['roster_id']) ? (int)$_POST['roster_id'] : null;
		if ($rosterId <= 0) {
			$rosterId = null;
		}

		$data = [
			'roster_name'            => trim($_POST['roster_name'] ?? ''),
			'grand_alliance'         => trim($_POST['grand_alliance'] ?? ''),
			'faction_id'             => (int)($_POST['faction_id'] ?? 0),
			'roster_points'          => (int)($_POST['roster_points'] ?? 0),
			'battle_formation'       => $_POST['battle_formation'] ?? null,
			'spell_lore'             => $_POST['spell_lore'] ?? null,
			'prayer_lore'            => $_POST['prayer_lore'] ?? null,
			'manifestation_lore'     => $_POST['manifestation_lore'] ?? null,
			'faction_terrain'        => $_POST['faction_terrain'] ?? null,
			'general_regiment_index' => $_POST['general_regiment_index'] ?? 0,
			'heroic_trait_id'           => $_POST['heroic_trait_id'] ?? null,
			'trait_target_unit_id'      => $_POST['trait_target_unit_id'] ?? null,
			'trait_regiment_index'      => $_POST['trait_regiment_index'] ?? null,
			'trait_unit_slot'           => $_POST['trait_unit_slot'] ?? null,
			'artefact_id'               => $_POST['artefact_id'] ?? null,
			'artefact_target_unit_id'   => $_POST['artefact_target_unit_id'] ?? null,
			'artefact_regiment_index'   => $_POST['artefact_regiment_index'] ?? null,
			'artefact_unit_slot'        => $_POST['artefact_unit_slot'] ?? null,
			'regiments'              => $_POST['regiments'] ?? [],
		];

		if ($data['roster_name'] === '' || $data['faction_id'] <= 0) {
			Session::set('roster_error', 'ロスター名またはファクションが不正です。');
			header('Location: ' . URL . 'roster/index');
			exit;
		}

		[$ok, $result] = $this->model->saveRoster($userId, $data, $rosterId);

		if (!$ok) {
			Session::set('roster_error', $result);
			if ($rosterId) {
				header('Location: ' . URL . 'roster/edit/' . $rosterId);
			} else {
				header('Location: ' . URL . 'roster/create_roster?' . http_build_query([
					'roster_name'    => $data['roster_name'],
					'grand_alliance' => $data['grand_alliance'],
					'faction_id'     => $data['faction_id'],
					'roster_points'  => $data['roster_points'],
				]));
			}
			exit;
		}

		Session::set('roster_success', 'ロスターを保存しました。');
		header('Location: ' . URL . 'roster/list');
		exit;
	}

	private function pullFlash(string $key): ?string
	{
		$value = Session::get($key);
		if ($value) {
			Session::set($key, null);
		}
		return $value ?: null;
	}

	public function getByFaction($factionId = '')
	{
		header('Content-Type: application/json; charset=utf-8');

		$userId = (int)Session::getUserInfo('user_id');
		$factionId = (int)($factionId ?: ($_GET['faction_id'] ?? 0));

		if ($factionId <= 0) {
			echo json_encode([], JSON_UNESCAPED_UNICODE);
			exit;
		}

		$rosters = $this->model->getRostersByUserAndFaction($userId, $factionId);
		echo json_encode($rosters, JSON_UNESCAPED_UNICODE);
		exit;
	}

	public function load($rosterId = '')
	{
		header('Content-Type: application/json; charset=utf-8');

		$userId = (int)Session::getUserInfo('user_id');
		$rosterId = (int)$rosterId;

		$data = $this->model->getRosterWithDetails($rosterId, $userId);
		if (!$data) {
			http_response_code(404);
			echo json_encode(['message' => 'ロスターが見つかりません。'], JSON_UNESCAPED_UNICODE);
			exit;
		}

		echo json_encode($data, JSON_UNESCAPED_UNICODE);
		exit;
	}

	/**
	 * 各伝承の生データを、フロントエンドが期待するグループ化テキスト形式に加工する
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
					'points'        => $row['points']
				];
			}

			// ★ nl2br() を撤去！ htmlspecialchars だけにする
			$ability_name = htmlspecialchars($row[$name_key], ENT_QUOTES, 'UTF-8');
			$value        = htmlspecialchars($row[$val_key], ENT_QUOTES, 'UTF-8');
			$effect       = htmlspecialchars($row['effect'], ENT_QUOTES, 'UTF-8');
			$keywords     = htmlspecialchars($row['keywords'], ENT_QUOTES, 'UTF-8');

			// ★ HTML内の繋ぎ目を <br> から \n（改行コード）に変更
			$text = "<strong>【{$ability_name}】</strong> ({$label}: {$value})\n"
				. "{$effect}\n"
				. "<span class='keywords-label'>Keywords:</span> {$keywords}";

			// ★ フレーバーテキストも nl2br を外し、\n でドッキング
			if (!empty($row['flavor_text'])) {
				$flavor = htmlspecialchars($row['flavor_text'], ENT_QUOTES, 'UTF-8');
				$text .= "\n<small class='text-muted lore-flavor'>{$flavor}</small>";
			}

			$lores[$lore_name]['texts'][] = $text;
		}

		// 各アビリティブロックの区切り（ここも \n を絡めることで pre-wrap と綺麗に調和します）
		foreach ($lores as $name => $lore) {
			$lores[$name]['combined_effect'] = implode("\n<hr class='lore-divider'>\n", $lore['texts']);
			unset($lores[$name]['texts']);
		}

		return array_values($lores);
	}

	public function getUnits($faction_id = '')
	{
		header('Content-Type: application/json; charset=utf-8');

		try {
			if (empty($faction_id) && isset($_GET['faction_id'])) {
				$faction_id = $_GET['faction_id'];
			}

			$type = isset($_GET['type']) ? trim($_GET['type']) : 'regiment';
			$hero_id = isset($_GET['hero_id']) ? intval($_GET['hero_id']) : 0;

			if ($hero_id > 0 && $type === 'unit') {
				$type = 'regiment';
			}

			if ($type === 'regiment') {
				if ($hero_id <= 0) {
					$units = [];
				} else {
					$units = $this->model->getCompanionUnitsForHero($hero_id, (int)$faction_id);
					if (empty($units)) {
						$units = $this->model->getRegimentUnits((int)$faction_id);
					}
				}
			} else {
				$units = $this->model->getHeroUnits($faction_id);
			}

			echo json_encode($this->normalizeUnitList($units), JSON_UNESCAPED_UNICODE);
			exit;
		} catch (\Exception $e) {
			http_response_code(500);
			echo json_encode(['message' => '取得失敗'], JSON_UNESCAPED_UNICODE);
			exit;
		}
	}

	/**
	 * 🌟 詳細ボタンが押された瞬間に、特定のユニットの詳細データ一式をJSONで返す
	 */
	public function getUnitDetail()
	{
		// レスポンスのヘッダーをJSON形式に指定
		header('Content-Type: application/json; charset=utf-8');

		try {
			$unit_id = isset($_GET['unit_id']) ? intval($_GET['unit_id']) : 0;

			if ($unit_id <= 0) {
				http_response_code(400);
				echo json_encode(['message' => '無効なユニットIDです。'], JSON_UNESCAPED_UNICODE);
				exit;
			}

			// 1. ユニットのコアステータス（移動力、傷、セーヴ、コントロールなど）を取得
			// モデル側に単一ユニットをIDで引くメソッド（例: getUnitById）がある想定
			$unitinfo = [];
			if (method_exists($this->model, 'getUnitById')) {
				$unitinfo = $this->model->getUnitById($unit_id);
			}

			if (!$unitinfo) {
				// 万が一ベースデータが取れなかった場合のフォールバック
				$unitinfo = ['id' => $unit_id];
			}

			// 2. 武器データの取得
			$weapons = [];
			if (method_exists($this->model, 'getUnitWeapons')) {
				$weapons = $this->model->getUnitWeapons($unit_id) ?: [];
			}

			// 3. アビリティデータの取得
			$abilities = [];
			if (method_exists($this->model, 'getUnitAbilities')) {
				$abilities = $this->model->getUnitAbilities($unit_id) ?: [];
			}

			// すべてを一つの器にまとめてフロントへ返却
			echo json_encode([
				'info'      => $unitinfo,
				'weapons'   => $weapons,
				'abilities' => $abilities
			], JSON_UNESCAPED_UNICODE);
			exit;
		} catch (\Exception $e) {
			http_response_code(500);
			echo json_encode([
				'success' => false,
				'message' => '詳細データの取得に失敗しました。'
			], JSON_UNESCAPED_UNICODE);
			exit;
		}
	}

	public function getEnhancements()
	{
		header('Content-Type: application/json; charset=utf-8');

		$factionId = (int)($_GET['faction_id'] ?? 0);
		if ($factionId <= 0) {
			echo json_encode(['traits' => [], 'artefacts' => []], JSON_UNESCAPED_UNICODE);
			exit;
		}

		echo json_encode([
			'traits'    => $this->model->getHeroicTraitsForFaction($factionId),
			'artefacts' => $this->model->getArtefactsForFaction($factionId),
		], JSON_UNESCAPED_UNICODE);
		exit;
	}

	private function normalizeUnitList(array $units): array
	{
		return array_values(array_map(function ($u) {
			$row = [
				'id'            => (int)($u['id'] ?? 0),
				'name'          => $u['name'] ?? '',
				'points'        => (int)($u['points'] ?? 0),
				'keywords'      => $u['keywords'] ?? '',
				'unit_size'     => (int)($u['unit_size'] ?? 1),
				'is_hero'       => (int)($u['is_hero'] ?? 0),
				'can_reinforce' => (int)($u['can_reinforce'] ?? 0),
				'is_general'    => (int)($u['is_general'] ?? 0),
				'is_unique'     => (int)($u['is_unique'] ?? 0),
			];
			// 随伴ユニット候補: この HERO 文脈で適格な連隊枠 option_id 群
			if (array_key_exists('option_ids', $u)) {
				$row['option_ids'] = $this->model->parseOptionIds($u['option_ids'] ?? null);
			}
			// HERO 候補: 編成枠と上限(枠割当UI/上限判定用)
			if (array_key_exists('regiment_option_limits', $u)) {
				$row['regiment_option_limits'] = $u['regiment_option_limits'] ?: [];
			}
			return $row;
		}, $units));
	}
}
