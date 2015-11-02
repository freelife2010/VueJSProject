<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 02.11.15
 * Time: 18:05
 */

namespace App\Helpers;


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

    protected function insertGetIdToBillingDB($query, $params = [])
    {
        $pdo         = $this->getDB()->getPdo();
        $queryHandle = $pdo->prepare($query);
        $queryHandle->execute($params);

        return $queryHandle->fetchAll(PDO::FETCH_OBJ);
    }


    protected function updateInBillingDB($query, $params = [])
    {
        return $this->getDB()->update($query, $params);
    }

    protected function deleteFromBillingDB($query, $params = [])
    {
        return $this->getDB()->delete($query, $params);
    }
}