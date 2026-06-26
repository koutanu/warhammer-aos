<div class="roster index">
	<h2>新規ロスター作成</h2>

	<?php if (!empty($roster_error)): ?>
		<div class="roster-flash roster-flash-error" role="alert"><?= $this->h($roster_error); ?></div>
	<?php endif; ?>

	<form id="rosterForm">
		<div class="form-group">
			<label for="rosterName">ロスター名</label>
			<input type="text" id="rosterName" placeholder="例: 2000pt ガチ編成" required>
		</div>

		<div class="form-group">
			<label>大同盟（Grand Alliance）</label>
			<div class="alliance-selector">
				<button type="button" class="alliance-btn btn-order" data-alliance="Order">ORDER</button>
				<button type="button" class="alliance-btn btn-chaos" data-alliance="Chaos">CHAOS</button>
				<button type="button" class="alliance-btn btn-death" data-alliance="Death">DEATH</button>
				<button type="button" class="alliance-btn btn-destruction" data-alliance="Destruction">DESTRUCTION</button>
			</div>
		</div>

		<div class="form-group">
			<label for="factionSelect">ファクション</label>
			<select id="factionSelect" required disabled>
				<option value="">-- まず大同盟を選択してください --</option>

				<?php if (!empty($roster_data) && is_array($roster_data)): ?>
					<?php foreach ($roster_data as $faction): ?>
						<option value="<?= $this->h($faction['id']); ?>"
							data-alliance="<?= $this->h($faction['grand_alliance']); ?>"
							class="faction-option"
							style="display: none;">
							<?= $this->h($faction['name']); ?>
						</option>
					<?php endforeach; ?>
				<?php endif; ?>
			</select>
		</div>

		<div class="form-group">
			<label for="rosterPoints">ポイント制限（オプション）</label>
			<select id="rosterPoints" name="points">
				<option value="500">500 pt</option>
				<option value="750">750 pt</option>
				<option value="1000" selected>1000 pt</option>
				<option value="1500">1500 pt</option>
				<option value="2000">2000 pt</option>
			</select>
		</div>

		<button type="submit" class="btn-submit">ロスターを作成する</button>
		<p class="index-sub-link"><a href="<?= URL; ?>roster/list">保存済みロスター一覧へ</a></p>
	</form>
</div>