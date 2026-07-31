<?php

declare(strict_types=1);

namespace App\Services\WhatsApp;

use App\Models\WhatsAppBroadcast;
use App\Models\WhatsAppContact;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;

final class BroadcastEngineService
{
    public function dispatchBroadcast(WhatsAppBroadcast $broadcast): void
    {
        $broadcast->update(['status' => 'processing']);

        $query = WhatsAppContact::where('team_id', $broadcast->team_id);

        if (!empty($broadcast->target_tag_ids)) {
            $query->whereHas('conversations.tags', function ($q) use ($broadcast): void {
                $q->whereIn('whatsapp_tags.id', $broadcast->target_tag_ids);
            });
        }

        $contacts = $query->get();
        $broadcast->update(['total_recipients' => $contacts->count()]);

        $cloudApiService = resolve(WhatsAppCloudApiService::class);
        $account = $broadcast->account;
        $template = $broadcast->template;

        $successCount = 0;
        $failedCount = 0;

        foreach ($contacts as $contact) {
            $conversation = WhatsAppConversation::firstOrCreate(
                [
                    'team_id' => $broadcast->team_id,
                    'whatsapp_account_id' => $account->id,
                    'whatsapp_contact_id' => $contact->id,
                ],
                ['status' => 'open']
            );

            $res = $cloudApiService->sendTemplateMessage(
                $account,
                $contact->phone_number,
                $template->name,
                $template->language,
                []
            );

            if ($res['success']) {
                $successCount++;
                WhatsAppMessage::create([
                    'team_id' => $broadcast->team_id,
                    'conversation_id' => $conversation->id,
                    'wamid' => $res['wamid'],
                    'direction' => 'outbound',
                    'sender_type' => 'system',
                    'type' => 'template',
                    'body' => "Broadcast Template: {$template->name}",
                    'status' => 'sent',
                    'sent_at' => now(),
                ]);
            } else {
                $failedCount++;
                WhatsAppMessage::create([
                    'team_id' => $broadcast->team_id,
                    'conversation_id' => $conversation->id,
                    'direction' => 'outbound',
                    'sender_type' => 'system',
                    'type' => 'template',
                    'body' => "Broadcast Template: {$template->name}",
                    'status' => 'failed',
                    'error_code' => $res['error_code'] ?? 'ERR',
                    'error_message' => $res['error_message'] ?? 'Failed',
                ]);
            }
        }

        $broadcast->update([
            'status' => 'completed',
            'successful_count' => $successCount,
            'failed_count' => $failedCount,
            'completed_at' => now(),
        ]);
    }
}
