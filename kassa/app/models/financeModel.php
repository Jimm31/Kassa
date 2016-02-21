<?php
/**
 * Created by PhpStorm.
 * User: Delorian
 * Date: 09.02.2016
 * Time: 0:46
 */

namespace models;


use core\Model;

class FinanceModel extends Model
{
    private $smsCost = 0.24;

    function index()
    {
        $startDate = date("01.m.Y");
        $endDate = (date("d.m.Y"));
        return ['start' => $startDate, 'end' => $endDate];
    }

    function getSummary()
    {
        $result = $this->findAll("SELECT SUM(cash) AS sum, MONTH(moneytime)-1 AS month FROM moneys LEFT JOIN stat ON moneys.user_name=stat.shortguid WHERE actionid IN (0, 1, 2, 3, 99) AND stat.group_guid IN(" . $this->cashier->getAvailableGroups() . ") GROUP BY MONTH(moneytime) ORDER BY moneytime", []);
        $tmp = [];
        foreach ($result as $row) {
            $tmp[] = [$row['month'], $row['sum']];
        }
        return json_encode($tmp);
    }

    function getAvailiableGroups()
    {
        $groups = $this->findAll("SELECT group_name AS `name`, group_guid AS `id` FROM groups WHERE group_guid IN (" . $this->cashier->getAvailableGroups() . ")", []);
        return json_encode($groups);
    }

    function getPayersStat()
    {
        $groups = $this->getGroups();
        $startDate = $this->getStartdate();
        $endDate = $this->getEndDate();

        $payers = $this->findAll("SELECT payer AS name, SUM(sum) AS `sum` FROM (SELECT cash AS 'sum', IF (value1 LIKE 'Карта%', 'Карта пополнения', value1) AS `payer`, user_name AS 'id' FROM moneys WHERE (moneys.moneytime BETWEEN ? AND ?) AND (actionid IN(99, 1, 2, 3, 0))) AS `M` LEFT JOIN stat ON `M`.id = stat.shortguid WHERE	stat.group_guid IN ($groups) GROUP BY payer", [$startDate, $endDate]);
        $total = 0;
        foreach ($payers as $payer) {
            $total += $payer['sum'];
        }
        return ['payers' => $payers, 'total' => $total];
    }

    public function getSummarySms()
    {
        $groups = $this->getGroups();
        $startDate = $this->getStartdate();
        $endDate = $this->getEndDate();

        $messages = $this->findAll("SELECT stat.group_name AS `group`,	SUM(CEILING(LENGTH(smsTEXT) / 160)) AS `parts`,	COUNT(smsTEXT) AS `sms` FROM sms LEFT JOIN stat ON sms.user_guid = stat.shortguid WHERE sms.smstime BETWEEN ? AND ? AND sms.srvret LIKE '%=%' AND stat.group_guid IN($groups) GROUP BY group_name", [$startDate, $endDate]);
        $total = 0;
        for ($i = 0; $i < count($messages); $i++) {
            $messages[$i]['sum'] = $messages[$i]['parts'] * $this->smsCost;
            $total += $messages[$i]['sum'];
        }

        return ['messages' => $messages, 'total' => $total];
    }

    public function getPayersStatInCSV()
    {
        $payers = $this->getPayersStat();
        $columnTitle = ['name' => 'Примечание', 'sum' => 'Сумма'];
        array_unshift($payers['payers'], $columnTitle);
        $this->saveToCSV($payers['payers'], 'Fin report');
    }

    public function getMessagesStatInCSV()
    {
        $messages = $this->getSummarySms();
        $columnTitle = ['group' => 'Группа', 'parts' => 'Частей', 'sms' => 'СМС', 'sum' => 'Сумма'];
        array_unshift($messages['messages'], $columnTitle);
        $this->saveToCSV($messages['messages'], 'SMS отчет');
    }

    private function saveToCSV($array, $fileName)
    {
        header("Content-type: text/csv");
        header("Content-Disposition: attachment; filename=$fileName.csv");
        $fp = fopen("php://output", 'w');
        fwrite($fp, b"\xEF\xBB\xBF");
        foreach ($array as $line) {
            fputcsv($fp, $line, ';');
        }
        fclose($fp);
        exit;
    }

    public function getPayerInfo()
    {
        $groups = $this->getGroups();
        $startDate = $this->getStartdate();
        $endDate = $this->getEndDate();
        if (isset($_REQUEST['payer'])) {
            urldecode($_REQUEST['payer']) == 'Карта пополнения' ? $payer = "actionid = 2" : $payer = "value1 = " . $this->quote(urldecode($_REQUEST['payer'])) . "";
        } else
            $payer = '';
        $payments = $this->findAll("SELECT stat.group_name AS 'userGroup', stat.FIO AS `userName`, M.client_login AS 'userLogin', M.cash AS 'sum', M.value1 AS 'payer', M.moneytime AS 'datetime' FROM (  SELECT   moneys.client_login,   moneys.cash,   moneys.value1,   moneys.moneytime,   moneys.cash_name,   moneys.user_name  FROM   moneys  WHERE   moneys.moneytime BETWEEN ? AND ? AND ($payer) ORDER BY   moneys.moneytime DESC ) AS `M`LEFT JOIN stat ON M.user_name = stat.shortguid WHERE stat.group_guid IN ($groups)", [$startDate, $endDate]);
        $total = 0;
        foreach ($payments as $payment) {
            $total += $payment['sum'];
        }
        return ['payments' => $payments, 'total' => $total];
    }

    /**
     * @return string
     */
    private function getGroups()
    {
        if (!isset($_REQUEST['groups']) or $_REQUEST['groups'] == 'all') {
            $groups = $this->cashier->getAvailableGroups();
            return $groups;
        } else {
            $groups = $_REQUEST['groups'];
            return $groups;
        }
    }

    /**
     * @return bool|string
     */
    private function getStartdate()
    {
        if (isset($_REQUEST['startDate']) and isset($_REQUEST['endDate'])) {
            $startDate = $this->convertToDashedDate($_REQUEST['startDate']);
            return $startDate;

        } else {
            $startDate = date("Y-m-01");
            return $startDate;

        }
    }

    /**
     * @return bool|string
     */
    private function getEndDate()
    {
        if (isset($_REQUEST['endDate'])) {
            $endDate = $this->addOneDay($this->convertToDashedDate($_REQUEST['endDate']));
        } else {
            $endDate = $this->addOneDay(date("Y-m-d"));
        }
        return $endDate;
    }


}