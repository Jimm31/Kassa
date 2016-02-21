<?php
/**
 * Created by PhpStorm.
 * User: Delorian
 * Date: 16.01.2016
 * Time: 16:11
 */
namespace models;

use core\Model;
use core\Session;

class LoginModel extends Model
{
    function index()
    {
    }

    function login($user, $password)
    {
        include_once('C:\Users\Delorian\PhpstormProjects\Kassa\config.php');
        /** @var array $chaisers Массив кассиров */
        $id = null;
        for ($i = 0; $i < count($chaisers['login']); $i++) {
            if ($chaisers['login'][$i] === $user and $chaisers['password'][$i] === $password) {
                $id = $i;
                break;
            }
        }
        if ($id === null) {
            Session::set('isAuthorize', false);
            return false;
        }

        $user = [];
        $user['guid'] = $chaisers['guid'][$id];
        $user['login'] = $chaisers['login'][$id];
        $user['password'] = $chaisers['password'][$id];
        $user['fio'] = $chaisers['fio'][$id];
        $user['permissions'] = $chaisers['permissions'][$id];
        $user['groups'] = $chaisers['groups'][$id];
        /** @var array $InterfacesList */
        $user['interfacesList'] = $InterfacesList;
        $cashier = new Cashier($user);
        Session::set('cashier', $cashier);
        Session::set('isAuthorize', true);
        return true;
    }

    function logout()
    {
        Session::destroy();
    }
}