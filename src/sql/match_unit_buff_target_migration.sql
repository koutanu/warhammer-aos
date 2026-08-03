-- 顕現召喚時の効果対象ユニットキー
ALTER TABLE `t_match_unit_status`
  ADD COLUMN `buff_target_unit_key` varchar(64) DEFAULT NULL
  COMMENT '顕現の効果対象 instanceKey (hero:/unit:)'
  AFTER `is_summoned`;
