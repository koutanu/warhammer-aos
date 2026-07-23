-- キーワードマスタにファクション限定フラグを追加。
-- faction_id が NULL = 全ファクション共通、値あり = そのファクション専用。
-- ※ scripts/migrate_keyword_faction.php が未適用時に自動実行する。

ALTER TABLE m_keywords_master
    ADD COLUMN faction_id INT NULL DEFAULT NULL AFTER accepts_param,
    ADD INDEX idx_keywords_master_faction (faction_id);
