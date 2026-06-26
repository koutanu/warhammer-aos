<?php

class Match_Model extends Model
{
    /** VP配点（scoring.js と同期すること） */
    const VP_OBJ_HOLD_ONE = 2;
    const VP_OBJ_HOLD_TWO_PLUS = 2;
    const VP_OBJ_HOLD_MORE = 2;
    const VP_BATTLE_TACTIC = 4;
    const MAX_ROUNDS = 5;
    const MAX_VP_PER_ROUND = 10;

    const GAME_PHASES = ['hero', 'movement', 'shooting', 'charge', 'combat', 'end'];

    public static function normalizeGamePhase(?string $trigger): string
    {
        $raw = strtoupper(trim((string)$trigger));
        if ($raw === '') {
            return 'any';
        }
        if (strpos($raw, 'DEPLOY') !== false) {
            return 'deployment';
        }
        if (strpos($raw, 'HERO') !== false) {
            return 'hero';
        }
        if (strpos($raw, 'MOVEMENT') !== false || $raw === 'MOVE') {
            return 'movement';
        }
        if (strpos($raw, 'SHOOT') !== false) {
            return 'shooting';
        }
        if (strpos($raw, 'CHARGE') !== false) {
            return 'charge';
        }
        if (strpos($raw, 'COMBAT') !== false || $raw === 'FIGHT') {
            return 'combat';
        }
        if (strpos($raw, 'END') !== false) {
            return 'end';
        }
        if (strpos($raw, 'ANY') !== false) {
            return 'any';
        }
        return 'any';
    }

    public static function isBattleScopedTrigger(?string $triggerTurn): bool
    {
        return self::normalizeTriggerTurn($triggerTurn) === 'battle';
    }

    public static function normalizeTriggerTurn(?string $triggerTurn): string
    {
        $raw = strtolower(trim((string)$triggerTurn));
        if ($raw === '') {
            return 'your';
        }
        if (strpos($raw, 'opponent') !== false || strpos($raw, 'enemy') !== false) {
            return 'opponent';
        }
        if (strpos($raw, 'any') !== false || strpos($raw, 'both') !== false) {
            return 'any';
        }
        if (strpos($raw, 'battle') !== false || strpos($raw, 'game') !== false) {
            return 'battle';
        }
        if (strpos($raw, 'your') !== false) {
            return 'your';
        }
        return 'your';
    }

    public function __construct()
    {
        parent::__construct();
    }

    public function getBattleplans()
    {
        $sql = 'SELECT * FROM m_battleplans ORDER BY sort_order ASC, id ASC;';
        return $this->db->select($sql);
    }

    public function getBattleTactics($grand_alliance = null)
    {
        $sql = 'SELECT * FROM m_battle_tactics
                WHERE grand_alliance IS NULL OR grand_alliance = :alliance
                ORDER BY sort_order ASC, id ASC;';
        return $this->db->select($sql, ['alliance' => $grand_alliance ?? '']);
    }

    public function getAllBattleTactics()
    {
        $sql = 'SELECT * FROM m_battle_tactics ORDER BY sort_order ASC, id ASC;';
        return $this->db->select($sql);
    }

    public function getFactions()
    {
        $sql = 'SELECT * FROM m_factions ORDER BY grand_alliance ASC, id ASC;';
        return $this->db->select($sql);
    }

    public function getFactionsWithRoster()
    {
        $sql = 'SELECT DISTINCT f.*
                FROM m_factions f
                INNER JOIN t_rosters r ON r.faction_id = f.id
                ORDER BY f.grand_alliance ASC, f.id ASC;';
        return $this->db->select($sql);
    }

    public function getFactionMap()
    {
        $factions = $this->getFactions();
        $map = [];
        foreach ($factions as $f) {
            $map[(int)$f['id']] = $f;
        }
        return $map;
    }

    /**
     * ラウンドVP計算（www/js/match/scoring.js と同じロジック）
     */
    public static function calcRoundVp(array $score): int
    {
        $obj = 0;
        if (!empty($score['obj_hold_one'])) {
            $obj += self::VP_OBJ_HOLD_ONE;
        }
        if (!empty($score['obj_hold_two_plus'])) {
            $obj += self::VP_OBJ_HOLD_TWO_PLUS;
        }
        if (!empty($score['obj_hold_more'])) {
            $obj += self::VP_OBJ_HOLD_MORE;
        }

        if (!empty($score['is_double_turn'])) {
            return min($obj, self::MAX_VP_PER_ROUND);
        }

        $bt = !empty($score['battle_tactic_completed']) ? self::VP_BATTLE_TACTIC : 0;
        return min($obj + $bt, self::MAX_VP_PER_ROUND);
    }

    public function createMatch(array $data): array
    {
        $userId = Session::getUserInfo('user_id');
        $now = date('Y-m-d H:i:s');

        $sql = 'INSERT INTO t_matches (
            user_id, battleplan_id, status, battle_round,
            game_battle_round, active_player_slot, game_phase, game_turn_counter,
            player_a_user_id, player_a_name, player_a_faction_id, player_a_roster_id, player_a_vp,
            player_b_user_id, player_b_name, player_b_faction_id, player_b_roster_id, player_b_vp,
            played_at, updated_at
        ) VALUES (
            :user_id, :battleplan_id, :status, 1,
            1, 1, :game_phase, 1,
            :player_a_user_id, :player_a_name, :player_a_faction_id, :player_a_roster_id, 0,
            :player_b_user_id, :player_b_name, :player_b_faction_id, :player_b_roster_id, 0,
            :played_at, :updated_at
        );';

        $bind = [
            'user_id'             => $userId,
            'battleplan_id'       => $data['battleplan_id'],
            'status'              => 'active',
            'game_phase'          => 'deployment',
            'player_a_user_id'    => $userId,
            'player_a_name'       => $data['player_a_name'],
            'player_a_faction_id' => $data['player_a_faction_id'] ?: null,
            'player_a_roster_id'  => $data['player_a_roster_id'] ?? null,
            'player_b_user_id'    => null,
            'player_b_name'       => $data['player_b_name'],
            'player_b_faction_id' => $data['player_b_faction_id'] ?: null,
            'player_b_roster_id'  => $data['player_b_roster_id'] ?? null,
            'played_at'           => $now,
            'updated_at'          => $now,
        ];

        $result = $this->db->executesql($sql, $bind);
        if (!$result[0]) {
            return [false, $result[1]];
        }

        $matchId = (int)$this->db->lastInsertId();
        $this->initRoundScores($matchId);

        return [true, $matchId];
    }

    private function initRoundScores(int $matchId): void
    {
        for ($round = 1; $round <= self::MAX_ROUNDS; $round++) {
            foreach ([1, 2] as $slot) {
                $sql = 'INSERT INTO t_match_round_scores
                    (match_id, player_slot, round_number, obj_hold_one, obj_hold_two_plus,
                     obj_hold_more, battle_tactic_id, battle_tactic_completed, first_player_slot, is_double_turn, round_vp)
                    VALUES
                    (:match_id, :player_slot, :round_number, 0, 0, 0, NULL, 0, NULL, 0, 0);';
                $this->db->executesql($sql, [
                    'match_id'     => $matchId,
                    'player_slot'  => $slot,
                    'round_number' => $round,
                ]);
            }
        }
    }

    public function getMatchHistoryByUser(int $userId): array
    {
        $sql = 'SELECT m.id, m.battleplan_id, m.status, m.winner,
                       m.player_a_name, m.player_a_vp, fa.name AS player_a_faction_name,
                       m.player_b_name, m.player_b_vp, fb.name AS player_b_faction_name,
                       m.played_at, m.completed_at,
                       bp.name AS battleplan_name
                FROM t_matches m
                LEFT JOIN m_battleplans bp ON bp.id = m.battleplan_id
                LEFT JOIN m_factions fa ON fa.id = m.player_a_faction_id
                LEFT JOIN m_factions fb ON fb.id = m.player_b_faction_id
                WHERE m.user_id = :user_id AND m.status = :status
                ORDER BY m.completed_at DESC, m.id DESC;';
        $matches = $this->db->select($sql, ['user_id' => $userId, 'status' => 'completed']);

        foreach ($matches as &$match) {
            $match['rounds'] = $this->getRoundScoreMap((int)$match['id']);
        }
        unset($match);

        return $matches;
    }

    /**
     * ラウンド別スコアを [round => [slot => round_vp]] の形にまとめて返す。
     * 結果画面の $rounds と同じ構造。
     */
    public function getRoundScoreMap(int $matchId): array
    {
        $map = [];
        foreach ($this->getRoundScores($matchId) as $row) {
            $r = (int)$row['round_number'];
            $slot = (int)$row['player_slot'];
            $map[$r][$slot] = [
                'round_vp'          => (int)$row['round_vp'],
                'first_player_slot' => isset($row['first_player_slot']) && $row['first_player_slot'] !== null ? (int)$row['first_player_slot'] : null,
                'is_double_turn'    => (int)$row['is_double_turn'],
            ];
        }
        return $map;
    }

    public function getMatchById(int $matchId): ?array
    {
        $sql = 'SELECT m.*, bp.name AS battleplan_name
                FROM t_matches m
                LEFT JOIN m_battleplans bp ON bp.id = m.battleplan_id
                WHERE m.id = :id LIMIT 1;';
        $rows = $this->db->select($sql, ['id' => $matchId]);
        return !empty($rows) ? $rows[0] : null;
    }

    public function deleteMatch(int $matchId, int $userId): bool
    {
        $owner = $this->db->select(
            'SELECT id FROM t_matches WHERE id = :id AND user_id = :user_id LIMIT 1;',
            ['id' => $matchId, 'user_id' => $userId]
        );
        if (empty($owner)) {
            return false;
        }

        $this->db->executesql(
            'DELETE FROM t_match_ability_usage WHERE match_id = :match_id;',
            ['match_id' => $matchId]
        );
        $this->db->executesql(
            'DELETE FROM t_match_round_scores WHERE match_id = :match_id;',
            ['match_id' => $matchId]
        );

        $result = $this->db->executesql(
            'DELETE FROM t_matches WHERE id = :id AND user_id = :user_id;',
            ['id' => $matchId, 'user_id' => $userId]
        );
        return (bool)$result[0];
    }

    public function getRoundScores(int $matchId): array
    {
        $sql = 'SELECT * FROM t_match_round_scores
                WHERE match_id = :match_id
                ORDER BY round_number ASC, player_slot ASC;';
        return $this->db->select($sql, ['match_id' => $matchId]);
    }

    public function buildMatchState(int $matchId): ?array
    {
        $match = $this->getMatchById($matchId);
        if (!$match) {
            return null;
        }

        $factionMap = $this->getFactionMap();
        $roundRows = $this->getRoundScores($matchId);

        $rounds = [];
        foreach ($roundRows as $row) {
            $r = (int)$row['round_number'];
            $slot = (int)$row['player_slot'];
            if (!isset($rounds[$r])) {
                $rounds[$r] = [];
            }
            $rounds[$r][$slot] = [
                'obj_hold_one'            => (int)$row['obj_hold_one'],
                'obj_hold_two_plus'       => (int)$row['obj_hold_two_plus'],
                'obj_hold_more'           => (int)$row['obj_hold_more'],
                'battle_tactic_id'        => $row['battle_tactic_id'] ? (int)$row['battle_tactic_id'] : null,
                'battle_tactic_completed' => (int)$row['battle_tactic_completed'],
                'first_player_slot'       => isset($row['first_player_slot']) && $row['first_player_slot'] !== null ? (int)$row['first_player_slot'] : null,
                'is_double_turn'          => (int)$row['is_double_turn'],
                'round_vp'                => (int)$row['round_vp'],
            ];
        }

        $players = $this->buildPlayersFromMatch($match, $factionMap, $rounds);
        $usedAbilities = $this->buildUsedAbilitiesMap($matchId);

        $currentGameRound = (int)($match['game_battle_round'] ?? 1);
        $firstPlayer = $rounds[$currentGameRound][1]['first_player_slot'] ?? null;

        return [
            'matchId'        => (int)$match['id'],
            'battleplanId'   => (int)$match['battleplan_id'],
            'battleplanName' => $match['battleplan_name'] ?? '',
            'status'         => $match['status'],
            'currentRound'   => (int)$match['battle_round'],
            'maxRounds'      => self::MAX_ROUNDS,
            'players'        => $players,
            'rounds'         => $rounds,
            'game'           => [
                'battleRound'     => (int)($match['game_battle_round'] ?? 1),
                'activePlayer'    => (int)($match['active_player_slot'] ?? 1),
                'firstPlayer'     => $firstPlayer,
                'phase'           => $match['game_phase'] ?? 'hero',
                'turnCounter'     => (int)($match['game_turn_counter'] ?? 1),
                'usedAbilities'   => $usedAbilities,
            ],
            'updatedAt'      => $match['updated_at'],
        ];
    }

    public function getAbilityUsageRows(int $matchId): array
    {
        $sql = 'SELECT * FROM t_match_ability_usage WHERE match_id = :match_id;';
        return $this->db->select($sql, ['match_id' => $matchId]);
    }

    public function buildUsedAbilitiesMap(int $matchId): array
    {
        $match = $this->getMatchById($matchId);
        if (!$match) {
            return [];
        }

        $turnCounter = (int)($match['game_turn_counter'] ?? 1);
        $map = [];

        foreach ($this->getAbilityUsageRows($matchId) as $row) {
            $slot = (int)$row['player_slot'];
            $key = $row['ability_key'];
            $usedInTurn = (int)$row['used_in_turn'];
            $battleScoped = $this->getAbilityScopeFromKey($key, $slot, $matchId) === 'battle';
            $activeThisTurn = $usedInTurn === $turnCounter;

            $map[$slot][$key] = [
                'used'             => $battleScoped || $activeThisTurn,
                'usedInTurn'       => $usedInTurn,
                'usedInGameRound'  => (int)$row['used_in_game_round'],
                'usedAtPhase'      => $row['used_at_phase'] ?? '',
                'activeThisTurn'   => $activeThisTurn,
                'battleScoped'     => $battleScoped,
            ];
        }

        return $map;
    }

    public function completeDeployment(int $matchId, int $firstPlayerSlot): bool
    {
        if (!in_array($firstPlayerSlot, [1, 2], true)) {
            return false;
        }

        $match = $this->getMatchById($matchId);
        if (!$match || $match['status'] === 'completed') {
            return false;
        }

        if (($match['game_phase'] ?? '') !== 'deployment') {
            return false;
        }

        $firstRound = (int)($match['game_battle_round'] ?? 1);

        $result = $this->db->executesql(
            'UPDATE t_matches SET game_phase = :phase, active_player_slot = :slot, updated_at = :updated_at WHERE id = :id;',
            ['phase' => 'hero', 'slot' => $firstPlayerSlot, 'updated_at' => date('Y-m-d H:i:s'), 'id' => $matchId]
        );
        if (!$result[0]) {
            return false;
        }

        $this->recordRoundFirstPlayer($matchId, $firstRound, $firstPlayerSlot);
        return true;
    }

    /**
     * 指定ラウンドの先攻プレイヤーを記録し、前ラウンドの先攻と比較してダブルターンを判定する。
     * - first_player_slot はそのラウンドの両 slot 行に同値を保存。
     * - R>=2 で前ラウンドの先攻と異なる場合、その先攻プレイヤーの行へ is_double_turn=1 を立てる。
     */
    private function recordRoundFirstPlayer(int $matchId, int $round, int $slot): void
    {
        if ($round < 1 || $round > self::MAX_ROUNDS || !in_array($slot, [1, 2], true)) {
            return;
        }

        $this->db->executesql(
            'UPDATE t_match_round_scores SET first_player_slot = :slot
             WHERE match_id = :match_id AND round_number = :round;',
            ['slot' => $slot, 'match_id' => $matchId, 'round' => $round]
        );

        // ダブルターン判定（R>=2 のみ）
        $isDoubleTurn = false;
        if ($round > 1) {
            $prev = $this->db->select(
                'SELECT first_player_slot FROM t_match_round_scores
                 WHERE match_id = :match_id AND round_number = :round AND first_player_slot IS NOT NULL
                 LIMIT 1;',
                ['match_id' => $matchId, 'round' => $round - 1]
            );
            $prevSlot = isset($prev[0]['first_player_slot']) ? (int)$prev[0]['first_player_slot'] : null;
            if ($prevSlot !== null && $prevSlot !== $slot) {
                $isDoubleTurn = true;
            }
        }

        // 当該ラウンドのダブルターンフラグをリセットしてから付与
        $this->db->executesql(
            'UPDATE t_match_round_scores SET is_double_turn = 0
             WHERE match_id = :match_id AND round_number = :round;',
            ['match_id' => $matchId, 'round' => $round]
        );
        if ($isDoubleTurn) {
            $this->db->executesql(
                'UPDATE t_match_round_scores SET is_double_turn = 1
                 WHERE match_id = :match_id AND round_number = :round AND player_slot = :slot;',
                ['match_id' => $matchId, 'round' => $round, 'slot' => $slot]
            );
        }
    }

    public function advanceGamePhase(int $matchId): bool
    {
        $match = $this->getMatchById($matchId);
        if (!$match || $match['status'] === 'completed') {
            return false;
        }

        $phase = $match['game_phase'] ?? 'hero';
        $idx = array_search($phase, self::GAME_PHASES, true);
        if ($idx === false) {
            $idx = 0;
        }

        if ($idx >= count(self::GAME_PHASES) - 1) {
            return false;
        }

        $next = self::GAME_PHASES[$idx + 1];
        $result = $this->db->executesql(
            'UPDATE t_matches SET game_phase = :phase, updated_at = :updated_at WHERE id = :id;',
            ['phase' => $next, 'updated_at' => date('Y-m-d H:i:s'), 'id' => $matchId]
        );
        return (bool)$result[0];
    }

    public function setGamePhase(int $matchId, string $phase): bool
    {
        if (!in_array($phase, self::GAME_PHASES, true)) {
            return false;
        }

        $match = $this->getMatchById($matchId);
        if (!$match || $match['status'] === 'completed') {
            return false;
        }

        $result = $this->db->executesql(
            'UPDATE t_matches SET game_phase = :phase, updated_at = :updated_at WHERE id = :id;',
            ['phase' => $phase, 'updated_at' => date('Y-m-d H:i:s'), 'id' => $matchId]
        );
        return (bool)$result[0];
    }

    public function endTurn(int $matchId): bool
    {
        $match = $this->getMatchById($matchId);
        if (!$match || $match['status'] === 'completed') {
            return false;
        }

        $active = (int)($match['active_player_slot'] ?? 1);
        $turnCounter = (int)($match['game_turn_counter'] ?? 1);
        $gameRound = (int)($match['game_battle_round'] ?? 1);
        $nextPlayer = $active === 1 ? 2 : 1;
        $nextTurn = $turnCounter + 1;

        if ($active === 2) {
            $gameRound++;
        }

        $this->clearTurnScopedAbilities($matchId, $active, $turnCounter);

        $result = $this->db->executesql(
            'UPDATE t_matches SET
                active_player_slot = :active_player,
                game_phase = :phase,
                game_turn_counter = :turn_counter,
                game_battle_round = :game_round,
                updated_at = :updated_at
             WHERE id = :id;',
            [
                'active_player' => $nextPlayer,
                'phase'         => 'hero',
                'turn_counter'  => $nextTurn,
                'game_round'    => $gameRound,
                'updated_at'    => date('Y-m-d H:i:s'),
                'id'            => $matchId,
            ]
        );

        return (bool)$result[0];
    }

    public function setActivePlayer(int $matchId, int $playerSlot): bool
    {
        if (!in_array($playerSlot, [1, 2], true)) {
            return false;
        }
        $result = $this->db->executesql(
            'UPDATE t_matches SET active_player_slot = :slot, game_phase = :phase, updated_at = :updated_at WHERE id = :id;',
            [
                'slot'       => $playerSlot,
                'phase'      => 'hero',
                'updated_at' => date('Y-m-d H:i:s'),
                'id'         => $matchId,
            ]
        );
        return (bool)$result[0];
    }

    public function toggleAbility(int $matchId, int $playerSlot, string $abilityKey, string $phase, ?string $triggerTurn = null): bool
    {
        if (!in_array($playerSlot, [1, 2], true) || $abilityKey === '') {
            return false;
        }

        $match = $this->getMatchById($matchId);
        if (!$match || $match['status'] === 'completed') {
            return false;
        }

        $turnCounter = (int)($match['game_turn_counter'] ?? 1);
        $gameRound = (int)($match['game_battle_round'] ?? 1);
        $now = date('Y-m-d H:i:s');

        $rows = $this->db->select(
            'SELECT * FROM t_match_ability_usage
             WHERE match_id = :match_id AND player_slot = :player_slot AND ability_key = :ability_key
             LIMIT 1;',
            [
                'match_id'     => $matchId,
                'player_slot'  => $playerSlot,
                'ability_key'  => $abilityKey,
            ]
        );

        $isBattleScoped = $this->getAbilityScopeFromKey($abilityKey, $playerSlot, $matchId) === 'battle';

        if (!empty($rows)) {
            $row = $rows[0];
            $isActive = (int)$row['used_in_turn'] === $turnCounter;
            // battle スコープは過去ターンに使用済みでも、再タップで使用取り消し（DELETE）できる
            if ($isActive || $isBattleScoped) {
                $this->db->executesql(
                    'DELETE FROM t_match_ability_usage WHERE id = :id;',
                    ['id' => $row['id']]
                );
            } else {
                $this->db->executesql(
                    'UPDATE t_match_ability_usage SET
                        used_in_game_round = :game_round,
                        used_in_turn = :turn_counter,
                        used_at_phase = :phase,
                        updated_at = :updated_at
                     WHERE id = :id;',
                    [
                        'game_round'    => $gameRound,
                        'turn_counter'  => $turnCounter,
                        'phase'         => $phase,
                        'updated_at'    => $now,
                        'id'            => $row['id'],
                    ]
                );
            }
            return true;
        }

        $result = $this->db->executesql(
            'INSERT INTO t_match_ability_usage
                (match_id, player_slot, ability_key, used_in_game_round, used_in_turn, used_at_phase, updated_at)
             VALUES
                (:match_id, :player_slot, :ability_key, :game_round, :turn_counter, :phase, :updated_at);',
            [
                'match_id'      => $matchId,
                'player_slot'   => $playerSlot,
                'ability_key'   => $abilityKey,
                'game_round'    => $gameRound,
                'turn_counter'  => $turnCounter,
                'phase'         => $phase,
                'updated_at'    => $now,
            ]
        );

        return (bool)$result[0];
    }

    private function clearTurnScopedAbilities(int $matchId, int $playerSlot, int $turnCounter): void
    {
        $rows = $this->db->select(
            'SELECT u.* FROM t_match_ability_usage u
             WHERE u.match_id = :match_id AND u.player_slot = :player_slot AND u.used_in_turn = :turn_counter;',
            [
                'match_id'      => $matchId,
                'player_slot'   => $playerSlot,
                'turn_counter'  => $turnCounter,
            ]
        );

        foreach ($rows as $row) {
            if ($this->getAbilityScopeFromKey($row['ability_key'], $playerSlot, $matchId) !== 'battle') {
                $this->db->executesql(
                    'DELETE FROM t_match_ability_usage WHERE id = :id;',
                    ['id' => $row['id']]
                );
            }
        }
    }

    /** [matchId][slot] => [abilityKey => usageScope] のメモ化キャッシュ */
    private $abilityScopeCache = [];

    /**
     * 指定スロットのデッキから ability_key => usageScope（'battle'|'turn'）のマップを構築する。
     * 同一リクエスト内では match/slot 単位でメモ化する。
     */
    private function getAbilityScopeMap(int $playerSlot, int $matchId): array
    {
        if (isset($this->abilityScopeCache[$matchId][$playerSlot])) {
            return $this->abilityScopeCache[$matchId][$playerSlot];
        }

        $map = [];
        $match = $this->getMatchById($matchId);
        $rosterId = 0;
        if ($match) {
            $rosterId = $playerSlot === 1
                ? (int)($match['player_a_roster_id'] ?? 0)
                : (int)($match['player_b_roster_id'] ?? 0);
        }

        if ($rosterId > 0) {
            require_once MODELS . 'roster_model.php';
            $rosterModel = new Roster_Model();
            foreach ($rosterModel->getRosterAbilityDeckForMatch($rosterId) as $entry) {
                $map[$entry['key']] = $entry['usageScope'] ?? 'turn';
            }
        }

        $this->abilityScopeCache[$matchId][$playerSlot] = $map;
        return $map;
    }

    /**
     * ability_key の使用スコープ（'battle' でゲーム終了まで保持 / 'turn' で毎ターンリセット）を返す。
     */
    private function getAbilityScopeFromKey(string $abilityKey, int $playerSlot, int $matchId): string
    {
        $map = $this->getAbilityScopeMap($playerSlot, $matchId);
        return $map[$abilityKey] ?? 'turn';
    }

    private function buildPlayersFromMatch(array $match, array $factionMap, array $rounds): array
    {
        $slots = [
            1 => [
                'slot'       => 1,
                'name'       => $match['player_a_name'] ?? 'Player 1',
                'factionId'  => $match['player_a_faction_id'] ? (int)$match['player_a_faction_id'] : null,
                'rosterId'   => $match['player_a_roster_id'] ? (int)$match['player_a_roster_id'] : null,
                'totalVp'    => (int)$match['player_a_vp'],
            ],
            2 => [
                'slot'       => 2,
                'name'       => $match['player_b_name'] ?? 'Player 2',
                'factionId'  => $match['player_b_faction_id'] ? (int)$match['player_b_faction_id'] : null,
                'rosterId'   => $match['player_b_roster_id'] ? (int)$match['player_b_roster_id'] : null,
                'totalVp'    => (int)$match['player_b_vp'],
            ],
        ];

        foreach ($slots as $slot => &$player) {
            $fid = $player['factionId'];
            $player['factionName'] = ($fid && isset($factionMap[$fid])) ? $factionMap[$fid]['name'] : '';
            $player['grandAlliance'] = ($fid && isset($factionMap[$fid])) ? $factionMap[$fid]['grand_alliance'] : '';
            $player['roster'] = $this->loadRosterSummary($player['rosterId']);
            $player['abilitiesDeck'] = $this->loadRosterAbilityDeck($player['rosterId']);
        }
        unset($player);

        return array_values($slots);
    }

    private function loadRosterSummary(?int $rosterId): ?array
    {
        if (!$rosterId) {
            return null;
        }
        require_once MODELS . 'roster_model.php';
        $rosterModel = new Roster_Model();
        return $rosterModel->getRosterSummaryForMatch($rosterId);
    }

    private function loadRosterAbilityDeck(?int $rosterId): array
    {
        if (!$rosterId) {
            return [];
        }
        require_once MODELS . 'roster_model.php';
        $rosterModel = new Roster_Model();
        return $rosterModel->getRosterAbilityDeckForMatch($rosterId);
    }

    public function recalcTotals(int $matchId): array
    {
        $roundRows = $this->getRoundScores($matchId);
        $totals = [1 => 0, 2 => 0];

        foreach ($roundRows as $row) {
            $vp = self::calcRoundVp($row);
            $slot = (int)$row['player_slot'];
            $totals[$slot] += $vp;

            if ((int)$row['round_vp'] !== $vp) {
                $this->db->executesql(
                    'UPDATE t_match_round_scores SET round_vp = :round_vp
                     WHERE id = :id;',
                    ['round_vp' => $vp, 'id' => $row['id']]
                );
            }
        }

        $this->db->executesql(
            'UPDATE t_matches SET player_a_vp = :a_vp, player_b_vp = :b_vp, updated_at = :updated_at
             WHERE id = :id;',
            [
                'a_vp'       => $totals[1],
                'b_vp'       => $totals[2],
                'updated_at' => date('Y-m-d H:i:s'),
                'id'         => $matchId,
            ]
        );

        return $totals;
    }

    public function updateRoundScore(int $matchId, int $playerSlot, int $roundNumber, array $score): bool
    {
        $roundVp = self::calcRoundVp($score);

        $sql = 'UPDATE t_match_round_scores SET
            obj_hold_one = :obj_hold_one,
            obj_hold_two_plus = :obj_hold_two_plus,
            obj_hold_more = :obj_hold_more,
            battle_tactic_id = :battle_tactic_id,
            battle_tactic_completed = :battle_tactic_completed,
            is_double_turn = :is_double_turn,
            round_vp = :round_vp
            WHERE match_id = :match_id AND player_slot = :player_slot AND round_number = :round_number;';

        $bind = [
            'obj_hold_one'            => !empty($score['obj_hold_one']) ? 1 : 0,
            'obj_hold_two_plus'       => !empty($score['obj_hold_two_plus']) ? 1 : 0,
            'obj_hold_more'           => !empty($score['obj_hold_more']) ? 1 : 0,
            'battle_tactic_id'        => !empty($score['battle_tactic_id']) ? (int)$score['battle_tactic_id'] : null,
            'battle_tactic_completed' => !empty($score['battle_tactic_completed']) ? 1 : 0,
            'is_double_turn'          => !empty($score['is_double_turn']) ? 1 : 0,
            'round_vp'                => $roundVp,
            'match_id'                => $matchId,
            'player_slot'             => $playerSlot,
            'round_number'            => $roundNumber,
        ];

        $result = $this->db->executesql($sql, $bind);
        if ($result[0]) {
            $this->recalcTotals($matchId);
        }
        return (bool)$result[0];
    }

    public function setPlayerVp(int $matchId, int $playerSlot, int $vp): bool
    {
        if (!in_array($playerSlot, [1, 2], true)) {
            return false;
        }

        $vp = max(0, $vp);
        $column = $playerSlot === 1 ? 'player_a_vp' : 'player_b_vp';

        $result = $this->db->executesql(
            "UPDATE t_matches SET {$column} = :vp, updated_at = :updated_at WHERE id = :id;",
            ['vp' => $vp, 'updated_at' => date('Y-m-d H:i:s'), 'id' => $matchId]
        );
        return (bool)$result[0];
    }

    public function advanceRound(int $matchId, int $firstPlayerSlot): bool
    {
        if (!in_array($firstPlayerSlot, [1, 2], true)) {
            return false;
        }

        $match = $this->getMatchById($matchId);
        if (!$match || $match['status'] === 'completed') {
            return false;
        }
        if ((int)$match['battle_round'] >= self::MAX_ROUNDS) {
            return false;
        }

        $next = (int)$match['battle_round'] + 1;
        $turnCounter = (int)($match['game_turn_counter'] ?? 1);

        $this->snapshotRoundVp($matchId, (int)$match['battle_round']);

        $this->clearTurnScopedAbilities($matchId, 1, $turnCounter);
        $this->clearTurnScopedAbilities($matchId, 2, $turnCounter);

        $result = $this->db->executesql(
            'UPDATE t_matches SET
                battle_round = :round,
                game_battle_round = :round,
                active_player_slot = :slot,
                game_phase = :phase,
                game_turn_counter = :turn_counter,
                updated_at = :updated_at
             WHERE id = :id;',
            [
                'round'        => $next,
                'slot'         => $firstPlayerSlot,
                'phase'        => 'hero',
                'turn_counter' => $turnCounter + 1,
                'updated_at'   => date('Y-m-d H:i:s'),
                'id'           => $matchId,
            ]
        );
        if (!$result[0]) {
            return false;
        }

        $this->recordRoundFirstPlayer($matchId, $next, $firstPlayerSlot);
        return true;
    }

    public function setRound(int $matchId, int $round): bool
    {
        if ($round < 1 || $round > self::MAX_ROUNDS) {
            return false;
        }
        $result = $this->db->executesql(
            'UPDATE t_matches SET battle_round = :round, updated_at = :updated_at WHERE id = :id;',
            ['round' => $round, 'updated_at' => date('Y-m-d H:i:s'), 'id' => $matchId]
        );
        return (bool)$result[0];
    }

    /**
     * 指定ラウンドで稼いだVP（手動累計VPの差分）を round_vp に記録する。
     * round_vp[R] = totalVp - sum(round_vp[1..R-1])。手動VP運用のため目標保持フラグは触らない。
     */
    private function snapshotRoundVp(int $matchId, int $roundNumber): void
    {
        if ($roundNumber < 1 || $roundNumber > self::MAX_ROUNDS) {
            return;
        }

        $match = $this->getMatchById($matchId);
        if (!$match) {
            return;
        }

        $columns = [1 => 'player_a_vp', 2 => 'player_b_vp'];
        foreach ($columns as $slot => $column) {
            $totalVp = (int)($match[$column] ?? 0);

            $prev = $this->db->select(
                'SELECT COALESCE(SUM(round_vp), 0) AS prev_sum
                 FROM t_match_round_scores
                 WHERE match_id = :match_id AND player_slot = :slot AND round_number < :round;',
                ['match_id' => $matchId, 'slot' => $slot, 'round' => $roundNumber]
            );
            $prevSum = (int)($prev[0]['prev_sum'] ?? 0);
            $roundVp = max(0, $totalVp - $prevSum);

            $this->db->executesql(
                'UPDATE t_match_round_scores SET round_vp = :round_vp
                 WHERE match_id = :match_id AND player_slot = :slot AND round_number = :round;',
                ['round_vp' => $roundVp, 'match_id' => $matchId, 'slot' => $slot, 'round' => $roundNumber]
            );
        }
    }

    public function completeMatch(int $matchId): ?array
    {
        $match = $this->getMatchById($matchId);
        if (!$match) {
            return null;
        }

        $this->snapshotRoundVp($matchId, (int)$match['battle_round']);

        $aVp = (int)($match['player_a_vp'] ?? 0);
        $bVp = (int)($match['player_b_vp'] ?? 0);

        if ($aVp > $bVp) {
            $winner = $match['player_a_name'];
        } elseif ($bVp > $aVp) {
            $winner = $match['player_b_name'];
        } else {
            $winner = 'Draw';
        }

        $now = date('Y-m-d H:i:s');
        $this->db->executesql(
            'UPDATE t_matches SET status = :status, winner = :winner, completed_at = :completed_at, updated_at = :updated_at
             WHERE id = :id;',
            ['status' => 'completed', 'winner' => $winner, 'completed_at' => $now, 'updated_at' => $now, 'id' => $matchId]
        );

        return [
            'winner'   => $winner,
            'playerAVp'=> $aVp,
            'playerBVp'=> $bVp,
        ];
    }
}
