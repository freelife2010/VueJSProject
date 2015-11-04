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

    private function getDB() {
        return DB::connection('billing');
    }

    protected function selectFromBillingDB($query, $params = [])
    {
        return $this->getDB()->select($query, $params);
    }

    protected function insertToBillingDB($query, $params = [])
    {
        return $this->getDB()->insert($query, $params);
    }

    protected function insertGetIdToBillingDB($query, $params = [], $return_id)
    {
        $pdo         = $this->getDB()->getPdo();
        $queryHandle = $pdo->prepare($query);
        $queryHandle->execute($params);
        $result      = $queryHandle->fetchAll(PDO::FETCH_OBJ);
        if (isset($result[0]))
            $id = $result[0]->$return_id;
        else $id = false;

        return $id;
    }


    protected function updateInBillingDB($query, $params = [])
    {
        return $this->getDB()->update($query, $params);
    }

    protected function deleteFromBillingDB($query, $params = [])
    {
        return $this->getDB()->delete($query, $params);
    }

    protected function getCurrencyIdFromBillingDB()
    {
        $currencyId = $this->selectFromBillingDB("
                            select currency_id
                            from currency where code = ?", ['USA']);
        if (isset($currencyId[0]))
            $currencyId = $currencyId[0]->currency_id;
        else $currencyId = false;

        return $currencyId;
    }

    protected function getCurrentUserIdFromBillingDB()
    {
        $user   = Auth::user();
        $result = $this->selectFromBillingDB('select client_id from client where name = ?',
                                [$user->email]);
        if (isset($result[0]))
            $clientId = $result[0]->client_id;
        else $clientId = false;

        return $clientId;
    }
}