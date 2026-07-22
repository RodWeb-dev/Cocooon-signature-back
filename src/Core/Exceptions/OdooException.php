<?php
declare(strict_types=1);

namespace App\Core\Exceptions;

/** 502 — Odoo is connected but return an error */
class OdooException extends HttpException
{
    public function __construct(string $message)
    {
        parent::__construct(502, $message);
    }
}
