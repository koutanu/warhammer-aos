<?php
$unit = $unit ?? [];
$faction = $faction ?? null;
$hasStory = trim((string)($unit['story_text'] ?? '')) !== '';
$sourceUrl = trim((string)($unit['story_source_url'] ?? ''));
$factionId = (int)($unit['faction_id'] ?? ($faction['id'] ?? 0));
?>
<div class="unit unit-story">
	<div class="unit-list-header">
		<div class="unit-list-title">
			<?php if ($factionId > 0): ?>
				<a href="<?= URL; ?>unit/faction/<?= $this->h($factionId); ?>" class="unit-back-link">&larr; <?= $this->h($faction['name'] ?? 'ユニット一覧'); ?></a>
			<?php else: ?>
				<a href="<?= URL; ?>unit/index" class="unit-back-link">&larr; 図鑑トップ</a>
			<?php endif; ?>
			<h2><?= $this->h($unit['name'] ?? ''); ?></h2>
			<p class="unit-story-kicker">Story</p>
		</div>
	</div>

	<?php if (!$hasStory): ?>
		<p class="unit-story-empty">このユニットのストーリーはまだ登録されていません。</p>
	<?php else: ?>
		<article class="unit-story-body">
			<?= nl2br($this->h($unit['story_text'])); ?>
		</article>

		<footer class="unit-story-attribution">
			<?php if ($sourceUrl !== ''): ?>
				<p>
					Source:
					<a href="<?= $this->h($sourceUrl); ?>" target="_blank" rel="noopener noreferrer">
						<?= $this->h($sourceUrl); ?>
					</a>
				</p>
			<?php endif; ?>
			<p class="unit-story-license">
				Text adapted from the
				<a href="https://ageofsigmar.fandom.com/wiki/Age_of_Sigmar_Wiki" target="_blank" rel="noopener noreferrer">Age of Sigmar Wiki</a>
				and available under
				<a href="https://www.fandom.com/licensing" target="_blank" rel="noopener noreferrer">CC-BY-SA</a>.
			</p>
		</footer>
	<?php endif; ?>
</div>
