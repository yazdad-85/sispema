<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SppFinancialService;

class ProcessExistingSppPayments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'spp:process-existing-payments {--dry-run : Show what would be processed without actually processing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process all existing SPP payments and create financial records';

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

        $this->info('🚀 Starting SPP Financial Integration...');
        
        $sppFinancialService = new SppFinancialService();
        
        if ($isDryRun) {
            // Count payments that would be processed
            $payments = \App\Models\Payment::where('status', 'completed')
                ->whereDoesntHave('cashBookEntries')
                ->count();
                
            $this->info("📊 Found {$payments} payments to process");
            return 0;
        }

        $result = $sppFinancialService->processAllExistingPayments();
        
        $this->info("✅ Processing completed!");
        $this->info("📊 Processed: {$result['processed']} payments");
        $this->info("❌ Errors: {$result['errors']} payments");
        $this->info("📈 Total: {$result['total']} payments");
        
        if ($result['errors'] > 0) {
            $this->warn("⚠️  Some payments failed to process. Check logs for details.");
            return 1;
        }
        
        return 0;
    }
}
