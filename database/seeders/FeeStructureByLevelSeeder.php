<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FeeStructure;
use App\Models\Institution;
use App\Models\AcademicYear;
use App\Models\ClassModel;

class FeeStructureByLevelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->command->info('Creating fee structures based on class levels...');
        
        $institutions = Institution::all();
        $academicYears = AcademicYear::where('is_current', true)->get();
        
        if ($academicYears->isEmpty()) {
            $this->command->warn('No active academic year found. Please set an academic year as current first.');
            return;
        }
        
        $academicYear = $academicYears->first();
        $this->command->info("Using academic year: {$academicYear->year_start}-{$academicYear->year_end}");
        
        $levels = ['VII', 'VIII', 'IX', 'X', 'XI', 'XII'];
        $created = 0;
        
        foreach ($institutions as $institution) {
            $this->command->info("Processing institution: {$institution->name}");
            
            foreach ($levels as $level) {
                // Cek apakah sudah ada struktur biaya untuk level ini
                $existingFeeStructure = FeeStructure::where('institution_id', $institution->id)
                    ->where('academic_year_id', $academicYear->id)
                    ->whereHas('class', function($query) use ($level) {
                        $query->where('level', $level);
                    })
                    ->first();
                
                if ($existingFeeStructure) {
                    $this->command->info("Fee structure for level {$level} already exists in {$institution->name}");
                    continue;
                }
                
                // Ambil kelas pertama dengan level yang sesuai
                $class = ClassModel::where('institution_id', $institution->id)
                    ->where('level', $level)
                    ->first();
                
                if (!$class) {
                    $this->command->warn("No class found for level {$level} in {$institution->name}");
                    continue;
                }
                
                // Buat struktur biaya berdasarkan level
                $monthlyAmount = $this->getMonthlyAmountByLevel($level);
                $yearlyAmount = $monthlyAmount * 12;
                
                FeeStructure::create([
                    'institution_id' => $institution->id,
                    'academic_year_id' => $academicYear->id,
                    'class_id' => $class->id,
                    'monthly_amount' => $monthlyAmount,
                    'yearly_amount' => $yearlyAmount,
                    'scholarship_discount' => 0,
                    'description' => "Struktur biaya untuk tingkat {$level}",
                    'is_active' => true,
                ]);
                
                $created++;
                $this->command->info("Created fee structure for level {$level} in {$institution->name}: Rp " . number_format($monthlyAmount, 0, ',', '.') . "/bulan");
            }
        }
        
        $this->command->info("Successfully created {$created} fee structures based on class levels.");
    }
    
    /**
     * Get monthly amount based on class level
     */
    private function getMonthlyAmountByLevel($level)
    {
        switch ($level) {
            case 'VII':
            case 'X':
                return 500000; // Tingkat 1: Rp 500.000/bulan
            case 'VIII':
            case 'XI':
                return 550000; // Tingkat 2: Rp 550.000/bulan
            case 'IX':
            case 'XII':
                return 600000; // Tingkat 3: Rp 600.000/bulan
            default:
                return 500000;
        }
    }
}
