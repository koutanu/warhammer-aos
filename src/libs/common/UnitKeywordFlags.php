<?php

/**
 * ユニットキーワード名から is_hero / is_general / is_unique を導出する。
 */
class UnitKeywordFlags
{
	public static function isHeroKeyword(string $name): bool
	{
		$upper = strtoupper(trim($name));
		return $upper === 'HERO' || trim($name) === '英雄';
	}

	public static function isGeneralKeyword(string $name): bool
	{
		$upper = strtoupper(trim($name));
		return str_contains($name, '総大将') || $upper === 'GENERAL';
	}

	public static function isUniqueKeyword(string $name): bool
	{
		$upper = strtoupper(trim($name));
		return trim($name) === '固有' || $upper === 'UNIQUE';
	}

	/**
	 * @param string[] $names
	 * @return array{is_hero:int, is_general:int, is_unique:int}
	 */
	public static function deriveFlagsFromNames(array $names): array
	{
		$flags = ['is_hero' => 0, 'is_general' => 0, 'is_unique' => 0];
		foreach ($names as $name) {
			if (self::isHeroKeyword($name)) {
				$flags['is_hero'] = 1;
			}
			if (self::isGeneralKeyword($name)) {
				$flags['is_general'] = 1;
			}
			if (self::isUniqueKeyword($name)) {
				$flags['is_unique'] = 1;
			}
		}
		return $flags;
	}
}
