<div class="rule">
	<section class="rule-section">
		<h1 class="rule-section-title">コアルール</h1>
		<div class="rule-card-list">
			<?php foreach ($commonAbilities as $ability): ?>
				<div class="rule-card">
					<h2 class="rule-card-name"><?= $ability['name']; ?></h2>
					<p class="rule-card-effect"><?= $ability['effect']; ?></p>
				</div>
			<?php endforeach; ?>
		</div>
	</section>

	<section class="rule-section">
		<h1 class="rule-section-title">ユニットキーワード</h1>
		<div class="rule-card-list">
			<?php foreach ($coreAbilities as $ability): ?>
				<div class="rule-card">
					<h2 class="rule-card-name"><?= $ability['name']; ?></h2>
					<p class="rule-card-effect"><?= $ability['effect']; ?></p>
				</div>
			<?php endforeach; ?>
		</div>
	</section>
</div>
