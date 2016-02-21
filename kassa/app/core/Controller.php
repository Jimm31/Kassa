<?php
/**
 * Created by PhpStorm.
 * User: Delorian
 * Date: 15.01.2016
 * Time: 19:42
 */
namespace core;


use models\Cashier;

abstract class Controller
{
    protected $view;
    /**
     * @var Cashier $cashier Кассир из сессии
     */
    protected $cashier;

    /**
     * Controller constructor.
     */
    public function __construct()
    {
        $this->view = new View();
        @$this->cashier = Session::get('cashier');
    }

    abstract function index_action();


}