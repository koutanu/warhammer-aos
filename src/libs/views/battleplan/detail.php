<?php
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
$turnJaMap = [
	'your' => '自分のターン',
	'opponent' => '相手のターン',
	'any' => 'どちらのターンでも',
	'battle' => 'バトル全体',
];

$normalizePhaseToken = static function ($token) use ($phaseJaMap) {
	$raw = strtolower(trim((string)$token));
	if ($raw === '' || isset($phaseJaMap[$raw])) {
		return $raw !== '' ? $raw : 'any';
	}
	$u = strtoupper($raw);
	if (str_contains($u, 'DEPLOY')) {
		return 'deployment';
	}
	if (str_contains($u, 'ROUND') && str_contains($u, 'START')) {
		return 'round_start';
	}
	if (str_contains($u, 'HERO')) {
		return 'hero';
	}
	if (str_contains($u, 'MOVEMENT') || $u === 'MOVE') {
		return 'movement';
	}
	if (str_contains($u, 'SHOOT')) {
		return 'shooting';
	}
	if (str_contains($u, 'CHARGE')) {
		return 'charge';
	}
	if (str_contains($u, 'COMBAT') || $u === 'FIGHT') {
		return 'combat';
	}
	if (str_contains($u, 'END')) {
		return 'end';
	}
	if (str_contains($u, 'ANY')) {
		return 'any';
	}
	return 'any';
};

$parsePhases = static function ($trigger) use ($normalizePhaseToken) {
	$parts = preg_split('/\s*,\s*/', trim((string)$trigger)) ?: [];
	$out = [];
	foreach ($parts as $part) {
		if ($part === '') {
			continue;
		}
		$norm = $normalizePhaseToken($part);
		if (!in_array($norm, $out, true)) {
			$out[] = $norm;
		}
	}
	return $out !== [] ? $out : ['any'];
};
?>
<div class="battleplan detail">
	<a href="<?= URL; ?>battleplan" class="battleplan-back-link">← バトルプラン一覧へ</a>

	<h1 class="battleplan-page-title"><?= $this->h($battleplan['name'] ?? ''); ?></h1>

	<p class="battleplan-detail-meta">
		<?= (int)($battleplan['rounds'] ?? 5); ?> ラウンド
		<?php if (isset($battleplan['max_vp_per_round'])): ?>
			／ ラウンド上限 VP <?= (int)$battleplan['max_vp_per_round']; ?>
		<?php endif; ?>
	</p>

	<section class="battleplan-abilities">
		<h2 class="battleplan-section-title">アビリティ</h2>

		<?php if (empty($abilities)): ?>
			<p class="battleplan-empty">このバトルプランに登録されたアビリティはありません。</p>
		<?php else: ?>
			<div class="battleplan-card-list">
				<?php foreach ($abilities as $ability): ?>
					<?php
					$phases = $parsePhases($ability['trigger_phase'] ?? '');
					$primaryPhase = $phases[0] ?? 'any';
					$turnKey = strtolower(trim((string)($ability['trigger_turn'] ?? 'your')));
					$turnLabel = $turnJaMap[$turnKey] ?? $turnKey;
					?>
					<div class="battleplan-card battleplan-card--<?= $this->h($primaryPhase); ?>">
						<div class="battleplan-card-head">
							<h3 class="battleplan-card-name"><?= $this->h($ability['name'] ?? ''); ?></h3>
							<?php if ($ability['command_cost'] !== null && $ability['command_cost'] !== ''): ?>
								<span class="battleplan-card-cp"><?= (int)$ability['command_cost']; ?> CP</span>
							<?php endif; ?>
						</div>
						<div class="battleplan-card-badges">
							<?php foreach ($phases as $phase): ?>
								<span class="battleplan-card-badge battleplan-card-badge--<?= $this->h($phase); ?>">
									<?= $this->h($phaseJaMap[$phase] ?? $phase); ?>
								</span>
							<?php endforeach; ?>
							<span class="battleplan-card-badge battleplan-card-badge--turn">
								<?= $this->h($turnLabel); ?>
							</span>
							<?php if (!empty($ability['trigger_condition_ja'])): ?>
								<span class="battleplan-card-badge battleplan-card-badge--condition">
									<?= $this->h($ability['trigger_condition_ja']); ?>
								</span>
							<?php endif; ?>
						</div>
						<?php if (!empty($ability['effect'])): ?>
							<p class="battleplan-card-effect"><?= $this->h($ability['effect']); ?></p>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</section>
</div>
