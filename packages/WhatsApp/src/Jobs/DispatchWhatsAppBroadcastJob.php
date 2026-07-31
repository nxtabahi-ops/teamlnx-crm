<?php

declare(strict_types=1);

namespace Relaticle\WhatsApp\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Relaticle\WhatsApp\Models\WhatsAppBroadcast;
use Relaticle\WhatsApp\Services\BroadcastEngineService;

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
