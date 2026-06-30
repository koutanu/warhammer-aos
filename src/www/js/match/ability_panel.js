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
			// 使用済みトグルのタップはアコーディオンを開閉しない。
			if (e.target.closest(".ability-used-toggle")) return;
			toggleAbilityCard(e.target.closest(".phase-ability-card"));
		});

		els.abilityList?.addEventListener("keydown", (e) => {
			if (e.key !== "Enter" && e.key !== " ") return;
			const card = e.target.closest(".phase-ability-card.is-expandable");
			if (!card || e.target !== card) return;
			e.preventDefault();
			toggleAbilityCard(card);
		});

		els.abilityList?.addEventListener("click", (e) => {
			const toggle = e.target.closest(".phase-passive-toggle");
			if (!toggle) return;
			const body = toggle.parentElement?.querySelector(".phase-passive-body");
			if (!body) return;
			const open = body.style.display === "flex";
			body.style.display = open ? "none" : "flex";
			toggle.setAttribute("aria-expanded", String(!open));
			const arrow = toggle.querySelector(".phase-passive-arrow");
			if (arrow) arrow.textContent = open ? "▸" : "▾";
		});
	}

	function toggleAbilityCard(card) {
		if (!card) return;
		const box = card.querySelector(".ability-effect-box");
		if (!box) return; // 効果説明のないカードは無反応。
		const open = box.style.display === "block";
		box.style.display = open ? "none" : "block";
		card.classList.toggle("is-open", !open);
		card.setAttribute("aria-expanded", String(!open));
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

	function isPassiveAbility(ab) {
		return MatchPhases.frequencyInfo(ab).kind === "passive";
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

	// 参照ユニットが1体だけのアビリティを同一ユニットでまとめるためのソートキー。
	// 複数ユニット共有・ユニット無しは "" を返し、従来位置にまとめる。
	function unitGroupKey(ab) {
		const names = ab.unitNames?.length
			? ab.unitNames
			: ab.unitName
				? [ab.unitName]
				: [];
		return names.length === 1 ? names[0] : "";
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

		// パッシブ（常時発動）は操作対象リストから切り離し、別セクションにまとめる。
		const passives = filtered.filter((ab) => isPassiveAbility(ab));
		const actives = filtered.filter((ab) => !isPassiveAbility(ab));

		if (!actives.length && !passives.length) {
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
		actives.forEach((ab) => {
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
					// 参照ユニットが1体だけのアビリティを同一ユニットで連続表示
					const aUnit = unitGroupKey(a);
					const bUnit = unitGroupKey(b);
					if (aUnit !== bUnit) return aUnit.localeCompare(bUnit);
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

		if (passives.length) {
			const section = document.createElement("section");
			section.className = "phase-passive-section";

			const toggle = document.createElement("button");
			toggle.type = "button";
			toggle.className = "phase-passive-toggle";
			toggle.setAttribute("aria-expanded", "false");
			toggle.innerHTML = `<span class="phase-passive-arrow">▸</span>常時発動（パッシブ）（${passives.length}）`;
			section.appendChild(toggle);

			const body = document.createElement("div");
			body.className = "phase-passive-body";
			body.style.display = "none";
			passives
				.slice()
				.sort((a, b) => {
					// 参照ユニットが1体だけのアビリティを同一ユニットで連続表示
					const aUnit = unitGroupKey(a);
					const bUnit = unitGroupKey(b);
					if (aUnit !== bUnit) return aUnit.localeCompare(bUnit);
					return (a.name || "").localeCompare(b.name || "");
				})
				.forEach((ab) =>
					body.appendChild(buildAbilityCard(ab, { passive: true })),
				);
			section.appendChild(body);

			els.abilityList.appendChild(section);
		}
	}

	function buildAbilityCard(ab, opts = {}) {
		const isPassive = !!opts.passive;
		const state = MatchStateManager.getState();
		const game = state.game || { usedAbilities: {} };
		const usedMap = game.usedAbilities?.[viewerSlot] || {};
		{
			// パッシブ/回数無制限(unlimited)は使用済み管理対象外なのでトグル/使用済み表現を出さない。
			const tracked = !isPassive && MatchPhases.isUsageTracked(ab);
			const used = tracked && !!usedMap[ab.key]?.used;
			const effectText = String(ab.effect || "").trim();
			const hasEffect = effectText !== "";
			const card = document.createElement("article");
			card.className =
				"phase-ability-card" +
				(used ? " is-used" : "") +
				(isPassive ? " phase-ability-card--passive" : "") +
				(hasEffect ? " is-expandable" : "") +
				(ab.category ? ` cat-${ab.category}` : "");
			card.dataset.abilityKey = ab.key;
			card.dataset.triggerTurn = ab.triggerTurn || "";
			// 効果説明があるカードのみアコーディオン開閉のタップ対象にする。
			if (hasEffect) {
				card.setAttribute("role", "button");
				card.setAttribute("tabindex", "0");
				card.setAttribute("aria-expanded", "false");
			}

			// 呪文/祈祷/顕現は lore 名（unitName=lore_name）をメタに表示しない。
			const loreCategories = ["spell", "prayer", "manifestation"];
			const unitLabel = loreCategories.includes(ab.category)
				? ""
				: formatUnitNames(ab);
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

			// ユニット能力で casting_type が指定されていればそれを優先（詠唱/祈祷）。
			// 伝承(lore)など未指定の場合は従来どおりカテゴリで判定する。
			const castLabel = ab.castingType
				? ab.castingType === "prayer"
					? "祈祷"
					: "詠唱"
				: ab.category === "prayer"
					? "詠唱"
					: "発動";
			const castingValue = formatCastingValue(ab.castingValue);
			const castBadge = castingValue
				? `<span class="ability-cast-badge">${escapeHtml(castLabel)} ${escapeHtml(castingValue)}</span>`
				: "";

			const freq = MatchPhases.frequencyInfo(ab);
			const freqBadge = freq.label
				? `<span class="ability-freq-badge freq-${escapeHtml(freq.kind)}">${escapeHtml(freq.label)}</span>`
				: "";

			const conditionText = String(ab.triggerCondition || "").trim();
			// 発動条件(日本語)があればフェイズ/ターンのバッジより優先してメタに表示する。
			const metaBadges = conditionText
				? `<span class="ability-phase-badge ability-condition-badge">${escapeHtml(conditionText)}</span>`
				: `${phaseLabel ? `<span class="ability-phase-badge">${escapeHtml(phaseLabel)}</span>` : ""}${turnLabel ? `<span class="ability-turn-badge">${escapeHtml(turnLabel)}</span>` : ""}`;

			const usedToggle = tracked
				? `<button type="button" class="ability-used-toggle" aria-pressed="${used}">
						${used ? "使用済み" : "使用する"}
					</button>`
				: "";

			card.innerHTML = `
				<div class="ability-card-head">
					<div class="ability-card-title-block">
						<div class="ability-badge-row">
							<span class="ability-category-badge cat-${escapeHtml(ab.category || "unit")}">${escapeHtml(categoryLabel)}</span>
							${cpBadge}
							${castBadge}
						</div>
						<strong class="ability-name">${escapeHtml(ab.name)}</strong>
					</div>
					${usedToggle}
				</div>
				<div class="ability-card-meta">
					${metaParts.length ? `<span class="ability-source">${escapeHtml(metaParts.join(" / "))}</span>` : ""}
					${metaBadges}
					${freqBadge}
					${used ? '<span class="ability-used-badge">使用済み</span>' : ""}
					${hasEffect ? '<span class="ability-expand-chevron" aria-hidden="true">▾</span>' : ""}
				</div>
				${hasEffect ? `<div class="ability-effect-box" style="display:none;"><p>${escapeHtml(effectText)}</p></div>` : ""}
			`;

			return card;
		}
	}

	function formatCastingValue(value) {
		if (value === null || value === undefined) return "";
		const str = String(value).trim();
		if (str === "") return "";
		// 既に "+" が付いている場合は重複させない。数値のみなら "+" を補う。
		if (/\+$/.test(str)) return str;
		return /^\d+$/.test(str) ? `${str}+` : str;
	}

	function escapeHtml(text) {
		const div = document.createElement("div");
		div.textContent = text;
		return div.innerHTML;
	}
});
