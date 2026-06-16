<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Mail\MailTransportInterface;
use App\Core\Mail\SmtpTransport;
use App\Core\Mail\BrevoTransport;
use App\Core\Exceptions\MailException;

class EmailService
{
    private MailTransportInterface $transport;

    private const NOREPLY = [
        'email' => 'noreply@cocoon-signature.com',
        'name'  => 'Cocoon Signature'
    ];

    public function __construct()
    {
        $this->transport = $_ENV['APP_ENV'] === 'development'
        ? new SmtpTransport()
        : new BrevoTransport();
    }

    private function renderTemplate(string $name, array $data): string
    {
        ob_start();
        include __DIR__ . '/../Templates/Mail/' . $name . '.php';
        return ob_get_clean();
    }

    public function sendWelcome(array $to, string $firstname): void
    {
        $body = $this->renderTemplate('welcome', ['firstname' => $firstname]);
        $subject = 'Bienvenue chez Cocoon Signature';

        try {
            $this->transport->send(self::NOREPLY, $to, $subject, $body);
        } catch (MailException $e) {
            error_log("Échec envoi mail de bienvenue à {$to['email']} : " . $e->getMessage());
        }
    }
}
