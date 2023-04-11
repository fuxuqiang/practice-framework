<?php

namespace Fuxuqiang\Framework;

enum ResponseCode: int
{
    case OK = 200;
    case BadRequest = 400;
    case NotFound = 404;
    case UnprocessableEntity = 422;
    case InternalServerError = 500;
}