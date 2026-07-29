-- マッチプレイ中の顕現召喚状態フラグ
ALTER TABLE `t_match_unit_status`
  ADD COLUMN `is_summoned` tinyint(1) NOT NULL DEFAULT 0 AFTER `is_destroyed`;
