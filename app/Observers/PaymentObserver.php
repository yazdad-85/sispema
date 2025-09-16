<?php

namespace App\Observers;

use App\Models\Payment;
use App\Models\BillingRecord;
use App\Models\Student;
use App\Models\FeeStructure;
use Illuminate\Support\Facades\Log;

class PaymentObserver
{
    /**
     * Handle the Payment "created" event.
     */
    public function created(Payment $payment)
    {
        Log::info('Payment created - checking for excess payment', [
            'payment_id' => $payment->id,
            'student_id' => $payment->student_id,
            'total_amount' => $payment->total_amount
        ]);

        // Check if this payment creates an excess
        $this->handleExcessPayment($payment);
    }

    /**
     * Handle the Payment "updated" event.
     */
    public function updated(Payment $payment)
    {
        // Only process if status changed to verified/completed
        if ($payment->wasChanged('status') && 
            in_array($payment->status, ['verified', 'completed'])) {
            
            Log::info('Payment status updated to verified - checking for excess payment', [
                'payment_id' => $payment->id,
                'student_id' => $payment->student_id,
                'status' => $payment->status
            ]);

            $this->handleExcessPayment($payment);
        }
    }

    /**
     * Handle excess payment detection and processing
     */
    private function handleExcessPayment(Payment $payment)
    {
        try {
            $student = Student::find($payment->student_id);
            if (!$student) {
                return;
            }

            // Get the billing record for this payment
            $billingRecord = BillingRecord::find($payment->billing_record_id);
            if (!$billingRecord) {
                return;
            }

            // Calculate excess payment for this specific billing record
            $excessAmount = $payment->total_amount - $billingRecord->amount;
            
            if ($excessAmount > 0) {
                Log::info('Excess payment detected', [
                    'student_id' => $student->id,
                    'payment_id' => $payment->id,
                    'billing_record_id' => $billingRecord->id,
                    'excess_amount' => $excessAmount
                ]);

                // Create excess payment billing record
                $this->createExcessPaymentBillingRecord($student, $excessAmount);
                
                // Update student credit balance
                $this->updateStudentCreditBalance($student, $excessAmount);
                
                // Apply excess payment to current billing
                $this->applyExcessPaymentToCurrentBilling($student);
            }

        } catch (\Exception $e) {
            Log::error('Error handling excess payment', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Create billing record for excess payment
     */
    private function createExcessPaymentBillingRecord(Student $student, $excessAmount)
    {
        // Check if student already has excess payment applied
        $existingExcessBilling = BillingRecord::where('student_id', $student->id)
            ->where('notes', 'LIKE', '%Excess Payment Transfer%')
            ->first();

        if ($existingExcessBilling) {
            // Update existing excess billing record
            $existingExcessBilling->amount += $excessAmount;
            $existingExcessBilling->remaining_balance += $excessAmount;
            $existingExcessBilling->notes = "Excess Payment Transfer from previous year - Total Amount: " . number_format($existingExcessBilling->amount);
            $existingExcessBilling->save();
            
            Log::info("Updated existing excess payment billing record for student {$student->id}: " . number_format($excessAmount));
            return;
        }

        // Get current academic year
        $currentAcademicYear = $student->academicYear;
        if (!$currentAcademicYear) {
            return;
        }

        // Get a default fee structure for excess payment
        $defaultFeeStructure = FeeStructure::where('is_active', true)
            ->where('academic_year_id', $currentAcademicYear->id)
            ->first();
        
        if (!$defaultFeeStructure) {
            $defaultFeeStructure = FeeStructure::where('is_active', true)->first();
        }
        
        if (!$defaultFeeStructure) {
            $defaultFeeStructure = FeeStructure::first();
        }
        
        if (!$defaultFeeStructure) {
            Log::error("No fee structure found for excess payment billing record for student {$student->id}");
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

        Log::info("Created excess payment billing record for student {$student->id}: " . number_format($excessAmount));
    }

    /**
     * Update student credit balance
     */
    private function updateStudentCreditBalance(Student $student, $excessAmount)
    {
        // Get current academic year
        $currentAcademicYear = $student->academicYear;
        if (!$currentAcademicYear) {
            return;
        }

        // Update student's credit_balance field
        $currentCreditBalance = $student->credit_balance ?? 0;
        $newCreditBalance = $currentCreditBalance + $excessAmount;
        
        $student->update([
            'credit_balance' => $newCreditBalance,
            'credit_balance_year' => $currentAcademicYear->year_start
        ]);
        
        Log::info("Updated credit_balance for student {$student->id}: " . number_format($newCreditBalance));
    }

    /**
     * Apply excess payment to current billing
     */
    private function applyExcessPaymentToCurrentBilling(Student $student)
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
            Log::info("Applied excess payment: " . number_format($appliedAmount) . " to current billing for student {$student->id}");
        }
    }
}
