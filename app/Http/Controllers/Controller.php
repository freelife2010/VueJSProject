<?php namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesCommands;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;

abstract class Controller extends BaseController {

	use DispatchesCommands, ValidatesRequests;

    protected function getResult($error, $alert, $html='', $extra = [])
    {
        $result = [
            'error' => $error,
            'alert' => $alert,
            'html'  => $html
        ];

        $result = array_merge($result, $extra);

        return $result;
    }
}
