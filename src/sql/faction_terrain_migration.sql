-- 陣営地形(ファクションテレイン)用フラグ
-- is_terrain = 1 のユニットはウォースクロール扱いの地形。ロスター編成の選択肢には出さず
-- (is_hidden = 1 と併用)、図鑑/対戦アビリティデッキでは軍勢レベルの地形として扱う。
ALTER TABLE m_units
    ADD COLUMN is_terrain TINYINT(1) NOT NULL DEFAULT 0 AFTER is_unique;
