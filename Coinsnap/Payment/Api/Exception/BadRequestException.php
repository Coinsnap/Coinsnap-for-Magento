<?php

declare(strict_types=1);

namespace Coinsnap\Payment\Api\Exception;

use Coinsnap\Payment\Api\Exception\RequestException;

class BadRequestException extends RequestException
{
    public const STATUS = 400;
}
