-- 陣営ストーリー（Fandom Wiki 英語本文）
-- 実行: php scripts/migrate_faction_story.php（冪等）推奨
ALTER TABLE m_factions ADD COLUMN story_text TEXT NULL AFTER is_hidden;
ALTER TABLE m_factions ADD COLUMN story_source_url VARCHAR(512) NULL AFTER story_text;
