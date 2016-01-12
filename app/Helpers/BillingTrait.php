<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 02.11.15
 * Time: 18:05
 */

namespace App\Helpers;


use Auth;
use DB;
use PDO;

/**
 * Trait contains function to work with Billing DB
 * Class BillingTrait
 * @package app\Helpers
 */
trait BillingTrait {

    /**
     * Returns connection to billing DB
     * @return \Illuminate\Database\Connection
     */
    private function getDB() {
        return DB::connection('billing');
    }

    /**
     * Selects data with $query, uses $params
     * @param $query
     * @param array $params
     * @return array
     */
    protected function selectFromBillingDB($query, $params = [])
    {
        return $this->getDB()->select($query, $params);
    }

    /**
     * Inserts data to Billing DB
     * @param $query
     * @param array $params
     * @return bool
     */
    protected function insertToBillingDB($query, $params = [])
    {
        return $this->getDB()->insert($query, $params);
    }

    /**
     * Inserts data to Billing DB
     * with returning of ID of inserted row
     * @param $query
     * @param array $params
     * @param $return_id primary key field name to return
     * @return bool
     */
    protected function insertGetIdToBillingDB($query, $params = [], $return_id)
    {
        $pdo         = $this->getDB()->getPdo();
        $queryHandle = $pdo->prepare($query);
        $queryHandle->queryString;
        $queryHandle->execute($params);
        $result      = $queryHandle->fetchAll(PDO::FETCH_OBJ);


        return $this->fetchField($result, $return_id);

    }


    protected function updateInBillingDB($query, $params = [])
    {
        return $this->getDB()->update($query, $params);
    }

    protected function deleteFromBillingDB($query, $params = [])
    {
        return $this->getDB()->delete($query, $params);
    }

    /**
     * Returns currency ID, "USA" by default
     * @param string $currency
     * @return bool
     */
    protected function getCurrencyIdFromBillingDB($currency = 'USA')
    {
        $currencyId = $this->selectFromBillingDB("
                            select currency_id
                            from currency where code = ?", [$currency]);

        return $this->fetchField($currencyId, 'currency_id');
    }

    /**
     * Returns current developer ID as it stored in Billing DB (table "client")
     * Uses "email" field to find client
     * @param null $user
     * @return bool
     */
    protected function getCurrentUserIdFromBillingDB($user = null)
    {
        $user   = $user ?: Auth::user();
        $result = $this->selectFromBillingDB('select client_id from client where name = ?',
                                [$user->email]);

        return $this->fetchField($result, 'client_id');
    }

    protected function getResourceByAliasFromBillingDB($alias, $fields = 'resource_id')
    {
        $resource = $this->selectFromBillingDB('select '.$fields.'
                                                from resource where alias = ?',
                                                [$alias]);
        if (isset($resource[0]))
            $resource = $resource[0];

        return $resource;
    }

    /**
     * Returns Laravel Fluent Query Builder for Billing DB queries
     * @param string $table table name
     * @return \Illuminate\Database\Query\Builder
     */
    protected function getFluentBilling($table)
    {
        return $this->getDB()->table($table);
    }

    /**
     * Returns app user's id from Billing
     * Uses App name and user's email to find the data
     * @param $user
     * @return bool
     */
    protected function getAPPUserIdFromBillingDB($user)
    {
        $clientName = $user->app->name."-".$user->email;

        $result = $this->selectFromBillingDB('select client_id from client where name = ?',
            [$clientName]);

        return $this->fetchField($result, 'client_id');
    }

    protected function getClientBalanceFromBillingDB($clientId)
    {
        $result = $this->selectFromBillingDB('select balance from с4_client_balance where client_id = ?',
            [$clientId]);

        return $this->fetchField($result, 'balance');
    }

    protected function deductClientBalanceInBillingDB($deductSum)
    {
        $clientId       = $this->getCurrentUserIdFromBillingDB();
        $currentBalance = $this->getClientBalanceFromBillingDB($clientId) * 100;
        $newSum         = $currentBalance - $deductSum;
        $newSum         = $newSum ? $newSum / 100 : 0;
        $newSum         = money_format('%i', $newSum);
        $db             = $this->getFluentBilling('с4_client_balance');

        return $db->whereClientId($clientId)->update(['balance' => $newSum]);

    }

    protected function fetchField($result, $field)
    {
        $fieldValue = false;
        if (isset($result[0])
            and isset($result[0]->$field))
            $fieldValue = $result[0]->$field;

        return $fieldValue;
    }

    protected function formatCDRData($cdr, $groupField = 'time')
    {
        $data = [];

        foreach ($cdr as $key => $entry) {
            $date = date('d.m', strtotime($entry->$groupField));
            if (isset($data[$date])) {
                $data[$date] += 1;
            } else $data[$date] = 1;
        }

        return ['labels' => array_keys($data), 'data' => array_values($data)];
    }
}