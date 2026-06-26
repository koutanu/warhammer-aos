-- 連隊編成ルール表示用: HERO の Warscroll に記載された連隊編成ルール
-- (例: "0-1 Gryph-hounds / Any Warrior Chamber") を整形済みテキストで保持する。
-- Wahapedia の regiment_options 原文を整形してこの列に格納する。
ALTER TABLE m_units
    ADD COLUMN regiment_options TEXT NULL AFTER keywords;
