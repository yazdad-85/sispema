<?php

namespace App\Observers;

use App\Models\AcademicYear;
use App\Models\FeeStructure;
use App\Models\ClassModel;
use App\Models\Institution;
use App\Models\Student;
use App\Models\BillingRecord;
use App\Models\Payment;
use App\Events\AcademicYearCreated;
use Illuminate\Support\Facades\Log;

class AcademicYearObserver
{
    /**
     * Handle the AcademicYear "created" event.
     */
    public function created(AcademicYear $academicYear)
    {
        Log::info('🎯 AcademicYear created - triggering event', [
            'year' => $academicYear->name,
            'note' => 'Fee structures will be copied manually after classes are created'
        ]);
        
        // Fire event untuk mencatat previous year debts
        event(new AcademicYearCreated($academicYear));
    }

    /**
     * Auto copy fee structures dari tahun ajaran sebelumnya
     */
    private function autoCopyFeeStructures(AcademicYear $newAcademicYear)
    {
        try {
            // Cari tahun ajaran sebelumnya (tahun sebelumnya)
            $previousYear = AcademicYear::where('year_start', $newAcademicYear->year_start - 1)
                ->where('year_end', $newAcademicYear->year_end - 1)
                ->first();
            
            if (!$previousYear) {
                Log::info('No previous academic year found for auto copy', [
                    'new_year' => $newAcademicYear->name
                ]);
                return;
            }

            Log::info('Auto copying fee structures', [
                'from_year' => $previousYear->name,
                'to_year' => $newAcademicYear->name
            ]);

            $totalCreated = 0;
            $institutions = Institution::all();

            foreach ($institutions as $institution) {
                $created = $this->copyFeeStructuresForInstitution($institution, $previousYear, $newAcademicYear);
                $totalCreated += $created;
            }

            Log::info('Auto copy fee structures completed', [
                'total_created' => $totalCreated,
                'new_year' => $newAcademicYear->name
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to auto copy fee structures', [
                'new_year' => $newAcademicYear->name,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Copy fee structures untuk satu institusi
     */
    private function copyFeeStructuresForInstitution($institution, $sourceYear, $targetYear)
    {
        $created = 0;
        
        // Ambil fee structures dari tahun sebelumnya
        $sourceFeeStructures = FeeStructure::with('class')
            ->where('institution_id', $institution->id)
            ->where('academic_year_id', $sourceYear->id)
            ->get();

        foreach ($sourceFeeStructures as $src) {
            $level = optional($src->class)->level;
            if (!$level) {
                continue;
            }

            // Cek apakah sudah ada di tahun baru
            $exists = FeeStructure::where('institution_id', $institution->id)
                ->where('academic_year_id', $targetYear->id)
                ->whereHas('class', function($q) use ($level) {
                    $q->where('level', $level);
                })
                ->exists();

            if ($exists) {
                continue;
            }

            // Cari kelas target dengan level sama
            $targetClass = ClassModel::where('institution_id', $institution->id)
                ->where('academic_year_id', $targetYear->id)
                ->where('level', $level)
                ->first();

            if (!$targetClass) {
                // Buat kelas baru jika belum ada
                $targetClass = ClassModel::create([
                    'class_name' => $this->getClassNameForLevel($level),
                    'institution_id' => $institution->id,
                    'academic_year_id' => $targetYear->id,
                    'level' => $level,
                    'grade_level' => $this->getGradeLevel($level),
                    'is_active' => true,
                    'is_graduated_class' => false,
                ]);
            }

            // Buat fee structure baru
            FeeStructure::create([
                'institution_id' => $institution->id,
                'academic_year_id' => $targetYear->id,
                'class_id' => $targetClass->id,
                'monthly_amount' => $src->monthly_amount,
                'yearly_amount' => $src->yearly_amount,
                'scholarship_discount' => $src->scholarship_discount,
                'description' => $src->description ?: 'Auto copied from ' . $sourceYear->name,
                'is_active' => true,
            ]);

            $created++;
        }

        return $created;
    }

    /**
     * Generate class name berdasarkan level
     */
    private function getClassNameForLevel($level)
    {
        $levelMap = [
            'VII' => 'VII A',
            'VIII' => 'VIII A', 
            'IX' => 'IX A',
            'X' => 'X A',
            'XI' => 'XI A',
            'XII' => 'XII A',
        ];

        return $levelMap[$level] ?? $level . ' A';
    }

    /**
     * Get grade level number
     */
    private function getGradeLevel($level)
    {
        $gradeMap = [
            'VII' => 7,
            'VIII' => 8,
            'IX' => 9,
            'X' => 10,
            'XI' => 11,
            'XII' => 12,
        ];

        return $gradeMap[$level] ?? 0;
    }

}
