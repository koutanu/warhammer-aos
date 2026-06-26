-- 全ファクション共通のコアアビリティ／ユニバーサルコマンド用マスタ
-- (DEPLOY UNIT, BANISH MANIFESTATION, All-out Attack などコマンド)
-- 特定ユニット/ファクションに属さないため m_ability_master とは別管理し、
-- command_cost でコマンドポイント費用を保持する。
--
-- ※ scripts/migrate_common_abilities.php が未適用時に自動実行し、初期データもシードする。

CREATE TABLE IF NOT EXISTS m_common_abilities (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  name          VARCHAR(255) NOT NULL,
  command_cost  TINYINT NULL DEFAULT NULL COMMENT 'CP費用。NULL=コマンドではない(CP不要)',
  trigger_phase VARCHAR(100) NULL,
  trigger_turn  VARCHAR(100) NULL,
  ability_type  VARCHAR(100) NULL COMMENT 'Passive / Once Per Turn など',
  icon_type     VARCHAR(20)  NULL COMMENT 'Offensive/Defensive/Movement/Shooting/Damage/Control/Rallying/Special',
  effect        TEXT NULL,
  flavor_text   TEXT NULL,
  sort_order    INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
