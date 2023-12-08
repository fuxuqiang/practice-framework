<?php

namespace Fuxuqiang\Framework;

class ResponseException extends \UnexpectedValueException
{
    public function __construct(string $message, ResponseCode $code = ResponseCode::BadRequest)
    {
        parent::__construct($message, $code->value);
    }
}