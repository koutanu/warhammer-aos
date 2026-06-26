-- ロスター永続化・連隊構造マイグレーション
-- 実行: php scripts/migrate_roster.php

-- 連隊テーブル
CREATE TABLE IF NOT EXISTS t_roster_regiments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  roster_id INT NOT NULL,
  sort_order TINYINT NOT NULL,
  hero_unit_id INT NOT NULL,
  is_general TINYINT(1) NOT NULL DEFAULT 0,
  enhancement_trait VARCHAR(255) NULL,
  enhancement_artefact VARCHAR(255) NULL,
  INDEX idx_roster_regiments_roster (roster_id),
  CONSTRAINT fk_roster_regiments_roster
    FOREIGN KEY (roster_id) REFERENCES t_rosters(id) ON DELETE CASCADE,
  CONSTRAINT fk_roster_regiments_hero
    FOREIGN KEY (hero_unit_id) REFERENCES m_units(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS t_roster_regiment_units (
  id INT AUTO_INCREMENT PRIMARY KEY,
  regiment_id INT NOT NULL,
  unit_id INT NOT NULL,
  sort_order TINYINT NOT NULL,
  is_reinforced TINYINT(1) NOT NULL DEFAULT 0,
  INDEX idx_roster_regiment_units_regiment (regiment_id),
  CONSTRAINT fk_roster_regiment_units_regiment
    FOREIGN KEY (regiment_id) REFERENCES t_roster_regiments(id) ON DELETE CASCADE,
  CONSTRAINT fk_roster_regiment_units_unit
    FOREIGN KEY (unit_id) REFERENCES m_units(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- t_rosters Enhancement 列（実際の ADD は scripts/migrate_roster.php が冪等に実行）
-- heroic_trait_id INT NULL
-- trait_target_unit_id INT NULL
-- artefact_target_unit_id INT NULL
-- terrain_id INT NULL  -- 選択された陣営地形(m_units.id)。NULL=未選択
