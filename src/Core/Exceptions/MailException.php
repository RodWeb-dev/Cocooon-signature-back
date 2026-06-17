<?php

declare(strict_types=1);

namespace App\Core\Exceptions;

use Throwable;

/** Thrown by SmtpTransport on socket errors or unexpected SMTP server responses. */
class MailException extends \Exception
{
    public function __construct(string $message = "", int $code = 0)
    {
        return parent::__construct($message, $code);
    }
}
