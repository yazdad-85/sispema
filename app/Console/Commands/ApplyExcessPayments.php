<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Student;
use App\Models\Payment;
use App\Models\BillingRecord;
use App\Models\AcademicYear;

class ApplyExcessPayments extends Command
{
    protected $signature = 'excess:apply {--student= : Specific student ID to process} {--all : Process all students}';
    protected $description = 'Apply excess payments to current year billing records';

    public function handle()
    {
        $this->info('🔄 Starting excess payment application...');

        if ($this->option('student')) {
            $this->applyToSpecificStudent($this->option('student'));
        } elseif ($this->option('all')) {
            $this->applyToAllStudents();
        } else {
            $this->error('Please specify --student=ID or --all');
            return 1;
        }

        $this->info('✅ Excess payment application completed!');
        return 0;
    }

    private function applyToSpecificStudent($studentId)
    {
        $student = Student::with(['classRoom', 'academicYear'])->find($studentId);
        
        if (!$student) {
            $this->error("Student with ID {$studentId} not found!");
            return;
        }

        $this->info("Processing student: {$student->name} (ID: {$studentId})");
        
        $this->applyExcessPaymentToCurrentBilling($student);
    }

    private function applyToAllStudents()
    {
        $this->info('Processing all students...');
        
        $students = Student::with(['classRoom', 'academicYear'])->get();
        $progressBar = $this->output->createProgressBar($students->count());
        $progressBar->start();

        $processedCount = 0;
        $appliedCount = 0;

        foreach ($students as $student) {
            $result = $this->applyExcessPaymentToCurrentBilling($student);
            if ($result) {
                $appliedCount++;
            }
            
            $processedCount++;
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
        
        $this->info("Processed: {$processedCount} students");
        $this->info("Students with excess payments applied: {$appliedCount}");
    }

    private function applyExcessPaymentToCurrentBilling($student)
    {
        // Get excess payment billing record
        $excessBilling = BillingRecord::where('student_id', $student->id)
            ->where('notes', 'LIKE', '%Excess Payment Transfer%')
            ->first();

        if (!$excessBilling) {
            return false; // No excess payment found
        }

        if ($excessBilling->remaining_balance <= 0) {
            $this->info("Student {$student->id} excess payment already fully applied");
            return false;
        }

        $excessAmount = $excessBilling->remaining_balance;
        $this->info("Found excess payment: " . number_format($excessAmount));

        // Get current academic year billing records (excluding excess payment record)
        $currentAcademicYear = $student->academicYear;
        if (!$currentAcademicYear) {
            $this->error("No academic year found for student {$student->id}");
            return false;
        }

        $currentBillingRecords = BillingRecord::where('student_id', $student->id)
            ->where('origin_year', $currentAcademicYear->year_start . '-' . $currentAcademicYear->year_end)
            ->where('notes', 'NOT LIKE', '%Excess Payment Transfer%')
            ->where('remaining_balance', '>', 0)
            ->orderBy('due_date', 'asc') // Apply to oldest bills first
            ->get();

        if ($currentBillingRecords->isEmpty()) {
            $this->info("No current billing records found for student {$student->id}");
            return false;
        }

        $remainingExcess = $excessAmount;
        $appliedToRecords = [];

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

            $appliedToRecords[] = [
                'id' => $billingRecord->id,
                'amount' => $amountToApply,
                'remaining' => $billingRecord->remaining_balance
            ];

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

        // Create payment record for the applied excess
        $appliedAmount = $excessAmount - $remainingExcess;
        if ($appliedAmount > 0) {
            $this->createExcessPaymentRecord($student, $appliedAmount, $appliedToRecords);
        }

        $this->info("Applied excess payment: " . number_format($appliedAmount) . " to " . count($appliedToRecords) . " billing records");
        
        return true;
    }

    private function createExcessPaymentRecord($student, $amount, $appliedToRecords)
    {
        // Create a payment record to track the excess payment application
        $payment = new Payment();
        $payment->student_id = $student->id;
        $payment->payment_date = now()->toDateString();
        $payment->total_amount = $amount;
        $payment->payment_method = 'transfer'; // Use valid enum value
        $payment->reference_number = 'EXCESS-' . now()->format('YmdHis');
        $payment->receipt_number = 'EXCESS-' . now()->format('YmdHis');
        $payment->kasir_id = 1; // System user
        $payment->notes = "Excess payment application from previous year - Applied to " . count($appliedToRecords) . " billing records";
        $payment->status = 'verified';
        $payment->billing_record_id = null; // Not tied to specific billing record
        $payment->save();

        $this->info("Created excess payment record: " . number_format($amount));
    }
}
