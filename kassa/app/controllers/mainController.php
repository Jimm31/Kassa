<?php
/**
 * Created by PhpStorm.
 * User: Delorian
 * Date: 16.01.2016
 * Time: 14:16
 */

namespace controllers;

use core\Controller;
use models\MainModel;

class MainController extends Controller
{
    function index_action()
    {
        $model = new MainModel();
        $data = $model->index();
        $this->view->show('main.twig', ['title' => 'Касса', 'data' => $data]);
    }


    function getUsers_action()
    {
        $model = new MainModel();
        echo $model->getUsers();
    }
}