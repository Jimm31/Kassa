<?php
/**
 * Created by PhpStorm.
 * User: Delorian
 * Date: 17.01.2016
 * Time: 22:08
 */

namespace controllers;


use core\Controller;
use models\UserModel;

class UserController extends Controller
{
    function addCash_action()
    {
        if (!$this->cashier->canAddMoney()) {
            echo '<h4 style="color: #FFF;">У вас нет прав пополнять счет!<h4>'; //TODO CСТраница с ошибками
        } else {
            if (!isset($_REQUEST['user']) or !isset($_REQUEST['amount'])) {
                $userLogin = $_REQUEST['user'];
                $this->view->show('user_addCash', ['user' => $userLogin]);
            } else {
                header('Content-Type: application/json');
                $userModel = new UserModel();
                echo $userModel->addCash();
            }
        }
    }

    function index_action()
    {
        // TODO: Implement index_action() method.
    }

    function save_action()
    {
        if (!$this->cashier->canEditUsers()) {
            echo 'У вас нет прав редактировать абонентов!';
            exit;
        }
        header('Content-Type: application/json');
        $m = new UserModel();
        echo $m->saveUser();
    }

    function edit_action()
    {
        if (!$this->cashier->canEditUsers()) {
            echo 'У вас нету прав редактировать или просматривать личную информацию!';
            exit;
        }

        if (isset($_REQUEST['id'])) {
            $authorizations = ['IP + MAC', 'HotSpot', 'PPP Any', 'Async', 'L2TP', 'OVPN', 'PPPoE', 'PPTP', 'SSTP'];
            $m = new UserModel();
            $info = $m->getEditInfo($_REQUEST['id']);
            $interfacesList = $this->cashier->interfacesList;
            $this->view->show('user_edit.twig', ['title' => 'Редактирование :: ' . $info['user']['login'],
                'user' => $info['user'],
                'groups' => $info['groups'],
                'tariffs' => $info['tariffs'],
                'authorizations' => $authorizations,
                'interfacesList' => $interfacesList

            ]);
        }
    }

    function trafficStat_action()
    {
        $model = new UserModel();
        header('Content-Type: application/json');
        echo $model->getTrafficStat();

    }

    function info_action()
    {
        if (!isset($_REQUEST['id'])) {
            echo 'Не выбран ни один пользователь';//todo Страница с ошибками
            exit;
        }

        $model = new UserModel();
        if (!$data = $model->showUserInfo()) {
            echo 'у вас нет доступа к даному пользователю';
        } else {
            $month = date("n");
            $this->view->show('user_info.twig', ['title' => 'Инфо :: ' . $data['userInfo']['login'], //todo Переделать одним массивом
                'userInfo' => $data['userInfo'],
                'moneyInfo' => $data['moneyInfo'],
                'start' => $data['start'],
                'end' => $data['end'],
                'cashier' => $this->cashier,
                'month' => $month
            ]);
        }

    }

    function onOff_action()
    {
        if (!isset($_REQUEST['user']) or !isset($_REQUEST['on'])) {
            echo "Не выбрано ни одного пользователя"; // TODO Сделать отображение ошибок
            exit;
        }
        if ($this->cashier->canOnOffUsers()) {
            header('Content-Type: application/json');
            $model = new UserModel();
            echo $model->onOff();
        } else {
            echo "У вас нет прав Включать/Выключать пользователей."; //TODO Сделать отображение ошибок;
        }
    }

    function sendMessage_action()
    {
        if (!$this->cashier->canSendMessages()) {
            echo '<h4 style="color: #FFF;">У вас нет прав оправлять сообщения!<h4>'; //TODO CСТраница с ошибками
        } else {
            if (!isset($_REQUEST['user']) or !isset($_REQUEST['message'])) {
                $userLogin = $_REQUEST['user'];
                $this->view->show('user_sendMessage', ['user' => $userLogin]);
            } else {
                header('Content-Type: application/json');
                $userModel = new UserModel();
                echo $userModel->sendSMS();
            }
        }
    }

    function services_action()
    {
        if (!$this->cashier->canManageServices()) {
            echo '<h4 style="color: #FFF;">У вас нет прав подключать или отключать услуги!<h4>'; //TODO CСТраница с ошибками
            exit;
        }
        if (isset($_REQUEST['user']) and !isset($_REQUEST['service'])) {
            $userLogin = $_REQUEST['user'];
            $m = new UserModel();
            $services = $m->getUserServices();
            $this->view->show('user_services', ['user' => $userLogin, 'services' => $services]);

        } else {
            header('Content-Type: application/json');
            $userModel = new UserModel();
            echo $userModel->setUserServices();
        }
    }

    function getIp_action()
    {
        header('Content-Type: application/json');
        if (!$this->cashier->canEditUsers()) {
            echo '0.0.0.0';
            exit;
        }

        $m = new UserModel();
        echo $m->getLastFreeIp();
    }

    function balance_action()
    {
        if (!$this->cashier->canSeeStatistics()) {
            echo '0000';
            exit;
        }
        if(isset($_REQUEST['id'])){
            $m=new UserModel();
            $user  = $m->getUserInfo($_REQUEST['id']);
        echo $user['ballance'];
        }

    }
}