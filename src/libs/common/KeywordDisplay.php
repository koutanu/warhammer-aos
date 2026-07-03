<?php

/**
 * キーワード表示・パースの共通ロジック
 */
class KeywordDisplay
{
	/** accepts_param を立てる既知のベース名（unit キーワード） */
	public const PARAM_BASE_NAMES = [
		'魔術師', '神官', '加護',
		'WIZARD', 'PRIEST', 'WARD',
	];

	/**
	 * 表示用: ベース名 + 任意の param_value
	 */
	public static function format(string $name, ?string $param): string
	{
		$name = trim($name);
		$param = self::normalizeParam($param);
		if ($param === null || $param === '') {
			return $name;
		}
		return $name . '(' . $param . ')';
	}

	/**
	 * SQL GROUP_CONCAT 用の式（uk / km エイリアス前提）
	 */
	public static function sqlNameExpr(string $nameCol = 'km.name', string $paramCol = 'uk.param_value'): string
	{
		return "CASE
            WHEN {$paramCol} IS NOT NULL AND {$paramCol} <> ''
            THEN CONCAT({$nameCol}, '(', {$paramCol}, ')')
            ELSE {$nameCol}
        END";
	}

	/**
	 * 「魔術師(1)」「WARD (5+)」など1トークンを [ベース名, param|null] に分解
	 *
	 * @return array{0: string, 1: ?string}
	 */
	public static function parseToken(string $token): array
	{
		$token = trim($token);
		if ($token === '') {
			return ['', null];
		}
		if (preg_match('/^(.+?)\s*\(([^)]+)\)\s*$/u', $token, $m)) {
			return [trim($m[1]), self::normalizeParam($m[2])];
		}
		return [$token, null];
	}

	public static function isParamCapableName(string $name): bool
	{
		$upper = strtoupper(trim($name));
		foreach (self::PARAM_BASE_NAMES as $base) {
			if (strtoupper($base) === $upper || $base === trim($name)) {
				return true;
			}
		}
		return false;
	}

	public static function normalizeParam(?string $param): ?string
	{
		if ($param === null) {
			return null;
		}
		$param = trim($param);
		return $param === '' ? null : mb_substr($param, 0, 20);
	}
}
