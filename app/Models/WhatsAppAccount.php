<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToTeamCreator;
use App\Models\Concerns\HasTeam;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class WhatsAppAccount extends Model
{
    use HasFactory;
    use HasTeam;
    use HasUlids;
    use SoftDeletes;

    protected $table = 'whatsapp_accounts';

    protected $fillable = [
        'team_id',
        'phone_number_id',
        'waba_id',
        'phone_number',
        'display_name',
        'access_token',
        'verify_token',
        'app_secret',
        'webhook_url',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'access_token' => 'encrypted',
            'is_active' => 'boolean',
        ];
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(WhatsAppConversation::class, 'whatsapp_account_id');
    }

    public function templates(): HasMany
    {
        return $this->hasMany(WhatsAppTemplate::class, 'whatsapp_account_id');
    }
}
