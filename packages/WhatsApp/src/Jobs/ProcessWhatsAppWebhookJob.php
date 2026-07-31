<?php

declare(strict_types=1);

namespace Relaticle\WhatsApp\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Relaticle\WhatsApp\Models\WhatsAppAccount;
use Relaticle\WhatsApp\Services\WebhookIngestionService;

final class ProcessWhatsAppWebhookJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public array $payload,
        public ?string $accountId = null
    ) {}

    public function handle(WebhookIngestionService $service): void
    {
        $account = null;
        if ($this->accountId) {
            $account = WhatsAppAccount::find($this->accountId);
        }

        if (! $account) {
            $wabaId = $this->payload['entry'][0]['id'] ?? null;
            $phoneNumberId = $this->payload['entry'][0]['changes'][0]['value']['metadata']['phone_number_id'] ?? null;

            if ($phoneNumberId) {
                $account = WhatsAppAccount::where('phone_number_id', $phoneNumberId)->first();
            }
            if (! $account && $wabaId) {
                $account = WhatsAppAccount::where('waba_id', $wabaId)->first();
            }
            if (! $account) {
                $account = WhatsAppAccount::first();
            }
        }

        if (! $account) {
            Log::error('ProcessWhatsAppWebhookJob: No WhatsApp account found for payload.');
            return;
        }

        $service->processPayload($this->payload, $account);
    }
}
