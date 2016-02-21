<?php

/**
 * Created by PhpStorm.
 * User: delorian
 * Date: 23.12.15
 * Time: 2:39
 */
namespace models;

use PDO;

class MikrobillWorker
{
    /**
     * @var $dbConnection PDO
     * */
    private $dbConnection;


    /**
     * @param PDO $dbConnection
     */
    function __construct($dbConnection)
    {
        $this->dbConnection = $dbConnection;
    }

    /**
     * @param float $guid Идентификатор пользователя
     * @param string $name Имя кассира в примечании
     * @param float $amount Сумма пополнения
     */
    public function addMoney($guid, $activator, $amount, $comment, $initiator)
    {
        $dateTime = date("Y-m-d H:i:s");
        $uid = $this->generateUID();
        $statement = $this->dbConnection->prepare("INSERT INTO addcash2 VALUES (?,?,0,?,0, ?, ?,?);");
        $statement->execute([$guid, $amount, $activator, $uid, $comment, $dateTime]);
        $this->writeToActionLog('ADD_MONEY', $guid, $amount, $initiator);
        $this->refreshDataBase();
    }

    public function enableService($userLogin, $isEnable, $serviceId, $isForced, $cashierLogin)
    {
        $isForced ? $isForced = 'True' : $isForced = 'False';
        $params = $isEnable . '||' . $serviceId . '||true';
        $statement = $this->dbConnection->prepare("INSERT INTO actions VALUES('ENABLE_SERVICE', ? , ? , ? , ?)");
        $statement->execute([$userLogin, $params, $isForced, $this->generateUID()]);
        $this->writeToActionLog('ENABLE_SERVICE', $userLogin, $params . '||' . $isForced, $cashierLogin);

        $statement = $this->dbConnection->prepare("SELECT otherinfo FROM stat WHERE user_name = ?");
        $statement->execute([$userLogin]);
        $otherInfo = $statement->fetch(PDO::FETCH_ASSOC);
        $otherInfo = explode('||', $otherInfo['otherinfo']);
        $userServices = explode('/*', $otherInfo[12]);
        if ($isEnable) {
            $userServices[] = $serviceId;
        } else {
            if (($i = array_search($serviceId, $userServices)) !== false) {
                unset($userServices[$i]);
            }
        }
    }

    public function editUser($oldLogin, $tariff, $login, $password, $ip, $startDate, $stopDate, $name, $contract, $passport, $addressCon, $phone, $email, $authorization, $mac, $client, $note, $group, $address, $personalCredit, $personalPay, $creditAlgorithm, $payAlgorithm, $interface, $cashierLogin)
    {
        $param = "$login||$password||$ip||$startDate||$stopDate||$name||$contract||$passport||$addressCon||$phone||$email||$authorization||$mac||$client||$note||$group||$address||$personalCredit||$personalPay||$creditAlgorithm||$payAlgorithm||$interface";
        $statement = $this->dbConnection->prepare("INSERT INTO actions VALUES ('SET_TARIF', ?, ?, ?, ?)");
        $statement->execute([$oldLogin, $param, $tariff, $this->generateUID()]);
        $this->writeToActionLog('SET_TARIFF', $oldLogin, $param, $cashierLogin, $tariff);

        $statement = $this->dbConnection->prepare("UPDATE stat SET user_name = ?, user_pswd = ?, tarif = ?, usrip = ? WHERE user_name = ?;");
        $statement->execute([$login, $password, $tariff, $ip, $oldLogin]);

    }

    /**
     * @param $tableName string Имя таблицы с логом платежей
     * @param $id string уникальный идентификатор платежа
     * @return bool
     */
    public function isDuplicatePayment($tableName, $id)
    {
        $statement = $this->dbConnection->prepare("SELECT `id` FROM $tableName WHERE operation_id LIKE ?;");
        $statement->execute([$id]);
        $result = $statement->fetch(PDO::FETCH_ASSOC);
        if ($result === false) {
            return false;
        }
        return true;
    }

    /**
     * @param $login string Логин клиента в личный кабинет
     * @return array|bool [id, login, contract, name, phone, address]
     */
    public function findUserByLogin($login)
    {
        $statement = $this->dbConnection->prepare("SELECT shortguid AS id, pinfo, ballance, user_pay  FROM stat LEFT JOIN tarifs ON stat.tarif_guid = tarifs.tarif_guid WHERE user_name LIKE ?;");
        $statement->execute([$login]);
        $result = $statement->fetch(PDO::FETCH_ASSOC);
        if ($result === false) {
            return false;
        }
        $balance = explode(' ', $result['ballance']);
        $payment = explode(' ', $result['user_pay']);
        $pinfo = explode('||', $result['pinfo']);
        $user = ['id' => $result['id'],
            'login' => $login,
            'contract' => $pinfo[5],
            'name' => $pinfo[3],
            'phone' => $pinfo[0],
            'address' => $pinfo[4],
            'balance' => (float)$balance[0],
            'payment' => (float)$payment[0]
        ];
        return $user;
    }

    public function onUser($user, $cashierLogin)
    {
        $this->onOffUser($user, 0, $cashierLogin);
    }

    public function offUser($user, $cashierLogin)
    {
        $this->onOffUser($user, 1, $cashierLogin);
    }

    public function sendSMS($userLogin, $message, $cashierLogin)
    {
        $statement = $this->dbConnection->prepare("INSERT INTO actions VALUES ('SEND_SMS',?,?,'',?);");
        $statement->execute([$userLogin, $message, $this->generateUID()]);
        $this->writeToActionLog('SEND_SMS', $userLogin, $message, $cashierLogin);
        $this->refreshDataBase();
    }


    public function writeToActionLog($action, $guid, $amount, $initiator, $param3 = '')
    {
        $dateTime = date("Y-m-d H:i:s");
        $statement = $this->dbConnection->prepare("INSERT INTO actionslog VALUES (?,?,?,?,?,?);");
        $statement->execute([$dateTime, $action, $guid, $amount, $param3, $initiator]);
    }


    public function writeToOperationLog($operationLog, $userLogin, $operationId, $amount)
    {
        $dateTime = date("Y-m-d H:i:s");
        $statement = $this->dbConnection->prepare("INSERT INTO $operationLog (`contract`,`sum`,`operation_id`,`date`) VALUES (?,?,?,?);");
        $statement->execute([$userLogin, $amount, $operationId, $dateTime]);
    }

    public function refreshDataBase()
    {
        $this->dbConnection->exec("INSERT INTO workparams VALUES('REFRESH_DB','')");
    }

    private function onOffUser($user, $state, $cashierLogin)
    {
        $statement = $this->dbConnection->prepare("INSERT INTO actions VALUES ('OFF_CLIENT',?,?,'',?);");
        $statement->execute([$user, $state, $this->generateUID()]);
        $this->writeToActionLog('OFF_CLIENT', $user, $state, $cashierLogin);
    }

    private function generateUID()
    {
        return $uid = uniqid("", true) . uniqid("", true);
    }


}