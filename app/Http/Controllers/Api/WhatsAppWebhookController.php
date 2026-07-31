<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessWhatsAppWebhookJob;
use App\Models\WhatsAppAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

final class WhatsAppWebhookController extends Controller
{
    /**
     * Meta Webhook GET verification endpoint.
     */
    public function verify(Request $request, string $accountId): Response
    {
        $account = WhatsAppAccount::find($accountId);
        if (!$account) {
            return response('Account Not Found', 404);
        }

        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        if ($mode === 'subscribe' && $token === $account->verify_token) {
            Log::info("WhatsApp Webhook verified for Account ID: {$accountId}");
            return response($challenge, 200);
        }

        Log::warning("WhatsApp Webhook verification failed for Account ID: {$accountId}");
        return response('Forbidden', 403);
    }

    /**
     * Meta Webhook POST event listener.
     */
    public function handle(Request $request, string $accountId): JsonResponse
    {
        $account = WhatsAppAccount::find($accountId);
        if (!$account) {
            return response()->json(['error' => 'Account Not Found'], 404);
        }

        // Validate X-Hub-Signature-256 if app_secret is configured
        if ($account->app_secret) {
            $signature = $request->header('X-Hub-Signature-256');
            if (!$this->validateSignature($request->getContent(), $signature, $account->app_secret)) {
                Log::warning("Invalid X-Hub-Signature-256 for Account ID: {$accountId}");
                return response()->json(['error' => 'Invalid Signature'], 401);
            }
        }

        $payload = $request->all();

        // Dispatch async job for non-blocking HTTP 200 response
        ProcessWhatsAppWebhookJob::dispatch($payload, $account->id);

        return response()->json(['status' => 'success'], 200);
    }

    private function validateSignature(string $content, ?string $signature, string $secret): bool
    {
        if (!$signature || !str_starts_with($signature, 'sha256=')) {
            return false;
        }

        $expectedHash = hash_hmac('sha256', $content, $secret);
        $providedHash = substr($signature, 7);

        return hash_equals($expectedHash, $providedHash);
    }
}
