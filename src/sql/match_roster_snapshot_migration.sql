-- 対戦作成時のロスター構成を試合結果画面で再現するためのスナップショット列
-- player_a/b_roster_snapshot: JSON（表示用に名前・効果などを埋め込んだ自己完結データ）

ALTER TABLE `t_matches`
  ADD COLUMN `player_a_roster_snapshot` MEDIUMTEXT DEFAULT NULL COMMENT 'Player A ロスター表示用スナップショット(JSON)',
  ADD COLUMN `player_b_roster_snapshot` MEDIUMTEXT DEFAULT NULL COMMENT 'Player B ロスター表示用スナップショット(JSON)';
