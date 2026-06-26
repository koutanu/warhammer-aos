-- 先攻/後攻・ダブルターン記録用マイグレーション
-- 各ラウンドの先攻プレイヤー slot を保存する列を追加。
-- ダブルターン判定は既存の is_double_turn 列を流用する。

ALTER TABLE t_match_round_scores
  ADD COLUMN first_player_slot tinyint(4) DEFAULT NULL COMMENT 'そのラウンドの先攻 slot (1 or 2)' AFTER battle_tactic_completed;
