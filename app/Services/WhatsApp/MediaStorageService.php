<?php

declare(strict_types=1);

namespace App\Services\WhatsApp;

use App\Models\WhatsAppAccount;
use App\Models\WhatsAppMessage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class MediaStorageService
{
    public function downloadAndStoreMedia(WhatsAppMessage $message, string $mediaId, WhatsAppAccount $account): bool
    {
        try {
            $cloudApiService = resolve(WhatsAppCloudApiService::class);
            $downloadUrl = $cloudApiService->getMediaUrl($account, $mediaId);

            if (!$downloadUrl) {
                return false;
            }

            $response = Http::withToken($account->access_token)->get($downloadUrl);

            if (!$response->successful()) {
                Log::error("Failed to download raw media binary from Meta for message {$message->id}", [
                    'media_id' => $mediaId,
                    'status' => $response->status(),
                ]);
                return false;
            }

            $contentType = $response->header('Content-Type', 'application/octet-stream');
            $extension = $this->getExtensionFromMime($contentType, $message->type);
            $filename = "media_" . Str::random(20) . "." . $extension;

            $relativeStoragePath = "whatsapp_media/{$message->team_id}/{$filename}";

            Storage::disk('public')->put($relativeStoragePath, $response->body());

            $message->update([
                'media_url' => Storage::disk('public')->url($relativeStoragePath),
                'media_mime_type' => $contentType,
                'media_size_bytes' => strlen($response->body()),
                'media_filename' => $filename,
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::error("MediaStorageService exception: " . $e->getMessage(), [
                'message_id' => $message->id,
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }

    private function getExtensionFromMime(string $mime, string $type): string
    {
        $mimeMap = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'video/mp4' => 'mp4',
            'audio/ogg' => 'ogg',
            'audio/opus' => 'opus',
            'audio/mpeg' => 'mp3',
            'application/pdf' => 'pdf',
        ];

        if (isset($mimeMap[$mime])) {
            return $mimeMap[$mime];
        }

        return match ($type) {
            'image' => 'jpg',
            'video' => 'mp4',
            'voice', 'audio' => 'ogg',
            'sticker' => 'webp',
            'document' => 'pdf',
            default => 'bin',
        };
    }
}
