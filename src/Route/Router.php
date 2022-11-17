<?php

namespace Fuxuqiang\Framework\Route;

use Fuxuqiang\Framework\ResponseException;

class Router
{
    public function __construct(private $target) {}

    /**
     * 生成路由文件
     */
    public function handle($namespace)
    {
        $search = strstr($namespace, '\\', true) . '\\';
        foreach ((require __DIR__ . str_repeat(DIRECTORY_SEPARATOR.'..', 4) . '/composer/autoload_psr4.php')[$search] as $item) {
            $handle = opendir(str_replace($search, $item . DIRECTORY_SEPARATOR, $namespace));
            while ($file = readdir($handle)) {
                if ($file != '.' && $file != '..') {
                    $class = $namespace . '\\' . strstr($file, '.php', true);
                    foreach ((new \ReflectionClass($class))->getMethods() as $method) {
                        foreach ($method->getAttributes(Route::class) as $attribute) {
                            $route = $attribute->newInstance();
                            $routes[$route->method][$route->url] = [
                                'class' => $class,
                                'method' => $method->getName(),
                                'middlewares' => $route->middlewares
                            ];
                        }
                    }
                }
            }
        }
        file_put_contents($this->target, sprintf('<?php%sreturn %s;', PHP_EOL, var_export($routes, true)));
    }

    /**
     * 获取匹配路由
     */
    public function get($method, $url)
    {
        return (require $this->target)[$method][$url] ?? throw new ResponseException('未找到链接', ResponseException::NOT_FOUND);
    }
}