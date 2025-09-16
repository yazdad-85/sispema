<?php

namespace App\Observers;

use App\Models\Student;
use App\Models\ActivityPlan;
use App\Models\Category;
use Illuminate\Support\Facades\Log;

class StudentObserver
{
    /**
     * Handle the Student "created" event.
     */
    public function created(Student $student)
    {
        // Update activity plans setelah siswa baru ditambahkan
        $this->updateActivityPlansForStudent($student);
    }

    /**
     * Handle the Student "updated" event.
     */
    public function updated(Student $student)
    {
        // Update activity plans jika ada perubahan yang mempengaruhi perencanaan
        if ($student->wasChanged(['institution_id', 'class_id', 'academic_year_id'])) {
            $this->updateActivityPlansForStudent($student);
        }
    }

    /**
     * Update activity plans berdasarkan siswa baru
     */
    private function updateActivityPlansForStudent(Student $student)
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
            
            Log::info('SPP category ensured for activity plan creation', [
                'student_id' => $student->id,
                'category_id' => $sppCategory->id
            ]);

            // Cari activity plan yang sesuai
            $activityPlan = ActivityPlan::where('institution_id', $student->institution_id)
                ->where('level', $student->classRoom->level ?? '')
                ->where('category_id', $sppCategory->id)
                ->first();

            if ($activityPlan) {
                // Hitung ulang jumlah siswa dan total amount
                $studentCount = Student::where('institution_id', $student->institution_id)
                    ->whereHas('classRoom', function($query) use ($student) {
                        $query->where('level', $student->classRoom->level ?? '');
                    })
                    ->where('academic_year_id', $student->academic_year_id)
                    ->count();

                // Hitung total amount dari billing records
                $totalAmount = \App\Models\BillingRecord::whereHas('student', function($query) use ($student) {
                        $query->where('institution_id', $student->institution_id)
                              ->whereHas('classRoom', function($q) use ($student) {
                                  $q->where('level', $student->classRoom->level ?? '');
                              })
                              ->where('academic_year_id', $student->academic_year_id);
                    })
                    ->where('status', 'active')
                    ->sum('amount');

                // Update activity plan
                $activityPlan->update([
                    'budget_amount' => $totalAmount,
                    'unit_price' => $student->billingRecords()->where('status', 'active')->first()->feeStructure->yearly_amount ?? 0,
                    'equivalent_1' => $studentCount, // Hanya angka, bukan string
                    'unit_1' => 'siswa',
                    'unit_2' => 'Per tahun',
                ]);

                Log::info('Activity plan updated for new student', [
                    'student_id' => $student->id,
                    'activity_plan_id' => $activityPlan->id,
                    'new_student_count' => $studentCount,
                    'new_total_amount' => $totalAmount
                ]);
            } else {
                // Buat activity plan baru jika belum ada
                $academicYear = $student->academicYear;
                $institution = $student->institution;
                $level = $student->classRoom->level ?? 'Unknown';
                
                // Hitung total amount dari billing records siswa ini
                $totalAmount = $student->billingRecords()->where('status', 'active')->sum('amount');
                
                $activityPlan = ActivityPlan::create([
                    'academic_year_id' => $student->academic_year_id,
                    'category_id' => $sppCategory->id,
                    'institution_id' => $student->institution_id,
                    'level' => $level,
                    'name' => "Penerimaan SPP - {$institution->name} - Level {$level}",
                    'start_date' => $academicYear->year_start . '-07-01',
                    'end_date' => $academicYear->year_end . '-06-30',
                    'budget_amount' => $totalAmount,
                    'description' => "Rencana penerimaan SPP untuk {$institution->name} level {$level}",
                    'unit_price' => $student->billingRecords()->where('status', 'active')->first()->feeStructure->yearly_amount ?? 0,
                    'equivalent_1' => 1, // Siswa pertama
                    'unit_1' => 'siswa',
                    'unit_2' => 'Per tahun',
                ]);

                Log::info('Activity plan created for new student', [
                    'student_id' => $student->id,
                    'activity_plan_id' => $activityPlan->id,
                    'institution_id' => $student->institution_id,
                    'level' => $level,
                    'total_amount' => $totalAmount
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to update activity plans for new student', [
                'student_id' => $student->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
