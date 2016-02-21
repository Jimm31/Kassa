<?php
namespace controllers;

use core\Controller;
use models\FinanceModel;

class FinanceController extends Controller
{
    /**
     * @var FinanceModel $model
     */
    private $model;

    function __construct()
    {
        parent::__construct();
        $this->model = new FinanceModel();
    }

    function index_action()
    {
        if (!$this->cashier->canSeeFinance()) {
            echo 'У вас нет прав на просмотр финансовой статистики!';
            exit;
        }
        $data = $this->model->index();
        $this->view->show('finance.twig', ['title' => 'Финансы', 'start' => $data['start'], 'login' => $this->cashier->login, 'end' => $data['end']]);
    }

    function summary_action()
    {
        if (!$this->cashier->canSeeFinance()) {
            echo 'У вас нет прав на просмотр финансовой статистики!';
            exit;
        }
        header('Content-Type: application/json');
        echo $this->model->getSummary();
    }

    function groups_action()
    {
        header('Content-Type: application/json');
        echo $this->model->getAvailiableGroups();

    }

    function payers_action()
    {
        if (!$this->cashier->canSeeFinance()) {
            echo 'У вас нет прав на просмотр финансовой статистики!';
            exit;
        }
        $data = $this->model->getPayersStat();
        $this->view->show('finance_payers.twig', [
            'payers' => $data['payers'],
            'total' => $data['total']
        ]);
    }

    function summarySms_action()
    {
        if (!$this->cashier->canSeeFinance()) {
            echo 'У вас нет прав на просмотр финансовой статистики!';
            exit;
        }
        $data = $this->model->getSummarySms();
        $this->view->show('finance_messages.twig', [
            'messages' => $data['messages'],
            'total' => $data['total']
        ]);
    }

    function payersInCSV_action()
    {
        if (!$this->cashier->canSeeFinance()) {
            echo 'У вас нет прав на просмотр финансовой статистики!';
            exit;
        }
        $this->model->getPayersStatInCSV();
    }

    function messagesStatInCSV_action()
    {
        if (!$this->cashier->canSeeFinance()) {
            echo 'У вас нет прав на просмотр финансовой статистики!';
            exit;
        }
        $this->model->getMessagesStatInCSV();
    }

    function payerInfo_action()
    {
        if (!$this->cashier->canSeeFinance()) {
            echo 'У вас нет прав на просмотр финансовой статистики!';
            exit;
        }
        $data = $this->model->getPayerInfo();
        $this->view->show('finance_payer_info.twig', [
            'payments' => $data['payments'],
            'total' => $data['total']
        ]);
    }

}