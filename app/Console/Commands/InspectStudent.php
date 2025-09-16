<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Student;
use App\Models\BillingRecord;
use App\Models\AcademicYear;

class InspectStudent extends Command
{
    protected $signature = 'spp:inspect-student {student_id}';
    protected $description = 'Inspect student debts and billing records';

    public function handle()
    {
        $id = (int) $this->argument('student_id');
        $student = Student::with(['academicYear','classRoom','billingRecords'])->find($id);
        if (!$student) { $this->error('Student not found'); return 1; }

        $this->info('Student: ' . $student->name . ' (ID ' . $student->id . ')');
        $this->line('Level: ' . ($student->classRoom->level ?? '-'));        
        $this->line('Academic Year: ' . ($student->academicYear->year_start ?? '-') . '/' . ($student->academicYear->year_end ?? '-'));
        $this->line('previous_debt: ' . number_format((float)($student->previous_debt ?? 0), 0, ',', '.'));
        $this->line('previous_debt_year: ' . ($student->previous_debt_year ?? '-'));

        $ay = $student->academicYear;
        if ($ay) {
            $hyphen = $ay->year_start . '-' . $ay->year_end;
            $slash = $ay->year_start . '/' . $ay->year_end;
            $current = $student->billingRecords->whereIn('origin_year', [$hyphen,$slash]);
            $this->line('Current year billing count: ' . $current->count());
            $sumRem = $current->sum(function($br){ return (float)$br->remaining_balance; });
            $sumAmt = $current->sum(function($br){ return (float)$br->amount; });
            $this->line('Current year total amount: ' . number_format($sumAmt, 0, ',', '.') . ', remaining: ' . number_format($sumRem, 0, ',', '.'));
        }

        // Previous year
        if ($ay) {
            $prev = AcademicYear::where('year_start', $ay->year_start - 1)->first();
            if ($prev) {
                $hy = $prev->year_start . '-' . $prev->year_end; $sl = $prev->year_start . '/' . $prev->year_end;
                $prevBr = $student->billingRecords->whereIn('origin_year', [$hy,$sl]);
                $this->line('Prev year billing count: ' . $prevBr->count());
                $sumPrevRem = $prevBr->sum(function($br){ return (float)$br->remaining_balance; });
                $this->line('Prev year remaining: ' . number_format($sumPrevRem, 0, ',', '.'));
            }
        }

        return 0;
    }
}
