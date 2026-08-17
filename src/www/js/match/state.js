/**
 * マッチ State 管理
 * - VP はプレイヤーごとの totalVp（サーバの player_*_vp 由来）を直接保持・更新する
 */
const MatchStateManager = {
	state: null,
	dirty: false,

	init(initialState) {
		this.state = this.normalizeState(initialState);
		this.dirty = false;
		return this.state;
	},

	normalizeState(raw) {
		const state = JSON.parse(JSON.stringify(raw || {}));
		state.players = state.players || [];
		state.game = state.game || {
			battleRound: 1,
			activePlayer: 1,
			firstPlayer: null,
			phase: "hero",
			turnCounter: 1,
			usedAbilities: {},
			abilityTargetUnits: {},
			destroyedUnits: {},
			summonedUnits: {},
			replacedUnits: {},
			movedUnits: {},
			shotUnits: {},
			foughtUnits: {},
		};
		state.game.usedAbilities = state.game.usedAbilities || {};
		state.game.abilityTargetUnits = state.game.abilityTargetUnits || {};
		state.game.destroyedUnits = state.game.destroyedUnits || {};
		state.game.summonedUnits = state.game.summonedUnits || {};
		state.game.replacedUnits = state.game.replacedUnits || {};
		state.game.movedUnits = state.game.movedUnits || {};
		state.game.shotUnits = state.game.shotUnits || {};
		state.game.foughtUnits = state.game.foughtUnits || {};
		if (state.game.firstPlayer === undefined) {
			state.game.firstPlayer = null;
		}
		(state.players || []).forEach((p) => {
			p.commandPoints = Math.max(0, Number(p.commandPoints) || 0);
			p.rageLevel = Math.max(0, Math.min(7, Number(p.rageLevel) || 0));
			p.rageDice = Math.max(0, Number(p.rageDice) || 0);
		});
		return state;
	},

	getState() {
		return this.state;
	},

	getPlayer(slot) {
		return this.state?.players?.find((p) => p.slot === slot) || null;
	},

	getPlayerVp(slot) {
		const p = this.getPlayer(slot);
		return p ? p.totalVp || 0 : 0;
	},

	setPlayerVp(slot, vp) {
		const p = this.getPlayer(slot);
		if (!p) return 0;
		p.totalVp = Math.max(0, vp);
		this.dirty = true;
		return p.totalVp;
	},

	getPlayerResource(slot, key) {
		const p = this.getPlayer(slot);
		if (!p || !this.isResourceKey(key)) return 0;
		return Number(p[key]) || 0;
	},

	setPlayerResource(slot, key, value) {
		const p = this.getPlayer(slot);
		if (!p || !this.isResourceKey(key)) return 0;
		let next = Math.max(0, Number(value) || 0);
		if (key === "rageLevel") {
			next = Math.min(7, next);
		}
		p[key] = next;
		this.dirty = true;
		return p[key];
	},

	isResourceKey(key) {
		return key === "commandPoints" || key === "rageLevel" || key === "rageDice";
	},

	applyServerState(serverState) {
		this.state = this.normalizeState(serverState);
		this.dirty = false;
		window.dispatchEvent(new CustomEvent("matchStateUpdated"));
	},

	/** ポーリング用: 使用済み・ターン進行のみ同期（参照用 phase は端末ローカル） */
	applyServerGameSync(game, updatedAt) {
		if (!this.state || !game) return;
		if (!this.state.game) {
			this.state.game = {
				battleRound: 1,
				activePlayer: 1,
				firstPlayer: null,
				phase: "hero",
				turnCounter: 1,
				usedAbilities: {},
				abilityTargetUnits: {},
				destroyedUnits: {},
				summonedUnits: {},
				replacedUnits: {},
				movedUnits: {},
				shotUnits: {},
				foughtUnits: {},
			};
		}
		const localPhase = this.state.game.phase;
		this.state.game.battleRound =
			game.battleRound ?? this.state.game.battleRound;
		this.state.game.activePlayer =
			game.activePlayer ?? this.state.game.activePlayer;
		this.state.game.firstPlayer =
			game.firstPlayer !== undefined
				? game.firstPlayer
				: this.state.game.firstPlayer;
		this.state.game.turnCounter =
			game.turnCounter ?? this.state.game.turnCounter;
		this.state.game.usedAbilities = game.usedAbilities || {};
		this.state.game.abilityTargetUnits = game.abilityTargetUnits || {};
		this.state.game.destroyedUnits = game.destroyedUnits || {};
		this.state.game.summonedUnits = game.summonedUnits || {};
		this.state.game.replacedUnits = game.replacedUnits || {};
		this.state.game.movedUnits = game.movedUnits || {};
		this.state.game.shotUnits = game.shotUnits || {};
		this.state.game.foughtUnits = game.foughtUnits || {};
		this.state.game.phase = localPhase;
		if (updatedAt) {
			this.state.updatedAt = updatedAt;
		}
		window.dispatchEvent(new CustomEvent("matchStateUpdated"));
	},

	/**
	 * ポーリング用: 相手端末で更新された CP / 憤激を取り込む。
	 * ローカル編集中（dirty）では呼ばないこと。
	 * @returns {boolean} 値が変わったか
	 */
	applyServerPlayerResources(players) {
		if (!this.state || !Array.isArray(players)) return false;
		let changed = false;
		players.forEach((remote) => {
			const local = this.getPlayer(remote.slot);
			if (!local) return;
			const next = {
				commandPoints: Math.max(0, Number(remote.commandPoints) || 0),
				rageLevel: Math.max(0, Math.min(7, Number(remote.rageLevel) || 0)),
				rageDice: Math.max(0, Number(remote.rageDice) || 0),
			};
			["commandPoints", "rageLevel", "rageDice"].forEach((key) => {
				if (local[key] !== next[key]) {
					local[key] = next[key];
					changed = true;
				}
			});
		});
		if (changed) {
			window.dispatchEvent(new CustomEvent("matchStateUpdated"));
		}
		return changed;
	},

	isDirty() {
		return this.dirty;
	},

	markClean() {
		this.dirty = false;
	},
};
