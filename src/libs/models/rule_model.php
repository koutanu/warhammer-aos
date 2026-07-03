<?php

class Rule_Model extends Model
{

	public function __construct()
	{
		parent::__construct();
	}

	public function getKeywordsByType(string $type = 'unit')
	{
		$type = ($type === 'faction') ? 'faction' : 'unit';
		$sql = "SELECT * FROM m_keywords_master WHERE keyword_type = :type ORDER BY sort_order ASC, id ASC;";
		return $this->db->select($sql, ['type' => $type]);
	}

	public function getCommonAbilities()
	{
		$sql = "SELECT * FROM m_common_abilities ORDER BY sort_order;";
		return $this->db->select($sql);
	}
}
