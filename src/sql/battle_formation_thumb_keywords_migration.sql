-- 戦闘陣形の詳細サムネ用キーワード（カンマ区切り）
-- m_keywords_master.name と一致させる。例: ルイネーションチェンバー
-- フェイズ・アビリティ詳細で、ロスター内の該当ユニットをサムネ表示する。

ALTER TABLE m_battle_formations
    ADD COLUMN thumb_keywords TEXT NULL DEFAULT NULL AFTER flavor_text;
