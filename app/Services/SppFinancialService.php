<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Category;
use App\Models\ActivityPlan;
use App\Models\ActivityRealization;
use App\Models\CashBook;
use App\Models\AcademicYear;
use App\Models\Student;
use App\Models\FeeStructure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SppFinancialService
{
    /**
     * Process SPP payment and create financial records
     */
    public function processSppPayment(Payment $payment)
    {
        // Guard: Only process verified/completed payments
        if (!in_array($payment->status, [Payment::STATUS_VERIFIED, Payment::STATUS_COMPLETED])) {
            Log::info('Skipping SPP payment processing due to status', [
                'payment_id' => $payment->id,
                'status' => $payment->status
            ]);
            return null;
        }

        // Duplicate guards
        $existingCashBook = CashBook::where('reference_type', 'payment')
            ->where('reference_id', $payment->id)
            ->first();
        $cashbookExists = (bool) $existingCashBook;
        $realizationExists = ActivityRealization::where('is_auto_generated', true)
            ->where('proof', $payment->receipt_number)
            ->exists();

        try {
            DB::beginTransaction();

            // Get or create SPP category
            $sppCategory = $this->getOrCreateSppCategory($payment);

            // Get current academic year
            $currentAcademicYear = AcademicYear::where('is_current', true)->first();
            if (!$currentAcademicYear) {
                throw new \Exception('Tidak ada tahun ajaran aktif');
            }

            // Get or create SPP activity plan for current year, grouped by institution + level
            $sppPlan = $this->getOrCreateSppPlanForPayment($currentAcademicYear, $sppCategory, $payment);

            // Ensure realization exists
            if ($realizationExists) {
                // Fetch existing realization to pass to cashbook if needed
                $realization = ActivityRealization::where('is_auto_generated', true)
                    ->where('proof', $payment->receipt_number)
                    ->first();
            } else {
                $realization = $this->createSppRealization($payment, $sppPlan);
            }

            // Add or sync cash book
            if (!$cashbookExists) {
                $this->addToCashBook($payment, $realization);
            } else {
                // Sync existing entry if amount/description/date differ
                $desiredDescription = 'Pembayaran SPP - ' . $payment->student->name;
                if ($payment->student->scholarshipCategory) {
                    $desiredDescription .= ' (' . $payment->student->scholarshipCategory->name . ')';
                }

                $needsUpdate = false;
                if ((float)$existingCashBook->credit !== (float)$payment->total_amount) $needsUpdate = true;
                if ($existingCashBook->description !== $desiredDescription) $needsUpdate = true;
                if ($existingCashBook->date->format('Y-m-d') !== (new \Carbon\Carbon($payment->payment_date))->format('Y-m-d')) $needsUpdate = true;

                if ($needsUpdate) {
                    $existingCashBook->date = $payment->payment_date;
                    $existingCashBook->description = $desiredDescription;
                    $existingCashBook->debit = 0;
                    $existingCashBook->credit = $payment->total_amount;
                    $existingCashBook->save();

                    // Recalculate balances since we changed a historical row
                    $this->recalculateCashBookBalances();
                }
            }

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
        if ($payment->student && $payment->student->scholarshipCategory) {
            $categoryName .= ' - ' . $payment->student->scholarshipCategory->name;
        }
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
     * Return level label from student class
     */
    private function getStudentLevel(Student $student): ?string
    {
        $class = $student->classRoom; // relation name in project is classRoom
        if (!$class) return null;
        return $class->safe_level ?? $class->level ?? null;
    }

    

    /**
     * Get or create SPP activity plan for academic year, grouped by institution and level
     */
    private function getOrCreateSppPlanForPayment(AcademicYear $academicYear, Category $category, Payment $payment): ActivityPlan
    {
        $student = $payment->student()->with(['institution', 'classRoom'])->first();
        $institution = $student ? $student->institution : null;
        $level = $student ? ($this->getStudentLevel($student) ?? '-') : '-';
        $institutionId = $institution ? $institution->id : null;
        $institutionName = $institution ? $institution->name : 'Lembaga';

        $planName = 'Penerimaan SPP (' . $institutionName . ') (' . $level . ')';

        $plan = ActivityPlan::where('academic_year_id', $academicYear->id)
            ->where('category_id', $category->id)
            ->where('institution_id', $institutionId)
            ->where('level', $level)
            ->first();

        if (!$plan) {
            $budget = $institutionId && $level
                ? $this->calculatePlanBudget($institutionId, $academicYear->id, $level)
                : 0;

            $plan = ActivityPlan::create([
                'academic_year_id' => $academicYear->id,
                'category_id' => $category->id,
                'institution_id' => $institutionId,
                'level' => $level,
                'name' => $planName,
                'start_date' => $academicYear->year_start . '-07-01',
                'end_date' => $academicYear->year_end . '-06-30',
                'budget_amount' => $budget,
                'description' => 'Rencana penerimaan SPP ' . $institutionName . ' level ' . $level . ' ' . $academicYear->year_start . '/' . $academicYear->year_end,
            ]);
        } else {
            // Keep budget fresh (e.g., setelah penambahan siswa/struktur biaya)
            if ($plan->institution_id && $plan->level) {
                $plan->budget_amount = $this->calculatePlanBudget($plan->institution_id, $academicYear->id, $plan->level);
                $plan->save();
            }
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
            'transaction_type' => 'credit',
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
            0,
            $payment->total_amount,
            'payment',
            $payment->id
        );
    }

    private function recalculateCashBookBalances(): void
    {
        $balance = 0;
        $entries = CashBook::orderBy('date')->orderBy('id')->get();
        foreach ($entries as $entry) {
            $balance = $balance + (float) $entry->credit - (float) $entry->debit;
            $entry->balance = $balance;
            $entry->save();
        }
    }

    /**
     * Process all existing SPP payments
     */
    public function processAllExistingPayments()
    {
        $payments = Payment::whereIn('status', ['verified', 'completed'])
            ->whereDoesntHave('cashBookEntries')
            ->with(['student.scholarshipCategory', 'student.classRoom', 'student.institution'])
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

    // Expose calculatePlanBudget for other components
    public function calculatePlanBudget(int $institutionId, int $academicYearId, string $level): float
    {
        $students = Student::where('institution_id', $institutionId)
            ->where('academic_year_id', $academicYearId)
            ->whereHas('classRoom', function ($q) use ($level) {
                $q->where('level', $level);
            })
            ->with(['scholarshipCategory', 'classRoom'])
            ->get();

        $total = 0.0;
        foreach ($students as $s) {
            $currentLevel = $this->getStudentLevel($s);
            if (!$currentLevel) continue;
            $fs = FeeStructure::findByLevel($s->institution_id, $academicYearId, $currentLevel);
            $yearlyAmount = $fs ? (float)$fs->yearly_amount : 0.0;

            $scholarshipPct = (float) (optional($s->scholarshipCategory)->discount_percentage ?? 0);
            $categoryName = optional($s->scholarshipCategory)->name;
            $applies = true;
            if (in_array($categoryName, ['Alumni', 'Yatim Piatu, Piatu, Yatim'])) {
                $applies = in_array($currentLevel, ['VII', 'X']);
            }
            $discount = $applies ? $yearlyAmount * ($scholarshipPct / 100) : 0;
            $effectiveYearly = max(0, $yearlyAmount - $discount);
            $total += $effectiveYearly;
        }
        return $total;
    }

    // Public helper for backfill: fetch or create plan by components
    public function getSppPlanForBackfill(AcademicYear $academicYear, Category $category, Student $student, string $institutionName, string $level): ?ActivityPlan
    {
        $institution = $student->institution;
        if (!$institution) return null;
        $institutionId = $institution->id;

        $plan = ActivityPlan::where('academic_year_id', $academicYear->id)
            ->where('category_id', $category->id)
            ->where('institution_id', $institutionId)
            ->where('level', $level)
            ->first();

        if (!$plan) {
            $plan = ActivityPlan::create([
                'academic_year_id' => $academicYear->id,
                'category_id' => $category->id,
                'institution_id' => $institutionId,
                'level' => $level,
                'name' => 'Penerimaan SPP (' . $institutionName . ') (' . $level . ')',
                'start_date' => $academicYear->year_start . '-07-01',
                'end_date' => $academicYear->year_end . '-06-30',
                'budget_amount' => $this->calculatePlanBudget($institutionId, $academicYear->id, $level),
                'description' => 'Rencana penerimaan SPP ' . $institutionName . ' level ' . $level . ' ' . $academicYear->year_start . '/' . $academicYear->year_end,
            ]);
        }

        return $plan;
    }
}
