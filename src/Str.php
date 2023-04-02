<?php

namespace Fuxuqiang\Framework;

class Str
{
    public static function snake(string $str): string
    {
        return strtolower(preg_replace('/(?<=\w)[A-Z]/', '_$0', $str));
    }

    public static function camel(string $str): string
    {
        return ucfirst(
            preg_replace_callback('/_([a-z])/', fn($matches) => strtoupper($matches[1]), $str)
        );
    }
}