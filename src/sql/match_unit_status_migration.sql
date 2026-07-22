-- マッチプレイ中のユニット撃破状態
CREATE TABLE IF NOT EXISTS `t_match_unit_status` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `match_id` int(11) NOT NULL,
  `player_slot` tinyint(4) NOT NULL COMMENT '1=player_a, 2=player_b',
  `unit_key` varchar(64) NOT NULL COMMENT 'instanceKey (hero:/unit:/manifest:/terrain:)',
  `is_destroyed` tinyint(1) NOT NULL DEFAULT 1,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_match_player_unit` (`match_id`,`player_slot`,`unit_key`),
  KEY `idx_match_id` (`match_id`),
  CONSTRAINT `fk_match_unit_status_match`
    FOREIGN KEY (`match_id`) REFERENCES `t_matches` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
