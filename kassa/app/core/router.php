<?php
/**
 * Created by PhpStorm.
 * User: Delorian
 * Date: 16.01.2016
 * Time: 13:15
 */
namespace core;

use Exception;

class Router
{
    public static function start()
    {
        try {
            $url_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            $uri_parts = explode('/', trim($url_path, ' /'));
            $module = array_shift($uri_parts);
            empty($uri_parts[0]) ? $controllerName = 'Main' : $controllerName = $uri_parts[0];
            empty($uri_parts[1]) ? $action = 'index' : $action = $uri_parts[1];
            if (count($uri_parts) % 2) {
                throw new Exception('Неверное количество параметров запроса');
            }
            $params = null;
            for ($i = 2; $i < count($uri_parts); $i++) {
                $params[$uri_parts[$i]] = $uri_parts[++$i];
            }
            if ($params !== null) {
                $_REQUEST = array_merge($_REQUEST, $params);
            }
            Session::start();
            if (!array_key_exists('isAuthorize', $_SESSION)) {
                Session::set('isAuthorize', false);
            }

            if (!Session::get('isAuthorize')) {
                $controllerName = 'Login';
                $action = 'login';
                $_SERVER['REQUEST_URI'] = '/kassa/login';
            }
            $controllerName = 'controllers\\' . $controllerName . 'Controller';
            $action = $action . '_action';

            if (!class_exists($controllerName)) {
                throw new Exception('Запрашеваемой страницы не существует: ' . $controllerName);
            }
            $controller = new $controllerName;
            if (!method_exists($controller, $action)) {
                throw new Exception('Указаного действия не существует:' . $action);
            }
            $controller->$action();
        } catch (Exception $e) {
            echo $e->getMessage();
        }

    }

}

//TODO Дописать работу с ошибками и несуществующими страницами