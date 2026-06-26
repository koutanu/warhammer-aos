<?php

class Login_Model extends Model
{

	public function __construct()
	{
		parent::__construct();
	}

	public function auth($account, $password)
	{
		// ユーザー情報の取得
		$sql = "SELECT id, account, password, name, auth FROM m_users WHERE account = :account LIMIT 1;";
		$result = $this->db->select($sql, ['account' => $account]);

		$user = $result[0] ?? null;

		// 1. ユーザーが存在し、かつパスワードが一致するか検証
		if ($user && password_verify($password, $user['password'])) {

			// 2. 【ここに追加】認証成功時のみ、最終ログイン日時を更新
			$this->db->update(
				'm_users',
				['last_login_at' => date('Y-m-d H:i:s')],
				"id = :id",
				['id' => $user['id']]
			);

			// セッション等に格納するユーザー情報を返す
			return array(
				'user_id'      => $user['id'],
				'user_account' => $user['account'],
				'user_name'    => $user['name'],
				'user_auth'    => $user['auth'],
			);
		}

		// 認証失敗（ユーザー不在、またはパスワード不一致）
		return false;
	}

	// public function createUser($account, $name, $password, $auth = 1)
	// {
	// 	$user_data = [
	// 		'account'    => $account,
	// 		'name'       => $name,
	// 		// ハッシュ化は必須（ここも完璧です）
	// 		'password'   => password_hash($password, PASSWORD_DEFAULT),
	// 		'auth'       => $auth,
	// 		'created_at' => date('Y-m-d H:i:s')
	// 	];

	// 	// insertメソッドは array(true, lastInsertId) を返す仕様でしたね
	// 	return $this->db->insert('m_users', $user_data);
	// }
}
