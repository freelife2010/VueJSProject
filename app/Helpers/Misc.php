<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 13.01.16
 * Time: 13:04
 */

namespace App\Helpers;


use DB;

class Misc
{
    public static function generateUniqueId($digits = 99999999,
        $field = 'tech_prefix', $table = 'users') {
        $number = mt_rand(0, $digits);

        // call the same function if the barcode exists already
        if (self::IdExists($number, $field, $table)) {
            return self::generateUniqueId();
        }

        // otherwise, it's valid and can be used
        return $number;
    }

    public static function IdExists($number, $field, $table) {
        // query the database and return a boolean
        // for instance, it might look like this in Laravel
        return DB::table($table)->where($field, '=', $number)->exists();
    }

    public static function filterNumbers($string)
    {
        return preg_replace( '/[^0-9]/', '', $string );
    }
}