<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinanceRecord extends Model
{
    use HasFactory;

    protected $table = 'finance_records';

    protected $fillable = [
        'date',
        'income',
        'expense',
    ];

    /**
     * Accessor for net balance of the day.
     */
    protected $casts = [
        'date' => 'date', 
    ];
    public function getNetAttribute(): float
    {
        return $this->income - $this->expense;
    }
}
