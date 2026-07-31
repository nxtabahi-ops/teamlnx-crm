<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\WhatsAppAccount;
use App\Models\WhatsAppMessage;
use App\Services\WhatsApp\MediaStorageService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

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
