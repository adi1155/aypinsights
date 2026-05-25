<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DashboardSnapshot extends Model
{
    protected $fillable = [
        'dashboard_type', 'company', 'branch', 'snapshot_date',
        'kpi_data', 'chart_data', 'table_data', 'currency', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'snapshot_date' => 'date',
            'kpi_data' => 'array',
            'chart_data' => 'array',
            'table_data' => 'array',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
