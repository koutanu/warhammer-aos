-- アビリティに詠唱値/祈祷値を保持する。
-- 一部のユニット能力（HEROの呪文/祈祷など）は発動に詠唱値(casting)や祈祷値(prayer)を要する。
-- casting_value = 値そのもの（例: 7 / 7+）。NULL = 値なし（バッジ非表示）。
-- casting_type  = 種別。'spell' = 詠唱値 / 'prayer' = 祈祷値 / NULL = なし。
--   表示ラベルは 'spell' → 「詠唱」、'prayer' → 「祈祷」。

ALTER TABLE m_ability_master
    ADD COLUMN casting_value VARCHAR(10) NULL DEFAULT NULL AFTER command_point,
    ADD COLUMN casting_type  VARCHAR(10) NULL DEFAULT NULL AFTER casting_value;
