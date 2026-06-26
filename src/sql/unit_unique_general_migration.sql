-- ユニットの「総大将(General)」「固有(Unique)」をマスタ側のフラグで管理する
-- is_general : 1 = 総大将。ロスター内にいる場合、ジェネラルは総大将ユニットでなければならない
-- is_unique  : 1 = 固有。同一ユニットはロスター全体で1体まで。神器・英雄特性を付与できない
--
-- ※ is_hero / can_reinforce と同じ流儀（unit_hero_reinforce_migration.sql 参照）

ALTER TABLE m_units
    ADD COLUMN is_general TINYINT(1) NOT NULL DEFAULT 0 AFTER can_reinforce,
    ADD COLUMN is_unique  TINYINT(1) NOT NULL DEFAULT 0 AFTER is_general;

-- 既存データのバックフィル（unit_keywords の日本語表記から移行）
UPDATE m_units SET is_general = 1 WHERE unit_keywords LIKE '%総大将%';
UPDATE m_units SET is_unique  = 1 WHERE unit_keywords LIKE '%固有%';
