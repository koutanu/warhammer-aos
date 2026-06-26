-- Regiment マスタのシード（ファクション横断の汎用連隊枠）
-- 既存DBの option_name と大文字小文字を一致させる

INSERT IGNORE INTO m_regiment_options (option_name) VALUES
  ('General Regiment'),
  ('STORMCAST EXEMPLAR'),
  ('WARRIOR CHAMBER');

-- 全 HERO ユニットに General Regiment 枠を付与
INSERT IGNORE INTO t_hero_regiment_options (hero_unit_id, option_id, max_limit)
SELECT u.id, ro.id, 0
FROM m_units u
JOIN m_regiment_options ro ON ro.option_name = 'General Regiment'
WHERE u.unit_keywords LIKE '%HERO%';

-- 全非HERO ユニットを General Regiment に適格登録
INSERT IGNORE INTO t_unit_regiment_eligibility (unit_id, option_id)
SELECT u.id, ro.id
FROM m_units u
JOIN m_regiment_options ro ON ro.option_name = 'General Regiment'
WHERE u.unit_keywords NOT LIKE '%HERO%' OR u.unit_keywords IS NULL;

-- Stormcast 固有枠（ファクション名で絞り込み）
INSERT IGNORE INTO t_hero_regiment_options (hero_unit_id, option_id, max_limit)
SELECT u.id, ro.id, 1
FROM m_units u
JOIN m_factions f ON f.id = u.faction_id
JOIN m_regiment_options ro ON ro.option_name = 'STORMCAST EXEMPLAR'
WHERE u.unit_keywords LIKE '%HERO%'
  AND (f.name LIKE '%Stormcast%' OR f.name LIKE '%ストームキャスト%' OR f.name_en LIKE '%Stormcast%');

INSERT IGNORE INTO t_unit_regiment_eligibility (unit_id, option_id)
SELECT u.id, ro.id
FROM m_units u
JOIN m_factions f ON f.id = u.faction_id
JOIN m_regiment_options ro ON ro.option_name = 'STORMCAST EXEMPLAR'
WHERE (u.unit_keywords NOT LIKE '%HERO%' OR u.unit_keywords IS NULL)
  AND (f.name LIKE '%Stormcast%' OR f.name LIKE '%ストームキャスト%' OR f.name_en LIKE '%Stormcast%');

INSERT IGNORE INTO t_hero_regiment_options (hero_unit_id, option_id, max_limit)
SELECT u.id, ro.id, 1
FROM m_units u
JOIN m_factions f ON f.id = u.faction_id
JOIN m_regiment_options ro ON ro.option_name = 'WARRIOR CHAMBER'
WHERE u.unit_keywords LIKE '%HERO%'
  AND (f.name LIKE '%Stormcast%' OR f.name LIKE '%ストームキャスト%' OR f.name_en LIKE '%Stormcast%');

INSERT IGNORE INTO t_unit_regiment_eligibility (unit_id, option_id)
SELECT u.id, ro.id
FROM m_units u
JOIN m_factions f ON f.id = u.faction_id
JOIN m_regiment_options ro ON ro.option_name = 'WARRIOR CHAMBER'
WHERE (u.unit_keywords NOT LIKE '%HERO%' OR u.unit_keywords IS NULL)
  AND (f.name LIKE '%Stormcast%' OR f.name LIKE '%ストームキャスト%' OR f.name_en LIKE '%Stormcast%');

-- 旧シード名から正規名への eligibility 再マッピング
INSERT IGNORE INTO t_unit_regiment_eligibility (unit_id, option_id)
SELECT ue.unit_id, ro_canon.id
FROM t_unit_regiment_eligibility ue
JOIN m_regiment_options ro_dup ON ro_dup.id = ue.option_id AND ro_dup.option_name = 'Stormcast Exemplar'
JOIN m_regiment_options ro_canon ON ro_canon.option_name = 'STORMCAST EXEMPLAR';

INSERT IGNORE INTO t_unit_regiment_eligibility (unit_id, option_id)
SELECT ue.unit_id, ro_canon.id
FROM t_unit_regiment_eligibility ue
JOIN m_regiment_options ro_dup ON ro_dup.id = ue.option_id AND ro_dup.option_name = 'Stormcast Warrior Chamber'
JOIN m_regiment_options ro_canon ON ro_canon.option_name = 'WARRIOR CHAMBER';

INSERT IGNORE INTO t_hero_regiment_options (hero_unit_id, option_id, max_limit)
SELECT ho.hero_unit_id, ro_canon.id, ho.max_limit
FROM t_hero_regiment_options ho
JOIN m_regiment_options ro_dup ON ro_dup.id = ho.option_id AND ro_dup.option_name = 'Stormcast Exemplar'
JOIN m_regiment_options ro_canon ON ro_canon.option_name = 'STORMCAST EXEMPLAR';

INSERT IGNORE INTO t_hero_regiment_options (hero_unit_id, option_id, max_limit)
SELECT ho.hero_unit_id, ro_canon.id, ho.max_limit
FROM t_hero_regiment_options ho
JOIN m_regiment_options ro_dup ON ro_dup.id = ho.option_id AND ro_dup.option_name = 'Stormcast Warrior Chamber'
JOIN m_regiment_options ro_canon ON ro_canon.option_name = 'WARRIOR CHAMBER';
