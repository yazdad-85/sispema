<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ResetSystemData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sispema:reset-data 
                            {--force : Skip confirmation prompt}
                            {--keep-academic-years : Keep academic years data}
                            {--keep-classes : Keep classes data}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset all system data except users, institutions, and scholarship categories';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('🔄 SISPEMA Data Reset Tool');
        $this->info('========================');
        
        // Show what will be kept
        $this->info('✅ Data yang AKAN DIPERTAHANKAN:');
        $this->line('   - Users (Pengguna)');
        $this->line('   - Institutions (Lembaga)');
        $this->line('   - Scholarship Categories (Kategori Beasiswa)');
        
        // Show what will be deleted
        $this->warn('❌ Data yang AKAN DIHAPUS:');
        $this->line('   - Students (Siswa)');
        $this->line('   - Classes (Kelas) - kecuali jika --keep-classes');
        $this->line('   - Academic Years (Tahun Ajaran) - kecuali jika --keep-academic-years');
        $this->line('   - Fee Structures (Struktur Biaya)');
        $this->line('   - Billing Records (Catatan Penagihan)');
        $this->line('   - Payments (Pembayaran)');
        $this->line('   - Activity Plans (Rencana Kegiatan)');
        $this->line('   - Activity Realizations (Realisasi Kegiatan)');
        $this->line('   - Cash Book (Buku Kas)');
        $this->line('   - App Settings (Pengaturan Aplikasi)');
        $this->line('   - Categories (Kategori)');
        
        // Confirmation
        if (!$this->option('force')) {
            if (!$this->confirm('Apakah Anda yakin ingin menghapus semua data tersebut?')) {
                $this->info('❌ Operasi dibatalkan.');
                return 0;
            }
        }
        
        try {
            $this->info('🚀 Memulai proses reset data...');
            
            // Disable foreign key checks temporarily
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            
            // Tables to delete (in order to respect foreign key constraints)
            $tablesToDelete = [
                'payment_allocations',
                'digital_payments', 
                'cash_book',
                'activity_realizations', 
                'activity_plans',
                'payments',
                'billing_records',
                'fee_structures',
                'students',
            ];
            
            // Add optional tables based on options
            if (!$this->option('keep-classes')) {
                $tablesToDelete[] = 'classes';
            }
            
            if (!$this->option('keep-academic-years')) {
                $tablesToDelete[] = 'academic_years';
            }
            
            $tablesToDelete = array_merge($tablesToDelete, [
                'app_settings',
                'categories',
            ]);
            
            // Delete main data tables
            foreach ($tablesToDelete as $table) {
                try {
                    $count = DB::table($table)->count();
                    if ($count > 0) {
                        $this->line("🗑️  Menghapus {$count} records dari {$table}...");
                        DB::table($table)->truncate();
                    }
                } catch (\Exception $e) {
                    $this->warn("⚠️  Gagal menghapus {$table}: " . $e->getMessage());
                    // Continue with other tables
                }
            }
            
            // Re-enable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            
            $this->info('✅ Reset data berhasil!');
            $this->line('');
            $this->info('📊 Data yang tersisa:');
            
            // Show remaining data counts
            $userCount = DB::table('users')->count();
            $institutionCount = DB::table('institutions')->count();
            $scholarshipCount = DB::table('scholarship_categories')->count();
            
            $this->line("   - Users: {$userCount}");
            $this->line("   - Institutions: {$institutionCount}");
            $this->line("   - Scholarship Categories: {$scholarshipCount}");
            
            if ($this->option('keep-academic-years')) {
                $academicYearCount = DB::table('academic_years')->count();
                $this->line("   - Academic Years: {$academicYearCount} (dipilih untuk dipertahankan)");
            }
            
            if ($this->option('keep-classes')) {
                $classCount = DB::table('classes')->count();
                $this->line("   - Classes: {$classCount} (dipilih untuk dipertahankan)");
            }
            
            $this->line('');
            $this->info('🎉 Sistem siap untuk data baru!');
            
            // Log the reset
            Log::info('System data reset completed', [
                'user_id' => auth()->id() ?? 'system',
                'kept_users' => $userCount,
                'kept_institutions' => $institutionCount,
                'kept_scholarship_categories' => $scholarshipCount,
                'keep_academic_years' => $this->option('keep-academic-years'),
                'keep_classes' => $this->option('keep-classes'),
            ]);
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error('❌ Terjadi kesalahan saat reset data:');
            $this->error($e->getMessage());
            
            Log::error('System data reset failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return 1;
        }
    }
}
