<?php

namespace Fuxuqiang\Framework;

interface Connector
{
    public function connect(): Mysql;
}
