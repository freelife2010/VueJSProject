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

    function __construct($model, $APP = null)
    {
        $this->model = $model;
        $this->APP   = $APP;
    }


    public function run($file, $columns)
    {
        $content = Excel::load($file)->get();
        $this->columns = $columns;
        $groups  = $content->groupBy($columns['email']);;
        foreach ($groups as $name => $items) {
            $items = $items->toArray();
            if (isset($items[0]))
                $this->saveResult($items[0]);
        }
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