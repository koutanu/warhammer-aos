-- キーワードマスタとユニット↔キーワード中間テーブル。
-- ※ 旧 m_core_abilities のリネーム／削除は migrate_keywords_master.php が実行する。
-- ※ 以下の CREATE は冪等に実行可能。

CREATE TABLE IF NOT EXISTS m_keywords_master (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  name         VARCHAR(255) NOT NULL,
  keyword_type ENUM('unit','faction') NOT NULL DEFAULT 'unit',
  effect       TEXT NULL,
  sort_order   INT NOT NULL DEFAULT 0,
  accepts_param TINYINT(1) NOT NULL DEFAULT 0,
  faction_id   INT NULL DEFAULT NULL,
  UNIQUE KEY uq_m_keywords_master_name_type (name, keyword_type),
  INDEX idx_keywords_master_faction (faction_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS m_unit_keywords (
  unit_id    INT NOT NULL,
  keyword_id INT NOT NULL,
  PRIMARY KEY (unit_id, keyword_id),
  INDEX idx_m_unit_keywords_keyword_id (keyword_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
