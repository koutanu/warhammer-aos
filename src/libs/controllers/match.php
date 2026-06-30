<?php

class Matchplay extends Controller
{
    private $class_name = 'match';

    public function __construct()
    {
        parent::__construct();
        Auth::handleLogin();
    }

    public function setup()
    {
        $token = Session::setToken($this->class_name . '/create');
        $data = [
            'token'        => $token,
            'js'           => [$this->class_name . '/setup.js'],
            'battleplans'  => $this->model->getBattleplans(),
            'factions'     => $this->model->getFactionsWithRoster(),
        ];
        $this->view->render($this->class_name, 'setup', 'マッチプレイ設定', $data);
    }

    public function history()
    {
        $userId = (int)Session::getUserInfo('user_id');
        $data = [
            'matches'        => $this->model->getMatchHistoryByUser($userId),
            'match_success'  => $this->pullFlash('match_success'),
            'match_error'    => $this->pullFlash('match_error'),
            'delete_token'   => Session::setToken($this->class_name . '/delete'),
        ];
        $this->view->render($this->class_name, 'history', '戦績一覧', $data);
    }

    public function delete()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . URL . 'match/history');
            exit;
        }

        $token = $_POST['token'] ?? '';
        if (!Session::checkToken($this->class_name . '/delete', $token)) {
            Session::set('match_error', 'セッションが無効です。再度お試しください。');
            header('Location: ' . URL . 'match/history');
            exit;
        }

        $userId = (int)Session::getUserInfo('user_id');
        $matchId = isset($_POST['match_id']) ? (int)$_POST['match_id'] : 0;

        if ($matchId <= 0) {
            Session::set('match_error', '削除対象の対戦が不正です。');
            header('Location: ' . URL . 'match/history');
            exit;
        }

        if ($this->model->deleteMatch($matchId, $userId)) {
            Session::set('match_success', '対戦記録を削除しました。');
        } else {
            Session::set('match_error', '対戦記録の削除に失敗しました。');
        }

        header('Location: ' . URL . 'match/history');
        exit;
    }

    public function create()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . URL . 'match/setup');
            exit;
        }

        $token = $_POST['token'] ?? '';
        if (!Session::checkToken($this->class_name . '/create', $token)) {
            header('Location: ' . URL . 'failure');
            exit;
        }

        $battleplanId = (int)($_POST['battleplan_id'] ?? 0);
        $playerAName  = trim($_POST['player_a_name'] ?? '');
        $playerBName  = trim($_POST['player_b_name'] ?? '');
        $factionAId   = (int)($_POST['player_a_faction_id'] ?? 0);
        $factionBId   = (int)($_POST['player_b_faction_id'] ?? 0);
        $rosterAId    = (int)($_POST['player_a_roster_id'] ?? 0);
        $rosterBId    = (int)($_POST['player_b_roster_id'] ?? 0);

        if ($battleplanId <= 0 || $playerAName === '' || $playerBName === '') {
            header('Location: ' . URL . 'match/setup');
            exit;
        }

        $userId = (int)Session::getUserInfo('user_id');
        require_once MODELS . 'roster_model.php';
        if ($rosterAId > 0) {
            $rosterModel = new Roster_Model();
            if (!$rosterModel->validateRosterForUser($rosterAId, $userId, $factionAId ?: null)) {
                header('Location: ' . URL . 'match/setup');
                exit;
            }
        }
        if ($rosterBId > 0) {
            if (!isset($rosterModel)) {
                $rosterModel = new Roster_Model();
            }
            if (!$rosterModel->validateRosterForUser($rosterBId, $userId, $factionBId ?: null)) {
                header('Location: ' . URL . 'match/setup');
                exit;
            }
        }

        [$ok, $matchId] = $this->model->createMatch([
            'battleplan_id'       => $battleplanId,
            'player_a_name'       => $playerAName,
            'player_b_name'       => $playerBName,
            'player_a_faction_id' => $factionAId,
            'player_b_faction_id' => $factionBId,
            'player_a_roster_id'  => $rosterAId > 0 ? $rosterAId : null,
            'player_b_roster_id'  => $rosterBId > 0 ? $rosterBId : null,
        ]);

        if (!$ok) {
            header('Location: ' . URL . 'failure');
            exit;
        }

        header('Location: ' . URL . 'match/play/' . $matchId);
        exit;
    }

    public function play($matchId = '')
    {
        $matchId = (int)$matchId;
        $match = $this->model->getMatchById($matchId);

        if (!$match) {
            header('Location: ' . URL . 'match/setup');
            exit;
        }

        if ($match['status'] === 'completed') {
            header('Location: ' . URL . 'match/summary/' . $matchId);
            exit;
        }

        $token = Session::setToken($this->class_name . '/api');
        $tactics = $this->model->getAllBattleTactics();
        $viewerSlot = (int)($_GET['slot'] ?? 1);
        if (!in_array($viewerSlot, [1, 2], true)) {
            $viewerSlot = 1;
        }

        $data = [
            'token'         => $token,
            'hide_nav'      => true,
            'js'            => [
                $this->class_name . '/phases.js',
                $this->class_name . '/state.js',
                $this->class_name . '/scoreboard.js',
                $this->class_name . '/ability_panel.js',
                $this->class_name . '/round_start.js',
                'roster/unit_detail.js',
                $this->class_name . '/roster_panel.js',
                $this->class_name . '/deployment.js',
            ],
            'match_id'      => $matchId,
            'viewer_slot'   => $viewerSlot,
            'initial_state' => $this->model->buildMatchState($matchId),
            'battle_tactics'=> $tactics,
        ];

        $this->view->render($this->class_name, 'play', 'スコアボード', $data);
    }

    public function summary($matchId = '')
    {
        $matchId = (int)$matchId;
        $state = $this->model->buildMatchState($matchId);

        if (!$state) {
            header('Location: ' . URL . 'match/setup');
            exit;
        }

        $match = $this->model->getMatchById($matchId);
        $data = [
            'state'  => $state,
            'match'  => $match,
            'hide_nav' => false,
        ];

        $this->view->render($this->class_name, 'summary', '試合結果', $data);
    }

    public function getState($matchId = '')
    {
        $this->jsonResponse(function () use ($matchId) {
            $matchId = (int)$matchId;
            $state = $this->model->buildMatchState($matchId);
            if (!$state) {
                $this->jsonError('試合が見つかりません。', 404);
            }
            return ['success' => true, 'state' => $state];
        });
    }

    public function updateScore()
    {
        $this->jsonResponse(function () {
            $body = $this->getJsonBody();
            $this->requireTokenFromBody($body);

            $matchId      = (int)($body['matchId'] ?? 0);
            $playerSlot   = (int)($body['playerSlot'] ?? 0);
            $roundNumber  = (int)($body['roundNumber'] ?? 0);
            $score        = $body['score'] ?? [];

            if ($matchId <= 0 || !in_array($playerSlot, [1, 2], true) || $roundNumber < 1 || $roundNumber > 5) {
                $this->jsonError('無効なパラメータです。', 400);
            }

            $match = $this->model->getMatchById($matchId);
            if (!$match || $match['status'] === 'completed') {
                $this->jsonError('試合を更新できません。', 400);
            }

            $ok = $this->model->updateRoundScore($matchId, $playerSlot, $roundNumber, $score);
            if (!$ok) {
                $this->jsonError('スコアの更新に失敗しました。', 500);
            }

            return ['success' => true, 'state' => $this->model->buildMatchState($matchId)];
        });
    }

    public function setVp()
    {
        $this->jsonResponse(function () {
            $body = $this->getJsonBody();
            $this->requireTokenFromBody($body);

            $matchId    = (int)($body['matchId'] ?? 0);
            $playerSlot = (int)($body['playerSlot'] ?? 0);
            $vp         = (int)($body['vp'] ?? 0);

            if ($matchId <= 0 || !in_array($playerSlot, [1, 2], true)) {
                $this->jsonError('無効なパラメータです。', 400);
            }

            $match = $this->model->getMatchById($matchId);
            if (!$match || $match['status'] === 'completed') {
                $this->jsonError('試合を更新できません。', 400);
            }

            $ok = $this->model->setPlayerVp($matchId, $playerSlot, $vp);
            if (!$ok) {
                $this->jsonError('VPの更新に失敗しました。', 500);
            }

            return ['success' => true, 'state' => $this->model->buildMatchState($matchId)];
        });
    }

    public function advanceRound()
    {
        $this->jsonResponse(function () {
            $body = $this->getJsonBody();
            $this->requireTokenFromBody($body);
            $matchId = (int)($body['matchId'] ?? 0);
            $firstPlayerSlot = (int)($body['firstPlayerSlot'] ?? 0);

            if ($matchId <= 0 || !in_array($firstPlayerSlot, [1, 2], true)) {
                $this->jsonError('無効なパラメータです。', 400);
            }

            $ok = $this->model->advanceRound($matchId, $firstPlayerSlot);
            if (!$ok) {
                $this->jsonError('ラウンドを進行できません。', 400);
            }

            return ['success' => true, 'state' => $this->model->buildMatchState($matchId)];
        });
    }

    public function setRound()
    {
        $this->jsonResponse(function () {
            $body = $this->getJsonBody();
            $this->requireTokenFromBody($body);
            $matchId = (int)($body['matchId'] ?? 0);
            $round   = (int)($body['round'] ?? 0);

            if ($matchId <= 0 || $round < 1 || $round > 5) {
                $this->jsonError('無効なパラメータです。', 400);
            }

            $ok = $this->model->setRound($matchId, $round);
            if (!$ok) {
                $this->jsonError('ラウンドの切替に失敗しました。', 400);
            }

            return ['success' => true, 'state' => $this->model->buildMatchState($matchId)];
        });
    }

    public function complete()
    {
        $this->jsonResponse(function () {
            $body = $this->getJsonBody();
            $this->requireTokenFromBody($body);
            $matchId = (int)($body['matchId'] ?? 0);

            if ($matchId <= 0) {
                $this->jsonError('無効なパラメータです。', 400);
            }

            $result = $this->model->completeMatch($matchId);
            if (!$result) {
                $this->jsonError('試合を終了できません。', 400);
            }

            return [
                'success' => true,
                'result'  => $result,
                'state'   => $this->model->buildMatchState($matchId),
            ];
        });
    }

    public function completeDeployment()
    {
        $this->jsonResponse(function () {
            $body = $this->getJsonBody();
            $this->requireTokenFromBody($body);
            $matchId = (int)($body['matchId'] ?? 0);
            $firstPlayerSlot = (int)($body['firstPlayerSlot'] ?? 0);

            if ($matchId <= 0 || !in_array($firstPlayerSlot, [1, 2], true)) {
                $this->jsonError('無効なパラメータです。', 400);
            }

            $ok = $this->model->completeDeployment($matchId, $firstPlayerSlot);
            if (!$ok) {
                $this->jsonError('配置を完了できません。', 400);
            }

            return ['success' => true, 'state' => $this->model->buildMatchState($matchId)];
        });
    }

    public function advancePhase()
    {
        $this->jsonResponse(function () {
            $body = $this->getJsonBody();
            $this->requireTokenFromBody($body);
            $matchId = (int)($body['matchId'] ?? 0);

            if ($matchId <= 0) {
                $this->jsonError('無効なパラメータです。', 400);
            }

            $ok = $this->model->advanceGamePhase($matchId);
            if (!$ok) {
                $this->jsonError('フェーズを進行できません。', 400);
            }

            return ['success' => true, 'state' => $this->model->buildMatchState($matchId)];
        });
    }

    public function setPhase()
    {
        $this->jsonResponse(function () {
            $body = $this->getJsonBody();
            $this->requireTokenFromBody($body);
            $matchId = (int)($body['matchId'] ?? 0);
            $phase = trim($body['phase'] ?? '');

            if ($matchId <= 0 || $phase === '') {
                $this->jsonError('無効なパラメータです。', 400);
            }

            $ok = $this->model->setGamePhase($matchId, $phase);
            if (!$ok) {
                $this->jsonError('フェーズの切替に失敗しました。', 400);
            }

            return ['success' => true, 'state' => $this->model->buildMatchState($matchId)];
        });
    }

    public function endTurn()
    {
        $this->jsonResponse(function () {
            $body = $this->getJsonBody();
            $this->requireTokenFromBody($body);
            $matchId = (int)($body['matchId'] ?? 0);

            if ($matchId <= 0) {
                $this->jsonError('無効なパラメータです。', 400);
            }

            $ok = $this->model->endTurn($matchId);
            if (!$ok) {
                $this->jsonError('ターンを終了できません。', 400);
            }

            return ['success' => true, 'state' => $this->model->buildMatchState($matchId)];
        });
    }

    public function setActivePlayer()
    {
        $this->jsonResponse(function () {
            $body = $this->getJsonBody();
            $this->requireTokenFromBody($body);
            $matchId = (int)($body['matchId'] ?? 0);
            $playerSlot = (int)($body['playerSlot'] ?? 0);

            if ($matchId <= 0 || !in_array($playerSlot, [1, 2], true)) {
                $this->jsonError('無効なパラメータです。', 400);
            }

            $ok = $this->model->setActivePlayer($matchId, $playerSlot);
            if (!$ok) {
                $this->jsonError('プレイヤーの切替に失敗しました。', 400);
            }

            return ['success' => true, 'state' => $this->model->buildMatchState($matchId)];
        });
    }

    public function toggleAbility()
    {
        $this->jsonResponse(function () {
            $body = $this->getJsonBody();
            $this->requireTokenFromBody($body);
            $matchId = (int)($body['matchId'] ?? 0);
            $playerSlot = (int)($body['playerSlot'] ?? 0);
            $abilityKey = trim($body['abilityKey'] ?? '');
            $phase = trim($body['phase'] ?? '');
            $triggerTurn = $body['triggerTurn'] ?? null;

            if ($matchId <= 0 || !in_array($playerSlot, [1, 2], true) || $abilityKey === '') {
                $this->jsonError('無効なパラメータです。', 400);
            }

            $ok = $this->model->toggleAbility($matchId, $playerSlot, $abilityKey, $phase, $triggerTurn);
            if (!$ok) {
                $this->jsonError('アビリティの更新に失敗しました。', 500);
            }

            return ['success' => true, 'state' => $this->model->buildMatchState($matchId)];
        });
    }

    public function getPlayerAbilities($matchId = '', $playerSlot = '')
    {
        $this->jsonResponse(function () use ($matchId, $playerSlot) {
            $matchId = (int)$matchId;
            $playerSlot = (int)$playerSlot;

            if ($matchId <= 0 || !in_array($playerSlot, [1, 2], true)) {
                $this->jsonError('無効なパラメータです。', 400);
            }

            $state = $this->model->buildMatchState($matchId);
            if (!$state) {
                $this->jsonError('試合が見つかりません。', 404);
            }

            $player = $state['players'][$playerSlot - 1] ?? null;
            if (!$player) {
                $this->jsonError('プレイヤーが見つかりません。', 404);
            }

            return [
                'success'       => true,
                'playerSlot'    => $playerSlot,
                'abilitiesDeck' => $player['abilitiesDeck'] ?? [],
                'usedAbilities' => $state['game']['usedAbilities'][$playerSlot] ?? [],
            ];
        });
    }

    private function pullFlash(string $key): ?string
    {
        $value = Session::get($key);
        if ($value) {
            Session::set($key, null);
        }
        return $value ?: null;
    }

    private function requireTokenFromBody(array $body): void
    {
        $token = $body['token'] ?? '';
        if (!Session::checkToken($this->class_name . '/api', $token)) {
            $this->jsonError('トークンが無効です。', 403);
        }
    }

    private function getJsonBody(): array
    {
        $raw = file_get_contents('php://input');
        if ($raw) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }
        return $_POST;
    }

    private function jsonResponse(callable $fn): void
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $data = $fn();
            echo json_encode($data, JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'サーバーエラー'], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    private function jsonError(string $message, int $code): void
    {
        http_response_code($code);
        echo json_encode(['success' => false, 'message' => $message], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
