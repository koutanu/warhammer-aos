<?php
$activeMatches = $active_matches ?? [];
if (empty($activeMatches)) {
	return;
}
?>
<div class="active-match-banner" role="status">
	<p class="active-match-banner-lead">進行中の試合があります</p>
	<ul class="active-match-banner-list">
		<?php foreach ($activeMatches as $m): ?>
			<?php
			$round = (int)($m['game_battle_round'] ?? 1);
			$p1 = $m['player_a_name'] ?? 'Player 1';
			$p2 = $m['player_b_name'] ?? 'Player 2';
			$vp1 = (int)($m['player_a_vp'] ?? 0);
			$vp2 = (int)($m['player_b_vp'] ?? 0);
			$bp = $m['battleplan_name'] ?? '';
			?>
			<li class="active-match-banner-item">
				<span class="active-match-banner-summary">
					<?= $this->h($p1); ?> vs <?= $this->h($p2); ?>
					（ラウンド <?= $round; ?> / VP <?= $vp1; ?>-<?= $vp2; ?>）
					<?php if ($bp !== ''): ?>
						<small><?= $this->h($bp); ?></small>
					<?php endif; ?>
				</span>
				<a href="<?= URL; ?>match/play/<?= $this->h($m['id']); ?>" class="active-match-banner-link">試合に戻る</a>
			</li>
		<?php endforeach; ?>
	</ul>
</div>
