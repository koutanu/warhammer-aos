/**
 * ロスター詳細ページ: アーミーオプション / エンハンスメント /
 * バトルタクティクス / ユニットのモーダル表示
 */
(function () {
	const abilityModalId = "rosterAbilityModal";
	const stageLabelEl = document.getElementById("rosterDetailStageLabels");
	let stageLabels = {
		affray: "Affray",
		strike: "Strike",
		domination: "Domination",
	};

	try {
		if (stageLabelEl?.textContent) {
			stageLabels = {
				...stageLabels,
				...JSON.parse(stageLabelEl.textContent),
			};
		}
	} catch (e) {
		/* ignore */
	}

	function escapeHtml(text) {
		const div = document.createElement("div");
		div.textContent = text == null ? "" : String(text);
		return div.innerHTML;
	}

	function formatTrigger(detail) {
		const conditionJa = String(detail?.trigger_condition_ja || "").trim();
		if (conditionJa) return conditionJa;

		const raw = String(
			detail?.trigger_phase || detail?.trigger || "",
		).trim();
		if (!raw) return "";
		if (
			typeof MatchPhases !== "undefined" &&
			MatchPhases.formatTriggerPhases
		) {
			return MatchPhases.formatTriggerPhases(raw);
		}
		return raw;
	}

	function parseDetail(btn) {
		const raw = btn.getAttribute("data-detail");
		if (!raw) return null;
		try {
			return JSON.parse(raw);
		} catch (e) {
			console.error("Failed to parse detail payload", e);
			return null;
		}
	}

	function buildStagesHtml(stages) {
		if (!Array.isArray(stages) || stages.length === 0) {
			return '<p class="detail-empty">段階情報はありません。</p>';
		}
		return `<div class="roster-ability-stages">${stages
			.map((stage) => {
				const label =
					stageLabels[stage.stage] || stage.stage || "Stage";
				const vp =
					stage.victory_points != null ? stage.victory_points : 2;
				return `<div class="roster-ability-stage">
					<span class="roster-ability-stage-badge">${escapeHtml(label)}</span>
					<strong class="roster-ability-stage-name">${escapeHtml(stage.name || "")}</strong>
					<p class="roster-ability-stage-effect">${escapeHtml(stage.effect || "")}</p>
					<span class="roster-ability-stage-vp">${escapeHtml(String(vp))} VP</span>
				</div>`;
			})
			.join("")}</div>`;
	}

	function openAbilityModal(detail) {
		const modal = document.getElementById(abilityModalId);
		if (!modal || !detail) return;

		const titleEl = document.getElementById("rosterAbilityModalTitle");
		const triggerEl = document.getElementById("rosterAbilityModalTrigger");
		const metaEl = document.getElementById("rosterAbilityModalMeta");
		const bodyEl = document.getElementById("rosterAbilityModalBody");

		if (titleEl) titleEl.textContent = detail.title || "詳細";

		const triggerText = formatTrigger(detail);
		if (triggerEl) {
			if (triggerText) {
				triggerEl.textContent = triggerText;
				triggerEl.style.display = "";
			} else {
				triggerEl.textContent = "";
				triggerEl.style.display = "none";
			}
		}

		if (metaEl) {
			if (detail.meta) {
				metaEl.textContent = detail.meta;
				metaEl.style.display = "";
			} else {
				metaEl.textContent = "";
				metaEl.style.display = "none";
			}
		}

		if (bodyEl) {
			if (Array.isArray(detail.stages) && detail.stages.length > 0) {
				bodyEl.innerHTML = buildStagesHtml(detail.stages);
			} else if (detail.effect_html) {
				bodyEl.innerHTML = `<div class="roster-ability-effect roster-ability-effect--html">${
					detail.effect || ""
				}</div>`;
			} else {
				const flavor = detail.flavor
					? `<small class="roster-ability-flavor">${escapeHtml(detail.flavor)}</small>`
					: "";
				bodyEl.innerHTML = `<div class="roster-ability-effect">${escapeHtml(
					detail.effect || "",
				)}</div>${flavor}`;
			}
		}

		modal.style.display = "flex";
		window.ModalScroll?.lock(abilityModalId);
	}

	function closeAbilityModal() {
		const modal = document.getElementById(abilityModalId);
		if (modal) modal.style.display = "none";
		window.ModalScroll?.unlock(abilityModalId);
	}

	function openUnitModal(btn) {
		const unitId = parseInt(btn.getAttribute("data-unit-id") || "0", 10);
		if (!unitId || typeof RosterUnitDetail === "undefined") return;
		RosterUnitDetail.show({
			id: unitId,
			name: btn.getAttribute("data-unit-name") || "",
		});
	}

	function init() {
		document.querySelectorAll(".js-open-ability").forEach((btn) => {
			btn.addEventListener("click", () => {
				const detail = parseDetail(btn);
				if (detail) openAbilityModal(detail);
			});
		});

		document.querySelectorAll(".js-open-unit").forEach((btn) => {
			btn.addEventListener("click", () => openUnitModal(btn));
		});

		document
			.getElementById("btnCloseRosterAbilityModal")
			?.addEventListener("click", closeAbilityModal);
		document
			.querySelectorAll(".js-close-ability-modal")
			.forEach((btn) => btn.addEventListener("click", closeAbilityModal));

		const modal = document.getElementById(abilityModalId);
		modal?.addEventListener("click", (e) => {
			if (e.target === modal) closeAbilityModal();
		});
	}

	document.addEventListener("DOMContentLoaded", init);
})();
