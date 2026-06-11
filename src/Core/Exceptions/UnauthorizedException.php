<?php
declare(strict_types=1);

namespace App\Core\Exceptions;

class UnauthorizedException extends HttpException
{
    public function __construct()
    {
        parent::__construct(401, 'Non autorisé');
    }
}
