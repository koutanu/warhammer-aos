-- GHB シーズン追加能力（陣営ごと・キーワード適格・ロスター全体で1つ）
-- t_rosters 列の ADD は scripts/migrate_season_enhancements.php が冪等に実行

CREATE TABLE IF NOT EXISTS `m_season_enhancements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `faction_id` int(11) NOT NULL,
  `season` varchar(16) NOT NULL DEFAULT '2026-27',
  `name` varchar(255) NOT NULL,
  `effect` text NOT NULL,
  `points` int(11) NOT NULL DEFAULT 0,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_hidden` tinyint(1) NOT NULL DEFAULT 0,
  `activation` enum('active','passive','reaction') NOT NULL DEFAULT 'active',
  `usage_scope` enum('unlimited','once_per_turn','once_per_phase','once_per_battle') NOT NULL DEFAULT 'unlimited',
  `usage_per` enum('unit','army') NOT NULL DEFAULT 'unit',
  `trigger_phase` set('deployment','hero','movement','shooting','charge','combat','end','any') DEFAULT NULL,
  `trigger_turn` enum('your','opponent','any','battle') NOT NULL DEFAULT 'your',
  `trigger_condition_ja` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_season_enh_faction_season` (`faction_id`, `season`),
  KEY `idx_season_enh_sort` (`faction_id`, `season`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `m_season_enhancement_keywords` (
  `enhancement_id` int(11) NOT NULL,
  `keyword_id` int(11) NOT NULL,
  `requirement` enum('require','exclude') NOT NULL DEFAULT 'require',
  PRIMARY KEY (`enhancement_id`, `keyword_id`),
  KEY `idx_season_enh_kw_keyword` (`keyword_id`),
  CONSTRAINT `fk_season_enh_kw_enh`
    FOREIGN KEY (`enhancement_id`) REFERENCES `m_season_enhancements` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_season_enh_kw_keyword`
    FOREIGN KEY (`keyword_id`) REFERENCES `m_keywords_master` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `m_faction_season_enhancement_labels` (
  `faction_id` int(11) NOT NULL,
  `season` varchar(16) NOT NULL,
  `label_ja` varchar(255) NOT NULL,
  `label_en` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`faction_id`, `season`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
