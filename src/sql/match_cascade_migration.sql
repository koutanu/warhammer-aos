-- t_matches 削除時に子テーブルを自動削除するための ON DELETE CASCADE 外部キー追加
-- t_match_round_scores / t_match_ability_usage の match_id を t_matches.id に紐付ける

-- 孤立行のクリーンアップ（外部キー追加が失敗しないよう先に実行）
DELETE c FROM t_match_round_scores c
  LEFT JOIN t_matches m ON c.match_id = m.id
  WHERE m.id IS NULL;

DELETE c FROM t_match_ability_usage c
  LEFT JOIN t_matches m ON c.match_id = m.id
  WHERE m.id IS NULL;

-- 外部キー制約の追加（ON DELETE CASCADE）
ALTER TABLE t_match_round_scores
  ADD CONSTRAINT fk_round_scores_match
  FOREIGN KEY (match_id) REFERENCES t_matches (id) ON DELETE CASCADE;

ALTER TABLE t_match_ability_usage
  ADD CONSTRAINT fk_ability_usage_match
  FOREIGN KEY (match_id) REFERENCES t_matches (id) ON DELETE CASCADE;
