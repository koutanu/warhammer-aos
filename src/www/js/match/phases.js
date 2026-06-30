/**
 * ゲームフェーズ定義・正規化・日本語表示
 * 参照: AoS 第4版コアルール（日本語版）のフェーズ名称
 */
const MatchPhases = {
	ORDER: ["hero", "movement", "shooting", "charge", "combat", "end"],

	/** ステッパー短縮ラベル */
	LABELS: {
		deployment: "配置",
		round_start: "ラウンド開始",
		hero: "ヒーロー",
		movement: "移動",
		shooting: "射撃",
		charge: "突撃",
		combat: "戦闘",
		end: "終了",
		any: "全般",
	},

	/** フェーズ正式名称（コアルール準拠） */
	PHASE_LABELS_JA: {
		deployment: "初期配置フェイズ",
		round_start: "ラウンド開始時",
		hero: "ヒーローフェーズ",
		movement: "移動フェイズ",
		shooting: "射撃フェイズ",
		charge: "突撃フェイズ",
		combat: "戦闘フェイズ",
		end: "ターン終了フェイズ",
		any: "全フェーズ",
	},

	TURN_LABELS_JA: {
		your: "自分のターン",
		opponent: "相手のターン",
		any: "いつでも",
		battle: "バトル中",
	},

	/** アビリティ種別（デッキ category） */
	CATEGORY_LABELS_JA: {
		common: "汎用コマンド/コアアビリティ",
		battletrait: "戦闘特性",
		terrain: "陣営地形",
		formation: "戦闘陣形",
		trait: "英雄特性",
		artefact: "神器",
		spell: "呪文",
		prayer: "祈祷",
		manifestation: "顕現",
		unit: "ユニット能力",
	},

	CATEGORY_ORDER: [
		"unit",
		"battletrait",
		"terrain",
		"formation",
		"trait",
		"artefact",
		"spell",
		"prayer",
		"manifestation",
		"common",
	],

	/**
	 * アビリティのアイコン分類(icon_type) → アイコンファイル名。
	 * 値は Wahapedia の ability_type カテゴリ（小文字化して照合）。
	 */
	ICON_BY_TYPE: {
		offensive: "abOffensive.png",
		defensive: "abDefensive.png",
		movement: "abMovement.png",
		shooting: "abShooting.png",
		damage: "abDamage.png",
		control: "abControl.png",
		rallying: "abRallying.png",
		special: "abSpecial.png",
	},

	/** icon_type が無い旧データ向けのフォールバック（正規化フェイズ → アイコン） */
	ICON_BY_PHASE: {
		round_start: "abSpecial.png",
		hero: "abControl.png",
		movement: "abMovement.png",
		shooting: "abShooting.png",
		charge: "abMovement.png",
		combat: "abOffensive.png",
		end: "abControl.png",
		any: "abSpecial.png",
	},

	ICON_DEFAULT: "abSpecial.png",

	/**
	 * アビリティオブジェクトから表示アイコンのファイル名を解決する。
	 * snake_case(icon_type/trigger_phase) と camelCase(iconType/triggerPhase) の両方を許容。
	 */
	abilityIconFile(ability) {
		const ab = ability || {};
		const iconType = String(ab.icon_type || ab.iconType || "")
			.trim()
			.toLowerCase();
		if (iconType && this.ICON_BY_TYPE[iconType]) {
			return this.ICON_BY_TYPE[iconType];
		}
		const phaseNorm = this.normalizePhase(
			ab.trigger_phase || ab.triggerPhase,
		);
		return this.ICON_BY_PHASE[phaseNorm] || this.ICON_DEFAULT;
	},

	/** ベースURLを付与した <img> 用のフルパスを返す */
	abilityIconUrl(baseUrl, ability) {
		return `${baseUrl || ""}assets/icons/${this.abilityIconFile(ability)}`;
	},

	normalizePhase(trigger) {
		const raw = String(trigger || "")
			.trim()
			.toUpperCase();
		if (!raw) return "any";
		if (raw.includes("DEPLOY")) return "deployment";
		if (raw.includes("ROUND") && raw.includes("START")) return "round_start";
		if (raw.includes("START") && raw.includes("TURN")) return "hero";
		if (raw.includes("HERO")) return "hero";
		if (raw.includes("MOVEMENT") || raw === "MOVE") return "movement";
		if (raw.includes("SHOOT")) return "shooting";
		if (raw.includes("CHARGE")) return "charge";
		if (raw.includes("COMBAT") || raw === "FIGHT") return "combat";
		if (raw.includes("END")) return "end";
		if (raw.includes("ANY")) return "any";
		return "any";
	},

	/**
	 * カンマ区切りで複数登録された trigger_phase を正規化フェーズ配列に変換。
	 * 空なら ["any"]。重複は除去。
	 */
	normalizePhases(trigger) {
		const raw = String(trigger || "").trim();
		if (!raw) return ["any"];
		const parts = raw
			.split(",")
			.map((s) => s.trim())
			.filter(Boolean);
		if (!parts.length) return ["any"];
		const norms = parts.map((p) => this.normalizePhase(p));
		return [...new Set(norms)];
	},

	normalizeTriggerTurn(triggerTurn) {
		const raw = String(triggerTurn || "")
			.trim()
			.toLowerCase();
		if (!raw) return "your";
		if (raw.includes("opponent") || raw.includes("enemy")) return "opponent";
		if (raw.includes("any") || raw.includes("both")) return "any";
		if (raw.includes("battle") || raw.includes("game")) return "battle";
		if (raw.includes("your")) return "your";
		return "your";
	},

	/** ステッパー等の短縮表示 */
	label(phase) {
		return this.LABELS[phase] || String(phase || "");
	},

	/** フェーズ正式名称 */
	labelPhaseJa(phaseOrNorm) {
		return this.PHASE_LABELS_JA[phaseOrNorm] || this.label(phaseOrNorm);
	},

	labelTurnJa(turnNorm) {
		return this.TURN_LABELS_JA[turnNorm] || turnNorm || "";
	},

	labelCategoryJa(category) {
		return this.CATEGORY_LABELS_JA[category] || category || "";
	},

	/**
	 * DB の英語 trigger_phase / trigger_timing を日本語表示に変換
	 */
	formatTriggerPhase(raw, norm) {
		const phaseNorm = norm || this.normalizePhase(raw);
		if (phaseNorm && phaseNorm !== "any") {
			return this.labelPhaseJa(phaseNorm);
		}
		const upper = String(raw || "").trim().toUpperCase();
		if (!upper) return "";
		if (upper.includes("ANY")) return this.labelPhaseJa("any");
		return this.labelPhaseJa(this.normalizePhase(raw));
	},

	/**
	 * 複数登録された trigger_phase を日本語ラベルへ変換し連結して返す。
	 * norms（正規化フェーズ配列）が渡されればそれを優先する。
	 */
	formatTriggerPhases(raw, norms) {
		const list =
			Array.isArray(norms) && norms.length
				? norms
				: this.normalizePhases(raw);
		const labels = [...new Set(list.map((n) => this.labelPhaseJa(n)))];
		return labels.filter(Boolean).join(" / ");
	},

	formatTriggerTurn(raw, norm) {
		const turnNorm = norm || this.normalizeTriggerTurn(raw);
		if (!String(raw || "").trim() && turnNorm === "your") {
			return this.labelTurnJa("your");
		}
		return this.labelTurnJa(turnNorm);
	},

	categorySortIndex(category) {
		const idx = this.CATEGORY_ORDER.indexOf(category);
		return idx >= 0 ? idx : 99;
	},

	matchesCurrentPhase(abilityNorm, currentPhase) {
		if (!abilityNorm || abilityNorm === "any") return true;
		return abilityNorm === currentPhase;
	},

	/** 複数登録フェーズ（配列）のいずれかが現在フェーズに該当すれば true */
	matchesAnyPhase(abilityNorms, currentPhase) {
		const norms = Array.isArray(abilityNorms)
			? abilityNorms
			: [abilityNorms];
		if (!norms.length) return true;
		return norms.some((n) => this.matchesCurrentPhase(n, currentPhase));
	},

	matchesCurrentTurn(turnNorm, isMyTurn) {
		const norm = turnNorm || "your";
		if (norm === "any" || norm === "battle") return true;
		if (norm === "opponent") return !isMyTurn;
		return isMyTurn;
	},

	nextPhase(currentPhase) {
		const idx = this.ORDER.indexOf(currentPhase);
		if (idx < 0 || idx >= this.ORDER.length - 1) return null;
		return this.ORDER[idx + 1];
	},

	isBattleScoped(triggerTurn) {
		return this.normalizeTriggerTurn(triggerTurn) === "battle";
	},

	/**
	 * アビリティの使用リセットスコープ（"battle"|"turn"）を返す。
	 * usage_scope = once_per_battle のみ battle（ゲーム終了まで使用済み保持）。
	 */
	usageScopeOf(ability) {
		const ab = ability || {};
		return String(ab.usageScope || "").toLowerCase() === "once_per_battle"
			? "battle"
			: "turn";
	},

	isBattleScope(scope) {
		return scope === "battle";
	},

	/** usage_per = army か判定 */
	isArmy(ability) {
		const ab = ability || {};
		return String(ab.usagePer || "").toLowerCase() === "army";
	},

	/**
	 * 使用済みトグルの対象か（回数制限あり かつ 非パッシブ）。
	 * unlimited / passive はトグル非表示。reaction でも once_per_* なら追跡対象。
	 */
	isUsageTracked(ability) {
		const ab = ability || {};
		if (String(ab.activation || "").toLowerCase() === "passive") return false;
		const scope = String(ab.usageScope || "unlimited").toLowerCase();
		return (
			scope === "once_per_turn" ||
			scope === "once_per_phase" ||
			scope === "once_per_round" ||
			scope === "once_per_battle"
		);
	},

	/**
	 * 構造化フィールド(activation / usage_scope / usage_per)から使用頻度の表示情報を返す。
	 * kind は CSS 着色用。回数制限が無いものは label を空にして非表示。
	 */
	frequencyInfo(ability) {
		const ab = ability || {};
		const activation = String(ab.activation || "active").toLowerCase();
		const scope = String(ab.usageScope || "unlimited").toLowerCase();
		const army = this.isArmy(ab) ? "（アーミー）" : "";

		if (activation === "passive") return { kind: "passive", label: "パッシブ" };
		if (scope === "once_per_battle")
			return { kind: "battle", label: "バトルに1回" + army };
		if (scope === "once_per_round")
			return { kind: "turn", label: "ラウンドに1回" + army };
		if (scope === "once_per_turn")
			return { kind: "turn", label: "ターンに1回" + army };
		if (scope === "once_per_phase")
			return { kind: "turn", label: "フェイズに1回" + army };
		if (activation === "reaction")
			return { kind: "reaction", label: "リアクション" };
		return { kind: "", label: "" };
	},
};
