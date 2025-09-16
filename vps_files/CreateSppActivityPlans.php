<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\BillingRecord;
use App\Models\ActivityPlan;
use App\Models\Category;
use App\Models\AcademicYear;
use App\Models\Institution;

class CreateSppActivityPlans extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'spp:create-activity-plans {--dry-run : Show what would be created without actually creating}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create activity plans for SPP based on billing records';

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

        $this->info('🚀 Creating SPP activity plans...');
        
        // Get SPP category
        $sppCategory = Category::where('name', 'Pembayaran SPP')->first();
        if (!$sppCategory) {
            $this->error('❌ SPP category not found!');
            return 1;
        }
        
        // Get active academic year
        $academicYear = AcademicYear::where('status', 'active')->first();
        if (!$academicYear) {
            $this->error('❌ No active academic year found!');
            return 1;
        }
        
        // Group billing records by institution and level
        $billingGroups = BillingRecord::with(['student.classRoom', 'student.institution'])
            ->whereHas('student', function($query) {
                $query->whereNotNull('class_id');
            })
            ->get()
            ->groupBy(function($billing) {
                return $billing->student->institution_id . '_' . ($billing->student->classRoom ? $billing->student->classRoom->level : 'Unknown');
            });
        
        $this->info("📊 Found {$billingGroups->count()} groups to process");
        
        $created = 0;
        $errors = 0;
        
        foreach ($billingGroups as $groupKey => $billings) {
            try {
                $firstBilling = $billings->first();
                $institution = $firstBilling->student->institution;
                $level = $firstBilling->student->classRoom ? $firstBilling->student->classRoom->level : 'Unknown';
                $totalAmount = $billings->sum('amount');
                $studentCount = $billings->count();
                
                // Check if activity plan already exists
                $existingPlan = ActivityPlan::where('academic_year_id', $academicYear->id)
                    ->where('institution_id', $institution->id)
                    ->where('level', $level)
                    ->where('category_id', $sppCategory->id)
                    ->first();
                
                if ($existingPlan) {
                    $this->line("⏭️  Activity plan already exists for {$institution->name} - Level {$level}");
                    continue;
                }
                
                if ($isDryRun) {
                    $this->line("Would create activity plan for: {$institution->name} - Level {$level} (Amount: " . number_format($totalAmount, 0, ',', '.') . ", Students: {$studentCount})");
                    $created++;
                    continue;
                }
                
                // Create activity plan
                ActivityPlan::create([
                    'academic_year_id' => $academicYear->id,
                    'category_id' => $sppCategory->id,
                    'institution_id' => $institution->id,
                    'level' => $level,
                    'name' => "Penerimaan SPP - {$institution->name} - Level {$level}",
                    'start_date' => $academicYear->year_start . '-07-01', // Juli
                    'end_date' => $academicYear->year_end . '-06-30', // Juni
                    'budget_amount' => $totalAmount,
                    'description' => "Rencana penerimaan SPP untuk {$studentCount} siswa di {$institution->name} level {$level}",
                    'unit_price' => $firstBilling->amount,
                    'equivalent_1' => $studentCount,
                    'unit_1' => 'siswa'
                ]);
                
                $created++;
                $this->line("✅ Created activity plan for: {$institution->name} - Level {$level}");
                
            } catch (\Exception $e) {
                $errors++;
                $this->error("❌ Failed to create activity plan for group {$groupKey}: " . $e->getMessage());
            }
        }
        
        $this->info("✅ SPP activity plans creation completed!");
        $this->info("📊 Created: {$created} activity plans");
        $this->info("❌ Errors: {$errors} activity plans");
        
        if ($errors > 0) {
            $this->warn("⚠️  Some activity plans failed to create. Check logs for details.");
            return 1;
        }
        
        return 0;
    }
}
