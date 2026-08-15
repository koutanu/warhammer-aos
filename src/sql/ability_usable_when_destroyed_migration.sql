-- 撃破後も使用できるユニットアビリティをマスタ側で保持する。
-- 試合中の撃破状態(t_match_unit_status.is_destroyed)とは別軸。
--
--   mysql -u USER -p DB_NAME < src/sql/ability_usable_when_destroyed_migration.sql

ALTER TABLE m_ability_master
    ADD COLUMN usable_when_destroyed TINYINT(1) NOT NULL DEFAULT 0 AFTER usage_per;

UPDATE m_ability_master
SET usable_when_destroyed = 1
WHERE usable_when_destroyed = 0
  AND (
    effect LIKE '%このユニットが破壊されていても使用できる%'
    OR effect LIKE '%even if this unit has been destroyed%'
    OR effect LIKE '%even if this unit is destroyed%'
    OR IFNULL(trigger_condition_ja, '') LIKE '%このユニットが破壊されていても使用できる%'
  );
