// 随伴スロットに表示する HERO バッジ（インラインスタイルでビルド非依存）。
// 複数スクリプトから参照されるため、読み込み順に依存しないトップレベルで定義する。
function buildHeroBadgeHtml() {
	return (
		'<span class="unit-hero-badge" style="display:inline-block;margin-right:0.4rem;' +
		"padding:1px 6px;border-radius:3px;background:#4a3b19;color:#ffcc00;" +
		'font-size:0.7rem;font-weight:bold;letter-spacing:0.04em;vertical-align:middle;">HERO</span>'
	);
}
window.buildHeroBadgeHtml = buildHeroBadgeHtml;

document.addEventListener("DOMContentLoaded", () => {
	const regimentsContainer = document.getElementById("regimentsContainer");
	const btnAddRegiment = document.getElementById("btnAddRegiment");
	const currentTotalPoints = document.getElementById("currentTotalPoints");
	const maxPointsLimit = parseInt(
		document.getElementById("maxPointsLimit")?.textContent || "0",
		10,
	);
	const rosterForm = document.getElementById("rosterForm");

	const regimentTemplate = document.getElementById("regimentTemplate");
	const mainUnitTemplate = document.getElementById("unitTemplate");

	let regimentCounter = 1;
	let lastGeneralRegimentIndex = null;

	function renderRegimentHint(regimentCard, optionsText) {
		if (!regimentCard) return;
		const hint = regimentCard.querySelector(".regiment-hint");
		if (!hint) return;

		const lines = (optionsText || "")
			.split("\n")
			.map((line) => line.trim())
			.filter((line) => line !== "");

		hint.innerHTML = "";
		if (lines.length === 0) {
			hint.style.display = "none";
			return;
		}

		const label = document.createElement("span");
		label.className = "regiment-hint-label";
		label.textContent = "連隊編成";
		const list = document.createElement("ul");
		list.className = "regiment-hint-list";
		lines.forEach((line) => {
			const li = document.createElement("li");
			li.textContent = line;
			list.appendChild(li);
		});
		hint.appendChild(label);
		hint.appendChild(list);
		hint.style.display = "block";
	}

	// ---- 連隊オプション枠 (max_limit) の割当ヘルパー ----

	function parseJSONAttr(value, fallback) {
		try {
			const parsed = JSON.parse(value);
			return parsed ?? fallback;
		} catch (e) {
			return fallback;
		}
	}

	// card に保存された [{option_id, max_limit, option_name}] を返す
	function getRegimentOptionLimits(card) {
		if (!card) return [];
		return parseJSONAttr(card.dataset.regimentOptionLimits, []);
	}

	function getRowOptionIds(row) {
		if (!row) return [];
		return parseJSONAttr(row.dataset.optionIds, []).map(Number);
	}

	// option_id => 容量 (0 は Infinity)
	function optionCapacityMap(card) {
		const map = {};
		getRegimentOptionLimits(card).forEach((l) => {
			const cap = Number(l.max_limit);
			map[Number(l.option_id)] = cap === 0 ? Infinity : cap;
		});
		return map;
	}

	// 明示割当(dataset.assignedOptionId)を枠別に集計（excludeRow を除外）
	function countAssignedByOption(card, excludeRow) {
		const counts = {};
		card.querySelectorAll(".unit-slot-row").forEach((row) => {
			if (row === excludeRow) return;
			const a = Number(row.dataset.assignedOptionId || 0);
			if (a > 0) counts[a] = (counts[a] || 0) + 1;
		});
		return counts;
	}

	// 既存割当を崩さず、候補が入れる空き枠を1つ返す（無ければ null）。
	// candidateOptionIds が空なら制約対象外として 0 を返す。
	function autoAssignOption(card, candidateOptionIds, excludeRow) {
		const ids = (candidateOptionIds || []).map(Number);
		if (ids.length === 0) return 0;

		const cap = optionCapacityMap(card);
		const counts = countAssignedByOption(card, excludeRow);
		const order = getRegimentOptionLimits(card).map((l) => Number(l.option_id));
		const eligibleOrdered = order.filter((o) => ids.includes(o));

		for (const o of eligibleOrdered) {
			const capacity = cap[o] ?? 0;
			const used = counts[o] || 0;
			if (used < capacity) return o;
		}
		return null;
	}

	// 行に枠セレクタ/hidden を描画する。
	// 適格枠 0/1 件: セレクタ非表示で hidden に値を設定。2件以上: <select> を表示。
	function renderUnitOptionSelector(row, card) {
		const container = row.querySelector(".unit-option-assign");
		const hidden = row.querySelector(".assigned-option-input");
		if (!hidden) return;

		const optionIds = getRowOptionIds(row);
		const limits = getRegimentOptionLimits(card);
		const eligible = limits.filter((l) => optionIds.includes(Number(l.option_id)));

		if (container) {
			container.innerHTML = "";
			container.style.display = "none";
		}

		if (eligible.length <= 1) {
			const val = eligible.length === 1 ? Number(eligible[0].option_id) : "";
			hidden.value = val === "" ? "" : String(val);
			row.dataset.assignedOptionId = val === "" ? "" : String(val);
			return;
		}

		let current = Number(row.dataset.assignedOptionId || hidden.value || 0);
		if (!eligible.some((l) => Number(l.option_id) === current)) {
			current = Number(eligible[0].option_id);
		}

		const cap = optionCapacityMap(card);
		const counts = countAssignedByOption(card, row);

		const select = document.createElement("select");
		select.className = "unit-option-select form-control";
		eligible.forEach((l) => {
			const oid = Number(l.option_id);
			const opt = document.createElement("option");
			opt.value = String(oid);
			const capacity = cap[oid];
			const remainLabel =
				capacity === Infinity
					? ""
					: `（残${Math.max(0, capacity - (counts[oid] || 0))}）`;
			opt.textContent = `${l.option_name}${remainLabel}`;
			if (oid === current) opt.selected = true;
			select.appendChild(opt);
		});

		hidden.value = String(current);
		row.dataset.assignedOptionId = String(current);
		if (container) {
			container.appendChild(select);
			container.style.display = "";
		}
	}

	// 連隊内の全行のセレクタ表示（残N）を再描画する
	function refreshRegimentOptionSelectors(card) {
		if (!card) return;
		card.querySelectorAll(".unit-slot-row").forEach((row) => {
			if (row.dataset.unitId) {
				renderUnitOptionSelector(row, card);
			}
		});
	}

	function getRegimentMaxUnits(regimentCard) {
		const isGeneral = !!regimentCard.querySelector(
			'input[name="general_regiment_index"]:checked',
		);
		return isGeneral ? 4 : 3;
	}

	function updateRegimentUnitLimitLabel(regimentCard) {
		const label = regimentCard.querySelector(".section-sub-label");
		if (!label) return;
		const maxUnits = getRegimentMaxUnits(regimentCard);
		label.textContent = `随伴部隊 (Units) — 最大${maxUnits}部隊`;
	}

	function updateAllRegimentUnitLimitLabels() {
		regimentsContainer
			.querySelectorAll(".regiment-card")
			.forEach(updateRegimentUnitLimitLabel);
	}

	function leaderIsGeneralUnit(card) {
		return card?.querySelector(".hero-slot-row")?.dataset.isGeneral === "1";
	}

	function rosterHasGeneralUnit() {
		return Array.from(
			regimentsContainer.querySelectorAll(".regiment-card"),
		).some(leaderIsGeneralUnit);
	}

	function getGeneralCard() {
		return (
			regimentsContainer
				.querySelector('input[name="general_regiment_index"]:checked')
				?.closest(".regiment-card") || null
		);
	}

	// 総大将ユニットがロスターにいる場合、ジェネラルは総大将ユニットでなければならない
	function enforceGeneralSotaisho() {
		if (!rosterHasGeneralUnit()) return;
		const current = getGeneralCard();
		if (current && leaderIsGeneralUnit(current)) return;
		const target = Array.from(
			regimentsContainer.querySelectorAll(".regiment-card"),
		).find(leaderIsGeneralUnit);
		if (!target) return;
		const radio = target.querySelector('input[name="general_regiment_index"]');
		if (radio) {
			radio.checked = true;
			lastGeneralRegimentIndex = radio.value;
		}
		updateAllRegimentUnitLimitLabels();
	}

	function revertGeneralRadio() {
		const card = lastGeneralRegimentIndex
			? regimentsContainer
					.querySelector(
						`.regiment-card input[name="general_regiment_index"][value="${lastGeneralRegimentIndex}"]`,
					)
					?.closest(".regiment-card")
			: null;
		const radio = (
			card || regimentsContainer.querySelector(".regiment-card")
		)?.querySelector('input[name="general_regiment_index"]');
		if (radio) radio.checked = true;
	}

	function updateRegimentHeroState(regimentCard) {
		const heroId = regimentCard.dataset.heroId || "";
		const unitsZone = regimentCard.querySelector(".regiment-units-zone");
		const addUnitBtn = regimentCard.querySelector(".btn-add-unit");
		const hint = regimentCard.querySelector(".hero-required-hint");

		if (heroId) {
			regimentCard.classList.remove("regiment-card--no-hero");
			regimentCard.classList.add("regiment-card--has-hero");
			if (unitsZone) unitsZone.classList.remove("regiment-units-zone--locked");
			if (addUnitBtn) addUnitBtn.disabled = false;
			if (hint) hint.style.display = "none";
		} else {
			regimentCard.classList.add("regiment-card--no-hero");
			regimentCard.classList.remove("regiment-card--has-hero");
			if (unitsZone) unitsZone.classList.add("regiment-units-zone--locked");
			if (addUnitBtn) addUnitBtn.disabled = true;
			if (hint) hint.style.display = "block";
		}
	}

	function updateAllPoints() {
		let totalPoints = 0;

		const regimentCards = regimentsContainer.querySelectorAll(".regiment-card");
		regimentCards.forEach((card) => {
			let regimentSubtotal = 0;

			const heroPoints = parseInt(card.dataset.heroPoints || "0", 10);
			if (card.dataset.heroId) {
				regimentSubtotal += heroPoints;
			}

			const unitRows = card.querySelectorAll(".unit-slot-row");
			unitRows.forEach((row) => {
				const pts = parseInt(row.getAttribute("data-points") || "0", 10);
				regimentSubtotal += pts;
			});

			const subtotalEl = card.querySelector(".regiment-points-val");
			if (subtotalEl) {
				subtotalEl.textContent = regimentSubtotal;
			}

			totalPoints += regimentSubtotal;
		});

		const armySelects = document.querySelectorAll(
			".army-options-section select, .faction-terrain-section select",
		);
		armySelects.forEach((select) => {
			if (select.value) {
				const selectedOption = select.options[select.selectedIndex];
				totalPoints += parseInt(selectedOption.dataset.points || "0", 10);
			}
		});

		if (typeof window.getEnhancementPoints === "function") {
			totalPoints += window.getEnhancementPoints();
		}

		if (currentTotalPoints) {
			currentTotalPoints.textContent = totalPoints;
			currentTotalPoints.style.color =
				totalPoints > maxPointsLimit ? "#d9534f" : "";
		}
	}

	function updateRegimentDeleteButtons() {
		const cards = regimentsContainer.querySelectorAll(".regiment-card");
		cards.forEach((card) => {
			const deleteBtn = card.querySelector(".btn-delete-regiment");
			if (deleteBtn) {
				deleteBtn.style.display = cards.length <= 1 ? "none" : "block";
			}
		});
	}

	function updateRegimentNumbers() {
		const cards = regimentsContainer.querySelectorAll(".regiment-card");
		cards.forEach((card, idx) => {
			const numEl = card.querySelector(".regiment-number");
			if (numEl) {
				numEl.textContent = `連隊 #${idx + 1} (REGIMENT ${idx + 1})`;
			}
		});
	}

	function reindexUnitSlots(regimentCard) {
		const regIndex = regimentCard.dataset.regimentIndex;
		const slotList = regimentCard.querySelector(".units-slot-list");
		if (!slotList) return;

		const rows = slotList.querySelectorAll(".unit-slot-row");
		rows.forEach((row, unitIndex) => {
			row.querySelectorAll("[name]").forEach((el) => {
				el.name = el.name.replace(
					/regiments\[\d+\]\[units\]\[\d+\]/,
					`regiments[${regIndex}][units][${unitIndex}]`,
				);
			});
			const selectBtn = row.querySelector(".btn-select-unit");
			if (selectBtn) {
				selectBtn.dataset.unitIndex = unitIndex;
			}
		});
	}

	function reindexRegiments() {
		const cards = Array.from(
			regimentsContainer.querySelectorAll(".regiment-card"),
		);
		if (!cards.length) return;

		let enhancementRefs = null;
		if (typeof window.captureEnhancementRegimentCards === "function") {
			enhancementRefs = window.captureEnhancementRegimentCards();
		}

		const generalCard = regimentsContainer
			.querySelector('input[name="general_regiment_index"]:checked')
			?.closest(".regiment-card");

		cards.forEach((card, idx) => {
			card.dataset.regimentIndex = String(idx);

			const heroInput = card.querySelector(".hero-id-input");
			if (heroInput) {
				heroInput.name = `regiments[${idx}][hero_id]`;
			}

			const generalRadio = card.querySelector(
				'input[name="general_regiment_index"]',
			);
			if (generalRadio) {
				generalRadio.value = String(idx);
			}

			card.querySelectorAll(".btn-select-hero, .btn-add-unit").forEach((btn) => {
				btn.dataset.regimentIndex = String(idx);
			});

			reindexUnitSlots(card);
		});

		regimentCounter = cards.length;

		if (generalCard && cards.includes(generalCard)) {
			const radio = generalCard.querySelector(
				'input[name="general_regiment_index"]',
			);
			if (radio) radio.checked = true;
		} else if (cards[0]) {
			const radio = cards[0].querySelector(
				'input[name="general_regiment_index"]',
			);
			if (radio) radio.checked = true;
		}

		updateRegimentNumbers();
		updateRegimentDeleteButtons();
		enforceGeneralSotaisho();
		updateAllRegimentUnitLimitLabels();
		lastGeneralRegimentIndex =
			regimentsContainer.querySelector(
				'input[name="general_regiment_index"]:checked',
			)?.value ?? null;

		if (typeof window.remapEnhancementRegimentIndices === "function") {
			window.remapEnhancementRegimentIndices(enhancementRefs);
		}
		if (typeof window.updateHeroEnhancementButtons === "function") {
			window.updateHeroEnhancementButtons();
		}
	}

	function addUnitSlot(regimentCard) {
		const heroId = regimentCard.dataset.heroId || "";
		if (!heroId) {
			alert("先に連隊長 (HERO) を選択してください。");
			return false;
		}

		const regIndex = regimentCard.dataset.regimentIndex;
		const slotList = regimentCard.querySelector(".units-slot-list");
		if (!slotList || !mainUnitTemplate) return false;

		const currentUnitsCount = slotList.querySelectorAll(".unit-slot-row").length;
		const maxUnits = getRegimentMaxUnits(regimentCard);
		if (currentUnitsCount >= maxUnits) {
			alert(`この連隊のユニットは最大${maxUnits}個までです。`);
			return false;
		}

		const unitIndex = currentUnitsCount;
		let html = mainUnitTemplate.innerHTML;
		html = html.replace(/__REG_INDEX__/g, regIndex);
		html = html.replace(/__unit_INDEX__/g, unitIndex);

		const tempDiv = document.createElement("div");
		tempDiv.innerHTML = html.trim();
		slotList.appendChild(tempDiv.firstChild);
		return true;
	}

	if (btnAddRegiment && regimentTemplate) {
		btnAddRegiment.addEventListener("click", () => {
			const currentRegimentCount =
				regimentsContainer.querySelectorAll(".regiment-card").length;
			if (currentRegimentCount >= 5) {
				alert("連隊は最大5個までです。");
				return;
			}
			const nextIndex = regimentCounter;
			const displayNum = currentRegimentCount + 1;

			let html = regimentTemplate.innerHTML;
			html = html.replace(/__REG_INDEX__/g, nextIndex);
			html = html.replace(/__REG_NUM__/g, displayNum);

			const tempDiv = document.createElement("div");
			tempDiv.innerHTML = html.trim();
			const newCard = tempDiv.firstChild;
			regimentsContainer.appendChild(newCard);
			reindexRegiments();

			updateRegimentHeroState(newCard);
			updateAllPoints();
		});
	}

	regimentsContainer.addEventListener("click", (e) => {
		if (e.target.classList.contains("btn-delete-regiment")) {
			const card = e.target.closest(".regiment-card");

			const radio = card.querySelector('input[name="general_regiment_index"]');
			if (radio && radio.checked) {
				const otherRadio = regimentsContainer.querySelector(
					`.regiment-card:not([data-regiment-index="${card.dataset.regimentIndex}"]) input[name="general_regiment_index"]`,
				);
				if (otherRadio) otherRadio.checked = true;
			}

			card.remove();
			reindexRegiments();
			updateAllPoints();
			return;
		}

		if (e.target.classList.contains("btn-add-unit")) {
			const regimentCard = e.target.closest(".regiment-card");
			if (addUnitSlot(regimentCard)) {
				updateAllPoints();
			}
			return;
		}

		if (e.target.classList.contains("btn-delete-unit")) {
			const row = e.target.closest(".unit-slot-row");
			const regimentCard = row.closest(".regiment-card");
			row.remove();
			reindexUnitSlots(regimentCard);
			refreshRegimentOptionSelectors(regimentCard);
			if (typeof window.updateHeroEnhancementButtons === "function") {
				window.updateHeroEnhancementButtons();
			}
			updateAllPoints();
		}
	});

	// 枠セレクタの変更: 満杯の枠は選べない（元に戻す）。OK なら残N表示を更新。
	regimentsContainer.addEventListener("change", (e) => {
		if (!e.target.classList.contains("unit-option-select")) return;
		const row = e.target.closest(".unit-slot-row");
		const card = e.target.closest(".regiment-card");
		if (!row || !card) return;

		const newOption = Number(e.target.value);
		const cap = optionCapacityMap(card);
		const counts = countAssignedByOption(card, row);
		const capacity = cap[newOption] ?? 0;
		const used = counts[newOption] || 0;

		if (used >= capacity) {
			alert("選択した編成枠は上限に達しています。別の枠を選ぶか、枠を空けてください。");
			e.target.value = row.dataset.assignedOptionId || "";
			return;
		}

		const hidden = row.querySelector(".assigned-option-input");
		if (hidden) hidden.value = String(newOption);
		row.dataset.assignedOptionId = String(newOption);
		refreshRegimentOptionSelectors(card);
	});

	if (rosterForm) {
		rosterForm.addEventListener("change", (e) => {
			if (e.target.name === "general_regiment_index") {
				const newCard = e.target.closest(".regiment-card");
				if (
					rosterHasGeneralUnit() &&
					newCard &&
					!leaderIsGeneralUnit(newCard)
				) {
					alert(
						"総大将を持つユニットがいるため、総大将ユニットの連隊をジェネラルに指定する必要があります。",
					);
					revertGeneralRadio();
					return;
				}

				const prevCard = lastGeneralRegimentIndex
					? regimentsContainer.querySelector(
							`.regiment-card input[name="general_regiment_index"][value="${lastGeneralRegimentIndex}"]`,
						)?.closest(".regiment-card")
					: null;
				if (prevCard) {
					const prevUnitCount =
						prevCard.querySelectorAll(".unit-slot-row").length;
					if (prevUnitCount > 3) {
						alert(
							"ジェネラルを変更すると、この連隊のユニット上限が3個になります。先に4個目のユニットを削除してください。",
						);
						const prevRadio = prevCard.querySelector(
							'input[name="general_regiment_index"]',
						);
						if (prevRadio) prevRadio.checked = true;
						return;
					}
				}
				lastGeneralRegimentIndex = e.target.value;
				updateAllRegimentUnitLimitLabels();
				return;
			}

			if (
				e.target.classList.contains("army-option-select") ||
				e.target.id === "prayerLore" ||
				e.target.id === "manifestationLore"
			) {
				updateAllPoints();
			}
		});

		rosterForm.addEventListener("submit", (e) => {
			reindexRegiments();

			const cards = regimentsContainer.querySelectorAll(".regiment-card");
			for (const card of cards) {
				const heroInput = card.querySelector(".hero-id-input");
				if (!heroInput || !heroInput.value) {
					e.preventDefault();
					alert("各連隊の連隊長 (HERO) を選択してください。");
					card.querySelector(".btn-select-hero")?.focus();
					return;
				}

				const unitRows = card.querySelectorAll(".unit-slot-row");
				for (const row of unitRows) {
					const unitId = row.querySelector(".unit-id-input")?.value;
					if (!unitId) {
						e.preventDefault();
						alert(
							"未選択の随伴部隊があります。ユニットを選ぶか、削除してください。",
						);
						row.querySelector(".btn-select-unit")?.focus();
						return;
					}
				}
			}

			if (rosterHasGeneralUnit()) {
				const generalCard = getGeneralCard();
				if (!generalCard || !leaderIsGeneralUnit(generalCard)) {
					e.preventDefault();
					alert(
						"総大将を持つユニットがいるため、総大将ユニットの連隊をジェネラルに指定してください。",
					);
					return;
				}
			}

			const total = parseInt(currentTotalPoints?.textContent || "0", 10);
			if (total > maxPointsLimit) {
				e.preventDefault();
				alert(
					`合計ポイント (${total} pt) が上限 (${maxPointsLimit} pt) を超えています。`,
				);
			}
		});
	}

	regimentsContainer.querySelectorAll(".regiment-card").forEach(updateRegimentHeroState);
	enforceGeneralSotaisho();
	updateAllRegimentUnitLimitLabels();
	lastGeneralRegimentIndex =
		regimentsContainer.querySelector(
			'input[name="general_regiment_index"]:checked',
		)?.value ?? null;

	window.updateAllPoints = updateAllPoints;
	window.updateRegimentHeroState = updateRegimentHeroState;
	window.renderRegimentHint = renderRegimentHint;
	window.reindexRegiments = reindexRegiments;
	window.enforceGeneralSotaisho = enforceGeneralSotaisho;
	window.autoAssignOption = autoAssignOption;
	window.renderUnitOptionSelector = renderUnitOptionSelector;
	window.refreshRegimentOptionSelectors = refreshRegimentOptionSelectors;
	updateAllPoints();
});

// 編集モード: 保存済みロスターの復元
document.addEventListener("DOMContentLoaded", () => {
	const regimentsContainer = document.getElementById("regimentsContainer");
	const regimentTemplate = document.getElementById("regimentTemplate");
	const mainUnitTemplate = document.getElementById("unitTemplate");
	const dataEl = document.getElementById("editRosterData");
	if (!dataEl || !regimentsContainer) return;

	let payload;
	try {
		payload = JSON.parse(dataEl.textContent);
	} catch (e) {
		return;
	}

	const regiments = payload.regiments || [];
	if (!regiments.length) return;

	function applyHero(card, hero) {
		const heroInput = card.querySelector(".hero-id-input");
		const heroDisplay = card.querySelector(".hero-name-display");
		const selectBtn = card.querySelector(".btn-select-hero");
		if (heroInput) heroInput.value = hero.id;
		card.dataset.heroId = String(hero.id);
		card.dataset.heroPoints = String(hero.points || 0);
		if (heroDisplay) {
			heroDisplay.innerHTML = `${hero.name} <span style="color: #ffcc00; font-size: 0.85rem; margin-left: 0.5rem; font-weight: bold;">(${hero.points} pt)</span>`;
		}
		if (selectBtn) selectBtn.textContent = "Heroを変更";
		card.dataset.regimentOptionLimits = JSON.stringify(
			hero.regiment_option_limits || [],
		);
		const heroSlot = card.querySelector(".hero-slot-row");
		if (heroSlot) {
			heroSlot.dataset.unitId = String(hero.id);
			heroSlot.dataset.keywords = hero.keywords || "HERO";
			heroSlot.dataset.isUnique = hero.is_unique ? "1" : "0";
			heroSlot.dataset.isGeneral = hero.is_general ? "1" : "0";
		}
		if (typeof window.renderRegimentHint === "function") {
			window.renderRegimentHint(card, hero.regiment_options || "");
		}
		if (typeof window.updateRegimentHeroState === "function") {
			window.updateRegimentHeroState(card);
		}
	}

	function applyUnit(card, unit, unitIndex) {
		const regIndex = card.dataset.regimentIndex;
		let html = mainUnitTemplate.innerHTML;
		html = html.replace(/__REG_INDEX__/g, regIndex);
		html = html.replace(/__unit_INDEX__/g, unitIndex);
		const tempDiv = document.createElement("div");
		tempDiv.innerHTML = html.trim();
		const row = tempDiv.firstChild;
		const slotList = card.querySelector(".units-slot-list");
		slotList.appendChild(row);

		const idInput = row.querySelector(".unit-id-input");
		if (idInput) idInput.value = unit.id;
		const nameDisplay = row.querySelector(".unit-name-display");
		const basePts =
			unit.basePoints !== undefined && unit.basePoints !== null
				? Number(unit.basePoints)
				: Number(unit.points);
		const pts = unit.is_reinforced ? basePts * 2 : basePts;
		const unitIsHero =
			unit.is_hero !== undefined && unit.is_hero !== null
				? !!Number(unit.is_hero)
				: /(^|,|\s)HERO(,|\s|$)/i.test(unit.keywords || "");
		if (nameDisplay) {
			const heroBadge =
				unitIsHero && typeof window.buildHeroBadgeHtml === "function"
					? window.buildHeroBadgeHtml()
					: "";
			nameDisplay.innerHTML = `${heroBadge}${unit.name} <span style="color: #ffcc00; font-size: 0.85rem; margin-left: 0.5rem; font-weight: bold;">(${basePts} pt)</span>`;
		}
		row.setAttribute("data-base-points", basePts);
		row.setAttribute("data-points", pts);
		row.dataset.unitId = String(unit.id);
		row.dataset.keywords = unit.keywords || "";
		row.dataset.isUnique = unit.is_unique ? "1" : "0";
		row.dataset.isGeneral = unit.is_general ? "1" : "0";
		row.dataset.isHero = unitIsHero ? "1" : "0";
		row.dataset.canReinforce = unit.can_reinforce ? "1" : "0";
		row.dataset.optionIds = JSON.stringify(unit.option_ids || []);
		row.dataset.assignedOptionId =
			unit.assigned_option_id != null ? String(unit.assigned_option_id) : "";
		if (typeof window.renderUnitOptionSelector === "function") {
			window.renderUnitOptionSelector(row, card);
		}
		const reinforceSection = row.querySelector(".reinforce-section");
		const reinforceCheckbox = row.querySelector(".reinforce-checkbox");
		const isHero =
			unit.is_hero !== undefined && unit.is_hero !== null
				? !!Number(unit.is_hero)
				: /(^|,|\s)HERO(,|\s|$)/i.test(unit.keywords || "");
		const canReinforce = !isHero && !!Number(unit.can_reinforce);
		if (reinforceSection) {
			reinforceSection.style.display = canReinforce ? "block" : "none";
		}
		if (reinforceCheckbox && unit.is_reinforced && canReinforce) {
			reinforceCheckbox.checked = true;
		}
		const selectBtn = row.querySelector(".btn-select-unit");
		if (selectBtn) selectBtn.textContent = "変更";
	}

	regiments.forEach((reg, idx) => {
		let card;
		if (idx === 0) {
			card = regimentsContainer.querySelector(".regiment-card");
		} else {
			if (!regimentTemplate) return;
			let html = regimentTemplate.innerHTML;
			html = html.replace(/__REG_INDEX__/g, String(idx));
			html = html.replace(/__REG_NUM__/g, String(idx + 1));
			const tempDiv = document.createElement("div");
			tempDiv.innerHTML = html.trim();
			card = tempDiv.firstChild;
			regimentsContainer.appendChild(card);
			if (typeof window.updateRegimentHeroState === "function") {
				window.updateRegimentHeroState(card);
			}
		}


		if (reg.is_general) {
			const radio = card.querySelector('input[name="general_regiment_index"]');
			if (radio) radio.checked = true;
		}

		if (reg.hero) applyHero(card, reg.hero);

		(reg.units || []).forEach((unit, unitIdx) => {
			applyUnit(card, unit, unitIdx);
		});
	});

	if (typeof window.reindexRegiments === "function") {
		window.reindexRegiments();
	}
	if (typeof window.updateHeroEnhancementButtons === "function") {
		window.updateHeroEnhancementButtons();
	}
	if (typeof window.updateAllPoints === "function") {
		window.updateAllPoints();
	}
});
