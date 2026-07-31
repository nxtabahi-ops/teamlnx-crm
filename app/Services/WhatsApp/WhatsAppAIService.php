<?php

declare(strict_types=1);

namespace App\Services\WhatsApp;

use App\Models\WhatsAppConversation;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class WhatsAppAIService
{
    /**
     * Summarize current chat conversation using AI.
     */
    public function summarizeConversation(WhatsAppConversation $conversation): string
    {
        $messages = $conversation->messages()
            ->latest('created_at')
            ->take(30)
            ->get()
            ->reverse();

        if ($messages->isEmpty()) {
            return "No message history available to summarize.";
        }

        $formattedChat = "";
        foreach ($messages as $msg) {
            $sender = $msg->direction === 'inbound' ? 'Customer' : 'Agent';
            $formattedChat .= "{$sender}: {$msg->body}\n";
        }

        $prompt = "Summarize the following WhatsApp customer conversation into bullet points covering:\n"
            . "1. Key customer inquiry / request\n"
            . "2. Current status\n"
            . "3. Recommended next action step for the agent.\n\n"
            . "Conversation:\n{$formattedChat}";

        return $this->callAiProvider($prompt);
    }

    /**
     * Generate smart reply suggestions.
     */
    public function generateReplySuggestions(WhatsAppConversation $conversation): array
    {
        $lastMessages = $conversation->messages()
            ->latest('created_at')
            ->take(10)
            ->get()
            ->reverse();

        $formattedChat = "";
        foreach ($lastMessages as $msg) {
            $sender = $msg->direction === 'inbound' ? 'Customer' : 'Agent';
            $formattedChat .= "{$sender}: {$msg->body}\n";
        }

        $prompt = "Based on this customer chat history, generate 3 distinct response suggestions for the agent.\n"
            . "Format as JSON array of 3 strings: [\"reply 1\", \"reply 2\", \"reply 3\"]\n\n"
            . "Chat history:\n{$formattedChat}";

        $response = $this->callAiProvider($prompt);

        $decoded = json_decode($response, true);
        if (is_array($decoded)) {
            return array_slice($decoded, 0, 3);
        }

        return [
            "Thank you for contacting us! How can I assist you today?",
            "I have noted your request and will update you shortly.",
            "Could you please share more details regarding your query?",
        ];
    }

    /**
     * Call AI endpoint (OpenAI / Gemini fallback).
     */
    private function callAiProvider(string $prompt): string
    {
        $apiKey = config('services.openai.api_key') ?? env('OPENAI_API_KEY');

        if (!$apiKey) {
            return "AI service configured without API key. Please set OPENAI_API_KEY in environment.";
        }

        try {
            $response = Http::withToken($apiKey)
                ->timeout(15)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => 'gpt-4o-mini',
                    'messages' => [
                        ['role' => 'system', 'content' => 'You are an expert CRM WhatsApp assistant.'],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'temperature' => 0.5,
                ]);

            if ($response->successful()) {
                return trim($response->json('choices.0.message.content', 'Unable to generate summary.'));
            }
        } catch (\Throwable $e) {
            Log::error("WhatsAppAIService exception: " . $e->getMessage());
        }

        return "AI response unavailable. Please check server logs.";
    }
}
