<?php
declare(strict_types=1);

namespace App\Core\Exceptions;

/** 401 — JWT is absent, malformed, or expired. */
class UnauthorizedException extends HttpException
{
    public function __construct()
    {
        parent::__construct(401, 'Non autorisé');
    }
}
