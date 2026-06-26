<div class="match setup">
	<h2>マッチプレイ設定</h2>
	<p class="setup-lead">バトルプランとプレイヤー情報を入力して、対戦を開始します。</p>

	<form id="matchSetupForm" action="<?= URL; ?>match/create" method="POST">
		<input type="hidden" name="token" value="<?= $this->h($token); ?>">

		<div class="form-group">
			<label for="battleplanId">バトルプラン (Battleplan)</label>
			<select id="battleplanId" name="battleplan_id" class="form-control" required>
				<option value="">-- バトルプランを選択 --</option>
				<?php if (!empty($battleplans)): ?>
					<?php foreach ($battleplans as $bp): ?>
						<option value="<?= $this->h($bp['id']); ?>">
							<?= $this->h($bp['name']); ?>
						</option>
					<?php endforeach; ?>
				<?php endif; ?>
			</select>
		</div>

		<div class="players-grid">
			<div class="player-setup-card player-a">
				<h3>Player 1</h3>
				<div class="form-group">
					<label for="playerAName">プレイヤー名</label>
					<input type="text" id="playerAName" name="player_a_name" class="form-control"
						placeholder="例: Player A" required maxlength="100">
				</div>
				<div class="form-group">
					<label for="playerAFaction">ファクション</label>
					<select id="playerAFaction" name="player_a_faction_id" class="form-control faction-select" data-roster-target="playerARoster">
						<option value="">-- ファクションを選択 --</option>
						<?php if (!empty($factions)): ?>
							<?php foreach ($factions as $faction): ?>
								<option value="<?= $this->h($faction['id']); ?>"
									data-alliance="<?= $this->h($faction['grand_alliance']); ?>">
									<?= $this->h($faction['name']); ?> (<?= $this->h($faction['grand_alliance']); ?>)
								</option>
							<?php endforeach; ?>
						<?php endif; ?>
					</select>
				</div>
				<div class="form-group">
					<label for="playerARoster">ロスター</label>
					<select id="playerARoster" name="player_a_roster_id" class="form-control roster-select" disabled>
						<option value="">-- ファクションを先に選択 --</option>
					</select>
				</div>
			</div>

			<div class="player-setup-card player-b">
				<h3>Player 2</h3>
				<div class="form-group">
					<label for="playerBName">プレイヤー名</label>
					<input type="text" id="playerBName" name="player_b_name" class="form-control"
						placeholder="例: Player B" required maxlength="100">
				</div>
				<div class="form-group">
					<label for="playerBFaction">ファクション</label>
					<select id="playerBFaction" name="player_b_faction_id" class="form-control faction-select" data-roster-target="playerBRoster">
						<option value="">-- ファクションを選択 --</option>
						<?php if (!empty($factions)): ?>
							<?php foreach ($factions as $faction): ?>
								<option value="<?= $this->h($faction['id']); ?>"
									data-alliance="<?= $this->h($faction['grand_alliance']); ?>">
									<?= $this->h($faction['name']); ?> (<?= $this->h($faction['grand_alliance']); ?>)
								</option>
							<?php endforeach; ?>
						<?php endif; ?>
					</select>
				</div>
				<div class="form-group">
					<label for="playerBRoster">ロスター</label>
					<select id="playerBRoster" name="player_b_roster_id" class="form-control roster-select" disabled>
						<option value="">-- ファクションを先に選択 --</option>
					</select>
				</div>
			</div>
		</div>

		<div class="form-actions">
			<a href="<?= URL; ?>home" class="btn-secondary">戻る</a>
			<button type="submit" class="btn-submit">対戦を開始する</button>
		</div>
	</form>
</div>
