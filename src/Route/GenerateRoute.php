<?php

namespace Fuxuqiang\Framework\Route;

class GenerateRoute
{
    public function handle($namespace, $target)
    {
        $search = strstr($namespace, '\\', true) . '\\';
        foreach ((require __DIR__ . '/../../../../composer/autoload_psr4.php')[$search] as $item) {
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
        file_put_contents($target, '<?php' . PHP_EOL . 'return ' . var_export($routes, true) . ';');
    }
}