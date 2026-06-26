/**
 * ユニット図鑑: 一覧画面の制御
 * - 「詳細」ボタンで既存の詳細モーダル(RosterUnitDetail)を表示
 * - 管理者向け「ロスターで非表示」チェックの Ajax 切替
 */
(function () {
	"use strict";

	function init() {
		bindTabs();
		bindAccordions();
		bindDetailButtons();
		bindHideToggles();
	}

	function bindAccordions() {
		document.querySelectorAll("[data-accordion]").forEach(function (btn) {
			btn.addEventListener("click", function () {
				const section = btn.closest(".option-section");
				if (!section) return;
				const isOpen = section.classList.toggle("is-open");
				btn.setAttribute("aria-expanded", isOpen ? "true" : "false");
			});
		});
	}

	function bindTabs() {
		const tabs = document.querySelectorAll(".unit-tab");
		if (!tabs.length) return;

		tabs.forEach(function (tab) {
			tab.addEventListener("click", function () {
				const targetId = tab.dataset.tabTarget;

				tabs.forEach(function (t) {
					t.classList.toggle("is-active", t === tab);
				});

				document
					.querySelectorAll(".unit-tab-panel")
					.forEach(function (panel) {
						panel.classList.toggle("is-active", panel.id === targetId);
					});
			});
		});
	}

	function bindDetailButtons() {
		document.querySelectorAll(".btn-unit-detail").forEach(function (btn) {
			btn.addEventListener("click", function () {
				if (typeof window.RosterUnitDetail === "undefined") {
					console.error("RosterUnitDetail が読み込まれていません。");
					return;
				}
				window.RosterUnitDetail.show({
					id: btn.dataset.unitId,
					name: btn.dataset.unitName,
					keywords: btn.dataset.keywords,
				});
			});
		});
	}

	function bindHideToggles() {
		document.querySelectorAll(".unit-hide-checkbox").forEach(function (checkbox) {
			checkbox.addEventListener("change", function () {
				toggleVisibility(checkbox);
			});
		});
	}

	async function toggleVisibility(checkbox) {
		const baseUrl = typeof getBaseURL === "function" ? getBaseURL() : "/";
		const token = typeof getToken === "function" ? getToken() : "";
		const unitId = checkbox.dataset.unitId;
		const isHidden = checkbox.checked ? 1 : 0;

		checkbox.disabled = true;

		try {
			const formData = new FormData();
			formData.append("token", token);
			formData.append("unit_id", unitId);
			formData.append("is_hidden", String(isHidden));

			const response = await fetch(`${baseUrl}unit/toggleVisibility`, {
				method: "POST",
				body: formData,
			});

			const result = await response.json();
			if (!response.ok || !result.ok) {
				throw new Error(result.message || "更新に失敗しました。");
			}

			updateCardState(unitId, result.is_hidden === 1);
		} catch (error) {
			console.error(error);
			alert(error.message || "更新に失敗しました。");
			checkbox.checked = !checkbox.checked;
		} finally {
			checkbox.disabled = false;
		}
	}

	function updateCardState(unitId, isHidden) {
		const card = document.querySelector(
			`.unit-card[data-unit-id="${unitId}"]`,
		);
		if (!card) return;

		card.classList.toggle("unit-card--hidden", isHidden);

		const nameEl = card.querySelector(".unit-card-name");
		if (!nameEl) return;

		let badge = nameEl.querySelector(".unit-hidden-badge");
		if (isHidden) {
			if (!badge) {
				badge = document.createElement("span");
				badge.className = "unit-hidden-badge";
				badge.textContent = "ロスター非表示";
				nameEl.appendChild(badge);
			}
		} else if (badge) {
			badge.remove();
		}
	}

	document.addEventListener("DOMContentLoaded", init);
})();
