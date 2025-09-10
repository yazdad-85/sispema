<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'institution_id',
        'nis',
        'name',
        'email',
        'phone',
        'address',
        'class_id',
        'academic_year_id',
        'scholarship_category_id',
        'status',
        'enrollment_date',
        'previous_debt',
        'previous_debt_year',
        'credit_balance',
        'credit_balance_year',
    ];

    protected $casts = [
        'enrollment_date' => 'date',
    ];

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function classRoom()
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function scholarshipCategory()
    {
        return $this->belongsTo(ScholarshipCategory::class);
    }

    public function billingRecords()
    {
        return $this->hasMany(BillingRecord::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function digitalPayments()
    {
        return $this->hasMany(DigitalPayment::class);
    }

    public function getActiveBillingRecords()
    {
        return $this->billingRecords()
            ->whereIn('status', ['active', 'partially_paid', 'overdue'])
            ->orderBy('origin_year')
            ->orderBy('billing_month');
    }

    public function getTotalOutstanding()
    {
        return $this->billingRecords()
            ->whereIn('status', ['active', 'partially_paid', 'overdue'])
            ->sum('remaining_balance');
    }
}
