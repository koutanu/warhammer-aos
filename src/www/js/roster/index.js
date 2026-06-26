document.addEventListener("DOMContentLoaded", () => {
	// ==========================================
	// #region 1. 初期定義・要素の取得
	// ==========================================
	const allianceButtons = document.querySelectorAll(".alliance-btn");
	const factionSelect = document.getElementById("factionSelect");
	const rosterForm = document.getElementById("rosterForm");

	// HTMLの全オプションを初期状態で配列にディープバックアップ
	const factionOptions = Array.from(
		document.querySelectorAll(".faction-option"),
	);
	// #endregion

	// ==========================================
	// #region 2. 独立した機能関数 (UI・データ処理)
	// ==========================================

	/**
	 * ボタンのアクティブ状態（見た目）を切り替える
	 * @param {HTMLElement} clickedButton - タップされたボタン要素
	 */
	function switchActiveButton(clickedButton) {
		allianceButtons.forEach((btn) => btn.classList.remove("active"));
		clickedButton.classList.add("active");
	}

	/**
	 * 選択された大同盟に応じてファクションのドロップダウンを再構築する
	 * @param {string} selectedAlliance - 大同盟名 (order, chaos など)
	 */
	function filterFactionDropdown(selectedAlliance) {
		// セレクトボックスの初期化
		factionSelect.value = "";
		factionSelect.disabled = false;
		factionSelect.innerHTML =
			'<option value="">-- ファクションを選択 --</option>';

		let hasMatch = false;

		// マッチする要素の抽出と流し込み
		factionOptions.forEach((option) => {
			const dbAlliance = option.getAttribute("data-alliance");

			// 大文字小文字を無視して比較
			if (
				dbAlliance &&
				dbAlliance.toLowerCase() === selectedAlliance.toLowerCase()
			) {
				const clonedOption = option.cloneNode(true);
				clonedOption.style.display = "block"; // 非表示の解除
				factionSelect.appendChild(clonedOption);
				hasMatch = true;
			}
		});

		// 該当データが0件だった場合の処理
		if (!hasMatch) {
			factionSelect.innerHTML =
				'<option value="">データがありません</option>';
			factionSelect.disabled = true;
		}
	}
	// #endregion

	// ==========================================
	// #region 3. イベントリスナー（実行トリガー）
	// ==========================================

	// --- 大同盟ボタンが押された時のイベント ---
	allianceButtons.forEach((button) => {
		button.addEventListener("click", () => {
			const selectedAlliance = button.getAttribute("data-alliance");

			switchActiveButton(button); // ① 見た目を変える
			filterFactionDropdown(selectedAlliance); // ② 中身を絞り込む
		});
	});

	// --- フォームが送信された時のイベント ---
	if (rosterForm) {
		rosterForm.addEventListener("submit", async (e) => {
			e.preventDefault();

			// 1. 各種入力・選択値の取得
			const rosterNameInput = document.getElementById("rosterName");
			const activeAllianceBtn = document.querySelector(
				".alliance-btn.active",
			);
			const factionSelect = document.getElementById("factionSelect");
			const rosterPointsSelect = document.getElementById("rosterPoints"); // IDで正しく取得

			const rosterName = rosterNameInput
				? rosterNameInput.value.trim()
				: "";
			const selectedAlliance = activeAllianceBtn
				? activeAllianceBtn.getAttribute("data-alliance")
				: null;
			const selectedFactionId = factionSelect ? factionSelect.value : "";
			const selectedRosterPoints = rosterPointsSelect
				? rosterPointsSelect.value
				: "1000"; // セレクトボックスの選択値を取得

			// 2. バリデーション
			if (!rosterName) {
				alert("ロスター名を入力してください。");
				rosterNameInput.focus();
				return;
			}
			if (!selectedAlliance || !selectedFactionId) {
				alert("大同盟とファクションを選択してください。");
				return;
			}

			// 3. 送信データのオブジェクト化
			const payload = {
				roster_name: rosterName,
				grand_alliance: selectedAlliance,
				faction_id: selectedFactionId,
				roster_points: selectedRosterPoints, // これで「1000」や「2000」が正しく入ります
			};

			// URLSearchParamsを使って、オブジェクトを「roster_name=xxx&grand_alliance=yyy...」の形式に変換
			const params = new URLSearchParams(payload);

			// 遷移先URLを組み立て
			const targetURL = `${getBaseURL()}roster/create_roster?${params.toString()}`;

			// 指定したURLへ画面を遷移させる
			window.location.href = targetURL;
		});
	}
	// #endregion
});
