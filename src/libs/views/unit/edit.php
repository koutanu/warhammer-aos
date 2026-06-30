<?php
$isNew = !empty($is_new);
$u = $unit ?? [];
$selectedFactionId = $isNew
	? (int)($faction['id'] ?? 0)
	: (int)($u['faction_id'] ?? 0);
$weaponRows = $weapons ?? [];
$abilityRows = $abilities ?? [];
$allAbilities = $all_abilities ?? [];
$regimentOptions = $regiment_options ?? [];
$eligibilityIds = array_map('intval', $unit_eligibility_ids ?? []);
$heroOptionMap = $hero_regiment_option_map ?? [];
$isHeroUnit = !empty($u['is_hero']);
$isManifestationUnit = !empty($u['is_manifestation']);
?>
<div class="unit unit-edit">

	<div class="unit-edit-header">
		<a href="<?= URL; ?>unit/faction/<?= $this->h($selectedFactionId); ?>" class="unit-back-link">&larr; 一覧へ戻る</a>
		<h2><?= $isNew ? 'ユニット新規作成' : 'ユニット編集: ' . $this->h($u['name'] ?? ''); ?></h2>
	</div>

	<?php if (!empty($unit_error)): ?>
		<div class="unit-flash unit-flash-error" role="alert"><?= $this->h($unit_error); ?></div>
	<?php endif; ?>

	<form id="unitEditForm" method="post" action="<?= URL; ?>unit/save" enctype="multipart/form-data">
		<input type="hidden" name="token" value="<?= $this->h($token ?? ''); ?>">
		<?php if (!$isNew): ?>
			<input type="hidden" name="unit_id" value="<?= $this->h($u['id'] ?? ''); ?>">
		<?php endif; ?>

		<!-- ============ コアステータス ============ -->
		<section class="unit-edit-section">
			<h3 class="unit-edit-section-title">基本情報</h3>

			<div class="form-row">
				<div class="form-group form-group--wide">
					<label>ユニット名 <span class="required">必須</span></label>
					<input type="text" name="name" value="<?= $this->h($u['name'] ?? ''); ?>" required>
				</div>
				<div class="form-group">
					<label>ファクション <span class="required">必須</span></label>
					<select name="faction_id" required>
						<option value="">-- 選択 --</option>
						<?php foreach (($factions ?? []) as $alliance => $factionList): ?>
							<optgroup label="<?= $this->h($alliance); ?>">
								<?php foreach ($factionList as $f): ?>
									<option value="<?= $this->h($f['id']); ?>" <?= ((int)$f['id'] === $selectedFactionId) ? 'selected' : ''; ?>>
										<?= $this->h($f['name']); ?>
									</option>
								<?php endforeach; ?>
							</optgroup>
						<?php endforeach; ?>
					</select>
				</div>
			</div>

			<div class="form-row stat-row">
				<div class="form-group">
					<label>移動力 (MOVE)</label>
					<input type="text" name="movement" value="<?= $this->h($u['movement'] ?? ''); ?>" placeholder='例: 6'>
				</div>
				<div class="form-group">
					<label>体力 (WOUNDS)</label>
					<input type="text" name="wounds" value="<?= $this->h($u['wounds'] ?? ''); ?>">
				</div>
				<div class="form-group">
					<label>防御力 (SAVE)</label>
					<input type="text" name="save" value="<?= $this->h($u['save'] ?? ''); ?>" placeholder='例: 4'>
				</div>
				<div class="form-group">
					<label id="controlLabel"
						data-label-control="確保力 (CONTROL)"
						data-label-banishment="追放 (BANISHMENT)"><?= $isManifestationUnit ? '追放 (BANISHMENT)' : '確保力 (CONTROL)'; ?></label>
					<input type="text" name="control" value="<?= $this->h($u['control'] ?? ''); ?>">
				</div>
			</div>

			<div class="form-row stat-row">
				<div class="form-group">
					<label>ポイント (POINTS)</label>
					<input type="number" name="points" value="<?= $this->h($u['points'] ?? ''); ?>">
				</div>
				<div class="form-group">
					<label>部隊サイズ (UNIT SIZE)</label>
					<input type="number" name="unit_size" value="<?= $this->h($u['unit_size'] ?? ''); ?>">
				</div>
				<div class="form-group">
					<label>ベースサイズ</label>
					<input type="text" name="base_size" value="<?= $this->h($u['base_size'] ?? ''); ?>">
				</div>
			</div>

			<div class="form-group form-group--wide unit-image-field">
				<label>ユニット画像</label>
				<!-- 新規アップロードが無い場合は既存パスを維持する -->
				<input type="hidden" name="image" value="<?= $this->h($u['image'] ?? ''); ?>">
				<?php if (!empty($u['image'])): ?>
					<div class="unit-image-preview">
						<img src="<?= URL . $this->h($u['image']); ?>" alt="<?= $this->h($u['name'] ?? ''); ?>">
					</div>
				<?php endif; ?>
				<input type="file" name="image_file" accept="image/jpeg,image/png,image/gif,image/webp">
				<small class="unit-image-hint">jpg / png / gif / webp（最大5MB）。アップロードすると既存画像は置き換えられます。</small>
			</div>

			<div class="form-group form-group--wide">
				<label>ユニットキーワード (カンマ区切り)</label>
				<textarea name="unit_keywords" rows="2" placeholder="例: HERO, INFANTRY, FLY, WARD (5+)"><?= $this->h($u['unit_keywords'] ?? ''); ?></textarea>
				<small class="unit-image-hint">ユニット自身のルール系キーワード（HERO や INFANTRY など）。</small>
			</div>

			<div class="form-group form-group--wide">
				<label>連隊枠キーワード (カンマ区切り)</label>
				<textarea name="faction_keywords" rows="2" placeholder="例: RUINATION CHAMBER / WARRIOR CHAMBER / ESHIN"><?= $this->h($u['faction_keywords'] ?? ''); ?></textarea>
				<small class="unit-image-hint">連隊の適格性判定に使うチェンバー/クラン等のキーワードのみを入力します。大同盟・軍勢キーワードはファクションから自動付与されるため入力不要です。</small>
			</div>

			<div class="form-group form-group--wide">
				<label>フレーバーテキスト</label>
				<textarea name="flavor_text" rows="2"><?= $this->h($u['flavor_text'] ?? ''); ?></textarea>
			</div>

			<div class="form-group form-check">
				<label>
					<input type="checkbox" name="is_hero" value="1" <?= !empty($u['is_hero']) ? 'checked' : ''; ?>>
					HERO（連隊長として選択可能にする）
				</label>
			</div>

			<div class="form-group form-check">
				<label>
					<input type="checkbox" name="can_reinforce" value="1" <?= !empty($u['can_reinforce']) ? 'checked' : ''; ?>>
					増強可能（ロスターで増強＝ポイント2倍を選べるようにする）
				</label>
			</div>

			<div class="form-group form-check">
				<label>
					<input type="checkbox" name="is_general" value="1" <?= !empty($u['is_general']) ? 'checked' : ''; ?>>
					総大将（ロスターに入れるとジェネラルに指定が必要）
				</label>
			</div>

			<div class="form-group form-check">
				<label>
					<input type="checkbox" name="is_unique" value="1" <?= !empty($u['is_unique']) ? 'checked' : ''; ?>>
					固有（ロスター全体で1体まで・神器/英雄特性を付与不可）
				</label>
			</div>

			<div class="form-group form-check">
				<label>
					<input type="checkbox" name="is_hidden" value="1" <?= !empty($u['is_hidden']) ? 'checked' : ''; ?>>
					ロスター作成画面に表示しない（非表示にする）
				</label>
			</div>

			<div class="form-group form-check">
				<label>
					<input type="checkbox" name="is_terrain" value="1" <?= !empty($u['is_terrain']) ? 'checked' : ''; ?>>
					陣営地形（ファクションテレイン。連隊編成の対象外。図鑑・対戦デッキで地形として扱う。通常はロスター非表示も併用）
				</label>
			</div>

			<div class="form-group form-check">
				<label>
					<input type="checkbox" name="is_manifestation" value="1" <?= !empty($u['is_manifestation']) ? 'checked' : ''; ?>>
					顕現（マニフェステーション/エンドレススペル。連隊編成の対象外。図鑑・対戦デッキで顕現として扱う。通常はロスター非表示も併用）
				</label>
			</div>
		</section>

		<!-- ============ 武器プロファイル ============ -->
		<section class="unit-edit-section">
			<h3 class="unit-edit-section-title">武器プロファイル</h3>
			<div class="table-responsive">
				<table class="unit-weapons-edit-table">
					<thead>
						<tr>
							<th>武器名</th>
							<th>種別</th>
							<th>射程"</th>
							<th>回数</th>
							<th>ヒット</th>
							<th>ウーンズ</th>
							<th>貫通</th>
							<th>ダメージ</th>
							<th>武器能力</th>
							<th></th>
						</tr>
					</thead>
					<tbody id="weaponsTableBody">
						<?php foreach ($weaponRows as $i => $w): ?>
							<tr class="weapon-row">
								<td><input type="text" name="weapons[<?= $i; ?>][name]" value="<?= $this->h($w['name'] ?? ''); ?>"></td>
								<td>
									<select name="weapons[<?= $i; ?>][type]">
										<option value="melee" <?= (($w['type'] ?? '') === 'melee') ? 'selected' : ''; ?>>近接</option>
										<option value="ranged" <?= (($w['type'] ?? '') === 'ranged') ? 'selected' : ''; ?>>射撃</option>
									</select>
								</td>
								<td><input type="number" name="weapons[<?= $i; ?>][rng]" value="<?= $this->h($w['rng'] ?? ''); ?>" class="cell-narrow"></td>
								<td><input type="text" name="weapons[<?= $i; ?>][atk]" value="<?= $this->h($w['atk'] ?? ''); ?>" class="cell-narrow"></td>
								<td><input type="text" name="weapons[<?= $i; ?>][hit]" value="<?= $this->h($w['hit'] ?? ''); ?>" class="cell-narrow"></td>
								<td><input type="text" name="weapons[<?= $i; ?>][wnd]" value="<?= $this->h($w['wnd'] ?? ''); ?>" class="cell-narrow"></td>
								<td><input type="text" name="weapons[<?= $i; ?>][rnd]" value="<?= $this->h($w['rnd'] ?? ''); ?>" class="cell-narrow"></td>
								<td><input type="text" name="weapons[<?= $i; ?>][dmg]" value="<?= $this->h($w['dmg'] ?? ''); ?>" class="cell-narrow"></td>
								<td><input type="text" name="weapons[<?= $i; ?>][abilities]" value="<?= $this->h($w['abilities'] ?? ''); ?>"></td>
								<td><button type="button" class="btn-remove-row" title="削除">×</button></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
			<button type="button" id="btnAddWeapon" class="btn-add-row">＋ 武器を追加</button>
		</section>

		<!-- ============ 能力 ============ -->
		<section class="unit-edit-section">
			<h3 class="unit-edit-section-title">能力 (ABILITIES)</h3>
			<p class="unit-edit-note">
				⚠️ 能力は複数のユニットで共有されている場合があります。既存能力を編集すると、同じ能力を持つ他のユニットにも変更が反映されます。
				このユニットだけ別内容にしたい場合は、既存能力を「外す」→ 新規追加してください。
			</p>

			<div class="ability-attach-bar">
				<select id="existingAbilitySelect">
					<option value="">-- 既存の能力から追加 --</option>
					<?php foreach ($allAbilities as $a): ?>
						<option
							value="<?= $this->h($a['id']); ?>"
							data-name="<?= $this->h($a['name']); ?>"
							data-command_point="<?= $this->h($a['command_point'] ?? ''); ?>"
							data-casting_value="<?= $this->h($a['casting_value'] ?? ''); ?>"
							data-casting_type="<?= $this->h($a['casting_type'] ?? ''); ?>"
							data-trigger_phase="<?= $this->h($a['trigger_phase']); ?>"
							data-trigger_turn="<?= $this->h($a['trigger_turn']); ?>"
							data-activation="<?= $this->h($a['activation'] ?? 'active'); ?>"
							data-usage_scope="<?= $this->h($a['usage_scope'] ?? 'unlimited'); ?>"
							data-usage_per="<?= $this->h($a['usage_per'] ?? 'unit'); ?>"
							data-icon_type="<?= $this->h($a['icon_type'] ?? ''); ?>"
							data-trigger_condition_en="<?= $this->h($a['trigger_condition_en'] ?? ''); ?>"
							data-trigger_condition_ja="<?= $this->h($a['trigger_condition_ja'] ?? ''); ?>"
							data-effect="<?= $this->h($a['effect']); ?>"
							data-flavor_text="<?= $this->h($a['flavor_text']); ?>"
							data-keywords="<?= $this->h($a['keywords']); ?>">
							<?= $this->h($a['name']); ?>
						</option>
					<?php endforeach; ?>
				</select>
				<button type="button" id="btnAttachExisting" class="btn-add-row">この能力を追加</button>
				<button type="button" id="btnAddAbility" class="btn-add-row">＋ 新規能力を追加</button>
			</div>

			<?php
			$ABILITY_PHASE_OPTS = ['round_start' => 'ラウンド開始', 'hero' => 'ヒーロー', 'movement' => '移動', 'shooting' => '射撃', 'charge' => '突撃', 'combat' => '戦闘', 'end' => '終了', 'deployment' => '配置', 'any' => '全般'];
			$ABILITY_TURN_OPTS = ['your' => '自分のターン', 'opponent' => '相手のターン', 'any' => 'いつでも', 'battle' => 'バトル中'];
			$ABILITY_ACTIVATION_OPTS = ['active' => '能動（アクティブ）', 'passive' => 'パッシブ（常時）', 'reaction' => 'リアクション'];
			$ABILITY_SCOPE_OPTS = ['unlimited' => '無制限', 'once_per_turn' => 'ターンに1回', 'once_per_phase' => 'フェイズに1回', 'once_per_round' => 'ラウンドに1回', 'once_per_battle' => 'バトルに1回'];
			$ABILITY_PER_OPTS = ['unit' => 'ユニット', 'army' => 'アーミー'];
			$ABILITY_ICON_OPTS = ['' => '（なし）', 'Offensive' => 'Offensive', 'Defensive' => 'Defensive', 'Movement' => 'Movement', 'Shooting' => 'Shooting', 'Damage' => 'Damage', 'Control' => 'Control', 'Rallying' => 'Rallying', 'Special' => 'Special'];
			$ABILITY_CASTING_TYPE_OPTS = ['' => '（なし）', 'spell' => '詠唱値', 'prayer' => '祈祷値'];
			$renderAbilityUsageFields = function ($idx, array $a = []) use ($ABILITY_PHASE_OPTS, $ABILITY_TURN_OPTS, $ABILITY_ACTIVATION_OPTS, $ABILITY_SCOPE_OPTS, $ABILITY_PER_OPTS, $ABILITY_ICON_OPTS, $ABILITY_CASTING_TYPE_OPTS) {
				$selectedPhases = array_filter(array_map('trim', explode(',', (string)($a['trigger_phase'] ?? ''))));
				$turn = $a['trigger_turn'] ?? 'your';
				$activation = $a['activation'] ?? 'active';
				$scope = $a['usage_scope'] ?? 'unlimited';
				$per = $a['usage_per'] ?? 'unit';
				$icon = (string)($a['icon_type'] ?? '');
				$castingType = (string)($a['casting_type'] ?? '');
				$castingValue = (string)($a['casting_value'] ?? '');
				ob_start(); ?>
				<div class="form-group">
					<label>発動フェイズ（複数可）</label>
					<select name="abilities[<?= $idx; ?>][trigger_phase][]" multiple size="4">
						<?php foreach ($ABILITY_PHASE_OPTS as $val => $lbl): ?>
							<option value="<?= $val; ?>" <?= in_array($val, $selectedPhases, true) ? 'selected' : ''; ?>><?= $this->h($lbl); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="form-group">
					<label>発動ターン</label>
					<select name="abilities[<?= $idx; ?>][trigger_turn]">
						<?php foreach ($ABILITY_TURN_OPTS as $val => $lbl): ?>
							<option value="<?= $val; ?>" <?= $turn === $val ? 'selected' : ''; ?>><?= $this->h($lbl); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="form-group">
					<label>発動様式</label>
					<select name="abilities[<?= $idx; ?>][activation]">
						<?php foreach ($ABILITY_ACTIVATION_OPTS as $val => $lbl): ?>
							<option value="<?= $val; ?>" <?= $activation === $val ? 'selected' : ''; ?>><?= $this->h($lbl); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="form-group">
					<label>使用回数</label>
					<select name="abilities[<?= $idx; ?>][usage_scope]">
						<?php foreach ($ABILITY_SCOPE_OPTS as $val => $lbl): ?>
							<option value="<?= $val; ?>" <?= $scope === $val ? 'selected' : ''; ?>><?= $this->h($lbl); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="form-group">
					<label>対象（once-per単位）</label>
					<select name="abilities[<?= $idx; ?>][usage_per]">
						<?php foreach ($ABILITY_PER_OPTS as $val => $lbl): ?>
							<option value="<?= $val; ?>" <?= $per === $val ? 'selected' : ''; ?>><?= $this->h($lbl); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="form-group">
					<label>アイコン分類</label>
					<select name="abilities[<?= $idx; ?>][icon_type]">
						<?php foreach ($ABILITY_ICON_OPTS as $val => $lbl): ?>
							<option value="<?= $val; ?>" <?= $icon === $val ? 'selected' : ''; ?>><?= $this->h($lbl); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="form-group">
					<label>詠唱/祈祷</label>
					<select name="abilities[<?= $idx; ?>][casting_type]">
						<?php foreach ($ABILITY_CASTING_TYPE_OPTS as $val => $lbl): ?>
							<option value="<?= $val; ?>" <?= $castingType === $val ? 'selected' : ''; ?>><?= $this->h($lbl); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="form-group">
					<label>詠唱値/祈祷値</label>
					<input type="text" name="abilities[<?= $idx; ?>][casting_value]" value="<?= $this->h($castingValue); ?>" class="cell-narrow" placeholder="例: 7">
				</div>
			<?php
				return ob_get_clean();
			};
			?>

			<div id="abilitiesContainer">
				<?php foreach ($abilityRows as $i => $a): ?>
					<div class="ability-edit-card" data-ability-existing="1">
						<input type="hidden" name="abilities[<?= $i; ?>][ability_id]" value="<?= $this->h($a['id'] ?? ''); ?>">
						<input type="hidden" name="abilities[<?= $i; ?>][_delete]" value="0" class="ability-delete-flag">
						<div class="ability-edit-row">
							<div class="form-group form-group--wide">
								<label>能力名</label>
								<input type="text" name="abilities[<?= $i; ?>][name]" value="<?= $this->h($a['name'] ?? ''); ?>">
							</div>
							<?= $renderAbilityUsageFields($i, $a); ?>
							<div class="form-group">
								<label>コマンドポイント</label>
								<input type="number" min="0" name="abilities[<?= $i; ?>][command_point]" value="<?= $this->h($a['command_point'] ?? ''); ?>" class="cell-narrow">
							</div>
						</div>
						<?php if (!empty($a['trigger_condition_en'])): ?>
							<div class="form-group form-group--wide">
								<label>発動条件（原文）</label>
								<p class="ability-condition-en"><?= $this->h($a['trigger_condition_en']); ?></p>
							</div>
						<?php endif; ?>
						<div class="form-group form-group--wide">
							<label>発動条件（日本語）</label>
							<textarea name="abilities[<?= $i; ?>][trigger_condition_ja]" rows="2" placeholder="例: ターンに1回（アーミー）、リアクション：このユニットが非CORE能力の対象に選ばれたとき"><?= $this->h($a['trigger_condition_ja'] ?? ''); ?></textarea>
						</div>
						<div class="form-group form-group--wide">
							<label>効果</label>
							<textarea name="abilities[<?= $i; ?>][effect]" rows="3"><?= $this->h($a['effect'] ?? ''); ?></textarea>
						</div>
						<div class="form-group form-group--wide">
							<label>フレーバーテキスト</label>
							<textarea name="abilities[<?= $i; ?>][flavor_text]" rows="2"><?= $this->h($a['flavor_text'] ?? ''); ?></textarea>
						</div>
						<div class="form-group form-group--wide">
							<label>キーワード</label>
							<input type="text" name="abilities[<?= $i; ?>][keywords]" value="<?= $this->h($a['keywords'] ?? ''); ?>">
						</div>
						<div class="ability-edit-actions">
							<button type="button" class="btn-remove-ability">このユニットから外す</button>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</section>

		<!-- ============ 連隊オプション ============ -->
		<section class="unit-edit-section unit-regiment-section">
			<h3 class="unit-edit-section-title">連隊オプション (REGIMENT)</h3>
			<p class="unit-edit-note">
				このファクションに登録された連隊オプションとの紐づけを編集します。
				オプション自体の追加はデータベースで行ってください。
				<?php if ($isNew): ?>
					<br>※ 新規作成時はファクション選択後の候補が表示されません。先に保存してから編集画面で設定してください。
				<?php else: ?>
					<br>※ ファクションを変更した場合は一度保存してから連隊オプションを編集してください（候補は現在のファクション基準で表示されます）。
				<?php endif; ?>
			</p>

			<?php if (empty($regimentOptions)): ?>
				<p class="unit-empty">このファクションには連隊オプションが登録されていません（データベースで追加してください）。</p>
			<?php else: ?>

				<div class="regiment-edit-group">
					<h4 class="regiment-edit-subtitle">所属できる連隊 (ELIGIBILITY)</h4>
					<p class="unit-image-hint">このユニットが随伴部隊として所属できる連隊オプションを選択します。</p>
					<div class="regiment-option-checks">
						<?php foreach ($regimentOptions as $opt): ?>
							<?php $oid = (int)$opt['id']; ?>
							<label class="regiment-option-check">
								<input type="checkbox" name="regiment_eligibility[]" value="<?= $this->h($oid); ?>"
									<?= in_array($oid, $eligibilityIds, true) ? 'checked' : ''; ?>>
								<span><?= $this->h($opt['option_name']); ?></span>
							</label>
						<?php endforeach; ?>
					</div>
				</div>

				<?php if ($isHeroUnit): ?>
					<div class="regiment-edit-group hero-regiment-group">
						<h4 class="regiment-edit-subtitle">連隊に編成できるオプション枠 (HERO)</h4>
						<p class="unit-image-hint">この HERO が連隊長として編成できるオプション枠を選択します。上限は 0 で無制限。</p>
						<table class="hero-regiment-table">
							<thead>
								<tr>
									<th>オプション</th>
									<th>編成可</th>
									<th>上限 (0=無制限)</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($regimentOptions as $opt): ?>
									<?php
									$oid = (int)$opt['id'];
									$enabled = array_key_exists($oid, $heroOptionMap);
									$maxLimit = $enabled ? (int)$heroOptionMap[$oid] : 1;
									?>
									<tr>
										<td><?= $this->h($opt['option_name']); ?></td>
										<td class="cell-center">
											<input type="checkbox" name="hero_regiment_options[<?= $oid; ?>][enabled]" value="1"
												<?= $enabled ? 'checked' : ''; ?>>
										</td>
										<td>
											<input type="number" min="0" class="cell-narrow"
												name="hero_regiment_options[<?= $oid; ?>][max_limit]"
												value="<?= $this->h($maxLimit); ?>">
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				<?php else: ?>
					<p class="unit-image-hint">※ このユニットは HERO ではないため、連隊長としての編成枠はありません（HERO に設定すると編集できます）。</p>
				<?php endif; ?>

			<?php endif; ?>
		</section>

		<div class="unit-edit-footer">
			<a href="<?= URL; ?>unit/faction/<?= $this->h($selectedFactionId); ?>" class="btn-cancel">キャンセル</a>
			<button type="submit" class="btn-save-unit">保存する</button>
		</div>
	</form>

	<!-- ============ テンプレート ============ -->
	<template id="weaponRowTemplate">
		<tr class="weapon-row">
			<td><input type="text" name="weapons[__IDX__][name]"></td>
			<td>
				<select name="weapons[__IDX__][type]">
					<option value="melee">近接</option>
					<option value="ranged">射撃</option>
				</select>
			</td>
			<td><input type="number" name="weapons[__IDX__][rng]" class="cell-narrow"></td>
			<td><input type="text" name="weapons[__IDX__][atk]" class="cell-narrow"></td>
			<td><input type="text" name="weapons[__IDX__][hit]" class="cell-narrow"></td>
			<td><input type="text" name="weapons[__IDX__][wnd]" class="cell-narrow"></td>
			<td><input type="text" name="weapons[__IDX__][rnd]" class="cell-narrow"></td>
			<td><input type="text" name="weapons[__IDX__][dmg]" class="cell-narrow"></td>
			<td><input type="text" name="weapons[__IDX__][abilities]"></td>
			<td><button type="button" class="btn-remove-row" title="削除">×</button></td>
		</tr>
	</template>

	<template id="abilityCardTemplate">
		<div class="ability-edit-card" data-ability-existing="0">
			<input type="hidden" name="abilities[__IDX__][ability_id]" value="">
			<input type="hidden" name="abilities[__IDX__][_delete]" value="0" class="ability-delete-flag">
			<div class="ability-edit-row">
				<div class="form-group form-group--wide">
					<label>能力名</label>
					<input type="text" name="abilities[__IDX__][name]">
				</div>
				<?= $renderAbilityUsageFields('__IDX__'); ?>
				<div class="form-group">
					<label>コマンドポイント</label>
					<input type="number" min="0" name="abilities[__IDX__][command_point]" class="cell-narrow">
				</div>
			</div>
			<div class="form-group form-group--wide">
				<label>発動条件（日本語）</label>
				<textarea name="abilities[__IDX__][trigger_condition_ja]" rows="2" placeholder="例: ターンに1回（アーミー）、リアクション：このユニットが非CORE能力の対象に選ばれたとき"></textarea>
			</div>
			<div class="form-group form-group--wide">
				<label>効果</label>
				<textarea name="abilities[__IDX__][effect]" rows="3"></textarea>
			</div>
			<div class="form-group form-group--wide">
				<label>フレーバーテキスト</label>
				<textarea name="abilities[__IDX__][flavor_text]" rows="2"></textarea>
			</div>
			<div class="form-group form-group--wide">
				<label>キーワード</label>
				<input type="text" name="abilities[__IDX__][keywords]">
			</div>
			<div class="ability-edit-actions">
				<button type="button" class="btn-remove-ability">削除</button>
			</div>
		</div>
	</template>

</div>