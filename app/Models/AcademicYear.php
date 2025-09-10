<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AcademicYear extends Model
{
    use HasFactory;

    protected $fillable = [
        'year_start',
        'year_end',
        'status',
        'is_current',
        'description',
    ];

    protected $casts = [
        'is_current' => 'boolean',
    ];

    // Relationships
    public function students()
    {
        return $this->hasMany(Student::class);
    }

    public function classes()
    {
        return $this->hasMany(ClassModel::class);
    }

    public function feeStructures()
    {
        return $this->hasMany(FeeStructure::class);
    }

    public function billingRecords()
    {
        return $this->hasMany(BillingRecord::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    // Accessors
    public function getNameAttribute()
    {
        return $this->year_start . '/' . $this->year_end;
    }

    public function getFullNameAttribute()
    {
        return 'Tahun Ajaran ' . $this->year_start . '/' . $this->year_end;
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeCurrent($query)
    {
        return $query->where('is_current', true);
    }

    // Methods
    public function isActive()
    {
        return $this->status === 'active';
    }

    public function isCurrent()
    {
        return $this->is_current === true;
    }
}
