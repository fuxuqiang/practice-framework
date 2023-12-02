<?php

namespace Fuxuqiang\Framework\Model;

use Fuxuqiang\Framework\{ResponseCode, ResponseException};

class ModelNotFoundException extends ResponseException
{
    protected $code = ResponseCode::BadRequest->value;
}