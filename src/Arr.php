<?php

namespace Fuxuqiang\Framework;

class Arr extends ObjectAccess
{
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function get(...$keys)
    {
        return $keys ? array_intersect_key($this->data, array_flip($keys)) : $this->data;
    }
}
