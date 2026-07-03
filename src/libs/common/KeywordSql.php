<?php

/**
 * キーワード表示用 SQL フラグメント（m_keywords_master + m_unit_keywords）
 */
class KeywordSql
{
	/**
	 * ユニットに紐づくキーワード名を GROUP_CONCAT する相関サブクエリ
	 *
	 * @param string $unitAlias 外側クエリのユニットテーブル別名
	 * @param string $type      'unit' | 'faction'
	 */
	public static function namesSubquery(string $unitAlias, string $type): string
	{
		$type = ($type === 'faction') ? 'faction' : 'unit';
		$expr = KeywordDisplay::sqlNameExpr('km.name', 'uk.param_value');
		return "(SELECT GROUP_CONCAT({$expr} ORDER BY km.sort_order ASC, km.name ASC SEPARATOR ', ')
                 FROM m_unit_keywords uk
                 JOIN m_keywords_master km ON km.id = uk.keyword_id AND km.keyword_type = '{$type}'
                 WHERE uk.unit_id = {$unitAlias}.id)";
	}

	/**
	 * 図鑑・ロスター表示用: ユニット/連隊枠キーワード + 大同盟/軍勢名
	 */
	public static function displayExpr(string $unitAlias = 'u', string $factionAlias = 'f'): string
	{
		$unitKw = self::namesSubquery($unitAlias, 'unit');
		$factionKw = self::namesSubquery($unitAlias, 'faction');
		return "CONCAT_WS(', ', NULLIF({$unitKw}, ''), NULLIF({$factionKw}, ''), NULLIF(UPPER({$factionAlias}.grand_alliance), ''), NULLIF(UPPER({$factionAlias}.name_en), ''))";
	}

	/**
	 * 大同盟・軍勢名を含めないキーワード文字列（マッチ画面など）
	 */
	public static function displayExprBasic(string $unitAlias = 'u'): string
	{
		$unitKw = self::namesSubquery($unitAlias, 'unit');
		$factionKw = self::namesSubquery($unitAlias, 'faction');
		return "CONCAT_WS(', ', NULLIF({$unitKw}, ''), NULLIF({$factionKw}, ''))";
	}

	/**
	 * 加護（WARD）キーワードの param_value を返す相関サブクエリ（モーダル防御力併記用）
	 */
	public static function wardParamSubquery(string $unitAlias = 'u'): string
	{
		return "(SELECT uk.param_value
                 FROM m_unit_keywords uk
                 JOIN m_keywords_master km ON km.id = uk.keyword_id
                 WHERE uk.unit_id = {$unitAlias}.id
                   AND km.keyword_type = 'unit'
                   AND km.name IN ('加護', 'WARD')
                 LIMIT 1)";
	}
}
