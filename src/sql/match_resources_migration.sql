-- マッチプレイ中の CP / 憤激レベル / 憤激ダイス
ALTER TABLE `t_matches`
  ADD COLUMN `player_a_cp` INT NOT NULL DEFAULT 0 COMMENT 'Player A コマンドポイント',
  ADD COLUMN `player_b_cp` INT NOT NULL DEFAULT 0 COMMENT 'Player B コマンドポイント',
  ADD COLUMN `player_a_rage_level` INT NOT NULL DEFAULT 0 COMMENT 'Player A 憤激レベル',
  ADD COLUMN `player_b_rage_level` INT NOT NULL DEFAULT 0 COMMENT 'Player B 憤激レベル',
  ADD COLUMN `player_a_rage_dice` INT NOT NULL DEFAULT 0 COMMENT 'Player A 憤激ダイス',
  ADD COLUMN `player_b_rage_dice` INT NOT NULL DEFAULT 0 COMMENT 'Player B 憤激ダイス';
