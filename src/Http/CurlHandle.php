<?php

namespace Fuxuqiang\Framework\Http;

class CurlHandle
{
    public readonly mixed $handle;
    
    public readonly array $params;

    public function __construct($handle, $params)
    {
        $this->handle = $handle;
        $this->params = $params;
    }
}
