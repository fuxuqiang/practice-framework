<?php

namespace Fuxuqiang\Framework\Route;

#[\Attribute]
class Route
{
    public function __construct(
        public readonly string $uri = '/',
        public readonly string $method = 'GET',
        public readonly array $middlewares = []
    ) {}
}