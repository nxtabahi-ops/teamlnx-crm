<?php

declare(strict_types=1);

namespace Relaticle\WhatsApp\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Relaticle\WhatsApp\Models\WhatsAppAccount;
use Relaticle\WhatsApp\Models\WhatsAppMessage;
use Relaticle\WhatsApp\Services\MediaStorageService;

final class DownloadWhatsAppMediaJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $messageId,
        public string $mediaId,
        public string $accountId
    ) {}

    public function handle(MediaStorageService $storageService): void
    {
        $message = WhatsAppMessage::find($this->messageId);
        $account = WhatsAppAccount::find($this->accountId);

        if ($message && $account) {
            $storageService->downloadAndStoreMedia($message, $this->mediaId, $account);
        }
    }
}
