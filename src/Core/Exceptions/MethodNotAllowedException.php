<?php
declare(strict_types=1);

namespace App\Core\Exceptions;

class MethodNotAllowedException extends HttpException
{
    public function __construct()
    {
        parent::__construct(405, 'Méthode non autorisée');
    }
}
