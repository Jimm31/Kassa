<?php

/**
 * Created by PhpStorm.
 * User: Delorian
 * Date: 15.01.2016
 * Time: 21:08
 */
namespace core;

use Twig_Autoloader;
use Twig_Environment;
use Twig_Loader_Filesystem;

class View
{

    private $twig;

    public function __construct()
    {
        Twig_Autoloader::register();
        $loader = new Twig_Loader_Filesystem('app/views');
        $this->twig = new Twig_Environment($loader);
    }

    public function show($template, $data = [])
    {
        $template = $this->twig->loadTemplate($template);
        echo $template->render($data);
    }
}