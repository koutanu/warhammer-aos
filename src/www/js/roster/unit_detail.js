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
	 * 防御力・加護など、数値 + 「+」表記の整形。
	 */
	function formatStatPlus(value) {
		if (value === null || value === undefined) return "";
		const str = String(value).trim();
		if (str === "") return "";
		if (/\+$/.test(str)) return str;
		return /^\d+$/.test(str) ? `${str}+` : str;
	}

	function isBlankStat(value) {
		if (value === null || value === undefined) return true;
		const str = String(value).trim();
		return str === "" || str === "0";
	}

	function formatWeaponNumber(value, suffix = "") {
		if (isBlankStat(value)) return "-";
		return `${String(value).trim()}${suffix}`;
	}

	/**
	 * 防御力表示。加護 param があるときは「4+/5+」形式。
	 */
	function formatSaveDisplay(save, ward) {
		const saveStr = formatStatPlus(save);
		if (!saveStr) return "-";
		const wardStr = formatStatPlus(ward);
		return wardStr ? `${saveStr}/${wardStr}` : saveStr;
	}

	function escapeHtml(str) {
		return String(str ?? "")
			.replace(/&/g, "&amp;")
			.replace(/</g, "&lt;")
			.replace(/>/g, "&gt;")
			.replace(/"/g, "&quot;");
	}

	function clearKeywordEffect() {
		const effectEl = getEl("detailKeywordEffect");
		if (!effectEl) return;
		effectEl.textContent = "";
		effectEl.style.display = "none";
		effectEl.hidden = true;
		effectEl.removeAttribute("data-keyword-id");
	}

	function setKeywordEffect(keywordId, effectText) {
		const effectEl = getEl("detailKeywordEffect");
		if (!effectEl) return;
		const openId = effectEl.getAttribute("data-keyword-id");
		if (openId === String(keywordId) && effectEl.style.display !== "none") {
			clearKeywordEffect();
			return;
		}
		effectEl.textContent = effectText;
		effectEl.setAttribute("data-keyword-id", String(keywordId));
		effectEl.hidden = false;
		effectEl.style.display = "";
	}

	/**
	 * キーワード一覧を描画。effect があるものはクリックで効果文を展開。
	 * @param {HTMLElement} keywordsEl
	 * @param {Array} details
	 * @param {string} [extraPlain] ヒーロー連隊適格名など effect なしの追記
	 */
	function renderKeywords(keywordsEl, details, extraPlain) {
		if (!keywordsEl) return;
		clearKeywordEffect();
		keywordsEl.innerHTML = "";

		const items = Array.isArray(details) ? details.slice() : [];
		const extra = (extraPlain || "").trim();
		if (extra) {
			extra.split(",").forEach((part) => {
				const name = part.trim();
				if (!name) return;
				items.push({
					id: null,
					display_name: name,
					effect: null,
				});
			});
		}

		if (!items.length) {
			keywordsEl.textContent = "-";
			return;
		}

		items.forEach((kw, index) => {
			if (index > 0) {
				keywordsEl.appendChild(document.createTextNode(", "));
			}
			const label = kw.display_name || kw.name || "";
			const effect = (kw.effect || "").trim();
			if (effect) {
				const btn = document.createElement("button");
				btn.type = "button";
				btn.className = "detail-keyword detail-keyword--has-effect";
				btn.textContent = label;
				const kid = kw.id != null ? kw.id : `idx-${index}`;
				btn.setAttribute("data-keyword-id", String(kid));
				btn.setAttribute("aria-expanded", "false");
				btn.title = "効果を表示";
				btn.addEventListener("click", () => {
					const effectEl = getEl("detailKeywordEffect");
					const wasOpen =
						effectEl &&
						effectEl.getAttribute("data-keyword-id") ===
							String(kid) &&
						effectEl.style.display !== "none";
					keywordsEl
						.querySelectorAll(".detail-keyword--has-effect")
						.forEach((el) =>
							el.setAttribute("aria-expanded", "false"),
						);
					setKeywordEffect(kid, effect);
					btn.setAttribute(
						"aria-expanded",
						wasOpen ? "false" : "true",
					);
				});
				keywordsEl.appendChild(btn);
			} else {
				const span = document.createElement("span");
				span.className = "detail-keyword";
				span.textContent = label;
				keywordsEl.appendChild(span);
			}
		});
	}

	function renderKeywordsPlain(keywordsEl, text) {
		if (!keywordsEl) return;
		clearKeywordEffect();
		keywordsEl.textContent = text || "-";
	}

	/**
	 * マッチ状態などから、このユニットに割当された神器・英雄特性・追加能力を解決する。
	 * @returns {{trait:?object, artefact:?object, season:?object}|null}
	 */
	function resolveUnitEnhancements(unit) {
		if (unit?.enhancements) return unit.enhancements;

		const unitId = Number(unit?.id || unit?.unitId || 0);
		if (!unitId) return null;

		const players =
			(typeof MatchStateManager !== "undefined" &&
				MatchStateManager.getState?.()?.players) ||
			[];
		if (!players.length) return null;

		const slot = Number(unit.playerSlot || 0);
		const candidates = slot
			? players.filter((p) => Number(p.slot) === slot)
			: players;

		for (const player of candidates) {
			const enh = player?.roster?.enhancements;
			if (!enh) continue;
			const trait =
				enh.trait && Number(enh.trait.targetUnitId) === unitId
					? enh.trait
					: null;
			const artefact =
				enh.artefact && Number(enh.artefact.targetUnitId) === unitId
					? enh.artefact
					: null;
			const season =
				enh.season && Number(enh.season.targetUnitId) === unitId
					? enh.season
					: null;
			if (trait || artefact || season) return { trait, artefact, season };
		}
		return null;
	}

	/**
	 * 召喚済み顕現から、このユニットを効果対象にしているバフを解決する。
	 * 効果文は顕現呪文(伝承)ではなく、顕現ユニット(m_units)に紐づくアビリティを使う。
	 * @returns {list<{label:string,name:string,effect:?string,kind:string}>}
	 */
	function resolveManifestationBuffs(unit) {
		const state =
			(typeof MatchStateManager !== "undefined" &&
				MatchStateManager.getState?.()) ||
			null;
		if (!state?.game?.manifestationTargets) return [];

		const slot = Number(unit?.playerSlot || 0);
		const instanceKey = String(unit?.instanceKey || "").trim();
		if (!slot || !instanceKey) return [];

		const targetMap = state.game.manifestationTargets[slot] || {};
		const manifestKeys = Object.keys(targetMap).filter(
			(manifestKey) => String(targetMap[manifestKey]) === instanceKey,
		);
		if (!manifestKeys.length) return [];

		const player =
			(typeof MatchStateManager.getPlayer === "function" &&
				MatchStateManager.getPlayer(slot)) ||
			(state.players || []).find((p) => Number(p.slot) === slot) ||
			null;
		const rosterManifests = player?.roster?.manifestations || [];
		const byKey = new Map();
		rosterManifests.forEach((m) => {
			const key = String(m.instanceKey || "").trim();
			if (key) byKey.set(key, m);
		});

		const items = [];
		manifestKeys.forEach((manifestKey) => {
			const manifest = byKey.get(manifestKey);
			const abilities = Array.isArray(manifest?.abilities)
				? manifest.abilities
				: [];
			const manifestName = manifest?.name || manifest?.spellName || "";

			if (abilities.length) {
				abilities.forEach((ab) => {
					const effect = String(ab?.effect || "").trim();
					items.push({
						label: "顕現",
						name: ab?.name || manifestName || manifestKey,
						effect: effect !== "" ? effect : null,
						kind: "manifestation",
					});
				});
				return;
			}

			// abilities 未ロード時のフォールバック（名前のみ）
			if (manifestName) {
				items.push({
					label: "顕現",
					name: manifestName,
					effect: null,
					kind: "manifestation",
				});
			}
		});
		return items;
	}

	function renderUnitEnhancements(unit) {
		const container = getEl("detailUnitEnhancements");
		if (!container) return;

		const resolved = resolveUnitEnhancements(unit);
		const items = [];
		if (resolved?.trait) {
			items.push({
				label: resolved.trait.label || "英雄特性",
				name: resolved.trait.name,
				effect: resolved.trait.effect,
				kind: "trait",
			});
		}
		if (resolved?.artefact) {
			items.push({
				label: resolved.artefact.label || "神器",
				name: resolved.artefact.name,
				effect: resolved.artefact.effect,
				kind: "artefact",
			});
		}
		if (resolved?.season) {
			items.push({
				label: resolved.season.label || "追加能力",
				name: resolved.season.name,
				effect: resolved.season.effect,
				kind: "season",
			});
		}
		resolveManifestationBuffs(unit).forEach((buff) => items.push(buff));

		if (!items.length) {
			container.innerHTML = "";
			container.style.display = "none";
			return;
		}

		container.innerHTML = items
			.map(
				(item) => `
			<div class="detail-enhancement-box detail-enhancement-box--${item.kind}">
				<div class="detail-enhancement-head">
					<span class="detail-enhancement-badge detail-enhancement-badge--${item.kind}">${escapeHtml(item.label)}</span>
					<strong class="detail-enhancement-name">${escapeHtml(item.name || "-")}</strong>
				</div>
				${
					item.effect
						? `<p class="detail-enhancement-effect">${escapeHtml(item.effect)}</p>`
						: ""
				}
			</div>`,
			)
			.join("");
		container.style.display = "";
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

	/**
	 * 武器の種別（近接/射撃）からアイコン <img> を生成する。
	 * フェーズアイコンと同じ流儀で、近接=combat / 射撃=shooting のアイコンを流用する。
	 * 判定は w.type（'melee'/'ranged'）優先、欠落時は w.rng の有無でフォールバック。
	 */
	function buildWeaponIcon(baseUrl, weapon) {
		const w = weapon || {};
		const type = String(w.type || "")
			.trim()
			.toLowerCase();
		const isRanged = type === "ranged" || (type === "" && !!w.rng);
		const phase = isRanged ? "shooting" : "combat";
		const file =
			typeof MatchPhases !== "undefined" && MatchPhases.ICON_BY_PHASE
				? MatchPhases.ICON_BY_PHASE[phase]
				: isRanged
					? "abShooting.png"
					: "abOffensive.png";
		const alt = isRanged ? "射撃武器" : "近接武器";
		const fallbackSrc = `${baseUrl}assets/icons/${ABILITY_ICON_FALLBACK}`;
		return `<img src="${baseUrl}assets/icons/${file}" alt="${alt}" title="${alt}" onerror="this.onerror=null;this.src='${fallbackSrc}';" style="vertical-align:middle;height:20px;margin-right:5px;">`;
	}

	let lastShownUnit = null;

	function bindAttackableWeapons(weaponsBody, detailData) {
		if (!weaponsBody || typeof window.MatchCombatRef === "undefined") {
			return;
		}
		const weapons = detailData?.weapons || [];
		weaponsBody.querySelectorAll("tr[data-weapon-index]").forEach((tr) => {
			const idx = parseInt(tr.dataset.weaponIndex, 10);
			const weapon = weapons[idx];
			if (!weapon || !window.MatchCombatRef.canAttackWith(weapon)) {
				return;
			}
			tr.classList.add("is-attackable");
			tr.setAttribute("role", "button");
			tr.tabIndex = 0;
			tr.setAttribute(
				"aria-label",
				`${weapon.name || "武器"}で攻撃対象を選ぶ`,
			);
			const activate = (e) => {
				if (e.type === "keydown" && e.key !== "Enter" && e.key !== " ") {
					return;
				}
				e.preventDefault();
				window.MatchCombatRef.start({
					attacker: lastShownUnit,
					weapon,
					detailData,
				});
			};
			tr.addEventListener("click", activate);
			tr.addEventListener("keydown", activate);
		});
	}

	async function show(unit) {
		const unitDetailModal = getEl(modalId);
		if (!unitDetailModal || !unit) return;

		const unitId = unit.id || unit.unitId;
		if (!unitId) return;

		lastShownUnit = {
			id: unitId,
			name: unit.name,
			keywords: unit.keywords,
			instanceKey: unit.instanceKey || "",
			playerSlot: unit.playerSlot,
		};

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
		if (getEl("detailSaveLabel"))
			getEl("detailSaveLabel").textContent = "防御力";
		if (getEl("detailUnitControl"))
			getEl("detailUnitControl").textContent = "...";
		if (keywordsEl) renderKeywordsPlain(keywordsEl, unit.keywords || "...");
		if (flavorTextEl) flavorTextEl.textContent = unit.flavor_text || "...";
		if (regimentSection) regimentSection.style.display = "none";
		if (regimentOptionsEl) regimentOptionsEl.innerHTML = "";
		renderUnitEnhancements(unit);

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
			if (lastShownUnit) {
				lastShownUnit.name = info.name || lastShownUnit.name;
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
				getEl("detailUnitSave").textContent = formatSaveDisplay(
					info.save,
					info.ward,
				);
			}
			if (getEl("detailSaveLabel")) {
				const wardStr = formatStatPlus(info.ward);
				getEl("detailSaveLabel").textContent = wardStr
					? "防御力/加護"
					: "防御力";
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
				const isHero = Number(info.is_hero);
				const regNames =
					isHero && info.regiment_eligibility_names
						? info.regiment_eligibility_names
						: "";
				const details = detailData.keyword_details;
				if (Array.isArray(details) && details.length > 0) {
					renderKeywords(keywordsEl, details, regNames);
				} else {
					let kw = info.keywords || unit.keywords || "-";
					if (regNames) {
						kw = kw && kw !== "-" ? `${kw}, ${regNames}` : regNames;
					}
					renderKeywordsPlain(keywordsEl, kw);
				}
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
					detailData.weapons.forEach((w, index) => {
						const tr = document.createElement("tr");
						tr.dataset.weaponIndex = String(index);
						const rangeDisplay = w.rng ? `${w.rng}"` : "近接";
						const badge = w.abilities
							? `<br><small style="color: #ffcc00; font-size:0.75rem;">★ ${w.abilities}</small>`
							: "";
						const canAttack =
							typeof window.MatchCombatRef !== "undefined" &&
							window.MatchCombatRef.canAttackWith(w);
						const viewPhase =
							window.MatchAbilityPanel?.getViewPhase?.();
						const inAttackPhase =
							viewPhase === "shooting" || viewPhase === "combat";
						if (inAttackPhase && !canAttack) {
							tr.classList.add("is-weapon-inactive");
						}
						const weaponIcon = buildWeaponIcon(baseUrl, w);
						tr.innerHTML = `
							<td>${weaponIcon}<strong>${w.name || "不明な武器"}</strong>${badge}</td>
							<td>${rangeDisplay}</td>
							<td>${formatWeaponNumber(w.atk)}</td>
							<td>${formatWeaponNumber(w.hit, "+")}</td>
							<td>${formatWeaponNumber(w.wnd, "+")}</td>
							<td>${formatWeaponNumber(w.rnd)}</td>
							<td>${formatWeaponNumber(w.dmg)}</td>
						`;
						weaponsBody.appendChild(tr);
					});
					bindAttackableWeapons(weaponsBody, detailData);
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
						const freqLabel =
							typeof MatchPhases !== "undefined" &&
							MatchPhases.frequencyInfo
								? MatchPhases.frequencyInfo({
										activation: ab.activation,
										usageScope: ab.usage_scope,
										usagePer: ab.usage_per,
									}).label
								: "";
						// 手動翻訳(trigger_condition_ja)があるときは、フェイズ・頻度の情報を
						// 内包する想定なので、両バッジをこの1バッジにまとめて置き換える。
						const triggerConditionJa = (
							ab.trigger_condition_ja || ""
						).trim();
						let phaseBadge;
						let typeBadge;
						if (triggerConditionJa) {
							phaseBadge = `<span style="font-size:0.7rem; background:#333; padding:2px 6px; border-radius:3px; margin-left:8px; color:#ccc;">${triggerConditionJa}</span>`;
							typeBadge = "";
						} else {
							phaseBadge = phaseLabel
								? `<span style="font-size:0.7rem; background:#333; padding:2px 6px; border-radius:3px; margin-left:8px; color:#ccc;">${phaseLabel}</span>`
								: "";
							typeBadge = freqLabel
								? `<span style="font-size:0.7rem; background:#4a3b19; padding:2px 6px; border-radius:3px; margin-left:4px; color:#ffcc00;">${freqLabel}</span>`
								: "";
						}
						const cpBadge =
							ab.command_point && Number(ab.command_point) > 0
								? `<span style="font-size:0.7rem; background:#1d3a5f; padding:2px 6px; border-radius:3px; margin-left:4px; color:#9ad;">CP ${Number(ab.command_point)}</span>`
								: "";
						const castStr = formatCastingValue(ab.casting_value);
						const castLabel =
							ab.casting_type === "prayer" ? "祈祷" : "詠唱";
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
		clearKeywordEffect();
		window.ModalScroll?.unlock(modalId);
	}

	function init() {
		const btnCloseDetailModal = getEl("btnCloseDetailModal");
		const unitDetailModal = getEl(modalId);
		if (btnCloseDetailModal) {
			btnCloseDetailModal.addEventListener("click", close);
		}
		if (unitDetailModal) {
			unitDetailModal
				.querySelectorAll(".detail-modal-close-top")
				.forEach((btn) => btn.addEventListener("click", close));
			unitDetailModal.addEventListener("click", (e) => {
				if (e.target === unitDetailModal) close();
			});
		}
	}

	document.addEventListener("DOMContentLoaded", init);

	return { show, close };
})();
