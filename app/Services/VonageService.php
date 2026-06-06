<?php

namespace App\Services;

use Vonage\Client;
use Vonage\Client\Credentials\Basic;
use Vonage\Messages\Channel\SMS\SMSText;
use Vonage\Messages\Channel\WhatsApp\WhatsAppText;
use Illuminate\Support\Facades\Log;

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

        $this->client->messages()->send($sms);
    }

    public function sendWhatsApp(string $to, string $message): void
    {
        $from = config('services.vonage.whatsapp_from');

        if (empty($from)) {
            throw new \RuntimeException('VONAGE_WHATSAPP_FROM não configurado. Defina o número WhatsApp Business no .env.');
        }

        $toFormatted   = $this->formatPhone($to);
        $fromFormatted = preg_replace('/\D/', '', $from);

        Log::debug('VonageService: enviando WhatsApp', [
            'to'      => $toFormatted,
            'from'    => $fromFormatted,
            'sandbox' => config('services.vonage.sandbox'),
        ]);

        $whatsapp = new WhatsAppText($toFormatted, $fromFormatted, $message);

        $messagesClient = $this->client->messages();

        if (config('services.vonage.sandbox')) {
            $messagesClient->getAPIResource()->setBaseUrl('https://messages-sandbox.nexmo.com/v1/messages');
        }

        try {
            $messagesClient->send($whatsapp);
        } catch (\JsonException $e) {
            // O SDK recebeu uma resposta não-JSON da API Vonage.
            // Causa mais comum no sandbox: o número destinatário não está na whitelist.
            // O número precisa enviar "join <keyword>" para +14157386102 no WhatsApp.
            Log::error('VonageService WhatsApp: resposta inválida da API (não-JSON)', [
                'to'      => $toFormatted,
                'sandbox' => config('services.vonage.sandbox'),
                'hint'    => 'Verifique se o número está na whitelist do Vonage Sandbox em: https://dashboard.nexmo.com/messages/sandbox',
                'error'   => $e->getMessage(),
            ]);

            throw new \RuntimeException(
                'Falha ao enviar WhatsApp. ' .
                (config('services.vonage.sandbox')
                    ? 'No modo sandbox, o número destinatário precisa estar na whitelist do Vonage.'
                    : 'Verifique as credenciais e o número remetente no Vonage Dashboard.'),
                0,
                $e
            );
        } catch (\Throwable $e) {
            Log::error('VonageService WhatsApp: erro inesperado', [
                'to'    => $toFormatted,
                'error' => $e->getMessage(),
                'class' => get_class($e),
            ]);

            throw $e;
        }
    }

    private function formatPhone(string $phone): string
    {
        $phone = preg_replace('/\D/', '', $phone);

        if (! str_starts_with($phone, '55')) {
            $phone = '55' . $phone;
        }

        return $phone;
    }
}
