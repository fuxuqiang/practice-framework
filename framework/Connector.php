<?php

namespace vendor;

interface Connector
{
    public function connect(): Mysql;
}
