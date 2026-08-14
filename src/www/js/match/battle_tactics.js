/**
 * バトルタクティクス達成モーダル（ターン終了 / 試合終了時）
 * + 参照用モーダル（カード一覧アコーディオン）
 */
const MatchBattleTactics = (function () {
	const STAGE_LABELS = {
		affray: "Affray",
		strike: "Strike",
		domination: "Domination",
	};

	let els = null;
	let viewEls = null;
	let apiPost = null;
	let pendingCallback = null;
	let pendingPlayerSlot = null;
	let submitting = false;
	let viewViewerSlot = 1;

	function init(options) {
		apiPost = options.apiPost;
		viewViewerSlot = options.viewerSlot || 1;
		els = {
			modal: document.getElementById("battleTacticsModal"),
			title: document.getElementById("battleTacticsModalTitle"),
			lead: document.getElementById("battleTacticsModalLead"),
			blocked: document.getElementById("battleTacticsModalBlocked"),
			empty: document.getElementById("battleTacticsModalEmpty"),
			list: document.getElementById("battleTacticsModalList"),
			cancel: document.getElementById("battleTacticsModalCancel"),
			skip: document.getElementById("battleTacticsModalSkip"),
			confirm: document.getElementById("battleTacticsModalConfirm"),
		};
		viewEls = {
			listModal: document.getElementById("battleTacticsViewModal"),
			listTitle: document.getElementById("battleTacticsViewTitle"),
			listLead: document.getElementById("battleTacticsViewLead"),
			listEmpty: document.getElementById("battleTacticsViewEmpty"),
			list: document.getElementById("battleTacticsViewList"),
			listClose: document.getElementById("battleTacticsViewClose"),
		};

		els.cancel?.addEventListener("click", function () {
			if (submitting) return;
			hide();
		});
		els.skip?.addEventListener("click", function () {
			submitCompletions([]);
		});
		els.confirm?.addEventListener("click", function () {
			submitCompletions(collectCompletions());
		});

		viewEls.listClose?.addEventListener("click", hideViewList);
		viewEls.listModal?.addEventListener("click", function (e) {
			if (e.target === viewEls.listModal) hideViewList();
		});
	}

	function getViewerCards() {
		const player = MatchStateManager.getPlayer(viewViewerSlot);
		return player?.battleTactics || [];
	}

	/** 自分の BT カード一覧モーダルを開く（アコーディオン） */
	function openViewList() {
		if (!viewEls?.listModal) return;

		const cards = getViewerCards();
		const player = MatchStateManager.getPlayer(viewViewerSlot);
		const name = player?.name || `Player ${viewViewerSlot}`;

		if (viewEls.listTitle) {
			viewEls.listTitle.textContent = `${name} のバトルタクティクス`;
		}
		if (viewEls.listLead) {
			viewEls.listLead.textContent =
				"カード名をタップすると Affray / Strike / Domination の詳細が開きます。";
		}
		if (viewEls.list) viewEls.list.innerHTML = "";

		const empty = cards.length === 0;
		if (viewEls.listEmpty) {
			viewEls.listEmpty.style.display = empty ? "" : "none";
		}
		if (viewEls.list) {
			viewEls.list.style.display = empty ? "none" : "";
		}

		if (!empty) {
			cards.forEach((card) => {
				viewEls.list.appendChild(buildAccordionItem(card));
			});
		}

		viewEls.listModal.style.display = "flex";
		window.ModalScroll?.lock("battleTacticsViewModal");
	}

	function buildAccordionItem(card) {
		const item = document.createElement("div");
		item.className = "battle-tactics-accordion-item";
		item.dataset.tacticId = String(card.id);

		const header = document.createElement("button");
		header.type = "button";
		header.className = "battle-tactics-accordion-header";
		header.setAttribute("aria-expanded", "false");

		const titleWrap = document.createElement("span");
		titleWrap.className = "battle-tactics-accordion-title-wrap";

		const title = document.createElement("strong");
		title.textContent = card.name || "Battle Tactic Card";

		const meta = document.createElement("span");
		meta.className = "battle-tactics-view-card-meta";
		meta.textContent = `進捗 ${card.highestCompletedOrder || 0} / 3`;

		titleWrap.appendChild(title);
		titleWrap.appendChild(meta);

		const chevron = document.createElement("span");
		chevron.className = "battle-tactics-accordion-chevron";
		chevron.setAttribute("aria-hidden", "true");
		chevron.textContent = "▼";

		header.appendChild(titleWrap);
		header.appendChild(chevron);

		const panel = document.createElement("div");
		panel.className = "battle-tactics-accordion-panel";
		panel.hidden = true;
		panel.appendChild(buildStagesPanel(card));

		header.addEventListener("click", function () {
			const willOpen = panel.hidden;
			// 他のアコーディオンを閉じる（同時に1つだけ開く）
			viewEls.list
				?.querySelectorAll(".battle-tactics-accordion-item")
				.forEach((other) => {
					if (other === item) return;
					other.classList.remove("is-open");
					const otherHeader = other.querySelector(
						".battle-tactics-accordion-header",
					);
					const otherPanel = other.querySelector(
						".battle-tactics-accordion-panel",
					);
					if (otherHeader) otherHeader.setAttribute("aria-expanded", "false");
					if (otherPanel) otherPanel.hidden = true;
				});

			item.classList.toggle("is-open", willOpen);
			header.setAttribute("aria-expanded", willOpen ? "true" : "false");
			panel.hidden = !willOpen;
		});

		item.appendChild(header);
		item.appendChild(panel);
		return item;
	}

	function buildStagesPanel(card) {
		const stagesWrap = document.createElement("div");
		stagesWrap.className = "battle-tactics-detail-stages";

		const progress = document.createElement("p");
		progress.className = "battle-tactics-detail-progress";
		progress.textContent = `進捗: ${card.highestCompletedOrder || 0} / 3（Affray → Strike → Domination）`;
		stagesWrap.appendChild(progress);

		const cardEffectText = String(card.effect || "").trim();
		if (cardEffectText) {
			const cardEffect = document.createElement("p");
			cardEffect.className = "battle-tactics-detail-card-effect";
			cardEffect.textContent = cardEffectText;
			stagesWrap.appendChild(cardEffect);
		}

		const stages = [...(card.stages || [])].sort(
			(a, b) => (a.stageOrder || 0) - (b.stageOrder || 0),
		);
		const completed = card.highestCompletedOrder || 0;

		stages.forEach((stage) => {
			const order = stage.stageOrder || 0;
			const isDone = order <= completed;
			const isNext = order === completed + 1;
			const row = document.createElement("div");
			row.className =
				"battle-tactics-detail-stage" +
				(isDone ? " is-done" : "") +
				(isNext ? " is-next" : "");

			const label = document.createElement("div");
			label.className = "battle-tactics-detail-stage-label";
			const stageKey = stage.stage || "";
			label.textContent = `${STAGE_LABELS[stageKey] || stageKey}: ${stage.name || ""}`;

			const effect = document.createElement("p");
			effect.className = "battle-tactics-detail-stage-effect";
			effect.textContent = stage.effect || "";

			const footer = document.createElement("div");
			footer.className = "battle-tactics-detail-stage-footer";
			const vp = document.createElement("span");
			vp.className = "battle-tactics-modal-vp";
			vp.textContent = `+${stage.victoryPoints ?? 2} VP`;
			const status = document.createElement("span");
			status.className = "battle-tactics-detail-stage-status";
			status.textContent = isDone
				? "達成済み"
				: isNext
					? "次の目標"
					: "未解放";
			footer.appendChild(vp);
			footer.appendChild(status);

			row.appendChild(label);
			row.appendChild(effect);
			row.appendChild(footer);
			stagesWrap.appendChild(row);
		});

		return stagesWrap;
	}

	function hideViewList() {
		if (!viewEls?.listModal) return;
		viewEls.listModal.style.display = "none";
		window.ModalScroll?.unlock("battleTacticsViewModal");
		if (viewEls.list) viewEls.list.innerHTML = "";
	}

	/**
	 * @param {number} playerSlot ターン終了中のプレイヤー
	 * @param {function(): void|Promise<void>} onDone
	 */
	function openForPlayer(playerSlot, onDone) {
		if (!els?.modal) {
			if (onDone) onDone();
			return;
		}

		const player = MatchStateManager.getPlayer(playerSlot);
		const cards = player?.battleTactics || [];
		const isDoubleTurn = !!player?.seizedInitiative || !!player?.isDoubleTurn;
		const name = player?.name || `Player ${playerSlot}`;

		pendingPlayerSlot = playerSlot;
		pendingCallback = onDone;

		if (els.title) {
			els.title.textContent = `${name} のバトルタクティクス達成`;
		}
		if (els.lead) {
			els.lead.textContent =
				"ターン終了時に達成した段階を選択してください（カードごとに最大1つ）。";
		}

		const showBlocked = isDoubleTurn;
		const showEmpty = !showBlocked && cards.length === 0;

		if (els.blocked) els.blocked.style.display = showBlocked ? "" : "none";
		if (els.empty) els.empty.style.display = showEmpty ? "" : "none";
		if (els.list) {
			els.list.innerHTML = "";
			els.list.style.display = showBlocked || showEmpty ? "none" : "";
		}
		if (els.confirm) {
			els.confirm.disabled = showBlocked;
			els.confirm.style.display = showBlocked ? "none" : "";
		}
		if (els.skip) {
			els.skip.textContent = showBlocked
				? "続行"
				: "達成なしで続行";
		}

		if (!showBlocked && !showEmpty) {
			renderCards(cards);
		}

		els.modal.style.display = "flex";
		window.ModalScroll?.lock("battleTacticsModal");
	}

	function renderCards(cards) {
		cards.forEach((card) => {
			const nextOrder = (card.highestCompletedOrder || 0) + 1;
			const nextStage =
				(card.stages || []).find((s) => s.stageOrder === nextOrder) ||
				null;

			const item = document.createElement("div");
			item.className = "battle-tactics-modal-card";

			const header = document.createElement("div");
			header.className = "battle-tactics-modal-card-header";
			header.textContent = card.name || "Battle Tactic Card";
			item.appendChild(header);

			const progress = document.createElement("p");
			progress.className = "battle-tactics-modal-progress";
			progress.textContent = `進捗: ${card.highestCompletedOrder || 0} / 3`;
			item.appendChild(progress);

			if (!nextStage) {
				const done = document.createElement("p");
				done.className = "battle-tactics-modal-done";
				done.textContent = "全段階達成済み";
				item.appendChild(done);
			} else {
				const label = document.createElement("label");
				label.className = "battle-tactics-modal-option";

				const checkbox = document.createElement("input");
				checkbox.type = "checkbox";
				checkbox.className = "battle-tactic-complete-check";
				checkbox.dataset.tacticId = String(card.id);
				checkbox.dataset.stageOrder = String(nextStage.stageOrder);

				const body = document.createElement("div");
				body.className = "battle-tactics-modal-option-body";

				const stageTitle = document.createElement("strong");
				const stageKey = nextStage.stage || "";
				stageTitle.textContent = `${STAGE_LABELS[stageKey] || stageKey}: ${nextStage.name}`;

				const effect = document.createElement("p");
				effect.className = "battle-tactics-modal-effect";
				effect.textContent = nextStage.effect || "";

				const vp = document.createElement("span");
				vp.className = "battle-tactics-modal-vp";
				vp.textContent = `+${nextStage.victoryPoints ?? 2} VP`;

				body.appendChild(stageTitle);
				body.appendChild(effect);
				body.appendChild(vp);

				label.appendChild(checkbox);
				label.appendChild(body);
				item.appendChild(label);
			}

			els.list.appendChild(item);
		});
	}

	function collectCompletions() {
		if (!els?.list) return [];
		const checks = els.list.querySelectorAll(
			".battle-tactic-complete-check:checked",
		);
		const completions = [];
		checks.forEach((el) => {
			completions.push({
				battleTacticId: parseInt(el.dataset.tacticId, 10),
				stageOrder: parseInt(el.dataset.stageOrder, 10),
			});
		});
		return completions;
	}

	async function submitCompletions(completions) {
		if (submitting) return;
		submitting = true;

		const slot = pendingPlayerSlot;
		const cb = pendingCallback;
		const app = document.getElementById("scoreboardApp");
		const matchId = parseInt(app?.dataset.matchId || "0", 10);
		const token = app?.dataset.token || "";

		try {
			if (completions.length > 0 && apiPost) {
				const res = await apiPost("match/completeBattleTactics", {
					token,
					matchId,
					playerSlot: slot,
					completions,
				});
				if (res.state) {
					MatchStateManager.applyServerState(res.state);
				}
			}
			hide();
			if (cb) await cb();
		} catch (e) {
			console.error("Battle tactics completion failed", e);
			alert(
				e.message ||
					"バトルタクティクスの更新に失敗しました。時間をおいて再度お試しください。",
			);
		} finally {
			submitting = false;
		}
	}

	function hide() {
		if (!els?.modal) return;
		els.modal.style.display = "none";
		window.ModalScroll?.unlock("battleTacticsModal");
		pendingCallback = null;
		pendingPlayerSlot = null;
		if (els.list) els.list.innerHTML = "";
	}

	return {
		init,
		openForPlayer,
		openViewList,
		hide,
	};
})();
