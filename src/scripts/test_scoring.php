<?php
/**
 * VP計算ロジックの簡易テスト
 * php scripts/test_scoring.php
 */
require_once __DIR__ . '/../libs/core/Config.php';
require_once __DIR__ . '/../libs/core/Model.php';
require_once __DIR__ . '/../libs/models/match_model.php';

$tests = [
    [
        'name' => 'empty score',
        'score' => ['obj_hold_one' => 0, 'obj_hold_two_plus' => 0, 'obj_hold_more' => 0, 'battle_tactic_completed' => 0, 'is_double_turn' => 0],
        'expected' => 0,
    ],
    [
        'name' => 'full objectives + tactic',
        'score' => ['obj_hold_one' => 1, 'obj_hold_two_plus' => 1, 'obj_hold_more' => 1, 'battle_tactic_completed' => 1, 'is_double_turn' => 0],
        'expected' => 10,
    ],
    [
        'name' => 'double turn - objectives only',
        'score' => ['obj_hold_one' => 1, 'obj_hold_two_plus' => 1, 'obj_hold_more' => 1, 'battle_tactic_completed' => 1, 'is_double_turn' => 1],
        'expected' => 6,
    ],
    [
        'name' => 'tactic only',
        'score' => ['obj_hold_one' => 0, 'obj_hold_two_plus' => 0, 'obj_hold_more' => 0, 'battle_tactic_completed' => 1, 'is_double_turn' => 0],
        'expected' => 4,
    ],
];

$passed = 0;
$failed = 0;

foreach ($tests as $t) {
    $result = Match_Model::calcRoundVp($t['score']);
    if ($result === $t['expected']) {
        echo "PASS: {$t['name']} => $result\n";
        $passed++;
    } else {
        echo "FAIL: {$t['name']} => expected {$t['expected']}, got $result\n";
        $failed++;
    }
}

echo "\n$passed passed, $failed failed\n";
exit($failed > 0 ? 1 : 0);
