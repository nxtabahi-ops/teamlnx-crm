<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasTeam;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class WhatsAppConversation extends Model
{
    use HasFactory;
    use HasTeam;
    use HasUlids;

    protected $table = 'whatsapp_conversations';

    protected $fillable = [
        'team_id',
        'whatsapp_account_id',
        'whatsapp_contact_id',
        'assigned_user_id',
        'status',
        'unread_count',
        'last_message_at',
        'last_message_preview',
        'window_expires_at',
    ];

    protected function casts(): array
    {
        return [
            'unread_count' => 'integer',
            'last_message_at' => 'datetime',
            'window_expires_at' => 'datetime',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(WhatsAppAccount::class, 'whatsapp_account_id');
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(WhatsAppContact::class, 'whatsapp_contact_id');
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(WhatsAppMessage::class, 'conversation_id')->orderBy('created_at', 'asc');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(WhatsAppTag::class, 'whatsapp_conversation_tag', 'conversation_id', 'tag_id');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(WhatsAppNote::class, 'conversation_id')->orderBy('created_at', 'desc');
    }
}
