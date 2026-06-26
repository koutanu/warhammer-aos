-- アビリティの発動条件を保持する。
-- Wahapedia の Warscrolls_abilities.csv にある condition 列
-- (例: "Once Per Turn (Army), Reaction: This unit was picked as the target of a non-CORE ability")
-- は自由記述のため ability_type マッピングでは表現できない。
-- 英語原文(trigger_condition_en)と手動翻訳(trigger_condition_ja)を別カラムで保持する。
--
-- ※ scripts/import_wahapedia.php が未適用時に自動で ALTER する。
--   trigger_condition_en は import を再実行すると CSV から backfill される。
--   trigger_condition_ja は手動入力で、import では一切上書きしない。

ALTER TABLE m_ability_master
    ADD COLUMN trigger_condition_en TEXT NULL DEFAULT NULL AFTER ability_type,
    ADD COLUMN trigger_condition_ja TEXT NULL DEFAULT NULL AFTER trigger_condition_en;
