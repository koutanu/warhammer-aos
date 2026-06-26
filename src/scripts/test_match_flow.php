<?php
/**
 * マッチ機能の統合テスト
 * php scripts/test_match_flow.php
 */
require_once __DIR__ . '/../libs/core/Config.php';
require_once __DIR__ . '/../libs/core/Database.php';
require_once __DIR__ . '/../libs/core/Session.php';
require_once __DIR__ . '/../libs/core/Model.php';
require_once __DIR__ . '/../libs/models/match_model.php';

Session::init();
$_SESSION['login_state'] = true;
$_SESSION['user_info'] = ['user_id' => 1, 'user_account' => 'test', 'user_name' => 'Test'];

$model = new Match_Model();

[$ok, $matchId] = $model->createMatch([
    'battleplan_id'       => 1,
    'player_a_name'       => 'TestA',
    'player_b_name'       => 'TestB',
    'player_a_faction_id' => 1,
    'player_b_faction_id' => 2,
]);

if (!$ok) {
    echo "FAIL: createMatch - $matchId\n";
    exit(1);
}
echo "PASS: createMatch id=$matchId\n";

$state = $model->buildMatchState($matchId);
if (!$state || $state['currentRound'] !== 1) {
    echo "FAIL: buildMatchState\n";
    exit(1);
}
echo "PASS: buildMatchState\n";

$model->updateRoundScore($matchId, 1, 1, [
    'obj_hold_one' => 1,
    'obj_hold_two_plus' => 1,
    'obj_hold_more' => 0,
    'battle_tactic_completed' => 1,
    'is_double_turn' => 0,
]);

$state = $model->buildMatchState($matchId);
$vp = Match_Model::calcRoundVp($state['rounds'][1][1]);
if ($vp !== 8) {
    echo "FAIL: round VP expected 8, got $vp\n";
    exit(1);
}
echo "PASS: updateRoundScore round1 P1 = 8 VP\n";

$model->advanceRound($matchId);
$state = $model->buildMatchState($matchId);
if ($state['currentRound'] !== 2) {
    echo "FAIL: advanceRound\n";
    exit(1);
}
echo "PASS: advanceRound\n";

$result = $model->completeMatch($matchId);
if (!$result || $result['playerAVp'] !== 8) {
    echo "FAIL: completeMatch\n";
    exit(1);
}
echo "PASS: completeMatch winner={$result['winner']}\n";

echo "\nAll integration tests passed.\n";
