<?php

declare(strict_types=1);

namespace App\Core\Mail;

interface MailTransportInterface
{
    /**
     * Sends an HTML email.
     *
     * @param array{email: string, name: string} $from      Sender — requires 'email' and 'name' keys
     * @param array{email: string, name: string} $to        Recipient — requires 'email' and 'name' keys
     * @param string                             $subject   Email subject line
     * @param string                             $htmlBody  Full HTML body
     * @throws \App\Core\Exceptions\MailException on transport failure
     */
    public function send(array $from, array $to, string $subject, string $htmlBody): void;
}
