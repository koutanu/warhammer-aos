/**
 * ユニット図鑑: 編集/新規フォームの制御
 * - 武器行の追加/削除
 * - 能力カードの追加（新規/既存アタッチ）と削除（新規は DOM 削除、既存は detach フラグ）
 */
(function () {
	"use strict";

	// 既存行と衝突しない十分大きな開始値
	let nextIdx = 100000;

	function init() {
		const addWeaponBtn = document.getElementById("btnAddWeapon");
		const addAbilityBtn = document.getElementById("btnAddAbility");
		const attachExistingBtn = document.getElementById("btnAttachExisting");

		if (addWeaponBtn) addWeaponBtn.addEventListener("click", addWeaponRow);
		if (addAbilityBtn)
			addAbilityBtn.addEventListener("click", function () {
				addAbilityCard(null);
			});
		if (attachExistingBtn)
			attachExistingBtn.addEventListener("click", attachExistingAbility);

		// 既存行の削除イベント（イベント委譲）
		const weaponsBody = document.getElementById("weaponsTableBody");
		if (weaponsBody) {
			weaponsBody.addEventListener("click", function (e) {
				const btn = e.target.closest(".btn-remove-row");
				if (btn) {
					const row = btn.closest(".weapon-row");
					if (row) row.remove();
				}
			});
		}

		const abilitiesContainer = document.getElementById("abilitiesContainer");
		if (abilitiesContainer) {
			abilitiesContainer.addEventListener("click", function (e) {
				const btn = e.target.closest(".btn-remove-ability");
				if (btn) toggleAbilityRemoval(btn.closest(".ability-edit-card"));
			});
		}
	}

	function addWeaponRow() {
		const tpl = document.getElementById("weaponRowTemplate");
		const body = document.getElementById("weaponsTableBody");
		if (!tpl || !body) return;

		const idx = nextIdx++;
		const html = tpl.innerHTML.replace(/__IDX__/g, idx);
		const wrapper = document.createElement("tbody");
		wrapper.innerHTML = html.trim();
		const row = wrapper.firstElementChild;
		body.appendChild(row);
	}

	function addAbilityCard(data) {
		const tpl = document.getElementById("abilityCardTemplate");
		const container = document.getElementById("abilitiesContainer");
		if (!tpl || !container) return null;

		const idx = nextIdx++;
		const html = tpl.innerHTML.replace(/__IDX__/g, idx);
		const wrapper = document.createElement("div");
		wrapper.innerHTML = html.trim();
		const card = wrapper.firstElementChild;
		container.appendChild(card);

		if (data) {
			setFieldValue(card, "ability_id", data.id);
			setFieldValue(card, "name", data.name);
			setFieldValue(card, "trigger_phase", data.trigger_phase);
			setFieldValue(card, "trigger_turn", data.trigger_turn);
			setFieldValue(card, "ability_type", data.ability_type);
			setFieldValue(card, "trigger_condition_ja", data.trigger_condition_ja);
			setFieldValue(card, "effect", data.effect);
			setFieldValue(card, "flavor_text", data.flavor_text);
			setFieldValue(card, "keywords", data.keywords);
			card.dataset.abilityExisting = "1";
		}
		return card;
	}

	function setFieldValue(card, fieldSuffix, value) {
		const el = card.querySelector(`[name$="[${fieldSuffix}]"]`);
		if (el) el.value = value || "";
	}

	function attachExistingAbility() {
		const select = document.getElementById("existingAbilitySelect");
		if (!select || !select.value) return;

		const opt = select.options[select.selectedIndex];
		addAbilityCard({
			id: opt.value,
			name: opt.dataset.name,
			trigger_phase: opt.dataset.trigger_phase,
			trigger_turn: opt.dataset.trigger_turn,
			ability_type: opt.dataset.ability_type,
			trigger_condition_ja: opt.dataset.trigger_condition_ja,
			effect: opt.dataset.effect,
			flavor_text: opt.dataset.flavor_text,
			keywords: opt.dataset.keywords,
		});

		select.value = "";
	}

	function toggleAbilityRemoval(card) {
		if (!card) return;

		// 新規(マスタ未アタッチ)なら DOM ごと削除。
		const isExisting = card.dataset.abilityExisting === "1";
		if (!isExisting) {
			card.remove();
			return;
		}

		// 既存(マスタにアタッチ済み)なら detach フラグの ON/OFF をトグル。
		const flag = card.querySelector(".ability-delete-flag");
		const btn = card.querySelector(".btn-remove-ability");
		if (!flag) return;

		const willRemove = flag.value !== "1";
		flag.value = willRemove ? "1" : "0";
		card.classList.toggle("ability-edit-card--removed", willRemove);

		card.querySelectorAll("input, textarea, select").forEach(function (el) {
			if (el.classList.contains("ability-delete-flag")) return;
			if (willRemove) {
				el.setAttribute("readonly", "readonly");
			} else {
				el.removeAttribute("readonly");
			}
		});

		if (btn) {
			btn.textContent = willRemove ? "元に戻す" : "このユニットから外す";
		}
	}

	document.addEventListener("DOMContentLoaded", init);
})();
