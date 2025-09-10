<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class WipeDataExceptCore extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:wipe-data 
                            {--yes : Run without interactive confirmation} 
                            {--keep= : Comma separated extra table names to keep (in addition to core whitelist)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete all data from the database except core tables: users, institutions, and institution_user (configurable)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $connection = DB::connection();
        $driver = $connection->getDriverName();

        // Base whitelist to preserve
        $whitelist = [
            'migrations',          // never truncate
            'users',
            'institutions',
            'institution_user',    // keep pivot user-institution relations
        ];

        // Allow user to keep more tables via --keep option
        $extra = $this->option('keep');
        if ($extra) {
            foreach (explode(',', $extra) as $t) {
                $t = trim($t);
                if ($t !== '') {
                    $whitelist[] = $t;
                }
            }
        }

        $whitelist = array_values(array_unique(array_map('strtolower', $whitelist)));

        // Get all tables
        $databaseName = $connection->getDatabaseName();
        if ($driver === 'mysql') {
            // Try information_schema first
            $rows = collect(DB::select('SELECT table_name FROM information_schema.tables WHERE table_schema = ? AND table_type = ?;', [$databaseName, 'BASE TABLE']));
            $tables = $rows->pluck('table_name')->filter()->map(fn($t) => (string) $t)->values()->all();

            // Fallback to SHOW FULL TABLES (column name is dynamic e.g. "Tables_in_dbname")
            if (empty($tables)) {
                $rows = DB::select('SHOW FULL TABLES WHERE Table_type = "BASE TABLE";');
                if (!empty($rows)) {
                    $first = (array) $rows[0];
                    $firstKey = array_key_first($first); // e.g. Tables_in_mydb
                    $tables = collect($rows)
                        ->map(function ($row) use ($firstKey) {
                            $arr = (array) $row;
                            return isset($arr[$firstKey]) ? (string) $arr[$firstKey] : '';
                        })
                        ->filter()
                        ->values()
                        ->all();
                }
            }
        } elseif ($driver === 'sqlite') {
            $tables = collect(DB::select("SELECT name FROM sqlite_master WHERE type='table';"))
                ->pluck('name')
                ->map(fn($t) => (string) $t)
                ->values()
                ->all();
        } else {
            $this->error("Unsupported DB driver: {$driver}");
            return self::FAILURE;
        }

        // Determine tables to truncate
        $toTruncate = collect($tables)
            ->filter(function ($table) use ($whitelist) {
                $name = strtolower($table);
                return !in_array($name, $whitelist, true);
            })
            ->values()
            ->all();

        if (empty($toTruncate)) {
            $this->info('No tables to truncate. Nothing to do.');
            return self::SUCCESS;
        }

        $this->warn('The following tables will be TRUNCATED (all rows deleted):');
        foreach ($toTruncate as $t) {
            $this->line(" - {$t}");
        }

        if (!$this->option('yes')) {
            if (!$this->confirm('Are you sure you want to proceed? This action cannot be undone.', false)) {
                $this->info('Aborted');
                return self::SUCCESS;
            }
        }

        // Disable FKs, truncate, then re-enable
        DB::beginTransaction();
        try {
            if ($driver === 'mysql') {
                DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            } elseif ($driver === 'sqlite') {
                DB::statement('PRAGMA foreign_keys = OFF;');
            }

            foreach ($toTruncate as $table) {
                // Use DELETE for sqlite to avoid issues with TRUNCATE and foreign keys
                if ($driver === 'sqlite') {
                    DB::table($table)->delete();
                    // Reset autoincrement
                    DB::statement("DELETE FROM sqlite_sequence WHERE name = ?", [$table]);
                } else {
                    DB::table($table)->truncate();
                }
            }

            if ($driver === 'mysql') {
                DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            } elseif ($driver === 'sqlite') {
                DB::statement('PRAGMA foreign_keys = ON;');
            }

            DB::commit();
            $this->info('Data wiped successfully. Preserved tables: '.implode(', ', $whitelist));
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Failed to wipe data: '.$e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}


