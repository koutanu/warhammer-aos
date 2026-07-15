<?php
$stageLabels = [
	'affray' => 'Affray',
	'strike' => 'Strike',
	'domination' => 'Domination',
];

$armyKeys = [
	'battle_formation' => 'バトルフォーメーション',
	'spell_lore' => '呪文伝承',
	'prayer_lore' => '奇蹟伝承',
	'manifestation_lore' => '顕現伝承',
];
?>
<div class="roster detail">
	<div class="detail-header">
		<div class="detail-header-main">
			<a href="<?= URL; ?>roster/list" class="detail-back-link">&larr; 一覧へ戻る</a>
			<h2><?= $this->h($roster['name'] ?? 'ロスター詳細'); ?></h2>
		</div>
		<div class="detail-header-actions">
			<a href="<?= URL; ?>roster/edit/<?= $this->h($roster['id']); ?>" class="btn-submit">編集</a>
		</div>
	</div>

	<div class="detail-summary-card">
		<div class="detail-summary-grid">
			<div class="detail-summary-item">
				<span class="detail-label">ファクション</span>
				<span class="detail-value"><?= $this->h($roster['faction_name'] ?? '-'); ?></span>
			</div>
			<div class="detail-summary-item">
				<span class="detail-label">大同盟</span>
				<span class="detail-value"><?= $this->h(strtoupper($roster['grand_alliance'] ?? '-')); ?></span>
			</div>
			<div class="detail-summary-item">
				<span class="detail-label">合計ポイント</span>
				<span class="detail-value"><?= $this->h($roster['total_points'] ?? 0); ?> / <?= $this->h($roster['point_limit'] ?? $roster['total_points'] ?? 0); ?> pt</span>
			</div>
			<div class="detail-summary-item">
				<span class="detail-label">更新日</span>
				<span class="detail-value"><?= $this->h($roster['updated_at'] ?? $roster['created_at'] ?? '-'); ?></span>
			</div>
		</div>
	</div>

	<section class="detail-section">
		<h3>アーミーオプション</h3>
		<ul class="detail-kv-list">
			<?php foreach ($armyKeys as $key => $label): ?>
				<?php
				$opt = $army_options[$key] ?? [];
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
				$terrain = $army_options['faction_terrain'] ?? [];
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
		<?php if (!empty($battle_tactics)): ?>
			<ul class="detail-simple-list">
				<?php foreach ($battle_tactics as $tactic): ?>
					<?php
					$tacticDetail = [
						'title' => $tactic['name'] ?? 'バトルタクティクス',
						'trigger' => '',
						'effect' => '',
						'flavor' => null,
						'effect_html' => false,
						'meta' => null,
						'stages' => $tactic['stages'] ?? [],
					];
					?>
					<li>
						<button type="button"
							class="detail-value detail-open-btn js-open-ability"
							data-detail="<?= $this->h(json_encode($tacticDetail, JSON_UNESCAPED_UNICODE)); ?>">
							<?= $this->h($tactic['name'] ?? ''); ?>
						</button>
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
						<p id="detailUnitKeywords" class="detail-keywords-text">-</p>
					</div>
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
