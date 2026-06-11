<?php
declare(strict_types=1);

namespace App\Core\Exceptions;

/** 404 — no route matched the requested URI. */
class NotFoundException extends HttpException
{
    public function __construct()
    {
        parent::__construct(404, 'Non trouvé');
    }
}
