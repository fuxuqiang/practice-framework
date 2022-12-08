<?php

namespace Fuxuqiang\Framework;

abstract class ObjectAccess
{
    protected $data;

    public function getData(...$keys)
    {
        return $keys ? array_intersect_key($this->data, array_flip($keys)) : $this->data;
    }

    public function __get($name)
    {
        return $this->data[$name] ?? null;
    }
}