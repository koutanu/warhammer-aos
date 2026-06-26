<?php

class View
{
	// 全画面共通で使用する基本的な情報のみ保持
	// ※これらは require された HTML 内で $this->title のように呼び出せます
	public $class;
	public $method;
	public $title;
	public $j_class;

	function __construct() {}

	/**
	 * 画面を組み立てて表示（レンダリング）する
	 * * @param string $class  フォルダ名
	 * @param string $method ファイル名
	 * @param string $title  ページタイトル
	 * @param array  $vars   HTMLに渡したいデータの連想配列
	 */
	public function render($class, $method, $title, $vars = [])
	{
		// 1. 基本情報をプロパティに保存
		$this->class  = $class;
		$this->method = $method;
		$this->title  = $title;

		// 2. データを変数として展開
		// これにより、['token' => 'abc'] は HTML内で $token として使えるようになります
		extract($vars);

		// 3. ブラウザキャッシュの設定（'refer' 画面のみ）
		if ($method == 'refer') {
			$limit = 30;
			$expires = gmdate("D, d M Y H:i:s", time() + $limit) . " GMT";
			header("Expires: $expires");
			header("Pragma: cache");
			header("Cache-Control: max-age=$limit");
		}

		// // 4. ヘッダー・フッターの出し分け判定
		// $isLogin = ($class === 'login');
		// $headerFile = $isLogin ? 'header_omit_wrapper.php' : 'header.php';
		// $footerFile = $isLogin ? 'footer_omit_wrapper.php' : 'footer.php';

		// hide_nav: 対戦中はサイドナビを非表示
		if (!isset($hide_nav)) {
			$hide_nav = false;
		}

		// 5. 各パーツの読み込み（順番が重要です）
		require VIEWS . 'head.php';
		if ($hide_nav) {
			require VIEWS . 'header_match.php';
		} else {
			require VIEWS . 'header.php';
		}

		// メインコンテンツ
		if ($class === 'failure') {
			require VIEWS . 'failure/index.php';
		} else {
			require VIEWS . $class . '/' . $method . '.php';
		}

		require VIEWS . 'footer.php';
	}

	/**
	 * エラー画面専用の表示処理
	 */
	public function failure($class, $title, $name = '')
	{
		$this->class   = $class;
		$this->title   = $title;
		$this->j_class = $name;

		require VIEWS . 'head.php';
		require VIEWS . 'header.php';
		require VIEWS . 'failure/index.php';
		require VIEWS . 'footer.php';
	}

	public function h($string)
	{
		return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
	}
}
