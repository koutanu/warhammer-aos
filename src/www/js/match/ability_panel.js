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
		opponentRosterStrip: document.getElementById(
			"phaseOpponentRosterStrip",
		),
		turnMy: document.getElementById("phaseTurnMy"),
		turnOpponent: document.getElementById("phaseTurnOpponent"),
		targetPickerModal: document.getElementById("abilityTargetPickerModal"),
		targetPickerList: document.getElementById("abilityTargetPickerList"),
		targetPickerEmpty: document.getElementById("abilityTargetPickerEmpty"),
		targetPickerCancel: document.getElementById(
			"abilityTargetPickerCancel",
		),
		targetPickerLead: document.querySelector(
			"#abilityTargetPickerModal .ability-target-picker-lead",
		),
	};

	let gameSyncing = false;
	let gamePollTimer = null;
	let lastServerUpdatedAt = null;
	let phaseModeActive = false;
	let pendingTargetPick = null;

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

	window.MatchAbilityPanel = { setMode, getViewPhase, getLivingUnitsForSlot };

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
				const card = summonBtn.closest(".phase-ability-card");
				const unitKey = summonBtn.dataset.manifestUnitKey || "";
				if (!unitKey) return;
				const summoned =
					summonBtn.getAttribute("aria-pressed") === "true";
				const needsTarget =
					!summoned &&
					(card?.dataset.targetsUnitOnSummon === "1" ||
						card?.dataset.targetsUnitOnSummon === "true");
				if (needsTarget) {
					openManifestationTargetPicker(unitKey);
					return;
				}
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

			const abilityKey = card.dataset.abilityKey || "";
			const triggerTurn = card.dataset.triggerTurn || "";
			const behaviorId = card.dataset.behaviorId || "";
			const isUsed = toggleBtn.getAttribute("aria-pressed") === "true";

			if (!isUsed && behaviorId === "pick_unit_once_per_battle") {
				openAbilityTargetPicker({
					abilityKey,
					triggerTurn,
					phase: viewPhase,
				});
				return;
			}

			postGameAction("match/toggleAbility", {
				playerSlot: viewerSlot,
				abilityKey,
				phase: viewPhase,
				triggerTurn,
			});
		});

		els.targetPickerCancel?.addEventListener("click", () => {
			closeAbilityTargetPicker();
		});
		els.targetPickerModal?.addEventListener("click", (e) => {
			if (e.target === els.targetPickerModal) {
				closeAbilityTargetPicker();
			}
		});
		els.targetPickerList?.addEventListener("click", (e) => {
			const thumb = e.target.closest(
				".ability-unit-thumb[data-unit-key]",
			);
			if (!thumb || !pendingTargetPick) return;
			const unitKey = thumb.dataset.unitKey || "";
			if (!unitKey) return;

			if (pendingTargetPick.mode === "manifestationSummon") {
				const manifestUnitKey = pendingTargetPick.manifestUnitKey || "";
				closeAbilityTargetPicker();
				if (!manifestUnitKey) return;
				postGameAction("match/toggleManifestationSummoned", {
					playerSlot: viewerSlot,
					unitKey: manifestUnitKey,
					targetUnitKey: unitKey,
				});
				return;
			}

			const payload = {
				playerSlot: viewerSlot,
				abilityKey: pendingTargetPick.abilityKey,
				phase: pendingTargetPick.phase,
				triggerTurn: pendingTargetPick.triggerTurn || "",
				unitKey,
			};
			closeAbilityTargetPicker();
			postGameAction("match/toggleAbility", payload);
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
			handlePhaseStripClick(e);
		});

		els.phaseShootingStrip?.addEventListener("click", (e) => {
			handlePhaseStripClick(e);
		});

		els.phaseCombatStrip?.addEventListener("click", (e) => {
			handlePhaseStripClick(e);
		});

		els.opponentRosterStrip?.addEventListener("click", (e) => {
			handlePhaseStripClick(e);
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

	function handlePhaseStripClick(e) {
		const toggle = e.target.closest(".phase-action-toggle");
		if (toggle) {
			e.stopPropagation();
			const unitKey = toggle.dataset.unitKey || "";
			const flag = toggle.dataset.flag || "";
			const playerSlot = parseInt(toggle.dataset.playerSlot, 10);
			if (!unitKey || !flag || !Number.isFinite(playerSlot)) return;
			postGameAction("match/toggleUnitPhaseFlag", {
				playerSlot,
				viewerSlot,
				unitKey,
				flag,
			});
			return;
		}
		const thumb = e.target.closest(".ability-unit-thumb");
		if (!thumb) return;
		e.stopPropagation();
		openUnitDetailFromThumb(thumb);
	}

	function unitInstanceKey(unit) {
		return String(unit?.instanceKey || "").trim();
	}

	function isRosterCombatUnitKey(key) {
		return key.startsWith("hero:") || key.startsWith("unit:");
	}

	function getAbilityTargetKeys(game, abilityKey) {
		const list = game?.abilityTargetUnits?.[viewerSlot]?.[abilityKey];
		return Array.isArray(list) ? list.map(String) : [];
	}

	function collectPickableUnits(roster, game, abilityKey) {
		const used = new Set(getAbilityTargetKeys(game, abilityKey));
		return livingRosterUnits(roster, game).filter((unit) => {
			const key = unitInstanceKey(unit);
			if (!key || !isRosterCombatUnitKey(key)) return false;
			return !used.has(key);
		});
	}

	function collectSummonTargetUnits(roster, game) {
		return livingRosterUnits(roster, game).filter((unit) => {
			const key = unitInstanceKey(unit);
			return key && isRosterCombatUnitKey(key);
		});
	}

	function resolveUnitsByKeys(roster, keys) {
		if (!keys?.length) return [];
		const byKey = new Map();
		collectRosterUnits(roster, null).forEach((unit) => {
			const key = unitInstanceKey(unit);
			if (key) byKey.set(key, unit);
		});
		return keys.map((key) => byKey.get(String(key))).filter(Boolean);
	}

	function setTargetPickerLead(text) {
		if (els.targetPickerLead) {
			els.targetPickerLead.textContent = text;
		}
	}

	function openAbilityTargetPicker({ abilityKey, triggerTurn, phase }) {
		const state = MatchStateManager.getState();
		const game = state.game || {};
		const myPlayer =
			MatchStateManager.getPlayer?.(viewerSlot) ||
			(state.players || []).find((p) => p.slot === viewerSlot) ||
			null;
		const candidates = collectPickableUnits(
			myPlayer?.roster || null,
			game,
			abilityKey,
		);

		pendingTargetPick = { mode: "ability", abilityKey, triggerTurn, phase };
		setTargetPickerLead(
			"このバトルでまだ使用していないユニットを選んでください。",
		);
		showTargetPicker(candidates);
	}

	function openManifestationTargetPicker(manifestUnitKey) {
		const state = MatchStateManager.getState();
		const game = state.game || {};
		const myPlayer =
			MatchStateManager.getPlayer?.(viewerSlot) ||
			(state.players || []).find((p) => p.slot === viewerSlot) ||
			null;
		const candidates = collectSummonTargetUnits(
			myPlayer?.roster || null,
			game,
		);

		pendingTargetPick = {
			mode: "manifestationSummon",
			manifestUnitKey,
		};
		setTargetPickerLead("効果を与えるユニットを選んでください。");
		showTargetPicker(candidates);
	}

	function showTargetPicker(candidates) {
		if (els.targetPickerList) {
			els.targetPickerList.innerHTML = candidates.length
				? buildUnitThumbsHtml(candidates, null, {
						selectable: true,
						showName: true,
					})
				: "";
		}
		if (els.targetPickerEmpty) {
			els.targetPickerEmpty.style.display = candidates.length
				? "none"
				: "flex";
		}
		if (els.targetPickerModal) {
			els.targetPickerModal.style.display = "flex";
		}
	}

	function closeAbilityTargetPicker() {
		pendingTargetPick = null;
		if (els.targetPickerModal) {
			els.targetPickerModal.style.display = "none";
		}
		if (els.targetPickerList) {
			els.targetPickerList.innerHTML = "";
		}
		setTargetPickerLead(
			"このバトルでまだ使用していないユニットを選んでください。",
		);
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
		const opponentPlayer = state.players?.find(
			(p) => p.slot === opponentSlot,
		);
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
		renderOpponentRosterStrip(
			opponentPlayer,
			game,
			opponentSlot,
			viewPhase,
		);
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
				unit.instanceKey || (unit.id ? `legacy:${unit.id}` : "");
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

	function collectOpponentRosterUnits(
		roster,
		destroyedMap = null,
		summonedMap = null,
	) {
		const units = collectRosterUnits(roster, destroyedMap);
		const seenKeys = new Set(
			units.map(
				(unit) =>
					unit.instanceKey || (unit.id ? `legacy:${unit.id}` : ""),
			),
		);

		(roster?.manifestations || []).forEach((unit) => {
			if (!unit) return;
			const key =
				unit.instanceKey || (unit.id ? `legacy:${unit.id}` : "");
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

	function getViewPhase() {
		return viewPhase;
	}

	function getLivingUnitsForSlot(slot) {
		const slotNum = parseInt(slot, 10);
		if (!Number.isFinite(slotNum) || slotNum < 1) return [];
		const state = MatchStateManager.getState() || {};
		const game = state.game || {};
		const player =
			MatchStateManager.getPlayer?.(slotNum) ||
			(state.players || []).find((p) => p.slot === slotNum) ||
			null;
		return collectOpponentRosterUnits(
			player?.roster || null,
			game?.destroyedUnits?.[slotNum] || {},
			game?.summonedUnits?.[slotNum] || {},
		).filter((unit) => unit.id);
	}

	function allRosterUnits(roster, game) {
		return collectOpponentRosterUnits(
			roster,
			null,
			game?.summonedUnits?.[viewerSlot] || {},
		);
	}

	function unitsForAbility(ab, livingUnits, allUnits) {
		return ab.usableWhenDestroyed ? allUnits : livingUnits;
	}

	function isAbilityAvailable(ab, livingUnits, allUnits = []) {
		const category = ab.category || "unit";
		if (ABILITY_CATEGORIES_WITHOUT_UNIT.has(category)) {
			return true;
		}
		const linked = resolveAbilityUnits(
			ab,
			unitsForAbility(ab, livingUnits, allUnits),
		);
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
			return allUnits.filter((u) => hasKeyword(u, "魔術師", "WIZARD"));
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

	function buildUnitThumbsHtml(units, category, opts = {}) {
		if (!units.length) return "";
		// category === null は顕現本体など「キーワードラベル不要・名前表示」の用途。
		const labelBases =
			category === null ? null : labelBasesForCategory(category);
		const showName = !!opts.showName || category === null;
		const markUsed = !!opts.markUsed;
		const selectable = !!opts.selectable;
		const buttons = units
			.map((unit) => {
				const key = unitInstanceKey(unit);
				const label = labelBases
					? findKeywordToken(unit, ...labelBases)
					: showName
						? String(unit.name || "")
						: "";
				const labelHtml = label
					? `<span class="ability-unit-thumb-label">${escapeHtml(label)}</span>`
					: "";
				const usedBadge = markUsed
					? `<span class="ability-unit-thumb-used-badge">使用済み</span>`
					: "";
				const classes = [
					"ability-unit-thumb",
					markUsed ? "is-ability-target-used" : "",
					selectable ? "is-selectable" : "",
				]
					.filter(Boolean)
					.join(" ");
				return `<button type="button" class="${classes}"
						data-unit-id="${unit.id}"
						data-unit-key="${escapeAttr(key)}"
						data-unit-name="${escapeAttr(unit.name || "")}"
						data-unit-keywords="${escapeAttr(unit.keywords || "")}"
						aria-label="${escapeAttr(unit.name || "ユニット")}">
						<span class="ability-unit-thumb-image">${unitThumbImageHtml(unit)}</span>${labelHtml}${usedBadge}
					</button>`;
			})
			.join("");
		return `<div class="ability-unit-thumbs" role="list">${buttons}</div>`;
	}

	function buildThumbsSectionHtml(label, units, category, opts = {}) {
		const thumbs = buildUnitThumbsHtml(units, category, opts);
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
			instanceKey: thumb.dataset.unitKey || "",
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
		if (str === "" || str === "-" || str === "–" || str === "—")
			return false;
		return true;
	}

	function getPhaseFlagMap(game, slot, flag) {
		const key =
			flag === "moved"
				? "movedUnits"
				: flag === "shot"
					? "shotUnits"
					: "foughtUnits";
		return game?.[key]?.[slot] || {};
	}

	function isPhaseFlagDone(doneMap, unit) {
		const key = unitInstanceKey(unit);
		return !!(key && doneMap[key]);
	}

	function isHeroUnit(unit) {
		return unitInstanceKey(unit).startsWith("hero:");
	}

	function sortUnitsHeroFirst(units) {
		const heroes = [];
		const rest = [];
		units.forEach((unit) => {
			if (isHeroUnit(unit)) heroes.push(unit);
			else rest.push(unit);
		});
		return heroes.concat(rest);
	}

	function phaseActionLabel(flag, done) {
		if (flag === "moved") return done ? "移動済み" : "移動待ち";
		if (flag === "shot") return done ? "射撃済み" : "射撃待ち";
		return done ? "攻撃済み" : "攻撃待ち";
	}

	function buildPhaseUnitCardHtml(unit, opts) {
		const unitClass = opts.unitClass || "";
		const extraInnerHtml = opts.extraInnerHtml || "";
		const flag = opts.flag || "";
		const done = !!opts.done;
		const playerSlot = opts.playerSlot;
		const ariaLabel = opts.ariaLabel || unit.name || "ユニット";
		const key = unitInstanceKey(unit);
		const doneClass = done ? " is-phase-done" : "";
		const toggleHtml = flag
			? `<button type="button" class="phase-action-toggle"
					data-flag="${escapeAttr(flag)}"
					data-unit-key="${escapeAttr(key)}"
					data-player-slot="${playerSlot}"
					aria-pressed="${done}">
					${escapeHtml(phaseActionLabel(flag, done))}
				</button>`
			: "";
		return `<div class="phase-unit-card${doneClass}">
			<button type="button" class="${escapeAttr(unitClass)} ability-unit-thumb"
				data-unit-id="${unit.id}"
				data-unit-key="${escapeAttr(key)}"
				data-unit-name="${escapeAttr(unit.name || "")}"
				data-unit-keywords="${escapeAttr(unit.keywords || "")}"
				data-player-slot="${playerSlot}"
				aria-label="${escapeAttr(ariaLabel)}">
				<span class="ability-unit-thumb-image">${unitThumbImageHtml(unit)}</span>
				${extraInnerHtml}
			</button>
			${toggleHtml}
		</div>`;
	}

	function buildMovementStripHtml(units, doneMap) {
		if (!units.length) return "";
		const cards = units
			.map((unit) =>
				buildPhaseUnitCardHtml(unit, {
					unitClass: "phase-movement-unit",
					extraInnerHtml: `<span class="phase-movement-value">${escapeHtml(formatMovement(unit.movement))}</span>`,
					flag: "moved",
					done: isPhaseFlagDone(doneMap, unit),
					playerSlot: viewerSlot,
					ariaLabel: `${unit.name || "ユニット"} 移動力 ${formatMovement(unit.movement)}`,
				}),
			)
			.join("");
		return `<section class="phase-movement-section">
			<h4 class="phase-movement-title">移動力</h4>
			<div class="phase-movement-units" role="list">${cards}</div>
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

		const doneMap = getPhaseFlagMap(game, viewerSlot, "moved");
		const units = sortUnitsHeroFirst(
			livingRosterUnits(roster, game).filter(
				(u) => u.id && hasMovement(u),
			),
		);

		if (!units.length) {
			strip.innerHTML = "";
			strip.style.display = "none";
			strip.hidden = true;
			return;
		}

		strip.innerHTML = buildMovementStripHtml(units, doneMap);
		strip.style.display = "";
		strip.hidden = false;
	}

	function buildShootingStripHtml(units, doneMap) {
		if (!units.length) return "";
		const cards = units
			.map((unit) =>
				buildPhaseUnitCardHtml(unit, {
					unitClass: "phase-shooting-unit",
					flag: "shot",
					done: isPhaseFlagDone(doneMap, unit),
					playerSlot: viewerSlot,
					ariaLabel: unit.name || "ユニット",
				}),
			)
			.join("");
		return `<section class="phase-shooting-section">
			<h4 class="phase-shooting-title">射撃可能ユニット</h4>
			<div class="phase-shooting-units" role="list">${cards}</div>
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

		const doneMap = getPhaseFlagMap(game, viewerSlot, "shot");
		const units = sortUnitsHeroFirst(
			livingRosterUnits(roster, game).filter(
				(u) => u.id && u.hasRangedWeapon,
			),
		);

		if (!units.length) {
			strip.innerHTML = "";
			strip.style.display = "none";
			strip.hidden = true;
			return;
		}

		strip.innerHTML = buildShootingStripHtml(units, doneMap);
		strip.style.display = "";
		strip.hidden = false;
	}

	function buildCombatStripHtml(units, doneMap) {
		if (!units.length) return "";
		const cards = units
			.map((unit) =>
				buildPhaseUnitCardHtml(unit, {
					unitClass: "phase-combat-unit",
					flag: "fought",
					done: isPhaseFlagDone(doneMap, unit),
					playerSlot: viewerSlot,
					ariaLabel: unit.name || "ユニット",
				}),
			)
			.join("");
		return `<section class="phase-combat-section">
			<h4 class="phase-combat-title">戦闘可能ユニット</h4>
			<div class="phase-combat-units" role="list">${cards}</div>
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

		const doneMap = getPhaseFlagMap(game, viewerSlot, "fought");
		const units = sortUnitsHeroFirst(
			livingRosterUnits(roster, game).filter((u) => u.id && u.hasWeapon),
		);

		if (!units.length) {
			strip.innerHTML = "";
			strip.style.display = "none";
			strip.hidden = true;
			return;
		}

		strip.innerHTML = buildCombatStripHtml(units, doneMap);
		strip.style.display = "";
		strip.hidden = false;
	}

	function buildOpponentRosterStripHtml(units, playerSlot, doneMap) {
		if (!units.length) return "";
		const cards = units
			.map((unit) =>
				buildPhaseUnitCardHtml(unit, {
					unitClass: "phase-opponent-unit",
					flag: "",
					done: isPhaseFlagDone(doneMap, unit),
					playerSlot,
					ariaLabel: unit.name || "ユニット",
				}),
			)
			.join("");
		return `<section class="phase-opponent-roster-section">
			<h4 class="phase-opponent-roster-title">相手ロスター</h4>
			<div class="phase-opponent-units" role="list">${cards}</div>
		</section>`;
	}

	function renderOpponentRosterStrip(
		opponentPlayer,
		game,
		opponentSlot,
		viewPhase,
	) {
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
		const doneMap =
			viewPhase === "combat"
				? getPhaseFlagMap(game, opponentSlot, "fought")
				: {};
		const units = sortUnitsHeroFirst(
			collectOpponentRosterUnits(
				roster,
				destroyedMap,
				summonedMap,
			).filter((u) => u.id),
		);

		if (!units.length) {
			strip.innerHTML = "";
			strip.hidden = true;
			return;
		}

		strip.innerHTML = buildOpponentRosterStripHtml(
			units,
			opponentSlot,
			doneMap,
		);
		strip.hidden = false;
	}

	function renderAbilities(state, game, myPlayer, isMyTurn) {
		if (!els.abilityList) return;

		const deck = myPlayer?.abilitiesDeck || [];
		const usedMap = game.usedAbilities?.[viewerSlot] || {};
		const livingUnits = livingRosterUnits(myPlayer?.roster || null, game);
		const allUnits = allRosterUnits(myPlayer?.roster || null, game);

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
				isAbilityAvailable(ab, livingUnits, allUnits)
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
				groupEl.appendChild(
					buildAbilityCard(ab, {}, livingUnits, allUnits),
				),
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
						buildAbilityCard(
							ab,
							{ passive: true },
							livingUnits,
							allUnits,
						),
					),
				);

			section.appendChild(grid);
			els.abilityList.appendChild(section);
		}
	}

	function buildAbilityCard(ab, opts = {}, livingUnits = [], allUnits = []) {
		const isPassive = !!opts.passive;
		const state = MatchStateManager.getState();
		const game = state.game || {
			usedAbilities: {},
			summonedUnits: {},
			abilityTargetUnits: {},
		};
		const usedMap = game.usedAbilities?.[viewerSlot] || {};
		const summonedMap = game.summonedUnits?.[viewerSlot] || {};
		{
			// パッシブ/回数無制限(unlimited)は使用済み管理対象外なのでトグル/使用済み表現を出さない。
			const tracked = !isPassive && MatchPhases.isUsageTracked(ab);
			const used = tracked && !!usedMap[ab.key]?.used;
			const behaviorId = String(ab.behaviorId || "").trim();
			const manifestUnitKey = String(ab.manifestUnitKey || "").trim();
			const targetsUnitOnSummon = !!ab.targetsUnitOnSummon;
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
			if (behaviorId) {
				card.dataset.behaviorId = behaviorId;
			}
			if (manifestUnitKey) {
				card.dataset.manifestUnitKey = manifestUnitKey;
			}
			if (targetsUnitOnSummon) {
				card.dataset.targetsUnitOnSummon = "1";
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
				? resolveAbilityUnits(
						ab,
						unitsForAbility(ab, livingUnits, allUnits),
					)
				: [];
			let unitThumbsHtml = "";
			if (hasEffect && ab.category === "manifestation") {
				const myPlayer =
					MatchStateManager.getPlayer?.(viewerSlot) ||
					(state.players || []).find((p) => p.slot === viewerSlot) ||
					null;
				const manifest = resolveManifestUnit(
					ab,
					myPlayer?.roster || null,
				);
				const sections = [];
				if (manifest) {
					sections.push(
						buildThumbsSectionHtml(
							"召喚する顕現",
							[manifest],
							null,
						),
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
				if (summoned && targetsUnitOnSummon && manifestUnitKey) {
					const targetKey =
						game.manifestationTargets?.[viewerSlot]?.[
							manifestUnitKey
						] || "";
					const targetUnits = targetKey
						? resolveUnitsByKeys(myPlayer?.roster || null, [
								targetKey,
							])
						: [];
					if (targetUnits.length) {
						sections.push(
							buildThumbsSectionHtml(
								"効果対象",
								targetUnits,
								null,
								{ showName: true },
							),
						);
					}
				}
				unitThumbsHtml = sections.length
					? `<div class="ability-unit-thumbs-row">${sections.join("")}</div>`
					: "";
			} else if (
				hasEffect &&
				behaviorId === "pick_unit_once_per_battle"
			) {
				const myPlayer =
					MatchStateManager.getPlayer?.(viewerSlot) ||
					(state.players || []).find((p) => p.slot === viewerSlot) ||
					null;
				const usedKeys = getAbilityTargetKeys(game, ab.key);
				const usedUnits = resolveUnitsByKeys(
					myPlayer?.roster || null,
					usedKeys,
				);
				const sections = [];
				if (usedUnits.length) {
					sections.push(
						buildThumbsSectionHtml(
							"このバトルで使用済み",
							usedUnits,
							null,
							{ markUsed: true, showName: true },
						),
					);
				}
				unitThumbsHtml = sections.length
					? `<div class="ability-unit-thumbs-row">${sections.join("")}</div>`
					: `<div class="ability-unit-thumbs-row"><div class="ability-unit-thumbs-section"><div class="ability-unit-thumbs-label">このバトルで使用済み</div><p class="ability-target-none">まだありません</p></div></div>`;
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
