<?php

declare(strict_types=1);

namespace App\Core\Mail;

use App\Core\Exceptions\MailException;

class SmtpTransport
{
    private $connection;

    public function send(string $from, string $to, string $subject, string $htmlBody): void
    {
        $this->connection = fsockopen($_ENV['MAILPIT_HOST'], (int) $_ENV['MAILPIT_PORT'], $errno, $errstr);
        if(!$this->connection) {
            throw new MailException($errstr, $errno);
        }
        
        $this->checkResponse(220);
        $this->sendCommand("HELO cocoon-signature");
        $this->checkResponse(250);
        $this->sendCommand("MAIL FROM:<" . $from . ">");
        $this->checkResponse(250);
        $this->sendCommand("RCPT TO:<" . $to . ">");
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

    private function checkResponse(int $expectedCode): string
    {
        $reponse = fgets($this->connection, 515);
        $code = (int) substr($reponse, 0, 3);

        if($code !== $expectedCode) {
            throw new MailException($reponse);
        }

        return $reponse;
    }

    private function sendCommand(string $command): void
    {
        fwrite($this->connection, $command . "\r\n");
    }
}
