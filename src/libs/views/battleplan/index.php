<div class="battleplan index">
	<h1 class="battleplan-page-title">バトルプラン</h1>

	<?php if (empty($battleplans)): ?>
		<p class="battleplan-empty">バトルプランが登録されていません。</p>
	<?php else: ?>
		<ul class="battleplan-list">
			<?php foreach ($battleplans as $bp): ?>
				<li>
					<a href="<?= URL; ?>battleplan/detail/<?= (int)$bp['id']; ?>" class="battleplan-list-item">
						<span class="battleplan-list-name"><?= $this->h($bp['name']); ?></span>
						<span class="battleplan-list-meta">
							<?= (int)($bp['rounds'] ?? 5); ?> ラウンド
						</span>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
</div>
