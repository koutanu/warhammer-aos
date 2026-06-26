<?php
// グランドアライアンスの表示順
$allianceOrder = ['Order', 'Chaos', 'Death', 'Destruction'];
$grouped = $factions ?? [];

// 既知の順番を先頭に、未知のアライアンスは後ろへ
$orderedKeys = [];
foreach ($allianceOrder as $a) {
	if (isset($grouped[$a])) {
		$orderedKeys[] = $a;
	}
}
foreach (array_keys($grouped) as $a) {
	if (!in_array($a, $orderedKeys, true)) {
		$orderedKeys[] = $a;
	}
}
?>
<div class="unit index">

	<?php if (empty($grouped)): ?>
		<p>ファクションが登録されていません。</p>
	<?php else: ?>
		<?php foreach ($orderedKeys as $alliance): ?>
			<div class="alliance-section alliance-section--<?= $this->h(strtolower($alliance)); ?>">
				<h1><?= $this->h($alliance); ?></h1>
				<?php foreach ($grouped[$alliance] as $faction): ?>
					<a href="<?= URL; ?>unit/faction/<?= $this->h($faction['id']); ?>" class="faction-item">
						<?= $this->h($faction['name']); ?>
					</a>
				<?php endforeach; ?>
			</div>
		<?php endforeach; ?>
	<?php endif; ?>

</div>
