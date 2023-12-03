<?php

namespace Fuxuqiang\Framework\Route;

use Fuxuqiang\Framework\{ResponseCode, ResponseException};

readonly class Router
{
    public function __construct(private string $target) {}

    /**
     * 生成路由文件
     * @throws \ReflectionException
     */
    public function handle($namespace): void
    {
        $routes = [];
        // 获取根命名空间
        $search = strstr($namespace, '\\', true) . '\\';
        // 遍历文件夹
        foreach ((require __DIR__ . '/../../../../composer/autoload_psr4.php')[$search] as $item) {
            // 遍历文件
            $handle = opendir(str_replace($search, $item.'/', $namespace));
            while ($file = readdir($handle)) {
                if ($file != '.' && $file != '..') {
                    // 获取ReflectionClass实例
                    $class = $namespace . '\\' . strstr($file, '.php', true);
                    $reflection = new \ReflectionClass($class);
                    // 获取类的Route注解
                    $baseRoute = ($attributes = $reflection->getAttributes(Route::class)) ? $attributes[0]->newInstance() : null;
                    // 获取方法的Route注解
                    foreach ($reflection->getMethods() as $method) {
                        if ($attributes = $method->getAttributes(Route::class)) {
                            $route = $attributes[0]->newInstance();
                            // 解析路由
                            $uri = ltrim($route->uri, '/');
                            if ($baseRoute?->uri) {
                                $uri = rtrim($baseRoute->uri, '/') . '/' . $uri;
                            }
                            $routes[$route->method][$uri] = [
                                'class' => $class,
                                'method' => $method->getName(),
                                'middlewares' => array_merge($baseRoute->middlewares ?? [], $route->middlewares),
                            ];
                        }
                    }
                }
            }
        }
        // 保存路由至文件
        file_put_contents($this->target, sprintf('<?php%sreturn %s;', PHP_EOL, var_export($routes, true)));
    }

    /**
     * 获取匹配路由
     * @throws ResponseException
     */
    public function get($method, $url)
    {
        return (require $this->target)[$method][$url] ?? throw new ResponseException('未找到链接', ResponseCode::NotFound);
    }
}
