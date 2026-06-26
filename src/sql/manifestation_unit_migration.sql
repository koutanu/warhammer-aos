-- 顕現(マニフェステーション/エンドレススペル)用フラグ
-- is_manifestation = 1 のユニットはウォースクロール扱いの顕現体。ロスター編成の選択肢には出さず
-- (is_hidden = 1 と併用)、図鑑/対戦アビリティデッキでは顕現として扱う。
ALTER TABLE m_units
    ADD COLUMN is_manifestation TINYINT(1) NOT NULL DEFAULT 0 AFTER is_terrain;
