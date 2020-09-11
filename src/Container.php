<?php

namespace Fuxuqiang\Framework;

class Container
{
    private static $binds, $instances;

    public static function bind($name, callable $provider)
    {
        self::$binds[$name] = $provider;
    }

    public static function instance($name, $instance)
    {
        self::$instances[$name] = $instance;
    }

    public static function get($name)
    {
        return self::$instances[$name] ??
            (isset(self::$binds[$name]) ? self::$instances[$name] = call_user_func(self::$binds[$name]) : null);
    }
}
