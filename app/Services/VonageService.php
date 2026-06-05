<?php

namespace App\Services;

use Vonage\Client;
use Vonage\Client\Credentials\Basic;
use Vonage\Messages\Channel\SMS\SMSText;
use Vonage\Messages\Channel\WhatsApp\WhatsAppText;

class VonageService
{
    private Client $client;

    public function __construct()
    {
        $credentials = new Basic(
            config('services.vonage.key'),
            config('services.vonage.secret')
        );

        $this->client = new Client($credentials);
    }

    public function sendSms(string $to, string $message): void
    {
        $sms = new SMSText(
            $this->formatPhone($to),
            config('services.vonage.sms_from'),
            $message
        );

        // SMS always uses the production endpoint
        $this->client->messages()->send($sms);
    }

    public function sendWhatsApp(string $to, string $message): void
    {
        $from = config('services.vonage.whatsapp_from');

        if (empty($from)) {
            throw new \RuntimeException('VONAGE_WHATSAPP_FROM is not configured. Set a WhatsApp Business number in your .env file.');
        }

        $whatsapp = new WhatsAppText(
            $this->formatPhone($to),
            preg_replace('/\D/', '', $from),
            $message
        );

        $messagesClient = $this->client->messages();

        // Sandbox endpoint only applies to WhatsApp/Viber, not SMS
        if (config('services.vonage.sandbox')) {
            $messagesClient->getAPIResource()->setBaseUrl('https://messages-sandbox.nexmo.com');
        }

        $messagesClient->send($whatsapp);
    }

    private function formatPhone(string $phone): string
    {
        $phone = preg_replace('/\D/', '', $phone);

        // Add Brazilian country code if not present
        if (! str_starts_with($phone, '55')) {
            $phone = '55' . $phone;
        }

        return $phone;
    }
}
