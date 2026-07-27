-- m_common_abilities.trigger_phase に round_start を許可する。
-- ability_usage_normalization_migration.sql 適用済みの既存DB向け。
-- （新規セットアップでは正規化マイグレーション側に既に含まれる。）
--
-- SET 定義の拡張は MODIFY で行う。既存の許可値・データはそのまま維持される。

ALTER TABLE m_common_abilities
    MODIFY COLUMN trigger_phase
        SET('deployment','round_start','hero','movement','shooting','charge','combat','end','any')
        NULL DEFAULT NULL;
