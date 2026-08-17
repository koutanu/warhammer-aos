/**
 * 射撃・近接の攻撃参照UI
 * データシートの武器タップ → 相手ユニット選択 → 攻撃に必要な数値の簡易対照
 */
window.MatchCombatRef = (function () {
	const modalId = "combatRefModal";
	const detailCache = new Map();

	let els = {};
	let session = null;
	let fetchSeq = 0;

	function getEl(id) {
		return document.getElementById(id);
	}

	function baseUrl() {
		return typeof getBaseURL === "function" ? getBaseURL() : "/";
	}

	function escapeHtml(str) {
		return String(str ?? "")
			.replace(/&/g, "&amp;")
			.replace(/</g, "&lt;")
			.replace(/>/g, "&gt;")
			.replace(/"/g, "&quot;");
	}

	function escapeAttr(str) {
		return String(str || "")
			.replace(/&/g, "&amp;")
			.replace(/"/g, "&quot;")
			.replace(/</g, "&lt;");
	}

	function formatStatPlus(value) {
		if (value === null || value === undefined) return "";
		const str = String(value).trim();
		if (str === "") return "";
		if (/\+$/.test(str)) return str;
		return /^\d+$/.test(str) ? `${str}+` : str;
	}

	function formatSaveDisplay(save, ward) {
		const saveStr = formatStatPlus(save);
		if (!saveStr) return "-";
		const wardStr = formatStatPlus(ward);
		return wardStr ? `${saveStr}/${wardStr}` : saveStr;
	}

	function isRangedWeapon(weapon) {
		const w = weapon || {};
		const type = String(w.type || "")
			.trim()
			.toLowerCase();
		return type === "ranged" || (type === "" && !!w.rng);
	}

	function canAttackWith(weapon) {
		if (!getEl(modalId)) return false;
		const phase = window.MatchAbilityPanel?.getViewPhase?.();
		if (phase !== "shooting" && phase !== "combat") return false;
		const ranged = isRangedWeapon(weapon);
		return phase === "shooting" ? ranged : !ranged;
	}

	function viewerSlot() {
		const app = getEl("scoreboardApp");
		const slot = parseInt(app?.dataset?.viewerSlot, 10);
		return Number.isFinite(slot) && slot > 0 ? slot : 1;
	}

	function resolveAttackerSlot(attacker) {
		const slot = parseInt(attacker?.playerSlot, 10);
		if (Number.isFinite(slot) && slot > 0) return slot;
		return viewerSlot();
	}

	function opponentSlotFor(attackerSlot) {
		return attackerSlot === 1 ? 2 : 1;
	}

	function livingUnits(slot) {
		const units = window.MatchAbilityPanel?.getLivingUnitsForSlot?.(slot);
		return Array.isArray(units) ? units : [];
	}

	function findRosterUnit(slot, instanceKey, unitId) {
		const units = livingUnits(slot);
		const key = String(instanceKey || "").trim();
		if (key) {
			const byKey = units.find(
				(unit) => String(unit.instanceKey || "").trim() === key,
			);
			if (byKey) return byKey;
		}
		const id = Number(unitId);
		if (!id) return null;
		return units.find((unit) => Number(unit.id) === id) || null;
	}

	function modelCount(rosterUnit, info) {
		const size = Number(rosterUnit?.unit_size ?? info?.unit_size);
		if (!Number.isFinite(size) || size <= 0) return "-";
		const reinforced = Number(rosterUnit?.is_reinforced) === 1;
		return String(reinforced ? size * 2 : size);
	}

	function keywordsText(detailData, fallback) {
		const details = detailData?.keyword_details;
		if (Array.isArray(details) && details.length) {
			const names = details
				.map((item) => item.display_name || item.name)
				.map((name) => String(name || "").trim())
				.filter(Boolean);
			if (names.length) return names.join(", ");
		}
		const fromInfo = String(detailData?.info?.keywords || "").trim();
		if (fromInfo) return fromInfo;
		const extra = String(fallback || "").trim();
		return extra || "-";
	}

	function unitThumbHtml(unit, info) {
		const image = unit?.image || info?.image || "";
		if (image) {
			return `<img src="${escapeAttr(baseUrl() + image)}" alt="" loading="lazy">`;
		}
		const name = unit?.name || info?.name || "?";
		const initial = String(name).trim().charAt(0).toUpperCase() || "?";
		return `<span class="combat-ref-identity-placeholder">${escapeHtml(initial)}</span>`;
	}

	function pickerThumbHtml(unit) {
		const image = unit.image || "";
		const imageHtml = image
			? `<img src="${escapeAttr(baseUrl() + image)}" alt="" loading="lazy">`
			: `<span class="ability-unit-thumb-placeholder">${escapeHtml((unit.name || "?").trim().charAt(0).toUpperCase())}</span>`;
		return `<button type="button" class="ability-unit-thumb is-selectable"
				data-unit-id="${unit.id}"
				data-unit-key="${escapeAttr(unit.instanceKey || "")}"
				data-unit-name="${escapeAttr(unit.name || "")}"
				aria-label="${escapeAttr(unit.name || "ユニット")}">
				<span class="ability-unit-thumb-image">${imageHtml}</span>
				<span class="ability-unit-thumb-label">${escapeHtml(unit.name || "")}</span>
			</button>`;
	}

	function weaponStat(label, value) {
		return `<div class="combat-ref-stat">
			<div class="combat-ref-stat-label">${escapeHtml(label)}</div>
			<div class="combat-ref-stat-value">${escapeHtml(value)}</div>
		</div>`;
	}

	const ARMY_PASSIVE_CATEGORIES = new Set([
		"battleplan",
		"battletrait",
	]);
	const EXCLUDED_PASSIVE_CATEGORIES = new Set(["common", "formation"]);

	function attackPhaseForWeapon(weapon) {
		return isRangedWeapon(weapon) ? "shooting" : "combat";
	}

	function rawTriggerPhase(ab) {
		return String(ab?.trigger_phase || ab?.triggerPhase || "").trim();
	}

	function isPassiveAbility(ab) {
		return String(ab?.activation || "").toLowerCase() === "passive";
	}

	function matchesAttackPhase(ab, attackPhase) {
		const raw = rawTriggerPhase(ab);
		if (!raw) return false;
		const norms =
			typeof MatchPhases !== "undefined" && MatchPhases.normalizePhases
				? MatchPhases.normalizePhases(raw)
				: [raw.toLowerCase()];
		return norms.includes(attackPhase) || norms.includes("any");
	}

	function deckAppliesToUnit(ab, unitName) {
		const names = ab.unitNames?.length
			? ab.unitNames
			: ab.unitName
				? [ab.unitName]
				: [];
		if (!names.length) {
			return ARMY_PASSIVE_CATEGORIES.has(ab.category || "unit");
		}
		const target = String(unitName || "").trim();
		if (!target) return false;
		return names.some((name) => String(name || "").trim() === target);
	}

	function playerForSlot(slot) {
		const slotNum = Number(slot);
		if (!Number.isFinite(slotNum) || slotNum < 1) return null;
		if (typeof MatchStateManager !== "undefined") {
			if (typeof MatchStateManager.getPlayer === "function") {
				const fromGetter = MatchStateManager.getPlayer(slotNum);
				if (fromGetter) return fromGetter;
			}
			const players = MatchStateManager.getState?.()?.players || [];
			return players.find((p) => Number(p.slot) === slotNum) || null;
		}
		return null;
	}

	function collectPassives({ slot, unitName, detailData, attackPhase }) {
		const seen = new Set();
		const out = [];

		function push(ab) {
			if (!isPassiveAbility(ab) || !matchesAttackPhase(ab, attackPhase)) {
				return;
			}
			const name = String(ab.name || "").trim();
			if (!name || seen.has(name)) return;
			seen.add(name);
			out.push({
				name,
				effect: String(ab.effect || "").trim(),
			});
		}

		(detailData?.abilities || []).forEach(push);
		const deck = playerForSlot(slot)?.abilitiesDeck || [];
		deck.forEach((ab) => {
			if (EXCLUDED_PASSIVE_CATEGORIES.has(ab.category || "")) return;
			if (!deckAppliesToUnit(ab, unitName)) return;
			push(ab);
		});
		return out;
	}

	function passivesHtml(items) {
		if (!items.length) return "";
		const cards = items
			.map(
				(item) =>
					`<div class="combat-ref-passive">
						<p class="combat-ref-passive-name">${escapeHtml(item.name)}</p>
						${
							item.effect
								? `<p class="combat-ref-passive-effect">${escapeHtml(item.effect)}</p>`
								: ""
						}
					</div>`,
			)
			.join("");
		return `<div class="combat-ref-passives">${cards}</div>`;
	}

	function showModal() {
		if (!els.modal) return;
		els.modal.style.display = "flex";
		window.ModalScroll?.lock(modalId);
	}

	function close() {
		fetchSeq += 1;
		session = null;
		if (els.modal) els.modal.style.display = "none";
		if (els.pickerList) els.pickerList.innerHTML = "";
		if (els.compareBody) els.compareBody.innerHTML = "";
		window.ModalScroll?.unlock(modalId);
	}

	function showPickerStep() {
		if (els.pickerStep) els.pickerStep.style.display = "flex";
		if (els.compareStep) els.compareStep.style.display = "none";
	}

	function showCompareStep() {
		if (els.pickerStep) els.pickerStep.style.display = "none";
		if (els.compareStep) els.compareStep.style.display = "flex";
	}

	function renderPicker() {
		if (!session) return;
		const defenderSlot = opponentSlotFor(session.attackerSlot);
		const candidates = livingUnits(defenderSlot)
			.filter((unit) => unit.id)
			.sort((a, b) => (a.name || "").localeCompare(b.name || ""));

		const weaponName = session.weapon?.name || "武器";
		const attackerName =
			session.detailData?.info?.name || session.attacker?.name || "ユニット";
		if (els.pickerLead) {
			els.pickerLead.textContent = `${attackerName} の ${weaponName} で攻撃するユニットを選んでください。`;
		}

		if (els.pickerList) {
			els.pickerList.innerHTML = candidates.length
				? `<div class="ability-unit-thumbs" role="list">${candidates.map(pickerThumbHtml).join("")}</div>`
				: "";
		}
		if (els.pickerEmpty) {
			els.pickerEmpty.style.display = candidates.length ? "none" : "flex";
		}
		showPickerStep();
		showModal();
	}

	function identityHtml(label, name, imageHtml) {
		return `<div class="combat-ref-side-label">${escapeHtml(label)}</div>
			<div class="combat-ref-identity">
				<div class="combat-ref-identity-image">${imageHtml}</div>
				<p class="combat-ref-identity-name">${escapeHtml(name)}</p>
			</div>`;
	}

	function renderCompare() {
		if (!session || !els.compareBody) return;
		const attackerInfo = session.detailData?.info || {};
		const defenderDetail = session.defenderDetail || {};
		const defenderInfo = defenderDetail.info || {};
		const weapon = session.weapon || {};
		const attackerRoster = session.attackerRoster;
		const defenderRoster = session.defenderRoster;

		const attackerName =
			attackerInfo.name || session.attacker?.name || "攻撃側";
		const defenderName =
			defenderInfo.name || session.defender?.name || "防御側";
		const rangeDisplay = weapon.rng ? `${weapon.rng}"` : "近接";
		const hit = weapon.hit ? `${weapon.hit}+` : "-";
		const wnd = weapon.wnd ? `${weapon.wnd}+` : "-";
		const saveLabel = formatStatPlus(defenderInfo.ward)
			? "防御力/加護"
			: "防御力";
		const attackPhase = attackPhaseForWeapon(weapon);
		const attackerPassives = collectPassives({
			slot: session.attackerSlot,
			unitName: attackerName,
			detailData: session.detailData,
			attackPhase,
		});
		const defenderPassives = collectPassives({
			slot: session.defenderSlot || opponentSlotFor(session.attackerSlot),
			unitName: defenderName,
			detailData: defenderDetail,
			attackPhase,
		});

		if (els.compareLead) {
			els.compareLead.textContent = `${attackerName} の ${weapon.name || "武器"} → ${defenderName}`;
		}

		els.compareBody.innerHTML = `
			<section class="combat-ref-side combat-ref-side--atk">
				${identityHtml("攻撃", attackerName, unitThumbHtml(attackerRoster || session.attacker, attackerInfo))}
				<div class="combat-ref-stat-grid">
					${weaponStat("体力", attackerInfo.wounds ?? "-")}
					${weaponStat("モデル", modelCount(attackerRoster, attackerInfo))}
				</div>
				<div class="combat-ref-weapon">
					<p class="combat-ref-weapon-name">${escapeHtml(weapon.name || "武器")}</p>
					<div class="combat-ref-weapon-stats">
						${weaponStat("射程", rangeDisplay)}
						${weaponStat("回数", weapon.atk ?? "-")}
						${weaponStat("ヒット", hit)}
						${weaponStat("ウーンズ", wnd)}
						${weaponStat("貫通", weapon.rnd || "0")}
						${weaponStat("ダメージ", weapon.dmg ?? "-")}
					</div>
					${
						weapon.abilities
							? `<p class="combat-ref-weapon-ability">★ ${escapeHtml(weapon.abilities)}</p>`
							: ""
					}
				</div>
				${passivesHtml(attackerPassives)}
			</section>
			<section class="combat-ref-side combat-ref-side--def">
				${identityHtml("防御", defenderName, unitThumbHtml(defenderRoster || session.defender, defenderInfo))}
				<div class="combat-ref-stat-grid">
					${weaponStat("体力", defenderInfo.wounds ?? "-")}
					${weaponStat(saveLabel, formatSaveDisplay(defenderInfo.save, defenderInfo.ward))}
					${weaponStat("モデル", modelCount(defenderRoster, defenderInfo))}
				</div>
				<p class="combat-ref-keywords">${escapeHtml(keywordsText(defenderDetail, session.defender?.keywords))}</p>
				${passivesHtml(defenderPassives)}
			</section>
		`;
	}

	async function fetchUnitDetail(unitId) {
		const id = Number(unitId);
		if (!id) throw new Error("無効なユニットです。");
		if (detailCache.has(id)) return detailCache.get(id);
		const response = await fetch(
			`${baseUrl()}roster/getUnitDetail?unit_id=${encodeURIComponent(id)}`,
		);
		if (!response.ok) throw new Error("詳細データの取得に失敗しました。");
		const data = await response.json();
		detailCache.set(id, data);
		return data;
	}

	async function selectDefender(unit) {
		if (!session || !unit?.id) return;
		const seq = ++fetchSeq;
		session.defender = unit;
		session.defenderRoster = unit;
		session.defenderSlot = opponentSlotFor(session.attackerSlot);
		showCompareStep();
		if (els.compareLead) {
			els.compareLead.textContent = "防御側データを読み込み中...";
		}
		if (els.compareBody) {
			els.compareBody.innerHTML =
				'<p class="combat-ref-status">防御側データを読み込み中...</p>';
		}
		try {
			const detail = await fetchUnitDetail(unit.id);
			if (seq !== fetchSeq || !session) return;
			session.defenderDetail = detail;
			renderCompare();
		} catch (error) {
			console.error(error);
			if (seq !== fetchSeq || !session) return;
			if (els.compareBody) {
				els.compareBody.innerHTML =
					'<p class="combat-ref-status is-error">防御側データの取得に失敗しました。</p>';
			}
		}
	}

	function start({ attacker, weapon, detailData }) {
		if (!els.modal || !attacker || !weapon) return;
		if (!canAttackWith(weapon)) return;
		const attackerSlot = resolveAttackerSlot(attacker);
		session = {
			attacker,
			weapon,
			detailData: detailData || {},
			attackerSlot,
			attackerRoster: findRosterUnit(
				attackerSlot,
				attacker.instanceKey,
				attacker.id || attacker.unitId,
			),
			defender: null,
			defenderRoster: null,
			defenderDetail: null,
			defenderSlot: opponentSlotFor(attackerSlot),
		};
		const attackerId = Number(detailData?.info?.id || attacker.id);
		if (attackerId && detailData) {
			detailCache.set(attackerId, detailData);
		}
		renderPicker();
	}

	function bind() {
		els.pickerCancel?.addEventListener("click", close);
		els.compareClose?.addEventListener("click", close);
		els.changeTarget?.addEventListener("click", () => {
			if (!session) return;
			session.defender = null;
			session.defenderRoster = null;
			session.defenderDetail = null;
			renderPicker();
		});
		els.pickerList?.addEventListener("click", (e) => {
			const thumb = e.target.closest(".ability-unit-thumb[data-unit-id]");
			if (!thumb || !session) return;
			const unitId = parseInt(thumb.dataset.unitId, 10);
			if (!unitId) return;
			const defenderSlot = opponentSlotFor(session.attackerSlot);
			const unit =
				findRosterUnit(
					defenderSlot,
					thumb.dataset.unitKey,
					unitId,
				) || {
					id: unitId,
					name: thumb.dataset.unitName || "",
					instanceKey: thumb.dataset.unitKey || "",
				};
			selectDefender(unit);
		});
		els.modal?.addEventListener("click", (e) => {
			if (e.target === els.modal) close();
		});
	}

	function init() {
		els = {
			modal: getEl(modalId),
			pickerStep: getEl("combatRefPickerStep"),
			compareStep: getEl("combatRefCompareStep"),
			pickerLead: getEl("combatRefPickerLead"),
			pickerList: getEl("combatRefPickerList"),
			pickerEmpty: getEl("combatRefPickerEmpty"),
			pickerCancel: getEl("combatRefPickerCancel"),
			compareLead: getEl("combatRefCompareLead"),
			compareBody: getEl("combatRefCompareBody"),
			changeTarget: getEl("combatRefChangeTarget"),
			compareClose: getEl("combatRefCompareClose"),
		};
		if (!els.modal) return;
		bind();
	}

	document.addEventListener("DOMContentLoaded", init);

	return { canAttackWith, start, close };
})();
