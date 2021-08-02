<?php

namespace Bmodel;

class Commons
{
    public static function splitStringCases($val)
    {
        $val = preg_replace("/([A-Z])/", '_$1', $val);
        $val = preg_replace("/([-])/", '_', $val);
        $val = str_replace('__', '_', $val);

        $val = strtolower($val);
        if (substr($val, 0, 1) == '_') {
            $val = substr($val, 1);
        }
        return explode('_', $val);
    }

    public static function camelCase($val)
    {
        $val = self::splitStringCases($val);

        $i = 0;
        $str = $val[$i++];
        while (isset($val[$i])) {
            $str .= strtoupper(substr($val[$i], 0, 1)) . substr($val[$i], 1);
            $i++;
        }
        return $str;
    }

    public static function pascalCase($val)
    {
        $str = self::camelCase($val);
        $str = strtoupper(substr($str, 0, 1)) . substr($str, 1);

        return $str;
    }

    public static function snakeCase($val)
    {
        $val = self::splitStringCases($val);
        return implode('_', $val);
    }

    public static function kebabCase($val)
    {
        $val = self::splitStringCases($val);
        return implode('-', $val);
    }
}
