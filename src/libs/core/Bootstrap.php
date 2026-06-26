<?php

class Bootstrap
{
	private $_url = null;
	private $_controller = null;
	private $_controllerPath = CONTROLLERS;
	private $_modelPath = MODELS;
	private $_errorFile = 'failure.php';
	private $_defaultFile = 'login.php';

	/**
	 * メイン処理の開始
	 * URLを解析し、適切なコントローラーを起動します
	 */
	public function init()
	{
		// URLを取得して配列に分解
		$this->_getUrl();

		// URLが空（トップページへのアクセスなど）の場合、デフォルトの画面を表示
		if (empty($this->_url[0])) {
			$this->_loadDefaultController();
			return false;
		}

		// 指定されたコントローラーを読み込み、メソッドを実行
		$this->_loadExistingController();
		$this->_callControllerMethod();
	}

	// --- 設定変更用メソッド（必要に応じて外部からパスを変更可能） ---

	public function setControllerPath($path)
	{
		$this->_controllerPath = trim($path, '/') . '/';
	}
	public function setModelPath($path)
	{
		$this->_modelPath = trim($path, '/') . '/';
	}
	public function setErrorFile($path)
	{
		$this->_errorFile = trim($path, '/');
	}
	public function setDefaultFile($path)
	{
		$this->_defaultFile = trim($path, '/');
	}

	/**
	 * URLを取得・整形する
	 * 例: example.com/user/edit/1 -> ['user', 'edit', '1']
	 */
	private function _getUrl()
	{
		// GETパラメータから 'url' を安全に取得
		$url = filter_input(INPUT_GET, 'url', FILTER_SANITIZE_URL);
		// 末尾のスラッシュを削除し、配列に分割
		$url = rtrim($url ?? '', '/');
		$this->_url = explode('/', $url);
	}

	/**
	 * URL指定がない場合に呼ばれるデフォルト処理（ログイン画面など）
	 */
	private function _loadDefaultController()
	{
		require_once $this->_controllerPath . $this->_defaultFile;
		$this->_controller = new Login();
		$this->_controller->index();
	}

	/**
	 * URLの1番目の要素を元にコントローラー・ファイルを読み込む
	 */
	private function _loadExistingController()
	{
		$file = $this->_controllerPath . $this->_url[0] . '.php';

		if (file_exists($file)) {
			include $file;
			// PHP 8+ 予約語対策: URLセグメントとクラス名のマッピング
			$classMap = ['match' => 'Matchplay'];
			$className = $classMap[$this->_url[0]] ?? $this->_url[0];
			$this->_controller = new $className;
			// 対応するモデルがあれば読み込む
			$this->_controller->loadModel($this->_url[0], $this->_modelPath);
		} else {
			// ファイルがなければエラー画面へ
			$this->_error();
			return false;
		}
	}

	/**
	 * URLの2番目以降の要素を元に、メソッドと引数を実行する
	 * 例: /user/edit/1/name -> $user->edit('1', 'name') を実行
	 */
	private function _callControllerMethod()
	{
		$length = count($this->_url);

		// メソッド名（url[1]）が指定されている場合
		if ($length > 1) {
			// メソッドがクラス内に存在するかチェック
			if (!method_exists($this->_controller, $this->_url[1])) {
				$this->_error();
				return;
			}

			// 引数（url[2]以降）を切り出し、可変引数として実行
			$params = array_slice($this->_url, 2);
			$this->_controller->{$this->_url[1]}(...$params);
		} else {
			// メソッド指定がない場合は、標準の index メソッドを実行
			$this->_controller->index();
		}
	}

	/**
	 * エラーページを表示して処理を終了する
	 */
	private function _error()
	{
		require_once $this->_controllerPath . $this->_errorFile;
		$this->_controller->index('noclass');
		exit;
	}
}
