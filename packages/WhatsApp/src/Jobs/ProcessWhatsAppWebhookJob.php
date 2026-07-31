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
        public string $accountId
    ) {}

    public function handle(WebhookIngestionService $service): void
    {
        $account = WhatsAppAccount::find($this->accountId);
        if (!$account) {
            Log::error("ProcessWhatsAppWebhookJob: WhatsApp account not found for ID {$this->accountId}");
            return;
        }

        $service->processPayload($this->payload, $account);
    }
}
