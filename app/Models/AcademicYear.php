<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

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

    /**
     * Boot method untuk otomatis mencatat previous year debts
     */
    protected static function boot()
    {
        parent::boot();

        static::created(function ($academicYear) {
            Log::info('🎯 AcademicYear created - auto recording previous debts', [
                'year' => $academicYear->name,
                'year_start' => $academicYear->year_start
            ]);
            
            // Otomatis catat previous year debts
            static::recordPreviousYearDebts($academicYear);
        });
    }

    /**
     * Record previous year debts when new academic year is created
     */
    private static function recordPreviousYearDebts($academicYear)
    {
        try {
            // Find previous academic year
            $previousYear = static::where('year_start', $academicYear->year_start - 1)->first();
            if (!$previousYear) {
                Log::info('⚠️  No previous academic year found for debt recording', [
                    'new_year' => $academicYear->name
                ]);
                return;
            }

            Log::info('📋 Recording previous year debts', [
                'from_year' => $previousYear->name,
                'to_year' => $academicYear->name
            ]);

            // Get all students from previous year
            $previousYearStudents = Student::where('academic_year_id', $previousYear->id)->get();
            
            $debtRecorded = 0;
            $totalProcessed = 0;

            foreach ($previousYearStudents as $student) {
                $debtAmount = static::calculatePreviousYearDebt($student, $previousYear);
                
                if ($debtAmount > 0) {
                    // Update student's previous_debt field
                    $student->update(['previous_debt' => $debtAmount]);
                    $debtRecorded++;
                    
                    Log::info('💰 Previous debt recorded', [
                        'student_id' => $student->id,
                        'student_name' => $student->name,
                        'debt_amount' => $debtAmount,
                        'previous_year' => $previousYear->name
                    ]);
                }
                
                $totalProcessed++;
            }

            Log::info('✅ Previous year debt recording completed', [
                'new_year' => $academicYear->name,
                'total_processed' => $totalProcessed,
                'debt_recorded' => $debtRecorded
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Failed to record previous year debts', [
                'new_year' => $academicYear->name,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Calculate previous year debt for a student
     */
    private static function calculatePreviousYearDebt($student, $previousYear)
    {
        // Check if student was actually enrolled in the previous year
        // If student's current academic year is the same as previous year, they are new students
        if ($student->academic_year_id == $previousYear->id) {
            Log::info('Student is new in current year, no previous debt', [
                'student_id' => $student->id,
                'student_name' => $student->name,
                'current_academic_year' => $student->academicYear->name,
                'previous_year' => $previousYear->name
            ]);
            return 0;
        }
        
        $totalDebt = 0;
        
        // Get all billing records from previous year
        $previousYearHyphen = $previousYear->year_start . '-' . $previousYear->year_end;
        $previousYearSlash = $previousYear->year_start . '/' . $previousYear->year_end;
        $billingRecords = BillingRecord::where('student_id', $student->id)
            ->where(function($q) use ($previousYearHyphen, $previousYearSlash){
                $q->where('origin_year', $previousYearHyphen)
                  ->orWhere('origin_year', $previousYearSlash);
            })
            ->with(['feeStructure.class'])
            ->get();
        
        $previousLevel = null;
        foreach ($billingRecords as $billingRecord) {
            // Calculate total payments for this billing record
            $totalPaid = Payment::where('billing_record_id', $billingRecord->id)
                ->whereIn('status', ['verified', 'completed'])
                ->sum('total_amount');
            
            // Calculate remaining debt
            $remainingDebt = max(0, (float)$billingRecord->amount - (float)$totalPaid);
            $totalDebt += $remainingDebt;

            // Capture previous level from fee structure/class if available
            if (!$previousLevel) {
                $previousLevel = optional(optional($billingRecord->feeStructure)->class)->level;
                if (!$previousLevel && $billingRecord->origin_class) {
                    $previousLevel = self::extractLevelFromClassName($billingRecord->origin_class);
                }
            }
        }
        
        // Apply scholarship rules for previous debt using previous level context
        $totalDebt = static::applyScholarshipRulesToPreviousDebt($student, $totalDebt, $previousLevel);
        
        // Fix .004 values to .000 (round down to nearest thousand)
        if ($totalDebt > 0 && $totalDebt % 1000 == 4) {
            $totalDebt = $totalDebt - 4;
        }
        
        return $totalDebt;
    }
    
    /**
     * Apply scholarship rules to previous debt
     */
    private static function applyScholarshipRulesToPreviousDebt($student, $totalDebt, $previousLevel = null)
    {
        if ($totalDebt <= 0) {
            return $totalDebt;
        }
        
        $currentLevel = $previousLevel ?: ($student->classRoom->level ?? 'Unknown');
        $scholarshipCategory = $student->scholarshipCategory;
        $categoryName = $scholarshipCategory->name ?? '';
        $discountPercentage = (float)($scholarshipCategory->discount_percentage ?? 0);
        
        Log::info('Applying scholarship rules to previous debt', [
            'student_id' => $student->id,
            'current_level' => $currentLevel,
            'category_name' => $categoryName,
            'discount_percentage' => $discountPercentage,
            'total_debt_before' => $totalDebt
        ]);
        
        // Ketentuan beasiswa:
        // 1. Yatim piatu 100% hanya berlaku untuk kelas VII/X, selanjutnya tidak berlaku
        // 2. Alumni hanya berlaku untuk kelas X saja
        // 3. Anak guru 100% selama menjadi siswa dan ketika lulus juga tidak ada tagihan
        
        if ($categoryName === 'Yatim Piatu, Piatu, Yatim' && $discountPercentage >= 100) {
            // Yatim piatu 100% hanya berlaku untuk level VII/X
            if (in_array($currentLevel, ['VII', 'X'])) {
                $totalDebt = 0;
                Log::info('Applied yatim piatu 100% discount for level ' . $currentLevel);
            } else {
                Log::info('Yatim piatu 100% discount not applicable for level ' . $currentLevel . ' (only for VII/X)');
            }
        } elseif ($categoryName === 'Alumni' && $discountPercentage > 0) {
            // Alumni hanya berlaku untuk kelas X saja
            if ($currentLevel === 'X') {
                $totalDebt = $totalDebt * (1 - $discountPercentage / 100);
                Log::info('Applied alumni discount ' . $discountPercentage . '% for level ' . $currentLevel);
            } else {
                Log::info('Alumni discount not applicable for level ' . $currentLevel . ' (only for X)');
            }
        } elseif (strpos(strtolower($categoryName), 'guru') !== false && $discountPercentage >= 100) {
            // Anak guru 100% berlaku untuk semua level
            $totalDebt = 0;
            Log::info('Applied anak guru 100% discount for level ' . $currentLevel);
        } elseif ($discountPercentage > 0) {
            // Beasiswa umum lainnya
            $totalDebt = $totalDebt * (1 - $discountPercentage / 100);
            Log::info('Applied general scholarship discount ' . $discountPercentage . '% for level ' . $currentLevel);
        }
        
        Log::info('Previous debt after scholarship rules', [
            'student_id' => $student->id,
            'total_debt_after' => $totalDebt
        ]);
        
        return $totalDebt;
    }

    private static function extractLevelFromClassName(?string $className): ?string
    {
        if (!$className) return null;
        $levels = ['VII','VIII','IX','X','XI','XII'];
        foreach ($levels as $lvl) {
            if (stripos($className, $lvl) !== false) {
                return $lvl;
            }
        }
        return null;
    }
}
