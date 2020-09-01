<?php namespace Bmodel;

function splitStringCases ($val) {
    $val = preg_replace('/[A-Z-]/', '_$1', $val);
    $val = str_replace('__', '_', $val);
    $val = strtolower($val);
    if (substr($val, 0, 1) == '_') $val = substr($val, 1);
    return explode('_', $val);
}
function camelCase ($val) {
    $val = splitStringCases($val);

    $i = 0;
    $str = $val[$i++];
    while(isset($val[$i])) {
        $str .= strtoupper(substr($val[$i], 0, 1)).substr($val[$i], 1);
        $i++;
    }
    return $str;
}

function pascalCase ($val) {
    $str = camelCase($val);
    $str = strtoupper(substr($str, 0, 1)).substr($str, 1);
    return $str;
}

function snakeCase ($val) {
    $val = splitStringCases($val);
    return implode('_', $val);
}

function kebabCase ($val) {
    $val = splitStringCases($val);
    return implode('-', $val);
}
