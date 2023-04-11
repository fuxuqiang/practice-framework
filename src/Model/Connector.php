<?php

namespace Fuxuqiang\Framework\Model;

interface Connector
{
    public function connect(): \Fuxuqiang\Framework\Mysql;
}
