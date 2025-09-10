<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\ClassModel;

class UpdateClassesLevelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->command->info('Updating class levels based on class names...');
        
        $classes = ClassModel::all();
        $updated = 0;
        
        foreach ($classes as $class) {
            $level = ClassModel::getLevelFromClassName($class->class_name);
            
            if ($level) {
                $class->level = $level;
                $class->save();
                $updated++;
                $this->command->info("Updated {$class->class_name} -> Level: {$level}");
            } else {
                $this->command->warn("Could not determine level for: {$class->class_name}");
            }
        }
        
        $this->command->info("Successfully updated {$updated} classes with level information.");
    }
}
