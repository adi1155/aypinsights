<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DashboardPreference extends Model
{
    protected $fillable = [
        'user_id', 'default_company', 'default_branch', 'theme', 'currency', 'widget_layout', 'filters',
    ];

    protected function casts(): array
    {
        return [
            'widget_layout' => 'array',
            'filters' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
