<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 09.11.15
 * Time: 18:50
 */

namespace App\Helpers;




use App\Jobs\StoreAPPUserToBillingDB;
use App\Jobs\StoreAPPUserToChatServer;
use App\Models\AppUser;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Maatwebsite\Excel\Facades\Excel;

class ExcelParser {
    use DispatchesJobs;

    protected $model;
    protected $APP;
    protected $columns;

    protected $saved = 0;
    protected $errors = [];

    function __construct($model, $APP = null)
    {
        $this->model = $model;
        $this->APP   = $APP;
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }




    public function run($file, $columns)
    {
        $content = Excel::load($file);
        $this->columns = $columns;
        $groups  = $content->get()->groupBy($columns['email']);
        $findEmail = $content->select([$columns['email']])->get();
        if (!empty($findEmail)
            and !empty($findEmail[0])) {
            foreach ($groups as $name => $items) {
                $items = $items->toArray();
                if ($this->checkItems($items, $columns))
                    $this->saveResult($items[0]);
                else break;
            }
        } else $this->errors[] = 'Cannot find column: '.$columns['email'];
    }

    protected function checkItems($items, $columns)
    {
        $result = false;
        if (isset($items[0])) {
            foreach ($columns as $column) {
                if (!isset($items[0][$column])) {
                    $this->errors[] = 'Cannot find column: '.$column;
                    return false;
                }
            }

            $result = true;
        }

        return $result;
    }

    protected function saveResult($items)
    {
        $columns        = $this->columns;
        $emailColumn    = $columns['email'];
        $usernameColumn = $columns['username'];
        $passwordColumn = $columns['password'];
        if ($this->model->isValidEmail($items[$emailColumn])) {
            $params = [
                'app_id'   => $this->APP ? $this->APP->id : 0,
                'email'    => $items[$emailColumn],
                'name'     => $items[$usernameColumn],
                'password' => $items[$passwordColumn]
            ];

            if ($user = AppUser::createUser($params)) {
                $this->saved++;
                $this->dispatch(new StoreAPPUserToBillingDB($user, $user->app));
                $this->dispatch(new StoreAPPUserToChatServer($user));
            }
        }
    }

    /**
     * @return int
     */
    public function getTotalSaved()
    {
        return $this->saved;
    }


}