<?php
/**
 * 試合結果用ロスタースナップショット表示
 *
 * @var array|null $snapshot
 * @var string $playerLabel
 * @var bool $hadRoster 試合時にロスターが紐づいていたか（フォールバック後も無い場合の文言用）
 */
$snapshot = $snapshot ?? null;
$playerLabel = $playerLabel ?? 'Player';
$hadRoster = !empty($hadRoster);
$armyKeys = [
	'battle_formation' => 'バトルフォーメーション',
	'spell_lore' => '呪文伝承',
	'prayer_lore' => '奇蹟伝承',
	'manifestation_lore' => '顕現伝承',
];
?>
<details class="summary-roster-panel" open>
	<summary class="summary-roster-panel-toggle">
		<span class="summary-roster-player"><?= $this->h($playerLabel); ?></span>
		<?php if ($snapshot): ?>
			<strong class="summary-roster-name"><?= $this->h($snapshot['name'] ?? 'ロスター'); ?></strong>
			<span class="summary-roster-meta">
				<?= $this->h($snapshot['factionName'] ?? ''); ?>
				<?php if (!empty($snapshot['totalPoints'])): ?>
					· <?= (int)$snapshot['totalPoints']; ?> pt
				<?php endif; ?>
			</span>
		<?php else: ?>
		<span class="summary-roster-meta"><?= $hadRoster ? 'ロスター情報なし' : 'ロスター未選択'; ?></span>
		<?php endif; ?>
	</summary>

	<?php if (!$snapshot): ?>
		<p class="detail-value detail-muted summary-roster-missing">
			この試合ではロスターが選択されていません。
		</p>
	<?php else: ?>
		<?php
		$armyOptions = $snapshot['armyOptions'] ?? [];
		$enhancements = $snapshot['enhancements'] ?? [];
		$battleTactics = $snapshot['battleTactics'] ?? [];
		$regiments = $snapshot['regiments'] ?? [];
		?>
		<div class="roster detail summary-roster-detail">
			<div class="detail-summary-card">
				<div class="detail-summary-grid">
					<div class="detail-summary-item">
						<span class="detail-label">ファクション</span>
						<span class="detail-value"><?= $this->h($snapshot['factionName'] ?? '-'); ?></span>
					</div>
					<div class="detail-summary-item">
						<span class="detail-label">大同盟</span>
						<span class="detail-value"><?= $this->h(strtoupper($snapshot['grandAlliance'] ?? '-')); ?></span>
					</div>
					<div class="detail-summary-item">
						<span class="detail-label">合計ポイント</span>
						<span class="detail-value"><?= $this->h($snapshot['totalPoints'] ?? 0); ?> / <?= $this->h($snapshot['pointLimit'] ?? $snapshot['totalPoints'] ?? 0); ?> pt</span>
					</div>
				</div>
			</div>

			<section class="detail-section">
				<h3>メモ</h3>
				<?php $memoText = trim((string)($snapshot['memo'] ?? '')); ?>
				<?php if ($memoText !== ''): ?>
					<div class="detail-memo-body"><?= nl2br($this->h($memoText)); ?></div>
				<?php else: ?>
					<p class="detail-value detail-muted">未登録</p>
				<?php endif; ?>
			</section>

			<section class="detail-section">
				<h3>アーミーオプション</h3>
				<ul class="detail-kv-list">
					<?php foreach ($armyKeys as $key => $label): ?>
						<?php
						$opt = $armyOptions[$key] ?? [];
						$name = $opt['name'] ?? null;
						$detail = $opt['detail'] ?? null;
						?>
						<li>
							<span class="detail-label"><?= $this->h($label); ?></span>
							<?php if ($name && $detail): ?>
								<button type="button"
									class="detail-value detail-open-btn js-open-ability"
									data-detail="<?= $this->h(json_encode($detail, JSON_UNESCAPED_UNICODE)); ?>">
									<?= $this->h($name); ?>
								</button>
							<?php else: ?>
								<span class="detail-value"><?= $this->h($name ?: '未選択'); ?></span>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
					<li>
						<span class="detail-label">陣営地形</span>
						<?php
						$terrain = $armyOptions['faction_terrain'] ?? [];
						$terrainName = $terrain['name'] ?? null;
						$terrainUnitId = (int)($terrain['unit_id'] ?? 0);
						?>
						<?php if ($terrainName && $terrainUnitId > 0): ?>
							<button type="button"
								class="detail-value detail-open-btn js-open-unit"
								data-unit-id="<?= $terrainUnitId; ?>"
								data-unit-name="<?= $this->h($terrainName); ?>">
								<?= $this->h($terrainName); ?>
							</button>
						<?php else: ?>
							<span class="detail-value"><?= $this->h($terrainName ?: '未選択'); ?></span>
						<?php endif; ?>
					</li>
				</ul>
			</section>

			<section class="detail-section">
				<h3>エンハンスメント</h3>
				<ul class="detail-kv-list">
					<li>
						<span class="detail-label">英雄特性</span>
						<?php if (!empty($enhancements['trait']['detail'])): ?>
							<button type="button"
								class="detail-value detail-open-btn js-open-ability"
								data-detail="<?= $this->h(json_encode($enhancements['trait']['detail'], JSON_UNESCAPED_UNICODE)); ?>">
								<?= $this->h($enhancements['trait']['name']); ?>
								<?php if (!empty($enhancements['trait']['target'])): ?>
									<span class="detail-muted">（<?= $this->h($enhancements['trait']['target']); ?>）</span>
								<?php endif; ?>
							</button>
						<?php else: ?>
							<span class="detail-value">未選択</span>
						<?php endif; ?>
					</li>
					<li>
						<span class="detail-label">神器</span>
						<?php if (!empty($enhancements['artefact']['detail'])): ?>
							<button type="button"
								class="detail-value detail-open-btn js-open-ability"
								data-detail="<?= $this->h(json_encode($enhancements['artefact']['detail'], JSON_UNESCAPED_UNICODE)); ?>">
								<?= $this->h($enhancements['artefact']['name']); ?>
								<?php if (!empty($enhancements['artefact']['target'])): ?>
									<span class="detail-muted">（<?= $this->h($enhancements['artefact']['target']); ?>）</span>
								<?php endif; ?>
							</button>
						<?php else: ?>
							<span class="detail-value">未選択</span>
						<?php endif; ?>
					</li>
					<li>
						<span class="detail-label"><?= $this->h($enhancements['season']['label'] ?? '追加能力'); ?></span>
						<?php if (!empty($enhancements['season']['detail'])): ?>
							<button type="button"
								class="detail-value detail-open-btn js-open-ability"
								data-detail="<?= $this->h(json_encode($enhancements['season']['detail'], JSON_UNESCAPED_UNICODE)); ?>">
								<?= $this->h($enhancements['season']['name']); ?>
								<?php if (!empty($enhancements['season']['target'])): ?>
									<span class="detail-muted">（<?= $this->h($enhancements['season']['target']); ?>）</span>
								<?php endif; ?>
							</button>
						<?php else: ?>
							<span class="detail-value">未選択</span>
						<?php endif; ?>
					</li>
				</ul>
			</section>

			<section class="detail-section">
				<h3>バトルタクティクス</h3>
				<?php if (!empty($battleTactics)): ?>
					<ul class="detail-simple-list summary-bt-list">
						<?php foreach ($battleTactics as $tactic): ?>
							<?php
							$completedOrder = (int)($tactic['highestCompletedOrder'] ?? 0);
							$stages = $tactic['stages'] ?? [];
							$tacticDetail = [
								'title' => $tactic['name'] ?? 'バトルタクティクス',
								'trigger' => '',
								'effect' => '',
								'flavor' => null,
								'effect_html' => false,
								'meta' => '進捗 ' . $completedOrder . ' / 3',
								'stages' => $stages,
							];
							$stageOrderLabels = [
								1 => 'Affray',
								2 => 'Strike',
								3 => 'Domination',
							];
							?>
							<li class="summary-bt-item">
								<button type="button"
									class="detail-value detail-open-btn js-open-ability summary-bt-name"
									data-detail="<?= $this->h(json_encode($tacticDetail, JSON_UNESCAPED_UNICODE)); ?>">
									<?= $this->h($tactic['name'] ?? ''); ?>
								</button>
								<span class="summary-bt-progress" title="達成段階">
									進捗 <?= $completedOrder; ?> / 3
								</span>
								<span class="summary-bt-stages" aria-label="段階達成状況">
									<?php foreach ($stageOrderLabels as $order => $label): ?>
										<?php
										$done = $completedOrder >= $order;
										$stageName = $label;
										foreach ($stages as $st) {
											$so = (int)($st['stage_order'] ?? 0);
											if ($so === $order && !empty($st['name'])) {
												$stageName = $st['name'];
												break;
											}
											// stage 文字列でも判定
											if ($so === 0 && isset($st['stage']) && strcasecmp((string)$st['stage'], $label) === 0 && !empty($st['name'])) {
												$stageName = $st['name'];
											}
										}
										?>
										<span class="summary-bt-stage<?= $done ? ' is-done' : ''; ?>" title="<?= $this->h($stageName); ?>">
											<?= $this->h($label); ?>
										</span>
									<?php endforeach; ?>
								</span>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php else: ?>
					<p class="detail-empty">未選択</p>
				<?php endif; ?>
			</section>

			<section class="detail-section">
				<h3>連隊編成</h3>
				<?php if (empty($regiments)): ?>
					<p class="detail-empty">連隊がありません。</p>
				<?php else: ?>
					<?php foreach ($regiments as $i => $regiment): ?>
						<?php
						$hero = $regiment['hero'] ?? [];
						$units = $regiment['units'] ?? [];
						$regPts = (int)($hero['points'] ?? 0);
						foreach ($units as $u) {
							$regPts += (int)($u['points'] ?? 0);
						}
						$heroId = (int)($hero['id'] ?? 0);
						?>
						<div class="detail-regiment<?= !empty($regiment['is_general']) ? ' is-general' : ''; ?>">
							<div class="detail-regiment-head">
								<strong>連隊 <?= (int)$i + 1; ?></strong>
								<?php if (!empty($regiment['is_general'])): ?>
									<span class="detail-badge">GENERAL</span>
								<?php endif; ?>
								<span class="detail-regiment-pts"><?= $regPts; ?> pt</span>
							</div>
							<ul class="detail-unit-list">
								<li class="detail-unit-row is-hero">
									<span class="detail-unit-role">連隊長</span>
									<?php if ($heroId > 0): ?>
										<button type="button"
											class="detail-unit-name detail-open-btn js-open-unit"
											data-unit-id="<?= $heroId; ?>"
											data-unit-name="<?= $this->h($hero['name'] ?? ''); ?>">
											<?= $this->h($hero['name'] ?? '-'); ?>
										</button>
									<?php else: ?>
										<span class="detail-unit-name"><?= $this->h($hero['name'] ?? '-'); ?></span>
									<?php endif; ?>
									<span class="detail-unit-pts"><?= $this->h($hero['points'] ?? 0); ?> pt</span>
								</li>
								<?php foreach ($units as $unit): ?>
									<?php $unitId = (int)($unit['id'] ?? 0); ?>
									<li class="detail-unit-row">
										<span class="detail-unit-role">随伴</span>
										<?php if ($unitId > 0): ?>
											<button type="button"
												class="detail-unit-name detail-open-btn js-open-unit"
												data-unit-id="<?= $unitId; ?>"
												data-unit-name="<?= $this->h($unit['name'] ?? ''); ?>">
												<?= $this->h($unit['name'] ?? '-'); ?>
												<?php if (!empty($unit['is_reinforced'])): ?>
													<span class="detail-muted">（増強）</span>
												<?php endif; ?>
											</button>
										<?php else: ?>
											<span class="detail-unit-name">
												<?= $this->h($unit['name'] ?? '-'); ?>
												<?php if (!empty($unit['is_reinforced'])): ?>
													<span class="detail-muted">（増強）</span>
												<?php endif; ?>
											</span>
										<?php endif; ?>
										<span class="detail-unit-pts"><?= $this->h($unit['points'] ?? 0); ?> pt</span>
									</li>
								<?php endforeach; ?>
							</ul>
						</div>
					<?php endforeach; ?>
				<?php endif; ?>
			</section>
		</div>
	<?php endif; ?>
</details>
