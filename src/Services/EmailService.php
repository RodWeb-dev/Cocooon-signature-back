<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Mail\MailTransportInterface;
use App\Core\Mail\SmtpTransport;
use App\Core\Mail\BrevoTransport;
use App\Core\Exceptions\MailException;

/** Sends transactional emails via SmtpTransport (dev) or BrevoTransport (prod). */
class EmailService
{
    private MailTransportInterface $transport;

    private const NOREPLY = [
        'email' => 'noreply@cocoon-signature.com',
        'name'  => 'Cocoon Signature'
    ];

    private const CONTACT = [
        'email' => 'contact-message@cocoon-signature.com',
        'name'  => 'Cocoon Signature'
    ];

    public function __construct()
    {
        $this->transport = $_ENV['APP_ENV'] === 'development'
        ? new SmtpTransport()
        : new BrevoTransport();
    }

    /**
     * Renders a PHP mail template to an HTML string.
     *
     * The included template has access to $data via PHP include scope sharing.
     *
     * @param string $name Template filename without extension (e.g. 'welcome')
     * @param array  $data Variables exposed to the template
     */
    private function renderTemplate(string $name, array $data): string
    {
        ob_start();
        include __DIR__ . '/../Templates/Mail/' . $name . '.php';
        return ob_get_clean();
    }

    /**
     * Sends the welcome email with an email-verification link.
     *
     * @param array  $to        Recipient as ['email' => ..., 'name' => ...]
     * @param string $firstname Recipient first name for personalisation
     * @param string $url       Email-verification URL
     */
    public function sendWelcome(array $to, string $firstname, string $url): void
    {
        $body = $this->renderTemplate('welcome', [
            'firstname' => $firstname,
            'url'     => $url
            ]);
        $subject = 'Bienvenue chez Cocoon Signature';

        try {
            $this->transport->send(self::NOREPLY, $to, $subject, $body);
        } catch (MailException $e) {
            error_log("Échec envoi mail de bienvenue à {$to['email']} : " . $e->getMessage());
        }
    }

    /**
     * Sends the password-reset email with a one-time reset link.
     *
     * @param array  $to        Recipient as ['email' => ..., 'name' => ...]
     * @param string $firstname Recipient first name for personalisation
     * @param string $url       Password-reset URL containing the one-time token
     */
    public function sendResetPassword(array $to, string $firstname, string $url): void
    {
        $body = $this->renderTemplate('reset-password', [
            'firstname' => $firstname,
            'url'       => $url
        ]);
        $subject = 'Réinitialisation de votre mot de passe';
        try {
            $this->transport->send(self::NOREPLY, $to, $subject, $body);
        } catch (MailException $e) {
            error_log("Échec envoi mail de réinitialisation à {$to['email']} : " . $e->getMessage());
        }
    }

    /**
     * Sends a contact-form message to the shop's contact address.
     *
     * @param string $name    Sender name as entered in the contact form
     * @param string $email   Sender email address
     * @param string $subject Message subject
     * @param string $content Message body
     */
    public function sendContactMessage($name, $email, $subject, $content): void
    {
        $body = $this->renderTemplate('contact-message', [
            'name' => $name,
            'email'    => $email,
            'subject'  => $subject,
            'content'  => $content
        ]);
        $subject = 'Nouveau message de ' . $name . ' depuis cocoon-signature.fr';
        try {
            $this->transport->send(self::CONTACT, ['email' => 'contact@cocoon-signature.com', 'name' => 'Corinne Gallot'], $subject, $body);
        } catch (MailException $e) {
            error_log("Échec envoi mail de contact-message de {$email} : " . $e->getMessage());
        }
    }
}
