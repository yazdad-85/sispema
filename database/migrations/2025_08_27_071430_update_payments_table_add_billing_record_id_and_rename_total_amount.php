<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdatePaymentsTableAddBillingRecordIdAndRenameTotalAmount extends Migration
{
    public function up()
    {
        Schema::table('payments', function (Blueprint $table) {
            // Add billing_record_id column
            $table->foreignId('billing_record_id')->nullable()->constrained()->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('payments', function (Blueprint $table) {
            // Remove billing_record_id column
            $table->dropForeign(['billing_record_id']);
            $table->dropColumn('billing_record_id');
        });
    }
}
