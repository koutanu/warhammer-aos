<?php
$groups = array_chunk($battleplans ?? [], 6);
?>
<div class="battleplan index">
	<h1 class="battleplan-page-title">バトルプラン</h1>

	<?php if (empty($battleplans)): ?>
		<p class="battleplan-empty">バトルプランが登録されていません。</p>
	<?php else: ?>
		<div class="battleplan-list-container">
			<?php foreach ($groups as $groupIndex => $group): ?>
				<section class="battleplan-group">
					<h2 class="battleplan-group-title">バトルプラン <?= (int)$groupIndex + 1; ?></h2>
					<ul class="battleplan-list">
						<?php foreach ($group as $i => $bp): ?>
							<li>
								<a href="<?= URL; ?>battleplan/detail/<?= (int)$bp['id']; ?>" class="battleplan-list-item">
									<span class="battleplan-list-num"><?= (int)$i + 1; ?></span>
									<span class="battleplan-list-name"><?= $this->h($bp['name']); ?></span>
									<span class="battleplan-list-meta">
										<?= (int)($bp['rounds'] ?? 5); ?> ラウンド
									</span>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				</section>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</div>