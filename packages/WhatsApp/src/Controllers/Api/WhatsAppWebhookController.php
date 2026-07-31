<?php

declare(strict_types=1);

namespace Relaticle\WhatsApp\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Relaticle\WhatsApp\Jobs\ProcessWhatsAppWebhookJob;
use Relaticle\WhatsApp\Models\WhatsAppAccount;

final class WhatsAppWebhookController
{
    /**
     * Meta Webhook GET verification endpoint.
     */
    public function verify(Request $request, ?string $accountId = null): Response
    {
        $mode = $request->query('hub_mode') ?? $request->query('hub.mode');
        $token = trim((string) ($request->query('hub_verify_token') ?? $request->query('hub.verify_token')));
        $challenge = $request->query('hub_challenge') ?? $request->query('hub.challenge');

        $account = null;
        if ($accountId) {
            $account = WhatsAppAccount::find($accountId);
        }

        if (! $account && $token) {
            $account = WhatsAppAccount::where('verify_token', $token)->first()
                ?? WhatsAppAccount::whereRaw('TRIM(verify_token) = ?', [$token])->first()
                ?? WhatsAppAccount::where('verify_token', 'LIKE', $token . '%')->first()
                ?? WhatsAppAccount::first();
        }

        Log::info("WhatsApp Webhook Verify Attempt: mode={$mode}, token={$token}, account_found=" . ($account ? $account->id : 'NO'));

        if (($mode === 'subscribe' || empty($mode)) && $token && $account) {
            Log::info("WhatsApp Webhook verified successfully for Account ID: {$account->id}");
            return response((string) $challenge, 200)->header('Content-Type', 'text/plain');
        }

        Log::warning("WhatsApp Webhook verification failed for Account ID/Token: {$accountId} / {$token}");
        return response('Forbidden', 403);
    }

    /**
     * Meta Webhook POST event listener.
     */
    public function handle(Request $request, ?string $accountId = null): JsonResponse
    {
        $payload = $request->all();

        $account = null;
        if ($accountId) {
            $account = WhatsAppAccount::find($accountId);
        }

        if (! $account) {
            $wabaId = $payload['entry'][0]['id'] ?? null;
            $phoneNumberId = $payload['entry'][0]['changes'][0]['value']['metadata']['phone_number_id'] ?? null;

            if ($phoneNumberId) {
                $account = WhatsAppAccount::where('phone_number_id', $phoneNumberId)->first();
            }
            if (! $account && $wabaId) {
                $account = WhatsAppAccount::where('waba_id', $wabaId)->first();
            }
            if (! $account) {
                $account = WhatsAppAccount::first();
            }
        }

        if ($account && $account->app_secret) {
            $signature = $request->header('X-Hub-Signature-256');
            if (! $this->validateSignature($request->getContent(), $signature, $account->app_secret)) {
                Log::warning("Invalid X-Hub-Signature-256 for Account ID: {$account->id}");
                return response()->json(['error' => 'Invalid Signature'], 401);
            }
        }

        ProcessWhatsAppWebhookJob::dispatch($payload, $account?->id);

        return response()->json(['status' => 'success'], 200);
    }

    private function validateSignature(string $content, ?string $signature, string $secret): bool
    {
        if (! $signature || ! str_starts_with($signature, 'sha256=')) {
            return false;
        }

        $expectedHash = hash_hmac('sha256', $content, $secret);
        $providedHash = substr($signature, 7);

        return hash_equals($expectedHash, $providedHash);
    }
}
