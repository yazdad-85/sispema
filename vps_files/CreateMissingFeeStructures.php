<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ClassModel;
use App\Models\FeeStructure;
use App\Models\Institution;
use App\Models\AcademicYear;

class CreateMissingFeeStructures extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fee:create-missing {--dry-run : Show what would be created without actually creating}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create missing fee structures for all classes';

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

        $this->info('🚀 Creating missing fee structures...');
        
        $classes = ClassModel::with(['institution', 'academicYear'])->get();
        $this->info("📊 Found {$classes->count()} classes to check");
        
        $created = 0;
        $skipped = 0;
        $errors = 0;
        
        foreach ($classes as $class) {
            try {
                // Check if fee structure already exists for this class
                $existingFeeStructure = FeeStructure::where('institution_id', $class->institution_id)
                    ->where('academic_year_id', $class->academic_year_id)
                    ->where('class_id', $class->id)
                    ->first();
                
                if ($existingFeeStructure) {
                    $skipped++;
                    continue;
                }
                
                if ($isDryRun) {
                    $this->line("Would create fee structure for: {$class->class_name} (Institution: {$class->institution_id}, Academic Year: {$class->academic_year_id})");
                    $created++;
                    continue;
                }
                
                // Create fee structure
                FeeStructure::create([
                    'institution_id' => $class->institution_id,
                    'academic_year_id' => $class->academic_year_id,
                    'class_id' => $class->id,
                    'monthly_amount' => 375000, // 4,500,000 / 12
                    'yearly_amount' => 4500000,
                    'scholarship_discount' => 0,
                    'description' => "Struktur biaya untuk {$class->class_name}",
                    'is_active' => true,
                ]);
                
                $created++;
                
                if ($created % 50 == 0) {
                    $this->info("Processed {$created} fee structures...");
                }
                
            } catch (\Exception $e) {
                $errors++;
                $this->error("Failed to create fee structure for class {$class->id}: " . $e->getMessage());
            }
        }
        
        $this->info("✅ Fee structures creation completed!");
        $this->info("📊 Created: {$created} fee structures");
        $this->info("⏭️  Skipped: {$skipped} fee structures (already exist)");
        $this->info("❌ Errors: {$errors} fee structures");
        
        if ($errors > 0) {
            $this->warn("⚠️  Some fee structures failed to create. Check logs for details.");
            return 1;
        }
        
        return 0;
    }
}
