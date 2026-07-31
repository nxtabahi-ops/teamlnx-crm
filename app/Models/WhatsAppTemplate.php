<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasTeam;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class WhatsAppTemplate extends Model
{
    use HasFactory;
    use HasTeam;
    use HasUlids;

    protected $table = 'whatsapp_templates';

    protected $fillable = [
        'team_id',
        'whatsapp_account_id',
        'name',
        'language',
        'category',
        'status',
        'components',
    ];

    protected function casts(): array
    {
        return [
            'components' => 'array',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(WhatsAppAccount::class, 'whatsapp_account_id');
    }
}
