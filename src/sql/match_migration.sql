-- AoS 4版 VPスコアボード用マイグレーション
-- 既存 t_matches を拡張し、ラウンド別スコア・マスタを追加

CREATE TABLE IF NOT EXISTS `m_battleplans` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `rounds` tinyint(4) NOT NULL DEFAULT 5,
  `max_vp_per_round` tinyint(4) NOT NULL DEFAULT 10,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `m_battle_tactics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `grand_alliance` varchar(50) DEFAULT NULL COMMENT 'NULL=汎用',
  `sort_order` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `t_match_round_scores` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `match_id` int(11) NOT NULL,
  `player_slot` tinyint(4) NOT NULL COMMENT '1=player_a, 2=player_b',
  `round_number` tinyint(4) NOT NULL,
  `obj_hold_one` tinyint(1) NOT NULL DEFAULT 0,
  `obj_hold_two_plus` tinyint(1) NOT NULL DEFAULT 0,
  `obj_hold_more` tinyint(1) NOT NULL DEFAULT 0,
  `battle_tactic_id` int(11) DEFAULT NULL,
  `battle_tactic_completed` tinyint(1) NOT NULL DEFAULT 0,
  `is_double_turn` tinyint(1) NOT NULL DEFAULT 0,
  `round_vp` tinyint(4) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_match_player_round` (`match_id`,`player_slot`,`round_number`),
  KEY `idx_match_id` (`match_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `t_match_ability_usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `match_id` int(11) NOT NULL,
  `player_slot` tinyint(4) NOT NULL COMMENT '1=player_a, 2=player_b',
  `ability_key` varchar(128) NOT NULL,
  `used_in_game_round` tinyint(4) NOT NULL DEFAULT 1,
  `used_in_turn` int(11) NOT NULL DEFAULT 1,
  `used_at_phase` varchar(32) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_match_player_ability` (`match_id`,`player_slot`,`ability_key`),
  KEY `idx_match_id` (`match_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
