<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Security\FilterInput;
use App\Models\ContactModel;
use App\Services\EmailService;

/** HTTP handler for the public contact form endpoint. */
class ContactController
{
    private EmailService $emailService;

    public function __construct()
    {
        $this->emailService = new EmailService();
    }

    /**
     * Validates and persists a contact message, then notifies the shop by email.
     *
     * Responds 201 on success, 400 on missing/invalid fields or persistence failure.
     *
     * @param object $request Request with body: name, email, subject, content
     */
    public function send(object $request): void
    {
        $body = $request->body;
        $fields = ["name", "email", "subject", "content"];

        $missing = FilterInput::required($fields, $body);
        if (!empty($missing)) {
            http_response_code(400);
            echo json_encode([
                "data" => null,
                "message" => null,
                "error" => [
                    "key" => "api.fields",
                    "params" => ["fields" => implode(", ", $missing)],
                ],
            ]);
            exit();
        }

        if (in_array("email", $fields) && !FilterInput::email($body["email"])) {
            http_response_code(400);
            echo json_encode([
                "data" => null,
                "message" => null,
                "error" => [
                    "key" => "api.valid_mail",
                    "params" => (object) [],
                ],
            ]);
            exit();
        }

        foreach ($body as &$b) {
            if (is_string($b)) {
                $b = FilterInput::sanitize($b);
            }
        }

        if (
            !ContactModel::create(
                $body["name"],
                $body["email"],
                $body["subject"],
                $body["content"],
            )
        ) {
            http_response_code(400);
            echo json_encode([
                "data" => null,
                "message" => null,
                "error" => [
                    "key" => "api.error",
                    "params" => (object) [],
                ],
            ]);
            exit();
        }

        $this->emailService->sendContactMessage(
            $body["name"],
            $body["email"],
            $body["subject"],
            $body["content"],
        );

        http_response_code(201);
        echo json_encode([
            "data" => null,
            "message" => ["key" => "api.send_contact", "params" => (object) []],
            "error" => null,
        ]);
    }
}
