<?php

require_once __DIR__ . '/../libs/core/Config.php';
require_once CORE . 'Auth.php';

spl_autoload_register(function ($class) {
	static $dirs = array(CORE, COMMON);
	foreach ($dirs as $dir) {
		$path = $dir . $class . '.php';
		if (is_readable($path)) {
			include $path;
		}
	}
});

$bootstrap = new Bootstrap();
$bootstrap->init();
