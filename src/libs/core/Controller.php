<?php

class Controller
{
	public $date;     // 日付データ保持用
	public $view;     // Viewクラスのインスタンス
	public $model;    // Modelクラスのインスタンス
	public $tasklog;  // ログ記録クラスのインスタンス

	/**
	 * コンストラクタ
	 * すべてのコントローラーが起動する際に共通の準備を行います
	 */
	function __construct()
	{
		// 画面表示（View）とログ出力（Tasklog）の機能を自動で準備
		$this->view = new View();
		$this->tasklog = new Tasklog();
	}

	/**
	 * モデルファイルを読み込む
	 * @param string $name モデル名（例: 'user'）
	 * @param string $modelPath モデルファイルの配置場所
	 */
	public function loadModel($name, $modelPath = 'models/')
	{
		// ファイル名を作成（例: models/user_model.php）
		$path = $modelPath . $name . '_model.php';

		if (file_exists($path)) {
			// 二重読み込み防止のため require_once を使用
			require_once $path;

			// クラス名を組み立て（例: user -> User_Model）
			// ucfirst は先頭の文字を大文字にする関数です
			$modelName = ucfirst($name) . '_Model';

			// モデルをインスタンス化して $this->model に格納
			$this->model = new $modelName();
			// 複数モデル対応させるなら
			// $this->models[$name] = new $modelName();
			// 使う時は $this->models['user']->getUserData();
		}
	}
}
