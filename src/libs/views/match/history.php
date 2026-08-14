<?php
$matches = $matches ?? [];
?>
<div class="match history">
	<div class="list-header">
		<h2>戦績一覧</h2>
		<a href="<?= URL; ?>match/setup" class="btn-submit">新しい対戦を開始</a>
	</div>

	<?php if (!empty($match_success)): ?>
		<div class="roster-flash roster-flash-success" role="status"><?= $this->h($match_success); ?></div>
	<?php endif; ?>

	<?php if (!empty($match_error)): ?>
		<div class="roster-flash roster-flash-error" role="alert"><?= $this->h($match_error); ?></div>
	<?php endif; ?>

	<?php if (!empty($matches)): ?>
		<div class="roster-list-table-wrap">
			<table class="roster-list-table">
				<thead>
					<tr>
						<th>対戦日</th>
						<th>バトルプラン</th>
						<th>ポイント</th>
						<th>対戦カード</th>
						<th></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($matches as $m): ?>
						<?php
						$playedAtRaw = $m['completed_at'] ?? $m['played_at'] ?? '';
						$playedAt = $playedAtRaw !== '' ? substr($playedAtRaw, 0, 10) : '-';
						$aFaction = $m['player_a_faction_name'] ?? '';
						$bFaction = $m['player_b_faction_name'] ?? '';
						$pointLimit = $m['point_limit'] ?? null;
						?>
						<tr>
							<td class="match-played-at-cell"><?= $this->h($playedAt); ?></td>
							<td class="match-battleplan-cell"><?= $this->h($m['battleplan_name'] ?? '-'); ?></td>
							<td class="match-roster-points-cell">
								<?php if ($pointLimit !== null): ?>
									<?= (int)$pointLimit; ?> pt
								<?php else: ?>
									-
								<?php endif; ?>
							</td>
							<td class="match-card-cell">
								<div class="match-card-inner">
									<span class="match-side">
										<strong><?= $this->h($m['player_a_name'] ?? 'Player 1'); ?></strong>
										<?php if ($aFaction !== ''): ?>
											<small>(<?= $this->h($aFaction); ?>)</small>
										<?php endif; ?>
									</span>
									<span class="match-score"><?= (int)($m['player_a_vp'] ?? 0); ?> - <?= (int)($m['player_b_vp'] ?? 0); ?></span>
									<span class="match-side">
										<strong><?= $this->h($m['player_b_name'] ?? 'Player 2'); ?></strong>
										<?php if ($bFaction !== ''): ?>
											<small>(<?= $this->h($bFaction); ?>)</small>
										<?php endif; ?>
									</span>
								</div>
								<?php
								$mRounds = $m['rounds'] ?? [];
								$playedRounds = [];
								for ($r = 1; $r <= 5; $r++) {
									$rv1 = (int)($mRounds[$r][1]['round_vp'] ?? 0);
									$rv2 = (int)($mRounds[$r][2]['round_vp'] ?? 0);
									$dt = ((int)($mRounds[$r][1]['is_double_turn'] ?? 0) === 1)
										|| ((int)($mRounds[$r][2]['is_double_turn'] ?? 0) === 1);
									if ($rv1 > 0 || $rv2 > 0 || $dt) {
										$playedRounds[$r] = ['vp' => [$rv1, $rv2], 'double_turn' => $dt];
									}
								}
								?>
								<!-- <?php if (!empty($playedRounds)): ?>
									<span class="match-round-breakdown" aria-label="ラウンド別スコア">
										<?php foreach ($playedRounds as $r => $info): ?>
											<span class="match-round-chip<?= $info['double_turn'] ? ' is-double-turn' : ''; ?>"<?= $info['double_turn'] ? ' title="ダブルターン"' : ''; ?>><span class="round-no">R<?= $r; ?></span><?= $info['vp'][0]; ?>-<?= $info['vp'][1]; ?><?php if ($info['double_turn']): ?><span class="double-turn-mark">DT</span><?php endif; ?></span>
										<?php endforeach; ?>
									</span>
								<?php endif; ?> -->
							</td>
							<td class="list-actions">
								<a href="<?= URL; ?>match/summary/<?= $this->h($m['id']); ?>" class="btn-edit">詳細</a>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php else: ?>
		<p class="list-empty">まだ戦績がありません。<a href="<?= URL; ?>match/setup">新しい対戦</a>を始めてみましょう。</p>
	<?php endif; ?>
</div>
