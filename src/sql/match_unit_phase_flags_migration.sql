-- マッチプレイ中のフェイズ行動済み（移動／射撃／近接）
CREATE TABLE IF NOT EXISTS `t_match_unit_phase_flags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `match_id` int(11) NOT NULL,
  `player_slot` tinyint(4) NOT NULL COMMENT '1=player_a, 2=player_b',
  `unit_key` varchar(64) NOT NULL COMMENT 'instanceKey',
  `flag` varchar(16) NOT NULL COMMENT 'moved|shot|fought',
  `used_in_turn` int(11) NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_match_phase_flag` (`match_id`,`player_slot`,`unit_key`,`flag`),
  KEY `idx_match_id` (`match_id`),
  CONSTRAINT `fk_match_unit_phase_flags_match`
    FOREIGN KEY (`match_id`) REFERENCES `t_matches` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
