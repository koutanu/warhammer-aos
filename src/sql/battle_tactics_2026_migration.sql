-- GHB 2026-27 Battle Tactics: カード段階サブテーブル + ロスター選択
-- m_battle_tactics.season の ADD は scripts/migrate_battle_tactics_2026.php が冪等に実行

CREATE TABLE IF NOT EXISTS `m_battle_tactic_stages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `battle_tactic_id` int(11) NOT NULL,
  `stage` enum('affray','strike','domination') NOT NULL,
  `stage_order` tinyint(4) NOT NULL COMMENT '1=Affray, 2=Strike, 3=Domination',
  `name` varchar(255) NOT NULL,
  `effect` text NOT NULL,
  `victory_points` tinyint(4) NOT NULL DEFAULT 2,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_tactic_stage` (`battle_tactic_id`, `stage`),
  UNIQUE KEY `uk_tactic_stage_order` (`battle_tactic_id`, `stage_order`),
  KEY `idx_battle_tactic_id` (`battle_tactic_id`),
  CONSTRAINT `fk_tactic_stages_card`
    FOREIGN KEY (`battle_tactic_id`) REFERENCES `m_battle_tactics` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `t_roster_battle_tactics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `roster_id` int(11) NOT NULL,
  `battle_tactic_id` int(11) NOT NULL,
  `sort_order` tinyint(4) NOT NULL DEFAULT 0 COMMENT '0..1 (最大2枚)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_roster_tactic` (`roster_id`, `battle_tactic_id`),
  UNIQUE KEY `uk_roster_sort` (`roster_id`, `sort_order`),
  KEY `idx_roster_id` (`roster_id`),
  KEY `idx_battle_tactic_id` (`battle_tactic_id`),
  CONSTRAINT `fk_roster_battle_tactics_roster`
    FOREIGN KEY (`roster_id`) REFERENCES `t_rosters` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_roster_battle_tactics_card`
    FOREIGN KEY (`battle_tactic_id`) REFERENCES `m_battle_tactics` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
