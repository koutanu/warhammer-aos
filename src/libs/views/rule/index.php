<?php
$phaseOrder = ['deployment', 'round_start', 'hero', 'movement', 'shooting', 'charge', 'combat', 'end', 'any'];
$phaseJaMap = [
	'deployment' => '初期配置',
	'round_start' => 'ラウンド開始',
	'hero' => 'ヒーロー',
	'movement' => '移動',
	'shooting' => '射撃',
	'charge' => '突撃',
	'combat' => '戦闘',
	'end' => 'ターン終了',
	'any' => '全般',
];
$normalizePhase = static function ($trigger) {
	$raw = strtoupper(trim((string)$trigger));
	if ($raw === '') {
		return 'any';
	}
	if (str_contains($raw, 'DEPLOY')) {
		return 'deployment';
	}
	if (str_contains($raw, 'ROUND') && str_contains($raw, 'START')) {
		return 'round_start';
	}
	if (str_contains($raw, 'START') && str_contains($raw, 'TURN')) {
		return 'hero';
	}
	if (str_contains($raw, 'HERO')) {
		return 'hero';
	}
	if (str_contains($raw, 'MOVEMENT') || $raw === 'MOVE') {
		return 'movement';
	}
	if (str_contains($raw, 'SHOOT')) {
		return 'shooting';
	}
	if (str_contains($raw, 'CHARGE')) {
		return 'charge';
	}
	if (str_contains($raw, 'COMBAT') || $raw === 'FIGHT') {
		return 'combat';
	}
	if (str_contains($raw, 'END')) {
		return 'end';
	}
	if (str_contains($raw, 'ANY')) {
		return 'any';
	}
	return 'any';
};

$abilitiesByPhase = [];
foreach ($commonAbilities as $ability) {
	$phase = $normalizePhase($ability['trigger_phase'] ?? '');
	$abilitiesByPhase[$phase][] = $ability;
}
?>
<div class="rule">
	<section class="rule-section">
		<h1 class="rule-section-title">コアルール</h1>
		<?php foreach ($phaseOrder as $phase): ?>
			<?php if (empty($abilitiesByPhase[$phase])) continue; ?>
			<div class="rule-phase-group">
				<h2 class="rule-phase-heading">
					<span class="rule-card-badge rule-card-badge--<?= $this->h($phase); ?>"><?= $this->h($phaseJaMap[$phase]); ?></span>
				</h2>
				<div class="rule-card-list">
					<?php foreach ($abilitiesByPhase[$phase] as $ability): ?>
						<div class="rule-card rule-card--<?= $this->h($phase); ?>">
							<div class="rule-card-head">
								<h3 class="rule-card-name"><?= $this->h($ability['name']); ?></h3>
								<?php if (!empty($ability['trigger_condition_ja'])): ?>
									<span class="rule-card-badge rule-card-badge--<?= $this->h($phase); ?>"><?= $this->h($ability['trigger_condition_ja']); ?></span>
								<?php endif; ?>
							</div>
							<p class="rule-card-effect"><?= $this->h($ability['effect']); ?></p>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		<?php endforeach; ?>
	</section>

	<section class="rule-section">
		<h1 class="rule-section-title">ユニットキーワード</h1>
		<div class="rule-card-list">
			<?php foreach ($unitKeywords as $keyword): ?>
				<div class="rule-card">
					<h2 class="rule-card-name"><?= $this->h($keyword['name']); ?></h2>
					<p class="rule-card-effect"><?= $this->h($keyword['effect']); ?></p>
				</div>
			<?php endforeach; ?>
		</div>
	</section>
</div>
