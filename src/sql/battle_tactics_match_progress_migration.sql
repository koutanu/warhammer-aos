-- Match play: battle tactic stage progress + per-stage VP
-- victory_points ALTER は scripts/migrate_battle_tactics_2026.php が冪等に実行

CREATE TABLE IF NOT EXISTS `t_match_battle_tactic_progress` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `match_id` int(11) NOT NULL,
  `player_slot` tinyint(4) NOT NULL COMMENT '1 or 2',
  `battle_tactic_id` int(11) NOT NULL,
  `highest_completed_order` tinyint(4) NOT NULL DEFAULT 0 COMMENT '0=none, 1=Affray, 2=Strike, 3=Domination',
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_match_slot_tactic` (`match_id`, `player_slot`, `battle_tactic_id`),
  KEY `idx_match_id` (`match_id`),
  KEY `idx_battle_tactic_id` (`battle_tactic_id`),
  CONSTRAINT `fk_match_bt_progress_match`
    FOREIGN KEY (`match_id`) REFERENCES `t_matches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_match_bt_progress_tactic`
    FOREIGN KEY (`battle_tactic_id`) REFERENCES `m_battle_tactics` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
