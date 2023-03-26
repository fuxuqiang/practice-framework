<?php

namespace Fuxuqiang\Framework;

class Container
{
    private static array $binds = [], $instances = [];

    /**
     * 注册一个绑定
     */
    public static function bind(string $name, callable $provider): void
    {
        self::$binds[$name] = $provider;
    }

    /**
     * 注册一个实例到容器中
     */
    public static function instance(string $name, $instance): void
    {
        self::$instances[$name] = $instance;
    }

    /**
     * 从容器中获取注册的内容
     */
    public static function get(string $name)
    {
        return self::$instances[$name] ??
            (isset(self::$binds[$name]) ? self::$instances[$name] = call_user_func(self::$binds[$name]) : null);
    }

    /**
     * 创建一个类的实例
     * @throws \ReflectionException
     */
    public static function newInstance(string $concrete)
    {
        $reflector = new \ReflectionClass($concrete);
        if ($constructor = $reflector->getConstructor()) {
            foreach ($constructor->getParameters() as $param) {
                if ($class = $param->getDeclaringClass()) {
                    $constructorArgs[] = $class->newInstance();
                }
            }
            return new $concrete(...$constructorArgs);
        } else {
            return new $concrete;
        }
    }
}
