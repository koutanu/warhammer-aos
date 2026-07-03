<?php

class Home extends Controller
{
	private $class_name = 'home';

	public function __construct()
	{
		parent::__construct();
		Auth::handleLogin();
	}

	function index()
	{
		$token = Session::setToken($this->class_name . '/index');
		$userId = (int)Session::getUserInfo('user_id');
		require_once MODELS . 'match_model.php';
		$matchModel = new Match_Model();

		// 画面に渡したいデータを一つの連想配列にまとめる
		$data = [
			'token'          => $token,
			'date'           => date('Y-m-d'),
			'js'             => [$this->class_name . '/index.js'],
			'active_matches' => $matchModel->getActiveMatchesByUser($userId),
		];

		// まとめてドーンと渡す！
		$this->view->render($this->class_name, 'index', 'Warhammer AoS', $data);
	}
}
