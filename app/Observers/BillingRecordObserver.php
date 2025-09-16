<?php

namespace App\Observers;

use App\Models\BillingRecord;
use App\Models\Student;
use Illuminate\Support\Facades\Log;

class BillingRecordObserver
{
    /**
     * Handle the BillingRecord "created" event.
     */
    public function created(BillingRecord $billingRecord)
    {
        Log::info('BillingRecord created - checking for excess payment application', [
            'billing_record_id' => $billingRecord->id,
            'student_id' => $billingRecord->student_id,
            'amount' => $billingRecord->amount
        ]);

        // Check if this student has excess payment that can be applied
        $this->applyExistingExcessPayment($billingRecord);
    }

    /**
     * Apply existing excess payment to new billing record
     */
    private function applyExistingExcessPayment(BillingRecord $billingRecord)
    {
        try {
            $student = Student::find($billingRecord->student_id);
            if (!$student) {
                return;
            }

            // Get excess payment billing record
            $excessBilling = BillingRecord::where('student_id', $student->id)
                ->where('notes', 'LIKE', '%Excess Payment Transfer%')
                ->where('remaining_balance', '>', 0)
                ->first();

            if (!$excessBilling) {
                return; // No excess payment to apply
            }

            // Skip if this is the excess payment billing record itself
            if ($billingRecord->id === $excessBilling->id) {
                return;
            }

            // Skip if this billing record is not for current academic year
            $currentAcademicYear = $student->academicYear;
            if (!$currentAcademicYear) {
                return;
            }

            $currentYearKey = $currentAcademicYear->year_start . '-' . $currentAcademicYear->year_end;
            if ($billingRecord->origin_year !== $currentYearKey) {
                return;
            }

            $excessAmount = $excessBilling->remaining_balance;
            $billingAmount = $billingRecord->remaining_balance;
            $amountToApply = min($excessAmount, $billingAmount);

            if ($amountToApply > 0) {
                // Update billing record
                $billingRecord->remaining_balance = $billingAmount - $amountToApply;
                
                // Update status based on remaining balance
                if ($billingRecord->remaining_balance <= 0) {
                    $billingRecord->status = 'fully_paid';
                } else {
                    $billingRecord->status = 'partially_paid';
                }
                
                $billingRecord->save();

                // Update excess payment billing record
                $excessBilling->remaining_balance = $excessAmount - $amountToApply;
                if ($excessBilling->remaining_balance <= 0) {
                    $excessBilling->status = 'fully_paid';
                } else {
                    $excessBilling->status = 'partially_paid';
                }
                $excessBilling->save();

                Log::info("Applied existing excess payment: " . number_format($amountToApply) . " to new billing record {$billingRecord->id} for student {$student->id}");
            }

        } catch (\Exception $e) {
            Log::error('Error applying existing excess payment to new billing record', [
                'billing_record_id' => $billingRecord->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
