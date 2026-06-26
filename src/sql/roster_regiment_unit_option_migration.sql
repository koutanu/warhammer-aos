-- 随伴ユニットが消費する「連隊オプション枠」を保存できるようにする。
-- HERO の編成枠 max_limit を割当ベースで強制するため、各随伴ユニットがどの枠に
-- 入っているか(assigned_option_id)を保持する。
-- NULL = 枠未指定(単一枠なら読込時に自動導出 / 無制限のみなら NULL のまま)。
ALTER TABLE t_roster_regiment_units
    ADD COLUMN assigned_option_id INT NULL AFTER unit_id;
