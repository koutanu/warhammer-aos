<?php

class Tasklog
{
	public $db;

	function __construct()
	{
		$this->db = new Database(DB_TYPE, DB_HOST, DB_NAME, DB_USER, DB_PASS);
	}

	public function getClassId($class)
	{
		$class_name = lcfirst($class);
		$sql = "SELECT class_id FROM amt_log_class WHERE class_name = :class_name;";
		$bind = array('class_name' => $class_name);
		$data = $this->db->select($sql, $bind);
		$id = $data[0]['class_id'];
		return $id;
	}

	public function getMethodId($method)
	{
		$method_name = str_replace('::', '', strstr($method, '::'));
		$sql = "SELECT method_id FROM amt_log_method WHERE method_name = :method_name;";
		$bind = array('method_name' => $method_name);
		$data = $this->db->select($sql, $bind);
		$id = $data[0]['method_id'];
		return $id;
	}

	public function getClassList()
	{
		return $this->db->select("SELECT * FROM amt_log_class ORDER BY order_number;");
	}

	public function getMethodList()
	{
		return $this->db->select("SELECT * FROM amt_log_method ORDER BY order_number;");
	}

	public function record($status, $status_text, $class_name, $method_name, $sql_sentence = '', $memo = '')
	{
		$user_id = intval(Session::getUserInfo('user_id'));
		$user_account = Session::getUserInfo('user_account');
		$user_name = Session::getUserInfo('user_name');
		$user_auth = intval(Session::getUserInfo('user_auth'));
		$sql = "INSERT INTO t_log ("
			. "status, "
			. "status_text, "
			. "user_id, "
			. "user_account, "
			. "user_name, "
			. "user_auth, "
			. "class_name, "
			. "method_name, "
			. "sql_sentence, "
			. "memo, "
			. "datetime) "
			. "SELECT "
			. ":status, "
			. ":status_text, "
			. ":user_id, "
			. ":user_account, "
			. ":user_name, "
			. ":user_auth, "
			. ":class_name, "
			. ":method_name, "
			. ":sql_sentence, "
			. ":memo, "
			. "'" . date('Y-m-d H:i:s') . "';";
		$bind = array(
			'status' => $status,
			'status_text' => $status_text,
			'user_id' => $user_id,
			'user_account' => $user_account,
			'user_name' => $user_name,
			'user_auth' => $user_auth,
			'class_name' => $class_name,
			'method_name' => $method_name,
			'sql_sentence' => $sql_sentence,
			'memo' => $memo
		);
		$count = $this->db->executesql($sql, $bind);
		if ($count > 0) {
			return true;
		} else {
			return false;
		}
	}
}
