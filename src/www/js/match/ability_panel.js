/**
 * フェーズ別アビリティ参照パネル
 */
document.addEventListener("DOMContentLoaded", () => {
	const app = document.getElementById("scoreboardApp");
	const phasePanel = document.getElementById("phasePanel");
	if (!app || !phasePanel) return;

	const matchId = parseInt(app.dataset.matchId, 10);
	const token = app.dataset.token;
	const viewerSlot = parseInt(app.dataset.viewerSlot, 10) || 1;
	const baseUrl = getBaseURL();

	const els = {
		rosterView: document.getElementById("rosterView"),
		phasePanel,
		tabRoster: document.getElementById("tabModeRoster"),
		tabPhase: document.getElementById("tabModePhase"),
		phaseStatus: document.getElementById("phaseStatusLine"),
		phaseStepper: document.getElementById("phaseStepper"),
		abilityList: document.getElementById("phaseAbilityList"),
		abilityEmpty: document.getElementById("phaseAbilityEmpty"),
		turnMy: document.getElementById("phaseTurnMy"),
		turnOpponent: document.getElementById("phaseTurnOpponent"),
	};

	let gameSyncing = false;
	let gamePollTimer = null;
	let lastServerUpdatedAt = null;
	let phaseModeActive = false;

	// 参照用ローカル state（サーバー poll で上書きしない）
	let viewPhase = "hero";
	let viewTurn = "my";

	bindModeTabs();
	bindPhaseActions();

	const initialState = MatchStateManager.getState?.() || null;
	if (initialState?.updatedAt) {
		lastServerUpdatedAt = initialState.updatedAt;
	}
	if (initialState?.game?.phase && MatchPhases.ORDER.includes(initialState.game.phase)) {
		viewPhase = initialState.game.phase;
	}

	renderPhasePanel();

	window.addEventListener("matchStateUpdated", () => {
		renderPhasePanel();
	});

	document.addEventListener("visibilitychange", () => {
		if (document.visibilityState === "visible" && phaseModeActive) {
			pollGameState();
		}
	});

	function bindModeTabs() {
		els.tabRoster?.addEventListener("click", () => setMode("roster"));
		els.tabPhase?.addEventListener("click", () => setMode("phase"));
	}

	function setMode(mode) {
		const isRoster = mode === "roster";
		phaseModeActive = !isRoster;
		if (els.rosterView) els.rosterView.style.display = isRoster ? "" : "none";
		if (els.phasePanel) els.phasePanel.style.display = isRoster ? "none" : "flex";
		els.tabRoster?.classList.toggle("active", isRoster);
		els.tabPhase?.classList.toggle("active", !isRoster);

		if (phaseModeActive) {
			startGamePoll();
			pollGameState();
		} else {
			stopGamePoll();
		}
	}

	function startGamePoll() {
		stopGamePoll();
		gamePollTimer = setInterval(pollGameState, 15000);
	}

	function stopGamePoll() {
		if (gamePollTimer) {
			clearInterval(gamePollTimer);
			gamePollTimer = null;
		}
	}

	function pollGameState() {
		if (gameSyncing || MatchStateManager.isDirty()) return;

		fetch(baseUrl + "match/getState/" + matchId)
			.then((res) => res.json())
			.then((data) => {
				if (!data.success || !data.state?.game) return;
				const serverUpdated = data.state.updatedAt || "";
				if (lastServerUpdatedAt && serverUpdated === lastServerUpdatedAt) return;
				lastServerUpdatedAt = serverUpdated;
				MatchStateManager.applyServerGameSync(data.state.game, serverUpdated);
			})
			.catch(() => {});
	}

	function bindPhaseActions() {
		els.phaseStepper?.addEventListener("click", (e) => {
			const step = e.target.closest(".phase-step");
			if (!step) return;
			const phase = step.dataset.phase;
			if (!phase || phase === viewPhase) return;
			viewPhase = phase;
			renderPhasePanel();
		});

		els.turnMy?.addEventListener("click", () => {
			if (viewTurn === "my") return;
			viewTurn = "my";
			updateTurnTabs();
			renderPhasePanel();
		});

		els.turnOpponent?.addEventListener("click", () => {
			if (viewTurn === "opponent") return;
			viewTurn = "opponent";
			updateTurnTabs();
			renderPhasePanel();
		});

		els.abilityList?.addEventListener("click", (e) => {
			const toggleBtn = e.target.closest(".ability-used-toggle");
			if (!toggleBtn) return;
			const card = toggleBtn.closest(".phase-ability-card");
			if (!card) return;

			postGameAction("match/toggleAbility", {
				playerSlot: viewerSlot,
				abilityKey: card.dataset.abilityKey,
				phase: viewPhase,
				triggerTurn: card.dataset.triggerTurn || "",
			});
		});

		els.abilityList?.addEventListener("click", (e) => {
			const detailBtn = e.target.closest(".ability-detail-toggle");
			if (!detailBtn) return;
			const box = detailBtn.parentElement?.querySelector(".ability-effect-box");
			if (!box) return;
			const open = box.style.display === "block";
			box.style.display = open ? "none" : "block";
			detailBtn.textContent = open ? "詳細" : "閉じる";
		});
	}

	function updateTurnTabs() {
		els.turnMy?.classList.toggle("active", viewTurn === "my");
		els.turnOpponent?.classList.toggle("active", viewTurn === "opponent");
	}

	function postGameAction(path, payload) {
		if (gameSyncing) return;
		gameSyncing = true;

		fetch(baseUrl + path, {
			method: "POST",
			headers: { "Content-Type": "application/json" },
			body: JSON.stringify({ token, matchId, ...payload }),
		})
			.then((res) => res.json())
			.then((data) => {
				if (!data.success) {
					alert(data.message || "更新に失敗しました。");
					return;
				}
				lastServerUpdatedAt = data.state?.updatedAt || lastServerUpdatedAt;
				MatchStateManager.applyServerState(data.state);
			})
			.catch(() => alert("通信エラーが発生しました。"))
			.finally(() => {
				gameSyncing = false;
			});
	}

	function renderPhasePanel() {
		const state = MatchStateManager.getState();
		const game = state.game || {
			battleRound: 1,
			activePlayer: 1,
			phase: "hero",
			usedAbilities: {},
		};

		const myPlayer = state.players?.find((p) => p.slot === viewerSlot);
		const rosterName =
			myPlayer?.roster?.name || myPlayer?.name || `Player ${viewerSlot}`;
		const isMyTurn = viewTurn === "my";
		const turnLabel = isMyTurn
			? MatchPhases.labelTurnJa("your")
			: MatchPhases.labelTurnJa("opponent");
		const phaseLabel = MatchPhases.labelPhaseJa(viewPhase);

		updateTurnTabs();

		if (els.phaseStatus) {
			els.phaseStatus.textContent = `参照: ${phaseLabel} / ${turnLabel} / 軍: ${rosterName}`;
		}

		renderStepper(viewPhase);
		renderAbilities(state, game, myPlayer, isMyTurn);
	}

	function renderStepper(currentPhase) {
		if (!els.phaseStepper) return;
		els.phaseStepper.innerHTML = "";

		MatchPhases.ORDER.forEach((phase) => {
			const btn = document.createElement("button");
			btn.type = "button";
			btn.className = "phase-step" + (phase === currentPhase ? " active" : "");
			btn.dataset.phase = phase;
			btn.textContent = MatchPhases.label(phase);
			els.phaseStepper.appendChild(btn);
		});
	}

	function commandCostValue(ab) {
		const raw =
			ab.commandCost === 0 || ab.commandCost ? Number(ab.commandCost) : 0;
		return Number.isNaN(raw) ? 0 : raw;
	}

	function formatUnitNames(ab) {
		const names = ab.unitNames?.length
			? ab.unitNames
			: ab.unitName
				? [ab.unitName]
				: [];
		if (!names.length) return "";
		if (names.length === 1) return names[0];
		if (names.length === 2) return names.join(" / ");
		return `${names[0]} 他${names.length - 1}`;
	}

	function renderAbilities(state, game, myPlayer, isMyTurn) {
		if (!els.abilityList) return;

		const deck = myPlayer?.abilitiesDeck || [];
		const usedMap = game.usedAbilities?.[viewerSlot] || {};

		const filtered = deck.filter((ab) => {
			const phaseNorms =
				ab.triggerPhaseNorms ||
				MatchPhases.normalizePhases(ab.triggerPhase);
			const turnNorm =
				ab.triggerTurnNorm ||
				MatchPhases.normalizeTriggerTurn(ab.triggerTurn);
			return (
				MatchPhases.matchesAnyPhase(phaseNorms, viewPhase) &&
				MatchPhases.matchesCurrentTurn(turnNorm, isMyTurn)
			);
		});

		els.abilityList.innerHTML = "";

		if (!myPlayer?.rosterId) {
			if (els.abilityEmpty) {
				els.abilityEmpty.style.display = "block";
				els.abilityEmpty.textContent =
					"ロスターが未選択です。マッチ設定でロスターを選ぶとアビリティが表示されます。";
			}
			return;
		}

		if (!filtered.length) {
			if (els.abilityEmpty) {
				els.abilityEmpty.style.display = "block";
				els.abilityEmpty.textContent = isMyTurn
					? "このフェーズで使えるアビリティはありません。"
					: "相手ターンで使える自分のアビリティはありません。";
			}
			return;
		}

		if (els.abilityEmpty) els.abilityEmpty.style.display = "none";

		const groups = new Map();
		filtered.forEach((ab) => {
			const cat = ab.category || "unit";
			if (!groups.has(cat)) groups.set(cat, []);
			groups.get(cat).push(ab);
		});

		MatchPhases.CATEGORY_ORDER.filter((cat) => groups.has(cat)).forEach(
			(cat) => {
				const items = groups.get(cat).sort((a, b) => {
					const aUsed = usedMap[a.key]?.used ? 1 : 0;
					const bUsed = usedMap[b.key]?.used ? 1 : 0;
					if (aUsed !== bUsed) return aUsed - bUsed;
					// 汎用コマンドはコマンドコスト有無でまとめる（CPありを先）
					if (cat === "common") {
						const aCp = commandCostValue(a);
						const bCp = commandCostValue(b);
						const aHasCp = aCp > 0 ? 0 : 1;
						const bHasCp = bCp > 0 ? 0 : 1;
						if (aHasCp !== bHasCp) return aHasCp - bHasCp;
						if (aCp !== bCp) return aCp - bCp;
					}
					return (a.name || "").localeCompare(b.name || "");
				});

				const groupEl = document.createElement("section");
				groupEl.className = `phase-ability-group cat-${escapeHtml(cat)}`;

				const title = document.createElement("h5");
				title.className = "phase-ability-group-title";
				title.textContent = `${MatchPhases.labelCategoryJa(cat)}（${items.length}）`;
				groupEl.appendChild(title);

				items.forEach((ab) => groupEl.appendChild(buildAbilityCard(ab)));
				els.abilityList.appendChild(groupEl);
			},
		);
	}

	function buildAbilityCard(ab) {
		const state = MatchStateManager.getState();
		const game = state.game || { usedAbilities: {} };
		const usedMap = game.usedAbilities?.[viewerSlot] || {};
		{
			const used = !!usedMap[ab.key]?.used;
			const card = document.createElement("article");
			card.className =
				"phase-ability-card" +
				(used ? " is-used" : "") +
				(ab.category ? ` cat-${ab.category}` : "");
			card.dataset.abilityKey = ab.key;
			card.dataset.triggerTurn = ab.triggerTurn || "";

			const unitLabel = formatUnitNames(ab);
			const categoryLabel = MatchPhases.labelCategoryJa(ab.category);
			const phaseNorms =
				ab.triggerPhaseNorms ||
				MatchPhases.normalizePhases(ab.triggerPhase);
			const phaseLabel = MatchPhases.formatTriggerPhases(
				ab.triggerPhase,
				phaseNorms,
			);
			const turnNorm =
				ab.triggerTurnNorm ||
				MatchPhases.normalizeTriggerTurn(ab.triggerTurn);
			const turnLabel = MatchPhases.formatTriggerTurn(ab.triggerTurn, turnNorm);

			const metaParts = [];
			if (unitLabel) metaParts.push(unitLabel);

			const commandCost =
				ab.commandCost === 0 || ab.commandCost
					? Number(ab.commandCost)
					: null;
			const cpBadge =
				commandCost !== null && !Number.isNaN(commandCost) && commandCost > 0
					? `<span class="ability-cp-badge">CP ${commandCost}</span>`
					: "";

			const freq = MatchPhases.frequencyInfo(ab);
			const freqBadge = freq.label
				? `<span class="ability-freq-badge freq-${escapeHtml(freq.kind)}">${escapeHtml(freq.label)}</span>`
				: "";

			const conditionText = String(ab.triggerCondition || "").trim();
			const conditionBlock = conditionText
				? `<p class="ability-trigger-condition"><span class="ability-trigger-condition-label">発動条件</span>${escapeHtml(conditionText)}</p>`
				: "";

			card.innerHTML = `
				<div class="ability-card-head">
					<div class="ability-card-title-block">
						<span class="ability-category-badge cat-${escapeHtml(ab.category || "unit")}">${escapeHtml(categoryLabel)}</span>
						<strong class="ability-name">${escapeHtml(ab.name)}</strong>
						${cpBadge}
					</div>
					<button type="button" class="ability-used-toggle" aria-pressed="${used}">
						${used ? "使用済み" : "未使用"}
					</button>
				</div>
				<div class="ability-card-meta">
					${metaParts.length ? `<span class="ability-source">${escapeHtml(metaParts.join(" / "))}</span>` : ""}
					${phaseLabel ? `<span class="ability-phase-badge">${escapeHtml(phaseLabel)}</span>` : ""}
					${turnLabel ? `<span class="ability-turn-badge">${escapeHtml(turnLabel)}</span>` : ""}
					${freqBadge}
					${used ? '<span class="ability-used-badge">使用済み</span>' : ""}
				</div>
				<button type="button" class="ability-detail-toggle">詳細</button>
				<div class="ability-effect-box" style="display:none;">
					${conditionBlock}
					<p>${escapeHtml(ab.effect || "")}</p>
				</div>
			`;

			return card;
		}
	}

	function escapeHtml(text) {
		const div = document.createElement("div");
		div.textContent = text;
		return div.innerHTML;
	}
});
