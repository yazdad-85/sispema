<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AcademicYear;
use App\Models\FeeStructure;
use App\Models\Institution;

class TestAutoCopyFeeStructure extends Command
{
    protected $signature = 'test:auto-copy-fee-structure';
    protected $description = 'Test sistem otomatis copy fee structure saat tahun ajaran baru dibuat';

    public function handle()
    {
        $this->info('🧪 Testing Auto Copy Fee Structure...');
        
        // Cari tahun ajaran yang ada
        $currentYear = AcademicYear::where('is_current', true)->first();
        if (!$currentYear) {
            $this->error('❌ Tidak ada tahun ajaran aktif');
            return;
        }
        
        $this->info("📅 Current Academic Year: {$currentYear->name}");
        
        // Hitung data sebelum test
        $feeStructuresBefore = FeeStructure::count();
        $academicYearsBefore = AcademicYear::count();
        
        $this->info("\n📈 Data sebelum test:");
        $this->info("   Academic Years: {$academicYearsBefore}");
        $this->info("   Fee Structures: {$feeStructuresBefore}");
        
        // Buat tahun ajaran baru untuk test
        $newYear = AcademicYear::create([
            'year_start' => 2027,
            'year_end' => 2028,
            'name' => '2027/2028',
            'is_current' => false,
            'description' => 'Test Auto Copy Fee Structure'
        ]);
        
        $this->info("\n✅ New academic year created: {$newYear->name}");
        
        // Cek hasil
        $feeStructuresAfter = FeeStructure::count();
        $academicYearsAfter = AcademicYear::count();
        
        $this->info("\n📈 Data setelah test:");
        $this->info("   Academic Years: {$academicYearsAfter} (+" . ($academicYearsAfter - $academicYearsBefore) . ")");
        $this->info("   Fee Structures: {$feeStructuresAfter} (+" . ($feeStructuresAfter - $feeStructuresBefore) . ")");
        
        // Cek fee structures untuk tahun baru
        $newYearFeeStructures = FeeStructure::where('academic_year_id', $newYear->id)->count();
        $this->info("\n🔍 Fee structures for new year: {$newYearFeeStructures}");
        
        if ($newYearFeeStructures > 0) {
            $this->info("✅ Auto copy fee structure working!");
            
            // Tampilkan detail
            $this->info("\n📋 Fee structures created:");
            $feeStructures = FeeStructure::where('academic_year_id', $newYear->id)
                ->with(['institution', 'class'])
                ->get();
            
            foreach ($feeStructures as $fs) {
                $this->info("   - {$fs->institution->name} - {$fs->class->class_name}: Rp " . number_format($fs->monthly_amount, 0, ',', '.'));
            }
        } else {
            $this->error("❌ Auto copy fee structure failed!");
        }
        
        // Hapus tahun ajaran test
        $newYear->delete();
        $this->info("\n🧹 Test academic year cleaned up");
        
        $this->info("\n🎉 Test completed!");
    }
}
