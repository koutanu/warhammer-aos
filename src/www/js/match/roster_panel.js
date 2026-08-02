/**
 * マッチプレイ中のロスター参照（メイン表示）
 * - 自分 / 相手 のロスターを上部タブで切替
 * - ユニットをタップで詳細モーダル
 * - 撃破 / 代替トグル（自分のロスターのみ）
 */
document.addEventListener("DOMContentLoaded", () => {
	const app = document.getElementById("scoreboardApp");
	const view = document.getElementById("rosterView");
	const panelBody = document.getElementById("rosterPanelBody");
	const tabMine = document.getElementById("rosterTabMine");
	const tabOpponent = document.getElementById("rosterTabOpponent");
	const btnGoPlayer2 = document.getElementById("btnGoPlayer2");
	const btnGoPlayer1 = document.getElementById("btnGoPlayer1");
	const btnRosterMemo = document.getElementById("btnRosterMemo");
	const rosterMemoModal = document.getElementById("rosterMemoModal");
	const rosterMemoBody = document.getElementById("rosterMemoBody");
	const rosterMemoClose = document.getElementById("rosterMemoClose");

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

	function getSummonedMap(slot) {
		const state = getState();
		return state?.game?.summonedUnits?.[slot] || {};
	}

	function isSummoned(slot, instanceKey) {
		if (!instanceKey) return false;
		return !!getSummonedMap(slot)[instanceKey];
	}

	function getReplacedMap(slot) {
		const state = getState();
		return state?.game?.replacedUnits?.[slot] || {};
	}

	function isReplaced(slot, instanceKey) {
		if (!instanceKey) return false;
		return !!getReplacedMap(slot)[instanceKey];
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
					playerSlot: viewingOpponent ? opponentSlot : viewerSlot,
				};
				if (window.RosterUnitDetail) {
					window.RosterUnitDetail.show(unit);
				}
			};
			btn.addEventListener("click", (e) => {
				if (e.target.closest(".unit-btn-destroyed-toggle")) return;
				if (e.target.closest(".unit-btn-replaced-toggle")) return;
				if (e.target.closest(".unit-btn-banish-toggle")) return;
				openDetail();
			});
			btn.addEventListener("keydown", (e) => {
				if (e.key !== "Enter" && e.key !== " ") return;
				if (e.target.closest(".unit-btn-destroyed-toggle")) return;
				if (e.target.closest(".unit-btn-replaced-toggle")) return;
				if (e.target.closest(".unit-btn-banish-toggle")) return;
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

		panelBody.querySelectorAll(".unit-btn-replaced-toggle").forEach((btn) => {
			btn.addEventListener("click", (e) => {
				e.preventDefault();
				e.stopPropagation();
				const unitKey = btn.dataset.instanceKey;
				const slotNum = parseInt(btn.dataset.playerSlot, 10);
				if (!unitKey || !slotNum) return;
				toggleReplaced(slotNum, unitKey);
			});
		});

		panelBody.querySelectorAll(".unit-btn-banish-toggle").forEach((btn) => {
			btn.addEventListener("click", (e) => {
				e.preventDefault();
				e.stopPropagation();
				const unitKey = btn.dataset.instanceKey;
				const slotNum = parseInt(btn.dataset.playerSlot, 10);
				if (!unitKey || !slotNum) return;
				toggleManifestationSummoned(slotNum, unitKey);
			});
		});
	}

	function destroyToggleHtml(unit, slot, destroyed) {
		const key = unit.instanceKey || "";
		if (!key) return "";
		const ariaPressed = destroyed ? "true" : "false";
		return `<button type="button"
			class="unit-btn-destroyed-toggle${destroyed ? " is-destroyed" : ""}"
			data-instance-key="${escapeAttr(key)}"
			data-player-slot="${slot}"
			aria-pressed="${ariaPressed}"
			aria-label="${escapeAttr(unit.name || "ユニット")}を${destroyed ? "生存に戻す" : "撃破にする"}">撃破</button>`;
	}

	function replaceToggleHtml(unit, slot, replaced) {
		const key = unit.instanceKey || "";
		if (!key) return "";
		const ariaPressed = replaced ? "true" : "false";
		return `<button type="button"
			class="unit-btn-replaced-toggle${replaced ? " is-replaced" : ""}"
			data-instance-key="${escapeAttr(key)}"
			data-player-slot="${slot}"
			aria-pressed="${ariaPressed}"
			aria-label="${escapeAttr(unit.name || "ユニット")}を${replaced ? "代替解除する" : "代替にする"}">代替</button>`;
	}

	function statusActionsHtml(unit, slot, canToggle, destroyed, replaced) {
		if (!canToggle) return "";
		return `<div class="unit-btn-status-actions">
			${destroyToggleHtml(unit, slot, destroyed)}
			${replaceToggleHtml(unit, slot, replaced)}
		</div>`;
	}

	function banishToggleHtml(unit, slot, canToggle, summoned) {
		const key = unit.instanceKey || "";
		if (!key || !summoned) return "";
		const disabled = canToggle ? "" : " disabled";
		return `<button type="button"
			class="unit-btn-banish-toggle"
			data-instance-key="${escapeAttr(key)}"
			data-player-slot="${slot}"
			aria-pressed="true"
			aria-label="${escapeAttr(unit.name || "顕現")}を追放する"${disabled}>追放</button>`;
	}

	function manifestButtonHtml(unit, slot, canToggle) {
		const key = unit.instanceKey || "";
		const isManifestation = key.indexOf("manifest:") === 0;
		const destroyed = isDestroyed(slot, key);
		const replaced = isReplaced(slot, key);
		const summoned = isManifestation && isSummoned(slot, key);

		if (isManifestation) {
			const summonedBadge = summoned
				? '<span class="summoned-badge">召喚中</span>'
				: "";
			const badges = summonedBadge
				? `<span class="unit-btn-badges">${summonedBadge}</span>`
				: "";
			return `<div class="roster-unit-btn is-manifestation${summoned ? " is-summoned" : ""}"
				role="button"
				tabindex="0"
				data-unit-id="${unit.id}"
				data-unit-name="${escapeAttr(unit.name)}"
				data-unit-keywords="${escapeAttr(unit.keywords || "")}">
				<span class="unit-btn-thumb">${unitThumbHtml(unit)}</span>
				<span class="unit-btn-info">
					<span class="unit-btn-name">${escapeHtml(unit.name)}</span>
				</span>
				${banishToggleHtml(unit, slot, canToggle, summoned)}
				${badges}
			</div>`;
		}

		const replacedBadge = replaced
			? '<span class="replaced-badge">代替</span>'
			: "";
		const badges = replacedBadge
			? `<span class="unit-btn-badges">${replacedBadge}</span>`
			: "";

		return `<div class="roster-unit-btn is-manifestation${destroyed ? " is-destroyed" : ""}${replaced ? " is-replaced" : ""}"
			role="button"
			tabindex="0"
			data-unit-id="${unit.id}"
			data-unit-name="${escapeAttr(unit.name)}"
			data-unit-keywords="${escapeAttr(unit.keywords || "")}">
			<span class="unit-btn-thumb">${unitThumbHtml(unit)}</span>
			<span class="unit-btn-info">
				<span class="unit-btn-name">${escapeHtml(unit.name)}</span>
			</span>
			${statusActionsHtml(unit, slot, canToggle, destroyed, replaced)}
			${badges}
		</div>`;
	}

	function updateTabs() {
		if (tabMine) {
			tabMine.classList.toggle("active", !viewingOpponent);
			tabMine.setAttribute("aria-selected", viewingOpponent ? "false" : "true");
		}
		if (tabOpponent) {
			tabOpponent.classList.toggle("active", viewingOpponent);
			tabOpponent.setAttribute(
				"aria-selected",
				viewingOpponent ? "true" : "false",
			);
		}
	}

	function refreshPanel() {
		updateTabs();
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
		const replaced = isReplaced(slot, unit.instanceKey);
		const replacedBadge = replaced
			? '<span class="replaced-badge">代替</span>'
			: "";
		const badges =
			role || reinforced || replacedBadge
				? `<span class="unit-btn-badges">${role}${reinforced}${replacedBadge}</span>`
				: "";
		const destroyed = isDestroyed(slot, unit.instanceKey);
		return `<div class="roster-unit-btn${isHero ? " is-hero" : ""}${destroyed ? " is-destroyed" : ""}${replaced ? " is-replaced" : ""}"
			role="button"
			tabindex="0"
			data-unit-id="${unit.id}"
			data-unit-name="${escapeAttr(unit.name)}"
			data-unit-keywords="${escapeAttr(unit.keywords || "")}">
			<span class="unit-btn-thumb">${unitThumbHtml(unit)}</span>
			<span class="unit-btn-info">
				<span class="unit-btn-name">${escapeHtml(unit.name)}</span>
			</span>
			${statusActionsHtml(unit, slot, canToggle, destroyed, replaced)}
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

	function postUnitStatusToggle(path, playerSlot, unitKey) {
		if (toggling || !matchId || !token) return;
		toggling = true;

		fetch(baseUrl + path, {
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

	function toggleDestroyed(playerSlot, unitKey) {
		postUnitStatusToggle("match/toggleUnitDestroyed", playerSlot, unitKey);
	}

	function toggleReplaced(playerSlot, unitKey) {
		postUnitStatusToggle("match/toggleUnitReplaced", playerSlot, unitKey);
	}

	function toggleManifestationSummoned(playerSlot, unitKey) {
		postUnitStatusToggle(
			"match/toggleManifestationSummoned",
			playerSlot,
			unitKey,
		);
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

	if (tabMine) {
		tabMine.addEventListener("click", () => {
			if (!viewingOpponent) return;
			viewingOpponent = false;
			refreshPanel();
		});
	}
	if (tabOpponent) {
		tabOpponent.addEventListener("click", () => {
			if (viewingOpponent) return;
			viewingOpponent = true;
			refreshPanel();
		});
	}

	function bindPlayerSwitch(btn) {
		if (!btn) return;
		btn.addEventListener("click", () => {
			const href = btn.dataset.href || "";
			if (!href) return;
			window.location.href = href;
		});
	}
	bindPlayerSwitch(btnGoPlayer2);
	bindPlayerSwitch(btnGoPlayer1);

	function closeRosterMemoModal() {
		if (!rosterMemoModal) return;
		rosterMemoModal.style.display = "none";
		window.ModalScroll?.unlock("rosterMemoModal");
	}

	function openRosterMemoModal() {
		if (!rosterMemoModal || !rosterMemoBody) return;
		const memo = (getPlayer(viewerSlot)?.roster?.memo || "").trim();
		rosterMemoBody.textContent = memo || "メモなし";
		rosterMemoModal.style.display = "flex";
		window.ModalScroll?.lock("rosterMemoModal");
	}

	if (btnRosterMemo) {
		btnRosterMemo.addEventListener("click", openRosterMemoModal);
	}
	if (rosterMemoClose) {
		rosterMemoClose.addEventListener("click", closeRosterMemoModal);
	}
	if (rosterMemoModal) {
		rosterMemoModal.addEventListener("click", (e) => {
			if (e.target === rosterMemoModal) closeRosterMemoModal();
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
