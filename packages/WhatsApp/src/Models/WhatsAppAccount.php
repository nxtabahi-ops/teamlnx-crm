<?php

declare(strict_types=1);

namespace Relaticle\WhatsApp\Models;

use App\Models\Concerns\BelongsToTeamCreator;
use App\Models\Concerns\HasTeam;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

final class WhatsAppAccount extends Model
{
    use BelongsToTeamCreator;
    use HasFactory;
    use HasTeam;
    use HasUlids;
    use SoftDeletes;

    protected $table = 'whatsapp_accounts';

    protected $fillable = [
        'team_id',
        'creator_id',
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
}
