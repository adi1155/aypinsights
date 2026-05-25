<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExecutiveNotification extends Model
{
    protected $fillable = ['user_id', 'type', 'severity', 'title', 'message', 'payload', 'is_read', 'read_at'];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'is_read' => 'boolean',
            'read_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
