<?php

define('SYS_NAME', 'template');
define('DOC_ROOT', realpath(dirname(__FILE__)) . '/../../www/');
define('LIBS', '../libs/');
define('CORE', LIBS . 'core/');
define('CONTROLLERS', LIBS . 'controllers/');
define('MODELS', LIBS . 'models/');
define('VIEWS', LIBS . 'views/');
define('COMMON', LIBS . 'common/');
define('MAX_ROW', 15);
define('DB_TYPE', 'mysql');

// サーバー用
// define('URL', 'https://app.koutanu.com/warhammer/www/');
// define('DB_HOST', 'mysql54.conoha.ne.jp');
// define('DB_NAME', '51l20_warhammer_aos');
// define('DB_USER', '51l20_warhammer_aos');
// define('DB_PASS', 'nR8.@UZV');

// ローカル用
// URLの定義を簡潔にします
define('URL', 'http://' . $_SERVER['HTTP_HOST'] . '/');
define('DB_HOST', 'db');
define('DB_NAME', '51l20_warhammer_aos');
define('DB_USER', 'root');
define('DB_PASS', 'root');

// require_once CORE . 'Mysqlview.php';
date_default_timezone_set('Asia/Tokyo');
