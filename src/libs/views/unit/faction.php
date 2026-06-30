<?php
// 構造化フィールド(トークン)を日本語表示へ変換する補助。phases.js のラベルマップに対応。
$phaseJaMap = ['deployment' => '配置', 'hero' => 'ヒーロー', 'movement' => '移動', 'shooting' => '射撃', 'charge' => '突撃', 'combat' => '戦闘', 'end' => '終了', 'any' => '全般'];
$turnJaMap = ['your' => '自分のターン', 'opponent' => '相手のターン', 'any' => 'いつでも', 'battle' => 'バトル中'];
$phaseJa = function ($csv) use ($phaseJaMap) {
	$parts = array_filter(array_map('trim', explode(',', (string)$csv)));
	$labels = [];
	foreach ($parts as $p) {
		$labels[] = $phaseJaMap[strtolower($p)] ?? $p;
	}
	return implode(' / ', array_unique($labels));
};
$turnJa = function ($v) use ($turnJaMap) {
	$v = strtolower(trim((string)$v));
	return $turnJaMap[$v] ?? $v;
};
$freqJa = function (array $row) {
	$activation = strtolower((string)($row['activation'] ?? 'active'));
	$scope = strtolower((string)($row['usage_scope'] ?? 'unlimited'));
	$army = strtolower((string)($row['usage_per'] ?? 'unit')) === 'army' ? '（アーミー）' : '';
	if ($activation === 'passive') return 'パッシブ';
	if ($scope === 'once_per_battle') return 'バトルに1回' . $army;
	if ($scope === 'once_per_turn') return 'ターンに1回' . $army;
	if ($scope === 'once_per_phase') return 'フェイズに1回' . $army;
	if ($activation === 'reaction') return 'リアクション';
	return '';
};
?>
<div class="unit faction-units">

	<div class="unit-list-header">
		<div class="unit-list-title">
			<a href="<?= URL; ?>unit/index" class="unit-back-link">&larr; 図鑑トップ</a>
			<h2><?= $this->h($faction['name']); ?></h2>
		</div>
		<?php if (!empty($is_admin)): ?>
			<a href="<?= URL; ?>unit/create?faction_id=<?= $this->h($faction['id']); ?>" class="btn-unit-add">＋ 新規ユニット追加</a>
		<?php endif; ?>
	</div>

	<?php if (!empty($unit_success)): ?>
		<div class="unit-flash unit-flash-success" role="status"><?= $this->h($unit_success); ?></div>
	<?php endif; ?>
	<?php if (!empty($unit_error)): ?>
		<div class="unit-flash unit-flash-error" role="alert"><?= $this->h($unit_error); ?></div>
	<?php endif; ?>

	<div class="unit-tabs" role="tablist">
		<button type="button" class="unit-tab is-active" data-tab-target="tab-units" role="tab">
			ユニット<?php if (!empty($units)): ?> (<?= count($units); ?>)<?php endif; ?>
		</button>
		<button type="button" class="unit-tab" data-tab-target="tab-options" role="tab">
			軍勢オプション
		</button>
	</div>

	<div class="unit-tab-panel is-active" id="tab-units">

		<?php if (empty($units)): ?>
			<p class="unit-empty">このファクションにはユニットが登録されていません。</p>
		<?php else: ?>
			<div class="unit-card-list">
				<?php foreach ($units as $unit): ?>
					<?php $isHidden = !empty($unit['is_hidden']); ?>
					<div class="unit-card<?= $isHidden ? ' unit-card--hidden' : ''; ?>" data-unit-id="<?= $this->h($unit['id']); ?>">
						<?php if (!empty($unit['image'])): ?>
							<div class="unit-card-thumb">
								<img src="<?= URL . $this->h($unit['image']); ?>" alt="<?= $this->h($unit['name']); ?>" loading="lazy">
							</div>
						<?php endif; ?>
						<div class="unit-card-main">
							<div class="unit-card-name">
								<?php if (!empty($unit['is_hero'])): ?>
									<span class="unit-hero-badge">英雄</span>
								<?php endif; ?>
								<?= $this->h($unit['name']); ?>
								<?php if ($isHidden): ?>
									<span class="unit-hidden-badge">ロスター非表示</span>
								<?php endif; ?>
							</div>
							<div class="unit-card-meta">
							</div>
						</div>

						<div class="unit-card-actions">
							<button type="button"
								class="btn-unit-detail"
								data-unit-id="<?= $this->h($unit['id']); ?>"
								data-unit-name="<?= $this->h($unit['name']); ?>"
								data-keywords="<?= $this->h($unit['keywords']); ?>">詳細</button>

							<?php if (!empty($is_admin)): ?>
								<a href="<?= URL; ?>unit/edit/<?= $this->h($unit['id']); ?>" class="btn-unit-edit">編集</a>
								<span class="unit-card-points"><?= $this->h($unit['points']); ?> pt</span>
								<label class="unit-hide-toggle">
									<input type="checkbox"
										class="unit-hide-checkbox"
										data-unit-id="<?= $this->h($unit['id']); ?>"
										<?= $isHidden ? 'checked' : ''; ?>>
									<span>ロスターで非表示</span>
								</label>
							<?php endif; ?>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

	</div><!-- /#tab-units -->

	<div class="unit-tab-panel" id="tab-options">
		<?php
		// 呪文/祈祷/顕現（伝承）を共通描画するヘルパー
		$renderLoreGroup = function ($lores, $emptyLabel) {
			if (empty($lores)) {
				echo '<p class="option-empty">' . $this->h($emptyLabel) . '</p>';
				return;
			}
			foreach ($lores as $lore) {
				$points = (int)($lore['points'] ?? 0);
				echo '<div class="option-card">';
				echo '<div class="option-card-head">';
				echo '<span class="option-card-name">' . $this->h($lore['lore_name']) . '</span>';
				if ($points > 0) {
					echo '<span class="option-card-points">' . $this->h($points) . ' pt</span>';
				}
				echo '</div>';
				// combined_effect は整形済みHTML（エスケープ済み内容）なのでそのまま出力
				echo '<div class="option-card-effect lore-effect">' . ($lore['combined_effect'] ?? '') . '</div>';
				echo '</div>';
			}
		};
		?>

		<section class="option-section">
			<button type="button" class="option-section-title" data-accordion aria-expanded="false">
				<span>陣営地形 / FACTION TERRAIN<?php if (!empty($faction_terrain)): ?> <span class="option-section-count">(<?= count($faction_terrain); ?>)</span><?php endif; ?></span>
				<span class="accordion-icon" aria-hidden="true"></span>
			</button>
			<div class="option-section-body">
				<?php if (empty($faction_terrain)): ?>
					<p class="option-empty">このファクションには陣営地形が登録されていません。</p>
				<?php else: ?>
					<div class="unit-card-list">
						<?php foreach ($faction_terrain as $terrain): ?>
							<div class="unit-card" data-unit-id="<?= $this->h($terrain['id']); ?>">
								<?php if (!empty($terrain['image'])): ?>
									<div class="unit-card-thumb">
										<img src="<?= URL . $this->h($terrain['image']); ?>" alt="<?= $this->h($terrain['name']); ?>" loading="lazy">
									</div>
								<?php endif; ?>
								<div class="unit-card-main">
									<div class="unit-card-name">
										<span class="unit-hero-badge">地形</span>
										<?= $this->h($terrain['name']); ?>
									</div>
								</div>
								<div class="unit-card-actions">
									<button type="button"
										class="btn-unit-detail"
										data-unit-id="<?= $this->h($terrain['id']); ?>"
										data-unit-name="<?= $this->h($terrain['name']); ?>"
										data-keywords="<?= $this->h($terrain['keywords'] ?? ''); ?>">詳細</button>
									<?php if (!empty($is_admin)): ?>
										<a href="<?= URL; ?>unit/edit/<?= $this->h($terrain['id']); ?>" class="btn-unit-edit">編集</a>
									<?php endif; ?>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>
		</section>

		<section class="option-section">
			<button type="button" class="option-section-title" data-accordion aria-expanded="false">
				<span>戦闘陣形 / BATTLE FORMATIONS<?php if (!empty($battle_formations)): ?> <span class="option-section-count">(<?= count($battle_formations); ?>)</span><?php endif; ?></span>
				<span class="accordion-icon" aria-hidden="true"></span>
			</button>
			<div class="option-section-body">
				<?php if (empty($battle_formations)): ?>
					<p class="option-empty">登録なし</p>
				<?php else: ?>
					<?php foreach ($battle_formations as $f): ?>
						<div class="option-card">
							<div class="option-card-head">
								<span class="option-card-name"><?= $this->h($f['formation_name']); ?></span>
								<?php $fFreq = $freqJa($f); ?>
								<?php if ($fFreq !== ''): ?>
									<span class="option-badge option-badge--cat"><?= $this->h($fFreq); ?></span>
								<?php endif; ?>
								<?php if (!empty($f['trigger_condition_ja'])): ?>
									<span class="option-badge"><?= $this->h($f['trigger_condition_ja']); ?></span>
								<?php elseif (!empty($f['trigger_phase'])): ?>
									<span class="option-badge"><?= $this->h($phaseJa($f['trigger_phase'])); ?></span>
								<?php endif; ?>
							</div>
							<?php if (!empty($f['ability_name'])): ?>
								<div class="option-card-sub"><?= $this->h($f['ability_name']); ?></div>
							<?php endif; ?>
							<div class="option-card-effect"><?= nl2br($this->h($f['effect'])); ?></div>
							<?php if (!empty($f['flavor_text'])): ?>
								<p class="option-flavor"><?= $this->h($f['flavor_text']); ?></p>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>
		</section>

		<section class="option-section">
			<button type="button" class="option-section-title" data-accordion aria-expanded="false">
				<span>戦闘特性 / BATTLE TRAITS<?php if (!empty($battle_traits)): ?> <span class="option-section-count">(<?= count($battle_traits); ?>)</span><?php endif; ?></span>
				<span class="accordion-icon" aria-hidden="true"></span>
			</button>
			<div class="option-section-body">
				<?php if (empty($battle_traits)): ?>
					<p class="option-empty">このファクションには戦闘特性が登録されていません。</p>
				<?php else: ?>
					<?php foreach ($battle_traits as $bt): ?>
						<div class="option-card">
							<div class="option-card-head">
								<span class="option-card-name"><?= $this->h($bt['name']); ?></span>
								<?php $btFreq = $freqJa($bt); ?>
								<?php if ($btFreq !== ''): ?>
									<span class="option-badge option-badge--cat"><?= $this->h($btFreq); ?></span>
								<?php endif; ?>
								<?php if (isset($bt['command_point']) && $bt['command_point'] !== null && $bt['command_point'] !== ''): ?>
									<span class="option-card-points"><?= $this->h($bt['command_point']); ?> CP</span>
								<?php endif; ?>
							</div>
							<?php if (!empty($bt['sub_faction_name'])): ?>
								<div class="option-card-sub"><?= $this->h($bt['sub_faction_name']); ?></div>
							<?php endif; ?>
							<?php if (!empty($bt['trigger_condition_ja'])): ?>
								<span class="option-badge"><?= $this->h($bt['trigger_condition_ja']); ?></span>
							<?php else: ?>
								<?php if (!empty($bt['trigger_phase'])): ?>
									<span class="option-badge"><?= $this->h($phaseJa($bt['trigger_phase'])); ?></span>
								<?php endif; ?>
								<?php if (!empty($bt['trigger_turn'])): ?>
									<span class="option-badge"><?= $this->h($turnJa($bt['trigger_turn'])); ?></span>
								<?php endif; ?>
							<?php endif; ?>
							<div class="option-card-effect"><?= nl2br($this->h($bt['effect'])); ?></div>
							<?php if (!empty($bt['flavor_text'])): ?>
								<p class="option-flavor"><?= $this->h($bt['flavor_text']); ?></p>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>
		</section>

		<?php
		// 開閉セクション付きで伝承グループを描画
		$renderLoreSection = function ($title, $lores) use ($renderLoreGroup) {
			$count = count($lores ?? []);
			echo '<section class="option-section">';
			echo '<button type="button" class="option-section-title" data-accordion aria-expanded="false">';
			echo '<span>' . $this->h($title);
			if ($count > 0) {
				echo ' <span class="option-section-count">(' . $count . ')</span>';
			}
			echo '</span><span class="accordion-icon" aria-hidden="true"></span>';
			echo '</button>';
			echo '<div class="option-section-body">';
			$renderLoreGroup($lores ?? [], '登録なし');
			echo '</div>';
			echo '</section>';
		};
		$renderLoreSection('呪文伝承 / SPELL LORES', $spell_lores ?? []);
		$renderLoreSection('奇蹟伝承 (祈祷) / PRAYER LORES', $prayer_lores ?? []);

		// 顕現は m_manifestation_lores（召喚呪文）を伝承名ごとにまとめ、
		// 各顕現をクリックでユニット風モーダル表示できるようカード化する。
		$manifestationGroups = [];
		foreach (($manifestation_details ?? []) as $m) {
			$manifestationGroups[$m['lore_name']][] = $m;
		}
		?>
		<section class="option-section">
			<button type="button" class="option-section-title" data-accordion aria-expanded="false">
				<span>顕現伝承 / MANIFESTATION LORES<?php if (!empty($manifestationGroups)): ?> <span class="option-section-count">(<?= count($manifestationGroups); ?>)</span><?php endif; ?></span>
				<span class="accordion-icon" aria-hidden="true"></span>
			</button>
			<div class="option-section-body">
				<?php if (empty($manifestationGroups)): ?>
					<p class="option-empty">登録なし</p>
				<?php else: ?>
					<?php foreach ($manifestationGroups as $loreName => $manifests): ?>
						<div class="option-card">
							<div class="option-card-head">
								<span class="option-card-name"><?= $this->h($loreName); ?></span>
							</div>
							<div class="manifestation-spell-list">
								<?php foreach ($manifests as $m): ?>
									<div class="manifestation-spell">
										<div class="manifestation-spell-head">
											<span class="manifestation-spell-name"><?= $this->h($m['manifestation_name']); ?></span>
											<?php if (!empty($m['casting_value'])): ?>
												<span class="option-badge">Casting <?= $this->h($m['casting_value']); ?>+</span>
											<?php endif; ?>
											<?php if (!empty($m['trigger_phase'])): ?>
												<span class="option-badge"><?= $this->h($phaseJa($m['trigger_phase'])); ?></span>
											<?php endif; ?>
										</div>
										<?php if (!empty($m['effect'])): ?>
											<div class="option-card-effect"><?= nl2br($this->h($m['effect'])); ?></div>
										<?php endif; ?>
										<?php if (!empty($m['keywords'])): ?>
											<p class="manifestation-spell-keywords"><span class="keywords-label">Keywords:</span> <?= $this->h($m['keywords']); ?></p>
										<?php endif; ?>
										<?php if (!empty($m['flavor_text'])): ?>
											<p class="option-flavor"><?= $this->h($m['flavor_text']); ?></p>
										<?php endif; ?>
										<?php if (!empty($m['unit_id'])): ?>
											<div class="manifestation-spell-actions">
												<button type="button"
													class="btn-unit-detail"
													data-unit-id="<?= $this->h($m['unit_id']); ?>"
													data-unit-name="<?= $this->h($m['manifestation_name']); ?>"
													data-keywords="<?= $this->h($m['keywords'] ?? ''); ?>">詳細</button>
												<?php if (!empty($is_admin)): ?>
													<a href="<?= URL; ?>unit/edit/<?= $this->h($m['unit_id']); ?>" class="btn-unit-edit">編集</a>
												<?php endif; ?>
											</div>
										<?php endif; ?>
									</div>
								<?php endforeach; ?>
							</div>
						</div>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>
		</section>

		<section class="option-section">
			<button type="button" class="option-section-title" data-accordion aria-expanded="false">
				<span>英雄特性 / HEROIC TRAITS<?php if (!empty($heroic_traits)): ?> <span class="option-section-count">(<?= count($heroic_traits); ?>)</span><?php endif; ?></span>
				<span class="accordion-icon" aria-hidden="true"></span>
			</button>
			<div class="option-section-body">
				<?php if (empty($heroic_traits)): ?>
					<p class="option-empty">このファクションには英雄特性が登録されていません。</p>
				<?php else: ?>
					<?php foreach ($heroic_traits as $t): ?>
						<div class="option-card">
							<div class="option-card-head">
								<span class="option-card-name"><?= $this->h($t['name']); ?></span>
								<?php if (!empty($t['category'])): ?>
									<span class="option-badge option-badge--cat"><?= $this->h($t['category']); ?></span>
								<?php endif; ?>
								<?php if (!empty($t['points'])): ?>
									<span class="option-card-points"><?= $this->h($t['points']); ?> pt</span>
								<?php endif; ?>
							</div>
							<?php $tFreq = $freqJa($t); ?>
							<?php if ($tFreq !== ''): ?>
								<span class="option-badge option-badge--cat"><?= $this->h($tFreq); ?></span>
							<?php endif; ?>
							<?php if (!empty($t['trigger_condition_ja'])): ?>
								<span class="option-badge"><?= $this->h($t['trigger_condition_ja']); ?></span>
							<?php elseif (!empty($t['trigger_phase'])): ?>
								<span class="option-badge"><?= $this->h($phaseJa($t['trigger_phase'])); ?></span>
							<?php endif; ?>
							<div class="option-card-effect"><?= nl2br($this->h($t['effect'])); ?></div>
							<?php if (!empty($t['description'])): ?>
								<p class="option-flavor"><?= $this->h($t['description']); ?></p>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>
		</section>

		<section class="option-section">
			<button type="button" class="option-section-title" data-accordion aria-expanded="false">
				<span>神器 / ARTEFACTS OF POWER<?php if (!empty($artefacts)): ?> <span class="option-section-count">(<?= count($artefacts); ?>)</span><?php endif; ?></span>
				<span class="accordion-icon" aria-hidden="true"></span>
			</button>
			<div class="option-section-body">
				<?php if (empty($artefacts)): ?>
					<p class="option-empty">このファクションには神器が登録されていません。</p>
				<?php else: ?>
					<?php foreach ($artefacts as $a): ?>
						<div class="option-card">
							<div class="option-card-head">
								<span class="option-card-name"><?= $this->h($a['name']); ?></span>
								<?php if (!empty($a['category'])): ?>
									<span class="option-badge option-badge--cat"><?= $this->h($a['category']); ?></span>
								<?php endif; ?>
								<?php if (!empty($a['points'])): ?>
									<span class="option-card-points"><?= $this->h($a['points']); ?> pt</span>
								<?php endif; ?>
							</div>
							<?php $aFreq = $freqJa($a); ?>
							<?php if ($aFreq !== ''): ?>
								<span class="option-badge option-badge--cat"><?= $this->h($aFreq); ?></span>
							<?php endif; ?>
							<?php if (!empty($a['trigger_condition_ja'])): ?>
								<span class="option-badge"><?= $this->h($a['trigger_condition_ja']); ?></span>
							<?php elseif (!empty($a['trigger_phase'])): ?>
								<span class="option-badge"><?= $this->h($phaseJa($a['trigger_phase'])); ?></span>
							<?php endif; ?>
							<div class="option-card-effect"><?= nl2br($this->h($a['effect'])); ?></div>
							<?php if (!empty($a['flavor_text'])): ?>
								<p class="option-flavor"><?= $this->h($a['flavor_text']); ?></p>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>
		</section>

	</div><!-- /#tab-options -->

	<!-- ユニット詳細モーダル（roster/unit_detail.js が制御）。
	     タブパネルの display:none の影響を受けないよう、タブの外側に配置する。 -->
	<div id="unitDetailModal" class="modal" style="display: none;">
		<div class="modal-content detail-modal-content detail-modal-content--datasheet">
			<h3 id="detailUnitName">ユニット名</h3>

			<!-- 1段目: 画像 / 連隊編成 / キーワード -->
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

			<!-- 2段目: 基本情報 / 武器 -->
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
							<div class="status-label">防御力</div>
							<div class="status-value" id="detailUnitSave">-</div>
						</div>
						<div class="status-box">
							<div class="status-label" id="detailControlLabel">確保力</div>
							<div class="status-value" id="detailUnitControl">-</div>
						</div>
					</div>
				</div>
				<div class="detail-stats-weapons">
					<h4 class="detail-weapons-section-title">WEAPONS / 武器</h4>
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

			<!-- 3段目: アビリティ -->
			<div class="detail-description-section">
				<h4>ABILITIES / 特殊能力</h4>
				<div id="detailUnitAbilitiesContainer"></div>
			</div>
			<div class="detail-modal-actions">
				<button type="button" id="btnCloseDetailModal" class="btn-close-detail">閉じる</button>
			</div>
		</div>
	</div>

</div>