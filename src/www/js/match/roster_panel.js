/**
 * マッチプレイ中のロスター参照（メイン表示）
 * - 自分 / 相手 のロスターをトグルで切替
 * - ユニットをタップで詳細モーダル
 */
document.addEventListener("DOMContentLoaded", () => {
	const app = document.getElementById("scoreboardApp");
	const view = document.getElementById("rosterView");
	const panelBody = document.getElementById("rosterPanelBody");
	const panelTitle = document.getElementById("rosterViewTitle");
	const btnViewOpponent = document.getElementById("btnViewOpponentRoster");
	const btnCopyP2Url = document.getElementById("btnCopyP2Url");

	if (!view || !panelBody) return;

	const viewerSlot = app ? parseInt(app.dataset.viewerSlot, 10) || 1 : 1;
	const opponentSlot = viewerSlot === 1 ? 2 : 1;
	const baseUrl =
		typeof getBaseURL === "function" ? getBaseURL() : "";

	let viewingOpponent = false;
	let matchState = null;

	function getState() {
		if (window.MatchStateManager?.getState) {
			const s = MatchStateManager.getState();
			if (s) return s;
		}
		if (matchState) return matchState;
		const input = document.getElementById("matchInitialState");
		if (!input) return null;
		try {
			matchState = JSON.parse(input.value);
		} catch (e) {
			matchState = null;
		}
		return matchState;
	}

	function getPlayer(slot) {
		const state = getState();
		if (!state || !state.players) return null;
		return state.players.find((p) => p.slot === slot) || null;
	}

	function renderRoster(player, isOpponent) {
		if (!player || !player.roster) {
			panelBody.innerHTML =
				'<p class="roster-panel-empty">ロスターが選択されていません。</p>';
			return;
		}

		const roster = player.roster;
		let html = `<div class="roster-panel-summary">
			<strong>${escapeHtml(roster.name)}</strong>
			<span class="roster-panel-pts">${roster.totalPoints} pt</span>
		</div>`;

		if (isOpponent) {
			html += '<p class="roster-panel-readonly-note">参照専用（相手の軍）</p>';
		}

		(roster.regiments || []).forEach((reg, idx) => {
			html += `<div class="roster-panel-regiment">
				<div class="regiment-panel-title">連隊 #${idx + 1}${reg.isGeneral ? ' <span class="general-badge">GENERAL</span>' : ""}</div>
				<div class="regiment-units">`;

			if (reg.hero) {
				html += unitButtonHtml(reg.hero, true);
			}

			(reg.units || []).forEach((unit) => {
				html += unitButtonHtml(unit, false);
			});

			html += "</div></div>";
		});

		const manifestations = roster.manifestations || [];
		if (manifestations.length > 0) {
			html += `<div class="roster-panel-manifestations">
				<div class="regiment-panel-title">顕現 / MANIFESTATIONS</div>
				<div class="regiment-units">`;
			manifestations.forEach((m) => {
				html += manifestButtonHtml(m);
			});
			html += "</div></div>";
		}

		const terrain = roster.terrain;
		if (terrain && terrain.id) {
			html += `<div class="roster-panel-manifestations">
				<div class="regiment-panel-title">陣営地形 / FACTION TERRAIN</div>
				<div class="regiment-units">${manifestButtonHtml(terrain)}</div>
			</div>`;
		}

		panelBody.innerHTML = html;

		panelBody.querySelectorAll(".roster-unit-btn").forEach((btn) => {
			btn.addEventListener("click", () => {
				const unit = {
					id: parseInt(btn.dataset.unitId, 10),
					name: btn.dataset.unitName,
					keywords: btn.dataset.unitKeywords,
				};
				if (window.RosterUnitDetail) {
					window.RosterUnitDetail.show(unit);
				}
			});
		});
	}

	function manifestButtonHtml(unit) {
		return `<button type="button" class="roster-unit-btn is-manifestation"
			data-unit-id="${unit.id}"
			data-unit-name="${escapeAttr(unit.name)}"
			data-unit-keywords="${escapeAttr(unit.keywords || "")}">
			<span class="unit-btn-thumb">${unitThumbHtml(unit)}</span>
			<span class="unit-btn-info">
				<span class="unit-btn-name">${escapeHtml(unit.name)}</span>
				<span class="unit-btn-pts">${unit.points ?? 0} pt</span>
			</span>
		</button>`;
	}

	function updatePanelTitle() {
		if (!panelTitle) return;
		if (viewingOpponent) {
			const opponent = getPlayer(opponentSlot);
			const name = opponent?.name || `Player ${opponentSlot}`;
			panelTitle.textContent = `相手ロスター (${name})`;
		} else {
			panelTitle.textContent = "自分のロスター";
		}
	}

	function updateOpponentButton() {
		if (!btnViewOpponent) return;
		btnViewOpponent.textContent = viewingOpponent
			? "自分のロスターに戻る"
			: "相手ロスターを確認";
	}

	function refreshPanel() {
		updatePanelTitle();
		updateOpponentButton();
		const slot = viewingOpponent ? opponentSlot : viewerSlot;
		renderRoster(getPlayer(slot), viewingOpponent);
	}

	function unitButtonHtml(unit, isHero) {
		const reinforced =
			unit.is_reinforced || unit.isReinforced
				? '<span class="reinforced-badge">増強</span>'
				: "";
		const role = isHero
			? '<span class="hero-badge">HERO</span>'
			: "";
		const badges =
			role || reinforced
				? `<span class="unit-btn-badges">${role}${reinforced}</span>`
				: "";
		return `<button type="button" class="roster-unit-btn${isHero ? " is-hero" : ""}"
			data-unit-id="${unit.id}"
			data-unit-name="${escapeAttr(unit.name)}"
			data-unit-keywords="${escapeAttr(unit.keywords || "")}">
			<span class="unit-btn-thumb">${unitThumbHtml(unit)}</span>
			<span class="unit-btn-info">
				<span class="unit-btn-name">${escapeHtml(unit.name)}</span>
				<span class="unit-btn-pts">${unit.basePoints ?? unit.points} pt</span>
			</span>
			${badges}
		</button>`;
	}

	function unitThumbHtml(unit) {
		const image = unit.image || "";
		if (image) {
			return `<img src="${escapeAttr(baseUrl + image)}" alt="${escapeAttr(unit.name)}" loading="lazy">`;
		}
		const initial = (unit.name || "?").trim().charAt(0).toUpperCase();
		return `<span class="unit-btn-thumb-placeholder">${escapeHtml(initial)}</span>`;
	}

	function escapeHtml(str) {
		const div = document.createElement("div");
		div.textContent = str || "";
		return div.innerHTML;
	}

	function escapeAttr(str) {
		return String(str || "")
			.replace(/&/g, "&amp;")
			.replace(/"/g, "&quot;")
			.replace(/</g, "&lt;");
	}

	if (btnViewOpponent) {
		btnViewOpponent.addEventListener("click", () => {
			viewingOpponent = !viewingOpponent;
			refreshPanel();
		});
	}

	if (btnCopyP2Url) {
		btnCopyP2Url.addEventListener("click", () => {
			const urlEl = document.getElementById("p2ShareUrl");
			const text = urlEl?.textContent || "";
			if (!text) return;
			navigator.clipboard
				?.writeText(text)
				.then(() => alert("Player 2 用 URL をコピーしました。"))
				.catch(() => prompt("URL をコピーしてください:", text));
		});
	}

	window.addEventListener("matchStateUpdated", () => refreshPanel());

	refreshPanel();

	window.MatchRosterPanel = {
		refresh(state) {
			if (state) matchState = state;
			refreshPanel();
		},
	};
});
