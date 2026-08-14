-- ユニット図鑑用ストーリー（Fandom Wiki 英語本文）
-- 実行: php scripts/migrate_unit_story.php（冪等）推奨
ALTER TABLE m_units ADD COLUMN story_text TEXT NULL AFTER flavor_text;
ALTER TABLE m_units ADD COLUMN story_source_url VARCHAR(512) NULL AFTER story_text;
