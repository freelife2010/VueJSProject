<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 03.11.15
 * Time: 19:44
 */

namespace App\API;


use Auth;

class PasswordVerifier {
    public function verify($username, $password)
    {
        $credentials = [
            'email'    => $username,
            'password' => $password,
        ];

        if (Auth::once($credentials)) {
            return Auth::user()->id;
        }

        return false;
    }
}