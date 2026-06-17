<?php

declare(strict_types=1);

namespace App\Core\Mail;

use App\Core\Mail\MailTransportInterface;
use App\Core\Exceptions\MailException;

/**
 * Minimal SMTP client that sends HTML emails over a raw TCP socket.
 * Reads MAILPIT_HOST / MAILPIT_PORT from the environment — dev transport only.
 * Production emails go through Brevo (EmailService).
 */
class SmtpTransport implements MailTransportInterface
{
    /** @var resource */
    private $connection;

    /**
     * Opens a TCP socket, runs the full SMTP dialogue, sends an HTML email, and closes the socket.
     *
     * @throws MailException if the socket cannot be opened or the server returns an unexpected status code
     */
    public function send(array $from, array $to, string $subject, string $htmlBody): void
    {
        $this->connection = fsockopen($_ENV['MAILPIT_HOST'], (int) $_ENV['MAILPIT_PORT'], $errno, $errstr);
        if(!$this->connection) {
            throw new MailException($errstr, $errno);
        }
        
        $this->checkResponse(220);
        $this->sendCommand("HELO cocoon-signature");
        $this->checkResponse(250);
        $this->sendCommand("MAIL FROM:<" . $from['email'] . ">");
        $this->checkResponse(250);
        $this->sendCommand("RCPT TO:<" . $to['email'] . ">");
        $this->checkResponse(250);
        $this->sendCommand("DATA");
        $this->checkResponse(354);
        $this->sendCommand("Subject: " . $subject);
        $this->sendCommand("MIME-Version: 1.0");
        $this->sendCommand("Content-Type: text/html; charset=UTF-8");
        $this->sendCommand("");
        $this->sendCommand($htmlBody);
        $this->sendCommand(".");
        $this->checkResponse(250);
        $this->sendCommand("QUIT");
        $this->checkResponse(221);

        fclose($this->connection);
    }

    /**
     * Reads one server response line and asserts the expected SMTP status code.
     *
     * @throws MailException if the server code does not match $expectedCode
     */
    private function checkResponse(int $expectedCode): string
    {
        $reponse = fgets($this->connection, 515);
        $code = (int) substr($reponse, 0, 3);

        if($code !== $expectedCode) {
            throw new MailException($reponse);
        }

        return $reponse;
    }

    /** Writes a single SMTP command followed by CRLF to the socket. */
    private function sendCommand(string $command): void
    {
        fwrite($this->connection, $command . "\r\n");
    }
}
