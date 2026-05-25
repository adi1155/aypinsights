<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduledReport extends Model
{
    protected $fillable = [
        'user_id', 'report_type', 'format', 'frequency', 'delivery_time',
        'recipients', 'filters', 'is_active', 'last_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'recipients' => 'array',
            'filters' => 'array',
            'is_active' => 'boolean',
            'last_sent_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
