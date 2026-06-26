-- 連隊オプションの option_name を「表示名(日本語可)」に解放するためのデカップリング。
--
-- 背景:
--   従来 option_name は (1) 画面表示ラベル兼 (2) ロジックの照合キー という二役を担っていた。
--   ロスター編成の随伴HERO判定が faction_keywords と option_name の LIKE 照合に依存していたため、
--   option_name を日本語にすると判定が壊れた。
--
-- 本マイグレーションでは:
--   1. 照合用の安定キー option_code(英語)を分離し、現在の option_name から複製する。
--      → インポート(import_wahapedia.php)のシードは option_code を参照するよう変更する。
--   2. ロジックを構造化テーブル(t_unit_regiment_eligibility / t_hero_regiment_options)に一本化する前提で、
--      これまで動的(LIKE照合)に導出していた「HERO 自身が入れる連隊」適格性を実データへバックフィルする。
--      ※ option_name がまだ英語のうちに実行すること。

-- 1) 照合用コード列を追加し、現状の英語 option_name を複製する。
ALTER TABLE m_regiment_options
    ADD COLUMN option_code VARCHAR(255) NULL AFTER option_name;

UPDATE m_regiment_options
SET option_code = UPPER(option_name)
WHERE option_code IS NULL OR option_code = '';

-- 2) 既存 HERO の「所属できる連隊(eligibility)」をバックフィルする。
--    従来 roster_model::getCompanionUnitsForHero の第2クエリが
--    「HERO の faction_keywords に option_name を含むか」で動的判定していたものを、
--    そのまま t_unit_regiment_eligibility へ確定保存する。
--    汎用枠(General Regiment)は HERO の合流対象から除外していたため、ここでも除外する。
INSERT IGNORE INTO t_unit_regiment_eligibility (unit_id, option_id)
SELECT u.id, ro.id
FROM m_units u
JOIN m_regiment_options ro
    ON ro.faction_id = u.faction_id
   AND UPPER(u.faction_keywords) LIKE CONCAT('%', UPPER(ro.option_name), '%')
WHERE u.is_hero = 1
  AND ro.option_name <> 'General Regiment';
