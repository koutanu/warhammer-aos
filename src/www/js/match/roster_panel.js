/**
 * マッチプレイ中のロスター参照（メイン表示）
 * - 自分 / 相手 のロスターをトグルで切替
 * - ユニットをタップで詳細モーダル
 * - 撃破トグル（自分のロスターのみ）
 */
document.addEventListener("DOMContentLoaded", () => {
	const app = document.getElementById("scoreboardApp");
	const view = document.getElementById("rosterView");
	const panelBody = document.getElementById("rosterPanelBody");
	const panelTitle = document.getElementById("rosterViewTitle");
	const btnViewOpponent = document.getElementById("btnViewOpponentRoster");
	const btnCopyP2Url = document.getElementById("btnCopyP2Url");

	if (!view || !panelBody) return;

	const matchId = app ? parseInt(app.dataset.matchId, 10) : 0;
	const token = app ? app.dataset.token : "";
	const viewerSlot = app ? parseInt(app.dataset.viewerSlot, 10) || 1 : 1;
	const opponentSlot = viewerSlot === 1 ? 2 : 1;
	const baseUrl =
		typeof getBaseURL === "function" ? getBaseURL() : "";

	let viewingOpponent = false;
	let matchState = null;
	let toggling = false;

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

	function getDestroyedMap(slot) {
		const state = getState();
		return state?.game?.destroyedUnits?.[slot] || {};
	}

	function isDestroyed(slot, instanceKey) {
		if (!instanceKey) return false;
		return !!getDestroyedMap(slot)[instanceKey];
	}

	function renderRoster(player, isOpponent) {
		if (!player || !player.roster) {
			panelBody.innerHTML =
				'<p class="roster-panel-empty">ロスターが選択されていません。</p>';
			return;
		}

		const roster = player.roster;
		const slot = player.slot;
		const canToggle = !isOpponent;
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
				html += unitButtonHtml(reg.hero, true, slot, canToggle);
			}

			(reg.units || []).forEach((unit) => {
				html += unitButtonHtml(unit, false, slot, canToggle);
			});

			html += "</div></div>";
		});

		const manifestations = roster.manifestations || [];
		if (manifestations.length > 0) {
			html += `<div class="roster-panel-manifestations">
				<div class="regiment-panel-title">顕現 / MANIFESTATIONS</div>
				<div class="regiment-units">`;
			manifestations.forEach((m) => {
				html += manifestButtonHtml(m, slot, canToggle);
			});
			html += "</div></div>";
		}

		const terrain = roster.terrain;
		if (terrain && terrain.id) {
			html += `<div class="roster-panel-manifestations">
				<div class="regiment-panel-title">陣営地形 / FACTION TERRAIN</div>
				<div class="regiment-units">${manifestButtonHtml(terrain, slot, canToggle)}</div>
			</div>`;
		}

		panelBody.innerHTML = html;

		panelBody.querySelectorAll(".roster-unit-btn").forEach((btn) => {
			const openDetail = () => {
				const unit = {
					id: parseInt(btn.dataset.unitId, 10),
					name: btn.dataset.unitName,
					keywords: btn.dataset.unitKeywords,
				};
				if (window.RosterUnitDetail) {
					window.RosterUnitDetail.show(unit);
				}
			};
			btn.addEventListener("click", (e) => {
				if (e.target.closest(".unit-btn-destroyed-toggle")) return;
				openDetail();
			});
			btn.addEventListener("keydown", (e) => {
				if (e.key !== "Enter" && e.key !== " ") return;
				if (e.target.closest(".unit-btn-destroyed-toggle")) return;
				e.preventDefault();
				openDetail();
			});
		});

		panelBody.querySelectorAll(".unit-btn-destroyed-toggle").forEach((btn) => {
			btn.addEventListener("click", (e) => {
				e.preventDefault();
				e.stopPropagation();
				const unitKey = btn.dataset.instanceKey;
				const slotNum = parseInt(btn.dataset.playerSlot, 10);
				if (!unitKey || !slotNum) return;
				toggleDestroyed(slotNum, unitKey);
			});
		});
	}

	function destroyToggleHtml(unit, slot, canToggle, destroyed) {
		const key = unit.instanceKey || "";
		if (!key) return "";
		const disabled = canToggle ? "" : " disabled";
		const ariaPressed = destroyed ? "true" : "false";
		return `<button type="button"
			class="unit-btn-destroyed-toggle${destroyed ? " is-destroyed" : ""}"
			data-instance-key="${escapeAttr(key)}"
			data-player-slot="${slot}"
			aria-pressed="${ariaPressed}"
			aria-label="${escapeAttr(unit.name || "ユニット")}を${destroyed ? "生存に戻す" : "撃破にする"}"${disabled}>撃破</button>`;
	}

	function manifestButtonHtml(unit, slot, canToggle) {
		const destroyed = isDestroyed(slot, unit.instanceKey);
		return `<div class="roster-unit-btn is-manifestation${destroyed ? " is-destroyed" : ""}"
			role="button"
			tabindex="0"
			data-unit-id="${unit.id}"
			data-unit-name="${escapeAttr(unit.name)}"
			data-unit-keywords="${escapeAttr(unit.keywords || "")}">
			<span class="unit-btn-thumb">${unitThumbHtml(unit)}</span>
			<span class="unit-btn-info">
				<span class="unit-btn-name">${escapeHtml(unit.name)}</span>
			</span>
			${destroyToggleHtml(unit, slot, canToggle, destroyed)}
		</div>`;
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

	function unitButtonHtml(unit, isHero, slot, canToggle) {
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
		const destroyed = isDestroyed(slot, unit.instanceKey);
		return `<div class="roster-unit-btn${isHero ? " is-hero" : ""}${destroyed ? " is-destroyed" : ""}"
			role="button"
			tabindex="0"
			data-unit-id="${unit.id}"
			data-unit-name="${escapeAttr(unit.name)}"
			data-unit-keywords="${escapeAttr(unit.keywords || "")}">
			<span class="unit-btn-thumb">${unitThumbHtml(unit)}</span>
			<span class="unit-btn-info">
				<span class="unit-btn-name">${escapeHtml(unit.name)}</span>
			</span>
			${destroyToggleHtml(unit, slot, canToggle, destroyed)}
			${badges}
		</div>`;
	}

	function unitThumbHtml(unit) {
		const image = unit.image || "";
		if (image) {
			return `<img src="${escapeAttr(baseUrl + image)}" alt="${escapeAttr(unit.name)}" loading="lazy">`;
		}
		const initial = (unit.name || "?").trim().charAt(0).toUpperCase();
		return `<span class="unit-btn-thumb-placeholder">${escapeHtml(initial)}</span>`;
	}

	function toggleDestroyed(playerSlot, unitKey) {
		if (toggling || !matchId || !token) return;
		toggling = true;

		fetch(baseUrl + "match/toggleUnitDestroyed", {
			method: "POST",
			headers: { "Content-Type": "application/json" },
			body: JSON.stringify({
				token,
				matchId,
				playerSlot,
				unitKey,
			}),
		})
			.then((res) => res.json())
			.then((data) => {
				if (!data.success) {
					alert(data.message || "更新に失敗しました。");
					return;
				}
				if (window.MatchStateManager?.applyServerState) {
					MatchStateManager.applyServerState(data.state);
				} else {
					matchState = data.state;
					refreshPanel();
				}
			})
			.catch(() => alert("通信エラーが発生しました。"))
			.finally(() => {
				toggling = false;
			});
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
