<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeeStructure extends Model
{
    use HasFactory;

    protected $fillable = [
        'institution_id',
        'academic_year_id',
        'class_id',
        'monthly_amount',
        'yearly_amount',
        'scholarship_discount',
        'description',
        'is_active',
    ];

    protected $casts = [
        'monthly_amount' => 'decimal:2',
        'yearly_amount' => 'decimal:2',
        'scholarship_discount' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function class()
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function billingRecords()
    {
        return $this->hasMany(BillingRecord::class);
    }

    // Accessor untuk monthly_fee (kompatibilitas)
    public function getMonthlyFeeAttribute()
    {
        return $this->monthly_amount;
    }

    // Accessor untuk registration_fee (kompatibilitas)
    public function getRegistrationFeeAttribute()
    {
        return $this->yearly_amount;
    }

    // Accessor untuk other_fees (kompatibilitas)
    public function getOtherFeesAttribute()
    {
        return 0; // Tidak ada di struktur tabel
    }
    
    // Method untuk mencari struktur biaya berdasarkan level kelas
    public static function findByLevel($institutionId, $academicYearId, $level)
    {
        return static::where('institution_id', $institutionId)
                    ->where('academic_year_id', $academicYearId)
                    ->whereHas('class', function($query) use ($level) {
                        $query->where('level', $level);
                    })
                    ->first();
    }
    
    /**
     * Calculate smart monthly payment distribution
     * Solves the problem of odd amounts when dividing yearly amount by 12
     * 
     * @param float $yearlyAmount Total yearly amount
     * @param float $firstMonthAmount Amount for first month (Juli)
     * @return array Monthly payment breakdown
     */
    public static function calculateSmartMonthlyDistribution($yearlyAmount, $firstMonthAmount = null)
    {
        // If no first month amount specified, calculate it
        if ($firstMonthAmount === null) {
            // Default: first month is 10% of yearly amount, rounded to nearest 50,000
            $firstMonthAmount = round($yearlyAmount * 0.1 / 50000) * 50000;
        }
        
        $remainingAmount = $yearlyAmount - $firstMonthAmount;
        $remainingMonths = 11;
        
        // Calculate regular monthly amount and round to thousands
        $regularMonthlyAmount = floor($remainingAmount / $remainingMonths);
        $regularMonthlyAmount = round($regularMonthlyAmount / 1000) * 1000; // Round to thousands
        
        // Calculate the remainder after rounding
        $remainder = $remainingAmount - ($regularMonthlyAmount * $remainingMonths);
        
        // Distribute the remainder to the first few months
        $months = [
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
            'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni'
        ];
        
        $monthlyBreakdown = [];
        $monthlyBreakdown['Juli'] = $firstMonthAmount;
        
        // Calculate final regular monthly amount (all months Aug-Jun should be the same)
        $totalRegularAmount = $yearlyAmount - $firstMonthAmount;
        $finalRegularMonthlyAmount = round($totalRegularAmount / 11 / 5000) * 5000; // Round to 5 thousands for easier change
        
        // Calculate the actual total with rounded amounts
        $actualTotal = $firstMonthAmount + ($finalRegularMonthlyAmount * 11);
        $finalDifference = $yearlyAmount - $actualTotal;
        
        // Adjust first month if there's a difference
        if ($finalDifference != 0) {
            $firstMonthAmount += $finalDifference;
        }
        
        // Set all regular months to the same amount
        for ($i = 1; $i < 12; $i++) {
            $month = $months[$i];
            $monthlyBreakdown[$month] = $finalRegularMonthlyAmount;
        }
        
        // Update first month amount
        $monthlyBreakdown['Juli'] = $firstMonthAmount;
        
        // Calculate final totals
        $totalCalculated = array_sum($monthlyBreakdown);
        $difference = $yearlyAmount - $totalCalculated;
        
        return [
            'yearly_amount' => $yearlyAmount,
            'first_month_amount' => $firstMonthAmount,
            'regular_monthly_amount' => $regularMonthlyAmount,
            'monthly_breakdown' => $monthlyBreakdown,
            'total_calculated' => $totalCalculated,
            'difference' => $difference
        ];
    }
    
    /**
     * Get monthly amount for specific month
     * 
     * @param string $month Month name (Juli, Agustus, etc.)
     * @return float Monthly amount
     */
    public function getMonthlyAmountForMonth($month)
    {
        $distribution = self::calculateSmartMonthlyDistribution($this->yearly_amount);
        return $distribution['monthly_breakdown'][$month] ?? $this->monthly_amount;
    }
    
    /**
     * Get all monthly amounts as array
     * 
     * @return array Monthly amounts by month
     */
    public function getAllMonthlyAmounts()
    {
        return self::calculateSmartMonthlyDistribution($this->yearly_amount)['monthly_breakdown'];
    }

    // Method untuk mencari struktur biaya berdasarkan level (tanpa academic year)
    public static function findByLevelOnly($institutionId, $level)
    {
        return static::where('institution_id', $institutionId)
                    ->whereHas('class', function($query) use ($level) {
                        $query->where('level', $level);
                    })
                    ->first();
    }
}
