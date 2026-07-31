<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\WhatsAppAccount;
use App\Services\WhatsApp\WebhookIngestionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

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
