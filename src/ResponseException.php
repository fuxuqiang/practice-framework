<?php

namespace Fuxuqiang\Framework;

class ResponseException extends \Exception
{
    const BAD_REQUEST = 400,
        NOT_FOUND = 404,
        UNPROCESSABLE_ENTITY = 422,
        INTERNAL_SERVER_ERROR = 500;
}