<!DOCTYPE html>
<html lang="ja">

<head>
	<meta charset="UTF-8">
	<title><?= (isset($title)) ? $title : 'Home' ?></title>

	<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">

	<!-- PWA -->
	<link rel="manifest" href="<?= $this->h(URL); ?>manifest.json">
	<meta name="theme-color" content="#131c1c">
	<meta name="mobile-web-app-capable" content="yes">

	<!-- iOS（ホーム画面追加・スタンドアロン表示） -->
	<meta name="apple-mobile-web-app-capable" content="yes">
	<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
	<meta name="apple-mobile-web-app-title" content="AoS Assistant">

	<!-- アイコン -->
	<link rel="icon" href="<?= $this->h(URL); ?>assets/icons/icon-192.png" type="image/png" sizes="192x192">
	<link rel="apple-touch-icon" href="<?= $this->h(URL); ?>assets/icons/apple-touch-icon.png" sizes="180x180">

	<!-- キャッシュとクローラー拒否 -->
	<meta http-equiv="Pragma" content="no-cache">
	<meta http-equiv="Cache-Control" content="no-cache">
	<meta name="robots" content="noindex" />

	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.11.2/css/all.css">
	<link rel="stylesheet" href="<?= $this->h(URL); ?>css/style.css">

	<?php if (isset($css)) : ?>
		<?php foreach ($css as $css) : ?>
			<link rel="stylesheet" href="<?= $this->h(URL); ?>css/<?= $css; ?>">
		<?php endforeach; ?>
	<?php endif; ?>
</head>

<body>