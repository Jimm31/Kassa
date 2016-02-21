<?php
/**
 * Created by PhpStorm.
 * User: Delorian
 * Date: 16.01.2016
 * Time: 16:04
 */

namespace controllers;


use core\Controller;
use models\LoginModel;

class LoginController extends Controller
{

    function index_action()
    {

    }

    function login_action()
    {
        if (isset($_REQUEST['login'])) {
            $name = $_REQUEST['login'];
            $password = $_REQUEST['password'];
            $model = new\models\LoginModel();
            if ($model->login($name, $password) == true) {
                header("Location: /kassa");
            } else {
                header("Location: /kassa/login/login/error/1");
            }
        }
        isset($_REQUEST['error']) ? $error = true : $error = false;
        $this->view->show('login.twig', ['error' => $error, 'title' => 'Авторизация']);
    }

    function logout_action()
    {
        $m = new LoginModel();
        $m->logout();
        header("Location: /kassa");
    }
}