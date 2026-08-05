<?php
$state = $state ?? [];
$match = $match ?? [];
$players = $state['players'] ?? [];
$rounds = $state['rounds'] ?? [];
$p1 = $players[0] ?? [];
$p2 = $players[1] ?? [];
$winner = $match['winner'] ?? '';
$isDraw = ($winner === 'Draw');
$rosterSnapshotA = $roster_snapshot_a ?? null;
$rosterSnapshotB = $roster_snapshot_b ?? null;
$stageLabels = [
	'affray' => 'Affray',
	'strike' => 'Strike',
	'domination' => 'Domination',
];
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
					$prevFirstSlot = null;
					if ($r > 1) {
						$prevS1 = $rounds[$r - 1][1] ?? [];
						$prevS2 = $rounds[$r - 1][2] ?? [];
						$prevFirstSlot = $prevS1['first_player_slot'] ?? ($prevS2['first_player_slot'] ?? null);
					}
					// 前ラウンド後攻 → 今ラウンド先攻 = ダブルターン（先攻の入れ替わりで導出）
					$isDoubleTurnRound = $firstSlot !== null
						&& $prevFirstSlot !== null
						&& (int)$firstSlot !== (int)$prevFirstSlot;
					$seize1 = (int)($s1['is_double_turn'] ?? 0) === 1;
					$seize2 = (int)($s2['is_double_turn'] ?? 0) === 1;
					// イニシアチブ奪取がある場合は上位概念としてそちらだけ表示
					$badge1 = $seize1 ? 'initiative' : ($isDoubleTurnRound && (int)$firstSlot === 1 ? 'double' : null);
					$badge2 = $seize2 ? 'initiative' : ($isDoubleTurnRound && (int)$firstSlot === 2 ? 'double' : null);
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
						<td><?= $vp1; ?><?php if ($badge1 === 'initiative'): ?> <span class="initiative-badge">イニシアチブ奪取</span><?php elseif ($badge1 === 'double'): ?> <span class="double-turn-badge">ダブルターン</span><?php endif; ?></td>
						<td><?= $vp2; ?><?php if ($badge2 === 'initiative'): ?> <span class="initiative-badge">イニシアチブ奪取</span><?php elseif ($badge2 === 'double'): ?> <span class="double-turn-badge">ダブルターン</span><?php endif; ?></td>
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

	<?php
	$ptsA = isset($rosterSnapshotA['totalPoints']) ? (int)$rosterSnapshotA['totalPoints'] : null;
	$ptsB = isset($rosterSnapshotB['totalPoints']) ? (int)$rosterSnapshotB['totalPoints'] : null;
	$hasAnySnapshot = !empty($rosterSnapshotA) || !empty($rosterSnapshotB);
	?>
	<?php if ($ptsA !== null || $ptsB !== null || $hasAnySnapshot): ?>
	<section class="summary-rosters">
		<?php if ($ptsA !== null || $ptsB !== null): ?>
			<div class="summary-roster-points">
				<span class="meta-label">ROSTER POINTS</span>
				<strong>
					<?php if ($ptsA !== null && $ptsB !== null && $ptsA === $ptsB): ?>
						<?= $ptsA; ?> pt
					<?php else: ?>
						<?= $ptsA !== null ? $ptsA . ' pt' : '-'; ?>
						<span class="summary-roster-points-sep">/</span>
						<?= $ptsB !== null ? $ptsB . ' pt' : '-'; ?>
					<?php endif; ?>
				</strong>
			</div>
		<?php endif; ?>

		<?php if ($hasAnySnapshot): ?>
			<h3>使用ロスター</h3>
			<div class="summary-rosters-grid">
				<?php
				$snapshot = $rosterSnapshotA;
				$playerLabel = $p1['name'] ?? 'Player 1';
				$hadRoster = !empty($rosterSnapshotA);
				require __DIR__ . '/_roster_snapshot.php';
				$snapshot = $rosterSnapshotB;
				$playerLabel = $p2['name'] ?? 'Player 2';
				$hadRoster = !empty($rosterSnapshotB);
				require __DIR__ . '/_roster_snapshot.php';
				?>
			</div>
		<?php endif; ?>
	</section>
	<?php endif; ?>

	<div class="form-actions">
		<a href="<?= URL; ?>match/setup" class="btn-submit">新しい対戦を開始</a>
		<a href="<?= URL; ?>home" class="btn-secondary">ホームへ戻る</a>
	</div>
</div>

<div id="rosterAbilityModal" class="modal roster-ability-modal" style="display: none;">
	<div class="modal-content roster-ability-modal-content">
		<button type="button" class="detail-modal-close-top js-close-ability-modal" aria-label="閉じる">×</button>
		<h3 id="rosterAbilityModalTitle">詳細</h3>
		<span id="rosterAbilityModalTrigger" class="badge" style="display:none;"></span>
		<p id="rosterAbilityModalMeta" class="detail-muted" style="display:none;"></p>
		<div id="rosterAbilityModalBody" class="roster-ability-modal-body"></div>
		<div class="detail-modal-actions">
			<button type="button" id="btnCloseRosterAbilityModal" class="btn-close-detail">閉じる</button>
		</div>
	</div>
</div>

<div id="unitDetailModal" class="modal" style="display: none;">
	<div class="modal-content detail-modal-content detail-modal-content--datasheet">
		<button type="button" class="detail-modal-close-top" aria-label="閉じる">×</button>
		<h3 id="detailUnitName">ユニット名</h3>

		<div class="detail-top-row">
			<div class="detail-top-image">
				<div class="detail-unit-image-wrap">
					<img id="detailUnitImage" src="" alt="" loading="lazy" style="display:none;">
				</div>
			</div>
			<div class="detail-top-meta">
				<div class="detail-top-regiment">
					<div class="detail-description-section" id="detailRegimentSection" style="display:none;">
						<h4>連隊編成 / REGIMENT</h4>
						<div id="detailRegimentOptions" class="detail-regiment-options"></div>
					</div>
				</div>
				<div class="detail-top-keywords">
					<div class="detail-description-section">
						<h4>KEYWORDS / キーワード</h4>
						<div id="detailUnitKeywords" class="detail-keywords detail-keywords-text">-</div>
						<div id="detailKeywordEffect" class="detail-keyword-effect" style="display:none;" hidden></div>
					</div>
					<div id="detailUnitEnhancements" class="detail-unit-enhancements" style="display:none;"></div>
					<div class="detail-description-section">
						<p id="detailUnitFlavorText" class="detail-flavor-text">-</p>
					</div>
					<span id="detailUnitPoints" class="unit-card-points">pt</span>
				</div>
			</div>
		</div>

		<div class="detail-stats-row">
			<div class="detail-stats-info">
				<div class="detail-status-grid">
					<div class="status-box">
						<div class="status-label">移動力</div>
						<div class="status-value" id="detailUnitMove">-</div>
					</div>
					<div class="status-box">
						<div class="status-label">体力</div>
						<div class="status-value" id="detailUnitWounds">-</div>
					</div>
					<div class="status-box">
						<div class="status-label" id="detailSaveLabel">防御力</div>
						<div class="status-value" id="detailUnitSave">-</div>
					</div>
					<div class="status-box">
						<div class="status-label" id="detailControlLabel">確保力</div>
						<div class="status-value" id="detailUnitControl">-</div>
					</div>
				</div>
			</div>
			<div class="detail-stats-weapons">
				<div class="table-responsive">
					<table class="detail-weapons-table">
						<thead>
							<tr>
								<th>武器名</th>
								<th>射程</th>
								<th>回数</th>
								<th>ヒット</th>
								<th>ウーンズ</th>
								<th>貫通</th>
								<th>ダメージ</th>
							</tr>
						</thead>
						<tbody id="detailWeaponsBody"></tbody>
					</table>
				</div>
			</div>
		</div>

		<div class="detail-description-section">
			<h4>ABILITIES / 特殊能力</h4>
			<div id="detailUnitAbilitiesContainer"></div>
		</div>
		<div class="detail-modal-actions">
			<button type="button" id="btnCloseDetailModal" class="btn-close-detail">閉じる</button>
		</div>
	</div>
</div>

<script type="application/json" id="rosterDetailStageLabels">
<?= json_encode($stageLabels, JSON_UNESCAPED_UNICODE); ?>
</script>
