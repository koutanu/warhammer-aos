<?php

class Login extends Controller
{
	private $class_name = 'login';
	private $alert;

	function __construct()
	{
		parent::__construct();
	}

	function index()
	{
		$data = [
			'js' => [$this->class_name . '/index.js'],
			'alert' => $this->alert
		];
		$this->view->render($this->class_name, 'index', '', $data);
	}

	// function account()
	// {
	// 	$data = [
	// 		'js' => [$this->class_name . '/index.js']
	// 	];
	// 	$this->view->render($this->class_name, 'account', 'アカウント新規追加', $data);
	// }

	/**
	 * ログイン実行
	 */
	function zikkou()
	{
		// --- 追加：IPアドレスを取得 ---
		$remote_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

		// ハニーポットのチェック
		$hp_email = filter_input(INPUT_POST, 'hp_email');

		if ($hp_email !== '' && $hp_email !== null) {
			// IPアドレスをログに含める
			$message = "不正アクセス検知(Honeypot) / IP: {$remote_ip} / Data: {$hp_email}";
			$this->tasklog->record(2, $message, $this->class_name, __FUNCTION__);

			header('location: ' . URL . 'login/error');
			exit;
		}

		$login_account = filter_input(INPUT_POST, 'login_account');
		$password = filter_input(INPUT_POST, 'password');

		$user_info = $this->model->auth($login_account, $password);

		if ($user_info !== false) {
			Session::init();
			session_regenerate_id(true);
			Session::set('user_info', $user_info);
			Session::set('login_state', true);
			Session::set('last_request_time', time());

			header('location: ' . URL . 'home');
			exit;
		} else {
			// --- 修正：ログインエラー時にもIPアドレスを記録 ---
			$message = "ログインエラー / アカウント: {$login_account} / IP: {$remote_ip}";
			// セキュリティ上、パスワードをそのままログに残すのは避けたほうが良いため除外を推奨します

			$this->tasklog->record(1, $message, $this->class_name, __FUNCTION__);
			header('location: ' . URL . 'login/error');
			exit;
		}
	}

	/**
	 * ユーザー作成実行
	 */
	// function createUser()
	// {
	// 	$account  = filter_input(INPUT_POST, 'account');
	// 	$password = filter_input(INPUT_POST, 'password');
	// 	$name     = filter_input(INPUT_POST, 'name');

	// 	// バリデーション（簡易例：空チェック）
	// 	if (!$account || !$password || !$name) {
	// 		$this->alert = '全項目入力してください。';
	// 		$this->account();
	// 		return;
	// 	}

	// 	if ($this->model->createUser($account, $name, $password) >= 0) {
	// 		// 登録成功時はログイン画面へ戻すなどの処理が必要
	// 		header('location: ' . URL . 'login');
	// 		exit;
	// 	} else {
	// 		$this->alert = '登録に失敗しました。';
	// 		$this->account();
	// 	}
	// }

	function error()
	{
		$data = [
			'js' => [$this->class_name . '/index.js'],
			'alert' => 'ログインできませんでした。'
		];
		$this->view->render($this->class_name, 'index', 'Login', $data);
	}

	function timeout()
	{
		header('location: ' . URL . 'login');
		exit;
	}
}
