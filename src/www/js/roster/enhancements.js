/**
 * Hero 行ベースの Enhancement（英雄特性・神器）付与
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
	const factionInput = document.querySelector('input[name="faction_id"]');
	const regimentsContainer = document.getElementById("regimentsContainer");

	const enhancementModal = document.getElementById("enhancementModal");
	const enhancementModalTitle = document.getElementById("enhancementModalTitle");
	const enhancementModalList = document.getElementById("enhancementModalList");
	const enhancementModalSearch = document.getElementById("enhancementModalSearch");
	const enhancementModalEmpty = document.getElementById("enhancementModalEmpty");
	const btnCloseEnhancementModal = document.getElementById("btnCloseEnhancementModal");

	if (!traitIdInput || !regimentsContainer) return;

	let enhancementData = { traits: [], artefacts: [] };
	let activeEnhancementType = null;
	let activeHeroRow = null;
	let cachedEnhancementItems = [];
	let openModalDetailId = null;

	function getFactionId() {
		return factionInput?.value || "";
	}

	function isHeroKeywords(keywords) {
		return String(keywords || "").toUpperCase().includes("HERO");
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
		return {
			traitId: traitIdInput.value || "",
			traitTarget: traitTargetInput?.value || "",
			traitKey,
			artefactId: artefactIdInput?.value || "",
			artefactTarget: artefactTargetInput?.value || "",
			artefactKey,
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

	function clearTrait() {
		setTrait("", { regimentIndex: "", unitSlot: "", unitId: "" }, null);
	}

	function clearArtefact() {
		setArtefact("", { regimentIndex: "", unitSlot: "", unitId: "" }, null);
	}

	function getItemTrigger(item, type) {
		if (!item) return "";
		return type === "trait" ? item.trigger_phase || "" : item.trigger_timing || "";
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

	function renderEnhancementDetailContent(box, item, type) {
		if (!box || !item) return;
		const title = box.querySelector(".enhancement-detail-title");
		const trigger = box.querySelector(".enhancement-detail-trigger");
		const effect = box.querySelector(".enhancement-detail-effect");
		if (title) title.textContent = item.name || "";
		if (trigger) {
			const raw = getItemTrigger(item, type);
			trigger.textContent =
				typeof MatchPhases !== "undefined"
					? MatchPhases.formatTriggerPhase(
							raw,
							MatchPhases.normalizePhase(raw),
						)
					: raw;
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
			<span class="enhancement-assigned-label">${type === "trait" ? "英雄特性" : "神器"}:</span>
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

		if (state.traitKey && !heroRowKeys.includes(state.traitKey)) {
			clearTrait();
		}
		if (state.artefactKey && !heroRowKeys.includes(state.artefactKey)) {
			clearArtefact();
		}

		// 固有ユニットには付与不可。割り当て済みなら解除する
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

		regimentsContainer.querySelectorAll(".hero-enhancement-actions").forEach((actions) => {
			actions.style.display = "none";
		});

		collectHeroRows().forEach((row) => {
			const actions = findHeroRowParent(row);
			if (!actions) return;

			const rowKey = getHeroRowKey(row);
			const btnTrait = actions.querySelector(".btn-add-trait");
			const btnArtefact = actions.querySelector(".btn-add-artefact");

			// 固有ユニットは英雄特性・神器を付与できない
			if (row.dataset.isUnique === "1") {
				actions.style.display = "none";
				if (btnTrait) {
					btnTrait.disabled = true;
					btnTrait.style.display = "none";
				}
				if (btnArtefact) {
					btnArtefact.disabled = true;
					btnArtefact.style.display = "none";
				}
				renderAssignedBadge(actions, "trait", null, clearTrait);
				renderAssignedBadge(actions, "artefact", null, clearArtefact);
				return;
			}

			actions.style.display = hasTraits || hasArtefacts ? "flex" : "none";

			const isTraitHere = refreshedState.traitKey === rowKey;
			const isArtefactHere = refreshedState.artefactKey === rowKey;
			const traitTaken = !!refreshedState.traitId;
			const artefactTaken = !!refreshedState.artefactId;

			if (btnTrait) {
				btnTrait.disabled = !hasTraits || (traitTaken && !isTraitHere);
				btnTrait.style.display = isTraitHere && refreshedState.traitId ? "none" : "inline-block";
			}
			if (btnArtefact) {
				btnArtefact.disabled = !hasArtefacts || (artefactTaken && !isArtefactHere);
				btnArtefact.style.display = isArtefactHere && refreshedState.artefactId ? "none" : "inline-block";
			}

			renderAssignedBadge(actions, "trait", isTraitHere ? window._enhancementTraitMeta : null, clearTrait);
			renderAssignedBadge(actions, "artefact", isArtefactHere ? window._enhancementArtefactMeta : null, clearArtefact);
		});
	}

	function openEnhancementModal(type, heroRow) {
		if (!enhancementModal) return;

		activeEnhancementType = type;
		activeHeroRow = heroRow;
		openModalDetailId = null;
		cachedEnhancementItems = type === "trait" ? enhancementData.traits : enhancementData.artefacts;

		if (enhancementModalTitle) {
			enhancementModalTitle.textContent =
				type === "trait" ? "英雄特性を選択" : "神器を選択";
		}
		if (enhancementModalSearch) enhancementModalSearch.value = "";
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
		} else {
			setArtefact(item.id, target, item);
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
		return pts;
	}

	document.addEventListener("click", (e) => {
		const target = e.target;
		if (!target) return;

		if (target.classList.contains("btn-add-trait")) {
			e.preventDefault();
			const actions = target.closest(".hero-enhancement-actions");
			const heroRow =
				actions?.closest(".unit-slot-row") ||
				actions?.closest(".form-group")?.querySelector(".hero-slot-row");
			if (heroRow) openEnhancementModal("trait", heroRow);
			return;
		}

		if (target.classList.contains("btn-add-artefact")) {
			e.preventDefault();
			const actions = target.closest(".hero-enhancement-actions");
			const heroRow =
				actions?.closest(".unit-slot-row") ||
				actions?.closest(".form-group")?.querySelector(".hero-slot-row");
			if (heroRow) openEnhancementModal("artefact", heroRow);
		}
	});

	if (enhancementModalSearch) {
		enhancementModalSearch.addEventListener("input", () => {
			const q = enhancementModalSearch.value.trim().toLowerCase();
			const filtered = cachedEnhancementItems.filter((item) =>
				item.name.toLowerCase().includes(q),
			);
			renderEnhancementList(filtered);
		});
	}

	if (btnCloseEnhancementModal) {
		btnCloseEnhancementModal.addEventListener("click", closeEnhancementModal);
	}
	if (enhancementModal) {
		enhancementModal.addEventListener("click", (e) => {
			if (e.target === enhancementModal) closeEnhancementModal();
		});
	}

	function findHeroRowByTarget(regimentIndex, unitSlot, unitId) {
		for (const row of collectHeroRows()) {
			const t = parseTargetFromRow(row);
			if (
				String(t.regimentIndex) === String(regimentIndex) &&
				String(t.unitSlot) === String(unitSlot) &&
				String(t.unitId) === String(unitId)
			) {
				return row;
			}
		}
		return null;
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

		if (roster.heroic_trait_id) {
			const item = enhancementData.traits.find(
				(t) => String(t.id) === String(roster.heroic_trait_id),
			);
			let regimentIndex = roster.trait_regiment_index;
			let unitSlot = roster.trait_unit_slot || "leader";
			let unitId = roster.trait_target_unit_id;

			if ((regimentIndex === null || regimentIndex === undefined || regimentIndex === "") && unitId) {
				for (const row of collectHeroRows()) {
					if (String(getHeroRowUnitId(row)) === String(unitId)) {
						regimentIndex = getHeroRowRegimentIndex(row);
						unitSlot = getHeroRowSlot(row);
						break;
					}
				}
			}

			if (item && regimentIndex !== null && regimentIndex !== undefined && regimentIndex !== "") {
				setTrait(item.id, {
					regimentIndex: String(regimentIndex),
					unitSlot: String(unitSlot),
					unitId: String(unitId || ""),
				}, item);
			}
		}

		if (roster.artefact_id) {
			const item = enhancementData.artefacts.find(
				(a) => String(a.id) === String(roster.artefact_id),
			);
			let regimentIndex = roster.artefact_regiment_index;
			let unitSlot = roster.artefact_unit_slot || "leader";
			let unitId = roster.artefact_target_unit_id;

			if ((regimentIndex === null || regimentIndex === undefined || regimentIndex === "") && unitId) {
				for (const row of collectHeroRows()) {
					if (String(getHeroRowUnitId(row)) === String(unitId)) {
						regimentIndex = getHeroRowRegimentIndex(row);
						unitSlot = getHeroRowSlot(row);
						break;
					}
				}
			}

			if (item && regimentIndex !== null && regimentIndex !== undefined && regimentIndex !== "") {
				setArtefact(item.id, {
					regimentIndex: String(regimentIndex),
					unitSlot: String(unitSlot),
					unitId: String(unitId || ""),
				}, item);
			}
		}
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
		const refs = { traitCard: null, artefactCard: null };

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
