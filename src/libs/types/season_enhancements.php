<?php

/**
 * GHB シーズン追加能力（陣営ごと・キーワード適格・ロスター全体で最大1つ）の型と定数。
 *
 * @phpstan-type SeasonEnhancementKeyword array{
 *   id: int,
 *   name: string,
 *   keyword_type: string,
 *   requirement: 'require'|'exclude'
 * }
 * @phpstan-type SeasonEnhancement array{
 *   id: int,
 *   faction_id: int,
 *   season: string,
 *   name: string,
 *   effect: string,
 *   points: int,
 *   sort_order: int,
 *   activation: string,
 *   usage_scope: string,
 *   usage_per: string,
 *   trigger_phase: string|null,
 *   trigger_turn: string,
 *   trigger_condition_ja: string|null,
 *   keywords: list<SeasonEnhancementKeyword>
 * }
 * @phpstan-type SeasonEnhancementLabel array{
 *   faction_id: int,
 *   season: string,
 *   label_ja: string,
 *   label_en: string|null
 * }
 */
class SeasonEnhancements
{
	public const SEASON_2026_27 = '2026-27';

	/** ラベル未登録時のフォールバック（UI 見出し） */
	public const DEFAULT_LABEL_JA = '追加能力';

	/** ロスターに選択できる追加能力の上限 */
	public const MAX_PER_ROSTER = 1;
}
