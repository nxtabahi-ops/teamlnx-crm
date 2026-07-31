<?php

declare(strict_types=1);

namespace Relaticle\WhatsApp\Filament\Pages;

use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\WithFileUploads;
use Relaticle\WhatsApp\Jobs\SendWhatsAppMessageJob;
use Relaticle\WhatsApp\Models\WhatsAppConversation;
use Relaticle\WhatsApp\Models\WhatsAppMessage;
use Relaticle\WhatsApp\Models\WhatsAppNote;
use Relaticle\WhatsApp\Models\WhatsAppTag;
use Relaticle\WhatsApp\Services\WhatsAppAIService;
use Relaticle\WhatsApp\Services\WhatsAppCloudApiService;

final class WhatsAppInbox extends Page
{
    use WithFileUploads;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationLabel = 'WhatsApp Shared Inbox';

    protected static ?int $navigationSort = 1;

    protected string $view = 'whatsapp::filament.pages.whats-app-inbox';

    public static function canAccess(): bool
    {
        return true;
    }

    // State properties
    public ?string $selectedConversationId = null;
    public string $searchQuery = '';
    public string $selectedStatus = 'all'; // open, pending, resolved, archived, all
    public ?string $selectedTagId = null;
    public string $replyText = '';
    public $attachment = null;
    public string $newNoteText = '';
    public ?string $aiSummary = null;
    public array $aiReplySuggestions = [];

    protected $queryString = [
        'selectedConversationId' => ['except' => ''],
        'selectedStatus' => ['except' => 'all'],
    ];

    public function mount(): void
    {
        $firstConv = $this->getConversationsProperty()->first();
        if ($firstConv && !$this->selectedConversationId) {
            $this->selectedConversationId = $firstConv->id;
        }
    }

    private function getTeamId(): ?string
    {
        return Filament::getTenant()?->getKey()
            ?? Auth::user()?->currentTeam?->getKey()
            ?? Auth::user()?->current_team_id;
    }

    public function getConversationsProperty(): Collection
    {
        $teamId = $this->getTeamId();
        if (!$teamId) {
            return collect();
        }

        return WhatsAppConversation::query()
            ->where('team_id', $teamId)
            ->when($this->selectedStatus !== 'all', fn ($q) => $q->where('status', $this->selectedStatus))
            ->when($this->selectedTagId, fn ($q) => $q->whereHas('tags', fn ($t) => $t->where('whatsapp_tags.id', $this->selectedTagId)))
            ->when($this->searchQuery, function ($q): void {
                $q->whereHas('contact', function ($c): void {
                    $c->where('profile_name', 'like', "%{$this->searchQuery}%")
                      ->orWhere('phone_number', 'like', "%{$this->searchQuery}%");
                })->orWhere('last_message_preview', 'like', "%{$this->searchQuery}%");
            })
            ->with(['contact.people', 'tags', 'assignedUser'])
            ->orderBy('last_message_at', 'desc')
            ->get();
    }

    public function getActiveConversationProperty(): ?WhatsAppConversation
    {
        if (!$this->selectedConversationId) {
            return null;
        }

        $conv = WhatsAppConversation::with([
            'contact.people',
            'account',
            'assignedUser',
            'tags',
            'notes.user',
            'messages' => fn ($q) => $q->orderBy('created_at', 'asc'),
        ])->find($this->selectedConversationId);

        if ($conv && $conv->unread_count > 0) {
            $conv->update(['unread_count' => 0]);
        }

        return $conv;
    }

    public function getTagsProperty(): Collection
    {
        $teamId = $this->getTeamId();
        return $teamId ? WhatsAppTag::where('team_id', $teamId)->get() : collect();
    }

    public function getTeamUsersProperty(): Collection
    {
        $team = Auth::user()?->currentTeam;
        return $team ? $team->allUsers() : collect();
    }

    public function selectConversation(string $id): void
    {
        $this->selectedConversationId = $id;
        $this->aiSummary = null;
        $this->aiReplySuggestions = [];
        $this->attachment = null;
    }

    public function removeAttachment(): void
    {
        $this->attachment = null;
    }

    public function sendReply(): void
    {
        if (empty(trim($this->replyText)) && !$this->attachment) {
            $this->validate([
                'replyText' => 'required|string|min:1',
            ]);
            return;
        }

        $conversation = $this->activeConversation;
        if (!$conversation) {
            return;
        }

        $msgType = 'text';
        $mediaUrl = null;
        $mime = null;
        $filename = null;

        if ($this->attachment) {
            $mime = $this->attachment->getMimeType();
            $filename = $this->attachment->getClientOriginalName();
            $msgType = 'document';

            if (str_starts_with($mime, 'image/')) {
                $msgType = 'image';
            } elseif (str_starts_with($mime, 'video/')) {
                $msgType = 'video';
            } elseif (str_starts_with($mime, 'audio/')) {
                $msgType = 'audio';
            }

            $path = $this->attachment->store('whatsapp-attachments', 'public');
            $mediaUrl = asset('storage/' . $path);
        }

        $bodyText = !empty(trim($this->replyText)) ? trim($this->replyText) : ($filename ?? 'Attachment');

        $message = WhatsAppMessage::create([
            'team_id' => $conversation->team_id,
            'conversation_id' => $conversation->id,
            'direction' => 'outbound',
            'sender_type' => 'user',
            'sender_user_id' => Auth::id(),
            'type' => $msgType,
            'body' => $bodyText,
            'media_url' => $mediaUrl,
            'media_filename' => $filename,
            'media_mime_type' => $mime,
            'status' => 'pending',
            'sent_at' => now(),
        ]);

        $conversation->update([
            'last_message_at' => now(),
            'last_message_preview' => $bodyText,
        ]);

        // Immediate direct send via Meta Cloud API for instant real-time delivery
        try {
            (new SendWhatsAppMessageJob($message->id))->handle(app(WhatsAppCloudApiService::class));
        } catch (\Throwable $e) {
            Log::error("Direct Send Reply Error: {$e->getMessage()}", ['exception' => $e]);
        }

        // Also dispatch job for queue safety
        SendWhatsAppMessageJob::dispatch($message->id);

        $this->replyText = '';
        $this->attachment = null;

        Notification::make()
            ->title('Message Sent')
            ->success()
            ->send();
    }

    public function addNote(): void
    {
        $this->validate([
            'newNoteText' => 'required|string|min:1',
        ]);

        $conversation = $this->activeConversation;
        if (!$conversation) {
            return;
        }

        WhatsAppNote::create([
            'team_id' => $conversation->team_id,
            'conversation_id' => $conversation->id,
            'user_id' => Auth::id(),
            'content' => $this->newNoteText,
        ]);

        $this->newNoteText = '';

        Notification::make()
            ->title('Internal note saved')
            ->success()
            ->send();
    }

    public function assignUser(?string $userId): void
    {
        $conversation = $this->activeConversation;
        if ($conversation) {
            $conversation->update(['assigned_user_id' => $userId]);
            Notification::make()->title('Conversation assigned')->success()->send();
        }
    }

    public function updateStatus(string $status): void
    {
        $conversation = $this->activeConversation;
        if ($conversation) {
            $conversation->update(['status' => $status]);
            $this->selectedStatus = $status;
            Notification::make()->title("Status updated to {$status}")->success()->send();
        }
    }

    public function toggleTag(string $tagId): void
    {
        $conversation = $this->activeConversation;
        if ($conversation) {
            $conversation->tags()->toggle($tagId);
        }
    }

    public function generateAiSummary(): void
    {
        $conversation = $this->activeConversation;
        if ($conversation) {
            $service = resolve(WhatsAppAIService::class);
            $this->aiSummary = $service->summarizeConversation($conversation);
        }
    }

    public function generateAiSuggestions(): void
    {
        $conversation = $this->activeConversation;
        if ($conversation) {
            $service = resolve(WhatsAppAIService::class);
            $this->aiReplySuggestions = $service->generateReplySuggestions($conversation);
        }
    }

    public function useAiSuggestion(string $text): void
    {
        $this->replyText = $text;
    }
}
