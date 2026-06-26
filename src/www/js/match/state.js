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
		};
		state.game.usedAbilities = state.game.usedAbilities || {};
		if (state.game.firstPlayer === undefined) {
			state.game.firstPlayer = null;
		}
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
			};
		}
		const localPhase = this.state.game.phase;
		this.state.game.battleRound = game.battleRound ?? this.state.game.battleRound;
		this.state.game.activePlayer = game.activePlayer ?? this.state.game.activePlayer;
		this.state.game.firstPlayer =
			game.firstPlayer !== undefined ? game.firstPlayer : this.state.game.firstPlayer;
		this.state.game.turnCounter = game.turnCounter ?? this.state.game.turnCounter;
		this.state.game.usedAbilities = game.usedAbilities || {};
		this.state.game.phase = localPhase;
		if (updatedAt) {
			this.state.updatedAt = updatedAt;
		}
		window.dispatchEvent(new CustomEvent("matchStateUpdated"));
	},

	isDirty() {
		return this.dirty;
	},

	markClean() {
		this.dirty = false;
	},
};
