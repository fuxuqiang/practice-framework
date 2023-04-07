<?php

namespace Fuxuqiang\Framework;

enum ResponseCode: int
{
    case BadRequest = 400;
    case NotFound = 404;
    case UnprocessableEntity = 422;
    case InternalServerError = 500;
}