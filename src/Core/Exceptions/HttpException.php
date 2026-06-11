<?php
declare(strict_types=1);

namespace App\Core\Exceptions;

/** Base class for HTTP errors — carries the status code and a client-safe message. */
class HttpException extends \Exception
{
    public function __construct(int $code, string $message)
    {
        parent::__construct($message, $code);
    }
}
