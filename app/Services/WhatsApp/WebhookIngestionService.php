<?php

declare(strict_types=1);

namespace App\Services\WhatsApp;

use App\Enums\CreationSource;
use App\Jobs\DownloadWhatsAppMediaJob;
use App\Models\People;
use App\Models\WhatsAppAccount;
use App\Models\WhatsAppContact;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use App\Models\WhatsAppWebhookLog;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class WebhookIngestionService
{
    public function processPayload(array $payload, WhatsAppAccount $account): void
    {
        // 1. Audit log raw event
        WhatsAppWebhookLog::create([
            'whatsapp_account_id' => $account->id,
            'event_type' => $payload['entry'][0]['changes'][0]['field'] ?? 'unknown',
            'payload' => $payload,
            'status' => 'processed',
        ]);

        $entries = $payload['entry'] ?? [];

        foreach ($entries as $entry) {
            $changes = $entry['changes'] ?? [];
            foreach ($changes as $change) {
                $value = $change['value'] ?? [];
                
                // Process inbound messages
                if (!empty($value['messages'])) {
                    $contacts = $value['contacts'] ?? [];
                    $messages = $value['messages'];
                    
                    foreach ($messages as $msgData) {
                        $this->handleInboundMessage($account, $msgData, $contacts);
                    }
                }

                // Process message delivery status callbacks
                if (!empty($value['statuses'])) {
                    $statuses = $value['statuses'];
                    foreach ($statuses as $statusData) {
                        $this->handleStatusUpdate($account, $statusData);
                    }
                }
            }
        }
    }

    private function handleInboundMessage(WhatsAppAccount $account, array $msgData, array $contacts): void
    {
        DB::transaction(function () use ($account, $msgData, $contacts): void {
            $wamid = $msgData['id'] ?? null;

            // Deduplicate message processing
            if ($wamid && WhatsAppMessage::where('wamid', $wamid)->exists()) {
                return;
            }

            $senderWaid = $msgData['from'] ?? '';
            $senderPhone = '+' . preg_replace('/\D/', '', $senderWaid);

            // Resolve contact profile name
            $profileName = $senderPhone;
            foreach ($contacts as $c) {
                if (($c['wa_id'] ?? '') === $senderWaid) {
                    $profileName = $c['profile']['name'] ?? $senderPhone;
                    break;
                }
            }

            // 1. Auto-create or find CRM People record in Relaticle
            $people = People::where('team_id', $account->team_id)
                ->where(function ($query) use ($senderPhone, $senderWaid): void {
                    // Match by custom field phone or name
                    $query->where('name', 'like', "%{$senderWaid}%")
                          ->orWhere('name', 'like', "%{$senderPhone}%");
                })
                ->first();

            if (!$people) {
                $people = People::create([
                    'team_id' => $account->team_id,
                    'name' => $profileName,
                    'creation_source' => CreationSource::WEB,
                ]);
            }

            // 2. Find or create WhatsAppContact
            $waContact = WhatsAppContact::firstOrCreate(
                [
                    'team_id' => $account->team_id,
                    'wa_id' => $senderWaid,
                ],
                [
                    'people_id' => $people->id,
                    'phone_number' => $senderPhone,
                    'profile_name' => $profileName,
                ]
            );

            // 3. Find or create WhatsAppConversation
            $conversation = WhatsAppConversation::firstOrCreate(
                [
                    'team_id' => $account->team_id,
                    'whatsapp_account_id' => $account->id,
                    'whatsapp_contact_id' => $waContact->id,
                ],
                [
                    'status' => 'open',
                    'unread_count' => 0,
                ]
            );

            // Extract message content based on type
            $msgType = $msgData['type'] ?? 'text';
            $body = null;
            $mediaId = null;
            $lat = null;
            $lng = null;
            $locName = null;
            $payload = $msgData;

            switch ($msgType) {
                case 'text':
                    $body = $msgData['text']['body'] ?? '';
                    break;

                case 'image':
                    $body = $msgData['image']['caption'] ?? '[Image]';
                    $mediaId = $msgData['image']['id'] ?? null;
                    break;

                case 'video':
                    $body = $msgData['video']['caption'] ?? '[Video]';
                    $mediaId = $msgData['video']['id'] ?? null;
                    break;

                case 'audio':
                case 'voice':
                    $body = '[Voice Note]';
                    $mediaId = $msgData[$msgType]['id'] ?? null;
                    break;

                case 'document':
                    $body = $msgData['document']['caption'] ?? $msgData['document']['filename'] ?? '[Document]';
                    $mediaId = $msgData['document']['id'] ?? null;
                    break;

                case 'sticker':
                    $body = '[Sticker]';
                    $mediaId = $msgData['sticker']['id'] ?? null;
                    break;

                case 'location':
                    $lat = $msgData['location']['latitude'] ?? null;
                    $lng = $msgData['location']['longitude'] ?? null;
                    $locName = $msgData['location']['name'] ?? $msgData['location']['address'] ?? 'Shared Location';
                    $body = "📍 Location: {$locName} ({$lat}, {$lng})";
                    break;

                case 'contacts':
                    $body = '[Contact Cards]';
                    break;

                default:
                    $body = "[{$msgType} message]";
                    break;
            }

            // 4. Save WhatsAppMessage record
            $message = WhatsAppMessage::create([
                'team_id' => $account->team_id,
                'conversation_id' => $conversation->id,
                'wamid' => $wamid,
                'direction' => 'inbound',
                'sender_type' => 'contact',
                'type' => $msgType,
                'body' => $body,
                'latitude' => $lat,
                'longitude' => $lng,
                'location_name' => $locName,
                'payload' => $payload,
                'status' => 'delivered',
                'delivered_at' => now(),
            ]);

            // Update conversation metadata
            $conversation->update([
                'unread_count' => $conversation->unread_count + 1,
                'last_message_at' => now(),
                'last_message_preview' => Str::limit($body, 100),
                'window_expires_at' => now()->addHours(24),
            ]);

            // Dispatch media download job if applicable
            if ($mediaId) {
                DownloadWhatsAppMediaJob::dispatch($message->id, $mediaId, $account->id);
            }
        });
    }

    private function handleStatusUpdate(WhatsAppAccount $account, array $statusData): void
    {
        $wamid = $statusData['id'] ?? null;
        $statusStr = $statusData['status'] ?? null; // sent, delivered, read, failed

        if (!$wamid || !$statusStr) {
            return;
        }

        $message = WhatsAppMessage::where('wamid', $wamid)->first();
        if (!$message) {
            return;
        }

        $updates = ['status' => $statusStr];

        if ($statusStr === 'sent') {
            $updates['sent_at'] = now();
        } elseif ($statusStr === 'delivered') {
            $updates['delivered_at'] = now();
        } elseif ($statusStr === 'read') {
            $updates['read_at'] = now();
        } elseif ($statusStr === 'failed') {
            $updates['error_code'] = (string) ($statusData['errors'][0]['code'] ?? 'META_ERR');
            $updates['error_message'] = $statusData['errors'][0]['title'] ?? 'Delivery failed';
        }

        $message->update($updates);
    }
}
