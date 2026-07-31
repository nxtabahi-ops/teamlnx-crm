<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class WhatsAppWebhookLog extends Model
{
    use HasUlids;

    protected $table = 'whatsapp_webhook_logs';

    public $timestamps = false;

    protected $fillable = [
        'whatsapp_account_id',
        'event_type',
        'payload',
        'status',
        'error',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(WhatsAppAccount::class, 'whatsapp_account_id');
    }
}
