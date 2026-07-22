-- バトルプラン固有アビリティ用マスタ
-- 試合で選んだ m_battleplans に紐づくシナリオ／シナリオ開始ルールなどを保持する。
-- シードは行わず、登録は手動（SQL）で行う。
--
-- ※ scripts/migrate_battleplan_abilities.php で適用する。

CREATE TABLE IF NOT EXISTS `m_battleplan_abilities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `battleplan_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `effect` text NULL,
  `command_cost` tinyint(4) DEFAULT NULL COMMENT 'CP費用。NULL=コマンドではない(CP不要)',
  `activation` enum('active','passive','reaction') NOT NULL DEFAULT 'active',
  `usage_scope` enum('unlimited','once_per_turn','once_per_phase','once_per_battle') NOT NULL DEFAULT 'unlimited',
  `usage_per` enum('unit','army') NOT NULL DEFAULT 'army',
  `trigger_phase` set('deployment','round_start','hero','movement','shooting','charge','combat','end','any') DEFAULT NULL,
  `trigger_turn` enum('your','opponent','any','battle') NOT NULL DEFAULT 'your',
  `icon_type` varchar(20) DEFAULT NULL COMMENT 'Offensive/Defensive/Movement/Shooting/Damage/Control/Rallying/Special',
  `trigger_condition_ja` text DEFAULT NULL,
  `flavor_text` text DEFAULT NULL,
  `is_hidden` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_battleplan_sort` (`battleplan_id`, `sort_order`),
  CONSTRAINT `fk_battleplan_abilities_battleplan`
    FOREIGN KEY (`battleplan_id`) REFERENCES `m_battleplans` (`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
