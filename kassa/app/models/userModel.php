<?php
/**
 * Created by PhpStorm.
 * User: Delorian
 * Date: 17.01.2016
 * Time: 22:12
 */

namespace models;


use core\Model;

class UserModel extends Model
{

    function index()
    {
        // TODO: Implement index() method.
    }

    public function sendSMS()
    {
        $userLogin = $_REQUEST['user'];
        $message = $_REQUEST['message'];
        $cashierLogin = $this->cashier->login;
        $mbWorker = new MikrobillWorker($this->connection);
        $mbWorker->sendSMS($userLogin, $message, $cashierLogin);
        $result = ['message' => '<b>Успех!</b><br>Сообщение будет отправлено в течении минуты.', 'error' => 0, 'type' => 'success'];
        return json_encode($result);
    }

    public function showUserInfo()
    {

        $userInfo = $this->getUserInfo($_REQUEST['id']);
        if ($userInfo == false) {
            return false;
        }
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        if (isset($_REQUEST['start']) and isset($_REQUEST['end'])) {
            $start = $_REQUEST['start'];
            $date = $_REQUEST['end'];
            $end = $this->addOneDay($date);
        } else {
            $start = date("Y-m-01");
            $date = $this->addOneDay(date("Y-m-d"));
            $end = $this->addOneDay($date);
        }
        $period = "BETWEEN '$start' AND '$end'";
        $clientID = $userInfo['shortguid'];
        $query = "SELECT moneytime,	value1,	cash, actball, corrected FROM moneys WHERE moneytime $period AND user_name = ? ORDER BY moneytime DESC";
        $moneyInfo = $this->findAll($query, [$clientID]);

        return ['userInfo' => $userInfo, 'moneyInfo' => $moneyInfo, 'start' => $start, 'end' => $end];

    }

    public function getTrafficStat()
    {
        $month = $_REQUEST['month'];
        $date = strtotime(date("Y-$month-1"));
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, date("Y"));
        $query = "SELECT traf_in AS download, traf_out AS upload FROM trafofday WHERE user_name = ? AND day > ? LIMIT $daysInMonth";
        $result = $this->findAll($query, [$_REQUEST['user'], $date]);
        $download = [];
        $upload = [];
        for ($i = 0; $i < count($result); $i++) {
            $download[$i] = [$i + 1, $result[$i]['download']];
            $upload[$i] = [$i + 1, $result[$i]['upload']];
        }
        $d = ['legend' => "&nbsp;Download", 'data' => $download];
        $u = ['legend' => "&nbsp;Upload", 'data' => $upload];
        return json_encode([$d, $u]);

    }

    private function getTraffic($traffic)
    {
        $tmp = explode('/', $traffic);
        $in = number_format((trim($tmp[0]) / 1048576), 2, '.', ' ');
        $out = number_format((trim($tmp[1]) / 1048576), 2, '.', ' ');
        return $in . ' / ' . $out . ' Мб.';
    }

    private function getAllTraffic($traffic)
    {
        $tmp = explode('/', $traffic);
        $in = number_format((trim($tmp[0]) / 1024), 2, '.', ' ');
        $out = number_format((trim($tmp[1]) / 1024), 2, '.', ' ');
        return $in . ' / ' . $out . ' Гб.';
    }


    public function onOff()
    {
        $state = $_REQUEST['on'];
        $user = $_REQUEST['user'];
        $groups = $this->cashier->getAvailableGroups();
        $user = $this->find("SELECT user_name FROM stat WHERE group_guid IN ($groups) AND stat.user_name =?", [$user]);
        if (!$user) {
            return json_encode(['message' => 'У вас нет прав изменять состояние даного пользователя!', 'error' => 0, 'type' => 'error']);
        }
        $mb = new MikrobillWorker($this->connection);
        if ($state == 1) {
            $mb->onUser($user['user_name'], $this->cashier->login);
            return json_encode(['message' => 'Пользователь будет включен в течении минуты!', 'error' => 0, 'type' => 'success']);
        }
        if ($state == 0) {
            $mb->offUser($user['user_name'], $this->cashier->login);
            return json_encode(['message' => 'Пользователь будет отключен в течении минуты!', 'error' => 0, 'type' => 'success']);
        }
        return 'Неверные параметры';

    }

    public function addCash()
    {
        $casierLogin = $this->cashier->login;
        $user = $_REQUEST['user'];
        $amount = (float)$_REQUEST['amount'];
        $comment = $_REQUEST['coment'];
        if (isset($_REQUEST['printreceipt'])) {
            $printreceipt = $_REQUEST['printreceipt'] == 'on' ? 'true' : 'false';
        } else {
            $printreceipt = 'false';
        }
        if (isset($_REQUEST['nosms'])) {
            $nosms = $_REQUEST['nosms'] == 'on' ? 'true' : 'false';
        } else {
            $nosms = 'false';
        }
        if (isset($_REQUEST['zerobalance'])) {
            $zerobalance = $_REQUEST['zerobalance'] == 'on' ? 'true' : 'false';
        } else {
            $zerobalance = 'false';
        }
        if (isset($_REQUEST['startonfirst'])) {
            $startonfirst = $_REQUEST['startonfirst'] == 'on' ? 'true' : 'false';
        } else {
            $startonfirst = 'false';
        }

        $groups = $this->cashier->getAvailableGroups();
        $row = $this->find("SELECT shortguid FROM stat WHERE user_name = ? and group_guid IN ($groups)", [$user]);
        if (!$row) {
            return json_encode(['message' => 'У вас нет прав для пополнения этого пользователя!', 'error' => 0, 'type' => 'error']);
        }
        $guid = $row['shortguid'];
        $activator = "chaiser=$casierLogin||$zerobalance||$startonfirst||$guid||$printreceipt||$nosms";
        $mb = new MikrobillWorker($this->connection);
        $mb->addMoney($guid, $activator, $amount, $comment, $casierLogin);
        return json_encode(['message' => '<b>Успех!</b><br>Деньги поступят на счет в течении минуты!', 'error' => 0, 'type' => 'success']);
    }

    public function getUserServices()
    {
        $otherInfo = $this->find("SELECT otherinfo FROM stat WHERE user_name = ?", [$_REQUEST['user']]);
        $services = $this->findAll("SELECT service_name, guid, paysize, payafterday, period, one_time_service FROM services", []);
        $userServices = explode('||', $otherInfo['otherinfo']);
        $userServices = explode('/*', $userServices[12]);
        foreach ($userServices as $userService) {
            $a = explode('|', $userService);
            $userService = $a[0];
            for ($i = 0; $i < count($services); $i++) {
                if ($services[$i]['guid'] === $userService) {
                    $services[$i]['checked'] = 'checked';
                    break;
                }
            }
        }
        return $services;
    }

    public function setUserServices()
    {
        $service = $_REQUEST['service'];
        $userLogin = $_REQUEST['user'];
        $isEnable = $_REQUEST['enable'];

        if ($service == '' or $userLogin == '' or $isEnable == '') {
            return json_encode(['message' => 'Неверный формат запроса!', 'error' => 0, 'type' => 'error']);
        }
        $isEnable == 'true' ? $state = 'включена' : $state = 'отключена';
        $mb = new MikrobillWorker($this->connection);
        $mb->enableService($userLogin, $isEnable, $service, 'True', $this->cashier->login);
        return json_encode(['message' => 'Услуга будет ' . $state . ' в течении минуты!', 'error' => 0, 'type' => 'success']);
    }

    public function getUserInfo($id)
    {
        $groups = $this->cashier->getAvailableGroups();
        $query = "SELECT
	                user_name AS `login`,
	                user_pswd AS `password`,
	                FIO AS `fio`,
	                group_name AS `group`,
	                group_guid AS `groupId`,
	                otherinfo AS `adress`,
	                usrip AS `ip`,
	                ballance AS `ballance`,
	                data1 AS `startDate`,
	                data2 AS `stopDate`,
	                tarif AS `tarif`,
	                tarif_guid AS `tariffId`,
	                paysize AS `paySize`,
	                promisepayenabled AS `promisePay`,
	                spdlim AS `speedLimit`,
	                curspd AS `currentSpeed`,
	                todaytraffic AS `trafficToday`,
	                monthtraffic AS `trafficMonth`,
	                SUBSTR(traffic, 1, LENGTH(traffic)-6) AS `allTraffic`,
	                turboenabled AS `turbo`,
	                state AS `isEnabled`,
	                pinfo,
					shortguid
                FROM
	                stat
	            WHERE
	                shortguid = ?
					AND group_guid IN($groups)";
        $userInfo = $this->find($query, [$id]);
        if ($userInfo == false) {
            return false;
        }
        $otherInfo = explode('||', $userInfo['adress']);
        $personalInfo = explode('||', $userInfo['pinfo']);
        if ($userInfo['isEnabled'] == 'Да') {
            if ($otherInfo[21] == 'True') {
                $userInfo['state'] = 'success';
            } else {
                $userInfo['state'] = 'danger';
            }
        } else {
            $userInfo['state'] = 'default';
        }
        $userInfo['ip'] = trim($userInfo['ip'], ';');
        $userInfo['address'] = $otherInfo[2];
        $userInfo['promisePay'] = $userInfo['promisePay'] == 'True' ? 'Включен' : 'Выключен';
        $userInfo['turbo'] = $userInfo['turbo'] == 'True' ? 'Включен' : 'Выключен';
        $userInfo['trafficToday'] = $this->getTraffic($userInfo['trafficToday']);
        $userInfo['trafficMonth'] = $this->getTraffic($userInfo['trafficMonth']);
        $userInfo['allTraffic'] = $this->getAllTraffic($userInfo['allTraffic']);
        $userInfo['personalPayment'] = $otherInfo[20];
        $userInfo['contract'] = $otherInfo[0];
        $userInfo['passport'] = $otherInfo[1];
        $userInfo['phone'] = $otherInfo[3];
        $userInfo['email'] = $otherInfo[4];
        $userInfo['authorization'] = $otherInfo[5];
        $userInfo['mac'] = $otherInfo[6];
        $userInfo['client'] = $otherInfo[8];
        $userInfo['note'] = $otherInfo[9];
        $userInfo['addressCon'] = $personalInfo[6];
        $userInfo['personalCredit'] = $otherInfo[34];
        $userInfo['creditAlgorithm'] = $otherInfo[27];
        $userInfo['personalPay'] = $otherInfo[20];
        $userInfo['payAlgorithm'] = $otherInfo[28];
        $userInfo['interface'] = $otherInfo[37];
        $userInfo['stopDate'] = $otherInfo[43];
        if (strtolower($userInfo['authorization']) === 'any')
            $userInfo['authorization'] = 'PPP Any';
        return $userInfo;
    }

    public function getEditInfo($id)
    {
        $user = $this->getUserInfo($id);
        $availableGroups = $this->cashier->getAvailableGroups();
        $availableTariffs = $this->cashier->getAvailableTariffs();
        $groups = $this->findAll("SELECT group_name AS `name`, group_guid AS `id` FROM groups WHERE group_guid IN ($availableGroups) OR group_guid = ?", [$user['groupId']]);
        $tariffs = $this->findAll("SELECT tarif_name AS `name`, tarif_guid AS `id` FROM tarifs WHERE tarif_guid IN ($availableTariffs) OR tarif_guid = ?", [$user['tariffId']]);
        return ['user' => $user, 'groups' => $groups, 'tariffs' => $tariffs];
    }

    public function saveUser()
    {
        if (!isset($_REQUEST['client']) or strlen($_REQUEST['client']) < 2) {
            return json_encode(['message' => 'Непрвильно заполнено поле Клиент!', 'error' => 0, 'type' => 'danger']);
        }

        $availableGroups = $this->cashier->getAvailableGroups();
        $oldUser = $this->find("SELECT user_name, group_guid, tarif_guid FROM stat WHERE user_name = ? AND  group_guid IN($availableGroups)", [$_REQUEST['oldLogin']]);
        if (!$oldUser) {
            return json_encode(['message' => 'У вас нет прав редактировать клиента <b>' . $_REQUEST['login'] . '</b>!', 'error' => 0, 'type' => 'danger']);
        }
        if (array_search($_REQUEST['tariff'], $this->cashier->tariffs) === false and $_REQUEST['tariff'] !== $oldUser['tarif_guid'] ) {
            return json_encode(['message' => 'У вас нет доступа к данному таифу!', 'error' => 0, 'type' => 'danger']);
        }

        if (array_search($_REQUEST['group'], $this->cashier->groups) === false and $_REQUEST['group'] !== $oldUser['group_guid']) {
            return json_encode(['message' => 'У вас нет доступа к данной группе!', 'error' => 0, 'type' => 'danger']);
        }

        $oldLogin = $_REQUEST['oldLogin'];
        $client = $_REQUEST['client'];
        $contract = $_REQUEST['contract'];
        $login = $_REQUEST['login'];
        $password = $_REQUEST['password'];
        $group = $this->find("SELECT group_name FROM groups WHERE group_guid = ?",[$_REQUEST['group']]);
        $group = $group['group_name'];
        $tariff = $this->find("SELECT tarif_name FROM tarifs WHERE tarif_guid = ?",[$_REQUEST['tariff']]);
        $tariff = $tariff['tarif_name'];
        $authorization = $_REQUEST['authorization'];
        $interface = $_REQUEST['interface'];
        $ip = $_REQUEST['ip'];
        $mac = $_REQUEST['mac'];
        $startDate = $this->convertToDottedDate($_REQUEST['startDate']);
        $stopDate = $this->convertToDottedDate($_REQUEST['stopDate']);
        $name = $_REQUEST['name'];
        $passport = $_REQUEST['passport'];
        $phone = $_REQUEST['phone'];
        $note = $_REQUEST['note'];
        $addressCon = $_REQUEST['addressCon'];
        $address = $_REQUEST['address'];
        $email = $_REQUEST['email'];
        $personalPay = $_REQUEST['personalPay'];
        $payAlgorithm = $_REQUEST['payAlgorithm'];
        $personalCredit = $_REQUEST['personalCredit'];
        $creditAlgorithm = $_REQUEST['creditAlgorithm'];
        $mb = new MikrobillWorker($this->connection);
        $mb->editUser($oldLogin,$tariff,$login,$password,$ip,$startDate,$stopDate,$name,$contract,
            $passport,$addressCon,$phone,$email,$authorization,$mac,$client,$group,$note,$address,$personalCredit,$personalPay,$creditAlgorithm,$payAlgorithm,$interface, $this->cashier->login);

        return json_encode(['message' => 'Изменения вступят в силу в течении минуты!', 'error' => 0, 'type' => 'success']);
    }

    public function getLastFreeIp()
    {
        if(!isset($_REQUEST['group']) or array_search($_REQUEST['group'], $this->cashier->groups) === false){
            return '0.0.0.0';
        }
        $ip = $this->find("SELECT first_free_ip AS `ip` FROM groups WHERE group_guid = ?", [$_REQUEST['group']]);
        return json_encode($ip);

    }
}