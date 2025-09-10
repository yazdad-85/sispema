<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Institution;

class UpdateInstitutionStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'institutions:update-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update institution status to active if is_active is null';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $institutions = Institution::whereNull('is_active')->get();
        
        if ($institutions->count() > 0) {
            foreach ($institutions as $institution) {
                $institution->update(['is_active' => true]);
                $this->info("Updated institution: {$institution->name}");
            }
            $this->info("Total {$institutions->count()} institutions updated.");
        } else {
            $this->info("No institutions need updating.");
        }
        
        return 0;
    }
}
