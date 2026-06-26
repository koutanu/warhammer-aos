-- アビリティのアイコン分類(icon_type)をマスタ側で保持する
-- Wahapedia の Warscrolls_abilities.csv にある ability_type 列
-- (Offensive / Defensive / Movement / Shooting / Damage / Control / Rallying / Special)
-- を、発動タイミング種別 (ability_type: Passive / Once Per ...) とは別軸で保存する。
--
-- ※ scripts/import_wahapedia.php が未適用時に自動で ALTER する。
--   既存行の icon_type は、import を再実行すると CSV から backfill される。

ALTER TABLE m_ability_master
    ADD COLUMN icon_type VARCHAR(20) NULL DEFAULT NULL AFTER ability_type;
