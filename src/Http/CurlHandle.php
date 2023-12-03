<?php

namespace Fuxuqiang\Framework\Http;

readonly class CurlHandle
{
    public function __construct(public mixed $handle, public array $params) {}

    public function getContent(): ?string
    {
        return curl_multi_getcontent($this->handle);
    }
}
