<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Student;
use App\Models\Payment;
use App\Models\BillingRecord;
use App\Models\AcademicYear;

class HandleExcessPayments extends Command
{
    protected $signature = 'excess:handle {--student= : Specific student ID to process} {--all : Process all students}';
    protected $description = 'Handle excess payments and transfer to new academic year billing';

    public function handle()
    {
        $this->info('🔄 Starting excess payment handling...');

        if ($this->option('student')) {
            $this->handleSpecificStudent($this->option('student'));
        } elseif ($this->option('all')) {
            $this->handleAllStudents();
        } else {
            $this->error('Please specify --student=ID or --all');
            return 1;
        }

        $this->info('✅ Excess payment handling completed!');
        return 0;
    }

    private function handleSpecificStudent($studentId)
    {
        $student = Student::with(['classRoom', 'academicYear'])->find($studentId);
        
        if (!$student) {
            $this->error("Student with ID {$studentId} not found!");
            return;
        }

        $this->info("Processing student: {$student->name} (ID: {$studentId})");
        
        $excessAmount = $this->calculateExcessPayment($student);
        
        if ($excessAmount > 0) {
            $this->info("Found excess payment: " . number_format($excessAmount));
            $this->applyExcessToNewYear($student, $excessAmount);
        } else {
            $this->info("No excess payment found for this student.");
        }
    }

    private function handleAllStudents()
    {
        $this->info('Processing all students...');
        
        $students = Student::with(['classRoom', 'academicYear'])->get();
        $progressBar = $this->output->createProgressBar($students->count());
        $progressBar->start();

        $processedCount = 0;
        $excessCount = 0;

        foreach ($students as $student) {
            $excessAmount = $this->calculateExcessPayment($student);
            
            if ($excessAmount > 0) {
                $this->applyExcessToNewYear($student, $excessAmount);
                $excessCount++;
            }
            
            $processedCount++;
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
        
        $this->info("Processed: {$processedCount} students");
        $this->info("Students with excess payments: {$excessCount}");
    }

    private function calculateExcessPayment($student)
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
                        $this->info("  Payment ID {$payment->id}: " . number_format($payment->total_amount) . " for billing " . number_format($billingRecord->amount) . " = excess " . number_format($excessForThisBilling));
                    }
                }
            }
        }
        
        return $totalExcess;
    }

    private function applyExcessToNewYear($student, $excessAmount)
    {
        // Get current academic year
        $currentAcademicYear = $student->academicYear;
        
        if (!$currentAcademicYear) {
            $this->warn("No academic year found for student {$student->id}");
            return;
        }

        // Check if student already has excess payment applied
        $existingExcessBilling = BillingRecord::where('student_id', $student->id)
            ->where('notes', 'LIKE', '%Excess Payment Transfer%')
            ->first();

        if ($existingExcessBilling) {
            $this->info("Student {$student->id} already has excess payment applied: " . number_format($existingExcessBilling->amount));
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
            $this->error("No fee structure found for excess payment billing record");
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

    private function getExcessPaymentDetails($student)
    {
        $billingRecords = BillingRecord::where('student_id', $student->id)->get();
        $payments = Payment::where('student_id', $student->id)->get();
        
        $totalBilling = $billingRecords->sum('amount');
        $totalPayment = $payments->sum('total_amount');
        $excessAmount = $totalPayment - $totalBilling;
        
        return [
            'total_billing' => $totalBilling,
            'total_payment' => $totalPayment,
            'excess_amount' => max(0, $excessAmount),
            'billing_records' => $billingRecords,
            'payments' => $payments
        ];
    }
}
