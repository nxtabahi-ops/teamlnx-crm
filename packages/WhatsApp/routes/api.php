<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Relaticle\WhatsApp\Controllers\Api\WhatsAppWebhookController;

Route::prefix('api/whatsapp')->group(function (): void {
    Route::get('webhook/{accountId}', [WhatsAppWebhookController::class, 'verify'])->name('whatsapp.webhook.verify');
    Route::post('webhook/{accountId}', [WhatsAppWebhookController::class, 'handle'])->name('whatsapp.webhook.handle');
});
