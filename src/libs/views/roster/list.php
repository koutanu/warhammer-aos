<div class="roster list">
	<div class="list-header">
		<h2>ロスター一覧</h2>
		<a href="<?= URL; ?>roster/create" class="btn-submit">新規作成</a>
	</div>

	<?php if (!empty($roster_success)): ?>
		<div class="roster-flash roster-flash-success" role="status"><?= $this->h($roster_success); ?></div>
	<?php endif; ?>

	<?php if (!empty($roster_error)): ?>
		<div class="roster-flash roster-flash-error" role="alert"><?= $this->h($roster_error); ?></div>
	<?php endif; ?>

	<?php if (!empty($rosters)): ?>
		<?php
		$rostersByFaction = [];
		foreach ($rosters as $roster) {
			$factionKey = (string)($roster['faction_id'] ?? '');
			if ($factionKey === '') {
				$factionKey = '_none';
			}
			if (!isset($rostersByFaction[$factionKey])) {
				$alliance = trim((string)($roster['grand_alliance'] ?? ''));
				$rostersByFaction[$factionKey] = [
					'name' => trim((string)($roster['faction_name'] ?? '')) !== ''
						? $roster['faction_name']
						: '未分類',
					'alliance' => $alliance !== '' ? $alliance : 'unknown',
					'rosters' => [],
				];
			}
			$rostersByFaction[$factionKey]['rosters'][] = $roster;
		}
		?>
		<div class="roster-list-by-faction">
			<?php foreach ($rostersByFaction as $group): ?>
				<section class="roster-faction-group alliance-section--<?= $this->h(strtolower($group['alliance'])); ?>">
					<h3 class="roster-faction-heading"><?= $this->h($group['name']); ?></h3>
					<?php foreach ($group['rosters'] as $roster): ?>
						<?php $isLocked = !empty($roster['is_locked_in_match']); ?>
						<div class="roster-list-item<?= $isLocked ? ' is-locked' : ''; ?>">
							<div class="roster-list-item-main">
								<strong class="roster-list-item-name">
									<?= $this->h($roster['name']); ?>
									<?php if ($isLocked): ?>
										<span class="roster-lock-badge">試合中</span>
									<?php endif; ?>
								</strong>
								<span class="roster-list-item-meta">
									<?= $this->h($roster['total_points']); ?> pt
									·
									<?= $this->h($roster['updated_at'] ?? $roster['created_at'] ?? '-'); ?>
								</span>
							</div>
							<div class="list-actions">
								<a href="<?= URL; ?>roster/detail/<?= $this->h($roster['id']); ?>" class="btn-detail">詳細</a>
								<?php if ($isLocked): ?>
									<span class="btn-edit is-disabled" title="進行中の試合で使用中のため編集できません" aria-disabled="true">編集</span>
									<span class="btn-delete is-disabled" title="進行中の試合で使用中のため削除できません" aria-disabled="true">削除</span>
								<?php else: ?>
									<a href="<?= URL; ?>roster/edit/<?= $this->h($roster['id']); ?>" class="btn-edit">編集</a>
									<form action="<?= URL; ?>roster/delete" method="post" class="delete-roster-form" onsubmit="return confirm('「<?= $this->h($roster['name']); ?>」を削除しますか？この操作は取り消せません。');">
										<input type="hidden" name="token" value="<?= $this->h($delete_token); ?>">
										<input type="hidden" name="roster_id" value="<?= $this->h($roster['id']); ?>">
										<button type="submit" class="btn-delete">削除</button>
									</form>
								<?php endif; ?>
							</div>
						</div>
					<?php endforeach; ?>
				</section>
			<?php endforeach; ?>
		</div>
	<?php else: ?>
		<p class="list-empty">保存済みのロスターがありません。<a href="<?= URL; ?>roster/create">新規作成</a>から始めてください。</p>
	<?php endif; ?>
</div>
