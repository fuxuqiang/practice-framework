<?php

namespace vendor;

class ObjectAccess
{
    protected $data;

    public function __get($name)
    {
        return $this->data[$name] ?? null;
    }
}