<?php

class Database extends PDO
{
	/** @var int transact() 内の最後の INSERT で得た ID（commit 後は lastInsertId() が 0 になるため保持） */
	public $lastTransactInsertId = 0;

	public function __construct($DB_TYPE, $DB_HOST, $DB_NAME, $DB_USER, $DB_PASS)
	{
		// Database.php 修正
		parent::__construct($DB_TYPE . ':host=' . $DB_HOST . ';dbname=' . $DB_NAME . ';charset=utf8mb4', $DB_USER, $DB_PASS);
		parent::setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); //AFTER_ERRMODE = エラーレポート / ERRMODE_EXCEPTION = 例外を投げる
	}

	public function select($sql, $bind = array())
	{
		// DSNで指定済みなら SET NAMES は不要
		$sth = $this->prepare($sql);
		try {
			foreach ($bind as $key => $value) {
				$sth->bindValue(":$key", $value);
			}
			$sth->execute();
			return $sth->fetchAll(PDO::FETCH_ASSOC);
		} catch (Exception $e) {
			// ログには詳細を書き、呼び出し元には空配列を返す
			error_log($e->getMessage());
			return array();
		}
	}

	public function insert($table, $data)
	{
		// 💡 追加：すべてのインサート処理に常に user_id を自動でねじ込む
		$data['created_by'] = Session::getUserInfo('user_id');

		$fieldNames = implode(', ', array_keys($data));
		$fieldValues = ':' . implode(', :', array_keys($data));
		$sth = $this->prepare("INSERT INTO $table ($fieldNames) VALUES ($fieldValues);");
		try {
			foreach ($data as $key => $value) {
				$sth->bindValue(":$key", $value);
			}
			$sth->execute();
			$last_insert_id = $this->lastInsertId();
			return array(true, $last_insert_id);
		} catch (Exception $e) {
			return array(false, $e->getMessage());
		}
	}

	public function executesql($sql, $bind = array())
	{
		// --- ★自動バインド処理の修正 ---
		$userId = class_exists('Session') ? Session::getUserInfo('user_id') : null;

		// SQL文の中に「:created_by」が含まれていて、まだ $bind に値がない場合は自動セット
		if (strpos($sql, ':created_by') !== false && !array_key_exists('created_by', $bind)) {
			$bind['created_by'] = $userId;
		}

		// SQL文の中に「:updated_by」が含まれていて、まだ $bind に値がない場合は自動セット
		if (strpos($sql, ':updated_by') !== false && !array_key_exists('updated_by', $bind)) {
			$bind['updated_by'] = $userId;
		}

		// SQL文の中に「:user_id」が含まれていて、まだ $bind に値がない場合は自動セット
		if (strpos($sql, ':user_id') !== false && !array_key_exists('user_id', $bind)) {
			$bind['user_id'] = $userId;
		}
		// --- ★ここまで ---

		$sth = $this->prepare($sql);
		foreach ($bind as $key => $value) {
			$sth->bindValue(":$key", $value);
		}
		try {
			$sth->execute();
			$row_count = $sth->rowCount();
			return array(true, $row_count);
		} catch (Exception $e) {
			return array(false, $e->getMessage());
		}
	}

	// Database.php の update メソッドを強化
	public function update($table, $data, $where, $whereBind = array())
	{
		// 💡 追加：すべてのアップデート処理に常に user_id (または updated_by) を自動でねじ込む
		$data['updated_by'] = Session::getUserInfo('user_id');

		$fieldDetails = "";
		foreach ($data as $key => $value) {
			$fieldDetails .= "`$key`=:$key,";
		}
		$fieldDetails = rtrim($fieldDetails, ',');

		$sql = "UPDATE `$table` SET $fieldDetails WHERE $where";
		$sth = $this->prepare($sql);

		// 1. 更新データのバインド
		foreach ($data as $key => $value) {
			$sth->bindValue(":$key", $value);
		}

		// 2. WHERE句データのバインド（キーが重複していても安全なようにする）
		foreach ($whereBind as $key => $value) {
			// もし :id などのキーが $data 側と被っていても、
			// ここで正しくWHERE用の値をセットし直す
			$sth->bindValue(":$key", $value);
		}

		try {
			$sth->execute();
			return array(true, $sth->rowCount());
		} catch (Exception $e) {
			error_log($e->getMessage());
			return array(false, "DB Error"); // ユーザーには詳細なSQLエラーを見せない
		}
	}

	public function delete($sql, $bind = array())
	{
		$sql1 = str_replace(';', ' LIMIT 1;', $sql);
		return $this->executesql($sql1, $bind);
	}

	public function deleteall($sql, $bind = array())
	{
		return $this->executesql($sql, $bind);
	}

	public function transact($sqls = array(), $binds = array())
	{
		$this->beginTransaction();
		$this->lastTransactInsertId = 0;
		$i = 0;
		$count = count($sqls);
		try {
			foreach ($sqls as $sql) {
				$sth = $this->prepare($sql);
				$bind = $binds[$i];
				foreach ($bind as $key => $value) {
					$sth->bindValue(":$key", $value, PDO::PARAM_STR);
				}
				$sth->execute();
				$insertId = $this->lastInsertId();
				if ($insertId) {
					$this->lastTransactInsertId = (int)$insertId;
				}
				$i++;
			}
			if ($i == $count) {
				$this->commit();
				return array(true, $i);
			} else {
				$rollback = $this->rollBack();
				return array(false, $rollback);
			}
		} catch (Exception $e) {
			$this->rollBack();
			return array(false, $e->getMessage());
		}
	}
}
