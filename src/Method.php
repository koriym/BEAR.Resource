<?php

declare(strict_types=1);

namespace BEAR\Resource;

/** @see https://www.rfc-editor.org/rfc/rfc9110#section-9 */
enum Method: string
{
    case GET = 'get';
    case POST = 'post';
    case PUT = 'put';
    case PATCH = 'patch';
    case DELETE = 'delete';
    case HEAD = 'head';
    case OPTIONS = 'options';
}
