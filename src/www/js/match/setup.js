document.addEventListener("DOMContentLoaded", function () {
	const form = document.getElementById("matchSetupForm");
	if (!form) return;

	form.addEventListener("submit", function (e) {
		const aName = document.getElementById("playerAName").value.trim();
		const bName = document.getElementById("playerBName").value.trim();
		if (aName === bName) {
			e.preventDefault();
			alert("プレイヤー名は異なる名前を入力してください。");
		}
	});

	async function loadRosters(factionId, targetSelectId) {
		const select = document.getElementById(targetSelectId);
		if (!select) return;

		select.innerHTML = '<option value="">-- ロスターを選択（任意） --</option>';
		if (!factionId) {
			select.disabled = true;
			select.innerHTML = '<option value="">-- ファクションを先に選択 --</option>';
			return;
		}

		select.disabled = true;
		try {
			const baseUrl = typeof getBaseURL === "function" ? getBaseURL() : "/";
			const response = await fetch(
				`${baseUrl}roster/getByFaction/${encodeURIComponent(factionId)}`,
			);
			const rosters = await response.json();
			if (Array.isArray(rosters) && rosters.length > 0) {
				rosters.forEach((roster) => {
					const opt = document.createElement("option");
					opt.value = roster.id;
					opt.textContent = `${roster.name} (${roster.total_points} pt)`;
					select.appendChild(opt);
				});
			} else {
				const opt = document.createElement("option");
				opt.value = "";
				opt.textContent = "-- 保存済みロスターなし --";
				select.appendChild(opt);
			}
			select.disabled = false;
		} catch (err) {
			console.error(err);
			select.innerHTML = '<option value="">-- 取得エラー --</option>';
		}
	}

	document.querySelectorAll(".faction-select").forEach((factionSelect) => {
		factionSelect.addEventListener("change", () => {
			const targetId = factionSelect.dataset.rosterTarget;
			if (targetId) {
				loadRosters(factionSelect.value, targetId);
			}
		});
	});
});
