<?php

/**
 * Created by PhpStorm.
 * User: Delorian
 * Date: 15.01.2016
 * Time: 19:52
 */
namespace core;

use models\Cashier;
use PDO;
use PDOException;

abstract class Model
{
    /**
     * @var PDO $connection параметры текущего кассира
     */
    protected $connection;
    /**
     * @var Cashier $cashier параметры текущего кассира
     */
    protected $cashier;

    public function __construct()
    {
        require_once 'config.php';
        try {
            /** @var mixed $db2 Data Base settings from config.php */
            $this->connection = new PDO("mysql:host=" . $db2['host'] . ";dbname=" . $db2['name'], $db2['user'], $db2['password']);
            $this->connection->exec("SET NAMES utf8");
        } catch (PDOException $e) {
            echo $e->getMessage();
        }
        $this->cashier = Session::get('cashier');
    }

    abstract function index();

    public function find($query, $params, $fetchMode = PDO::FETCH_ASSOC)
    {
        $statement = $this->connection->prepare($query);
        $statement->execute($params);
        $result = $statement->fetch($fetchMode);
        return $result;
    }

    public function findAll($query, $params, $fetchMode = PDO::FETCH_ASSOC)
    {
        $statement = $this->connection->prepare($query);
        $statement->execute($params);
        $result = $statement->fetchAll($fetchMode);
        return $result;
    }

    public function quote($string){
        return $this->connection->quote($string);
    }

    public function execute($query, $params)
    {
        $statement = $this->connection->prepare($query);
        return $statement->execute($params);
    }

    protected function addOneDay($date)
    {
        $date1 = str_replace('-', '/', $date);
        $nextDay = date("Y-m-d", strtotime($date1 . "+1 days"));
        return $nextDay;
    }

    protected function removeOneDay($date)
    {
        $date1 = str_replace('-', '/', $date);
        $prevDay = date("Y-m-d", strtotime($date1 . "-1 days"));
        return $prevDay;
    }

    protected function convertToDashedDate($date){
        if($date=='') return $date;
        $tmp = explode('.', $date);
        return $tmp[2].'-'.$tmp[1].'-'.$tmp[0];
    }

    protected function convertToDottedDate($date){
        if($date=='') return $date;
        $tmp = explode('-', $date);
        return $tmp[2].'.'.$tmp[1].'.'.$tmp[0];
    }
}