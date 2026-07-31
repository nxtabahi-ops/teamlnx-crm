<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasTeam;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

final class WhatsAppTag extends Model
{
    use HasFactory;
    use HasTeam;
    use HasUlids;

    protected $table = 'whatsapp_tags';

    protected $fillable = [
        'team_id',
        'name',
        'color',
    ];

    public function conversations(): BelongsToMany
    {
        return $this->belongsToMany(WhatsAppConversation::class, 'whatsapp_conversation_tag', 'tag_id', 'conversation_id');
    }
}
