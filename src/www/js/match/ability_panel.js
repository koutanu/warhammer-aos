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
		phaseMovementStrip: document.getElementById("phaseMovementStrip"),
		phaseShootingStrip: document.getElementById("phaseShootingStrip"),
		phaseCombatStrip: document.getElementById("phaseCombatStrip"),
		opponentRosterStrip: document.getElementById("phaseOpponentRosterStrip"),
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

	// renderPhasePanel() より前に初期化（TDZ 回避）
	const ABILITY_CATEGORIES_WITHOUT_UNIT = new Set([
		"common",
		"battleplan",
		"battletrait",
		"formation",
	]);

	bindModeTabs();
	bindPhaseActions();

	const initialState = MatchStateManager.getState?.() || null;
	if (initialState?.updatedAt) {
		lastServerUpdatedAt = initialState.updatedAt;
	}
	if (
		initialState?.game?.phase &&
		MatchPhases.ORDER.includes(initialState.game.phase)
	) {
		viewPhase = initialState.game.phase;
	}

	renderPhasePanel();

	const isDeployment = (initialState?.game?.phase || "") === "deployment";
	if (!isDeployment) {
		setMode("phase");
	}

	window.MatchAbilityPanel = { setMode };

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
		if (els.rosterView)
			els.rosterView.style.display = isRoster ? "" : "none";
		if (els.phasePanel)
			els.phasePanel.style.display = isRoster ? "none" : "flex";
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
		gamePollTimer = setInterval(pollGameState, 3000);
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
				if (
					lastServerUpdatedAt &&
					serverUpdated === lastServerUpdatedAt
				)
					return;
				lastServerUpdatedAt = serverUpdated;
				MatchStateManager.applyServerGameSync(
					data.state.game,
					serverUpdated,
				);
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
			const summonBtn = e.target.closest(".ability-summon-toggle");
			if (summonBtn) {
				const unitKey = summonBtn.dataset.manifestUnitKey || "";
				if (!unitKey) return;
				postGameAction("match/toggleManifestationSummoned", {
					playerSlot: viewerSlot,
					unitKey,
				});
				return;
			}

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
			const thumb = e.target.closest(".ability-unit-thumb");
			if (thumb) {
				e.stopPropagation();
				openUnitDetailFromThumb(thumb);
				return;
			}
			// 使用済み・召喚トグルのタップはアコーディオンを開閉しない。
			if (e.target.closest(".ability-used-toggle")) return;
			if (e.target.closest(".ability-summon-toggle")) return;
			toggleAbilityCard(e.target.closest(".phase-ability-card"));
		});

		els.phaseMovementStrip?.addEventListener("click", (e) => {
			const thumb = e.target.closest(".ability-unit-thumb");
			if (!thumb) return;
			e.stopPropagation();
			openUnitDetailFromThumb(thumb);
		});

		els.phaseShootingStrip?.addEventListener("click", (e) => {
			const thumb = e.target.closest(".ability-unit-thumb");
			if (!thumb) return;
			e.stopPropagation();
			openUnitDetailFromThumb(thumb);
		});

		els.phaseCombatStrip?.addEventListener("click", (e) => {
			const thumb = e.target.closest(".ability-unit-thumb");
			if (!thumb) return;
			e.stopPropagation();
			openUnitDetailFromThumb(thumb);
		});

		els.opponentRosterStrip?.addEventListener("click", (e) => {
			const thumb = e.target.closest(".ability-unit-thumb");
			if (!thumb) return;
			e.stopPropagation();
			openUnitDetailFromThumb(thumb);
		});

		els.abilityList?.addEventListener("keydown", (e) => {
			if (e.key !== "Enter" && e.key !== " ") return;
			const card = e.target.closest(".phase-ability-card.is-expandable");
			if (!card || e.target !== card) return;
			e.preventDefault();
			toggleAbilityCard(card);
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
				lastServerUpdatedAt =
					data.state?.updatedAt || lastServerUpdatedAt;
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
		const opponentSlot = viewerSlot === 1 ? 2 : 1;
		const opponentPlayer = state.players?.find((p) => p.slot === opponentSlot);
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
		renderMovementStrip(viewPhase, myPlayer?.roster || null, game);
		renderShootingStrip(viewPhase, myPlayer?.roster || null, game);
		renderCombatStrip(viewPhase, myPlayer?.roster || null, game);
		renderOpponentRosterStrip(opponentPlayer, game, opponentSlot);
		renderAbilities(state, game, myPlayer, isMyTurn);
	}

	function renderStepper(currentPhase) {
		if (!els.phaseStepper) return;
		els.phaseStepper.innerHTML = "";

		MatchPhases.ORDER.forEach((phase) => {
			const btn = document.createElement("button");
			btn.type = "button";
			btn.className =
				"phase-step" + (phase === currentPhase ? " active" : "");
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

	function collectRosterUnits(roster, destroyedMap = null) {
		const units = [];
		const seenKeys = new Set();

		function pushUnit(unit) {
			if (!unit) return;
			const key =
				unit.instanceKey ||
				(unit.id ? `legacy:${unit.id}` : "");
			if (!key || seenKeys.has(key)) return;
			if (destroyedMap && destroyedMap[key]) return;
			seenKeys.add(key);
			units.push(unit);
		}

		(roster?.regiments || []).forEach((reg) => {
			pushUnit(reg.hero);
			(reg.units || []).forEach(pushUnit);
		});
		pushUnit(roster?.terrain);
		return units;
	}

	function collectOpponentRosterUnits(roster, destroyedMap = null, summonedMap = null) {
		const units = collectRosterUnits(roster, destroyedMap);
		const seenKeys = new Set(
			units.map(
				(unit) =>
					unit.instanceKey ||
					(unit.id ? `legacy:${unit.id}` : ""),
			),
		);

		(roster?.manifestations || []).forEach((unit) => {
			if (!unit) return;
			const key =
				unit.instanceKey ||
				(unit.id ? `legacy:${unit.id}` : "");
			if (!key || seenKeys.has(key)) return;
			if (!summonedMap || !summonedMap[key]) return;
			if (destroyedMap && destroyedMap[key]) return;
			seenKeys.add(key);
			units.push(unit);
		});

		return units;
	}

	function getDestroyedMapForViewer(game) {
		return game?.destroyedUnits?.[viewerSlot] || {};
	}

	function livingRosterUnits(roster, game) {
		return collectOpponentRosterUnits(
			roster,
			getDestroyedMapForViewer(game),
			game?.summonedUnits?.[viewerSlot] || {},
		);
	}

	function isAbilityAvailable(ab, livingUnits) {
		const category = ab.category || "unit";
		if (ABILITY_CATEGORIES_WITHOUT_UNIT.has(category)) {
			return true;
		}
		const linked = resolveAbilityUnits(ab, livingUnits);
		if (
			category === "spell" ||
			category === "prayer" ||
			category === "manifestation"
		) {
			return linked.length > 0;
		}
		const names = ab.unitNames?.length
			? ab.unitNames
			: ab.unitName
				? [ab.unitName]
				: [];
		if (!names.length) {
			return true;
		}
		return linked.length > 0;
	}

	function keywordBaseName(token) {
		const trimmed = String(token || "").trim();
		if (!trimmed) return "";
		const match = trimmed.match(/^(.+?)\s*\([^)]*\)\s*$/u);
		return (match ? match[1] : trimmed).trim();
	}

	function hasKeyword(unit, ...bases) {
		const keywords = String(unit?.keywords || "");
		if (!keywords) return false;
		const normalizedBases = bases.map((b) => b.toUpperCase());
		return keywords.split(/,\s*/).some((token) => {
			const base = keywordBaseName(token).toUpperCase();
			return normalizedBases.includes(base);
		});
	}

	function findKeywordToken(unit, ...bases) {
		const keywords = String(unit?.keywords || "");
		if (!keywords) return "";
		const normalizedBases = bases.map((b) => b.toUpperCase());
		for (const token of keywords.split(/,\s*/)) {
			const trimmed = token.trim();
			if (!trimmed) continue;
			const base = keywordBaseName(trimmed).toUpperCase();
			if (normalizedBases.includes(base)) return trimmed;
		}
		return "";
	}

	function labelBasesForCategory(category) {
		if (category === "spell" || category === "manifestation") {
			return ["魔術師", "WIZARD"];
		}
		if (category === "prayer") {
			return ["神官", "PRIEST"];
		}
		return null;
	}

	function resolveAbilityUnits(ab, unitsOrRoster) {
		const allUnits = Array.isArray(unitsOrRoster)
			? unitsOrRoster
			: collectRosterUnits(unitsOrRoster);
		const category = ab.category || "unit";

		if (category === "spell" || category === "manifestation") {
			return allUnits.filter((u) =>
				hasKeyword(u, "魔術師", "WIZARD"),
			);
		}
		if (category === "prayer") {
			return allUnits.filter((u) => hasKeyword(u, "神官", "PRIEST"));
		}

		const names = ab.unitNames?.length
			? ab.unitNames
			: ab.unitName
				? [ab.unitName]
				: [];
		if (!names.length) return [];

		const byName = new Map();
		allUnits.forEach((u) => {
			if (u.name && !byName.has(u.name)) byName.set(u.name, u);
		});
		return names.map((name) => byName.get(name)).filter(Boolean);
	}

	function resolveManifestUnit(ab, roster) {
		const key = String(ab?.manifestUnitKey || "").trim();
		if (!key || !roster) return null;
		const list = roster.manifestations || [];
		return (
			list.find((m) => String(m.instanceKey || "").trim() === key) || null
		);
	}

	function unitThumbImageHtml(unit) {
		const image = unit.image || "";
		if (image) {
			return `<img src="${escapeAttr(baseUrl + image)}" alt="" loading="lazy">`;
		}
		const initial = (unit.name || "?").trim().charAt(0).toUpperCase();
		return `<span class="ability-unit-thumb-placeholder">${escapeHtml(initial)}</span>`;
	}

	function buildUnitThumbsHtml(units, category) {
		if (!units.length) return "";
		// category === null は顕現本体など「キーワードラベル不要・名前表示」の用途。
		const labelBases =
			category === null ? null : labelBasesForCategory(category);
		const buttons = units
			.map((unit) => {
				const label = labelBases
					? findKeywordToken(unit, ...labelBases)
					: category === null
						? String(unit.name || "")
						: "";
				const labelHtml = label
					? `<span class="ability-unit-thumb-label">${escapeHtml(label)}</span>`
					: "";
				return `<button type="button" class="ability-unit-thumb"
						data-unit-id="${unit.id}"
						data-unit-name="${escapeAttr(unit.name || "")}"
						data-unit-keywords="${escapeAttr(unit.keywords || "")}"
						aria-label="${escapeAttr(unit.name || "ユニット")}">
						<span class="ability-unit-thumb-image">${unitThumbImageHtml(unit)}</span>${labelHtml}
					</button>`;
			})
			.join("");
		return `<div class="ability-unit-thumbs" role="list">${buttons}</div>`;
	}

	function buildThumbsSectionHtml(label, units, category) {
		const thumbs = buildUnitThumbsHtml(units, category);
		if (!thumbs) return "";
		return `<div class="ability-unit-thumbs-section">
			<div class="ability-unit-thumbs-label">${escapeHtml(label)}</div>
			${thumbs}
		</div>`;
	}

	function openUnitDetailFromThumb(thumb) {
		if (!thumb?.dataset?.unitId || !window.RosterUnitDetail) return;
		const slot = parseInt(thumb.dataset.playerSlot, 10);
		window.RosterUnitDetail.show({
			id: parseInt(thumb.dataset.unitId, 10),
			name: thumb.dataset.unitName,
			keywords: thumb.dataset.unitKeywords,
			playerSlot: Number.isFinite(slot) && slot > 0 ? slot : viewerSlot,
		});
	}

	function formatMovement(value) {
		if (value === null || value === undefined) return "-";
		const str = String(value).trim();
		if (str === "") return "-";
		return /"$/.test(str) ? str : `${str}"`;
	}

	function hasMovement(unit) {
		const value = unit?.movement;
		if (value === null || value === undefined) return false;
		const str = String(value).trim();
		if (str === "" || str === "-" || str === "–" || str === "—") return false;
		return true;
	}

	function buildMovementStripHtml(units) {
		if (!units.length) return "";
		const buttons = units
			.map(
				(unit) =>
					`<button type="button" class="phase-movement-unit ability-unit-thumb"
						data-unit-id="${unit.id}"
						data-unit-name="${escapeAttr(unit.name || "")}"
						data-unit-keywords="${escapeAttr(unit.keywords || "")}"
						aria-label="${escapeAttr((unit.name || "ユニット") + " 移動力 " + formatMovement(unit.movement))}">
						<span class="ability-unit-thumb-image">${unitThumbImageHtml(unit)}</span>
						<span class="phase-movement-value">${escapeHtml(formatMovement(unit.movement))}</span>
					</button>`,
			)
			.join("");
		return `<section class="phase-movement-section">
			<h4 class="phase-movement-title">移動力（参照）</h4>
			<div class="phase-movement-units" role="list">${buttons}</div>
		</section>`;
	}

	function renderMovementStrip(viewPhase, roster, game = null) {
		const strip = els.phaseMovementStrip;
		if (!strip) return;

		if (viewPhase !== "movement" || !roster) {
			strip.innerHTML = "";
			strip.style.display = "none";
			strip.hidden = true;
			return;
		}

		const units = livingRosterUnits(roster, game)
			.filter((u) => u.id && hasMovement(u))
			.sort((a, b) => (a.name || "").localeCompare(b.name || ""));

		if (!units.length) {
			strip.innerHTML = "";
			strip.style.display = "none";
			strip.hidden = true;
			return;
		}

		strip.innerHTML = buildMovementStripHtml(units);
		strip.style.display = "";
		strip.hidden = false;
	}

	function buildShootingStripHtml(units) {
		if (!units.length) return "";
		const buttons = units
			.map(
				(unit) =>
					`<button type="button" class="phase-shooting-unit ability-unit-thumb"
						data-unit-id="${unit.id}"
						data-unit-name="${escapeAttr(unit.name || "")}"
						data-unit-keywords="${escapeAttr(unit.keywords || "")}"
						aria-label="${escapeAttr(unit.name || "ユニット")}">
						<span class="ability-unit-thumb-image">${unitThumbImageHtml(unit)}</span>
					</button>`,
			)
			.join("");
		return `<section class="phase-shooting-section">
			<h4 class="phase-shooting-title">射撃可能ユニット（参照）</h4>
			<div class="phase-shooting-units" role="list">${buttons}</div>
		</section>`;
	}

	function renderShootingStrip(viewPhase, roster, game = null) {
		const strip = els.phaseShootingStrip;
		if (!strip) return;

		if (viewPhase !== "shooting" || !roster) {
			strip.innerHTML = "";
			strip.style.display = "none";
			strip.hidden = true;
			return;
		}

		const units = livingRosterUnits(roster, game)
			.filter((u) => u.id && u.hasRangedWeapon)
			.sort((a, b) => (a.name || "").localeCompare(b.name || ""));

		if (!units.length) {
			strip.innerHTML = "";
			strip.style.display = "none";
			strip.hidden = true;
			return;
		}

		strip.innerHTML = buildShootingStripHtml(units);
		strip.style.display = "";
		strip.hidden = false;
	}

	function buildCombatStripHtml(units) {
		if (!units.length) return "";
		const buttons = units
			.map(
				(unit) =>
					`<button type="button" class="phase-combat-unit ability-unit-thumb"
						data-unit-id="${unit.id}"
						data-unit-name="${escapeAttr(unit.name || "")}"
						data-unit-keywords="${escapeAttr(unit.keywords || "")}"
						aria-label="${escapeAttr(unit.name || "ユニット")}">
						<span class="ability-unit-thumb-image">${unitThumbImageHtml(unit)}</span>
					</button>`,
			)
			.join("");
		return `<section class="phase-combat-section">
			<h4 class="phase-combat-title">戦闘可能ユニット（参照）</h4>
			<div class="phase-combat-units" role="list">${buttons}</div>
		</section>`;
	}

	function renderCombatStrip(viewPhase, roster, game = null) {
		const strip = els.phaseCombatStrip;
		if (!strip) return;

		if (viewPhase !== "combat" || !roster) {
			strip.innerHTML = "";
			strip.style.display = "none";
			strip.hidden = true;
			return;
		}

		const units = livingRosterUnits(roster, game)
			.filter((u) => u.id && u.hasWeapon)
			.sort((a, b) => (a.name || "").localeCompare(b.name || ""));

		if (!units.length) {
			strip.innerHTML = "";
			strip.style.display = "none";
			strip.hidden = true;
			return;
		}

		strip.innerHTML = buildCombatStripHtml(units);
		strip.style.display = "";
		strip.hidden = false;
	}

	function buildOpponentRosterStripHtml(units, playerSlot) {
		if (!units.length) return "";
		const buttons = units
			.map(
				(unit) =>
					`<button type="button" class="phase-opponent-unit ability-unit-thumb"
						data-unit-id="${unit.id}"
						data-unit-name="${escapeAttr(unit.name || "")}"
						data-unit-keywords="${escapeAttr(unit.keywords || "")}"
						data-player-slot="${playerSlot}"
						aria-label="${escapeAttr(unit.name || "ユニット")}">
						<span class="ability-unit-thumb-image">${unitThumbImageHtml(unit)}</span>
					</button>`,
			)
			.join("");
		return `<section class="phase-opponent-roster-section">
			<h4 class="phase-opponent-roster-title">相手ロスター</h4>
			<div class="phase-opponent-units" role="list">${buttons}</div>
		</section>`;
	}

	function renderOpponentRosterStrip(opponentPlayer, game, opponentSlot) {
		const strip = els.opponentRosterStrip;
		if (!strip) return;

		const roster = opponentPlayer?.roster || null;
		if (!roster) {
			strip.innerHTML = "";
			strip.hidden = true;
			return;
		}

		const destroyedMap = game?.destroyedUnits?.[opponentSlot] || {};
		const summonedMap = game?.summonedUnits?.[opponentSlot] || {};
		const units = collectOpponentRosterUnits(
			roster,
			destroyedMap,
			summonedMap,
		)
			.filter((u) => u.id)
			.sort((a, b) => (a.name || "").localeCompare(b.name || ""));

		if (!units.length) {
			strip.innerHTML = "";
			strip.hidden = true;
			return;
		}

		strip.innerHTML = buildOpponentRosterStripHtml(units, opponentSlot);
		strip.hidden = false;
	}

	function renderAbilities(state, game, myPlayer, isMyTurn) {
		if (!els.abilityList) return;

		const deck = myPlayer?.abilitiesDeck || [];
		const usedMap = game.usedAbilities?.[viewerSlot] || {};
		const livingUnits = livingRosterUnits(myPlayer?.roster || null, game);

		const filtered = deck.filter((ab) => {
			const phaseNorms =
				ab.triggerPhaseNorms ||
				MatchPhases.normalizePhases(ab.triggerPhase);
			const turnNorm =
				ab.triggerTurnNorm ||
				MatchPhases.normalizeTriggerTurn(ab.triggerTurn);
			return (
				MatchPhases.matchesAnyPhase(phaseNorms, viewPhase) &&
				MatchPhases.matchesCurrentTurn(turnNorm, isMyTurn) &&
				isAbilityAvailable(ab, livingUnits)
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

		const orderedCategories = [
			...MatchPhases.CATEGORY_ORDER.filter((cat) => groups.has(cat)),
			...[...groups.keys()].filter(
				(cat) => !MatchPhases.CATEGORY_ORDER.includes(cat),
			),
		];

		orderedCategories.forEach((cat) => {
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
				const groupLabel =
					cat === "season_enhancement" && items[0]?.source
						? items[0].source
						: MatchPhases.labelCategoryJa(cat);
				title.textContent = `${groupLabel}（${items.length}）`;
				groupEl.appendChild(title);

				items.forEach((ab) =>
					groupEl.appendChild(buildAbilityCard(ab, {}, livingUnits)),
				);
				els.abilityList.appendChild(groupEl);
			});

		if (passives.length) {
			const section = document.createElement("section");
			section.className = "phase-ability-group phase-passive-section";

			const title = document.createElement("h5");
			title.className = "phase-ability-group-title";
			title.textContent = `常時発動（パッシブ）（${passives.length}）`;
			section.appendChild(title);

			const grid = document.createElement("div");
			grid.className = "phase-passive-grid";

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
					grid.appendChild(
						buildAbilityCard(ab, { passive: true }, livingUnits),
					),
				);

			section.appendChild(grid);
			els.abilityList.appendChild(section);
		}
	}

	function buildAbilityCard(ab, opts = {}, livingUnits = []) {
		const isPassive = !!opts.passive;
		const state = MatchStateManager.getState();
		const game = state.game || { usedAbilities: {}, summonedUnits: {} };
		const usedMap = game.usedAbilities?.[viewerSlot] || {};
		const summonedMap = game.summonedUnits?.[viewerSlot] || {};
		{
			// パッシブ/回数無制限(unlimited)は使用済み管理対象外なのでトグル/使用済み表現を出さない。
			const tracked = !isPassive && MatchPhases.isUsageTracked(ab);
			const used = tracked && !!usedMap[ab.key]?.used;
			const manifestUnitKey = String(ab.manifestUnitKey || "").trim();
			const summoned =
				ab.category === "manifestation" &&
				manifestUnitKey !== "" &&
				!!summonedMap[manifestUnitKey];
			const effectText = String(ab.effect || "").trim();
			const hasEffect = effectText !== "";
			const card = document.createElement("article");
			card.className =
				"phase-ability-card" +
				(used ? " is-used" : "") +
				(summoned ? " is-summoned" : "") +
				(isPassive ? " phase-ability-card--passive" : "") +
				(hasEffect ? " is-expandable" : "") +
				(ab.category ? ` cat-${ab.category}` : "");
			card.dataset.abilityKey = ab.key;
			card.dataset.triggerTurn = ab.triggerTurn || "";
			if (manifestUnitKey) {
				card.dataset.manifestUnitKey = manifestUnitKey;
			}
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
			const categoryLabel =
				ab.category === "season_enhancement" && ab.source
					? ab.source
					: MatchPhases.labelCategoryJa(ab.category);
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
			const turnLabel = MatchPhases.formatTriggerTurn(
				ab.triggerTurn,
				turnNorm,
			);

			const metaParts = [];
			if (unitLabel) metaParts.push(unitLabel);

			const commandCost =
				ab.commandCost === 0 || ab.commandCost
					? Number(ab.commandCost)
					: null;
			const cpBadge =
				commandCost !== null &&
				!Number.isNaN(commandCost) &&
				commandCost > 0
					? `<span class="ability-cp-badge">CP ${commandCost}</span>`
					: "";

			// ユニット能力で casting_type が指定されていればそれを優先（詠唱/祈祷）。
			// 伝承(lore)など未指定の場合は従来どおりカテゴリで判定する。
			const castLabel = ab.castingType
				? ab.castingType === "prayer"
					? "奇蹟"
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

			const summonToggle =
				ab.category === "manifestation" && manifestUnitKey
					? `<button type="button" class="ability-summon-toggle${summoned ? " is-summoned" : ""}" data-manifest-unit-key="${escapeAttr(manifestUnitKey)}" aria-pressed="${summoned}">
						${summoned ? "召喚中" : "召喚成功"}
					</button>`
					: "";

			const abilityUnits = hasEffect
				? resolveAbilityUnits(ab, livingUnits)
				: [];
			let unitThumbsHtml = "";
			if (hasEffect && ab.category === "manifestation") {
				const myPlayer =
					MatchStateManager.getPlayer?.(viewerSlot) ||
					(state.players || []).find((p) => p.slot === viewerSlot) ||
					null;
				const manifest = resolveManifestUnit(ab, myPlayer?.roster || null);
				const sections = [];
				if (manifest) {
					sections.push(
						buildThumbsSectionHtml("召喚する顕現", [manifest], null),
					);
				}
				if (abilityUnits.length) {
					sections.push(
						buildThumbsSectionHtml(
							"詠唱可能な魔術師",
							abilityUnits,
							ab.category,
						),
					);
				}
				unitThumbsHtml = sections.length
					? `<div class="ability-unit-thumbs-row">${sections.join("")}</div>`
					: "";
			} else if (hasEffect) {
				unitThumbsHtml = buildUnitThumbsHtml(abilityUnits, ab.category);
			}

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
					<div class="ability-card-actions">${usedToggle}${summonToggle}</div>
				</div>
				<div class="ability-card-meta">
					${metaParts.length ? `<span class="ability-source">${escapeHtml(metaParts.join(" / "))}</span>` : ""}
					${metaBadges}
					${freqBadge}
					${used ? '<span class="ability-used-badge">使用済み</span>' : ""}
					${summoned ? '<span class="ability-summoned-badge">召喚中</span>' : ""}
					${hasEffect ? '<span class="ability-expand-chevron" aria-hidden="true">▾</span>' : ""}
				</div>
				${hasEffect ? `<div class="ability-effect-box" style="display:none;"><p>${escapeHtml(effectText)}</p>${unitThumbsHtml}</div>` : ""}
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

	function escapeAttr(str) {
		return String(str || "")
			.replace(/&/g, "&amp;")
			.replace(/"/g, "&quot;")
			.replace(/</g, "&lt;");
	}
});
