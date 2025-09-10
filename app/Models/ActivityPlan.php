<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'academic_year_id',
        'category_id',
        'name',
        'start_date',
        'end_date',
        'budget_amount',
        'description',
        'unit_price',
        'equivalent_1',
        'equivalent_2',
        'equivalent_3',
        'unit_1',
        'unit_2',
        'unit_3'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'budget_amount' => 'decimal:2'
    ];

    // Relationships
    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function realizations()
    {
        return $this->hasMany(ActivityRealization::class, 'plan_id');
    }

    // Scopes
    public function scopeForAcademicYear($query, $academicYearId)
    {
        return $query->where('academic_year_id', $academicYearId);
    }

    // Accessors
    public function getTotalRealizationAttribute()
    {
        return $this->realizations()->sum('total_amount');
    }

    public function getRemainingBudgetAttribute()
    {
        return $this->budget_amount - $this->total_realization;
    }

    public function getRealizationPercentageAttribute()
    {
        if ($this->budget_amount == 0) return 0;
        return ($this->total_realization / $this->budget_amount) * 100;
    }
}
