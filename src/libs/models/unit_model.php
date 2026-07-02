<?php

class Unit_Model extends Model
{
	public function __construct()
	{
		parent::__construct();
	}

	// =========================================================================
	// 取得系
	// =========================================================================

	/**
	 * 全ファクションをグランドアライアンス別にまとめて返す（図鑑トップ用）
	 */
	public function getFactionsGrouped()
	{
		$sql = "SELECT * FROM m_factions WHERE is_hidden = 0 ORDER BY grand_alliance ASC, name ASC, id ASC;";
		$rows = $this->db->select($sql);

		$grouped = [];
		foreach ($rows as $row) {
			$alliance = $row['grand_alliance'] ?? 'other';
			if ($alliance === null || $alliance === '') {
				$alliance = 'other';
			}
			$grouped[$alliance][] = $row;
		}
		return $grouped;
	}

	public function getFactionById($factionId)
	{
		$sql = "SELECT * FROM m_factions WHERE id = :id LIMIT 1;";
		$rows = $this->db->select($sql, ['id' => (int)$factionId]);
		return !empty($rows) ? $rows[0] : null;
	}

	/**
	 * 指定ファクションのユニット一覧（is_hidden も含めて図鑑表示用に取得）
	 * 陣営地形(is_terrain)・顕現(is_manifestation)は専用セクションで扱うため除外する。
	 */
	public function getUnitsByFaction($factionId)
	{
		$sql = "SELECT u.id, u.name, u.points,
                       CONCAT_WS(', ', NULLIF(u.unit_keywords, ''), NULLIF(u.faction_keywords, ''), NULLIF(UPPER(f.grand_alliance), ''), NULLIF(UPPER(f.name_en), '')) AS keywords,
                       u.unit_size, u.is_hidden, u.is_hero, u.can_reinforce, u.image
                FROM m_units u
                JOIN m_factions f ON f.id = u.faction_id
                WHERE u.faction_id = :id
                  AND (u.is_terrain = 0 OR u.is_terrain IS NULL)
                  AND (u.is_manifestation = 0 OR u.is_manifestation IS NULL)
                ORDER BY u.is_hidden ASC, u.is_hero DESC, u.name ASC, u.id ASC;";
		return $this->db->select($sql, ['id' => (int)$factionId]);
	}

	/**
	 * 指定ファクションの陣営地形(ファクションテレイン)一覧。
	 * 各地形はプロファイル・武器・アビリティをまとめた配列で返す。
	 */
	public function getFactionTerrain($factionId)
	{
		return $this->getSpecialUnitsByFlag((int)$factionId, 'is_terrain');
	}

	/**
	 * 指定ファクションの顕現(マニフェステーション)一覧。
	 * 各顕現はプロファイル・武器・アビリティをまとめた配列で返す。
	 */
	public function getFactionManifestations($factionId)
	{
		return $this->getSpecialUnitsByFlag((int)$factionId, 'is_manifestation');
	}

	/**
	 * 指定フラグ(is_terrain / is_manifestation)が立ったユニットを
	 * プロファイル＋武器＋アビリティ付きで取得する共通処理。
	 */
	private function getSpecialUnitsByFlag(int $factionId, string $flagColumn): array
	{
		$allowed = ['is_terrain', 'is_manifestation'];
		if (!in_array($flagColumn, $allowed, true)) {
			return [];
		}

		$sql = "SELECT u.*,
                       CONCAT_WS(', ', NULLIF(u.unit_keywords, ''), NULLIF(u.faction_keywords, ''), NULLIF(UPPER(f.grand_alliance), ''), NULLIF(UPPER(f.name_en), '')) AS keywords
                FROM m_units u
                JOIN m_factions f ON f.id = u.faction_id
                WHERE u.faction_id = :id AND u.{$flagColumn} = 1
                ORDER BY u.name ASC, u.id ASC;";
		$rows = $this->db->select($sql, ['id' => $factionId]);

		foreach ($rows as &$row) {
			$row['weapons']   = $this->getUnitWeapons((int)$row['id']);
			$row['abilities'] = $this->getUnitAbilities((int)$row['id']);
		}
		unset($row);

		return $rows;
	}

	public function getUnitById($unitId)
	{
		$sql = "SELECT u.*,
                       CONCAT_WS(', ', NULLIF(u.unit_keywords, ''), NULLIF(u.faction_keywords, ''), NULLIF(UPPER(f.grand_alliance), ''), NULLIF(UPPER(f.name_en), '')) AS keywords
                FROM m_units u
                JOIN m_factions f ON f.id = u.faction_id
                WHERE u.id = :id LIMIT 1;";
		$rows = $this->db->select($sql, ['id' => (int)$unitId]);
		return !empty($rows) ? $rows[0] : null;
	}

	public function getUnitWeapons($unitId)
	{
		$sql = "SELECT * FROM m_unit_weapons WHERE unit_id = :unit_id ORDER BY id ASC;";
		return $this->db->select($sql, ['unit_id' => (int)$unitId]);
	}

	/**
	 * ユニットに紐づく能力（m_ability_master を JOIN）
	 */
	public function getUnitAbilities($unitId)
	{
		$sql = "SELECT m.id, m.name, m.command_point, m.casting_value, m.casting_type, m.trigger_phase, m.trigger_turn, m.activation, m.usage_scope, m.usage_per, m.trigger_condition_en, m.trigger_condition_ja, m.icon_type, m.effect, m.flavor_text, m.keywords
                FROM m_unit_abilities AS ua
                JOIN m_ability_master AS m ON ua.ability_id = m.id
                WHERE ua.unit_id = :unit_id
                ORDER BY m.id ASC;";
		return $this->db->select($sql, ['unit_id' => (int)$unitId]);
	}

	/**
	 * 既存からアタッチする用の能力候補一覧
	 */
	public function getAllAbilities()
	{
		$sql = "SELECT id, name, command_point, casting_value, casting_type, trigger_phase, trigger_turn, activation, usage_scope, usage_per, trigger_condition_en, trigger_condition_ja, icon_type, effect, flavor_text, keywords
                FROM m_ability_master
                ORDER BY name ASC, id ASC;";
		return $this->db->select($sql);
	}

	// =========================================================================
	// 連隊オプション（ユニット編集での紐づけ用）
	// =========================================================================

	/**
	 * 指定ファクションに紐づく連隊オプション候補一覧
	 */
	public function getFactionRegimentOptions($factionId)
	{
		$sql = "SELECT id, option_name FROM m_regiment_options
                WHERE faction_id = :id  ORDER BY sort_order ASC, option_name ASC, id ASC;";
		return $this->db->select($sql, ['id' => (int)$factionId]);
	}

	/**
	 * ユニットが所属できる連隊オプションの option_id 配列
	 */
	public function getUnitEligibilityOptionIds($unitId)
	{
		$sql = "SELECT option_id FROM t_unit_regiment_eligibility WHERE unit_id = :id;";
		$rows = $this->db->select($sql, ['id' => (int)$unitId]);
		return array_map(static fn($r) => (int)$r['option_id'], $rows);
	}

	/**
	 * HERO が連隊に編成できるオプション枠を [option_id => max_limit] で返す
	 */
	public function getHeroRegimentOptionMap($unitId)
	{
		$sql = "SELECT option_id, max_limit FROM t_hero_regiment_options WHERE hero_unit_id = :id;";
		$rows = $this->db->select($sql, ['id' => (int)$unitId]);
		$map = [];
		foreach ($rows as $r) {
			$map[(int)$r['option_id']] = (int)$r['max_limit'];
		}
		return $map;
	}

	// =========================================================================
	// 軍勢オプション（図鑑のファクション単位表示用）
	// =========================================================================

	public function getBattleFormations($factionId)
	{
		$sql = "SELECT * FROM m_battle_formations WHERE faction_id = :id AND is_hidden = 0 ORDER BY formation_name ASC, id ASC;";
		return $this->db->select($sql, ['id' => (int)$factionId]);
	}

	public function getSpellLores($factionId)
	{
		$sql = "SELECT * FROM m_spell_lores WHERE faction_id = :id ORDER BY lore_name ASC, id ASC;";
		return $this->db->select($sql, ['id' => (int)$factionId]);
	}

	public function getPrayerLores($factionId)
	{
		$sql = "SELECT * FROM m_prayer_lores WHERE faction_id = :id ORDER BY lore_name ASC, id ASC;";
		return $this->db->select($sql, ['id' => (int)$factionId]);
	}

	public function getManifestationLores($factionId)
	{
		$sql = "SELECT * FROM m_manifestation_lores WHERE faction_id = :id ORDER BY lore_name ASC, id ASC;";
		return $this->db->select($sql, ['id' => (int)$factionId]);
	}

	public function getBattleTraitsForFaction($factionId)
	{
		$sql = "SELECT id, faction_id, sub_faction_name, name, command_point, trigger_phase, trigger_turn, activation, usage_scope, usage_per, trigger_condition_ja, effect, keywords, flavor_text
                FROM m_battle_traits
                WHERE faction_id = :id
                ORDER BY sub_faction_name ASC, id ASC;";
		return $this->db->select($sql, ['id' => (int)$factionId]);
	}

	public function getHeroicTraitsForFaction($factionId)
	{
		$sql = "SELECT id, category, name, points, is_hero_only, trigger_phase, trigger_turn, activation, usage_scope, usage_per, trigger_condition_ja, effect, description
                FROM m_heroic_traits
                WHERE faction_id = :id AND is_hidden = 0 
                ORDER BY category ASC, name ASC;";
		return $this->db->select($sql, ['id' => (int)$factionId]);
	}

	public function getArtefactsForFaction($factionId)
	{
		$sql = "SELECT id, category, name, points, is_hero_only, trigger_phase, trigger_turn, activation, usage_scope, usage_per, trigger_condition_ja, effect, flavor_text
                FROM m_artefacts_of_power
                WHERE faction_id = :id AND is_hidden = 0 
                ORDER BY category ASC, name ASC;";
		return $this->db->select($sql, ['id' => (int)$factionId]);
	}

	// =========================================================================
	// 保存系
	// =========================================================================

	/**
	 * ユニットの新規作成 / 更新（コア + 武器 + 能力）
	 *
	 * @param array    $data   コア・武器・能力をまとめた配列
	 * @param int|null $unitId 既存ユニットID（null なら新規）
	 * @return array [bool $ok, int|string $resultOrMessage]
	 */
	public function saveUnit(array $data, ?int $unitId = null)
	{
		$name = trim($data['name'] ?? '');
		if ($name === '') {
			return [false, 'ユニット名を入力してください。'];
		}
		$factionId = (int)($data['faction_id'] ?? 0);
		if ($factionId <= 0) {
			return [false, 'ファクションが不正です。'];
		}

		$core = [
			'faction_id' => $factionId,
			'name'       => mb_substr($name, 0, 255),
			'movement'   => $this->nullableStr($data['movement'] ?? null),
			'wounds'     => $this->nullableStr($data['wounds'] ?? null),
			'save'       => $this->nullableStr($data['save'] ?? null),
			'control'    => $this->nullableStr($data['control'] ?? null),
			'points'     => $this->nullableInt($data['points'] ?? null),
			'unit_size'  => $this->nullableInt($data['unit_size'] ?? null),
			'base_size'  => $this->nullableStr($data['base_size'] ?? null),
			'unit_keywords'    => $this->nullableStr($data['unit_keywords'] ?? null),
			'faction_keywords' => $this->nullableStr($data['faction_keywords'] ?? null),
			'flavor_text' => $this->nullableStr($data['flavor_text'] ?? null),
			'image'      => $this->nullableStr($data['image'] ?? null),
			'is_hidden'  => !empty($data['is_hidden']) ? 1 : 0,
			'is_hero'       => !empty($data['is_hero']) ? 1 : 0,
			'can_reinforce' => !empty($data['can_reinforce']) ? 1 : 0,
			'is_general'    => !empty($data['is_general']) ? 1 : 0,
			'is_unique'     => !empty($data['is_unique']) ? 1 : 0,
			'is_terrain'    => !empty($data['is_terrain']) ? 1 : 0,
			'is_manifestation' => !empty($data['is_manifestation']) ? 1 : 0,
		];

		try {
			$this->db->beginTransaction();

			if ($unitId) {
				$existing = $this->getUnitById($unitId);
				if (!$existing) {
					$this->db->rollBack();
					return [false, 'ユニットが見つかりません。'];
				}
				$set = [];
				foreach (array_keys($core) as $col) {
					$set[] = "`{$col}` = :{$col}";
				}
				$sql = "UPDATE m_units SET " . implode(', ', $set) . " WHERE id = :id;";
				$stmt = $this->db->prepare($sql);
				foreach ($core as $k => $v) {
					$stmt->bindValue(":{$k}", $v);
				}
				$stmt->bindValue(':id', (int)$unitId, PDO::PARAM_INT);
				$stmt->execute();
			} else {
				$cols = array_keys($core);
				$sql = "INSERT INTO m_units (" . implode(', ', $cols) . ") VALUES (:" . implode(', :', $cols) . ");";
				$stmt = $this->db->prepare($sql);
				foreach ($core as $k => $v) {
					$stmt->bindValue(":{$k}", $v);
				}
				$stmt->execute();
				$unitId = (int)$this->db->lastInsertId();
			}

			$this->replaceUnitWeapons((int)$unitId, $data['weapons'] ?? []);
			$this->syncUnitAbilities((int)$unitId, $data['abilities'] ?? []);
			$this->syncUnitRegimentEligibility((int)$unitId, $factionId, $data['regiment_eligibility'] ?? []);
			$this->syncHeroRegimentOptions(
				(int)$unitId,
				$factionId,
				!empty($core['is_hero']),
				$data['hero_regiment_options'] ?? []
			);

			$this->db->commit();
			return [true, (int)$unitId];
		} catch (\Throwable $e) {
			if ($this->db->inTransaction()) {
				$this->db->rollBack();
			}
			error_log('saveUnit failed: ' . $e->getMessage());
			return [false, '保存に失敗しました。'];
		}
	}

	/**
	 * 当該ユニットの武器を全削除 → 再INSERT
	 */
	private function replaceUnitWeapons(int $unitId, array $weapons): void
	{
		$this->db->prepare('DELETE FROM m_unit_weapons WHERE unit_id = :unit_id;')
			->execute([':unit_id' => $unitId]);

		$insert = $this->db->prepare(
			'INSERT INTO m_unit_weapons (unit_id, name, type, rng, atk, hit, wnd, rnd, dmg, abilities, memo)
             VALUES (:unit_id, :name, :type, :rng, :atk, :hit, :wnd, :rnd, :dmg, :abilities, :memo);'
		);

		foreach ($weapons as $w) {
			$wname = trim($w['name'] ?? '');
			if ($wname === '') {
				continue;
			}
			$type = strtolower(trim($w['type'] ?? 'melee'));
			$type = ($type === 'ranged') ? 'ranged' : 'melee';

			$insert->execute([
				':unit_id'   => $unitId,
				':name'      => mb_substr($wname, 0, 255),
				':type'      => $type,
				':rng'       => $this->nullableInt($w['rng'] ?? null),
				':atk'       => $this->nullableStr($w['atk'] ?? null),
				':hit'       => $this->nullableStr($w['hit'] ?? null),
				':wnd'       => $this->nullableStr($w['wnd'] ?? null),
				':rnd'       => $this->nullableStr($w['rnd'] ?? null),
				':dmg'       => $this->nullableStr($w['dmg'] ?? null),
				':abilities' => $this->nullableStr($w['abilities'] ?? null),
				':memo'      => $this->nullableStr($w['memo'] ?? null),
			]);
		}
	}

	/**
	 * ユニットの能力を同期する。
	 * 各能力エントリ:
	 *   ability_id   既存マスタID（空なら新規作成）
	 *   _delete      "1" のときこのユニットから外す（マスタは削除しない）
	 *   name, trigger_phase, trigger_turn, ability_type, effect, flavor_text
	 *
	 * 注意: m_ability_master は複数ユニットで共有されるため、
	 *       既存マスタの編集は他ユニットにも波及する。
	 */
	private function syncUnitAbilities(int $unitId, array $abilities): void
	{
		$updateMaster = $this->db->prepare(
			'UPDATE m_ability_master
                SET name = :name, command_point = :command_point, casting_value = :casting_value, casting_type = :casting_type,
                    trigger_phase = :trigger_phase, trigger_turn = :trigger_turn,
                    activation = :activation, usage_scope = :usage_scope, usage_per = :usage_per,
                    icon_type = :icon_type, trigger_condition_ja = :trigger_condition_ja,
                    effect = :effect, flavor_text = :flavor_text, keywords = :keywords
                WHERE id = :id;'
		);
		$insertMaster = $this->db->prepare(
			'INSERT INTO m_ability_master (name, command_point, casting_value, casting_type, trigger_phase, trigger_turn, activation, usage_scope, usage_per, icon_type, trigger_condition_ja, effect, flavor_text, keywords)
             VALUES (:name, :command_point, :casting_value, :casting_type, :trigger_phase, :trigger_turn, :activation, :usage_scope, :usage_per, :icon_type, :trigger_condition_ja, :effect, :flavor_text, :keywords);'
		);
		$attach = $this->db->prepare(
			'INSERT INTO m_unit_abilities (unit_id, ability_id) VALUES (:unit_id, :ability_id);'
		);

		// 重複行が増えるのを防ぐため、当該ユニットの能力リンクを一旦全削除してから張り直す
		// （m_unit_abilities に一意制約が無くても重複しない）。マスタ本体は削除しない。
		$this->db->prepare('DELETE FROM m_unit_abilities WHERE unit_id = :unit_id;')
			->execute([':unit_id' => $unitId]);

		$attached = []; // ability_id の重複アタッチ防止

		foreach ($abilities as $ab) {
			$abilityId = isset($ab['ability_id']) && $ab['ability_id'] !== '' ? (int)$ab['ability_id'] : 0;
			$delete = !empty($ab['_delete']);
			$abName = trim($ab['name'] ?? '');

			// 「外す」指定、または名前が空のものはリンクしない
			if ($delete || $abName === '') {
				continue;
			}

			$fields = [
				':name'                 => mb_substr($abName, 0, 255),
				':command_point'        => $this->nullableInt($ab['command_point'] ?? null),
				':casting_value'        => $this->nullableStr($ab['casting_value'] ?? null),
				':casting_type'         => $this->sanitizeCastingType($ab['casting_type'] ?? null),
				':trigger_phase'        => $this->sanitizeTriggerPhaseSet($ab['trigger_phase'] ?? null),
				':trigger_turn'         => $this->sanitizeEnum($ab['trigger_turn'] ?? null, ['your', 'opponent', 'any', 'battle'], 'your'),
				':activation'           => $this->sanitizeEnum($ab['activation'] ?? null, ['active', 'passive', 'reaction'], 'active'),
				':usage_scope'          => $this->sanitizeEnum($ab['usage_scope'] ?? null, ['unlimited', 'once_per_turn', 'once_per_phase', 'once_per_battle'], 'unlimited'),
				':usage_per'            => $this->sanitizeEnum($ab['usage_per'] ?? null, ['unit', 'army'], 'unit'),
				':icon_type'            => $this->nullableStr($ab['icon_type'] ?? null),
				':trigger_condition_ja' => $this->nullableStr($ab['trigger_condition_ja'] ?? null),
				// effect は m_ability_master で NOT NULL のため、未入力時は NULL ではなく空文字にする
				':effect'               => (string)($ab['effect'] ?? ''),
				':flavor_text'          => $this->nullableStr($ab['flavor_text'] ?? null),
				':keywords'             => $this->nullableStr($ab['keywords'] ?? null),
			];

			if ($abilityId > 0) {
				$updateMaster->execute($fields + [':id' => $abilityId]);
			} else {
				$insertMaster->execute($fields);
				$abilityId = (int)$this->db->lastInsertId();
			}

			if ($abilityId > 0 && !isset($attached[$abilityId])) {
				$attach->execute([':unit_id' => $unitId, ':ability_id' => $abilityId]);
				$attached[$abilityId] = true;
			}
		}
	}

	/**
	 * ユニットが所属できる連隊オプション(t_unit_regiment_eligibility)を同期する。
	 * 当該ユニット分を全削除してから、ファクションに属する option_id のみ再登録する。
	 *
	 * @param int   $unitId
	 * @param int   $factionId  不正な option_id 混入を防ぐためのファクション
	 * @param array $optionIds  チェックされた option_id 配列
	 */
	private function syncUnitRegimentEligibility(int $unitId, int $factionId, array $optionIds): void
	{
		$this->db->prepare('DELETE FROM t_unit_regiment_eligibility WHERE unit_id = :unit_id;')
			->execute([':unit_id' => $unitId]);

		$valid = $this->filterFactionOptionIds($factionId, $optionIds);
		if (empty($valid)) {
			return;
		}

		$insert = $this->db->prepare(
			'INSERT IGNORE INTO t_unit_regiment_eligibility (unit_id, option_id) VALUES (:unit_id, :option_id);'
		);
		foreach ($valid as $optionId) {
			$insert->execute([':unit_id' => $unitId, ':option_id' => $optionId]);
		}
	}

	/**
	 * HERO が連隊に編成できるオプション枠(t_hero_regiment_options)を同期する。
	 * 当該ユニット分を全削除してから、HERO のときのみ再登録する。
	 *
	 * @param int   $unitId
	 * @param int   $factionId
	 * @param bool  $isHero      非HERO のときは枠を持たない(全削除のみ)
	 * @param array $optionMap   [option_id => max_limit] 形式
	 */
	private function syncHeroRegimentOptions(int $unitId, int $factionId, bool $isHero, array $optionMap): void
	{
		$this->db->prepare('DELETE FROM t_hero_regiment_options WHERE hero_unit_id = :unit_id;')
			->execute([':unit_id' => $unitId]);

		if (!$isHero || empty($optionMap)) {
			return;
		}

		$validIds = array_flip($this->filterFactionOptionIds($factionId, array_keys($optionMap)));

		$insert = $this->db->prepare(
			'INSERT IGNORE INTO t_hero_regiment_options (hero_unit_id, option_id, max_limit)
             VALUES (:unit_id, :option_id, :max_limit);'
		);
		foreach ($optionMap as $optionId => $maxLimit) {
			$optionId = (int)$optionId;
			if (!isset($validIds[$optionId])) {
				continue;
			}
			$insert->execute([
				':unit_id'   => $unitId,
				':option_id' => $optionId,
				':max_limit' => max(0, (int)$maxLimit),
			]);
		}
	}

	/**
	 * 渡された option_id のうち、指定ファクションに属するものだけを int 配列で返す。
	 */
	private function filterFactionOptionIds(int $factionId, array $optionIds): array
	{
		$ids = array_values(array_unique(array_filter(array_map('intval', $optionIds), static fn($v) => $v > 0)));
		if (empty($ids)) {
			return [];
		}

		$placeholders = implode(',', array_fill(0, count($ids), '?'));
		$sql = "SELECT id FROM m_regiment_options WHERE faction_id = ? AND id IN ({$placeholders});";
		$stmt = $this->db->prepare($sql);
		$stmt->execute(array_merge([$factionId], $ids));
		return array_map(static fn($r) => (int)$r['id'], $stmt->fetchAll(PDO::FETCH_ASSOC));
	}

	/**
	 * ユニットの画像パスのみを更新する
	 */
	public function updateImage(int $unitId, ?string $path)
	{
		$stmt = $this->db->prepare('UPDATE m_units SET image = :image WHERE id = :id;');
		try {
			$stmt->bindValue(':image', $path);
			$stmt->bindValue(':id', $unitId, PDO::PARAM_INT);
			$stmt->execute();
			return true;
		} catch (\Throwable $e) {
			error_log('updateImage failed: ' . $e->getMessage());
			return false;
		}
	}

	/**
	 * ロスター作成画面での表示/非表示を切り替える
	 */
	public function toggleVisibility(int $unitId, int $isHidden)
	{
		$stmt = $this->db->prepare('UPDATE m_units SET is_hidden = :is_hidden WHERE id = :id;');
		try {
			$stmt->bindValue(':is_hidden', $isHidden ? 1 : 0, PDO::PARAM_INT);
			$stmt->bindValue(':id', $unitId, PDO::PARAM_INT);
			$stmt->execute();
			return [true, $isHidden ? 1 : 0];
		} catch (\Throwable $e) {
			error_log('toggleVisibility failed: ' . $e->getMessage());
			return [false, '更新に失敗しました。'];
		}
	}

	// =========================================================================
	// ヘルパー
	// =========================================================================

	private function nullableInt($value): ?int
	{
		if ($value === null || $value === '') {
			return null;
		}
		return (int)$value;
	}

	private function nullableStr($value): ?string
	{
		if ($value === null) {
			return null;
		}
		$value = trim((string)$value);
		return $value === '' ? null : $value;
	}

	/**
	 * 詠唱/祈祷の種別を 'spell'/'prayer' のみ許容。範囲外・空は NULL。
	 */
	private function sanitizeCastingType($value): ?string
	{
		$v = strtolower(trim((string)($value ?? '')));
		return in_array($v, ['spell', 'prayer'], true) ? $v : null;
	}

	/**
	 * ENUM カラム向けに値を許容リストへ丸める。範囲外/空は $default を返す。
	 */
	private function sanitizeEnum($value, array $allowed, string $default): string
	{
		$v = strtolower(trim((string)($value ?? '')));
		return in_array($v, $allowed, true) ? $v : $default;
	}

	/**
	 * SET(trigger_phase) 向け。配列 or カンマ区切り文字列を許容トークンのみのカンマ区切りへ。
	 * 空なら NULL。
	 */
	private function sanitizeTriggerPhaseSet($value): ?string
	{
		$allowed = ['deployment', 'hero', 'movement', 'shooting', 'charge', 'combat', 'end', 'any'];
		$parts = is_array($value) ? $value : explode(',', (string)($value ?? ''));
		$clean = [];
		foreach ($parts as $p) {
			$p = strtolower(trim((string)$p));
			if ($p !== '' && in_array($p, $allowed, true) && !in_array($p, $clean, true)) {
				$clean[] = $p;
			}
		}
		return empty($clean) ? null : implode(',', $clean);
	}
}
