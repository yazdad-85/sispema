<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Category;
use App\Models\ActivityPlan;
use App\Models\ActivityRealization;
use App\Models\CashBook;
use App\Models\AcademicYear;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SppFinancialService
{
    /**
     * Process SPP payment and create financial records
     */
    public function processSppPayment(Payment $payment)
    {
        try {
            DB::beginTransaction();

            // Get or create SPP category
            $sppCategory = $this->getOrCreateSppCategory($payment);

            // Get current academic year
            $currentAcademicYear = AcademicYear::where('is_current', true)->first();
            if (!$currentAcademicYear) {
                throw new \Exception('Tidak ada tahun ajaran aktif');
            }

            // Get or create SPP activity plan for current year
            $sppPlan = $this->getOrCreateSppPlan($currentAcademicYear, $sppCategory);

            // Create realization for SPP payment
            $realization = $this->createSppRealization($payment, $sppPlan);

            // Add to cash book directly (since auto-generated realizations don't create cash book entries)
            $this->addToCashBook($payment, $realization);

            DB::commit();

            Log::info('SPP payment processed successfully', [
                'payment_id' => $payment->id,
                'student_id' => $payment->student_id,
                'amount' => $payment->total_amount,
                'realization_id' => $realization->id
            ]);

            return $realization;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to process SPP payment', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get or create SPP category based on payment details
     */
    private function getOrCreateSppCategory(Payment $payment)
    {
        $categoryName = 'Pembayaran SPP';
        
        // Add scholarship info if applicable
        if ($payment->student && $payment->student->scholarshipCategory) {
            $categoryName .= ' - ' . $payment->student->scholarshipCategory->name;
        }

        // Add payment method info
        $categoryName .= ' (' . ucfirst($payment->payment_method) . ')';

        $category = Category::where('name', $categoryName)
            ->where('type', 'pemasukan')
            ->first();

        if (!$category) {
            $category = Category::create([
                'name' => $categoryName,
                'type' => 'pemasukan',
                'is_active' => true
            ]);
        }

        return $category;
    }

    /**
     * Get or create SPP activity plan for academic year
     */
    private function getOrCreateSppPlan(AcademicYear $academicYear, Category $category)
    {
        $planName = 'Penerimaan SPP ' . $academicYear->year_start . '/' . $academicYear->year_end;

        $plan = ActivityPlan::where('academic_year_id', $academicYear->id)
            ->where('category_id', $category->id)
            ->where('name', 'like', '%Penerimaan SPP%')
            ->first();

        if (!$plan) {
            $plan = ActivityPlan::create([
                'academic_year_id' => $academicYear->id,
                'category_id' => $category->id,
                'name' => $planName,
                'start_date' => $academicYear->year_start . '-07-01', // July 1st
                'end_date' => $academicYear->year_end . '-06-30', // June 30th
                'budget_amount' => 0, // Will be updated as payments come in
                'description' => 'Rencana penerimaan SPP untuk tahun ajaran ' . $academicYear->year_start . '/' . $academicYear->year_end
            ]);
        }

        return $plan;
    }

    /**
     * Create realization for SPP payment
     */
    private function createSppRealization(Payment $payment, ActivityPlan $plan)
    {
        $description = 'Pembayaran SPP - ' . $payment->student->name;
        if ($payment->student->scholarshipCategory) {
            $description .= ' (' . $payment->student->scholarshipCategory->name . ')';
        }
        $description .= ' - ' . ucfirst($payment->payment_method);

        return ActivityRealization::create([
            'plan_id' => $plan->id,
            'date' => $payment->payment_date,
            'description' => $description,
            'transaction_type' => 'credit', // Pemasukan SPP
            'unit_price' => $payment->total_amount,
            'equivalent_1' => 1,
            'equivalent_2' => 0,
            'equivalent_3' => 0,
            'total_amount' => $payment->total_amount,
            'proof' => $payment->receipt_number,
            'status' => 'confirmed',
            'is_auto_generated' => true
        ]);
    }

    /**
     * Add payment to cash book
     */
    private function addToCashBook(Payment $payment, ActivityRealization $realization)
    {
        $description = 'Pembayaran SPP - ' . $payment->student->name;
        if ($payment->student->scholarshipCategory) {
            $description .= ' (' . $payment->student->scholarshipCategory->name . ')';
        }

        return CashBook::addEntry(
            $payment->payment_date,
            $description,
            0, // debit
            $payment->total_amount, // credit (pemasukan)
            'payment',
            $payment->id
        );
    }

    /**
     * Process all existing SPP payments
     */
    public function processAllExistingPayments()
    {
        $payments = Payment::where('status', 'completed')
            ->whereDoesntHave('cashBookEntries')
            ->with(['student.scholarshipCategory'])
            ->get();

        $processed = 0;
        $errors = 0;

        foreach ($payments as $payment) {
            try {
                $this->processSppPayment($payment);
                $processed++;
            } catch (\Exception $e) {
                $errors++;
                Log::error('Failed to process existing payment', [
                    'payment_id' => $payment->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return [
            'processed' => $processed,
            'errors' => $errors,
            'total' => $payments->count()
        ];
    }

    /**
     * Get financial summary for SPP
     */
    public function getSppFinancialSummary($academicYearId = null)
    {
        $query = Payment::where('status', 'completed');

        if ($academicYearId) {
            $query->whereHas('student', function($q) use ($academicYearId) {
                $q->where('academic_year_id', $academicYearId);
            });
        }

        $payments = $query->get();

        return [
            'total_payments' => $payments->count(),
            'total_amount' => $payments->sum('total_amount'),
            'by_payment_method' => $payments->groupBy('payment_method')->map(function($group) {
                return [
                    'count' => $group->count(),
                    'amount' => $group->sum('total_amount')
                ];
            }),
            'by_scholarship' => $payments->groupBy(function($payment) {
                return $payment->student->scholarshipCategory ? $payment->student->scholarshipCategory->name : 'Reguler';
            })->map(function($group) {
                return [
                    'count' => $group->count(),
                    'amount' => $group->sum('total_amount')
                ];
            })
        ];
    }
}
