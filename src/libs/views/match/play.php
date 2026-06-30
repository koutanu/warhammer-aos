<?php
$stateJson = json_encode($initial_state ?? [], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);
$viewerSlot = (int)($viewer_slot ?? 1);
$p2ShareUrl = URL . 'match/play/' . (int)($match_id ?? 0) . '?slot=2';
?>
<div class="match scoreboard" id="scoreboardApp"
	data-match-id="<?= $this->h($match_id); ?>"
	data-token="<?= $this->h($token); ?>"
	data-viewer-slot="<?= $this->h($viewerSlot); ?>">
	<input type="hidden" id="matchInitialState" value="<?= $this->h($stateJson); ?>">

	<div class="scoreboard-layout">
		<aside class="scoreboard-sidebar">
			<div class="sidebar-battleplan">
				<span class="sidebar-label">BATTLEPLAN</span>
				<strong id="battleplanName">-</strong>
			</div>

			<div class="match-mode-tabs">
				<button type="button" id="tabModeRoster" class="match-mode-tab active">ロスター</button>
				<button type="button" id="tabModePhase" class="match-mode-tab">フェイズ</button>
			</div>

			<div class="vp-bar" id="vpBar">
				<div class="vp-player vp-player-1" id="vpPlayer1">
					<div class="vp-player-info">
						<span class="vp-player-slot">PLAYER 1</span>
						<strong class="vp-player-name" id="player1Name">-</strong>
						<span class="vp-player-faction" id="player1Faction">-</span>
						<span class="vp-player-priority" id="player1Priority" style="display:none;"></span>
					</div>
					<div class="vp-counter">
						<button type="button" class="vp-step vp-minus" data-player="1" data-delta="-1" aria-label="VPを減らす">&minus;</button>
						<strong class="vp-value" id="player1TotalVp">0</strong>
						<button type="button" class="vp-step vp-plus" data-player="1" data-delta="1" aria-label="VPを増やす">&plus;</button>
					</div>
				</div>

				<div class="vp-bar-divider">VS</div>

				<div class="vp-player vp-player-2" id="vpPlayer2">
					<div class="vp-player-info">
						<span class="vp-player-slot">PLAYER 2</span>
						<strong class="vp-player-name" id="player2Name">-</strong>
						<span class="vp-player-faction" id="player2Faction">-</span>
						<span class="vp-player-priority" id="player2Priority" style="display:none;"></span>
					</div>
					<div class="vp-counter">
						<button type="button" class="vp-step vp-minus" data-player="2" data-delta="-1" aria-label="VPを減らす">&minus;</button>
						<strong class="vp-value" id="player2TotalVp">0</strong>
						<button type="button" class="vp-step vp-plus" data-player="2" data-delta="1" aria-label="VPを増やす">&plus;</button>
					</div>
				</div>
			</div>

			<div class="sidebar-actions">
				<button type="button" id="btnViewOpponentRoster" class="btn-view-opponent-roster">相手ロスターを確認</button>
				<div class="sidebar-round">
					<span class="sidebar-round-label">ラウンド</span>
					<strong id="currentRoundValue">1</strong> / <span id="maxRoundValue">5</span>
				</div>
				<button type="button" id="btnNextRound" class="btn-next-round">次のラウンドへ</button>
				<button type="button" id="btnCompleteMatch" class="btn-header-end">試合終了</button>
			</div>

			<?php if ($viewerSlot === 1): ?>
				<div id="p2ShareBanner" class="match-p2-share-banner">
					<span>Player 2 用 URL:</span>
					<code id="p2ShareUrl"><?= $this->h($p2ShareUrl); ?></code>
					<button type="button" id="btnCopyP2Url" class="btn-copy-p2-url">コピー</button>
				</div>
			<?php endif; ?>
		</aside>

		<main class="scoreboard-main">
			<section id="deploymentView" class="match-deployment-view" style="display:none;">
				<div class="deployment-header">
					<h3 class="deployment-title">配置ターン</h3>
					<p class="deployment-lead">連隊を配置し、配置フェイズで使えるアビリティを確認してください。準備ができたら「配置完了」を押して最初のラウンドを開始します。</p>
				</div>
				<div class="deployment-body">
					<section class="deployment-section">
						<h4 class="deployment-section-title">配置フェイズで使えるアビリティ（参照）</h4>
						<p id="deploymentAbilityEmpty" class="phase-ability-empty" style="display:none;"></p>
						<div id="deploymentAbilityList" class="phase-ability-list"></div>
					</section>
					<section class="deployment-section">
						<h4 class="deployment-section-title">あなたの連隊</h4>
						<div id="deploymentRegiments" class="deployment-regiments"></div>
					</section>
				</div>
				<footer class="deployment-footer">
					<button type="button" id="btnCompleteDeployment" class="btn-footer btn-footer-primary">配置完了（最初のラウンドを開始）</button>
				</footer>
			</section>

			<section id="rosterView" class="match-roster-view">
				<div class="roster-view-header">
					<h3 id="rosterViewTitle">自分のロスター</h3>
				</div>
				<div class="roster-view-body" id="rosterPanelBody">
					<p class="roster-panel-empty">ロスターが選択されていません。</p>
				</div>
			</section>

			<div id="phasePanel" class="match-phase-panel" style="display:none;">
				<div class="phase-panel-header">
					<div class="phase-turn-tabs">
						<button type="button" id="phaseTurnMy" class="phase-turn-tab active">自分のターン</button>
						<button type="button" id="phaseTurnOpponent" class="phase-turn-tab">相手のターン</button>
						<button type="button" id="btnShowRoundStart" class="btn-show-round-start" style="display:none;">ラウンド開始字に使えるアビリティ</button>
					</div>
					<p id="phaseStatusLine" class="phase-status-line">-</p>
					<div id="phaseStepper" class="phase-stepper"></div>
				</div>
				<div class="phase-panel-body">
					<h4 class="phase-list-title">使えるアビリティ（参照）</h4>
					<p id="phaseAbilityEmpty" class="phase-ability-empty" style="display:none;"></p>
					<div id="phaseAbilityList" class="phase-ability-list"></div>
				</div>
			</div>
		</main>
	</div>

	<div id="firstPlayerModal" class="modal-overlay" style="display:none;">
		<div class="modal-content first-player-modal-content">
			<h3 id="firstPlayerModalTitle">先攻はどちら？</h3>
			<p id="firstPlayerModalMessage" class="first-player-modal-message">このラウンドで先に手番を行うプレイヤーを選んでください。</p>
			<div class="first-player-modal-choices">
				<button type="button" class="btn-first-player" id="firstPlayerChoice1" data-slot="1">Player 1</button>
				<button type="button" class="btn-first-player" id="firstPlayerChoice2" data-slot="2">Player 2</button>
			</div>
			<div class="first-player-modal-actions">
				<button type="button" id="firstPlayerModalCancel" class="btn-secondary">キャンセル</button>
			</div>
		</div>
	</div>

	<div id="roundStartModal" class="modal-overlay" style="display:none;">
		<div class="modal-content round-start-modal-content">
			<h3 id="roundStartTitle">ラウンド開始時に使えるアビリティ</h3>
			<p class="round-start-modal-lead">このラウンドの開始時に確認・使用するアビリティです。</p>
			<div id="roundStartList" class="phase-ability-list"></div>
			<p id="roundStartEmpty" class="phase-ability-empty" style="display:none;"></p>
			<div class="round-start-modal-actions">
				<button type="button" id="roundStartClose" class="btn-submit">確認した</button>
			</div>
		</div>
	</div>

	<div id="confirmModal" class="modal-overlay" style="display:none;">
		<div class="modal-content confirm-modal-content">
			<h3 id="confirmModalTitle">確認</h3>
			<p id="confirmModalMessage"></p>
			<div class="confirm-modal-actions">
				<button type="button" id="confirmModalCancel" class="btn-secondary">キャンセル</button>
				<button type="button" id="confirmModalOk" class="btn-submit">OK</button>
			</div>
		</div>
	</div>

	<div id="unitDetailModal" class="modal match-unit-detail-modal" style="display: none;">
		<div class="modal-content detail-modal-content">
			<h3 id="detailUnitName">ユニット名</h3>
			<div class="detail-modal-body-wrapper">
				<div class="detail-main-info-col">
					<div class="detail-unit-image-wrap">
						<img id="detailUnitImage" src="" alt="" loading="lazy" style="display:none;">
					</div>
					<div class="detail-status-grid">
						<div class="status-box">
							<div class="status-label">移動力</div>
							<div class="status-value" id="detailUnitMove">-</div>
						</div>
						<div class="status-box">
							<div class="status-label">体力</div>
							<div class="status-value" id="detailUnitWounds">-</div>
						</div>
						<div class="status-box">
							<div class="status-label">防御力</div>
							<div class="status-value" id="detailUnitSave">-</div>
						</div>
						<div class="status-box">
							<div class="status-label" id="detailControlLabel">確保力</div>
							<div class="status-value" id="detailUnitControl">-</div>
						</div>
					</div>
				</div>
				<div class="detail-weapons-col">
					<h4>WEAPONS / 武器</h4>
					<div class="table-responsive">
						<table class="detail-weapons-table">
							<thead>
								<tr>
									<th>武器</th>
									<th>射程</th>
									<th>回数</th>
									<th>ヒット</th>
									<th>ウーンズ</th>
									<th>貫通</th>
									<th>ダメージ</th>
								</tr>
							</thead>
							<tbody id="detailWeaponsBody"></tbody>
						</table>
					</div>
					<div class="detail-description-section">
						<h4>KEYWORDS / キーワード</h4>
						<p id="detailUnitKeywords" class="detail-keywords-text">-</p>
					</div>
				</div>
			</div>
			<div class="detail-description-section">
				<h4>ABILITIES / 特殊能力</h4>
				<div id="detailUnitAbilitiesContainer"></div>
			</div>
			<div class="detail-modal-actions">
				<button type="button" id="btnCloseDetailModal" class="btn-close-detail">閉じる</button>
			</div>
		</div>
	</div>
</div>