<?php
declare(strict_types=1);

namespace App\Core\Exceptions;

class NotFoundException extends HttpException
{
    public function __construct()
    {
        parent::__construct(404, 'Non trouvé');
    }
}
