-- m_units.regiment_options（自由文の連隊編成テキスト）を廃止する。
--
-- 連隊編成の表示は、構造化テーブル（t_hero_regiment_options × m_regiment_options）から
-- 動的生成する方式へ移行済み（roster_model::regimentOptionsTextSubquery）。
-- 判定ロジックも t_unit_regiment_eligibility / t_hero_regiment_options に一本化済みのため、
-- 本カラムは不要になった。
--
-- 注意: このカラムを参照/書き込みするコード（roster_model の各SELECT、
--       import_wahapedia.php、backfill_regiment_options.php）を撤去・改修した後に実行すること。
ALTER TABLE m_units
    DROP COLUMN regiment_options;
