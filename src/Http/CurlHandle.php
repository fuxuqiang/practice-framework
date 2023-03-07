<?php

namespace Fuxuqiang\Framework\Http;

class CurlHandle
{
    public function __construct(
        public readonly mixed $handle,
        public readonly array $params
    ) {}

    public function getContent(): ?string
    {
        return curl_multi_getcontent($this->handle);
    }
}
