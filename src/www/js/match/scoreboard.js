/**
 * スコアボード UI コントローラー
 * - VP は ± ボタンで手動増減（ラウンド自動計算は廃止）
 * - ターン切替前 / 次ラウンド / 試合終了前に BT 達成モーダル
 * - ロスター表示をメインに、フェーズ参照はタブ切替
 */
document.addEventListener("DOMContentLoaded", function () {
	const app = document.getElementById("scoreboardApp");
	if (!app) return;

	const matchId = parseInt(app.dataset.matchId, 10);
	const token = app.dataset.token;
	const viewerSlot = parseInt(app.dataset.viewerSlot, 10) || 1;
	const baseUrl = getBaseURL();

	const initialState = JSON.parse(
		document.getElementById("matchInitialState").value || "{}",
	);

	MatchStateManager.init(initialState);

	const syncTimers = { 1: null, 2: null };
	const resourceSyncTimers = { 1: null, 2: null };

	const els = {
		battleplanName: document.getElementById("battleplanName"),
		player1Name: document.getElementById("player1Name"),
		player2Name: document.getElementById("player2Name"),
		player1Faction: document.getElementById("player1Faction"),
		player2Faction: document.getElementById("player2Faction"),
		player1TotalVp: document.getElementById("player1TotalVp"),
		player2TotalVp: document.getElementById("player2TotalVp"),
		vpBar: document.getElementById("vpBar"),
		btnSwitchTurn: document.getElementById("btnSwitchTurn"),
		btnReselectFirstPlayer: document.getElementById("btnReselectFirstPlayer"),
		activeTurnLabel: document.getElementById("activeTurnLabel"),
		sidebarTurn: document.getElementById("sidebarTurn"),
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
		player1Initiative: document.getElementById("player1Initiative"),
		player2Initiative: document.getElementById("player2Initiative"),
		firstPlayerModal: document.getElementById("firstPlayerModal"),
		firstPlayerModalTitle: document.getElementById("firstPlayerModalTitle"),
		firstPlayerModalMessage: document.getElementById("firstPlayerModalMessage"),
		firstPlayerChoice1: document.getElementById("firstPlayerChoice1"),
		firstPlayerChoice2: document.getElementById("firstPlayerChoice2"),
		firstPlayerModalCancel: document.getElementById("firstPlayerModalCancel"),
		resourceBar: document.getElementById("resourceBar"),
		mineCp: document.getElementById("mineCp"),
		mineRageLevel: document.getElementById("mineRageLevel"),
		mineRageDice: document.getElementById("mineRageDice"),
		oppCp: document.getElementById("oppCp"),
		oppRageLevel: document.getElementById("oppRageLevel"),
		oppRageDice: document.getElementById("oppRageDice"),
		resourceOppLabel: document.getElementById("resourceOppLabel"),
	};

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

	MatchBattleTactics.init({ apiPost, viewerSlot });

	render();
	bindEvents();

	document
		.getElementById("btnViewBattleTactics")
		?.addEventListener("click", function () {
			MatchBattleTactics.openViewList();
		});

	window.addEventListener("matchStateUpdated", function () {
		render();
	});

	window.addEventListener("beforeunload", function (e) {
		if (MatchStateManager.isDirty()) {
			e.preventDefault();
			e.returnValue = "";
		}
	});

	let turnPollTimer = null;
	let turnPollInFlight = false;

	function pollTurnState() {
		if (turnPollInFlight || MatchStateManager.isDirty()) return;

		turnPollInFlight = true;
		fetch(baseUrl + "match/getState/" + matchId)
			.then((res) => res.json())
			.then((data) => {
				if (!data.success || !data.state?.game) return;
				if (MatchStateManager.isDirty()) return;

				const local = MatchStateManager.getState()?.game || {};
				const remote = data.state.game;
				const turnChanged =
					local.activePlayer !== remote.activePlayer ||
					local.battleRound !== remote.battleRound ||
					local.firstPlayer !== remote.firstPlayer;

				if (turnChanged) {
					MatchStateManager.applyServerGameSync(
						remote,
						data.state.updatedAt,
					);
				}

				MatchStateManager.applyServerPlayerResources(
					data.state.players,
				);
			})
			.catch(() => {})
			.finally(() => {
				turnPollInFlight = false;
			});
	}

	function startTurnPoll() {
		stopTurnPoll();
		turnPollTimer = setInterval(pollTurnState, 3000);
	}

	function stopTurnPoll() {
		if (turnPollTimer) {
			clearInterval(turnPollTimer);
			turnPollTimer = null;
		}
	}

	startTurnPoll();

	document.addEventListener("visibilitychange", function () {
		if (document.visibilityState === "visible") {
			pollTurnState();
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

		els.resourceBar?.querySelectorAll(".res-step").forEach((btn) => {
			btn.addEventListener("click", function () {
				const slot = parseInt(btn.dataset.player, 10);
				if (slot !== viewerSlot) return;
				const key = btn.dataset.resource;
				const delta = parseInt(btn.dataset.delta, 10) || 0;
				if (!key) return;
				const current = MatchStateManager.getPlayerResource(slot, key);
				MatchStateManager.setPlayerResource(slot, key, current + delta);
				render();
				scheduleResourceSync(slot);
			});
		});

		els.btnSwitchTurn?.addEventListener("click", function () {
			if (els.btnSwitchTurn.disabled) return;
			const state = MatchStateManager.getState();
			if (state.game?.phase === "deployment") return;

			const firstPlayer = state.game?.firstPlayer;
			if (firstPlayer !== 1 && firstPlayer !== 2) return;

			const active = state.game?.activePlayer || 1;
			if (active !== firstPlayer) return;

			const secondPlayer = firstPlayer === 1 ? 2 : 1;

			MatchBattleTactics.openForPlayer(active, function () {
				return setActivePlayer(secondPlayer);
			});
		});

		els.btnNextRound?.addEventListener("click", function () {
			if (els.btnNextRound.disabled) return;
			const state = MatchStateManager.getState();
			const active = state.game?.activePlayer || 1;
			const p1 = MatchStateManager.getPlayer(1) || {};
			const p2 = MatchStateManager.getPlayer(2) || {};

			MatchBattleTactics.openForPlayer(active, function () {
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
		});

		els.btnReselectFirstPlayer?.addEventListener("click", function () {
			if (els.btnReselectFirstPlayer.disabled) return;
			const state = MatchStateManager.getState();
			const phase = getRoundActionPhase(state);
			if (
				phase.isDeployment ||
				!phase.isFirstPlayerTurn ||
				phase.round < 2
			) {
				return;
			}

			const p1 = MatchStateManager.getPlayer(1) || {};
			const p2 = MatchStateManager.getPlayer(2) || {};
			openFirstPlayerModal(
				"先攻を選び直す",
				"このラウンドで先に手番を行うプレイヤーを選び直してください。",
				p1.name || "Player 1",
				p2.name || "Player 2",
				function (slot) {
					setRoundFirstPlayer(slot);
				},
			);
		});

		els.btnCompleteMatch.addEventListener("click", function () {
			showConfirm(
				"試合終了",
				"試合を終了して結果画面へ移動しますか？",
				function () {
					const state = MatchStateManager.getState();
					const active = state.game?.activePlayer || 1;
					MatchBattleTactics.openForPlayer(active, function () {
						return syncComplete();
					});
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
		const opponentSlot = viewerSlot === 1 ? 2 : 1;
		const mine = viewerSlot === 1 ? p1 : p2;
		const opp = opponentSlot === 1 ? p1 : p2;

		els.battleplanName.textContent = state.battleplanName || "-";

		els.player1Name.textContent = p1.name || "Player 1";
		els.player2Name.textContent = p2.name || "Player 2";
		els.player1Faction.textContent = p1.factionName || "";
		els.player2Faction.textContent = p2.factionName || "";

		els.player1TotalVp.textContent = p1.totalVp ?? 0;
		els.player2TotalVp.textContent = p2.totalVp ?? 0;

		if (els.mineCp) els.mineCp.textContent = mine.commandPoints ?? 0;
		if (els.mineRageLevel) els.mineRageLevel.textContent = mine.rageLevel ?? 0;
		if (els.mineRageDice) els.mineRageDice.textContent = mine.rageDice ?? 0;
		if (els.oppCp) els.oppCp.textContent = opp.commandPoints ?? 0;
		if (els.oppRageLevel) els.oppRageLevel.textContent = opp.rageLevel ?? 0;
		if (els.oppRageDice) els.oppRageDice.textContent = opp.rageDice ?? 0;
		if (els.resourceOppLabel) {
			const oppName = opp.name || (opponentSlot === 1 ? "Player 1" : "Player 2");
			els.resourceOppLabel.textContent = `相手（${oppName}）`;
		}

		renderRound(state);
		renderTurn(state);
		renderPriority(state);

		updateAllianceClasses(p1, p2);

		if (window.MatchRosterPanel) {
			window.MatchRosterPanel.refresh(state);
		}
	}

	function getRoundActionPhase(state) {
		const isDeployment = state.game?.phase === "deployment";
		const active = state.game?.activePlayer || 1;
		const firstPlayer = state.game?.firstPlayer;
		const maxRounds = state.maxRounds || 5;
		const round = state.game?.battleRound ?? state.currentRound ?? 1;
		const isFinal = round >= maxRounds;
		const hasPriority = firstPlayer === 1 || firstPlayer === 2;
		const isFirstPlayerTurn = hasPriority && active === firstPlayer;
		const isSecondPlayerTurn = hasPriority && !isFirstPlayerTurn;

		return {
			isDeployment,
			active,
			firstPlayer,
			round,
			maxRounds,
			isFinal,
			hasPriority,
			isFirstPlayerTurn,
			isSecondPlayerTurn,
		};
	}

	function renderRound(state) {
		const phase = getRoundActionPhase(state);

		if (els.currentRoundValue) els.currentRoundValue.textContent = phase.round;
		if (els.maxRoundValue) els.maxRoundValue.textContent = phase.maxRounds;

		if (!els.btnNextRound) return;

		// 後攻のターン中のみ「次のラウンドへ」（ターン切替後）。最終ラウンドは試合終了を使う。
		const showNextRound =
			!phase.isDeployment && phase.isSecondPlayerTurn && !phase.isFinal;
		els.btnNextRound.style.display = showNextRound ? "" : "none";
		els.btnNextRound.disabled = !showNextRound;
		els.btnNextRound.textContent = "次のラウンドへ";
	}

	function renderTurn(state) {
		const phase = getRoundActionPhase(state);
		const activePlayer = MatchStateManager.getPlayer(phase.active) || {};
		const name = activePlayer.name || `Player ${phase.active}`;

		if (els.activeTurnLabel) {
			els.activeTurnLabel.textContent = `${name} のターン`;
		}

		if (els.sidebarTurn) {
			els.sidebarTurn.style.display = phase.isDeployment ? "none" : "";
		}

		const showFirstPlayerActions =
			!phase.isDeployment && phase.isFirstPlayerTurn;

		if (els.btnSwitchTurn) {
			els.btnSwitchTurn.style.display = showFirstPlayerActions ? "" : "none";
			els.btnSwitchTurn.disabled = !showFirstPlayerActions;

			if (showFirstPlayerActions) {
				// 先攻ターン終了 → 後攻へ。viewer が先攻なら「相手のターンへ」、後攻なら「自分のターンへ」
				els.btnSwitchTurn.textContent =
					phase.active === viewerSlot ? "相手のターンへ" : "自分のターンへ";
			}
		}

		if (els.btnReselectFirstPlayer) {
			const showReselect = showFirstPlayerActions && phase.round >= 2;
			els.btnReselectFirstPlayer.style.display = showReselect ? "" : "none";
			els.btnReselectFirstPlayer.disabled = !showReselect;
		}
	}

	function renderPriority(state) {
		const firstPlayer = state.game?.firstPlayer ?? null;
		const p1 = MatchStateManager.getPlayer(1) || {};
		const p2 = MatchStateManager.getPlayer(2) || {};
		applyPriorityBadge(els.player1Priority, 1, firstPlayer);
		applyPriorityBadge(els.player2Priority, 2, firstPlayer);
		applyInitiativeBadge(els.player1Initiative, !!p1.seizedInitiative);
		applyInitiativeBadge(els.player2Initiative, !!p2.seizedInitiative);
	}

	function applyInitiativeBadge(el, seized) {
		if (!el) return;
		if (!seized) {
			el.style.display = "none";
			el.textContent = "";
			return;
		}
		el.style.display = "";
		el.textContent = "イニシアチブ奪取";
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

	function scheduleResourceSync(slot) {
		clearTimeout(resourceSyncTimers[slot]);
		resourceSyncTimers[slot] = setTimeout(() => syncResources(slot), 400);
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

	async function syncResources(slot) {
		try {
			await apiPost("match/setResources", {
				token,
				matchId,
				playerSlot: slot,
				commandPoints: MatchStateManager.getPlayerResource(slot, "commandPoints"),
				rageLevel: MatchStateManager.getPlayerResource(slot, "rageLevel"),
				rageDice: MatchStateManager.getPlayerResource(slot, "rageDice"),
			});
			MatchStateManager.markClean();
		} catch (e) {
			console.error("Resource sync failed", e);
		}
	}

	async function setActivePlayer(playerSlot) {
		try {
			const res = await apiPost("match/setActivePlayer", {
				token,
				matchId,
				playerSlot,
			});
			if (res.success) {
				MatchStateManager.applyServerState(res.state);
			}
		} catch (e) {
			console.error("Set active player failed", e);
			alert("ターンを切り替えできませんでした。時間をおいて再度お試しください。");
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

	async function setRoundFirstPlayer(firstPlayerSlot) {
		try {
			const res = await apiPost("match/setRoundFirstPlayer", {
				token,
				matchId,
				firstPlayerSlot,
			});
			if (res.success) {
				MatchStateManager.applyServerState(res.state);
			}
		} catch (e) {
			console.error("Set round first player failed", e);
			alert("先攻を変更できませんでした。時間をおいて再度お試しください。");
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
