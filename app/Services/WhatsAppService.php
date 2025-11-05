<?php

namespace App\Services;

use Twilio\Rest\Client;
use Exception;

class WhatsAppService
{
    protected $client;
    protected $fromNumber;

    public function __construct()
    {
        $this->client = new Client(
            config('services.twilio.account_sid'),
            config('services.twilio.auth_token')
        );
        $this->fromNumber = config('services.twilio.whatsapp_number');
    }

    /**
     * Envoyer un message WhatsApp
     */
    public function sendMessage(string $to, string $message): array
    {
        try {
            // Formater le numéro (ajouter whatsapp: prefix)
            $formattedTo = $this->formatPhoneNumber($to);

            $twilioMessage = $this->client->messages->create(
                $formattedTo,
                [
                    'from' => $this->fromNumber,
                    'body' => $message
                ]
            );

            return [
                'success' => true,
                'message_id' => $twilioMessage->sid,
                'status' => $twilioMessage->status
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Envoyer un message avec template WhatsApp
     */
    public function sendTemplateMessage(string $to, string $templateName, array $variables = []): array
    {
        try {
            $formattedTo = $this->formatPhoneNumber($to);

            $twilioMessage = $this->client->messages->create(
                $formattedTo,
                [
                    'from' => $this->fromNumber,
                    'contentSid' => $templateName,
                    'contentVariables' => json_encode($variables)
                ]
            );

            return [
                'success' => true,
                'message_id' => $twilioMessage->sid,
                'status' => $twilioMessage->status
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Formater le numéro de téléphone pour WhatsApp
     */
    protected function formatPhoneNumber(string $phone): string
    {
        // Enlever les espaces et caractères spéciaux
        $phone = preg_replace('/[^0-9+]/', '', $phone);

        // Ajouter +221 si c'est un numéro sénégalais sans indicatif
        if (strlen($phone) === 9 && !str_starts_with($phone, '+')) {
            $phone = '+221' . $phone;
        }

        // Ajouter le prefix whatsapp:
        if (!str_starts_with($phone, 'whatsapp:')) {
            $phone = 'whatsapp:' . $phone;
        }

        return $phone;
    }

    /**
     * Vérifier le statut d'un message
     */
    public function getMessageStatus(string $messageSid): ?string
    {
        try {
            $message = $this->client->messages($messageSid)->fetch();
            return $message->status;
        } catch (Exception $e) {
            return null;
        }
    }
}