<?php

declare(strict_types=1);

namespace Relaticle\WhatsApp\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Relaticle\WhatsApp\Models\WhatsAppAccount;

final class WhatsAppCloudApiService
{
    private string $baseUrl = 'https://graph.facebook.com/v21.0';

    /**
     * Send text message via Meta Graph API.
     */
    public function sendTextMessage(WhatsAppAccount $account, string $recipientPhone, string $text): array
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $this->formatPhoneNumber($recipientPhone),
            'type' => 'text',
            'text' => [
                'preview_url' => true,
                'body' => $text,
            ],
        ];

        return $this->postMessage($account, $payload);
    }

    /**
     * Send media message (image, video, document, audio, sticker).
     */
    public function sendMediaMessage(WhatsAppAccount $account, string $recipientPhone, string $mediaType, string $mediaUrlOrId, ?string $caption = null, ?string $filename = null): array
    {
        $mediaPayload = filter_var($mediaUrlOrId, FILTER_VALIDATE_URL)
            ? ['link' => $mediaUrlOrId]
            : ['id' => $mediaUrlOrId];

        if ($caption && in_array($mediaType, ['image', 'video', 'document'], true)) {
            $mediaPayload['caption'] = $caption;
        }

        if ($filename && $mediaType === 'document') {
            $mediaPayload['filename'] = $filename;
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $this->formatPhoneNumber($recipientPhone),
            'type' => $mediaType,
            $mediaType => $mediaPayload,
        ];

        return $this->postMessage($account, $payload);
    }

    /**
     * Send Meta Approved HSM Template message.
     */
    public function sendTemplateMessage(WhatsAppAccount $account, string $recipientPhone, string $templateName, string $languageCode = 'en_US', array $components = []): array
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $this->formatPhoneNumber($recipientPhone),
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => [
                    'code' => $languageCode,
                ],
                'components' => $components,
            ],
        ];

        return $this->postMessage($account, $payload);
    }

    /**
     * Fetch Media download URL from Meta using Media ID.
     */
    public function getMediaUrl(WhatsAppAccount $account, string $mediaId): ?string
    {
        $response = Http::withToken($account->access_token)
            ->get("{$this->baseUrl}/{$mediaId}");

        if ($response->successful()) {
            return $response->json('url');
        }

        Log::error("Failed to fetch Meta media URL for ID {$mediaId}", [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        return null;
    }

    /**
     * Helper POST dispatcher.
     */
    private function postMessage(WhatsAppAccount $account, array $payload): array
    {
        $url = "{$this->baseUrl}/{$account->phone_number_id}/messages";

        $response = Http::withToken($account->access_token)
            ->acceptJson()
            ->post($url, $payload);

        if (!$response->successful()) {
            Log::error("WhatsApp Cloud API Error for Account {$account->id}", [
                'payload' => $payload,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [
                'success' => false,
                'error_code' => (string) $response->json('error.code', 'HTTP_' . $response->status()),
                'error_message' => $response->json('error.message', $response->body()),
            ];
        }

        $wamid = $response->json('messages.0.id');

        return [
            'success' => true,
            'wamid' => $wamid,
            'response' => $response->json(),
        ];
    }

    private function formatPhoneNumber(string $phone): string
    {
        return preg_replace('/\D/', '', $phone);
    }
}
