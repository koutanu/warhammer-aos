/**
 * スコアボード UI コントローラー
 * - VP は ± ボタンで手動増減（ラウンド自動計算は廃止）
 * - ロスター表示をメインに、フェーズ参照はタブ切替
 */
document.addEventListener("DOMContentLoaded", function () {
	const app = document.getElementById("scoreboardApp");
	if (!app) return;

	const matchId = parseInt(app.dataset.matchId, 10);
	const token = app.dataset.token;
	const baseUrl = getBaseURL();

	const initialState = JSON.parse(
		document.getElementById("matchInitialState").value || "{}",
	);

	MatchStateManager.init(initialState);

	const syncTimers = { 1: null, 2: null };

	const els = {
		battleplanName: document.getElementById("battleplanName"),
		player1Name: document.getElementById("player1Name"),
		player2Name: document.getElementById("player2Name"),
		player1Faction: document.getElementById("player1Faction"),
		player2Faction: document.getElementById("player2Faction"),
		player1TotalVp: document.getElementById("player1TotalVp"),
		player2TotalVp: document.getElementById("player2TotalVp"),
		vpBar: document.getElementById("vpBar"),
		btnNextRound: document.getElementById("btnNextRound"),
		currentRoundValue: document.getElementById("currentRoundValue"),
		maxRoundValue: document.getElementById("maxRoundValue"),
		btnCompleteMatch: document.getElementById("btnCompleteMatch"),
		confirmModal: document.getElementById("confirmModal"),
		confirmModalTitle: document.getElementById("confirmModalTitle"),
		confirmModalMessage: document.getElementById("confirmModalMessage"),
		confirmModalOk: document.getElementById("confirmModalOk"),
		confirmModalCancel: document.getElementById("confirmModalCancel"),
		player1Priority: document.getElementById("player1Priority"),
		player2Priority: document.getElementById("player2Priority"),
		firstPlayerModal: document.getElementById("firstPlayerModal"),
		firstPlayerModalTitle: document.getElementById("firstPlayerModalTitle"),
		firstPlayerModalMessage: document.getElementById("firstPlayerModalMessage"),
		firstPlayerChoice1: document.getElementById("firstPlayerChoice1"),
		firstPlayerChoice2: document.getElementById("firstPlayerChoice2"),
		firstPlayerModalCancel: document.getElementById("firstPlayerModalCancel"),
	};

	render();
	bindEvents();

	window.addEventListener("matchStateUpdated", function () {
		render();
	});

	window.addEventListener("beforeunload", function (e) {
		if (MatchStateManager.isDirty()) {
			e.preventDefault();
			e.returnValue = "";
		}
	});

	function bindEvents() {
		els.vpBar?.querySelectorAll(".vp-step").forEach((btn) => {
			btn.addEventListener("click", function () {
				const slot = parseInt(btn.dataset.player, 10);
				const delta = parseInt(btn.dataset.delta, 10) || 0;
				const current = MatchStateManager.getPlayerVp(slot);
				MatchStateManager.setPlayerVp(slot, current + delta);
				render();
				scheduleSync(slot);
			});
		});

		els.btnNextRound?.addEventListener("click", function () {
			if (els.btnNextRound.disabled) return;
			const p1 = MatchStateManager.getPlayer(1) || {};
			const p2 = MatchStateManager.getPlayer(2) || {};
			openFirstPlayerModal(
				"次のラウンド: 先攻はどちら？",
				"次のラウンドで先に手番を行うプレイヤーを選んでください。使用済みアビリティはリセットされます。",
				p1.name || "Player 1",
				p2.name || "Player 2",
				function (slot) {
					advanceRound(slot);
				},
			);
		});

		els.btnCompleteMatch.addEventListener("click", function () {
			showConfirm(
				"試合終了",
				"試合を終了して結果画面へ移動しますか？",
				function () {
					syncComplete();
				},
			);
		});

		els.confirmModalCancel.addEventListener("click", hideConfirm);

		els.firstPlayerChoice1?.addEventListener("click", function () {
			selectFirstPlayer(1);
		});
		els.firstPlayerChoice2?.addEventListener("click", function () {
			selectFirstPlayer(2);
		});
		els.firstPlayerModalCancel?.addEventListener("click", hideFirstPlayerModal);
	}

	function render() {
		const state = MatchStateManager.getState();
		const p1 = MatchStateManager.getPlayer(1) || {};
		const p2 = MatchStateManager.getPlayer(2) || {};

		els.battleplanName.textContent = state.battleplanName || "-";

		els.player1Name.textContent = p1.name || "Player 1";
		els.player2Name.textContent = p2.name || "Player 2";
		els.player1Faction.textContent = p1.factionName || "";
		els.player2Faction.textContent = p2.factionName || "";

		els.player1TotalVp.textContent = p1.totalVp ?? 0;
		els.player2TotalVp.textContent = p2.totalVp ?? 0;

		renderRound(state);
		renderPriority(state);

		updateAllianceClasses(p1, p2);

		if (window.MatchRosterPanel) {
			window.MatchRosterPanel.refresh(state);
		}
	}

	function renderRound(state) {
		const maxRounds = state.maxRounds || 5;
		const round = state.game?.battleRound ?? state.currentRound ?? 1;

		if (els.currentRoundValue) els.currentRoundValue.textContent = round;
		if (els.maxRoundValue) els.maxRoundValue.textContent = maxRounds;

		if (els.btnNextRound) {
			const isFinal = round >= maxRounds;
			els.btnNextRound.disabled = isFinal;
			els.btnNextRound.textContent = isFinal ? "最終ラウンド" : "次のラウンドへ";
		}
	}

	function renderPriority(state) {
		const firstPlayer = state.game?.firstPlayer ?? null;
		applyPriorityBadge(els.player1Priority, 1, firstPlayer);
		applyPriorityBadge(els.player2Priority, 2, firstPlayer);
	}

	function applyPriorityBadge(el, slot, firstPlayer) {
		if (!el) return;
		if (firstPlayer !== 1 && firstPlayer !== 2) {
			el.style.display = "none";
			el.textContent = "";
			el.classList.remove("is-first", "is-second");
			return;
		}
		const isFirst = slot === firstPlayer;
		el.style.display = "";
		el.textContent = isFirst ? "先攻" : "後攻";
		el.classList.toggle("is-first", isFirst);
		el.classList.toggle("is-second", !isFirst);
	}

	function updateAllianceClasses(p1, p2) {
		const pane1 = document.getElementById("vpPlayer1");
		const pane2 = document.getElementById("vpPlayer2");
		if (pane1) {
			pane1.className = `vp-player vp-player-1 alliance-${(p1.grandAlliance || "").toLowerCase()}`;
		}
		if (pane2) {
			pane2.className = `vp-player vp-player-2 alliance-${(p2.grandAlliance || "").toLowerCase()}`;
		}
	}

	function scheduleSync(slot) {
		clearTimeout(syncTimers[slot]);
		syncTimers[slot] = setTimeout(() => syncVp(slot), 400);
	}

	async function syncVp(slot) {
		const vp = MatchStateManager.getPlayerVp(slot);
		try {
			await apiPost("match/setVp", {
				token,
				matchId,
				playerSlot: slot,
				vp,
			});
			MatchStateManager.markClean();
		} catch (e) {
			console.error("VP sync failed", e);
		}
	}

	async function advanceRound(firstPlayerSlot) {
		try {
			const res = await apiPost("match/advanceRound", {
				token,
				matchId,
				firstPlayerSlot,
			});
			if (res.success) {
				MatchStateManager.applyServerState(res.state);
			}
		} catch (e) {
			console.error("Advance round failed", e);
			alert("ラウンドを進行できませんでした。時間をおいて再度お試しください。");
		}
	}

	async function syncComplete() {
		try {
			await Promise.all([syncVp(1), syncVp(2)]);
			const res = await apiPost("match/complete", { token, matchId });
			if (res.success) {
				window.location.href = baseUrl + "match/summary/" + matchId;
			}
		} catch (e) {
			console.error("Match completion failed", e);
			alert("試合を終了できませんでした。時間をおいて再度お試しください。");
		}
	}

	async function apiPost(endpoint, body) {
		const res = await fetch(baseUrl + endpoint, {
			method: "POST",
			headers: { "Content-Type": "application/json" },
			body: JSON.stringify(body),
		});
		const data = await res.json();
		if (!res.ok || !data.success) {
			throw new Error(data.message || "API error");
		}
		return data;
	}

	let confirmCallback = null;

	function showConfirm(title, message, onOk) {
		els.confirmModalTitle.textContent = title;
		els.confirmModalMessage.textContent = message;
		els.confirmModal.style.display = "flex";
		window.ModalScroll?.lock("confirmModal");
		confirmCallback = onOk;
		els.confirmModalOk.onclick = function () {
			const cb = confirmCallback;
			hideConfirm();
			if (cb) cb();
		};
	}

	function hideConfirm() {
		els.confirmModal.style.display = "none";
		window.ModalScroll?.unlock("confirmModal");
		confirmCallback = null;
	}

	let firstPlayerCallback = null;

	function openFirstPlayerModal(title, message, p1Name, p2Name, onSelect) {
		if (!els.firstPlayerModal) return;
		if (els.firstPlayerModalTitle) els.firstPlayerModalTitle.textContent = title;
		if (els.firstPlayerModalMessage)
			els.firstPlayerModalMessage.textContent = message;
		if (els.firstPlayerChoice1)
			els.firstPlayerChoice1.textContent = p1Name || "Player 1";
		if (els.firstPlayerChoice2)
			els.firstPlayerChoice2.textContent = p2Name || "Player 2";
		els.firstPlayerModal.style.display = "flex";
		window.ModalScroll?.lock("firstPlayerModal");
		firstPlayerCallback = onSelect;
	}

	function hideFirstPlayerModal() {
		if (!els.firstPlayerModal) return;
		els.firstPlayerModal.style.display = "none";
		window.ModalScroll?.unlock("firstPlayerModal");
		firstPlayerCallback = null;
	}

	function selectFirstPlayer(slot) {
		const cb = firstPlayerCallback;
		hideFirstPlayerModal();
		if (cb) cb(slot);
	}

	// 配置完了など他スクリプトから先攻選択モーダルを呼べるよう公開する
	window.MatchFirstPlayerModal = {
		open: openFirstPlayerModal,
		close: hideFirstPlayerModal,
	};
});
