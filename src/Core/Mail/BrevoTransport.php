<?php

declare(strict_types=1);

namespace App\Core\Mail;

use App\Core\Mail\MailTransportInterface;
use App\Core\Exceptions\MailException;
use Brevo\Brevo;
use Brevo\TransactionalEmails\Requests\SendTransacEmailRequest;
use Brevo\TransactionalEmails\Types\SendTransacEmailRequestSender;
use Brevo\TransactionalEmails\Types\SendTransacEmailRequestToItem;
use Brevo\Exceptions\BrevoApiException;


class BrevoTransport implements MailTransportInterface
{
    private Brevo $brevo;

    public function __construct()
    {
        $this->brevo = new Brevo($_ENV['BREVO_API_KEY']);
    }

    public function send(array $from, array $to, string $subject, string $htmlBody): void
    {
        $request = new SendTransacEmailRequest([
            'subject'     => $subject,
            'htmlContent' => $htmlBody,
            'sender'      => new SendTransacEmailRequestSender([
                'email' => $from['email'],
                'name'  => $from['name']
            ]),
            'to'          => [new SendTransacEmailRequestToItem([
                'email' => $to['email'],
                'name'  => $to['name']
            ]),]
        ]);

        try {
            $this->brevo->transactionalEmails->sendTransacEmail($request);
        } catch (BrevoApiException $e) {
            throw new MailException($e->getBody(), $e->getCode());
        }
    }
}
