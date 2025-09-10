<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BillingRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'fee_structure_id',
        'origin_year',
        'origin_class',
        'amount',
        'remaining_balance',
        'status',
        'due_date',
        'billing_month',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'remaining_balance' => 'decimal:2',
        'due_date' => 'date',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function feeStructure()
    {
        return $this->belongsTo(FeeStructure::class);
    }

    public function paymentAllocations()
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function getTotalPaidAttribute()
    {
        return $this->paymentAllocations()->sum('allocated_amount');
    }

    public function getPaymentPercentageAttribute()
    {
        if ($this->amount == 0) return 0;
        return round(($this->getTotalPaidAttribute() / $this->amount) * 100, 2);
    }

    public function updateStatus()
    {
        $totalPaid = $this->getTotalPaidAttribute();
        
        if ($totalPaid >= $this->amount) {
            $this->status = 'fully_paid';
        } elseif ($totalPaid > 0) {
            $this->status = 'partially_paid';
        } else {
            $this->status = 'active';
        }

        $this->remaining_balance = $this->amount - $totalPaid;
        $this->save();
    }
    
    // Method untuk mencari struktur biaya berdasarkan level siswa
    public static function findFeeStructureByStudentLevel($student, $academicYearId = null)
    {
        $institutionId = $student->institution_id;
        $level = $student->classRoom->level;
        
        if ($academicYearId) {
            return FeeStructure::findByLevel($institutionId, $academicYearId, $level);
        }
        
        return FeeStructure::findByLevelOnly($institutionId, $level);
    }
}
