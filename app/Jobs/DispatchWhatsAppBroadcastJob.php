<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\WhatsAppBroadcast;
use App\Services\WhatsApp\BroadcastEngineService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class DispatchWhatsAppBroadcastJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $broadcastId
    ) {}

    public function handle(BroadcastEngineService $engineService): void
    {
        $broadcast = WhatsAppBroadcast::find($this->broadcastId);
        if ($broadcast) {
            $engineService->dispatchBroadcast($broadcast);
        }
    }
}
