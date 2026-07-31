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

final class WhatsAppNote extends Model
{
    use BelongsToTeamCreator;
    use HasFactory;
    use HasTeam;
    use HasUlids;

    protected $table = 'whatsapp_notes';

    protected $fillable = [
        'team_id',
        'creator_id',
        'conversation_id',
        'user_id',
        'content',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(WhatsAppConversation::class, 'conversation_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
