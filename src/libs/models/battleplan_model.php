<?php

class Battleplan_Model extends Model
{
	public function __construct()
	{
		parent::__construct();
	}

	public function getAll(): array
	{
		return $this->db->select(
			'SELECT * FROM m_battleplans ORDER BY sort_order ASC, id ASC;'
		);
	}

	public function getById(int $id): ?array
	{
		if ($id <= 0) {
			return null;
		}
		$rows = $this->db->select(
			'SELECT * FROM m_battleplans WHERE id = :id LIMIT 1;',
			['id' => $id]
		);
		return !empty($rows) ? $rows[0] : null;
	}

	public function getAbilitiesByBattleplanId(int $battleplanId): array
	{
		if ($battleplanId <= 0) {
			return [];
		}
		return $this->db->select(
			'SELECT * FROM m_battleplan_abilities
			 WHERE battleplan_id = :battleplan_id
			   AND (is_hidden = 0 OR is_hidden IS NULL)
			 ORDER BY sort_order ASC, name ASC;',
			['battleplan_id' => $battleplanId]
		);
	}
}
