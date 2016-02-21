<?php

spl_autoload_register(function ($className) {
    if ($className == 'Twig_Autoloader') {
        require_once __DIR__ . '\libs\Twig\Autoloader.php';
    }
    $ds = DIRECTORY_SEPARATOR;
    $dir = __DIR__;
    $className = str_replace('\\', $ds, $className);
    $file = "{$dir}{$ds}{$className}.php";
    if (is_readable($file)) require_once $file;
    //else echo $file . '<br>';
});

use core\Router;

/*
Здесь обычно подключаются дополнительные модули, реализующие различный функционал:
	> аутентификацию
	> кеширование
	> работу с формами
	> абстракции для доступа к данным
	> ORM
	> Unit тестирование
	> Benchmarking
	> Работу с изображениями
	> Backup
	> и др.
*/

Router::start(); // запускаем маршрутизатор
