<?php

namespace App\Observers;

use App\Models\FeeStructure;
use App\Models\ActivityPlan;
use App\Models\Category;
use App\Models\BillingRecord;
use Illuminate\Support\Facades\Log;

class FeeStructureObserver
{
    /**
     * Handle the FeeStructure "created" event.
     */
    public function created(FeeStructure $feeStructure)
    {
        Log::info('FeeStructure created - creating billing records and activity plans', [
            'fee_structure_id' => $feeStructure->id,
            'institution_id' => $feeStructure->institution_id,
            'class_id' => $feeStructure->class_id,
            'academic_year_id' => $feeStructure->academic_year_id
        ]);

        // Buat billing records untuk semua siswa di kelas ini
        $this->createBillingRecordsForStudents($feeStructure);
        
        // Update activity plans
        $this->updateActivityPlansForFeeStructure($feeStructure);
    }

    /**
     * Handle the FeeStructure "updated" event.
     */
    public function updated(FeeStructure $feeStructure)
    {
        // Update activity plans jika ada perubahan yang mempengaruhi perencanaan
        if ($feeStructure->wasChanged(['institution_id', 'class_id', 'academic_year_id', 'monthly_amount', 'yearly_amount'])) {
            Log::info('FeeStructure updated - updating activity plans', [
                'fee_structure_id' => $feeStructure->id,
                'changed_fields' => $feeStructure->getChanges()
            ]);

            $this->updateActivityPlansForFeeStructure($feeStructure);
        }
    }

    /**
     * Handle the FeeStructure "deleted" event.
     */
    public function deleted(FeeStructure $feeStructure)
    {
        Log::info('FeeStructure deleted - updating activity plans', [
            'fee_structure_id' => $feeStructure->id,
            'institution_id' => $feeStructure->institution_id,
            'class_id' => $feeStructure->class_id,
            'academic_year_id' => $feeStructure->academic_year_id
        ]);

        $this->updateActivityPlansForFeeStructure($feeStructure);
    }

    /**
     * Update activity plans berdasarkan perubahan fee structure
     */
    private function updateActivityPlansForFeeStructure(FeeStructure $feeStructure)
    {
        try {
            // Buat atau cari kategori SPP otomatis
            $sppCategory = Category::firstOrCreate(
                ['name' => 'Pembayaran SPP'],
                [
                    'name' => 'Pembayaran SPP',
                    'type' => 'pemasukan',
                    'description' => 'Sumbangan Pembinaan Pendidikan'
                ]
            );

            // Dapatkan informasi kelas dan lembaga
            $class = $feeStructure->class;
            $institution = $feeStructure->institution;
            $academicYear = $feeStructure->academicYear;

            if (!$class || !$institution || !$academicYear) {
                Log::warning('Missing related data for fee structure', [
                    'fee_structure_id' => $feeStructure->id,
                    'class_exists' => $class ? true : false,
                    'institution_exists' => $institution ? true : false,
                    'academic_year_exists' => $academicYear ? true : false
                ]);
                return;
            }

            $level = $class->level ?? 'Unknown';

            // Cari activity plan yang sesuai
            $activityPlan = ActivityPlan::where('institution_id', $institution->id)
                ->where('level', $level)
                ->where('category_id', $sppCategory->id)
                ->where('academic_year_id', $academicYear->id)
                ->first();

            // Hitung total amount dari billing records yang aktif
            $totalAmount = BillingRecord::whereHas('student', function($query) use ($institution, $level, $academicYear) {
                    $query->where('institution_id', $institution->id)
                          ->where('academic_year_id', $academicYear->id)
                          ->whereHas('classRoom', function($q) use ($level) {
                              $q->where('level', $level);
                          });
                })
                ->where('status', 'active')
                ->sum('amount');

            // Hitung jumlah siswa
            $studentCount = \App\Models\Student::where('institution_id', $institution->id)
                ->where('academic_year_id', $academicYear->id)
                ->whereHas('classRoom', function($query) use ($level) {
                    $query->where('level', $level);
                })
                ->count();

            if ($activityPlan) {
                if ($totalAmount > 0) {
                    // Update activity plan jika masih ada billing records
                    $activityPlan->update([
                        'budget_amount' => $totalAmount,
                        'unit_price' => $feeStructure->yearly_amount, // Gunakan yearly_amount untuk perhitungan SPP
                        'equivalent_1' => $studentCount,
                        'unit_1' => 'siswa',
                        'unit_2' => 'Per tahun',
                    ]);

                    Log::info('Activity plan updated for fee structure change', [
                        'fee_structure_id' => $feeStructure->id,
                        'activity_plan_id' => $activityPlan->id,
                        'new_student_count' => $studentCount,
                        'new_total_amount' => $totalAmount
                    ]);
                } else {
                    // Hapus activity plan jika tidak ada billing records lagi
                    $activityPlan->delete();
                    
                    Log::info('Activity plan deleted - no more billing records', [
                        'fee_structure_id' => $feeStructure->id,
                        'activity_plan_id' => $activityPlan->id
                    ]);
                }
            } else {
                // Buat activity plan baru jika belum ada dan ada billing records
                if ($totalAmount > 0) {
                    $activityPlan = ActivityPlan::create([
                        'academic_year_id' => $academicYear->id,
                        'category_id' => $sppCategory->id,
                        'institution_id' => $institution->id,
                        'level' => $level,
                        'name' => "Penerimaan SPP - {$institution->name} - Level {$level}",
                        'start_date' => $academicYear->year_start . '-07-01',
                        'end_date' => $academicYear->year_end . '-06-30',
                        'budget_amount' => $totalAmount,
                        'description' => "Rencana penerimaan SPP untuk {$studentCount} siswa di {$institution->name} level {$level}",
                        'unit_price' => $feeStructure->yearly_amount, // Gunakan yearly_amount untuk perhitungan SPP
                        'equivalent_1' => $studentCount,
                        'unit_1' => 'siswa',
                        'unit_2' => 'Per tahun',
                    ]);

                    Log::info('Activity plan created for fee structure', [
                        'fee_structure_id' => $feeStructure->id,
                        'activity_plan_id' => $activityPlan->id,
                        'institution_id' => $institution->id,
                        'level' => $level,
                        'total_amount' => $totalAmount,
                        'student_count' => $studentCount
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to update activity plans for fee structure', [
                'fee_structure_id' => $feeStructure->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Buat billing records untuk semua siswa di kelas yang sesuai dengan fee structure
     */
    private function createBillingRecordsForStudents(FeeStructure $feeStructure)
    {
        try {
            // Cari semua siswa di kelas yang sesuai dengan fee structure
            $students = \App\Models\Student::where('institution_id', $feeStructure->institution_id)
                ->where('academic_year_id', $feeStructure->academic_year_id)
                ->where('class_id', $feeStructure->class_id)
                ->get();

            Log::info('Found students for fee structure', [
                'fee_structure_id' => $feeStructure->id,
                'student_count' => $students->count()
            ]);

            $createdCount = 0;
            $skippedCount = 0;

            foreach ($students as $student) {
                // Cek apakah siswa sudah memiliki billing records untuk fee structure ini
                $existingBilling = BillingRecord::where('student_id', $student->id)
                    ->where('fee_structure_id', $feeStructure->id)
                    ->first();

                if ($existingBilling) {
                    $skippedCount++;
                    Log::info('Student already has billing record', [
                        'student_id' => $student->id,
                        'student_name' => $student->name,
                        'existing_billing_id' => $existingBilling->id
                    ]);
                    continue;
                }

                // Buat billing records untuk 12 bulan
                for ($month = 1; $month <= 12; $month++) {
                    BillingRecord::create([
                        'student_id' => $student->id,
                        'fee_structure_id' => $feeStructure->id,
                        'billing_month' => $month,
                        'origin_year' => $student->academic_year_id,
                        'origin_class' => $student->class_id,
                        'amount' => $feeStructure->monthly_amount,
                        'remaining_balance' => $feeStructure->monthly_amount,
                        'status' => 'active',
                        'notes' => 'ANNUAL',
                        'due_date' => now()->addDays(30)
                    ]);
                }

                $createdCount++;
                Log::info('Created billing records for student', [
                    'student_id' => $student->id,
                    'student_name' => $student->name,
                    'fee_structure_id' => $feeStructure->id,
                    'monthly_amount' => $feeStructure->monthly_amount
                ]);
            }

            Log::info('Billing records creation completed', [
                'fee_structure_id' => $feeStructure->id,
                'created_for_students' => $createdCount,
                'skipped_students' => $skippedCount,
                'total_billing_records_created' => $createdCount * 12
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to create billing records for students', [
                'fee_structure_id' => $feeStructure->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
