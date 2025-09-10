<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB; // Added this import for DB facade

class RemoveEdcPaymentMethods extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Update existing EDC payments to use 'cash' as default
        DB::table('payments')
            ->where('payment_method', 'edc')
            ->update(['payment_method' => 'cash']);
        
        // Update the enum constraint in the database
        // Note: This might require manual database alteration in some cases
        // as Laravel doesn't easily support enum modifications
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // If we need to rollback, we could restore EDC payments
        // But since we're removing it completely, this might not be necessary
    }
}
