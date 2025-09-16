<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AcademicYear;
use App\Models\FeeStructure;
use App\Models\ClassModel;
use App\Models\Institution;
use Illuminate\Support\Facades\Log;

class AutoCopyFeeStructures extends Command
{
    protected $signature = 'fee:auto-copy {academic_year_id?} {--force : Force copy even if classes exist}';
    protected $description = 'Auto copy fee structures untuk tahun ajaran yang belum memiliki fee structure';

    public function handle()
    {
        $academicYearId = $this->argument('academic_year_id');
        
        if ($academicYearId) {
            $academicYear = AcademicYear::find($academicYearId);
            if (!$academicYear) {
                $this->error("❌ Academic year ID {$academicYearId} tidak ditemukan");
                return;
            }
            $this->copyForAcademicYear($academicYear);
        } else {
            // Copy untuk semua tahun ajaran yang belum memiliki fee structure
            $academicYears = AcademicYear::orderBy('year_start')->get();
            
            foreach ($academicYears as $academicYear) {
                $feeStructureCount = FeeStructure::where('academic_year_id', $academicYear->id)->count();
                $classCount = ClassModel::where('academic_year_id', $academicYear->id)->count();
                
                if ($feeStructureCount == 0) {
                    if ($classCount > 0) {
                        $this->info("📅 Processing {$academicYear->name} (has {$classCount} classes)...");
                        $this->copyForAcademicYear($academicYear);
                    } else {
                        $this->warn("⚠️  Skipping {$academicYear->name} (no classes created yet)");
                    }
                } else {
                    $this->info("⏭️  Skipping {$academicYear->name} (already has {$feeStructureCount} fee structures)");
                }
            }
        }
    }
    
    private function copyForAcademicYear(AcademicYear $academicYear)
    {
        try {
            // Cari tahun ajaran sebelumnya
            $previousYear = AcademicYear::where('year_start', $academicYear->year_start - 1)
                ->where('year_end', $academicYear->year_end - 1)
                ->first();
            
            if (!$previousYear) {
                $this->warn("⚠️  No previous academic year found for {$academicYear->name}");
                return;
            }

            $this->info("📋 Copying from {$previousYear->name} to {$academicYear->name}");

            $totalCreated = 0;
            $institutions = Institution::all();

            foreach ($institutions as $institution) {
                $created = $this->copyFeeStructuresForInstitution($institution, $previousYear, $academicYear);
                $totalCreated += $created;
                
                if ($created > 0) {
                    $this->info("   ✅ {$institution->name}: {$created} fee structures");
                }
            }

            $this->info("🎉 Total created: {$totalCreated} fee structures for {$academicYear->name}");

        } catch (\Exception $e) {
            $this->error("❌ Error copying fee structures for {$academicYear->name}: " . $e->getMessage());
            Log::error('Auto copy fee structures failed', [
                'academic_year' => $academicYear->name,
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
