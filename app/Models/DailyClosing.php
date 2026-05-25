<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyClosing extends Model
{
    protected $table = 'daily_closing';

    protected $fillable = [
        'closing_date', 'company', 'branch', 'opening_balance', 'receipts', 'payments',
        'closing_balance', 'bank_balance', 'cash_in_hand', 'pending_deposits',
        'outstanding_cheques', 'daily_profit_loss', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'closing_date' => 'date',
            'metadata' => 'array',
            'opening_balance' => 'decimal:2',
            'receipts' => 'decimal:2',
            'payments' => 'decimal:2',
            'closing_balance' => 'decimal:2',
            'bank_balance' => 'decimal:2',
            'cash_in_hand' => 'decimal:2',
            'pending_deposits' => 'decimal:2',
            'outstanding_cheques' => 'decimal:2',
            'daily_profit_loss' => 'decimal:2',
        ];
    }
}
