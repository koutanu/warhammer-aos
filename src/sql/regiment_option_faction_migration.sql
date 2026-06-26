-- 連隊オプション(m_regiment_options)をファクション単位で管理できるようにする。
-- 既存はグローバル(option_name のみ UNIQUE)だったが、ファクションごとに
-- 同名オプションを許容できるよう faction_id を追加し UNIQUE 制約を張り直す。
ALTER TABLE m_regiment_options
    ADD COLUMN faction_id INT NULL AFTER id;

-- 既存オプションのバックフィル: 参照しているユニット(HERO 枠 / 適格性)の
-- ファクションから faction_id を推定する。
UPDATE m_regiment_options ro
JOIN (
    SELECT x.option_id, MIN(x.faction_id) AS faction_id
    FROM (
        SELECT ho.option_id, u.faction_id
        FROM t_hero_regiment_options ho
        JOIN m_units u ON u.id = ho.hero_unit_id
        UNION ALL
        SELECT e.option_id, u.faction_id
        FROM t_unit_regiment_eligibility e
        JOIN m_units u ON u.id = e.unit_id
    ) x
    GROUP BY x.option_id
) src ON src.option_id = ro.id
SET ro.faction_id = src.faction_id
WHERE ro.faction_id IS NULL;

-- 参照が無く推定できなかった既存オプションは Stormcast Eternals(faction_id=1)へ寄せる。
UPDATE m_regiment_options SET faction_id = 1 WHERE faction_id IS NULL;

-- option_name 単独の UNIQUE を破棄し、(faction_id, option_name) の複合 UNIQUE に変更。
ALTER TABLE m_regiment_options
    DROP INDEX unique_option,
    ADD UNIQUE KEY uq_faction_option (faction_id, option_name);

-- faction との整合性を保つための外部キー(任意)。
ALTER TABLE m_regiment_options
    ADD CONSTRAINT fk_regiment_options_faction
        FOREIGN KEY (faction_id) REFERENCES m_factions (id) ON DELETE CASCADE;
