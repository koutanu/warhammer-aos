-- アビリティに必要なコマンドポイント(CP)を保持する。
-- 陣営地形(ファクションテレイン)を含むアビリティで、発動に必要な CP を登録できるようにする。
-- NULL = CP 不要（バッジ非表示）。1 以上のとき対戦アビリティデッキ／図鑑詳細で "CP n" バッジを表示する。

ALTER TABLE m_ability_master
    ADD COLUMN command_point TINYINT NULL DEFAULT NULL AFTER name_en;
