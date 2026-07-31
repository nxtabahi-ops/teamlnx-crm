<?php

declare(strict_types=1);

namespace Relaticle\WhatsApp\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Relaticle\WhatsApp\Models\WhatsAppMessage;
use Relaticle\WhatsApp\Services\WhatsAppCloudApiService;

final class SendWhatsAppMessageJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $messageId
    ) {}

    public function handle(WhatsAppCloudApiService $apiService): void
    {
        $message = WhatsAppMessage::with(['conversation.account', 'conversation.contact'])->find($this->messageId);
        if (!$message || $message->direction !== 'outbound' || $message->status !== 'pending') {
            return;
        }

        $account = $message->conversation->account;
        $contact = $message->conversation->contact;

        if (!$account || !$contact) {
            $message->update([
                'status' => 'failed',
                'error_message' => 'Account or Contact missing',
            ]);
            return;
        }

        if ($message->type === 'text') {
            $result = $apiService->sendTextMessage($account, $contact->phone_number, $message->body ?? '');
        } else {
            $result = $apiService->sendMediaMessage(
                $account,
                $contact->phone_number,
                $message->type,
                $message->media_url ?? '',
                $message->body,
                $message->media_filename
            );
        }

        if ($result['success']) {
            $message->update([
                'status' => 'sent',
                'wamid' => $result['wamid'],
                'sent_at' => now(),
            ]);
        } else {
            $message->update([
                'status' => 'failed',
                'error_code' => $result['error_code'] ?? 'ERR',
                'error_message' => $result['error_message'] ?? 'Sending failed',
            ]);
        }
    }
}
