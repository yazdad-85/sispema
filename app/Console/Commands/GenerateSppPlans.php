<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Student;
use App\Models\AcademicYear;
use App\Models\Institution;
use App\Models\ClassModel;
use App\Models\ActivityPlan;
use App\Models\Category;
use App\Services\SppFinancialService;

class GenerateSppPlans extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'spp:generate-plans {--academic-year= : Specific academic year ID} {--institution= : Specific institution ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate SPP activity plans based on existing student billing records';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('🔄 Generating SPP activity plans based on student billing...');
        
        // Get SPP category
        $sppCategory = Category::where('name', 'like', '%SPP%')->first();
        if (!$sppCategory) {
            $this->error('❌ SPP category not found. Please create a category with "SPP" in the name.');
            return 1;
        }
        
        // Get academic years to process
        $academicYearId = $this->option('academic-year');
        $institutionId = $this->option('institution');
        
        if ($academicYearId) {
            $academicYears = AcademicYear::where('id', $academicYearId)->get();
        } else {
            $academicYears = AcademicYear::where('status', 'active')->get();
        }
        
        if ($academicYears->isEmpty()) {
            $this->error('❌ No active academic years found.');
            return 1;
        }
        
        $sppService = new SppFinancialService();
        $totalPlans = 0;
        
        foreach ($academicYears as $academicYear) {
            $this->info("📅 Processing Academic Year: {$academicYear->year_start}/{$academicYear->year_end}");
            
            // Get institutions to process
            if ($institutionId) {
                $institutions = Institution::where('id', $institutionId)->get();
            } else {
                $institutions = Institution::where('is_active', true)->get();
            }
            
            foreach ($institutions as $institution) {
                $this->info("🏫 Processing Institution: {$institution->name}");
                
                // Get all class levels for this institution
                $levels = ClassModel::where('institution_id', $institution->id)
                    ->where('is_active', true)
                    ->distinct()
                    ->pluck('level')
                    ->filter()
                    ->values();
                
                foreach ($levels as $level) {
                    // Check if students exist for this combination
                    $studentCount = Student::where('institution_id', $institution->id)
                        ->where('academic_year_id', $academicYear->id)
                        ->whereHas('classRoom', function ($query) use ($level) {
                            $query->where('level', $level);
                        })
                        ->count();
                    
                    if ($studentCount == 0) {
                        $this->warn("⚠️  No students found for {$institution->name} - Level {$level}");
                        continue;
                    }
                    
                    // Calculate budget for this level
                    $budget = $sppService->calculatePlanBudget($institution->id, $academicYear->id, $level);
                    
                    if ($budget <= 0) {
                        $this->warn("⚠️  No budget calculated for {$institution->name} - Level {$level}");
                        continue;
                    }
                    
                    // Create or update activity plan
                    $planName = "Penerimaan SPP ({$institution->name}) ({$level})";
                    
                    $plan = ActivityPlan::updateOrCreate(
                        [
                            'academic_year_id' => $academicYear->id,
                            'category_id' => $sppCategory->id,
                            'institution_id' => $institution->id,
                            'level' => $level,
                        ],
                        [
                            'name' => $planName,
                            'budget_amount' => $budget,
                            'start_date' => $academicYear->year_start . '-07-01',
                            'end_date' => $academicYear->year_end . '-06-30',
                            'description' => "Rencana penerimaan SPP untuk {$institution->name} tingkat {$level} tahun ajaran {$academicYear->year_start}/{$academicYear->year_end}",
                            'unit_price' => 0,
                            'equivalent_1' => 0,
                            'equivalent_2' => 0,
                            'equivalent_3' => 0,
                            'unit_1' => '',
                            'unit_2' => '',
                            'unit_3' => '',
                        ]
                    );
                    
                    $this->info("✅ Created/Updated: {$planName} - Budget: " . number_format($budget, 0, ',', '.'));
                    $totalPlans++;
                }
            }
        }
        
        $this->info("🎉 Successfully generated {$totalPlans} SPP activity plans!");
        $this->info("💡 You can now check the activity plans at: /activity-plans");
        
        return 0;
    }
}
