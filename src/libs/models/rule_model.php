<?php

class Rule_Model extends Model
{

	public function __construct()
	{
		parent::__construct();
	}

	public function getCoreAbilities()
	{
		$sql = "SELECT * FROM m_core_abilities ORDER BY id ASC;";
		return $this->db->select($sql);
	}

	public function getCommonAbilities()
	{
		$sql = "SELECT * FROM m_common_abilities ORDER BY sort_order;";
		return $this->db->select($sql);
	}
}
