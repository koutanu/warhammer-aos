<div class="roster list">
	<div class="list-header">
		<h2>ロスター一覧</h2>
		<a href="<?= URL; ?>roster/index" class="btn-submit">新規作成</a>
	</div>

	<?php if (!empty($roster_success)): ?>
		<div class="roster-flash roster-flash-success" role="status"><?= $this->h($roster_success); ?></div>
	<?php endif; ?>

	<?php if (!empty($roster_error)): ?>
		<div class="roster-flash roster-flash-error" role="alert"><?= $this->h($roster_error); ?></div>
	<?php endif; ?>

	<?php if (!empty($rosters)): ?>
		<div class="roster-list-table-wrap">
			<table class="roster-list-table">
				<thead>
					<tr>
						<th>ロスター名</th>
						<th>ファクション</th>
						<th>ポイント</th>
						<th>更新日</th>
						<th></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($rosters as $roster): ?>
						<tr>
							<td><strong><?= $this->h($roster['name']); ?></strong></td>
							<td><?= $this->h($roster['faction_name'] ?? '-'); ?></td>
							<td><?= $this->h($roster['total_points']); ?> pt</td>
							<td><?= $this->h($roster['updated_at'] ?? $roster['created_at'] ?? '-'); ?></td>
							<td class="list-actions">
								<a href="<?= URL; ?>roster/edit/<?= $this->h($roster['id']); ?>" class="btn-edit">編集</a>
								<form action="<?= URL; ?>roster/delete" method="post" class="delete-roster-form" onsubmit="return confirm('「<?= $this->h($roster['name']); ?>」を削除しますか？この操作は取り消せません。');">
									<input type="hidden" name="token" value="<?= $this->h($delete_token); ?>">
									<input type="hidden" name="roster_id" value="<?= $this->h($roster['id']); ?>">
									<button type="submit" class="btn-delete">削除</button>
								</form>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php else: ?>
		<p class="list-empty">保存済みのロスターがありません。<a href="<?= URL; ?>roster/index">新規作成</a>から始めてください。</p>
	<?php endif; ?>
</div>
