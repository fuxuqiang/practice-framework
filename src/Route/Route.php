<?php

namespace Fuxuqiang\Framework\Route;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
readonly class Route
{
    public function __construct(
        public string $uri = '',
        public string $method = 'GET',
        public array  $middlewares = []
    ) {}
}