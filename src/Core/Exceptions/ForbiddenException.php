<?php
declare(strict_types=1);

namespace App\Core\Exceptions;

/** 403 — authenticated but not allowed to access this resource. */
class ForbiddenException extends HttpException
{
    public function __construct()
    {
        parent::__construct(403, 'Accès interdit');
    }
}
