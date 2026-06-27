-- アビリティ使用管理スキーマの正規化。
--
-- ターン/フェイズ/使用回数(once-per)を、テーブル横断で同じ「直交した軸」で持たせる。
-- 過負荷だった ability_type(カテゴリ+頻度+α混在) と trigger_timing を廃止し、意味ごとに分離する。
--
-- 共通カラム(対象9テーブルに統一):
--   activation   ENUM('active','passive','reaction')                                   発動様式
--   usage_scope  ENUM('unlimited','once_per_turn','once_per_phase','once_per_battle')   使用回数スコープ
--   usage_per    ENUM('unit','army')                                                    once-per の対象軸((Army)の正規化)
--   trigger_phase SET('deployment','hero','movement','shooting','charge','combat','end','any')  発動フェイズ(複数可)
--   trigger_turn ENUM('your','opponent','any','battle')                                 発動ターン
--   icon_type    VARCHAR(20)   表示カテゴリ(Offensive/Defensive/.../Special)
--   trigger_condition_ja TEXT  個別の日本語表示オーバーライド(フロント表示のみ優先)
--
-- 日本語表示は基本システムのラベルマップ(phases.js)で生成する(英語トークンを保存)。
-- trigger_condition_ja に値があるときだけ、フロントの表示でその値を優先する。
--
-- 既存値との整合は取らない前提(データは登録し直す)。型が変わる trigger_phase / trigger_turn は
-- 厳格モードでの変換エラーを避けるため DROP してから ADD で作り直す。
--
-- ============================================================================
-- STEP 1: 新カラム追加 / 型の作り直し(本マイグレーションで適用)
-- ============================================================================

-- m_ability_master ----------------------------------------------------------
ALTER TABLE m_ability_master
    ADD COLUMN activation  ENUM('active','passive','reaction') NOT NULL DEFAULT 'active' AFTER ability_type,
    ADD COLUMN usage_scope ENUM('unlimited','once_per_turn','once_per_phase','once_per_battle') NOT NULL DEFAULT 'unlimited' AFTER activation,
    ADD COLUMN usage_per   ENUM('unit','army') NOT NULL DEFAULT 'unit' AFTER usage_scope;
ALTER TABLE m_ability_master DROP COLUMN trigger_phase;
ALTER TABLE m_ability_master DROP COLUMN trigger_turn;
ALTER TABLE m_ability_master
    ADD COLUMN trigger_phase SET('deployment','hero','movement','shooting','charge','combat','end','any') NULL DEFAULT NULL AFTER usage_per,
    ADD COLUMN trigger_turn  ENUM('your','opponent','any','battle') NOT NULL DEFAULT 'your' AFTER trigger_phase;

-- m_common_abilities --------------------------------------------------------
ALTER TABLE m_common_abilities
    ADD COLUMN activation  ENUM('active','passive','reaction') NOT NULL DEFAULT 'active' AFTER ability_type,
    ADD COLUMN usage_scope ENUM('unlimited','once_per_turn','once_per_phase','once_per_battle') NOT NULL DEFAULT 'unlimited' AFTER activation,
    ADD COLUMN usage_per   ENUM('unit','army') NOT NULL DEFAULT 'unit' AFTER usage_scope;
ALTER TABLE m_common_abilities DROP COLUMN trigger_phase;
ALTER TABLE m_common_abilities DROP COLUMN trigger_turn;
ALTER TABLE m_common_abilities
    ADD COLUMN trigger_phase SET('deployment','hero','movement','shooting','charge','combat','end','any') NULL DEFAULT NULL AFTER usage_per,
    ADD COLUMN trigger_turn  ENUM('your','opponent','any','battle') NOT NULL DEFAULT 'your' AFTER trigger_phase;

-- m_heroic_traits -----------------------------------------------------------
ALTER TABLE m_heroic_traits
    ADD COLUMN activation  ENUM('active','passive','reaction') NOT NULL DEFAULT 'active' AFTER ability_type,
    ADD COLUMN usage_scope ENUM('unlimited','once_per_turn','once_per_phase','once_per_battle') NOT NULL DEFAULT 'unlimited' AFTER activation,
    ADD COLUMN usage_per   ENUM('unit','army') NOT NULL DEFAULT 'unit' AFTER usage_scope,
    ADD COLUMN trigger_turn ENUM('your','opponent','any','battle') NOT NULL DEFAULT 'your' AFTER trigger_phase,
    ADD COLUMN icon_type    VARCHAR(20) NULL DEFAULT NULL AFTER usage_per,
    ADD COLUMN trigger_condition_ja TEXT NULL DEFAULT NULL AFTER icon_type;
ALTER TABLE m_heroic_traits DROP COLUMN trigger_phase;
ALTER TABLE m_heroic_traits
    ADD COLUMN trigger_phase SET('deployment','hero','movement','shooting','charge','combat','end','any') NULL DEFAULT NULL AFTER usage_per;

-- m_artefacts_of_power (trigger_phase 自体が無いので ADD のみ) ---------------
ALTER TABLE m_artefacts_of_power
    ADD COLUMN activation  ENUM('active','passive','reaction') NOT NULL DEFAULT 'active' AFTER ability_type,
    ADD COLUMN usage_scope ENUM('unlimited','once_per_turn','once_per_phase','once_per_battle') NOT NULL DEFAULT 'unlimited' AFTER activation,
    ADD COLUMN usage_per   ENUM('unit','army') NOT NULL DEFAULT 'unit' AFTER usage_scope,
    ADD COLUMN trigger_phase SET('deployment','hero','movement','shooting','charge','combat','end','any') NULL DEFAULT NULL AFTER usage_per,
    ADD COLUMN trigger_turn  ENUM('your','opponent','any','battle') NOT NULL DEFAULT 'your' AFTER trigger_phase,
    ADD COLUMN icon_type     VARCHAR(20) NULL DEFAULT NULL AFTER trigger_turn,
    ADD COLUMN trigger_condition_ja TEXT NULL DEFAULT NULL AFTER icon_type;

-- m_battle_formations (ability_type 列は無い) -------------------------------
ALTER TABLE m_battle_formations
    ADD COLUMN activation  ENUM('active','passive','reaction') NOT NULL DEFAULT 'active' AFTER effect,
    ADD COLUMN usage_scope ENUM('unlimited','once_per_turn','once_per_phase','once_per_battle') NOT NULL DEFAULT 'unlimited' AFTER activation,
    ADD COLUMN usage_per   ENUM('unit','army') NOT NULL DEFAULT 'unit' AFTER usage_scope,
    ADD COLUMN icon_type   VARCHAR(20) NULL DEFAULT NULL AFTER usage_per;
ALTER TABLE m_battle_formations DROP COLUMN trigger_phase;
ALTER TABLE m_battle_formations DROP COLUMN trigger_turn;
ALTER TABLE m_battle_formations
    ADD COLUMN trigger_phase SET('deployment','hero','movement','shooting','charge','combat','end','any') NULL DEFAULT NULL AFTER usage_per,
    ADD COLUMN trigger_turn  ENUM('your','opponent','any','battle') NOT NULL DEFAULT 'your' AFTER trigger_phase;

-- m_battle_traits -----------------------------------------------------------
ALTER TABLE m_battle_traits
    ADD COLUMN activation  ENUM('active','passive','reaction') NOT NULL DEFAULT 'active' AFTER ability_type,
    ADD COLUMN usage_scope ENUM('unlimited','once_per_turn','once_per_phase','once_per_battle') NOT NULL DEFAULT 'unlimited' AFTER activation,
    ADD COLUMN usage_per   ENUM('unit','army') NOT NULL DEFAULT 'unit' AFTER usage_scope,
    ADD COLUMN icon_type   VARCHAR(20) NULL DEFAULT NULL AFTER usage_per;
ALTER TABLE m_battle_traits DROP COLUMN trigger_phase;
ALTER TABLE m_battle_traits DROP COLUMN trigger_turn;
ALTER TABLE m_battle_traits
    ADD COLUMN trigger_phase SET('deployment','hero','movement','shooting','charge','combat','end','any') NULL DEFAULT NULL AFTER usage_per,
    ADD COLUMN trigger_turn  ENUM('your','opponent','any','battle') NOT NULL DEFAULT 'your' AFTER trigger_phase;

-- m_spell_lores -------------------------------------------------------------
ALTER TABLE m_spell_lores
    ADD COLUMN activation  ENUM('active','passive','reaction') NOT NULL DEFAULT 'active' AFTER effect,
    ADD COLUMN usage_scope ENUM('unlimited','once_per_turn','once_per_phase','once_per_battle') NOT NULL DEFAULT 'unlimited' AFTER activation,
    ADD COLUMN usage_per   ENUM('unit','army') NOT NULL DEFAULT 'unit' AFTER usage_scope,
    ADD COLUMN trigger_turn ENUM('your','opponent','any','battle') NOT NULL DEFAULT 'your' AFTER usage_per,
    ADD COLUMN icon_type    VARCHAR(20) NULL DEFAULT NULL AFTER trigger_turn,
    ADD COLUMN trigger_condition_ja TEXT NULL DEFAULT NULL AFTER icon_type;
ALTER TABLE m_spell_lores DROP COLUMN trigger_phase;
ALTER TABLE m_spell_lores
    ADD COLUMN trigger_phase SET('deployment','hero','movement','shooting','charge','combat','end','any') NULL DEFAULT NULL AFTER usage_per;

-- m_prayer_lores ------------------------------------------------------------
ALTER TABLE m_prayer_lores
    ADD COLUMN activation  ENUM('active','passive','reaction') NOT NULL DEFAULT 'active' AFTER effect,
    ADD COLUMN usage_scope ENUM('unlimited','once_per_turn','once_per_phase','once_per_battle') NOT NULL DEFAULT 'unlimited' AFTER activation,
    ADD COLUMN usage_per   ENUM('unit','army') NOT NULL DEFAULT 'unit' AFTER usage_scope,
    ADD COLUMN trigger_turn ENUM('your','opponent','any','battle') NOT NULL DEFAULT 'your' AFTER usage_per,
    ADD COLUMN icon_type    VARCHAR(20) NULL DEFAULT NULL AFTER trigger_turn,
    ADD COLUMN trigger_condition_ja TEXT NULL DEFAULT NULL AFTER icon_type;
ALTER TABLE m_prayer_lores DROP COLUMN trigger_phase;
ALTER TABLE m_prayer_lores
    ADD COLUMN trigger_phase SET('deployment','hero','movement','shooting','charge','combat','end','any') NULL DEFAULT NULL AFTER usage_per;

-- m_manifestation_lores -----------------------------------------------------
ALTER TABLE m_manifestation_lores
    ADD COLUMN activation  ENUM('active','passive','reaction') NOT NULL DEFAULT 'active' AFTER effect,
    ADD COLUMN usage_scope ENUM('unlimited','once_per_turn','once_per_phase','once_per_battle') NOT NULL DEFAULT 'unlimited' AFTER activation,
    ADD COLUMN usage_per   ENUM('unit','army') NOT NULL DEFAULT 'unit' AFTER usage_scope,
    ADD COLUMN trigger_turn ENUM('your','opponent','any','battle') NOT NULL DEFAULT 'your' AFTER usage_per,
    ADD COLUMN icon_type    VARCHAR(20) NULL DEFAULT NULL AFTER trigger_turn,
    ADD COLUMN trigger_condition_ja TEXT NULL DEFAULT NULL AFTER icon_type;
ALTER TABLE m_manifestation_lores DROP COLUMN trigger_phase;
ALTER TABLE m_manifestation_lores
    ADD COLUMN trigger_phase SET('deployment','hero','movement','shooting','charge','combat','end','any') NULL DEFAULT NULL AFTER usage_per;

-- ============================================================================
-- STEP 2: 旧カラムの削除(データ登録し直し & 表示確認が済んでから手動で実行する)
--   システムロジックは既に新カラムのみを参照するため、これらは未使用になる。
-- ============================================================================
-- ALTER TABLE m_ability_master      DROP COLUMN ability_type;
-- ALTER TABLE m_common_abilities     DROP COLUMN ability_type;
-- ALTER TABLE m_heroic_traits        DROP COLUMN ability_type;
-- ALTER TABLE m_artefacts_of_power   DROP COLUMN ability_type, DROP COLUMN trigger_timing;
-- ALTER TABLE m_battle_traits        DROP COLUMN ability_type;
