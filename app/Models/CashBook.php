<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashBook extends Model
{
    use HasFactory;

    protected $table = 'cash_book';

    protected $fillable = [
        'date',
        'description',
        'debit',
        'credit',
        'balance',
        'reference_type',
        'reference_id'
    ];

    protected $casts = [
        'date' => 'date',
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
        'balance' => 'decimal:2'
    ];

    // Relationships
    public function payment()
    {
        return $this->belongsTo(\App\Models\Payment::class, 'reference_id');
    }

    public function realization()
    {
        return $this->belongsTo(\App\Models\ActivityRealization::class, 'reference_id');
    }

    // Scopes
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    public function scopeByReference($query, $type, $id)
    {
        return $query->where('reference_type', $type)->where('reference_id', $id);
    }

    // Static methods
    public static function getCurrentBalance()
    {
        $lastEntry = static::orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->first();
        
        return $lastEntry ? $lastEntry->balance : 0;
    }

    public static function addEntry($date, $description, $debit = 0, $credit = 0, $referenceType = null, $referenceId = null)
    {
        $currentBalance = static::getCurrentBalance();
        $newBalance = $currentBalance + $credit - $debit;

        return static::create([
            'date' => $date,
            'description' => $description,
            'debit' => $debit,
            'credit' => $credit,
            'balance' => $newBalance,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId
        ]);
    }
}
