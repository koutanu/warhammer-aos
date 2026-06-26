/**
 * 配置ターン（Deployment Phase）コントローラー
 * - game.phase === "deployment" の間だけ配置ビューを表示する
 * - 配置フェイズで使えるアビリティと、自分の連隊を表示
 * - 「配置完了」で round1 / hero フェーズへ遷移（サーバー保存・両者同期）
 */
document.addEventListener("DOMContentLoaded", () => {
	const app = document.getElementById("scoreboardApp");
	const view = document.getElementById("deploymentView");
	if (!app || !view) return;

	const matchId = parseInt(app.dataset.matchId, 10);
	const token = app.dataset.token;
	const viewerSlot = parseInt(app.dataset.viewerSlot, 10) || 1;
	const baseUrl = typeof getBaseURL === "function" ? getBaseURL() : "";

	const els = {
		view,
		modeTabs: document.querySelector(".match-mode-tabs"),
		rosterView: document.getElementById("rosterView"),
		phasePanel: document.getElementById("phasePanel"),
		abilityList: document.getElementById("deploymentAbilityList"),
		abilityEmpty: document.getElementById("deploymentAbilityEmpty"),
		regiments: document.getElementById("deploymentRegiments"),
		btnComplete: document.getElementById("btnCompleteDeployment"),
	};

	let pollTimer = null;
	let submitting = false;
	let wasDeploymentActive = false;

	bindEvents();
	syncView();

	window.addEventListener("matchStateUpdated", syncView);

	document.addEventListener("visibilitychange", () => {
		if (document.visibilityState === "visible" && isDeploymentActive()) {
			pollState();
		}
	});

	function isDeploymentActive() {
		const state = MatchStateManager.getState?.() || {};
		return (state.game?.phase || "") === "deployment";
	}

	function syncView() {
		const active = isDeploymentActive();
		els.view.style.display = active ? "flex" : "none";

		// 配置中は通常レイアウト（タブ・ロスター・フェーズ）を隠す
		if (els.modeTabs) els.modeTabs.style.display = active ? "none" : "";
		if (active) {
			if (els.rosterView) els.rosterView.style.display = "none";
			if (els.phasePanel) els.phasePanel.style.display = "none";
		}

		if (active) {
			renderAbilities();
			renderRegiments();
			startPoll();
		} else {
			stopPoll();
			// 配置から抜けた瞬間のみ通常レイアウト（ロスタービュー表示）へ戻す。
			// フェーズ表示中などは ability_panel のモード管理に委ね、上書きしない。
			if (
				wasDeploymentActive &&
				els.rosterView &&
				els.rosterView.style.display === "none"
			) {
				els.rosterView.style.display = "";
			}
		}

		wasDeploymentActive = active;
	}

	function bindEvents() {
		els.btnComplete?.addEventListener("click", promptFirstPlayer);

		els.abilityList?.addEventListener("click", (e) => {
			const detailBtn = e.target.closest(".ability-detail-toggle");
			if (!detailBtn) return;
			const box = detailBtn.parentElement?.querySelector(".ability-effect-box");
			if (!box) return;
			const open = box.style.display === "block";
			box.style.display = open ? "none" : "block";
			detailBtn.textContent = open ? "詳細" : "閉じる";
		});
	}

	function getPlayer() {
		const state = MatchStateManager.getState?.() || {};
		return (state.players || []).find((p) => p.slot === viewerSlot) || null;
	}

	function renderAbilities() {
		if (!els.abilityList) return;
		const player = getPlayer();
		const deck = player?.abilitiesDeck || [];

		const filtered = deck.filter((ab) => {
			const phaseNorms =
				ab.triggerPhaseNorms ||
				MatchPhases.normalizePhases(ab.triggerPhase);
			return phaseNorms.includes("deployment");
		});

		els.abilityList.innerHTML = "";

		if (!player?.rosterId) {
			showAbilityEmpty(
				"ロスターが未選択です。マッチ設定でロスターを選ぶとアビリティが表示されます。",
			);
			return;
		}

		if (!filtered.length) {
			showAbilityEmpty("配置フェイズで使えるアビリティはありません。");
			return;
		}

		if (els.abilityEmpty) els.abilityEmpty.style.display = "none";
		filtered.forEach((ab) => els.abilityList.appendChild(buildAbilityCard(ab)));
	}

	function showAbilityEmpty(message) {
		if (!els.abilityEmpty) return;
		els.abilityEmpty.style.display = "block";
		els.abilityEmpty.textContent = message;
	}

	function buildAbilityCard(ab) {
		const card = document.createElement("article");
		card.className =
			"phase-ability-card" + (ab.category ? ` cat-${ab.category}` : "");

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
		const conditionBlock = conditionText
			? `<p class="ability-trigger-condition"><span class="ability-trigger-condition-label">発動条件</span>${escapeHtml(conditionText)}</p>`
			: "";

		card.innerHTML = `
			<div class="ability-card-head">
				<div class="ability-card-title-block">
					<span class="ability-category-badge cat-${escapeHtml(ab.category || "unit")}">${escapeHtml(categoryLabel)}</span>
					<strong class="ability-name">${escapeHtml(ab.name)}</strong>
					${cpBadge}
				</div>
			</div>
			<div class="ability-card-meta">
				${unitLabel ? `<span class="ability-source">${escapeHtml(unitLabel)}</span>` : ""}
				<span class="ability-phase-badge">${escapeHtml(MatchPhases.labelPhaseJa("deployment"))}</span>
				${freqBadge}
			</div>
			<button type="button" class="ability-detail-toggle">詳細</button>
			<div class="ability-effect-box" style="display:none;">
				${conditionBlock}
				<p>${escapeHtml(ab.effect || "")}</p>
			</div>
		`;
		return card;
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

	function renderRegiments() {
		if (!els.regiments) return;
		const player = getPlayer();
		const roster = player?.roster;

		if (!roster) {
			els.regiments.innerHTML =
				'<p class="roster-panel-empty">ロスターが選択されていません。</p>';
			return;
		}

		let html = `<div class="roster-panel-summary">
			<strong>${escapeHtml(roster.name)}</strong>
			<span class="roster-panel-pts">${roster.totalPoints} pt</span>
		</div>`;

		(roster.regiments || []).forEach((reg, idx) => {
			html += `<div class="roster-panel-regiment">
				<div class="regiment-panel-title">連隊 #${idx + 1}${reg.isGeneral ? ' <span class="general-badge">GENERAL</span>' : ""}</div>
				<div class="regiment-units">`;

			if (reg.hero) {
				html += unitRowHtml(reg.hero, true);
			}
			(reg.units || []).forEach((unit) => {
				html += unitRowHtml(unit, false);
			});

			html += "</div></div>";
		});

		els.regiments.innerHTML = html;
	}

	function unitRowHtml(unit, isHero) {
		const reinforced =
			unit.is_reinforced || unit.isReinforced
				? '<span class="reinforced-badge">増強</span>'
				: "";
		const role = isHero ? '<span class="hero-badge">HERO</span>' : "";
		const badges =
			role || reinforced
				? `<span class="deployment-unit-badges">${role}${reinforced}</span>`
				: "";
		return `<div class="deployment-unit${isHero ? " is-hero" : ""}">
			<span class="deployment-unit-thumb">${unitThumbHtml(unit)}</span>
			<span class="deployment-unit-info">
				<span class="deployment-unit-name">${escapeHtml(unit.name)}${badges}</span>
				<span class="deployment-unit-pts">${unit.basePoints ?? unit.points} pt</span>
			</span>
		</div>`;
	}

	function unitThumbHtml(unit) {
		const image = unit.image || "";
		if (image) {
			return `<img src="${escapeAttr(baseUrl + image)}" alt="${escapeAttr(unit.name)}" loading="lazy">`;
		}
		const initial = (unit.name || "?").trim().charAt(0).toUpperCase();
		return `<span class="deployment-unit-thumb-placeholder">${escapeHtml(initial)}</span>`;
	}

	function promptFirstPlayer() {
		if (submitting) return;

		const state = MatchStateManager.getState?.() || {};
		const players = state.players || [];
		const p1 = players.find((p) => p.slot === 1) || {};
		const p2 = players.find((p) => p.slot === 2) || {};

		if (window.MatchFirstPlayerModal) {
			window.MatchFirstPlayerModal.open(
				"先攻はどちら？",
				"最初のラウンドで先に手番を行うプレイヤーを選んでください。",
				p1.name || "Player 1",
				p2.name || "Player 2",
				completeDeployment,
			);
		} else {
			completeDeployment(1);
		}
	}

	function completeDeployment(firstPlayerSlot) {
		if (submitting) return;
		if (firstPlayerSlot !== 1 && firstPlayerSlot !== 2) return;
		submitting = true;
		if (els.btnComplete) els.btnComplete.disabled = true;

		fetch(baseUrl + "match/completeDeployment", {
			method: "POST",
			headers: { "Content-Type": "application/json" },
			body: JSON.stringify({ token, matchId, firstPlayerSlot }),
		})
			.then((res) => res.json())
			.then((data) => {
				if (!data.success) {
					alert(data.message || "配置を完了できませんでした。");
					return;
				}
				MatchStateManager.applyServerState(data.state);
			})
			.catch(() => alert("通信エラーが発生しました。"))
			.finally(() => {
				submitting = false;
				if (els.btnComplete) els.btnComplete.disabled = false;
			});
	}

	function startPoll() {
		stopPoll();
		pollTimer = setInterval(pollState, 15000);
	}

	function stopPoll() {
		if (pollTimer) {
			clearInterval(pollTimer);
			pollTimer = null;
		}
	}

	function pollState() {
		if (submitting || MatchStateManager.isDirty()) return;

		fetch(baseUrl + "match/getState/" + matchId)
			.then((res) => res.json())
			.then((data) => {
				if (!data.success || !data.state?.game) return;
				// サーバー側で配置が完了していたらフル適用して遷移する
				if (data.state.game.phase !== "deployment") {
					MatchStateManager.applyServerState(data.state);
				}
			})
			.catch(() => {});
	}

	function escapeHtml(str) {
		const div = document.createElement("div");
		div.textContent = str ?? "";
		return div.innerHTML;
	}

	function escapeAttr(str) {
		return String(str || "")
			.replace(/&/g, "&amp;")
			.replace(/"/g, "&quot;")
			.replace(/</g, "&lt;");
	}
});
