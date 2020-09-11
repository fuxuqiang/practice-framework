<?php

namespace vendor;

class Route
{
    private static $routes = [];

    private $middlewares = [], $prefix;

    /**
     * 添加路由
     */
    private function add(array $routes)
    {
        foreach ($routes as $method => $group) {
            $_group = [];
            foreach ($group as $uri => $action) {
                $_group[$this->prefix ? rtrim($this->prefix . '/' . $uri, '/') : $uri] = $this->middlewares ?
                    [$action, $this->middlewares] : $action;
            }
            self::$routes[$method] = array_merge(self::$routes[$method] ?? [], $_group);
        }
    }

    /**
     * 设置路由中间件
     */
    private function middleware($middleware, ...$args)
    {
        if ($args) {
            $this->middlewares[$middleware] = $args;
        } else {
            $this->middlewares[] = $middleware;
        }
        return $this;
    }

    /**
     * 设置路由前缀
     */
    private function prefix(string $prefix)
    {
        $this->prefix = $prefix;
        return $this;
    }

    /**
     * 设置资源路由
     */
    private function resource(string $name, array $actions)
    {
        $actions = array_intersect($actions, ['add', 'update', 'del', 'list', 'show']);
        $methods = ['add' => 'POST', 'update' => 'PUT', 'del' => 'DELETE', 'list' => 'GET', 'show' => 'GET'];
        foreach ($actions as $action) {
            $this->add([
                $methods[$action] => [
                    $name . ($action == 'list' ? 's' : '') => ucfirst($name) . '@' . $action
                ]
            ]);
        }
    }

    /**
     * 获取路由中间件
     */
    public static function get($method, $uri)
    {
        if ($route = self::$routes[$method][$uri] ?? null) {
            return $route;
        } else {
            throw new \Exception('', 404);
        }
    }

    /**
     * 调用类方法
     */
    public function __call($name, $args)
    {
        return $this->$name(...$args);
    }

    /**
     * 静态调用类方法
     */
    public static function __callStatic($name, $args)
    {
        return (new self)->$name(...$args);
    }
}
