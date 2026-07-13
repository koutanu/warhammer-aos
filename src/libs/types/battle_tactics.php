<?php

/**
 * GHB Battle Tactics カード／段階／ロスター選択の型定義と定数。
 *
 * 達成順序の正本は TACTIC_STAGE_ORDER（常に Affray → Strike → Domination）。
 *
 * @phpstan-type BattleTacticStage array{
 *   id: int,
 *   battle_tactic_id: int,
 *   stage: 'affray'|'strike'|'domination',
 *   stage_order: 1|2|3,
 *   name: string,
 *   effect: string,
 *   victory_points: int
 * }
 * @phpstan-type BattleTacticCard array{
 *   id: int,
 *   name: string,
 *   season: string,
 *   grand_alliance: string|null,
 *   sort_order: int,
 *   stages: list<BattleTacticStage>
 * }
 * @phpstan-type RosterBattleTacticsSelection list<int>
 */
class BattleTactics
{
	public const SEASON_2024_25 = '2024-25';
	public const SEASON_2026_27 = '2026-27';

	/** ロスターに選択できるカード枚数の上限 */
	public const MAX_CARDS_PER_ROSTER = 2;

	/**
	 * 各カード内の達成順序（stage => stage_order）。
	 * Affray → Strike → Domination 以外の順序は許可しない。
	 *
	 * @var array<string, int>
	 */
	public const TACTIC_STAGE_ORDER = [
		'affray'      => 1,
		'strike'      => 2,
		'domination'  => 3,
	];

	/**
	 * stage_order から stage キーを返す。
	 */
	public static function stageKeyForOrder(int $order): ?string
	{
		$flipped = array_flip(self::TACTIC_STAGE_ORDER);
		return $flipped[$order] ?? null;
	}

	/**
	 * マスタの stages 配列が Affray→Strike→Domination の完全な3段階か。
	 *
	 * @param list<array{stage?:string,stage_order?:int}> $stages
	 */
	public static function hasValidStageSequence(array $stages): bool
	{
		if (count($stages) !== count(self::TACTIC_STAGE_ORDER)) {
			return false;
		}

		$byOrder = [];
		foreach ($stages as $stage) {
			$order = (int)($stage['stage_order'] ?? 0);
			$key = (string)($stage['stage'] ?? '');
			if ($order < 1 || $key === '') {
				return false;
			}
			if (isset($byOrder[$order])) {
				return false;
			}
			$expectedKey = self::stageKeyForOrder($order);
			if ($expectedKey === null || $expectedKey !== $key) {
				return false;
			}
			if ((int)(self::TACTIC_STAGE_ORDER[$key] ?? 0) !== $order) {
				return false;
			}
			$byOrder[$order] = $key;
		}

		foreach (self::TACTIC_STAGE_ORDER as $key => $order) {
			if (($byOrder[$order] ?? null) !== $key) {
				return false;
			}
		}

		return true;
	}
}
