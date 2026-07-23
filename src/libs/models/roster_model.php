<?php

require_once __DIR__ . '/../types/battle_tactics.php';
require_once __DIR__ . '/../types/season_enhancements.php';

class Roster_Model extends Model
{
	public function __construct()
	{
		parent::__construct();
	}

	public function getFactions()
	{
		$sql = "SELECT * FROM m_factions ORDER BY grand_alliance ASC, id ASC;";
		return $this->db->select($sql);
	}

	public function getFactionName($id)
	{
		$sql = "SELECT name FROM m_factions WHERE id = :id LIMIT 1;";
		$result = $this->db->select($sql, ['id' => $id]);

		return !empty($result) ? $result[0]['name'] : '';
	}

	public function getBattleTraits()
	{
		$sql = "SELECT * FROM m_battle_traits ORDER BY id ASC;";
		return $this->db->select($sql);
	}

	public function getBattleTraitsByFaction(int $factionId)
	{
		$sql = "SELECT * FROM m_battle_traits WHERE faction_id = :faction_id ORDER BY id ASC;";
		return $this->db->select($sql, ['faction_id' => $factionId]);
	}

	public function getBattleFormations($id)
	{
		$sql = "SELECT * FROM m_battle_formations WHERE faction_id = :id AND is_hidden = 0;";
		return $this->db->select($sql, ['id' => $id]);
	}

	/**
	 * 呪文伝承 (Spell Lore)
	 */
	public function getSpellLores($id)
	{
		$sql = "SELECT * FROM m_spell_lores WHERE faction_id = :id ORDER BY lore_name ASC, id ASC;";
		return $this->db->select($sql, ['id' => $id]);
	}

	/**
	 * 奇蹟伝承 (Prayer Lore)
	 */
	public function getPrayerLores($id)
	{
		$sql = "SELECT * FROM m_prayer_lores WHERE faction_id = :id ORDER BY lore_name ASC, id ASC;";
		return $this->db->select($sql, ['id' => $id]);
	}

	/**
	 * 顕現魔術の伝承 (Manifestation Lore)
	 */
	public function getManifestationLores($id)
	{
		$sql = "SELECT * FROM m_manifestation_lores WHERE faction_id = :id ORDER BY lore_name ASC, id ASC;";
		return $this->db->select($sql, ['id' => $id]);
	}

	/**
	 * ファクションの全ユニット一覧（一覧用の軽量版）
	 */
	public function getUnits($faction_id)
	{
		$sql = "SELECT u.id, u.name, u.points,
                       " . KeywordSql::displayExpr('u', 'f') . " AS keywords,
                       u.unit_size, u.is_hero, u.can_reinforce FROM m_units u
                JOIN m_factions f ON f.id = u.faction_id
                WHERE u.faction_id = :id AND (u.is_hidden = 0 OR u.is_hidden IS NULL)
                  AND (u.is_terrain = 0 OR u.is_terrain IS NULL)
                  AND (u.is_manifestation = 0 OR u.is_manifestation IS NULL)
                ORDER BY u.name ASC, u.id ASC;";
		return $this->db->select($sql, ['id' => $faction_id]);
	}

	/**
	 * ファクションの陣営地形(ファクションテレイン)一覧。プロファイル＋アビリティを含む。
	 * ロスター画面の読み取り専用表示・対戦アビリティデッキの自動合流に使う。
	 */
	public function getFactionTerrain(int $factionId): array
	{
		return $this->getSpecialUnitsByFlag($factionId, 'is_terrain');
	}

	/**
	 * ファクションの顕現(マニフェステーション)一覧。プロファイル＋アビリティを含む。
	 * ロスター画面の読み取り専用表示・対戦アビリティデッキの自動合流に使う。
	 */
	public function getFactionManifestations(int $factionId): array
	{
		return $this->getSpecialUnitsByFlag($factionId, 'is_manifestation');
	}

	/**
	 * 指定フラグ(is_terrain / is_manifestation)が立ったユニットを
	 * プロファイル＋アビリティ付きで取得する共通処理。
	 */
	private function getSpecialUnitsByFlag(int $factionId, string $flagColumn): array
	{
		$allowed = ['is_terrain', 'is_manifestation'];
		if (!in_array($flagColumn, $allowed, true)) {
			return [];
		}

		$sql = "SELECT u.*,
                       " . KeywordSql::displayExpr('u', 'f') . " AS keywords
                FROM m_units u
                JOIN m_factions f ON f.id = u.faction_id
                WHERE u.faction_id = :id AND u.{$flagColumn} = 1
                ORDER BY u.name ASC, u.id ASC;";
		$rows = $this->db->select($sql, ['id' => $factionId]);

		foreach ($rows as &$row) {
			$row['abilities'] = $this->getUnitAbilities((int)$row['id']);
		}
		unset($row);

		return $rows;
	}

	/**
	 * 連隊長（HERO）候補 — is_hero = 1 のユニット
	 */
	public function getHeroUnits($faction_id)
	{
		$regimentText = $this->regimentOptionsTextSubquery('u');
		$sql = "SELECT u.id, u.name, u.points,
                       " . KeywordSql::displayExpr('u', 'f') . " AS keywords,
                       u.unit_size, u.is_hero, u.can_reinforce, u.is_general, u.is_unique,
                       {$regimentText} AS regiment_options
                FROM m_units u
                JOIN m_factions f ON f.id = u.faction_id
                WHERE u.faction_id = :id AND u.is_hero = 1
                  AND (u.is_hidden = 0 OR u.is_hidden IS NULL)
                  AND (u.is_terrain = 0 OR u.is_terrain IS NULL)
                  AND (u.is_manifestation = 0 OR u.is_manifestation IS NULL)
                ORDER BY u.name ASC, u.id ASC;";
		$heroes = $this->db->select($sql, ['id' => $faction_id]);

		foreach ($heroes as &$hero) {
			$hero['regiment_option_limits'] = $this->getHeroOptionLimits((int)$hero['id']);
		}
		unset($hero);

		return $heroes;
	}

	/**
	 * HERO が連隊長として編成できる枠を [{option_id, max_limit, option_name}] で返す。
	 * max_limit は 0 で無制限。フロントの枠割当UI／上限判定に使う。
	 */
	public function getHeroOptionLimits(int $heroId): array
	{
		$sql = "SELECT ho.option_id, ho.max_limit, ro.option_name
                FROM t_hero_regiment_options ho
                JOIN m_regiment_options ro ON ro.id = ho.option_id
                WHERE ho.hero_unit_id = :id
                ORDER BY ro.sort_order ASC, ro.option_name ASC, ro.id ASC;";
		$rows = $this->db->select($sql, ['id' => $heroId]);
		return array_map(static function ($r) {
			return [
				'option_id'   => (int)$r['option_id'],
				'max_limit'   => (int)$r['max_limit'],
				'option_name' => $r['option_name'],
			];
		}, $rows);
	}

	/**
	 * GROUP_CONCAT 由来の "1,2,3" を int 配列に変換する。
	 */
	public function parseOptionIds($raw): array
	{
		if ($raw === null || $raw === '') {
			return [];
		}
		$ids = array_map('intval', explode(',', (string)$raw));
		return array_values(array_filter($ids, static fn($v) => $v > 0));
	}

	/**
	 * 随伴部隊候補 — HERO 以外（HERO データが無い場合は全ユニット）
	 */
	public function getRegimentUnits($faction_id)
	{
		$sql = "SELECT u.id, u.name, u.points,
                       " . KeywordSql::displayExpr('u', 'f') . " AS keywords,
                       u.unit_size, u.is_hero, u.can_reinforce, u.is_general, u.is_unique                 FROM m_units u
                JOIN m_factions f ON f.id = u.faction_id
                WHERE u.faction_id = :id AND (u.is_hero = 0 OR u.is_hero IS NULL)
                  AND (u.is_hidden = 0 OR u.is_hidden IS NULL)
                  AND (u.is_terrain = 0 OR u.is_terrain IS NULL)
                  AND (u.is_manifestation = 0 OR u.is_manifestation IS NULL)
                ORDER BY u.name ASC, u.id ASC;";
		$units = $this->db->select($sql, ['id' => $faction_id]);

		return !empty($units) ? $units : $this->getUnits($faction_id);
	}

	/**
	 * 指定したヒーローが選択可能な連隊オプション（IDのリスト）を取得
	 */
	public function getHeroRegimentOptions($hero_unit_id)
	{
		$sql = "SELECT option_id FROM t_hero_regiment_options WHERE hero_unit_id = :h_id;";
		$result = $this->db->select($sql, ['h_id' => $hero_unit_id]);

		// 配列（[1, 3, 4]のような形式）にして返す
		return array_column($result, 'option_id');
	}

	/**
	 * HERO の連隊編成ルール表示テキストを構造化テーブルから生成する相関サブクエリ。
	 * 旧 m_units.regiment_options（自由文）の代替で、改行区切りの可読テキストを返す。
	 * max_limit > 0 は「（最大Nユニットまで）」、0 は無制限としてオプション名のみを表示する。
	 *
	 * @param string $unitAlias 外側クエリのユニットテーブル別名（u.id を参照する）
	 */
	private function regimentOptionsTextSubquery(string $unitAlias = 'u'): string
	{
		return "(SELECT GROUP_CONCAT(
                    CONCAT(ro.option_name, IF(ho.max_limit > 0, CONCAT('（最大', ho.max_limit, 'ユニットまで）'), ''))
                    ORDER BY ro.sort_order ASC, ro.option_name ASC, ro.id ASC
                    SEPARATOR '\n')
                 FROM t_hero_regiment_options ho
                 JOIN m_regiment_options ro ON ro.id = ho.option_id
                 WHERE ho.hero_unit_id = {$unitAlias}.id)";
	}

	/**
	 * 特定の連隊枠（option_id）に属するユニット一覧を取得
	 */
	public function getUnitsByRegimentOption($option_id)
	{
		$sql = "SELECT u.* FROM m_units u
                JOIN t_unit_regiment_eligibility ue ON u.id = ue.unit_id
                WHERE ue.option_id = :opt_id AND (u.is_hidden = 0 OR u.is_hidden IS NULL);";
		return $this->db->select($sql, ['opt_id' => $option_id]);
	}

	/**
	 * 指定 Hero に適格な随伴メンバーを取得する。
	 *
	 * 判定は構造化テーブルだけで完結する:
	 *   「リーダー(H)が編成枠に持つオプション O（t_hero_regiment_options）」かつ
	 *   「候補(X)が所属できる連隊オプション O（t_unit_regiment_eligibility）」が一致する X を返す。
	 *
	 * 非HERO 随伴ユニットも他HERO メンバーも同じ join で扱うため、
	 * option_name への文字列照合（キーワードLIKE）は使用しない。
	 * これにより option_name を表示専用（日本語可）にできる。
	 */
	public function getCompanionUnitsForHero(int $heroId, int $factionId): array
	{
		$sql = "SELECT u.id, u.name, u.points,
                       " . KeywordSql::displayExpr('u', 'f') . " AS keywords,
                       u.unit_size, u.is_hero, u.can_reinforce, u.is_general, u.is_unique,
                       GROUP_CONCAT(DISTINCT ho.option_id) AS option_ids
                FROM m_units u
                JOIN m_factions f ON f.id = u.faction_id
                JOIN t_unit_regiment_eligibility ue ON ue.unit_id = u.id
                JOIN t_hero_regiment_options ho ON ho.option_id = ue.option_id
                WHERE ho.hero_unit_id = :hero_id
                  AND u.faction_id = :faction_id
                  AND u.id <> :self_id
                  AND (u.is_hidden = 0 OR u.is_hidden IS NULL)
                GROUP BY u.id, u.name, u.points, keywords,
                         u.unit_size, u.is_hero, u.can_reinforce, u.is_general, u.is_unique
                ORDER BY u.name ASC, u.id ASC;";
		return $this->db->select($sql, [
			'hero_id'    => $heroId,
			'faction_id' => $factionId,
			'self_id'    => $heroId,
		]);
	}

	/**
	 * ファクション別 Enhancement カテゴリ（初版: Stormcast のみ）
	 */
	public function getHeroicTraitsForFaction(int $factionId): array
	{
		$sql = "SELECT id, category, source_reference, name, points, is_hero_only, trigger_phase, trigger_turn, activation, usage_scope, usage_per, trigger_condition_ja, effect, description
                FROM m_heroic_traits
                WHERE faction_id = :id AND is_hero_only = 1 AND is_hidden = 0
                ORDER BY source_reference ASC, category ASC, name ASC;";
		return $this->db->select($sql, ['id' => $factionId]);
	}

	public function getArtefactsForFaction(int $factionId): array
	{
		$sql = "SELECT id, category, source_reference, name, points, is_hero_only, trigger_phase, trigger_turn, activation, usage_scope, usage_per, trigger_condition_ja, effect, flavor_text
                FROM m_artefacts_of_power
                WHERE faction_id = :id AND is_hero_only = 1 AND is_hidden = 0
                ORDER BY source_reference ASC, category ASC, name ASC;";
		return $this->db->select($sql, ['id' => $factionId]);
	}

	public function getHeroicTraitById(int $id): ?array
	{
		$sql = "SELECT * FROM m_heroic_traits WHERE id = :id LIMIT 1;";
		$rows = $this->db->select($sql, ['id' => $id]);
		return !empty($rows) ? $rows[0] : null;
	}

	public function getArtefactById(int $id): ?array
	{
		$sql = "SELECT * FROM m_artefacts_of_power WHERE id = :id LIMIT 1;";
		$rows = $this->db->select($sql, ['id' => $id]);
		return !empty($rows) ? $rows[0] : null;
	}

	/**
	 * 陣営×シーズンの追加能力一覧（必須キーワード付き）。
	 *
	 * @return list<array<string,mixed>>
	 */
	public function getSeasonEnhancementsForFaction(
		int $factionId,
		string $season = SeasonEnhancements::SEASON_2026_27
	): array {
		$sql = "SELECT id, faction_id, season, name, effect, points, sort_order,
		               activation, usage_scope, usage_per,
		               trigger_phase, trigger_turn, trigger_condition_ja
                FROM m_season_enhancements
                WHERE faction_id = :faction_id
                  AND season = :season
                  AND is_hidden = 0
                ORDER BY sort_order ASC, name ASC;";
		$rows = $this->db->select($sql, [
			'faction_id' => $factionId,
			'season'     => $season,
		]);
		if (empty($rows)) {
			return [];
		}

		$ids = array_map(static fn($r) => (int)$r['id'], $rows);
		$bind = [];
		$placeholders = [];
		foreach ($ids as $i => $id) {
			$key = 'eid' . $i;
			$placeholders[] = ':' . $key;
			$bind[$key] = $id;
		}
		$kwSql = "SELECT sek.enhancement_id, sek.requirement, km.id, km.name, km.keyword_type
                  FROM m_season_enhancement_keywords sek
                  JOIN m_keywords_master km ON km.id = sek.keyword_id
                  WHERE sek.enhancement_id IN (" . implode(',', $placeholders) . ")
                  ORDER BY sek.enhancement_id ASC, km.sort_order ASC, km.name ASC;";
		$kwRows = $this->db->select($kwSql, $bind);

		$keywordsByEnh = [];
		foreach ($kwRows as $kw) {
			$eid = (int)$kw['enhancement_id'];
			$keywordsByEnh[$eid][] = [
				'id'           => (int)$kw['id'],
				'name'         => $kw['name'],
				'keyword_type' => $kw['keyword_type'],
				'requirement'  => ($kw['requirement'] ?? 'require') === 'exclude' ? 'exclude' : 'require',
			];
		}

		foreach ($rows as &$row) {
			$row['id'] = (int)$row['id'];
			$row['faction_id'] = (int)$row['faction_id'];
			$row['points'] = (int)$row['points'];
			$row['sort_order'] = (int)$row['sort_order'];
			$row['keywords'] = $keywordsByEnh[(int)$row['id']] ?? [];
		}
		unset($row);

		return $rows;
	}

	/**
	 * 陣営×シーズンの UI ラベル（未登録時はデフォルト日本語名）。
	 *
	 * @return array{label_ja: string, label_en: string|null}
	 */
	public function getSeasonEnhancementLabel(
		int $factionId,
		string $season = SeasonEnhancements::SEASON_2026_27
	): array {
		$sql = "SELECT label_ja, label_en
                FROM m_faction_season_enhancement_labels
                WHERE faction_id = :faction_id AND season = :season
                LIMIT 1;";
		$rows = $this->db->select($sql, [
			'faction_id' => $factionId,
			'season'     => $season,
		]);
		if (!empty($rows)) {
			return [
				'label_ja' => (string)$rows[0]['label_ja'],
				'label_en' => $rows[0]['label_en'] !== null && $rows[0]['label_en'] !== ''
					? (string)$rows[0]['label_en']
					: null,
			];
		}
		return [
			'label_ja' => SeasonEnhancements::DEFAULT_LABEL_JA,
			'label_en' => null,
		];
	}

	public function getSeasonEnhancementById(int $id): ?array
	{
		$sql = "SELECT * FROM m_season_enhancements WHERE id = :id LIMIT 1;";
		$rows = $this->db->select($sql, ['id' => $id]);
		if (empty($rows)) {
			return null;
		}
		$row = $rows[0];
		$row['keywords'] = $this->getSeasonEnhancementKeywordRules($id);
		return $row;
	}

	/**
	 * @return list<array{id:int,requirement:string}>
	 */
	public function getSeasonEnhancementKeywordRules(int $enhancementId): array
	{
		$sql = "SELECT keyword_id, requirement
                FROM m_season_enhancement_keywords
                WHERE enhancement_id = :id
                ORDER BY keyword_id ASC;";
		$rows = $this->db->select($sql, ['id' => $enhancementId]);
		$out = [];
		foreach ($rows as $r) {
			$out[] = [
				'id'          => (int)$r['keyword_id'],
				'requirement' => ($r['requirement'] ?? 'require') === 'exclude' ? 'exclude' : 'require',
			];
		}
		return $out;
	}

	/**
	 * @return list<int>
	 * @deprecated use getSeasonEnhancementKeywordRules
	 */
	public function getSeasonEnhancementKeywordIds(int $enhancementId): array
	{
		$rules = $this->getSeasonEnhancementKeywordRules($enhancementId);
		return array_map(static fn($r) => (int)$r['id'], $rules);
	}

	/**
	 * ユニットがシーズン追加能力のキーワード制約を満たすか。
	 * require はすべて所持、exclude は1つも所持しないこと。
	 *
	 * @param list<array{id?:int,requirement?:string}|int> $keywordRules
	 */
	public function unitMatchesSeasonEnhancementKeywords(int $unitId, array $keywordRules): bool
	{
		if ($unitId <= 0) {
			return false;
		}

		$requireIds = [];
		$excludeIds = [];
		foreach ($keywordRules as $rule) {
			if (is_int($rule)) {
				$requireIds[] = $rule;
				continue;
			}
			$id = (int)($rule['id'] ?? 0);
			if ($id <= 0) {
				continue;
			}
			if (($rule['requirement'] ?? 'require') === 'exclude') {
				$excludeIds[] = $id;
			} else {
				$requireIds[] = $id;
			}
		}

		if (empty($requireIds) && empty($excludeIds)) {
			return true;
		}

		$sql = "SELECT keyword_id FROM m_unit_keywords WHERE unit_id = :unit_id;";
		$rows = $this->db->select($sql, ['unit_id' => $unitId]);
		$owned = array_map(static fn($r) => (int)$r['keyword_id'], $rows);
		$ownedSet = array_fill_keys($owned, true);

		foreach ($requireIds as $kid) {
			if (!isset($ownedSet[$kid])) {
				return false;
			}
		}
		foreach ($excludeIds as $kid) {
			if (isset($ownedSet[$kid])) {
				return false;
			}
		}
		return true;
	}

	/**
	 * ユニットが必須キーワードをすべて持つか（空配列なら常に true）。
	 *
	 * @param list<int> $requiredKeywordIds
	 */
	public function unitHasRequiredKeywords(int $unitId, array $requiredKeywordIds): bool
	{
		return $this->unitMatchesSeasonEnhancementKeywords($unitId, $requiredKeywordIds);
	}


    // =========================================================================
    // 🌟 以下、詳細モーダル表示（オンデマンド取得）用の追加メソッド
    // =========================================================================

	/**
	 * 特定のユニット1件の詳細パラメータ（移動力、傷、セーヴ等）を丸ごと取得
	 */
	public function getUnitById($unit_id)
	{
		$regimentText = $this->regimentOptionsTextSubquery('u');
		$eligibilityNames = "(SELECT GROUP_CONCAT(DISTINCT ro.option_name
                        ORDER BY ro.sort_order ASC, ro.option_name ASC SEPARATOR ', ')
                    FROM t_unit_regiment_eligibility ue
                    JOIN m_regiment_options ro ON ro.id = ue.option_id
                    WHERE ue.unit_id = u.id)";
		$sql = "SELECT u.*,
                       " . KeywordSql::displayExpr('u', 'f') . " AS keywords,
                       " . KeywordSql::wardParamSubquery('u') . " AS ward,
                       {$regimentText} AS regiment_options,
                       {$eligibilityNames} AS regiment_eligibility_names
                FROM m_units u
                JOIN m_factions f ON f.id = u.faction_id
                WHERE u.id = :id LIMIT 1;";
		$result = $this->db->select($sql, ['id' => $unit_id]);

		return !empty($result) ? $result[0] : null;
	}

	/**
	 * ユニットに紐づく武器（射撃・近接）プロファイル一覧を取得
	 */
	public function getUnitWeapons($unit_id)
	{
		// データベースのテーブル名が m_unit_weapons であると仮定しています。
		// 近接（Melee）を下に、射撃（Ranged）を上にする、またはID順などでお好みでソートしてください。
		$sql = "SELECT * FROM m_unit_weapons WHERE unit_id = :unit_id ORDER BY id ASC;";
		return $this->db->select($sql, ['unit_id' => $unit_id]);
	}

	/**
	 * ユニットに紐づく固有アビリティ（能力）一覧を取得
	 */
	/**
	 * ユニットに紐づく固有アビリティ（能力）一覧を取得
	 * 新しいマスタテーブル構成に合わせてJOINするように変更
	 */
	public function getUnitAbilities($unit_id)
	{
		// unit_abilities(紐付け) と ability_master(本体) を JOIN して詳細を取得
		$sql = "SELECT 
                    m.id, 
                    m.name, 
                    m.command_point,
                    m.casting_value,
                    m.casting_type,
                    m.trigger_phase, 
                    m.trigger_turn, 
                    m.activation,
                    m.usage_scope,
                    m.usage_per,
                    m.trigger_condition_en,
                    m.trigger_condition_ja,
                    m.icon_type, 
                    m.effect, 
                    m.flavor_text
                FROM m_unit_abilities AS ua
                JOIN m_ability_master AS m ON ua.ability_id = m.id
                WHERE ua.unit_id = :unit_id
                ORDER BY m.id ASC;";

		return $this->db->select($sql, ['unit_id' => $unit_id]);
	}

	// =========================================================================
	// ロスター永続化
	// =========================================================================

	public function getRostersByUser(int $userId): array
	{
		$sql = "SELECT r.*, f.name AS faction_name
                FROM t_rosters r
                LEFT JOIN m_factions f ON f.id = r.faction_id
                WHERE r.user_id = :user_id
                ORDER BY r.updated_at DESC, r.created_at DESC, r.id DESC;";
		return $this->db->select($sql, ['user_id' => $userId]);
	}

	public function getRostersByUserAndFaction(int $userId, int $factionId): array
	{
		$sql = "SELECT r.id, r.name, r.total_points, r.faction_id, r.point_limit, f.name AS faction_name
                FROM t_rosters r
                LEFT JOIN m_factions f ON f.id = r.faction_id
                WHERE r.user_id = :user_id AND r.faction_id = :faction_id
                ORDER BY r.name ASC;";
		return $this->db->select($sql, ['user_id' => $userId, 'faction_id' => $factionId]);
	}

	public function getRosterById(int $rosterId, ?int $userId = null): ?array
	{
		$sql = "SELECT r.*, f.name AS faction_name, f.grand_alliance
                FROM t_rosters r
                LEFT JOIN m_factions f ON f.id = r.faction_id
                WHERE r.id = :id";
		$bind = ['id' => $rosterId];
		if ($userId !== null) {
			$sql .= " AND r.user_id = :user_id";
			$bind['user_id'] = $userId;
		}
		$sql .= " LIMIT 1;";
		$rows = $this->db->select($sql, $bind);
		return !empty($rows) ? $rows[0] : null;
	}

	public function getRosterRegiments(int $rosterId): array
	{
		$regimentText = $this->regimentOptionsTextSubquery('u');
		$sql = "SELECT rr.*, u.name AS hero_name, u.points AS hero_points,
                       " . KeywordSql::displayExpr('u', 'f') . " AS hero_keywords,
                       u.unit_size AS hero_unit_size, u.is_general AS hero_is_general, u.is_unique AS hero_is_unique,
                       {$regimentText} AS hero_regiment_options,
                       u.image AS hero_image, u.movement AS hero_movement
                FROM t_roster_regiments rr
                JOIN m_units u ON u.id = rr.hero_unit_id
                JOIN m_factions f ON f.id = u.faction_id
                WHERE rr.roster_id = :roster_id
                ORDER BY rr.sort_order ASC;";
		$rows = $this->db->select($sql, ['roster_id' => $rosterId]);
		foreach ($rows as &$row) {
			$row['regiment_option_limits'] = $this->getHeroOptionLimits((int)$row['hero_unit_id']);
		}
		unset($row);
		return $rows;
	}

	public function getRosterRegimentUnits(int $regimentId, int $heroId = 0): array
	{
		$sql = "SELECT ru.*, u.name, u.points,
                       " . KeywordSql::displayExpr('u', 'f') . " AS keywords,
                       u.unit_size, u.is_hero, u.can_reinforce, u.is_general, u.is_unique, u.image, u.movement,
                       (SELECT GROUP_CONCAT(DISTINCT ue.option_id)
                          FROM t_unit_regiment_eligibility ue
                          JOIN t_hero_regiment_options ho
                            ON ho.option_id = ue.option_id AND ho.hero_unit_id = :hero_id
                         WHERE ue.unit_id = u.id) AS option_ids
                FROM t_roster_regiment_units ru
                JOIN m_units u ON u.id = ru.unit_id
                JOIN m_factions f ON f.id = u.faction_id
                WHERE ru.regiment_id = :regiment_id
                ORDER BY ru.sort_order ASC;";
		return $this->db->select($sql, ['regiment_id' => $regimentId, 'hero_id' => $heroId]);
	}

	/**
	 * 射撃武器(type=ranged)を1つ以上持つユニット id の一覧を返す。
	 *
	 * @param int[] $unitIds
	 * @return int[]
	 */
	private function getUnitIdsWithRangedWeapons(array $unitIds): array
	{
		$unitIds = array_values(array_unique(array_filter(array_map('intval', $unitIds))));
		if (empty($unitIds)) {
			return [];
		}

		$bind = [];
		$placeholders = [];
		foreach ($unitIds as $i => $id) {
			$key = 'uid' . $i;
			$placeholders[] = ':' . $key;
			$bind[$key] = $id;
		}

		$sql = 'SELECT DISTINCT unit_id FROM m_unit_weapons
                WHERE type = \'ranged\' AND unit_id IN (' . implode(',', $placeholders) . ')';
		$rows = $this->db->select($sql, $bind);

		return array_map('intval', array_column($rows, 'unit_id'));
	}

	/**
	 * 武器プロファイル（近接・射撃いずれか）を1つ以上持つユニット id の一覧を返す。
	 *
	 * @param int[] $unitIds
	 * @return int[]
	 */
	private function getUnitIdsWithWeapons(array $unitIds): array
	{
		$unitIds = array_values(array_unique(array_filter(array_map('intval', $unitIds))));
		if (empty($unitIds)) {
			return [];
		}

		$bind = [];
		$placeholders = [];
		foreach ($unitIds as $i => $id) {
			$key = 'uid' . $i;
			$placeholders[] = ':' . $key;
			$bind[$key] = $id;
		}

		$sql = 'SELECT DISTINCT unit_id FROM m_unit_weapons
                WHERE unit_id IN (' . implode(',', $placeholders) . ')';
		$rows = $this->db->select($sql, $bind);

		return array_map('intval', array_column($rows, 'unit_id'));
	}

	public function getRosterWithDetails(int $rosterId, ?int $userId = null): ?array
	{
		$roster = $this->getRosterById($rosterId, $userId);
		if (!$roster) {
			return null;
		}

		$regimentRows = $this->getRosterRegiments($rosterId);
		$regimentUnitsByRow = [];
		$allUnitIds = [];

		foreach ($regimentRows as $row) {
			$heroId = (int)$row['hero_unit_id'];
			if ($heroId > 0) {
				$allUnitIds[] = $heroId;
			}
			$units = $this->getRosterRegimentUnits((int)$row['id'], $heroId);
			$regimentUnitsByRow[(int)$row['id']] = $units;
			foreach ($units as $u) {
				$unitId = (int)($u['unit_id'] ?? 0);
				if ($unitId > 0) {
					$allUnitIds[] = $unitId;
				}
			}
		}

		$rangedSet = array_flip(
			$this->getUnitIdsWithRangedWeapons($allUnitIds)
		);
		$weaponSet = array_flip(
			$this->getUnitIdsWithWeapons($allUnitIds)
		);

		$regiments = [];

		foreach ($regimentRows as $row) {
			$units = $regimentUnitsByRow[(int)$row['id']];
			$heroId = (int)$row['hero_unit_id'];
			$regiments[] = [
				'id'                  => (int)$row['id'],
				'sort_order'          => (int)$row['sort_order'],
				'is_general'          => (int)$row['is_general'],
				'enhancement_trait'   => $row['enhancement_trait'],
				'enhancement_artefact' => $row['enhancement_artefact'],
				'hero' => [
					'id'       => $heroId,
					'instanceKey' => 'hero:' . (int)$row['id'],
					'name'     => $row['hero_name'],
					'points'   => (int)$row['hero_points'],
					'keywords' => $row['hero_keywords'],
					'unit_size' => (int)$row['hero_unit_size'],
					'is_general' => (int)($row['hero_is_general'] ?? 0),
					'is_unique'  => (int)($row['hero_is_unique'] ?? 0),
					'regiment_options' => $row['hero_regiment_options'],
					'regiment_option_limits' => $row['regiment_option_limits'] ?? [],
					'image'    => $row['hero_image'] ?? null,
					'movement' => $row['hero_movement'] ?? null,
					'hasRangedWeapon' => isset($rangedSet[$heroId]),
					'hasWeapon' => isset($weaponSet[$heroId]),
				],
				'units' => array_map(function ($u) use ($rangedSet, $weaponSet) {
					$basePts = (int)$u['points'];
					$unitId = (int)$u['unit_id'];
					$rosterUnitId = (int)($u['id'] ?? 0);
					return [
						'id'            => $unitId,
						'instanceKey'   => 'unit:' . $rosterUnitId,
						'name'          => $u['name'],
						'points'        => !empty($u['is_reinforced']) ? $basePts * 2 : $basePts,
						'basePoints'    => $basePts,
						'keywords'      => $u['keywords'],
						'unit_size'     => (int)$u['unit_size'],
						'is_hero'       => (int)($u['is_hero'] ?? 0),
						'can_reinforce' => (int)($u['can_reinforce'] ?? 0),
						'is_general'    => (int)($u['is_general'] ?? 0),
						'is_unique'     => (int)($u['is_unique'] ?? 0),
						'is_reinforced' => (int)$u['is_reinforced'],
						'sort_order'    => (int)$u['sort_order'],
						'image'         => $u['image'] ?? null,
						'movement'      => $u['movement'] ?? null,
						'hasRangedWeapon' => isset($rangedSet[$unitId]),
						'hasWeapon'     => isset($weaponSet[$unitId]),
						'option_ids'    => $this->parseOptionIds($u['option_ids'] ?? null),
						'assigned_option_id' => isset($u['assigned_option_id']) && $u['assigned_option_id'] !== null
							? (int)$u['assigned_option_id'] : null,
					];
				}, $units),
			];
		}

		return [
			'roster'                 => $roster,
			'regiments'              => $regiments,
			'selected_tactics_cards' => $this->getSelectedBattleTacticIds($rosterId),
		];
	}

	/**
	 * ロスターに紐づくバトルタクティクスカード ID（sort_order 昇順）。
	 *
	 * @return list<int>
	 */
	public function getSelectedBattleTacticIds(int $rosterId): array
	{
		$sql = 'SELECT battle_tactic_id
                FROM t_roster_battle_tactics
                WHERE roster_id = :roster_id
                ORDER BY sort_order ASC;';
		$rows = $this->db->select($sql, ['roster_id' => $rosterId]);
		return array_map('intval', array_column($rows, 'battle_tactic_id'));
	}

	/**
	 * GHB 2026-27 カードを段階付きで取得する。
	 *
	 * @return list<BattleTacticCard>
	 */
	public function getBattleTacticCardsForSeason(string $season = BattleTactics::SEASON_2026_27): array
	{
		$sql = 'SELECT id, name, season, grand_alliance, sort_order
                FROM m_battle_tactics
                WHERE season = :season
                ORDER BY sort_order ASC, id ASC;';
		$cards = $this->db->select($sql, ['season' => $season]);
		if (empty($cards)) {
			return [];
		}

		$cardIds = array_map('intval', array_column($cards, 'id'));
		$bind = [];
		$placeholders = [];
		foreach ($cardIds as $i => $id) {
			$key = 'cid' . $i;
			$placeholders[] = ':' . $key;
			$bind[$key] = $id;
		}

		$stageSql = 'SELECT id, battle_tactic_id, stage, stage_order, name, effect, victory_points
                     FROM m_battle_tactic_stages
                     WHERE battle_tactic_id IN (' . implode(',', $placeholders) . ')
                     ORDER BY battle_tactic_id ASC, stage_order ASC;';
		$stageRows = $this->db->select($stageSql, $bind);

		$stagesByCard = [];
		foreach ($stageRows as $row) {
			$cid = (int)$row['battle_tactic_id'];
			$stagesByCard[$cid][] = [
				'id'               => (int)$row['id'],
				'battle_tactic_id' => $cid,
				'stage'            => $row['stage'],
				'stage_order'      => (int)$row['stage_order'],
				'name'             => $row['name'],
				'effect'           => $row['effect'],
				'victory_points'   => (int)($row['victory_points'] ?? 2),
			];
		}

		$result = [];
		foreach ($cards as $card) {
			$cid = (int)$card['id'];
			$result[] = [
				'id'              => $cid,
				'name'            => $card['name'],
				'season'          => $card['season'],
				'grand_alliance'  => $card['grand_alliance'],
				'sort_order'      => (int)$card['sort_order'],
				'stages'          => $stagesByCard[$cid] ?? [],
			];
		}
		return $result;
	}

	/**
	 * 選択カード ID を正規化（int 化・空除去）。枚数上限は検証側で見る。
	 *
	 * @param mixed $raw
	 * @return list<int>
	 */
	private function normalizeSelectedTacticsCards($raw): array
	{
		if ($raw === null || $raw === '') {
			return [];
		}
		if (!is_array($raw)) {
			$raw = [$raw];
		}
		$ids = [];
		foreach ($raw as $value) {
			$id = (int)$value;
			if ($id > 0) {
				$ids[] = $id;
			}
		}
		return $ids;
	}

	/**
	 * ロスター保存時のバトルタクティクス選択検証（0〜2枚）。
	 *
	 * @param list<int> $selectedCardIds
	 * @return string|null エラーメッセージ。問題なければ null。
	 */
	public function validateBattleTacticsSelection(array $selectedCardIds): ?string
	{
		$selectedCardIds = $this->normalizeSelectedTacticsCards($selectedCardIds);

		if (count($selectedCardIds) > BattleTactics::MAX_CARDS_PER_ROSTER) {
			return 'バトルタクティクスカードは最大'
				. BattleTactics::MAX_CARDS_PER_ROSTER
				. '枚まで選択できます。';
		}

		if (count($selectedCardIds) !== count(array_unique($selectedCardIds))) {
			return '同じバトルタクティクスカードを重複して選択できません。';
		}

		if (empty($selectedCardIds)) {
			return null;
		}

		$bind = [];
		$placeholders = [];
		foreach ($selectedCardIds as $i => $id) {
			$key = 'tid' . $i;
			$placeholders[] = ':' . $key;
			$bind[$key] = $id;
		}

		$sql = 'SELECT id, season
                FROM m_battle_tactics
                WHERE id IN (' . implode(',', $placeholders) . ');';
		$rows = $this->db->select($sql, $bind);
		$found = [];
		foreach ($rows as $row) {
			$found[(int)$row['id']] = $row['season'];
		}

		foreach ($selectedCardIds as $id) {
			if (!isset($found[$id])) {
				return '選択されたバトルタクティクスカードが不正です。';
			}
			if ($found[$id] !== BattleTactics::SEASON_2026_27) {
				return 'GHB 2026-27 のバトルタクティクスカードのみ選択できます。';
			}
		}

		$stageSql = 'SELECT battle_tactic_id, stage, stage_order
                     FROM m_battle_tactic_stages
                     WHERE battle_tactic_id IN (' . implode(',', $placeholders) . ')
                     ORDER BY battle_tactic_id ASC, stage_order ASC;';
		$stageRows = $this->db->select($stageSql, $bind);
		$stagesByCard = [];
		foreach ($stageRows as $row) {
			$cid = (int)$row['battle_tactic_id'];
			$stagesByCard[$cid][] = [
				'stage'       => $row['stage'],
				'stage_order' => (int)$row['stage_order'],
			];
		}

		foreach ($selectedCardIds as $id) {
			if (!BattleTactics::hasValidStageSequence($stagesByCard[$id] ?? [])) {
				return 'バトルタクティクスカードの段階データが不正です（Affray → Strike → Domination が必要です）。';
			}
		}

		return null;
	}

	/**
	 * ロスターのバトルタクティクス選択を置き換える。
	 *
	 * @param list<int> $selectedCardIds
	 * @return array{0: bool, 1?: string}
	 */
	private function replaceRosterBattleTactics(int $rosterId, array $selectedCardIds): array
	{
		$selectedCardIds = $this->normalizeSelectedTacticsCards($selectedCardIds);

		$sqls = ['DELETE FROM t_roster_battle_tactics WHERE roster_id = :roster_id;'];
		$binds = [['roster_id' => $rosterId]];

		foreach ($selectedCardIds as $sortOrder => $tacticId) {
			$sqls[] = 'INSERT INTO t_roster_battle_tactics
				(roster_id, battle_tactic_id, sort_order)
				VALUES (:roster_id, :battle_tactic_id, :sort_order);';
			$binds[] = [
				'roster_id'        => $rosterId,
				'battle_tactic_id' => $tacticId,
				'sort_order'       => (int)$sortOrder,
			];
		}

		return $this->db->transact($sqls, $binds);
	}

	public function getRosterSummaryForMatch(int $rosterId): ?array
	{
		$data = $this->getRosterWithDetails($rosterId);
		if (!$data) {
			return null;
		}

		$rosterRow = $data['roster'];

		return [
			'id'           => (int)$rosterRow['id'],
			'name'         => $rosterRow['name'],
			'factionId'    => (int)$rosterRow['faction_id'],
			'factionName'  => $rosterRow['faction_name'] ?? '',
			'totalPoints'  => (int)$rosterRow['total_points'],
			'heroicTrait'  => $this->resolveEnhancementName($rosterRow['heroic_trait_id'] ?? null, 'trait'),
			'artefact'     => $this->resolveEnhancementName($rosterRow['artefact_id'] ?? null, 'artefact'),
			'seasonEnhancement' => $this->resolveEnhancementName(
				$rosterRow['season_enhancement_id'] ?? null,
				'season'
			),
			// ユニット詳細モーダル用: 割当先 unitId 付きの神器・英雄特性・追加能力
			'enhancements' => [
				'trait'    => $this->resolveEnhancementDetailForMatch($rosterRow, 'trait'),
				'artefact' => $this->resolveEnhancementDetailForMatch($rosterRow, 'artefact'),
				'season'   => $this->resolveEnhancementDetailForMatch($rosterRow, 'season'),
			],
			'manifestations' => $this->getManifestationUnitsForLore((int)($rosterRow['manifestation_lore_id'] ?? 0)),
			'terrain'      => $this->getTerrainUnitForMatch((int)($rosterRow['terrain_id'] ?? 0)),
			'battleTactics' => $this->getSelectedBattleTacticCardsForMatch($rosterId),
			'regiments'    => array_map(function ($reg) {
				return [
					'sortOrder'  => $reg['sort_order'],
					'isGeneral'  => (bool)$reg['is_general'],
					'hero'       => $reg['hero'],
					'units'      => $reg['units'],
				];
			}, $data['regiments']),
		];
	}

	/**
	 * マッチ用: ロスター選択済み BT カードを段階付きで返す。
	 *
	 * @return list<array{id:int,name:string,sortOrder:int,stages:list<array{id:int,stage:string,stageOrder:int,name:string,effect:string,victoryPoints:int}>}>
	 */
	public function getSelectedBattleTacticCardsForMatch(int $rosterId): array
	{
		$selectedIds = $this->getSelectedBattleTacticIds($rosterId);
		if (empty($selectedIds)) {
			return [];
		}

		$allCards = $this->getBattleTacticCardsForSeason(BattleTactics::SEASON_2026_27);
		$byId = [];
		foreach ($allCards as $card) {
			$byId[(int)$card['id']] = $card;
		}

		$result = [];
		foreach ($selectedIds as $sortOrder => $cardId) {
			$card = $byId[$cardId] ?? null;
			if (!$card) {
				continue;
			}
			$stages = [];
			foreach ($card['stages'] as $stage) {
				$stages[] = [
					'id'             => (int)$stage['id'],
					'stage'          => $stage['stage'],
					'stageOrder'     => (int)$stage['stage_order'],
					'name'           => $stage['name'],
					'effect'         => $stage['effect'],
					'victoryPoints'  => (int)($stage['victory_points'] ?? 2),
				];
			}
			$result[] = [
				'id'        => (int)$card['id'],
				'name'      => $card['name'],
				'sortOrder' => (int)$sortOrder,
				'stages'    => $stages,
			];
		}
		return $result;
	}

	/**
	 * ロスターで選択した顕現の伝承(召喚呪文)が召喚する顕現ユニットの一覧を返す。
	 * 各召喚呪文行の m_manifestation_lores.unit_id を辿り、対応するウォースクロール
	 * (m_units) の基本情報を返す。unit_id 未設定の呪文はスキップ。マッチプレイ中の
	 * ロスターパネルでウォースクロール詳細を開くために使う。
	 */
	private function getManifestationUnitsForLore(int $loreRowId): array
	{
		if ($loreRowId <= 0) {
			return [];
		}

		$anchor = $this->db->select(
			'SELECT * FROM m_manifestation_lores WHERE id = :id LIMIT 1;',
			['id' => $loreRowId]
		);
		if (empty($anchor)) {
			return [];
		}
		$anchor = $anchor[0];

		$rows = $this->db->select(
			'SELECT * FROM m_manifestation_lores
			 WHERE faction_id = :faction_id AND lore_name = :lore_name
			 ORDER BY id ASC;',
			[
				'faction_id' => (int)$anchor['faction_id'],
				'lore_name'  => $anchor['lore_name'],
			]
		);

		$manifestations = [];
		$seen = [];
		foreach ($rows as $row) {
			$unitId = (int)($row['unit_id'] ?? 0);
			if ($unitId <= 0 || isset($seen[$unitId])) {
				continue;
			}

			$unit = $this->db->select(
				'SELECT u.id, u.name, u.points, u.image,
				        ' . KeywordSql::displayExprBasic('u') . ' AS keywords
				 FROM m_units u
				 WHERE u.id = :id LIMIT 1;',
				['id' => $unitId]
			);
			if (empty($unit)) {
				continue;
			}
			$unit = $unit[0];
			$seen[$unitId] = true;

			$manifestations[] = [
				'id'          => (int)$unit['id'],
				'instanceKey' => 'manifest:' . (int)$unit['id'],
				'name'        => $unit['name'],
				'points'      => (int)($unit['points'] ?? 0),
				'image'       => $unit['image'] ?? null,
				'keywords'    => $unit['keywords'] ?? '',
				'spellName'   => $row['manifestation_name'] ?? null,
			];
		}

		return $manifestations;
	}

	/**
	 * ロスターで選択した陣営地形(ファクションテレイン)のウォースクロール基本情報を返す。
	 * マッチプレイ中のロスターパネルでウォースクロール詳細を開くために使う。
	 * 未選択(0)の場合は null。
	 */
	private function getTerrainUnitForMatch(int $terrainId): ?array
	{
		if ($terrainId <= 0) {
			return null;
		}

		$unit = $this->db->select(
			'SELECT u.id, u.name, u.points, u.image,
			        ' . KeywordSql::displayExprBasic('u') . ' AS keywords
			 FROM m_units u
			 WHERE u.id = :id LIMIT 1;',
			['id' => $terrainId]
		);
		if (empty($unit)) {
			return null;
		}
		$unit = $unit[0];

		return [
			'id'          => (int)$unit['id'],
			'instanceKey' => 'terrain:' . (int)$unit['id'],
			'name'        => $unit['name'],
			'points'      => (int)($unit['points'] ?? 0),
			'image'       => $unit['image'] ?? null,
			'keywords'    => $unit['keywords'] ?? '',
		];
	}

	public function getRosterAbilityDeckForMatch(int $rosterId): array
	{
		$data = $this->getRosterWithDetails($rosterId);
		if (!$data) {
			return [];
		}

		$roster = $data['roster'];
		$regiments = $data['regiments'];
		$deck = [];

		$formationId = (int)($roster['battle_formation_id'] ?? 0);
		if ($formationId > 0) {
			$formation = $this->getBattleFormationById($formationId);
			if ($formation) {
				$deck[] = $this->buildDeckEntry(
					'army:formation:' . $formationId,
					$formation['formation_name'] ?? $formation['ability_name'] ?? 'Formation',
					$formation['effect'] ?? '',
					$formation['trigger_phase'] ?? '',
					$formation['trigger_turn'] ?? '',
					'バトルフォーメーション',
					null,
					'formation',
					null,
					null,
					$formation['activation'] ?? 'active',
					$formation['usage_scope'] ?? 'unlimited',
					$formation['usage_per'] ?? 'unit',
					trim((string)($formation['trigger_condition_ja'] ?? ''))
				);
			}
		}

		$traitId = (int)($roster['heroic_trait_id'] ?? 0);
		if ($traitId > 0) {
			$trait = $this->getHeroicTraitById($traitId);
			if ($trait) {
				$heroName = $this->resolveEnhancementHeroLabel(
					$regiments,
					$roster['trait_regiment_index'] ?? null,
					$roster['trait_unit_slot'] ?? 'leader',
					(int)($roster['trait_target_unit_id'] ?? 0)
				);
				$deck[] = $this->buildDeckEntry(
					'army:trait:' . $traitId,
					$trait['name'],
					$trait['effect'] ?? $trait['description'] ?? '',
					$trait['trigger_phase'] ?? '',
					$trait['trigger_turn'] ?? '',
					'英雄特性',
					$heroName,
					'trait',
					null,
					null,
					$trait['activation'] ?? 'active',
					$trait['usage_scope'] ?? 'unlimited',
					$trait['usage_per'] ?? 'unit',
					trim((string)($trait['trigger_condition_ja'] ?? ''))
				);
			}
		}

		$artefactId = (int)($roster['artefact_id'] ?? 0);
		if ($artefactId > 0) {
			$artefact = $this->getArtefactById($artefactId);
			if ($artefact) {
				$heroName = $this->resolveEnhancementHeroLabel(
					$regiments,
					$roster['artefact_regiment_index'] ?? null,
					$roster['artefact_unit_slot'] ?? 'leader',
					(int)($roster['artefact_target_unit_id'] ?? 0)
				);
				$deck[] = $this->buildDeckEntry(
					'army:artefact:' . $artefactId,
					$artefact['name'],
					$artefact['effect'] ?? $artefact['flavor_text'] ?? '',
					$artefact['trigger_phase'] ?? '',
					$artefact['trigger_turn'] ?? '',
					'神器',
					$heroName,
					'artefact',
					null,
					null,
					$artefact['activation'] ?? 'active',
					$artefact['usage_scope'] ?? 'unlimited',
					$artefact['usage_per'] ?? 'unit',
					trim((string)($artefact['trigger_condition_ja'] ?? ''))
				);
			}
		}

		$seasonEnhId = (int)($roster['season_enhancement_id'] ?? 0);
		if ($seasonEnhId > 0) {
			$seasonEnh = $this->getSeasonEnhancementById($seasonEnhId);
			if ($seasonEnh) {
				$factionIdForLabel = (int)($roster['faction_id'] ?? 0);
				$season = (string)($seasonEnh['season'] ?? SeasonEnhancements::SEASON_2026_27);
				$label = $factionIdForLabel > 0
					? $this->getSeasonEnhancementLabel($factionIdForLabel, $season)
					: ['label_ja' => SeasonEnhancements::DEFAULT_LABEL_JA];
				$unitName = $this->resolveEnhancementHeroLabel(
					$regiments,
					$roster['season_enhancement_regiment_index'] ?? null,
					$roster['season_enhancement_unit_slot'] ?? 'leader',
					(int)($roster['season_enhancement_target_unit_id'] ?? 0)
				);
				$deck[] = $this->buildDeckEntry(
					'army:season_enhancement:' . $seasonEnhId,
					$seasonEnh['name'],
					$seasonEnh['effect'] ?? '',
					$seasonEnh['trigger_phase'] ?? '',
					$seasonEnh['trigger_turn'] ?? '',
					$label['label_ja'],
					$unitName,
					'season_enhancement',
					null,
					null,
					$seasonEnh['activation'] ?? 'active',
					$seasonEnh['usage_scope'] ?? 'unlimited',
					$seasonEnh['usage_per'] ?? 'unit',
					trim((string)($seasonEnh['trigger_condition_ja'] ?? ''))
				);
			}
		}

		$factionId = (int)($roster['faction_id'] ?? 0);
		if ($factionId > 0) {
			foreach ($this->getBattleTraitsByFaction($factionId) as $bt) {
				$btId = (int)($bt['id'] ?? 0);
				if ($btId <= 0) {
					continue;
				}
				$key = 'army:battletrait:' . $btId;
				$deck[] = $this->buildDeckEntry(
					$key,
					$bt['name'] ?? '',
					$bt['effect'] ?? '',
					$bt['trigger_phase'] ?? '',
					$bt['trigger_turn'] ?? '',
					'バトルトレイト',
					null,
					'battletrait',
					$key,
					null,
					$bt['activation'] ?? 'active',
					$bt['usage_scope'] ?? 'unlimited',
					$bt['usage_per'] ?? 'unit',
					trim((string)($bt['trigger_condition_ja'] ?? ''))
				);
			}

			// 陣営地形(ファクションテレイン)のアビリティを軍勢レベルで合流させる。
			// ロスターで選択された地形のみを対象とする(未選択なら追加しない)。
			$selectedTerrainId = (int)($roster['terrain_id'] ?? 0);
			if ($selectedTerrainId > 0) {
				$terrainUnit = $this->getUnitById($selectedTerrainId);
				$terrainName = $terrainUnit['name'] ?? '';
				foreach ($this->getUnitAbilities($selectedTerrainId) as $ability) {
					$abilityId = (int)($ability['id'] ?? 0);
					if ($abilityId <= 0) {
						continue;
					}
					$triggerCondition = trim((string)($ability['trigger_condition_ja'] ?? ''));
					if ($triggerCondition === '') {
						$triggerCondition = trim((string)($ability['trigger_condition_en'] ?? ''));
					}
					$terrainCp = ($ability['command_point'] === null || $ability['command_point'] === '')
						? null
						: (int)$ability['command_point'];
					$key = 'army:terrain:' . $abilityId;
					$deck[] = $this->buildDeckEntry(
						$key,
						$ability['name'] ?? '',
						$ability['effect'] ?? '',
						$ability['trigger_phase'] ?? '',
						$ability['trigger_turn'] ?? '',
						'陣営地形',
						$terrainName,
						'terrain',
						$key,
						$terrainCp,
						$ability['activation'] ?? 'active',
						$ability['usage_scope'] ?? 'unlimited',
						$ability['usage_per'] ?? 'unit',
						$triggerCondition,
						$ability['casting_value'] ?? null,
						$ability['casting_type'] ?? null
					);
				}
			}

			// 顕現(マニフェステーション)ユニット自体のアビリティはフェーズ画面のデッキに載せない。
			// 召喚呪文(顕現の伝承)のみを下の buildLoreDeckEntries で追加し、顕現本体の能力は
			// マッチプレイ中のロスターパネル(ウォースクロール詳細)で確認する。
		}

		$deck = array_merge(
			$deck,
			$this->buildLoreDeckEntries(
				(int)($roster['spell_lore_id'] ?? 0),
				'm_spell_lores',
				'spell_name',
				'army:spell',
				'呪文伝承',
				'spell'
			),
			$this->buildLoreDeckEntries(
				(int)($roster['prayer_lore_id'] ?? 0),
				'm_prayer_lores',
				'prayer_name',
				'army:prayer',
				'奇蹟伝承',
				'prayer'
			),
			$this->buildLoreDeckEntries(
				(int)($roster['manifestation_lore_id'] ?? 0),
				'm_manifestation_lores',
				'manifestation_name',
				'army:manifest',
				'顕現の伝承',
				'manifestation'
			)
		);

		$seenUnitAbilities = [];
		foreach ($regiments as $regiment) {
			$units = array_merge([$regiment['hero']], $regiment['units'] ?? []);
			foreach ($units as $unit) {
				$unitId = (int)($unit['id'] ?? 0);
				if ($unitId <= 0) {
					continue;
				}
				foreach ($this->getUnitAbilities($unitId) as $ability) {
					$abilityId = (int)($ability['id'] ?? 0);
					if ($abilityId <= 0) {
						continue;
					}
					$dedupeKey = 'ability:' . $abilityId;
					$triggerCondition = trim((string)($ability['trigger_condition_ja'] ?? ''));
					if ($triggerCondition === '') {
						$triggerCondition = trim((string)($ability['trigger_condition_en'] ?? ''));
					}
					$unitAbilityCp = ($ability['command_point'] === null || $ability['command_point'] === '')
						? null
						: (int)$ability['command_point'];
					$deck[] = $this->buildDeckEntry(
						$dedupeKey,
						$ability['name'] ?? '',
						$ability['effect'] ?? '',
						$ability['trigger_phase'] ?? '',
						$ability['trigger_turn'] ?? '',
						'ユニット能力',
						$unit['name'] ?? '',
						'unit',
						$dedupeKey,
						$unitAbilityCp,
						$ability['activation'] ?? 'active',
						$ability['usage_scope'] ?? 'unlimited',
						$ability['usage_per'] ?? 'unit',
						$triggerCondition,
						$ability['casting_value'] ?? null,
						$ability['casting_type'] ?? null
					);
				}
			}
		}

		foreach ($this->getCommonAbilities() as $common) {
			$commonId = (int)($common['id'] ?? 0);
			if ($commonId <= 0) {
				continue;
			}
			$commandCost = ($common['command_cost'] === null || $common['command_cost'] === '')
				? null
				: (int)$common['command_cost'];
			$key = 'common:' . $commonId;
			$deck[] = $this->buildDeckEntry(
				$key,
				$common['name'] ?? '',
				$common['effect'] ?? '',
				$common['trigger_phase'] ?? '',
				$common['trigger_turn'] ?? '',
				'共通アビリティ',
				null,
				'common',
				$key,
				$commandCost,
				$common['activation'] ?? 'active',
				$common['usage_scope'] ?? 'unlimited',
				$common['usage_per'] ?? 'unit',
				trim((string)($common['trigger_condition_ja'] ?? ''))
			);
		}

		return $this->deduplicateAbilityDeck($deck);
	}

	/**
	 * 試合で選んだバトルプランに紐づくアビリティをデッキ形式で返す。
	 * 両プレイヤーに同じ行を載せ、フェイズ／ターンはクライアント側でフィルタする。
	 */
	public function getBattleplanAbilityDeckForMatch(int $battleplanId): array
	{
		if ($battleplanId <= 0) {
			return [];
		}

		$rows = $this->db->select(
			'SELECT a.id, a.name, a.effect, a.command_cost, a.activation, a.usage_scope, a.usage_per,
			        a.trigger_phase, a.trigger_turn, a.icon_type, a.trigger_condition_ja,
			        bp.name AS battleplan_name
			 FROM m_battleplan_abilities a
			 INNER JOIN m_battleplans bp ON bp.id = a.battleplan_id
			 WHERE a.battleplan_id = :battleplan_id
			   AND (a.is_hidden = 0 OR a.is_hidden IS NULL)
			 ORDER BY a.sort_order ASC, a.name ASC;',
			['battleplan_id' => $battleplanId]
		);

		$deck = [];
		foreach ($rows as $row) {
			$abilityId = (int)($row['id'] ?? 0);
			if ($abilityId <= 0) {
				continue;
			}
			$commandCost = ($row['command_cost'] === null || $row['command_cost'] === '')
				? null
				: (int)$row['command_cost'];
			$key = 'battleplan:' . $abilityId;
			$source = trim((string)($row['battleplan_name'] ?? ''));
			if ($source === '') {
				$source = 'バトルプラン';
			}
			$entry = $this->buildDeckEntry(
				$key,
				$row['name'] ?? '',
				$row['effect'] ?? '',
				$row['trigger_phase'] ?? '',
				$row['trigger_turn'] ?? '',
				$source,
				null,
				'battleplan',
				$key,
				$commandCost,
				$row['activation'] ?? 'active',
				$row['usage_scope'] ?? 'unlimited',
				$row['usage_per'] ?? 'army',
				trim((string)($row['trigger_condition_ja'] ?? ''))
			);
			$iconType = trim((string)($row['icon_type'] ?? ''));
			if ($iconType !== '') {
				$entry['iconType'] = $iconType;
			}
			$deck[] = $entry;
		}

		return $deck;
	}

	/**
	 * 全ファクション共通のコアアビリティ／ユニバーサルコマンドを取得する。
	 * 特定ユニット/ファクションに属さず、command_cost にCP費用を保持する。
	 */
	private function getCommonAbilities(): array
	{
		return $this->db->select(
			'SELECT id, name, command_cost, trigger_phase, trigger_turn, activation, usage_scope, usage_per, trigger_condition_ja, icon_type, effect, flavor_text
             FROM m_common_abilities
             WHERE is_hidden = 0 OR is_hidden IS NULL
             ORDER BY sort_order ASC, name ASC;'
		);
	}

	private function deduplicateAbilityDeck(array $deck): array
	{
		$map = [];

		foreach ($deck as $entry) {
			$dedupeKey = $entry['dedupeKey'] ?? $entry['key'];
			if (!isset($map[$dedupeKey])) {
				$entry['key'] = $dedupeKey;
				$entry['dedupeKey'] = $dedupeKey;
				if (!isset($entry['unitNames'])) {
					$entry['unitNames'] = $entry['unitName'] ? [$entry['unitName']] : [];
				}
				$map[$dedupeKey] = $entry;
				continue;
			}

			$existing = &$map[$dedupeKey];
			foreach ($entry['unitNames'] ?? [] as $name) {
				if ($name !== '' && !in_array($name, $existing['unitNames'], true)) {
					$existing['unitNames'][] = $name;
				}
			}
			if (!empty($entry['unitName']) && !in_array($entry['unitName'], $existing['unitNames'], true)) {
				$existing['unitNames'][] = $entry['unitName'];
			}
			unset($existing);
		}

		return array_values($map);
	}

	private function buildDeckEntry(
		string $key,
		string $name,
		string $effect,
		string $triggerPhase,
		string $triggerTurn,
		string $source,
		?string $unitName,
		string $category,
		?string $dedupeKey = null,
		?int $commandCost = null,
		?string $activation = 'active',
		?string $usageScope = 'unlimited',
		?string $usagePer = 'unit',
		?string $triggerCondition = '',
		?string $castingValue = null,
		?string $castingType = null
	): array {
		$dedupeKey = $dedupeKey ?? $key;
		$unitNames = $unitName ? [$unitName] : [];

		$castingValue = $castingValue !== null ? trim((string)$castingValue) : '';
		$castingType = $castingType !== null ? trim((string)$castingType) : '';

		$activation = $this->normalizeActivation($activation);
		$usageScope = $this->normalizeUsageScope($usageScope);
		$usagePer   = $this->normalizeUsagePer($usagePer);

		return [
			'key'              => $dedupeKey,
			'dedupeKey'        => $dedupeKey,
			'name'             => $name,
			'effect'           => $effect,
			'triggerPhase'     => $triggerPhase,
			'triggerPhaseNorm' => $this->normalizeTriggerPhaseForDeck($triggerPhase),
			'triggerPhaseNorms' => $this->normalizeTriggerPhasesForDeck($triggerPhase),
			'triggerTurn'      => $triggerTurn,
			'triggerTurnNorm'  => $this->normalizeTriggerTurnForDeck($triggerTurn),
			'source'           => $source,
			'unitName'         => $unitName,
			'unitNames'        => $unitNames,
			'category'         => $category,
			'commandCost'      => $commandCost,
			'activation'       => $activation,
			'usageScope'       => $usageScope,
			'usagePer'         => $usagePer,
			'triggerCondition' => (string)($triggerCondition ?? ''),
			'castingValue'     => $castingValue !== '' ? $castingValue : null,
			'castingType'      => $castingType !== '' ? $castingType : null,
		];
	}

	/** activation を正規化(active/passive/reaction)。 */
	private function normalizeActivation(?string $value): string
	{
		$v = strtolower(trim((string)($value ?? '')));
		return in_array($v, ['active', 'passive', 'reaction'], true) ? $v : 'active';
	}

	/** usage_scope を正規化(unlimited/once_per_turn/once_per_phase/once_per_battle)。 */
	private function normalizeUsageScope(?string $value): string
	{
		$v = strtolower(trim((string)($value ?? '')));
		$allowed = ['unlimited', 'once_per_turn', 'once_per_phase', 'once_per_battle'];
		return in_array($v, $allowed, true) ? $v : 'unlimited';
	}

	/** usage_per を正規化(unit/army)。 */
	private function normalizeUsagePer(?string $value): string
	{
		$v = strtolower(trim((string)($value ?? '')));
		return $v === 'army' ? 'army' : 'unit';
	}

	private function getBattleFormationById(int $id): ?array
	{
		$rows = $this->db->select(
			'SELECT * FROM m_battle_formations WHERE id = :id LIMIT 1;',
			['id' => $id]
		);
		return !empty($rows) ? $rows[0] : null;
	}

	private function buildLoreDeckEntries(
		int $loreRowId,
		string $table,
		string $nameColumn,
		string $keyPrefix,
		string $sourceLabel,
		string $category
	): array {
		if ($loreRowId <= 0) {
			return [];
		}

		$anchor = $this->db->select(
			"SELECT * FROM {$table} WHERE id = :id LIMIT 1;",
			['id' => $loreRowId]
		);
		if (empty($anchor)) {
			return [];
		}
		$anchor = $anchor[0];

		$rows = $this->db->select(
			"SELECT * FROM {$table}
			 WHERE faction_id = :faction_id AND lore_name = :lore_name
			 ORDER BY id ASC;",
			[
				'faction_id' => (int)$anchor['faction_id'],
				'lore_name'  => $anchor['lore_name'],
			]
		);

		// 祈祷は詠唱値(chanting_value)、呪文/顕現は発動値(casting_value)を保持する。
		$valueColumn = ($category === 'prayer') ? 'chanting_value' : 'casting_value';

		$entries = [];
		foreach ($rows as $row) {
			$entries[] = $this->buildDeckEntry(
				$keyPrefix . ':' . $loreRowId . ':' . (int)$row['id'],
				$row[$nameColumn] ?? $row['lore_name'] ?? '',
				$row['effect'] ?? '',
				$row['trigger_phase'] ?? 'hero',
				$row['trigger_turn'] ?? '',
				$sourceLabel,
				$row['lore_name'] ?? null,
				$category,
				null,
				null,
				$row['activation'] ?? 'active',
				$row['usage_scope'] ?? 'unlimited',
				$row['usage_per'] ?? 'unit',
				trim((string)($row['trigger_condition_ja'] ?? '')),
				$row[$valueColumn] ?? null
			);
		}
		return $entries;
	}

	private function resolveEnhancementHeroLabel(
		array $regiments,
		$regimentIndex,
		?string $unitSlot,
		int $unitId
	): ?string {
		if ($regimentIndex === null || $regimentIndex === '') {
			return null;
		}
		$regIdx = (int)$regimentIndex;
		if (!isset($regiments[$regIdx])) {
			return null;
		}
		$regiment = $regiments[$regIdx];
		$slot = $unitSlot ?? 'leader';

		if ($slot === 'leader') {
			return $regiment['hero']['name'] ?? null;
		}

		if (preg_match('/^(\d+)$/', (string)$slot, $m)) {
			$unitIndex = (int)$m[1];
			$units = $regiment['units'] ?? [];
			if (isset($units[$unitIndex]) && (int)($units[$unitIndex]['id'] ?? 0) === $unitId) {
				return $units[$unitIndex]['name'] ?? null;
			}
		}

		return null;
	}

	/**
	 * カンマ区切りで複数登録された trigger_phase を正規化フェーズの配列に変換する。
	 * 空の場合は ['any'] を返す。重複は除去する。
	 */
	private function normalizeTriggerPhasesForDeck(?string $trigger): array
	{
		$raw = trim((string)$trigger);
		if ($raw === '') {
			return ['any'];
		}
		$parts = array_filter(
			array_map('trim', explode(',', $raw)),
			static fn($p) => $p !== ''
		);
		if (empty($parts)) {
			return ['any'];
		}
		$norms = array_map(
			fn($p) => $this->normalizeTriggerPhaseForDeck($p),
			$parts
		);
		return array_values(array_unique($norms));
	}

	private function normalizeTriggerPhaseForDeck(?string $trigger): string
	{
		$raw = strtoupper(trim((string)$trigger));
		if ($raw === '') {
			return 'any';
		}
		if (strpos($raw, 'DEPLOY') !== false) {
			return 'deployment';
		}
		if (strpos($raw, 'ROUND') !== false && strpos($raw, 'START') !== false) {
			return 'round_start';
		}
		if (strpos($raw, 'HERO') !== false) {
			return 'hero';
		}
		if (strpos($raw, 'MOVEMENT') !== false || $raw === 'MOVE') {
			return 'movement';
		}
		if (strpos($raw, 'SHOOT') !== false) {
			return 'shooting';
		}
		if (strpos($raw, 'CHARGE') !== false) {
			return 'charge';
		}
		if (strpos($raw, 'COMBAT') !== false || $raw === 'FIGHT') {
			return 'combat';
		}
		if (strpos($raw, 'END') !== false) {
			return 'end';
		}
		if (strpos($raw, 'ANY') !== false) {
			return 'any';
		}
		return 'any';
	}

	private function normalizeTriggerTurnForDeck(?string $triggerTurn): string
	{
		$raw = strtolower(trim((string)$triggerTurn));
		if ($raw === '') {
			return 'your';
		}
		if (strpos($raw, 'opponent') !== false || strpos($raw, 'enemy') !== false) {
			return 'opponent';
		}
		if (strpos($raw, 'any') !== false || strpos($raw, 'both') !== false) {
			return 'any';
		}
		if (strpos($raw, 'battle') !== false || strpos($raw, 'game') !== false) {
			return 'battle';
		}
		if (strpos($raw, 'your') !== false) {
			return 'your';
		}
		return 'your';
	}

	public function isUnitEligibleForHero(int $heroId, int $unitId, int $factionId = 0): bool
	{
		if ($factionId <= 0) {
			$hero = $this->getUnitById($heroId);
			$factionId = $hero ? (int)$hero['faction_id'] : 0;
		}
		$companions = $this->getCompanionUnitsForHero($heroId, $factionId);
		foreach ($companions as $u) {
			if ((int)$u['id'] === $unitId) {
				return true;
			}
		}
		return false;
	}

	public function validateRosterForUser(int $rosterId, int $userId, ?int $factionId = null): bool
	{
		$roster = $this->getRosterById($rosterId, $userId);
		if (!$roster) {
			return false;
		}
		if ($factionId && (int)$roster['faction_id'] !== $factionId) {
			return false;
		}
		return true;
	}

	/**
	 * 連隊の随伴ユニットが、連隊長(HERO)の編成枠 max_limit を超えていないか検証する。
	 *
	 * 明示割当(assigned_option_id)はその枠でカウントし、未割当ユニットは残容量へ
	 * 割当ベース(マッチング)でフィット可能か判定する。0=無制限。
	 *
	 * @return string|null エラーメッセージ。問題なければ null。
	 */
	private function validateRegimentOptionLimits(int $heroId, int $factionId, array $units): ?string
	{
		$limits = $this->getHeroOptionLimits($heroId);
		if (empty($limits)) {
			return null; // 枠定義が無い HERO は従来通り総数制限のみ
		}

		$capacity = [];          // option_id => 容量 (0 は無制限)
		foreach ($limits as $l) {
			$capacity[(int)$l['option_id']] = (int)$l['max_limit'];
		}

		// この HERO 文脈での各ユニットの適格 option_id を引く lookup
		$companions = $this->getCompanionUnitsForHero($heroId, $factionId);
		$eligibleByUnit = [];
		foreach ($companions as $c) {
			$eligibleByUnit[(int)$c['id']] = $this->parseOptionIds($c['option_ids'] ?? null);
		}

		$counts = [];            // option_id => 明示割当の数
		$unassigned = [];        // 未割当ユニットの適格 option_id 配列

		foreach ($units as $unit) {
			$unitId = (int)($unit['unit_id'] ?? 0);
			if ($unitId <= 0) {
				continue;
			}
			$eligible = $eligibleByUnit[$unitId] ?? [];
			if (empty($eligible)) {
				continue; // 枠制約の対象外(無制限相当)
			}
			$assigned = (int)($unit['assigned_option_id'] ?? 0);
			if ($assigned > 0) {
				if (!in_array($assigned, $eligible, true)) {
					return '指定された連隊編成枠が不正です。';
				}
				$counts[$assigned] = ($counts[$assigned] ?? 0) + 1;
			} else {
				$unassigned[] = $eligible;
			}
		}

		// 明示割当が上限超過していないか
		foreach ($counts as $optionId => $cnt) {
			$cap = $capacity[$optionId] ?? 0;
			if ($cap > 0 && $cnt > $cap) {
				return 'この連隊長の編成枠（上限）を超えています。';
			}
		}

		// 残容量で未割当ユニットをフィットできるか
		$residual = [];
		foreach ($capacity as $optionId => $cap) {
			$used = $counts[$optionId] ?? 0;
			$residual[$optionId] = ($cap === 0) ? PHP_INT_MAX : max(0, $cap - $used);
		}

		if (!$this->regimentAssignmentFeasible($unassigned, $residual)) {
			return 'この連隊長の編成枠（上限）を超えています。';
		}

		return null;
	}

	/**
	 * 容量付き二部マッチング(増加道法)。各ユニット(適格 option_id 配列)を、容量の残る枠へ
	 * 1つずつ割り当てられるか判定する。option_id が $residual に無い枠は容量0扱い。
	 * 既割当ユニットを別枠へ退避させる再割当も試すため、可能な割当が存在すれば必ず成功する。
	 *
	 * @param array $unitsOptionIds 各ユニットの適格 option_id 配列の配列
	 * @param array $residual       option_id => 残容量 (PHP_INT_MAX で無制限)
	 */
	private function regimentAssignmentFeasible(array $unitsOptionIds, array $residual): bool
	{
		if (empty($unitsOptionIds)) {
			return true;
		}

		$slotsByOption = []; // option_id => 現在その枠に割り当てた unit index の配列

		$tryAssign = function (int $u, array &$visited) use (&$tryAssign, $unitsOptionIds, $residual, &$slotsByOption): bool {
			foreach ($unitsOptionIds[$u] as $opt) {
				$opt = (int)$opt;
				if (isset($visited[$opt])) {
					continue;
				}
				$visited[$opt] = true;
				$cap = $residual[$opt] ?? 0;
				$current = $slotsByOption[$opt] ?? [];
				if (count($current) < $cap) {
					$slotsByOption[$opt][] = $u;
					return true;
				}
				// 容量満杯: 既割当ユニットを別枠へ退避できれば、この枠を空ける
				foreach ($current as $pos => $w) {
					if ($tryAssign($w, $visited)) {
						unset($slotsByOption[$opt][$pos]);
						$slotsByOption[$opt][] = $u;
						return true;
					}
				}
			}
			return false;
		};

		foreach (array_keys($unitsOptionIds) as $u) {
			$visited = [];
			if (!$tryAssign((int)$u, $visited)) {
				return false;
			}
		}
		return true;
	}

	public function saveRoster(int $userId, array $data, ?int $rosterId = null): array
	{
		if ($rosterId && !$this->validateRosterForUser($rosterId, $userId)) {
			return [false, 'ロスターが見つかりません。'];
		}

		$now = date('Y-m-d H:i:s');
		$regiments = $data['regiments'] ?? [];
		$generalIndex = isset($data['general_regiment_index']) ? (int)$data['general_regiment_index'] : 0;

		if (empty($regiments)) {
			return [false, '連隊が1つ以上必要です。'];
		}

		if (count($regiments) > 5) {
			return [false, '連隊は最大5個までです。'];
		}

		$totalPoints = $this->calculateTotalPoints($data);
		$pointLimit = (int)($data['roster_points'] ?? 0);
		if ($pointLimit > 0 && $totalPoints > $pointLimit) {
			return [false, "合計ポイント ({$totalPoints} pt) が上限 ({$pointLimit} pt) を超えています。"];
		}

		foreach ($regiments as $idx => $regiment) {
			$heroId = (int)($regiment['hero_id'] ?? 0);
			if ($heroId <= 0) {
				return [false, '各連隊の連隊長 (HERO) を選択してください。'];
			}

			$units = $regiment['units'] ?? [];
			$maxUnits = ((int)$idx === $generalIndex) ? 4 : 3;
			$unitCount = count(array_filter($units, function ($u) {
				return (int)($u['unit_id'] ?? 0) > 0;
			}));
			if ($unitCount > $maxUnits) {
				return [false, 'ジェネラルの連隊はユニット4個、それ以外は3個までです。'];
			}
			foreach ($units as $unit) {
				$unitId = (int)($unit['unit_id'] ?? 0);
				if ($unitId <= 0) {
					continue;
				}
				if (!$this->isUnitEligibleForHero($heroId, $unitId, (int)$data['faction_id'])) {
					return [false, 'Heroに適格でないユニットが含まれています。'];
				}
			}

			$optionErr = $this->validateRegimentOptionLimits($heroId, (int)$data['faction_id'], $units);
			if ($optionErr !== null) {
				return [false, $optionErr];
			}
		}

		// 固有ユニットの重複チェック（ロスター全体で同一IDは1体まで）
		$idCounts = [];
		foreach ($regiments as $regiment) {
			$heroId = (int)($regiment['hero_id'] ?? 0);
			if ($heroId > 0) {
				$idCounts[$heroId] = ($idCounts[$heroId] ?? 0) + 1;
			}
			foreach (($regiment['units'] ?? []) as $unit) {
				$uid = (int)($unit['unit_id'] ?? 0);
				if ($uid > 0) {
					$idCounts[$uid] = ($idCounts[$uid] ?? 0) + 1;
				}
			}
		}
		foreach ($idCounts as $uid => $count) {
			if ($count > 1 && $this->unitFlags((int)$uid)['is_unique']) {
				return [false, '固有ユニットは1体までしか選択できません。'];
			}
		}

		// 総大将チェック（総大将ユニットがいる場合、ジェネラルは総大将でなければならない）
		$hasGeneralUnit = false;
		$generalLeaderIsGeneralUnit = false;
		foreach ($regiments as $idx => $regiment) {
			$heroId = (int)($regiment['hero_id'] ?? 0);
			if ($heroId <= 0) {
				continue;
			}
			if ($this->unitFlags($heroId)['is_general']) {
				$hasGeneralUnit = true;
				if ((int)$idx === $generalIndex) {
					$generalLeaderIsGeneralUnit = true;
				}
			}
		}
		if ($hasGeneralUnit && !$generalLeaderIsGeneralUnit) {
			return [false, '総大将を持つユニットはジェネラルに指定する必要があります。'];
		}

		$enhanceErr = $this->validateEnhancements($data, $regiments);
		if ($enhanceErr) {
			return [false, $enhanceErr];
		}

		$selectedTactics = $this->normalizeSelectedTacticsCards($data['selected_tactics_cards'] ?? []);
		$tacticsErr = $this->validateBattleTacticsSelection($selectedTactics);
		if ($tacticsErr) {
			return [false, $tacticsErr];
		}

		$sqls = [];
		$binds = [];

		if ($rosterId) {
			$sqls[] = 'UPDATE t_rosters SET
				name = :name,
				faction_id = :faction_id,
				total_points = :total_points,
				battle_formation_id = :battle_formation_id,
				spell_lore_id = :spell_lore_id,
				prayer_lore_id = :prayer_lore_id,
				manifestation_lore_id = :manifestation_lore_id,
				terrain_id = :terrain_id,
				grand_alliance = :grand_alliance,
				point_limit = :point_limit,
				heroic_trait_id = :heroic_trait_id,
				trait_target_unit_id = :trait_target_unit_id,
				trait_regiment_index = :trait_regiment_index,
				trait_unit_slot = :trait_unit_slot,
				artefact_id = :artefact_id,
				artefact_target_unit_id = :artefact_target_unit_id,
				artefact_regiment_index = :artefact_regiment_index,
				artefact_unit_slot = :artefact_unit_slot,
				season_enhancement_id = :season_enhancement_id,
				season_enhancement_target_unit_id = :season_enhancement_target_unit_id,
				season_enhancement_regiment_index = :season_enhancement_regiment_index,
				season_enhancement_unit_slot = :season_enhancement_unit_slot,
				updated_at = :updated_at
				WHERE id = :id AND user_id = :user_id;';
			$binds[] = array_merge($this->buildRosterEnhancementBind($data), [
				'name'                   => $data['roster_name'],
				'faction_id'             => (int)$data['faction_id'],
				'total_points'           => $totalPoints,
				'battle_formation_id'    => $this->nullableInt($data['battle_formation'] ?? null),
				'spell_lore_id'          => $this->nullableInt($data['spell_lore'] ?? null),
				'prayer_lore_id'         => $this->nullableInt($data['prayer_lore'] ?? null),
				'manifestation_lore_id'  => $this->nullableInt($data['manifestation_lore'] ?? null),
				'terrain_id'             => $this->nullableInt($data['faction_terrain'] ?? null),
				'grand_alliance'         => $data['grand_alliance'] ?? null,
				'point_limit'            => $pointLimit ?: null,
				'updated_at'             => $now,
				'id'                     => $rosterId,
				'user_id'                => $userId,
			]);

			$sqls[] = 'DELETE FROM t_roster_regiments WHERE roster_id = :roster_id;';
			$binds[] = ['roster_id' => $rosterId];
		} else {
			$sqls[] = 'INSERT INTO t_rosters (
				user_id, faction_id, name, total_points,
				battle_formation_id, spell_lore_id, prayer_lore_id, manifestation_lore_id,
				terrain_id,
				grand_alliance, point_limit,
				heroic_trait_id, trait_target_unit_id, trait_regiment_index, trait_unit_slot,
				artefact_id, artefact_target_unit_id, artefact_regiment_index, artefact_unit_slot,
				season_enhancement_id, season_enhancement_target_unit_id,
				season_enhancement_regiment_index, season_enhancement_unit_slot,
				created_at, updated_at
			) VALUES (
				:user_id, :faction_id, :name, :total_points,
				:battle_formation_id, :spell_lore_id, :prayer_lore_id, :manifestation_lore_id,
				:terrain_id,
				:grand_alliance, :point_limit,
				:heroic_trait_id, :trait_target_unit_id, :trait_regiment_index, :trait_unit_slot,
				:artefact_id, :artefact_target_unit_id, :artefact_regiment_index, :artefact_unit_slot,
				:season_enhancement_id, :season_enhancement_target_unit_id,
				:season_enhancement_regiment_index, :season_enhancement_unit_slot,
				:created_at, :updated_at
			);';
			$binds[] = array_merge($this->buildRosterEnhancementBind($data), [
				'user_id'                => $userId,
				'faction_id'             => (int)$data['faction_id'],
				'name'                   => $data['roster_name'],
				'total_points'           => $totalPoints,
				'battle_formation_id'    => $this->nullableInt($data['battle_formation'] ?? null),
				'spell_lore_id'          => $this->nullableInt($data['spell_lore'] ?? null),
				'prayer_lore_id'         => $this->nullableInt($data['prayer_lore'] ?? null),
				'manifestation_lore_id'  => $this->nullableInt($data['manifestation_lore'] ?? null),
				'terrain_id'             => $this->nullableInt($data['faction_terrain'] ?? null),
				'grand_alliance'         => $data['grand_alliance'] ?? null,
				'point_limit'            => $pointLimit ?: null,
				'created_at'             => $now,
				'updated_at'             => $now,
			]);
		}

		$result = $this->db->transact($sqls, $binds);
		if (!$result[0]) {
			return [false, $result[1] ?? '保存に失敗しました。'];
		}

		if (!$rosterId) {
			$rosterId = (int)$this->db->lastTransactInsertId;
		}

		$regimentSqls = [];
		$regimentBinds = [];

		foreach ($regiments as $sortOrder => $regiment) {
			$heroId = (int)$regiment['hero_id'];
			$regimentSqls[] = 'INSERT INTO t_roster_regiments (
				roster_id, sort_order, hero_unit_id, is_general, enhancement_trait, enhancement_artefact
			) VALUES (
				:roster_id, :sort_order, :hero_unit_id, :is_general, NULL, NULL
			);';
			$regimentBinds[] = [
				'roster_id'            => $rosterId,
				'sort_order'           => (int)$sortOrder,
				'hero_unit_id'         => $heroId,
				'is_general'           => ((int)$sortOrder === $generalIndex) ? 1 : 0,
			];
		}

		$regResult = $this->db->transact($regimentSqls, $regimentBinds);
		if (!$regResult[0]) {
			return [false, $regResult[1] ?? '連隊の保存に失敗しました。'];
		}

		$regimentRows = $this->getRosterRegiments($rosterId);
		$unitSqls = [];
		$unitBinds = [];

		foreach ($regimentRows as $row) {
			$sortOrder = (int)$row['sort_order'];
			$regimentData = $regiments[$sortOrder] ?? null;
			if (!$regimentData) {
				continue;
			}
			$units = $regimentData['units'] ?? [];
			$unitSort = 0;
			foreach ($units as $unit) {
				$unitId = (int)($unit['unit_id'] ?? 0);
				if ($unitId <= 0) {
					continue;
				}
				$unitSqls[] = 'INSERT INTO t_roster_regiment_units (
					regiment_id, unit_id, assigned_option_id, sort_order, is_reinforced
				) VALUES (
					:regiment_id, :unit_id, :assigned_option_id, :sort_order, :is_reinforced
				);';
				$unitBinds[] = [
					'regiment_id'        => (int)$row['id'],
					'unit_id'            => $unitId,
					'assigned_option_id' => $this->nullableInt($unit['assigned_option_id'] ?? null),
					'sort_order'         => $unitSort,
					'is_reinforced'      => !empty($unit['is_reinforced']) ? 1 : 0,
				];
				$unitSort++;
			}
		}

		if (!empty($unitSqls)) {
			$unitResult = $this->db->transact($unitSqls, $unitBinds);
			if (!$unitResult[0]) {
				return [false, $unitResult[1] ?? '随伴部隊の保存に失敗しました。'];
			}
		}

		$tacticsResult = $this->replaceRosterBattleTactics($rosterId, $selectedTactics);
		if (!$tacticsResult[0]) {
			return [false, $tacticsResult[1] ?? 'バトルタクティクスの保存に失敗しました。'];
		}

		return [true, $rosterId];
	}

	public function deleteRoster(int $rosterId, int $userId): bool
	{
		$result = $this->db->executesql(
			'DELETE FROM t_rosters WHERE id = :id AND user_id = :user_id;',
			['id' => $rosterId, 'user_id' => $userId]
		);
		return (bool)$result[0];
	}

	private function nullableInt($value): ?int
	{
		if ($value === null || $value === '') {
			return null;
		}
		return (int)$value;
	}

	/**
	 * ユニットの総大将/固有フラグを取得（静的キャッシュ付き）
	 *
	 * @return array{is_general:int, is_unique:int}
	 */
	private function unitFlags(int $unitId): array
	{
		static $cache = [];
		if (!isset($cache[$unitId])) {
			$unit = $this->getUnitById($unitId);
			$cache[$unitId] = [
				'is_general' => (int)($unit['is_general'] ?? 0),
				'is_unique'  => (int)($unit['is_unique'] ?? 0),
			];
		}
		return $cache[$unitId];
	}

	public function calculateTotalPoints(array $data): int
	{
		$total = 0;
		$regiments = $data['regiments'] ?? [];

		foreach ($regiments as $regiment) {
			$heroId = (int)($regiment['hero_id'] ?? 0);
			if ($heroId > 0) {
				$hero = $this->getUnitById($heroId);
				if ($hero) {
					$total += (int)$hero['points'];
				}
			}

			$units = $regiment['units'] ?? [];
			foreach ($units as $unit) {
				$unitId = (int)($unit['unit_id'] ?? 0);
				if ($unitId <= 0) {
					continue;
				}
				$unitData = $this->getUnitById($unitId);
				if ($unitData) {
					$pts = (int)$unitData['points'];
					if (!empty($unit['is_reinforced'])) {
						$pts *= 2;
					}
					$total += $pts;
				}
			}
		}

		$optionMap = [
			'battle_formation'   => 'm_battle_formations',
			'spell_lore'         => 'm_spell_lores',
			'prayer_lore'        => 'm_prayer_lores',
			'manifestation_lore' => 'm_manifestation_lores',
		];

		foreach ($optionMap as $key => $table) {
			$id = (int)($data[$key] ?? 0);
			if ($id > 0) {
				$sql = "SELECT points FROM {$table} WHERE id = :id LIMIT 1;";
				$rows = $this->db->select($sql, ['id' => $id]);
				if (!empty($rows)) {
					$total += (int)($rows[0]['points'] ?? 0);
				}
			}
		}

		$terrainId = (int)($data['faction_terrain'] ?? 0);
		if ($terrainId > 0) {
			$terrain = $this->getUnitById($terrainId);
			if ($terrain) {
				$total += (int)$terrain['points'];
			}
		}

		$traitId = (int)($data['heroic_trait_id'] ?? 0);
		if ($traitId > 0) {
			$trait = $this->getHeroicTraitById($traitId);
			if ($trait) {
				$total += (int)$trait['points'];
			}
		}

		$artefactId = (int)($data['artefact_id'] ?? 0);
		if ($artefactId > 0) {
			$artefact = $this->getArtefactById($artefactId);
			if ($artefact) {
				$total += (int)$artefact['points'];
			}
		}

		$seasonEnhId = (int)($data['season_enhancement_id'] ?? 0);
		if ($seasonEnhId > 0) {
			$seasonEnh = $this->getSeasonEnhancementById($seasonEnhId);
			if ($seasonEnh) {
				$total += (int)$seasonEnh['points'];
			}
		}

		return $total;
	}

	private function buildRosterEnhancementBind(array $data): array
	{
		return [
			'heroic_trait_id'           => $this->nullableInt($data['heroic_trait_id'] ?? null),
			'trait_target_unit_id'      => $this->nullableInt($data['trait_target_unit_id'] ?? null),
			'trait_regiment_index'      => $this->nullableInt($data['trait_regiment_index'] ?? null),
			'trait_unit_slot'           => $this->nullableString($data['trait_unit_slot'] ?? null),
			'artefact_id'               => $this->nullableInt($data['artefact_id'] ?? null),
			'artefact_target_unit_id'   => $this->nullableInt($data['artefact_target_unit_id'] ?? null),
			'artefact_regiment_index'   => $this->nullableInt($data['artefact_regiment_index'] ?? null),
			'artefact_unit_slot'        => $this->nullableString($data['artefact_unit_slot'] ?? null),
			'season_enhancement_id'              => $this->nullableInt($data['season_enhancement_id'] ?? null),
			'season_enhancement_target_unit_id'  => $this->nullableInt($data['season_enhancement_target_unit_id'] ?? null),
			'season_enhancement_regiment_index'  => $this->nullableInt($data['season_enhancement_regiment_index'] ?? null),
			'season_enhancement_unit_slot'       => $this->nullableString($data['season_enhancement_unit_slot'] ?? null),
		];
	}

	private function nullableString($value): ?string
	{
		if ($value === null || $value === '') {
			return null;
		}
		return (string)$value;
	}

	private function resolveEnhancementTarget(array $regiments, int $regimentIndex, ?string $slot, int $unitId): bool
	{
		if ($unitId <= 0 || !isset($regiments[$regimentIndex])) {
			return false;
		}

		$regiment = $regiments[$regimentIndex];
		$slot = $slot ?? 'leader';

		if ($slot === 'leader') {
			return (int)($regiment['hero_id'] ?? 0) === $unitId;
		}

		if (preg_match('/^(\d+)$/', $slot, $matches)) {
			$unitIndex = (int)$matches[1];
			$units = $regiment['units'] ?? [];
			if (!isset($units[$unitIndex])) {
				return false;
			}
			return (int)($units[$unitIndex]['unit_id'] ?? 0) === $unitId;
		}

		return false;
	}

	private function validateEnhancements(array $data, array $regiments): ?string
	{
		$traitId = (int)($data['heroic_trait_id'] ?? 0);
		$traitTarget = (int)($data['trait_target_unit_id'] ?? 0);
		$traitReg = $data['trait_regiment_index'] ?? null;
		$traitSlot = $data['trait_unit_slot'] ?? null;
		$artefactId = (int)($data['artefact_id'] ?? 0);
		$artefactTarget = (int)($data['artefact_target_unit_id'] ?? 0);
		$artefactReg = $data['artefact_regiment_index'] ?? null;
		$artefactSlot = $data['artefact_unit_slot'] ?? null;

		if ($traitId > 0) {
			if ($traitTarget <= 0 || $traitReg === '' || $traitReg === null) {
				return '英雄特性の付与先 Hero を選択してください。';
			}
			if (!$this->resolveEnhancementTarget($regiments, (int)$traitReg, $traitSlot, $traitTarget)) {
				return '英雄特性の付与先がロスター内の Hero と一致しません。';
			}
			if ($this->unitFlags($traitTarget)['is_unique']) {
				return '固有ユニットには英雄特性を付与できません。';
			}
		} elseif ($traitTarget > 0 || ($traitReg !== '' && $traitReg !== null)) {
			return '英雄特性が未選択です。';
		}

		if ($artefactId > 0) {
			if ($artefactTarget <= 0 || $artefactReg === '' || $artefactReg === null) {
				return '神器の付与先 Hero を選択してください。';
			}
			if (!$this->resolveEnhancementTarget($regiments, (int)$artefactReg, $artefactSlot, $artefactTarget)) {
				return '神器の付与先がロスター内の Hero と一致しません。';
			}
			if ($this->unitFlags($artefactTarget)['is_unique']) {
				return '固有ユニットには神器を付与できません。';
			}
		} elseif ($artefactTarget > 0 || ($artefactReg !== '' && $artefactReg !== null)) {
			return '神器が未選択です。';
		}

		$seasonId = (int)($data['season_enhancement_id'] ?? 0);
		$seasonTarget = (int)($data['season_enhancement_target_unit_id'] ?? 0);
		$seasonReg = $data['season_enhancement_regiment_index'] ?? null;
		$seasonSlot = $data['season_enhancement_unit_slot'] ?? null;
		$seasonLabel = SeasonEnhancements::DEFAULT_LABEL_JA;
		$factionId = (int)($data['faction_id'] ?? 0);
		if ($factionId > 0) {
			$seasonLabel = $this->getSeasonEnhancementLabel(
				$factionId,
				SeasonEnhancements::SEASON_2026_27
			)['label_ja'];
		}

		if ($seasonId > 0) {
			if ($seasonTarget <= 0 || $seasonReg === '' || $seasonReg === null) {
				return $seasonLabel . 'の付与先ユニットを選択してください。';
			}
			if (!$this->resolveEnhancementTarget($regiments, (int)$seasonReg, $seasonSlot, $seasonTarget)) {
				return $seasonLabel . 'の付与先がロスター内のユニットと一致しません。';
			}
			$seasonEnh = $this->getSeasonEnhancementById($seasonId);
			if (!$seasonEnh) {
				return $seasonLabel . 'が不正です。';
			}
			if ((int)$seasonEnh['faction_id'] !== $factionId) {
				return $seasonLabel . 'が選択中の陣営と一致しません。';
			}
			if (($seasonEnh['season'] ?? '') !== SeasonEnhancements::SEASON_2026_27) {
				return $seasonLabel . 'のシーズンが不正です。';
			}
			$requiredKw = $seasonEnh['keywords'] ?? [];
			if (!$this->unitMatchesSeasonEnhancementKeywords($seasonTarget, $requiredKw)) {
				return $seasonLabel . 'の付与先ユニットが適格キーワード条件を満たしていません。';
			}
		} elseif ($seasonTarget > 0 || ($seasonReg !== '' && $seasonReg !== null)) {
			return $seasonLabel . 'が未選択です。';
		}

		return null;
	}

	private function resolveEnhancementName($id, string $type): ?string
	{
		if (!$id) {
			return null;
		}
		if ($type === 'trait') {
			$row = $this->getHeroicTraitById((int)$id);
		} elseif ($type === 'artefact') {
			$row = $this->getArtefactById((int)$id);
		} elseif ($type === 'season') {
			$row = $this->getSeasonEnhancementById((int)$id);
		} else {
			return null;
		}
		return $row ? $row['name'] : null;
	}

	/**
	 * マッチ用ユニット詳細向けに、割当先付きのエンハンスメント詳細を返す。
	 *
	 * @return array{id:int,name:string,effect:string,targetUnitId:int,label?:string}|null
	 */
	private function resolveEnhancementDetailForMatch(array $roster, string $type): ?array
	{
		if ($type === 'trait') {
			$id = (int)($roster['heroic_trait_id'] ?? 0);
			if ($id <= 0) {
				return null;
			}
			$row = $this->getHeroicTraitById($id);
			if (!$row) {
				return null;
			}
			return [
				'id'           => $id,
				'name'         => (string)($row['name'] ?? ''),
				'effect'       => (string)($row['effect'] ?? $row['description'] ?? ''),
				'targetUnitId' => (int)($roster['trait_target_unit_id'] ?? 0),
				'label'        => '英雄特性',
			];
		}

		if ($type === 'artefact') {
			$id = (int)($roster['artefact_id'] ?? 0);
			if ($id <= 0) {
				return null;
			}
			$row = $this->getArtefactById($id);
			if (!$row) {
				return null;
			}
			return [
				'id'           => $id,
				'name'         => (string)($row['name'] ?? ''),
				'effect'       => (string)($row['effect'] ?? $row['flavor_text'] ?? ''),
				'targetUnitId' => (int)($roster['artefact_target_unit_id'] ?? 0),
				'label'        => '神器',
			];
		}

		if ($type === 'season') {
			$id = (int)($roster['season_enhancement_id'] ?? 0);
			if ($id <= 0) {
				return null;
			}
			$row = $this->getSeasonEnhancementById($id);
			if (!$row) {
				return null;
			}
			$factionId = (int)($roster['faction_id'] ?? 0);
			$season = (string)($row['season'] ?? SeasonEnhancements::SEASON_2026_27);
			$labelInfo = $factionId > 0
				? $this->getSeasonEnhancementLabel($factionId, $season)
				: ['label_ja' => SeasonEnhancements::DEFAULT_LABEL_JA];
			return [
				'id'           => $id,
				'name'         => (string)($row['name'] ?? ''),
				'effect'       => (string)($row['effect'] ?? ''),
				'targetUnitId' => (int)($roster['season_enhancement_target_unit_id'] ?? 0),
				'label'        => (string)($labelInfo['label_ja'] ?? SeasonEnhancements::DEFAULT_LABEL_JA),
			];
		}

		return null;
	}
}
