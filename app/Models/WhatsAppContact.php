<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasTeam;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class WhatsAppContact extends Model
{
    use HasFactory;
    use HasTeam;
    use HasUlids;

    protected $table = 'whatsapp_contacts';

    protected $fillable = [
        'team_id',
        'people_id',
        'wa_id',
        'phone_number',
        'profile_name',
        'avatar_url',
        'custom_metadata',
    ];

    protected function casts(): array
    {
        return [
            'custom_metadata' => 'array',
        ];
    }

    public function people(): BelongsTo
    {
        return $this->belongsTo(People::class, 'people_id');
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(WhatsAppConversation::class, 'whatsapp_contact_id');
    }
}
