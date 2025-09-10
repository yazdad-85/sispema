<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBillingRecordsTable extends Migration
{
    public function up()
    {
        Schema::create('billing_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->foreignId('fee_structure_id')->constrained()->onDelete('cascade');
            $table->string('origin_year');
            $table->string('origin_class');
            $table->decimal('amount', 12, 2);
            $table->decimal('remaining_balance', 12, 2);
            $table->enum('status', ['active', 'partially_paid', 'fully_paid', 'overdue'])->default('active');
            $table->date('due_date');
            $table->string('billing_month');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('billing_records');
    }
}
