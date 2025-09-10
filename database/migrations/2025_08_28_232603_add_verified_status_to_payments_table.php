<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB; // Added this import for DB facade

class AddVerifiedStatusToPaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Note: Laravel doesn't easily support enum modifications
        // We need to use raw SQL to modify the enum
        DB::statement("ALTER TABLE payments MODIFY COLUMN status ENUM('pending', 'verified', 'completed', 'failed', 'cancelled') DEFAULT 'completed'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Revert back to original enum
        DB::statement("ALTER TABLE payments MODIFY COLUMN status ENUM('pending', 'completed', 'failed', 'cancelled') DEFAULT 'completed'");
    }
}
