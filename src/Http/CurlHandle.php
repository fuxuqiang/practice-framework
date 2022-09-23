<?php

namespace Fuxuqiang\Framework\Http;

class CurlHandle
{
    public function __construct(
        public readonly mixed $handle,
        public readonly array $params
    ) {}
}
