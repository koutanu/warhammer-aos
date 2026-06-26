document.addEventListener("DOMContentLoaded", () => {
	const unitModal = document.getElementById("unitModal");
	const modalUnitList = document.getElementById("modalUnitList");
	const modalUnitSearch = document.getElementById("modalUnitSearch");
	const modalUnitEmpty = document.getElementById("modalUnitEmpty");
	const modalTitle = document.getElementById("modalTitle");
	const btnCloseModal = document.getElementById("btnCloseModal");

	let activeUnitRow = null;
	let activeRegimentCard = null;
	let modalMode = "unit";
	let cachedUnits = [];

	// HERO 判定は is_hero フラグを優先。未提供時のみキーワードでフォールバック
	function isHeroUnit(unit) {
		if (unit && unit.is_hero !== undefined && unit.is_hero !== null) {
			return !!Number(unit.is_hero);
		}
		return /(^|,|\s)HERO(,|\s|$)/i.test((unit && unit.keywords) || "");
	}

	// HERO バッジは create_units.js のトップレベルで定義済み（window.buildHeroBadgeHtml）
	function heroBadgeHtml() {
		return typeof window.buildHeroBadgeHtml === "function"
			? window.buildHeroBadgeHtml()
			: "";
	}

	function isUniqueUnit(unit) {
		return !!Number(unit && unit.is_unique);
	}

	// ロスター内で選択済みのユニットID一覧を集約（excludeEl は再選択中の入力を除外）
	function collectSelectedUnitIds(excludeEl) {
		const ids = [];
		document
			.querySelectorAll(".regiment-card .hero-id-input, .regiment-card .unit-id-input")
			.forEach((inp) => {
				if (inp === excludeEl) return;
				if (inp.value) ids.push(String(inp.value));
			});
		return ids;
	}

	document.addEventListener("click", async (event) => {
		const target = event.target;
		if (!target) return;

		if (target.classList.contains("btn-select-unit")) {
			event.preventDefault();
			activeUnitRow = target.closest(".unit-slot-row");
			activeRegimentCard = target.closest(".regiment-card");
			if (!activeRegimentCard) return;

			const heroId = activeRegimentCard.dataset.heroId || "";
			if (!heroId) {
				alert("先に連隊長 (HERO) を選択してください。");
				return;
			}

			await openSelectionModal("unit", heroId);
			return;
		}

		if (target.classList.contains("btn-select-hero")) {
			event.preventDefault();
			activeRegimentCard = target.closest(".regiment-card");
			activeUnitRow = null;
			await openSelectionModal("hero");
		}
	});

	async function openSelectionModal(mode, heroId = "") {
		const factionId =
			document.querySelector('input[name="faction_id"]')?.value || "";

		modalMode = mode;
		if (modalTitle) {
			modalTitle.textContent =
				mode === "hero"
					? "連隊長 (HERO) を選択してください"
					: "随伴ユニットを選択してください";
		}
		if (modalUnitSearch) modalUnitSearch.value = "";

		const apiType = mode === "unit" ? "regiment" : mode;
		let url =
			getBaseURL() +
			`roster/getUnits?type=${encodeURIComponent(apiType)}&faction_id=${encodeURIComponent(factionId)}`;
		if (mode === "unit" && heroId) {
			url += `&hero_id=${encodeURIComponent(heroId)}`;
		}

		try {
			const response = await fetch(url);
			cachedUnits = await response.json();
			if (!Array.isArray(cachedUnits)) cachedUnits = [];
			renderUnitList(cachedUnits);
			unitModal.style.display = "flex";
			window.ModalScroll?.lock("unitModal");
		} catch (error) {
			console.error(error);
			alert("ユニット取得エラー");
		}
	}

	if (modalUnitSearch) {
		modalUnitSearch.addEventListener("input", () => {
			const query = modalUnitSearch.value.trim().toLowerCase();
			const filtered = cachedUnits.filter((unit) =>
				unit.name.toLowerCase().includes(query),
			);
			renderUnitList(filtered);
		});
	}

	function renderUnitList(units) {
		modalUnitList.innerHTML = "";

		if (!units.length) {
			if (modalUnitEmpty) {
				modalUnitEmpty.style.display = "block";
				modalUnitEmpty.textContent =
					modalMode === "unit"
						? "このHeroに登録可能なユニットがありません。"
						: "選択可能なHeroがありません。";
			}
			return;
		}

		if (modalUnitEmpty) modalUnitEmpty.style.display = "none";

		const excludeEl =
			modalMode === "hero"
				? activeRegimentCard?.querySelector(".hero-id-input")
				: activeUnitRow?.querySelector(".unit-id-input");
		const selectedIds = collectSelectedUnitIds(excludeEl);

		units.forEach((unit) => {
			const unitWrapper = document.createElement("div");
			unitWrapper.className = "modal-unit-wrapper";

			const unitBtn = document.createElement("button");
			unitBtn.type = "button";
			unitBtn.className = "modal-unit-btn";
			const sizeLabel =
				unit.unit_size && unit.unit_size > 1 ? ` ×${unit.unit_size}` : "";
			const isHero = isHeroUnit(unit);
			const alreadySelectedUnique =
				isUniqueUnit(unit) && selectedIds.includes(String(unit.id));
			if (alreadySelectedUnique) {
				unitBtn.disabled = true;
				unitBtn.title = "固有ユニットは1体までしか選択できません。";
			}
			if (modalMode === "unit" && isHero) {
				const heroBadge = document.createElement("span");
				heroBadge.className = "modal-unit-hero-badge";
				heroBadge.textContent = "HERO";
				unitBtn.appendChild(heroBadge);
				unitBtn.appendChild(
					document.createTextNode(`${unit.name}${sizeLabel} (${unit.points} pt)`),
				);
			} else {
				unitBtn.textContent = `${unit.name}${sizeLabel} (${unit.points} pt)`;
			}
			unitBtn.addEventListener("click", () => {
				if (modalMode === "hero") {
					selectHero(unit);
				} else {
					selectUnit(unit);
				}
			});

			const detailBtn = document.createElement("button");
			detailBtn.type = "button";
			detailBtn.className = "modal-unit-detail-btn";
			detailBtn.textContent = "i";
			detailBtn.title = "詳細を確認";
			detailBtn.addEventListener("click", (e) => {
				e.stopPropagation();
				if (window.RosterUnitDetail) {
					window.RosterUnitDetail.show(unit);
				}
			});

			unitWrapper.appendChild(unitBtn);
			unitWrapper.appendChild(detailBtn);
			modalUnitList.appendChild(unitWrapper);
		});
	}

	function selectHero(unit) {
		if (!activeRegimentCard) return;

		const heroInputCheck = activeRegimentCard.querySelector(".hero-id-input");
		if (
			isUniqueUnit(unit) &&
			collectSelectedUnitIds(heroInputCheck).includes(String(unit.id))
		) {
			alert("固有ユニットは1体までしか選択できません。");
			return;
		}

		const prevHeroId = activeRegimentCard.dataset.heroId || "";
		const heroInput = activeRegimentCard.querySelector(".hero-id-input");
		const heroDisplay = activeRegimentCard.querySelector(".hero-name-display");
		const selectBtn = activeRegimentCard.querySelector(".btn-select-hero");

		if (prevHeroId && prevHeroId !== String(unit.id)) {
			clearRegimentUnits(activeRegimentCard);
			alert("Heroを変更したため、随伴部隊をリセットしました。");
		}

		if (heroInput) heroInput.value = unit.id;
		activeRegimentCard.dataset.heroId = String(unit.id);
		activeRegimentCard.dataset.heroPoints = String(unit.points || 0);

		if (heroDisplay) {
			const sizeLabel =
				unit.unit_size && unit.unit_size > 1
					? ` <span style="color: rgba(255,255,255,0.5); font-size: 0.8rem;">×${unit.unit_size}</span>`
					: "";
			heroDisplay.innerHTML = `${unit.name}${sizeLabel} <span style="color: #ffcc00; font-size: 0.85rem; margin-left: 0.5rem; font-weight: bold;">(${unit.points} pt)</span>`;
		}
		if (selectBtn) selectBtn.textContent = "Heroを変更";

		activeRegimentCard.dataset.regimentOptionLimits = JSON.stringify(
			unit.regiment_option_limits || [],
		);

		if (typeof window.renderRegimentHint === "function") {
			window.renderRegimentHint(activeRegimentCard, unit.regiment_options || "");
		}
		if (typeof window.updateRegimentHeroState === "function") {
			window.updateRegimentHeroState(activeRegimentCard);
		}
		const heroSlot = activeRegimentCard.querySelector(".hero-slot-row");
		if (heroSlot) {
			heroSlot.dataset.unitId = String(unit.id);
			heroSlot.dataset.keywords = unit.keywords || "";
			heroSlot.dataset.isUnique = unit.is_unique ? "1" : "0";
			heroSlot.dataset.isGeneral = unit.is_general ? "1" : "0";
		}
		if (typeof window.enforceGeneralSotaisho === "function") {
			window.enforceGeneralSotaisho();
		}
		if (typeof window.updateHeroEnhancementButtons === "function") {
			window.updateHeroEnhancementButtons();
		}
		if (typeof window.updateAllPoints === "function") {
			window.updateAllPoints();
		}

		closeUnitModal();
	}

	function selectUnit(unit) {
		if (!activeUnitRow) return;

		const idInput = activeUnitRow.querySelector(".unit-id-input");
		if (
			isUniqueUnit(unit) &&
			collectSelectedUnitIds(idInput).includes(String(unit.id))
		) {
			alert("固有ユニットは1体までしか選択できません。");
			return;
		}

		const optionCard = activeUnitRow.closest(".regiment-card");
		const optionIds = unit.option_ids || [];
		const assignedOption =
			typeof window.autoAssignOption === "function"
				? window.autoAssignOption(optionCard, optionIds, activeUnitRow)
				: 0;
		if (optionIds.length > 0 && assignedOption === null) {
			alert(
				"この連隊長の編成枠（上限）に空きがありません。先に枠を空けてください。",
			);
			return;
		}

		if (idInput) idInput.value = unit.id;

		const nameDisplay = activeUnitRow.querySelector(".unit-name-display");
		if (nameDisplay) {
			const sizeLabel =
				unit.unit_size && unit.unit_size > 1
					? ` <span style="color: rgba(255,255,255,0.5); font-size: 0.8rem;">×${unit.unit_size}</span>`
					: "";
			const heroBadge = isHeroUnit(unit) ? heroBadgeHtml() : "";
			nameDisplay.innerHTML = `${heroBadge}${unit.name}${sizeLabel} <span style="color: #ffcc00; font-size: 0.85rem; margin-left: 0.5rem; font-weight: bold;">(${unit.points} pt)</span>`;
		}

		activeUnitRow.setAttribute("data-base-points", unit.points);
		activeUnitRow.setAttribute("data-points", unit.points);
		activeUnitRow.dataset.unitId = String(unit.id);
		activeUnitRow.dataset.keywords = unit.keywords || "";
		activeUnitRow.dataset.isUnique = unit.is_unique ? "1" : "0";
		activeUnitRow.dataset.isGeneral = unit.is_general ? "1" : "0";
		activeUnitRow.dataset.isHero = isHeroUnit(unit) ? "1" : "0";
		activeUnitRow.dataset.optionIds = JSON.stringify(optionIds);
		activeUnitRow.dataset.assignedOptionId =
			assignedOption && assignedOption > 0 ? String(assignedOption) : "";
		if (typeof window.refreshRegimentOptionSelectors === "function") {
			window.refreshRegimentOptionSelectors(optionCard);
		} else if (typeof window.renderUnitOptionSelector === "function") {
			window.renderUnitOptionSelector(activeUnitRow, optionCard);
		}

		const reinforceSection = activeUnitRow.querySelector(".reinforce-section");
		const reinforceCheckbox = activeUnitRow.querySelector(".reinforce-checkbox");
		const selectBtn = activeUnitRow.querySelector(".btn-select-unit");

		activeUnitRow.dataset.canReinforce = unit.can_reinforce ? "1" : "0";

		// HERO は増強(リインフォース)対象外。増強可フラグが立っているユニットのみ選択可
		const isHero = isHeroUnit(unit);
		const canReinforce = !isHero && !!Number(unit.can_reinforce);
		if (reinforceCheckbox) reinforceCheckbox.checked = false;
		if (reinforceSection) {
			reinforceSection.style.display = canReinforce ? "block" : "none";
		}
		if (selectBtn) selectBtn.textContent = "変更";

		closeUnitModal();

		if (typeof window.updateHeroEnhancementButtons === "function") {
			window.updateHeroEnhancementButtons();
		}
		if (typeof window.updateAllPoints === "function") {
			window.updateAllPoints();
		}
	}

	function clearRegimentUnits(regimentCard) {
		const slotList = regimentCard.querySelector(".units-slot-list");
		if (slotList) slotList.innerHTML = "";
	}

	document.addEventListener("change", (event) => {
		const target = event.target;
		if (!target) return;

		if (target.classList.contains("reinforce-checkbox")) {
			const activeRow = target.closest(".unit-slot-row");
			if (!activeRow) return;

			const basePts = parseInt(
				activeRow.getAttribute("data-base-points") || "0",
				10,
			);

			if (target.checked) {
				activeRow.setAttribute("data-points", basePts * 2);
			} else {
				activeRow.setAttribute("data-points", basePts);
			}

			if (typeof window.updateAllPoints === "function") {
				window.updateAllPoints();
			}
		}
	});

	function closeUnitModal() {
		unitModal.style.display = "none";
		window.ModalScroll?.unlock("unitModal");
		activeUnitRow = null;
		activeRegimentCard = null;
		cachedUnits = [];
	}

	if (btnCloseModal) btnCloseModal.addEventListener("click", closeUnitModal);
	if (unitModal) {
		unitModal.addEventListener("click", (e) => {
			if (e.target === unitModal) closeUnitModal();
		});
	}
});
