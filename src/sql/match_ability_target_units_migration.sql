-- アビリティ対象ユニット履歴（バトル中保持）と usage への紐づけ

ALTER TABLE `t_match_ability_usage`
  ADD COLUMN `target_unit_key` varchar(64) DEFAULT NULL AFTER `used_at_phase`;

CREATE TABLE IF NOT EXISTS `t_match_ability_target_units` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `match_id` int(11) NOT NULL,
  `player_slot` tinyint(4) NOT NULL COMMENT '1=player_a, 2=player_b',
  `ability_key` varchar(128) NOT NULL,
  `unit_key` varchar(64) NOT NULL COMMENT 'instanceKey (hero:/unit:)',
  `used_in_turn` int(11) NOT NULL DEFAULT 1,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_match_player_ability_unit` (`match_id`,`player_slot`,`ability_key`,`unit_key`),
  KEY `idx_match_id` (`match_id`),
  CONSTRAINT `fk_ability_target_units_match`
    FOREIGN KEY (`match_id`) REFERENCES `t_matches` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
