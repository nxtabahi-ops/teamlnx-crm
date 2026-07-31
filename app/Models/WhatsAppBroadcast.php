<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasTeam;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class WhatsAppBroadcast extends Model
{
    use HasFactory;
    use HasTeam;
    use HasUlids;

    protected $table = 'whatsapp_broadcasts';

    protected $fillable = [
        'team_id',
        'whatsapp_account_id',
        'whatsapp_template_id',
        'name',
        'status',
        'target_tag_ids',
        'total_recipients',
        'successful_count',
        'failed_count',
        'scheduled_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'target_tag_ids' => 'array',
            'total_recipients' => 'integer',
            'successful_count' => 'integer',
            'failed_count' => 'integer',
            'scheduled_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(WhatsAppAccount::class, 'whatsapp_account_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(WhatsAppTemplate::class, 'whatsapp_template_id');
    }
}
