<?php

declare(strict_types=1);

namespace App\Core\Mail;

interface MailTransportInterface
{
    public function send(array $from, array $to, string $subject, string $htmlBody): void;
}
