<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 13.01.16
 * Time: 13:04
 */

namespace App\Helpers;


use DB;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use SimpleXMLElement;
use Storage;

class Misc
{
    public static function generateUniqueId(
        $digits = 99999999,
        $field = 'tech_prefix',
        $table = 'users',
        $minDigit = 0
    ) {
        $number = mt_rand($minDigit, $digits);

        // call the same function if the barcode exists already
        if (self::IdExists($number, $field, $table)) {
            return self::generateUniqueId();
        }

        // otherwise, it's valid and can be used
        return $number;
    }

    public static function IdExists($number, $field, $table)
    {
        // query the database and return a boolean
        // for instance, it might look like this in Laravel
        return DB::table($table)->where($field, '=', $number)->exists();
    }

    public static function filterNumbers($string)
    {
        return preg_replace('/[^0-9]/', '', $string);
    }

    public static function getFile($storage, $filename)
    {
        $file = null;
        try {
            Storage::disk($storage)->has($filename);
            $file = Storage::disk($storage)->get($filename);
        } catch (FileNotFoundException $e) {
            \Log::warning('File not found', [
                'file' => $filename
            ]);
        }

        return $file;
    }

    public static function testXml($request)
    {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><document></document>');
        $xml->addAttribute('type', 'freeswitch/xml');
        $section = $xml->addChild('section');
        $section->addAttribute('name', 'dialplan');
        $section->addAttribute('description', 'dialplan');
        $context = $section->addChild('context');
        $context->addAttribute('name', 'default');
        $extension = $context->addChild('extension');
        $extension->addAttribute('name', 'test9');
        $condition = $extension->addChild('condition');
        $condition->addAttribute('field', 'destination_number');
        $condition->addAttribute('expression', '^(.*)$');
        self::parseXml($request->xml, $condition);

        return new \Dingo\Api\Http\Response($xml->asXML(), 200, ['Content-Type' => 'application/xml']);
    }

    static function parseXml($simpleXml, $condition)
    {
        $simpleXml = new SimpleXMLElement($simpleXml);
        $action    = $condition->addChild('action');
        self::appendAttributes($simpleXml->attributes(), $action);
        self:: appendChildren($simpleXml, $action);
    }

    static function appendChildren($element, $parent)
    {
        $children = $element->children();

        foreach ($children as $child) {
            $appendedChild = $parent->addChild($child->getName(), (string)$child);
            if ($child->attributes())
                appendAttributes($child->attributes(), $appendedChild);
            if ($child->children())
                self::appendChildren($child, $appendedChild);
        }
    }

    static function appendAttributes($attributes, $parent)
    {
        $attr = $parent->attributes();
        foreach ($attributes as $attribute) {
            if (!isset($attr[$attribute->getName()]))
                $parent->addAttribute($attribute->getName(), (string)$attribute);
        }
    }

    static function isValidXML($xml)
    {
        $doc = @simplexml_load_string($xml);
        if ($doc) {
            return true;
        } else {
            return false;
        }
    }
}