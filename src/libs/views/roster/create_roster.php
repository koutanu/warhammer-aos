<div class="roster create_roster">
	<h2><?= !empty($edit_roster_id) ? 'ロスター編集' : '新規ロスター作成'; ?></h2>

	<?php if (!empty($roster_error)): ?>
		<div class="roster-flash roster-flash-error" role="alert"><?= $this->h($roster_error); ?></div>
	<?php endif; ?>

	<?php if (!empty($roster_meta)): ?>
		<div class="roster-meta-card">
			<div class="meta-item roster-title-display">
				<span class="meta-label">ROSTER NAME</span>
				<strong class="meta-value"><?= $this->h($roster_meta['roster_name']); ?></strong>
			</div>

			<div class="meta-item faction-display">
				<span class="meta-label">FACTION</span>
				<span class="meta-value">
					<?= $this->h($roster_meta['faction_name']); ?>
				</span>
			</div>

			<div class="meta-row">
				<div class="meta-item alliance-badge alliance-<?= strtolower($this->h($roster_meta['grand_alliance'])); ?>">
					<span class="meta-label">GRAND ALLIANCE</span>
					<span class="meta-value"><?= strtoupper($this->h($roster_meta['grand_alliance'])); ?></span>
				</div>
				<div class="meta-item point-display">
					<span class="meta-label">POINT</span>
					<strong class="meta-value"><?= $this->h($roster_meta['roster_points']); ?> pt</strong>
				</div>
			</div>
		</div>
	<?php endif; ?>

	<?php if (!empty($saved_options)): ?>
		<script id="savedArmyOptions" type="application/json">
			<?= $saved_options ? json_encode($saved_options, JSON_UNESCAPED_UNICODE) : '{}'; ?>
		</script>
	<?php endif; ?>
	<?php if (!empty($edit_roster_json)): ?>
		<script id="editRosterData" type="application/json">
			<?= $edit_roster_json; ?>
		</script>
	<?php endif; ?>

	<form id="rosterForm" action="<?= URL; ?>roster/save" method="POST">
		<input type="hidden" name="token" value="<?= $this->h($token ?? ''); ?>">
		<?php if (!empty($edit_roster_id)): ?>
			<input type="hidden" name="roster_id" value="<?= $this->h($edit_roster_id); ?>">
		<?php endif; ?>
		<input type="hidden" name="roster_name" value="<?= $this->h($roster_meta['roster_name'] ?? ''); ?>">
		<input type="hidden" name="grand_alliance" value="<?= $this->h($roster_meta['grand_alliance'] ?? ''); ?>">
		<input type="hidden" name="faction_id" value="<?= $this->h($roster_meta['faction_id'] ?? ''); ?>">
		<input type="hidden" name="roster_points" value="<?= $this->h($roster_meta['roster_points'] ?? ''); ?>">
		<input type="hidden" name="heroic_trait_id" id="heroicTraitIdInput" value="">
		<input type="hidden" name="trait_target_unit_id" id="traitTargetUnitIdInput" value="">
		<input type="hidden" name="trait_regiment_index" id="traitRegimentIndexInput" value="">
		<input type="hidden" name="trait_unit_slot" id="traitUnitSlotInput" value="">
		<input type="hidden" name="artefact_id" id="artefactIdInput" value="">
		<input type="hidden" name="artefact_target_unit_id" id="artefactTargetUnitIdInput" value="">
		<input type="hidden" name="artefact_regiment_index" id="artefactRegimentIndexInput" value="">
		<input type="hidden" name="artefact_unit_slot" id="artefactUnitSlotInput" value="">

		<div class="army-options-section">
			<h3>アーミーオプション / ARMY-WIDE OPTIONS</h3>

			<div class="form-grid">
				<div class="form-group">
					<label for="battleFormation">バトルフォーメーション (Battle Formation)</label>
					<select id="battleFormation" name="battle_formation" class="form-control army-option-select">
						<option value="">-- フォーメーションを選択 --</option>
						<?php if (!empty($battle_formations)): ?>
							<?php foreach ($battle_formations as $formation): ?>
								<?php $pts = $formation['points'] ?? 0; ?>
								<option value="<?= $this->h($formation['id']); ?>"
									data-points="<?= $this->h($pts); ?>"
									data-ability-name="<?= $this->h($formation['formation_name']); ?>"
									data-trigger="<?= $this->h($formation['trigger_phase']); ?>"
									data-effect="<?= $this->h($formation['effect']); ?>"
									data-flavor="<?= $this->h($formation['flavor_text'] ?? ''); ?>">
									<?= $this->h($formation['formation_name']); ?> (<?= $this->h($pts); ?> pt)
								</option>
							<?php endforeach; ?>
						<?php endif; ?>
					</select>

					<button type="button" id="btnToggleFormation" class="btn-view-detail" data-target="formationDetail" disabled>詳細を確認する</button>

					<div id="formationDetail" class="formation-detail-box" style="display: none; position: relative;">
						<button type="button" class="detail-close-btn" data-target="battleFormation">×</button>
						<h4 id="detailAbilityName"></h4>
						<span id="detailTrigger" class="badge"></span>
						<p id="detailEffect"></p>
						<small id="detailFlavor" class="text-muted"></small>
					</div>
				</div>

				<div class="form-group">
					<label for="spellLore">呪文伝承 (Spell Lore)</label>
					<select id="spellLore" name="spell_lore" class="form-control army-option-select">
						<option value="">-- 呪文伝承を選択 --</option>
						<?php if (!empty($spell_lores)): ?>
							<?php foreach ($spell_lores as $lore): ?>
								<option value="<?= $this->h($lore['id']); ?>"
									data-points="<?= $this->h($lore['points']); ?>"
									data-ability-name="<?= $this->h($lore['lore_name']); ?>"
									data-trigger="<?= $this->h($lore['trigger_phase']); ?>"
									data-effect="<?= $this->h($lore['combined_effect']); ?>"> <?= $this->h($lore['lore_name']); ?> (<?= $this->h($lore['points']); ?> pt)
								</option>
							<?php endforeach; ?>
						<?php endif; ?>
					</select>

					<button type="button" id="btnToggleSpell" class="btn-view-detail" data-target="spellLoreDetail" disabled>詳細を確認する</button>

					<div id="spellLoreDetail" class="formation-detail-box" style="display: none; position: relative;">
						<button type="button" class="detail-close-btn" data-target="spellLore">×</button>
						<h4 id="spellAbilityName"></h4>
						<span id="spellTrigger" class="badge"></span>
						<p id="spellEffect"></p>
					</div>
				</div>

				<div class="form-group">
					<label for="prayerLore">奇蹟伝承 (Prayer Lore)</label>
					<select id="prayerLore" name="prayer_lore" class="form-control">
						<option value="">-- 奇蹟伝承を選択 --</option>
						<?php if (!empty($prayer_lores)): ?>
							<?php foreach ($prayer_lores as $lore): ?>
								<option value="<?= $this->h($lore['id']); ?>"
									data-points="<?= $this->h($lore['points']); ?>"
									data-ability-name="<?= $this->h($lore['lore_name']); ?>"
									data-trigger="<?= $this->h($lore['trigger_phase']); ?>"
									data-effect="<?= $this->h($lore['combined_effect']); ?>"> <?= $this->h($lore['lore_name']); ?> (<?= $this->h($lore['points']); ?> pt)
								</option>
							<?php endforeach; ?>
						<?php endif; ?>
					</select>

					<button type="button" id="btnTogglePrayer" class="btn-view-detail" data-target="prayerLoreDetail" disabled>詳細を確認する</button>

					<div id="prayerLoreDetail" class="formation-detail-box" style="display: none; position: relative;">
						<button type="button" class="detail-close-btn" data-target="prayerLore">×</button>
						<h4 id="prayerAbilityName"></h4>
						<span id="prayerTrigger" class="badge"></span>
						<p id="prayerEffect"></p>
					</div>
				</div>

				<div class="form-group">
					<label for="manifestationLore">顕現魔術の伝承 (Manifestation Lore)</label>
					<select id="manifestationLore" name="manifestation_lore" class="form-control">
						<option value="">-- 顕現魔術の伝承を選択 --</option>
						<?php if (!empty($manifestation_lores)): ?>
							<?php foreach ($manifestation_lores as $lore): ?>
								<option value="<?= $this->h($lore['id']); ?>"
									data-points="<?= $this->h($lore['points']); ?>"
									data-ability-name="<?= $this->h($lore['lore_name']); ?>"
									data-trigger="<?= $this->h($lore['trigger_phase']); ?>"
									data-effect="<?= $this->h($lore['combined_effect']); ?>">
									<?= $this->h($lore['lore_name']); ?> (<?= $this->h($lore['points']); ?> pt)
								</option>
							<?php endforeach; ?>
						<?php endif; ?>
					</select>

					<button type="button" id="btnToggleManifestation" class="btn-view-detail" data-target="manifestationLoreDetail" disabled>詳細を確認する</button>

					<div id="manifestationLoreDetail" class="formation-detail-box" style="display: none; position: relative;">
						<button type="button" class="detail-close-btn" data-target="manifestationLore">×</button>
						<h4 id="manifestationAbilityName"></h4>
						<span id="manifestationTrigger" class="badge"></span>
						<p id="manifestationEffect"></p>
					</div>
				</div>
			</div>
		</div>

		<?php if (!empty($faction_terrain)): ?>
			<div class="faction-terrain-section">
				<h3>陣営地形 / FACTION TERRAIN</h3>
				<div class="form-group">
					<label for="factionTerrain">陣営地形 (Faction Terrain)</label>
					<select id="factionTerrain" name="faction_terrain" class="form-control army-option-select">
						<option value="">-- 陣営地形を選択 (任意) --</option>
						<?php foreach ($faction_terrain as $terrain): ?>
							<?php $tpts = (int)($terrain['points'] ?? 0); ?>
							<option value="<?= $this->h($terrain['id']); ?>"
								data-points="<?= $this->h($tpts); ?>"
								data-unit-id="<?= $this->h($terrain['id']); ?>">
								<?= $this->h($terrain['name']); ?> (<?= $this->h($tpts); ?> pt)
							</option>
						<?php endforeach; ?>
					</select>

					<button type="button" id="btnViewTerrainDetail" class="btn-view-detail" disabled>詳細を確認する</button>
				</div>
			</div>
		<?php endif; ?>

		<hr class="section-divider">

		<div class="regiments-section">
			<h3>連隊の編成 / REGIMENTS</h3>
			<div class="regiments-container" id="regimentsContainer">
				<div class="regiment-card regiment-card--no-hero" data-regiment-index="0" data-hero-id="">
					<div class="regiment-header">
						<span class="regiment-number">連隊 #1 (REGIMENT 1)</span>
						<label class="general-label">
							<input type="radio" name="general_regiment_index" value="0" checked> ジェネラル (GENERAL)
						</label>
						<button type="button" class="btn-delete-regiment" style="display:none;">この連隊を削除</button>
					</div>

					<div class="regiment-card-body">
						<div class="regiment-hero-zone">
							<div class="form-group">
								<label>連隊長 (HERO) <span class="required">必須</span></label>
								<input type="hidden" name="regiments[0][hero_id]" class="hero-id-input" value="">
								<div class="hero-slot-row" data-unit-id="" data-keywords="">
									<div class="hero-name-wrap">
										<span class="hero-name-display">未選択</span>
									</div>
									<button type="button" class="btn-select-hero" data-regiment-index="0">Heroを選択</button>
								</div>
								<div class="regiment-hint" style="display:none;"></div>
								<div class="hero-enhancement-actions" style="display:none;">
									<button type="button" class="btn-add-trait" disabled>+ 英雄特性</button>
									<button type="button" class="btn-add-artefact" disabled>+ 神器</button>
									<div class="enhancement-assigned trait-assigned" style="display:none;"></div>
									<div class="enhancement-assigned artefact-assigned" style="display:none;"></div>
								</div>
							</div>
						</div>

						<div class="regiment-units-zone regiment-units-zone--locked">
							<label class="section-sub-label">随伴部隊 (Units) — 最大4部隊</label>
							<p class="hero-required-hint">先に連隊長 (HERO) を選択してください</p>
							<div class="units-slot-list"></div>
							<button type="button" class="btn-add-unit" data-regiment-index="0" disabled>+ ユニットを追加 (Add Unit)</button>
						</div>
					</div>

					<div class="regiment-footer">
						<div class="regiment-subtotal">
							連隊小計: <strong class="regiment-points-val">0</strong> pt
						</div>
					</div>
				</div>
			</div>

			<div class="regiment-actions">
				<button type="button" id="btnAddRegiment" class="btn-add-regiment-card">+ 新しい連隊を追加 (Add Regiment)</button>
			</div>
		</div>

		<div class="roster-sticky-counter">
			<div class="counter-content">
				<span>合計ポイント:</span>
				<span class="points-progress">
					<strong id="currentTotalPoints">0</strong> / <span id="maxPointsLimit"><?= $this->h($roster_meta['roster_points']); ?></span> pt
				</span>
			</div>
		</div>

		<div class="form-actions">
			<a href="javascript:history.back();" class="btn-secondary">戻る</a>
			<button type="submit" class="btn-submit">この内容で確定する</button>
		</div>
	</form>

	<!-- unitのテンプレート -->
	<template id="unitTemplate">
		<div class="unit-slot-row">
			<div class="slot-main-content">
				<input type="hidden" name="regiments[__REG_INDEX__][units][__unit_INDEX__][unit_id]" class="unit-id-input" value="">
				<input type="hidden" name="regiments[__REG_INDEX__][units][__unit_INDEX__][assigned_option_id]" class="assigned-option-input" value="">

				<div class="unit-name-wrap">
					<span class="unit-name-display">未選択</span>
				</div>

				<span class="unit-option-assign" style="display:none;"></span>

				<button type="button" class="btn-select-unit" data-regiment-index="__REG_INDEX__" data-unit-index="__unit_INDEX__">ユニットを選択</button>
				<button type="button" class="btn-delete-unit">削除</button>
			</div>

			<div class="reinforce-section">
				<label>
					<input type="checkbox" name="regiments[__REG_INDEX__][units][__unit_INDEX__][is_reinforced]" class="reinforce-checkbox" value="1">
					この部隊を増強する (ポイントが2倍になります)
				</label>
			</div>
			<div class="hero-enhancement-actions unit-hero-enhancement" style="display:none;">
				<button type="button" class="btn-add-trait" disabled>+ 英雄特性</button>
				<button type="button" class="btn-add-artefact" disabled>+ 神器</button>
				<div class="enhancement-assigned trait-assigned" style="display:none;"></div>
				<div class="enhancement-assigned artefact-assigned" style="display:none;"></div>
			</div>
		</div>
	</template>

	<div id="unitModal" class="modal-overlay">
		<div class="modal-content">
			<div class="modal-header">
				<h3 id="modalTitle">ユニットを選択してください</h3>
				<button type="button" id="btnCloseModal">&times;</button>
			</div>
			<div class="modal-search-wrap">
				<input type="text" id="modalUnitSearch" class="modal-search-input" placeholder="ユニット名で検索..." autocomplete="off">
			</div>
			<div id="modalUnitList"></div>
			<p id="modalUnitEmpty" class="modal-empty-msg" style="display:none;">該当するユニットがありません。</p>
		</div>
	</div>

	<!-- 英雄特性、神器のモーダル -->
	<div id="enhancementModal" class="modal-overlay" style="display:none;">
		<div class="modal-content">
			<div class="modal-header">
				<h3 id="enhancementModalTitle">エンハンスメントを選択</h3>
				<button type="button" id="btnCloseEnhancementModal">&times;</button>
			</div>
			<div class="modal-search-wrap">
				<input type="text" id="enhancementModalSearch" class="modal-search-input" placeholder="名前で検索..." autocomplete="off">
			</div>
			<div id="enhancementModalList"></div>
			<p id="enhancementModalEmpty" class="modal-empty-msg" style="display:none;">選択肢がありません。</p>
		</div>
	</div>

	<div id="unitDetailModal" class="modal" style="display: none;">
		<div class="modal-content detail-modal-content">
			<h3 id="detailUnitName">ユニット名</h3>
			<div class="detail-modal-body-wrapper">
				<div class="detail-main-info-col">
					<div class="detail-unit-image-wrap">
						<img id="detailUnitImage" src="" alt="" loading="lazy" style="display:none;">
					</div>
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
				<div class="detail-weapons-col">
					<h4>⚔️ WEAPONS PROFILE</h4>
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
					<div class="detail-description-section" id="detailRegimentSection" style="display:none;">
						<h4>連隊編成 / REGIMENT</h4>
						<div id="detailRegimentOptions" class="detail-regiment-options"></div>
					</div>
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
			<div class="detail-description-section">
				<h4>ABILITIES / 特殊能力</h4>
				<div id="detailUnitAbilitiesContainer"></div>
			</div>
			<div class="detail-modal-actions">
				<button type="button" id="btnCloseDetailModal" class="btn-close-detail">閉じる</button>
			</div>
		</div>
	</div>

	<!-- 連隊のテンプレート -->
	<template id="regimentTemplate">
		<div class="regiment-card regiment-card--no-hero" data-regiment-index="__REG_INDEX__" data-hero-id="">
			<div class="regiment-header">
				<span class="regiment-number">連隊 #__REG_NUM__ (REGIMENT __REG_NUM__)</span>
				<label class="general-label">
					<input type="radio" name="general_regiment_index" value="__REG_INDEX__"> ジェネラル (GENERAL)
				</label>
				<button type="button" class="btn-delete-regiment">この連隊を削除</button>
			</div>

			<div class="regiment-card-body">
				<div class="regiment-hero-zone">
					<div class="form-group">
						<label>連隊長 (HERO) <span class="required">必須</span></label>
						<input type="hidden" name="regiments[__REG_INDEX__][hero_id]" class="hero-id-input" value="">
						<div class="hero-slot-row" data-unit-id="" data-keywords="">
							<div class="hero-name-wrap">
								<span class="hero-name-display">未選択</span>
							</div>
							<button type="button" class="btn-select-hero" data-regiment-index="__REG_INDEX__">Heroを選択</button>
						</div>
						<div class="regiment-hint" style="display:none;"></div>
						<div class="hero-enhancement-actions" style="display:none;">
							<button type="button" class="btn-add-trait" disabled>+ 英雄特性</button>
							<button type="button" class="btn-add-artefact" disabled>+ 神器</button>
							<div class="enhancement-assigned trait-assigned" style="display:none;"></div>
							<div class="enhancement-assigned artefact-assigned" style="display:none;"></div>
						</div>
					</div>

				</div>

				<div class="regiment-units-zone regiment-units-zone--locked">
					<label class="section-sub-label">随伴部隊 (Units) — 最大3部隊</label>
					<p class="hero-required-hint">先に連隊長 (HERO) を選択してください</p>
					<div class="units-slot-list"></div>
					<button type="button" class="btn-add-unit" data-regiment-index="__REG_INDEX__" disabled>+ ユニットを追加 (Add Unit)</button>
				</div>
			</div>

			<div class="regiment-footer">
				<div class="regiment-subtotal">
					連隊小計: <strong class="regiment-points-val">0</strong> pt
				</div>
			</div>
		</div>
	</template>
</div>