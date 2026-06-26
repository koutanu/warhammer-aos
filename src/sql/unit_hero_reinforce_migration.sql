-- ユニットの「HERO 判定」と「増強(リインフォース)可否」をマスタ側で管理するためのフラグ
-- is_hero      : 1 = HERO ユニット / 0 = それ以外
-- can_reinforce: 1 = 増強(ポイント2倍)できる / 0 = 増強不可
--
-- ※ is_hero カラムは別途追加済みの想定。未追加の場合は下記コメントを外して実行すること。
-- ALTER TABLE m_units
--     ADD COLUMN is_hero TINYINT(1) NOT NULL DEFAULT 0 AFTER is_hidden;

ALTER TABLE m_units
    ADD COLUMN can_reinforce TINYINT(1) NOT NULL DEFAULT 0 AFTER is_hero;

-- 既存データのバックフィル
-- 1) これまで unit_keywords に "HERO" を含むユニットを is_hero = 1 にそろえる
UPDATE m_units
SET is_hero = 1
WHERE unit_keywords LIKE '%HERO%';

-- 2) HERO 以外で部隊サイズが 2 以上のユニットは、ひとまず増強可としておく
--    （実際の増強可否はユニット編集画面で個別に調整してください）
UPDATE m_units
SET can_reinforce = 1
WHERE is_hero = 0 AND COALESCE(unit_size, 1) > 1;
