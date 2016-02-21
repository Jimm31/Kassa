<?php
/**
 * Created by PhpStorm.
 * User: Delorian
 * Date: 16.01.2016
 * Time: 17:27
 */

namespace models;

/**
 * show off @property, @property-read, @property-write
 *
 * @property-read string $id
 * @property-read string $name the foo prop
 * @property-read array $groups the foo prop
 * @property-read array $tariffs the foo prop
 * @property-read string $login the bar prop
 * @property-read array $interfacesList the bar prop
 */
class Cashier
{

    private $SEE_FINANCIAL_STATISTICS = 2;
    private $SEE_USERS_STATISTICS = 3;
    private $SEND_MESSAGES = 4;
    private $ADD_MONEY = 5;
    private $DISABLE_USERS = 6;
    private $EDIT_USERS = 7;
    private $CREATE_USERS = 8;
    private $DELETE_USERS = 9;
    private $MANAGED_SERVICES = 10;
    private $SEE_TICKETS = 11;

    private $id;
    private $login;
    private $password;
    private $name;
    private $permissions;
    private $groups;
    private $tariffs;
    private $interfacesList;

    function __construct($cashier)
    {
        $this->id = $cashier['guid'];
        $this->login = $cashier['login'];
        $this->password = $cashier['password'];
        $this->name = $cashier['fio'];
        $this->interfacesList = $cashier['interfacesList'];
        $this->permissions = explode('||', $cashier['permissions']);
        $a = explode('||', $cashier['groups']);
        foreach ($a as $b) {
            $c = explode('**', $b);
            $this->groups[] = $c[0];
            for ($i = 1; $i < count($c); $i++) {
                if (!in_array($c[$i], $this->tariffs)) {
                    $this->tariffs[] = $c[$i];
                }
            }
        }
        $gr = '';
        $ta = '';
        foreach ($this->groups as $group) {
            $gr .= $group . ',';
        }
        foreach ($this->tariffs as $tariff) {
            $ta .= $tariff . ',';
        }

    }

    public function canSeeFinance()
    {
        return $this->permissions[$this->SEE_FINANCIAL_STATISTICS] === 'True' ? true : false;
    }

    public function canSeeStatistics()
    {
        return $this->permissions[$this->SEE_USERS_STATISTICS] === 'True' ? true : false;
    }

    public function canSendMessages()
    {
        return $this->permissions[$this->SEND_MESSAGES] === 'True' ? true : false;
    }

    public function canAddMoney()
    {
        return $this->permissions[$this->ADD_MONEY] === 'True' ? true : false;
    }

    public function canOnOffUsers()
    {
        return $this->permissions[$this->DISABLE_USERS] === 'True' ? true : false;
    }

    public function canEditUsers()
    {
        return $this->permissions[$this->EDIT_USERS] === 'True' ? true : false;
    }

    public function canCreateUsers()
    {
        return $this->permissions[$this->CREATE_USERS] === 'True' ? true : false;
    }

    public function canDeleteUsers()
    {
        return $this->permissions[$this->DELETE_USERS] === 'True' ? true : false;
    }

    public function canManageServices()
    {
        return $this->permissions[$this->MANAGED_SERVICES] === 'True' ? true : false;
    }

    public function canManageTickets()
    {
        return $this->permissions[$this->SEE_TICKETS] === 'True' ? true : false;
    }

    public function __toString()
    {
        return $this->id . " - " . $this->login . " ";
    }

    public function getAvailableGroups()
    {
        return implode(',', $this->groups);
    }

    public function getAvailableTariffs()
    {
        return implode(',', $this->tariffs);
    }

    public function __get($key)
    {
        switch ($key) {
            case 'id':
                return $this->id;
            case 'login':
                return $this->login;
            case 'name':
                return $this->name;
            case 'password':
                return $this->password;
            case 'groups':
                return $this->groups;
            case 'tariffs':
                return $this->tariffs;
            case 'interfacesList':
                return $this->interfacesList;
        }
    }

}