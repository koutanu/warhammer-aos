/**
 * AoS 4版 VP計算（match_model.php と同期すること）
 */
const MatchScoring = {
	VP_OBJ_HOLD_ONE: 2,
	VP_OBJ_HOLD_TWO_PLUS: 2,
	VP_OBJ_HOLD_MORE: 2,
	VP_BATTLE_TACTIC: 4,
	MAX_ROUNDS: 5,
	MAX_VP_PER_ROUND: 10,

	calcRoundVp(score) {
		let obj = 0;
		if (score.obj_hold_one) obj += this.VP_OBJ_HOLD_ONE;
		if (score.obj_hold_two_plus) obj += this.VP_OBJ_HOLD_TWO_PLUS;
		if (score.obj_hold_more) obj += this.VP_OBJ_HOLD_MORE;

		if (score.is_double_turn) {
			return Math.min(obj, this.MAX_VP_PER_ROUND);
		}

		const bt = score.battle_tactic_completed ? this.VP_BATTLE_TACTIC : 0;
		return Math.min(obj + bt, this.MAX_VP_PER_ROUND);
	},

	calcTotalVp(rounds, playerSlot) {
		let total = 0;
		for (let r = 1; r <= this.MAX_ROUNDS; r++) {
			const score = rounds[r]?.[playerSlot];
			if (score) {
				total += this.calcRoundVp(score);
			}
		}
		return total;
	},

	emptyScore() {
		return {
			obj_hold_one: 0,
			obj_hold_two_plus: 0,
			obj_hold_more: 0,
			battle_tactic_id: null,
			battle_tactic_completed: 0,
			is_double_turn: 0,
			round_vp: 0,
		};
	},
};
