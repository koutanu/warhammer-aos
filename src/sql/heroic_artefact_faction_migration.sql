-- 英雄特性 (m_heroic_traits) / 神器 (m_artefacts_of_power) を
-- カテゴリ名のハードコード判定から faction_id ベースへ移行する。
-- 既存データはストームキャスト・エターナル (faction_id = 1) のみのため
-- 全行を faction_id = 1 でバックフィルする。
-- category 列は表示上のグルーピング用にそのまま残す。

ALTER TABLE m_heroic_traits
    ADD COLUMN faction_id INT(11) NULL AFTER id,
    ADD INDEX idx_ht_faction (faction_id);

ALTER TABLE m_artefacts_of_power
    ADD COLUMN faction_id INT(11) NULL AFTER id,
    ADD INDEX idx_ap_faction (faction_id);

UPDATE m_heroic_traits      SET faction_id = 1 WHERE faction_id IS NULL;
UPDATE m_artefacts_of_power SET faction_id = 1 WHERE faction_id IS NULL;
