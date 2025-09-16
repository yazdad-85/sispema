<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Student;
use App\Models\Institution;
use App\Models\AcademicYear;
use App\Models\ClassModel;
use App\Models\FeeStructure;

class TestPromotionSystem extends Command
{
    protected $signature = 'test:promotion-system';
    protected $description = 'Test sistem promosi siswa dengan billing record dan activity plan';

    public function handle()
    {
        $this->info('🧪 Testing Promotion System...');
        
        // Cari data yang diperlukan
        $institution = Institution::first();
        $currentAcademicYear = AcademicYear::where('is_current', true)->first();
        $nextAcademicYear = AcademicYear::where('id', '>', $currentAcademicYear->id)->first();
        
        if (!$institution || !$currentAcademicYear || !$nextAcademicYear) {
            $this->error('❌ Data tidak lengkap untuk testing');
            return;
        }
        
        $this->info("📊 Using Institution: {$institution->name}");
        $this->info("📅 Current Academic Year: {$currentAcademicYear->name}");
        $this->info("📅 Next Academic Year: {$nextAcademicYear->name}");
        
        // Cari kelas VII dan VIII
        $classVII = ClassModel::where('institution_id', $institution->id)
            ->where('academic_year_id', $currentAcademicYear->id)
            ->where('level', 'VII')
            ->first();
            
        $classVIII = ClassModel::where('institution_id', $institution->id)
            ->where('academic_year_id', $nextAcademicYear->id)
            ->where('level', 'VIII')
            ->first();
        
        if (!$classVII || !$classVIII) {
            $this->error('❌ Kelas VII atau VIII tidak ditemukan');
            return;
        }
        
        $this->info("🏫 From Class: {$classVII->class_name}");
        $this->info("🏫 To Class: {$classVIII->class_name}");
        
        // Cari fee structure untuk tahun ajaran baru
        $feeStructure = FeeStructure::where('institution_id', $institution->id)
            ->where('academic_year_id', $nextAcademicYear->id)
            ->where('class_id', $classVIII->id)
            ->first();
        
        if (!$feeStructure) {
            $this->error('❌ Fee structure untuk tahun ajaran baru tidak ditemukan');
            return;
        }
        
        $this->info("💰 Fee Structure: {$feeStructure->monthly_amount}");
        
        // Hitung data sebelum test
        $studentsBefore = Student::count();
        $billingRecordsBefore = \App\Models\BillingRecord::count();
        $activityPlansBefore = \App\Models\ActivityPlan::count();
        
        $this->info("\n📈 Data sebelum test:");
        $this->info("   Students: {$studentsBefore}");
        $this->info("   Billing Records: {$billingRecordsBefore}");
        $this->info("   Activity Plans: {$activityPlansBefore}");
        
        // Buat siswa test
        $testStudent = Student::create([
            'nis' => 'PROMO' . time(),
            'name' => 'Test Student Promotion',
            'institution_id' => $institution->id,
            'academic_year_id' => $currentAcademicYear->id,
            'class_id' => $classVII->id,
            'status' => 'active',
            'enrollment_date' => now(),
        ]);
        
        $this->info("\n✅ Test student created: {$testStudent->name}");
        
        // Simulasikan promosi
        $this->info("\n🚀 Simulating promotion...");
        
        // Update student (seperti promosi)
        $testStudent->update([
            'class_id' => $classVIII->id,
            'academic_year_id' => $nextAcademicYear->id
        ]);
        
        // Buat billing record untuk tahun ajaran baru (seperti promosi)
        $this->createBillingRecordsForPromotedStudent($testStudent, $nextAcademicYear);
        
        $this->info("✅ Student promoted to {$classVIII->class_name}");
        
        // Cek hasil
        $studentsAfter = Student::count();
        $billingRecordsAfter = \App\Models\BillingRecord::count();
        $activityPlansAfter = \App\Models\ActivityPlan::count();
        
        $this->info("\n📈 Data setelah test:");
        $this->info("   Students: {$studentsAfter} (+" . ($studentsAfter - $studentsBefore) . ")");
        $this->info("   Billing Records: {$billingRecordsAfter} (+" . ($billingRecordsAfter - $billingRecordsBefore) . ")");
        $this->info("   Activity Plans: {$activityPlansAfter} (+" . ($activityPlansAfter - $activityPlansBefore) . ")");
        
        // Cek billing records untuk siswa test
        $testBillingRecords = $testStudent->billingRecords()->count();
        $this->info("\n🔍 Test student billing records: {$testBillingRecords}");
        
        if ($testBillingRecords > 0) {
            $this->info("✅ Promotion billing system working!");
        } else {
            $this->error("❌ Promotion billing system failed!");
        }
        
        // Hapus siswa test
        $testStudent->delete();
        $this->info("\n🧹 Test student cleaned up");
        
        $this->info("\n🎉 Test completed!");
    }
    
    /**
     * Buat billing record untuk siswa yang dipromosi
     */
    private function createBillingRecordsForPromotedStudent(Student $student, AcademicYear $nextAcademicYear)
    {
        try {
            // Cari fee structure untuk kelas dan tahun ajaran baru
            $feeStructure = FeeStructure::where('institution_id', $student->institution_id)
                ->where('academic_year_id', $nextAcademicYear->id)
                ->where('class_id', $student->class_id)
                ->first();
            
            // Jika tidak ada, cari berdasarkan level
            if (!$feeStructure && $student->classRoom) {
                $feeStructure = FeeStructure::where('institution_id', $student->institution_id)
                    ->where('academic_year_id', $nextAcademicYear->id)
                    ->where('level', $student->classRoom->level)
                    ->first();
            }
            
            // Jika masih tidak ada, gunakan fallback
            if (!$feeStructure) {
                $feeStructure = FeeStructure::where('institution_id', $student->institution_id)
                    ->where('academic_year_id', $nextAcademicYear->id)
                    ->first();
            }
            
            if ($feeStructure) {
                // Hapus billing record lama untuk tahun ajaran baru (jika ada)
                \App\Models\BillingRecord::where('student_id', $student->id)
                    ->where('origin_year', $nextAcademicYear->year_start . '/' . $nextAcademicYear->year_end)
                    ->delete();
                
                // Buat billing record baru untuk 12 bulan
                for ($month = 1; $month <= 12; $month++) {
                    \App\Models\BillingRecord::create([
                        'student_id' => $student->id,
                        'fee_structure_id' => $feeStructure->id,
                        'billing_month' => $month,
                        'origin_year' => $nextAcademicYear->year_start . '/' . $nextAcademicYear->year_end,
                        'origin_class' => $student->class_id,
                        'amount' => $feeStructure->monthly_amount,
                        'remaining_balance' => $feeStructure->monthly_amount,
                        'status' => 'active',
                        'notes' => 'ANNUAL',
                        'due_date' => now()->addDays(30), // 30 hari dari sekarang
                    ]);
                }
                
                $this->info("✅ Billing records created for promoted student");
            } else {
                $this->error("❌ No fee structure found for promoted student");
            }
        } catch (\Exception $e) {
            $this->error("❌ Failed to create billing records: " . $e->getMessage());
        }
    }
}
