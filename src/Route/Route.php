<?php

namespace Fuxuqiang\Framework\Route;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class Route
{
    public function __construct(
        public readonly string $uri = '',
        public readonly string $method = 'GET',
        public readonly array $middlewares = []
    ) {}
}