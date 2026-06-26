<?php
$state = $state ?? [];
$match = $match ?? [];
$players = $state['players'] ?? [];
$rounds = $state['rounds'] ?? [];
$p1 = $players[0] ?? [];
$p2 = $players[1] ?? [];
$winner = $match['winner'] ?? '';
$isDraw = ($winner === 'Draw');
?>
<div class="match summary">
	<h2>試合結果</h2>

	<div class="summary-header-card">
		<div class="summary-battleplan">
			<span class="meta-label">BATTLEPLAN</span>
			<strong><?= $this->h($state['battleplanName'] ?? ''); ?></strong>
		</div>
		<div class="summary-winner <?= $isDraw ? 'is-draw' : ''; ?>">
			<?php if ($isDraw): ?>
				<span class="winner-label">RESULT</span>
				<strong class="winner-name">引き分け</strong>
			<?php else: ?>
				<span class="winner-label">WINNER</span>
				<strong class="winner-name"><?= $this->h($winner); ?></strong>
			<?php endif; ?>
		</div>
	</div>

	<div class="summary-totals">
		<div class="summary-player total-card alliance-<?= strtolower($this->h($p1['grandAlliance'] ?? '')); ?>">
			<span class="player-label"><?= $this->h($p1['name'] ?? 'Player 1'); ?></span>
			<strong class="vp-total"><?= (int)($p1['totalVp'] ?? 0); ?> VP</strong>
			<span class="faction-name"><?= $this->h($p1['factionName'] ?? ''); ?></span>
		</div>
		<div class="summary-vs">VS</div>
		<div class="summary-player total-card alliance-<?= strtolower($this->h($p2['grandAlliance'] ?? '')); ?>">
			<span class="player-label"><?= $this->h($p2['name'] ?? 'Player 2'); ?></span>
			<strong class="vp-total"><?= (int)($p2['totalVp'] ?? 0); ?> VP</strong>
			<span class="faction-name"><?= $this->h($p2['factionName'] ?? ''); ?></span>
		</div>
	</div>

	<div class="round-breakdown">
		<h3>ラウンド別スコア</h3>
		<table class="round-table">
			<thead>
				<tr>
					<th>Round</th>
					<th>先攻</th>
					<th><?= $this->h($p1['name'] ?? 'P1'); ?></th>
					<th><?= $this->h($p2['name'] ?? 'P2'); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php for ($r = 1; $r <= 5; $r++): ?>
					<?php
					$s1 = $rounds[$r][1] ?? ['round_vp' => 0];
					$s2 = $rounds[$r][2] ?? ['round_vp' => 0];
					$vp1 = (int)($s1['round_vp'] ?? 0);
					$vp2 = (int)($s2['round_vp'] ?? 0);
					$firstSlot = $s1['first_player_slot'] ?? ($s2['first_player_slot'] ?? null);
					$dt1 = (int)($s1['is_double_turn'] ?? 0) === 1;
					$dt2 = (int)($s2['is_double_turn'] ?? 0) === 1;
					if ($firstSlot === 1) {
						$firstLabel = $this->h($p1['name'] ?? 'P1');
					} elseif ($firstSlot === 2) {
						$firstLabel = $this->h($p2['name'] ?? 'P2');
					} else {
						$firstLabel = '-';
					}
					?>
					<tr>
						<td>R<?= $r; ?></td>
						<td class="round-first-cell"><?= $firstLabel; ?></td>
						<td><?= $vp1; ?><?php if ($dt1): ?> <span class="double-turn-badge">ダブルターン</span><?php endif; ?></td>
						<td><?= $vp2; ?><?php if ($dt2): ?> <span class="double-turn-badge">ダブルターン</span><?php endif; ?></td>
					</tr>
				<?php endfor; ?>
			</tbody>
			<tfoot>
				<tr>
					<td>合計</td>
					<td></td>
					<td><strong><?= (int)($p1['totalVp'] ?? 0); ?></strong></td>
					<td><strong><?= (int)($p2['totalVp'] ?? 0); ?></strong></td>
				</tr>
			</tfoot>
		</table>
	</div>

	<div class="form-actions">
		<a href="<?= URL; ?>match/setup" class="btn-submit">新しい対戦を開始</a>
		<a href="<?= URL; ?>home" class="btn-secondary">ホームへ戻る</a>
	</div>
</div>
