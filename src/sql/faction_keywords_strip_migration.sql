-- m_units.faction_keywords から大同盟（grand_alliance）と軍勢（name_en）トークンを除去する。
--
-- 背景:
--   従来 faction_keywords には「大同盟 + 軍勢 + 連隊枠キーワード」を格納していたが、
--   大同盟・軍勢は取得時に m_factions（grand_alliance / name_en）から動的結合するよう変更した。
--   そのため既存データの大同盟・軍勢トークンを削除し、連隊枠キーワード
--   （例: RUINATION CHAMBER / WARRIOR CHAMBER / ESHIN）のみを残す。
--
-- 処理:
--   1. カンマ直後の空白を除去して正規化（", WARRIOR CHAMBER" -> ",WARRIOR CHAMBER"）。
--   2. 前後をカンマで囲み、",<大同盟>," / ",<軍勢>," を "," へ置換してトークン除去。
--   3. 先頭・末尾のカンマを除去し、空文字なら NULL にする。
--
-- 冪等: 既に除去済みの行はトークンが存在しないため変化しない。再実行可。

UPDATE m_units u
JOIN m_factions f ON f.id = u.faction_id
SET u.faction_keywords = NULLIF(
    TRIM(BOTH ',' FROM
        REPLACE(
            REPLACE(
                CONCAT(',', REPLACE(UPPER(u.faction_keywords), ', ', ','), ','),
                CONCAT(',', UPPER(f.grand_alliance), ','),
                ','
            ),
            CONCAT(',', UPPER(f.name_en), ','),
            ','
        )
    ),
    ''
)
WHERE u.faction_keywords IS NOT NULL
  AND u.faction_keywords <> '';
