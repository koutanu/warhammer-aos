/**
 * ユニット詳細モーダル（ロスター編成・マッチプレイ共通）
 */
window.RosterUnitDetail = (function () {
	const modalId = "unitDetailModal";

	const ABILITY_ICON_FALLBACK = "abSpecial.png";

	function getEl(id) {
		return document.getElementById(id);
	}

	/**
	 * 詠唱値/祈祷値の整形。数値のみなら "+" を補い、既に付いていれば重複させない。
	 */
	function formatCastingValue(value) {
		if (value === null || value === undefined) return "";
		const str = String(value).trim();
		if (str === "") return "";
		if (/\+$/.test(str)) return str;
		return /^\d+$/.test(str) ? `${str}+` : str;
	}

	/**
	 * アビリティのアイコン分類(icon_type)からアイコン <img> を生成する。
	 * 解決ロジックは MatchPhases に集約し、未読込時は最低限のフォールバックを使う。
	 */
	function buildAbilityIcon(baseUrl, ability, altLabel) {
		const src =
			typeof MatchPhases !== "undefined" && MatchPhases.abilityIconUrl
				? MatchPhases.abilityIconUrl(baseUrl, ability)
				: `${baseUrl}assets/icons/${ABILITY_ICON_FALLBACK}`;
		const alt = altLabel || "アビリティ";
		const fallbackSrc = `${baseUrl}assets/icons/${ABILITY_ICON_FALLBACK}`;
		// アイコン画像が未配置でも壊れたリンクを出さないようフォールバック
		return `<img src="${src}" alt="${alt}" onerror="this.onerror=null;this.src='${fallbackSrc}';" style="vertical-align:middle;height:20px;margin-right:5px;">`;
	}

	async function show(unit) {
		const unitDetailModal = getEl(modalId);
		if (!unitDetailModal || !unit) return;

		const unitId = unit.id || unit.unitId;
		if (!unitId) return;

		if (getEl("detailUnitName")) {
			getEl("detailUnitName").textContent = unit.name || "-";
		}

		const weaponsBody = getEl("detailWeaponsBody");
		const abilitiesContainer = getEl("detailUnitAbilitiesContainer");
		const keywordsEl = getEl("detailUnitKeywords");
		const flavorTextEl = getEl("detailUnitFlavorText");
		const regimentSection = getEl("detailRegimentSection");
		const regimentOptionsEl = getEl("detailRegimentOptions");

		if (getEl("detailUnitMove"))
			getEl("detailUnitMove").textContent = "...";
		if (getEl("detailUnitWounds"))
			getEl("detailUnitWounds").textContent = "...";
		if (getEl("detailUnitSave"))
			getEl("detailUnitSave").textContent = "...";
		if (getEl("detailUnitControl"))
			getEl("detailUnitControl").textContent = "...";
		if (keywordsEl) keywordsEl.textContent = unit.keywords || "...";
		if (flavorTextEl) flavorTextEl.textContent = unit.flavor_text || "...";
		if (regimentSection) regimentSection.style.display = "none";
		if (regimentOptionsEl) regimentOptionsEl.innerHTML = "";

		// 画像はいったん隠してから、取得結果に応じて表示する
		const imageEl = getEl("detailUnitImage");
		if (imageEl) {
			imageEl.removeAttribute("src");
			imageEl.style.display = "none";
		}

		if (weaponsBody) {
			weaponsBody.innerHTML =
				'<tr><td colspan="7" style="color:#aaa; text-align:center; padding: 15px;">詳細データを読み込み中...</td></tr>';
		}
		if (abilitiesContainer) {
			abilitiesContainer.innerHTML =
				'<p style="color:#aaa; font-style:italic; padding: 10px;">アビリティを読み込み中...</p>';
		}

		unitDetailModal.style.display = "flex";
		window.ModalScroll?.lock(modalId);

		try {
			const baseUrl =
				typeof getBaseURL === "function" ? getBaseURL() : "/";
			const response = await fetch(
				`${baseUrl}roster/getUnitDetail?unit_id=${encodeURIComponent(unitId)}`,
			);
			if (!response.ok)
				throw new Error("詳細データの取得に失敗しました。");

			const detailData = await response.json();
			const info = detailData.info || {};

			if (getEl("detailUnitName") && info.name) {
				getEl("detailUnitName").textContent = info.name;
			}
			if (getEl("detailUnitPoints") && info.points) {
				getEl("detailUnitPoints").textContent = info.points + "pt";
			}
			if (getEl("detailUnitMove")) {
				getEl("detailUnitMove").textContent = info.movement
					? `${info.movement}"`
					: "-";
			}
			if (getEl("detailUnitWounds")) {
				getEl("detailUnitWounds").textContent = info.wounds ?? "-";
			}
			if (getEl("detailUnitSave")) {
				getEl("detailUnitSave").textContent = info.save
					? `${info.save}+`
					: "-";
			}
			// 顕現(マニフェステーション)は 確保力(CONTROL) の代わりに 追放(BANISHMENT) を持ち、
			// 値は "7+" のように + を後置で表記する。
			const isManifestation = Number(info.is_manifestation);
			if (getEl("detailControlLabel")) {
				getEl("detailControlLabel").textContent = isManifestation
					? "追放"
					: "確保力";
			}
			if (getEl("detailUnitControl")) {
				const ctrl = info.control;
				const hasCtrl =
					ctrl !== null && ctrl !== undefined && ctrl !== "";
				getEl("detailUnitControl").textContent = !hasCtrl
					? "-"
					: isManifestation
						? `${ctrl}+`
						: `${ctrl}`;
			}
			if (keywordsEl) {
				let kw = info.keywords || unit.keywords || "-";
				const isHero = Number(info.is_hero);
				const regNames = info.regiment_eligibility_names;
				if (isHero && regNames) {
					kw = kw && kw !== "-" ? `${kw}, ${regNames}` : regNames;
				}
				keywordsEl.textContent = kw;
			}
			if (flavorTextEl) {
				flavorTextEl.textContent = info.flavor_text || "-";
			}

			if (regimentSection && regimentOptionsEl) {
				const optionLines = (info.regiment_options || "")
					.split("\n")
					.map((line) => line.trim())
					.filter((line) => line !== "");
				if (optionLines.length > 0) {
					const list = document.createElement("ul");
					list.className = "detail-regiment-list";
					optionLines.forEach((line) => {
						const li = document.createElement("li");
						li.textContent = line;
						list.appendChild(li);
					});
					regimentOptionsEl.innerHTML = "";
					regimentOptionsEl.appendChild(list);
					regimentSection.style.display = "";
				} else {
					regimentOptionsEl.innerHTML = "";
					regimentSection.style.display = "none";
				}
			}

			if (imageEl) {
				if (info.image) {
					imageEl.src = baseUrl + info.image;
					imageEl.alt = info.name || unit.name || "";
					imageEl.style.display = "";
				} else {
					imageEl.removeAttribute("src");
					imageEl.style.display = "none";
				}
			}

			if (weaponsBody) {
				weaponsBody.innerHTML = "";
				if (detailData.weapons && detailData.weapons.length > 0) {
					detailData.weapons.forEach((w) => {
						const tr = document.createElement("tr");
						const rangeDisplay = w.rng ? `${w.rng}"` : "Melee";
						const badge = w.abilities
							? `<br><small style="color: #ffcc00; font-size:0.75rem;">★ ${w.abilities}</small>`
							: "";
						tr.innerHTML = `
							<td><strong>${w.name || "不明な武器"}</strong>${badge}</td>
							<td>${rangeDisplay}</td>
							<td>${w.atk ?? "-"}</td>
							<td>${w.hit ? w.hit + "+" : "-"}</td>
							<td>${w.wnd ? w.wnd + "+" : "-"}</td>
							<td>${w.rnd ? w.rnd : "0"}</td>
							<td>${w.dmg ?? "-"}</td>
						`;
						weaponsBody.appendChild(tr);
					});
				} else {
					weaponsBody.innerHTML =
						'<tr><td colspan="7" style="color:#aaa; text-align:center; padding: 15px;">武器情報が登録されていません。</td></tr>';
				}
			}

			if (abilitiesContainer) {
				abilitiesContainer.innerHTML = "";
				if (detailData.abilities && detailData.abilities.length > 0) {
					detailData.abilities.forEach((ab) => {
						const abBox = document.createElement("div");
						abBox.className = "detail-ability-box";
						const effectText =
							ab.effect ||
							ab.declaration_effect ||
							"効果テキストデータがありません";
						const phaseNorm =
							typeof MatchPhases !== "undefined"
								? MatchPhases.normalizePhase(ab.trigger_phase)
								: "";
						const phaseLabel =
							typeof MatchPhases !== "undefined"
								? MatchPhases.formatTriggerPhase(
										ab.trigger_phase,
										phaseNorm,
									)
								: ab.trigger_phase || "";
						const phaseBadge = phaseLabel
							? `<span style="font-size:0.7rem; background:#333; padding:2px 6px; border-radius:3px; margin-left:8px; color:#ccc;">${phaseLabel}</span>`
							: "";
						const freqLabel =
							typeof MatchPhases !== "undefined" &&
							MatchPhases.frequencyInfo
								? MatchPhases.frequencyInfo({
										activation: ab.activation,
										usageScope: ab.usage_scope,
										usagePer: ab.usage_per,
									}).label
								: "";
						const typeBadge = freqLabel
							? `<span style="font-size:0.7rem; background:#4a3b19; padding:2px 6px; border-radius:3px; margin-left:4px; color:#ffcc00;">${freqLabel}</span>`
							: "";
						const cpBadge =
							ab.command_point && Number(ab.command_point) > 0
								? `<span style="font-size:0.7rem; background:#1d3a5f; padding:2px 6px; border-radius:3px; margin-left:4px; color:#9ad;">CP ${Number(ab.command_point)}</span>`
								: "";
						const castStr = formatCastingValue(ab.casting_value);
						const castLabel = ab.casting_type === "prayer" ? "祈祷" : "詠唱";
						const castBadge = castStr
							? `<span style="font-size:0.7rem; background:#3a1d4f; padding:2px 6px; border-radius:3px; margin-left:4px; color:#caa;">${castLabel} ${castStr}</span>`
							: "";
						const abilityIcon = buildAbilityIcon(
							baseUrl,
							ab,
							ab.icon_type || phaseLabel || ab.name,
						);

						abBox.innerHTML = `
							<div class="ability-title" style="font-weight:bold; color:#ffcc00; margin-bottom:6px; border-bottom:1px solid #444; padding-bottom:3px;">
							${abilityIcon}${ab.name}${phaseBadge}${typeBadge}${cpBadge}${castBadge}
							</div>
							<p class="ability-effect" style="margin:0; white-space:pre-wrap; font-size:0.9rem; line-height:1.4; color:#eee;">${effectText}</p>
						`;
						abilitiesContainer.appendChild(abBox);
					});
				} else {
					abilitiesContainer.innerHTML =
						'<p style="color:#aaa; font-style:italic; padding: 10px;">固有アビリティは登録されていません。</p>';
				}
			}
		} catch (error) {
			console.error(error);
			if (weaponsBody) {
				weaponsBody.innerHTML =
					'<tr><td colspan="7" style="color:#ff5555; text-align:center; padding: 15px;">データの取得に失敗しました。</td></tr>';
			}
			if (abilitiesContainer) {
				abilitiesContainer.innerHTML =
					'<p style="color:#ff5555; padding: 10px;">エラーが発生したため、詳細を読み込めませんでした。</p>';
			}
		}
	}

	function close() {
		const unitDetailModal = getEl(modalId);
		if (unitDetailModal) unitDetailModal.style.display = "none";
		window.ModalScroll?.unlock(modalId);
	}

	function init() {
		const btnCloseDetailModal = getEl("btnCloseDetailModal");
		const unitDetailModal = getEl(modalId);
		if (btnCloseDetailModal) {
			btnCloseDetailModal.addEventListener("click", close);
		}
		if (unitDetailModal) {
			unitDetailModal.addEventListener("click", (e) => {
				if (e.target === unitDetailModal) close();
			});
		}
	}

	document.addEventListener("DOMContentLoaded", init);

	return { show, close };
})();
