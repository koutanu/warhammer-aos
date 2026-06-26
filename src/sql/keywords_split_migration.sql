-- m_units.keywords を「ユニットのルールキーワード」と「所属(ファクション)キーワード」に分割。
--   keywords  -> unit_keywords    : HERO/INFANTRY/WARD など、ユニット自身のルール系キーワード
--   keywords2 -> faction_keywords : ORDER/STORMCAST ETERNALS/各チェンバーなど、所属を表すキーワード
-- 連隊適格性のキーワード照合(STORMCAST EXEMPLAR 等)は faction_keywords を参照する。
ALTER TABLE m_units CHANGE COLUMN keywords  unit_keywords    VARCHAR(555) NULL DEFAULT NULL;
ALTER TABLE m_units CHANGE COLUMN keywords2 faction_keywords VARCHAR(555) NULL DEFAULT NULL;
