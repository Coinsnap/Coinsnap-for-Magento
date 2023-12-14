<?php

declare(strict_types=1);

namespace Coinsnap\Payment\Api\Exception;

class ForbiddenException extends RequestException
{
    public const STATUS = 403;
}
