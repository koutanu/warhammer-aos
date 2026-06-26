-- Wahapedia インポート用: ユニットの再取り込みキー
-- (scripts/import_wahapedia.php が未適用時に自動実行)
ALTER TABLE m_units
    ADD COLUMN wahapedia_id VARCHAR(16) NULL DEFAULT NULL AFTER faction_id;

CREATE UNIQUE INDEX uq_m_units_wahapedia_id ON m_units (wahapedia_id);
