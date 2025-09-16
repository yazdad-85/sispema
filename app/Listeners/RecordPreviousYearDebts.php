<?php

namespace App\Listeners;

use App\Events\AcademicYearCreated;
use App\Models\Student;
use App\Models\BillingRecord;
use App\Models\Payment;
use App\Models\AcademicYear;
use Illuminate\Support\Facades\Log;

class RecordPreviousYearDebts
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  AcademicYearCreated  $event
     * @return void
     */
    public function handle(AcademicYearCreated $event)
    {
        $academicYear = $event->academicYear;
        
        Log::info('🎯 AcademicYearCreated event triggered', [
            'academic_year' => $academicYear->name,
            'year_start' => $academicYear->year_start
        ]);
        
        try {
            // Find previous academic year
            $previousYear = AcademicYear::where('year_start', $academicYear->year_start - 1)->first();
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
                $debtAmount = $this->calculatePreviousYearDebt($student, $previousYear);
                
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
    private function calculatePreviousYearDebt(Student $student, AcademicYear $previousYear)
    {
        $totalDebt = 0;
        
        // Get all billing records from previous year
        $previousYearString = $previousYear->year_start . '-' . $previousYear->year_end;
        $billingRecords = BillingRecord::where('student_id', $student->id)
            ->where('origin_year', $previousYearString)
            ->get();
        
        foreach ($billingRecords as $billingRecord) {
            // Calculate total payments for this billing record
            $totalPaid = Payment::where('billing_record_id', $billingRecord->id)
                ->whereIn('status', ['verified', 'completed'])
                ->sum('total_amount');
            
            // Calculate remaining debt
            $remainingDebt = max(0, (float)$billingRecord->amount - (float)$totalPaid);
            $totalDebt += $remainingDebt;
        }
        
        return $totalDebt;
    }
}
