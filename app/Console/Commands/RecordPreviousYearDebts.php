<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Student;
use App\Models\BillingRecord;
use App\Models\Payment;
use App\Models\AcademicYear;
use Illuminate\Support\Facades\Log;

class RecordPreviousYearDebts extends Command
{
    protected $signature = 'debt:record-previous-year {academic_year_id?}';
    protected $description = 'Record previous year debts for students when academic year changes';

    public function handle()
    {
        $academicYearId = $this->argument('academic_year_id');
        
        if ($academicYearId) {
            $academicYear = AcademicYear::find($academicYearId);
            if (!$academicYear) {
                $this->error("Academic year with ID {$academicYearId} not found!");
                return 1;
            }
            $this->processAcademicYear($academicYear);
        } else {
            // Process all academic years that have a previous year
            $academicYears = AcademicYear::orderBy('year_start')->get();
            foreach ($academicYears as $academicYear) {
                $this->processAcademicYear($academicYear);
            }
        }
        
        return 0;
    }

    private function processAcademicYear(AcademicYear $academicYear)
    {
        $this->info("📅 Processing academic year: {$academicYear->name}");
        
        // Find previous academic year
        $previousYear = AcademicYear::where('year_start', $academicYear->year_start - 1)->first();
        if (!$previousYear) {
            $this->warn("⚠️  No previous academic year found for {$academicYear->name}");
            return;
        }
        
        $this->info("📋 Previous year: {$previousYear->name}");
        
        // Get all students from previous year
        $previousYearStudents = Student::where('academic_year_id', $previousYear->id)->get();
        $this->info("👥 Found {$previousYearStudents->count()} students from previous year");
        
        $processed = 0;
        $debtRecorded = 0;
        $errors = 0;
        
        foreach ($previousYearStudents as $student) {
            try {
                $debtAmount = $this->calculatePreviousYearDebt($student, $previousYear);
                
                if ($debtAmount > 0) {
                    // Update student's previous_debt field
                    $student->update(['previous_debt' => $debtAmount]);
                    $debtRecorded++;
                    
                    $this->line("💰 Student {$student->name}: Rp " . number_format($debtAmount, 0, ',', '.'));
                }
                
                $processed++;
                
                if ($processed % 100 === 0) {
                    $this->info("Processed {$processed} students...");
                }
                
            } catch (\Exception $e) {
                $errors++;
                $this->error("❌ Error processing student {$student->name}: " . $e->getMessage());
                Log::error('Previous year debt calculation error', [
                    'student_id' => $student->id,
                    'student_name' => $student->name,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        $this->info("✅ Completed processing {$academicYear->name}");
        $this->info("📊 Processed: {$processed} students");
        $this->info("💰 Debt recorded: {$debtRecorded} students");
        $this->info("❌ Errors: {$errors} students");
    }

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
