<?php

namespace Fuxuqiang\Framework\Model;


class ModelNotFoundException extends \UnexpectedValueException
{
    protected $code = \Fuxuqiang\Framework\ResponseCode::BadRequest->value;
}