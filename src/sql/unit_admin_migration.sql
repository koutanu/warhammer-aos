-- ユニット図鑑(管理者編集)用: ロスター作成画面で非表示にするためのフラグ
-- is_hidden = 1 のユニットはロスター作成画面の選択肢に表示されない
ALTER TABLE m_units
    ADD COLUMN is_hidden TINYINT(1) NOT NULL DEFAULT 0 AFTER image;

-- ユニット↔能力リンクの重複防止（更新時の二重登録を防ぐ）
-- ※既存の重複行がある場合は事前に解消してから実行すること
CREATE UNIQUE INDEX uq_m_unit_abilities ON m_unit_abilities (unit_id, ability_id);
