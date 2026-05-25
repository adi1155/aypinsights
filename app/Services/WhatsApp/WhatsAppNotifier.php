<?php

namespace App\Services\WhatsApp;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Hook for WhatsApp Business API / Twilio integration.
 * Configure WHATSAPP_WEBHOOK_URL in .env to enable outbound alerts.
 */
class WhatsAppNotifier
{
    public function send(string $to, string $message): bool
    {
        $url = config('services.whatsapp.webhook_url');

        if (! $url) {
            Log::info('WhatsApp hook (dry-run)', compact('to', 'message'));

            return false;
        }

        $response = Http::post($url, ['to' => $to, 'message' => $message]);

        return $response->successful();
    }
}
