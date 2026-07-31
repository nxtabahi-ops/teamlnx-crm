<?php

declare(strict_types=1);

namespace Relaticle\WhatsApp\Models;

use App\Models\Concerns\BelongsToTeamCreator;
use App\Models\Concerns\HasTeam;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class WhatsAppMessage extends Model
{
    use BelongsToTeamCreator;
    use HasFactory;
    use HasTeam;
    use HasUlids;

    protected $table = 'whatsapp_messages';

    protected $fillable = [
        'team_id',
        'creator_id',
        'conversation_id',
        'wamid',
        'direction',
        'sender_type',
        'sender_user_id',
        'type',
        'body',
        'media_url',
        'media_mime_type',
        'media_filename',
        'media_size_bytes',
        'latitude',
        'longitude',
        'location_name',
        'payload',
        'status',
        'error_code',
        'error_message',
        'sent_at',
        'delivered_at',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'media_size_bytes' => 'integer',
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
            'read_at' => 'datetime',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(WhatsAppConversation::class, 'conversation_id');
    }

    public function senderUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_user_id');
    }
}
