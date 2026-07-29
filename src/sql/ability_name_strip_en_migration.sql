-- m_ability_master.name から末尾の「 (English)」を除去する。
-- 英語原文は name_en に残す。translate_abilities.php は以降、日本語のみを書き込む。
--
-- 実行例:
--   mysql -u USER -p DB_NAME < src/sql/ability_name_strip_en_migration.sql

UPDATE m_ability_master
SET name = TRIM(REPLACE(name, CONCAT(' (', name_en, ')'), ''))
WHERE name_en IS NOT NULL
  AND name LIKE CONCAT('% (', name_en, ')');
