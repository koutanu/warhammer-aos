/**
 * ロスター作成画面 アーミーオプション連動スクリプト (iPad最適化版)
 */
document.addEventListener("DOMContentLoaded", () => {
	/**
	 * セレクトボックスの選択状態に応じて詳細データを準備し、専用ボタンと連動させる共通関数
	 * @param {HTMLSelectElement} selectEl - 対象のセレクトボックス要素
	 * @param {HTMLElement} detailBoxEl - 詳細ボックス要素
	 * @param {HTMLButtonElement} toggleBtnEl - 詳細を開くボタン要素
	 * @param {Object} selectors - 詳細ボックス内の各パーツのID
	 */
	const initOptionDetailLinkage = (
		selectEl,
		detailBoxEl,
		toggleBtnEl,
		selectors,
	) => {
		// 必須要素が画面に存在しない場合は処理をスキップ（エラー防止ガード）
		if (!selectEl || !detailBoxEl || !toggleBtnEl) return;

		// 詳細ボックス内の各テキスト要素を取得
		const titleEl = document.getElementById(selectors.titleId);
		const triggerEl = document.getElementById(selectors.triggerId);
		const effectEl = document.getElementById(selectors.effectId);
		const flavorEl = document.getElementById(selectors.flavorId);

		/**
		 * trigger_condition_ja 優先、なければ MatchPhases で日本語化
		 * （enhancements.js / detail.js と同じルール）
		 */
		const formatOptionTrigger = (option) => {
			const conditionJa = String(
				option.dataset.triggerConditionJa || "",
			).trim();
			if (conditionJa) return conditionJa;
			const raw = option.dataset.trigger || "";
			if (
				typeof MatchPhases !== "undefined" &&
				MatchPhases.formatTriggerPhases
			) {
				return MatchPhases.formatTriggerPhases(raw);
			}
			return raw;
		};

		/**
		 * セレクトボックス変更時にデータを裏側で同期する処理
		 */
		const updateDetailData = () => {
			const selectedOption = selectEl.options[selectEl.selectedIndex];

			// 未選択、または空バリューの場合はボタンを無効化し、詳細ボックスも閉じる
			if (!selectEl.value || !selectedOption) {
				toggleBtnEl.disabled = true;
				toggleBtnEl.classList.remove("is-active");
				detailBoxEl.style.display = "none";
				return;
			}

			// dataset から値を取得してDOMに反映
			if (titleEl)
				titleEl.textContent = selectedOption.dataset.abilityName || "";
			if (triggerEl) {
				const triggerLabel = formatOptionTrigger(selectedOption);
				triggerEl.textContent = triggerLabel;
				triggerEl.style.display = triggerLabel ? "" : "none";
			}
			if (effectEl)
				effectEl.innerHTML = selectedOption.dataset.effect || "";

			// フレーバーテキストの表示・非表示切り替え
			if (flavorEl) {
				const flavorText = selectedOption.dataset.flavor;
				if (flavorText && flavorText.trim() !== "") {
					flavorEl.textContent = flavorText;
					flavorEl.style.display = "block";
				} else {
					flavorEl.style.display = "none";
				}
			}

			// 選択肢が有効なため「詳細を確認する」ボタンを押せるようにする
			toggleBtnEl.disabled = false;
		};

		/**
		 * 「詳細を確認する」ボタンがクリックされた時の開閉ロジック
		 */
		toggleBtnEl.addEventListener("click", () => {
			if (
				detailBoxEl.style.display === "none" ||
				detailBoxEl.style.display === ""
			) {
				detailBoxEl.style.display = "block";
				toggleBtnEl.classList.add("is-active");
			} else {
				detailBoxEl.style.display = "none";
				toggleBtnEl.classList.remove("is-active");
			}
		});

		// セレクトボックス変更時のイベントリスナー
		selectEl.addEventListener("change", updateDetailData);

		// 画面リロード時や「戻る」で値が残っていた場合のための初期実行
		if (selectEl.value) {
			updateDetailData();
		}
	};

	// --------------------------------------------------------
	// ① バトルフォーメーションの連動設定
	// --------------------------------------------------------
	initOptionDetailLinkage(
		document.getElementById("battleFormation"),
		document.getElementById("formationDetail"),
		document.getElementById("btnToggleFormation"),
		{
			titleId: "detailAbilityName",
			triggerId: "detailTrigger",
			effectId: "detailEffect",
			flavorId: "detailFlavor",
		},
	);

	// --------------------------------------------------------
	// ② 呪文伝承 (Spell Lore) の連動設定
	// --------------------------------------------------------
	initOptionDetailLinkage(
		document.getElementById("spellLore"),
		document.getElementById("spellLoreDetail"),
		document.getElementById("btnToggleSpell"), // ※HTML側に id="btnToggleSpell" を追加してください
		{
			titleId: "spellAbilityName",
			triggerId: "spellTrigger",
			effectId: "spellEffect",
			flavorId: "spellFlavor",
		},
	);

	// --------------------------------------------------------
	// ③ 奇蹟伝承 (Prayer Lore) の連動設定
	// --------------------------------------------------------
	initOptionDetailLinkage(
		document.getElementById("prayerLore"),
		document.getElementById("prayerLoreDetail"),
		document.getElementById("btnTogglePrayer"),
		{
			titleId: "prayerAbilityName",
			triggerId: "prayerTrigger",
			effectId: "prayerEffect",
			flavorId: "prayerFlavor",
		},
	);

	// --------------------------------------------------------
	// ④ 顕現の伝承 (Manifestation Lore) の連動設定
	// --------------------------------------------------------
	initOptionDetailLinkage(
		document.getElementById("manifestationLore"),
		document.getElementById("manifestationLoreDetail"),
		document.getElementById("btnToggleManifestation"), // ※HTML側に id="btnToggleManifestation" を追加してください
		{
			titleId: "manifestationAbilityName",
			triggerId: "manifestationTrigger",
			effectId: "manifestationEffect",
			flavorId: "manifestationFlavor",
		},
	);

	// --------------------------------------------------------
	//  詳細表示ボックスの「閉じる（×）ボタン」一括設定（安全な連動版）
	// --------------------------------------------------------
	const closeButtons = document.querySelectorAll(".detail-close-btn");

	closeButtons.forEach((btn) => {
		btn.addEventListener("click", () => {
			const targetSelectId = btn.getAttribute("data-target");
			const selectEl = document.getElementById(targetSelectId);
			const detailBox = btn.parentElement; // .formation-detail-box

			if (detailBox) {
				// 詳細ボックスを閉じる
				detailBox.style.display = "none";
			}

			if (selectEl) {
				// セレクトボックスを未選択（最初のoption）に戻す
				selectEl.value = "";

				// changeイベントを強制発火させることで、上記の updateDetailData() が走り、
				// 連動する「詳細を確認する」ボタンも自動的に disabled 状態へリセットされます
				selectEl.dispatchEvent(new Event("change"));
			}
		});
	});

	// --------------------------------------------------------
	//  陣営地形: 選択した地形のウォースクロール詳細を表示
	// --------------------------------------------------------
	const terrainSelect = document.getElementById("factionTerrain");
	const terrainDetailBtn = document.getElementById("btnViewTerrainDetail");
	if (terrainSelect && terrainDetailBtn) {
		const syncTerrainBtn = () => {
			terrainDetailBtn.disabled = !terrainSelect.value;
		};
		terrainSelect.addEventListener("change", syncTerrainBtn);
		terrainDetailBtn.addEventListener("click", () => {
			if (!terrainSelect.value || !window.RosterUnitDetail) return;
			window.RosterUnitDetail.show({ id: terrainSelect.value });
		});
		syncTerrainBtn();
	}

	// --------------------------------------------------------
	//  バトルタクティクス: 最大2枚選択 + 段階表示
	// --------------------------------------------------------
	const tacticsSection = document.getElementById("battleTacticsSection");
	if (tacticsSection) {
		const maxCards = parseInt(tacticsSection.dataset.maxCards || "2", 10);
		const checkboxes = Array.from(
			tacticsSection.querySelectorAll(".battle-tactic-checkbox"),
		);
		const countEl = document.getElementById("battleTacticsCount");

		const syncTacticSelection = () => {
			const checked = checkboxes.filter((cb) => cb.checked);
			if (countEl) {
				const strong = countEl.querySelector("strong");
				if (strong) strong.textContent = String(checked.length);
			}
			const atLimit = checked.length >= maxCards;
			checkboxes.forEach((cb) => {
				const card = cb.closest(".battle-tactic-card");
				if (!cb.checked) {
					cb.disabled = atLimit;
					card?.classList.toggle("is-disabled", atLimit);
				} else {
					cb.disabled = false;
					card?.classList.remove("is-disabled");
					card?.classList.add("is-selected");
				}
				if (cb.checked) {
					card?.classList.add("is-selected");
				} else if (!atLimit) {
					card?.classList.remove("is-selected");
				} else {
					card?.classList.remove("is-selected");
				}
			});
		};

		checkboxes.forEach((cb) => {
			cb.addEventListener("change", () => {
				const checked = checkboxes.filter((c) => c.checked);
				if (checked.length > maxCards) {
					cb.checked = false;
					alert(
						`バトルタクティクスカードは最大${maxCards}枚まで選択できます。`,
					);
				}
				syncTacticSelection();
			});
		});

		syncTacticSelection();
		window.syncBattleTacticSelection = syncTacticSelection;
	}
});

// 編集モード: 保存済みアーミーオプションの復元
document.addEventListener("DOMContentLoaded", () => {
	const savedEl = document.getElementById("savedArmyOptions");
	if (!savedEl) return;

	let opts;
	try {
		opts = JSON.parse(savedEl.textContent);
	} catch (e) {
		return;
	}

	const mapping = {
		battle_formation: "battleFormation",
		spell_lore: "spellLore",
		prayer_lore: "prayerLore",
		manifestation_lore: "manifestationLore",
		faction_terrain: "factionTerrain",
	};

	Object.entries(mapping).forEach(([key, elId]) => {
		const val = opts[key];
		if (!val) return;
		const select = document.getElementById(elId);
		if (select) {
			select.value = String(val);
			select.dispatchEvent(new Event("change"));
		}
	});

	const savedTactics = Array.isArray(opts.selected_tactics_cards)
		? opts.selected_tactics_cards.map(String)
		: [];
	if (savedTactics.length) {
		document.querySelectorAll(".battle-tactic-checkbox").forEach((cb) => {
			cb.checked = savedTactics.includes(String(cb.value));
		});
		if (typeof window.syncBattleTacticSelection === "function") {
			window.syncBattleTacticSelection();
		}
	}
});
