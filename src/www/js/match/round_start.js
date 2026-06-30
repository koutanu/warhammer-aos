/**
 * ラウンド開始アビリティ バナー
 * - trigger_phase=round_start のアビリティを各ラウンド開始時にモーダルで表示する。
 * - 配置完了直後の第1ラウンド、および「次のラウンドへ」での各ラウンド開始を検知する。
 * - ラウンド中は再表示ボタンから開き直せる。usage がある場合は使用済みトグルも可能。
 */
document.addEventListener("DOMContentLoaded", () => {
	const app = document.getElementById("scoreboardApp");
	const modal = document.getElementById("roundStartModal");
	if (!app || !modal) return;

	const matchId = parseInt(app.dataset.matchId, 10);
	const token = app.dataset.token;
	const viewerSlot = parseInt(app.dataset.viewerSlot, 10) || 1;
	const baseUrl = typeof getBaseURL === "function" ? getBaseURL() : "";

	const els = {
		modal,
		list: document.getElementById("roundStartList"),
		empty: document.getElementById("roundStartEmpty"),
		title: document.getElementById("roundStartTitle"),
		close: document.getElementById("roundStartClose"),
		showBtn: document.getElementById("btnShowRoundStart"),
	};

	let posting = false;

	// 初回ロード状態を記録（ロード直後は自動表示しない）
	const initial = readGame();
	let lastSeenRound = initial.battleRound;
	let lastSeenDeployment = initial.phase === "deployment";

	bindEvents();
	updateShowButton();

	window.addEventListener("matchStateUpdated", () => {
		const game = readGame();
		const isDeployment = game.phase === "deployment";
		const roundAdvanced = game.battleRound > lastSeenRound;
		const leftDeployment = lastSeenDeployment && !isDeployment;

		if ((roundAdvanced || leftDeployment) && !isDeployment) {
			openModal();
		} else if (els.modal.style.display !== "none") {
			// 表示中（トグル後の再描画など）は内容のみ更新
			renderList();
		}

		lastSeenRound = game.battleRound;
		lastSeenDeployment = isDeployment;
		updateShowButton();
	});

	function readGame() {
		const state = MatchStateManager.getState?.() || {};
		const game = state.game || {};
		return {
			battleRound: game.battleRound ?? state.currentRound ?? 1,
			phase: game.phase || "hero",
		};
	}

	function getRoundStartAbilities() {
		const state = MatchStateManager.getState?.() || {};
		const player = (state.players || []).find((p) => p.slot === viewerSlot);
		const deck = player?.abilitiesDeck || [];
		return deck.filter((ab) => {
			const phaseNorms =
				ab.triggerPhaseNorms ||
				MatchPhases.normalizePhases(ab.triggerPhase);
			return phaseNorms.includes("round_start");
		});
	}

	function bindEvents() {
		els.close?.addEventListener("click", closeModal);
		els.showBtn?.addEventListener("click", () => {
			if (getRoundStartAbilities().length) openModal();
		});

		els.list?.addEventListener("click", (e) => {
			const toggleBtn = e.target.closest(".ability-used-toggle");
			if (toggleBtn) {
				const card = toggleBtn.closest(".phase-ability-card");
				if (card) toggleUsed(card.dataset.abilityKey);
				return;
			}
			toggleCard(e.target.closest(".phase-ability-card"));
		});
	}

	function updateShowButton() {
		if (!els.showBtn) return;
		const game = readGame();
		const has = getRoundStartAbilities().length > 0;
		els.showBtn.style.display =
			has && game.phase !== "deployment" ? "" : "none";
	}

	function openModal() {
		renderList();
		els.modal.style.display = "flex";
		window.ModalScroll?.lock("roundStartModal");
	}

	function closeModal() {
		els.modal.style.display = "none";
		window.ModalScroll?.unlock("roundStartModal");
	}

	function renderList() {
		if (!els.list) return;
		const abilities = getRoundStartAbilities();
		const game = readGame();

		if (els.title) {
			els.title.textContent = `ラウンド ${game.battleRound} 開始時に使えるアビリティ`;
		}

		els.list.innerHTML = "";

		if (!abilities.length) {
			if (els.empty) {
				els.empty.style.display = "block";
				els.empty.textContent =
					"ラウンド開始時に使えるアビリティはありません。";
			}
			return;
		}

		if (els.empty) els.empty.style.display = "none";

		abilities
			.slice()
			.sort((a, b) => (a.name || "").localeCompare(b.name || ""))
			.forEach((ab) => els.list.appendChild(buildCard(ab)));
	}

	function buildCard(ab) {
		const state = MatchStateManager.getState();
		const game = state.game || { usedAbilities: {} };
		const usedMap = game.usedAbilities?.[viewerSlot] || {};

		const tracked = MatchPhases.isUsageTracked(ab);
		const used = tracked && !!usedMap[ab.key]?.used;
		const effectText = String(ab.effect || "").trim();
		const hasEffect = effectText !== "";

		const card = document.createElement("article");
		card.className =
			"phase-ability-card" +
			(used ? " is-used" : "") +
			(hasEffect ? " is-expandable" : "") +
			(ab.category ? ` cat-${ab.category}` : "");
		card.dataset.abilityKey = ab.key;
		if (hasEffect) {
			card.setAttribute("role", "button");
			card.setAttribute("tabindex", "0");
			card.setAttribute("aria-expanded", "false");
		}

		const categoryLabel = MatchPhases.labelCategoryJa(ab.category);
		const unitLabel = formatUnitNames(ab);

		const commandCost =
			ab.commandCost === 0 || ab.commandCost ? Number(ab.commandCost) : null;
		const cpBadge =
			commandCost !== null && !Number.isNaN(commandCost) && commandCost > 0
				? `<span class="ability-cp-badge">CP ${commandCost}</span>`
				: "";

		const freq = MatchPhases.frequencyInfo(ab);
		const freqBadge = freq.label
			? `<span class="ability-freq-badge freq-${escapeHtml(freq.kind)}">${escapeHtml(freq.label)}</span>`
			: "";

		const conditionText = String(ab.triggerCondition || "").trim();
		const metaBadges = conditionText
			? `<span class="ability-phase-badge ability-condition-badge">${escapeHtml(conditionText)}</span>`
			: `<span class="ability-phase-badge">${escapeHtml(MatchPhases.labelPhaseJa("round_start"))}</span>`;

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
					</div>
					<strong class="ability-name">${escapeHtml(ab.name)}</strong>
				</div>
				${usedToggle}
			</div>
			<div class="ability-card-meta">
				${unitLabel ? `<span class="ability-source">${escapeHtml(unitLabel)}</span>` : ""}
				${metaBadges}
				${freqBadge}
				${used ? '<span class="ability-used-badge">使用済み</span>' : ""}
				${hasEffect ? '<span class="ability-expand-chevron" aria-hidden="true">▾</span>' : ""}
			</div>
			${hasEffect ? `<div class="ability-effect-box" style="display:none;"><p>${escapeHtml(effectText)}</p></div>` : ""}
		`;

		return card;
	}

	function toggleCard(card) {
		if (!card) return;
		const box = card.querySelector(".ability-effect-box");
		if (!box) return;
		const open = box.style.display === "block";
		box.style.display = open ? "none" : "block";
		card.classList.toggle("is-open", !open);
		card.setAttribute("aria-expanded", String(!open));
	}

	function toggleUsed(abilityKey) {
		if (posting || !abilityKey) return;
		posting = true;

		fetch(baseUrl + "match/toggleAbility", {
			method: "POST",
			headers: { "Content-Type": "application/json" },
			body: JSON.stringify({
				token,
				matchId,
				playerSlot: viewerSlot,
				abilityKey,
				phase: "round_start",
			}),
		})
			.then((res) => res.json())
			.then((data) => {
				if (!data.success) {
					alert(data.message || "更新に失敗しました。");
					return;
				}
				MatchStateManager.applyServerState(data.state);
			})
			.catch(() => alert("通信エラーが発生しました。"))
			.finally(() => {
				posting = false;
			});
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

	function escapeHtml(text) {
		const div = document.createElement("div");
		div.textContent = text ?? "";
		return div.innerHTML;
	}
});
