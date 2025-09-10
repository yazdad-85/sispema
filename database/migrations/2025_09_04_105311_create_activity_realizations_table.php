<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateActivityRealizationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('activity_realizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained('activity_plans')->onDelete('cascade');
            $table->date('date');
            $table->text('description');
            $table->enum('transaction_type', ['debit', 'credit']);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('equivalent_1', 10, 2)->default(0);
            $table->decimal('equivalent_2', 10, 2)->default(0);
            $table->decimal('equivalent_3', 10, 2)->default(0);
            $table->decimal('total_amount', 15, 2);
            $table->string('proof')->nullable();
            $table->enum('status', ['draft', 'confirmed'])->default('draft');
            $table->boolean('is_auto_generated')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('activity_realizations');
    }
}
