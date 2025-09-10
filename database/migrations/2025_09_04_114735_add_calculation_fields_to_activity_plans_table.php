<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCalculationFieldsToActivityPlansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('activity_plans', function (Blueprint $table) {
            $table->decimal('unit_price', 15, 2)->nullable()->after('budget_amount');
            $table->decimal('equivalent_1', 10, 2)->nullable()->after('unit_price');
            $table->decimal('equivalent_2', 10, 2)->nullable()->after('equivalent_1');
            $table->decimal('equivalent_3', 10, 2)->nullable()->after('equivalent_2');
            $table->string('unit_1', 50)->nullable()->after('equivalent_3');
            $table->string('unit_2', 50)->nullable()->after('unit_1');
            $table->string('unit_3', 50)->nullable()->after('unit_2');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('activity_plans', function (Blueprint $table) {
            $table->dropColumn(['unit_price', 'equivalent_1', 'equivalent_2', 'equivalent_3', 'unit_1', 'unit_2', 'unit_3']);
        });
    }
}
