-- キーワードのパラメータ対応（魔術師(1), 加護(5+) など）
-- ※ scripts/migrate_keyword_params.php が未適用時に自動実行し、既存データも移行する。

ALTER TABLE m_keywords_master
    ADD COLUMN accepts_param TINYINT(1) NOT NULL DEFAULT 0 AFTER sort_order;

ALTER TABLE m_unit_keywords
    ADD COLUMN param_value VARCHAR(20) NULL DEFAULT NULL AFTER keyword_id;
