<?php
/**
 * Created by PhpStorm.
 * User: Delorian
 * Date: 17.01.2016
 * Time: 2:12
 */

namespace models;


use core\Model;

class MainModel extends Model
{
    



    function index()
    {
        $query = "SELECT
						SUBSTR(trafic, 1, LENGTH(trafic) - 6) AS `traffic`,
						speed,
						lineload,
						conline AS `on`,
						offclients AS `off`,
						SUBSTR(balance, 1, LENGTH(balance) - 8) AS `balance`,
						SUBSTR(spent, 1, LENGTH(spent) - 8) AS `spent`,
						SUBSTR(debt, 1, LENGTH(debt) - 8) AS `debt`
					FROM
						allstat
					WHERE
						cashier = ?";
        $data = $this->find($query, [$this->cashier->login]);
        $tmp = explode('из', $data['on']);
        $data['on'] = trim($tmp[0]);
        $data['all'] = trim($tmp[1]);
        $tmp = explode('из', $data['off']);
        $data['off'] = trim($tmp[0]);
        $data['traffic'] = $this->convertTraffic($data['traffic']);
        return $data;
    }

    function getUsers()
    {
        $where = " 1=1 ";
        $order_by = "user_name";
        $rows = 25;
        $current = 1;
        $limit_l = ($current * $rows) - ($rows);
        $limit_h = $limit_l + $rows;
        $groups = $this->cashier->getAvailableGroups();
//Handles Sort querystring sent from Bootgrid
        if (isset($_REQUEST['sort']) && is_array($_REQUEST['sort'])) {
            $order_by = "";
            foreach ($_REQUEST['sort'] as $key => $value)
                $order_by .= " $key $value";
        }
//Handles search querystring sent from Bootgrid
        if (isset($_REQUEST['searchPhrase'])) {
            $search = trim($_REQUEST['searchPhrase']);
            $where .= " AND ( user_name LIKE '" . $search . "%' OR FIO LIKE '" . $search . "%' OR tarif LIKE '" . $search . "%' OR group_name LIKE '%" . $search . "%' ) ";
        }
//Handles determines where in the paging count this result set falls in
        if (isset($_REQUEST['rowCount']))
            $rows = $_REQUEST['rowCount'];
//calculate the low and high limits for the SQL LIMIT x,y clause
        if (isset($_REQUEST['current'])) {
            $current = $_REQUEST['current'];
            $limit_l = ($current * $rows) - ($rows);
            $limit_h = $rows;
        }
        if ($rows == -1)
            $limit = ""; //no limit
        else
            $limit = " LIMIT $limit_l,$limit_h ";
//NOTE: No security here please beef this up using a prepared statement - as is this is prone to SQL injection.
        $sql = "SELECT shortguid as id, state, user_name, FIO, group_name, tarif, ballance, otherinfo from stat WHERE $where AND group_guid IN($groups) ORDER BY $order_by $limit";
        try {
            $results_array = $this->findAll($sql, []);
            for ($i = 0; $i < count($results_array); $i++) {
                $otherInfo = explode('||', $results_array[$i]['otherinfo']);
                if ($results_array[$i]['state'] == 'Да') {
                    if ($otherInfo[21] == 'True') {
                        $results_array[$i]['state'] = 1;
                    } else {
                        $results_array[$i]['state'] = 2;
                    }
                } else {
                    $results_array[$i]['state'] = 3;
                }
                $results_array[$i]['otherinfo'] = '';
            }
            $json = json_encode($results_array);
            /* specific search then how many match */
            $nRows = $this->connection->query("SELECT count(*) FROM stat WHERE $where AND group_guid IN($groups)")->fetchColumn();
            header('Content-Type: application/json; charset=utf-8'); //tell the broswer JSON is coming
            if (isset($_REQUEST['rowCount'])) //Means we're using bootgrid library
                echo "{ \"current\": $current, \"rowCount\":$rows, \"rows\": " . $json . ", \"total\": $nRows }";
            else
                echo $json; //Just plain vanillat JSON output
        } catch (\PDOException $e) {
            echo 'SQL PDO ERROR: ' . $e->getMessage();
        }

    }

    private function convertTraffic($traffic)
    {
        $tmp = explode('/', $traffic);
        $in = number_format((trim($tmp[0]) / 1024), 0, '.', '');
        $out = number_format((trim($tmp[1]) / 1024), 0, '.', '');
        return $in . ' / ' . $out . ' Гб.';
    }
}