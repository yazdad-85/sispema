<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Student;
use App\Models\FeeStructure;
use Carbon\Carbon;

class CreateBillingRecords extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'billing:create-records {--dry-run : Show what would be created without actually creating}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create billing records for all students who don\'t have them';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        
        if ($isDryRun) {
            $this->info('🔍 DRY RUN MODE - No changes will be made');
        }

        $this->info('🚀 Creating billing records for students...');
        
        $students = Student::with(['classRoom', 'institution', 'academicYear'])
            ->whereDoesntHave('billingRecords')
            ->get();
        
        $this->info("📊 Found {$students->count()} students without billing records");
        
        $created = 0;
        $errors = 0;
        
        foreach ($students as $student) {
            try {
                if ($isDryRun) {
                    $this->line("Would create billing record for: {$student->name}");
                    $created++;
                    continue;
                }
                
                $this->ensureBillingRecords($student);
                $created++;
                
                if ($created % 100 == 0) {
                    $this->info("Processed {$created} students...");
                }
                
            } catch (\Exception $e) {
                $errors++;
                $this->error("Failed to create billing record for student {$student->id}: " . $e->getMessage());
            }
        }
        
        $this->info("✅ Billing records creation completed!");
        $this->info("📊 Created: {$created} billing records");
        $this->info("❌ Errors: {$errors} billing records");
        
        if ($errors > 0) {
            $this->warn("⚠️  Some billing records failed to create. Check logs for details.");
            return 1;
        }
        
        return 0;
    }
    
    /**
     * Auto-generate billing records for students who don't have them
     */
    private function ensureBillingRecords($student)
    {
        // Check if student already has an annual billing record for CURRENT academic year
        $currentYearName = optional($student->academicYear) ? ($student->academicYear->year_start.'-'.$student->academicYear->year_end) : null;
        $existingBilling = $student->billingRecords()
            ->where('notes', 'ANNUAL')
            ->when($currentYearName, function($q) use ($currentYearName){
                $q->where('origin_year', $currentYearName)->orWhere('origin_year', str_replace('-', '/', $currentYearName));
            })
            ->first();
        
        if (!$existingBilling) {
            // Get the student's class level (prefer safe_level)
            $level = $student->classRoom ? ($student->classRoom->safe_level ?? $student->classRoom->level) : 'VII';
            
            // Try to find fee structure by class_id first (most specific)
            $feeStructure = FeeStructure::where('institution_id', $student->institution_id)
                ->where('academic_year_id', $student->academic_year_id)
                ->where('class_id', $student->class_id)
                ->first();
            
            // Fallback: try by level
            if (!$feeStructure) {
                $feeStructure = FeeStructure::where('institution_id', $student->institution_id)
                    ->where('academic_year_id', $student->academic_year_id)
                    ->whereHas('class', function($q) use ($level){
                        $q->where('level', $level);
                    })
                    ->first();
            }
            
            // Final fallback: try any fee structure for this institution and academic year
            if (!$feeStructure) {
                $feeStructure = FeeStructure::where('institution_id', $student->institution_id)
                    ->where('academic_year_id', $student->academic_year_id)
                    ->first();
            }

            if ($feeStructure) {
                // Get academic year info
                $academicYear = $student->academicYear;
                $dueDate = $academicYear ? Carbon::create($academicYear->year_end, 12, 31) : now()->addYear();
                
                // Create annual billing record with all required fields
                $student->billingRecords()->create([
                    'fee_structure_id' => $feeStructure->id,
                    'origin_year' => $academicYear ? $academicYear->getNameAttribute() : date('Y'),
                    'origin_class' => $student->classRoom ? $student->classRoom->class_name : 'Unknown',
                    'amount' => $feeStructure->yearly_amount,
                    'remaining_balance' => $feeStructure->yearly_amount, // Initially same as amount
                    'status' => 'active',
                    'due_date' => $dueDate,
                    'billing_month' => 'ANNUAL',
                    'notes' => 'ANNUAL',
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                
                \Log::info("Auto-generated billing record for student {$student->id}: Rp " . number_format($feeStructure->yearly_amount, 0, ',', '.'));
            } else {
                \Log::warning("No fee structure found for student {$student->id} (Level: {$level}, Institution: {$student->institution_id}, Academic Year: {$student->academic_year_id})");
                throw new \Exception("No fee structure found for student {$student->id}");
            }
        }
    }
}
