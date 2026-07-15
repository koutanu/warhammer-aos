/**
 * Hero 行ベースの Enhancement（英雄特性・神器）付与
 * + シーズン追加能力（キーワード適格・ロスター全体で1つ）
 * 付与先は連隊番号 + スロット（leader / unit index）で一意化
 */
document.addEventListener("DOMContentLoaded", () => {
	const traitIdInput = document.getElementById("heroicTraitIdInput");
	const traitTargetInput = document.getElementById("traitTargetUnitIdInput");
	const traitRegimentInput = document.getElementById("traitRegimentIndexInput");
	const traitSlotInput = document.getElementById("traitUnitSlotInput");
	const artefactIdInput = document.getElementById("artefactIdInput");
	const artefactTargetInput = document.getElementById("artefactTargetUnitIdInput");
	const artefactRegimentInput = document.getElementById("artefactRegimentIndexInput");
	const artefactSlotInput = document.getElementById("artefactUnitSlotInput");
	const seasonIdInput = document.getElementById("seasonEnhancementIdInput");
	const seasonTargetInput = document.getElementById("seasonEnhancementTargetUnitIdInput");
	const seasonRegimentInput = document.getElementById("seasonEnhancementRegimentIndexInput");
	const seasonSlotInput = document.getElementById("seasonEnhancementUnitSlotInput");
	const factionInput = document.querySelector('input[name="faction_id"]');
	const regimentsContainer = document.getElementById("regimentsContainer");

	const enhancementModal = document.getElementById("enhancementModal");
	const enhancementModalTitle = document.getElementById("enhancementModalTitle");
	const enhancementModalList = document.getElementById("enhancementModalList");
	const enhancementModalEmpty = document.getElementById("enhancementModalEmpty");
	const btnCloseEnhancementModal = document.getElementById("btnCloseEnhancementModal");

	if (!traitIdInput || !regimentsContainer) return;

	let enhancementData = {
		traits: [],
		artefacts: [],
		seasonEnhancements: [],
		seasonEnhancementLabel: { label_ja: "追加能力", label_en: null },
	};
	let activeEnhancementType = null;
	let activeHeroRow = null;
	let cachedEnhancementItems = [];
	let openModalDetailId = null;

	function getFactionId() {
		return factionInput?.value || "";
	}

	function getSeasonLabel() {
		return enhancementData.seasonEnhancementLabel?.label_ja || "追加能力";
	}

	function updateSeasonButtonLabels() {
		const label = getSeasonLabel();
		regimentsContainer
			.querySelectorAll(".btn-add-season-enhancement")
			.forEach((btn) => {
				btn.textContent = `+ ${label}`;
			});
	}

	function isHeroKeywords(keywords) {
		return String(keywords || "").toUpperCase().includes("HERO");
	}

	function keywordBaseName(token) {
		return String(token || "")
			.trim()
			.replace(/\s*\([^)]*\)\s*$/, "")
			.trim()
			.toUpperCase();
	}

	function unitMatchesSeasonKeywords(row, keywords) {
		if (!keywords || !keywords.length) return true;
		const tokens = String(row?.dataset?.keywords || "")
			.split(",")
			.map((t) => keywordBaseName(t))
			.filter(Boolean);
		const tokenSet = new Set(tokens);

		const required = [];
		const excluded = [];
		keywords.forEach((kw) => {
			const name = keywordBaseName(kw.name || "");
			if (!name) return;
			if (kw.requirement === "exclude") excluded.push(name);
			else required.push(name);
		});

		if (!required.every((name) => tokenSet.has(name))) return false;
		if (excluded.some((name) => tokenSet.has(name))) return false;
		return true;
	}

	function seasonItemsForUnit(row) {
		return (enhancementData.seasonEnhancements || []).filter((item) =>
			unitMatchesSeasonKeywords(row, item.keywords || []),
		);
	}

	function getHeroRowUnitId(row) {
		if (!row) return "";
		if (row.classList.contains("hero-slot-row")) {
			return row.dataset.unitId || "";
		}
		if (row.classList.contains("unit-slot-row")) {
			return row.dataset.unitId || row.querySelector(".unit-id-input")?.value || "";
		}
		return "";
	}

	function getHeroRowSlot(row) {
		if (!row) return "";
		if (row.classList.contains("hero-slot-row")) {
			return "leader";
		}
		return row.querySelector(".btn-select-unit")?.dataset.unitIndex ?? "";
	}

	function getHeroRowRegimentIndex(row) {
		return row?.closest(".regiment-card")?.dataset.regimentIndex ?? "";
	}

	function buildTargetKey(regimentIndex, unitSlot) {
		if (regimentIndex === "" || regimentIndex === undefined) return "";
		if (unitSlot === "leader") {
			return `${regimentIndex}:leader`;
		}
		return `${regimentIndex}:unit:${unitSlot}`;
	}

	function getHeroRowKey(row) {
		return buildTargetKey(getHeroRowRegimentIndex(row), getHeroRowSlot(row));
	}

	function parseTargetFromRow(row) {
		return {
			regimentIndex: getHeroRowRegimentIndex(row),
			unitSlot: getHeroRowSlot(row),
			unitId: getHeroRowUnitId(row),
			key: getHeroRowKey(row),
		};
	}

	function findHeroRowParent(row) {
		if (!row) return null;
		if (row.classList.contains("hero-slot-row")) {
			return row.closest(".form-group")?.querySelector(".hero-enhancement-actions");
		}
		if (row.classList.contains("unit-slot-row")) {
			return row.querySelector(".hero-enhancement-actions");
		}
		return null;
	}

	function getState() {
		const traitKey = buildTargetKey(
			traitRegimentInput?.value ?? "",
			traitSlotInput?.value ?? "",
		);
		const artefactKey = buildTargetKey(
			artefactRegimentInput?.value ?? "",
			artefactSlotInput?.value ?? "",
		);
		const seasonKey = buildTargetKey(
			seasonRegimentInput?.value ?? "",
			seasonSlotInput?.value ?? "",
		);
		return {
			traitId: traitIdInput.value || "",
			traitTarget: traitTargetInput?.value || "",
			traitKey,
			artefactId: artefactIdInput?.value || "",
			artefactTarget: artefactTargetInput?.value || "",
			artefactKey,
			seasonId: seasonIdInput?.value || "",
			seasonTarget: seasonTargetInput?.value || "",
			seasonKey,
		};
	}

	function setTrait(id, target, item) {
		traitIdInput.value = id ? String(id) : "";
		if (traitTargetInput) traitTargetInput.value = target.unitId ? String(target.unitId) : "";
		if (traitRegimentInput) traitRegimentInput.value = target.regimentIndex !== "" ? String(target.regimentIndex) : "";
		if (traitSlotInput) traitSlotInput.value = target.unitSlot || "";
		window._enhancementTraitMeta = item || null;
	}

	function setArtefact(id, target, item) {
		artefactIdInput.value = id ? String(id) : "";
		if (artefactTargetInput) artefactTargetInput.value = target.unitId ? String(target.unitId) : "";
		if (artefactRegimentInput) artefactRegimentInput.value = target.regimentIndex !== "" ? String(target.regimentIndex) : "";
		if (artefactSlotInput) artefactSlotInput.value = target.unitSlot || "";
		window._enhancementArtefactMeta = item || null;
	}

	function setSeason(id, target, item) {
		if (seasonIdInput) seasonIdInput.value = id ? String(id) : "";
		if (seasonTargetInput) seasonTargetInput.value = target.unitId ? String(target.unitId) : "";
		if (seasonRegimentInput) {
			seasonRegimentInput.value =
				target.regimentIndex !== "" ? String(target.regimentIndex) : "";
		}
		if (seasonSlotInput) seasonSlotInput.value = target.unitSlot || "";
		window._enhancementSeasonMeta = item || null;
	}

	function clearTrait() {
		setTrait("", { regimentIndex: "", unitSlot: "", unitId: "" }, null);
	}

	function clearArtefact() {
		setArtefact("", { regimentIndex: "", unitSlot: "", unitId: "" }, null);
	}

	function clearSeason() {
		setSeason("", { regimentIndex: "", unitSlot: "", unitId: "" }, null);
	}

	function getItemTrigger(item) {
		if (!item) return "";
		const conditionJa = String(item.trigger_condition_ja || "").trim();
		if (conditionJa) return conditionJa;
		const raw = item.trigger_phase || "";
		if (typeof MatchPhases !== "undefined" && MatchPhases.formatTriggerPhases) {
			return MatchPhases.formatTriggerPhases(raw);
		}
		return raw;
	}

	function getItemEffect(item) {
		if (!item) return "";
		return item.effect || item.description || item.flavor_text || "";
	}

	function escapeHtml(text) {
		const div = document.createElement("div");
		div.textContent = text;
		return div.innerHTML;
	}

	function toggleDetailBox(box, show) {
		if (!box) return;
		box.style.display = show ? "block" : "none";
	}

	function enhancementTypeLabel(type) {
		if (type === "trait") return "英雄特性";
		if (type === "artefact") return "神器";
		if (type === "season") return getSeasonLabel();
		return "";
	}

	function renderEnhancementDetailContent(box, item, type) {
		if (!box || !item) return;
		const title = box.querySelector(".enhancement-detail-title");
		const trigger = box.querySelector(".enhancement-detail-trigger");
		const effect = box.querySelector(".enhancement-detail-effect");
		if (title) title.textContent = item.name || "";
		if (trigger) {
			const label = getItemTrigger(item);
			trigger.textContent = label;
			trigger.style.display = label ? "" : "none";
		}
		if (effect) effect.textContent = getItemEffect(item);
	}

	async function loadEnhancements() {
		const factionId = getFactionId();
		if (!factionId) return;

		try {
			const res = await fetch(
				getBaseURL() + `roster/getEnhancements?faction_id=${encodeURIComponent(factionId)}`,
			);
			enhancementData = await res.json();
			if (!enhancementData.traits) enhancementData.traits = [];
			if (!enhancementData.artefacts) enhancementData.artefacts = [];
			if (!enhancementData.seasonEnhancements) enhancementData.seasonEnhancements = [];
			if (!enhancementData.seasonEnhancementLabel) {
				enhancementData.seasonEnhancementLabel = {
					label_ja: "追加能力",
					label_en: null,
				};
			}
			updateSeasonButtonLabels();
		} catch (e) {
			console.error(e);
		}
	}

	function collectHeroRows() {
		const rows = [];
		regimentsContainer.querySelectorAll(".hero-slot-row").forEach((row) => {
			if (getHeroRowUnitId(row)) rows.push(row);
		});
		regimentsContainer.querySelectorAll(".unit-slot-row").forEach((row) => {
			const isHeroRow =
				row.dataset.isHero === "1" || isHeroKeywords(row.dataset.keywords);
			if (getHeroRowUnitId(row) && isHeroRow) {
				rows.push(row);
			}
		});
		return rows;
	}

	function collectAllUnitRows() {
		const rows = [];
		regimentsContainer.querySelectorAll(".hero-slot-row").forEach((row) => {
			if (getHeroRowUnitId(row)) rows.push(row);
		});
		regimentsContainer.querySelectorAll(".unit-slot-row").forEach((row) => {
			if (getHeroRowUnitId(row)) rows.push(row);
		});
		return rows;
	}

	function renderAssignedBadge(actionsEl, type, item, onClear) {
		const badge = actionsEl.querySelector(`.${type}-assigned`);
		if (!badge) return;

		if (!item) {
			badge.style.display = "none";
			badge.innerHTML = "";
			return;
		}

		const detailId = `${type}-detail-${actionsEl.closest(".regiment-card")?.dataset.regimentIndex ?? "x"}`;
		badge.style.display = "block";
		badge.innerHTML = `
			<span class="enhancement-assigned-label">${escapeHtml(enhancementTypeLabel(type))}:</span>
			<strong>${escapeHtml(item.name)}</strong> (${item.points} pt)
			<button type="button" class="btn-clear-enhancement" data-type="${type}">解除</button>
			<button type="button" class="btn-view-enhancement-detail" data-detail-id="${detailId}" data-enhance-type="${type}">詳細を確認</button>
			<div id="${detailId}" class="enhancement-detail-box" style="display:none;">
				<button type="button" class="detail-close-btn enhancement-detail-close">×</button>
				<h4 class="enhancement-detail-title"></h4>
				<span class="enhancement-detail-trigger badge"></span>
				<p class="enhancement-detail-effect"></p>
			</div>
		`;

		const detailBox = badge.querySelector(`#${detailId}`);
		renderEnhancementDetailContent(detailBox, item, type);

		badge.querySelector(".btn-clear-enhancement")?.addEventListener("click", (e) => {
			e.preventDefault();
			onClear();
			updateHeroEnhancementButtons();
			if (typeof window.updateAllPoints === "function") window.updateAllPoints();
		});

		const detailBtn = badge.querySelector(".btn-view-enhancement-detail");
		detailBtn?.addEventListener("click", (e) => {
			e.preventDefault();
			const isOpen = detailBox?.style.display === "block";
			toggleDetailBox(detailBox, !isOpen);
			if (detailBtn) detailBtn.textContent = isOpen ? "詳細を確認" : "詳細を閉じる";
		});

		badge.querySelector(".enhancement-detail-close")?.addEventListener("click", (e) => {
			e.preventDefault();
			toggleDetailBox(detailBox, false);
			if (detailBtn) detailBtn.textContent = "詳細を確認";
		});
	}

	function updateHeroEnhancementButtons() {
		const state = getState();
		const heroRowKeys = collectHeroRows().map((row) => getHeroRowKey(row));
		const allUnitKeys = collectAllUnitRows().map((row) => getHeroRowKey(row));

		if (state.traitKey && !heroRowKeys.includes(state.traitKey)) {
			clearTrait();
		}
		if (state.artefactKey && !heroRowKeys.includes(state.artefactKey)) {
			clearArtefact();
		}
		if (state.seasonKey && !allUnitKeys.includes(state.seasonKey)) {
			clearSeason();
		}

		collectHeroRows().forEach((row) => {
			if (row.dataset.isUnique !== "1") return;
			const rowKey = getHeroRowKey(row);
			const s = getState();
			if (s.traitKey === rowKey) clearTrait();
			if (s.artefactKey === rowKey) clearArtefact();
		});

		const refreshedState = getState();
		const hasTraits = enhancementData.traits.length > 0;
		const hasArtefacts = enhancementData.artefacts.length > 0;
		const hasSeason = enhancementData.seasonEnhancements.length > 0;

		regimentsContainer.querySelectorAll(".hero-enhancement-actions").forEach((actions) => {
			actions.style.display = "none";
		});

		collectAllUnitRows().forEach((row) => {
			const actions = findHeroRowParent(row);
			if (!actions) return;

			const rowKey = getHeroRowKey(row);
			const isHeroRow =
				row.classList.contains("hero-slot-row") ||
				row.dataset.isHero === "1" ||
				isHeroKeywords(row.dataset.keywords);
			const isUnique = row.dataset.isUnique === "1";

			const btnTrait = actions.querySelector(".btn-add-trait");
			const btnArtefact = actions.querySelector(".btn-add-artefact");
			const btnSeason = actions.querySelector(".btn-add-season-enhancement");

			const isTraitHere = refreshedState.traitKey === rowKey;
			const isArtefactHere = refreshedState.artefactKey === rowKey;
			const isSeasonHere = refreshedState.seasonKey === rowKey;
			const traitTaken = !!refreshedState.traitId;
			const artefactTaken = !!refreshedState.artefactId;
			const seasonTaken = !!refreshedState.seasonId;

			const eligibleSeasonItems = seasonItemsForUnit(row);
			const canTakeSeason = hasSeason && eligibleSeasonItems.length > 0;
			const showTraitArtefact = isHeroRow && !isUnique && (hasTraits || hasArtefacts);
			// ロスター全体で1つのみ: 未選択なら適格ユニットにボタン、選択済みなら付与先にだけバッジ
			const showSeasonButton = canTakeSeason && !seasonTaken;
			const showSeasonBadge = !!(isSeasonHere && refreshedState.seasonId);
			const showSeason = showSeasonButton || showSeasonBadge;

			if (!showTraitArtefact && !showSeason) {
				actions.style.display = "none";
				if (btnTrait) btnTrait.style.display = "none";
				if (btnArtefact) btnArtefact.style.display = "none";
				if (btnSeason) btnSeason.style.display = "none";
				renderAssignedBadge(actions, "trait", null, clearTrait);
				renderAssignedBadge(actions, "artefact", null, clearArtefact);
				renderAssignedBadge(actions, "season", null, clearSeason);
				return;
			}

			actions.style.display = "flex";
			updateSeasonButtonLabels();

			if (btnTrait) {
				const show = showTraitArtefact && hasTraits;
				btnTrait.style.display =
					show && !(isTraitHere && refreshedState.traitId) ? "inline-block" : "none";
				btnTrait.disabled = !show || (traitTaken && !isTraitHere);
			}
			if (btnArtefact) {
				const show = showTraitArtefact && hasArtefacts;
				btnArtefact.style.display =
					show && !(isArtefactHere && refreshedState.artefactId)
						? "inline-block"
						: "none";
				btnArtefact.disabled = !show || (artefactTaken && !isArtefactHere);
			}
			if (btnSeason) {
				btnSeason.style.display = showSeasonButton ? "inline-block" : "none";
				btnSeason.disabled = !showSeasonButton;
			}

			renderAssignedBadge(
				actions,
				"trait",
				isTraitHere && showTraitArtefact ? window._enhancementTraitMeta : null,
				clearTrait,
			);
			renderAssignedBadge(
				actions,
				"artefact",
				isArtefactHere && showTraitArtefact ? window._enhancementArtefactMeta : null,
				clearArtefact,
			);
			renderAssignedBadge(
				actions,
				"season",
				showSeasonBadge ? window._enhancementSeasonMeta : null,
				clearSeason,
			);
		});
	}

	function openEnhancementModal(type, heroRow) {
		if (!enhancementModal) return;

		activeEnhancementType = type;
		activeHeroRow = heroRow;
		openModalDetailId = null;

		if (type === "trait") {
			cachedEnhancementItems = enhancementData.traits;
		} else if (type === "artefact") {
			cachedEnhancementItems = enhancementData.artefacts;
		} else {
			cachedEnhancementItems = seasonItemsForUnit(heroRow);
		}

		if (enhancementModalTitle) {
			enhancementModalTitle.textContent =
				type === "trait"
					? "英雄特性を選択"
					: type === "artefact"
						? "神器を選択"
						: `${getSeasonLabel()}を選択`;
		}
		renderEnhancementList(cachedEnhancementItems);
		enhancementModal.style.display = "flex";
		window.ModalScroll?.lock("enhancementModal");
	}

	function closeEnhancementModal() {
		if (enhancementModal) enhancementModal.style.display = "none";
		window.ModalScroll?.unlock("enhancementModal");
		activeEnhancementType = null;
		activeHeroRow = null;
		cachedEnhancementItems = [];
		openModalDetailId = null;
	}

	function getEnhancementGroupLabel(item) {
		const src = String(item?.source_reference || "").trim();
		if (src) return src;
		const category = String(item?.category || "").trim();
		if (category) return category;
		if (Array.isArray(item?.keywords) && item.keywords.length) {
			const requiredNames = item.keywords
				.filter((k) => k.requirement !== "exclude")
				.map((k) => k.name)
				.filter(Boolean);
			if (requiredNames.length) return requiredNames.join(" / ");
		}
		return "その他";
	}

	function groupEnhancementItems(items) {
		const groups = new Map();
		items.forEach((item) => {
			const label = getEnhancementGroupLabel(item);
			if (!groups.has(label)) groups.set(label, []);
			groups.get(label).push(item);
		});
		return groups;
	}

	function renderEnhancementItem(item) {
		const wrapper = document.createElement("div");
		wrapper.className = "modal-enhancement-wrapper";

		const row = document.createElement("div");
		row.className = "modal-enhancement-row";

		const selectBtn = document.createElement("button");
		selectBtn.type = "button";
		selectBtn.className = "modal-unit-btn";
		selectBtn.textContent = `${item.name} (${item.points} pt)`;
		selectBtn.addEventListener("click", () => selectEnhancement(item));

		const detailBtn = document.createElement("button");
		detailBtn.type = "button";
		detailBtn.className = "modal-enhancement-detail-btn";
		detailBtn.textContent = "i";
		detailBtn.title = "詳細を確認";

		const inlineDetail = document.createElement("div");
		inlineDetail.className = "enhancement-modal-inline-detail";
		inlineDetail.style.display = "none";
		inlineDetail.innerHTML = `
			<span class="badge">${escapeHtml(getItemTrigger(item, activeEnhancementType))}</span>
			<p>${escapeHtml(getItemEffect(item))}</p>
			<button type="button" class="btn-close-inline-detail">閉じる</button>
		`;

		detailBtn.addEventListener("click", (e) => {
			e.stopPropagation();
			const show = inlineDetail.style.display === "none";
			enhancementModalList.querySelectorAll(".enhancement-modal-inline-detail").forEach((el) => {
				el.style.display = "none";
			});
			inlineDetail.style.display = show ? "block" : "none";
			openModalDetailId = show ? String(item.id) : null;
		});

		inlineDetail.querySelector(".btn-close-inline-detail")?.addEventListener("click", (e) => {
			e.stopPropagation();
			inlineDetail.style.display = "none";
			openModalDetailId = null;
		});

		row.appendChild(selectBtn);
		row.appendChild(detailBtn);
		wrapper.appendChild(row);
		wrapper.appendChild(inlineDetail);
		return wrapper;
	}

	function renderEnhancementList(items) {
		if (!enhancementModalList) return;
		enhancementModalList.innerHTML = "";

		if (!items.length) {
			if (enhancementModalEmpty) enhancementModalEmpty.style.display = "block";
			return;
		}
		if (enhancementModalEmpty) enhancementModalEmpty.style.display = "none";

		const groups = groupEnhancementItems(items);
		groups.forEach((groupItems, label) => {
			const group = document.createElement("div");
			group.className = "modal-enhancement-group";

			const heading = document.createElement("h4");
			heading.className = "modal-enhancement-group-title";
			heading.textContent = label;
			group.appendChild(heading);

			groupItems.forEach((item) => {
				group.appendChild(renderEnhancementItem(item));
			});

			enhancementModalList.appendChild(group);
		});
	}

	function selectEnhancement(item) {
		if (!activeHeroRow || !activeEnhancementType) return;
		const target = parseTargetFromRow(activeHeroRow);
		if (!target.unitId) return;

		if (activeEnhancementType === "trait") {
			setTrait(item.id, target, item);
		} else if (activeEnhancementType === "artefact") {
			setArtefact(item.id, target, item);
		} else {
			setSeason(item.id, target, item);
		}

		closeEnhancementModal();
		updateHeroEnhancementButtons();
		if (typeof window.updateAllPoints === "function") window.updateAllPoints();
	}

	function getEnhancementPoints() {
		let pts = 0;
		if (window._enhancementTraitMeta) {
			pts += parseInt(window._enhancementTraitMeta.points || "0", 10);
		}
		if (window._enhancementArtefactMeta) {
			pts += parseInt(window._enhancementArtefactMeta.points || "0", 10);
		}
		if (window._enhancementSeasonMeta) {
			pts += parseInt(window._enhancementSeasonMeta.points || "0", 10);
		}
		return pts;
	}

	function resolveRowFromActions(actions) {
		return (
			actions?.closest(".unit-slot-row") ||
			actions?.closest(".form-group")?.querySelector(".hero-slot-row")
		);
	}

	document.addEventListener("click", (e) => {
		const target = e.target;
		if (!target) return;

		if (target.classList.contains("btn-add-trait")) {
			e.preventDefault();
			const heroRow = resolveRowFromActions(target.closest(".hero-enhancement-actions"));
			if (heroRow) openEnhancementModal("trait", heroRow);
			return;
		}

		if (target.classList.contains("btn-add-artefact")) {
			e.preventDefault();
			const heroRow = resolveRowFromActions(target.closest(".hero-enhancement-actions"));
			if (heroRow) openEnhancementModal("artefact", heroRow);
			return;
		}

		if (target.classList.contains("btn-add-season-enhancement")) {
			e.preventDefault();
			const heroRow = resolveRowFromActions(target.closest(".hero-enhancement-actions"));
			if (heroRow) openEnhancementModal("season", heroRow);
		}
	});

	if (btnCloseEnhancementModal) {
		btnCloseEnhancementModal.addEventListener("click", closeEnhancementModal);
	}
	if (enhancementModal) {
		enhancementModal.addEventListener("click", (e) => {
			if (e.target === enhancementModal) closeEnhancementModal();
		});
	}

	function restoreEnhancementFromRoster(roster, type) {
		const idKey =
			type === "trait"
				? "heroic_trait_id"
				: type === "artefact"
					? "artefact_id"
					: "season_enhancement_id";
		const id = roster[idKey];
		if (!id) return;

		const items =
			type === "trait"
				? enhancementData.traits
				: type === "artefact"
					? enhancementData.artefacts
					: enhancementData.seasonEnhancements;
		const item = items.find((x) => String(x.id) === String(id));
		if (!item) return;

		let regimentIndex =
			type === "trait"
				? roster.trait_regiment_index
				: type === "artefact"
					? roster.artefact_regiment_index
					: roster.season_enhancement_regiment_index;
		let unitSlot =
			type === "trait"
				? roster.trait_unit_slot || "leader"
				: type === "artefact"
					? roster.artefact_unit_slot || "leader"
					: roster.season_enhancement_unit_slot || "leader";
		let unitId =
			type === "trait"
				? roster.trait_target_unit_id
				: type === "artefact"
					? roster.artefact_target_unit_id
					: roster.season_enhancement_target_unit_id;

		const searchRows =
			type === "season" ? collectAllUnitRows() : collectHeroRows();
		if (
			(regimentIndex === null ||
				regimentIndex === undefined ||
				regimentIndex === "") &&
			unitId
		) {
			for (const row of searchRows) {
				if (String(getHeroRowUnitId(row)) === String(unitId)) {
					regimentIndex = getHeroRowRegimentIndex(row);
					unitSlot = getHeroRowSlot(row);
					break;
				}
			}
		}

		if (
			item &&
			regimentIndex !== null &&
			regimentIndex !== undefined &&
			regimentIndex !== ""
		) {
			const target = {
				regimentIndex: String(regimentIndex),
				unitSlot: String(unitSlot),
				unitId: String(unitId || ""),
			};
			if (type === "trait") setTrait(item.id, target, item);
			else if (type === "artefact") setArtefact(item.id, target, item);
			else setSeason(item.id, target, item);
		}
	}

	function restoreFromEdit() {
		const dataEl = document.getElementById("editRosterData");
		if (!dataEl) return;

		let payload;
		try {
			payload = JSON.parse(dataEl.textContent);
		} catch (e) {
			return;
		}

		const roster = payload.roster || {};
		restoreEnhancementFromRoster(roster, "trait");
		restoreEnhancementFromRoster(roster, "artefact");
		restoreEnhancementFromRoster(roster, "season");
	}

	function findHeroRowOnCard(card, unitSlot, unitId) {
		if (!card) return null;

		let row = null;
		if (unitSlot === "leader") {
			row = card.querySelector(".hero-slot-row");
		} else {
			row = Array.from(card.querySelectorAll(".unit-slot-row")).find((r) => {
				return (
					String(r.querySelector(".btn-select-unit")?.dataset.unitIndex ?? "") ===
					String(unitSlot)
				);
			});
		}

		if (!row || String(getHeroRowUnitId(row)) !== String(unitId)) {
			return null;
		}
		return row;
	}

	function captureEnhancementRegimentCards() {
		const state = getState();
		const refs = { traitCard: null, artefactCard: null, seasonCard: null };

		if (state.traitId && traitRegimentInput?.value !== "") {
			refs.traitCard = regimentsContainer.querySelector(
				`.regiment-card[data-regiment-index="${traitRegimentInput.value}"]`,
			);
		}
		if (state.artefactId && artefactRegimentInput?.value !== "") {
			refs.artefactCard = regimentsContainer.querySelector(
				`.regiment-card[data-regiment-index="${artefactRegimentInput.value}"]`,
			);
		}
		if (state.seasonId && seasonRegimentInput?.value !== "") {
			refs.seasonCard = regimentsContainer.querySelector(
				`.regiment-card[data-regiment-index="${seasonRegimentInput.value}"]`,
			);
		}
		return refs;
	}

	function remapEnhancementRegimentIndices(refs) {
		if (!refs) return;

		const state = getState();

		if (state.traitId && refs.traitCard && refs.traitCard.isConnected) {
			const unitSlot = traitSlotInput?.value || "leader";
			const unitId = traitTargetInput?.value || "";
			const row = findHeroRowOnCard(refs.traitCard, unitSlot, unitId);
			if (row) {
				const target = parseTargetFromRow(row);
				setTrait(state.traitId, target, window._enhancementTraitMeta);
			} else {
				clearTrait();
			}
		} else if (state.traitId && (!refs.traitCard || !refs.traitCard.isConnected)) {
			clearTrait();
		}

		if (state.artefactId && refs.artefactCard && refs.artefactCard.isConnected) {
			const unitSlot = artefactSlotInput?.value || "leader";
			const unitId = artefactTargetInput?.value || "";
			const row = findHeroRowOnCard(refs.artefactCard, unitSlot, unitId);
			if (row) {
				const target = parseTargetFromRow(row);
				setArtefact(state.artefactId, target, window._enhancementArtefactMeta);
			} else {
				clearArtefact();
			}
		} else if (state.artefactId && (!refs.artefactCard || !refs.artefactCard.isConnected)) {
			clearArtefact();
		}

		if (state.seasonId && refs.seasonCard && refs.seasonCard.isConnected) {
			const unitSlot = seasonSlotInput?.value || "leader";
			const unitId = seasonTargetInput?.value || "";
			const row = findHeroRowOnCard(refs.seasonCard, unitSlot, unitId);
			if (row) {
				const target = parseTargetFromRow(row);
				setSeason(state.seasonId, target, window._enhancementSeasonMeta);
			} else {
				clearSeason();
			}
		} else if (state.seasonId && (!refs.seasonCard || !refs.seasonCard.isConnected)) {
			clearSeason();
		}
	}

	window.getEnhancementPoints = getEnhancementPoints;
	window.updateHeroEnhancementButtons = updateHeroEnhancementButtons;
	window.captureEnhancementRegimentCards = captureEnhancementRegimentCards;
	window.remapEnhancementRegimentIndices = remapEnhancementRegimentIndices;

	loadEnhancements().then(() => {
		restoreFromEdit();
		updateHeroEnhancementButtons();
		if (typeof window.updateAllPoints === "function") window.updateAllPoints();
	});
});
