<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Student;
use App\Models\AcademicYear;
use App\Models\BillingRecord;
use App\Models\Payment;
use Illuminate\Support\Facades\Log;

class FixPreviousDebt extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'debt:fix-previous {--academic-year=} {--student-id=} {--all}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix previous debt calculation for students';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔧 Starting previous debt fix...');
        
        $academicYearId = $this->option('academic-year');
        $studentId = $this->option('student-id');
        $all = $this->option('all');
        
        if ($all) {
            $this->fixAllStudents();
        } elseif ($academicYearId) {
            $this->fixStudentsInAcademicYear($academicYearId);
        } elseif ($studentId) {
            $this->fixSpecificStudent($studentId);
        } else {
            $this->error('Please specify --academic-year, --student-id, or --all');
            return 1;
        }
        
        $this->info('✅ Previous debt fix completed!');
        return 0;
    }
    
    private function fixAllStudents()
    {
        $this->info('Fixing all students...');
        
        $students = Student::with(['classRoom', 'academicYear', 'scholarshipCategory', 'billingRecords', 'payments'])->get();
        
        $this->info("Found {$students->count()} students");
        
        $progressBar = $this->output->createProgressBar($students->count());
        $progressBar->start();
        
        $fixed = 0;
        $skipped = 0;
        
        foreach ($students as $student) {
            $oldPreviousDebt = $student->previous_debt;
            $newPreviousDebt = $this->calculateCorrectPreviousDebt($student);
            
            if ($oldPreviousDebt != $newPreviousDebt) {
                $student->update([
                    'previous_debt' => $newPreviousDebt,
                    'previous_debt_year' => $newPreviousDebt > 0 ? $this->getPreviousYearKey($student) : null
                ]);
                $fixed++;
                
                $this->line("\nFixed student {$student->id} ({$student->name}): {$oldPreviousDebt} → {$newPreviousDebt}");
            } else {
                $skipped++;
            }
            
            $progressBar->advance();
        }
        
        $progressBar->finish();
        $this->line("\n");
        
        $this->info("Fixed: {$fixed} students");
        $this->info("Skipped: {$skipped} students");
    }
    
    private function fixStudentsInAcademicYear($academicYearId)
    {
        $academicYear = AcademicYear::find($academicYearId);
        if (!$academicYear) {
            $this->error("Academic year with ID {$academicYearId} not found");
            return;
        }
        
        $this->info("Fixing students in academic year: {$academicYear->name}");
        
        $students = Student::with(['classRoom', 'academicYear', 'scholarshipCategory', 'billingRecords', 'payments'])
            ->where('academic_year_id', $academicYearId)
            ->get();
        
        $this->info("Found {$students->count()} students");
        
        $fixed = 0;
        foreach ($students as $student) {
            $oldPreviousDebt = $student->previous_debt;
            $newPreviousDebt = $this->calculateCorrectPreviousDebt($student);
            
            if ($oldPreviousDebt != $newPreviousDebt) {
                $student->update([
                    'previous_debt' => $newPreviousDebt,
                    'previous_debt_year' => $newPreviousDebt > 0 ? $this->getPreviousYearKey($student) : null
                ]);
                $fixed++;
                
                $this->line("Fixed student {$student->id} ({$student->name}): {$oldPreviousDebt} → {$newPreviousDebt}");
            }
        }
        
        $this->info("Fixed: {$fixed} students");
    }
    
    private function fixSpecificStudent($studentId)
    {
        $student = Student::with(['classRoom', 'academicYear', 'scholarshipCategory', 'billingRecords', 'payments'])->find($studentId);
        if (!$student) {
            $this->error("Student with ID {$studentId} not found");
            return;
        }
        
        $this->info("Fixing student: {$student->name} (ID: {$student->id})");
        
        $oldPreviousDebt = $student->previous_debt;
        $newPreviousDebt = $this->calculateCorrectPreviousDebt($student);
        
        $this->line("Current previous debt: {$oldPreviousDebt}");
        $this->line("Calculated previous debt: {$newPreviousDebt}");
        
        if ($oldPreviousDebt != $newPreviousDebt) {
            $student->update([
                'previous_debt' => $newPreviousDebt,
                'previous_debt_year' => $newPreviousDebt > 0 ? $this->getPreviousYearKey($student) : null
            ]);
            $this->info("✅ Updated previous debt: {$oldPreviousDebt} → {$newPreviousDebt}");
        } else {
            $this->info("✅ No changes needed");
        }
    }
    
    private function calculateCorrectPreviousDebt($student)
    {
        // Check if student is new (shouldn't have previous debt)
        if ($this->isNewStudent($student)) {
            return 0;
        }
        
        // Calculate previous debt from billing records
        $totalDebt = 0;
        
        // Get all billing records from previous year
        $previousYear = $this->getPreviousAcademicYear($student);
        if (!$previousYear) {
            return 0;
        }
        
        $previousYearString = $previousYear->year_start . '-' . $previousYear->year_end;
        $billingRecords = BillingRecord::where('student_id', $student->id)
            ->where('origin_year', $previousYearString)
            ->get();
        
        // If no billing records found for previous year, try to calculate from current billing records
        if ($billingRecords->count() == 0) {
            // Fallback: use current billing records if they exist
            $currentBillingRecords = $student->billingRecords;
            if ($currentBillingRecords->count() > 0) {
                foreach ($currentBillingRecords as $billingRecord) {
                    // Calculate total payments for this billing record
                    $totalPaid = Payment::where('billing_record_id', $billingRecord->id)
                        ->whereIn('status', ['verified', 'completed'])
                        ->sum('total_amount');
                    
                    // Calculate remaining debt
                    $remainingDebt = max(0, (float)$billingRecord->amount - (float)$totalPaid);
                    $totalDebt += $remainingDebt;
                }
            }
        } else {
            foreach ($billingRecords as $billingRecord) {
                // Calculate total payments for this billing record
                $totalPaid = Payment::where('billing_record_id', $billingRecord->id)
                    ->whereIn('status', ['verified', 'completed'])
                    ->sum('total_amount');
                
                // Calculate remaining debt
                $remainingDebt = max(0, (float)$billingRecord->amount - (float)$totalPaid);
                $totalDebt += $remainingDebt;
            }
        }
        
        // Apply scholarship rules
        $totalDebt = $this->applyScholarshipRulesToPreviousDebt($student, $totalDebt);
        
        // Fix .004 values to .000 (round down to nearest thousand)
        if ($totalDebt > 0 && $totalDebt % 1000 == 4) {
            $totalDebt = $totalDebt - 4;
        }
        
        // Handle excess payments
        $this->handleExcessPayments($student);
        
        // Apply excess payments to current billing
        $this->applyExcessPaymentsToCurrentBilling($student);
        
        return $totalDebt;
    }
    
    private function handleExcessPayments($student)
    {
        // Get all payments for this student
        $payments = Payment::where('student_id', $student->id)->get();
        
        $totalExcess = 0;
        
        foreach ($payments as $payment) {
            if ($payment->billing_record_id) {
                // Get the specific billing record for this payment
                $billingRecord = BillingRecord::find($payment->billing_record_id);
                
                if ($billingRecord) {
                    $excessForThisBilling = $payment->total_amount - $billingRecord->amount;
                    if ($excessForThisBilling > 0) {
                        $totalExcess += $excessForThisBilling;
                    }
                }
            }
        }
        
        // If there's excess payment, create billing record for it and update credit_balance
        if ($totalExcess > 0) {
            $this->createExcessPaymentBillingRecord($student, $totalExcess);
            $this->updateStudentCreditBalance($student, $totalExcess);
        }
    }
    
    private function createExcessPaymentBillingRecord($student, $excessAmount)
    {
        // Check if student already has excess payment applied
        $existingExcessBilling = BillingRecord::where('student_id', $student->id)
            ->where('notes', 'LIKE', '%Excess Payment Transfer%')
            ->first();

        if ($existingExcessBilling) {
            return; // Already exists
        }

        // Get current academic year
        $currentAcademicYear = $student->academicYear;
        if (!$currentAcademicYear) {
            return;
        }

        // Get a default fee structure for excess payment
        $defaultFeeStructure = \App\Models\FeeStructure::where('is_active', true)
            ->where('academic_year_id', $currentAcademicYear->id)
            ->first();
        
        if (!$defaultFeeStructure) {
            $defaultFeeStructure = \App\Models\FeeStructure::where('is_active', true)->first();
        }
        
        if (!$defaultFeeStructure) {
            $defaultFeeStructure = \App\Models\FeeStructure::first();
        }
        
        if (!$defaultFeeStructure) {
            return;
        }

        // Create new billing record for excess payment
        $billingRecord = new BillingRecord();
        $billingRecord->student_id = $student->id;
        $billingRecord->fee_structure_id = $defaultFeeStructure->id;
        $billingRecord->origin_year = $currentAcademicYear->year_start . '-' . $currentAcademicYear->year_end;
        $billingRecord->origin_class = $student->classRoom->name ?? 'Unknown';
        $billingRecord->amount = $excessAmount;
        $billingRecord->remaining_balance = $excessAmount;
        $billingRecord->status = 'active';
        $billingRecord->due_date = now()->addDays(30); // Due in 30 days
        $billingRecord->billing_month = 'Excess Payment Transfer';
        $billingRecord->notes = "Excess Payment Transfer from previous year - Amount: " . number_format($excessAmount);
        $billingRecord->save();

        $this->info("Created excess payment billing record for student {$student->id}: " . number_format($excessAmount));
    }
    
    private function updateStudentCreditBalance($student, $excessAmount)
    {
        // Update student's credit_balance field
        $currentAcademicYear = $student->academicYear;
        if ($currentAcademicYear) {
            $student->update([
                'credit_balance' => $excessAmount,
                'credit_balance_year' => $currentAcademicYear->year_start
            ]);
            $this->info("Updated credit_balance for student {$student->id}: " . number_format($excessAmount));
        }
    }
    
    private function applyExcessPaymentsToCurrentBilling($student)
    {
        // Get excess payment billing record
        $excessBilling = BillingRecord::where('student_id', $student->id)
            ->where('notes', 'LIKE', '%Excess Payment Transfer%')
            ->first();

        if (!$excessBilling || $excessBilling->remaining_balance <= 0) {
            return; // No excess payment to apply
        }

        $excessAmount = $excessBilling->remaining_balance;

        // Get current academic year billing records (excluding excess payment record)
        $currentAcademicYear = $student->academicYear;
        if (!$currentAcademicYear) {
            return;
        }

        $currentBillingRecords = BillingRecord::where('student_id', $student->id)
            ->where('origin_year', $currentAcademicYear->year_start . '-' . $currentAcademicYear->year_end)
            ->where('notes', 'NOT LIKE', '%Excess Payment Transfer%')
            ->where('remaining_balance', '>', 0)
            ->orderBy('due_date', 'asc')
            ->get();

        if ($currentBillingRecords->isEmpty()) {
            return;
        }

        $remainingExcess = $excessAmount;

        foreach ($currentBillingRecords as $billingRecord) {
            if ($remainingExcess <= 0) {
                break;
            }

            $currentRemaining = $billingRecord->remaining_balance;
            $amountToApply = min($remainingExcess, $currentRemaining);

            // Update billing record
            $billingRecord->remaining_balance = $currentRemaining - $amountToApply;
            
            // Update status based on remaining balance
            if ($billingRecord->remaining_balance <= 0) {
                $billingRecord->status = 'fully_paid';
            } else {
                $billingRecord->status = 'partially_paid';
            }
            
            $billingRecord->save();

            $remainingExcess -= $amountToApply;
        }

        // Update excess payment billing record
        $excessBilling->remaining_balance = $remainingExcess;
        if ($remainingExcess <= 0) {
            $excessBilling->status = 'fully_paid';
        } else {
            $excessBilling->status = 'partially_paid';
        }
        $excessBilling->save();

        $appliedAmount = $excessAmount - $remainingExcess;
        if ($appliedAmount > 0) {
            $this->info("Applied excess payment: " . number_format($appliedAmount) . " to current billing for student {$student->id}");
        }
    }
    
    private function isNewStudent($student)
    {
        // If student's current academic year is the first year, they are new
        $currentAcademicYear = $student->academicYear;
        if (!$currentAcademicYear) {
            return true;
        }
        
        // Check if this is the first academic year (2025-2026)
        // Only students in 2025-2026 academic year are considered new
        if ($currentAcademicYear->year_start == 2025) {
            return true;
        }
        
        // Students in 2026-2027 are not new (they should have previous debt from 2025-2026)
        return false;
    }
    
    private function getPreviousAcademicYear($student)
    {
        $currentAcademicYear = $student->academicYear;
        if (!$currentAcademicYear) {
            return null;
        }
        
        return AcademicYear::where('year_start', $currentAcademicYear->year_start - 1)
            ->first();
    }
    
    private function getPreviousYearKey($student)
    {
        $previousYear = $this->getPreviousAcademicYear($student);
        return $previousYear ? (string)$previousYear->year_start : null;
    }
    
    private function applyScholarshipRulesToPreviousDebt($student, $totalDebt)
    {
        if ($totalDebt <= 0) {
            return $totalDebt;
        }
        
        // Use previous level if we can infer it from last year's billing
        $previousLevel = null;
        $previousYear = $this->getPreviousAcademicYear($student);
        if ($previousYear) {
            $prevYearString = $previousYear->year_start . '-' . $previousYear->year_end;
            $prevBilling = BillingRecord::where('student_id', $student->id)
                ->where('origin_year', $prevYearString)
                ->with(['feeStructure.class'])
                ->first();
            if ($prevBilling) {
                $previousLevel = optional(optional($prevBilling->feeStructure)->class)->level;
            }
            if (!$previousLevel && $prevBilling && $prevBilling->origin_class) {
                $previousLevel = $this->extractLevelFromClassName($prevBilling->origin_class);
            }
        }
        $currentLevel = $previousLevel ?: ($student->classRoom->level ?? 'Unknown');
        $scholarshipCategory = $student->scholarshipCategory;
        $categoryName = $scholarshipCategory->name ?? '';
        $discountPercentage = (float)($scholarshipCategory->discount_percentage ?? 0);
        
        // Ketentuan beasiswa:
        // 1. Yatim piatu 100% hanya berlaku untuk kelas VII/X, selanjutnya tidak berlaku
        // 2. Alumni hanya berlaku untuk kelas X saja
        // 3. Anak guru 100% selama menjadi siswa dan ketika lulus juga tidak ada tagihan
        
        if ($categoryName === 'Yatim Piatu, Piatu, Yatim' && $discountPercentage >= 100) {
            // Yatim piatu 100% hanya berlaku untuk level VII/X
            if (in_array($currentLevel, ['VII', 'X'])) {
                $totalDebt = 0;
            }
        } elseif ($categoryName === 'Alumni' && $discountPercentage > 0) {
            // Alumni hanya berlaku untuk kelas X saja
            if ($currentLevel === 'X') {
                $totalDebt = $totalDebt * (1 - $discountPercentage / 100);
            }
        } elseif (strpos(strtolower($categoryName), 'guru') !== false && $discountPercentage >= 100) {
            // Anak guru 100% berlaku untuk semua level
            $totalDebt = 0;
        } elseif ($discountPercentage > 0) {
            // Beasiswa umum lainnya
            $totalDebt = $totalDebt * (1 - $discountPercentage / 100);
        }
        
        return $totalDebt;
    }

    private function extractLevelFromClassName(?string $className): ?string
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
