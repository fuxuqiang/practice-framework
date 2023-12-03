<?php

namespace Fuxuqiang\Framework\Request;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER)]
readonly class Validator
{
    public function __construct(public ValidationType $type) {}
}