-- マッチプレイ中の代替ユニット置き換えフラグ（表示用）
ALTER TABLE `t_match_unit_status`
  ADD COLUMN `is_replaced` tinyint(1) NOT NULL DEFAULT 0 AFTER `is_summoned`;
