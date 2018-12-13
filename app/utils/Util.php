<?php
namespace App\Utils;

class Util
{
    public static function safeString($noSafeString)
    {
        return filter_var($noSafeString, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH);;
    }
}
