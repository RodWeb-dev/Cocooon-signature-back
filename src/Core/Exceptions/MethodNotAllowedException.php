<?php
declare(strict_types=1);

namespace App\Core\Exceptions;

/** 405 — the URI matched a route but the HTTP method did not. */
class MethodNotAllowedException extends HttpException
{
    public function __construct()
    {
        parent::__construct(405, 'Méthode non autorisée');
    }
}
