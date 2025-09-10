<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ActivityRealization;
use App\Models\ActivityPlan;
use App\Models\AcademicYear;
use App\Models\Payment;
use App\Models\Student;
use App\Models\Category;
use App\Services\SppFinancialService;
use Illuminate\Support\Facades\DB;

class SppBackfillPlans extends Command
{
    protected $signature = 'spp:backfill-plans {--dry-run : Show actions without changing data}';

    protected $description = 'Backfill rencana kegiatan Penerimaan SPP per lembaga+level dan relink realisasi + hitung ulang budget';

    public function handle()
    {
        $this->info('🔄 Backfilling SPP plans (by institution + level)...');
        $dry = (bool) $this->option('dry-run');

        $service = new SppFinancialService();

        $countRelink = 0;
        $countPlans = 0;
        $years = AcademicYear::all()->keyBy('id');

        // Ambil semua realisasi auto-generated dari pembayaran SPP
        $reals = ActivityRealization::where('is_auto_generated', true)
            ->with(['plan', 'plan.category'])
            ->get();

        foreach ($reals as $real) {
            // Dapatkan payment
            $payment = Payment::find($real->proof ? null : $real->id); // fallback: real->proof tidak menyimpan id; gunakan reference di CashBook
            if (!$payment) {
                // Ambil dari cash_book reference
                $cb = \App\Models\CashBook::where('reference_type', 'payment')
                    ->where('date', $real->date)
                    ->where('credit', $real->total_amount)
                    ->first();
                if ($cb) $payment = Payment::find($cb->reference_id);
            }
            if (!$payment) continue;

            $student = $payment->student()->with(['institution','classRoom','scholarshipCategory'])->first();
            if (!$student) continue;

            $level = $student->classRoom ? ($student->classRoom->safe_level ?? $student->classRoom->level ?? '-') : '-';
            $institution = $student->institution;
            $institutionId = $institution ? $institution->id : null;
            $institutionName = $institution ? $institution->name : 'Lembaga';

            // Tahun ajaran aktif rencana asal realisasi
            $ay = $real->plan ? $years->get($real->plan->academic_year_id) : AcademicYear::where('is_current', true)->first();
            if (!$ay) continue;

            // Kategori tetap sesuai existing plan (pemasukan SPP ...)
            $category = $real->plan ? $real->plan->category : Category::where('type','pemasukan')->first();
            if (!$category) continue;

            // Pastikan rencana target ada melalui service
            if (!$dry) {
                $targetPlan = (new SppFinancialService())->getSppPlanForBackfill($ay, $category, $student, $institutionName, $level);
                if ($targetPlan && $real->plan_id !== $targetPlan->id) {
                    $real->plan_id = $targetPlan->id;
                    $real->save();
                    $countRelink++;
                }
            } else {
                $countRelink++;
            }
        }

        // Hitung ulang budget untuk seluruh plan yang punya institution_id & level
        $plans = ActivityPlan::whereNotNull('institution_id')->whereNotNull('level')->get();
        foreach ($plans as $plan) {
            if ($dry) { $countPlans++; continue; }
            $plan->budget_amount = $service->calculatePlanBudget($plan->institution_id, $plan->academic_year_id, $plan->level);
            $plan->save();
            $countPlans++;
        }

        $this->info("✅ Backfill selesai. Relinked: {$countRelink}, Plans recalculated: {$countPlans}");
        if ($dry) $this->warn('DRY RUN: tidak ada perubahan yang disimpan.');

        return 0;
    }
}
