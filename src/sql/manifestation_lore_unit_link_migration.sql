-- 召喚呪文(顕現の伝承)に、対応する顕現ユニット本体(m_units.id)への参照を持たせる。
-- NULL = 未紐づけ。図鑑では unit_id があればウォースクロール詳細を表示する。
ALTER TABLE m_manifestation_lores
    ADD COLUMN unit_id INT NULL DEFAULT NULL AFTER faction_id;
