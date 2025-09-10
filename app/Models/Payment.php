<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    // Payment status constants
    const STATUS_PENDING = 'pending';
    const STATUS_VERIFIED = 'verified';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';

    // Payment method constants
    const METHOD_CASH = 'cash';
    const METHOD_TRANSFER = 'transfer';
    const METHOD_QRIS = 'qris';

    protected $fillable = [
        'student_id',
        'billing_record_id',
        'payment_date',
        'total_amount',
        'payment_method',
        'receipt_number',
        'notes',
        'status',
        'kasir_id',
    ];

    protected $casts = [
        'payment_date' => 'datetime',
        'total_amount' => 'integer',
    ];

    /**
     * Get status display text
     */
    public function getStatusTextAttribute()
    {
        return $this->getStatusText($this->status);
    }

    /**
     * Get status class for styling
     */
    public function getStatusClassAttribute()
    {
        return $this->getStatusClass($this->status);
    }

    /**
     * Get status text by status value
     */
    public static function getStatusText($status)
    {
        $statusMap = [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_VERIFIED => 'Terverifikasi',
            self::STATUS_COMPLETED => 'Terverifikasi',
            self::STATUS_FAILED => 'Gagal',
            self::STATUS_CANCELLED => 'Dibatalkan',
        ];

        return $statusMap[$status] ?? ucfirst($status);
    }

    /**
     * Get status class by status value
     */
    public static function getStatusClass($status)
    {
        $classMap = [
            self::STATUS_PENDING => 'warning',
            self::STATUS_VERIFIED => 'success',
            self::STATUS_COMPLETED => 'success',
            self::STATUS_FAILED => 'danger',
            self::STATUS_CANCELLED => 'secondary',
        ];

        return $classMap[$status] ?? 'secondary';
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function billingRecord()
    {
        return $this->belongsTo(BillingRecord::class);
    }

    public function kasir()
    {
        return $this->belongsTo(User::class, 'kasir_id');
    }
    
    // Alias for backward compatibility
    public function cashier()
    {
        return $this->kasir();
    }

    /**
     * Get cash book entries for this payment
     */
    public function cashBookEntries()
    {
        return $this->hasMany(CashBook::class, 'reference_id')
            ->where('reference_type', 'payment');
    }
}
