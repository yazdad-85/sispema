<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Student;
use App\Models\Institution;
use App\Models\AcademicYear;
use App\Models\ClassModel;
use App\Models\FeeStructure;

class TestAutoBillingSystem extends Command
{
    protected $signature = 'test:auto-billing-system';
    protected $description = 'Test sistem otomatis billing record dan activity plan untuk siswa baru';

    public function handle()
    {
        $this->info('🧪 Testing Auto Billing System...');
        
        // Cari data yang diperlukan
        $institution = Institution::first();
        $academicYear = AcademicYear::first();
        $class = ClassModel::first();
        $feeStructure = FeeStructure::first();
        
        if (!$institution || !$academicYear || !$class || !$feeStructure) {
            $this->error('❌ Data tidak lengkap untuk testing');
            return;
        }
        
        $this->info("📊 Using Institution: {$institution->name}");
        $this->info("📅 Using Academic Year: {$academicYear->name}");
        $this->info("🏫 Using Class: {$class->class_name}");
        $this->info("💰 Using Fee Structure: {$feeStructure->name}");
        
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
            'nis' => 'TEST' . time(),
            'name' => 'Test Student Auto Billing',
            'institution_id' => $institution->id,
            'academic_year_id' => $academicYear->id,
            'class_id' => $class->id,
            'status' => 'active',
            'enrollment_date' => now(),
        ]);
        
        $this->info("\n✅ Test student created: {$testStudent->name}");
        
        // Panggil method createBillingRecordForStudent secara manual
        $this->createBillingRecordForStudent($testStudent);
        
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
            $this->info("✅ Auto billing system working!");
        } else {
            $this->error("❌ Auto billing system failed!");
        }
        
        // Hapus siswa test
        $testStudent->delete();
        $this->info("\n🧹 Test student cleaned up");
        
        $this->info("\n🎉 Test completed!");
    }
    
    /**
     * Buat billing record otomatis untuk siswa baru
     */
    private function createBillingRecordForStudent(Student $student)
    {
        try {
            // Cari fee structure berdasarkan class_id terlebih dahulu
            $feeStructure = FeeStructure::where('institution_id', $student->institution_id)
                ->where('academic_year_id', $student->academic_year_id)
                ->where('class_id', $student->class_id)
                ->first();
            
            // Jika tidak ada, cari berdasarkan level
            if (!$feeStructure && $student->classRoom) {
                $feeStructure = FeeStructure::where('institution_id', $student->institution_id)
                    ->where('academic_year_id', $student->academic_year_id)
                    ->where('level', $student->classRoom->level)
                    ->first();
            }
            
            // Jika masih tidak ada, gunakan fallback
            if (!$feeStructure) {
                $feeStructure = FeeStructure::where('institution_id', $student->institution_id)
                    ->where('academic_year_id', $student->academic_year_id)
                    ->first();
            }
            
            if ($feeStructure) {
                // Buat billing record untuk 12 bulan
                for ($month = 1; $month <= 12; $month++) {
                    \App\Models\BillingRecord::create([
                        'student_id' => $student->id,
                        'fee_structure_id' => $feeStructure->id,
                        'billing_month' => $month,
                        'origin_year' => $student->academic_year_id,
                        'origin_class' => $student->class_id,
                        'amount' => $feeStructure->monthly_amount,
                        'remaining_balance' => $feeStructure->monthly_amount,
                        'status' => 'active',
                        'notes' => 'ANNUAL',
                        'due_date' => now()->addDays(30), // 30 hari dari sekarang
                    ]);
                }
                
                $this->info("✅ Billing records created for test student");
            } else {
                $this->error("❌ No fee structure found for test student");
            }
        } catch (\Exception $e) {
            $this->error("❌ Failed to create billing records: " . $e->getMessage());
        }
    }
}
